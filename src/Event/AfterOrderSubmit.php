<?php


namespace Mwenju\Common\Event;


class AfterOrderSubmit
{
    public $order_id;
    public function __construct(int $order_id)
    {
        $this->order_id = $order_id;
    }
}