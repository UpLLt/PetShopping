<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/8/2
 * Time: 15:43
 */

namespace Notify\Controller;

use Common\Model\RechargeModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;
use Think\Controller;

use Think\Log;

/**
 * 充值基类
 * Class RechargeBaseController
 * @package Notify\Controller
 */
class RechargeBaseController extends Controller
{
    protected $wallet_model;
    protected $wallet_bill_model;
    protected $recharge_model;
    private $_recharge_data;

    public function __construct()
    {
        parent::__construct();
        $this->wallet_bill_model = new WalletBillModel();
        $this->wallet_model = new WalletModel();
        $this->recharge_model = new RechargeModel();

    }

    /**
     * @param $out_trade_no
     * @param $price
     *
     * @return bool
     */
    public function chechValidity($out_trade_no, $price)
    {
        $this->_recharge_data = $this->recharge_model->where(['out_trade_no' => $out_trade_no])->find();
        if (!$this->_recharge_data)
            return false;

        if ($this->_recharge_data['total_fee'] != $price) {
            Log::record('充值金额与订单金额不符,订单编号为:' . $out_trade_no, Log::WARN);
            return false;
        }

        if ($this->_recharge_data['status'] == RechargeModel::STATUS_PAY_SUCCESS)
            return false;
        return true;
    }


    /**
     * 钱包金额增加
     * @return bool
     */
    public function compute($payType, $notify_message)
    {
        if (!$this->_recharge_data) {
            return false;
        }
        $pay_money = $this->_recharge_data['total_fee'];
        if ($pay_money <= 0) {
            return false;
        }

        $iscommit = true;
        $this->recharge_model->startTrans();
        $mid = $this->_recharge_data['mid'];
        $before_change = $this->wallet_model->getBalance($mid);
        $result_wallet = $this->wallet_model->addmoney($mid, $pay_money);
        if ($result_wallet === false) {
            $iscommit = false;
        }

        //钱包流水记录
        $result_wallet_bill = $this->wallet_bill_model->addBill($mid, $pay_money, $before_change, '充值', WalletBillModel::BILL_TYPE_IN);

        if ($result_wallet_bill == false) {
            $iscommit = false;
        }

        $result_change_status = $this->recharge_model
            ->where(['out_trade_no' => $this->_recharge_data['out_trade_no']])
            ->save([
                'notify_status'  => RechargeModel::NOTIFY_STATUS_SUCCESS,
                'notify_time'    => time(),
                'update_time'    => time(),
                'status'         => RechargeModel::STATUS_PAY_SUCCESS,
                'paytype'        => $payType,
                'notify_message' => $notify_message,
            ]);


        if ($result_change_status === false)
            $iscommit = false;

        if ($iscommit) {
            $this->recharge_model->commit();
            return true;
        } else {
            $this->recharge_model->rollback();
            return false;
        }
    }

}