<?php

class WxProxy
{
    protected $AuthorizeURL = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    public function run()
    {
        if (isset($_GET['code'])) {
            header('Location: ' . $_COOKIE['return_uri'] . '?code=' . $_GET['code'] . '&state=' . $_GET['state']);
        } else {
            $protocol = $this->is_HTTPS() ? 'https://' : 'http://';
            $params   = array(
                'appid'         => $_GET['appid'],
                'redirect_uri'  => $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['DOCUMENT_URI'],
                'response_type' => $_GET['response_type'],
                'scope'         => $_GET['scope'],
                'state'         => $_GET['state'],
            );
            setcookie('return_uri', urldecode($_GET['return_uri']), $_SERVER['REQUEST_TIME'] + 60, '/');
            header('Location: ' . $this->AuthorizeURL . '?' . http_build_query($params) . '#wechat_redirect');
        }
    }

    /**
     * 是否https
     */
    protected function is_HTTPS()
    {
        if (!isset($_SERVER['HTTPS'])) {
            return false;
        }
        if ($_SERVER['HTTPS'] === 1) { //Apache
            return true;
        } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
            return true;
        } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
            return true;
        }
        return false;
    }
}

$app = new WxProxy();
$app->run();
