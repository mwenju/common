<?php


namespace Mwenju\Common\Factory;


use Mwenju\Common\Service\OrderService;
use Mwenju\Common\Utils\UtilsUserLogin;
use Hyperf\Contract\ContainerInterface;

class ShopOrderServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $user = UtilsUserLogin::check();
        return make(OrderService::class, compact('user'));
    }
}