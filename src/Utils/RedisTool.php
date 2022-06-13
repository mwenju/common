<?php


namespace Mwenju\Common\Utils;


use Hyperf\Utils\ApplicationContext;
use \Hyperf\Redis\Redis;

class RedisTool
{
    public static function init():Redis
    {
        return ApplicationContext::getContainer()->get(Redis::class);
    }
}