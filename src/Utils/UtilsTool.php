<?php


namespace Mwenju\Common\Utils;


use Hyperf\DbConnection\Db;
use Hyperf\Di\Exception\Exception;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;

class UtilsTool
{

    public static function img_url($str,$size = '',$returnDef = true,$device_type = ''){
        return img_url($str,$size,$returnDef,$device_type);
    }

    public static function redis():Redis
    {
        return ApplicationContext::getContainer()->get(Redis::class);
    }

    public static function logger($name = 'app'):LoggerInterface
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->make($name);
    }

    public static function input($key = '',$def = '', $filter = '')
    {
        if (empty($key)) return ApplicationContext::getContainer()->get(RequestInterface::class)->all();
        $value = ApplicationContext::getContainer()->get(RequestInterface::class)->input($key,$def);
        return !empty($filter) ? call_user_func($filter, $value) : $value;
    }

    public static function config_value($name = '',$def = "")
    {
        $nameKey = "CONFIG_".$name;
        $redis = UtilsTool::redis();
        $val = $redis->get($nameKey);
        if($val) return $val;
        $val = Db::table("mf_config")->where('config_name',$name)->value('config_value');
        $redis->set($nameKey,$val);
        return !is_null($val)?$val:$def;
    }
    public static function getimgs($str,$size='')
    {
        $reg = "/[img|IMG].*?src=['|\"](.*?(?:[.gif|.jpg]))['|\"].*?[\/]?>/";
        $matches = array();
        preg_match_all($reg, $str, $matches);
        foreach ($matches[1] as $value) {
            $data[] = self::img_url($value,$size);
        }
        return isset($data)?$data:[];
    }

    /**
     * 生成随机唯一订单编号
     * @param int $user_id 登录用户ID
     * @return string
     */
    public static function create_order_number($user_id = 0)
    {
        $str = substr(date("YmdHis"),-12);
        if ($user_id > 0) $str .= str_pad($user_id,4,"0",STR_PAD_LEFT);
        $str .= str_pad(rand(1,9999),4,"0",STR_PAD_LEFT);
        return $str;
    }

    public static function exception($msg = '',$code = 300)
    {
        Logger::init()->error($msg.":".$code);
        throw new Exception($msg,$code);
    }

    public static function jsonSuccess($data = [],$msg = '提交成功')
    {
        return JsonResponse::ajaxSuccess($data,$msg);
    }

    public static function jsonError($msg = '',$code = 300)
    {
        return JsonResponse::ajaxError($msg,$code);
    }
    public static function md7($str)
    {
        $len = strlen($str);
        if ($len <= 6) {
            $len = 6;
        }
        $tmp_len = $len;
        $val_arr = array();
        for ($i = 0; $i < $len; $i++) {
            $val = md5($str . $tmp_len);
            $tmp_len = abs(hexdec(substr($val, $len, $len)) % 32);
            if ($tmp_len < 6) {
                $tmp_len = $tmp_len + 6;
            }

            $tmp_arr = array();
            for ($k = 0; $k < $tmp_len; $k++) {
                $tmp_arr[] = $val[$k];
            }
            $val_arr[] = implode('', $tmp_arr);
        }

        $val_str = implode('', $val_arr);
        $val_str = substr($val_str, $len, 32);

        for ($i = 0; $i < $tmp_len; $i++) {
            $val_str = md5($val_str);
        }
        return $val_str;
    }

    public static function guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }

    public static function get_rand($num = 8)
    {
        $str = range(0,9);
        shuffle($str);
        return implode('', array_slice($str,0,$num));
    }
    public static function httpGet($url,$post='')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
    /**
     * URL生成 支持路由反射
     * @access public
     * @param  string            $url 路由地址
     * @param  string|array      $vars 参数（支持数组和字符串）a=val&b=val2... ['a'=>'val1', 'b'=>'val2']
     * @param  string|bool       $suffix 伪静态后缀，默认为true表示获取配置值
     * @param  boolean|string    $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    public static function url($url = '', $vars = '', $suffix = true, $domain = false)
    {
        // 解析URL
        if (0 === strpos($url, '[') && $pos = strpos($url, ']')) {
            // [name] 表示使用路由命名标识生成URL
            $name = substr($url, 1, $pos - 1);
            $url  = 'name' . substr($url, $pos + 1);
        }

        if (false === strpos($url, '://') && 0 !== strpos($url, '/')) {
            $info = parse_url($url);
            $url  = !empty($info['path']) ? $info['path'] : '';

            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];

                if (false !== strpos($anchor, '?')) {
                    // 解析参数
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }

                if (false !== strpos($anchor, '@')) {
                    // 解析域名
                    list($anchor, $domain) = explode('@', $anchor, 2);
                }
            } elseif (strpos($url, '@') && false === strpos($url, '\\')) {
                // 解析域名
                list($url, $domain) = explode('@', $url, 2);
            }
        }
        // 解析参数
        if (is_string($vars)) {
            // aaa=1&bbb=2 转换成数组
            parse_str($vars, $vars);
        }
        $depr = "/";
        $url  = str_replace('/', $depr, $url);
        $anchor = !empty($anchor) ? '#' . $anchor : '';
        // 参数组装
        if (!empty($vars)) {
            // 添加参数
            foreach ($vars as $var => $val) {
                if ('' !== trim($val)) {
                    $url .= $depr . $var . $depr . urlencode($val);
                }
            }
            $url .= $suffix . $anchor;
        } else {
            $url .= $suffix . $anchor;
        }
        $url = $domain . '/' . ltrim($url, '/');
        return $url;
    }
    public static function createQr($text = '',$url = '')
    {
        return self::url("api/qrcode/create",['text'=>$text,'url'=>$url],'','https://api.mwenju.com');
    }

    /**
     * @param $array 要排序的数组
     * @param $row 排序依据列
     * @param $type 排序类型[asc or desc]
     * @return array
     */
    public static function array_sort($array,$row,$type){
        $array_temp = array();
        foreach($array as $v){
            $array_temp[$v[$row]] = $v;
        }
        if($type == 'asc'){
            ksort($array_temp);
        }elseif($type='desc'){
            krsort($array_temp);
        }else{
        }
        return $array_temp;
    }
}