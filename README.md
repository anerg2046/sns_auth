# 通用第三方登录

## 目前支持
- 微博登录（移动&PC版）
- QQ登录（移动&PC版）
- 移动版微信
- 网站微信扫码登录
>微信可获取unionid（如有）

## 安装方法
```
composer require anerg2046/sns_auth
```

>类库使用的命名空间为`\\anerg\\OAuth2`

## 典型用法
>以ThinkPHP5为例

```
<?php
namespace app\web\controller;

use anerg\OAuth2\OAuth;

class SnsLogin {

    public function qq() {
        $config = [
            'app_key'    => 'xxxxxx',
            'app_secret' => 'xxxxxxxxxxxxxxxxxxxx',
            'scope'      => 'get_user_info',
            'callback'   => [
                'default' => 'http://xxx.com/sns_login/callback/qq',
                'mobile'  => 'http://h5.xxx.com/sns_login/callback/qq',
            ]
        ];
        $OAuth  = OAuth::getInstance($config, 'qq');
        $OAuth->setDisplay('mobile');//此处为可选,若没有设置为mobile,则跳转的授权页面可能不适合手机浏览器访问
        return redirect($OAuth->getAuthorizeURL());
    }

    public function callback($channel) {
        $OAuth    = OAuth::getInstance($channel);
        $OAuth->getAccessToken();
        $sns_info = $OAuth->userinfo();
        /**
         * 此处获取了sns提供的用户数据
         * 你可以进行其他操作
         */
    }

}
```
