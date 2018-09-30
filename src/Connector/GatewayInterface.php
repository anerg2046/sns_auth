<?php
namespace anerg\OAuth2\Connector;

/**
 * 所有第三方登录必须支持的接口方法
 */
interface GatewayInterface
{

    /**
     * 得到跳转地址
     */
    public function getRedirectUrl();

    /**
     * 获取当前授权用户的openid标识
     */
    public function openid();

    /**
     * 获取格式化后的用户信息
     */
    public function userinfo();

    /**
     * 获取原始接口返回的用户信息
     */
    public function userinfoRaw();
}
