<?php

namespace anerg\OAuth2\Gateways;

use anerg\OAuth2\Connector\Gateway;
use anerg\OAuth2\Helper\Str;

class Facebook extends Gateway
{
    const API_BASE            = 'https://graph.facebook.com/v3.1/';
    protected $AuthorizeURL   = 'https://www.facebook.com/v3.1/dialog/oauth';
    protected $AccessTokenURL = 'https://graph.facebook.com/v3.1/oauth/access_token';

    /**
     * 构造函数
     *
     * @param array $config
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->clientParams();
    }

    /**
     * 设置客户端请求的参数
     *
     * @return void
     */
    private function clientParams()
    {
        if (isset($this->config['access_token']) && !empty($this->config['access_token'])) {
            $this->token['access_token'] = $this->config['access_token'];
        }
    }

    /**
     * 得到跳转地址
     */
    public function getRedirectUrl()
    {
        $params = [
            'response_type' => $this->config['response_type'],
            'client_id'     => $this->config['app_id'],
            'redirect_uri'  => $this->config['callback'],
            'scope'         => $this->config['scope'],
            'state'         => $this->config['state'] ?: Str::random(),
        ];
        return $this->AuthorizeURL . '?' . http_build_query($params);
    }

    /**
     * 获取当前授权用户的openid标识
     */
    public function openid()
    {
        $userinfo = $this->userinfo();
        return $userinfo['openid'];
    }

    /**
     * 获取格式化后的用户信息
     */
    public function userinfo()
    {
        $rsp = $this->userinfoRaw();

        $userinfo = [
            'openid'  => $rsp['id'],
            'channel' => 'facebook',
            'nick'    => $rsp['name'],
            'gender'  => $this->getGender($rsp), //不一定会返回
            'avatar'  => $this->getAvatar($rsp),
        ];
        return $userinfo;
    }

    /**
     * 获取原始接口返回的用户信息
     */
    public function userinfoRaw()
    {
        $this->getToken();
        $fields = isset($this->config['fields']) ? $this->config['fields'] : 'id,name,gender,picture.width(400)';
        return $this->call('me', ['access_token' => $this->token['access_token'], 'fields' => $fields], 'GET');
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
        $method  = strtoupper($method);
        $request = [
            'method' => $method,
            'uri'    => self::API_BASE . $api,
        ];

        $data = $this->$method($request['uri'], $params);

        return json_decode($data, true);
    }

    /**
     * Facebook的AccessToken请求参数
     * @return array
     */
    protected function accessTokenParams()
    {
        $params = [
            'client_id'     => $this->config['app_id'],
            'client_secret' => $this->config['app_secret'],
            'code'          => isset($_REQUEST['code']) ? $_REQUEST['code'] : '',
            'redirect_uri'  => $this->config['callback'],
        ];
        return $params;
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $token 获取access_token的方法的返回值
     */
    protected function parseToken($token)
    {
        $token = json_decode($token, true);
        if (isset($token['error'])) {
            throw new \Exception($token['error']['message']);
        }
        return $token;
    }

    /**
     * 格式化性别
     *
     * @param array $rsp
     * @return string
     */
    private function getGender($rsp)
    {
        $gender = isset($rsp['gender']) ? $rsp['gender'] : null;
        $return = 'n';
        switch ($gender) {
            case 'male':
                $return = 'm';
                break;
            case 'female':
                $return = 'f';
                break;
        }
        return $return;
    }

    /**
     * 获取用户头像
     *
     * @param array $rsp
     * @return string
     */
    private function getAvatar($rsp)
    {
        if (isset($rsp['picture']['data']['url'])) {
            return $rsp['picture']['data']['url'];
        }
        return '';
    }
}
