<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Silex\WebTestCase;

class InitialTest extends \PHPUnit_Framework_TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../app/app.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }
    public function testInitialPage()
    {
        $client = $this->createClient(array());
        $crawler = $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isOk());
    }
}
