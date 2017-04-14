<?php

$app = require_once __DIR__ . '/../app.php';

if (php_sapi_name() !== 'cli')
{
	echo '-- cli only --';
	exit;
}

$enc = getenv('SMTP_ENC') ?: 'tls';
$transport = \Swift_SmtpTransport::newInstance(getenv('SMTP_HOST'), getenv('SMTP_PORT'), $enc)
	->setUsername(getenv('SMTP_USERNAME'))
	->setPassword(getenv('SMTP_PASSWORD'));

$mailer = \Swift_Mailer::newInstance($transport);

$mailer->registerPlugin(new \Swift_Plugins_AntiFloodPlugin(100, 30));

$mailer->getTransport()->stop();

$app['xdb']->set('boot', []);
$boot = json_decode($app['xdb']->get('boot'), true)['version'];

$app['monolog']->debug('mail service started .. ' . $boot);

$loop_count = 1;

$domain = getenv('DOMAIN');

$from_noreply_address = getenv('MAIL_NOREPLY_ADDRESS');

$app->boot();

while (true)
{
	sleep(1);

	if ($loop_count % 1800 === 0)
	{
		error_log('..mail.. ' . $boot . ' .. ' . $loop_count);
	}

	$loop_count++;

	$mail = $app['predis']->rpop('mail_queue_high_priority');

	if (!$mail)
	{
		$mail = $app['predis']->rpop('mail_queue_low_priority');
	}

	if (!$mail)
	{
		continue;
	}

	$mail_ary = json_decode($mail, true);

	$to = $mail_ary['to'];
	$template = $mail_ary['template'];
	$subject = $mail_ary['subject'];

	if (!$to)
	{
		$app['monolog']->error('mail error: no "to" address');
		continue;
	}

	if (!$template)
	{
		$app['monolog']->error('mail error: no template');
		continue;
	}

	if (!$subject)
	{
		$app['monolog']->error('mail error: no subject');
		continue;
	}

	$template_html = $app['twig']->loadTemplate('mail/' . $template . '.html.twig');
	$template_text = $app['twig']->loadTemplate('mail/' . $template . '.text.twig');

	$text = $template_text->render($mail_ary);
	$html = $template_html->render($mail_ary);

	$message = \Swift_Message::newInstance()
		->setSubject($app->trans($subject))
		->setBody($text)
		->addPart($html, 'text/html')
		->setTo($to)
		->setFrom($from_noreply_address);

	if ($mailer->send($message, $failed_recipients))
	{
		$app['monolog']->debug('mail ' . $template . ' send to ' . $to);
	}
	else
	{
		$app['monolog']->error('mail error: failed sending verify to ' . $to);
	}

	$mailer->getTransport()->stop();
}
