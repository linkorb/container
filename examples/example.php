<?php

namespace Example;

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

require_once __DIR__ . '/../vendor/autoload.php';

class HelloService
{
    protected $hostname;

    public function __construct(LoggerInterface $logger, $hostname)
    {
        $this->logger = $logger;
        $this->hostname = $hostname;
    }

    public function greet($greeting = 'Hello')
    {
        $this->logger->info($greeting . ' from ' . $this->hostname);
    }
}

$c = new \LinkORB\Container\Container();

// Define parameters
$c->set('hostname', gethostname());

// Define lazy-loaded service (no instantiation yet)
$c->registerService(HelloService::class);

// Define a second service by interface
$logger = new Logger('name');
$logger->pushHandler(new ErrorLogHandler());
$c->set(LoggerInterface::class, $logger);

// Instantiation of HelloService happens only here
$s = $c->get(HelloService::class);

// Invoke a method on the service, passing in extra params
$res = $c->invoke($s, 'greet', ['greeting' => 'Hi']);

