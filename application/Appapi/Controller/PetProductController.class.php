<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/24
 * Time: 下午5:09
 */

namespace Appapi\Controller;


use Issue\Model\ProductPetModel;


/**
 * 活体宠物买卖
 * Class PetProductController
 * @package Appapi\Controller
 */
class PetProductController extends ApibaseController
{
    private $product_pet_model;

    public function __construct()
    {
        $this->product_pet_model = new ProductPetModel();
        parent::__construct();
    }


    /**
     * 获取宠物列表
     */
    public function getList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $pet_type = I('post.pet_type');
        $pet_variety_id = I('post.pet_variety');
        $sequence = I('post.sequence');

        $this->checkparam([$pet_type]);

        $field = 'id,pet_name,pet_price,pet_picture as cover';
        $where = [
            'pet_type' => $pet_type,
            'status' => 0,
        ];

        if ($pet_variety_id) {
            $where['pet_variety_id'] = $pet_variety_id;
        }

        if ($sequence == 'asc') {
            $order = 'pet_price asc';
        } else if ($sequence == 'desc') {
            $order = 'pet_price desc';
        } else {
            $order = 'pet_price asc';
        }

        $where['show'] = 1;


        $result = $this->product_pet_model
            ->where($where)
            ->field($field)
            ->order($order ? $order : 'create_time desc')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['create_time'] = dateDefault($v['create_time']);
            if ($v['cover']) {
                $cover = json_decode($v['cover'], true);
                $result[$k]['cover'] = $this->setUrl($cover[0]['url']);
            }
        }

        exit($this->returnApiSuccess($result));
    }


    public function detail()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $id = I('post.id');

        $this->checkparam([$id]);

        $result = $this->product_pet_model
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'pet_type as b on a.pet_variety_id = b.pet_variety_id')
            ->field('a.*,b.pet_variety as pet_variety')
            ->where(['a.id' => $id])
            ->find();

        $result['pet_picture'] = json_decode($result['pet_picture'], true);
        foreach ($result['pet_picture'] as $k => $v) {
            $result['pet_picture'][$k]['url'] = $this->setUrl($result['pet_picture'][$k]['url']);
            unset($result['pet_picture'][$k]['alt']);
        }
        if ($result['pet_picture'])
            $result['cover'] = $result['pet_picture'][0]['url'];

        if (!is_array($result['pet_picture'])) $result['pet_picture'] = [];

        $result['pet_sex'] = $this->product_pet_model->getSextoString($result['pet_sex']);
        $result['pet_colour'] = $this->product_pet_model->getPetColorString($result['pet_colour']);
        $result['pet_age'] = $this->product_pet_model->getPetAgetoString($result['pet_age']);
        $result['pet_fur'] = $this->product_pet_model->getPetFurtoString($result['pet_fur']);

        unset($result['show']);
//        $this->product_pet_model
        $result['detail_url'] = $this->geturl('/Wap/ProductPet/detail/id/' . $result['id']);
        unset($result['pet_content']);

        exit($this->returnApiSuccess($result));
    }
}