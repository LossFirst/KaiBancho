<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $user = $player->getDatafromToken($osutoken);
        $player->updateToken($osutoken, $user);
        $body = $request->getContent();
        $output = $packet->check(unpack('C*', $body), $user, $osutoken);
        return $output;
    }

    function loginFunction($username, $hash)
    {
        if(Auth::attempt(['name' => $username, 'password' => $hash])) {
            $packet = new Packet();
            $helper = new Helper();
            $player = new Player();
            $user = Auth::user();
            Log::info($username . " has logged in");

            $output = array_merge(
                $packet->create(92, $user->bantime),	//ban status/time
                $packet->create(5, $user->id),	//user id
                $packet->create(75, 19),	//bancho protocol version
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
            return Response::make(implode(array_map("chr", $output)), 200, ['cho-token' => $token]);
        } else {
            Log::info($username . " has failed to logged in");
        }
        return '';
    }

}
