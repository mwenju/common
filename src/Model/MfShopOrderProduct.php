<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Utils\UtilsTool;
use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $product_id 
 * @property int $supplier_id 
 * @property string $idea_title 
 * @property string $product_name 
 * @property string $bar_code 
 * @property string $art_no 
 * @property string $product_unit 
 * @property string $param_list 
 * @property string $price 
 * @property string $bid_price 
 * @property int $integrate_num 
 * @property int $num 
 * @property int $send_num 
 * @property int $get_num 
 * @property string $list_img_path 
 * @property string $beizhu 
 * @property int $top_depot_id 
 * @property int $check_status 
 * @property int $from_shop_id 
 * @property int $is_return
 */
class MfShopOrderProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_order_product';

    public function getListImgPathAttribute($value)
    {
        return UtilsTool::img_url($value);
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected array $casts = ['id' => 'integer', 'order_id' => 'integer', 'product_id' => 'integer', 'supplier_id' => 'integer', 'integrate_num' => 'integer', 'num' => 'integer', 'send_num' => 'integer', 'get_num' => 'integer', 'top_depot_id' => 'integer', 'check_status' => 'integer', 'from_shop_id' => 'integer'];
}