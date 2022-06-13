<?php


namespace Mwenju\Common\Factory;

use Mwenju\Common\Utils\Logger;
use Psr\Container\ContainerInterface;
class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return Logger::init('app');
    }
}