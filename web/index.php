<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

$app = require_once __DIR__ . '/../app.php';

$app->register(new Silex\Provider\SecurityServiceProvider(), [
	'security.firewalls' => [
		'admin' 	=> [
			'pattern' 	=> '^/admin',
			'http' 		=> true,
			'users' 	=> [
				'admin' 	=> ['ROLE_ADMIN', '$2y$10$3i9/lVd8UOFIJ6PAMFt8gu3/r5g0qeCJvoSlLCsvMTythye19F77a'],
			],
		],
	],

	'security.role_hierarchy' => [
		'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'],
	],

]);

//

$app->get('/vote', function (Request $request) use ($app)
{
    return $app['twig']->render('vote.html.twig', []);
});

//

/*
$app->get('/projects', function (Request $request) use ($app)
{
	$projects = $app['xdb']->get('projects');

	$response = new Response();
	$response->setContent($projects);
	$response->headers->set('Content-Type', 'application/json');

	return $response;
}
*/
//

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

});

//

$app->get('/{token}', 'controller\\vote::token')->assert('token', '[a-z0-9-]{8}');

//

$app->get('/edit', function (Request $request, Application $app)
{
	$edit_project = $app['session']->get('edit_project');

    return $app['twig']->render('edit.html.twig', ['edit_project' => $edit_project]);
});

//

$app->post('/login-token', 'controller\\edit::login_token');

//

$app->get('/{token}', function (Request $request, Application $app, $token)
{
	$edit_login = $app['xdb']->get('edit_login_' . $token);

	$app['session']->set('edit_login', $edit_login);

	return $app->redirect('/edit');

})->assert('token', '[a-z0-9-]{12}');



$app->post('/img', 'controller\\edit::img');

$app->get('/admin', function (Request $request) use ($app)
{

	$editors = $app['xdb']->get('project_editors');



    return $app['twig']->render('admin.html.twig', []);
});

$app->get('/', function (Request $request) use ($app)
{


    return $app['twig']->render('base.html.twig', [

	]);
});

$app->run();
