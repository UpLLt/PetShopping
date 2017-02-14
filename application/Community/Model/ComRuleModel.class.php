<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/21
 * Time: 11:54
 */

namespace Community\Model;


use Common\Model\CommonModel;

class ComRuleModel extends CommonModel
{
    const KEY_1 = '签到';
    const KEY_2 = '发帖';
    const KEY_3 = '回复';
    const KEY_4 = '消费';
    const KEY_5 = '抵扣';
    const KEY_6 = '发帖关键字';
    const KEY_7 = '回复关键字';
    const KEY_8 = '上限';
    const KEY_9 = '积分抵扣最大比例';

    public function getValue($key)
    {
        return $this->where(['rul_key' => $key])->field('rul_value')->find();
    }

    /*
     *
     */
    public function getNum($type)
    {
//        dump($type);
        switch ($type) {
            case self::KEY_1:
                $where['rul_key'] = 'rul_click';
            case self::KEY_2:
                $where['rul_key'] = 'rul_pub';
            case self::KEY_3:
                $where['rul_key'] = 'rul_reply';
            case self::KEY_4:
                $where['rul_key'] = 'rul_cons';
            case self::KEY_5:
                $where['rul_key'] = 'rul_dedu';
            case self::KEY_6:
                $where['rul_key'] = 'rul_pubword';
            case self::KEY_7:
                $where['rul_key'] = 'rul_repword';
            case self::KEY_8:
                $where['rul_key'] = 'rul_max';
            case self::KEY_9:
                $where['rul_key'] = 'rul_dedu_max';
            default:
                $where = [];
                break;
        }//return self :: KEY_1;
//        dump($where);
        return $this->where($where)->field('rul_value')->find();
    }

}