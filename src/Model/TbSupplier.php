<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;
/**
 * @property int $id 
 * @property int $user_id 
 * @property string $supplier_name 
 * @property int $add_user_id 
 * @property int $add_time 
 * @property string $desc 
 * @property string $link_man 
 * @property int $link_mobile 
 * @property string $link_email 
 * @property string $addr 
 * @property string $logo 
 * @property string $brand_ids 
 * @property int $status 
 * @property int $audit_status
 * @property int $pay_type
 * @property string $create_time 
 * @property string $update_time 
 * @property int $is_new
 * @property string $bond_rate
 * @property string $service_fee_rate
 */
class TbSupplier extends Model implements CacheableInterface
{
    use Cacheable;
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_supplier';
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
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'add_user_id' => 'integer', 'add_time' => 'integer', 'link_mobile' => 'integer', 'status' => 'integer', 'pay_type' => 'integer'];
}