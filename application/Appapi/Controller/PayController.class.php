<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/6
 * Time: 上午11:10
 */

namespace Appapi\Controller;

use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\RechargeModel;
use Common\Model\SmslogModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\MemberModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;

/**
 * 支付接口
 * Class PayController
 * @package Appapi\Controller
 */
class PayController extends ApibaseController
{
    private $order_model;
    private $wallet_model;
    private $wallet_bill_model;

    private $com_sco_model;
    private $coupon_model;

    private $member_model;

    private $recharge_model;
    private $smslog_model;

    public function __construct()
    {
        parent::__construct();
        $this->order_model = new OrderModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();

        $this->com_sco_model = new ComScoreModel();
        $this->coupon_model = new CouponModel();

        $this->member_model = new MemberModel();

        $this->recharge_model = new RechargeModel();
        $this->smslog_model = new SmslogModel();
    }


    /**
     * 统一下单支付
     */
    public function UnifiedOrder()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $pay_type = I('post.paytype');


        $this->checkparam([$mid, $token, $order_id, $pay_type]);

        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        if ($pay_type != 'wxpay' && $pay_type != 'alipay')
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '支付类型错误'));


        $order_data = $this->order_model->where(['id' => $order_id, 'mid' => $mid])->find();
        if (!$order_data) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if ($order_data['status'] != OrderModel::STATUS_WAIT_FOR_PAY) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单已支付或已取消'));
        }

        $out_trade_no = $order_data['order_sn'];
        $money_all = $order_data['order_price'];

        if ($pay_type == 'wxpay') {
            $money_all = $money_all * 100;

            vendor('WxPayPubHelper.WxPayPubHelper');
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("body", C("SMS_ACCOUNT.company"));//商品描述
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");  //商户订单号
            $unifiedOrder->setParameter("total_fee", $money_all);  //总金额
            $unifiedOrder->setParameter("notify_url", \WxPayConf_pub::NOTIFY_URL);  //通知地址
            $unifiedOrder->setParameter("trade_type", "APP");  //交易类型
            $unifiedOrder->setParameter("device_info", "WEB");  //设备号

            $appparam = $unifiedOrder->getResultAppApi();

            if ($appparam) {
                $data['Appparam'] = $appparam;

            } else {
                $data['error'] = $unifiedOrder->result;

            }
        }


        if ($pay_type == 'alipay') {
            vendor('Alipay.Corefunction');
            vendor('Alipay.Md5function');
            vendor('Alipay.Notify');
            vendor('Alipay.Submit');
            vendor('Alipay.RSAfunction');
            $alipay_res = new \AlipaySubmit(C('ALIPAY_CONFIG'));
            $para_sort = [
                'service'        => 'mobile.securitypay.pay',
                'partner'        => C('ALIPAY_CONFIG.partner'),
                'seller_id'      => C('ALIPAY_CONFIG.partner'),
                '_input_charset' => 'utf-8',
                'notify_url'     => C('ALIPAY_CONFIG.notify_url'),
                'subject'        => C("SMS_ACCOUNT.company"),
                'body'           => C("SMS_ACCOUNT.company"),
                'payment_type'   => '1',
                'out_trade_no'   => $out_trade_no,
                'total_fee'      => $money_all,
                'it_b_pay'       => '30m',
                'return_url'     => C('ALIPAY_CONFIG.return_url'),
            ];
            $pay_string = $alipay_res->buildRequestParaToString($para_sort);

            $data['pay_string'] = $pay_string;  //后端签名支付
        }

        exit($this->returnApiSuccess($data));

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
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_id = I('post.order_id');
        $paypwd = I('post.paypwd');

