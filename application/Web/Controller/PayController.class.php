<?php
namespace Web\Controller;

use Common\Model\AddressModel;
use Common\Model\CartModel;
use Common\Model\LogisticsTempModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Common\Model\SmslogModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\MemberModel;
use Consumer\Model\TicketModel;
use Consumer\Model\WalletModel;
use Issue\Model\ProductPetModel;
use Consumer\Model\WalletBillModel;
use Common\Model\OrderModel;
use Think\Controller;

/**
 * 支付列表
 * Class IndexController
 * @package Web\Controller
 */
class PayController extends BaseController
{
    private $product_pet_model, $cart_model, $product_model, $product_option_model , $logistics_model, $coupon_model,$ticket_model,$com_sco_model;
    private $address_model ,$order_product_model,$order_model ,$com_record_model,$member_model ,$wallet_model ,$wallet_bill_model ,$smslog_model;
    public function __construct()
    {
        parent::__construct();

        $this->product_pet_model = new ProductPetModel();
        $this->cart_model = new CartModel();
        $this->product_model = new ProductModel();
        $this->product_option_model = new ProductOptionModel();
        $this->logistics_model = new LogisticsTempModel();
        $this->coupon_model = new CouponModel();
        $this->ticket_model = new TicketModel();
        $this->com_sco_model = new ComScoreModel();
        $this->address_model = new AddressModel();
        $this->order_product_model = new OrderProductModel();
        $this->order_model   = new OrderModel();
        $this->com_record_model = new ComRecordModel();
        $this->member_model = new MemberModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
        $this->smslog_model = new SmslogModel();
    }



//    public function _initialize()
//    {
//        vendor('Alipay_PC.Corefunction');
//        vendor('Alipay_PC.Md5function');
//        vendor('Alipay_PC.Notify');
//        vendor('Alipay_PC.Submit');
//
//    }
//
//    //doalipay方法
//    /*该方法其实就是将接口文件包下alipayapi.php的内容复制过来
//      然后进行相关处理
//    */
//    public function doalipay()
//    {
//        $mid = session('mid');
//        $this->is_login();
//        $order_id = I('post.order_id');
//
//        $order_data = $this->order_model->where(['id' => $order_id, 'mid' => $mid])->find();
//        if (!$order_data) exit($this->error( '订单不存在'));
//
//        if ($order_data['status'] != OrderModel::STATUS_WAIT_FOR_PAY) {
//            exit($this->error( '订单已支付或已取消'));
//        }
//
//
//
//        $out_trade_no = $order_data['order_sn'];
//        $money_all = $order_data['order_price'];
//
////        $test = C('TEST_ID');
////        $domain = is_string($test);
////
////        if(  $domain === true ) $price = 0.01;
//        /*********************************************************
//         * 把alipayapi.php中复制过来的如下两段代码去掉，
//         * 第一段是引入配置项，
//         * 第二段是引入submit.class.php这个类。
//         * 为什么要去掉？？
//         * 第一，配置项的内容已经在项目的Config.php文件中进行了配置，我们只需用C函数进行调用即可；
//         * 第二，这里调用的submit.class.php类库我们已经在PayAction的_initialize()中已经引入；所以这里不再需要；
//         *****************************************************/
//        // require_once("alipay.config.php");
//        // require_once("lib/alipay_submit.class.php");
//
//        //这里我们通过TP的C函数把配置项参数读出，赋给$alipay_config；
//        $alipay_config = C('alipay_config');
//        /**************************请求参数**************************/
//        $payment_type = "1"; //支付类型 //必填，不能修改
//        $notify_url = C('alipay.notify_url'); //服务器异步通知页面路径
//        $return_url = C('alipay.return_url'); //页面跳转同步通知页面路径
//        $seller_email = C('alipay.seller_email');//卖家支付宝帐户必填
//        $out_trade_no = $out_trade_no;//商户订单号 通过支付页面的表单进行传递，注意要唯一！
//        $subject = "咪咻宠物";  //订单名称 //必填 通过支付页面的表单进行传递
//        $total_fee = $money_all;   //付款金额  //必填 通过支付页面的表单进行传递
//        $body = "咪咻宠物";  //订单描述 通过支付页面的表单进行传递
//        $show_url = U('Index/index');  //商品展示地址 通过支付页面的表单进行传递
//        $anti_phishing_key = "";//防钓鱼时间戳 //若要使用请调用类文件submit中的query_timestamp函数
//        $exter_invoke_ip = get_client_ip(); //客户端的IP地址
//        /************************************************************/
//
//        //构造要请求的参数数组，无需改动
//        $parameter = array(
//            "service" => "create_direct_pay_by_user",
//            "partner" => trim($alipay_config['partner']),
//            "payment_type" => $payment_type,
//            "notify_url" => $notify_url,
//            "return_url" => $return_url,
//            "seller_email" => $seller_email,
//            "out_trade_no" => $out_trade_no,
//            "subject" => $subject,
//            "total_fee" => $total_fee,
//            "body" => $body,
//            "show_url" => $show_url,
//            "anti_phishing_key" => $anti_phishing_key,
//            "exter_invoke_ip" => $exter_invoke_ip,
//            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
//        );
//        //建立请求
//        $alipaySubmit = new  \AlipaySubmit($alipay_config);
//        $html_text = $alipaySubmit->buildRequestFormPC($parameter, "post", "确认");
//        echo $html_text;
//    }
//
//    /******************************
//     * 服务器异步通知页面方法
//     * 其实这里就是将notify_url.php文件中的代码复制过来进行处理
//     *******************************/
//    function notifyurl()
//    {
//        /*
//        同理去掉以下两句代码；
//        */
//        //require_once("alipay.config.php");
//        //require_once("lib/alipay_notify.class.php");
//
//        //这里还是通过C函数来读取配置项，赋值给$alipay_config
//        $alipay_config = C('alipay_config');
//        //计算得出通知验证结果
//        $alipayNotify = new \AlipayNotify($alipay_config);
//        $verify_result = $alipayNotify->verifyNotify();
//        if ($verify_result) {
//            //验证成功
//            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
//            $out_trade_no = $_POST['out_trade_no'];      //商户订单号
//            $trade_no = $_POST['trade_no'];          //支付宝交易号
//            $trade_status = $_POST['trade_status'];      //交易状态
//            $total_fee = $_POST['total_fee'];         //交易金额
//            $notify_id = $_POST['notify_id'];         //通知校验ID。
//            $notify_time = $_POST['notify_time'];       //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
//            $buyer_email = $_POST['buyer_email'];       //买家支付宝帐号；
//            $parameter = array(
//                "out_trade_no" => $out_trade_no, //商户订单编号；
//                "trade_no" => $trade_no,     //支付宝交易号；
//                "total_fee" => $total_fee,    //交易金额；
//                "trade_status" => $trade_status, //交易状态
//                "notify_id" => $notify_id,    //通知校验ID。
//                "notify_time" => $notify_time,  //通知的发送时间。
//                "buyer_email" => $buyer_email,  //买家支付宝帐号；
//            );
//            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
//                //
//            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
//
//
//                if (!checkorderstatus($out_trade_no)) {
//                    orderhandle($parameter);
//                    //进行订单处理，并传送从支付宝返回的参数；
//                }
//
//
//
//            }
//            echo "success";        //请不要修改或删除
//        } else {
//            //验证失败
//            echo "fail";
//        }
//    }
//
//    /*
//        页面跳转处理方法；
//        这里其实就是将return_url.php这个文件中的代码复制过来，进行处理；
//        */
//    function returnurl()
//    {
//        //头部的处理跟上面两个方法一样，这里不罗嗦了！
//        $alipay_config = C('alipay_config');
////        dump($alipay_config);exit;
//
//        $alipayNotify = new  \ AlipayNotify($alipay_config);//计算得出通知验证结果
//        $verify_result = $alipayNotify->verifyReturn();
//
//        if ($verify_result) {
//            //验证成功
//            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
//            $out_trade_no = $_GET['out_trade_no'];      //商户订单号
//            $trade_no = $_GET['trade_no'];          //支付宝交易号
//            $trade_status = $_GET['trade_status'];      //交易状态
//            $total_fee = $_GET['total_fee'];         //交易金额
//            $notify_id = $_GET['notify_id'];         //通知校验ID。
//            $notify_time = $_GET['notify_time'];       //通知的发送时间。
//            $buyer_email = $_GET['buyer_email'];       //买家支付宝帐号；
//
//            $parameter = array(
//                "out_trade_no" => $out_trade_no,      //商户订单编号；
//                "trade_no" => $trade_no,          //支付宝交易号；
//                "total_fee" => $total_fee,         //交易金额；
//                "trade_status" => $trade_status,      //交易状态
//                "notify_id" => $notify_id,         //通知校验ID。
//                "notify_time" => $notify_time,       //通知的发送时间。
//                "buyer_email" => $buyer_email,       //买家支付宝帐号
//            );
//
//            if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
//
//
//                $order_sn = $out_trade_no; //订单号
//                $order = $this->order_model->where(['order_sn' => $order_sn])->find();
//                $data = [
//                    'status' => OrderModel::STATUS_PAY_SUCCESS,
//                    'pay_type' => OrderModel::PAY_TYPE_ALIPAY,
//                    'pay_time' => time(),
//                ];
//                $result = $this->order_model->where(['order_sn' => $order_sn])->save($data);
//                if($order['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {
//                    $info = $this->order_model
//                        ->alias('a')
//                        ->join('LEFT JOIN ego_hospital as b ON a.id = b.order_sid')
//                        ->join('LEFT JOIN ego_hospital_shop as c ON b.hid = c.id')
//                        ->where(array('a.id' => $order['id']))
//                        ->field('c.sid')
//                        ->find();
//                    $rst = M('member_shop')
//                        ->where(array('id' => $info['sid']))
//                        ->setInc('balance', $order['order_price']);
//
//                }
//
//                if( $order['order_type'] == OrderModel::ORDER_TYPE_PET ){
//
//                    $username = $this->order_model
//                        ->where(['ego_order.id'=>$order['id']])
//                        ->join('LEFT JOIN ego_member on ego_member.id = ego_order.mid')
//                        ->field('ego_order.*,ego_member.username')
//                        ->find();
//
//
//                    $content = C('BUYPET_CONTENT');
//                    $data = [
//                        'content'     => $content,
//                        'mobile'      => $username['username'],
//                        'create_time' => time(),
//                        'end_time'    => time()
//                    ];
//                    vendor("Cxsms.Cxsms");
//                    $options = C('SMS_ACCOUNT');
//
//                    $Cxsms = new \Cxsms($options);
//                    $result = $Cxsms->send($username['username'], $content);
//                    if ($result && $result['returnsms']['returnstatus'] == 'Success') {
//                        $this->smslog_model->add($data);
//                    }
//                }
//
//                //如果是商品，修改库存  TODO
//                if ($order['order_type'] == OrderModel::ORDER_TYPE_GOODS) {
//                    $this->product_change($order['id']);
//                }
//                redirect(C('alipay.successpage'));//跳转到配置项中配置的支付成功页面；
//
//            } else {
//                echo "trade_status=" . $_GET['trade_status'];
//                redirect(C('alipay.errorpage'));//跳转到配置项中配置的支付失败页面；
//            }
//        } else {
//            //验证失败
//            //如要调试，请看alipay_notify.php页面的verifyReturn函数
//            echo "支付失败！";
//        }
//
//    }

