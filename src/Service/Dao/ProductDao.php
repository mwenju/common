<?php


namespace Mwenju\Common\Service\Dao;


use _PHPStan_76800bfb5\Nette\Neon\Exception;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductPriceLog;
use Mwenju\Common\Model\TbProductType;
use Mwenju\Common\Model\TbSupplier;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

class ProductDao extends Base
{
    public function getList($param,$page,$limit)
    {
        $param              = array_map("trim", $param);
        $tag                = $param['tag']??"";
        $is_on_sale         = $param['is_on_sale']??"";
        $supplier_id        = $param['supplier_id']??0;
        $brand_id           = $param['brand_id']??0;
        $product_type_id    = $param['product_type_id']??0;
        $top_depot_id       = $param['top_depot_id']??0;
        $keyword            = $param['keyword']??"";
        $start_time         = $param['start_time']??"";
        $end_time           = $param['end_time']??"";
        $mold               = $param['mold']??""; // 合作模式：0-自营，1-合作，2-云仓
        $map[]              = ['is_del','=',0];

        if(!empty($tag))
        {
            $map[] = [$tag,'=',1];
        }

        if ($supplier_id > 0)
        {
            $map[] = ['supplier_id','=',$supplier_id];
        }

        if($brand_id >0)
        {
            $map[] = ['brand_id','=',$brand_id];
        }

        if(!empty($start_time))
        {
            $map[] = ['create_time','>=',$start_time];
        }

        if(!empty($end_time))
        {
            $map[] = ['create_time','<=',$end_time];
        }
        if (strlen($is_on_sale) > 0)
        {
            $map[] = ['is_on_sale','=',$is_on_sale];
        }
        $model = TbProduct::query()->where($map);
        if(!empty($keyword))
        {
            $model->where(function(Builder $query) use ($keyword){
               return  $query->where("product_name","like","%{$keyword}%")
                   ->orWhere("bar_code","like","%{$keyword}%")
                   ->orWhere("art_no","like","%{$keyword}%");
            });
        }
        if(strlen($mold) > 0)
        {
            $supplier_ids = TbSupplier::where("is_new",$mold)->pluck("id");
            $model->whereIn("supplier_id",$supplier_ids);
        }
        if($product_type_id > 0)
        {
            $typeids = [];
            $typeids = $this->getChild($product_type_id,$typeids);
            $model->whereIn("product_type_id",$typeids);
        }
        $model->orderBy("id","desc");
        return $this->pagination($model,$page,$limit);
    }

    public function getChild($id = 0,$chids = [])
    {
        $id = !is_array($id)?[$id]:$id;
        $ids = Db::table("tb_product_type")->whereIn('parent_id',$id)->pluck('id');
        if(count($ids) >0)
        {
            $chids[] = $ids;
            return $this->getChild($ids,$chids);
        }
        else
        {
            $chids = $id;
        }
        return $chids;
    }

