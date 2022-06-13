<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfUser;
use Mwenju\Common\Model\MfUserCard;
use Mwenju\Common\Model\MfUserOrder;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
class UserCardService
{
    private $maxNum = 100;

    #[Inject]
    private ValidatorFactoryInterface $validationFactory;

    public function getList($map = [],$rows = 30){
        $data = Db::table('mf_user_card as a')->selectRaw("a.*,b.sex,b.birthday")
            ->leftJoin('mf_user_detail as b','a.user_id','=','b.user_id')
            ->where($map)
            ->paginate($rows);
        return ['total'=>$data->total(),'rows'=>$data->items()];
    }

    public function getInfoByNumber($card_number = ''){
        if(empty($card_number)) return [];
        if(strlen($card_number) >= 8){
            $where[] = ['a.card_number','=',$card_number];
        }else{
            $where[] = ['a.key_number','=',$card_number];
        }
        $row = Db::table('mf_user_card as a')->selectRaw("a.*,b.addr")
            ->leftJoin('mf_shop as b','a.shop_id','=','b.id')
            ->where($where)->first();
        if(!$row) return [];

        $row->consume_total = Db::table("mf_user_order")->where("user_id",$row->user_id)->where("shop_id",$row->shop_id)->sum("paid_price");

        return $row;
    }

    public function getInfoByNumberAndUserId($card_number = '',$user_id = 0){
        if(strlen($card_number) >= 8){
            $where[] = ['card_number','=',$card_number];
        }else{
            $where[] = ['key_number','=',$card_number];
        }
        return MfUserCard::where($where)->where("user_id",$user_id)->first();
    }

    public function getInfoById($card_id = 0){
        return MfUserCard::find($card_id);
    }

    public function getInfoByMobile($mobile = ''){
        return MfUserCard::where("mobile",$mobile)->first();
    }

