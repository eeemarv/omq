<?php

namespace controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class login
{

	/**
	 *
	 */

	public function login(Request $request, Application $app)
	{
		$data = [
			'email'		=> '',
			'password'	=> '',
		];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('email', EmailType::class)
			->add('password', PasswordType::class)
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();





			return $app->redirect('/edit');
		}

		return $app['twig']->render('login/login.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function register(Request $request, Application $app)
	{
		$data = [
			'username'	=> '',
			'email'		=> '',
			'agree'		=> false,
			'password'	=> '',
		];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('username', TextType::class)
			->add('email', EmailType::class)
			->add('password', PasswordType::class)
			->add('accept', CheckboxType::class)
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('/edit');
		}

		return $app['twig']->render('login/register.html.twig', ['form' => $form->createView()]);
	}
	/**
	 *
	 */

	public function terms(Request $request, Application $app)
	{
		return $app['twig']->render('login/terms.html.twig', []);
	}

	/**
	 *
	 */

	public function password_reset(Request $request, Application $app)
	{
		$data = [
			'email'	=> '',
		];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('email', EmailType::class)
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$email = strtolower($data['email']);




			return $app->redirect('/edit');
		}

		return $app['twig']->render('login/password_reset.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function password_reset_token(Request $request, Application $app, $token)
	{


		return $app['twig']->render('alert.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function new_password(Request $request, Application $app)
	{
		$data = [
			'password'	=> '',
		];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('password', PasswordType::class)
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('/edit');
		}

		return $app['twig']->render('login/new_password.html.twig', ['form' => $form->createView()]);
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

