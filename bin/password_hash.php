<?php

$app = require_once __DIR__ . '/../app.php';

$password = $argv[1] ?? false;

if (!$password)
{
	echo 'password is missing.';
}

$encoder = new Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder(13);

$hash = $encoder->encodePassword($password, '');

echo  'hash password: ' . $hash;
echo "\n";

exit;
