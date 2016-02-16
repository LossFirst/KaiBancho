<?php

namespace App\Http\Controllers;

use App\Libraries\Scores;
use App\OsuBeatmaps;
use Carbon\Carbon;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Route;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Log;
use DB;
use App\Libraries\Helper as Helper;
use App\User;
use Cache;
use Redis;

class Ranking extends Controller
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

    public function getScores(Request $request)
    {
        $rankingLib = new Scores();
        $this->route = Route::getCurrentRoute()->getActionName();
        $checksum = $request->query("c");
        $beatmapID = $request->query("i");
        $beatmap = $rankingLib->getBeatmapData($checksum, $beatmapID);
        $helper = new Helper();
        $output = "2|"; //-1 = Not Submitted, 0 = Pending, 1 = unknown, 2 = Ranked, 3 = Approved
        $output .= "false|"; //Need more info
        $output .= sprintf("%s|", (($beatmap === null) ? $request->query("i") : $beatmap->beatmap_id)); //Beatmap ID
        $output .= sprintf("%d|", (($beatmap === null) ? 0 : $beatmap->beatmapset_id)); //Beatmap Set ID
        $output .= sprintf("%d", DB::table('osu_scores')->where('beatmapHash', '=', $request->query("c"))->count()); //How many ranks are in the table for the ID Difficulty Hash
        $output .= "\n";
        $output .= sprintf("%s", $request->query('m')); //Mode I believe
        $output .= "\n";
        $output .= sprintf("[bold:0,size:20]%s|\n", (($beatmap === null) ?  str_replace(".osu","",$request->query("f")) : $beatmap->title)); //Sets text size and name for viewing
        $output .= sprintf("%s", (($beatmap === null) ? 0 : $beatmap->difficultyrating)); //Difficulty Rating, (Not sure how it's calculated, might as well pull it from the api)
        $output .= "\n";
        $user = User::where('name', $request->query("us"))->first();
        $selfrank = $rankingLib->getUserRanking($request->query('c'), $user, $request->query('m'));
        if(!is_null($selfrank))
        {
            $output .= $helper->scoreString($selfrank->id, $user->name, $selfrank->score, $selfrank->combo, $selfrank->count50, $selfrank->count100, $selfrank->count300, $selfrank->countMiss, $selfrank->countKatu, $selfrank->countGeki, $selfrank->fc, $selfrank->mods, $selfrank->user_id, $selfrank->rank, strtotime($selfrank->created_at));
        }
        else
        {
            $output .= "\n";
        }
        $ranking = $rankingLib->getRankings($request->query('c'), $request->query('m'));
        foreach ($ranking as $rank)
        {
            $player = User::find($rank->user_id);
            $output .= $helper->scoreString($rank->id, $player->name, $rank->score, $rank->combo, $rank->count50, $rank->count100, $rank->count300, $rank->countMiss, $rank->countKatu, $rank->countGeki, $rank->fc, $rank->mods, $rank->user_id, $rank->rank, strtotime($rank->created_at));
        }
        return $output;
    }

    public function submitModular(Request $request)
    {
        $this->route = Route::getCurrentRoute()->getActionName();
        $rankingLib = new Scores();
        $helper = new Helper();
        $score = explode(":", $helper->decrypt($request->input('score'), $request->input('iv')));
        $mods = $rankingLib->mods($score[13]);
        if ($mods->autopilot == false && $mods->autoplay == false && $mods->relax == false) {
            $beatmap = OsuBeatmaps::where('checksum', $score[0])->first();
            if ($beatmap !== null) {
                if($score[15] === "0")
                    $rankingLib->submitOsuScore($beatmap, $score, $mods);
                elseif($score[15] === "1")
                    $rankingLib->submitTaikoScore($beatmap, $score, $mods);
                elseif($score[15] === "2")
                    $rankingLib->submitCTBScore($beatmap, $score, $mods);
                else
                    $rankingLib->submitManiaScore($beatmap, $score, $mods);
            }
        }
        return "";
    }
}