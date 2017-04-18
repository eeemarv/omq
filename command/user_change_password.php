<?php

namespace command;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class user_change_password extends Command
{
    protected function configure()
    {
		$this->setName('user:change-password')
			->setDescription('Change the password of a user')
			->setHelp('Change the password of a user.')
			->addArgument('username',
				InputArgument::REQUIRED,
				'The username.'
			)
			->addArgument('password',
				InputArgument::REQUIRED,
				'The new password (must be at least 6 characters)'
			);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

		$username = $input->getArgument('username');
		$password = $input->getArgument('password');

		$io = new SymfonyStyle($input, $output);

		$app = $this->getContainer();

		$found = $app['xdb']->search(['username' => $username])

		if (!$found)
		{
			$io->error('This user does not seem to exist');
			return;
		}

		if (strlen($password) < 6)
		{
			$io->error('The password must be at least 6 characters');
			return;
		}


    }
}