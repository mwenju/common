<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Model\TbRechargeOrder;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class RechargeOrderService
{
    public static function getUserCardListByShopId($shop_id = 0,$mobile = "",$create_time="",$page = 0,$limit =30){
        $map[] = ['shop_id','=',$shop_id];
        $map[] = ['scene','=',2];

        if(!empty($create_time)){
            $start_time = date("Y-m-d 00:00:00",strtotime($create_time));
            $end_time = date("Y-m-d 23:59:59",strtotime($create_time));
            $map[] = ['create_time','>=',$start_time];
            $map[] = ['create_time','<=>',$end_time];
        }
        $list = TbRechargeOrder::where("shop_id",$shop_id)
            ->where($map)
            ->when($mobile,function ($query,$mobile){
                return $query->where(function ($q) use ($mobile){
                    return $q->where("mobile","like","%{$mobile}%")
                        ->orWhere("card_number","like","%{$mobile}%");
                });
            })
            ->orderBy("id","desc")
            ->limit($limit)
            ->offset($page)
            ->get();
        return $list;
    }

    public static function notify($param = [])
    {
        if(isset($param['trade_state']) && $param['trade_state'] != 'SUCCESS')
        {
            Logger::init()->error('已交易成功返回trade_state:'.$param['trade_state_desc']);
            return $param['trade_state_desc'];
        }

        $order = TbRechargeOrder::where('order_code',$param['out_trade_no'])->first();
        if(!$order) {
            Logger::init()->error("商户单号不存在:".$param['out_trade_no']);
            return "单号不存在";
        }
        if($order->pay_state == 1)
        {
            Logger::init()->error("重复通知，已经处理:".$param['out_trade_no']);
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }
        Db::beginTransaction();
        try
        {
            $upNum = Db::table("tb_recharge_order")->where("order_code",$param['out_trade_no'])
                ->where("pay_state",0)
                ->update([
                    'pay_time'=>date("Y-m-d H:i:s"),
                    'pay_state'=>1,
                    'pay_type'=>1,
                    'is_delete'=>0,
                    'transaction_id'=>$param['transaction_id'],
                    'bank_type'=>$param['bank_type'],
                    'fee_type'=>$param['fee_type']
                ]);
            if($upNum == 0)
            {
                Db::commit();
                Logger::init()->info("支付状态更新数量0:".$param['out_trade_no']);
                return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            }
            $money = $order->money+$order->pix;
            if($order->scene == 1)
            {
                $ShopAccountService = new ShopAccountService();
                $ShopAccountService->changeAccount($order->shop_id,$money,"微信充值：{$money}元",0);
            }
            elseif($order->scene == 2)
            {
                $UserCard = new UserCardService();
                $UserCard->recharge($order->card_number,$money);
                if($order->coupon_template_id > 0){
                    $UserCouponList = new UserCouponListService();
                    $UserCouponList->bind($order->user_id,$order->coupon_template_id);
                }
            }
            else
            {
                Logger::init()->error("未知充值场景:".$order->toJson());
                UtilsTool::exception("未知充值场景");
            }

            Db::commit();
        }
        catch (\Exception $e)
        {
            Db::rollback();
            Logger::init()->error("WX_PAY_ERR:".$e->getMessage());
            return $e->getMessage();
        }
        try
        {
            //充值送红包
            $couponService = new CouponService();
            // 成功赠送则发送短信提醒
            if($couponService->bindByRecharge($order->id)){
                Logger::init()->info("充值赠送成功");
            }
            if($order->scene == 1)
            {
                Sms::send($order->mobile,'RECHARGE_SUCCESS',['fee'=>$money]);
            }
            else
            {
                // TODO 短信模板 待定
            }

        }
        catch (\Exception $e)
        {
            Logger::init()->error("充值成功，其他错误：".$e->getMessage());
        }
        return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    public static function checkPay($order_code = '')
    {
        $wx = new WxService();
        if(strpos($order_code,'R') !== false)
        {
            $row = TbRechargeOrder::where('order_code',$order_code)->get();
            if(!$row)
            {
                UtilsTool::exception('订单不存在');
            }
            if($row['pay_state'] > 0) {
                UtilsTool::exception('订单状态已支付');
            }
            $result = $wx->orderQuery($order_code);
            RechargeOrderService::notify($result);
        }
        else
        {
            $row  = MfShopOrder::where('order_code',$order_code)->find();
            if(!$row)
            {
                UtilsTool::exception('订单不存在');
            }
            if($row['status'] > 0)
            {
                UtilsTool::exception('订单状态已结束，不能更新');
            }
            $result = $wx->orderQuery($order_code);
            OrderService::notify($result);
        }

        return $result;
    }

    /**
     * 5分钟内未支付订单沦陷查询
     */
    public static function autoCheckPay()
    {
        $time = 60*5;
        MfShopOrder::where('create_time','>=',date("Y-m-d H:i:s",time()-$time))
            ->where('status','=',0)
            ->get()->each(function ($item,$index){
                self::checkPay($item->order_code);
            });
        TbRechargeOrder::where('create_time','>=',date("Y-m-d H:i:s",time()-$time))
            ->where('pay_state','=',0)
            ->where('is_delete','=',1)
            ->get()->each(function ($item,$index){
                self::checkPay($item->order_code);
            });
    }
}