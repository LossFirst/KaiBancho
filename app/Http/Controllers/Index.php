<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Route;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Log;
use Auth;
use App\Libraries\Packet as Packet;
use App\Libraries\Packets as Packets;
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
        return response()->make($output)->withHeaders(['cho-protocol' => config('bancho.ProtocolVersion'), 'cho-token' => $osutoken]);
    }

    function loginFunction($username, $hash)
    {
        $this->route = Route::getCurrentRoute()->getActionName();
        $packet = new Packet();
        $headers = [];
        $output = array();
        if(Auth::attempt(['name' => $username, 'password' => $hash])) {
            $helper = new Helper();
            $player = new Player();
            $user = Auth::user();
            Log::info($username . " has logged in");
            $player->setStatus($user->id, array('SongName' => '', 'SongChecksum' => '', 'Mode' => 0, 'Status' => 0));
            $output = array_merge(
                $packet->create(Packets::OUT_BanStatus, $user->bantime),
                $packet->create(Packets::OUT_LoginRequest, $user->id),
                $packet->create(Packets::OUT_Protocol, config('bancho.ProtocolVersion')),
                $packet->create(Packets::OUT_UserGroup, $user->usergroup),
                //$packet->create(72, array(3, 4)),	//friend list
                $packet->create(Packets::OUT_PlayerLocaleInfo, $player->getData($user)),
                $packet->create(Packets::OUT_ChannelsLoaded, null),
                $packet->create(Packets::OUT_ChannelJoined, '#osu'),
                $packet->create(Packets::OUT_ChannelJoined, '#news'),
                $packet->create(Packets::OUT_ChannelList, array('#osu', 'Main channel', 2147483647 - 1)),
                $packet->create(Packets::OUT_ChannelList, array('#news', 'This will contain announcements and info, while beta lasts.', 1))
            );
            $token = $helper->generateToken();
            $player->setToken($token, $user);
            $headers = ['cho-token' => $token];
        } else {
            Log::info($username . " has failed to logged in");
        }
        return response()->make(implode(array_map("chr", $output)))->withHeaders(array_merge(['cho-protocol' => config('bancho.ProtocolVersion')], $headers));
    }

}
