<?php

namespace Mwenju\Common\Rpc;

interface BuyOrderServiceInterface
{
    public function getList($param = []);

    public function getInfo($param = []);

    public function audit($param = []);

    public function getProductList($param = []);

    public function getProductInfo($param = []);

    public function updateProduct($param = []);

    public function addProduct($param = []);

    public function clearProduct($param = []);

    public function submit($param = []);

    public function quickAdd($param = []);

    public function sendOrder($param = []);

    public function payOrder($param = []);
}