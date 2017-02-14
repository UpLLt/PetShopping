<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/22
 * Time: 下午3:48
 */

namespace Issue\Model;


use Common\Model\CommonModel;
use Common\Model\PetModel;
use Think\Model;


/*
 * 我要买宠,宠物数据
 */

class ProductPetModel extends PetModel
{

    const LOCK_ON = 1;

    //自动验证
    protected $_validate = [
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        ['pet_name', 'require', '姓名' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
        ['pet_type', 'require', '类型' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
        ['pet_variety_id', 'require', '品种' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
        ['pet_colour', 'require', '颜色' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
        ['pet_age', 'require', '年龄' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
        ['pet_fur', 'require', '毛色' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
//        ['pet_vaccine_z', 'require', '针' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
//        ['pet_vaccine_m', 'require', '苗' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
        ['pet_price', 'require', '价格' . '不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH],
    ];


    protected $_auto = [
        ['create_time', 'time', Model::MODEL_INSERT, 'function'],
    ];


    public function deletePet($id)
    {
        if (empty($id)) return false;
        return $this->save(['id' => $id, 'show' => 0]);
    }


    public function getHotPet($size = 8)
    {
        $result = $this
            ->where(['hot' => 1])
            ->where(['show' => 1])
            ->where(['status' => 0])
            ->limit(0, $size)
            ->field('id,pet_name,pet_price,pet_picture')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['cate_sign'] = 'pet';
            $result[$k]['cover'] = '';
            $v['pet_picture'] = json_decode($v['pet_picture'], true);
            if ($v['pet_picture'])
                $result[$k]['cover'] = setUrl($v['pet_picture'][0]['url']);
            unset($result[$k]['pet_picture']);
        }

        return $result;
    }
}