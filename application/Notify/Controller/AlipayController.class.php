<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/25
 * Time: 14:05
 */

namespace Notify\Controller;

use Common\Model\OrderModel;
use Common\Model\SmslogModel;
use Think\Log;


/**
 * 支付宝
 * Class AlipayController
 * @package Notify\Controller
 */
class AlipayController extends NotifybaseController
{
    public $order_model;
    public $smslog_model;

    public function __construct()
    {
        parent::__construct();
        $this->order_model = new OrderModel();
        $this->smslog_model = new SmslogModel();
        vendor('Alipay.RSAfunction');
        vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');
    }

    public function index()
    {
        $alipay_config = C('ALIPAY_CONFIG');
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify = $alipayNotify->verifyNotify();

        if (!$verify) {
            Log::record('支付宝回调校验失败' . json_encode($alipay_config), Log::WARN);
            Log::record('支付宝回调校验失败' . json_encode(I('')), Log::WARN);
            echo 'fail';
            exit;
        }


        if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
            $data = I('post.');
            if (empty($data))
                exit('empty');

            $order_sn = $data['out_trade_no'];
            $order_price = $data['total_fee'];

            $order = $this->order_model->where(['order_sn' => $order_sn])->find();
            if (!$order) {
                Log::record($order_sn . '订单编号不存在', Log::WARN);
                echo 'fail';
            }


            $data = [
                'status'   => OrderModel::STATUS_PAY_SUCCESS,
                'pay_type' => OrderModel::PAY_TYPE_ALIPAY,
                'pay_time' => time(),
            ];
            $result = $this->order_model->where(['order_sn' => $order_sn])->save($data);

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

            echo 'success';
        }

    }
}