<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfUserOrder;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class UserMallService
{
    private $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function submit($shop_id = 0,$product_id = 0,$num = 1)
    {
        $proInfo = ShopProductService::getInfo($shop_id,$product_id);
        if (!$proInfo)
        {
            UtilsTool::exception("商品有误");
        }
        if ($num > $proInfo->salable_num)
        {
            UtilsTool::exception("库存不足");
        }

        $total = $num * $proInfo->integrate_num;

        try
        {
            Db::beginTransaction();
            $userCardService = new UserCardService();
            list($add,$after,$card_number) = $userCardService->integralUpdateByUserIdShopId($this->user->getUserId(),$shop_id,-1*$total,3,'积分兑换');
            $order = [
                'order_code'=>UtilsTool::create_order_number($this->user->getUserId()),
                'product_total_price'=>0,
                'discount'=>0,
                'pay_price'=>0,
                'paid_integrate'=>$total,
                'create_time'=>date("Y-m-d H:i:s"),
                'user_id'=>$this->user->getUserId(),
                'shop_id'=>$shop_id,
                'mobile'=>$this->user->getMobile(),
                'state'=>1,
                'card_number'=>$card_number,
            ];
            $order_id = Db::table("mf_user_order")->insertGetId($order);
            Db::table("mf_user_order_product")->insert([
                'order_id'=>$order_id,
                'product_id'=>$product_id,
                'product_name'=>$proInfo->product_name,
                'bar_code'=>$proInfo->bar_code,
                'art_no'=>$proInfo->art_no,
                'price'=>0,
                'num'=>$num,
                'integrate'=>$proInfo->integrate_num,
            ]);
            Db::table("mf_shop_product")->where("shop_id",$shop_id)->where("product_id",$product_id)
                ->update([
                    'salable_num'=>Db::raw("salable_num - {$num}"),
                    'lock_num'=>Db::raw("salable_num + {$num}"),
                ]);
            Db::commit();
            return $order;
        }catch (\Exception $e)
        {
            Db::rollback();
            UtilsTool::exception($e->getMessage());
        }

    }

    public function send($order_id = 0)
    {
        $orderInfo = MfUserOrder::find($order_id);
        if ($orderInfo->state != 1)
        {
            UtilsTool::exception("已兑换");
        }
        try {
            Db::beginTransaction();

            Db::table("mf_user_order")
                ->where("id",$order_id)
                ->update(['state'=>3,'delivery_time'=>date("Y-m-d H:i:s")]);

            $oproductlist = Db::table("mf_user_order_product")
                ->where("order_id",$order_id)->get();

            foreach ($oproductlist as $item)
            {
                Db::table("mf_shop_product")
                    ->where("product_id",$item->product_id)
                    ->where("shop_id",$orderInfo->shop_id)
                    ->decrement("lock_num",$item->num);
            }
            Db::commit();
        }catch (\Exception $e)
        {
            Db::rollback();
            UtilsTool::exception($e->getMessage());
        }

    }
}