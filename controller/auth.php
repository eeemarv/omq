<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;

class auth
{

	/**
	 *
	 */

	public function login(Request $request, app $app)
	{
		$data = [
			'email'		=> '',
			'password'	=> '',
		];

		$form = $app->form($data)
			->add('email', EmailType::class, [
				'constraints' => new Assert\Email(),
			])
			->add('password', PasswordType::class, [
				'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 6])],
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();





			return $app->redirect('edit');
		}

		return $app['twig']->render('auth/login.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function register(Request $request, app $app)
	{
		$data = [
//			'username'	=> '',
			'email'		=> '',
			'password'	=> '',
			'accept'	=> false,
		];

		$form = $app->form($data)

/*
			->add('username', TextType::class, [
				'constraints'	=> [
					new Assert\Length(['min' => 2, 'max' => 6]),
					new Assert\Regex('[a-z0-9-]'),
				]
 			])
 */

			->add('email', EmailType::class, [
				'constraints' => new Assert\Email(),
			])
			->add('password', PasswordType::class, [
				'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 6])],
			])
			->add('accept', CheckboxType::class, [
				'constraints' => new Assert\IsTrue(),
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$data['subject'] = 'mail_register_confirm.subject';
			$data['template'] = 'register_confirm';
			$data['to'] = $data['email'] = strtolower($data['email']);

			$token = $app['token']->set_length(20)->gen();

			$data['url'] = $app->url('register_confirm', ['token' => $token]);
			$data['token'] = $token;

			$redis_key = 'register_confirm_' . $token;
			$app['predis']->set($redis_key, json_encode($data));
			$app['predis']->expire($redis_key, 14400);

			$app['mail']->queue_priority($data);

			$app['session']->getFlashBag()->add('success', $app->trans('register.success'));
			return $app->redirect('login');
		}

		return $app['twig']->render('auth/register.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function register_confirm(Request $request, app $app, $token)
	{
		$redis_key = 'register_confirm_' . $token;
		$data = $app['predis']->get($redis_key);

		if (!$data)
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'register_confirm.not_found.subject',
				'text'		=> 'register_confirm.not_found.text',
			]);
		}

		$data = json_decode($data, true);

		$count = $app['xdb']->search(['email' => $data['email'], 'type' => 'user']);

		if ($count)
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'register_confirm.already_done.subject',
				'text'		=> 'register_confirm.already_done.text',
			]);
		}

		do
		{
			$id = $app['uuid']->gen();
			$exists = $app['xdb']->exists($id);
		}
		while ($exists);

		$data['uuid'] = $id;
		$data['type'] = 'user';

		$app['xdb']->set('user_' . $id, $data);

		$app['predis']->del($redis_key);

		return $app['twig']->render('page/panel_success.html.twig', [
			'subject'	=> 'register_confirm.success.subject',
			'text'		=> 'register_confirm.success.text',
		]);
	}

	/**
	 *
	 */

	public function password_reset(Request $request, app $app)
	{
		$data = [
			'email'	=> '',
		];

		$form = $app->form($data)
			->add('email', EmailType::class, [
				'constraints' => new Assert\Email(),
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$email = strtolower($data['email']);




			return $app->redirect('/edit');
		}

		return $app['twig']->render('auth/password_reset.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function password_reset_token(Request $request, app $app, $token)
	{


		return $app['twig']->render('alert.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function new_password(Request $request, app $app)
	{
		$data = [
			'password'	=> '',
		];

		$form = $app->form($data)
			->add('password', PasswordType::class)
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('/edit');
		}

		return $app['twig']->render('auth/new_password.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function post(Request $request, app $app)
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

		$key = 'login_token_' . $token;

		$app['predis']->set($key, $email);
		$app['predis']->expire($key, 14400); // 4 hours;

		$host = $request->getHost();

		$app['mail']->queue([
			'template'	=> 'login_token',
			'to'		=> $email,
			'url'		=> $host . '/' . $token,
		]);

		return $app->json(['notice' => $app->trans('notice.token_send_email')]);
	}

	/**
	 *
	 */

	public function token(Request $request, app $app, $token)
	{
		$edit_login = $app['xdb']->get('edit_login_' . $token);

		$app['session']->set('edit_login', $edit_login);

		return $app->redirect('edit');
	}
}

