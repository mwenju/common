<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\TbProductStock;
use \Hyperf\Database\Model\Collection;
class ProductStockDao extends Base
{
    public function getStockNumByProductIds($productIds = [],$top_depot_id = 0):Collection
    {
        return TbProductStock::where("top_depot_id",$top_depot_id)->whereIn("product_id",$productIds)->get();
    }
}