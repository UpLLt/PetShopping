<?php
return [
    //'配置项'=>'配置值'
    'URL_MODEL'  => 2,
    //取消模板
    'DEFAULT_THEME' => '',

    //微信配置参数
    'WEIXINPAY_CONFIG'       => array(
        'APPID'              => 'wx13c1e5dd79282d30', // 微信支付APPID
        'MCHID'              => '1421100002', // 微信支付MCHID 商户收款账号
        'KEY'                => '49636c258b10b5260b39699ad495556e', // 微信支付KEY
        'APPSECRET'          => '6f5c48f04efb0c7ca26e829728d2ff68', // 公众帐号secert (公众号支付专用)
        'NOTIFY_URL'         => 'https://www.mixiupet.com/Notify/Wxpay/PCnotify', // 接收支付状态的连接
        'NOTIFY_RECHARGE_URL'=> 'https://www.mixiupet.com/Notify/WxRecharge/pcindex',
    ),



    //支付宝配置参数
    'alipay_config'=>array(
        'partner' =>'2088521345765954',   //这里是你在成功申请支付宝接口后获取到的PID；
        'seller_id' => '2088521345765954',
        'key'=>'olnd6unj1vtaew2bpqzhiny8bw8ktkv2',//这里是你在成功申请支付宝接口后获取到的Key
        'sign_type'=>strtoupper('MD5'),
        'input_charset'=> strtolower('utf-8'),
        'cacert'=> getcwd().'\\cacert.pem',
        'transport'=> 'http',
    ),
    //以上配置项，是从接口包中alipay.config.php 文件中复制过来，进行配置；

    'alipay'   =>array(
        //这里是卖家的支付宝账号，也就是你申请接口时注册的支付宝账号
        'seller_email'=>'3539790873@qq.com',
        //这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
        'notify_url'=>'https://www.mixiupet.com/Web/Alipay/notifyurl',
        'notify_recharge'=>'https://www.mixiupet.com/Web/Alipay/notifyurlrecharge',
        //这里是页面跳转通知url，提交到项目的Pay控制器的returnurl方法；
        'return_url'=>'https://www.mixiupet.com/Web/Alipay/returnurl',
        'return_url_recharge'=>'https://www.mixiupet.com/Web/Asset/wallet',
        //支付成功跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参payed（已支付列表）
        'successpage'=>'https://www.mixiupet.com/Web/Pay/paysuccess',
        //支付失败跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参unpay（未支付列表）
        'errorpage'=>'https://www.mixiupet.com//Web/Index/index',
    ),


];