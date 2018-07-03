# 通用第三方登录

## 目前支持
- 微博登录（移动&PC版）
- QQ登录（移动&PC版）
- 移动版微信
- 网站微信扫码登录

>微信可获取unionid（如有）

## update 2018-07-03
*现在支持支付宝登录

```php
//支付宝配置参数
$config = [
    'app_id'      => '********',
    'scope'       => 'auth_user',
    'pem_private' => 'pathto/private.pem', // 你的私钥
    'pem_public'  => 'pathto/public.pem', // 支付宝公钥
    'callback'    => [
        'default' => 'http://user.abc.cn/sns/alipay',
        'mobile'  => 'http://user.abc.cn/sns/alipay',
        ],
    ];
```

## update 2018-06-28
**现在支持微信公众号多域名登录**

## 安装方法
```
composer require anerg2046/sns_auth
```

>类库使用的命名空间为`\\anerg\\OAuth2`

## 典型用法
>以ThinkPHP5为例

```php
<?php

namespace app\web\controller;

use anerg\OAuth2\OAuth;

class SnsLogin {

    /**
     * 此处应当考虑使用空控制器来简化代码
     * 同时应当考虑对第三方渠道名称进行检查
     * $config配置参数应当放在配置文件中
     * callback对应了普通PC版的返回页面和移动版的页面
     */
    public function qq() {
        $config = [
            'app_id'    => 'xxxxxx',
            'app_secret' => 'xxxxxxxxxxxxxxxxxxxx',
            'scope'      => 'get_user_info',
            'callback'   => [
                'default' => 'http://xxx.com/sns_login/callback/qq',
                'mobile'  => 'http://h5.xxx.com/sns_login/callback/qq',
            ]
        ];
        $OAuth  = OAuth::getInstance($config, 'qq');
        $OAuth->setDisplay('mobile'); //此处为可选,若没有设置为mobile,则跳转的授权页面可能不适合手机浏览器访问
        return redirect($OAuth->getAuthorizeURL());
    }

    public function callback($channel) {
        $config   = [
            'app_id'    => 'xxxxxx',
            'app_secret' => 'xxxxxxxxxxxxxxxxxxxx',
            'scope'      => 'get_user_info',
            'callback'   => [
                'default' => 'http://xxx.com/sns_login/callback/qq',
                'mobile'  => 'http://h5.xxx.com/sns_login/callback/qq',
            ]
        ];
        $OAuth    = OAuth::getInstance($config, $channel);
        $OAuth->getAccessToken();
        /**
         * 在获取access_token的时候可以考虑忽略你传递的state参数
         * 此参数使用cookie保存并验证
         */
//        $ignore_stat = true;
//        $OAuth->getAccessToken(true);
        $sns_info = $OAuth->userinfo();
        /**
         * 此处获取了sns提供的用户数据
         * 你可以进行其他操作
         */
    }

}
```

## 新增微信登录代理用法

>本方式解决了微信公众号登录只能设定一个回调域名的问题

### 使用方法

* 将wx_proxy.php放置在微信公众号设定的回调域名某个地址，如 http://www.abc.com/proxy/wx_proxy.php
* config中加入配置参数proxy_url，地址为 http://www.abc.com/proxy/wx_proxy.php
* 跳转的时候直接跳转到 $OAuth->getProxyURL($config['proxy_url']) 返回的地址即可

```php
    //其他代码略
    $config = [
        'app_id'    => 'xxxxxx',
        'app_secret' => 'xxxxxxxxxxxxxxxxxxxx',
        'scope'      => 'snsapi_base',
        'proxy_url'  => 'http://www.abc.com/proxy/wx_proxy.php',
        'callback'   => [
            'default' => 'http://xxx.com/sns_login/callback/qq',
            'mobile'  => 'http://h5.xxx.com/sns_login/callback/qq',
        ]
    ];

    $OAuth = OAuth::getInstance($config, 'weixin');
    $OAuth->setDisplay('mobile');
    return redirect($OAuth->getProxyURL($config['proxy_url']));
    //其他代码略
…………
```
