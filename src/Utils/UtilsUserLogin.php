<?php


namespace Mwenju\Common\Utils;


use Mwenju\Common\Model\MfAdmin;
use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfUser;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Pojo\User;
use Hyperf\Di\Exception\Exception;
use Mwenju\Common\Service\UserService;

class UtilsUserLogin
{
    /**
     * @throws Exception
     */
    public function check($must = true):User|Exception
    {
        $token = UtilsTool::input("token");
        $userLogin = $this->updateInfo($token);
        if ($must && $userLogin->getUserId() == 0){
            UtilsTool::exception("未登录",302);
        }
        return $userLogin;
    }

    public function updateInfo($token = ''):User|Exception
    {
        return di(UserService::class)->loginInfo($token);
    }

}