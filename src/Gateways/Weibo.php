<?php

namespace anerg\OAuth2\Gateways;

use anerg\OAuth2\Connector\Gateway;

class Weibo extends Gateway
{

    const API_BASE            = 'https://api.weibo.com/2/';
    protected $AuthorizeURL   = 'https://api.weibo.com/oauth2/authorize';
    protected $AccessTokenURL = 'https://api.weibo.com/oauth2/access_token';

    /**
     * 得到跳转地址
     */
    public function getRedirectUrl()
    {
        $this->switchAccessTokenURL();
        $params = [
            'client_id'    => $this->config['app_id'],
            'redirect_uri' => $this->config['callback'],
            'scope'        => $this->config['scope'],
            'state'        => $this->config['state'],
            'display'      => $this->display,
        ];
        return $this->AuthorizeURL . '?' . http_build_query($params);
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
            throw new \Exception('没有获取到新浪微博用户ID！');
        }
    }

    /**
     * 获取格式化后的用户信息
     */
    public function userinfo()
    {
        $rsp = $this->userinfoRaw();

        $userinfo = [
            'openid'  => $this->openid(),
            'channel' => 'weibo',
            'nick'    => $rsp['screen_name'],
            'gender'  => $rsp['gender'],
            'avatar'  => $rsp['avatar_hd'],
        ];
        return $userinfo;
    }

    /**
     * 获取原始接口返回的用户信息
     */
    public function userinfoRaw()
    {
        $this->getToken();

        return $this->call('users/show.json', ['uid' => $this->openid()]);
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
            $this->AuthorizeURL = 'https://open.weibo.cn/oauth2/authorize';
        }
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $token 获取access_token的方法的返回值
     */
    protected function parseToken($token)
    {
        $data = json_decode($token, true);
        if (isset($data['access_token'])) {
            $data['openid'] = $data['uid'];
            unset($data['uid']);
            return $data;
        } else {
            throw new \Exception("获取新浪微博ACCESS_TOKEN出错：{$data['error']}");
        }
    }
}
