<?php

namespace anerg\OAuth2\Gateways\Twitter;

use anerg\OAuth2\Connector\Gateway;
use anerg\OAuth2\Helper\Str;

class TwitterOAuth extends Gateway
{
    const API_BASE = 'https://api.twitter.com/';

    private $tokenSecret = '';

    /**
     * 得到跳转地址
     */
    public function getRedirectUrl()
    {
        $oauthToken = $this->call('oauth/request_token', ['oauth_callback' => $this->config['callback']], 'POST');
        return self::API_BASE . 'oauth/authenticate?oauth_token=' . $oauthToken['oauth_token'];
    }

    /**
     * 获取当前授权用户的openid标识
     */
    public function openid()
    {}

    /**
     * 获取格式化后的用户信息
     */
    public function userinfo()
    {}

    /**
     * 获取原始接口返回的用户信息
     */
    public function userinfoRaw()
    {}

    /**
     * 发起请求
     *
     * @param string $api
     * @param array $params
     * @param string $method
     * @return array
     */
    protected function call($api, $params = [], $method = 'GET')
    {
        $request = [
            'method' => strtoupper($method),
            'uri'    => self::API_BASE . $api,
        ];
        $oauthParams                    = $this->getOAuthParams($params);
        $oauthParams['oauth_signature'] = $this->signature($request, $oauthParams);

        $headers = ['Authorization' => $this->getAuthorizationHeader($oauthParams)];

        return $this->$method($request['uri'], $params, $headers);
    }

    protected function getOAuthParams($params = [])
    {
        $_default = [
            'oauth_consumer_key'     => $this->config['app_id'],
            'oauth_nonce'            => Str::random(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => $this->timestamp,
            'oauth_token'            => '',
            'oauth_version'          => '1.0',
        ];
        return array_merge($_default, $params);
    }

    protected function signature($request, $params = [])
    {

        ksort($params);
        $sign_str = Str::buildParams($params, true);
        $sign_str = $request['method'] . '&' . rawurlencode($request['uri']) . '&' . rawurlencode($sign_str);
        $sign_key = $this->config['app_secret'] . '&' . $this->tokenSecret;

        return rawurlencode(base64_encode(hash_hmac('sha1', $sign_str, $sign_key, true)));
    }

    protected function getAuthorizationHeader($params)
    {
        $return = 'OAuth ';
        foreach ($params as $k => $param) {
            $return .= $k . '="' . $param . '", ';
        }
        return rtrim($return, ', ');
    }
}
