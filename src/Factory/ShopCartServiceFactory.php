<?php


namespace Mwenju\Common\Factory;


use Mwenju\Common\Service\ShopCartService;
use Mwenju\Common\Utils\UtilsUserLogin;
use Hyperf\Contract\ContainerInterface;

class ShopCartServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $user = di(UtilsUserLogin::class)->check(true);
        return make(ShopCartService::class, compact('user'));
    }
}