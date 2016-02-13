<?php

namespace App\Http\Controllers;

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
        $this->route = Route::getCurrentRoute()->getActionName();
        $checksum = $request->query("c");
        $beatmapID = $request->query("i");
        $beatmap = Cache::get(sprintf("%s:%s", $checksum, $beatmapID), function() use ($checksum, $beatmapID) {
            $entry = OsuBeatmaps::where('checksum', $checksum)->first();
            if($entry === null) {
                $client = new \GuzzleHttp\Client();
                $res = $client->request('GET', 'https://osu.ppy.sh/api/get_beatmaps', [
                    'query' => ['k' => config('bancho.osuAPIKey'), 'h' => sprintf("%s", $checksum)]
                ]);
                $data = json_decode($res->getBody());
                if (!empty($data)) {
                    $entry = OsuBeatmaps::create([
                        'beatmap_id' => $data[0]->beatmap_id,
                        'beatmapset_id' => $data[0]->beatmapset_id,
                        'title' => $data[0]->title,
                        'creator' => $data[0]->creator,
                        'bpm' => $data[0]->bpm,
                        'checksum' => $data[0]->file_md5,
                        'version' => $data[0]->version,
                        'total_length' => $data[0]->total_length,
                        'hit_length' => $data[0]->hit_length,
                        'countTotal' => $data[0]->max_combo,
                        'diff_drain' => $data[0]->diff_drain,
                        'diff_size' => $data[0]->diff_size,
                        'diff_overall' => $data[0]->diff_overall,
                        'diff_approach' => $data[0]->diff_approach,
                        'playmode' => $data[0]->mode,
                        'approved' => $data[0]->approved,
                        'difficultyrating' => $data[0]->difficultyrating,
                        'playcount' => 0,
                        'passcount' => 0
                    ]);
                }
            }
            Cache::forever(sprintf("%s:%s", $checksum, $beatmapID), $entry);
            return $entry;
        });
        $helper = new Helper();
        $output = "2|"; //-1 = Not Submitted, 0 = Pending, 1 = unknown, 2 = Ranked, 3 = Approved
        $output .= "false|"; //Need more info
        $output .= sprintf("%d|", (($beatmap === null) ? 0 : $beatmap->beatmapset_id)); //Beatmap Set ID
        $output .= sprintf("%s|", (($beatmap === null) ? $request->query("i") : $beatmap->beatmap_id)); //Beatmap ID
        $output .= sprintf("%d", DB::table('osu_scores')->where('beatmapHash', '=', $request->query("c"))->count()); //How many ranks are in the table for the ID Difficulty Hash
        $output .= "\n";
        $output .= "0"; //Something, idk
        $output .= "\n";
        $output .= sprintf("[bold:0,size:20]%s|\n", (($beatmap === null) ?  str_replace(".osu","",$request->query("f")) : $beatmap->title)); //Sets text size and name for viewing
        $output .= "0"; //What is this, its not difficulty, not rating, might as well set to 0
        $output .= "\n";
        $user = User::where('name', $request->query("us"))->first();
        $selfrank = DB::table('osu_scores')->where('user_id', '=', $user->id)->where('beatmapHash', '=', $request->query("c"))->select('id','user_id','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods', DB::raw(sprintf("FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM osu_scores WHERE beatmapHash = '%s' )) AS rank", $user->id, $request->query("c"))),'created_at')->orderBy('rank','asc')->first();
        if(!is_null($selfrank))
        {
            $output .= $helper->scoreString($selfrank->id, $user->name, $selfrank->score, $selfrank->combo, $selfrank->count50, $selfrank->count100, $selfrank->count300, $selfrank->countMiss, $selfrank->countKatu, $selfrank->countGeki, $selfrank->fc, $selfrank->mods, $selfrank->user_id, $selfrank->rank, strtotime($selfrank->created_at));
        }
        else
        {
            $output .= "\n";
        }
        $ranking = DB::table('osu_scores')->select('id','user_id','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods', DB::raw(sprintf("FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM osu_scores WHERE beatmapHash = '%s' )) AS rank", $request->query("c"))),'created_at')->where('beatmapHash', '=', $request->query("c"))->orderBy('rank','asc')->limit(50)->get();
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
        $helper = new Helper();
        $score = explode(":", $helper->decrypt($request->input('score'), $request->input('iv')));
        $mods = $this->mods($score[13]);
        if($mods->autopilot == false && $mods->autoplay == false && $mods->relax == false) {
            $beatmap = OsuBeatmaps::where('checksum', $score[0])->first();
            if($beatmap !== null) {
                $user = User::where('name', $score[1])->first();
                if ($score[14] === 'True') {
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
                        'fc' => ($score[11] === 'True' ? true : false),
                        'mods' => $score[13],
                        'pass' => ($score[14] === 'True' ? true : false),
                        'checksum' => $score[16],
                        'created_at' => Carbon::now()
                    ]);
                    if ($score[10] > $user->OsuUserStats->max_combo) {
                        $user->OsuUserStats->max_combo = $score[10];
                    }
                    $user->OsuUserStats->count300 = $user->OsuUserStats->count300 + $score[3] + $score[6];
                    $user->OsuUserStats->count100 = $user->OsuUserStats->count100 + $score[4] + $score[7];
                    $user->OsuUserStats->count50 = $user->OsuUserStats->count50 + $score[5];
                    $user->OsuUserStats->countMiss = $user->OsuUserStats->countMiss + $score[8];
                    $user->OsuUserStats->ranked_score = $user->OsuUserStats->ranked_score + $score[9];
                    $user->OsuUserStats->playcount = $user->OsuUserStats->playcount + 1;
                    $pp = $this->calcPP($score[0], $score[10], $this->getAccuracyAlt($score[3] + $score[6], $score[4] + $score[7], $score[5], $score[8]));
                    if ($mods->doubletime || $mods->nightcore) {
                        $pp = ($pp + ($pp * .12));
                    }
                    if ($mods->hidden) {
                        $pp = ($pp + ($pp * .06));
                    }
                    if ($mods->hardrock) {
                        $pp = ($pp + ($pp * .06));
                    }
                    if ($mods->flashlight) {
                        $pp = ($pp + ($pp * .12));
                    }
                    if ($mods->easy) {
                        $pp = ($pp - ($pp * .5));
                    }
                    if ($mods->halftime) {
                        $pp = ($pp - ($pp * .7));
                    }
                    if ($mods->nofail) {
                        $pp = ($pp - ($pp * .1));
                    }
                    if ($mods->spunout) {
                        $pp = ($pp - ($pp * .05));
                    }
                    $user->OsuUserStats->pp = $user->OsuUserStats->pp + $pp;
                }
                $user->OsuUserStats->total_score = $user->OsuUserStats->total_score + $score[9];
                $user->OsuUserStats->save();
            }
        }
        return "";
    }

    function calcPP($bmhash, $combo, $acc)
    {
        $X = 1.1;
        $beatmap = OsuBeatmaps::where('checksum', $bmhash)->first();
        $approach = $beatmap->diff_approach;
        $speed = ($beatmap->countTotal / $beatmap->total_length);
        $comboFloat = ($combo / $beatmap->countTotal);
        $comboSpeedStuff = (($speed + $comboFloat) / $X);
        $data = ($comboSpeedStuff^$X + $approach^$X + $acc^$X)^(1/$X);
        log::info($data);
        return $data;
    }

    function mods($mods)
    {
        $array = (object)array();
        if(($mods - 16384) >= 0) { $array->perfect = true; $mods = ($mods - 16384); } else { $array->perfect = false; };
        if(($mods - 8192) >= 0) { $array->autopilot = true; $mods = ($mods - 8192); } else { $array->autopilot = false; };
        if(($mods - 4096) >= 0) { $array->spunout = true; $mods = ($mods - 4096); } else { $array->spunout = false; };
        if(($mods - 2048) >= 0) { $array->autoplay = true; $mods = ($mods - 2048); } else { $array->autoplay = false; };
        if(($mods - 1024) >= 0) { $array->flashlight = true; $mods = ($mods - 1024); } else { $array->flashlight = false; };
        if(($mods - 512) >= 0) { $array->nightcore = true; $mods = ($mods - 512); } else { $array->nightcore = false; };
        if(($mods - 256) >= 0) { $array->halftime = true; $mods = ($mods - 256); } else { $array->halftime = false; };
        if(($mods - 128) >= 0) { $array->relax = true; $mods = ($mods - 128); } else { $array->relax = false; };
        if(($mods - 64) >= 0) { $array->doubletime = true; $mods = ($mods - 64); } else { $array->doubletime = false; };
        if(($mods - 32) >= 0) { $array->suddendeath = true; $mods = ($mods - 32); } else { $array->suddendeath = false; };
        if(($mods - 16) >= 0) { $array->hardrock = true; $mods = ($mods - 16); } else { $array->hardrock = false; };
        if(($mods - 8) >= 0) { $array->hidden = true; $mods = ($mods - 8); } else { $array->hidden = false; };
        //if(($mods - 4) >= 0) { $array->novideo = true; $mods = ($mods - 4); } else { $array->novideo = false; };
        if(($mods - 2) >= 0) { $array->easy = true; $mods = ($mods - 2); } else { $array->easy = false; };
        if(($mods - 1) >= 0) { $array->nofail = true; $mods = ($mods - 1); } else { $array->nofail = false; };
        return $array;
    }

    function getAccuracyAlt($c300, $c100, $c50, $cMiss)
    {
        $totalHits = ($c50 + $c100 + $c300 + $cMiss) * 300;
        $hits = $c50 * 50 + $c100 * 100 + $c300 * 300;
        if($hits && $totalHits != 0) {
            return $hits / $totalHits;
        } else {
            return 0;
        }
    }
}