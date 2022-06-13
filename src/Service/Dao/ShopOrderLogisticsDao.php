<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Model\MfShopOrderLogistic;
use Mwenju\Common\Model\ShopOrderLogistics;
use Hyperf\DbConnection\Db;

class ShopOrderLogisticsDao extends Base
{

    public function getInfo($id = 0)
    {
        $info = MfShopOrderLogistic::find($id);
        $info->shop_name = $info->order->shop_name;
        return  $info;
    }

    public function getList($param = [])
    {
        $order_id               = $param['order_id']??0;
        $top_depot_id           = $param['top_depot_id']??0;
        $page                   = $param['page']??0;
        $rows                   = $param['rows']??10;
        $keyword                = $param['keyword']??'';
        $start_time             = $param['start_time']??'';
        $end_time               = $param['end_time']??'';
        $is_subsidy             = $param['is_subsidy']??0;
        $subsidy_ok             = $param['subsidy_ok']??0;
        $province_code          = $param['province_code']??0;
        $city_code              = $param['city_code']??0;
        $area_code              = $param['area_code']??0;
        $logistics_comp_cname   = $param['logistics_comp_cname']??'';
        $is_peihuo              = $param['is_peihuo']??'';
        $map = [];
        if ($order_id > 0)
        {
            $map[] = ['a.order_id','=',$order_id];
        }
        if ($top_depot_id > 0)
        {
            $map[] = ['b.top_depot_id','=',$top_depot_id];
        }
        if (!empty($start_time))
        {
            $map[] = ['a.create_time','>=',$start_time];
        }
        if (!empty($end_time))
        {
            $end_time = date("Y-m-d 23:59:59",strtotime($end_time));
            $map[] = ['a.create_time','<=',$end_time];
        }
        if($is_subsidy > 0)
        {
            $map[] = ['b.product_total_price','>=',399];
        }
        if($subsidy_ok>0)
        {
            $map[] = ['a.subsidy_money','>',0];
        }
        if (strlen($is_peihuo)>0)
        {
            $map[] = ['a.is_peihuo','=',$is_peihuo];
        }
        else
        {
            if($is_subsidy > 0)
            {
                $map[] = ['a.subsidy_money','=',0];
            }
        }
        if(!empty($province_code)) $map[] = ['b.province_code','=',$province_code];
        if(!empty($city_code)) $map[] = ['b.city_code','=',$city_code];
        if(!empty($area_code)) $map[] = ['b.area_code','=',$area_code];
        if(!empty($logistics_comp_cname)) $map[] = ['a.logistics_comp_cname','=',$logistics_comp_cname];

        $data = Db::table("mf_shop_order_logistics as a")->selectRaw("a.*,b.address,b.shop_name,'' addr_type_str")
            ->leftJoin("mf_shop_order as b",'a.order_id','=','b.id')
            ->where($map)->when($keyword,function ($query,$keyword){
                return $query->where(function ($q) use ($keyword){
                    return $q->where("b.order_code","like","%$keyword%")
                        ->orWhere("a.mobile","like","%$keyword%")
                        ->orWhere("a.consignee","like","%$keyword%")
                        ->orWhere("a.logistics_comp_cname","like","%$keyword%");
                });
            });
        $total = $data->count();
        $list = $data->forPage($page,$rows)->orderBy("a.id",'desc')->get();
        return [$total,$list];
    }

    public function compList()
    {
        $sql = "SELECT DISTINCT logistics_comp_cname from mf_shop_order_logistics WHERE logistics_comp_cname<>''";
        return Db::select($sql);
    }

    public function update($param = [])
    {
        $id = $param['id']??0;
        $model = MfShopOrderLogistic::find($id);
        if (!$model) throw new \Exception("记录不存在");
        $model->logistics_money = $param['logistics_money']??0;
        $model->img_urls        = $param['img_urls']??"";
        $model->is_peihuo       = $param['is_peihuo']??0;
        $model->delivery_time   = $param['delivery_time']??"";
        if ($param['logistics_code']??"")
        {
            $model->logistics_code = $param['logistics_code'];
        }
        $model->save();
        return $model;
    }

}