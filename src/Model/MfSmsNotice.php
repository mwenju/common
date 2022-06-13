<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $userid 
 * @property string $mobile 
 * @property string $status 
 * @property string $msgid 
 * @property string $ext 
 * @property string $done_time 
 * @property string $send_time 
 * @property string $return_time 
 * @property string $create_time 
 */
class MfSmsNotice extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_sms_notice';
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
    protected array $casts = ['id' => 'integer'];
}