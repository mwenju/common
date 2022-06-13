<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfShopAccount;
use Mwenju\Common\Model\MfShopAddress;
use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Model\MfShopOrderLogistic;
use Mwenju\Common\Model\MfShopTradeHistory;
use Mwenju\Common\Model\MfUserCard;
use Mwenju\Common\Model\MfUserOrder;
use Mwenju\Common\Service\Dao\ShopDao;
use Mwenju\Common\Service\Formatter\ShopFormatter;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class ShopService
 * @package App\Common\Service
 * @RpcService(name="ShopService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("ShopService","jsonrpc","jsonrpc")]
class ShopService extends BaseService
{
    #[Inject]
    private ShopFormatter $formatter;
    #[Inject]
    private ShopDao $shopDao;

    public function getList($param = [])
    {
        [$page,$rows] = $this->pageFmt($param);
        [$total,$list] = $this->shopDao->getList($param,$page,$rows);
        return ['total'=>$total,'rows'=>$this->formatter->formatList($list)];
    }

    public static function getMobile($shop_id = 0)
    {
        return Db::table("mf_shop a")->leftJoin("mf_user b","a.user_id",'=',"b.id")->value("b.mobile");
    }

    public static function getInfoByUserIdOrShopId($user_id = 0,$shop_id = 0)
    {
        $shop_info = Db::table("mf_shop as a")->selectRaw("a.*,a.id shop_id,a.cname shop_name,c.face_img,b.face_img store_header_img,b.desction,a.addr,a.link_mobile")
            ->leftJoin('mf_shop_store as b','b.shop_id','=','a.id')
            ->leftJoin('mf_user as c','a.user_id','=','c.id')
            ->when($shop_id,function ($query,$shop_id){
                return $query->where("a.id",$shop_id);
            })
            ->when($user_id,function ($query,$user_id){
                return $query->where("a.user_id",$user_id);
            })
            ->where('b.audit_state',1)
            ->first();
        if($shop_info){
            $shop_info->face_img = UtilsTool::img_url($shop_info->face_img);
            $shop_info->store_header_img = UtilsTool::img_url($shop_info->store_header_img,'',false);
        }

        return  $shop_info;
    }

    /**
     * 是否首次注册商户
     * @param number $shop_id
     * @return bool
     */
    public static function isFirstUser($shop_id = 0)
    {
        //是否存在已付款订单
        $onum = MfShopOrder::where(['shop_id'=>$shop_id])->whereNotIn('status',[3,4])->count();
        // 是否存在赠送奖励
        $money = MfShopTradeHistory::where('shop_id',$shop_id)->where('trade_type',12)->sum('num');
        return $onum == 0 && $money>0;
    }

    public function subsidy($logistics_id = 0,$money = 0,$logistics_code = '')
    {
        if($logistics_id <= 0) UtilsTool::exception("id有误");

        if($money <= 0) UtilsTool::exception("补贴金额不能为空");

        if(empty($logistics_code)) UtilsTool::exception("物流单号不能为空");

        $logistics_info = MfShopOrderLogistic::find($logistics_id);

        if(!$logistics_info) UtilsTool::exception("id有误");

        if($logistics_info['subsidy_money'] > 0) UtilsTool::exception("已经补贴，不能重复操作");

        $order_info = MfShopOrder::find($logistics_info->order_id);

        $shop_id = $order_info->shop_id;

        Db::beginTransaction();
        try {
            Db::table("mf_shop_order_logistics")->where("id",$logistics_id)
                ->update([
                    'subsidy_money'=>$money,
                    'logistics_code'=>$logistics_code
                ]);
            Db::table("mf_shop_account")->where('shop_id',$shop_id)
                ->increment('enable_money',$money);

            Db::table("mf_shop_trade_history")->insert([
                'shop_id'=>$shop_id,
                'why_info'=>"运费补贴：{$money}元",
                'do_user_id'=>session('admin_id'),
                'num'=>$money,
                'in_or_out'=>1,
                'create_time'=>date("Y-m-d H:i:s"),
                'trade_type'=>3 //变动类型,0-微信充值,1-采购消费,2-结算返还,3-物流费返还,4-邀请奖励,5-注册送
            ]);
            Db::commit();
            // 补贴到账消息
            Sms::send($order_info->mobile,'FRIGHT_SUBSIDY',['order_code'=>$order_info->order_code,'fee'=>$money]);
            // 余额变动消息
            Sms::send($order_info->mobile,'ACCOUNT_ADD_NOTICE',['price'=>abs($money),'remark'=>"运费补贴：{$money}元"]);

        }catch (\Exception $e)
        {
            Db::rollback();
            Logger::init()->error($e->getMessage());
            UtilsTool::exception("操作失败，请联系管理员:".$e->getMessage(),$e->getCode());
        }

    }

    public static function getShopStoreInfo($shop_id = 0,$mobile= "")
    {
        $shop_account = MfShopAccount::selectRaw("user_recharge_money,user_recharge_count,enable_recharge_money")
            ->where("shop_id",$shop_id)
            ->first();

        $today_total_count = MfUserOrder::where("create_time",'>=',date("Y-m-d 00:00:00"))
            ->where("shop_id",$shop_id)->count();

        $today_total_price = MfUserOrder::where("create_time",'>=',date("Y-m-d 00:00:00"))
            ->where("shop_id",$shop_id)->sum("product_total_price");

        $shop_user_count = MfUserCard::where("shop_id",$shop_id)->where("state",1)->count();

        return [
            'user_recharge_money'=>$shop_account->user_recharge_money,
            'user_recharge_count'=>$shop_account->user_recharge_count,
            'enable_recharge_money'=>$shop_account->enable_recharge_money,
            'shop_user_count'=>$shop_user_count,
            'today_total_count'=>$today_total_count,
            'today_total_price'=>$today_total_price,
            "recharge_keep_money"=>UtilsTool::config_value("RECHARGE_KEEP_MONEY"),
            "mobile"=>$mobile
        ];
    }

    public function getInfo($param = [])
    {
        $id = $param['id']??0;
        return MfShop::find($id);
    }
}