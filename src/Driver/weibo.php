<?php

/**
 * 新浪微博登陆Api
 *
 * @author Coeus <r.anerg@gmail.com>
 */

namespace anerg\OAuth2\Driver;

use anerg\OAuth2\Util\Http;

class weibo extends \anerg\OAuth2\OAuth {

    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $AuthorizeURL = 'https://api.weibo.com/oauth2/authorize';

    /**
     * 获取Access Token的api接口
     * @var type String
     */
    protected $AccessTokenURL = 'https://api.weibo.com/oauth2/access_token';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://api.weibo.com/2/';

    public function getAuthorizeURL() {
        setcookie('A_S', NOW_TIME, 600);
        $this->initConfig();
        //Oauth 标准参数
        $params = array(
            'client_id'    => $this->config['app_key'],
            'redirect_uri' => $this->config['callback'],
            'state'        => NOW_TIME,
            'scope'        => $this->config['scope'],
            'display'      => $this->display
        );
        return $this->AuthorizeURL . '?' . http_build_query($params);
    }

    protected function initConfig() {
        parent::initConfig();
        if ($this->display == 'mobile') {
            $this->AuthorizeURL = 'https://open.weibo.cn/oauth2/authorize';
        }
    }

    public function call($api, $param = '', $method = 'GET') {
        /* 腾讯QQ调用公共参数 */
        $params = array(
            'access_token' => $this->token['access_token'],
        );

        $data = Http::request($this->url($api, '.json'), $this->param($params, $param), $method);
        return json_decode($data, true);
    }

    protected function parseToken($result) {
        $data = json_decode($result, true);
        if ($data['access_token'] && $data['expires_in'] && $data['remind_in'] && $data['uid']) {
            $data['openid'] = $data['uid'];
            unset($data['uid']);
            return $data;
        } else {
            throw new Exception("获取新浪微博ACCESS_TOKEN出错：{$data['error']}");
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
            throw new Exception('没有获取到新浪微博用户ID！');
    }

    /**
     * 获取授权用户的用户信息
     */
    public function userinfo() {
        $rsp = $this->call('users/show', 'uid=' . $this->openid());
        if (isset($rsp['error_code'])) {
            throw new Exception('接口访问失败！' . $rsp['error']);
        } else {
            $userinfo = array(
                'openid'  => $this->openid(),
                'channel' => 'weibo',
                'nick'    => $rsp['screen_name'],
                'gender'  => $rsp['gender']
            );
            return $userinfo;
        }
    }

}
