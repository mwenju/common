<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\UtilsTool;
use WXBizDataCrypt;

class WxService
{
    private $device_type = "weixin";// android,ios,h5,miniprogram,weixin
    private $trade_type = 'JSAPI';
    private $mch_id = "";
    private $appid = '';
    private $appsecret = '';
    private $notify_url = '';
    private $attach = "";
    private $returnData = [];

    public function __construct($device_type = '')
    {
        $this->setDeviceType($device_type);
    }

    public function setNotifyUrl($notify_url = '')
    {
        $this->notify_url = $notify_url;
    }

    public function setAttach($attach = '')
    {
        $this->attach = $attach;
    }

    public function setAppId($appid = '')
    {
        $this->appid = $appid;
    }

    public function setAppsecret($appsecret = '')
    {
        $this->appsecret = $appsecret;
    }

    public function setDeviceType($device_type)
    {
        $this->device_type = $device_type;
        if(in_array($device_type,['android','ios']))
        {
            $this->trade_type = "APP";
        }
        elseif (in_array($device_type,['miniprogram','weixin']))
        {
            $this->trade_type = "JSAPI";
        }
        elseif ($device_type == 'h5')
        {
            $this->trade_type = 'MWEB';
        }

    }

    public function getAppID()
    {
        if(!empty($this->appid)) return $this->appid;
        if($this->device_type == 'miniprogram')
        {
            return UtilsTool::config_value("api.wx_miniprogram.appid");
        }
        elseif(in_array($this->device_type,['android','ios']))
        {
            return UtilsTool::config_value("api.wx_app.appid");
        }
        else
        {
            return UtilsTool::config_value("api.wx.appid");
        }
    }

    public function getAppSecret()
    {
        if(!empty($this->appsecret)) return $this->appsecret;
        if($this->device_type == 'miniprogram')
        {
            return UtilsTool::config_value("api.wx_miniprogram.appsecret");
        }
        elseif(in_array($this->device_type,['android','ios']))
        {
            return UtilsTool::config_value("api.wx_app.appsecret");
        }
        else
        {
            return UtilsTool::config_value("api.wx.appsecret");
        }
    }

    public function orderQuery($out_trade_no)
    {
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        //检测必填参数
        $inputObj = new WxPayOrderQuery();
        $inputObj->SetAppid($this->getAppID());//公众账号ID
        $inputObj->SetMch_id(UtilsTool::config_value('api.wx_pay.mch_id'));//商户号
        $inputObj->SetNonce_str( $this->getRandChar(32));//随机字符串
        $inputObj->SetOut_trade_no($out_trade_no);
        $inputObj->SetSign();//签名
        $xml = $inputObj->ToXml();
        $response = $this->postXmlCurl($xml, $url, false, 6);
        $result = WxPayResults::Init($response);
        return $result;
    }

