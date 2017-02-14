<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/23
 * Time: 10:26
 */

namespace Community\Model;


use Common\Model\CommonModel;

class ComLevelModel extends CommonModel
{
    const VIP_1 = 1;
    const VIP_2 = 2;
    const VIP_3 = 3;
    const VIP_4 = 4;
    const VIP_5 = 5;

    /*
     * 获取会员等级
     */
    public function getLev($num) {
        $where['lev_end'] = array('egt', $num);
        $where['lev_start'] = array('elt', $num);
        return $this->where($where)->field('lev_num')->find();
    }
}