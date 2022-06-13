<?php


namespace Mwenju\Common\Event;


class SearchWords
{
    public $keyword;
    public $shop_id;

    public function __construct($keyword,$shop_id)
    {
        $this->keyword = $keyword;
        $this->shop_id = $shop_id;
    }

}