<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => '/setupHat'], function() use($router){
            $router->post('', 'HatSetupController@setUpHatLR');
            $router->put('/{id}', 'HatSetupController@updateHatLRSetup');
            $router->get('', 'HatSetupController@getAllHatSetups');
            $router->get('/{id}', 'HatSetupController@getHatSetup');
            $router->get('/hatTable', 'HatSetupController@getHatTable');
            $router->get('/hatChart', 'HatSetupController@getHatChart');
            //$router->get('/switchHatParent', 'HatSetupController@switchHatParent');
 });

 // Hat Routes
 $router->group(['prefix' => '/hats'], function() use($router){
$router->get('/', 'HatController@getHats');
$router->post('/', 'HatController@addHat');
$router->get('/completeHat', 'HatController@completeHat');
$router->post('/setUpPc', 'HatController@setUpHatPC');
$router->post('/setUpLR', 'HatController@setUpHatLR');
$router->post('/setUpPersonnel', 'HatController@setUpPersonnel');
$router->put('/{id}', 'HatController@updateHat');
$router->put('/update/{id}', 'HatController@updateCompleteHat');
$router->get('/{id}', 'HatController@getHat');
$router->delete('/personnelHats/{id}', 'HatController@deletePersonnelHats');
$router->post('/import', 'HatController@importData');
$router->post('/importHat', 'HatController@importHatData');
 });


// $router->delete('/hats/{id}', 'HatController@deleteHat');

// Hat Level Routes
$router->group(['prefix' => '/hatLevels'], function() use($router){
        $router->get('', 'HatLevelController@getHatLevels');
        $router->post('', 'HatLevelController@addHatLevel');
        $router->get('/{id}', 'HatLevelController@getHatLevel');
        $router->put('/{id}', 'HatLevelController@updateHatLevel');
        $router->delete('/{id}', 'HatLevelController@deleteHatLevel');
 });

// Hat Rank Routes

$router->group(['prefix' => '/hatRanks'], function() use($router){
    $router->get('', 'HatRankController@getHatRanks');
    $router->post('', 'HatRankController@addHatRank');
    $router->get('/{id}', 'HatRankController@getHatRank');
    $router->put('/{id}', 'HatRankController@updateHatRank');
    $router->delete('/{id}', 'HatRankController@deleteHatRank');
});


// Hatting Chart

$router->get('/hattingChart', 'HatController@hattingChart');
$router->get('/hattingTable', 'HatController@hattingTable');
$router->get('/getAllHats', 'HatController@getAllHats');
$router->get('/hatDetails/{id}', 'HatController@getHatDetails');
