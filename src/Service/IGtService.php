<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\UtilsTool;
use GTClient;
use GTNotification;
use GTPushRequest;

class IGtService
{
    public static function pushToSingleByCid($cid = '',$title = '',$body = "",$url = '',$clickType = 'startapp')
    {
        if(empty($title) || empty($body)){
            Logger::init()->error("标题内容不能为空");
            return;
        }
        //创建API，APPID等配置参考 环境要求 进行获取
        $api = new GTClient(UtilsTool::config_value("gt.HOST"),UtilsTool::config_value("gt.AppKey"),UtilsTool::config_value("gt.AppID"),UtilsTool::config_value("gt.MasterSecret"));
        //设置推送参数
        $push = new GTPushRequest();
        $push->setRequestId(self::micro_time());
        $message = new GTPushMessage();
        $notify = new GTNotification();
        $notify->setTitle($title);
        $notify->setBody($body);
        if(!empty($url)){
            $notify->setUrl($url);
        }
        //点击通知后续动作，目前支持以下后续动作:
        //1、intent：打开应用内特定页面url：打开网页地址。2、payload：自定义消息内容启动应用。3、payload_custom：自定义消息内容不启动应用。4、startapp：打开应用首页。5、none：纯通知，无后续动作
        $notify->setClickType($clickType);
        $message->setNotification($notify);
        $push->setPushMessage($message);

        //处理返回结果
        if(!empty($cid)){
            $push->setCid($cid);
            $result = $api->pushApi()->pushToSingleByCid($push);
        }
        else{
            $result = $api->pushApi()->pushAll($push);
        }
        Logger()->info($result);
        return $result;
    }
    public static function micro_time()
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ($sec . substr($usec, 2, 3));
        return $time;
    }
}