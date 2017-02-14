<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/29
 * Time: 上午10:36
 */

namespace Appapi\Controller;


use Common\Model\PetModel;
use Common\Model\PetTypeModel;


/**
 * 宠物基本信息接口
 * Class PetController
 * @package Appapi\Controller
 */
class PetController extends ApibaseController
{
    private $pettype_model;

    public function __construct()
    {
        $this->pettype_model = new PetTypeModel();
        parent::__construct();
    }

    /**
     * 获取品种列表
     */
    public function getPetCategory()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $pet_type = I('post.pet_type');
        $this->checkparam($pet_type);

//        F('PetCategoryCat', null);
//        F('PetCategoryDog', null);

        /*if ($pet_type == PetModel::PET_TYPE_CAT) {
            $result = F('PetCategoryCat');
        } else if ($pet_type == PetModel::PET_TYPE_DOG) {
            $result = F('PetCategoryDog');
        } else {

        }*/

        if (!$result) {
            $result = $this->pettype_model
                ->where(['pet_type' => $pet_type])
                ->field('pet_variety_id,pet_variety,pet_letter')
                ->order('pet_letter asc')
                ->select();

            $default = [[
                'pet_variety_id' => '',
                'pet_variety'    => '全部',
                'pet_letter'     => '',
            ]];

            $result = array_merge($default, $result);

            if ($pet_type == PetModel::PET_TYPE_CAT) {
                F('PetCategoryCat', $result);
            } else if ($pet_type == PetModel::PET_TYPE_DOG) {
                F('PetCategoryDog', $result);
            } else {

            }
        }

        exit($this->returnApiSuccess($result));
    }


}