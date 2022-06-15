<?php


namespace Mwenju\Common\Rpc;


use Mwenju\Common\Pojo\User;

interface ShopOrderServiceInterface
{
    public function getList($param = []);
    public function getProductList($param = []);
    public function getInfo($param = []);
    public function audit($param = []);
    public function addProduct($param = []);
    public function updateProduct($param = []);
    public function clearProduct($param = []);
    public function submit($param = []);
    public function cancel($param = []);
    public function sendOrder($order_id = 0,$admin_id = 0,$user_id = 0);
    public function receiveReturnOrder($param = []);
    public function oneKeySend($order_id = 0,$admin_id = 0);

}