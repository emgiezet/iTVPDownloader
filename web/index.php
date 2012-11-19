<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\MonologServiceProvider;

use Buzz\Message;
$app = new Silex\Application();

$app->register(new FormServiceProvider());

$app
		->register(new Silex\Provider\TranslationServiceProvider(),
				array('locale_fallback' => 'en',));

$app
		->register(new Silex\Provider\TwigServiceProvider(),
				array('twig.path' => __DIR__ . '/../src/resources/views',));

$app
		->register(new Silex\Provider\MonologServiceProvider(),
				array('monolog.logfile' => __DIR__ . '/../logs/development.log',));

$app->register(new Silex\Provider\ValidatorServiceProvider());
$app
		->register(new Silex\Provider\TranslationServiceProvider(),
				array('translator.messages' => array(),));
$services = json_decode(getenv("VCAP_SERVICES"));

if(is_array($services))
{
	$redis = $services['redis-2.2'];
}
else {
	$params = 'tcp://127.0.0.1:6379/'
}


$app
		->register(new Predis\Silex\PredisServiceProvider(),
				array('predis.parameters' => $params,
						'predis.options' => array('profile' => '2.2'),));

$app['debug'] = true;

$pages = array('/' => 'homepage', '/error' => 'error');
$params = array();
foreach ($pages as $route => $view) {
	$app
			->match($route,
					function (Request $request) use ($app, $view) {
						switch ($view) {
						case 'homepage':
// 							return var_export($app['predis']->info(), true);
							$data = array();

							$form = $app['form.factory']
									->createBuilder('form', $data)
									->add('url', 'text',
											array(
													'constraints' => new Assert\Url()))
									->getForm();

							if ('POST' == $request->getMethod()) {
								$form->bind($request);

								if ($form->isValid()) {
									$data = $form->getData();

									$name = $data['url'];
									
									$cached = $app['predis']->get('{url:'.urlencode($name).'}:path');
									if($cached === NULL)
									{
										$browser = new Buzz\Browser();
										/**
										@var Buzz\Response
										 */
										$response = $browser->get($name);
	
										$content = $response->getContent();
										$matches = array();
										preg_match_all(
												"/object_id+\:+\'+([0-9]{1,})+\'\,/",
												$content, $matches);
										foreach ($matches as $match) {
											if (is_array($match)) {
												if (intval($match[0]) > 1) {
													$url = 'http://www.tvp.pl/pub/stat/videofileinfo?video_id='
															. $match[0];
													$json = $browser->get($url);
													$json = $json->getContent();
													$data = json_decode($json);
													$video_url = $data->video_url;
													
												}
	
											}
										}
										if (!$video_url) {
											$params['error'] = true;
										} else {
											
											$app['predis']->set('{url:'.urlencode($name).'}:path', $video_url);
											$app['predis']->set('{url:'.urlencode($name).'}:data', $data);
											$app['predis']->incrBy('{url:'.urlencode($name).'}:count',1);
											
										}
									} else {
										$video_url = $app['predis']->get('{url:'.urlencode($name).'}:path');
										$data = $app['predis']->get('{url:'.urlencode($name).'}:data');
										$app['predis']->incrBy('{url:'.urlencode($name).'}:count',1);
										$count = $app['predis']->get('{url:'.urlencode($name).'}:count');
									}
									$params['video'] = $video_url;
									$params['data'] = $data;
								} else {
									$params['error'] = true;
								}
							}
							
							$params['form'] = $form->createView();
						default:
							return $app['twig']
									->render($view . '.html.twig', $params);
							break;
						}

					})->bind($view);
}
$app->run();
