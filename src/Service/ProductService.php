<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfBrand;
use Mwenju\Common\Model\MfDepot;
use Mwenju\Common\Model\MfDepotProduct;
use Mwenju\Common\Model\MfSearchSw;
use Mwenju\Common\Model\MfSearchSwHistory;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductImg;
use Mwenju\Common\Model\TbProductParamValue;
use Mwenju\Common\Model\TbProductStock;
use Mwenju\Common\Model\TbProductType;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Service\Dao\ProductDao;
use Mwenju\Common\Service\Dao\ProductGroupDao;
use Mwenju\Common\Service\Dao\ProductImgsDao;
use Mwenju\Common\Service\Dao\ProductPriceDao;
use Mwenju\Common\Service\Dao\ProductStockDao;
use Mwenju\Common\Service\Formatter\ProductFormatter;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\RedisTool;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;
use JetBrains\PhpStorm\Immutable;

/**
 * 商品服务
 * Class ProductService
 * @package App\Common\Service
 * @RpcService(name="ProductService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("ProductService","jsonrpc","jsonrpc")]
class ProductService extends BaseService
{

    #[Inject]
    private ProductDao $productDao;

    #[Inject]
    private ProductStockDao $productStockDao;

    #[Inject]
    private ProductFormatter $formatter;

    #[Inject]
    private ProductImgsDao $productImgsDao;

    #[Inject]
    private ProductPriceDao $productPriceDao;

    #[Inject]
    private ProductGroupDao $productGroupDao;

    public function getList($param = [])
    {
        $top_depot_id = $param['top_depot_id']??0;
        list($page,$limit) = $this->pageFmt($param);
        [$total,$list] =  $this->productDao->getList($param,$page,$limit);
        $productIds = [];
        foreach ($list as $item)
        {
            $productIds[] = $item->id;
        }
        $productStocks = $this->productStockDao->getStockNumByProductIds($productIds,$top_depot_id);
        foreach ($productStocks as $item)
        {
            $stocks[$item->product_id] = ['stock_num'=>$item->stock_num,'lock_num'=>$item->lock_num,'warn_num'=>$item->warn_num];
        }

        $productDepots = MfDepotProduct::whereIn("product_id",$productIds)->where("top_depot_id",$top_depot_id)->get();
        foreach ($productDepots as $item)
        {
            $depots[$item->product_id] = [
                "store_num"=>$item->store_num,
                "depot_id"=>$item->depot_id,
                "last_bid_price"=>$item->last_bid_price,
                'depot_name'=>MfDepot::findFromCache($item->depot_id)->cname
            ];
        }

        foreach ($list as $k=>$item)
        {
            $list[$k]->stock_num        = $stocks[$item->id]['stock_num']??0;
            $list[$k]->lock_num         = $stocks[$item->id]['lock_num']??0;
            $list[$k]->warn_num         = $stocks[$item->id]['warn_num']??0;
            $list[$k]->depot_name       = $depots[$item->id]['depot_name']??"";
            $list[$k]->depot_id         = $depots[$item->id]['depot_id']??0;
            $list[$k]->last_bid_price   = $depots[$item->id]['last_bid_price']??0;
            $list[$k]->top_depot_id     = $top_depot_id;
        }

        return ['total'=>$total,'rows'=>$this->formatter->formatList($list)];
    }

    public function updateOrInsert(array $param)
    {
        Db::beginTransaction();
        try {

            $model = $this->productDao->updateOrInsert($param);
            $this->productImgsDao->updateOrInsert($param,$model->id);
            $this->productPriceDao->updateOrInsert($param,$model->id);
            $this->productGroupDao->updateOrInsert($param,$model->id);
            self::updateParam($model);
            $res = di(DepotProductService::class)->bindProduct($model->id,$param['depot_id']??0,$param['warn_num']??0);
            if ($res['err_code'] > 0) throw new \Exception($res['msg']);
            Db::commit();

        }catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }
        return arraySuccess("提交成功");
    }

    public static function getChild($id = 0,$chids = [])
    {
        $id = !is_array($id)?[$id]:$id;
        $ids = TbProductType::whereIn('parent_id',$id)->pluck('id');
        if(count($ids) >0)
        {
            $chids[] = $ids;
            return self::getChild($ids,$chids);
        }
        else
        {
            $chids = $id;
        }
        return $chids;
    }

    /**
     * 获取商品规格
     * @param string $product_param_values_json
     * @return string
     */
    public static function getParamStr($product_param_values_json = '')
    {
        $str = '';
        if(!empty($product_param_values_json))
        {
            $arr = json_decode($product_param_values_json,true);
            if($arr){
                foreach ($arr as $item) {
                    if(!isset($item['cname'])) continue;
                    if(empty($item['value'])) continue;
                    $str .= $item['cname'].":".$item['value'].",";
                }
            }
        }
        if(!empty($str)) $str = substr($str,0,-1);
        return $str;
    }

    public static function getTypeName($type_id = 0,$glue=",")
    {
        $redis = RedisTool::init();
        $namestr = $redis->get("PRODUCT_TYPE_NAME_".$type_id);
        if($namestr)
        {
            $name = json_decode($namestr,true);
            return implode($glue,$name);
        }
        $info = TbProductType::where("id",$type_id)->first();
        $name[] = isset($info->cname)?$info->cname:'';
        if(isset($info->parent_id))
        {
            $info2 = TbProductType::where("id",$info->parent_id)->first();
            $name[] = isset($info2->cname)?$info2->cname:'';
        }
        if(isset($info2->parent_id))
        {
            $info3 = TbProductType::where("id",$info2->parent_id)->first();
            $name[] = isset($info3->cname)?$info3->cname:'';
        }
        rsort($name);
        $redis->set("PRODUCT_TYPE_NAME_".$type_id,json_encode($name));
        return implode($glue,$name);
    }

    /**
     * @param User $user
     * @param $current_version
     * @param int $page
     * @param int $rows
     * @return array
     */
    public static function getHomeProduct($user,$current_version,$page = 0,$rows = 10)
    {
        $top_depot_id   = $user->getDepotId();
        $shop_id        = $user->getShopId();
        $device_type    = $user->getDeviceType();
        $where = '';
        // 过滤黑名单
        $bbrands = ProductService::getExcludeBrandIds($user);
        if(!empty($bbrands)){
            $brand_str = implode(",",$bbrands);
            $where = " and brand_id not in({$brand_str})";
        }
        $sql = "SELECT DISTINCT p.id product_id,id,product_name,product_param_values_json,all_real_num,brand_id,
        is_hot,is_new,list_img_path,market_price,original_price,wholesale_price,product_unit,art_no,bar_code,tag_title,
        (real_sale_num+virtual_sale_num) sale_num FROM(
 /*购物车商品top40*/
SELECT * FROM(SELECT product_id,1 AS lvl FROM mf_shop_cart WHERE shop_id={$shop_id} ORDER BY id DESC LIMIT 0,40) tb1
UNION 
/*本人购买历史top40*/
SELECT * FROM(
        SELECT a.product_id,2 AS lvl FROM mf_shop_order_product a
        LEFT JOIN mf_shop_order b ON b.id=a.order_id
        WHERE b.shop_id={$shop_id}
        GROUP BY a.product_id ORDER BY SUM(a.num) DESC LIMIT 0,40) tb3
UNION
/*60天以内全场热销top20*/
SELECT * FROM(
        SELECT a.product_id,3 AS lvl FROM mf_shop_order_product a
        LEFT JOIN mf_shop_order b ON b.id=a.order_id
        WHERE UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(b.create_time)<5275202
        GROUP BY a.product_id ORDER BY SUM(a.num) DESC LIMIT 0,20) tb3
UNION
/*最近上架商品top1000*/
SELECT * FROM (SELECT tb_product.id AS product_id,4 AS lvl FROM tb_product ORDER BY id DESC LIMIT 0,1000) tb4
) p1
LEFT JOIN tb_product p ON p.id=p1.product_id
WHERE p1.product_id IS NOT NULL
AND p.is_on_sale=1 AND p.is_show=1
AND (p.supplier_id IN(112,114,115,116) OR p.supplier_id>=117)
{$where}
ORDER BY p1.lvl,p1.product_id DESC LIMIT {$page},{$rows}";
        $res = Db::select($sql);
//        UtilsTool::logger()->info(print_r($res,true));

//        self::resultHidden($res,['all_real_num','product_param_values_json','id']);

        foreach (['all_real_num','product_param_values_json','id'] as $name){
           foreach ($res as $k=>$v){
               unset($res[$k]->$name);
           }
        }

        // 未登录隐藏批发价
        if(!empty($current_version)
            && UtilsTool::config_value("PRICE_NO_LOGIN_SHOW")
            && $device_type == 'ios'
            && strpos(UtilsTool::config_value("PRICE_NO_LOGIN_SHOW_VERSION"),$current_version) !== false)
        {
        }
        else
        {
//            if($shop_id == 0) self::resultHidden($res,['wholesale_price','original_price','market_price']);
            if ($shop_id == 0)
            {
                foreach (['wholesale_price','original_price','market_price'] as $name){
                    foreach ($res as $k=>$v){
                        unset($res[$k]->$name);
                    }
                }
            }

        }
        // 购物车数量
        if($shop_id > 0)
        {
            $ShopCartService = new ShopCartService();
            $cart_list = $ShopCartService->items(false,$user);
            if($cart_list)
            {
                foreach ($cart_list as $c)
                {
                    $cart[$c->product_id] = $c->num;
                }
            }
        }
        $img_style = UtilsTool::config_value("IMG_STYLE_LIST_BIG");

        foreach ($res as $k=>$v)
        {
            $res[$k]->list_img_path = img_url($v->list_img_path,$img_style);
            $res[$k]->cart_num = isset($cart[$v->product_id]) ? $cart[$v->product_id]:0;
            $product_ids[] = $v->product_id;
            $res[$k]->market_price = isset($v->market_price)?(float)$v->market_price:'';
        }

        if(isset($product_ids))
        {
            $product_stock_res = TbProductStock::whereIn('product_id',$product_ids)->where('top_depot_id','=',$top_depot_id)->get();
            if($product_stock_res->isNotEmpty())
            {
                foreach ($product_stock_res as $v)
                {
                    $product_stock[$v->product_id] = $v;
                }
            }
        }
        foreach ($res as $k=>$v)
        {
            $res[$k]->salable_num = isset($product_stock[$v->product_id])?$product_stock[$v->product_id]['salable_num']:0;
        }
        ActService::productList($res,$user);
        return $res;
    }

    /**
     * 获取商品库存信息
     * @param int $productId 商品ID
     * @param int $depotId 仓库ID
     * @return array
     */
    public static function getStockNum($productId = 0,$depotId = 1)
    {
        $dinfo = MfDepotProduct::where("top_depot_id",$depotId)
            ->where("product_id",$productId)
            ->where("is_delete",0)
            ->first();
        $pinfo = TbProductStock::where("top_depot_id",$depotId)
            ->where("product_id",$productId)
            ->first();
        return [
            'depot_store_num'=>isset($dinfo->store_num)?$dinfo->store_num:'-',
            'depot_lock_num'=>isset($dinfo->lock_num)?$dinfo->lock_num:'-',
            'stock_num'=>isset($pinfo->stock_num)?$pinfo->stock_num:'-',
            'salable_num'=>isset($pinfo->salable_num)?$pinfo->salable_num:'-',
            'lock_num'=>isset($pinfo->lock_num)?$pinfo->lock_num:'-',
            'warn_num'=>isset($pinfo->warn_num)?$pinfo->warn_num:'-',
            'depot_id'=>isset($dinfo->depot_id)?$dinfo->depot_id:0
        ];
    }

    public static function updateParam(TbProduct $model)
    {
        $json = $model->product_param_values_json;
        if(empty($json)) return;

        TbProductParamValue::where("product_id",$model->id)->delete();
        $insert = [];
        foreach ($json as $v)
        {
            $val = trim($v['value']);
            if(empty($val)) continue;
            $insert[] = [
                'product_id'=>$model->id,
                'product_param_id'=>$v['id'],
                'param_val'=>$v['value'],
                'param_name'=>$v['cname'],
                'product_type_id'=>$model->product_type_id
            ];
        }
        if(count($insert) > 0){
            TbProductParamValue::insert($insert);
        }
    }

    /**
     * @param int $product_id
     * @param User $user
     * @return array|\Hyperf\Utils\HigherOrderTapProxy|mixed|void
     * @throws \Hyperf\Di\Exception\Exception
     */
    public static function getDetail(int $product_id,User $user): mixed
    {
        $shop_id        = $user->getShopId();
        $top_deopt_id   = $user->getDepotId();
        $device_type    = $user->getDeviceType();

        $detail = TbProduct::selectRaw('product_name,id product_id,brand_id,market_price,original_price,wholesale_price,jianyi_price,
		        (jianyi_price-wholesale_price) lirun_price,idea_title,art_no,tag_title,video_link,
				real_sale_num,virtual_sale_num,content,is_on_sale,is_show,product_param_values_json,product_unit')
            ->where("id",$product_id)->first();
        if(!$detail) UtilsTool::exception("商品信息有误");
        $detail = $detail->toArray();
        $detail['top_depot_id'] = $top_deopt_id;

        if($detail['is_on_sale'] == 0) {
            UtilsTool::exception("商品已下架");
        }
        if($detail['is_show'] == 0) {
            UtilsTool::exception("商品已下架");
        }
        $detail['brand_name'] = MfBrand::where("id",$detail['brand_id'])->value('cname');
        $detail['salable_num'] = TbProductStock::where('top_depot_id',$top_deopt_id)->where('product_id',$product_id)->value('salable_num');
        if(UtilsTool::config_value("PRICE_NO_LOGIN_SHOW") && $device_type == 'ios')
        {
            $detail['market_price'] = floatval($detail['market_price']);
            $detail['original_price'] = floatval($detail['original_price']);
            $detail['wholesale_price'] = floatval($detail['wholesale_price']);
        }
        else
        {
            if($shop_id == 0)
            {
                foreach ($detail as $k=>$v)
                {
                    if(in_array($k,['wholesale_price','jianyi_price','lirun_price','market_price','original_price']))
                    {
                        unset($detail[$k]);
                    };
                }
            }
            else{
                $detail['market_price'] = floatval($detail['market_price']);
                $detail['original_price'] = floatval($detail['original_price']);
                $detail['wholesale_price'] = floatval($detail['wholesale_price']);

                ActService::productDetail($detail,$user);//促销活动
            }
        }

        $detail['sale_num'] = $detail['real_sale_num']+$detail['virtual_sale_num'];
        foreach ($detail as $k=>$v)
        {
            if(in_array($k,['real_sale_num','virtual_sale_num']))
            {
                unset($detail[$k]);
            };
        }
        $detail['cart_num'] = 0;

        // 购物车数量
        if($shop_id > 0)
        {
            $ShopCartService = new ShopCartService($user);
            $cart_list = $ShopCartService->items(false,$user);
            $detail['cart_num'] = isset($cart_list[$product_id]->num)?$cart_list[$product_id]->num:0;
        }

        // 规格属性列表
        $detail['param_values'] = !is_null(json_decode($detail['product_param_values_json'],true))?json_decode($detail['product_param_values_json'],true):[];

        unset($detail['product_param_values_json']);
        // 同系列关联商品列表
        $detail['group_list'] = Db::select('select a.*,b.wholesale_price from mf_product_group_link a
			left join tb_product b on b.id=a.product_id
			where a.product_group_id =(select product_group_id from mf_product_group_link c 
			where c.product_id=?)
			and b.is_del=0 and b.is_on_sale=1
			order by a.sort asc',[$product_id]);

        $imgPaths = TbProductImg::where('product_id',$product_id)
            ->orderBy('sort','asc')
            ->pluck("img_path")->toArray();

        $detail['image_list'] = array_map(function ($val){
            return ['img_path'=>UtilsTool::img_url($val,UtilsTool::config_value("IMG_STYLE_DETAIL_BIG"))];
        },$imgPaths);

        $detail['content'] = self::getimgs($detail['content'],UtilsTool::config_value("IMG_STYLE_DETAIL_BIG"));
        $detail['ext_image_list'] = [
            'http://img.mwenju.com/uploads/2021/0401/16172587254731.jpg',
            'http://img.mwenju.com/uploads/2021/0401/16172587255321.jpg'
        ];
        if (!empty($detail['video_link']))
        {
            $detail['video'] = [
                'url'=>"http://videos.mwenju.com/".$detail['video_link'],
                'img'=>"http://videos.mwenju.com/".$detail['video_link']."?vframe/jpg/offset/1/h/240"
            ];
        }
        else
        {
            $detail['video'] = null;
        }
        return $detail;
    }

    public static function getimgs($str,$size='')
    {
        $reg = "/[img|IMG].*?src=['|\"](.*?(?:[.gif|.jpg]))['|\"].*?[\/]?>/";
        $matches = array();
        preg_match_all($reg, $str, $matches);
        foreach ($matches[1] as $value) {
            $data[] = UtilsTool::img_url($value,$size);
        }
        return isset($data)?$data:[];
    }

    public static function getListByDepotId($depotId = 0,$supplier_id = 0,$keywords = "",$page = 1,$rows = 30)
    {
        $map[] = ['b.is_del','=',0];
        if(!empty($keywords))
        {
            $map[] = ['product_name|bar_code|art_no','like',"%{$keywords}%"];
        }
        if($supplier_id > 0 || $supplier_id < 0)
        {
            $map[] = ['b.supplier_id','=',$supplier_id];
        }
        $data = Db::table('tb_product as b')
            ->selectRaw('ifnull(a.id,0) depot_product_id,b.id,b.id product_id,b.product_name,ifnull(a.store_num,0) store_num,b.list_img_path,ifnull(d.cname,"") depot_code,
            ifnull(g.last_bid_price,0) last_bid_price,e.cname brand_name,b.bar_code,b.art_no,b.product_unit,f.supplier_name,b.product_param_values_json,b.product_type_id,
            b.is_on_sale,cg.salable_num,cg.cc_product_unit,b.cc_price,b.bid_price,b.wholesale_price')
            ->leftJoin('mf_depot_product as a','b.id','=','a.product_id and a.is_delete=0')
            ->leftJoin('mf_depot as d','d.id','=','a.depot_id')
            ->leftJoin('mf_brand as e','e.id','=','b.brand_id')
            ->leftJoin('tb_supplier as f','f.id','=','b.supplier_id')
            ->leftJoin('tb_product_stock as g','g.product_id','=','b.id and g.top_depot_id='.$depotId) // TODO
            ->leftJoin(['cc_product_stock'=>'cg'],'cg.product_id=b.id and cg.top_depot_id='.$depotId)
            ->where($map)
            ->orderBy("b.id","desc")
            ->paginate($rows)->each(function ($item,$index){
                $item->param_str = ProductService::getParamStr($item->product_param_values_json);
                $item->type_name = ProductService::getTypeName($item->product_type_id,"》");
            });
        return [$data->total(),$data->items()];
    }

    /**
     * @param User $user
     * @return array
     */
    public static function getExcludeBrandIds($user)
    {
        if(empty($token)) return [];
        $tokenData = redis()->get($token);
        if(!$tokenData) return [];

        $area_codes = [];
        if(!empty($user->getProvinceCode())){
            $area_codes[] = $user->getProvinceCode();
        }
        if(!empty($user->getCityCode())){
            $area_codes[] = $user->getCityCode();
        }
        if(!empty($user->getAreaCode())){
            $area_codes[] = $user->getAreaCode();
        }
        $bids = [];
        $brand_ids = Db::table("tb_exclude_brands")->whereIn("area_code",$area_codes)->pluck("brand_ids")->toArray();
        if($brand_ids){
            foreach ($brand_ids as $brand){
                if (empty($brand)) continue;
                foreach (explode(",",$brand) as $b){
                    $bids[] = $b;
                }
            }
            $bids = array_unique($bids);
        }
        return $bids;
    }

    /**
     * 积分商品列表
     * @return mixed
     */
    public static  function integrateProductList()
    {
        $sql = "SELECT a.id product_id,a.product_name,IFNULL(b.salable_num,0) stock_num,a.integrate_num,a.list_img_path,a.product_unit 
                from tb_product a LEFT JOIN tb_product_stock b on b.product_id=a.id and b.top_depot_id=1
                WHERE a.is_del=0 and a.is_on_sale=1 and a.is_integrate=1 ORDER BY a.on_sale_time desc";
        $res = Db::select($sql);
        foreach ($res as $k=>$v)
        {
            $res[$k]->list_img_path = UtilsTool::img_url($v->list_img_path,'listh');
            $res[$k]->buy_num = 0;
        }
        return $res;
    }

    /**
     * @param $price
     * @param $id
     * @param $user User
     * @return bool|false|float|int|mixed|string
     */
    public static function getAreaPrice($price,$id,$user)
    {

        $redis          = UtilsTool::redis();
        $def_price      = $redis->get("PRICE_".$id."_0_0_0");
        $province_price = $redis->get("PRICE_".$id."_".$user->getProvinceCode()."_0_0");
        $city_price     = $redis->get("PRICE_".$id."_".$user->getProvinceCode()."_".$user->getCityCode()."_0");
        $area_price     = $redis->get("PRICE_".$id."_".$user->getProvinceCode()."_".$user->getCityCode()."_".$user->getAreaCode());
        $act_price      = $redis->get("CURRENT_PRICE_".$id);
        $rgn_price      = 0;
        $tag            = $user->getTag();
        if ($def_price){
            $rgn_price = $def_price;
        }
        if ($province_price){
            $rgn_price = $province_price;
        }
        if ($city_price){
            $rgn_price = $city_price;
        }
        if($area_price) {
            $rgn_price = $area_price;
        }
        if ($rgn_price){
            $price = $rgn_price;
        }
        if ($act_price && $act_price < $price)
        {
            $price = $act_price;
        }
        if ( !empty($tag) && $price){
            $rate = UtilsTool::config_value("PRICE_TAG_".$tag,0);
            $rate = floatval($rate);
            if ($rate > 0){
                $price = round($price*$rate,2);
            }
        }
        return $price;
    }

    /**
     * @param $res
     * @param User $user
     */
    public function filter(&$res,$user)
    {
        $redis = UtilsTool::redis();
        if(count($res) > 0)
        {
            foreach ($res as $k=>$v)
            {
                $product_id = $v['product_id'];
                $act_row = $redis->get("CURRENT_ACT_INFO_".$product_id);
                if($act_row)
                {
                    $data = json_decode($act_row,true);
                    $res[$k]['act_list'][] = [
                        'act_name'=>$data['act_name'],
                        'act_price'=>$this->getAreaPrice($data['act_price'],$product_id,$user),
                        'start_time'=>date("Y-m-d",strtotime($data['start_time'])),
                        'end_time'=>date("Y-m-d",strtotime($data['end_time'])),
                        'tag_img'=>$data['tag_img'],
                    ];
                }

                $act = $redis->get("ACT_TAG_".$product_id);
                if($act){
                    $act = json_decode($act,true);
                    $res[$k]['act_list'][] = [
                        "act_name"=>$act['act_name'],
                        'start_time'=>date("Y-m-d",strtotime($act['start_time'])),
                        'end_time'=>date("Y-m-d",strtotime($act['end_time'])),
                        "tag_img"=>UtilsTool::img_url($act['tag_img'])
                    ];
                }

            }
        }
    }

    public function checkExclude(&$input)
    {
        $area_codes = [];
        if(isset($input['province_code']) && !empty($input['province_code'])){
            $area_codes[] = $input['province_code'];
        }
        if(isset($input['city_code']) && !empty($input['city_code'])){
            $area_codes[] = $input['city_code'];
        }
        if(isset($input['area_code']) && !empty($input['area_code'])){
            $area_codes[] = $input['area_code'];
        }

        if(count($area_codes) == 0) return;
        $bids = [];
        if(!empty($input['exclude_brand_ids'])){
            $bids = explode(",",$input['exclude_brand_ids']);
        }
        $brand_ids = Db::table("tb_exclude_brands")->whereIn("area_code",$area_codes)->pluck("brand_ids")->toArray();

        if($brand_ids){
            foreach ($brand_ids as $brand){
                if (empty($brand)) continue;
                foreach (explode(",",$brand) as $b){
                    $bids[] = $b;
                }
            }
            $bids = array_unique($bids);
            $input['exclude_brand_ids'] = implode(",",$bids);
        }
        return $bids;
    }
}