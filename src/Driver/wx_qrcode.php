<?php

/**
 * 微信网站扫码登陆Api
 *
 * @author Coeus <r.anerg@gmail.com>
 */

namespace anerg\OAuth2\Driver;

use anerg\OAuth2\Util\Http;

class wx_qrcode extends \anerg\OAuth2\OAuth {

    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $AuthorizeURL = 'https://open.weixin.qq.com/connect/qrconnect';

    /**
     * 获取Access Token的api接口
     * @var type String
     */
    protected $AccessTokenURL = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://api.weixin.qq.com/sns/';

    /**
     * 请求Authorize访问地址
     */
    public function getAuthorizeURL() {
        setcookie('A_S', $this->timestamp, $this->timestamp + 600, '/');
        $this->initConfig();
        //Oauth 标准参数
        $params = array(
            'appid'         => $this->config['app_key'],
            'redirect_uri'  => $this->config['callback'],
            'response_type' => $this->config['response_type'],
            'scope'         => $this->config['scope'],
            'state'         => $this->timestamp,
        );
        return $this->AuthorizeURL . '?' . http_build_query($params) . '#wechat_redirect';
    }

    /**
     * 默认的AccessToken请求参数
     * @return type
     */
    protected function _params() {
        $params = array(
            'appid'      => $this->config['app_key'],
            'secret'     => $this->config['app_secret'],
            'grant_type' => $this->config['grant_type'],
            'code'       => $_GET['code'],
        );
        return $params;
    }

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    微博API
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'GET') {
        /* 微信调用公共参数 */
        $params = array(
            'access_token' => $this->token['access_token'],
            'openid'       => $this->openid(),
            'lang'         => 'zh_CN'
        );

        $data = Http::request($this->url($api), $params, $method);
        return json_decode($data, true);
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result) {
        $data = json_decode($result, true);
        if ($data['access_token'] && $data['expires_in'] && $data['openid']) {
            return $data;
        } else {
            throw new Exception("获取微信 ACCESS_TOKEN 出错：{$result}");
        }
    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function openid() {
        $data = $this->token;
        if (isset($data['openid']))
            return $data['openid'];
        else
            throw new Exception('没有获取到微信用户ID！');
    }

    /**
     * 获取授权用户的用户信息
     */
    public function userinfo() {
        $rsp = $this->call('userinfo');
        if (!$rsp || (isset($rsp['errcode']) && $rsp['errcode'] != 0)) {
            throw new Exception('接口访问失败！' . $rsp['errmsg']);
        } else {
            $userinfo = array(
                'openid'  => $this->token['openid'],
                'unionid' => isset($this->token['unionid']) ? $this->token['unionid'] : '',
                'channel' => 'weixin_qrcode',
                'nick'    => $rsp['nickname'],
                'gender'  => $rsp['sex'] == 1 ? 'm' : 'f'
            );
            return $userinfo;
        }
    }

    public function userinfo_all() {
        $rsp = $this->call('userinfo');
        if (!$rsp || (isset($rsp['errcode']) && $rsp['errcode'] != 0)) {
            throw new Exception('接口访问失败！' . $rsp['errmsg']);
        } else {
            return $rsp;
        }
    }

}
