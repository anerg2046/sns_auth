<?php

namespace anerg\OAuth2\Gateways;

use anerg\OAuth2\Connector\Gateway;

class Weixin extends Gateway
{
    const API_BASE            = 'https://api.weixin.qq.com/sns/';
    protected $AuthorizeURL   = 'https://open.weixin.qq.com/connect/qrconnect';
    protected $AccessTokenURL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    /**
     * 得到跳转地址
     */
    public function getRedirectUrl()
    {
        $this->switchAccessTokenURL();
        $params = [
            'appid'         => $this->config['app_id'],
            'redirect_uri'  => $this->config['callback'],
            'response_type' => $this->config['response_type'],
            'scope'         => $this->config['scope'],
            'state'         => $this->config['state'],
        ];
        return $this->AuthorizeURL . '?' . http_build_query($params) . '#wechat_redirect';
    }

    /**
     * 获取中转代理地址
     */
    public function getProxyURL()
    {
        $params = [
            'appid'         => $this->config['app_id'],
            'response_type' => $this->config['response_type'],
            'scope'         => $this->config['scope'],
            'state'         => $this->config['state'],
            'return_uri'    => $this->config['callback'],
        ];
        return $this->config['proxy_url'] . '?' . http_build_query($params);
    }

    /**
     * 获取当前授权用户的openid标识
     */
    public function openid()
    {
        $this->getToken();

        if (isset($this->token['openid'])) {
            return $this->token['openid'];
        } else {
            throw new \Exception('没有获取到微信用户ID！');
        }
    }

    /**
     * 获取格式化后的用户信息
     */
    public function userinfo()
    {
        $rsp = $this->userinfoRaw();

        $avatar = $rsp['headimgurl'];
        if ($avatar) {
            $avatar = \preg_replace('~\/\d+$~', '/0', $avatar);
        }

        $userinfo = [
            'openid'  => $this->openid(),
            'unionid' => isset($this->token['unionid']) ? $this->token['unionid'] : '',
            'channel' => 'weixin',
            'nick'    => $rsp['nickname'],
            'gender'  => $this->getGender($rsp['sex']),
            'avatar'  => $avatar,
        ];
        return $userinfo;
    }

    /**
     * 获取原始接口返回的用户信息
     */
    public function userinfoRaw()
    {
        $this->getToken();

        return $this->call('userinfo');
    }

    /**
     * 发起请求
     *
     * @param string $api
     * @param array $params
     * @param string $method
     * @return array
     */
    private function call($api, $params = [], $method = 'GET')
    {
        $method = strtoupper($method);

        $params['access_token'] = $this->token['access_token'];
        $params['openid']       = $this->openid();
        $params['lang']         = 'zh_CN';

        $data = $this->$method(self::API_BASE . $api, $params);
        return json_decode($data, true);
    }

    /**
     * 根据第三方授权页面样式切换跳转地址
     *
     * @return void
     */
    private function switchAccessTokenURL()
    {
        if ($this->display == 'mobile') {
            $this->AuthorizeURL = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        } else {
            //微信扫码网页登录，只支持此scope
            $this->config['scope'] = 'snsapi_login';
        }
    }

    /**
     * 默认的AccessToken请求参数
     *
     * @return array
     */
    protected function accessTokenParams()
    {
        $params = [
            'appid'      => $this->config['app_id'],
            'secret'     => $this->config['app_secret'],
            'grant_type' => $this->config['grant_type'],
            'code'       => isset($_REQUEST['code']) ? $_REQUEST['code'] : '',
        ];
        return $params;
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $token 获取access_token的方法的返回值
     */
    protected function parseToken($token)
    {
        $data = json_decode($token, true);
        if (isset($data['access_token'])) {
            return $data;
        } else {
            throw new \Exception("获取微信 ACCESS_TOKEN 出错：{$token}");
        }
    }

    /**
     * 格式化性别
     *
     * @param string $gender
     * @return string
     */
    private function getGender($gender)
    {
        $return = null;
        switch ($gender) {
            case 1:
                $return = 'm';
                break;
            case 2:
                $return = 'f';
                break;
            default:
                $return = 'n';
        }
        return $return;
    }
}
