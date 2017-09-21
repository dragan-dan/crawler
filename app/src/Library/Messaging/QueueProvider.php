<?php
namespace Library\Messaging;

use Exceptions\MessagingException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Uecode\Bundle\QPushBundle\DependencyInjection\Configuration;

class QueueProvider implements ServiceProviderInterface
{
    /**
     * Register Message Queue Provider in Silex App
     * @param Application $app
     */
    public function register(Application $app) {

        /**
         * @var \Symfony\Component\Config\
         */
        $configs = $app['config'];
        $config = $configs->get('messaging');

        $container = new ContainerBuilder();

        // Load database config
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../../app/config')
        );

        $loader->load('database.yml');

        // Register message queues
        $this->registerQueues($container, $config);

        // Register logger
        $container->setDefinition(
            'logger',
            new Definition(
                '\Symfony\Bridge\Monolog\Logger',
                [ $configs->get('monolog.logger_name'), $configs->get('monolog.handler') ]
            )
        );

        // Lazy-load function for message queues
        $app['queue'] = $app->protect(function($queue) use ($container) {
            $serviceId = sprintf('uecode_qpush.%s', $queue);
            if (!$container->hasDefinition($serviceId)) {
                throw new MessagingException(sprintf(MessagingException::QUEUE_NOT_DEFINED, $queue));
            }

            $queue = $container->get($serviceId);
            return $queue;
        });
    }

    /**
     * Runtime Configuration handler for Message Queue Provider
     * @param Application $app
     */
    public function boot (Application $app) {

    }

    /**
     * Build a Symfony DI container with Providers for each message queue
     *
     * @param ContainerBuilder $container
     * @param array $configs
     * @return ContainerBuilder $container
     */
    private function registerQueues(ContainerBuilder $container, Array $configs)
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/Resources/config')
        );

        $loader->load('parameters.yml');
        $loader->load('services.yml');

        $registry = $container->getDefinition('uecode_qpush.registry');
        $cache    = $config['cache_service'] ?: 'uecode_qpush.file_cache';
        $container->setParameter('kernel.cache_dir', PATH_ROOT . '/app/cache');

        foreach ($config['queues'] as $queue => $values) {

            // Adds logging property to queue options
            $values['options']['logging_enabled'] = $config['logging_enabled'];

            $provider   = $values['provider'];
            $class      = null;
            $client     = null;

            switch ($config['providers'][$provider]['driver']) {
                case 'sync':
                    $class  = $container->getParameter('uecode_qpush.provider.sync');
                    $client = $this->createCrawlClient();
                    break;
                case 'custom':
                    $class  = $container->getParameter('uecode_qpush.provider.custom');
                    $client = $this->createCustomClient($config['providers'][$provider]['service'] . '_' . $queue);

                    // Register custom handler
                    $container->setDefinition(
                        $config['providers'][$provider]['service'] . '_' . $queue,
                        new Definition(
                            'Library\Messaging\Providers\DatabaseClient',
                            [ $queue, [], new Reference('postgres_client'), new Reference($cache), new Reference('logger') ])
                    );

                    break;
            }

            $definition = new Definition(
                $class,
                [$queue, $values['options'], $client, new Reference($cache), new Reference('logger')]
            );

            $name = sprintf('uecode_qpush.%s', $queue);

            $container->setDefinition($name, $definition)
                ->addTag('monolog.logger', ['channel' => 'qpush'])
                ->addTag(
                    'uecode_qpush.event_listener',
                    [
                        'event' => "{$queue}.on_notification",
                        'method' => "onNotification",
                        'priority' => 255
                    ]
                )
                ->addTag(
                    'uecode_qpush.event_listener',
                    [
                        'event' => "{$queue}.message_received",
                        'method' => "onMessageReceived",
                        'priority' => -255
                    ]
                )
            ;

            $registry->addMethodCall('addProvider', [$queue, new Reference($name)]);
        }


        return $container;
    }


    private function createCrawlClient()
    {
        return new Reference('event_dispatcher');
    }

    /**
     * @param string $serviceId
     *
     * @return Reference
     */
    private function createCustomClient($serviceId)
    {
        return new Reference($serviceId);
    }

    /**
     * Returns the Extension Alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'uecode_qpush';
    }
}
