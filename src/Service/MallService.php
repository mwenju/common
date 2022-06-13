<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShopAccount;
use Mwenju\Common\Model\MfShopAddress;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductStock;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class MallService
{
    public function submit($param = [])
    {
        $json_data          = $param['json_data']??'';
        $json_data          = json_decode($json_data,true);
        $device_type        = $param['device_type']??'';
        $shop_id            = $param['shop_id']??0;
        $user_id            = $param['user_id']??0;
        $shop_name          = $param['shop_name']??0;
        $product_ids = [];
        foreach ($json_data as $v)
        {
            $product_ids[] = $v['id'];
        }
        $product_res       = TbProduct::whereIn("id",$product_ids)->get();
        $product_info = [];
        foreach ($product_res as $re)
        {
            $product_info[$re->id] = $re;
        }
        $product_integrate  = TbProduct::whereIn("id",$product_ids)->pluck("integrate_num",'id');
        $have_total         = MfShopAccount::where("shop_id",$shop_id)->value("enable_integrate");
        $total              = 0;
        $product_total_num  = 0;
        $top_depot_id       = 1;
        $now                = date("Y-m-d H:i:s");
        $product_total_type_num = count($product_ids);
        foreach ($json_data as $v)
        {
            $total += $v['num'] * $product_integrate[$v['id']];
            $product_total_num += $v['num'];
        }
        if ($have_total < $total)
        {
            UtilsTool::exception("积分不足");
        }
        $product_stock  = TbProductStock::whereIn("product_id",$product_ids)
            ->where("top_depot_id",$top_depot_id)
            ->pluck("salable_num",'product_id');
        foreach ($json_data as $v)
        {
            if($v['num'] > $product_stock[$v['id']])
            {
                UtilsTool::exception($product_info[$v['id']]['product_name']."库存不足");
            }
        }
        $address = ShopAddressService::getLastOrderAddress($shop_id);
        try {
            Db::beginTransaction();
            $order = [
                'shop_id'               =>$shop_id,
                'user_id'               =>$user_id,
                'shop_name'             =>$shop_name,
                'order_code'            =>UtilsTool::create_order_number($user_id),
                'all_money'             =>0,
                'real_money'            =>0,
                'status'                =>1,
                'product_total_price'   =>0,
                'product_total_num'     =>$product_total_num,
                'product_total_type_num'=>$product_total_type_num,
                'total_price'           =>0,
                'discount_price'        =>0,
                'pay_price'             =>0,
                'paid_price'            =>0,
                'pay_type'              =>4,
                'paid_integrate'        =>$total,
                'pay_time'              =>$now,
                'create_time'           =>$now,
                'consignee'             =>$address->consignee,
                'mobile'                =>$address->mobile,
                'province_code'         =>$address->province_code,
                'city_code'             =>$address->city_code,
                'area_code'             =>$address->area_code,
                'address'               =>$address->address,
                'address_id'            =>$address->address_id,
                'top_depot_id'          =>$top_depot_id,
                'device_type'           =>$device_type,
            ];
            $order_id = Db::table("mf_shop_order")->insertGetId($order);
            $order['id'] = $order_id;
            $order_product = [];
            foreach ($json_data as $v)
            {
                $order_product[] = [
                    'order_id'=>$order_id,
                    'product_id'=>$v['id'],
                    'supplier_id'=>$product_info[$v['id']]['supplier_id'],
                    'idea_title'=>$product_info[$v['id']]['idea_title'],
                    'product_name'=>$product_info[$v['id']]['product_name'],
                    'bar_code'=>$product_info[$v['id']]['bar_code'],
                    'art_no'=>$product_info[$v['id']]['art_no'],
                    'product_unit'=>$product_info[$v['id']]['product_unit'],
                    'param_list'=>$product_info[$v['id']]['product_param_values_json'],
                    'price'=>0,
                    'bid_price'=>$product_info[$v['id']]['bid_price'],
                    'num'=>$v['num'],
                    'integrate_num'=>$product_integrate[$v['id']],
                    'list_img_path'=>$product_info[$v['id']]['list_img_path'],
                    'top_depot_id'=>1
                ];
            }
            Db::table("mf_shop_order_product")->insert($order_product);

            $sql = 'UPDATE mf_shop_order_product a,tb_product_stock b,tb_product c set 
                        b.lock_num = b.lock_num+a.num,
                        b.salable_num = b.salable_num-a.num
                        WHERE a.order_id=? 
                        AND a.product_id=b.product_id 
                        AND b.product_id=c.id
                        AND c.is_on_sale>0
                        AND b.top_depot_id=1
                        AND b.salable_num>=a.num
                       ';
            $update_num = Db::update($sql,[$order_id]);

            if ($update_num < $product_total_type_num)
            {
                UtilsTool::exception("库存不足");
            }

            Db::table("mf_shop_order_log")->insert([
                'order_id'=>$order_id,
                'shop_id'=>$shop_id,
                'user_id'=>$user_id,
                'status'=>0,
                'remark'=>'提交订单',
                'create_time'=>$now
            ]);
            Db::table("mf_shop_order_log")->insert([
                'order_id'=>$order_id,
                'shop_id'=>$shop_id,
                'user_id'=>$user_id,
                'status'=>0,
                'remark'=>'订单已确认',
                'create_time'=>$now
            ]);
            Db::table("mf_shop_account")->where("shop_id",$shop_id)
                ->decrement("enable_integrate",$total);
            Db::table("mf_shop_account_log")->insert([
                'shop_id'=>$shop_id,
                'why_info'=>'积分兑换',
                'add_type'=>6,
                'in_or_out'=>-1,
                'account_field'=>'enable_integrate',
                'add_num'=>(-1)*$total,
                'create_time'=>$now
            ]);
            Db::commit();
            Sms::send($this->user->getMobile(),'MALL_PAY_ORDER');
            return $order;
        }
        catch (\Exception $e)
        {
            Db::rollback();
            UtilsTool::exception($e->getMessage());
        }
    }
}