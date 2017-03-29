<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

$app = require_once __DIR__ . '/../app.php';

//

$app->get('/vote', function (Request $request) use ($app)
{
    return $app['twig']->render('vote.html.twig', []);
});

$app->get('/business', function (Request $request, Application $app)
{
	$projects = $app['redis']->get('projects_enc');

	if (!$projects)
	{
		// get from xdb.
	}

	$projects = htmlspecialchars('{"druppie": "hoepla"}');

    return $app['twig']->render('index.html.twig', [
		'projects'	=> $projects,
	]);

})->bind('business');

$app->get('/{token}', 'controller\\vote::token')->assert('token', '[a-z0-9-]{8}');

$app->get('/edit', function (Request $request, Application $app)
{
	$edit_project = $app['session']->get('edit_project');

    return $app['twig']->render('edit.html.twig', ['edit_project' => $edit_project]);
});

$app->match('/login', 'controller\\login::login');

$app->get('/what', function (Request $request) use ($app)
{
    return $app['twig']->render('what.html.twig', [

	]);
})->bind('what');

$app->get('/qr', 'controller\\pay::qr');

$app->get('/{token}', 'controller\\login::token')->assert('token', '[a-z0-9-]{12}');

$app->post('/edit/load-img', 'controller\\edit::load_img');

$app->match('/admin', 'controller\\admin::settings');
$app->match('/admin/editor/{id}', 'controller\\admin::editor')->assert('id', '\d+');


$app->get('/', function (Request $request) use ($app)
{


    return $app['twig']->render('base.html.twig', [

	]);
});

$app->run();
