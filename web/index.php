<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use util\app;

$app = require_once __DIR__ . '/../app.php';

//

$app->get('/vote', function (Request $request) use ($app)
{
    return $app['twig']->render('vote.html.twig', []);
});

$app->get('/business', function (Request $request, app $app)
{
	$projects = $app['predis']->get('projects_enc');

	if (!$projects)
	{
		// get from xdb.
	}

	$projects = htmlspecialchars('{"druppie": "hoepla"}');

    return $app['twig']->render('index.html.twig', [
		'projects'	=> $projects,
	]);

})->bind('business');

$app->match('/login', 'controller\\auth::login')->bind('login');

$app->match('/register', 'controller\\auth::register')->bind('register');
$app->get('/register-sent', 'controller\\auth::register_sent')
	->bind('register_sent');
$app->get('/register/{token}', 'controller\\auth::register_confirm')
	->assert('token', '[a-z0-9-]{20}')->bind('register_confirm');

$app->match('/password-reset', 'controller\\auth::password_reset_request')
	->bind('password_reset_request');
$app->get('/password-reset-sent', 'controller\\auth::password_reset_sent')
	->bind('password_reset_sent');
$app->match('/password-reset/{token}', 'controller\\auth::password_reset')
	->assert('token', '[a-z0-9-]{20}')->bind('password_reset');

$app->get('/terms', 'controller\\page::terms')->bind('terms');

$app->get('/{token}', 'controller\\vote::token')->assert('token', '[a-z0-9-]{8}');

$app->get('/edit', function (Request $request, app $app)
{
	$edit_project = $app['session']->get('edit_project');

    return $app['twig']->render('edit.html.twig', ['edit_project' => $edit_project]);
});


$app->get('/what', function (Request $request) use ($app)
{
    return $app['twig']->render('what.html.twig', [

	]);
})->bind('what');

$app->get('/qr', 'controller\\pay::qr');
$app->get('/{token}', 'controller\\pay::token')->assert('token', '[a-z0-9-]{10}');

$app->get('/{token}', 'controller\\login::token')->assert('token', '[a-z0-9-]{12}');

$app->post('/edit/load-img', 'controller\\edit::load_img');

$app->match('/admin', 'controller\\admin::settings');
$app->match('/admin/editor/{id}', 'controller\\admin::editor')->assert('id', '\d+');

$app->match('/pay', 'controller\\pay::pay')->bind('pay');
$app->match('/', 'controller\\pay::pay')->bind('pay');

$app->run();
