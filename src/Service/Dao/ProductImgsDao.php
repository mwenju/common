<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\TbProductImg;

class ProductImgsDao
{
    public function updateOrInsert(array $param,int $id)
    {
        $imgs = $param['image']??[];
        TbProductImg::where('product_id',$id)->delete();
        $img_list = [];
        if($imgs)
        {
            foreach ($imgs as $k=>$img)
            {
                if(empty($img)) continue;
                $img_list[] = ['img_path'=>$img,'product_id'=>$id,'sort'=>$k];
            }
            if(count($img_list) > 0){
                TbProductImg::insert($img_list);
            }
        }
    }
}