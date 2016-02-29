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
        return DB::table($table)->where('user_id', '=', $user->id)->where('beatmapHash', '=', $checksum)->select('id','user_id','score','combo','count50','count100','count300','countMiss','countKatu','countGeki','fc','mods', DB::raw(sprintf("FIND_IN_SET( score, (SELECT GROUP_CONCAT( score ORDER BY score DESC ) FROM osu_scores WHERE beatmapHash = '%s' )) AS rank", $checksum)),'created_at')->orderBy('rank','asc')->first();
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

    public function submitOsuScore($beatmap, $score, $mods, $currentTime)
    {
        $beatmap->playcount = $beatmap->playcount + 1;
        $user = User::where('name', $score[1])->first();
        if ($score[14] === 'True') {
            $beatmap->passcount = $beatmap->passcount + 1;
            $scoreSubmission = DB::table('osu_scores')->where('beatmapHash', $beatmap->checksum)->where('user_id', $user->id)->first();
            $pp = $this->calcPP($score[0], $this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8]), $mods, $score);
            if($scoreSubmission === null) {
                DB::table('osu_scores')->insert([
                    'beatmapHash' => $score[0],
                    'user_id' => $user->id,
                    'score' => $score[9],
                    'pp' => $pp,
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
                    'created_at' => $currentTime
                ]);
                if ($score[10] > $user->OsuUserStats->max_combo) {
                    $user->OsuUserStats->max_combo = $score[10];
                }
                $user->OsuUserStats->count300 += $score[3];
                $user->OsuUserStats->count100 += $score[4];
                $user->OsuUserStats->count50 +=$score[5];
                $user->OsuUserStats->countMiss += $score[8];
                $user->OsuUserStats->ranked_score += $score[9];
                $user->OsuUserStats->playcount += 1;
                $user->OsuUserStats->pp += $pp;
                $redis = new RedisMessage();
                $message = sprintf("You have gained %d PP with accuracy of %01.2f from %s - %s [%s]", $pp, ($this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8])) * 100, $beatmap->author, $beatmap->title, $beatmap->version);
                $return = array('Channel' => $user->name, 'Message' => $message);
                $redis->SendMessage((object)array('id' => -1, 'name' => 'PP_Bot'), $return);
            } else {
                if((integer)$score[9] > $scoreSubmission->score)
                {
                    $oldPP = $scoreSubmission->pp;
                    $ppDiff = $pp - $oldPP;
                    DB::table('osu_scores')->where('beatmapHash', $beatmap->checksum)->where('user_id', $user->id)->update([
                        'beatmapHash' => $score[0],
                        'user_id' => $user->id,
                        'score' => $score[9],
                        'pp' => $pp,
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
                        'created_at' => $currentTime
                    ]);
                    if ($score[10] > $user->OsuUserStats->max_combo) {
                        $user->OsuUserStats->max_combo = $score[10];
                    }
                    $user->OsuUserStats->count300 += $score[3];
                    $user->OsuUserStats->count100 += $score[7];
                    $user->OsuUserStats->count50 += $score[5];
                    $user->OsuUserStats->countMiss += $score[8];
                    $user->OsuUserStats->ranked_score += $score[9];
                    $user->OsuUserStats->playcount += 1;
                    $user->OsuUserStats->pp += $ppDiff;
                    $redis = new RedisMessage();
                    $message = sprintf("You have gained/lost %d PP with accuracy of %01.2f from %s - %s [%s]", $ppDiff, ($this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8])) * 100, $beatmap->author, $beatmap->title, $beatmap->version);
                    $return = array('Channel' => $user->name, 'Message' => $message);
                    $redis->SendMessage((object)array('id' => -1, 'name' => 'PP_Bot'), $return);
                }
            }
        }
        $user->OsuUserStats->total_score += $score[9];
        $user->OsuUserStats->save();
        $beatmap->save();
    }

    public function submitTaikoScore($beatmap, $score, $mods, $currentTime)
    {
        $beatmap->playcount = $beatmap->playcount + 1;
        $user = User::where('name', $score[1])->first();
        if ($score[14] === 'True') {
            $beatmap->passcount = $beatmap->passcount + 1;
            $scoreSubmission = DB::table('taiko_scores')->where('beatmapHash', $beatmap->checksum)->where('user_id', $user->id)->first();
            $pp = $this->calcPP($score[0], $this->getAccuracyAlt($score[3] + $score[6], $score[4] + $score[7], $score[5], $score[8]), $mods, $score);
            if($scoreSubmission === null) {
                DB::table('taiko_scores')->insert([
                    'beatmapHash' => $score[0],
                    'user_id' => $user->id,
                    'score' => $score[9],
                    'pp' => $pp,
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
                    'created_at' => $currentTime
                ]);
                if ($score[10] > $user->TaikoUserStats->max_combo) {
                    $user->TaikoUserStats->max_combo = $score[10];
                }
                $user->TaikoUserStats->count300 += $score[3];
                $user->TaikoUserStats->count100 += $score[4];
                $user->TaikoUserStats->count50 += $score[5];
                $user->TaikoUserStats->countMiss += $score[8];
                $user->TaikoUserStats->ranked_score += $score[9];
                $user->TaikoUserStats->playcount += 1;
                $user->TaikoUserStats->pp += $pp;
                $redis = new RedisMessage();
                $message = sprintf("You have gained %d PP with accuracy of %01.2f from %s - %s [%s]", $pp, ($this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8])) * 100, $beatmap->author, $beatmap->title, $beatmap->version);
                $return = array('Channel' => $user->name, 'Message' => $message);
                $redis->SendMessage((object)array('id' => -1, 'name' => 'PP_Bot'), $return);
            } else {
                if((integer)$score[9] > $scoreSubmission->score)
                {
                    $oldPP = $scoreSubmission->pp;
                    $ppDiff = $pp - $oldPP;
                    DB::table('taiko_scores')->where('beatmapHash', $beatmap->checksum)->where('user_id', $user->id)->update([
                        'beatmapHash' => $score[0],
                        'user_id' => $user->id,
                        'score' => $score[9],
                        'pp' => $pp,
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
                        'created_at' => $currentTime
                    ]);
                    if ($score[10] > $user->TaikoUserStats->max_combo) {
                        $user->TaikoUserStats->max_combo = $score[10];
                    }
                    $user->TaikoUserStats->count300 += $score[3];
                    $user->TaikoUserStats->count100 += $score[4];
                    $user->TaikoUserStats->count50 += $score[5];
                    $user->TaikoUserStats->countMiss += $score[8];
                    $user->TaikoUserStats->ranked_score += $score[9];
                    $user->TaikoUserStats->playcount += 1;
                    $user->TaikoUserStats->pp += $ppDiff;
                    $redis = new RedisMessage();
                    $message = sprintf("You have gained/lost %d PP with accuracy of %01.2f from %s - %s [%s]", $ppDiff, ($this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8])) * 100, $beatmap->author, $beatmap->title, $beatmap->version);
                    $return = array('Channel' => $user->name, 'Message' => $message);
                    $redis->SendMessage((object)array('id' => -1, 'name' => 'PP_Bot'), $return);
                }
            }
        }
        $user->TaikoUserStats->total_score += $score[9];
        $user->TaikoUserStats->save();
        $beatmap->save();
    }

    public function submitManiaScore($beatmap, $score, $mods, $currentTime)
    {
        $beatmap->playcount = $beatmap->playcount + 1;
        $user = User::where('name', $score[1])->first();
        if ($score[14] === 'True') {
            $beatmap->passcount = $beatmap->passcount + 1;
            $scoreSubmission = DB::table('mania_scores')->where('beatmapHash', $beatmap->checksum)->where('user_id', $user->id)->first();
            $pp = $this->calcPP($score[0], $this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8]), $mods, $score);
            if($scoreSubmission === null) {
                DB::table('mania_scores')->insert([
                    'beatmapHash' => $score[0],
                    'user_id' => $user->id,
                    'score' => $score[9],
                    'pp' => $pp,
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
                    'created_at' => $currentTime
                ]);
                if ($score[10] > $user->ManiaUserStats->max_combo) {
                    $user->ManiaUserStats->max_combo = $score[10];
                }
                $user->ManiaUserStats->count300 += $score[3];
                $user->ManiaUserStats->count100 += $score[4];
                $user->ManiaUserStats->count50 += $score[5];
                $user->ManiaUserStats->countMiss += $score[8];
                $user->ManiaUserStats->ranked_score += $score[9];
                $user->ManiaUserStats->playcount += 1;
                $user->ManiaUserStats->pp += $pp;
                $redis = new RedisMessage();
                $message = sprintf("You have gained %d PP with accuracy of %01.2f from %s - %s [%s]", $pp, ($this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8])) * 100, $beatmap->author, $beatmap->title, $beatmap->version);
                $return = array('Channel' => $user->name, 'Message' => $message);
                $redis->SendMessage((object)array('id' => -1, 'name' => 'PP_Bot'), $return);
            } else {
                if((integer)$score[9] > $scoreSubmission->score)
                {
                    $oldPP = $scoreSubmission->pp;
                    $ppDiff = $pp - $oldPP;
                    DB::table('mania_scores')->where('beatmapHash', $beatmap->checksum)->where('user_id', $user->id)->update([
                        'beatmapHash' => $score[0],
                        'user_id' => $user->id,
                        'score' => $score[9],
                        'pp' => $pp,
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
                        'created_at' => $currentTime
                    ]);
                    if ($score[10] > $user->ManiaUserStats->max_combo) {
                        $user->ManiaUserStats->max_combo = $score[10];
                    }
                    $user->ManiaUserStats->count300 += $score[3];
                    $user->ManiaUserStats->count100 += $score[4];
                    $user->ManiaUserStats->count50 += $score[5];
                    $user->ManiaUserStats->countMiss += $score[8];
                    $user->ManiaUserStats->ranked_score += $score[9];
                    $user->ManiaUserStats->playcount += 1;
                    $user->ManiaUserStats->pp += $ppDiff;
                    $redis = new RedisMessage();
                    $message = sprintf("You have gained/lost %d PP with accuracy of %01.2f from %s - %s [%s]", $ppDiff, ($this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8])) * 100, $beatmap->author, $beatmap->title, $beatmap->version);
                    $return = array('Channel' => $user->name, 'Message' => $message);
                    $redis->SendMessage((object)array('id' => -1, 'name' => 'PP_Bot'), $return);
                }
            }
        }
        $user->ManiaUserStats->total_score += $score[9];
        $user->ManiaUserStats->save();
        $beatmap->save();
    }

    public function submitCTBScore($beatmap, $score, $mods, $currentTime)
    {
        $beatmap->playcount = $beatmap->playcount + 1;
        $user = User::where('name', $score[1])->first();
        if ($score[14] === 'True') {
            $beatmap->passcount = $beatmap->passcount + 1;
            $scoreSubmission = DB::table('ctb_scores')->where('beatmapHash', $beatmap->checksum)->where('user_id', $user->id)->first();
            $pp = $this->calcPP($score[0], $this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8]), $mods, $score);
            if($scoreSubmission === null) {
                DB::table('ctb_scores')->insert([
                    'beatmapHash' => $score[0],
                    'user_id' => $user->id,
                    'score' => $score[9],
                    'pp' => $pp,
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
                    'created_at' => $currentTime
                ]);
                if ($score[10] > $user->CTBUserStats->max_combo) {
                    $user->CTBUserStats->max_combo = $score[10];
                }
                $user->CTBUserStats->count300 += $score[3];
                $user->CTBUserStats->count100 += $score[4];
                $user->CTBUserStats->count50 += $score[5];
                $user->CTBUserStats->countMiss += $score[8];
                $user->CTBUserStats->ranked_score += $score[9];
                $user->CTBUserStats->playcount += 1;
                $user->CTBUserStats->pp += $pp;
                $redis = new RedisMessage();
                $message = sprintf("You have gained %d PP with accuracy of %01.2f from %s - %s [%s]", $pp, ($this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8])) * 100, $beatmap->author, $beatmap->title, $beatmap->version);
                $return = array('Channel' => $user->name, 'Message' => $message);
                $redis->SendMessage((object)array('id' => -1, 'name' => 'PP_Bot'), $return);
            } else {
                if((integer)$score[9] > $scoreSubmission->score)
                {
                    $oldPP = $scoreSubmission->pp;
                    $ppDiff = $pp - $oldPP;
                    DB::table('ctb_scores')->where('beatmapHash', $beatmap->checksum)->where('user_id', $user->id)->update([
                        'beatmapHash' => $score[0],
                        'user_id' => $user->id,
                        'score' => $score[9],
                        'pp' => $pp,
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
                        'created_at' => $currentTime
                    ]);
                    if ($score[10] > $user->CTBUserStats->max_combo) {
                        $user->CTBUserStats->max_combo = $score[10];
                    }
                    $user->CTBUserStats->count300 += $score[3];
                    $user->CTBUserStats->count100 += $score[4];
                    $user->CTBUserStats->count50 += $score[5];
                    $user->CTBUserStats->countMiss += $score[8];
                    $user->CTBUserStats->ranked_score += $score[9];
                    $user->CTBUserStats->playcount += 1;
                    $user->CTBUserStats->pp += $ppDiff;
                    $redis = new RedisMessage();
                    $message = sprintf("You have gained/lost %d PP with accuracy of %01.2f from %s - %s [%s]", $ppDiff, ($this->getAccuracyAlt($score[3], $score[4], $score[5], $score[8])) * 100, $beatmap->author, $beatmap->title, $beatmap->version);
                    $return = array('Channel' => $user->name, 'Message' => $message);
                    $redis->SendMessage((object)array('id' => -1, 'name' => 'PP_Bot'), $return);
                }
            }
        }
        $user->CTBUserStats->total_score += $score[9];
        $user->CTBUserStats->save();
        $beatmap->save();
    }

    public function calcPP($bmhash, $acc, $mods, $score)
    {
        $X = 1.1;
        $beatmap = OsuBeatmaps::where('checksum', $bmhash)->first();
        $aimpp = ((($this->calcAimPP($beatmap, $mods, $score, $acc))^$X));
        $speedpp = ((($this->calcSpeedPP($beatmap, $mods, $score, $acc))^$X));
        $accpp = ((($this->calcAccPP($beatmap, $mods, $score, $acc))^$X));
        $pp = $this->calcMods($mods, ($aimpp + $speedpp + $accpp)^(1/$X));
        //Log::info(sprintf("Aim: %f || Speed: %f || Acc: %f || PPvFUCK: %f", $aimpp, $speedpp, $accpp, $pp));
        return $pp;
    }

    function calcAimPP($beatmap, $mods, $score, $accuracy)
    {
        $ar = $beatmap->diff_approach;
        $countTotal = $beatmap->countTotal;
        $length = $beatmap->hit_length;
        $cs = $beatmap->diff_size;
        $combo = (integer)$score[10];
        $miss = (integer)$score[8];

        $csValue = $this->calcMods($mods, $cs);
        $arValue = $this->calcMods($mods, $ar);
        $lengthValue = ($countTotal / $length);
        $comboMissValue = ((($miss === 0) ? $combo : $combo / $miss) / 6);

        //log::info(sprintf("Aim Diff: %f || AR Diff: %f || Length Diff: %f || ComboMiss: %f", $csValue, $arValue, $lengthValue, $comboMissValue));

        return ((($csValue + $arValue + $lengthValue + $comboMissValue)) * $accuracy);
    }

    function calcSpeedPP($beatmap, $mods, $score, $accuracy)
    {
        $length = $beatmap->hit_length;
        $countTotal = $beatmap->countTotal;
        $combo = (integer)$score[10];
        $miss = (integer)$score[8];
        $drain = $beatmap->diff_drain;

        $lengthValue = ($countTotal / $length);
        $drainValue = $this->calcMods($mods, $drain);
        $comboMissValue = ((($miss === 0) ? $combo : $combo / $miss) / 6);

        //log::info(sprintf("Drain Diff: %f || Length Diff: %f || ComboMiss: %f", $drainValue, $lengthValue, $comboMissValue));

        return ((($drainValue + $lengthValue + $comboMissValue)) * $accuracy);
    }

    function calcAccPP($beatmap, $mods, $score, $accuracy)
    {
        $od = $beatmap->diff_overall;
        $countTotal = $beatmap->countTotal;
        $length = $beatmap->hit_length;

        $lengthValue = ($countTotal / $length);
        $odValue = ($this->calcMods($mods, $od));

        //log::info(sprintf("OD Diff: %f || Length Diff: %f || Accuracy: %f", $odValue, $lengthValue, $accuracy));

        return ((($odValue + $lengthValue + $accuracy) * 3) * $accuracy);
    }

    function calcMods($mods, $calc)
    {
        if ($mods->doubletime || $mods->nightcore) {
            $calc = ($calc + ($calc * .12));
        }
        if ($mods->hidden) {
            $calc = ($calc + ($calc * .06));
        }
        if ($mods->hardrock) {
            $calc = ($calc + ($calc * .06));
        }
        if ($mods->flashlight) {
            $calc = ($calc + ($calc * .12));
        }
        if ($mods->easy) {
            $calc = ($calc - ($calc * .5));
        }
        if ($mods->halftime) {
            $calc = ($calc - ($calc * .7));
        }
        if ($mods->nofail) {
            $calc = ($calc - ($calc * .1));
        }
        if ($mods->spunout) {
            $calc = ($calc - ($calc * .05));
        }
        return $calc;
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