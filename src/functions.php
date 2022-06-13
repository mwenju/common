<?php
if (!function_exists("url")){
    function url(?string $url,array $query,?string $domain){
        if (!preg_match("/^http?/", $domain)) {
            $domain = "http://".$domain;
        }
        if (!preg_match("/^http?/", $url)) {
            $url = rtrim($domain,"/")."/".trim($url,'/')."?";
        }
        return $url.http_build_query($query);
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
if (!function_exists("httpGet")){
    function httpGet($url,$post='')
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
}
