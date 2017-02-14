<?php
if (file_exists("data/conf/db.php")) {
    $db = include "data/conf/db.php";
} else {
    $db = [];
}
if (file_exists("data/conf/config.php")) {
    $runtime_config = include "data/conf/config.php";
} else {
    $runtime_config = [];
}

if (file_exists("data/conf/route.php")) {
    $routes = include 'data/conf/route.php';
} else {
    $routes = [];
}

$configs = [
    "LOAD_EXT_FILE"        => "extend",
    'UPLOADPATH'           => 'data/upload/',
    //'SHOW_ERROR_MSG'        =>  true,    // 显示错误信息
    'SHOW_PAGE_TRACE'      => false,
    'TMPL_STRIP_SPACE'     => true,// 是否去除模板文件里面的html空格与换行
    'THIRD_UDER_ACCESS'    => false, //第三方用户是否有全部权限，没有则需绑定本地账号
    /* 标签库 */
    'TAGLIB_BUILD_IN'      => THINKCMF_CORE_TAGLIBS,
    'TMPL_DETECT_THEME'    => false,       // 自动侦测模板主题
    'TMPL_TEMPLATE_SUFFIX' => '.html',     // 默认模板文件后缀
    'DEFAULT_MODULE'       => 'Web',  // 默认模块
    'DEFAULT_CONTROLLER'   => 'Index', // 默认控制器名称
    'DEFAULT_ACTION'       => 'index', // 默认操作名称
    'DEFAULT_M_LAYER'      => 'Model', // 默认的模型层名称
    'DEFAULT_C_LAYER'      => 'Controller', // 默认的控制器层名称

    'DEFAULT_FILTER' => 'htmlspecialchars', // 默认参数过滤方法 用于I函数...htmlspecialchars

    'LANG_SWITCH_ON'       => true,   // 开启语言包功能
    'DEFAULT_LANG'         => 'zh-cn', // 默认语言
    'LANG_LIST'            => 'zh-cn,en-us,zh-tw',
    'LANG_AUTO_DETECT'     => true,
    'ADMIN_LANG_SWITCH_ON' => false,   // 后台开启语言包功能

    'VAR_MODULE'     => 'g',     // 默认模块获取变量
    'VAR_CONTROLLER' => 'm',    // 默认控制器获取变量
    'VAR_ACTION'     => 'a',    // 默认操作获取变量

    'APP_USE_NAMESPACE'  => true, // 关闭应用的命名空间定义
    'APP_AUTOLOAD_LAYER' => 'Controller,Model', // 模块自动加载的类库后缀

    'SP_TMPL_PATH'                 => 'themes/',       // 前台模板文件根目录
    'SP_DEFAULT_THEME'             => 'simplebootx',       // 前台模板文件
    'SP_TMPL_ACTION_ERROR'         => 'error', // 默认错误跳转对应的模板文件,注：相对于前台模板路径
    'SP_TMPL_ACTION_SUCCESS'       => 'success', // 默认成功跳转对应的模板文件,注：相对于前台模板路径
    'SP_ADMIN_STYLE'               => 'flat',
    'SP_ADMIN_TMPL_PATH'           => 'admin/themes/',       // 各个项目后台模板文件根目录
    'SP_ADMIN_DEFAULT_THEME'       => 'simplebootx',       // 各个项目后台模板文件
    'SP_ADMIN_TMPL_ACTION_ERROR'   => 'Admin/error.html', // 默认错误跳转对应的模板文件,注：相对于后台模板路径
    'SP_ADMIN_TMPL_ACTION_SUCCESS' => 'Admin/success.html', // 默认成功跳转对应的模板文件,注：相对于后台模板路径
    'TMPL_EXCEPTION_FILE'          => SITE_PATH . 'public/exception.html',

    'AUTOLOAD_NAMESPACE' => ['plugins' => './plugins/'], //扩展模块列表

    'ERROR_PAGE' => '',//不要设置，否则会让404变302

    'VAR_SESSION_ID' => 'session_id',

    "UCENTER_ENABLED"       => 0, //UCenter 开启1, 关闭0
    "COMMENT_NEED_CHECK"    => 0, //评论是否需审核 审核1，不审核0
    "COMMENT_TIME_INTERVAL" => 60, //评论时间间隔 单位s

    /* URL设置 */
    'URL_CASE_INSENSITIVE'  => true,   // 默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'             => 0,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式，提供最好的用户体验和SEO支持
    'URL_PATHINFO_DEPR'     => '/',    // PATHINFO模式下，各参数之间的分割符号
    'URL_HTML_SUFFIX'       => '',  // URL伪静态后缀设置

    'VAR_PAGE' => "p",

    'URL_ROUTER_ON'   => true,
    'URL_ROUTE_RULES' => $routes,

    /*性能优化*/
    'OUTPUT_ENCODE'   => true,// 页面压缩输出

    'HTML_CACHE_ON'    => false, // 开启静态缓存
    'HTML_CACHE_TIME'  => 60,   // 全局静态缓存有效期（秒）
    'HTML_FILE_SUFFIX' => '.html', // 设置静态缓存文件后缀

    'TMPL_PARSE_STRING' => [
        '__UPLOAD__'   => __ROOT__ . '/data/upload/',
        '__STATICS__'  => __ROOT__ . '/statics/',
        '__WEB_ROOT__' => __ROOT__,
    ],
    'PAGE_NUMBER'       => 30,

    'SMS_ACCOUNT' => [
        'userid'   => '1146',
        'account'  => 'hljkjhd',
        'password' => 'yhd2017nbsx',
        'company'  => '咪咻宠物',
    ],

    'REGISTER_CONTENT'=> "【咪咻】咪咻君拜见小主，恭迎小主加入我们咪粉大家庭。小主以后有任何需要尽管吩咐，有任何不满意的尽管吐槽。咪咻君24小时服务于小主，奉上咪咻家全国免费服务热线：400-135-1314。",
    'SEND_CONTENT'=>"【咪咻】伟大的小主，您购买的货物咪咻君已通过镖师从内务府发出，不日将抵达宫中，咪咻家全体臣子肝脑涂地只为小主能早日御用，微臣咪咻君敬上。小主若有疑问，可随时召告咪咻君，奉上咪咻家全国免费服务热线：400-135-1314.",
    'TIXIAN_CONTENT' => "【咪咻】小主大大，您的提现申请臣已接旨，臣必将竭尽全力为您处理，不日必定为小主传来捷报。小主如有任何疑问，随时联系咪咻君，奉上咪咻家全国免费服务热线：400-135-1314。",
    'BUYPET_CONTENT'=>"【咪咻】恭喜小主成功挑选到属于您的爱宠，贺喜小主即将成为一名合格的铲屎官。臣也必当赴汤蹈火为小主的养宠之路排忧解难，小主如有任何需求随时联系咪咻君，奉上咪咻家全国免费服务热线：400-135-1314",
    'YIMIAO_CONTENT'=>"【咪咻】您的宠物该打疫苗了",
    'QUCHONG_CONTENT'=>"【咪咻】您的宠物该驱虫了",

    'ALIPAY_CONFIG'      => [
        'partner'               => '2088521345765954',
        'seller_id'             => '2088521345765954',
        'key'                   => 'olnd6unj1vtaew2bpqzhiny8bw8ktkv2',
        'seller_email'          => '3539790873@qq.com',
        'sign_type'             => strtolower('RSA'),
        'input_charset'         => strtolower('utf-8'),
        'cacert'                => '',
        'rsa_private_key'       => getcwd() . '/simplewind/Core/Library/Vendor/Alipay/lib/rsa_private_key.pem',
        'rsa_public_key'        => getcwd() . '/simplewind/Core/Library/Vendor/Alipay/lib/rsa_public_key.pem',
        'rsa_private_key_pkcs8' => getcwd() . '/simplewind/Core/Library/Vendor/Alipay/lib/rsa_private_key_pkcs8.pem',
        'transport'             => 'http',
        'notify_url'            => 'https://www.mixiupet.com/Notify/Alipay/index', //支付回调地址
        'notify_recharge_url'   => 'https://www.mixiupet.com/Notify/AlipayRecharge/index', //充值回调地址
        'return_url'            => 'm.alipay.com',
        'ali_public_key'        => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB',
    ],


    //阿里极速快递查询
    'ALI_JISUKDCX_KEY'   => [
        'AppKey'    => '23564236',
        'AppSecret' => '3b2ff7c4c29afbe65e2fb1b74c0c9fec',
        'AppCode'   => '4e623e4aec46429d8aef39a60ce9ef89',
    ],

    //医疗、殡葬、运输默认图片域名
    'DEFAULT_YUNSHU_URL' => 'https://www.mixiupet.com/public/images/yunshu.png',
    'DEFAULT_BINYI_URL'  => 'https://www.mixiupet.com/public/images/binyi.png',
    'DEFAULT_JIYANG_URL' => 'https://www.mixiupet.com/public/images/jiyang.png',

    'MODULE_ALLOW_LIST' =>
        [
            'Finance',
            'Merchant',
            'Web',
            'Wap',
            'Purchase',
            'Foster',
            'Marriage',
            'Funeral',
            'Category',
            'Issue',
            'Community',
            'Admin',
            'Portal',
            'Asset',
            'Api',
            'User',
            'Wx',
            'Comment',
            'Qiushi',
            'Tpl',
            'Topic',
            'Install',
            'Bug',
            'Better',
            'Pay',
            'Cas',
            'APP',
            'Advertisement',
            'Consumer',
            'Appapi',
            'Commodity',
            'Transport',
            'Store',
            'Notify',
        ],
];

return array_merge($configs, $db, $runtime_config);
