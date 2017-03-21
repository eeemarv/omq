<?php

use Symfony\Component\HttpFoundation\Request;
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
	$projects = $app['redis']->get('projects');

	if (!$projects)
	{

		$projects = [];
		// get from xdb.
	}

    return $app['twig']->render('vote.html.twig', [
		'projects'	=> $projects,
		's3_img'	=> getenv('S3_IMG'),
	]);
});

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

$app->get('/{token}', function (Request $request, Application $app, $token)
{
	$ticket = $app['xdb']->get('ticket_' . $token);

	$app['session']->set('ticket', $ticket);

	return $app->redirect('/');

})->assert('token', '/^[a-z0-9\-]{8}$/');

//

$app->get('/edit', function (Request $request, Application $app)
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

$app->get('/{token}', function (Request $request, Application $app, $token)
{
	$ticket = $app['xdb']->get('ticket_' . $token);

	$app['session']->set('ticket', $ticket);

	return $app->redirect('/');

})->assert('token', '/^[a-z0-9\-]{12}$/');



$app->post('/img', function (Request $request, Application $app){

/*
	$image = ($_FILES['image']) ?: null;

	if (!$image)
	{
		echo json_encode(['error' => 'The image file is missing.']);
		exit;
	}

	$size = $image['size'];
	$tmp_name = $image['tmp_name'];
	$type = $image['type'];

	if ($size > (200 * 1024))
	{
		echo json_encode(['error' => 'The file is too big.']);
		exit;
	}

	if ($type != 'image/jpeg')
	{
		echo json_encode(['error' => 'No valid filetype.']);
		exit;
	}

	$exif = exif_read_data($tmp_name);

	$orientation = $exif['COMPUTED']['Orientation'] ?? false;

	$tmpfile = tempnam(sys_get_temp_dir(), 'img');

	$imagine = new Imagine\Imagick\Imagine();

	$image = $imagine->open($tmp_name);

	switch ($orientation)
	{
		case 3:
		case 4:
			$image->rotate(180);
			break;
		case 5:
		case 6:
			$image->rotate(-90);
			break;
		case 7:
		case 8:
			$image->rotate(90);
			break;
		default:
			break;
	}

	$image->thumbnail(new Imagine\Image\Box(200, 200), Imagine\Image\ImageInterface::THUMBNAIL_INSET);
	$image->save($tmpfile);

	//

	$filename = $id . '_';
	$filename .= substr(sha1($filename . microtime()), 0, 16);
	$filename .= '.' . $ext;

	$err = $app['eland.s3']->img_upload($filename, $tmpfile);

	if ($err)
	{
		$app['monolog']->error('pict: ' .  $err . ' -- ' . $filename);

		$response = ['error' => 'Uploading img failed.'];
	}
	else
	{
		$app['db']->update('users', [
			'"PictureFile"'	=> $filename
		],['id' => $id]);

		$app['monolog']->info('User image ' . $filename . ' uploaded. User: ' . $id);

		readuser($id, true);

		$response = ['success' => 1, 'filename' => $filename];
	}
	*/
});

$app->get('/', function (Request $request) use ($app)
{
	$projects = $app['redis']->get('projects');

	if (!$projects)
	{

		$projects = [];
		// get from xdb.
	}

    return $app['twig']->render('index.html.twig', [
		'projects'	=> $projects,
		's3_img'	=> getenv('S3_IMG'),
	]);
});

$app->run();
