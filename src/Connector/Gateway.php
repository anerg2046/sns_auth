<?php
namespace anerg\OAuth2\Connector;

use anerg\OAuth2\Connector\GatewayInterface;

/**
 * 所有第三方登录必须继承的抽象类
 */
abstract class Gateway implements GatewayInterface
{
    /**
     * 配置参数
     * @var array
     */
    protected $config;

    /**
     * 当前时间戳
     * @var int
     */
    protected $timestamp;

    /**
     * 默认第三方授权页面样式
     * @var string
     */
    protected $display = 'default';

    public function __construct($config = null)
    {
        if (!$config) {
            throw new \Exception('传入的配置不能为空');
        }
        //默认参数
        $_config = [
            'app_id'        => '',
            'app_secret'    => '',
            'callback'      => '',
            'response_type' => 'code',
            'grant_type'    => 'authorization_code',
            'proxy'         => '',
        ];
        $this->config    = array_merge($_config, $config);
        $this->timestamp = time();
    }

    /**
     * 设置授权页面样式
     *
     * @param string $display
     * @return self
     */
    public function setDisplay($display)
    {
        $this->display = $display;
        return $this;
    }

    public function GET($url, $params, $headers)
    {
        $client   = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url, ['proxy' => $this->config['proxy'], 'headers' => $headers, 'query' => $param]);
        return $response->getBody()->getContents();
    }
}
