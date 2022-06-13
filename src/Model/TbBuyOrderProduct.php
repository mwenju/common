<?php

namespace Mwenju\Common\Model;

class TbBuyOrderProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_buy_order_product';
    public function buyOrder()
    {
        return $this->belongsTo("App\\Common\\Model\\TbBuyOrder", 'buy_order_id', 'id');
    }

    public function product()
    {
        return $this->hasOne("App\\Common\\Model\\TbProduct",'id','product_id');
    }
}