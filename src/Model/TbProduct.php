<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
use Hyperf\Database\Model\Collection;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;

/**
 * @property int $id 
 * @property int $product_id 
 * @property int $add_user_id 
 * @property string $product_name 
 * @property string $list_img_path 
 * @property string $idea_title 
 * @property int $supplier_id 
 * @property string $tag_ids 
 * @property string $tag_title 
 * @property int $brand_id 
 * @property int $product_type_id 
 * @property string $product_param_ids 
 * @property string $product_param_cnames 
 * @property string $product_param_values 
 * @property array $product_param_values_json
 * @property string $bid_price 
 * @property string $market_price 
 * @property string $wholesale_price 
 * @property string $original_price 
 * @property string $jianyi_price 
 * @property string $cc_price 
 * @property int $integrate_num 
 * @property int $cc_integrate_num 
 * @property string $bar_code 
 * @property string $art_no 
 * @property string $product_unit 
 * @property string $cc_product_unit 
 * @property int $cc_packing_rate 
 * @property string $content 
 * @property int $all_real_num 
 * @property int $all_virtual_num 
 * @property int $real_sale_num 
 * @property int $virtual_sale_num 
 * @property int $last_check_user_id 
 * @property int $last_check_time 
 * @property int $status 
 * @property int $is_on_sale 
 * @property int $is_ciridao 
 * @property int $is_hot 
 * @property int $is_integrate 
 * @property int $is_lock 
 * @property int $is_show 
 * @property int $is_new 
 * @property int $is_home 
 * @property int $is_del 
 * @property string $create_time 
 * @property string $update_time 
 * @property string $keyword
 * @property string $video_link
 * @property string $on_sale_time
 */
class TbProduct extends Model implements CacheableInterface
{
    use Cacheable;
    public static function getParamStr($product_param_values_json = '')
    {
        $str = '';
        if (!empty($product_param_values_json)) {
            $arr = json_decode($product_param_values_json, true);
            if ($arr) {
                foreach ($arr as $item) {
                    if (!isset($item['cname'])) {
                        continue;
                    }
                    if (empty($item['value'])) {
                        continue;
                    }
                    $str .= $item['cname'] . ":" . $item['value'] . ",";
                }
            }
        }
        if (!empty($str)) {
            $str = substr($str, 0, -1);
        }
        return $str;
    }
    public function type()
    {
        return $this->hasOne("App\\Common\\Model\\TbProductType", 'id', 'product_type_id');
    }
    public function supplier()
    {
        return $this->hasOne("App\\Common\\Model\\TbSupplier", 'id', 'supplier_id');
    }
    public function brand()
    {
        return $this->hasOne("App\\Common\\Model\\MfBrand", 'id', 'brand_id');
    }
    public function stock()
    {
        return $this->hasMany("App\\Common\\Model\\TbProductStock","id","product_id");
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_product';
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
    protected array $casts = ['id' => 'integer',
        'product_param_values_json'=>'array',
        'product_id' => 'integer', 'add_user_id' => 'integer', 'supplier_id' => 'integer', 'brand_id' => 'integer', 'product_type_id' => 'integer', 'integrate_num' => 'integer', 'cc_integrate_num' => 'integer', 'cc_packing_rate' => 'integer', 'all_real_num' => 'integer', 'all_virtual_num' => 'integer', 'real_sale_num' => 'integer', 'virtual_sale_num' => 'integer', 'last_check_user_id' => 'integer', 'last_check_time' => 'integer', 'status' => 'integer', 'is_on_sale' => 'integer', 'is_ciridao' => 'integer', 'is_hot' => 'integer', 'is_integrate' => 'integer', 'is_lock' => 'integer', 'is_show' => 'integer', 'is_new' => 'integer', 'is_home' => 'integer', 'is_del' => 'integer'];
}