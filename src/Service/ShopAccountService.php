<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfShopAccount;
use Mwenju\Common\Model\MfUser;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Exception\Exception;

class ShopAccountService
{
    /**
     * 金额变更
     * @param int $shop_id
     * @param int $money
     * @param string $info
     * @param int $trade_type
     * @throws Exception
     */
    public function changeAccount($shop_id = 0,$money = 0,$info = '',$trade_type = 0,$do_user_id = 0)
    {
        if($shop_id == 0 || $money == 0) throw new Exception('参数有误');

        $shop_info = MfShopAccount::firstOrCreate(['shop_id'=>$shop_id]);

        $update = [];
        if($money > 0)
        {
            $in_or_out = 1;
            $update['enable_money'] = $shop_info->enable_money  + $money;
            if($trade_type == 0){
                $update['all_money'] = $shop_info->all_money  + $money;
            }
        }
        else
        {
            $in_or_out = -1;
            $update['enable_money'] = $shop_info->enable_money  - abs($money);
        }
        Db::table("mf_shop_account")->where("shop_id",$shop_id)->update($update);

        Db::table("mf_shop_trade_history")->insert([
            'shop_id'=>$shop_id,
            'why_info'=>$info,
            'do_user_id'=>$do_user_id,
            'num'=>abs($money),
            'in_or_out'=>$in_or_out,
            'trade_type'=>$trade_type,
            'create_time'=>date("Y-m-d H:i:s")
        ]);

        $user_id = MfShop::where("id",$shop_id)->value("user_id");
        $mobile = MfUser::where("id",$user_id)->value("mobile");

        // 余额变动通知
        if($money > 0)
        {
            $template_id = 'ACCOUNT_ADD_NOTICE';
        }else{
            $template_id = 'ACCOUNT_DEC_NOTICE';
        }
        Sms::send($mobile,$template_id,['price'=>abs($money),'remark'=>$info]);

    }

    public static function getIntegrateList($shop_id = 0,$page = 1,$rows = 10)
    {
        return Db::table("mf_shop_account_log")->selectRaw("*,'' after_integral")->where("shop_id",$shop_id)
            ->where("account_field",'enable_integrate')
            ->orderBy("id",'desc')
            ->limit($rows)
            ->offset($page)
            ->get()->toArray();
    }

    /**
     * 商户账号更新
     * @param int $shop_id
     * @param string $account_field
     * @param string $add_type
     * @param int $add_num
     * @param string $why_info
     */
    public static function update($shop_id = 0,$account_field = "",$add_type = '',$add_num = 0,$why_info = "")
    {
        Db::table("mf_shop_account")->where("shop_id",$shop_id)
            ->increment($account_field,$add_num);
        Db::table("mf_shop_account_log")->insert([
            'shop_id'=>$shop_id,
            'account_field'=>$account_field,
            'add_num'=>$add_num,
            'add_type'=>$add_type,
            'create_time'=>date("Y-m-d H:i:s"),
            'why_info'=>$why_info,
            'in_or_out'=>$add_num>0?1:-1
        ]);

    }

    /**
     * 充值提现申请提交
     * @param int $shop_id
     * @param int $money
     * @throws Exception
     */
    public static function applyCashSubmit($shop_id = 0,$money = 0)
    {
        $sa = MfShopAccount::where("shop_id",$shop_id)->first();
        $keep_money = UtilsTool::config_value("RECHARGE_KEEP_MONEY");
        if($money > $sa->enable_recharge_money - $keep_money){
            UtilsTool::exception("余额不足");
        }
        Db::table("mf_shop_account")->where("shop_id",$shop_id)
            ->update([
                'enable_recharge_money'=>Db::raw("enable_recharge_money - {$money}"),
                'freeze_recharge_money'=>Db::raw("freeze_recharge_money + {$money}"),
            ]);
    }

    public static function applyCashAudit($shop_id = 0)
    {
        $shop_row = MfShopAccount::where("shop_id",$shop_id)->first();
        Db::table("mf_shop_account")->where("shop_id",$shop_id)
            ->update(["freeze_recharge_money"=>0]);
        Db::table("mf_shop_account_log")->insert([
            'shop_id'=>$shop_id,
            'account_field'=>"enable_recharge_money",
            'add_num'=>$shop_row->freeze_recharge_money,
            'add_type'=>2,
            'create_time'=>date("Y-m-d H:i:s"),
            'why_info'=>"提现",
            'in_or_out'=>-1
        ]);
    }
}