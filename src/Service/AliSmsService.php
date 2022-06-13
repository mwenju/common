<?php

// This file is auto-generated, don't edit it. Thanks.
namespace Mwenju\Common\Service;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsResponse;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\UtilsTool;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use Hyperf\DbConnection\Db;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class AliSmsService
 * @package app\Common\Service
 * @RpcService(name="AliSmsService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("AliSmsService","jsonrpc","jsonrpc")]
class AliSmsService {

    /**
     * 使用AK&SK初始化账号Client
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @return Dysmsapi Client
     */
    public static function createClient($accessKeyId, $accessKeySecret){
        $config = new Config([
            // 您的AccessKey ID
            "accessKeyId" => $accessKeyId,
            // 您的AccessKey Secret
            "accessKeySecret" => $accessKeySecret
        ]);
        // 访问的域名
        $config->endpoint = "dysmsapi.aliyuncs.com";
        return new Dysmsapi($config);
    }
    public static function sendSms($mobile='',$template_id='',$data=[],$ext=[])
    {
        return self::send($mobile,$template_id,$data,$ext);
    }
    /**
     * @param string $mobile
     * @param string $template_id
     * @param array $data
     * @param array $ext
     * @return void
     */
    public static function send($mobile='',$template_id='',$data=[],$ext=[]){
        $client = self::createClient(UtilsTool::config_value("aliSms.accessKeyId"), UtilsTool::config_value("aliSms.accessKeySecret"));
        $sendSmsRequest = new SendSmsRequest([]);
        $sendSmsRequest->phoneNumbers = $mobile;
        $sendSmsRequest->signName = "买文具网";

        $template_data = Db::table('mf_sms_template')->where('template_id',$template_id)->first();

        // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $sendSmsRequest->templateCode = $template_data->template_code;

        // 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
        if(!empty($data))
        {
            $sendSmsRequest->templateParam = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $tp = new \App\Common\Utils\Tp();
        if(is_array($data) && $template_data)
        {
            $content = $tp->parse($template_data->template_data,$data);
        }
        else
        {
            $content = is_array($data)?json_encode($data,JSON_UNESCAPED_UNICODE):$data;
        }
        if (env("SMS_DEGUG",false)){
            return Logger::init("SMS")->info($content);
        }
        // 复制代码运行请自行打印 API 的返回值
        /**
         * @var SendSmsResponse
         */
        $acsResponse = $client->sendSms($sendSmsRequest);
        Logger::init()->info("sms:",(array)$acsResponse);
        $insert_data = array(
            'content'=>$content,
            'template_id'=>$template_id,
            'state'=>$acsResponse->body->code!='OK'?0:1,
            'send_date'=>date("Y-m-d H:i:s"),
            'mobile'=>$mobile,
            'message'=>$acsResponse->body->message,
            'res_info'=>isset($acsResponse->body->bizId)?$acsResponse->body->bizId:''
        );
        if(!empty($ext)) $insert_data = array_merge($insert_data,$ext);
        Db::table('mf_sms_log')->insert($insert_data);
        return $acsResponse->body->code;
    }
}