<?php


namespace Mwenju\Common\Event;


class OrderCancel
{
    public $shop_id;
    public $paid_price;
    public $paid_integrate;
    public $paid_balance_price;

    /**
     * OrderCancel constructor.
     * @param $shop_id
     * @param $paid_price
     * @param $paid_integrate
     * @param $paid_balance_price
     */
    public function __construct($shop_id, $paid_price, $paid_integrate, $paid_balance_price)
    {
        $this->shop_id = $shop_id;
        $this->paid_price = $paid_price;
        $this->paid_integrate = $paid_integrate;
        $this->paid_balance_price = $paid_balance_price;
    }

}