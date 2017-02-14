<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/30
 * Time: 上午9:39
 */

namespace Consumer\Model;


use Common\Model\CommonModel;
use Think\Log;


/**
 * 用户钱包流水
 * Class WalletBillModel
 * @package Consumer\Model
 */
class WalletBillModel extends CommonModel
{
    const BILL_TYPE_IN =  1;  //收入
    const BILL_TYPE_OUT = 2;  //支出


    const SALE_PET = 1; //宠物出售
    const REFUND   = 2; //商品退款
    const BREEDING = 3; //出售宠物配种

    const BUY_PET  = 4; //购买活体宠物
    const BUY_PRODUCT = 5; //购买商品
    const BUY_MEDICAL = 6; //宠物医疗
    const BUY_BREEDING= 7; //购买宠物配种
    const BUY_TRANSPOT = 8;//宠物运输
    const BUY_FOSTER   = 9 ;//宠物寄养
    const BUY_BURIED   = 10;//宠物殡仪
    const BUY_WITHDRAWALS   = 11;//用户提现
    const BUY_WITHDRAWALS_REFUND = 12; //用户提现退款
    const BUY_STORE = 13; // 商家提现



    public function source(){
        $array = array(
            self::SALE_PET,
            self::REFUND,
            self::BREEDING,
            self::BUY_PET,
            self::BUY_PRODUCT,
            self::BUY_MEDICAL,
            self::BUY_BREEDING,
            self::BUY_TRANSPOT,
            self::BUY_FOSTER,
            self::BUY_BURIED,
            self::BUY_WITHDRAWALS,
            self::BUY_WITHDRAWALS_REFUND,
            self::BUY_STORE,
        );
        return $array;
    }

    public function getSourceValues($source){
        switch($source){
            case self::SALE_PET:
                return '宠物出售';
            case self::REFUND:
                return '商品退款';
            case self::BREEDING:
                return '出售宠物配种';
            case self::BUY_PET:
                return '购买活体宠物';
            case self::BUY_PRODUCT:
                return '购买商品';
            case self::BUY_MEDICAL:
                return '宠物医疗';
            case self::BUY_BREEDING:
                return '购买宠物配种';
            case self::BUY_TRANSPOT:
                return '宠物运输';
            case self::BUY_FOSTER:
                return '宠物寄养';
            case self::BUY_BURIED:
                return '宠物殡仪';
            case self::BUY_WITHDRAWALS:
                return '用户提现';
            case self::BUY_WITHDRAWALS_REFUND:
                return '用户提现退款';
        }
    }

    /**
     * 添加流水记录
     *
     * @param $mid
     * @param $money 变动金额
     * @param $before 变动前金额
     * @param $source 来源
     * @param $ioe 收入OR支出
     *
     * @return bool|mixed
     */
    public function addBill($mid, $money, $before, $source, $ioe)
    {
        if (empty($mid) && empty($money) && empty($before) && empty($source) && empty($ioe))
            return false;

        if ($ioe == self::BILL_TYPE_IN) {
            $after = $before + $money;
        } else if ($ioe == self::BILL_TYPE_OUT) {
            $after = $before - $money;
        } else {
            Log::record('流水类型错误: ioe = ' . $ioe, Log::WARN);
            return false;
        }

        return $this->add([
            'mid'         => $mid,
            'bill_amt'    => $money,
            'bill_before' => $before,
            'bill_after'  => $after,
            'bill_type'   => $ioe,
            'bill_source' => $source,
            'create_time' => time(),
        ]);
    }


    /**
     * 获取流水
     *
     * @param        $mid
     * @param string $ioe
     *
     * @return mixed
     */
    public function getAllBill($mid, $ioe = '')
    {
        if ($ioe) $where['bill_type'] = $ioe;
        $where['mid'] = $mid;

        return $this->where($where)->select();
    }


    public function getStatusValues($status){
        switch ($status){
            case self:: BILL_TYPE_IN:
                return '收入';
            case self:: BILL_TYPE_OUT:
                return '支出';
        }
    }



}
