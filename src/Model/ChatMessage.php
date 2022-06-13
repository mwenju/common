<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $from_user_id 
 * @property string $to_user_id 
 * @property string $session_id 
 * @property string $content 
 * @property string $send_time 
 * @property int $msg_type 
 * @property int $command_type 
 * @property int $is_delete 
 * @property int $is_read 
 * @property string $img_h 
 * @property string $img_w 
 * @property string $duration 
 */
class ChatMessage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'chat_message';
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
    protected array $casts = ['id' => 'integer', 'msg_type' => 'integer', 'command_type' => 'integer', 'is_delete' => 'integer', 'is_read' => 'integer'];
}