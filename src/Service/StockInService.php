<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfDepotProduct;
use Mwenju\Common\Model\TbBuyOrder;
use Mwenju\Common\Model\TbBuyOrderProduct;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbStockInOrder;
use Mwenju\Common\Model\TbStockInOrderProduct;
use Mwenju\Common\Model\TbStockTakeOrder;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\Database\Exception\QueryException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Exception\Exception;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class SearchService
 * @RpcService(name="StockInService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("StockInService","jsonrpc","jsonrpc")]
class StockInService
{
    /**
     * @var User
     */
    private $user;
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * 新扫码入库
     * @param array $param
     * @return array
     */
    public function addProduct($param = [])
    {
        $buy_order_id   = $param['buy_order_id']??0;
        $product_id     = $param['product_id']??0;
        $num            = $param['num']??0;
        $bid_price      = $param['bid_price']??0;
        $user_id        = $param['user_id']??0;
        $admin_id       = $param['admin_id']??0;

        if (empty($buy_order_id))
            return arrayError("缺少参数：buy_order_id");

        if (empty($product_id))
            return arrayError("缺少参数：product_id");

        if (empty($num))
            return arrayError("缺少参数：num");

        $buy_order = TbBuyOrder::find($buy_order_id);
        if (!$buy_order)
            return arrayError("订单记录不存在");

        $buy_order_product = TbBuyOrderProduct::where("buy_order_id",$buy_order_id)->where("product_id",$product_id)->first();
        if (!$buy_order_product)
            return arrayError("未匹配到进货记录哦");

        if ($num + $buy_order_product->receive_num > $buy_order_product->buy_num)
            return arrayError("签收数不能大于进货数");

        $depot = MfDepotProduct::where('top_depot_id', $buy_order->top_depot_id)
            ->where("is_delete", 0)
            ->where('product_id', $product_id)->first();

        if (!$depot) {
            return arrayError($product_id . '还未分配库位');
        }

        Db::beginTransaction();
        try {
            TbStockInOrderProduct::insert([
                'product_id' => $product_id,
                'buy_order_id' => $buy_order_id,
                'top_depot_id' => $buy_order->top_depot_id,
                'supplier_id' => $buy_order->supplier_id,
                'depot_id' => $depot->depot_id,
                'admin_id' => $admin_id,
                'user_id' => $user_id,
                'num' => $num,
                'bid_price' => $bid_price,
                'bid_total_price' => $bid_price*$num,
                'create_time'=>date("Y-m-d H:i:s")
            ]);

            $buy_order_product->receive_num += $num;
            $buy_order_product->save();
            Db::commit();
        }
        catch (QueryException $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage().$e->getSql());
        }
        return arraySuccess("添加成功");
    }

    /**
     * 扫码添加
     * @param int $product_id
     * @param int $num
     * @param int $bid_price
     * @throws Exception
     */
    public function add($product_id = 0, $num = 0, $bid_price = 0)
    {
        if ($bid_price <= 0)
            UtilsTool::exception("进货价不能为空");
        $depot_id = $this->user->getDepotId();
        $supplier_id = TbProduct::where('id', $product_id)->value('supplier_id');
        if (!$supplier_id)
            UtilsTool::exception('商品不存在');

        // 是否存在其他未提交的入库单申请
        $ishave_other = TbStockInOrderProduct::where('supplier_id', '<>', $supplier_id)
            ->where('top_depot_id', $depot_id)
            ->where('stock_in_order_id', 0)
            ->first();

        if ($ishave_other)
            UtilsTool::exception('其他入库申请未完成，不能继续新的操作');

        $depot = MfDepotProduct::where('top_depot_id', $depot_id)
            ->where("is_delete", 0)
            ->where('product_id', $product_id)->first();
        if (!$depot) {
            throw new Exception($product_id . '还未分配库位');
        }

        $siop = TbStockInOrderProduct::where('product_id', $product_id)
            ->where('top_depot_id', $depot_id)
            ->where('stock_in_order_id', 0)
            ->first();
        // 已存在则更新，否则创建
        if ($siop) {
            if($num <= 0) {
                TbStockInOrderProduct::where("id",$siop->id)->delete();
                Logger::init()->error('数量0移出入库单,product_id:'.$product_id);
                return;
            }
            $siop->num = $num;
            $siop->bid_price = $bid_price;
            $siop->bid_total_price = $num*$bid_price;
            $siop->save();
        } else {
            if($num <= 0) {
                Logger::init()->error('数量0不添加入库单,product_id:'.$product_id);
                return;
            }
            TbStockInOrderProduct::insert([
                'product_id' => $product_id,
                'top_depot_id' => $depot_id,
                'supplier_id' => $supplier_id,
                'depot_id' => $depot->depot_id,
                'admin_id' => $this->user->getAdminId(),
                'user_id' => $this->user->getUserId(),
                'num' => $num,
                'bid_price' => $bid_price,
                'bid_total_price' => $bid_price*$num,
                'create_time'=>date("Y-m-d H:i:s")
            ]);
        }

    }

    /**
     * 提交入库单申请
     */
    public function submit($param = [])
    {
        $img_urls           = $param['imgs']??"";
        $remark             = $param['remark']??"";
        $buy_order_id       = $param['buy_order_id']??0;
        $user_id            = $param['user_id']??0;
        $admin_id           = $param['admin_id']??0;
        $create_by          = $param['create_by']??'';
        $is_end             = $param['is_end']??0;
        $input_total_price  = $param['input_total_price']??0;

        if (empty($buy_order_id))
            return arrayError("进货单ID必传");

        $buy_order      = TbBuyOrder::find($buy_order_id);

        if (!$buy_order)
            return arrayError("订单记录不存在");

        $buy_order_product_count = TbStockInOrderProduct::where("buy_order_id",$buy_order_id)->where("stock_in_order_id",0)->count();
        if ($buy_order_product_count == 0)
            return arrayError("您还未添加商品哦");

        $row = Db::select("SELECT sum(buy_num) buy_num,sum(receive_num) receive_num FROM tb_buy_order_product WHERE buy_order_id=?",[$buy_order_id]);
        if ($row[0]->buy_num == $row[0]->receive_num)
        {
            $buy_order->order_status = 3;
        }
        else
        {
            $buy_order->order_status = $is_end>0?3:2;
        }

        Db::beginTransaction();
        try
        {
            $depot_id = $buy_order->top_depot_id;
            $buy_order->save();
            $type_total_num = Db::table("tb_stock_in_order_product")->where('buy_order_id',$buy_order_id)
                ->where('stock_in_order_id',0)
                ->where('top_depot_id',$depot_id)
                ->count();

            $total_num = Db::table("tb_stock_in_order_product")->where('buy_order_id',$buy_order_id)
                ->where('stock_in_order_id',0)
                ->where('top_depot_id',$depot_id)
                ->sum('num');

            $total_price = Db::table("tb_stock_in_order_product")->where('buy_order_id',$buy_order_id)
                ->where('stock_in_order_id',0)
                ->where('top_depot_id',$depot_id)
                ->sum('bid_total_price');

            $order_id = Db::table("tb_stock_in_order")->insertGetId([
                'buy_order_id' => $buy_order_id,
                'top_depot_id' => $depot_id,
                'admin_id' => $admin_id,
                'user_id' => $user_id,
                'supplier_id'=>$buy_order->supplier_id,
                'order_code'=>'SI'.UtilsTool::create_order_number($admin_id),
                'total_price'=>$total_price,
                'input_total_price'=>$input_total_price,
                'total_num'=>$total_num,
                'create_time'=>date("Y-m-d H:i:s"),
                'type_total_num'=>$type_total_num,
                'img_urls'=>$img_urls,
                'remark'=>$remark,
                'create_by'=>$create_by,
            ]);

            Db::table("tb_stock_in_order_product")->where('buy_order_id',$buy_order_id)
                ->where('stock_in_order_id',0)
                ->where('top_depot_id',$depot_id)
                ->update(['stock_in_order_id'=>$order_id]);
           Db::commit();
        }
        catch (QueryException $e)
        {
           Db::rollBack();
           Logger::init('sql')->error($e->getSql().$e->getSql());
           return arrayError($e->getMessage().$e->getSql());
        }
        return arraySuccess("提交成功");
    }

    /**
     * 进货单审核
     * @param array $param
     */
    public function audit($param = [])
    {
        $id             = $param['id']??0;
        $audit_status   = $param['audit_status']??0;
        $user_id        = $param['user_id']??0;
        $admin_id       = $param['admin_id']??0;
        $audit_by       = $param['audit_by']??"";

        $stock_in_order = TbStockInOrder::find($id);
        if ($stock_in_order->audit_status > 0)
            return arrayError("已审核不能重复操作");

        if ($audit_status == 1)
        {
            // 更新仓库库存
            $sql = "UPDATE tb_product_stock a,tb_stock_in_order_product b SET a.stock_num=a.stock_num + b.num,a.last_bid_price=b.bid_price
                    WHERE a.product_id=b.product_id and a.top_depot_id=b.top_depot_id and b.stock_in_order_id=?";
            Db::update($sql,[$id]);
            // 更新库位库存
            $sql = "UPDATE mf_depot_product a,tb_stock_in_order_product b SET a.store_num=a.store_num + b.num
                    WHERE a.product_id=b.product_id and a.top_depot_id=b.top_depot_id AND b.stock_in_order_id=?";
            Db::update($sql,[$id]);
            // 插入日志
            $sql = "INSERT INTO tb_product_stock_log (product_id,bid_price,add_num,do_type,do_type_id,remark,admin_id,user_id,supplier_id,top_depot_id,create_time)
                    SELECT product_id,bid_price,num,IF(b.is_out>0,6,1) do_type,b.id do_type_id,IF(b.is_out>0,'厂家退货','入库更新') remark,
                    {$admin_id} admin_id,{$user_id} user_id,a.supplier_id,a.top_depot_id,now()
                    from tb_stock_in_order_product a LEFT JOIN tb_stock_in_order b on a.stock_in_order_id=b.id
                    WHERE a.stock_in_order_id=?";
            Db::update($sql,[$id]);
        }
        else
        {
            if ($stock_in_order->is_out > 0)
            {
                $source_order_id = $stock_in_order->source_order_id;
                TbStockInOrderProduct::where("stock_in_order_id",$id)->get()->each(function ($item,$index) use ($source_order_id){
                    Db::table("tb_stock_in_order_product")->where("stock_in_order_id",$source_order_id)
                        ->where("product_id",$item->product_id)
                        ->decrement("return_num",abs($item->num));
                });
            }
            else
            {
                $sql = "UPDATE tb_buy_order_product a,tb_stock_in_order_product b SET a.receive_num=a.receive_num - b.num
                        WHERE a.product_id=b.product_id and a.top_depot_id=b.top_depot_id AND a.buy_order_id=b.buy_order_id
                        and b.stock_in_order_id=?;";
                Db::update($sql,[$id]);
            }
        }
        $stock_in_order->audit_admin_id = $admin_id;
        $stock_in_order->audit_time = date("Y-m-d H:i:s");
        $stock_in_order->audit_status = $audit_status;
        $stock_in_order->audit_by = $audit_by;
        $stock_in_order->save();
        return arraySuccess("审核成功");
    }
}