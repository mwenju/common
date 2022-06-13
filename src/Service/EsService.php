<?php

declare(strict_types=1);
namespace Mwenju\Common\Service;

use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Hyperf\Elasticsearch\ClientBuilderFactory;
/**
 * Class EsService
 * @package App\Service
 */
class EsService
{

    public $client;

    public $index = 'mwj_index';

    public function __construct(ContainerInterface $container)
    {
        $builder = $container->get(ClientBuilderFactory::class)->create();

        $this->client = $builder->setHosts(['http://'.config("es.host").':'.config('es.port')])->build();
    }

    public function search()
    {
        return $this->client->search();
    }

    public function getQuery($input = [])
    {
        $query = [];
        $keyword                    = !empty($input['keyword'])?trim($input['keyword']):'';
        $bar_code                   = !empty($input['bar_code'])?trim($input['bar_code']):'';
        $brand_id                   = !empty($input['brand_id'])?intval($input['brand_id']):0;
        $product_id                 = !empty($input['product_id'])?intval($input['product_id']):0;
        $product_type_id            = !empty($input['product_type_id'])?intval($input['product_type_id']):0;
        $supplier_id                = !empty($input['supplier_id'])?intval($input['supplier_id']):0;
        $is_hot                     = !empty($input['is_hot'])?intval($input['is_hot']):0;
        $is_new                     = !empty($input['is_new'])?intval($input['is_new']):0;
        $ids                        = !empty($input['ids'])?trim($input['ids']):"";
        $exclude_brand_ids          = !empty($input['exclude_brand_ids'])?trim($input['exclude_brand_ids']):"";
        $exclude_supplier_ids       = !empty($input['exclude_supplier_ids'])?trim($input['exclude_supplier_ids']):"";
        $exclude_brand_ids_arr      = [];
        $exclude_supplier_ids_arr   = [];

        if(!empty($exclude_brand_ids))
        {
            $exclude_brand_ids_arr = explode(",",$exclude_brand_ids);
        }

        if(!empty($exclude_supplier_ids))
        {
            $exclude_supplier_ids_arr = explode(",",$exclude_supplier_ids);
        }

        if(!empty($input['id'])){
            $query['bool']['must'] = [
                'match'=>['id'=>$input['id']]
            ];
            return $query;
        }
        if(!empty($ids)){
            $ids_arr = explode(",",$ids);
            $query['bool']['must'] = [
                'terms'=>['id'=>$ids_arr]
            ];
            return $query;
        }
        if($brand_id > 0){
            $query['bool']['must'][] = [
                'match'=>['brand_id'=>$brand_id]
            ];
        }
        if($is_hot > 0){
            $query['bool']['must'][] = [
                'match'=>['is_hot'=>1]
            ];
        }
        if($is_new > 0){
            $query['bool']['must'][] = [
                'match'=>['is_new'=>1]
            ];
        }
        if($product_type_id > 0){
            $query['bool']['must'][] = [
                'match'=>['product_type_id'=>$product_type_id]
            ];
        }
        if($supplier_id > 0){
            $query['bool']['must'][] = [
                'match'=>['supplier_id'=>$supplier_id]
            ];
        }
        if(!empty($exclude_brand_ids)){
            foreach ($exclude_brand_ids_arr as $bid){
                $query['bool']['must_not'][] = [
                    'match'=>['brand_id'=>$bid]
                ];
            }
        }
        if(!empty($exclude_supplier_ids)){
            foreach ($exclude_supplier_ids_arr as $bid){
                $query['bool']['must_not'][] = [
                    'match'=>['supplier_id'=>$bid]
                ];
            }
        }
        if($product_id > 0){
            $query['bool']['must_not'][] = [
                'match'=>['id'=>$product_id]
            ];
        }

        if(is_numeric($keyword) && strlen($keyword) == 13)
        {
            $bar_code = $keyword;
        }
        if(!empty($bar_code)){
            $query['bool']['must'][] = [
                'term'=>['bar_code'=>$bar_code]
            ];
            return $query;
        }
        if(!empty($keyword))
        {
            $query['bool']['should'][] = [
                'match'=>['product_name'=>$keyword],
            ];
            $query['bool']['should'][] = [
                'match'=>['brand_name'=>$keyword],
            ];
            $query['bool']['should'][] = [
                'match'=>['type_name'=>$keyword],
            ];
            $query['bool']['should'][] = [
                'match'=>['idea_title'=>$keyword],
            ];
            $query['bool']['should'][] = [
                'match'=>['art_no'=>$keyword],
            ];
            $query['bool']['should'][] = [
                'match'=>['keyword'=>$keyword],
            ];
        }
        // 属性搜索
        if(isset($input['param_data'])){
            $param_data_list = json_decode(urldecode($input['param_data']),true);
        }else{
            $param_data_list = '';
        }
//        log_info($param_data_list);
        if($param_data_list){
            foreach ($param_data_list as $param_data){
                foreach ($param_data as $id=>$val){
                    $query['bool']['must'][] = ['nested'=>[
                        'path'=>'params',
                        "query"=>[
                            "bool"=>[
                                "must"=>[
                                    "match_phrase"=>["params.id"=>$id],
                                    "match_phrase"=>["params.value"=>$val],
                                ]
                            ]
                        ]
                    ]];
                }
            }
        }
        return $query;
    }