    //获取预支付订单
    public function getPrePayOrder($body, $out_trade_no, $total_fee,$openid = ''):array
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $onoce_str = $this->getRandChar(32);
        $data = [];
        $data["appid"] = $this->getAppID();
        $data["body"] = $body;
        $data["mch_id"] = UtilsTool::config_value('api.wx_pay.mch_id');
        $data["nonce_str"] = $onoce_str;
        $data["notify_url"] = !empty($this->notify_url)?$this->notify_url:UtilsTool::config_value("api.wx_pay.wxpaynotifyurl");
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = get_client_ip();
        $data["total_fee"] = $total_fee*100;
        $data["trade_type"] = $this->trade_type; // APP JSAPI MWEB
        if(!empty($this->attach))
        {
            $data['attach'] = $this->attach;
        }
        if($this->trade_type == 'JSAPI')
        {
            $data['openid'] = $openid;
        }
        $s = $this->getSign($data);
        $data["sign"] = $s;
        $xml = $this->arrayToXml($data);
        Logger::init()->info($xml);
        $response = $this->postXmlCurl($xml, $url);
        //将微信返回的结果xml转成数组
        return $this->xmlToArray($response);
    }

    //执行第二次签名，才能返回给客户端使用
    public function getOrder($prepayId,$order_code = '')
    {
        $time = time();
        if($this->trade_type == 'APP')
        {
            $data["appid"] = $this->getAppID();
            $data["noncestr"] = $this->getRandChar(32);
            $data["timestamp"] = (string)$time;
            $data["package"] = "Sign=WXPay";
            $data['prepayid'] = $prepayId;
            $data['partnerid'] = UtilsTool::config_value('api.wx_pay.mch_id');
            $data["sign"] = $this->getSign($data);
        }else
        {
            $data["appId"] = $this->getAppID();
            $data["nonceStr"] = $this->getRandChar(32);
            $data["timeStamp"] = (string)$time;
            $data["package"] = "prepay_id=".$prepayId;
            $data['signType'] = "MD5";
            $data["paySign"] = $this->getSign($data);
        }
        $data['order_code'] = $order_code;
        Logger::init()->info("WX_PAY_SIGN_PARAM => ".json_encode($data));
        return $data;
    }

    /**
     * 获取openid
     * @param string $code
     */
    public function getOpenID($code = '')
    {
        $appid = $this->getAppID();
        $appsecret = $this->getAppSecret();
        if($this->device_type == 'miniprogram')
        {
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$appsecret}&js_code={$code}&grant_type=authorization_code";
        }
        else
        {
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type=authorization_code";
        }

        $res = json_decode(UtilsTool::httpGet($url), true);
        if(isset($res['errcode']) && !empty($res['errcode']))
        {
            Logger::init()->info("GET_OPENID_ERR:".json_encode([$url,$res],JSON_UNESCAPED_UNICODE));
            return UtilsTool::exception($res['errmsg'].$res['errcode']);
        }

        if($this->device_type == 'miniprogram'){
            $res = array_merge($res,['access_token'=>$this->getAccessToken($res['openid'])]);
        }
        Logger::init()->info("GET_OPEN_ID:".json_encode($res));
        $this->returnData = $res;
        return $res['openid'];
    }

    public function getAccessToken($openid = "")
    {
        $redis = redis();
        $access_token = $redis->get("access_token_".$openid);
        if($access_token) return $access_token;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->getAppID()}&secret={$this->getAppSecret()}";
        $res = json_decode(UtilsTool::httpGet($url), true);
        if(isset($res['errcode']) && !empty($res['errcode']))
        {
            Logger::init()->error("GET_AccessToken_ERR:",[$url,$res]);
            return UtilsTool::exception($res['errmsg'].$res['errcode']);
        }
        $redis->setex("access_token_".$openid,$res['expires_in'],$res['access_token']);
        return $res['access_token'];
    }
    public function returnData($code = '')
    {
        $this->getOpenID($code);
        $redis = redis();
        Logger::init()->info("WX_AUTH_DATA:".json_encode($this->returnData));
        $redis->set("session_key_".$this->returnData['openid'],$this->returnData['session_key']);
        return $this->returnData;
    }

    public function decryptData($openid,$encryptedData, $iv)
    {
        $redis = redis();
        $sessionKey = $redis->get("session_key_".$openid);
        $pc = new WXBizDataCrypt($this->appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return \json_decode($data,true);
        } else {
            UtilsTool::exception("网路繁忙，请重新授权",$errCode);
        }
    }

    public function getUserInfo($access_token = '',$open_id='')
    {

        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$open_id}";
        $res = json_decode(UtilsTool::httpGet($url), true);
        if(isset($res['errcode']) && !empty($res['errcode']))
        {
            Logger::init()->error("GET_WX_USERINFO_ERR:".json_encode([$url,$res],JSON_UNESCAPED_UNICODE));
            UtilsTool::exception($res['errmsg'],$res['errcode']);
        }
        return $res;
    }
    /*
     生成签名
     */
    function getSign($Obj)
    {
        foreach ($Obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo "【string】 =".$String."</br>";
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".UtilsTool::config_value('api.wx_pay.mch_key');
        //        echo "<textarea style='width: 50%; height: 150px;'>$String</textarea> <br />";
        //签名步骤三：MD5加密
        Logger()->info("WX_PAY_SIGN_MD5 => ".$String);
        $result_ = strtoupper(md5($String));
        Logger()->info("WX_PAY_SIGN_MD5_STR => ".$result_);
        return $result_;
    }

    //获取指定长度的随机字符串
    function getRandChar($length){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }

    //数组转xml
    function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val))
            {
                $xml.="<".$key.">".$val."</".$key.">";

            }
            else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.="</xml>";
        return $xml;
    }

    //post https请求，CURLOPT_POSTFIELDS xml格式
    function postXmlCurl($xml,$url,$second=30)
    {
        //初始化curl
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data)
        {
            curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }

    /*
     获取当前服务器的IP
     */
    function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }

    //将数组转成uri字符串
    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }

    function domnode_to_array($node) {
        $output = array();
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = $this->domnode_to_array($child);
                    if(isset($child->tagName)) {
                        $t = $child->tagName;
                        if(!isset($output[$t])) {
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    }
                    elseif($v) {
                        $output = (string) $v;
                    }
                }
                if(is_array($output)) {
                    if($node->attributes->length) {
                        $a = array();
                        foreach($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if(is_array($v) && count($v)==1 && $t!='@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }
        return $output;
    }

    function xml_to_array($xml)
    {
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches))
        {
            $count = count($matches[0]);
            $arr = array();
            for($i = 0; $i < $count; $i++)
            {
                $key = $matches[1][$i];
                $val = $this->xml_to_array( $matches[2][$i] );  // 递归
                if(array_key_exists($key, $arr))
                {
                    if(is_array($arr[$key]))
                    {
                        if(!array_key_exists(0,$arr[$key]))
                        {
                            $arr[$key] = array($arr[$key]);
                        }
                    }else{
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = $val;
                }else{
                    $arr[$key] = $val;
                }
            }
            return $arr;
        }else{
            return $xml;
        }
    }

    function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
    /**
     * 回调地址
     */
    public function wxpaynotifyurl($val){
        //判断当前甩完之后，商品的总进度，如果满，则更新状态。  status=1
//        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
//        $xml = file_get_contents('php://input');;
//        if(empty($xml)) UtilsTool::exception('请求错误');
//        $xml_data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
//        $val = json_decode(json_encode($xml_data), true);

        foreach( $val as $k=>$v) {
            if($k == 'sign') {
                $xmlSign = $val[$k];
                unset($val[$k]);
            };
        }
        $sign = $this->makeSign($val);
        if($val["result_code"] == "SUCCESS" && $val['return_code'] == "SUCCESS" && $xmlSign === $sign){
            return $val;
        }else{
            $msg = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
            UtilsTool::exception($msg);
        }
    }
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量
     */
    function makeSign($data){
        // 去空
        $data=array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a=http_build_query($data);
        $string_a=urldecode($string_a);
        //签名步骤二：在string后加入KEY
        $string_sign_temp=$string_a."&key=".UtilsTool::config_value('api.wx_pay.mch_key');
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result=strtoupper($sign);
        return $result;
    }

}