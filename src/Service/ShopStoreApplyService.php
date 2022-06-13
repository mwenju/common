<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShopStore;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class ShopStoreApplyService
{
    public static function submit($shop_id = 0,$ID_number = '',$ID_card_img = '',$qr_code_img = '',$face_img='',$desction='')
    {
        $ssa = MfShopStore::where("shop_id",$shop_id)->first();
        if($ssa){
            UtilsTool::exception("已经提交申请，不能重复提交");
        }
        if(empty($ID_number)){
//            exception("身份证号码不能为空");
        }
        if(empty($ID_card_img)){
//            exception("请上传身份证图片");
        }
        if(empty($qr_code_img)){
//            exception("请上传收款二维码");
        }

        MfShopStore::insert([
            'shop_id'=>$shop_id,
            'id_number'=>$ID_number,
            'id_card_img'=>$ID_card_img,
            'qr_code_img'=>$qr_code_img,
            'face_img'=>$face_img,
            'desction'=>$desction,
            'create_time'=>date("Y-m-d H:i:s")
        ]);
    }

    public static function update($shop_id = 0,$ID_number = '',$ID_card_img = '',$qr_code_img = '')
    {
        $ssa = MfShopStore::where("shop_id",$shop_id)->first();
        if($ssa->audit_state > 0){
            UtilsTool::exception("已审核通过不能修改");
        }
        if(empty($ID_number)){
            UtilsTool::exception("身份证号码不能为空");
        }
        if(empty($ID_card_img)){
            UtilsTool::exception("请上传身份证图片");
        }
        if(empty($qr_code_img)){
            UtilsTool::exception("请上传收款二维码");
        }

        MfShopStore::where('shop_id',$shop_id)->update([
            'ID_number'=>$ID_number,
            'ID_card_img'=>$ID_card_img,
            'qr_code_img'=>$qr_code_img,
        ]);
    }

    public static function audit($shop_id = 0,$audit_state = 1,$admin_id = 0,$audit_remark = ''){
        if(!in_array($audit_state,[1,-1])) UtilsTool::exception("状态值有误");
        $row = Db::table("mf_shop_store as a")->selectRaw("a.*,c.mobile")
            ->leftJoin('mf_shop as b','b.id','=','a.shop_id')
            ->leftJoin('mf_user as c','c.id','=','b.user_id')
            ->where("shop_id",$shop_id)->first();
        if(!$row) UtilsTool::exception("记录不存在");
        if($row->audit_state != 0) UtilsTool::exception("当前申请已审核不能再操作");
        if(Db::table("mf_shop_store")
            ->where("shop_id",$shop_id)
            ->where("audit_state",0)
            ->update(['audit_state'=>$audit_state,
                'audit_time'=>date("Y-m-d H:i:s"),
                'admin_id'=>$admin_id,
                'audit_remark'=>$audit_remark]))
        {

            if($audit_state == 1) // 通过
            {
                Sms::send($row->mobile,"SHOP_STORE_AUDIT_SUCCESS");
            }
            elseif ($audit_state == -1) // 未通过
            {
                Sms::send($row->mobile,"SHOP_STORE_AUDIT_FAIL",['remark'=>$audit_remark]);
            }

        }
    }

    public function getInfo($shop_id = 0){
        return MfShopStore::where("shop_id",$shop_id)->first();
    }

    public static function checkState($shop_id = 0){
        if($shop_id == 0)
            UtilsTool::exception("还未登录",302);
        $row = MfShopStore::where("shop_id",$shop_id)->first();
        if(!$row)
            UtilsTool::exception("您还未申请开通微店哦",3041);
        if($row->audit_state == 0)
            UtilsTool::exception("您的微店申请还在审核中，请耐心等待",3042);
        if($row->audit_state < 0)
            UtilsTool::exception("您的微店申请审核未通过",3043);
    }

    public static function checkStateReturn($shop_id = 0)
    {
        $code = 0;
        $msg = "";
        try {
            self::checkState($shop_id);
        }
        catch (\Exception $e)
        {
            $code = $e->getCode();
            $msg = $e->getMessage();
        }
        return [$code,$msg];
    }
}