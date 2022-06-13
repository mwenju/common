<?php


namespace Mwenju\Common\Utils;


use Mwenju\Common\Model\MfAdmin;
use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfUser;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Pojo\User;
use Hyperf\Di\Exception\Exception;

class UtilsUserLogin
{
    public static function check($must = true):User
    {
        $token = UtilsTool::input("token");
        $userLogin = self::updateInfo($token);
        if ($must && $userLogin->getUserId() == 0){
            UtilsTool::exception("未登录",302);
        }
        return $userLogin;
    }

    public static function updateInfo($token = '')
    {
        $userLogin = new User();
        $userLogin->setDeviceCode(UtilsTool::input("device_code"));
        $userLogin->setDeviceType(UtilsTool::input("device_type",'weixin'));
        $userLogin->setDepotId(1);
        if(!empty($token))
        {
            $tokenData = UtilsTool::redis()->get("TOKEN_".$token);
            if($tokenData){
                $userLogin = unserialize($tokenData);
            }
            else
            {
                $user = MfUser::where("token",$token)->first();
                if ($user)
                {
                    $userLogin->setUserId((int)$user->id);
                    $userLogin->setMobile($user->mobile?$user->mobile:"");

                    $userLogin->setUserInfo($user->getAttributes());
                    $shop = MfShop::where("user_id",$user->id)->first();
                    if ($shop)
                    {
                        $userLogin->setShopId((int)$shop->id);
                        $userLogin->setAreaCode($shop->area_code?$shop->area_code:"0");
                        $userLogin->setCityCode($shop->city_code?$shop->city_code:"0");
                        $userLogin->setProvinceCode($shop->province_code?$shop->province_code:"0");
                        $userLogin->setShopInfo($shop->getAttributes());
                        $userLogin->setTag((string)$shop->shop_tags);
                        $userLogin->setShopName($shop->cname);
                    }
                    $admUser = MfAdmin::where("user_id",$user->id)->first();
                    if ($admUser)
                    {
                        $userLogin->setAdminId($admUser->id);
                        $userLogin->setAdminName($admUser->real_name);
                        $userLogin->setAdminDepotId(intval($admUser->top_depot_id));
                        $userLogin->setAdminRoleId(intval($admUser->role_ids));
                    }else{
                        $userLogin->setAdminId(0);
                        $userLogin->setAdminName('');
                        $userLogin->setAdminRoleId(0);
                        $userLogin->setAdminDepotId(1);
                    }

                    $suppliser = TbSupplier::where("user_id",$user->id)->first();
                    if ($suppliser)
                    {
                        $userLogin->setSupplierName($suppliser->supplier_name);
                        $userLogin->setSupplierId($suppliser->id);
                    }

                    UtilsTool::redis()->set("TOKEN_".$token,serialize($userLogin));
                }

            }
        }

        return $userLogin;
    }

}