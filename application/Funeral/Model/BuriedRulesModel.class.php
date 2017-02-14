<?php
namespace Funeral\Model;
use Common\Model\CommonModel;

/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/25
 * Time: 14:08
 */
class BuriedRulesModel extends CommonModel
{
    /**
     * @param $province 省代码
     * @param $city     市代码
     * @param $country  区县代码
     */
    public function getOne($province, $city, $country) {
        $where = array(
            'bu_province' => $province,
            'bu_city' => $city,
            'bu_country' => $country,
        );
        return $this->where($where)->find();
    }

    /**
     * @param $country
     * @return mixed
     * 获取单个区域信息
     */
    public function getDetail($country)
    {
        $field = 'bu_send_addre, bu_price, bu_cremation, bu_cremation_price, bu_overstep_price, bu_normal,bu_tree, bu_luxury, bu_normal_west,bu_luxury_west';
        return $this->where(array('bu_country' => $country))->field($field)->find();
    }

}