    public function getListByUserId($user_id = 0,$shop_id = 0){

        $map[] = ['a.user_id','=',$user_id];
        $map[] = ['a.state','=',1];
        if($shop_id > 0){
            $map[] = ['a.shop_id','=',$shop_id];
        }
        $res = Db::table("mf_user_card as a")->selectRaw("a.id,a.card_number,a.shop_id,a.user_id,a.real_name,a.shop_name,
            a.balance,a.balance_total,a.integral,a.integral_total,a.qr_url,a.active_time,b.link_mobile mobile,b.addr,b.face_img,b.desction")
            ->leftJoin('mf_shop as b','a.shop_id','=','b.id')
            ->where($map)->get()->each(function ($item,$index){
                $item->qr_url = self::qrUrl($item->card_number);
                $item->face_img = UtilsTool::img_url($item->face_img);
                $item->consume_total  = MfUserOrder::where("card_number",$item->card_number)
                    ->where("state",1)
                    ->sum("paid_price");
            });
        return $res;
    }

    /**
     * @param int $user_id
     * @param int $shop_id
     * @return bool|\Hyperf\Database\Model\Builder|\Hyperf\Database\Model\Model|object|null
     */
    public function getInfoByUserIdAndShopId($user_id = 0,$shop_id = 0)
    {
        if($user_id == 0 || $shop_id == 0) return false;
        $map[] = ['user_id','=',$user_id];
        $map[] = ['state','=',1];
        if($shop_id > 0){
            $map[] = ['shop_id','=',$shop_id];
        }
        $card_info = MfUserCard::where($map)->first();
        if ($card_info){
            $card_info->qr_url = self::qrUrl($card_info->card_number);
            $card_info->consume_total  = MfUserOrder::where("card_number",$card_info->card_number)
                ->where("state",1)
                ->sum("paid_price");
            return $card_info;
        }
        return false;
    }

    public function checkState($user_card){
        if(!$user_card)
            UtilsTool::exception("卡号有误");
        switch ($user_card->state){
            case 0:
                UtilsTool::exception("未激活");
            case 2:
                UtilsTool::exception("已冻结");
            case 3:
                UtilsTool::exception("已作废");
        }
    }

    /**
     * 会员卡绑定开卡
     * @param int $shop_id
     * @param string $mobile
     * @param string $card_number
     * @param string $name
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function bind($shop_id = 0,$mobile = '',$card_number = '',$name = ''){

        $validator = $this->validationFactory->make(
            ['mobile'=>$mobile],
            [
                'mobile' => 'numeric|required',
            ],
            [
                'mobile.required' => '手机号必填',
                'mobile.numeric' => '手机号格式有误',
            ]
        );

        if($validator->fails())
        {
            UtilsTool::exception($validator->errors()->first());
        }

        if(strlen($card_number) >= 8){
            $where[] = ['card_number','=',$card_number];
        }else{
            $where[] = ['key_number','=',$card_number];
        }

        $card = MfUserCard::where($where)->first();
        if(!$card){
            UtilsTool::logger()->error("卡号不正确:".$card_number);
            UtilsTool::exception("卡号不正确");
        }
        if($card->state != 0){
            $msg = [1=>'此卡已激活,不能重复操作','此卡已冻结','此卡已作废'];
            UtilsTool::logger()->error("绑定失败:".$msg[$card->state]);
            UtilsTool::exception($msg[$card->state]);
        }
        if($card->shop_id > 0 && $shop_id != $card->shop_id){
            UtilsTool::exception("此会员卡不属于当前门店哦");
        }
        $user = MfUser::where("mobile",$mobile)->first();
        if(!$user)
        {
            $userService = new UserService();
            $user = $userService->reg(['mobile'=>$mobile,'nick_name'=>$name,'real_name'=>$name]);

        }
        if(MfUserCard::where("user_id",$user->id)->where("shop_id",$shop_id)->count() > 0)
        {
            UtilsTool::exception("当前门店会员卡已存在，不能绑定多个哦！");
        }
        MfUserCard::where($where)->update([
            'mobile'=>$mobile,
            'user_id'=>$user->id,
            'shop_id'=>$shop_id,
            'real_name'=>$name,
            'shop_name'=>MfShop::where("id",$shop_id)->value("cname"),
            'active_time'=>date("Y-m-d H:i:s"),
            'state'=>1
        ]);
        // 自动关注门店
        $userShopFollowService = new UserShopFollowService();
        $userShopFollowService->add($user->id,$shop_id);
    }

    public function userBind($user_id = 0,$card_number = ''){
        if(empty($card_number)) UtilsTool::exception("卡号不能为空");
        if(strlen($card_number) >= 8){
            $where[] = ['card_number','=',$card_number];
        }else{
            $where[] = ['key_number','=',$card_number];
        }
        $card = MfUserCard::where($where)->first();

        if(!$card){
            UtilsTool::exception("卡号不正确");
        }
        if($card->user_id == $user_id && ($card->card_number==$card_number || $card->key_number==$card_number))
        {
            return;
        }
        if($card->shop_id == 0)
        {
            UtilsTool::exception("此卡未被门店激活");
        }
        if($card->user_id > 0 && $card->user_id != $user_id)
        {
            UtilsTool::exception("此卡已绑定，不能再次绑定");
        }
        if($card->state > 1){
            $msg = [1=>'此卡已激活,不能重复操作','此卡已冻结','此卡已作废'];
            UtilsTool::exception($msg[$card->state]);
        }
        if(MfUserCard::where("user_id",$user_id)->where("shop_id",$card->shop_id)->count() > 0)
        {
            UtilsTool::exception("当前门店会员卡已存在，不能绑定多个哦！");
        }
        $user = MfUser::where("id",$user_id)->first();
        MfUserCard::where($where)->update([
            'mobile'=>$user->mobile,
            'real_name'=>$user->nick_name,
            'user_id'=>$user_id,
            'state'=>1,
            'active_time'=>date("Y-m-d H:i:s")
        ]);
        // 自动关注门店
        $userShopFollowService = new UserShopFollowService();
        $userShopFollowService->add($user_id,$card->shop_id);
    }

    /**
     * 自动绑定虚拟卡
     * @param int $shop_id
     * @param int $user_id
     * @param string $mobile
     */
    public function autoBind($shop_id = 0,$user_id = 0,$mobile = '')
    {
        if(MfUserCard::where("user_id",$user_id)->where("shop_id",$shop_id)->where("state","<>",3)->exists())
        {
            return;
        }
        $last_id = MfUserCard::max("id") + 1;

        $card_number = str_pad(1, 3, "0",STR_PAD_RIGHT).
            str_pad($last_id,3,"0",STR_PAD_LEFT).UtilsTool::get_rand(2);

        $shop = MfShop::find($shop_id);
        $user = MfUser::find($user_id);

        MfUserCard::insert([
            'card_number'=>$card_number,
            'shop_id'=>$shop_id,
            'shop_name'=>$shop->cname,
            'real_name'=>$user->nick_name,
            'batch_num'=>0,
            'user_id'=>$user_id,
            'mobile'=>$mobile,
            'state'=>1,
            'active_time'=>date("Y-m-d H:i:s"),
            'create_time'=>date("Y-m-d H:i:s")
        ]);
    }
    /**
     * 批量创建
     * @param int $shop_id
     * @param int $num
     * @param int $batch_num
     * @return array
     * @throws \Exception
     */
    public function createBatch($shop_id = 0,$num = 1,$batch_num = 0){
        $insert = [];
        $shop_name = null;
        if($shop_id > 0){
            $shop = MfShop::find($shop_id);
            if(!$shop){
                UtilsTool::exception('店铺ID不存在');
            }
            if($shop->status != 1) UtilsTool::exception("店铺未审核");
            $shop_name = $shop->cname;
        }
        $batch_num = $batch_num > 0 ? $batch_num : (MfUserCard::max("batch_num") + 1);
        $last_id = MfUserCard::max("id") + 1;
        if($num > $this->maxNum){
            $n = floor($num/$this->maxNum);
            for ($i=0;$i<$n;$i++){
                $insert =  $this->createBatch($shop_id,$this->maxNum,$batch_num);
            }
            $n1 = $num - $n*$this->maxNum;
            if($n1 > 0){
                $insert = $this->createBatch($shop_id,$n1,$batch_num);
            }
            return $insert;
        }
        else{
            for ($i=0;$i<$num;$i++){
                $card_number = str_pad($batch_num, 3, "0",STR_PAD_RIGHT);
//                $card_number =  "1".$prefix;
                $card_number .= str_pad($last_id+$i,3,"0",STR_PAD_LEFT).UtilsTool::get_rand(2);
                $insert[] = [
                    'card_number'=>$card_number,
                    'shop_id'=>$shop_id,
                    'shop_name'=>$shop_name,
                    'batch_num'=>$batch_num
                ];
            }
            try {
                MfUserCard::insert($insert);
            }catch (\Exception $e){
                UtilsTool::exception($e->getMessage());
                return $this->createBatch($shop_id,$num,$batch_num);
            }
            return $insert;
        }
    }

    public function createCard($shop_id = 0)
    {
        $last_id = MfUserCard::max("id") + 1;
        $shop_name = null;
        if($shop_id > 0){
            $shop = MfShop::find($shop_id);
            if(!$shop){
                UtilsTool::exception('店铺ID不存在');
            }
            if($shop->status != 1)
                UtilsTool::exception("店铺未审核");
            $shop_name = $shop->cname;
        }
        $card_number = str_pad(1, 3, "0",STR_PAD_RIGHT);
        $card_number .= str_pad($last_id+1,3,"0",STR_PAD_LEFT).UtilsTool::get_rand(2);
        $insert = [
            'card_number'=>$card_number,
            'shop_id'=>$shop_id,
            'shop_name'=>$shop_name,
            'batch_num'=>0
        ];
        MfUserCard::insert($insert);
    }

    /**
     * 充值
     * @param $card_number
     * @param $price
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function recharge($card_number,$price){
        if(strlen($card_number) >= 8){
            $where[] = ['card_number','=',$card_number];
        }else{
            $where[] = ['key_number','=',$card_number];
        }
        $card = MfUserCard::where($where)->first();
        if(!$card)
            UtilsTool::exception("卡号不存在");
        if(empty($price) || $price == 0 || !is_numeric($price))
            UtilsTool::exception("充值金额填写有误");
        $this->balanceUpdate($card_number,abs($price));
    }

    /**
     * 订单余额支付
     * @param string $card_number
     * @param int $price
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function orderBalancePay($card_number = '',$price = 0){

        if(empty($price) || $price == 0 || !is_numeric($price))
            UtilsTool::exception("充值金额填写有误");
        $this->balanceUpdate($card_number,-abs($price),2,'门店扣减');
    }

    /**
     * 交易撤销退还余额
     * @param int $shop_id
     * @param int $user_id
     * @param int $price
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function orderCancel($shop_id = 0,$user_id = 0,$price = 0){
        $card = MfUserCard::where("shop_id",$shop_id)->where("user_id",$user_id)->first();
        if(!$card){
            UtilsTool::exception("会员卡有误");
        }
        if(!$card)
            UtilsTool::exception("卡号不存在");
        if(empty($price) || $price == 0 || !is_numeric($price))
            UtilsTool::exception("金额填写有误");
        $this->balanceUpdate($card->card_number,abs($price),3,'交易撤销');
        $this->integralUpdateByCardNumber($card->card_number,-intval($price),2,'撤销扣除');
    }

    public function integralExchange($shop_id = 0,$card_number = 0,$num = 0,$remark = "")
    {
        $this->integralUpdateByCardNumber($card_number,-$num,3,$remark?$remark:"店铺积分兑换");
    }

    /**
     * 余额更新
     * @param $card_number
     * @param $add_num
     * @param int $add_type 变更类型，1-充值，2-店铺扣减，3-取消退还
     * @param string $why_info
     * @throws \Hyperf\Di\Exception\Exception
     */
    private function balanceUpdate($card_number,$add_num,$add_type = 1,$why_info = "充值"){
        if(strlen($card_number) >= 8){
            $where[] = ['card_number','=',$card_number];
        }else{
            $where[] = ['key_number','=',$card_number];
        }
        $card = MfUserCard::where($where)->first();
        if(!$card){
            UtilsTool::exception("会员卡有误");
        }
        if($card->balance == 0) return;
        if($add_num == 0) return;
        $balance = $card->balance + $add_num;
        if($add_type == 1){
            $balance_total = $card->balance_total + $add_num;
        }else{
            $balance_total = $card->balance_total;
        }
        if($balance < 0){
            UtilsTool::exception("余额不足");
        }

        $upNum = Db::table("mf_user_card")->where($where)
            ->where("balance",$card->balance)
            ->update(['balance'=>$balance,'balance_total'=>$balance_total]);
        if($upNum == 0){
            UtilsTool::exception("操作失败");
        }
        // 更新门店账户统计
        Db::table("mf_shop_account")->where('shop_id',$card->shop_id)
            ->increment("user_recharge_money",$add_num);
        if($add_type == 1){
            Db::table("mf_shop_account")->where('shop_id',$card->shop_id)
                ->update([
                    'enable_recharge_money'=>Db::raw("enable_recharge_money+".$add_num),
                    'user_recharge_count'=>Db::raw("user_recharge_count + 1")]);
        }
        Db::table("mf_user_balance_log")->insert([
            'shop_id'=>$card->shop_id,
            'user_id'=>$card->user_id,
            'after_balance'=>$card->balance + $add_num,
            'add_type'=>$add_type,
            'add_num'=>$add_num,
            'why_info'=>$why_info,
        ]);
    }

    public function integralUpdateByUserIdShopId($user_id = 0,$shop_id = 0,$add_num = 0,$add_type = 1,$why_info = "下单赠送")
    {
        $card = Db::table("mf_user_card")->where("shop_id",$shop_id)->where("user_id",$user_id)->first();
        if(!$card){
            UtilsTool::exception("您还不是会员，请联系店主办理");
        }
        return $this->integralUpdate($card,$add_num,$add_type,$why_info);
    }

    public function integralUpdateByCardNumber($card_number = 0,$add_num = 0,$add_type = 1,$why_info = "下单赠送")
    {
        if(strlen($card_number) >= 8){
            $where[] = ['card_number','=',$card_number];
        }else{
            $where[] = ['key_number','=',$card_number];
        }

        $card = Db::table("mf_user_card")->where($where)->first();
        if(!$card){
            UtilsTool::exception("您不是当前店铺会员");
        }

        return $this->integralUpdate($card,$add_num,$add_type,$why_info);
    }


    /**
     * 积分更新
     * @param $card
     * @param int $add_num
     * @param int $add_type
     * @param string $why_info
     * @return array|void
     * @throws \Hyperf\Di\Exception\Exception
     */
    private function integralUpdate($card,$add_num = 0,$add_type = 1,$why_info = "下单赠送"){

        $add_num = intval($add_num);
        if($add_num == 0) return;
        $integral = $card->integral + $add_num;
        if($add_type == 1){
            $integral_total = $card->integral_total + $add_num;
        }
        else{
            $integral_total = $card->integral_total;
        }
        if($integral < 0){
            UtilsTool::exception("积分不足");
        }

        $upNum = Db::table("mf_user_card")->where('id',$card->id)
            ->where("integral",$card->integral)
            ->update(['integral'=>$integral,'integral_total'=>$integral_total]);
        if($upNum == 0){
            UtilsTool::exception("操作失败");
        }
        Db::table("mf_user_integral_log")->insert([
            'shop_id'=>$card->shop_id,
            'user_id'=>$card->user_id,
            'after_integral'=>$card->integral + $add_num,
            'add_type'=>$add_type,
            'add_num'=>$add_num,
            'why_info'=>$why_info,
            'create_time'=>date("Y-m-d H:i:s"),
            'ip'=>get_client_ip()
        ]);
        return [$add_num,$card->integral + $add_num,$card->card_number];
    }

    public static function qrUrl($card_number = '')
    {
        return UtilsTool::url("/api/qrcode/index",['card_number'=>$card_number],'',UtilsTool::config_value("api.url_domain_root"));
    }

    public static function qrStr($card_number = '')
    {
        return 'https://api.mwenju.com/uapi/user/qrcode?card_number='.$card_number;
    }
}