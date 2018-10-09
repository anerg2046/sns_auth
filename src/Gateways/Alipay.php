<?php

namespace anerg\OAuth2\Gateways;

use anerg\OAuth2\Connector\Gateway;
use anerg\OAuth2\Helper\Str;

class Alipay extends Gateway
{
    const RSA_PRIVATE = 1;
    const RSA_PUBLIC  = 2;

    const API_BASE            = 'https://openapi.alipay.com/gateway.do';
    protected $AuthorizeURL   = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm';
    protected $AccessTokenURL = 'https://openapi.alipay.com/gateway.do';
    /**
     * 得到跳转地址
     */
    public function getRedirectUrl()
    {
        $params = [
            'app_id'       => $this->config['app_id'],
            'redirect_uri' => $this->config['callback'],
            'scope'        => $this->config['scope'],
            'state'        => $this->config['state'],
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
            throw new \Exception('没有获取到支付宝用户ID！');
        }
    }

    /**
     * 获取格式化后的用户信息
     */
    public function userinfo()
    {
        $rsp = $this->userinfoRaw();

        $userinfo = [
            'openid'  => $this->token['openid'],
            'channel' => 'alipay',
            'nick'    => $rsp['nick_name'],
            'gender'  => strtolower($rsp['gender']),
            'avatar'  => $rsp['avatar'],
        ];
        return $userinfo;
    }

    /**
     * 获取原始接口返回的用户信息
     */
    public function userinfoRaw()
    {
        $this->getToken();

        $rsp = $this->call('alipay.user.info.share');
        return $rsp['alipay_user_info_share_response'];
    }

    /**
     * 发起请求
     *
     * @param string $api
     * @param array $params
     * @param string $method
     * @return array
     */
    private function call($api, $params = [], $method = 'POST')
    {
        $method = strtoupper($method);

        $_params = [
            'app_id'     => $this->config['app_id'],
            'method'     => $api,
            'charset'    => 'UTF-8',
            'sign_type'  => 'RSA2',
            'timestamp'  => date("Y-m-d H:i:s"),
            'version'    => '1.0',
            'auth_token' => $this->token['access_token'],
        ];
        $params         = array_merge($_params, $params);
        $params['sign'] = $this->signature($params);

        $data = $this->$method(self::API_BASE, $params);
        $data = mb_convert_encoding($data, 'utf-8', 'gbk');
        return json_decode($data, true);
    }

    /**
     * 默认的AccessToken请求参数
     * @return array
     */
    protected function accessTokenParams()
    {
        $params = [
            'app_id'     => $this->config['app_id'],
            'method'     => 'alipay.system.oauth.token',
            'charset'    => 'UTF-8',
            'sign_type'  => 'RSA2',
            'timestamp'  => date("Y-m-d H:i:s"),
            'version'    => '1.0',
            'grant_type' => $this->config['grant_type'],
            'code'       => isset($_GET['auth_code']) ? $_GET['auth_code'] : '',
        ];
        $params['sign'] = $this->signature($params);
        return $params;
    }

    /**
     * 支付宝签名
     */
    private function signature($data = [])
    {
        ksort($data);
        $str = Str::buildParams($data);

        $rsaKey = $this->getRsaKeyVal(self::RSA_PRIVATE);
        $res    = openssl_get_privatekey($rsaKey);
        if ($res !== false) {
            $sign = '';
            openssl_sign($str, $sign, $res, OPENSSL_ALGO_SHA256);
            openssl_free_key($res);
            return base64_encode($sign);
        }
        throw new \Exception('支付宝RSA私钥不正确');
    }

    /**
     * 获取密钥
     *
     * @param int $type
     * @return string
     */
    private function getRsaKeyVal($type = self::RSA_PUBLIC)
    {
        if ($type === self::RSA_PUBLIC) {
            $keyname = 'pem_public';
            $header  = '-----BEGIN PUBLIC KEY-----';
            $footer  = '-----END PUBLIC KEY-----';
        } else {
            $keyname = 'pem_private';
            $header  = '-----BEGIN RSA PRIVATE KEY-----';
            $footer  = '-----END RSA PRIVATE KEY-----';
        }
        $rsa = $this->config[$keyname];
        if (is_file($rsa)) {
            $rsa = file_get_contents($rsa);
        }
        if (empty($rsa)) {
            throw new \Exception('支付宝RSA密钥未配置');
        }
        $rsa    = str_replace([PHP_EOL, $header, $footer], '', $rsa);
        $rsaVal = $header . PHP_EOL . chunk_split($rsa, 64, PHP_EOL) . $footer;
        return $rsaVal;
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $token 获取access_token的方法的返回值
     */
    protected function parseToken($token)
    {
        $token = mb_convert_encoding($token, 'utf-8', 'gbk');
        $data  = json_decode($token, true);

        if (isset($data['alipay_system_oauth_token_response'])) {
            $data           = $data['alipay_system_oauth_token_response'];
            $data['openid'] = $data['user_id'];
            return $data;
        } else {
            throw new \Exception("获取支付宝 ACCESS_TOKEN 出错：{$token}");
        }
    }
}
