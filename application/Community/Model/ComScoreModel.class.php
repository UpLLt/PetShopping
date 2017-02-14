<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/21
 * Time: 11:17
 */

namespace Community\Model;


use Common\Model\CommonModel;

class ComScoreModel extends CommonModel
{
    /*
     * 获取用户积分等级信息
     */
    public function info($id)
    {
        $where['sco_member_id'] = $id;
        return $this->where($where)->field('sco_id, sco_now, sco_history, sco_level')->find();
    }

    /*
     * 获取用户积分等级
     */
    public function getMemberScoLevel($id)
    {
        $where['sco_member_id'] = $id;
        return $this->where($where)->getField('sco_level');
    }

    /*
     * 修改积分信息
     */
    public function updateSco($id, $now, $history, $level)
    {
        $data = [
            'sco_now'     => $now,
            'sco_history' => $history,
            'sco_level'   => $level,
        ];
        return $this->where(['sco_member_id' => $id])->save($data);
    }

    /**
     * 增加积分
     *
     * @param        $mid
     * @param        $num
     * @param string $key
     *
     * @return bool
     */
    public function saveScore($mid, $num, $key = 'sco_now')
    {
        return $this->where(['sco_member_id' => $mid])->setInc($key, $num);
    }

    /**
     * 减积分
     *
     * @param        $mid
     * @param        $num
     * @param string $key
     *
     * @return bool
     */
    public function decScore($mid, $num, $key = 'sco_now')
    {
        return $this->where(['sco_member_id' => $mid])->setDec($key, $num);
    }


    /**
     * 初始化用户积分
     *
     * @param $mid
     *
     * @return bool|mixed
     */
    public function init($mid)
    {
        if (empty($mid)) return false;
        return $this->add([
            'sco_member_id' => $mid,
        ]);
    }


    /**
     * 积分抵扣计算
     *
     * @param $mid
     * @param $total
     *
     * @return array
     */
    public function scoExchange($mid, $total, $sco_or_price = false)
    {
        $Com_Rule_model = new ComRuleModel();
        //每x积分抵扣多少钱人民币
        $rule_dedu = $Com_Rule_model->getValue('rul_dedu');
        $rule_dedu = $rule_dedu['rul_value'];

//        return $rule_dedu;

        //积分抵扣最大比例
        $rule_dedu_max = $Com_Rule_model->getValue('rul_dedu_max');
        $rule_dedu_max = $rule_dedu_max['rul_value'];

//        return $rule_dedu_max;

        $score = $this->info($mid);
        //当前积分
        $sco_now = $score['sco_now'];

        //最大抵扣金额
        $price_max = $total * $rule_dedu_max / 100;

        //现有积分可抵扣多少钱
        $sco_now_price = $sco_now / $rule_dedu;
//        return $sco_now_price;

        $data = [
            'score'     => $score['sco_now'],
            'price_max' => number_format($price_max, 2),
            'deduction' => number_format($sco_now_price, 2),
        ];

        if (!$sco_or_price) {
            if ($price_max > $sco_now_price) {
                $data['price'] = $sco_now_price;
            } else {
                $data['price'] = $price_max;
            }
            $data['price'] = number_format($data['price'], 2);
        } else {
            if ($price_max > $sco_now_price) {
                $data = $sco_now;
            } else {
                //总价 * 积分/元  = 总积分
                $data = $price_max * $rule_dedu;
            }
        }
        return $data;
    }


    /**
     * 积分兑换人民币
     *
     * @param $scon
     *
     * @return float|int
     */
    public function sconToMoney($scon)
    {
        $Com_Rule_model = new ComRuleModel();
        //每x积分抵扣多少钱人民币
        $rule_dedu = $Com_Rule_model->getValue('rul_dedu');
        $rule_dedu = $rule_dedu['rul_value'];
        return $scon / $rule_dedu;
    }
}