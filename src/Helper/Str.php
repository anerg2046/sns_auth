<?php
namespace anerg\OAuth2\Helper;

class Str
{
    public static function uFirst($str)
    {
        return ucfirst(strtolower($str));
    }

    public static function buildParams($params, $urlencode = false)
    {
        $params    = array_filter($params);
        $param_str = '';
        foreach ($params as $k => $v) {
            if ($k == 'sign') {
                continue;
            }
            $param_str .= $k . '=';
            $param_str .= $urlencode ? rawurlencode($v) : $v;
            $param_str .= '&';
        }
        return rtrim($param_str, '&');
    }

    public static function random($length = 16)
    {
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        return substr(str_shuffle($str_pol), 0, $length);
    }

    public static function getClientIP()
    {
        $ip = '127.0.0.1';
        if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else if (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        }
        return $ip;
    }
}
