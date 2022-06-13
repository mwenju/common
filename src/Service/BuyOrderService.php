<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\TbBuyOrder;
use Mwenju\Common\Model\TbBuyOrderProduct;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Service\Dao\BuyOrderDao;
use Mwenju\Common\Service\Dao\BuyOrderProductDao;
use Mwenju\Common\Service\Formatter\BuyOrderFormatter;
use Mwenju\Common\Service\Formatter\BuyOrderProductFormatter;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 采购单
 * Class BuyOrderService
 * @package App\Common\Service
 * @RpcService(name="BuyOrderService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("BuyOrderService","jsonrpc","jsonrpc")]
class BuyOrderService extends BaseService
{

    #[Inject]
    protected AuditLogService $auditLogService;

    #[Inject]
    protected BuyOrderFormatter $formatter;

    #[Inject]
    protected BuyOrderProductFormatter $buyOrderProductFormatter;

    #[Inject]
    protected BuyOrderDao $buyOrderDao;

    #[Inject]
    private BuyOrderProductDao $buyOrderProductDao;

    public function getList($param = [])
    {
        list($page,$limit) = $this->pageFmt($param);
        [$total,$list] = $this->buyOrderDao->getList($param,$page,$limit);
        return ['total'=>$total,'rows'=>$this->formatter->formatList($list)];
    }

    public function getInfo($param = [])
    {
        $item = $this->buyOrderDao->getInfo($param['id']);
        return $this->formatter->base($item);
    }

    public function audit($param = [])
    {
        $id                 = $param['id'] ?? 0;
        $audit_status       = $param['audit_status'] ?? 0;
        $session_user_id    = $param['session_user_id']??0;
        $session_admin_id   = $param['session_admin_id']??0;
        $audit_remark       = $param['audit_remark']??'';
        return $this->auditLogService->add([
            'model'=>'tb_buy_order',
            'model_id'=>$id,
            'audit_status'=>$audit_status,
            'audit_user_id'=>$session_user_id,
            'audit_remark'=>$audit_remark]
        );
    }

    public function getProductList($param = [])
    {
        [$total,$list,$footer] = $this->buyOrderProductDao->getList($param);
        return ['total'=>$total,'rows'=>$this->buyOrderProductFormatter->formatList($list),'footer'=>$footer];
    }

    public function getProductInfo($param = [])
    {
        $info = $this->buyOrderProductDao->getInfo($param);
        if (!$info) return [];
        return $this->buyOrderProductFormatter->base($info);
    }

    public function updateProduct($param = [])
    {
        $product_id = $param['product_id']??0;
        $num        = $param['buy_num']??1;
        $price      = $param['buy_price']??0;
        $product    = TbProduct::find($product_id);
        if (!$product)
            return arrayError("商品不存在");
        $row = TbBuyOrderProduct::where("buy_order_id",0)->where("product_id",$product_id)->first();
        if ($row)
        {
            if ($num <= 0)
            {
                TbBuyOrderProduct::where("id",$row->id)->delete();
            }
            else
            {
                $row->buy_num = $num;
                $row->buy_price = $price;
                $row->total_buy_price = $price*$num;
                $row->save();
            }
            return arraySuccess("更新成功");
        }else
        {
            return $this->addProduct($param);
        }
    }

    public function addProduct($param = [])
    {
        $product_id     = $param['product_id']??0;
        $top_depot_id   = $param['top_depot_id']??0;
        $num            = $param['buy_num']??1;
        $price          = $param['buy_price']??0;
        $product        = TbProduct::find($product_id);
        if (!$product)
            return arrayError("商品不存在");
        $row = TbBuyOrderProduct::where("buy_order_id",0)->where("product_id",$product_id)->first();
        if ($row)
        {
            return arrayError("已添加");

            if ($num <= 0)
            {
                TbBuyOrderProduct::where("id",$row->id)->delete();
            }
            else
            {
                $row->buy_num += $num;
                $row->total_buy_price = $row->buy_price*$row->buy_num;
                $row->save();
            }
            return arraySuccess("更新成功");
        }

//        $supplier = TbSupplier::find($product->supplier_id);
//        $top_depot_id = $supplier->is_new > 1 ? 2:1;

        $price = $price > 0 ? $price : $product->bid_price;
        TbBuyOrderProduct::insert([
            'product_id'=>$product_id,
            'top_depot_id'=>$top_depot_id,
            'buy_num'=>$num,
            'buy_price'=>$price,
            'product_name'=>$product->product_name,
            'supplier_id'=>$product->supplier_id,
            'bar_code'=>$product->bar_code,
            'art_no'=>$product->art_no,
            'product_unit'=>$product->product_unit,
            'total_buy_price'=>$num*$price,
        ]);
        return arraySuccess("添加成功");
    }

    public function clearProduct($param = [])
    {
        $supplier_id = $param['supplier_id']??0;
        TbBuyOrderProduct::where("supplier_id",$supplier_id)->where("buy_order_id",0)->delete();
        return arraySuccess("清除成功");
    }

    public function submit($param = [])
    {
        $session_user_id    = $param['session_user_id']??0;
        $session_admin_id   = $param['session_admin_id']??0;
        $session_user_name  = $param['session_user_name']??"";
        $supplier_id        = $param['supplier_id']??0;
        $top_depot_id       = $param['top_depot_id']??0;
        $remark             = $param['remark']??"";
        if (empty($supplier_id))
            return arrayError("请选择厂商");

        $type_total_num = TbBuyOrderProduct::where("supplier_id",$supplier_id)->where("buy_order_id",0)->count();
        if ($type_total_num == 0)
            return arrayError("请添加采购商品");

        $supplier   = TbSupplier::find($supplier_id);
        $bond_rate  = $supplier->bond_rate??0;

        $total_price        = TbBuyOrderProduct::where("supplier_id",$supplier_id)->where("buy_order_id",0)->sum("total_buy_price");
        $credit_total_price = $total_price*$bond_rate/100;
        $total_num          = TbBuyOrderProduct::where("supplier_id",$supplier_id)->where("buy_order_id",0)->sum("buy_num");

        Db::beginTransaction();
        try
        {
            $id = TbBuyOrder::insertGetId([
                'buy_order_number'=>'CG'.UtilsTool::create_order_number(),
                'supplier_id'=>$supplier_id,
                'supplier_name'=>$supplier->supplier_name,
                'admin_id'=>$session_admin_id,
                'top_depot_id'=>$top_depot_id,
                'total_price'=>$total_price,
                'type_total_num'=>$type_total_num,
                'total_num'=>$total_num,
                'credit_total_price'=>$credit_total_price,
                'bond_rate'=>$bond_rate??0,
                'add_user_id'=>$session_user_id,
                'create_by'=>$session_user_name,
                'remark'=>trim($remark),
                'create_time'=>date("Y-m-d H:i:s"),
            ]);
            TbBuyOrderProduct::where("supplier_id",$supplier_id)->where("buy_order_id",0)->update(
                [
                    'buy_order_id'=>$id,
                    'create_time'=>date("Y-m-d H:i:s"),
                ]
            );
            $res = $this->auditLogService->add([
                'model'=>'tb_buy_order',
                'model_id'=>$id,
                'audit_user_id'=>$session_user_id,
            ]);
            if ($res['err_code'] > 0) throw new \Exception($res['msg']);
            Db::commit();
        }
        catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }
       return arraySuccess("创建成功");
    }

    public function quickAdd($param = [])
    {
        $data = $this->addProduct($param);
        if ($data['err_code'] > 0) return $data;
        return $this->submit($param);
    }

    public function sendOrder($param = [])
    {
        $id = $param['id'] ?? 0;
        $row = TbBuyOrder::find($id);
        if (!$row)
            return arrayError("记录不存在");
        if ($row->audit_status != 1)
            return arrayError("未审核不能发货");
        if ($row->order_status > 0)
            return arrayError("已发货");
        $row->order_status = 1;
        $row->save();
        return arraySuccess("已标记发货");
    }
}