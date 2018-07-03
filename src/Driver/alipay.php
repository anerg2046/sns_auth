<?php

/**
 * 支付宝登陆Api
 *
 * @author Coeus <r.anerg@gmail.com>
 */

namespace anerg\OAuth2\Driver;

use anerg\helper\Http;

class alipay extends \anerg\OAuth2\OAuth
{
    const RSA_PRIVATE = 1;
    const RSA_PUBLIC  = 2;

    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $AuthorizeURL = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm';

    /**
     * 获取Access Token的api接口
     * @var type String
     */
    protected $AccessTokenURL = 'https://openapi.alipay.com/gateway.do';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://openapi.alipay.com/gateway.do';

    /**
     * 请求Authorize访问地址
     */
    public function getAuthorizeURL()
    {
        setcookie('A_S', $this->timestamp, $this->timestamp + 600, '/');
        $this->initConfig();
        //Oauth 标准参数
        $params = array(
            'app_id'       => $this->config['app_id'],
            'redirect_uri' => $this->config['callback'],
            'scope'        => $this->config['scope'],
            'state'        => $this->timestamp,
        );
        return $this->AuthorizeURL . '?' . http_build_query($params);
    }

    /**
     * 默认的AccessToken请求参数
     * @return type
     */
    protected function _params()
    {
        $params = array(
            'app_id'     => $this->config['app_id'],
            'method'     => 'alipay.system.oauth.token',
            'charset'    => 'UTF-8',
            'sign_type'  => 'RSA2',
            'timestamp'  => date("Y-m-d H:i:s"),
            'version'    => '1.0',
            'grant_type' => $this->config['grant_type'],
            'code'       => $_GET['auth_code'],
        );
        $params['sign'] = $this->signature($params);
        return $params;
    }

    /**
     * 支付宝签名
     */
    public function signature($data = [])
    {
        ksort($data);
        $str = $this->buildParams($data);

        $rsaKey = $this->getRsaKeyVal(self::RSA_PRIVATE);
        $res    = openssl_get_privatekey($rsaKey);
        $sign   = '';
        openssl_sign($str, $sign, $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        return base64_encode($sign);
    }

    public function buildParams($params, $urlencode = false)
    {
        $params    = array_filter($params);
        $param_str = '';
        foreach ($params as $k => $v) {
            if ($k == 'sign') {
                continue;
            }
            $param_str .= $k . '=';
            $param_str .= $urlencode ? urldecode($v) : $v;
            $param_str .= '&';
        }
        return rtrim($param_str, '&');
    }

    protected function getRsaKeyVal($type = self::RSA_PUBLIC)
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
            $rsa = file_get_contents($rsa, 'r');
        }
        if (empty($rsa)) {
            throw new \Exception('支付宝RSA密钥未配置');
        }
        $rsa    = str_replace([PHP_EOL, $header, $footer], '', $rsa);
        $rsaVal = $header . PHP_EOL . chunk_split($rsa, 64, PHP_EOL) . $footer;
        return $rsaVal;
    }

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    支付宝API方法
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'POST')
    {
        $params = array(
            'app_id'     => $this->config['app_id'],
            'method'     => $api,
            'charset'    => 'UTF-8',
            'sign_type'  => 'RSA2',
            'timestamp'  => date("Y-m-d H:i:s"),
            'version'    => '1.0',
            'auth_token' => $this->token['access_token'],
        );
        $params['sign'] = $this->signature($params);

        $data = Http::request($this->ApiBase, $params, $method);
        return json_decode($data, true);
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result)
    {
        $data = json_decode($result, true);
        $data = $data['alipay_system_oauth_token_response'];
        if ($data['access_token'] && $data['expires_in'] && $data['user_id']) {
            $data['openid'] = $data['user_id'];
            return $data;
        } else {
            throw new \Exception("获取支付宝 ACCESS_TOKEN 出错：{$result}");
        }
    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function openid()
    {
        $data = $this->token;
        if (isset($data['openid'])) {
            return $data['openid'];
        } else {
            throw new \Exception('没有获取到支付宝用户ID！');
        }

    }

    /**
     * 获取授权用户的用户信息
     */
    public function userinfo()
    {
        $rsp = $this->call('alipay.user.info.share');
        $rsp = $rsp['alipay_user_info_share_response'];
        if (!$rsp || (isset($rsp['code']) && $rsp['code'] != 10000)) {
            throw new \Exception('接口访问失败！' . $rsp['msg']);
        } else {
            $userinfo = array(
                'openid'  => $this->token['openid'],
                'channel' => 'alipay',
                'nick'    => $rsp['nick_name'],
                'gender'  => strtolower($rsp['gender']),
                'avatar'  => $rsp['avatar'],
            );
            return $userinfo;
        }
    }

    public function userinfo_all()
    {
        $rsp = $this->call('alipay.user.info.share');
        $rsp = $rsp['alipay_user_info_share_response'];
        if (!$rsp || (isset($rsp['code']) && $rsp['code'] != 10000)) {
            throw new \Exception('接口访问失败！' . $rsp['msg']);
        } else {
            return $rsp;
        }
    }

}
