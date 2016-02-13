<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Route;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Log;
use Auth;
use Response;
use App\Libraries\Packet as Packet;
use App\Libraries\Helper as Helper;
use App\Libraries\Player as Player;

class Index extends Controller
{
    private $time_start = 0;
    private $time_end = 0;
    private $time = 0;
    private $route = '';

    public function __construct()
    {
        $this->time_start = microtime(true);
    }

    public function __destruct()
    {
        $this->time_end = microtime(true);
        $this->time = ($this->time_end - $this->time_start)*1000;
        if(config('bancho.debug') === true)
            Log::info(sprintf("%s Total Execution Time: %d milliseconds", $this->route, $this->time));
    }

    public function getIndex()
    {
        return view('welcome');
    }

    public function postIndex(Request $request)
    {
        $osutoken = $request->header('osu-token');
        //Is it a login request?
        if(!isset($osutoken))
        {
            $content = explode("\n", $request->getContent());
            $extraData = explode("|", $content[2]);
            return $this->loginFunction($content[0], $content[1], $extraData[0]);
        }
        $player = new Player();
        $packet = new Packet();
        $userID = $player->getIDfromToken($osutoken, true);
        $player->updateToken($osutoken, $userID);
        $body = $request->getContent();
        $this->route = sprintf('%s packet %s', Route::getCurrentRoute()->getActionName(), unpack('C',$body)[1]);
        //$packet->debug($body);
        $output = $packet->check($body, $userID, $osutoken);
        return response()->make($output)->withHeaders(['cho-protocol' => config('bancho.ProtocolVersion') ,'Connection' => 'Keep-Alive']);
    }

    function loginFunction($username, $hash)
    {
        $this->route = Route::getCurrentRoute()->getActionName();
        if(Auth::attempt(['name' => $username, 'password' => $hash])) {
            $packet = new Packet();
            $helper = new Helper();
            $player = new Player();
            $user = Auth::user();
            Log::info($username . " has logged in");

            $output = array_merge(
                $packet->create(92, $user->bantime),	//ban status/time
                $packet->create(5, $user->id),	//user id
                $packet->create(75, config('bancho.ProtocolVersion')),	//bancho protocol version
                $packet->create(71, $user->usergroup),	//user rank (supporter etc)
                //$packet->create(72, array(3, 4)),	//friend list
                $packet->create(83, $player->getData($user)),
                $packet->create(11, $player->getDataDetailed($user)),
                $packet->create(89, null),
                //foreach player online, packet 12 or 95
                $packet->create(64, '#osu'),	//main channel
                $packet->create(64, '#news'),
                $packet->create(65, array('#osu', 'Main channel', 2147483647 - 1)),	//secondary channel
                $packet->create(65, array('#news', 'This will contain announcements and info, while beta lasts.', 1)),
                $packet->create(65, array('#kfc', 'Kawaii friends club', 0)),	//secondary channel
                $packet->create(65, array('#aqn', 'cuz fuck yeah', 1337)),
                $packet->create(07, array('KaiBanchoo', 'This is a test message! First step to getting chat working!', '#osu', 2))
            );
            $token = $helper->generateToken();
            $player->setToken($token, $user);
            return response()->make(implode(array_map("chr", $output)))->header('cho-token', $token);
        } else {
            Log::info($username . " has failed to logged in");
        }
        return '';
    }

}
