<?php

use Aws\S3\S3Client;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = getenv('DEBUG');

$app['redis'] = function () {
	try
	{
		$url = getenv('REDIS_URL') ?: getenv('REDISCLOUD_URL');
		$con = parse_url($url);

		if (isset($con['pass']))
		{
			$con['password'] = $con['pass'];
		}

		$con['scheme'] = 'tcp';

		return new Predis\Client($con);
	}
	catch (Exception $e)
	{
		echo 'Couldn\'t connected to Redis: ';
		echo $e->getMessage();
		exit;
	}
};

$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => [
        'url'   => getenv('DATABASE_URL'),
    ],
]);

$app->register(new Silex\Provider\TwigServiceProvider(), [
	'twig.path' => __DIR__ . '/view',
	'twig.options'	=> [
		'cache'		=> __DIR__ . '/cache',
		'debug'		=> getenv('DEBUG'),
	],
]);

$app->extend('twig', function($twig, $app) {
    $twig->addGlobal('s3_img', getenv('S3_IMG'));
    $twig->addGlobal('projects', $app['xdb']->get('projects'));
    return $twig;
});

$app->register(new Silex\Provider\SecurityServiceProvider(), [

	'security.firewalls' => [
		'admin' 	=> [
			'pattern' 	=> '^/admin',
			'http'		=> true,
			'users' 	=> [
				'admin' 	=> ['ROLE_ADMIN', getenv('ADMIN_PASSWORD')],
			],
		],
		'editor'	=> [
			'pattern'	=> '^/edit',
			'users'		=> [],

		],
	],

	'security.role_hierarchy' => [
		'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'],
	],

]);

$app->register(new Silex\Provider\FormServiceProvider());

$app->register(new Silex\Provider\ValidatorServiceProvider());

$app->register(new Silex\Provider\MonologServiceProvider(), []);

$app->extend('monolog', function($monolog, $app) {

	$monolog->setTimezone(new DateTimeZone('UTC'));

	$handler = new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG);
	$handler->setFormatter(new \Bramus\Monolog\Formatter\ColoredLineFormatter());
	$monolog->pushHandler($handler);

	return $monolog;
});

$app->register(new Silex\Provider\AssetServiceProvider(), [
	'assets.version' => '1',
	'assets.version_format' => '%s?v=%s',
	'assets.base_path'	=> '/assets',
]);

$app->register(new Silex\Provider\LocaleServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => ['nl', 'en'],
));

use Symfony\Component\Translation\Loader\YamlFileLoader;

$app->extend('translator', function($translator, $app) {

	$translator->addLoader('yaml', new YamlFileLoader());

	$translator->addResource('yaml', __DIR__.'/translations/en.yml', 'en');
	$translator->addResource('yaml', __DIR__.'/translations/nl.yml', 'nl');

	return $translator;
});


$app->register(new Silex\Provider\SessionServiceProvider(), [
	'session.storage.handler'	=> new service\redis_session($app['redis']),
	'session.storage.options'	=> [
		'name'						=> 'cwvote',
//		'cookie_domain'				=> '.' . getenv('OVERALL_DOMAIN'),
		'cookie_lifetime'			=> 172800,
	],
]);

$app['xdb'] = function($app){
	return new service\xdb($app['db'], $app['redis'], $app['monolog']);
};

$app['s3'] = function($app){
	return new service\s3($app['monolog']);
};

$app['token'] = function($app){
	return new service\token();
};

$app['redis_session'] = function($app){
	return new service\redis_session($app['redis']);
};


return $app;
