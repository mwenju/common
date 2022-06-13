<?php


namespace Mwenju\Common\Service;


use _PHPStan_76800bfb5\Nette\Neon\Exception;
use Mwenju\Common\Model\YunShopCredit;
use Mwenju\Common\Model\YunShopCreditLog;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 授信记录
 * Class ShopCreditService
 * @package App\Common\Service
 * @RpcService(name="ShopCreditService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("ShopCreditService","jsonrpc","jsonrpc")]
class ShopCreditService extends BaseService
{
    #[Inject]
    protected AuditLogService $auditLogService;

    public function getList($param = [])
    {
        $param = array_map('trim',$param);
        $audit_status   = $param['audit_status']??"";
        $supplier_id    = $param['supplier_id']??0;
        $top_depot_id   = $param['top_depot_id']??1;
        $keyword        = $param['keyword']??"";
        list($page,$limit) = $this->pageFmt($param);
        $map = [];
        if (strlen($audit_status) > 0){
            $map[] = ['a.audit_status','=',$audit_status];
        }
        if ($supplier_id > 0){
            $map[] = ['a.supplier_id','=',$supplier_id];
        }

        $data = Db::table("yun_shop_credit as a")->selectRaw("a.*,b.cname shop_name,c.supplier_name")
            ->leftJoin("mf_shop as b","b.id","=","a.shop_id")
            ->leftJoin("tb_supplier as c","c.id","=","a.supplier_id")
            ->where($map)->when($keyword,function ($query,$keyword){
                return $query->where(function ($q) use ($keyword){
                    return $q->where("c.supplier_name",'like',"%{$keyword}%")
                        ->orWhere("b.cname",'like',"%{$keyword}%")
                        ->orWhere("a.create_by",'like',"%{$keyword}%");
                });
            });
        $total = $data->count();
        $list = $data->orderBy("id","desc")->limit($limit)->offset($page)->get()->each(function ($item,$index){
        });
        foreach ($list as $k=>$item)
        {
            $list[$k]->audit_status_str = trans("audit.status_".$item->audit_status);
        }
        return ['total'=>$total,'rows'=>$list];
    }

    public function getInfo($param = [])
    {
        $id = $param['id']??0;
        return YunShopCredit::find($id);
    }

    public function create($param = [])
    {
        $supplier_id            = $param['supplier_id']??0;
        $shop_id                = $param['shop_id']??0;
        $session_user_id        = $param['session_user_id']??0;
        $payer                  = $param['payer']??0;
        $start_time             = $param['start_time']??"";
        $end_time               = $param['end_time']??"";
        $credit_limit_money     = $param['credit_limit_money']??0;
        $remarks                = $param['remarks']??"";
        $files                  = $param['files']??"";
        $create_by              = $param['create_by']??"";
        $last_update_by         = $param['last_update_by']??"";
        $create_time            = date("Y-m-d H:i:s");
        $last_update_time       = date("Y-m-d H:i:s");

        if (empty($supplier_id) || empty($shop_id))
            return arrayError("请选择供应商和商家");
        if (empty($start_time) || empty($end_time))
            return arrayError("起止时间不能为空");
        if (strtotime($end_time) < strtotime($start_time))
            return arrayError("截止日期不能小于开始日期");

        $start_time = date("Y-m-d 00:00:00",strtotime($start_time));
        $end_time = date("Y-m-d 23:59:59",strtotime($end_time));

        $row = YunShopCredit::where("shop_id",$shop_id)->where("supplier_id",$supplier_id)->count();
        if ($row > 0)
            return arrayError("已创建不能重复创建");

        Db::beginTransaction();
        try {
            $id = YunShopCredit::insertGetId([
                'supplier_id'       =>$supplier_id,
                'shop_id'           =>$shop_id,
                'start_time'        =>$start_time,
                'end_time'          =>$end_time,
                'credit_limit_money'=>abs($credit_limit_money),
                'enable_money'      =>abs($credit_limit_money),
                'remarks'           =>$remarks,
                'files'             =>$files,
                'create_by'         =>$create_by,
                'last_update_by'    =>$last_update_by,
                'create_time'       =>$create_time,
                'last_update_time'  =>$last_update_time,
                'payer'             =>$payer,
            ]);

            $res = $this->auditLogService->add([
                'model'=>'yun_shop_credit',
                'model_id'=>$id,
                'audit_user_id'=>$session_user_id,
            ]);
            if ($res['err_code'] > 0) throw new Exception($res['msg']);
            Db::commit();
        }
        catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }

        return arraySuccess("添加成功",["id"=>$id]);
    }

    public function update($param = [])
    {
        $id                     = $param['id']??0;
        $payer                  = $param['payer']??0;
        $start_time             = $param['start_time']??"";
        $end_time               = $param['end_time']??"";
        $credit_limit_money     = $param['credit_limit_money']??0;
        $remarks                = $param['remarks']??"";
        $files                  = $param['files']??"";
        $last_update_by         = $param['last_update_by']??"";
        $last_update_time       = date("Y-m-d H:i:s");
        if (empty($id))
            return $this->create($param);

        if (empty($start_time) || empty($end_time))
            return arrayError("起止时间不能为空");

        if (strtotime($end_time) < strtotime($start_time))
            return arrayError("截止日期不能小于开始日期");

        $start_time = date("Y-m-d 00:00:00",strtotime($start_time));
        $end_time   = date("Y-m-d 23:59:59",strtotime($end_time));

        $row = YunShopCredit::find($id);
        if (!$row)
            return arrayError("记录不存在");

        $audit_status = $this->auditLogService->getLastAuditStatus(['model'=>'yun_shop_credit','model_id'=>$id]);
        if ($row->audit_status == 1 || $audit_status == 1)
        return arrayError("已审核不能修改,如需修改，请提交变更申请");

        $upRows = YunShopCredit::where("id",$id)->update([
            'start_time'        =>$start_time,
            'end_time'          =>$end_time,
            'credit_limit_money'=>abs($credit_limit_money),
            'enable_money'      =>abs($credit_limit_money),
            'remarks'           =>$remarks,
            'files'             =>$files,
            'last_update_by'    =>$last_update_by,
            'last_update_time'  =>$last_update_time,
            'audit_status'      =>0,
            'payer'             =>$payer,
        ]);

        return arraySuccess("更新成功");
    }

    public function audit($param = [])
    {
        $id                 = $param['id']??0;
        $audit_status       = $param['audit_status']??0;
        $session_user_id    = $param['session_user_id']??0;
        return $this->auditLogService->add([
            'model'=>'yun_shop_credit',
            'model_id'=>$id,
            'audit_status'=>$audit_status,
            'audit_user_id'=>$session_user_id,
        ]);
    }

    public function getShopInfo($param = []):?YunShopCredit
    {
        $shop_id        = $param['shop_id']??0;
        $supplier_id    = $param['supplier_id']??0;
        return YunShopCredit::where("shop_id",$shop_id)->where("supplier_id",$supplier_id)->first();
    }

    /**
     * @param int $supplier_id
     * @param int $shop_id
     * @param int $add_num
     * @param int $add_type
     * @param string $why_info
     */
    public function change($supplier_id = 0,$shop_id = 0,$add_num = 0,$add_type = 0,$why_info = '',$create_by = '')
    {
        $shopCredit = $this->getShopInfo(['shop_id'=>$shop_id,'supplier_id'=>$supplier_id]);
        if (!$shopCredit) return;
        $shopCredit->enable_money = $shopCredit->enable_money + $add_num;
        $shopCredit->save();

        $shopCreditLog = new YunShopCreditLog();
        $shopCreditLog->add_num     = $add_num;
        $shopCreditLog->add_type    = $add_type;
        $shopCreditLog->why_info    = $why_info;
        $shopCreditLog->create_by    = $create_by;
        $shopCreditLog->create_time = date("Y-m-d H:i:s");
        $shopCreditLog->save();
    }
}