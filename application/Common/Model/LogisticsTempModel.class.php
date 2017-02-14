<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/30
 * Time: 下午6:38
 */

namespace Common\Model;


class LogisticsTempModel extends CommonModel
{
    const TEMP_SWITCH_NO = 1; // 否
    const TEMP_SWITCH_YES = 2;  // 是


    /**
     * 获取是否包邮 string
     *
     * @param $status
     *
     * @return string
     */
    public function getTempSwitchtoString($status)
    {
        switch ($status) {
            case  self::TEMP_SWITCH_NO:
                return '否';
                break;
            case  self::TEMP_SWITCH_YES:
                return '是';
                break;
            default :
                return '';
                break;

        }
    }


    /**
     * @param string $id
     *
     * @return string
     */
    public function getOption($id = '')
    {
        $result = $this->select();
        $option = '';
        $state = '';
        foreach ($result as $k => $v) {
            $state = '';
            if ($v['temp_id'] == $id) $state = 'selected="selected"';
            $option .= '<option ' . $state . ' value="' . $v['temp_id'] . '">' . $v['temp_name'] . '</option>';
        }
        return $option;
    }


    /**
     * 计算价格
     *
     * @param $id
     * @param $quantity
     * @param $price
     *
     *
     * temp_switch 是否包邮
     * in_number  多少件内,多少钱
     * in_price   多少件内,多少钱
     * add_number 每增加多少件
     * add_price  每增加多少件，增加多少钱
     *
     * full_number 满多少件包邮
     * full_total  满多少钱包邮
     *
     * @return bool|int
     */
    public function computational_cost($id, $quantity, $price)
    {
        $result = $this->find($id);
        if (!$result) return 0;

        //是否包邮判断
        if ($result['temp_switch'] == self::TEMP_SWITCH_YES)
            return 0;
        $total = $price * $quantity;

        //满多少件包邮 or 满多少钱包邮
        if ($quantity > $result['full_number'] || $total > $result['full_total']) {
            return 0;
        } else {
            //多少件内，多少钱
            if ($quantity < $result['in_number']) {
                return $result['in_price'];
            } else {
                $number = $quantity - $result['in_number'];
                //计算多少次
                $mltiple = intval($number / $result['add_number']);
                $add_price = $mltiple * $result['add_price'];

                return $result['in_price'] + $add_price;
            }

        }
    }
}