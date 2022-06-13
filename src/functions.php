<?php

use Mwenju\Common\Utils\JsonResponse;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\RedisFactory;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\Contract\SessionInterface;
if (! function_exists('di')) {
    /**
     * Finds an entry of the container by its identifier and returns it.
     * @return mixed|\Psr\Container\ContainerInterface
     */
    function di(?string $id = null):\Psr\Container\ContainerInterface
    {
        $container = ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }

        return $container;
    }
}
if (!function_exists("config_value"))
{
    function config_value($name = '', $def = "")
    {
        $nameKey = "CONFIG_".$name;
        $redis = UtilsTool::redis();
        $val = $redis->get($nameKey);
        if($val) return $val;
        $val = Db::table("mf_config")->where('config_name',$name)->value('config_value');
        $redis->set($nameKey,$val);
        return !is_null($val)?$val:$def;
    }
}
if (!function_exists("url")){
    function url(?string $url,array $query){
        $domain = config_value("api.url_domain_root");
        if (!preg_match("/^http?/", $domain)) {
            $domain = "http://".$domain;
        }
        if (!preg_match("/^http?/", $url)) {
            $url = rtrim($domain,"/")."/".trim($url,'/')."?";
        }
        return $url.http_build_query($query);
    }
}

if (!function_exists("img_url"))
{
    function img_url(?string $str,$size = 'listh',$returnDef = true,$device_type = '')
    {
        if (empty($str)) {
            if ($returnDef && preg_match("/^http?/", $returnDef)) {
                return $returnDef;
            }
            return $returnDef ? config_value('api.no_img_path') : "";
        }
        if (!empty($size)) $size = '-' . $size;
        if (preg_match("/^http?/", $str)) {
            if (preg_match("/img/i", $str)) {
                $str = rtrim($str,"-detailh");
                return $str . $size;
            }
            return $str;
        } else {

            $str = ltrim($str, '/');
            if ($device_type == 'miniprogram') {
                return config_value('api.https_img_server_url') . $str . $size;
            }
            return config_value('api.img_server_url') . $str . $size;
        }
    }
}
if (!function_exists("input")) {
    function input($key = '', $def = '', $filter = '')
    {
        if (empty($key)) return ApplicationContext::getContainer()->get(RequestInterface::class)->all();
        $value = ApplicationContext::getContainer()->get(RequestInterface::class)->input($key, $def);
        return !empty($filter) ? call_user_func($filter, $value) : $value;
    }
}
if (!function_exists("jsonSuccess")) {
    function jsonSuccess($data = [],$msg = '')
    {
        if (empty($msg))
        {
            if (!is_array($data) && !is_object($data) && strlen($data) > 0) {
                $msg = $data;
                $data = [];
            }
            $msg = $msg ? $msg : "提交成功";
        }
        return JsonResponse::ajaxSuccess($data,$msg);
    }
}
if (!function_exists("md7")) {
    function md7($str)
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
}

if (!function_exists("arraySuccess")){
    function arraySuccess($msg = "处理成功",$data = []){
        return ['data'=>$data,'msg'=>$msg,'err_code'=>0];
    }
}
if (!function_exists("arrayError")){
    function arrayError($msg = "处理成功",$err_code = 300){
        return ['msg'=>$msg,'err_code'=>$err_code];
    }
}
if (!function_exists('redis')) {
    /**
     * Redis
     * @param string $name
     * @return \Hyperf\Redis\RedisProxy|Redis
     */
    function redis($name = 'default')
    {
        return ApplicationContext::getContainer()->get(RedisFactory::class)->get($name);
    }
}

if (!function_exists('Logger')) {
    /**
     * Redis
     * @return StdoutLoggerInterface
     */
    function Logger()
    {
        return ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
    }
}

if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {
        /**
         * @var ServerRequestInterface $request
         */
        $request = ApplicationContext::getContainer()->get(ServerRequestInterface::class);
        $ip_addr = $request->getHeaderLine('x-forwarded-for');
        if (verify_ip($ip_addr)) {
            return $ip_addr;
        }
        $ip_addr = $request->getHeaderLine('remote-host');
        if (verify_ip($ip_addr)) {
            return $ip_addr;
        }
        $ip_addr = $request->getHeaderLine('x-real-ip');
        if (verify_ip($ip_addr)) {
            return $ip_addr;
        }
        $ip_addr = $request->getServerParams()['remote_addr'] ?? '0.0.0.0';
        if (verify_ip($ip_addr)) {
            return $ip_addr;
        }
        return '0.0.0.0';
    }
}


if (!function_exists('get_container')) {
    function get_container($id)
    {
        return ApplicationContext::getContainer()->get($id);
    }
}

if (!function_exists('verify_ip')) {
    function verify_ip($realip)
    {
        return filter_var($realip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
}
//输出控制台日志
if (!function_exists('p')) {
    function p($val, $title = null, $starttime = '')
    {
        print_r('[ ' . date("Y-m-d H:i:s") . ']:');
        if ($title != null) {
            print_r("[" . $title . "]:");
        }
        print_r($val);
        print_r("\r\n");
    }
}

if (!function_exists('uuid')) {
    function uuid($length)
    {
        if (function_exists('random_bytes')) {
            $uuid = bin2hex(\random_bytes($length));
        } else if (function_exists('openssl_random_pseudo_bytes')) {
            $uuid = bin2hex(\openssl_random_pseudo_bytes($length));
        } else {
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $uuid = substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
        }
        return $uuid;
    }
}
if (!function_exists('filter_emoji')) {
    function filter_emoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        $cleaned = strip_tags($str);
        return htmlspecialchars(($cleaned));
    }


}

if (!function_exists('convert_underline')) {
    function convert_underline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }
}
if (!function_exists('hump_to_line')) {

    /*
        * 驼峰转下划线
        */
    function hump_to_line($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return $str;
    }
}

