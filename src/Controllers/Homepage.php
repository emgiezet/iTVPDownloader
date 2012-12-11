<?php
namespace iTVPDownloader\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class HomepageControllerProvider implements ControllerProviderInterface
{
	public function connect(Application $app)
	{
		// creates a new controller based on the default route
		$controllers = $app['controllers_factory'];

		$controllers->get('/', function (Application $app) {
			return $app->redirect('/hello');
		});

		return $controllers;
	}
}