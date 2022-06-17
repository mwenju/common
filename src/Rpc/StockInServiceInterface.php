<?php

namespace Mwenju\Common\Rpc;

interface StockInServiceInterface
{
    public function addProduct($param = []);

    public function updateProduct($id,$num,$bid_price);

    public function submit($param = []);

    public function audit($param = []);
}