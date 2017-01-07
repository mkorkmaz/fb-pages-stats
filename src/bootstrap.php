<?php

declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;
use Links\Command as l;
use Stats\Command as s;
use Symfony\Component\Console\Application;

/**
 * @param array $config
 * @return ServiceProvider
 */
function bootstrap(array $config)
{
    $logger = new Logger('name');
    $logger->pushHandler(new StreamHandler($config['blue_file'], Logger::WARNING));
    $logger->pushHandler(new StreamHandler($config['blue_file'], Logger::INFO));
    $config['db_name'] = $config['elasticsearch'];
    $client = Elasticsearch\ClientBuilder::create()->setHosts($config['elasticsearch']['hosts'])->build();
    $soupmixElasticsearch =  new Soupmix\ElasticSearch(['db_name' => $config['elasticsearch']['db_name']], $client);
    $guzzleHeaders = [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:50.0) Gecko/20100101 Firefox/50.0',
        'allow_redirects' => false
    ];
    $guzzleClient = new GuzzleClient(new Client(['headers' => $guzzleHeaders]));

    /**
     * Set Provider
     */
    $provider = ServiceProvider::getInstance();
    $provider->set('config', $config);
    $provider->set(Monolog\Logger::class, $logger);
    $provider->set(GuzzleClient::class, $guzzleClient);
    $provider->set(Soupmix\ElasticSearch::class, $soupmixElasticsearch);
    return $provider;
}
/**
 * Set Application, Commands and then run application
 * @param string $name
 * @param string $version
 * @return Application
 */
function getApplication(string $name = null, string $version = null)
{
    $application = new Application($name, $version);
    // Links Commands
    $application->add(new l\LinksCommand());
    // Stats Commands
    $application->add(new s\GetCommand());
    $application->add(new s\UpdateCommand());
    $application->add(new s\UpdateAllCommand());
    return $application;
}
