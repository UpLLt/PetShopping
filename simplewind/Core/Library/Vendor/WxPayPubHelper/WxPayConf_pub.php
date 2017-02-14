<?php

/**
 *    配置账号信息
 */
class WxPayConf_pub
{
    //=======【基本信息设置】=====================================
    //微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
    const APPID = 'wx13c1e5dd79282d30';
    //受理商ID，身份标识
    const MCHID = '1421100002';
    //商户支付密钥Key。审核通过后，在微信发送的邮件中查看
    const KEY = '49636c258b10b5260b39699ad495556e';
    //JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
    const APPSECRET = '6f5c48f04efb0c7ca26e829728d2ff68 ';

    //=======【JSAPI路径设置】===================================
    //获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
    const JS_API_CALL_URL = 'https://www.mixiupet.com/Notify/Wxpay/index';//'http://.../Admin/Order/pay';

    //=======【证书路径设置】=====================================
    //证书路径,注意应该填写绝对路径
    const SSLCERT_PATH = 'https://www.mixiupet.com/simplewind/Library/Vendor/WxPayPubHelper/cacert/apiclient_cert.pem';
    const SSLKEY_PATH = 'https://www.mixiupet.com/simplewind/Library/Vendor/WxPayPubHelper/cacert/apiclient_key.pem';

    //=======【异步通知url设置】===================================
    //异步通知url，商户根据实际开发过程设定
    const NOTIFY_URL = 'https://www.mixiupet.com/Notify/Wxpay/index';
    //充值回调
    const NOTIFY_RECHARGE_URL = 'https://www.mixiupet.com/Notify/WxRecharge/index';

    //=======【curl超时设置】===================================
    //本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
    const CURL_TIMEOUT = 30;
}

?>