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
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://www.googleapis.com/';

    /**
     * 请求Authorize访问地址
     */
    public function getAuthorizeURL()
    {
        setcookie('A_S', $this->timestamp, $this->timestamp + 600, '/');
        $this->initConfig();
        //Oauth 标准参数
        $params = [
            'client_id'     => $this->config['app_id'],
            'redirect_uri'  => $this->config['callback'],
            'response_type' => $this->config['response_type'],
            'scope'         => $this->config['scope'],
            'state'         => $this->timestamp,
        ];
        return $this->AuthorizeURL . '?' . http_build_query($params);
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
            throw new \Exception("获取谷歌 ACCESS_TOKEN 出错：{$result}");
        }
    }

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    微博API
     * @param  string $param  调用API的额外参数
     * @return json
     */
    public function call($api, $param = '')
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->token['access_token'],
        ];

        $client   = new \GuzzleHttp\Client();
        $response = $client->request('GET', $this->url($api), ['headers' => $headers]);
        $data     = $response->getBody()->getContents();

        return json_decode($data, true);
    }

    /**
     * 获取授权用户的用户信息
     *
     * @return array
     */
    public function userinfo()
    {
        $rsp = $this->call('oauth2/v2/userinfo');
        if (!$rsp || !isset($rsp['id'])) {
            throw new \Exception('接口访问失败！' . json_encode($rsp));
        } else {
            $userinfo = [
                'openid'  => $rsp['id'],
                'channel' => 'google',
                'nick'    => $rsp['name'],
                'gender'  => $this->getGender($rsp['gender']),
                'avatar'  => $rsp['picture'],
            ];
            return $userinfo;
        }
    }

    /**
     * 获取原始用户信息
     *
     * @return array
     */
    public function userinfoRaw()
    {
        $rsp = $this->call('oauth2/v2/userinfo');
        if (!$rsp || !isset($rsp['id'])) {
            throw new \Exception('接口访问失败！' . json_encode($rsp));
        } else {
            return $rsp;
        }
    }

    /**
     * 获取用户的openid
     *
     * @return string
     */
    public function openid()
    {
        $userinfo = $this->userinfo();
        return $userinfo['openid'];
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
