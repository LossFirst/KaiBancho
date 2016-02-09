<?php

namespace App\Http\Controllers;

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Log;
use DB;
use App\Libraries\Helper as Helper;
use App\User;

class Ranking extends Controller
{
    public function getScores(Request $request)
    {
        $helper = new Helper();
        $output = "2|"; //2 = Approved, 0 = Unapproved
        $output .= "false|"; //Need more info
        $output .= "1|"; //Beatmap ID (You'll need to have a full database of Beatmap Set ID's with Beatmap ID's)
        $output .= sprintf("%s|",$request->query("i")); //Beatmap Set ID
        $output .= sprintf("%d", DB::table('osu_scores')->where('beatmapHash', '=', $request->query("c"))->count()); //How many ranks are in the table for the ID Difficulty Hash
        $output .= "\n";
        $output .= "0"; //Something, idk
        $output .= "\n";
        $output .= sprintf("[bold:0,size:20]%s|\n",str_replace(".osu","",$request->query("f"))); //Sets text size and name for viewing
        $output .= "0"; //What is this, its not difficulty, not rating, might as well set to 0
        $output .= "\n";
        $user = User::where('name', $request->query("us"))->first();
        //$selfrank = Ranking::where('user', $request->query("us"))->where('beatmapID', $request->query("c"));
        $selfrank = DB::table('osu_scores')->select('id','user_id','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods', DB::raw('FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM osu_scores )) AS rank'),'created_at')->where('user_id', '=', $user->id)->where('beatmapHash', '=', $request->query("c"))->orderBy('rank','asc')->first();
        if(!is_null($selfrank))
        {
            $output .= $helper->scoreString($selfrank->id, $user->name, $selfrank->score, $selfrank->combo, $selfrank->count50, $selfrank->count100, $selfrank->count300, $selfrank->countMiss, $selfrank->countKatu, $selfrank->countGeki, $selfrank->fc, $selfrank->mods, $selfrank->user_id, $selfrank->rank, strtotime($selfrank->created_at));
        }
        else
        {
            $output .= "\n";
        }
        $ranking = DB::table('osu_scores')->select('id','user_id','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods', DB::raw('FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM osu_scores )) AS rank'),'created_at')->where('beatmapHash', '=', $request->query("c"))->orderBy('rank','asc')->limit(50)->get();
        foreach ($ranking as $rank)
        {
            $player = User::find($rank->user_id);
            $output .= $helper->scoreString($rank->id, $player->name, $rank->score, $rank->combo, $rank->count50, $rank->count100, $rank->count300, $rank->countMiss, $rank->countKatu, $rank->countGeki, $rank->fc, $rank->mods, $rank->user_id, $rank->rank, strtotime($rank->created_at));
        }
        return $output;
    }

    public function submitModular(Request $request)
    {
        $helper = new Helper();
        $score = explode(":", $helper->decrypt($request->input('score'), $request->input('iv')));
        $user = User::where('name', $score[1])->first();
        $passed = $score[14] === 'True' ? true: false;
        if($passed) {
            DB::table('osu_scores')->insert([
                'beatmapHash' => $score[0],
                'user_id' => $user->id,
                'score' => $score[9],
                'rank' => $score[12],
                'combo' => $score[10],
                'count50' => $score[5],
                'count100' => $score[4],
                'count300' => $score[3],
                'countMiss' => $score[8],
                'countKatu' => $score[7],
                'countGeki' => $score[6],
                'fc' => $score[11],
                'mods' => $score[13],
                'pass' => $passed,
                'checksum' => $score[16]
            ]);
            if($score[9] > $user->OsuUserStats->max_combo)
            {
                $user->OsuUserStats->max_combo = $score[10];
            }
            $user->OsuUserStats->count300 = $user->OsuUserStats->count300 + $score[3] + $score[6];
            $user->OsuUserStats->count100 = $user->OsuUserStats->count100 + $score[4] + $score[7];
            $user->OsuUserStats->count50 = $user->OsuUserStats->count50 + $score[5];
            $user->OsuUserStats->countMiss = $user->OsuUserStats->countMiss + $score[8];
            $user->OsuUserStats->ranked_score = $user->OsuUserStats->ranked_score + $score[9];
            $user->OsuUserStats->playcount = $user->OsuUserStats->playcount + 1;
        }
        $user->OsuUserStats->total_score = $user->OsuUserStats->total_score + $score[9];
        $user->OsuUserStats->save();
        return "";
    }
}