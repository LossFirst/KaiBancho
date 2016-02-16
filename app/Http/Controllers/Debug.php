<?php

namespace App\Http\Controllers;

use App\OsuBeatmaps;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Log;
use DB;

class Debug extends Controller
{
    public function getDebug(Request $request, $section)
    {
        Log::info(sprintf("Request made to %s from %s", $section, $request->getClientIp()));
        return '';
    }

    public function postDebug(Request $request, $section)
    {
        Log::info(sprintf("Request made to %s", $section));
        return '';
    }

    public function getSearchID(Request $request)
    {
        $beatmap = OsuBeatmaps::where('beatmap_id', $request->query('b'))->first();
        $output = sprintf("%s.osz|%s|%s|%s|%s|%s|%s|%s|%s|0|0|0|", $beatmap->beatmapset_id, $beatmap->author, $beatmap->title, $beatmap->creator, $beatmap->approved, $beatmap->playmode, $beatmap->created_at, $beatmap->beatmapset_id, $beatmap->beatmap_id);
        //368148.osz|Tatsh|reunion|Sangzin|-1|0|2016-01-24 21:17:45|368148|377880|0|0|0|
        Log::info($request->getQueryString());
        return $output;
    }

    public function getSearch(Request $request)
    {
        Log::info("Search was called");
        Log::info($request->getQueryString());
        $query = "101
            405053.osz|Azusa Tadokoro|Junshin Always|Shad0w1and|1|9.27419|2016-02-08T02:56:07.000Z|405053|409296|1|0|0||Advanced@0,Lami's Extra@0,Little's Insane@0,Normal@0,StarR's Extra@0,ZZHBOY's Hard@0,Extra@0,Aerous' Extra@0
            180191.osz|T-ara|No.9|Tony|1|9.375|2016-02-07T19:16:29.000Z|180191|213454|1|0|0||Hard@0,Normal@0,Gabe's Easy@0
            375073.osz|nanobii|rainbow road|Natsu|1|9.28926|2016-02-07T02:38:09.000Z|375073|384015|0|0|0||Eun & Irvin's Rainbow@0,Insane@0,Hyper@0,Hard@0,Normal@0,Easy@0
            381981.osz|Philip Glass|Music Box|Namki|1|9.14286|2016-02-06T20:41:10.000Z|381981|389413|0|0|0||Normal@0,Manul's Hard@0
            368312.osz|Team \"Hanayamata\"|Yorokobi Synchronicity|Xinely|1|8.64706|2016-02-06T11:55:34.000Z|368312|378056|0|0|0||Cup@2,Platter@2,Salad@2,Cherry Blossom@2
            403814.osz|Mami Kawada|Contrail ~Kiseki~|Meg|1|8.8018|2016-02-05T21:42:26.000Z|403814|407818|1|0|0||Insane@0,Easy@0,vellya's Hard@0,hmr's Normal@0,Lecana's Insane@0
            389179.osz|Jay Chou|Fa Ru Xue|KaedekaShizuru|1|9.67647|2016-01-24T21:44:54.000Z|389179|394385|0|0|0||Snowy Hair@0,Crystal's Hard@0,Kencho's Normal@0
            220785.osz|Hanatan|Tengaku|Rakuen|1|9.19072|2016-02-04T23:24:32.000Z|220785|248845|0|0|0||Normal@0,Hard@0,[ -Scarlet- ]'s Extra@0,Easy@0,Insane@0,Natsu's Insane@0,Skystar's Extra@0,Crystal's Insane@0,handsome's Insane@0
            364750.osz|Taeyeon|I (feat. Verbal Jint)|wasinkingspeedq|1|9.28125|2016-02-03T03:03:32.000Z|364750|374945|1|0|0||Hard@0,Easy@0,Normal@0
            374875.osz|fhana|Comet Lucifer ~The Seed and the Sower~|Mikii|1|9.06897|2016-02-04T08:32:10.000Z|374875|383795|1|0|0||Gifdium@0,Insane@0,Hard@0,Easy@0,Normal@0,Elvis' Insane@0
            128931.osz|Feint|Tower Of Heaven (You Are Slaves)|eLy|1|9.60823|2016-02-04T00:29:27.000Z|128931|166248|0|0|0||Heaven@0,Another@0,Extra@0,Hyper@0,Normal@0,Hard@0
            348412.osz|nano.RIPE|Real World (TV ver.)|Tyrell|1|7.875|2016-02-03T07:08:23.000Z|348412|360279|0|0|0||Cup@2,Mysterious World@2,Salad@2
            395252.osz|Hayami Saori|Sono Koe ga Chizu ni Naru (TV EDIT)|Rizia|1|9.2844|2016-02-02T22:31:54.000Z|395252|398669|1|0|0||SnowWhite@0,Hard@0,Normal@0,Flask's Easy@0
            351390.osz|Shano & 40mP|Natsukoi Hanabi|-Sh1n1-|1|8.42105|2016-02-02T02:26:40.000Z|351390|363001|0|0|0||Futsuu@1,Kantan@1,Muzukashii@1,Oni@1
            353237.osz|Rita|Dream Walker|Vert|1|8.83636|2016-01-30T10:54:55.000Z|353237|364753|0|0|0||Insane@0,Zweib's Insane@0,Mei's Hard@0,Kencho's Normal@0,117's Easy@0
            247747.osz|The Eden Project|Lost|Bearizm|1|8.91724|2016-01-30T13:38:32.000Z|247747|269546|0|0|0||Escapism@0,Hard@0,Insane@0,Normal@0,wendao's Advanced@0
            375452.osz|TOKIO|ding-dong|Volta|1|8.5|2016-01-30T22:23:08.000Z|375452|384388|0|0|0||Futsuu@1,Kantan@1,Muzukashii@1,Oni@1
            323010.osz|Otokaze|Amamichi|UnLock-|1|9.29134|2016-02-01T13:46:54.000Z|323010|337413|0|0|0||Insane@0,bbHard@0,Sz's Light Hard@0,Normal@0,Light Hard@0
            287251.osz|Fire EX.|Shattered Dreams|qoot8123|1|8.6875|2016-02-02T08:55:48.000Z|287251|304048|0|0|0||Inner Oni@1,Kantan@1,Nardo's Muzukashii@1,Futsuu@1,m1ng's Oni@1,Ishida's Inner Oni@1
            384588.osz|nano|Bull's eye|HB24|1|9.38743|2016-02-02T03:27:49.000Z|384588|391182|1|0|0||First Impact@0,Easy@0,Hard@0,Normal@0,Sotarks' Insane@0,Light Insane@0
            374767.osz|Lia|Bravely You (TV Size)|Reana|1|9.13953|2016-02-01T19:48:05.000Z|374767|383683|0|0|0||Charlotte@0,Curi's Easy@0,Doyak's Hard@0,Zzz's Normal@0,Taeyang's Insane@0
            378669.osz|Tou Chi Chen|Secret Kakuranger TEKINA Remix|-Kamikaze-|1|7.66667|2016-02-01T22:29:11.000Z|378669|386971|0|0|0||8K Ninja@3,8K Normal@3,Ray's 8K Hyperanger@3,Zenx's 7K Hard@3,Zenx's 7K Normal@3
            348801.osz|Kayano Ai / Tomatsu Haruka / Hayami Saori|secret base ~Kimi ga Kureta Mono~ (10 years after Ver.)|Rizen|1|9.28571|2016-02-01T12:06:08.000Z|348801|360834|1|0|0||Normal@0,Beginner@0,Easy@0,You Found Me@0,Doyak's Hard@0
            367484.osz|Diceros Bicornis|Innocent Tempest|Ichigaki|1|9.10588|2016-02-01T06:09:33.000Z|367484|377185|0|0|0||INFINITE@3,NOVICE@3,ADVANCED@3,Zan's EXHAUST@3
            400597.osz|Noisestorm|Ignite|Shiirn|1|8.68254|2016-02-01T04:15:20.000Z|400597|404321|0|0|0||Collab Hard@0,Insane@0,Riki's Normal@0
            403676.osz|Mr.Kitty|Mother Mary|ByBy13|1|9.31111|2016-02-01T03:49:09.000Z|403676|407629|0|0|0||Dark@0
            364652.osz|petit milady|Koi wa Milk Tea|Okoratu|1|9.25532|2016-01-31T23:25:30.000Z|364652|374850|0|0|0||Amai@0,Meyko's Hard@0,Normal@0,Easy@0
            330647.osz|BIGBANG&2NE1|Lollipop|Gero|1|8.3421|2016-02-01T03:21:45.000Z|330647|344156|1|0|0||Euny's Easy@0,Gerawak's Hard@0,Rainbow@0,Twin Normal@0,eINnyc's Advanced@0
            398976.osz|NAMCO|PATH OF GODDESS CLAIRE|Doyak|1|8.78947|2016-01-20T02:25:11.000Z|398976|402699|0|0|0||Hard@0,Normal@0
            334466.osz|MYTH & ROID|L.L.L.|jonathanlfj|1|9.07101|2016-01-31T05:35:43.000Z|334466|347610|1|0|0||Overkill@0,Raose's Hard@0,Normal@0,Tt's Insane@0,Trust's Easy@0,captin's Extra@0,Nyquill's Insane@0,Hard@0,Irre's Insane@0
            395846.osz|MY FIRST STORY|ALONE|Saut|1|9.30315|2016-01-31T03:31:45.000Z|395846|399246|0|0|0||Isolation@0,Extra@0,Easy@0,Normal@0,Insane@0,Hard@0
            230739.osz|USAO|Miracle 5ympho X (Extended Mix)|RLC|1|8.90449|2016-01-30T19:45:36.000Z|230739|256823|0|0|0||5ympho XtrA@0,W1's ONII-CHAN@0,Normal@0,Cloud's Easy@0,momoko's Insane@0,Asphyxia's Hard@0,toybot's Advanced@0
            398921.osz|senya|Hitomi ni Kakusareta Omoi|Satellite|1|9.56684|2016-01-30T20:02:05.000Z|398921|402647|0|0|0||Lunatic@0,Normal@0,N a s y a's Lunatic@0,Sellenite's Lunatic@0,wring's Hard@0
            363882.osz|P*Light|YELLOW SPLASH!!|Minakami Yuki|1|9.38947|2016-01-31T00:46:16.000Z|363882|374175|0|0|0||Special Splash!!@0,fanzhen's Expert@0,yf's Extreme@0,wkyik's Extra@0,Guy's Extra@0,Standard@0,Regraz's Hyper@0,StarR's Another@0,Enjoy's Insane@0
            403822.osz|Mami Kawada|Contrail ~Kiseki~|Enjoy|1|9.48624|2016-01-30T12:56:45.000Z|403822|407823|1|0|0||Insane@0,Normal@0,wkyik's Insane@0,Depths' Insane@0,Hard@0,KSHR's Easy@0,hvick225's Insane@0
            388428.osz|Reol|Midnight Stroller|Chaoslitz|1|9.23153|2016-01-29T17:34:23.000Z|388428|394969|0|0|0||Nightfall@0,117's Hard@0,Kencho's Normal@0,ZZH's Easy@0
            343093.osz|yanaginagi|Haru Modoki (Asterisk DnB Remix)|Gomuryuu|1|8.59596|2016-01-30T02:24:56.000Z|343093|355322|0|0|0||Genuine@3,Frim's Hard@3,Normal@3
            372625.osz|GRANRODEO|Punky Funky Love (TV Size)|Feb|1|9.28378|2016-01-28T00:16:24.000Z|372625|381519|1|0|0||Insane@0,Normal@0,Hard@0,Light Insane@0,Saut's Zone@0,Advanced@0
            397976.osz|Michael Wong|Yue Ding|Xinely|1|9.70588|2016-01-29T16:46:54.000Z|397976|401635|0|0|0||Easy@0,Normal@0,Our Promise@0
            302736.osz|Aqua Timez|Niji|-Sh1n1-|1|9.19231|2016-01-29T01:16:53.000Z|302736|319388|0|0|0||Rain@2,Manuxz's Platter@2,Q-H's Salad@2
            401869.osz|Azusa Tadokoro|Junshin Always|Rizia|1|9.39429|2016-01-28T00:41:47.000Z|401869|405680|1|0|0||623's Easy@0,Extra@0,Hard@0,eINess' Expert@0,Sekai's Insane@0,Colorful@0,KwaN's Normal@0
            403073.osz|ginkiha|EOS (kamome sano rmx)|Monstrata|1|9.35211|2016-01-26T07:05:45.000Z|403073|406950|0|0|0||Stardust@0
            372200.osz|Nekomata Master feat. Misawa Aki|chrono diver -fragment-|moph|1|9.35749|2016-01-25T09:01:35.000Z|372200|381084|0|0|0||Extra@0,Normal@0,Hyper@0,Rizen's Advanced@0,Insane@0,tasuke's Muzukashii@1,tasuke's Oni@1
            242220.osz|Hanatan|Kuroneko|Lavender|1|9.2234|2016-01-27T21:29:30.000Z|242220|265497|1|0|0||Negai@0,Insane@0,ZJ's Normal@0,Rakuen's Hard@0
            382584.osz|Nakamura Eriko, Shimizu Ai, Aoba Ringo|Hoshikaze no Horoscope(#12 ver.)|Momochikun|1|9.16964|2016-01-27T06:07:19.000Z|382584|389843|0|0|0||Easy@0,Hard@0,Insane@0,Normal@0,Light Insane@0
            392118.osz|SawanoHiroyuki[nZk]:mizuki|&Z|Sotarks|1|9.36318|2016-01-27T04:19:43.000Z|392118|395791|0|0|0||HB's Insane@0,Inherit the Stars@0,FCL's Normal@0,apple's Easy@0,MrSergio's Hard@0
            358420.osz|Yazawa Nico (CV.Tokui Sora)|Nicopuri Joshidou|yf_bmp|1|9.06726|2016-01-27T02:14:15.000Z|358420|369135|0|0|0||Expert@0,Lyric's Hard@0,z1085684963's Insane@0,Vert's Insane@0,ZZH's Normal@0,YIIII's Easy@0,Ayaya's Extra@0
            392869.osz|Nishiura Tomohito|Broken Promise (Orgel Version)|- Magic Bomb -|1|7.81818|2016-01-26T21:14:18.000Z|392869|396357|0|0|0||Hyperion's Cup@2,Platter@2,Salad@2
            328684.osz|kors k|Dot|Hydria|1|8.80488|2016-01-26T06:02:30.000Z|328684|342618|0|0|0||Tremor@3
            321002.osz|Last Note. feat.mirin|Fake out|Kyubey|1|9.22513|2016-01-24T18:57:21.000Z|321002|335882|0|0|0||True@0,ryuu's Beginner@0,alacat's Another@0,Seikatu's Hyper@0,Normal@0
            379370.osz|SoundTeMP|Christmas in 13th Month (Vesuvia Ecky's Poetic Mix)|neonat|1|9.48936|2016-01-26T00:26:46.000Z|379370|387494|0|0|0||Normal@0,Insane@0,Regraz's Hard@0
            383341.osz|Tom Day|New Beginnings|Auxent|1|8.89231|2016-01-24T02:23:32.000Z|383341|390345|0|0|0||Commencement@0,Normal@0,Hard@0,Sieg's Insane@0
            388403.osz|Hanatan|Attakain Dakara|Down|1|9.48947|2016-01-24T13:07:02.000Z|388403|393734|0|0|0||Soup Tabetai@0,Left's Insane@0,Hard@0,Rose's Insane@0,Easy@0,Taeyang's Normal@0
            403851.osz|Bruno Mars|Marry You|Natsu|1|9.05926|2016-01-24T07:51:18.000Z|403851|407867|0|0|0||Insane@0,Normal@0,Easy@0,Euny's Hard@0
            277421.osz|Lindsey Stirling|Senbonzakura|MrSergio|1|9.40048|2016-01-25T06:39:46.000Z|277421|294465|1|0|0||Extra@0,Hard@0,Normal@0,Harby's HD@3,Harby's NM@3,AsaNe's Easy@0,Insane@0
            385288.osz|Party Favor|BAP U (not sorry Remix)|Shadren|1|9.189|2016-01-25T01:32:28.000Z|385288|391738|0|0|0||Insane@0,Hard@0,Easy@0,Normal@0,Advanced@0
            367869.osz|Rita|Winter Diamond|BeatofIke|1|9.42515|2016-01-24T13:33:23.000Z|367869|377619|1|0|0||Easy@0,Normal@0,Beginner@0,Hard@0,pkhg's Insane@0,Insane@0
            361462.osz|MikitoP|Akaito|EdamaMe411|1|9.68421|2016-01-24T10:19:08.000Z|361462|372022|0|0|0||Oni@1
            310607.osz|t+pazolite|Electric \"Sister\" Bitch|Verniy_Chan|1|9.28099|2016-01-24T05:34:26.000Z|310607|327015|0|0|0||ADVANCED Lv.12@3,Rinzler's EXHAUST Lv.14@3,Rido's INFINITE Lv.16@3,NOVICE Lv.8@3,BASIC Lv.6@3
            320717.osz|Nanamori-chu * Goraku-bu|Happy Time wa Owaranai|Setz|1|9.58294|2016-01-24T05:39:49.000Z|320717|335668|0|0|0||Eternal Happiness@0,A Mystery's Insane@0,Karia's Hard@0,Gaia's Normal@0,Easy@0
            400396.osz|onoken|felys|Shiirn|1|8.52632|2016-01-24T01:58:59.000Z|400396|404101|0|0|0||Home@0
            399140.osz|lily white|Futari Happiness|Baraatje123|1|8.85714|2016-01-23T22:08:48.000Z|399140|402862|0|0|0||Aeonian Friendship@0
            387185.osz|Rise Against|State of the Union|pishifat|1|8.7094|2016-01-16T06:00:36.000Z|387185|393010|0|0|0||Extreme@0,Extra@0,Milan-'s Baby Insane@0,Insane@0,Hard@0,Normal@0,Easy@0
            170604.osz|Tokisawa Nao|BRYNHILDR IN THE DARKNESS -Ver. EJECTED-|gxytcgxytc|1|8.91192|2016-01-21T17:33:03.000Z|170604|204891|1|0|0||Dark@0,Bless' Normal@0,Easy@0,Melt's Hard@0,yf's Extra@0,OblivioN's Insane@0
            399732.osz|Pizuya's Cell|Wataru mono no todaeta hashi|Garven|1|7.89744|2016-01-22T10:25:34.000Z|399732|403400|0|0|0||Hard@0,Normal@0
            384688.osz|Ito Kanako|Amadeus|FrostxE|1|9.51579|2016-01-22T08:34:26.000Z|384688|391271|0|0|0||Insane@0,Hard@0,Normal@0,Easy@0
            395149.osz|Asterisk|Rain|Monstrata|1|9.73864|2016-01-22T03:14:25.000Z|395149|398584|0|0|0||Monsoon@0
            398977.osz|toby fox|Bergentrueckung|Mazziv|1|8.91318|2016-01-22T03:51:35.000Z|398977|402698|0|0|0||Easy@0,Normal@0,Checkmate@0
            288656.osz|Demetori|Shoujo Satori ~ Innumerable Eyes|A Mystery|1|9.57385|2016-01-20T06:35:14.000Z|288656|305497|0|0|0||Hard@0,Advanced@0,Normal@0,Extra@0,Insane@0,pishi's Extra@0
            382664.osz|dBu music|Higan Kikou ~ Titanic of Stygian|Lily Bread|1|9.43162|2016-01-16T20:47:45.000Z|382664|389895|0|0|0||Lunatic@0,Hard@0,Normal@0,Mollon's Easy@0
            394742.osz|Sawai Miku|Colorful. (Asterisk DnB Remix)|Depths|1|9.2233|2016-01-18T20:46:01.000Z|394742|398167|0|0|0||Prismatic@0
            365963.osz|MY FIRST STORY|Itsuwari NEUROSE|Saut|1|9.25227|2016-01-19T03:23:07.000Z|365963|376088|0|0|0||Broccoly's Extra@0,Jommy's Extreme@0,Madness@0,Insane@0,Normal@0,Easy@0,Hard@0
            62788.osz|ZODIACSYNDICATE|Astraea no Soubei|wring|1|9.03268|2016-01-18T22:01:08.000Z|62788|102966|0|0|0||Extreme@0,Advanced@0,Basic@0,Entry@0,AngelHoney's ExtrA@0,Hard@0
            402187.osz|Azusa Tadokoro|Junshin Always|handsome|1|9.36723|2016-01-17T12:39:45.000Z|402187|406038|1|0|0||Easy@0,Hard@0,Insane@0,Normal@0,Extra@0
            237854.osz|Jeff Williams|Time to say Goodbye (feat. Casey Lee Williams)|Pho|1|9.45528|2016-01-17T01:35:45.000Z|237854|262428|0|0|0||Normal@0,Milar1ngo's Insane@0,Easy@0,Hard@0,Hyper@0,Maoratu's Extra@0,Farewell@0
            354111.osz|Mami Kawada|Sky is the limit (1 Chorus ver.)|BetaStar|1|9.27381|2016-01-17T18:28:49.000Z|354111|365638|0|0|0||Easy@0,moph's Hard@0,Sc4's Normal@0,Kibbleru's Firmament@0
            368704.osz|Henry Fong|Le Disco|Aldwych|1|9.67308|2016-01-16T02:02:32.000Z|368704|378415|0|0|0||Futsuu@1,Muzukashii@1,Oni@1,Kantan@1
            365247.osz|WISE|By your side feat. Kana Nishino|Modem|1|9.215|2016-01-16T19:11:16.000Z|365247|375415|0|0|0||Easy@0,By my side@0,Hard@0,Normal@0,10nya's Light Insane@0
            347719.osz|Demetori|Kuuchuu ni Shizumu Kishinjou ~ Counter-Clock World|jonathanlfj|1|9.52577|2016-01-16T16:55:34.000Z|347719|359613|0|0|0||Extra Stage@0,Lunatic Collab@0
            376075.osz|Camellia|dreamless wanderer|Smoothie World|1|8.76119|2016-01-07T12:21:22.000Z|376075|384976|0|0|0||This is Just a Dream@0
            347021.osz|Demetori|Kagayaku Hari no Kobito-zoku ~ Counter-Attack of the Weak|GoldenWolf|1|9.51064|2016-01-17T04:24:22.000Z|347021|358965|0|0|0||Extra Stage@0
            402995.osz|Taneda Risa|Wareta Ringo (TV edit)|deetz|1|9.23913|2016-01-16T09:56:14.000Z|402995|406864|0|0|0||A Thousand Winds@0,Hard@0,Easy@0,Kamal's Normal@0
            365826.osz|Misato Aki|Glitter|Misure|1|9.27344|2016-01-16T19:13:39.000Z|365826|375960|1|0|0||Insane@0,Garden's Insane@0,Hard@0,Normal@0,imouto's Insane@0,Narcissu's Easy@0,Narcissu's Insane@0
            400208.osz|Hanatan|Uta ni Katachi wa Nai keredo|Asahina Momoko|1|9.62338|2016-01-14T18:36:38.000Z|400208|403942|0|0|0||Recollection@0
            372075.osz|Tamura Yukari|Pleasure treasure|Milan-|1|9.15217|2016-01-13T11:27:44.000Z|372075|380963|0|0|0||lolis@0,easy@0,normal@0,hard@0
            317273.osz|tyDi (Feat. RUNAGROUND)|'Chase You Down'|Stjpa|1|9.26667|2016-01-16T04:21:59.000Z|317273|332448|0|0|0||Rocket's Easy@0,Tragic Love@0,HC's Advanced@0,Normal@0
            332436.osz|wazgul|Po Pi Po (Ryu* Remix) + TF2 [MaD] feat. Ippon Manzoku.|BigEarsMau|1|8.95918|2016-01-12T18:03:09.000Z|332436|345791|1|0|0||Normal@0,zorefire's Easy@0,Baraatje123's Juicer@0,Raiden's Oni@1,Nuo's Hard@0,Amethyst's Muzukashii@1
            248187.osz|u's|Love wing bell (TV Size)|Nardoxyribonucleic|1|9.25714|2016-01-15T16:53:59.000Z|248187|269954|0|0|0||Futsuu@1,Kantan@1,Muzukashii@1,Oni@1
            375265.osz|Grant Kirkhope|Freezeezy Peak|-Tenshi-|1|9.22222|2016-01-15T13:59:47.000Z|375265|384220|0|0|0||Oni@1,Muzukashii@1,Futsuu@1,Kantan@1
            375264.osz|Plasmagica|Have a nice MUSIC!!(TVedit)|ErunamoJAZZ|1|9.33333|2016-01-15T13:05:53.000Z|375264|384218|1|0|0||Advanced@0,Hard@0,Neku's Normal@0,pkhg's Insane@0,KwaN's Easy@0
            336414.osz|Wagakki Band|Tengaku|Shiro|1|7.20455|2016-01-14T21:41:14.000Z|336414|349221|0|0|0||Uncompressed Fury of a Raging Japanese God@0
            350161.osz|Lia|Bravely You|CelsiusLK|1|9.32727|2016-01-10T16:05:30.000Z|350161|361947|0|0|0||Desire@0
            393865.osz|Royal Republic|Addictive|-Nya-|1|8.77436|2016-01-13T20:06:04.000Z|393865|397293|0|0|0||Obsession@0,Hard@0,Easy@0,Normal@0
            387784.osz|Shawn Wasabi|Marble Soda|Len|1|9.5509|2016-01-12T13:55:01.000Z|387784|393386|0|0|0||Hard@0,Normal@0,Crier's Extra@0,Crier's Hyper@0,Narcissu's Insane@0
            331376.osz|yanaginagi|Haru Modoki|Labyr|1|8.85294|2016-01-12T03:06:07.000Z|331376|344873|0|0|0||Oni@1,Muzukashii@1,Futsuu@1,chaica's Kantan@1,Lundle's Oni@1
            381390.osz|Ayane|Eien no Memories|Shad0w1and|1|8.62|2016-01-12T11:26:45.000Z|381390|388923|1|0|0||Hard@0,Insane@0,Normal@0,ZZHBOY's Advanced@0
            378596.osz|KEYTALK|STARRING STAR|Kagetsu|1|9.05303|2016-01-11T09:16:35.000Z|378596|386911|1|0|0||Insane@0,Easy@0,Rocket's Normal@0,Marianna's Hard@0
            397016.osz|NICO Touches the Walls|Tenchi Gaeshi|-Nya-|1|9.02614|2016-01-12T01:44:56.000Z|397016|400581|1|0|0||Insane@0,Easy@0,Hard@0,Normal@0
            388858.osz|ave;new feat. Sakura Saori|snow of love|chaica|1|9.05714|2016-01-10T21:13:34.000Z|388858|394123|0|0|0||Oni@1,Futsuu@1,Kantan@1,Muzukashii@1
            ";
        return $query;
    }
}
