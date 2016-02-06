<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Log;
use Cache;
use Auth;
use Response;
use App\Libraries\Packet as Packet;
use App\Libraries\Helper as Helper;
use App\Libraries\Player as Player;

class Index extends Controller
{
    public function getIndex(Request $request)
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
        $packet = new Packet();
        $user = Cache::tags(['user'])->get($osutoken);
        Cache::tags(['user'])->put($osutoken, $user, 1);
        $body = $request->getContent();
        $output = $packet->check(unpack('C*', $body), $user, $osutoken);
        return $output;
    }

    function loginFunction($username, $hash, $version)
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
                $packet->create(83, array(	//local player
                    'id' => $user->id,
                    'playerName' => $user->name,
                    'utcOffset' => 0 + 24,
                    'country' => $user->country,
                    'playerRank' => 0,
                    'longitude' => 0,
                    'latitude' => 0,
                    'globalRank' => 0,
                )),
                $packet->create(11, array(		//more local player data
                    'id' => $user->id,
                    'bStatus' => 0,		//byte
                    'string0' => '',	//String
                    'string1' => '',	//string
                    'mods' => 0,		//int
                    'playmode' => 0,	//byte
                    'int0' => 0,		//int
                    'score' => $user->total_score,			//long 	score
                    'accuracy' => $user->accuracy,	//float accuracy
                    'playcount' => 0,			//int playcount
                    'experience' => 0,			//long 	experience
                    'int1' => 0,	//int 	global rank?
                    'pp' => $user->pp_raw,			//short	pp 				if set, will use?
                )),
                $packet->create(89, null),
                //foreach player online, packet 12 or 95
                $packet->create(64, '#osu'),	//main channel
                $packet->create(64, '#news'),
                $packet->create(65, array('#osu', 'Main channel', 2147483647 - 1)),	//secondary channel
                $packet->create(65, array('#news', 'This will contain announcements and info, while beta lasts.', 1)),
                $packet->create(65, array('#kfc', 'Kawaii friends club', 0)),	//secondary channel
                $packet->create(65, array('#aqn', 'cuz fuck yeah', 1337)),
                $packet->create(07, array('KaiBanchoo', 'This is a test message! First step to getting chat working!', '#osu', 3))
            );
            $token = $helper->generateToken();
            Cache::tags(['user'])->put($token, $user, 1); //Only needs it for 1 min, since we are going to refresh the data constantly if the player is connected.
            if(Cache::has('currentLogin'))
            {
                $currentLogin = Cache::get('currentLogin');
                Cache::put('currentLogin', array_merge($currentLogin, array($token)), 60);
            } else {
                Cache::put('currentLogin', array($token), 60);
            }
            return Response::make(implode(array_map("chr", $output)), 200, ['cho-token' => $token]);
        } else {
            Log::info($username . " has failed to logged in");
        }
        return '';
    }

}
