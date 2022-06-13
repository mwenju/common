<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $mobile 
 * @property string $content 
 * @property string $template_id 
 * @property int $state 
 * @property string $res_info 
 * @property string $message 
 * @property string $send_date 
 * @property int $order_id 
 */
class MfSmsLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_sms_log';
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
    protected array $casts = ['id' => 'integer', 'state' => 'integer', 'order_id' => 'integer'];
}