<?php

/**
 * QQ登陆Api
 *
 * @author Coeus <r.anerg@gmail.com>
 */

namespace anerg\OAuth2\Driver;

use anerg\OAuth2\Util\Http;

class qq extends \anerg\OAuth2 {

    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $AuthorizeURL = 'https://graph.qq.com/oauth2.0/authorize';

    /**
     * 获取Access Token的api接口
     * @var type String
     */
    protected $AccessTokenURL = 'https://graph.qq.com/oauth2.0/token';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://graph.qq.com/';

    /**
     * 请求Authorize访问地址
     */
    public function getAuthorizeURL() {
        setcookie('A_S', NOW_TIME, 600);
        $this->initConfig();
        //Oauth 标准参数
        $params = array(
            'response_type' => $this->config['response_type'],
            'client_id'     => $this->config['app_key'],
            'redirect_uri'  => $this->config['callback'],
            'state'         => NOW_TIME,
            'scope'         => $this->config['scope'],
            'display'       => $this->Display
        );
        return $this->AuthorizeURL . '?' . http_build_query($params);
    }

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    微博API
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'GET') {
        /* 腾讯QQ调用公共参数 */
        $params = array(
            'oauth_consumer_key' => $this->config['app_key'],
            'access_token'       => $this->token['access_token'],
            'openid'             => $this->openid(),
            'format'             => 'json'
        );

        $data = Http::request($this->url($api), $this->param($params, $param), $method);
        return json_decode($data, true);
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result) {
        parse_str($result, $data);
        if ($data['access_token'] && $data['expires_in']) {
            $this->token    = $data;
            $data['openid'] = $this->openid();
            return $data;
        } else {
            throw new Exception("获取腾讯QQ ACCESS_TOKEN 出错：{$result}");
        }
    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function openid() {
        $data = $this->token;
        if (isset($data['openid'])) {
            return $data['openid'];
        } elseif ($data['access_token']) {
            $data = Http::get($this->url('oauth2.0/me'), array('access_token' => $data['access_token']));
            $data = json_decode(trim(substr($data, 9), " );\n"), true);
            if (isset($data['openid'])) {
                return $data['openid'];
            } else {
                throw new Exception("获取用户openid出错：{$data['error_description']}");
            }
        } else {
            throw new Exception('没有获取到openid！');
        }
    }

    /**
     * 获取授权用户的用户信息
     */
    public function userinfo() {
        $rsp = $this->call('user/get_user_info');
        if (!$rsp || $rsp['ret'] != 0) {
            throw new Exception('接口访问失败！' . $rsp['msg']);
        } else {
            $userinfo = array(
                'openid'  => $this->openid(),
                'channel' => 'qq',
                'nick'    => $rsp['nickname']
            );
            return $userinfo;
        }
    }

}