    public function pay(){
        $order_sn = I('order_sn');

        $order = $this->order_model->where('order_sn ='.$order_sn)->field('order_price,id')->find();
        $this->assign('order',$order);
        $this->display();
    }


    public function UnifiedOrder(){

        $mid = session('mid');
        $this->is_login();
        $order_id = I('get.order_id');

        $order_data = $this->order_model->where(['id' => $order_id, 'mid' => $mid])->find();
        if (!$order_data) exit($this->error( '订单不存在'));

        if ($order_data['status'] != OrderModel::STATUS_WAIT_FOR_PAY) {
            exit($this->error( '订单已支付或已取消'));
        }



        $out_trade_no = $order_data['order_sn'];
        $money_all = $order_data['order_price'];
        $config = C('WEIXINPAY_CONFIG');
            $money_all = $money_all * 100;
            $order = [
                'body'         => '咪咻',
                'total_fee'    => $money_all,
                'out_trade_no' => $out_trade_no,
                'product_id'   => $out_trade_no,
                'notify_url'   => $config['NOTIFY_URL'],
            ];

            weixinpay($order);

    }

    /**
     * 余额支付
     *
     * 1.判断支付密码
     *
     * 2.订单必须是未付款
     *
     * 3.余额不足
     * 4.积分足够，扣除积分
     * 5.优惠券，锁定
     *
     * 7.修改订单状态
     * 8.修改钱包
     *
     */
    public function balancePay()
    {

        $mid = session('mid');
        $this->check_login();
        $order_id = I('post.order_sn');
        $paypwd = I('post.paypwd');



        $data_order = $this->order_model->where(['id' => $order_id, 'mid' => $mid])->find();

        if (!$data_order) exit($this->returnApiError(BaseController::FATAL_ERROR, '订单不存在'));
        $member_user = $this->member_model->where( 'id='.$mid )->getField('pay_password');

        if( !$member_user )  exit($this->returnApiError(BaseController::FATAL_ERROR, '尚未设置支付密码，请设置支付密码'));

        if (!$this->member_model->check_user_password($mid, $paypwd))
            exit($this->returnApiError(BaseController::FATAL_ERROR, '支付密码错误'));

        if ($data_order['status'] != OrderModel::STATUS_WAIT_FOR_PAY) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '订单已支付或已取消'));
        }

        $coupon_id = $data_order['coupon_id'];
        $score = $data_order['score'];
        //优惠券价格
        $coupon_price = '';
        //积分价格
        $score_price = '';

        if ($coupon_id) {
            $coupon = $this->coupon_model->getCoupon($coupon_id);
            //判断过期
            if ($coupon['expiration_time'] < time()) {
                exit($this->returnApiError(BaseController::FATAL_ERROR, '优惠券过期'));
            }
            //优惠券价格
            $coupon_price = $coupon['price'];
        }

        if ($score) {
            $score_price = '';


        }

        $money = $data_order['order_price'] - $coupon_price - $score_price;

        //余额不足
        $balance = $this->wallet_model->getBalance($mid);
        if ($money > $balance) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '余额不足,支付失败'));
        }

        $iscommit = true;
        $this->order_model->startTrans();

        //修改订单状态
        if (!$this->order_model->setStatus($order_id, OrderModel::STATUS_PAY_SUCCESS))
            $iscommit = false;

        //修改钱包
        if (!$this->wallet_model->subMoney($mid, $money))
            $iscommit = false;

        //修改钱包记录
        if (!$this->wallet_bill_model->addBill($mid, $money, $balance, $this->order_model->getOrdrTypetoString($data_order['order_type']), WalletBillModel::BILL_TYPE_OUT))
            $iscommit = false;

        //如果是商品，修改库存
        if ($data_order['order_type'] == OrderModel::ORDER_TYPE_GOODS) {
            $this->product_change($data_order['id']);
        }

        if($data_order['order_type'] == OrderModel::ORDER_TYPE_PET) {

            $username = $this->member_model->where(['id'=>$mid])->find();

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
                $add_sms = $this->smslog_model->add($data);
                if( !$add_sms )   $iscommit = false;
            }
        }

        //提交事物
        if ($iscommit) {
            $this->order_model->commit();
            exit($this->returnApiSuccess());
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(BaseController::FATAL_ERROR, '支付失败'));
        }
    }

    /**
     * @param $order_id
     * @return bool
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


    public function order_status(){
        $order_sn = I('post.order_sn');
        $status = $this->order_model->where(['id' => $order_sn])->getField('status');
       
        if( $status == OrderModel::STATUS_PAY_SUCCESS){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(2);
        }

    }

    public function paysuccess(){
        $this->display();
    }

}