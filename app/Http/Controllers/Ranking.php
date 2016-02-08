<?php

namespace App\Http\Controllers;

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Log;
use DB;
use Response;
use App\Libraries\Packet as Packet;
use App\Libraries\Helper as Helper;
use App\Libraries\Player as Player;
use Crypt;

class Ranking extends Controller
{
    public function getScores(Request $request)
    {
        $helper = new Helper();
        $output = "2|"; //2 = Approved, 0 = Unapproved
        $output .= "false|"; //Need more info
        $output .= "1|"; //Beatmap ID (You'll need to have a full database of Beatmap Set ID's with Beatmap ID's)
        $output .= sprintf("%s|",$request->query("i")); //Beatmap Set ID
        $output .= sprintf("%d", DB::table('rankings')->where('beatmapID', '=', $request->query("c"))->count()); //How many ranks are in the table for the ID Difficulty Hash
        $output .= "\n";
        $output .= "0"; //Something, idk
        $output .= "\n";
        $output .= sprintf("[bold:0,size:20]%s|\n",str_replace(".osu","",$request->query("f"))); //Sets text size and name for viewing
        $output .= "0"; //What is this, its not difficulty, not rating, might as well set to 0
        $output .= "\n";
        //$selfrank = Ranking::where('user', $request->query("us"))->where('beatmapID', $request->query("c"));
        $selfrank = DB::table('rankings')->select('id','user','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods','avatarID', DB::raw('FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM rankings )) AS rank'),'timestamp')->where('user', '=', $request->query("us"))->where('beatmapID', '=', $request->query("c"))->orderBy('rank','asc')->first();
        if(!is_null($selfrank))
        {
            $output .= $helper->scoreString($selfrank->id, $selfrank->user, $selfrank->score, $selfrank->combo, $selfrank->count50, $selfrank->count100, $selfrank->count300, $selfrank->countMiss, $selfrank->countKatu, $selfrank->countGeki, $selfrank->fc, $selfrank->mods, $selfrank->avatarID, $selfrank->rank, $selfrank->timestamp);
        }
        else
        {
            $output .= "\n";
        }
        $ranking = DB::table('rankings')->select('id','user','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods','avatarID', DB::raw('FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM rankings )) AS rank'),'timestamp')->where('beatmapID', '=', $request->query("c"))->orderBy('rank','asc')->limit(50)->get();
        foreach ($ranking as $rank)
        {
            $output .= $helper->scoreString($rank->id, $rank->user, $rank->score, $rank->combo, $rank->count50, $rank->count100, $rank->count300, $rank->countMiss, $rank->countKatu, $rank->countGeki, $rank->fc, $rank->mods, $rank->avatarID, $rank->rank, $rank->timestamp);
        }
        return $output;
    }

    public function submitModular(Request $request)
    {
        $helper = new Helper();
        $score = explode(":", $helper->decrypt($request->input('score'), $request->input('iv')));
        Log::info($score);
        $player = DB::table('users')->select('id', 'total_score')->where('name', '=', $score[1])->first();
        if($score[14] == "True") {
            DB::table('rankings')->insert([
                'beatmapID' => $score[0],
                'user' => $score[1],
                'score' => $score[9],
                'combo' => $score[10],
                'count50' => $score[5],
                'count100' => $score[4],
                'count300' => $score[3],
                'countMiss' => $score[8],
                'countKatu' => $score[7],
                'countGeki' => $score[6],
                'fc' => $score[11],
                'mods' => $score[13],
                'avatarID' => $player->id,
                'timestamp' => $score[17]
            ]);
        }
        DB::table('users')->where('id', '=', $player->id)->update(['total_score' => $player->total_score + $score[9]]);
        return "";
    }
}