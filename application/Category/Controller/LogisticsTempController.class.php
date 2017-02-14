<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/30
 * Time: 下午5:51
 */

namespace Category\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\LogisticsTempModel;

/**
 * 运输模板
 * Class LogisticsTempController
 * @package Category\Controller
 */
class LogisticsTempController extends AdminbaseController
{
    private $logistics_temp_model;

    public function __construct()
    {
        parent::__construct();
        $this->logistics_temp_model = new LogisticsTempModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $fields = [
            'keyword' => ["field" => "temp_name", "operator" => "like", 'order' => 'string'],
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

        $count = $this->logistics_temp_model
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->logistics_temp_model
            ->alias('a')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();


        $tablebody = '';
        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a href="' . U('LogisticsTemp/edit', ['id' => $v['temp_id']]) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('LogisticsTemp/delete', ['temp_id' => $v['temp_id']]) . '">删除</a>';

            $tablebody .= '<tr>
                                <td>' . ($k + 1) . '</td>
                                <td>' . $v['temp_name'] . '</td>
                                <td>' . $this->logistics_temp_model->getTempSwitchtoString($v['temp_switch']) . '</td>
                                <td>' . ($v['in_number'] . '件内' . $v['in_price'] . '元 ，每增加' . $v['add_number'] . '件 , 增加' . $v['add_price']) . '元</td>
                                <td>满' . $v['full_number'] . '件 | 满' . $v['full_total'] . '元</td>
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


    public function delete(){
        $result = $this->logistics_temp_model->delete(I('temp_id'));
        if( $result ) {
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }


    public function add_post()
    {
        if (IS_POST) {
            if ($this->logistics_temp_model->create()) {
                if ($this->logistics_temp_model->add()) {
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            } else {
                $this->error($this->logistics_temp_model->getError());
            }
        }
    }


    public function edit($id)
    {
        if (empty($id)) $this->error('empty');
        $result = $this->logistics_temp_model->find($id);
        $this->assign('data', $result);
        $this->display();
    }


    public function edit_post()
    {
        if (IS_POST) {
            if ($this->logistics_temp_model->create()) {
                if ($this->logistics_temp_model->save() === false) {
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