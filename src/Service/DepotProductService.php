<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfDepot;
use Mwenju\Common\Model\MfDepotProduct;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductStock;
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Db;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 结算单
 * Class DepotProductService
 * @package App\Common\Service
 * @RpcService(name="DepotProductService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("DepotProductService","jsonrpc","jsonrpc")]
class DepotProductService extends BaseService
{
    public function bindProduct($product_id = 0,$depot_id = 0,$warn_num = 0)
    {
        $depotModel = MfDepot::find($depot_id);
        if (!$depotModel)
            return arrayError("货位信息有误");
        if ($depotModel->is_seat != 1)
            return arrayError("清选择货位");

        $product = TbProduct::find($product_id);
        $top_depot_id = $depotModel->top_depot_id;

        if ($product->supplier->is_new == 2){
            if ($top_depot_id != 3)
                return arrayError("云仓商品清选择云仓货位");
        }
        $depotProduct = MfDepotProduct::where("top_depot_id",$top_depot_id)
            ->where("is_delete",0)
            ->where("product_id",$product_id)
            ->first();
        $is_add = true;
        Db::beginTransaction();
        try {
            if ($depotProduct)
            {
                if (intval($depot_id) == $depotProduct->depot_id)
                {
                    $depotProduct->depot_id = $depot_id;
                    $is_add = false;
                }
                else
                {
                    $depotProduct->is_delete = 1;
                    $store_num      = $depotProduct->store_num;
                    $lock_num       = $depotProduct->lock_num;
                    $now_bid_price  = $depotProduct->now_bid_price;
                }
                $depotProduct->save();
            }
            else
            {
                $store_num      = 0;
                $lock_num       = 0;
                $now_bid_price  = $product->bid_price;
            }
            if ($is_add)
            {
                $depotProductModel = new MfDepotProduct();
                $depotProductModel->product_id      = $product_id;
                $depotProductModel->depot_id        = $depot_id;
                $depotProductModel->top_depot_id    = $top_depot_id;
                $depotProductModel->store_num       = $store_num;
                $depotProductModel->lock_num        = $lock_num;
                $depotProductModel->now_bid_price   = $now_bid_price;
                $depotProductModel->save();
            }

            $productStockModel = TbProductStock::where('product_id',$product_id)->where('top_depot_id',$top_depot_id)->first();
            if ($productStockModel){
                $productStockModel->warn_num = $warn_num;
            }else{
                $productStockModel = new TbProductStock();
                $productStockModel->product_id = $product_id;
                $productStockModel->top_depot_id = $top_depot_id;
                $productStockModel->warn_num = $warn_num;
            }
            $productStockModel->save();
            Db::commit();
        }
        catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }

//        $productStockModel->save();
//        $ps = TbProductStock::where('product_id',product_id)->where("top_depot_id",$top_depot_id)->first();
//        if(!$ps)
//        {
//            Db::table("tb_product_stock")->insert([
//                'product_id'=>$product_id,
//                'top_depot_id'=>$top_depot_id,
//                'warn_num'=>$warn_num,
//            ]);
//        }
        return arraySuccess("绑定成功");
    }

}