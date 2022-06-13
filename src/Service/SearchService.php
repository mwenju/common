<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfSearchSw;
use Mwenju\Common\Model\MfSearchSwHistory;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Service\Dao\ProductDao;
use Mwenju\Common\Service\Formatter\ProductFormatter;
use Mwenju\Common\Service\ProductService;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Event\SearchWords;
use Mwenju\Common\Rpc\ISearchService;
use Mwenju\Common\Utils\UtilsTool;
use Mwenju\Common\Utils\UtilsUserLogin;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\Event\EventDispatcher;
use Hyperf\RpcServer\Annotation\RpcService;
use Hyperf\Utils\ApplicationContext;

/**
 * Class SearchService
 * @RpcService(name="SearchService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("SearchService","jsonrpc","jsonrpc")]
class SearchService extends BaseService implements ISearchService
{

    private EsService $esService;

    private ProductService $productService;

    private QueueService $queueService;

    private EventDispatcher $eventDispatcher;

    private $index = 'mwj_index';
    public function __construct(EsService $esService,
                                ProductService $productService,
                                QueueService $queueService,
                                EventDispatcher $eventDispatcher
                            )
    {
        $this->esService = $esService;
        $this->productService = $productService;
        $this->queueService = $queueService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function query($input)
    {
        $es = $this->esService->client;
        $esService = $this->esService;
        $productService = $this->productService;
        $user                   = UtilsUserLogin::check(false);
        $page                   = !empty($input["page"])?intval($input["page"]):1;
        $pagesize               = !empty($input["rows"])?intval($input["rows"]):10;
        $params['index']        = $this->index;
        $params['type']         = 'product';
        $params['body']['from'] = $page<1?0:($page-1)*$pagesize;
        $params['body']['size'] = $pagesize;
        $order_by               = !empty($input['order_by'])?trim($input['order_by']):'';
        $shop_id                = $user->getShopId();
        $is_hot                 = !empty($input['is_hot'])?1:0;
        $is_new                 = !empty($input['is_new'])?1:0;
        $product_id             = !empty($input['product_id'])?1:0;
        $keyword                = !empty($input['keyword'])?trim($input['keyword']):'';
        $input['province_code'] = $user->getProvinceCode();
        $input['city_code']     = $user->getCityCode();
        $input['area_code']     = $user->getAreaCode();
        $input['tag']           = $user->getTag();
        $product_type_id        = !empty($input['product_type_id'])?intval($input['product_type_id']):0;
        UtilsTool::logger()->info("param",$input);

        switch ($order_by){
            case 'sale_num_desc':
                $sort = ['sale_num'=>['order'=>"desc"]];
                break;
            case "sale_num_asc":
                $sort = ['sale_num'=>['order'=>"asc"]];
                break;
            case "price_asc":
                $sort = ['wholesale_price'=>['order'=>"asc"]];
                break;
            case "price_desc":
                $sort = ['wholesale_price'=>['order'=>"desc"]];
                break;
            default:
                $sort = ['id'=>['order'=>'desc']];
        }

        if(!empty($order_by) || $product_type_id > 0)
        {
            $params['body']['sort'] = $sort;
        }
        else
        {
            if($is_hot > 0){
                $params['body']['sort'] = ['sale_num'=>['order'=>'desc']];
            }

            if($is_new > 0){
                $params['body']['sort'] = ['id'=>['order'=>'desc']];
            }
        }
        if ($shop_id > 0)
        {
            $productService->checkExclude($input);
        }

        $query = $esService->getQuery($input);
        if($query)
        {
            $params['body']['query'] = $query;
        }
        else{
            $params['body']['query']['match_all'] = new \stdClass;
        }

        $params['_source'] = [
            'id',
            'product_name',
            'brand_name',
            'type_name',
            'idea_title',
            'tag_title',
            'product_unit',
            'product_type_id',
            'brand_id',
            'supplier_id',
            'params',
            'list_img_path',
            'sale_num',
            'bar_code',
            'art_no'
        ];
        // 登录显示价格
        if($shop_id > 0){
            $params['_source'] = array_merge($params['_source'],['wholesale_price','market_price']);
        };
        try {
            $res = $es->search($params);
        }catch (\Exception $e)
        {
            return $e;
        }

        foreach ($res['hits']['hits'] as $v){
            $product_ids[] = $v['_source']['id'];
        }
        $product_stock = [];
        if(isset($product_ids))
        {
            Db::table("tb_product_stock")
                ->whereIn('product_id',$product_ids)
                ->where('top_depot_id',1)
                ->get()->each(function ($v,$k) use (&$product_stock){
                    $product_stock[$v->product_id] = (array)$v;
                });
        }

        $data = [];
        foreach ($res['hits']['hits'] as $v){
            $v['_source']['list_img_path'] = UtilsTool::img_url($v['_source']['list_img_path'],"listh");
            $v['_source']['product_id'] = $v['_source']['id'];
            $v['_source']['salable_num'] = isset($product_stock[$v['_source']['id']])?$product_stock[$v['_source']['id']]['salable_num']:0;
            if(in_array('wholesale_price',$params['_source'])){
                $v['_source']['wholesale_price'] = $this->productService->getAreaPrice($v['_source']['wholesale_price'],$v['_source']['id'],$user);
            }
            $data[] = $v['_source'];
        }
        if(!empty($keyword) && $product_id == 0 && $page == 1){
            $this->eventDispatcher->dispatch(new SearchWords($keyword,$shop_id));
        }
        $productService->filter($data,$user);
        return ['total'=>$res['hits']['total'],'data'=>$data];
    }

    public static function updateSearchWord($keyword = '',$shop_id = 0)
    {
        $s = MfSearchSw::where('sw',$keyword)->first();
        if($s)
        {
            $s->increment('hits',1);
            $s->last_time = time();
            $s->save();
        }
        else
        {
            $s = new MfSearchSw();
            $s->sw = $keyword;
            $s->hits = 1;
            $s->add_time = time();
            $s->last_time = time();
            $s->save();
        }
        MfSearchSwHistory::insert(['shop_id'=>$shop_id,'search_sw_id'=>$s->id,'add_time'=>time()]);
    }

    public function productList($param = [])
    {
        return di(ProductService::class)->getList($param);
    }
    public function create()
    {
        return $this->esService->create();
    }
    public function index($param = [])
    {
        return $this->esService->index($param);
    }
    public function delete($param = [])
    {
        return $this->esService->delete($param);
    }
    public function delDoc($param = [])
    {
        return $this->esService->delDoc($param);
    }
    public function putMapping($param = [])
    {
        return $this->esService->putMapping($param);
    }
}