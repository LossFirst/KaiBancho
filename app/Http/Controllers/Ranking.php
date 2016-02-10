<?php

namespace App\Http\Controllers;

use App\OsuBeatmaps;
use Carbon\Carbon;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;

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
    public function getScores(Request $request)
    {
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
        $output .= sprintf("%s|", (isset($beatmap) ? $request->query("i") : $beatmap->beatmap_id)); //Beatmap ID
        $output .= sprintf("%d", DB::table('osu_scores')->where('beatmapHash', '=', $request->query("c"))->count()); //How many ranks are in the table for the ID Difficulty Hash
        $output .= "\n";
        $output .= "0"; //Something, idk
        $output .= "\n";
        $output .= sprintf("[bold:0,size:20]%s|\n", (isset($beatmap) ?  str_replace(".osu","",$request->query("f")) : $beatmap->title)); //Sets text size and name for viewing
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
        $helper = new Helper();
        $score = explode(":", $helper->decrypt($request->input('score'), $request->input('iv')));
        $user = User::where('name', $score[1])->first();
        if($score[14] === 'True') {
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
                'fc' => ($score[11] === 'True' ? true: false),
                'mods' => $score[13],
                'pass' => ($score[14] === 'True' ? true: false),
                'checksum' => $score[16],
                'created_at' => Carbon::now()
            ]);
            if($score[10] > $user->OsuUserStats->max_combo)
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