<?php
namespace Issue\Controller;

use Common\Controller\AdminbaseController;
use Common\Model\PetModel;
use Common\Model\PetTypeModel;
use Issue\Model\ProductPetModel;
use Think\Controller;

/**
 * 卖宠物
 * Class IndexController
 * @package Issue\Controller
 */
class IndexController extends AdminbaseController
{

    private $product_pet_model;

    private $pettype_model;

    public function __construct()
    {
        $this->product_pet_model = new ProductPetModel();
        $this->pettype_model = new PetTypeModel();

        parent::__construct();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {

        $fields = [
            'keyword'  => ["field" => "pet_name", "operator" => "like", 'datatype' => 'string'],
            'pet_type' => ["field" => "pet_type", "operator" => "=", 'datatype' => 'string'],
        ];

        $where_ands = [];
        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }

        $where = join(" and ", $where_ands);

        $where .= empty($where) ? " `show` = 1 " :" and  `show` = 1 ";


        $count = $this->product_pet_model
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->product_pet_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('create_time desc')
            ->select();
//        dump($this->product_pet_model->getLastSql());exit;
//        dump($this->product_pet_model->getLastSql());exit;

        $tablebody = '';
        foreach ($result as $k => $v) {

//            class="js-ajax-delete"
            $result[$k]['str_manage'] = '<a class="" href="' . U('Index/edit', ['id' => $v['id']]) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a  class="js-ajax-delete" href="' . U('Index/delete', ['id' => $v['id']]) . '">删除</a>';

            if($v['status'] == 0 && $v['hot'] == 0 && $v['show'] == 1) {
                $hot = '<a class="" href="' . U('Index/uphot', ['id' => $v['id'], 'hot' => 1]) . '">推荐上热门</a>';
            } elseif($v['status'] == 0 && $v['hot'] == 1 && $v['show'] == 1) {
                $hot = '<a class="" href="' . U('Index/uphot', ['id' => $v['id'], 'hot' => 0]) . '">下热门</a>';
            } else {
                $hot = '-';
            }


            $tablebody .= '<tr>
                                <td>' . ($k + 1) . '</td>
                                <td>' . ($v['id']) . '</td>
                                <td>' . $this->product_pet_model->petTypetoString($v['pet_type']) . '</td>
                                <td>' . $v['pet_name'] . '</td>
                                <td>' . $this->product_pet_model->getPetColorString($v['pet_colour']) . '</td>
                                <td>' . $v['pet_age'] . '</td>
                                <td>' . $v['pet_price'] . '</td>
                                <td>' . $v['pet_vaccine_z'] . '</td>
                                <td>' . $v['pet_vaccine_m'] . '</td>
                                <td>' . $v['pet_phone'] . '</td>
                                <td>' . ($v['status'] ? '已出售' : '未出售') . '</td>
                                <td>' . date('Y-m-d h:i:s', $v['create_time']) . '</td>
                                <td>'.$hot.'</td>
                                <td>' . $result[$k]['str_manage'] . '</td>
                           </tr>';
        }
        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }


    /**
     * 发布宠物
     */
    public function sell()
    {
        $this->assign('options', $this->pettype_model->getVarietyOption(PetModel::PET_TYPE_CAT));
        $this->assign('ageoptions', $this->pettype_model->getAgePetOption());
        $this->assign('furoptions', $this->pettype_model->getFurOption());
        $this->assign('coloroptions', $this->pettype_model->getColorOption());


        $this->display();
    }


    /**
     * 获取分类
     */
    public function getCategory()
    {
        if (IS_AJAX) {
            $id = I('post.id');
            if (empty($id)) $this->ajaxReturn(['msg' => '']);
            $option = $this->pettype_model->getVarietyOption($id);
            $this->ajaxReturn(['msg' => $option, 'status' => 'success']);
        }
    }


    public function add_post()
    {
        if (IS_POST) {
            if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['pet_picture'][] = ["url" => $photourl, "alt" => $_POST['photos_alt'][$key]];
                }
            }

            $pet = I("post.post");
            $pet['pet_picture'] = json_encode($_POST['pet_picture']);
            $pet['pet_content'] = htmlspecialchars_decode($pet['pet_content']);

            if (!$this->product_pet_model->create($pet)) $this->error($this->product_pet_model->getError());

            if (!$this->product_pet_model->add()) {
                $this->error('error');
            } else {
                $this->success('success');
            }


        }
    }


    /**
     * 软删除
     *
     * @param $id
     */
    public function delete($id)
    {
        if (empty($id)) $this->error('empty');
        if ($this->product_pet_model->deletePet($id) === false) $this->error('error');
        $this->success('success');
    }

    /**
     * 编辑
     *
     * @param $id
     */
    public function edit($id)
    {
        if (empty($id)) $this->error('empty');

        $result = $this->product_pet_model->find($id);

        $this->assign('options', $this->pettype_model->getVarietyOption($result['pet_type'], $result['pet_variety_id']));
        $this->assign('ageoptions', $this->pettype_model->getAgePetOption($result['pet_age']));
        $this->assign('furoptions', $this->pettype_model->getFurOption($result['pet_fur']));
        $this->assign('coloroptions', $this->pettype_model->getColorOption($result['pet_colour']));


        $pet_picture = json_decode($result['pet_picture'], true);
        if (!is_array($pet_picture)) $pet_picture = '';
        $this->assign('pet_picture', $pet_picture);
        $this->assign('data', $result);
        $this->display();
    }

    /**
     * 编辑提交
     */
    public function edit_post()
    {
        if (IS_POST) {
            if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['pet_picture'][] = ["url" => $photourl, "alt" => $_POST['photos_alt'][$key]];
                }
            }

            $pet = I("post.post");
            $pet['pet_picture'] = json_encode($_POST['pet_picture']);
            $pet['pet_content'] = htmlspecialchars_decode($pet['pet_content']);

            if (!$this->product_pet_model->create($pet)) $this->error($this->product_pet_model->getError());

            if ($this->product_pet_model->save() === false) $this->error('error');

            $this->success('success');
        }
    }

    /**
     * 推荐上/下首页热门
     */
    public function upHot() {
        $id = I('get.id');
        $hot = I('get.hot');

        if($hot == 1) {
            $count = $this->product_pet_model
                ->where(array('status' => 0, 'show' => 1, 'hot' => 1))
                ->count();
            if($count >= 8) {
                $this->error('推荐热门不能超过8个，请先行取消其他热门再设置');
            }
        }
        $rst = $this->product_pet_model
            ->where(array('id' => $id))
            ->setField('hot', $hot);
        if($rst) {
            $this->success('成功');
        } else {
            $this->error('失败，请重试');
        }

    }


}
