<?php

namespace controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class pay
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

	public function qr(Request $request, Application $app)
	{
		$token = $app['token']->set_hyphen_chance(9)->set_length(10)->gen();


		return $app['twig']->render('pay/qr.html.twig', [

			'voucher_url'	=> 'https://omdev.be/' . $token,
			'amount'		=> 30,
			'unit'			=> 'Ant',

		]);
	}

	public function pay(Request $request, Application $app)
	{
		$editors = $app['xdb']->get('project_editors');
		$settings = $app['xdb']->get('settings');

		$settings = [
			'editors'				=> '',
			'max_projects_default'	=> 5,
		];

		$builder = $app['form.factory']->createBuilder(FormType::class, $settings);

		$builder->add('amount', NumberType::class)
			->add('submit',SubmitType::class);


/*
			->add('editors', TextareaType::class)
			->add('default_max_projects', NumberType::class)
			->add('submit', SubmitType::class, [
				'label' => 'Save',
			])*/

		$form = $builder->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('/edit');
		}

		return $app['twig']->render('admin/settings.html.twig', [
			'form' 		=> $form->createView(),
			'editors'	=> $editors,
		]);


//
		$token = $app['token']->set_hyphen_chance(9)->set_length(12)->gen();


		return $app['twig']->render('pay/pay.html.twig', [
			'unit'			=> 'Ant',
		]);
	}

}

