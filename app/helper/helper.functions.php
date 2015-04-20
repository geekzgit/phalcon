<?php
/**
 * 辅助函数
 * @author geekzhumail@gmail.com
 * @since 2015-03-04
 */

if (! function_exists('isWechatAgent')) {
    /**
     * 判断是否为微信浏览器
     */
    function isWechatAgent() {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($ua, 'MicroMessenger')) {
            // 确定为微信浏览器
            return true;
        }
        return false;
    }
}

/*
 * 产生一个多位随即数
 */
if (! function_exists('createNoncestr')) {
    function createNoncestr( $length = 32, $type = 'all')
    {
        if ($type == 'all') {
            $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        } else if($type == 'number') {
            $chars = "0123456789";
        }
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
}

/**
 * 是否是本地
 */
if (! function_exists('isLocal')) {
    function isLocal( $length = 32, $type = 'all')
    {
        $config = Phalcon\DI::getDefault()->getConfig();
        if ($config->env == 'local') {
            return true;
        }
        return false;
    }
}

/**
 * 获取当前全部url
 */
if (! function_exists('getCurrentUrl')) {
    function getCurrentUrl($htmlentities = false, $strip = true) {
        // filter function
        $filter = function($input, $htmlentities, $strip) {
            $input = urldecode($input);
            $input = str_ireplace(array("\0", '%00', "\x0a", '%0a', "\x1a", '%1a'), '', $input);
            if ($strip) {
                $input = strip_tags($input);
            }
            if ($htmlentities) {
                $input = htmlentities($input, ENT_QUOTES, 'UTF-8'); // or whatever encoding you use...
            }
            return trim($input);
        };

        $url = array();
        // set protocol
        $url['protocol'] = 'http://';
        if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) === 'on' || $_SERVER['HTTPS'] == 1)) {
            $url['protocol'] = 'https://';
        } elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            $url['protocol'] = 'https://';
        }
        // set host
        $url['host'] = $_SERVER['HTTP_HOST'];
        // set request uri in a secure way
        $url['request_uri'] = $filter($_SERVER['REQUEST_URI'], $htmlentities, $strip);
        return join('', $url);
    }
}
