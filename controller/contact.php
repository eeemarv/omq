<?php

namespace controller;

use util\app;
use util\user;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;

class contact
{
	/**
	 *
	 */

	public function contact(Request $request, app $app)
	{
		$data = [
			'email'		=> '',
			'message'	=> '',
		];

		$form = $app->form($data)
			->add('email', EmailType::class, [
				'constraints' => new Assert\Email(),
			])

			->add('message', TextareaType::class, [
				'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 20, 'max' => 2000])],
			])

			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$data['subject'] = 'mail_contact_confirm.subject';
			$data['top'] = 'mail_contact_confirm.top';
			$data['bottom'] = 'mail_contact_confirm.bottom';
			$data['template'] = 'link';
			$data['to'] = $data['email'] = strtolower($data['email']);

			$token = $app['token']->set_length(20)->gen();

			$data['url'] = $app->url('contact_confirm', ['token' => $token]);
			$data['token'] = $token;

			$redis_key = 'contact_confirm_' . $token;
			$app['predis']->set($redis_key, json_encode($data));
			$app['predis']->expire($redis_key, 14400);

			$app['mail']->queue_priority($data);

			$app['session']->getFlashBag()->add('warning', $app->trans('contact.confirm_email_sent'));

			return $app->redirect($app->path('index'));
		}

		return $app['twig']->render('contact/contact.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function contact_confirm(Request $request, app $app, string $token)
	{
		$redis_key = 'contact_confirm_' . $token;
		$data = $app['predis']->get($redis_key);

		if (!$data)
		{
			$app['session']->getFlashBag()->add('error', $app->trans('contact.confirm_not_found'));

			return $app->redirect($app->path('index'));
		}

		$data = json_decode($data, true);

		$email = strtolower($data['email']);

		$app['xdb']->set('contact', $data['email'], ['text' => $data['text']]);

		$app['predis']->del($redis_key);

		$app['mail']->queue([
			'to'		=> getenv('MAIL_CONTACT'),
			'template'	=> 'contact',
			'text'		=> $data['text'],
			'reply_to'	=> $data['mail'],
		]);

		$app['session']->getFlashBag()->add('success', $app->trans('contact.success'));

		return $app->redirect($app->path('index'));
	}
}

