<?php

namespace XHGui\Test\Controller;

use Slim\Environment;
use Slim\Slim;
use XHGui\Controller\ImportController;
use XHGui\Searcher\SearcherInterface;
use XHGui\Test\TestCase;
use XHGui\Profile;
use XHGui\ServiceContainer;

class ImportTest extends TestCase
{
    /** @var SearcherInterface */
    private $profiles;
    /** @var ImportController */
    private $import;
    /** @var Slim */
    private $app;

    public function setUp()
    {
        parent::setUp();
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
        ]);

        $di = ServiceContainer::instance();
        $this->app = $di['app'] = $this->getMockBuilder(Slim::class)
            ->setMethods(['redirect', 'render', 'urlFor'])
            ->setConstructorArgs([$di['config']])
            ->getMock();

        $this->import = $di['importController'];
        $this->profiles = $di['searcher'];
        $this->profiles->truncate();
    }

    public function testImportSuccess()
    {
        $data = [
            'meta' => [
                'url' => '/things?key=value',
                'simple_url' => '/things',
                'get' => [],
                'env' => [],
                'SERVER' => ['REQUEST_TIME' => 1358787612],
                'request_ts_micro' => ['sec' => 1358787612, 'usec' => 123456],
            ],
            'profile' => [
                "main()" => [
                    "ct" => 1,
                    "wt" => 50139,
                    "cpu" => 49513,
                    "mu" => 3449360,
                    "pmu" => 3535120,
                ],
            ],
        ];
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'slim.input' => json_encode($data),
        ]);

        $before = $this->profiles->getForUrl('/things', []);
        $this->assertEmpty($before['results']);

        $this->import->import($this->app->request(), $this->app->response());

        $after = $this->profiles->getForUrl('/things', []);
        $this->assertNotEmpty($after['results']);
        $this->assertInstanceOf(Profile::class, $after['results'][0]);
    }
}