//        $coupon_id = I('post.coupon_id'); //优惠券 id可选参数
//        $score = I('post.score'); //积分

        $this->checkparam([$mid, $token, $order_id, $paypwd]);

        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

        $data_order = $this->order_model->where(['id' => $order_id, 'mid' => $mid])->find();
        if (!$data_order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if (!$this->member_model->check_user_password($mid, $paypwd))
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '支付密码错误'));

        if ($data_order['status'] != OrderModel::STATUS_WAIT_FOR_PAY) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单已支付或已取消'));
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
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '优惠券过期'));
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
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '余额不足,支付失败'));
        }

        $iscommit = true;
        $this->order_model->startTrans();

        //修改订单状态
        if (!$this->order_model->setStatus($order_id, OrderModel::STATUS_PAY_SUCCESS))
            $iscommit = false;
        //支付时间
        if($this->order_model->where(array('id' => $order_id))->setField('pay_time', time()) == false) {
            $iscommit = false;
        }

        //修改钱包
        if (!$this->wallet_model->subMoney($mid, $money))
            $iscommit = false;

        //修改钱包记录
        if (!$this->wallet_bill_model->addBill($mid, $money, $balance, $this->order_model->getOrdrTypetoString($data_order['order_type']), WalletBillModel::BILL_TYPE_OUT))
            $iscommit = false;

        if($data_order['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {
            $info = $this->order_model
                ->alias('a')
                ->join('LEFT JOIN ego_hospital as b ON a.id = b.order_sid')
                ->join('LEFT JOIN ego_hospital_shop as c ON b.hid = c.id')
                ->where(array('a.id' => $order_id))
                ->field('c.sid')
                ->find();
            $rst = M('member_shop')
                ->where(array('id' => $info['sid']))
                ->setInc('balance', $money);
            if(!$rst) {
                $iscommit = false;
            }
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
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '支付失败'));
        }
    }


    public function UnifiedRecharge()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $price = I('post.price');
        $pay_type = I('post.paytype');
        $this->checkparam([$mid, $token]);
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));


        $out_trade_no = $this->recharge_model->getOrderNumber();

        $insert_data = [
            'mid'           => $mid,
            'out_trade_no'  => $out_trade_no,
            'total_fee'     => $price,
            'status'        => RechargeModel::STATUS_WAIT_FOR_PAY,
            'notify_status' => RechargeModel::NOTIFY_STATUS_DEFAULT,
            'create_time'   => time(),
            'update_time'   => time(),
        ];

        if (!$this->recharge_model->create($insert_data))
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $this->recharge_model->getError()));

        if (!$this->recharge_model->add())
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '失败'));


        if ($pay_type == 'wxpay') {
            $money_all = $price * 100;

            vendor('WxPayPubHelper.WxPayPubHelper');
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("body", C("SMS_ACCOUNT.company"));//商品描述
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");  //商户订单号
            $unifiedOrder->setParameter("total_fee", $money_all);  //总金额
            $unifiedOrder->setParameter("notify_url", \WxPayConf_pub::NOTIFY_RECHARGE_URL);  //通知地址
            $unifiedOrder->setParameter("trade_type", "APP");  //交易类型
            $unifiedOrder->setParameter("device_info", "WEB");  //设备号

            $appparam = $unifiedOrder->getResultAppApi();

            if ($appparam) {
                $data['Appparam'] = $appparam;

            } else {
                $data['error'] = $unifiedOrder->result;
            }
        }


        if ($pay_type == 'alipay') {
            $money_all = $price;

            vendor('Alipay.Corefunction');
            vendor('Alipay.Md5function');
            vendor('Alipay.Notify');
            vendor('Alipay.Submit');
            vendor('Alipay.RSAfunction');
            $alipay_res = new \AlipaySubmit(C('ALIPAY_CONFIG'));
            $para_sort = [
                'service'        => 'mobile.securitypay.pay',
                'partner'        => C('ALIPAY_CONFIG.partner'),
                'seller_id'      => C('ALIPAY_CONFIG.partner'),
                '_input_charset' => 'utf-8',
                'notify_url'     => C('ALIPAY_CONFIG.notify_recharge_url'),
                'subject'        => C("SMS_ACCOUNT.company"),
                'body'           => C("SMS_ACCOUNT.company"),
                'payment_type'   => '1',
                'out_trade_no'   => $out_trade_no,
                'total_fee'      => $money_all,
                'it_b_pay'       => '30m',
                'return_url'     => C('ALIPAY_CONFIG.return_url'),
            ];
            $pay_string = $alipay_res->buildRequestParaToString($para_sort);

            $data['pay_string'] = $pay_string;  //后端签名支付
        }

        exit($this->returnApiSuccess($data));
    }


}