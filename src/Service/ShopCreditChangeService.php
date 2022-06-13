<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\YunShopCredit;
use Mwenju\Common\Model\YunShopCreditChange;
use Hyperf\DbConnection\Db;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 授信记录
 * Class ShopCreditChangeService
 * @package App\Common\Service
 * @RpcService(name="ShopCreditChangeService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("ShopCreditChangeService","jsonrpc","jsonrpc")]
class ShopCreditChangeService extends BaseService
{
    public function getList($param = [])
    {
        $param = array_map('trim',$param);
        $audit_status   = $param['audit_status']??"";
        $is_delete      = $param['is_delete']??0;
        $supplier_id    = $param['supplier_id']??0;
        $keyword        = $param['keyword']??"";
        list($page,$limit) = $this->pageFmt($param);
        $map[] = ['a1.is_delete','=',$is_delete];

        if (strlen($audit_status) > 0){
            $map[] = ['a1.audit_status','=',$audit_status];
        }
        if ($supplier_id > 0){
            $map[] = ['a1.supplier_id','=',$supplier_id];
        }

        $data = Db::table("yun_shop_credit_change as a1")->selectRaw("a1.*,a.shop_id,a.supplier_id,a.enable_money,
            a.start_time,b.cname shop_name,c.supplier_name,a.end_time before_end_time,a.credit_limit_money before_credit_limit_money")
            ->leftJoin("yun_shop_credit as a","a.id","=","a1.shop_credit_id")
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
        $list = $data->orderBy("a1.id","desc")->limit($limit)->offset($page)->get()->each(function ($item,$index){
            $item->enable_money = $item->enable_money + ($item->credit_limit_money - $item->before_credit_limit_money);
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
        return YunShopCreditChange::find($id);
    }

    public function create($param = [])
    {
        $shop_credit_id         = $param['id']??0;
        $payer                  = $param['payer']??0;
        $session_admin_id       = $param['session_admin_id']??0;
        $end_time               = $param['end_time']??0;
        $credit_limit_money     = $param['credit_limit_money']??0;
        $remarks                = $param['remarks']??"";
        $files                  = $param['files']??"";
        $create_by              = $param['create_by']??"";
        $create_time            = date("Y-m-d H:i:s");

        if (empty($shop_credit_id))
            return arrayError("请选择授信记录");

        if (empty($end_time))
            return arrayError("请选择截至日期");

        $parent_row = YunShopCredit::find($shop_credit_id);

        if ($parent_row->audit_status == 0)
            return arrayError("当前授信还未审核，可以直接修改保存哦");

        $row = YunShopCreditChange::where("shop_credit_id",$shop_credit_id)->where("audit_status",0)->count();

        if ($row > 0)
            return arrayError("已有申请等待审核，不能重复提交");

        $end_time               = date("Y-m-d 23:59:59",strtotime($end_time));

        $id = YunShopCreditChange::insertGetId([
            'shop_credit_id'    =>$shop_credit_id,
            'supplier_id'       =>$parent_row->supplier_id,
            'end_time'          =>$end_time,
            'credit_limit_money'=>abs($credit_limit_money),
            'remarks'           =>$remarks,
            'files'             =>$files,
            'create_by'         =>$create_by,
            'create_admin_id'   =>$session_admin_id,
            'create_time'       =>$create_time,
            'payer'             =>$payer,
        ]);
        return arraySuccess("添加成功",["id"=>$id]);
    }

    public function audit($param = [])
    {
        $id             = $param['id']??0;
        $audit_status   = $param['audit_status']??0;
        $audit_by       = $param['audit_by']??"";
        $audit_user_id  = $param['audit_user_id']??0;

        $row = YunShopCreditChange::find($id);
        if (!$row)
            return arrayError("记录不存在");

        if ($row->audit_status > 0)
            return arrayError("已审核，不能重复操作");

        if (!in_array($audit_status,[1,2]))
            return arrayError("状态值有误");

        $row->audit_status = $audit_status;
        $row->audit_user_id = $audit_user_id;
        $row->audit_by = $audit_by;
        $row->audit_time = date("Y-m-d H:i:s");
        $row->save();
        if($audit_status == 1)
        {
            $parent_row = YunShopCredit::find($row->shop_credit_id);
            $new_enable_money = $parent_row->enable_money + ($row->credit_limit_money - $parent_row->credit_limit_money);
            $parent_row->enable_money = $new_enable_money;
            $parent_row->credit_limit_money = $row->credit_limit_money;
            $parent_row->end_time = $row->end_time;
            $parent_row->payer = $row->payer;
            $parent_row->save();
        }

        return arraySuccess("操作成功");
    }

    public function delete($param = [])
    {
        $id             = $param['id']??0;
        $delete_by      = $param['delete_by']??"";
        $delete_time    = date("Y-m-d H:i:s");
        $row = YunShopCreditChange::find($id);
        $row->is_delete = 0;
        $row->delete_by = $delete_by;
        $row->delete_time = $delete_time;
        $row->save();
        return arraySuccess("操作成功");
    }
}