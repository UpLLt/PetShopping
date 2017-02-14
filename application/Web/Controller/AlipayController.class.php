<?php
namespace Web\Controller;

use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Common\Model\SmslogModel;
use Common\Model\NotifyModel;
use Common\Model\RechargeModel;

use Think\Controller;
use Think\Log;

/**
 * 支付宝支付
 * Class IndexController
 * @package Web\Controller
 */

class AlipayController extends RechargeBaseController
{

    private $order_model ,$order_product_model ,$smslog_model , $product_model ,$product_option_model;

    public function __construct()
    {
        parent::__construct();
        $this->_initialize();
        $this->order_model = new OrderModel();
        $this->order_product_model = new OrderProductModel();
        $this->smslog_model = new SmslogModel();
        $this->product_model = new ProductModel();
        $this->product_option_model = new ProductOptionModel();


    }

    public function _initialize()
    {
        vendor('Alipay_PC.Corefunction');
        vendor('Alipay_PC.Md5function');
        vendor('Alipay_PC.Notify');
        vendor('Alipay_PC.Submit');

    }

    //doalipay方法
    /*该方法其实就是将接口文件包下alipayapi.php的内容复制过来
      然后进行相关处理
    */
    public function doalipay()
    {
        $mid = session('mid');
        $this->is_login();
        $order_id = I('post.order_id');

        $order_data = $this->order_model->where(['id' => $order_id, 'mid' => $mid])->find();
        if (!$order_data) exit($this->error( '订单不存在'));

        if ($order_data['status'] != OrderModel::STATUS_WAIT_FOR_PAY) {
            exit($this->error( '订单已支付或已取消'));
        }



        $out_trade_no = $order_data['order_sn'];
        $money_all = $order_data['order_price'];
        /*********************************************************
         * 把alipayapi.php中复制过来的如下两段代码去掉，
         * 第一段是引入配置项，
         * 第二段是引入submit.class.php这个类。
         * 为什么要去掉？？
         * 第一，配置项的内容已经在项目的Config.php文件中进行了配置，我们只需用C函数进行调用即可；
         * 第二，这里调用的submit.class.php类库我们已经在PayAction的_initialize()中已经引入；所以这里不再需要；
         *****************************************************/
        // require_once("alipay.config.php");
        // require_once("lib/alipay_submit.class.php");

        //这里我们通过TP的C函数把配置项参数读出，赋给$alipay_config；
        $alipay_config = C('alipay_config');
        /**************************请求参数**************************/
        $payment_type = "1"; //支付类型 //必填，不能修改
        $notify_url = C('alipay.notify_url'); //服务器异步通知页面路径
        $return_url = C('alipay.return_url'); //页面跳转同步通知页面路径
        $seller_email = C('alipay.seller_email');//卖家支付宝帐户必填
        $out_trade_no = $out_trade_no;//商户订单号 通过支付页面的表单进行传递，注意要唯一！
        $subject = "咪咻";  //订单名称 //必填 通过支付页面的表单进行传递
        $total_fee = $money_all;   //付款金额  //必填 通过支付页面的表单进行传递
        $body = "咪咻";  //订单描述 通过支付页面的表单进行传递
        $show_url = U('Index/index');  //商品展示地址 通过支付页面的表单进行传递
        $anti_phishing_key = "";//防钓鱼时间戳 //若要使用请调用类文件submit中的query_timestamp函数
        $exter_invoke_ip = get_client_ip(); //客户端的IP地址
        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "create_direct_pay_by_user",
            "partner" => trim($alipay_config['partner']),
            "payment_type" => $payment_type,
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "seller_email" => $seller_email,
            "out_trade_no" => $out_trade_no,
            "subject" => $subject,
            "total_fee" => $total_fee,
            "body" => $body,
            "show_url" => $show_url,
            "anti_phishing_key" => $anti_phishing_key,
            "exter_invoke_ip" => $exter_invoke_ip,
            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
        );
        //建立请求
        $alipaySubmit = new  \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "post", "确认");
        echo $html_text;
    }





    /******************************
     * 服务器异步通知页面方法
     * 其实这里就是将notify_url.php文件中的代码复制过来进行处理
     *******************************/
    function notifyurl()
    {

        /*
        同理去掉以下两句代码；
        */
        //require_once("alipay.config.php");
        //require_once("lib/alipay_notify.class.php");

        //这里还是通过C函数来读取配置项，赋值给$alipay_config
        $alipay_config = C('alipay_config');
        //计算得出通知验证结果
        $alipayNotify = new  \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if ($verify_result) {
            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $out_trade_no = $_POST['out_trade_no'];      //商户订单号
            $trade_no = $_POST['trade_no'];          //支付宝交易号
            $trade_status = $_POST['trade_status'];      //交易状态
            $total_fee = $_POST['total_fee'];         //交易金额
            $notify_id = $_POST['notify_id'];         //通知校验ID。
            $notify_time = $_POST['notify_time'];       //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
            $buyer_email = $_POST['buyer_email'];       //买家支付宝帐号；
            $parameter = array(
                "out_trade_no" => $out_trade_no, //商户订单编号；
                "trade_no" => $trade_no,     //支付宝交易号；
                "total_fee" => $total_fee,    //交易金额；
                "trade_status" => $trade_status, //交易状态
                "notify_id" => $notify_id,    //通知校验ID。
                "notify_time" => $notify_time,  //通知的发送时间。
                "buyer_email" => $buyer_email,  //买家支付宝帐号；
            );
            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                //
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                $order_sn = $out_trade_no; //订单号
                $order = $this->order_model->where(['order_sn' => $order_sn])->find();
                $data = [
                    'status' => OrderModel::STATUS_PAY_SUCCESS,
                    'pay_type' => OrderModel::PAY_TYPE_ALIPAY,
                    'pay_time' => time(),
                ];
                $result = $this->order_model->where(['order_sn' => $order_sn])->save($data);
                if($order['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {
                    $info = $this->order_model
                        ->alias('a')
                        ->join('LEFT JOIN ego_hospital as b ON a.id = b.order_sid')
                        ->join('LEFT JOIN ego_hospital_shop as c ON b.hid = c.id')
                        ->where(array('a.id' => $order['id']))
                        ->field('c.sid')
                        ->find();
                    $rst = M('member_shop')
                        ->where(array('id' => $info['sid']))
                        ->setInc('balance', $order['order_price']);

                }

                if( $order['order_type'] == OrderModel::ORDER_TYPE_PET ){

                    $username = $this->order_model
                        ->where(['ego_order.id'=>$order['id']])
                        ->join('LEFT JOIN ego_member on ego_member.id = ego_order.mid')
                        ->field('ego_order.*,ego_member.username')
                        ->find();


                    $content = C('BUYPET_CONTENT');
                    $data = [
                        'content'     => $content,
                        'mobile'      => $username['username'],
                        'create_time' => time(),
                        'end_time'    => time()
                    ];
                    vendor("Cxsms.Cxsms");
                    $options = C('SMS_ACCOUNT');

                    $Cxsms = new \Cxsms($options);
                    $result = $Cxsms->send($username['username'], $content);
                    if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                        $this->smslog_model->add($data);
                    }
                }


                //如果是商品，修改库存  TODO
                if ($order['order_type'] == OrderModel::ORDER_TYPE_GOODS) {
                    $this->product_change($order['id']);
                }
                if( $order['order_type'] == OrderModel::ORDER_TYPE_PET ){

                    $username = $this->order_model
                        ->where(['ego_order.id'=>$order['id']])
                        ->join('LEFT JOIN ego_member on ego_member.id = ego_order.mid')
                        ->field('ego_order.*,ego_member.username')
                        ->find();


                    $content = C('BUYPET_CONTENT');
                    $data = [
                        'content'     => $content,
                        'mobile'      => $username['username'],
                        'create_time' => time(),
                        'end_time'    => time()
                    ];
                    vendor("Cxsms.Cxsms");
                    $options = C('SMS_ACCOUNT');

                    $Cxsms = new \Cxsms($options);
                    $result = $Cxsms->send($username['username'], $content);
                    if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                        $this->smslog_model->add($data);
                    }
                }

                if($order['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {
                    $info = $this->order_model
                        ->alias('a')
                        ->join('LEFT JOIN ego_hospital as b ON a.id = b.order_sid')
                        ->join('LEFT JOIN ego_hospital_shop as c ON b.hid = c.id')
                        ->where(array('a.id' => $order['id']))
                        ->field('c.sid')
                        ->find();
                    $rst = M('member_shop')
                        ->where(array('id' => $info['sid']))
                        ->setInc('balance', $order['order_price']);
                    $data_s = [
                        'status' => OrderModel::STATUS_COMPLETE,
                    ];
                    $result = $this->order_model->where(['order_sn' => $order_sn])->save($data_s);
                    /*if(!$rst) {
                        $iscommit = false;
                    }*/
                }
            }
            echo "success";        //请不要修改或删除
        } else {
            //验证失败
            echo "fail";
        }
    }

    /******************************
     * 服务器异步通知页面方法
     * 其实这里就是将notify_url.php文件中的代码复制过来进行处理
     *******************************/
    function notifyurlrecharge()
    {
        /*
        同理去掉以下两句代码；
        */
        //require_once("alipay.config.php");
        //require_once("lib/alipay_notify.class.php");

        //这里还是通过C函数来读取配置项，赋值给$alipay_config
        $alipay_config = C('alipay_config');
        //计算得出通知验证结果
        $alipayNotify = new  \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if(!$verify_result) Log::record('支付宝回调错误'.$verify_result,Log::WARN);
        if ($verify_result) {
            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $out_trade_no = $_POST['out_trade_no'];      //商户订单号
            $trade_no = $_POST['trade_no'];          //支付宝交易号
            $trade_status = $_POST['trade_status'];      //交易状态
            $total_fee = $_POST['total_fee'];         //交易金额
            $notify_id = $_POST['notify_id'];         //通知校验ID。
            $notify_time = $_POST['notify_time'];       //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
            $buyer_email = $_POST['buyer_email'];       //买家支付宝帐号；
            $parameter = array(
                "out_trade_no" => $out_trade_no, //商户订单编号；
                "trade_no" => $trade_no,     //支付宝交易号；
                "total_fee" => $total_fee,    //交易金额；
                "trade_status" => $trade_status, //交易状态
                "notify_id" => $notify_id,    //通知校验ID。
                "notify_time" => $notify_time,  //通知的发送时间。
                "buyer_email" => $buyer_email,  //买家支付宝帐号；
            );
            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                //
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {


                //订单状态和金额检查
                if (!$this->chechValidity($out_trade_no, $total_fee)) {
                    //错误日志
                    Log::record('充值检测异常,订单号为:' . $out_trade_no . '/n', Log::WARN);
                    exit;
                }

                //钱包金额变动
                if (!$this->compute(RechargeModel::PAY_TYPE_ALIPAY, json_encode($_POST))) {
                    Log::record('alipay充值:' . json_encode($_POST), Log::WARN);
                    exit;
                }
            }
            echo "success";        //请不要修改或删除
        } else {
            //验证失败
            echo "fail";
        }
    }




    /*
        页面跳转处理方法；
        这里其实就是将return_url.php这个文件中的代码复制过来，进行处理；
        */
    function returnurl()
    {

        //头部的处理跟上面两个方法一样，这里不罗嗦了！
        $alipay_config = C('alipay_config');
//        dump($alipay_config);exit;

        $alipayNotify = new  \ AlipayNotify($alipay_config);//计算得出通知验证结果
        $verify_result = $alipayNotify->verifyReturn();

            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            $out_trade_no = $_GET['out_trade_no'];      //商户订单号
            $trade_no = $_GET['trade_no'];          //支付宝交易号
            $trade_status = $_GET['trade_status'];      //交易状态
            $total_fee = $_GET['total_fee'];         //交易金额
            $notify_id = $_GET['notify_id'];         //通知校验ID。
            $notify_time = $_GET['notify_time'];       //通知的发送时间。
            $buyer_email = $_GET['buyer_email'];       //买家支付宝帐号；

            $parameter = array(
                "out_trade_no" => $out_trade_no,      //商户订单编号；
                "trade_no" => $trade_no,          //支付宝交易号；
                "total_fee" => $total_fee,         //交易金额；
                "trade_status" => $trade_status,      //交易状态
                "notify_id" => $notify_id,         //通知校验ID。
                "notify_time" => $notify_time,       //通知的发送时间。
                "buyer_email" => $buyer_email,       //买家支付宝帐号
            );

            if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {

                redirect(C('alipay.successpage'));//跳转到配置项中配置的支付成功页面；

            } else {
                echo "trade_status=" . $_GET['trade_status'];
                redirect(C('alipay.errorpage'));//跳转到配置项中配置的支付失败页面；
            }


    }


    /**
     * 商品
     * 库存变更
     * 销量变更
     */
    public function product_change($order_id)
    {
        if (!$order_id) return false;
        $order_data = $this->order_model->find($order_id);

        $order_product_data = $this->order_product_model->where(['order_id' => $order_data['id']])->select();

        foreach ($order_product_data as $k => $v) {
            $v['snapshot'] = json_decode($v['snapshot'], true);
            $this->product_model
                ->where(['id' => $v['product_id']])
                ->save(['sales_volume' => ['exp', 'sales_volume+' . $v['quantity']]]);
            $this->product_option_model
                ->where(['option_key_id' => $v['snapshot']['option_key_id']])
                ->save(['inventory' => ['exp', 'inventory-' . $v['quantity']]]);
        }
        return true;
    }
}