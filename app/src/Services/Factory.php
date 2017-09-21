<?php

namespace Services;

use Config\Config;
use Config\RouteConfig;
use Console\Command\Crawler\Actions\CrawlerConsumerAction;
use Services\Crawler\Consumers\CrawlerConsumer;
use Services\Crawler\Crawler;
use Services\Crawler\DAO\EmailDAO;
use Services\QTM\DAO\TaskDAO;
use Services\QTM\QTM;
use Silex\Application;
use Services\Crawler\EmailCrawler;

/**
 * Class Factory
 * Service builder factory.
 * Can be used as wrapper for dependencies interconnecting between different services
 * handles request data passing back and forth
 * @package Services
 */
class Factory
{
    /**
     * @var Application
     */
    private static $silexApp;

    /**
     * @param Application $silexApp
     */
    public static function init(Application $silexApp)
    {
        self::$silexApp = $silexApp;
    }

    public static function create($serviceName)
    {
        $service = null;
        /** @var Config $config */
        $config  = self::$silexApp['config'];

        switch($serviceName) {
            case 'container':
                $service = new RouteConfig();
                break;
            case 'crawler':
                $postgresDb = self::$silexApp['db_connection'];
                $emailDAO = new EmailDAO($postgresDb, 'emails');

                $maxDepth = $config->get('maxDepth');
                $emailCrawler = new EmailCrawler();
                /** @var QTM $qtm */
                $qtm     = Factory::create('qtm');
                $service = new Crawler($emailDAO, $maxDepth, $emailCrawler, $qtm);

                break;
            case 'qtm' :
                $connection    = self::$silexApp['db_connection'];
                $taskDAO       = new TaskDAO($connection, 'tasks');
                $queueProvider = self::$silexApp['queue'];
                $service       = new QTM($taskDAO, $queueProvider);
                break;
            case 'postgre_connection':
                $service = self::$silexApp['db_connection'];
                break;
            case 'crawlerConsumer':

                /** \Uecode\Bundle\QPushBundle\Provider\CustomProvider **/
                $qtm        = Factory::create('qtm');
                $crawler     = Factory::create('crawler');
                $crawlerAction = new CrawlerConsumerAction($crawler);
                $service    = new CrawlerConsumer($qtm, $crawlerAction);
                break;
            default:
                throw new \Exception('Service not found');
        }
        return $service;
    }
}
