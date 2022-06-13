<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Model\MfShopOrderProduct;
use Mwenju\Common\Model\MfShopProduct;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class ShopProductService
 * @package App\Common\Service
 * @RpcService(name="ShopProductService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("ShopProductService","jsonrpc","jsonrpc")]
class ShopProductService
{
    public static function getList($shop_id = 0)
    {
        $sql = "SELECT `b`.id `product_id`,IFNULL(a.salable_num,0) stock_num,`b`.`product_name`,`b`.`list_img_path`,
            TRUNCATE(`b`.`cc_integrate_num`/10,2) market_price,b.cc_integrate_num integrate_num,`b`.`bar_code`,`b`.`art_no` FROM `tb_product` `b` 
            LEFT JOIN `mf_shop_product` `a` ON `b`.`id`=`a`.`product_id` and `a`.`shop_id` = ? 
            WHERE b.is_integrate=1 and b.is_on_sale=1 and b.is_del=0 ORDER BY stock_num desc, b.on_sale_time desc";
        $res = Db::select($sql,[$shop_id]);
        foreach ($res as $k=>$v){
            $res[$k]->list_img_path = UtilsTool::img_url($v->list_img_path,'listh');
        }
        return $res;
    }

    public static function getInfo($shop_id = 0,$product_id = 0)
    {
        return Db::table("mf_shop_product as a")->selectRaw("a.salable_num,b.product_name,b.list_img_path,b.market_price,
            b.cc_integrate_num integrate_num,b.bar_code,b.art_no")
            ->leftJoin('tb_product as b','b.id','=','a.product_id')
            ->where("a.shop_id",$shop_id)
            ->where("a.product_id",$product_id)
            ->first();
    }

    public function find($param = [])
    {
        $keyword        = $param['keyword']??"";
        $shop_id        = $param['shop_id']??0;
        $supplier_id    = $param['supplier_id']??0;
        $top_depot_id   = $param['top_depot_id']??0;
        $page           = $param['page']??1;
        $rows           = $param['rows']??10;
        $map = [];
        if ($shop_id > 0){
            $map[] = ['a.shop_id','=',$shop_id];
        }
        if ($supplier_id > 0){
            $map[] = ['b.supplier_id','=',$supplier_id];
        }
        if ($top_depot_id > 0){
            $map[] = ['a.from_top_depot_id','=',$top_depot_id];
        }
        $query = Db::table("mf_shop_product as a")->selectRaw("a.*,b.product_name,b.list_img_path,b.bar_code,b.art_no")
            ->leftJoin('tb_product as b','b.id','=','a.product_id')
            ->where($map);
        if (!empty($keyword)){
            $query->where(function (Builder $query) use ($keyword){
                return $query->where("b.product_name",'like',"%{$keyword}%")
                    ->orWhere("b.bar_code",'like',"%{$keyword}%")
                    ->orWhere("b.art_no",'like',"%{$keyword}%");
            });
        }
        $total  = $query->count();
        $list   = $query->orderBy("a.id","desc")->forPage($page,$rows)->get();
        return ['total'=>$total,'rows'=>$list];
    }

    public function receiveByOrderId($order_id = 0)
    {
        $order_info = MfShopOrder::find($order_id);
        if($order_info->status != 3)
        {
            return arrayError("订单状态有误");
        }
        // 积分订单记录库存，皖新仓订单全部记录库存
        if($order_info->paid_integrate > 0 || $order_info->top_depot_id == 3)
        {
            MfShopOrderProduct::where("order_id",$order_id)->get()->each(function ($item,$index) use ($order_info){

                if (MfShopProduct::where("shop_id",$order_info->shop_id)->where("product_id",$item->product_id)->count() > 0)
                {
                    MfShopProduct::where("shop_id",$order_info->shop_id)
                        ->where("product_id",$item->product_id)
                        ->where("from_top_depot_id",$order_info->top_depot_id)
                        ->increment('salable_num',$item->num);
                }else{
                    MfShopProduct::insert([
                        'shop_id'=>$order_info->shop_id,
                        'product_id'=>$item->product_id,
                        'salable_num'=>$item->num,
                        'from_top_depot_id'=>$order_info->top_depot_id,
                    ]);
                }
            });
        }

    }
}