<?php

namespace App\Libraries;

use Cache;
use App\OsuBeatmaps;
use App\User;
use DB;
use Log;
use Carbon\Carbon;

class Scores {
    public function getBeatmapData($checksum, $beatmapID)
    {
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
                        'beatmap_id' => (integer)$data[0]->beatmap_id,
                        'beatmapset_id' => (integer)$data[0]->beatmapset_id,
                        'title' => $data[0]->title,
                        'creator' => $data[0]->creator,
                        'author' => $data[0]->artist,
                        'bpm' => (integer)$data[0]->bpm,
                        'checksum' => $data[0]->file_md5,
                        'version' => $data[0]->version,
                        'total_length' => (integer)$data[0]->total_length,
                        'hit_length' => (integer)$data[0]->hit_length,
                        'countTotal' => ($data[0]->max_combo == 'null') ? 0 : (integer)$data[0]->max_combo,
                        'diff_drain' => (float)$data[0]->diff_drain,
                        'diff_size' => (float)$data[0]->diff_size,
                        'diff_overall' => (float)$data[0]->diff_overall,
                        'diff_approach' => (float)$data[0]->diff_approach,
                        'playmode' => (integer)$data[0]->mode,
                        'approved' => (integer)$data[0]->approved,
                        'difficultyrating' => (float)$data[0]->difficultyrating,
                        'playcount' => 0,
                        'passcount' => 0
                    ]);
                }
            }
            Cache::forever(sprintf("%s:%s", $checksum, $beatmapID), $entry);
            return $entry;
        });
        return $beatmap;
    }

    public function getUserRanking($checksum, $user, $mode)
    {
        $table = '';
        switch($mode)
        {
            case '0':
                $table = 'osu_scores';
                break;
            case '1':
                $table = 'taiko_scores';
                break;
            case '2':
                $table = 'ctb_scores';
                break;
            case '3':
                $table = 'mania_scores';
                break;
        }
        return DB::table($table)->where('user_id', '=', $user->id)->where('beatmapHash', '=', $checksum)->select('id','user_id','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods', DB::raw(sprintf("FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM osu_scores WHERE beatmapHash = '%s' )) AS rank", $user->id, $checksum)),'created_at')->orderBy('rank','asc')->first();
    }

    public function getRankings($checksum, $mode)
    {
        $table = '';
        switch($mode)
        {
            case '0':
                $table = 'osu_scores';
                break;
            case '1':
                $table = 'taiko_scores';
                break;
            case '2':
                $table = 'ctb_scores';
                break;
            case '3':
                $table = 'mania_scores';
                break;
        }
        return DB::table($table)->select('id','user_id','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods', DB::raw(sprintf("FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM osu_scores WHERE beatmapHash = '%s' )) AS rank", $checksum)),'created_at')->where('beatmapHash', '=', $checksum)->orderBy('rank','asc')->limit(50)->get();
    }

    public function submitOsuScore($beatmap, $score, $mods)
    {
        $beatmap->playcount = $beatmap->playcount + 1;
        $user = User::where('name', $score[1])->first();
        if ($score[14] === 'True') {
            $beatmap->passcount = $beatmap->passcount + 1;
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
        $beatmap->save();
    }

    public function submitTaikoScore($beatmap, $score, $mods)
    {
        $beatmap->playcount = $beatmap->playcount + 1;
        $user = User::where('name', $score[1])->first();
        if ($score[14] === 'True') {
            $beatmap->passcount = $beatmap->passcount + 1;
            DB::table('taiko_scores')->insert([
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
                'created_at' => Carbon::now()
            ]);
            if ($score[10] > $user->TaikoUserStats->max_combo) {
                $user->TaikoUserStats->max_combo = $score[10];
            }
            $user->TaikoUserStats->count300 = $user->TaikoUserStats->count300 + $score[3] + $score[6];
            $user->TaikoUserStats->count100 = $user->TaikoUserStats->count100 + $score[4] + $score[7];
            $user->TaikoUserStats->count50 = $user->TaikoUserStats->count50 + $score[5];
            $user->TaikoUserStats->countMiss = $user->TaikoUserStats->countMiss + $score[8];
            $user->TaikoUserStats->ranked_score = $user->TaikoUserStats->ranked_score + $score[9];
            $user->TaikoUserStats->playcount = $user->TaikoUserStats->playcount + 1;
        }
        $user->TaikoUserStats->total_score = $user->TaikoUserStats->total_score + $score[9];
        $user->TaikoUserStats->save();
        $beatmap->save();
    }

    public function submitManiaScore($beatmap, $score, $mods)
    {
        $beatmap->playcount = $beatmap->playcount + 1;
        $user = User::where('name', $score[1])->first();
        if ($score[14] === 'True') {
            $beatmap->passcount = $beatmap->passcount + 1;
            DB::table('mania_scores')->insert([
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
                'created_at' => Carbon::now()
            ]);
            if ($score[10] > $user->ManiaUserStats->max_combo) {
                $user->ManiaUserStats->max_combo = $score[10];
            }
            $user->ManiaUserStats->count300 = $user->ManiaUserStats->count300 + $score[3] + $score[6];
            $user->ManiaUserStats->count100 = $user->ManiaUserStats->count100 + $score[4] + $score[7];
            $user->ManiaUserStats->count50 = $user->ManiaUserStats->count50 + $score[5];
            $user->ManiaUserStats->countMiss = $user->ManiaUserStats->countMiss + $score[8];
            $user->ManiaUserStats->ranked_score = $user->ManiaUserStats->ranked_score + $score[9];
            $user->ManiaUserStats->playcount = $user->ManiaUserStats->playcount + 1;
        }
        $user->ManiaUserStats->total_score = $user->ManiaUserStats->total_score + $score[9];
        $user->ManiaUserStats->save();
        $beatmap->save();
    }

    public function submitCTBScore($beatmap, $score, $mods)
    {
        $beatmap->playcount = $beatmap->playcount + 1;
        $user = User::where('name', $score[1])->first();
        if ($score[14] === 'True') {
            $beatmap->passcount = $beatmap->passcount + 1;
            DB::table('ctb_scores')->insert([
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
                'created_at' => Carbon::now()
            ]);
            if ($score[10] > $user->CTBUserStats->max_combo) {
                $user->CTBUserStats->max_combo = $score[10];
            }
            $user->CTBUserStats->count300 = $user->CTBUserStats->count300 + $score[3] + $score[6];
            $user->CTBUserStats->count100 = $user->CTBUserStats->count100 + $score[4] + $score[7];
            $user->CTBUserStats->count50 = $user->CTBUserStats->count50 + $score[5];
            $user->CTBUserStats->countMiss = $user->CTBUserStats->countMiss + $score[8];
            $user->CTBUserStats->ranked_score = $user->CTBUserStats->ranked_score + $score[9];
            $user->CTBUserStats->playcount = $user->CTBUserStats->playcount + 1;
        }
        $user->CTBUserStats->total_score = $user->CTBUserStats->total_score + $score[9];
        $user->CTBUserStats->save();
        $beatmap->save();
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

    public function mods($mods)
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