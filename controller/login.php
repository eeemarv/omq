<?php

namespace controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class login
{
	public function login(Request $request, Application $app)
	{
		$data = ['email'	=> ''];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('email', EmailType::class)
			->add('submit', SubmitType::class, [
				'label' => 'Save',
			])
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('/edit');
		}

		return $app['twig']->render('login/login.html.twig', ['form' => $form->createView()]);
	}

	public function register(Request $request, Application $app)
	{
		$data = ['email'	=> ''];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('email', EmailType::class)
			->add('submit', SubmitType::class, [
				'label' => 'Save',
			])
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('/edit');
		}

		return $app['twig']->render('login/register.html.twig', ['form' => $form->createView()]);
	}

	public function password_reset(Request $request, Application $app)
	{
		$data = ['email'	=> ''];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('email', EmailType::class)
			->add('submit', SubmitType::class, [
				'label' => 'Save',
			])
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('/edit');
		}

		return $app['twig']->render('login/password_reset.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function post(Request $request, Application $app)
	{
		$email = $request->get('email');

		$errors = $app['validator']->validate($email, new Assert\Email());

		if ($errors > 0)
		{
			$app['monolog']->info('unvalid email: ' . $email . ' - ' . (string) $errors);

			return $app->json(['notice' => $app->trans('notice.unvalid_email')]);
		}

		$editors = $app['xdb']->get('project_editors');

		if (!isset($editors[$email]))
		{
			$app['monolog']->info('no access for email: ' . $email);

			return $app->json(['notice' => $app->trans('notice.no_access_email')]);
		}

		$token = $app['token']->set_length(12)->gen();

		$key = 'cwv_login_token_' . $token;

		$app['redis']->set($key, $email);
		$app['redis']->expire($key, 14400); // 4 hours;

		$host = $request->getHost();

		$app['redis']->lpush('cwv_email_queue', json_encode([
			'template'	=> 'login_token',
			'to'		=> $email,
			'url'		=> $host . '/' . $token,
		]));

		return $app->json(['notice' => $app->trans('notice.token_send_email')]);
	}

	public function token(Request $request, Application $app, $token)
	{
		$edit_login = $app['xdb']->get('edit_login_' . $token);

		$app['session']->set('edit_login', $edit_login);

		return $app->redirect('/edit');
	}
}

