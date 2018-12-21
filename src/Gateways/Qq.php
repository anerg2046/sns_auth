<?php

namespace anerg\OAuth2\Gateways;

use anerg\OAuth2\Connector\Gateway;

class Qq extends Gateway
{
    const API_BASE            = 'https://graph.qq.com/';
    protected $AuthorizeURL   = 'https://graph.qq.com/oauth2.0/authorize';
    protected $AccessTokenURL = 'https://graph.qq.com/oauth2.0/token';

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
            'state'         => $this->config['state'],
            'scope'         => $this->config['scope'],
            'display'       => $this->display,
        ];
        return $this->AuthorizeURL . '?' . http_build_query($params);
    }

    /**
     * 获取当前授权用户的openid标识
     */
    public function openid()
    {

        $this->getToken();

        if (!isset($this->token['openid']) || !$this->token['openid']) {
            $userID                 = $this->getOpenID();
            $this->token['openid']  = $userID['openid'];
            $this->token['unionid'] = isset($userID['unionid']) ?: '';
        }

        return $this->token['openid'];
    }

    /**
     * 获取格式化后的用户信息
     */
    public function userinfo()
    {
        $rsp = $this->userinfoRaw();

        $avatar = $rsp['figureurl_qq_2'] ?: $rsp['figureurl_qq_1'];
        if ($avatar) {
            $avatar = \preg_replace('~\/\d+$~', '/0', $avatar);
        }

        $userinfo = [
            'openid'  => $openid = $this->openid(),
            'unionid' => isset($this->token['unionid']) ? $this->token['unionid'] : '',
            'channel' => 'qq',
            'nick'    => $rsp['nickname'],
            'gender'  => $rsp['gender'] == "男" ? 'm' : 'f',
            'avatar'  => $avatar,
        ];
        return $userinfo;
    }

    /**
     * 获取原始接口返回的用户信息
     */
    public function userinfoRaw()
    {
        return $this->call('user/get_user_info');
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

        $params['openid']             = $this->openid();
        $params['oauth_consumer_key'] = $this->config['app_id'];
        $params['access_token']       = $this->token['access_token'];
        $params['format']             = 'json';

        $data = $this->$method(self::API_BASE . $api, $params);

        $ret = json_decode($data, true);
        if ($ret['ret'] != 0) {
            throw new \Exception("qq获取用户信息出错：" . $ret['msg']);
        }
        return $ret;
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $token 获取access_token的方法的返回值
     */
    protected function parseToken($token)
    {
        parse_str($token, $data);
        if (isset($data['access_token'])) {
            return $data;
        } else {
            throw new \Exception("获取腾讯QQ ACCESS_TOKEN 出错：" . $token);
        }
    }

    /**
     * 通过接口获取openid
     *
     * @return string
     */
    private function getOpenID()
    {
        $client = new \GuzzleHttp\Client();

        $query = ['access_token' => $this->token['access_token']];
        // 如果要获取unionid，先去申请：http://wiki.connect.qq.com/%E5%BC%80%E5%8F%91%E8%80%85%E5%8F%8D%E9%A6%88
        if (isset($this->config['withUnionid']) && $this->config['withUnionid'] === true) {
            $query['unionid'] = 1;
        }

        $response = $client->request('GET', self::API_BASE . 'oauth2.0/me', ['query' => $query]);
        $data     = $response->getBody()->getContents();
        $data     = json_decode(trim(substr($data, 9), " );\n"), true);
        if (isset($data['openid'])) {
            return $data;
        } else {
            throw new \Exception("获取用户openid出错：" . $data['error_description']);
        }
    }
}
