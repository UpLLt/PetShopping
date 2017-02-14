<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/29
 * Time: 下午2:56
 */

namespace Consumer\Model;


use Common\Model\CommonModel;

/**
 * 用户钱包
 * Class WalletModel
 * @package Consumer\Model
 */
class WalletModel extends CommonModel
{
    /**
     * 初始化
     *
     * @param $mid
     *
     * @return bool|mixed
     */
    public function init($mid)
    {
        if (empty($mid)) return false;
        return $this->add([
            'mid'         => $mid,
            'create_time' => time(),
        ]);
    }


    /**
     * 获取当前余额
     *
     * @param      $mid
     * @param bool $format
     *
     * @return mixed|string
     */
    public function getBalance($mid, $format = false)
    {
        $result = $this->where(['mid' => $mid])->getField('balance');
        if ($format) return number_format($result, 2);
        return $result;
    }


    /**
     * 增加钱包金额
     *
     * @param mixed|string $mid
     * @param array        $money
     *
     * @return bool
     */
    public function addMoney($mid, $money)
    {
        if(!checkNumber($money)) return false;
        return $this->where(['mid' => $mid])->save(["balance" => ["exp", "balance+" . $money]]);
    }

    /**
     * 减少钱包金额
     *
     * @param $mid
     * @param $money
     *
     * @return bool
     */
    public function subMoney($mid, $money)
    {
        if(!checkNumber($money)) return false;
        return $this->where(['mid' => $mid])->save(["balance" => ["exp", "balance-" . $money]]);
    }

}