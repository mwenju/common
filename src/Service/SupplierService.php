<?php


namespace Mwenju\Common\Service;

use Mwenju\Common\Model\MfUser;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Model\YunShopCredit;
use Mwenju\Common\Service\Dao\SupplierAccountDao;
use Mwenju\Common\Service\Dao\SupplierDao;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class SupplierService
 * @package App\Common\Service
 * @RpcService(name="SupplierService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("SupplierService","jsonrpc","jsonrpc")]
class SupplierService extends BaseService
{
    #[Inject]
    private UserService $userService;

    #[Inject]
    private SupplierDao $supplierDao;

    #[Inject]
    private SupplierAccountDao $supplierAccountDao;

    public function getList($param = [])
    {
        $audit_status   = $param['audit_status']??"";
        $status         = $param['status']??1;
        $keyword        = $param['keyword']??"";
        list($page,$limit) = $this->pageFmt($param);
        $map = [];
        if (strlen($audit_status) > 0){
            $map[] = ['a.audit_status','=',$audit_status];
        }
        if (strlen($status) > 0){
            $map[] = ['a.status','=',$status];
        }
        $data = Db::table("tb_supplier as a")->selectRaw('a.*,b.mobile,c.enable_money,c.freeze_money,
            c.all_money,c.all_out_money,c.get_money,c.rebate,c.min_price,c.bank_id,c.bank_area,c.bank_area_id,c.bank_account,
            c.bank_card_number,0 balance,a.audit_status,\'\' audit_status_str,\'\' new_type ')
            ->leftJoin("mf_user as b",'b.id','=','a.user_id')
            ->leftJoin("tb_supplier_account as c",'c.supplier_id','=','a.id')
            ->where($map)->when($keyword,function ($query,$keyword){
            return $query->where(function ($q) use ($keyword){
                return $q->where("a.supplier_name",'like',"%{$keyword}%")
                    ->orWhere("a.link_man",'like',"%{$keyword}%")
                    ->orWhere("b.mobile",'like',"%{$keyword}%")
                    ->orWhere("a.link_mobile",'like',"%{$keyword}%");
            });
        });
        $total = $data->count();
        $list = $data->orderBy("id","desc")->limit($limit)->offset($page)->get()->each(function ($item,$index){
            $item->balance = bcsub($item->all_money,$item->all_out_money,4);
            $item->audit_status_str = trans("audit.status_".$item->audit_status);
            $item->new_type = trans("new_type.new_type_".$item->is_new);
        });
        return ['total'=>$total,'rows'=>$list];
    }

    public function audit($param = [])
    {
        $id = $param['id'] ?? 0;
        $audit_status = $param['audit_status'] ?? 0;
        $session_user_id = $param['session_user_id']??0;
        $session_admin_id = $param['session_admin_id']??0;
        $row = TbSupplier::find($id);
        if (!$row) return arrayError("记录不存在");
        if ($row->audit_status > 0) return arrayError("已审核，不能重复操作");
        $row->audit_status = $audit_status;
        $row->save();
        return arraySuccess("已审核成功");
    }

    public function selectList($param = [])
    {
        $shop_id    = $param['shop_id']??0;
        $is_new     = $param['is_new']??0;
        $map[]      = ['status','=',1];
        $supplier_ids = [];
        if ($shop_id > 0)
        {
            $ids  =YunShopCredit::where("shop_id",$shop_id)->pluck('supplier_id');
            if ($ids->count() > 0){
                foreach ($ids as $supplier_id)
                {
                    $supplier_ids[] = $supplier_id;
                }
            }
            else
            {
                $supplier_ids[] = -1;
            }
        }
        if ($is_new > 0)
        {
            $map[] = ['is_new','=',$is_new];
        }
        return TbSupplier::where($map)->when($supplier_ids,function ($query,$supplier_ids){
            return $query->whereIn("id",$supplier_ids);
        })->get();
    }

    public function create($param = [])
    {
        $pwd    = $param['password']??'';
        $mobile = $param['mobile']??'';
        try {
            $param['user_id'] = $this->checkUserMobile($mobile,0,$pwd);
            $param['supplier_id'] = $this->supplierDao->create($param)->id;
            $this->supplierAccountDao->create($param);
        }catch (\Exception $e)
        {
            return arrayError($e->getMessage());
        }
        return arraySuccess("创建成功");
    }

    public function update($param = [])
    {
        $pwd    = $param['password']??'';
        $mobile = $param['mobile']??'';
        $id     = intval($param['id'])??0;
        try {
            $param['user_id'] = $this->checkUserMobile($mobile,$id,$pwd);
            $this->supplierDao->update($id,$param);
            $param['supplier_id'] = $id;
            $this->supplierAccountDao->update($id,$param);
        }catch (\Exception $e)
        {
            return arrayError($e->getMessage());
        }
        return arraySuccess("更新成功");
    }

    private function checkUserMobile($mobile,$supplier_id = 0,$pwd = '')
    {
        if($supplier_id > 0)
        {
            if(!empty($mobile))
            {
                $user_id = MfUser::where('mobile',$mobile)->first()->id;
                if(!$user_id)
                {
                    $user_id = $this->userService->reg(['mobile'=>$mobile,'pwd'=>$pwd]);
                }
                $res = TbSupplier::where('user_id','=',$user_id)->where('id','<>',$supplier_id)->count();
                if($res)
                {
                    UtilsTool::exception('已注册'.$mobile);
                }
            }
            else
            {
                $user_id = TbSupplier::where('id',$supplier_id)->first()->user_id;
            }
        }
        else
        {
            $user_id = MfUser::where('mobile',$mobile)->first()->id;
            if($user_id)
            {
                $res = TbSupplier::where('user_id','=',$user_id)->where('id','<>',$supplier_id)->count();
                if($res)
                {
                    UtilsTool::exception('已绑定其他供应商'.$mobile);
                }
            }
            if(!$user_id)
            {
                $user_id = $this->userService->reg(['mobile'=>$mobile,'pwd'=>$pwd]);
            }
        }
        return $user_id;
    }
}