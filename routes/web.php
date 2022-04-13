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

 // Hat Routes
$router->get('/hats', 'HatController@getHats');
$router->get('/hats/{id}', 'HatController@getHat');
$router->post('/hats', 'HatController@addHat');
//$router->get('/hats/org_chart', 'HatController@getOrgChart');
$router->post('/hats/setUpPc', 'HatController@setUpHatPC');
$router->post('/hats/setUpLR', 'HatController@setUpHatLR');
$router->post('/hats/setUpPersonnel', 'HatController@setUpPersonnel');
 $router->put('/hats/{id}', 'HatController@updateHat');
// $router->delete('/hats/{id}', 'HatController@deleteHat');

// Hat Level Routes

$router->get('/hatlevels', 'HatLevelController@getHatLevels');
$router->get('/hatlevels/{id}', 'HatLevelController@getHatLevel');
$router->post('/hatlevels', 'HatLevelController@addHatLevel');
$router->put('/hatlevels/{id}', 'HatLevelController@updateHatLevel');
$router->delete('/hatlevels/{id}', 'HatLevelController@deleteHatLevel');

// Hat Rank Routes

$router->get('/hatranks', 'HatRankController@getHatRanks');
$router->get('/hatranks/{id}', 'HatRankController@getHatRank');
$router->post('/hatranks', 'HatRankController@addHatRank');
$router->put('/hatranks/{id}', 'HatRankController@updateHatRank');
$router->delete('/hatranks/{id}', 'HatRankController@deleteHatRank');


// Hatting Chart

$router->get('/hattingChart', 'HatController@hattingChart');