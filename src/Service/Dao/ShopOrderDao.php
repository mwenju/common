<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Model\MfShopOrderProduct;

class ShopOrderDao extends Base
{
    public function getList($param,$page,$limit)
    {
        $audit_status               = $param['audit_status']??"";
        $is_return                  = $param['is_return']??"";
        $is_jiesuan                 = $param['is_jiesuan']??"";
        $pay_type                   = $param['pay_type']??0;
        $supplier_id                = $param['supplier_id']??0;
        $shop_id                    = $param['shop_id']??0;
        $top_depot_id               = $param['top_depot_id']??0;
        $keyword                    = $param['keyword']??"";
        $province_code              = $param['province_code']??"";
        $city_code                  = $param['city_code']??"";
        $area_code                  = $param['area_code']??"";
        $delivery_status            = $param['delivery_status']??"";
        $status                     = $param['status']??"";
        $start_time                 = $param['start_time']??"";
        $end_time                   = $param['end_time']??"";
        $delivery_time_start        = $param['delivery_time_start']??"";
        $delivery_time_end          = $param['delivery_time_end']??"";
        $map = [];
        if (strlen($audit_status) > 0){
            $map[] = ['audit_status','=',$audit_status];
        }
        if ($supplier_id > 0){
            $map[] = ['supplier_id','=',$supplier_id];
        }
        if (strlen($is_return) > 0){
            $map[] = ['is_return','=',$is_return];
        }
        if ($pay_type > 0){
            $map[] = ['pay_type','=',$pay_type];
        }
        if ($shop_id > 0){
            $map[] = ['shop_id','=',$shop_id];
        }
        if ($top_depot_id > 0){
            $map[] = ['top_depot_id','=',$top_depot_id];
        }
        if (!empty($start_time)){
            $map[] = ['create_time','>=',$start_time];
        }
        if (!empty($delivery_time_start)){
            $map[] = ['delivery_time','>=',$delivery_time_start];
        }
        if (!empty($end_time)){
            $map[] = ['create_time','<=',date("Y-m-d 23:59:59",strtotime($end_time))];
        }
        if (!empty($delivery_time_end)){
            $map[] = ['delivery_time','<=',date("Y-m-d 23:59:59",strtotime($delivery_time_end))];
        }
        if (!empty($province_code))
        {
            $map[] = ['province_code','=',$province_code];
        }
        if (!empty($city_code))
        {
            $map[] = ['city_code','=',$city_code];
        }
        if (!empty($area_code))
        {
            $map[] = ['area_code','=',$area_code];
        }
        if (strlen($is_jiesuan)>0)
        {
            if ($is_jiesuan>0)
                $map[] = ['jiesuan','>',0];
            else
                $map[] = ['jiesuan','=',0];
        }
        $query = MfShopOrder::where($map)->when($keyword,function ($query,$keyword){
                return $query->where(function ($q) use ($keyword){
                    return $q->where("consignee",'like',"%{$keyword}%")
                        ->orWhere("supplier_name",'like',"%{$keyword}%")
                        ->orWhere("order_code",'like',"%{$keyword}%")
                        ->orWhere("mobile",'like',"%{$keyword}%")
                        ->orWhere("shop_name",'like',"%{$keyword}%");
                });
            })
            ->when($delivery_status,function ($query,$delivery_status){
                return $query->whereIn("delivery_status",explode(",",$delivery_status));
            })
            ->when($status,function ($query,$status){
                return $query->whereIn("status",explode(",",$status));
            });
        $query->orderBy("id",'desc');

        return $this->pagination($query,$page,$limit);
    }

    public function getBidTotalPriceByOrderIds($ids = [])
    {
        $total_price  = 0;
        $models = MfShopOrderProduct::find($ids);
        foreach ($models as $model){
            $total_price += $model->num * $model->bid_price;
        }
        return $total_price;
    }
}