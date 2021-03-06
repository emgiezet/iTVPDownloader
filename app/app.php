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

$app['debug'] = true;

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
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
$redisConfig = null;

if (is_object($services)) {
    foreach ($services as $key => $val) {
        if ($key === 'redis-2.2') {
            if (is_object($val[0])) {
                $redisConfig = $val[0]->credentials;
            }
        }

    }
}
if (is_object($redisConfig)) {
    $redisParams = array('host' => $redisConfig->hostname,
            'port' => $redisConfig->port, 'password' => $redisConfig->password,
            'name' => $redisConfig->name,);
} else {
    $redisParams = 'tcp://127.0.0.1:6379/';
}

$app
        ->register(new Predis\Silex\PredisServiceProvider(),
                array('predis.parameters' => $redisParams,
                        'predis.options' => array('profile' => '2.2'),));

$pages = array('/' => 'homepage', '/error' => 'error');
$params = array();
foreach ($pages as $route => $view) {
    $app
            ->match($route,
                    function (Request $request) use ($app, $view)
                    {
                        switch ($view) {
                        case 'homepage':
                        // 							return var_export($app['predis']->info(), true);
                            $data = array();

                            $form = $app['form.factory']
                                    ->createBuilder('form', $data,
                                            array('csrf_protection' => false))
                                    ->add('url', 'text',
                                            array(
                                                    'constraints' => new Assert\Url()))
                                    ->getForm();

                            if ('POST' == $request->getMethod()) {
                                $form->bind($request);

                                if ($form->isValid()) {
                                    $data = $form->getData();

                                    $name = $data['url'];
                                    $name = str_replace('https://', 'http://',
                                            $name);

                                    //                                     $cached = $app['predis']
                                    //                                             ->get(
                                    //                                                     '{url:' . urlencode($name)
                                    //                                                             . '}:path');
                                    $cached = NULL;
                                    if ($cached === NULL) {
                                        $browser = new Buzz\Browser();

                                        $vodMatches = array();
                                        preg_match_all(
                                                '/^(https?):\/\/beta.vod.tvp.pl\/+(.*)\/+wideo+\/+(.*)+\/+([0-9]{1,})/',
                                                $name, $vodMatches);

                                        if (!empty($vodMatches)) {
                                            //Zasysamy z VOD tvp.pl!
                                            // Init Session
                                            $movieId = $vodMatches[4][0];

                                            $request = new \Buzz\Message\Request(
                                                    'GET',
                                                    '/pub/sess/initsession',
                                                    'http://www.tvp.pl');
                                            $request
                                                    ->addHeader(
                                                            'User-agent: Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
                                            $response = new \Buzz\Message\Response();
                                            $browser->send($request, $response);

                                            //Viewrequest

                                            $request2 = new \Buzz\Message\Request(
                                                    'GET',
                                                    '/pub/sess/viewrequest?object_id='
                                                            . $movieId,
                                                    'http://www.tvp.pl');
                                            $response2 = new \Buzz\Message\Response();

                                            $browser
                                                    ->send($request2,
                                                            $response2);

                                            $cookieJar = new \Buzz\Util\CookieJar();
                                            $cookieJar
                                                    ->processSetCookieHeaders(
                                                            $request2,
                                                            $response2);

                                            $url = '/shared/cdn/tokenizer_v2.php?object_id='
                                                    . $movieId
                                                    . '&sdt_version=sdt-v2';

                                            $request3 = new \Buzz\Message\Request(
                                                    'GET', $url,
                                                    'http://www.tvp.pl');

                                            $request3
                                                    ->addHeader(
                                                            'User-agent: Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
                                            $cookies = '';
                                            foreach ($cookieJar->getCookies() as $cookie) {
                                                $cookies .= $cookie->getName()
                                                        . '='
                                                        . $cookie->getValue()
                                                        . '; ';
                                            }
                                            $request3
                                                    ->addHeader(
                                                            'Cookie: '
                                                                    . $cookies);
                                            
                                            $json = new \Buzz\Message\Response();

                                            $browser->send($request3, $json);
                                            //echo('<pre>');
                                            //var_dump($request3);
                                            
                                            
                                            $json = $json->getContent();
                                            $data = json_decode($json);
                                            //var_dump($data);
                                            
                                            //die;
                                            if (is_object($data)) {
                                                if ($data->status == 'OK') {
                                                    $video_url = $data->url;
                                                } else {
                                                    $video_url = false;
                                                }
                                            } else {
                                                $video_url = false;
                                            }

                                        } else {
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
                                                if (is_array($match)
                                                        && isset($match[0])) {
                                                    if (intval($match[0]) > 1) {
                                                        $url = 'http://www.tvp.pl/pub/stat/videofileinfo?video_id='
                                                                . $match[0];
                                                        $json = $browser
                                                                ->get($url);
                                                        $json = $json
                                                                ->getContent();
                                                        $data = json_decode(
                                                                $json);
                                                        $video_url = $data
                                                                ->video_url;

                                                    } else {
                                                        $video_url = false;
                                                    }

                                                } else {
                                                    $video_url = false;
                                                }
                                            }
                                        }

                                        if (!$video_url) {
                                            $params['error'] = true;
                                        } else {

                                            $app['predis']
                                                    ->set(
                                                            '{url:'
                                                                    . urlencode(
                                                                            $name)
                                                                    . '}:path',
                                                            $video_url);
                                            $app['predis']
                                                    ->set(
                                                            '{url:'
                                                                    . urlencode(
                                                                            $name)
                                                                    . '}:data',
                                                            $data);
                                            $app['predis']
                                                    ->incrBy(
                                                            '{url:'
                                                                    . urlencode(
                                                                            $name)
                                                                    . '}:count',
                                                            1);

                                        }
                                    } else {
                                        $video_url = $app['predis']
                                                ->get(
                                                        '{url:'
                                                                . urlencode(
                                                                        $name)
                                                                . '}:path');
                                        $data = $app['predis']
                                                ->get(
                                                        '{url:'
                                                                . urlencode(
                                                                        $name)
                                                                . '}:data');
                                        $app['predis']
                                                ->incrBy(
                                                        '{url:'
                                                                . urlencode(
                                                                        $name)
                                                                . '}:count', 1);
                                        $count = $app['predis']
                                                ->get(
                                                        '{url:'
                                                                . urlencode(
                                                                        $name)
                                                                . '}:count');
                                    }
                                    $params['video'] = $video_url;
                                    $params['data'] = $data;
                                } else {
                                    $params['error'] = true;
                                }
                            }

                            $params['form'] = $form->createView();
                        default:
                            $body = $app['twig']
                                    ->render($view . '.html.twig', $params);
                            return new Response($body, 200,
                                    array(
                                            'Cache-Control' => 's-maxage=0, max-age=0, must-revalidate, no-cache',
                                            'Pragma' => 'no-cache'));
                            break;
                        }

                    })->bind($view);
}

return $app;
