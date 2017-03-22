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
$boot = $app['xdb']->get('boot')['version'];

echo 'mail service started .. ' . $boot . "\n";

$loop_count = 1;

$domain = getenv('DOMAIN');

$from_noreply_address = getenv('MAIL_NOREPLY_ADDRESS');

while (true)
{
	sleep(1);

	if ($loop_count % 3600 === 0)
	{
		error_log('..mail.. ' . $boot . ' .. ' . $loop_count);
	}

	$loop_count++;

	$mail = $app['redis']->rpop('email_queue');

	if (!$mail)
	{
		continue;
	}

	$mail_ary = json_decode($mail, true);

	$to = $mail_ary['to'];
	$template = $mail_ary['template'];

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

	$template_html = $this->twig->loadTemplate('mail_' . $template . '.html.twig');
	$template_text = $this->twig->loadTemplate('mail_' . $template . '.text.twig');

	$text = $template_text->render($mail_ary);
	$html = $template_html->render($mail_ary);

	$message = \Swift_Message::newInstance()
		->setSubject('Plan A, Community Way, verify your email address')
		->setBody($text)
		->addPart($html, 'text/html')
		->setTo($to)
		->setFrom($from_noreply_address);

	if ($mailer->send($message, $failed_recipients))
	{
		$app['monolog']->debug('mail verify send to ' . $to);
	}
	else
	{
		$app['monolog']->error('mail error: failed sending verify to ' . $to);
	}

	$mailer->getTransport()->stop();
}
