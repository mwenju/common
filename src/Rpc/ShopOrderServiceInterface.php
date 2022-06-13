<?php


namespace Mwenju\Common\Rpc;


use Mwenju\Common\Pojo\User;
use Mwenju\Common\Service\OrderService;

interface ShopOrderServiceInterface
{
    public function setUser($user):OrderService;
    public function fenjian($order_product_id = 0);
    public function fenJianSubmit($order_id = 0);
    public function fuJian($order_product_id = 0);
    public function fuJianSubmit($order_id = 0);
    public function cancel($order_id = 0);
    public function autoCancel();
    public function noPayNotice();
    public function notify($param = []);
    public function getPrice($cart = null,$coupon_id = 0);
    public function submit(User $user,$input = []);
    public function receive($order_id = 0);
    public function buyAgain($order_id = 0);
    public function getNum($order_product_id = 0,$get_num = 0);
    public function receiveSubmit($order_id = 0);
    public function remarkSubmit($order_id = 0,$remark = '');
}