<?php


namespace Mwenju\Common\Utils;


use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;

class Logger
{
    public static function init($name = 'app'):LoggerInterface
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->make($name);
    }
}