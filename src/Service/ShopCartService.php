<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\ActSeckillProduct;
use Mwenju\Common\Model\MfShopCart;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductStock;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Rpc\ShopCartServiceInterface;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class ShopCartService
{
    public $item = [];
    public $selectItem = [];
    private $user;
    private $shop_id = 0;
    private $depot_id = 1;

    public function setUser(User $user):ShopCartService
    {
        $this->user = $user;
        $this->depot_id = $user->getDepotId();
        $this->shop_id = $user->getShopId();
        return $this;
    }

    public function getShopID()
    {
        return $this->user->getShopId();
    }

    /**
     * @param int $goods_id
     * @param User $user
     * @return TbProduct|TbProduct[]|\Hyperf\Database\Model\Collection|\Hyperf\Database\Model\Model|null
     * @throws \Hyperf\Di\Exception\Exception
     */
    private function getGoodsRow(int $goods_id, User $user)
    {
        $gRow = TbProduct::find($goods_id);

        if(!$gRow)
        {
            UtilsTool::exception('商品信息有误', 100006);
        }
        if($gRow->is_on_sale == 0)
        {
            UtilsTool::exception('商品已下架，不能购买', 100006);
        }
        if($gRow->is_del == 1)
        {
            UtilsTool::exception('商品已下架，不能购买', 100006);
        }
        $salable_num = TbProductStock::where('top_depot_id',$user->getDepotId())->where('product_id',$goods_id)->value('salable_num');

        $act_salable_num = ActService::currentStock($salable_num,$gRow->id,$user->getDepotId());

        $gRow->salable_num = $salable_num>$act_salable_num?$act_salable_num:$salable_num;//最小库存为准

        $gRow->wholesale_price = ProductService::getAreaPrice($gRow->wholesale_price,$gRow->id,$user);

        return $gRow;
    }

    /**
     * 向购物车中添加1个商品
     * @param $goods_id int 商品id
     * @param int $num
     * @param User $user
     * @return array
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function add($goods_id,$num=1,$user = null)
    {
        $num = intval($num);

        if($num <= 0) UtilsTool::exception("请填写商品数量");

        $gRow = $this->getGoodsRow($goods_id,$user);

        $wholesale_price = $gRow->wholesale_price;

        if($gRow['salable_num'] <= 0) UtilsTool::exception("商品库存不足");

        $cart = MfShopCart::where("shop_id",$user->getShopId())
            ->where("product_id",$goods_id)
            ->first();
        if($cart)
        {
            ActService::checkLimit($user->getShopId(),$goods_id,$cart->num + $num);
            if($cart->num + $num > $gRow->salable_num) {
                UtilsTool::exception("库存不足");
            }
            $cart->product_name = $gRow->product_name;
            $cart->product_unit = $gRow->product_unit;
            $cart->shop_price = $wholesale_price;
            $cart->param_list = $gRow->product_param_values_json;
            $cart->image_path = $gRow->list_img_path;
            $cart->num = $cart->num + $num;
            $cart->total_price = $cart->total_price + bcmul($wholesale_price,$num,2);
            $cart->save();
        }else{
            ActService::checkLimit($user->getShopId(),$goods_id,$num);
            $cart = new MfShopCart();
            $cart->product_id = $goods_id;
            $cart->shop_id = $user->getShopId();
            $cart->product_name = $gRow->product_name;
            $cart->product_unit = $gRow->product_unit;
            $cart->shop_price = $wholesale_price;
            $cart->param_list = $gRow->product_param_values_json;
            $cart->image_path = $gRow->list_img_path;
            if($num > $gRow->salable_num) {
                UtilsTool::exception("库存不足");
            }
            $cart->num = $num;
            $cart->total_price = bcmul($num,$wholesale_price,2);
            $cart->create_time = $cart->last_update_time = date("Y-m-d H:i:s");
            $cart->save();
        }
        return $this->totalList($user->getShopId());
    }

    /**
     * 设置数量
     * @param unknown $goods_id
     * @param int $num
     * @param User $user
     * @return array
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function set($goods_id,$num = 0,$user = null)
    {
        $num = intval($num);
        if($num <= 0) {
            $this->del($goods_id,$user->getShopId());
            return $this->totalList($user->getShopId());
        }
        $gRow = $this->getGoodsRow($goods_id,$user);

        $wholesale_price = $gRow->wholesale_price;

        if($gRow['salable_num'] <= 0) {
            UtilsTool::exception("商品库存不足");
        }

        $cart = MfShopCart::where("shop_id",$user->getShopId())
            ->where("product_id",$goods_id)
            ->first();
        if($cart) {
            ActService::checkLimit($user->getShopId(),$goods_id,$num);
            if($num > $gRow->salable_num) {
                UtilsTool::exception("库存不足");
            }
            $cart->num = $num;
            $cart->shop_price = $wholesale_price;
            $cart->total_price = bcmul($num,$wholesale_price,2);
            $cart->save();
        }
        else{
            $this->add($goods_id,$num,$user);
        }
        return $this->totalList($user->getShopId());
    }

    public function selectProductId()
    {
        $selectItem = $this->selectItem;
        if(empty($selectItem))
        {
            return [];
        }
        if($selectItem == 'all')
        {
            return array_keys($this->item);
        }
        return explode(',', $selectItem);
    }

    /**
     * 列出购物车所有的商品
     * @param bool $show_select
     * @param User $user
     * @return array
     */
    public function items($show_select = false,$user = null)
    {
        $this->item = [];
        $items = [];
        $this->img_style = UtilsTool::config_value("IMG_STYLE_LIST_BIG");
        $shop_id = $user->getShopId();
        $map[] = ['a.shop_id','=',$shop_id];
        if($show_select)
        {
            $map[] = ['a.selected','=',1];
        }

        $items = Db::table("mf_shop_cart as a")->selectRaw("a.*,b.wholesale_price")
            ->leftJoin('tb_product as b','a.product_id','=','b.id')
            ->where($map)
            ->orderBy("a.id","desc")
            ->get()
            ->each(function ($item,$index) use ($shop_id){

                $item->total_price = $item->shop_price*$item->num;
                $item->param_list = json_decode($item->param_list,true);
                $item->param_str = '';
                $item->image_path = UtilsTool::img_url($item->image_path,$this->img_style);
                if($item->param_list)
                {
                    foreach ($item->param_list as $p)
                    {
                        if(empty($p['value'])) continue;
                        $item->param_str .= $p['cname'].':'.$p['value'].",";
                    }
                }
                $item->param_str = !empty($item->param_str)?substr($item->param_str,0,-1):'';
                $item->checked = $item->selected>0?true:false;
                $act_info = ActService::currentAct($item->product_id,$item->shop_id);
                if($act_info){
                    $item->current_act = [
                        'limit_day_num'=>$act_info['limit_day_num'],
                        'limit_total_num'=>$act_info['limit_total_num'],
                        'act_name'=>$act_info['act_name'],
                        'salable_num'=>$act_info['salable_num'],
                    ];
                }
            })->toArray();
        if(!$items){
            return [];
        }
        $redis = redis();
        foreach ($items as $v)
        {
            $product_ids[] = $v->product_id;
            $act_salable_num = $redis->get("CURRENT_STOCK_{$user->getDepotId()}_".$v->product_id);
            if($act_salable_num)
            {
                $act_salable_nums[$v->product_id] = $act_salable_num;
            }
        }

        $is_on_sale = TbProduct::whereIn('id',$product_ids)->pluck('is_on_sale','id')->toArray();
        $salable_num = TbProductStock::where('top_depot_id',$user->getDepotId())
            ->whereIn('product_id',$product_ids)->pluck('salable_num','product_id')->toArray();

        foreach ($items as $k=>$item)
        {
            $item->salable_num = isset($salable_num[$item->product_id])?$salable_num[$item->product_id]:0;
            $item->salable_num = isset($act_salable_nums[$item->product_id])?$act_salable_nums[$item->product_id]:$item->salable_num;
            $item->salable_num = $item->salable_num < 0 ? 0 : $item->salable_num;
            $item->is_on_sale = isset($is_on_sale[$item->product_id])?$is_on_sale[$item->product_id]:0;
            $this->item[$item->product_id] = $item;
        }
        return $this->item;
    }

    /**
     * @param User $user
     * @return array
     */
    public function getSelectProductIds($user = null)
    {
        $product_ids = [];
        $product_ids = Db::table("mf_shop_cart")->where("shop_id",$user->getShopId())
            ->where("selected",1)
            ->pluck("product_id")->toArray();

        return $product_ids;
    }

    /**
     * @param array $SelectProductIds
     * @param User $user
     * @return \Hyperf\Utils\Collection
     */
    public function getStockNum($SelectProductIds = [],$user = null)
    {
        $SelectProductIds = !empty($SelectProductIds)?$SelectProductIds:$this->getSelectProductIds($user);
        $redis = redis();
        $proStocks =  TbProductStock::where('top_depot_id',$user->getDepotId())
            ->whereIn('product_id',$SelectProductIds)
            ->pluck('salable_num','product_id');
        foreach ($proStocks as $product_id=>$stock)
        {
            if($redis->get("CURRENT_ACT_ING_".$product_id) > 0)
            {
                $salable_num = $redis->get("CURRENT_STOCK_{$this->depot_id}_".$product_id);
                $proStocks[$product_id] = $salable_num;
            }
        }
        return $proStocks;
    }

    public function getProductName($shop_id = 0)
    {
        return $cart_store_num = MfShopCart::where("shop_id",$shop_id)
            ->where("selected",1)
            ->pluck("product_name",'product_id');
    }

    /**
     * 监测库存
     * @param User $user
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function checkStock($user = null)
    {
        $selectProductIds = $this->getSelectProductIds($user);
        $xiajia_list = TbProduct::whereIn('id',$selectProductIds)->where("is_on_sale",0)->get();
        if(!$xiajia_list->isEmpty())
        {
            $product_names = '';
            foreach ($xiajia_list as $v)
            {
                $product_names .= $v->product_name."，";
            }
            UtilsTool::exception($product_names."已下架",303);
        }
        $fact_store_num = $this->getStockNum($selectProductIds,$user);
        $cart_store_num = MfShopCart::where("shop_id",$user->getShopId())
            ->whereIn("product_id",$selectProductIds)
            ->pluck("num",'product_id');

        $product_names = $this->getProductName($user->getShopId());
        foreach ($selectProductIds as $product_id)
        {
            if($cart_store_num[$product_id] > $fact_store_num[$product_id])
            {
                UtilsTool::exception($product_names[$product_id]."库存不足",303);
            }
        }
        $this->checkPrice($selectProductIds,$user);
    }

    /**
     * 价格变化,自动更新购物车
     * @param array $selectProductIds
     * @param User $user
     */
    public function checkPrice($selectProductIds = [],$user = null)
    {
        Db::table("mf_shop_cart as a")->selectRaw("a.*,b.wholesale_price")
            ->leftJoin('tb_product as b','a.product_id','=','b.id')
            ->where("a.shop_id",$user->getShopId())
            ->whereIn("a.product_id",$selectProductIds)
            ->get()->each(function ($item,$index) use ($user){
                $curPrice = ProductService::getAreaPrice($item->wholesale_price,$item->product_id,$user);
                if($curPrice != $item->shop_price){
                    MfShopCart::where("id",$item->id)->update([
                        'shop_price'=>$curPrice,
                        'total_price'=>$item->num * $curPrice
                    ]);
                }
                ActService::checkLimit($user->getShopId(),$item->product_id,$item->num);
            });

    }

    /**
     * 是否已全选
     * @return bool
     */
    public function isCheckAll($shop_id = 0)
    {
        return MfShopCart::where("shop_id",$shop_id)
                ->where("selected",0)
                ->count() == 0;
    }

    public function del($goods_id,$shop_id = 0)
    {
        MfShopCart::where("shop_id",$shop_id)
            ->where("product_id",$goods_id)
            ->delete();
        return $this->totalList($shop_id);
    }

    /**
     * 返回购物车有几种商品
     * @return int
     */
    public function totalType($shop_id = 0)
    {
        return MfShopCart::where("shop_id",$shop_id)
            ->where("selected",1)
            ->count();
    }

    /**
     * 返回购物车中商品的个数
     * @return int
     */
    public function totalNum($is_selected = false,$shop_id = 0)
    {
        $map = [];
        if($is_selected)
        {
            $map[] = ['selected','=',1];
        }
        return MfShopCart::where("shop_id",$shop_id)
            ->where($map)
            ->sum("num");
    }
    /**
     * 返回购物车中商品的总价格
     * @return float
     */
    public function totalPrice($shop_id = 0)
    {
        $total_price = Db::table("mf_shop_cart")->where("shop_id",$shop_id)
            ->where("selected",1)
            ->sum("total_price");
        return round($total_price,2);
    }

    public function totalList($shop_id = 0)
    {
        $all_total_num      = 0; // 所有商品数总和
        $total_num          = 0; // 选中商品数总和
        $all_total_type_num = 0; // 所有商品品类数
        $total_type_num     = 0; // 选中商品品类数
        $total_price        = 0; // 选中商品总金额
        $all_total_price    = 0; // 所有商品总金额
        $is_check_all       = true;
        $list = Db::table("mf_shop_cart")->where("shop_id",$shop_id)->get();
        if($list)
        {
            foreach ($list as $v)
            {
                $all_total_type_num ++;
                $all_total_num += $v->num;
                $all_total_price = bcadd($all_total_price,$v->total_price,4);
                if($v->selected == 1) {
                    $total_type_num ++;
                    $total_num += $v->num;
                    $total_price = bcadd($total_price,$v->total_price,4);
                }
                if($v->selected == 0)
                {
                    $is_check_all = false;
                }
            }
        }
        $total_price = round($total_price,2);
        $all_total_price = round($all_total_price,2);
        return [
            'all_total_num'=>$all_total_num,
            'all_total_type_num'=>$all_total_type_num,
            'total_num'=>$total_num,
            'total_type_num'=>$total_type_num,
            'all_total_price'=>$all_total_price,
            'total_price'=>$total_price,
            'is_check_all'=>$is_check_all,
            'notice'=>$this->cartNotice($total_price)
        ];
    }

    /**
     * 清空购物车* @return void
     */
    public function clear($shop_id = 0)
    {
        MfShopCart::where("shop_id",$shop_id)->delete();
        $this->item = [];
    }

    public function dohSelect($id = 0,$shop_id = 0)
    {
        $cartInfo = MfShopCart::where("id",$id)->where("shop_id",$shop_id)->first();
        if(!$cartInfo) UtilsTool::exception("记录不存在");
        $cartInfo->selected = $cartInfo->selected>0?0:1;
        $cartInfo->save();

        return $this->totalList($shop_id);
    }

    public function dohSelectAll($checked = 0,$shop_id = 0)
    {
        MfShopCart::where("shop_id",$shop_id)->update([
            'selected'=>$checked>0?1:0
        ]);
        return $this->totalList($shop_id);
    }
    private function cartNotice($total_price = 0)
    {
        $FREIGHT_LIMIT = UtilsTool::config_value('FREIGHT_LIMIT');
        if($total_price >= $FREIGHT_LIMIT)
        {
            $notice = "已享满{$FREIGHT_LIMIT}元享受运费补贴";
        }
        else
        {
            $sub_price = round($FREIGHT_LIMIT-$total_price,2);
            $notice = "还差{$sub_price}元享受运费补贴(安徽10元/件，省外25元/件)";
        }
        return $notice;
    }

    /**
     * @param User $user
     * @return array
     */
    public function cartNumMap($user = null)
    {
        $aList = ActSeckillProduct::where("end_time",'>=',date("Y-m-d H:i:s"))
            ->pluck("product_id")->toArray();
        $cList = MfShopCart::where("shop_id",$user->getShopId())->pluck("num",'product_id');
        $pids = [];
        foreach ($cList as $pid=>$item) {
            $pids[] = $pid;
        }
        $pids = array_unique(array_merge($aList,$pids));
        $list = [];
        foreach ($pids as $pid){
            $list[$pid] = [
                'cart_num'=>isset($cList[$pid])?$cList[$pid]:0,
                'act_list'=>ActService::currentActList($pid,$user)
            ];
        }

        return $list;
    }

    /**
     * @param User $user
     * @return array
     */
    public function alertCartProduct($user = null)
    {
        $selectProductIds = $this->getSelectProductIds($user);
        $list = Db::table("mf_shop_cart as a")->selectRaw("a.*,b.is_on_sale")
            ->leftJoin("tb_product as b",'b.id','=','a.product_id')
            ->where("a.shop_id",$user->getShopId())
            ->whereIn("a.product_id",$selectProductIds)
            ->get();
        $fact_store_num = $this->getStockNum($selectProductIds,$user);
        $plist = [];
        $this->img_style = UtilsTool::config_value("IMG_STYLE_LIST_BIG");
        foreach ($list as $v)
        {
            $v->image_path = UtilsTool::img_url($v->image_path,$this->img_style);
            $v->salable_num = $fact_store_num[$v->product_id];
            unset($v->param_list);
            if($v->is_on_sale == 0){
                $v->tag = '下架';
                $plist[] = $v;
            }elseif($fact_store_num[$v->product_id] <= 0){
                $v->tag = '售罄';
                $plist[] = $v;
            }elseif($v->num > $fact_store_num[$v->product_id]){
                $v->tag = '库存不足';
                $plist[] = $v;
            }
        }
        return $plist;
    }

    /**
     * 取消选中所有异常商品接口
     * @param User $user
     */
    public function cancelAlertProduct($user = null)
    {
        $plist =$this->alertCartProduct($user);
        if(count($plist) > 0){
            foreach ($plist as $p){
                $ids[] = $p->id;
            }
            Db::table("mf_shop_cart")->whereIn("id",$ids)->update(['selected'=>0]);
        }
        return Db::table("mf_shop_cart")->where("selected",1)->where('shop_id',$user->getShopId())->count();
    }

    public function push($from_shop_id = 0,$to_shop_id = 0)
    {
        $sql = "INSERT INTO `mwjdb`.`mf_shop_cart` (`shop_id`,`from_shop_id`, `product_id`, `num`, `shop_price`, `total_price`, `image_path`, `product_name`, `product_unit`, `param_list`, `create_time`, `last_update_time`) 
                SELECT {$to_shop_id} `shop_id`,{$from_shop_id} `from_shop_id`,`product_id`, `num`, `shop_price`, `total_price`, `image_path`, `product_name`, `product_unit`, `param_list`, `create_time`, `last_update_time` from mf_shop_cart a 
                WHERE a.shop_id={$from_shop_id} AND a.product_id NOT in(
                    SELECT product_id from mf_shop_cart b where b.shop_id={$to_shop_id}
                )";
        Db::insert($sql);
    }

}