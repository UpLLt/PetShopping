<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/22
 * Time: 下午4:30
 */

namespace Common\Model;


/**
 * 宠物基类
 * Class PetModel
 * @package Issue\Model
 */
class PetModel extends CommonModel
{
    const PET_TYPE_CAT = 1;
    const PET_TYPE_DOG = 2;

    const CHECK_1 = 1;//待审核
    const CHECK_2 = 2;//审核通过
    const CHECK_3 = 3;//审核拒绝

    /**
     * 获取审核状态
     *
     * @param $value
     *
     * @return string
     */
    public function getCheck($value)
    {
        switch ($value) {
            case self::CHECK_1:
                return '待审核';
            case self::CHECK_2:
                return '审核通过';
            case self::CHECK_3:
                return '审核拒绝';
        }
    }

    /**
     * 年龄
     * @var array
     */
    public $age = [
        [
            'id'   => '1',
            'name' => '幼年',
        ],
        [
            'id'   => '2',
            'name' => '中年',
        ],
        [
            'id'   => '3',
            'name' => '老年',
        ],
        [
            'id'   => '4',
            'name' => '通用',
        ],
    ];

    /**
     * 年龄
     * @var array
     */
    public $agepet = [
        [
            'id'   => '1',
            'name' => '幼年',
        ],
        [
            'id'   => '2',
            'name' => '中年',
        ],
        [
            'id'   => '3',
            'name' => '老年',
        ],

    ];

    /**
     * 体型
     * @var array
     */
    public $body_type = [
        [
            'id'   => '1',
            'name' => '小型',
        ],
        [
            'id'   => '2',
            'name' => '中型',
        ],
        [
            'id'   => '3',
            'name' => '大型',
        ],
        [
            'id'   => '4',
            'name' => '通用',
        ],

    ];

    /**
     * 体型
     * @var array
     */
    public $body_type_more = [
        [
            'id'   => '1',
            'name' => '小型',
        ],
        [
            'id'   => '2',
            'name' => '中型',
        ],
        [
            'id'   => '3',
            'name' => '大型',
        ],
        [
            'id'   => '4',
            'name' => '通用',
        ],
    ];


    /**
     * 体型2
     * @var array
     */
    public $age_more_type = [
        [
            'id'   => '1',
            'name' => '幼年',
        ],
        [
            'id'   => '2',
            'name' => '中年',
        ],
        [
            'id'   => '3',
            'name' => '老年',
        ],
        [
            'id'   => '4',
            'name' => '通用',
        ]
    ];


    /**
     * 毛长
     * @var array
     */
    public $fur = [
        [
            'id'   => '1',
            'name' => '短毛',
        ],
        [
            'id'   => '2',
            'name' => '中长',
        ],
        [
            'id'   => '3',
            'name' => '长',
        ],
    ];


    public $color = [
        [
            'id'   => '1',
            'name' => '白色',
        ],
        [
            'id'   => '2',
            'name' => '棕色',
        ],
        [
            'id'   => '3',
            'name' => '黑色',
        ],
        [
            'id'   => '4',
            'name' => '杂色',
        ],
    ];

    /**
     * 获取宠物类型
     *
     * @param $id 宠物id
     *
     * @return mixed 类型id
     */
    public function getPetType($id)
    {
        return $this->where(['id' => $id])->getField('pet_type');
    }

    /**
     *
     * 获取宠物类型
     *
     * @param $id 宠物id
     *
     * @return string 中文
     */
    public function getPetTypetoString($id)
    {
        return $this->petTypetoString($this->getPetType($id));
    }

    /**
     * 类型匹配
     *
     * @param $type_id 类型id
     *
     * @return string 类型对应中文字符串
     */
    public function petTypetoString($type_id)
    {
        switch ($type_id) {
            case self::PET_TYPE_CAT:
                return '猫';
                break;
            case self::PET_TYPE_DOG:
                return '狗';
                break;
            default:
                return '通用';
                break;
        }
    }

    /**
     * 获取年龄的描述
     *
     * @param $id
     *
     * @return string
     */
    public function getPetAgetoString($id)
    {
        foreach ($this->age as $k => $v) {
            if ($v['id'] == $id) return $v['name'];
        }
        return '错误的参数';
    }

    /**
     * 获取毛长描述
     *
     * @param $id
     *
     * @return string
     */
    public function getPetFurtoString($id)
    {
        foreach ($this->fur as $k => $v) {
            if ($v['id'] == $id) return $v['name'];
        }
        return '错误的参数';
    }


    /**
     * 获取颜色描述
     *
     * @param $id
     *
     * @return string
     */
    public function getPetColorString($id)
    {
        foreach ($this->color as $k => $v) {
            if ($v['id'] == $id) return $v['name'];
        }
        return '错误的参数';
    }


