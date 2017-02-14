<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/22
 * Time: 下午5:36
 */

namespace Common\Model;

use Think\Model;


/**
 * 宠物品种表
 * Class PetTypeModel
 * @package Common\Model
 */
class PetTypeModel extends PetModel
{
    /**
     * [验证字段1, 验证规则, 错误提示, [验证条件, 附加规则, 验证时间]]
     * @var array
     */
    protected $_validate = [
        ['pet_type', 'require', 'pet_type 字段必须！'], //默认情况下用正则进行验证
        ['pet_variety_id', 'require', 'pet_variety_id 字段必须！'], //默认情况下用正则进行验证
        ['pet_letter', 'require', 'pet_letter 字段必须！'], //默认情况下用正则进行验证
    ];

    protected $_auto = [
        ['create_time', 'time', Model::MODEL_INSERT, 'function'], // 对update_time字段在更新的时候写入当前时间戳
    ];


    /**
     * 获取品种
     *
     * @param $pet_type 类型
     */
    public function getVariety($pet_type)
    {
        return $this->where(['pet_type' => $pet_type])->select();
    }


    /**
     * 获取品种 <option></option>
     *
     * @param $pet_type
     *
     * @return string
     */
    public function getVarietyOption($pet_type, $id = '')
    {
        $data = $this->getVariety($pet_type);
        $option = '';
        $state = '';
        foreach ($data as $k => $v) {
            if ($id) $state = $v['pet_variety_id'] == $id ? 'selected="selected"' : '';
            $option .= '<option  ' . $state . ' value="' . $v['pet_variety_id'] . '">' . $v['pet_variety'] . '</option>';
        }
        return $option;
    }


    /**
     * ASSCI 排序
     * @param int $type
     */
    public function getPetLetter( $type = 2 ){

        $lists = $this->where(['pet_type'=> $type])->field('pet_variety,pet_letter,pet_variety_id')->select();
        foreach( $lists as $k => $v ){
            if( $v['pet_letter'] == 'A' || $v['pet_letter'] == 'B' || $v['pet_letter'] == 'C' ){
                $data['A-C']['name'] = 'A-C';
                $data['A-C']['lists'][] = $v;

            }
            else if( $v['pet_letter'] == 'D' || $v['pet_letter'] == 'E' || $v['pet_letter'] == 'F' ){

                $data['D-F']['name'] = 'D-F';
                $data['D-F']['lists'][] = $v;
            }
            else if( $v['pet_letter'] == 'G' || $v['pet_letter'] == 'H' || $v['pet_letter'] == 'I' ){

                $data['H-I']['name'] = 'H-I';
                $data['H-I']['lists'][] = $v;
            }
            else if( $v['pet_letter'] == 'J' || $v['pet_letter'] == 'K' || $v['pet_letter'] == 'L' ){

                $data['J-L']['name'] = 'J-L';
                $data['J-L']['lists'][] = $v;
            }
            else if( $v['pet_letter'] == 'M' || $v['pet_letter'] == 'N' || $v['pet_letter'] == 'O' ){

                $data['M-O']['name'] = 'M-O';
                $data['M-O']['lists'][] = $v;
            }
            else if( $v['pet_letter'] == 'P' || $v['pet_letter'] == 'Q' || $v['pet_letter'] == 'R' ){

                $data['P-R']['name'] = 'P-R';
                $data['P-R']['lists'][] = $v;
            }
            else if( $v['pet_letter'] == 'S' || $v['pet_letter'] == 'T' || $v['pet_letter'] == 'U' ){

                $data['S-U']['name'] = 'S-U';
                $data['S-U']['lists'][] = $v;
            }
            else if( $v['pet_letter'] == 'V' || $v['pet_letter'] == 'W' || $v['pet_letter'] == 'X' ){

                $data['V-X']['name'] = 'V-X';
                $data['V-X']['lists'][] = $v;
            }
            else if( $v['pet_letter'] == 'Y' || $v['pet_letter'] == 'Z'  ){

                $data['Y-Z']['name'] = 'Y-Z';
                $data['Y-Z']['lists'][] = $v;
            }
        }

        return $data;

    }

}