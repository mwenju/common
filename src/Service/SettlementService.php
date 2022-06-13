<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfAdmin;
use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\TbProfitloss;
use Mwenju\Common\Model\TbProfitlossProduct;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Model\TbSupplierAccount;
use Mwenju\Common\Model\TbSupplierAccountLog;
use Mwenju\Common\Model\YunSettlement;
use Mwenju\Common\Model\YunSettlementPayLog;
use Mwenju\Common\Model\YunShopCredit;
use Mwenju\Common\Model\YunShopCreditLog;
use Mwenju\Common\Service\Dao\ShopOrderDao;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\Time;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\Database\Exception\QueryException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 结算单
 * Class SettlementService
 * @package App\Common\Service
 * @RpcService(name="SettlementService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("SettlementService","jsonrpc","jsonrpc")]
class SettlementService extends BaseService
{

    #[Inject]
    protected AuditLogService $auditLogService;

    #[Inject]
    private ShopOrderDao $shopOrderDao;

    public function getList($param = [])
    {
        $audit_status   = $param['audit_status']??"";
        $supplier_id    = $param['supplier_id']??0;
        $shop_id        = $param['shop_id']??0;
        $keyword        = $param['keyword']??"";
        list($page,$limit) = $this->pageFmt($param);
        $map = [];
        if (strlen($audit_status) > 0){
            $map[] = ['a.audit_status','=',$audit_status];
        }
        if ($supplier_id > 0){
            $map[] = ['a.supplier_id','=',$supplier_id];
        }
        if ($shop_id > 0){
            $map[] = ['a.shop_id','=',$shop_id];
        }
        $data = Db::table("yun_settlement as a")->selectRaw("a.*,b.supplier_name,c.cname shop_name,'' audit_status_str")
            ->leftJoin("tb_supplier as b","b.id","=","a.supplier_id")
            ->leftJoin("mf_shop as c","c.id","=","a.shop_id")
            ->where($map)->when($keyword,function ($query,$keyword){
                return $query->where(function ($q) use ($keyword){
                    return $q->where("a.supplier_name",'like',"%{$keyword}%")
                        ->orWhere("a.shop_name",'like',"%{$keyword}%")
                        ->orWhere("a.order_code",'like',"%{$keyword}%")
                        ->orWhere("a.create_by",'like',"%{$keyword}%");
                });
            });
        $total = $data->count();
        $list = $data->orderBy("id","desc")->limit($limit)->offset($page)->get()->each(function ($item,$index){
            $item->audit_status_str = trans("audit.status_".$item->audit_status);
        });
        return ['total'=>$total,'rows'=>$list];
    }

    public function getInfo($param = [])
    {
        $item = YunSettlement::selectRaw("*,'' audit_status_str")->find($param['id']??0);
        $item->audit_status_str = trans("audit.status_".$item->audit_status);
        return $item;
    }

    public function getOrderList($param = [])
    {
        $settlement_id = $param['settlement_id']??0;
        $map = [];
        if ($settlement_id > 0)
        {
            $map[] = ["settlement_id",'=',$settlement_id];
        }
        $data = Db::table("yun_settlement_order")->where($map);
        $total = $data->count();
        $total_price = 0;
        $list = $data->orderBy("id","desc")->get()->each(function ($item,$index) use (&$total_price){
            $total_price += $item->total_price;
        });
        $footer[] = ['total_price'=>$total_price];
        return ['total'=>$total,'rows'=>$list,'footer'=>$footer];
    }

    public function create($param = [])
    {
        $delivery_time_start            = $param['delivery_time_start']??'';
        $delivery_time_end              = $param['delivery_time_end']??"";
        $supplier_id                    = $param['supplier_id']??0;
        $session_user_id                = $param['session_user_id']??0;
        $shop_id                        = $param['shop_id']??0;
        $session_admin_id               = $param['session_admin_id']??0; // =0 系统创建
        $session_admin_name             = $param['session_admin_name']??"";
        $sale_total_price               = 0;
        $return_total_price             = 0;
        $credit_total_price             = 0;
        $service_fee_price              = 0;
        $freight_total_price            = 0;
        $bond_rate                      = 0;
        $service_fee_rate               = 0;
        $bid_total_price                = 0;

        if (empty($delivery_time_start) || empty($delivery_time_end))
            return arrayError("起止时间不能为空");
        if ($supplier_id == 0 || $shop_id == 0)
            return arrayError("请选择厂家与供应商");
        // 人工创建的日期不能重叠
        $res = Db::select("SELECT order_code from yun_settlement WHERE (start_time>=? && end_time<=?) or (start_time >=? && end_time <=?) and create_by_admin_id>0",[$delivery_time_start,$delivery_time_start,$delivery_time_end,$delivery_time_end]);
        if ($res)
        {
            return arrayError("时间重叠 ，请重新选择时间条件");
        }
        $shop               = MfShop::find($shop_id);
        $supplier           = TbSupplier::find($supplier_id);
        $bond_rate          = $supplier->bond_rate;
        $service_fee_rate   = $supplier->service_fee_rate;

        $data = Db::table("mf_shop_order")->where("shop_id",$shop_id)
            ->where("supplier_id",$supplier_id);

        if ($session_admin_id > 0){
            $data->where("pay_type",6);
        }else{
            $data->where("pay_type",5);
        }
        $data->where("jiesuan",0)
            ->where("delivery_time",">=",$delivery_time_start)
            ->where("delivery_time","<=",date("Y-m-d 23:59:59",strtotime($delivery_time_end)));
        $total_num = $data->count();

        if ($total_num == 0)
            return arrayError("没有满足条件的订单哦");

        $audit_status = 0;
        // 系统创建自动审核通过
        if ($session_admin_id == 0){
            $audit_status = 1;
        }
        Db::beginTransaction();
        try {
            $id = Db::table("yun_settlement")->insertGetId([
                'order_code'            =>'JSD'.UtilsTool::create_order_number(),
                'shop_id'               =>$shop_id,
                'supplier_id'           =>$supplier_id,
                'supplier_name'         =>$supplier->supplier_name,
                'shop_name'             =>$shop->cname,
                'start_time'            =>$delivery_time_start,
                'end_time'              =>$delivery_time_end,
                'create_time'           =>date("Y-m-d H:i:s"),
                'create_by'             =>$session_admin_name,
                'create_by_admin_id'    =>$session_admin_id,
                'total_num'             =>$total_num,
                'service_fee_rate'      =>$service_fee_rate,
                'bond_rate'             =>$bond_rate,
                'paid_price'            =>0,
                'audit_status'          =>$audit_status,
            ]);

            $insert         = [];
            $sale_order_ids = [];
            $return_order_ids = [];

            $data->get()->each(function ($item,$index) use (&$insert,$id,$shop,$supplier,
                &$sale_total_price,&$return_total_price,&$freight_total_price,&$sale_order_ids,&$return_order_ids){
                $item->total_price = $item->is_return == 1 ? -$item->total_price : $item->total_price;
                if ($item->is_return == 1) {
                    $return_total_price += abs($item->total_price);
                    $return_order_ids[] = $item->id;
                }
                if ($item->is_return == 0) {
                    $sale_total_price += $item->total_price;
                    $sale_order_ids[] = $item->id;
                }
                $freight_total_price += $item->freight_price;
                $insert[] = [
                    'settlement_id'=>$id,
                    'shop_order_id'=>$item->id,
                    'shop_order_code'=>$item->order_code,
                    'total_price'=>$item->total_price,
                    'supplier_name'=>$supplier->supplier_name,
                    'shop_name'=>$shop->cname,
                    'delivery_time'=>$item->delivery_time,
                    'receive_time'=>$item->receive_time,
                ];
            });
            if (count($insert) > 0)
            {
                Db::table("yun_settlement_order")->insert($insert);
                Db::table("mf_shop_order")->whereIn("id",$sale_order_ids)->update(['jiesuan'=>$id]);
            }
            $total_price        = $sale_total_price - $return_total_price;
            $service_fee_price  = $total_price * $service_fee_rate;
            $bid_total_price    = $this->shopOrderDao->getBidTotalPriceByOrderIds($sale_order_ids) - $this->shopOrderDao->getBidTotalPriceByOrderIds($return_order_ids);
            $bond_total_price   = $bid_total_price * $bond_rate;

            Db::table("yun_settlement")->where("id",$id)->update([
                'total_price'           =>$total_price,
                'sale_total_price'      =>$sale_total_price,
                'return_total_price'    =>$return_total_price,
                'freight_total_price'   =>$freight_total_price,
                'bid_total_price'       =>$bid_total_price,
                'service_fee_price'     =>$service_fee_price,
                'bond_total_price'      =>$bond_total_price,
            ]);
            if ($session_user_id > 0)
            {
                $res = $this->auditLogService->add([
                    'model'=>'yun_settlement',
                    'model_id'=>$id,
                    'audit_user_id'=>$session_user_id,
                ]);
            }
            if ($res['err_code'] > 0) throw new \Exception($res['msg']);
            Db::commit();
        }catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }
        return arraySuccess("创建成功",['id'=>$id,'total_price'=>$total_price]);
    }

    public function audit($param = [])
    {
        $id                 = $param['id']??0;
        $audit_status       = $param['audit_status']??0;
        $session_admin_name = $param['session_admin_name']??'';
        $session_user_id    = $param['session_user_id']??'';
        $audit_remark       = $param['audit_remark']??'';
        $settlement         = YunSettlement::find($id);

        if (!$settlement)
            return arrayError('记录不存在');

        if ($settlement->audit_status > 0)
            return arrayError("已审核不能重复操作");

        if (!in_array($audit_status,[1,2]))
            return arrayError('参数有误');

        Db::beginTransaction();
        try {
            $res = $this->auditLogService->add(['model'=>'yun_settlement','model_id'=>$id,'audit_status'=>$audit_status,'audit_user_id'=>$session_user_id,'audit_remark'=>$audit_remark]);
            if ($res['err_code'] > 0) throw new \Exception($res['msg']);
            $audit_status = $res['data']['audit_status'];

            if ($audit_status == 1)
            {
                // 更新订单结算状态
                Db::update("UPDATE mf_shop_order a,(SELECT shop_order_id from yun_settlement_order WHERE settlement_id=?) b 
                    SET a.jiesuan=1 WHERE a.id=b.shop_order_id",[$id]);
            }
            Db::commit();
        }catch (QueryException $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }
        return arraySuccess('操作成功');
    }

    public function pay($param = [])
    {
        $id                 = $param['id']??0;
        $paid_price         = $param['paid_price']??0;
        $session_admin_name = $param['session_admin_name']??'';
        $session_user_id    = $param['session_user_id']??0;
        $session_admin_id   = $param['session_admin_id']??0;
        $remarks            = $param['remarks']??'';
        $img_urls           = $param['img_urls']??'';
        $settlement         = YunSettlement::find($id);
        if (!$settlement){
            return arrayError("记录不存在");
        }
        if ($settlement->audit_status == 0){
            return arrayError("未审核不能操作");
        }
        // 负数自动完结
        if ($settlement->total_price <= 0){

            $paid_price = $settlement->total_price;
        }
        if ($settlement->total_price>0 && $paid_price <= 0){
            return arrayError("请收入实收金额");
        }
        if ($settlement->total_price-$settlement->paid_price == 0){
            return arrayError("请已支付完结，不能再次支付");
        }
        if ($settlement->total_price>0 && ($paid_price+$settlement->paid_price) > $settlement->total_price){
            return arrayError("支付金额不能大于应收金额");
        }
        Db::beginTransaction();
        try {

            $settlement->paid_price = $settlement->paid_price+$paid_price;
            $settlement->save();

            $settlementPayLog = new YunSettlementPayLog();
            $settlementPayLog->settlement_id = intval($id);
            $settlementPayLog->paid_price   = $paid_price;
            $settlementPayLog->create_by    = $session_admin_name;
            $settlementPayLog->remarks      = $remarks;
            $settlementPayLog->img_urls      = $img_urls;
            $settlementPayLog->create_time  = date("Y-m-d H:i:s");
            $settlementPayLog->admin_id     = intval($session_admin_id);
            $settlementPayLog->save();

            $add_money = $settlement->total_price - $settlement->bid_total_price - $settlement->service_fee_price - $settlement->freight_total_price;

            // 负数或一次性完结
            if ($paid_price == $settlement->total_price){
                $rate = 1;
            }else{
                $rate = ($add_money/$settlement->total_price);
            }
            $add_money = $paid_price * $rate;

            di(SupplierAccountService::class)->change($settlement->supplier_id,$add_money,13,'结算单收款',$session_admin_id,$session_user_id);

            di(ShopCreditService::class)->change($settlement->supplier_id,$settlement->shop_id,$paid_price,4,'结算调整');

            // 收款完结 更新所有订单已结算
            if ($settlement->paid_price == $settlement->total_price){
                Db::update("UPDATE mf_shop_order a,(SELECT shop_order_id from yun_settlement_order WHERE settlement_id=?) b 
                    SET a.jiesuan=1 WHERE a.id=b.shop_order_id",[$id]);
            }

            Db::commit();
        }
        catch (\Exception $e)
        {
            Db::rollBack();
            return arraySuccess($e->getMessage());
        }

        return arraySuccess("收款成功");
    }

    public function getPayLogList($param = [])
    {
        $settlement_id = $param['settlement_id']??0;
        $map = [];
        if ($settlement_id > 0)
        {
            $map[] = ["settlement_id",'=',$settlement_id];
        }
        $data = Db::table("yun_settlement_pay_log")->where($map);
        $list = $data->orderBy("id","desc")->get();
        return $list;
    }

    /**
     * @param int $profitlossId
     * @param int $admin_id
     * @return array
     */
    public function createByProfitlossId($profitlossId = 0,$admin_id = 0)
    {
        $model      = TbProfitloss::find($profitlossId);
        // 盘亏单创建结算单
        if ($model->profit_total_price > 0) return;
        $supplier   = TbSupplier::findFromCache($model->supplier_id);
        $admin      = MfAdmin::find($admin_id);
        $sale_total_price               = $model->profit_total_price;
        $return_total_price             = 0;
        $freight_total_price            = 0;
        $bond_rate                      = $supplier->bond_rate;
        $service_fee_rate               = $supplier->service_fee_rate;
        $bid_total_price                = $model->profit_total_price;
        Db::beginTransaction();
        try {
            $id = Db::table("yun_settlement")->insertGetId([
                'order_code'            =>'JSD'.UtilsTool::create_order_number(),
                'shop_id'               =>0,
                'supplier_id'           =>$model->supplier_id,
                'supplier_name'         =>$supplier->supplier_name,
                'shop_name'             =>"",
                'start_time'            =>date("Y-m-d 00:00:00"),
                'end_time'              =>date("Y-m-d 23:59:59"),
                'create_time'           =>date("Y-m-d H:i:s"),
                'create_by'             =>$admin->real_name,
                'create_by_admin_id'    =>0,// 系统自动创建
                'total_num'             =>0,
                'service_fee_rate'      =>$supplier->service_fee_rate,
                'bond_rate'             =>$supplier->bond_rate,
                'paid_price'            =>0,
            ]);
            $total_num = 1;
            $insert[] = [
                'settlement_id'=>$id,
                'shop_order_id'=>$model->id,
                'shop_order_code'=>$model->order_code,
                'total_price'=>$model->profit_total_price,
                'supplier_name'=>$supplier->supplier_name,
                'shop_name'=>"云仓",
                'delivery_time'=>$model->create_time,
                'receive_time'=>$model->create_time,
            ];

            if (count($insert) > 0)
            {
                Db::table("yun_settlement_order")->insert($insert);
            }
            $total_price        = $sale_total_price;
            $service_fee_price  = $total_price * $service_fee_rate;
            $bond_total_price   = $bid_total_price * $bond_rate;

            Db::table("yun_settlement")->where("id",$id)->update([
                'total_price'           =>$total_price,
                'sale_total_price'      =>$sale_total_price,
                'return_total_price'    =>$return_total_price,
                'freight_total_price'   =>$freight_total_price,
                'bid_total_price'       =>$bid_total_price,
                'service_fee_price'     =>$service_fee_price,
                'bond_total_price'      =>$bond_total_price,
                'total_num'             =>$total_num,
            ]);
            $res = $this->auditLogService->add([
                'model'=>'yun_settlement',
                'model_id'=>$id,
                'audit_user_id'=>$admin->user_id,
            ]);
            if ($res['err_code'] > 0) throw new \Exception($res['msg']);
            Db::commit();
        }catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }
        return arraySuccess("创建成功");
    }

    /**
     * 每天自动创建结算单，昨天现金支付订单
     */
    public function autoCreate()
    {
        [$start_time,$end_time]         = Time::today();
        $param['session_admin_id']      = 0;
        $param['session_user_id']       = 0;
        $param['delivery_time_start']   = date("Y-m-d 00:00:00",$start_time);
        $param['delivery_time_end']     = date("Y-m-d 23:59:59",$end_time);

        $res = Db::select("SELECT supplier_id,shop_id from mf_shop_order WHERE supplier_id>0 and top_depot_id=3 
                        and `status` in(3,4) and delivery_time>=? and delivery_time<=? and pay_type=5 and jiesuan=0
                        GROUP BY supplier_id,shop_id",[$param['delivery_time_start'],$param['delivery_time_end']]);
        if ($res){
            foreach ($res as $re){
                $param['supplier_id']   = $re->supplier_id;
                $param['shop_id']       = $re->shop_id;
                $arr = $this->create($param);
                if($arr['err_code'] > 0){
                    Logger::init('job')->error($arr['msg']);
                    continue;
                }
                $param['paid_price']    = $arr['data']['total_price'];
                $param['id']            = $arr['data']['id'];
                $pay_res = $this->pay($param);// 自动已收全款
                if ( $pay_res['err_code']>0);
                {
                    Logger::init('job')->error($pay_res['msg']);
                    continue;
                }
            }
        }
    }

}