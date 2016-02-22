<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['middleware' => 'web'], function () {
    // Auth
    Route::auth();

    Route::post('/login', 'Auth\customLogin@login');

    // User Avatar for the game
    Route::get('/{user}', function(App\User $user = null) {
        if(isset($user) && $user->avatar != "")
        {
            $img = Image::make($user->avatar)->resize(128, 128);
        } else {
            $img = Image::make(config('bancho.defaultAvatar'));
        }
        return $img->response();
    })->where('user', '[0-9]+');

    Route::get('/w/u/{userid}', 'userProfile@getProfile')->where('userid', '[0-9]+');
    Route::get('/u/{userid}', 'userProfile@getProfile')->where('userid', '[0-9]+');
    Route::get('/u/{username}', 'userProfile@getProfileName')->where('username', '[0-9A-Za-z]+');
    Route::get('/w/u/{username}', 'userProfile@getProfileName')->where('username', '[0-9A-Za-z]+');

    // Dashboard
    Route::get('/dashboard', 'dashboard@index');
    Route::get('/dashboard/avatar', 'dashboard@getAvatarPage');
    Route::post('/dashboard/avatar', 'dashboard@postAvatarPage');

    // Index
    Route::get('/', 'Index@getIndex');
    Route::get('/home', 'HomeController@index');
});
Route::get('/w/web/osu-osz2-getscores.php', "Ranking@getScores");
Route::get('/web/osu-osz2-getscores.php', "Ranking@getScores");
Route::get('/w/d/{beatmapid}', function($beatmapID) {
    return redirect(sprintf('http://bloodcat.com/osu/s/%s', $beatmapID));
})->where('beatmapid', '[0-9]+');
Route::get('/d/{beatmapid}', function($beatmapID) {
    return redirect(sprintf('http://bloodcat.com/osu/s/%s', $beatmapID));
})->where('beatmapid', '[0-9]+');
Route::post('/w/web/osu-submit-modular.php', "Ranking@submitModular");
Route::post('/web/osu-submit-modular.php', "Ranking@submitModular");
Route::get('/w/web/osu-search.php', "Debug@getSearch");
Route::get('/web/osu-search.php', "Debug@getSearch");
Route::get('/w/web/osu-search-set.php', 'Debug@getSearchID');
Route::get('/web/osu-search-set.php', 'Debug@getSearchID');
Route::get('/w/web/lastfm.php', function() {
    return '';
});
Route::get('/web/lastfm.php', function() {
    return '';
});
Route::get('/web/check-updates.php', function() {
    return '[{}]';
});
Route::post('/', 'Index@postIndex');
Route::get('/{section}', 'Debug@getDebug')->where(['section' => '.*']);
Route::post('/{section}', 'Debug@postDebug')->where(['section' => '.*']);