    public function create()
    {
        $params = [
            'index' => "mwj_index",
            'body' => [
                'settings' => [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 2,
                    'analysis'=>[
                        'analyzer'=>['ik'=>['tokenizer'=>'ik_max_word']]
                    ]
                ],
                'mappings'=>[
                    'product'=>[
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties'=>[
                            'id'=>[
                                'type'=>'integer'
                            ],
                            'product_name'=>[
                                'type'=>'text',
                                "analyzer"=>"standard",//standard,ik_smart,ik_max_word
                                "search_analyzer"=>"standard"
                            ],
                            'keyword'=>[
                                'type'=>'text',
                                "analyzer"=>"standard",//standard,ik_smart,ik_max_word
                                "search_analyzer"=>"standard"
                            ],
                            'brand_name'=>[
                                'type'=>'text',
                                "analyzer"=>"ik_max_word",
                                "search_analyzer"=>"ik_smart"
                            ],
                            'type_name'=>[
                                'type'=>'text',
                                "analyzer"=>"ik_max_word",
                                "search_analyzer"=>"ik_smart"
                            ],
                            'wholesale_price'=>[
                                'type'=>'double',
                            ],
                            'sale_num'=>[
                                'type'=>'long',
                            ],
                            'params'=>[
                                'type'=>'nested',
                                'properties'=>[
                                    'id'=>[
                                        'type'=>'integer'
                                    ],
                                    'cname'=>[
                                        'type'=>'text',
                                    ],
                                    'value'=>[
                                        'type'=>'text',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $this->client->indices()->create($params);
    }

    public function index($param = [])
    {
        $where[] = ['is_show','=',1];
        $where[] = ['is_del','=',0];
        $where[] = ['is_on_sale','=',1];
        if(!empty($param['id']) &&$param['id'] > 0){
            $where[] = ['id','=',$param['id']];
        }else{
            $where[] = ['brand_id','>',0];
            $where[] = ['product_type_id','>',0];
        }
        $brand = Db::table("mf_brand")->pluck("cname","id");
        $type = Db::table("tb_product_type")->pluck("cname","id");
        $i = 0;
        Db::table("tb_product")
            ->where($where)
            ->orderBy("id",'asc')
            ->chunk(100,function ($items,$index) use ($brand,$type,&$i){
                foreach($items as $item){
                    $params = [
                        'index' => $this->index,
                        'type' => 'product',
                        'id'=>$item->id,
                        'body' => [
                            'id'=>$item->id,
                            'product_name' => $item->product_name,
                            'brand_name' => isset($brand[$item->brand_id])?$brand[$item->brand_id]:'',
                            'type_name' => isset($type[$item->product_type_id])?$type[$item->product_type_id]:'',
                            'idea_title' => $item->idea_title,
                            'tag_title' => $item->tag_title,
                            'bar_code'=>$item->bar_code,
                            'art_no'=>$item->art_no,
                            'keyword'=>$item->keyword,
                            'market_price'=>$item->market_price,
                            'wholesale_price'=>$item->wholesale_price,
                            'jianyi_price'=>$item->jianyi_price,
                            'original_price'=>$item->original_price,
                            'sale_num'=>$item->real_sale_num+$item->virtual_sale_num,
                            'product_unit'=>$item->product_unit,
                            'list_img_path'=>$item->list_img_path,
                            'supplier_id'=>$item->supplier_id,
                            'brand_id'=>$item->brand_id,
                            'is_hot'=>$item->is_hot,
                            'is_new'=>$item->is_new,
                            'is_home'=>$item->is_home,
                            'product_type_id'=>$item->product_type_id,
                            'params'=>json_decode($item->product_param_values_json,true),
                        ]
                    ];
                    $i ++;
                    $this->client->index($params);
                };
            });

        return $i;
    }

    public function delDoc($input = []){
        $params = [
            'index' => $this->index,
            'type' => 'product',
            'id' => isset($input['id'])?$input['id']:0
        ];

        // 删除 /my_index/my_type/my_id 目录下的文件
        return $response = $this->client->delete($params);
    }

    public function close(){
        return  $this->client->indices()->close(['index'=>$this->index]);
    }

    public function open(){
        return $this->client->indices()->open(['index'=>$this->index]);
    }
    public function delete($input = [])
    {
        $params = ['index' => $this->index];
        $response = $this->client->indices()->delete($params);
        return $response;
    }

    public function putMapping($param = [])
    {
        return $this->client->indices()->putMapping($param);
    }
}