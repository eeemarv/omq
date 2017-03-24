<?php

namespace controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class vote
{
	public function vote(Request $request, Application $app)
	{

	}

	/**
	 *
	 */

	public function token(Request $request, Application $app, $token)
	{
		$ticket = json_decode($app['xdb']->get('ticket_' . $token), true);

		$app['session']->set('ticket', $ticket);

		return $app->redirect('/vote');
	}

	public function edit__dd(Request $request, Application $app)
	{

	}
}

