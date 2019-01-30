<?php

namespace anerg\OAuth2\Gateways;

use anerg\OAuth2\Connector\Gateway;

class Google extends Gateway
{
    const API_BASE            = 'https://www.googleapis.com/';
    const AUTHORIZE_URL       = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected $AccessTokenURL = 'https://www.googleapis.com/oauth2/v4/token';

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
            'client_id'     => $this->config['app_id'],
            'redirect_uri'  => $this->config['callback'],
            'response_type' => $this->config['response_type'],
            'scope'         => $this->config['scope'],
            'state'         => $this->config['state'],
        ];
        return self::AUTHORIZE_URL . '?' . http_build_query($params);
    }

    /**
     * 获取当前授权用户的openid标识
     */
    public function openid()
    {
        $userinfo = $this->userinfoRaw();
        return $userinfo['id'];
    }

    /**
     * 获取格式化后的用户信息
     */
    public function userinfo()
    {
        $rsp      = $this->userinfoRaw();
        $userinfo = [
            'openid'  => $rsp['id'],
            'channel' => 'google',
            'nick'    => $rsp['name'],
            'email'   => isset($rsp['email']) ? $rsp['email'] : '',
            'gender'  => isset($rsp['gender']) ? $this->getGender($rsp['gender']) : 'n',
            'avatar'  => $rsp['picture'],
        ];
        return $userinfo;
    }

    /**
     * 获取原始接口返回的用户信息
     */
    public function userinfoRaw()
    {
        $this->getToken();

        return $this->call('oauth2/v2/userinfo');
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
        $headers = ['Authorization' => 'Bearer ' . $this->token['access_token']];

        $data = $this->$method(self::API_BASE . $api, $params, $headers);
        return json_decode($data, true);
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($token)
    {
        $data = json_decode($token, true);
        if (isset($data['access_token'])) {
            return $data;
        } else {
            throw new \Exception("获取谷歌 ACCESS_TOKEN 出错：{$token}");
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
}
