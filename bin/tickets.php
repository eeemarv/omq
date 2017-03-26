<?php

$app = require_once __DIR__ . '/../app.php';

$page_count = $_GET['pages'] ?? 2;
$tickets_per_page = $_GET['per_page'] ?? 10;
$hash_length = $_GET['hash_length'] ?? 8;
$hyphen_chance = $_GET['hyphen_chance'] ?? 1;
$err_corr = $_GET['err_corr'] ?? 'Q';

$app->boot();

$app['token']->set_length($hash_length);
$app['token']->set_hyphen_chance($hyphen_chance);

$app['translator']->setLocale('nl');

ini_set('max_execution_time', 600);

$time_pre = microtime(true);

$url = 'https://a.omdev.be/';

$pages = [];



for ($p = 0; $p < $page_count; $p++)
{
	$tickets = [];

	for ($i = 0; $i < $tickets_per_page; $i++)
	{
		$token = $app['token']->gen();

		$tickets[] = $url . $token;
	}

	$pages[] = [
		'tickets'	=> $tickets
	];
}

$html = $app['twig']->render('tickets.html.twig', [
	'pages'		=> $pages,
	'err_corr'	=> $err_corr,
]);

$mpdf = new mPDF('', 'A4', 0, '', 0, 0, 0, 0, 0, 'L');

$css = file_get_contents('tickets.css');

$mpdf->WriteHTML($css, 1);

$mpdf->WriteHTML($html);

$mpdf->SetTitle('cw tickets, batch-0');
$mpdf->SetAuthor('cwvote');
$mpdf->SetCreator('cwvote');
$mpdf->SetSubject('cw tickets');
$mpdf->SetKeywords('cw tickets');

$time_post = microtime(true);

if ($_GET['file'])
{
	$mpdf->Output('./../tickets/batch-0.pdf', 'F');
}
else
{
	// Output a PDF file directly to the browser
	$mpdf->Output();
}

error_log('time: ' . ($time_post - $time_pre));