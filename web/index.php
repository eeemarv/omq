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

//

/*

$app->get('/p', function (Request $request) use ($app)
{
	$projects = $app['xdb']->get('projects');

	$response = new Response();
	$response->setContent($projects);
	$response->headers->set('Content-Type', 'application/json');

	return $response;
}

*/

$app->get('/p', function (Request $request) use ($app)
{
	$token = $app['security.token_storage']->getToken();


	$ret =  'token: ' . $token;
	if (null !== $token)
	{
		$user = $token->getUser();

		$ret .= 'user';
	}

	$ret =  'token: ' . $token;
	if (null !== $token)
	{
		$user = $token->getUser();

		$ret .= ' - user: ' . $user;
		$ret .= ' - salt: ' . $user->getSalt();
	}



		// find the encoder for a UserInterface instance
	$encoder = $app['security.encoder_factory']->getEncoder($user);

	//$ret .= ' - enc: ' . $encoder;

	// compute the encoded password for foo
	$password = $encoder->encodePassword($_GET['pass'], '');

	$ret .= "\n";
	$ret .= 'password: ' . $password;

	return $ret;
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

$app->match('/login', 'controller\\login::login');

$app->get('/{token}', 'controller\\login::token')->assert('token', '[a-z0-9-]{12}');



$app->post('/edit/load-img', 'controller\\edit::load_img');

$app->get('/admin', function (Request $request) use ($app)
{

	$editors = $app['xdb']->get('project_editors');

	$token = $app['security.token_storage']->getToken();


	$ret =  'token: ' . $token;
	if (null !== $token)
	{
		$user = $token->getUser();

		$ret .= ' - user: ' . $user;
		$ret .= ' - salt: ' . $user->getSalt();
	}



		// find the encoder for a UserInterface instance
	$encoder = $app['security.encoder_factory']->getEncoder($user);

	//$ret .= ' - enc: ' . $encoder;

	// compute the encoded password for foo
	$password = $encoder->encodePassword($_GET['pass'], '');

	$ret .= "\n";
	$ret .= 'password: ' . $password;

	return $ret;

    return $app['twig']->render('admin.html.twig', []);
});

$app->get('/', function (Request $request) use ($app)
{


    return $app['twig']->render('base.html.twig', [

	]);
});

$app->run();
