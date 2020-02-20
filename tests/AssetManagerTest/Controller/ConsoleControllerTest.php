<?php

namespace AssetManagerTest\Controller;

use AssetManager\Controller\ConsoleController;
use AssetManager\Resolver\MapResolver;
use AssetManager\Service\AssetCacheManager;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\AssetManager;
use AssetManager\Service\MimeResolver;
use JSMin;
use Laminas\Console\Adapter\AdapterInterface;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mvc\Console\Router\RouteMatch;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch as V2RouteMatch;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Resolver\ResolverInterface;
use PHPUnit\Framework\TestCase;

class ConsoleControllerTest extends TestCase
{
    protected static $assetName;
    /**
     *
     * @var ConsoleController
     */
    protected $controller;
    protected $request;
    protected $routeMatch;
    protected $event;

    public static function setUpBeforeClass(): void
    {
        self::$assetName = '_assettest.' . time();
    }

    public function setUp(): void
    {
        require_once __DIR__ . '/../../_files/JSMin.inc';

        $config = [
            'filters' => [
                self::$assetName => [
                    [
                        'filter' => 'JSMin',
                    ],
                ],
            ],
        ];

        $assetFilterManager = new AssetFilterManager($config['filters']);
        $assetCacheManager  = $this->getAssetCacheManager();

        $resolver     = $this->getResolver(__DIR__ . '/../../_files/require-jquery.js');
        $assetManager = new AssetManager($resolver, $config);
        $assetManager->setAssetFilterManager($assetFilterManager);
        $assetManager->setAssetCacheManager($assetCacheManager);

        $this->request    = new ConsoleRequest();
        $this->routeMatch = $this->createRouteMatch(['controller' => 'console']);

        $this->event = new MvcEvent();
        $this->event->setRouteMatch($this->routeMatch);

        $this->controller = new ConsoleController(
            $this->getMock(AdapterInterface::class),
            $assetManager,
            []
        );
        $this->controller->setEvent($this->event);
    }

    /**
     * @return AssetCacheManager
     */
    protected function getAssetCacheManager()
    {
        $serviceLocator    = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
        $config            = [
            self::$assetName => [
                'cache'   => 'FilePathCache',
                'options' => [
                    'dir' => sys_get_temp_dir()
                ]
            ],
        ];
        $assetCacheManager = new AssetCacheManager($serviceLocator, $config);
        return $assetCacheManager;
    }

    /**
     *
     * @return ResolverInterface
     */
    protected function getResolver()
    {
        $mimeResolver = new MimeResolver();
        $resolver     = new MapResolver([
            self::$assetName => __DIR__ . '/../../_files/require-jquery.js'
        ]);
        $resolver->setMimeResolver($mimeResolver);
        return $resolver;
    }

    public function createRouteMatch(array $params = [])
    {
        $class = class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
        return new $class($params);
    }

    public function testWarmupAction()
    {
        $this->routeMatch->setParam('action', 'warmup');
        $this->controller->dispatch($this->request);

        $dumpedAsset = sys_get_temp_dir() . '/' . self::$assetName;
        $this->assertEquals(
            file_get_contents($dumpedAsset),
            JSMin::minify(file_get_contents(__DIR__ . '/../../_files/require-jquery.js'))
        );
    }
}