    /**
     * 获取年龄 option
     *
     * @param string $id
     *
     * @return string
     */
    public function getAgeOption($id = '')
    {
        $option = '';
        $state = '';
        foreach ($this->age as $k => $v) {
            if ($id) $state = $v['id'] == $id ? 'selected="selected"' : '';
            $option .= '<option ' . $state . ' value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        return $option;
    }


    /**
     * 获取年龄 option
     *
     * @param string $id
     *
     * @return string
     */
    public function getAgePetOption($id = '')
    {
        $option = '';
        $state = '';
        foreach ($this->agepet as $k => $v) {
            if ($id) $state = $v['id'] == $id ? 'selected="selected"' : '';
            $option .= '<option ' . $state . ' value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        return $option;
    }

    /**
     * 获取年龄 option
     *
     * @param string $id
     *
     * @return string
     */
    public function getAgeMoreOption($id = '')
    {
        $option = '';
        $state = '';
        foreach ($this->age_more_type as $k => $v) {
            if ($id) $state = $v['id'] == $id ? 'selected="selected"' : '';
            $option .= '<option ' . $state . ' value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        return $option;
    }



    /**
     * 获取毛色 option
     *
     * @param string $id
     *
     * @return string
     */
    public function getFurOption($id = '')
    {
        $option = '';
        $state = '';
        foreach ($this->fur as $k => $v) {
            if ($id) $state = $v['id'] == $id ? 'selected="selected"' : '';
            $option .= '<option ' . $state . ' value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }

        return $option;
    }

    /**
     * 获取毛色 option
     *
     * @param $id
     *
     * @return string
     */
    public function getColorOption($id)
    {
        $option = '';
        $state = '';
        foreach ($this->color as $k => $v) {
            if ($id) $state = $v['id'] == $id ? 'selected="selected"' : '';
            $option .= '<option ' . $state . ' value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        return $option;
    }

    /**
     * 修改审核状态
     *
     * @param $id
     * @param $status
     *
     * @return bool
     */
    public function editStatus($id, $status)
    {
        $where['pid'] = $id;
        $data['pe_status'] = $status;
        return $this->where($where)->save($data);
    }


    /**
     * 获取体型描述
     *
     * @param $id
     *
     * @return string
     */
    public function getBodyTypeString($id)
    {
        foreach ($this->body_type as $k => $v) {
            if ($v['id'] == $id) return $v['name'];
        }
        return '错误的参数';
    }




    /**
     * 获取体型 option
     *
     * @param string $id
     *
     * @return string
     */
    public function getBodyTypeOption($id = '')
    {
        $option = '';
        $state = '';
        foreach ($this->body_type as $k => $v) {
            if ($id) $state = $v['id'] == $id ? 'selected="selected"' : '';
            $option .= '<option ' . $state . ' value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        return $option;
    }

    /**
     * 获取体型 option
     *
     * @param string $id
     *
     * @return string
     */
    public function getBodyTypeMoreOption($id = '')
    {
        $option = '';
        $state = '';
        foreach ($this->body_type_more as $k => $v) {
            if ($id) $state = $v['id'] == $id ? 'selected="selected"' : '';
            $option .= '<option ' . $state . ' value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        return $option;
    }

    /**
     * 获取性别
     *
     * @param $sex
     *
     * @return string
     */
    public function getSextoString($sex)
    {
        if ($sex == 1) {
            return '公';
        } else if ($sex == 2) {
            return '母';
        } else {
            return '未知';
        }
    }


    /**
     * 根据生日算年龄
     *
     * @param $birthday
     *
     * @return bool|string
     */
    public function getage($birthday)
    {
//        list($year, $month, $day) = explode("-", $birthday);
//        $year_diff = date("Y") - $year;
//        $month_diff = date("m") - $month;
//        $day_diff = date("d") - $day;
//        if ($day_diff < 0 || $month_diff < 0)
//            $year_diff--;
//        return $year_diff;


        $date1_stamp=time();
        $date2_stamp=strtotime($birthday);
        list($date_1['y'],$date_1['m'])=explode("-",date('Y-m',$date1_stamp));
        list($date_2['y'],$date_2['m'])=explode("-",date('Y-m',$date2_stamp));
        return abs($date_1['y'] - $date_2['y'])*12 + ( $date_1['m'] - $date_2['m']);




    }




    /**
     * 获取猫狗 option
     *
     * @param $id
     *
     * @return string
     */
    public function getPetOption($id = '')
    {
        $option = '';
        if ($id == self::PET_TYPE_CAT)
            $option .= '<option selected="selected" value="' . self::PET_TYPE_CAT . '">猫</option>';
        else
            $option .= '<option value="' . self::PET_TYPE_CAT . '">猫</option>';

        if ($id == self::PET_TYPE_DOG)
            $option .= '<option selected="selected" value="' . self::PET_TYPE_DOG . '">狗</option>';
        else
            $option .= '<option value="' . self::PET_TYPE_DOG . '">狗</option>';

        return $option;
    }


    /**
     * 产地
     * @var array
     */
    public $make_place = [
        [
            'id'    => 1,
            'name' => '国产',
        ],
        [
            'id'    => 2,
            'name' => '进口',
        ],
    ];
}