<?php

namespace command;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class user_create extends Command
{
    protected function configure()
    {
		$this->setName('user:create')
			->setDescription('Creates a new user')
			->setHelp('Creates a new user with role, username, email and password.')
			->addOption('username',
				'u',
				InputOption::VALUE_REQUIRED,
				'A short name for the user of maximum 6 characters consisting lowercase characters or numbers. Hyphens in between characters are also allowed.',
				null
			)
			->addOption('password',
				'p',
				InputOption::VALUE_REQUIRED,
				'A password of minimum 6 characters',
				null
			)
			->addOption('email',
				'm',
				InputOption::VALUE_REQUIRED,
				'A valid unique email address',
				null
			)
			->addOption('role',
				'r',
				InputOption::VALUE_REQUIRED,
				'A role for the user (lowercase)',
				null
			);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

		$username = $input->getOption('username');
		$password = $input->getOption('password');
		$email = $input->getOption('email');
		$role = $input->getOption('role');


		$io = new SymfonyStyle($input, $output);
		$io->title('Create a new user');

		if (!$username)
		{
			$username = $io->ask('username', null);
		}

		if (!$password)
		{
			$password = $io->askHidden('password', null);
		}

		if (!$email)
		{
			$email = $io->ask('email', null);
		}

		if (!$role)
		{
			$role = $io->ask('role', null);
		}


    }
}