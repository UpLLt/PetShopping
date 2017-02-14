<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/22
 * Time: 下午5:18
 */

namespace Category\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\PetTypeModel;

/**
 * 宠物类型管理
 * Class PetCategoryController
 * @package Category\Controller
 */
class PetCategoryController extends AdminbaseController
{
    private $pettype_model;

    public function __construct()
    {
        parent::__construct();
        $this->pettype_model = new PetTypeModel();
    }


    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $fields = [
            'keyword' => ["field" => "pet_variety", "operator" => "like", 'order' => 'string'],
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

        $count = $this->pettype_model
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->pettype_model
            ->alias('a')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $tablebody = '';
        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a href="' . U('PetCategory/edit', ['id' => $v['pet_variety_id']]) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('PetCategory/delete', ['id' => $v['pet_variety_id']]) . '">删除</a>';

            $tablebody .= '<tr>
                                <td>' . ($k + 1) . '</td>
                                <td>' . $v['pet_variety_id'] . '</td>
                                <td>' . $this->pettype_model->petTypetoString($v['pet_type']) . '</td>
                                <td>' . $v['pet_variety'] . '</td>
                                <td>' . $v['pet_letter'] . '</td>
                                <td>' . date('Y-m-d H:i:s', $v['create_time']) . '</td>
                                <td>' . $result[$k]['str_manage'] . '</td>
                           </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }

    public function add()
    {
        $this->display();
    }


    /**
     * 新增宠物类型
     */
    public function add_post()
    {
        if (IS_POST) {
            if ($this->pettype_model->create()) {
                if ($this->pettype_model->add()) {
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            } else {
                $this->error($this->pettype_model->getError());
            }
        }
    }


    public function edit($id)
    {
        if (empty($id)) $this->error('empty');
        $this->assign('data', $this->pettype_model->find($id));
        $this->display();
    }

    public function delete($id)
    {
        if (empty($id)) $this->error('empty');
        if ($this->pettype_model->delete($id))
            $this->success('success');
        else $this->error('error');
    }


    public function edit_post()
    {
        if (IS_POST) {
            if ($this->pettype_model->create()) {
                if ($this->pettype_model->save() === false) {
                    $this->error('失败');
                } else {
                    $this->success('成功');
                }
            } else {
                $this->error($this->pettype_model->getError());
            }
        }
    }
}