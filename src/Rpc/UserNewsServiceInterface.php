<?php


namespace Mwenju\Common\Rpc;


interface UserNewsServiceInterface
{
    public function getList($user_id = 0,$shop_id = 0,$page = 0,$limit = 30);
    public function getListByShopId($shop_id = 0,$page = 0,$limit = 30);
    public function create($shop_id = 0,$title = '',$content = '',$imgs = "");
}