<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/30
 * Time: 上午11:20
 */

namespace Consumer\Model;


use Common\Model\CommonModel;

class CouponModel extends CommonModel
{

    //有效
    const STATUS_VALIDITY = 1;
    //过期
    const STATUS_OVERDUE = 2;
    //已使用
    const STATUS_USED = 3;

    /**
     * 检查优惠卷
     *
     * @param $code
     *
     * @return mixed
     */
    public function checkCouponNumber($code)
    {
        return $this->where(['coupon_number' => $code])->count();
    }

    /**
     * 检查优惠卷
     *
     * @param $code
     * @param $mid
     *
     * @return bool|mixed
     */
    public function checkCoupon($code, $mid)
    {
        $result = $this->where(['coupon_number' => $code, 'mid' => $mid, 'status' => self::STATUS_VALIDITY])->find();
        if (!$result) return false;
        return $result;
    }


    /**
     * 过期状态变更
     */
    public function CouponStatusChange()
    {
        $result = $this->where('coupon_id = cou_status ')->select();
        foreach ($result as $k => $v) {
            if (time() > $v['expiration_time']) $this->where('coupon_id=' . $v['coupon_id'])->save(['cou_status' => self:: STATUS_OVERDUE]);
        }
    }

    /**
     * 优惠卷使用状态更改
     *
     * @param $code
     * @param $mid
     *
     * @return bool
     */
    public function useCoupon($code, $mid)
    {
        return $this->where(['coupon_number' => $code, 'mid' => $mid])->save(['status' => self::STATUS_USED]);
    }


    public function getCouponnumber()
    {
        $code = $this->get_code(8) . $this->get_code(4, 1);
        if ($this->checkCouponNumber($code) > 0) {
            return self::getCouponnumber();
        }
        return $code;
    }

    /**
     * 状态码中文说明
     *
     * @param $status
     *
     * @return string
     */
    public function getStatusValues($status)
    {
        switch ($status) {
            case self::STATUS_VALIDITY:
                return '有效';
                break;
            case self::STATUS_USED:
                return '已使用';
                break;
            case self::STATUS_OVERDUE:
                return '过期';
                break;
            default:
                return 'error';
                break;
        }
    }

    /**
     * 获取验证码
     *
     * @param int $length
     * @param int $mode
     *
     * @return string
     */
    public function get_code($length = 32, $mode = 0)//获取随机验证码函数
    {
        switch ($mode) {
            case '1':
                $str = '123456789';
                break;
            case '2':
                $str = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case '3':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case '4':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                break;
            case '5':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
                break;
            case '6':
                $str = 'abcdefghijklmnopqrstuvwxyz1234567890';
                break;
            default:
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
                break;
        }
        $checkstr = '';
        $len = strlen($str) - 1;
        for ($i = 0; $i < $length; $i++) {
            //$num=rand(0,$len);//产生一个0到$len之间的随机数
            $num = mt_rand(0, $len);//产生一个0到$len之间的随机数
            $checkstr .= $str[$num];
        }
        return $checkstr;
    }


    /**
     *
     * 获取优惠券
     *
     * @param $id
     *
     * @return mixed
     */
    public function getCoupon($id)
    {
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
        $result = $this->alias('a')->field('a.*,b.price,b.describe')->join($join)->find($id);
        return $result;
    }


    /**
     * 获取优惠券价值
     * @param $id
     *
     * @return mixed
     */
    public function getCouponValue($id)
    {
        $result = $this->getCoupon($id);
        return $result['price'];
    }




}