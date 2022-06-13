<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $template_id 
 * @property string $template_code 
 * @property string $template_data 
 * @property string $beizhu 
 * @property string $create_date 
 * @property int $is_yingxiao 
 */
class MfSmsTemplate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_sms_template';
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
    protected array $casts = ['id' => 'integer', 'is_yingxiao' => 'integer'];
}