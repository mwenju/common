<?php

namespace Mwenju\Common\Service\Formatter;

use Mwenju\Common\Model\MfAdmin;
use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfUser;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Pojo\User;

class UserFormatter
{
    public function base(MfUser $model):User
    {
        $user = new User();
        $user->setUserId($model->id);
        $user->setMobile($model->mobile);
        $shop = MfShop::where("user_id",$model->id)->first();
        if ($shop)
        {
            $user->setShopId((int)$shop->id);
            $user->setAreaCode($shop->area_code??"0");
            $user->setCityCode($shop->city_code??"0");
            $user->setProvinceCode($shop->province_code??"0");
            $user->setTag((string)$shop->shop_tags);
        }
        $admUser = MfAdmin::where("user_id",$model->id)->first();
        if ($admUser)
        {
            $user->setAdminId($admUser->id);
            $user->setAdminName($admUser->real_name);
            $user->setDepotId(intval($admUser->top_depot_id));
            $user->setAdminRoleId(intval($admUser->role_ids));
        }else{
            $user->setAdminId(0);
            $user->setAdminName('');
            $user->setDepotId(0);
            $user->setAdminRoleId(0);
        }
        $suppliser = TbSupplier::where("user_id",$model->id)->first();
        if ($suppliser)
        {
            $user->setSupplierName($suppliser->supplier_name);
            $user->setSupplierId($suppliser->id);
        }
        return $user;
    }
}