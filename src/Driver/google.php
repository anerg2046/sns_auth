<?php

namespace anerg\OAuth2\Driver;

class google extends \anerg\OAuth2\OAuth
{
    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $AuthorizeURL = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * 获取Access Token的api接口
     * @var type String
     */
    protected $AccessTokenURL = 'https://www.googleapis.com/oauth2/v4/token';

    /**
     * 请求Authorize访问地址
     */
    public function getAuthorizeURL()
    {
        setcookie('A_S', $this->timestamp, $this->timestamp + 600, '/');
        $this->initConfig();
        //Oauth 标准参数
        $params = array(
            'client_id'     => $this->config['app_id'],
            'redirect_uri'  => $this->config['callback'],
            'response_type' => $this->config['response_type'],
            'scope'         => $this->config['scope'],
            'state'         => $this->timestamp,
        );
        return $this->AuthorizeURL . '?' . http_build_query($params);
    }

    /**
     * 默认的AccessToken请求参数
     * @return type
     */
    protected function _params($code = null)
    {
        $params = array(
            'client_id'     => $this->config['app_id'],
            'client_secret' => $this->config['app_secret'],
            'grant_type'    => $this->config['grant_type'],
            'redirect_uri'  => $this->config['callback'],
            'code'          => is_null($code) ? $_GET['code'] : $code,
        );
        return $params;
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result)
    {
        $data = json_decode($result, true);
        if (isset($data['access_token']) && isset($data['expires_in'])) {
            return $data;
        } else {
            exception("获取谷歌 ACCESS_TOKEN 出错：{$result}");
        }
    }

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    微博API
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'GET')
    {
        /* 谷歌调用公共参数 */
        $params = [
            'access_token' => $this->token['access_token'],
        ];

        $data = Http::request($this->url($api), $this->param($params, $param), $method);
        print_r(json_decode($data, true));
        return json_decode($data, true);
    }

    /**
     * 获取授权用户的用户信息
     */
    public function userinfo()
    {
        $rsp = $this->call('profile');
        if (!$rsp || (isset($rsp['errcode']) && $rsp['errcode'] != 0)) {
            exception('接口访问失败！' . $rsp['errmsg']);
        } else {
            $userinfo = array(
                'openid'  => $this->token['openid'],
                'unionid' => isset($this->token['unionid']) ? $this->token['unionid'] : '',
                'channel' => 'weixin',
                'nick'    => $rsp['nickname'],
                'gender'  => $this->getGender($rsp['sex']),
                'avatar'  => $rsp['headimgurl'],
            );
            return $userinfo;
        }
    }
}
