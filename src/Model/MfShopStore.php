<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property string $ID_number 
 * @property string $ID_card_img 
 * @property string $qr_code_img 
 * @property int $audit_state 
 * @property string $audit_remark 
 * @property string $desction 
 * @property string $face_img 
 * @property string $create_time 
 * @property string $audit_time 
 * @property int $admin_id 
 */
class MfShopStore extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_store';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'audit_state' => 'integer', 'admin_id' => 'integer'];
}