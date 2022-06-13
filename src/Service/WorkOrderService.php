<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShopOrderProduct;
use Hyperf\DbConnection\Db;

class WorkOrderService
{
    public function getInfo($check_order_id = 0)
    {
        $data = Db::table("tb_work_order as a")->selectRaw("a.*,b.order_code,b.mobile,b.address,b.consignee")
            ->leftJoin('mf_shop_order as b','a.order_id','=','b.id')
            ->where("a.id",$check_order_id)
            ->first();
        $data->audit_status_str = trans('lang.audit_status_'.$data->audit_status);
        $data->list = MfShopOrderProduct::selectRaw("product_name,send_num,get_num,art_no,bar_code,product_unit")
            ->where("order_id",$data->order_id)
            ->where('send_num','<>',Db::raw("get_num"))
            ->get();
        return $data;
    }
}