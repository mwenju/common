<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\MfProductGroup;
use Mwenju\Common\Model\MfProductGroupLink;

class ProductGroupDao
{
    public function updateOrInsert(array $param,int $id):void
    {
        // 系列
        $group_id = $param['group_id']??0;
        if(!is_numeric($group_id) && !empty($group_id))
        {
            $group = MfProductGroup::insert([
                'cname'=>$group_id,
                'product_type_id'=>$param['product_type_id']
            ]);
            $group_id = $group->id;
        }

        if($group_id > 0)
        {
            $link_name = $param['link_name']??'';
            MfProductGroupLink::where('product_id',$id)->delete();
            if (!empty($link_name))
            {
                MfProductGroupLink::insert([
                    'product_id'=>$id,
                    'product_group_id'=>$group_id,
                    'link_name'=>$link_name??$param['product_name']
                ]);
            }
        }
    }

}