    public function updateOrInsert(array $param):TbProduct
    {
        $id                 = $param['id']??0;
        $product_name       = $param['product_name']??"";
        $top_depot_id       = $param['top_depot_id']??0;
        $supplier_id        = $param['supplier_id']??0;
        $product_type_id    = $param['product_type_id']??0;
        $brand_id           = $param['brand_id']??0;
        $bar_code           = $param['bar_code']??"";
        $art_no             = $param['art_no']??"";
        $keyword            = $param['keyword']??"";
        $wholesale_price    = $param['wholesale_price']??0;
        $jianyi_price       = $param['jianyi_price']??0;
        $market_price       = $param['market_price']??0;
        $original_price     = $param['original_price']??0;
        $integrate_num      = $param['integrate_num']??0;
        $cc_integrate_num   = $param['cc_integrate_num']??0;
        $product_unit       = $param['product_unit']??"";
        $content            = $param['content']??"";
        $tag_title          = $param['tag_title']??"";
        $video_link         = $param['video_link']??"";
        $list_img_path      = $param['image'][0]??'';
        $session_admin_id   = $param['session_admin_id']??0;
        $is_lock            = 1;
        if (empty($product_name))
            throw new Exception("商品名称不能为空");

        if (empty($product_type_id))
            throw new Exception("分类不能为空");

        if (TbProductType::where("parent_id",$product_type_id)->count() > 0)
            throw new Exception("请选择三级分类");

        if (empty($brand_id))
            throw new Exception("品牌不能为空");

        if (empty($bar_code))
            throw new Exception("条码不能为空");

        if (empty($art_no))
            throw new Exception("货号不能为空");

        if ($this->checkBarCode($bar_code,$art_no,$id))
            throw new Exception("条码货号重复:");

        $post_param             = $param['param']??[];
        $product_param_ids      = [];
        $product_param_cnames   = [];
        $product_param_values_json = [];
        foreach ($post_param as $paramId => $v)
        {
            if(empty($v))
            {
                throw new Exception($param['paramName'][$paramId]."不能为空");
            }
            $product_param_ids[] = $paramId;
            $product_param_cnames[] = $param['paramName'][$paramId];
            $product_param_values[] = $v;
            $product_param_values_json[] = ['id'=>$paramId,'value'=>$v,'cname'=>$param['paramName'][$paramId]];
        }
        $product_param_ids          = implode(',',$product_param_ids);
        $product_param_cnames       = implode(',',$product_param_cnames);
//        $product_param_values_json  = json_encode($product_param_values_json,JSON_UNESCAPED_UNICODE);

        // 分离货号
        preg_match_all("/(\D+|\d+)/", $art_no, $matches);//分离
        foreach ($matches[0] as $v)//循环填空格
        {
            $keyword = $keyword." ".$v;
        }

        if ($id > 0)
        {
            $model = TbProduct::find($id);
            $model->update_time = date("Y-m-d H:i:s");
            $supplier_id        = $model->supplier_id;
            $wholesale_price    = $model->$wholesale_price;
        }
        else
        {
            if (empty($supplier_id))
                throw new Exception("请选择供应商");

            $model = new TbProduct();
            $model->supplier_id = $supplier_id;
            $model->create_time = date("Y-m-d H:i:s");
            $model->wholesale_price = $wholesale_price;
        }
        // 云仓商品不显示
        if (TbSupplier::find($supplier_id)->is_new == 2){
            $model->is_show = 0;
            $is_lock = 0;
        }
        $model->product_name = $product_name;
        $model->product_type_id = $product_type_id;
        $model->brand_id = $brand_id;
        $model->bar_code = $bar_code;
        $model->art_no = $art_no;
        $model->keyword = $keyword;
        $model->original_price = $original_price;
        $model->jianyi_price = $jianyi_price??$this->jianyiPrice($wholesale_price,$product_type_id);
        $model->market_price = $market_price??$this->marketPrice($wholesale_price,$product_type_id);
        $model->product_param_ids = $product_param_ids;
        $model->product_param_cnames = $product_param_cnames;
        $model->product_param_values_json = $product_param_values_json;
        $model->is_lock = $is_lock;
        $model->list_img_path = $list_img_path;
        $model->content = html_entity_decode($content);
        $model->video_link = $video_link;
        $model->tag_title = $tag_title;
        $model->integrate_num = $integrate_num;
        $model->cc_integrate_num = $cc_integrate_num;
        $model->product_unit = $product_unit;
        $model->save();

        if ($id == 0)
        {
            TbProductPriceLog::insert([
                'product_id'=>$model->id,
                'wholesale_price'=>$wholesale_price,
                'market_price'=>$market_price,
                'admin_id'=>$session_admin_id,
                'create_time'=>date("Y-m-d H:i:s"),
            ]);
        }

        return $model;

    }
    /**
     * 更加批发价计算建议售价
     * @param int $wholesale_price
     * @param int $type_id
     * @return string
     */
    public function jianyiPrice($wholesale_price = 0,$type_id = 0)
    {
        $type =  TbProductType::find($type_id);
        $sale_price_per = !empty($type->sale_price_per)?$type->sale_price_per:1.5;
        return number_format($wholesale_price * $sale_price_per, 1);
    }

    /**
     * 市场价计算
     * @param int $wholesale_price
     * @param int $type_id
     * @return string
     */
    public function marketPrice($wholesale_price = 0,$type_id = 0)
    {
        $type =  TbProductType::find($type_id);
        $marker_price_per = !empty($type->marker_price_per)?$type->marker_price_per:1.5;
        return number_format($wholesale_price * $marker_price_per, 1);
    }

    public function checkBarCode(?string $bar_code,?string $art_no,int $id):bool
    {
        if ($id > 0)
            return TbProduct::where("bar_code", $bar_code)->where("art_no", $art_no)->where("id","<>",$id)->count() > 0;
        return TbProduct::where("bar_code", $bar_code)->where("art_no", $art_no)->count() > 0;
    }
}