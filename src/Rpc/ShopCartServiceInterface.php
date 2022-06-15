<?php
namespace Mwenju\Common\Rpc;

use Mwenju\Common\Pojo\User;
use Mwenju\Common\Service\ShopCartService;

Interface ShopCartServiceInterface
{
    public function getShopID();
    public function setUser(User $user);
    public function add($goods_id,$num=1);
    public function set($goods_id,$num = 0);
    public function selectProductId();
    public function items($show_select = false);
    public function getSelectProductIds();
    public function getStockNum($SelectProductIds = []);
    public function getProductName();
    public function checkStock();
    public function checkPrice($selectProductIds = []);
    public function isCheckAll();
    public function del($goods_id);
    public function totalType();
    public function totalNum($is_selected = false);
    public function totalPrice();
    public function totalList();
    public function clear();
    public function dohSelect($id = 0);
    public function dohSelectAll($checked = 0);
    public function cartNumMap();
    public function alertCartProduct();
    public function cancelAlertProduct();
    public function push($from_shop_id = 0,$to_shop_id = 0);
}