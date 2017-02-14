<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/29
 * Time: 17:20
 */

namespace Marriage\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\PetModel;

class PublishController extends AdminbaseController
{
    private $pet_model;

    public function __construct()
    {
        parent::__construct();
        $this->pet_model = new PetModel();
    }

    public function lists() {
        $fields = [
            'nickname' => ["field" => "ego_member.nickname", "operator" => "=", 'datatype' => 'string'],
            'pe_status' => ["field" => "pe_status", "operator" => "=", 'datatype' => 'string'],
        ];
//        dump($_POST);
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

        $count = $this->pet_model
            ->where($where)
            ->alias('a')
            ->join('ego_pet_type ON a.pe_type = ego_pet_type.pet_variety_id')
            ->join('LEFT JOIN ego_member  on a.pe_member_id = ego_member.id ')
            ->count();
        $page = $this->page($count,C('PAGE_NUMBER'));
        $list  = $this->pet_model
            ->alias('a')
            ->join('ego_pet_type ON a.pe_type = ego_pet_type.pet_variety_id')
            ->join('LEFT JOIN ego_member  on a.pe_member_id = ego_member.id ')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*,nickname, ego_pet_type.pet_variety')
            ->where($where)
            ->order('a.create_time desc')
            ->select();

        $marriage = '';

//        dump($list);
        foreach( $list as $k => $v ){
//            dump($v['pe_state']);
            $status = $this->pet_model->getCheck($v['pe_status']);
            if($status == '待审核') {
                $method = '待审核 | '. '<a href="' . U('Publish/edit_status', ['id' => $v['pid'],'status' => '2']) . '">审核通过</a>'.' | '. '<a href="' . U('Publish/edit_status', ['id' => $v['pid'],'status' => '3']) . '">审核拒绝</a>';
            } elseif($status == '审核通过' && $v['pe_state'] == 1) {
                $method = '审核通过 | '. '<a href="' . U('Publish/dropList', ['id' => $v['pid']]) . '">下架</a>';
            } elseif($status == '审核拒绝') {
                $method = '审核拒绝';
            } elseif($status == '审核通过' && $v['pe_state'] == 2) {
                $method = '审核通过 | '. '<a href="' . U('Publish/upList', ['id' => $v['pid']]) . '">上架</a>';
            }

            $marriage .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . '<a href="' . U('Publish/detail', array('id' => $v['pid'])) . '">'.$v['pe_name'].'</a>' . '</td>
                            <td>' . $v['pet_variety']  . '</td>
                            <td>' . $v['pe_age'] . '</td>
                            <td>' . $v['pe_area'] . '</td>
                            <td>' . $v['pe_breeding'] . '</td>
                            <td>' . $v['pe_phone'] . '</td>
                            <td>' . date("Y-m-d H:i:s",$v['create_time']) . '</td>
                            <td>' . $method . '</td>
                          </tr>';
        }


        $this->assign('Page',$page->show());
        $this->assign('lists', $marriage);
        $this->assign('formget', I(''));
        $this->display();
    }

    public function detail() {
        $id = I('get.id');
        $info = $this->pet_model
            ->alias('a')
            ->join('ego_pet_type ON a.pe_type = ego_pet_type.pet_variety_id')
            ->field('ego_pet_type.pet_variety,a.pid, a.pe_name, pe_age, pe_picture, pe_breeding, pe_area ,pe_phone,pe_status')
            ->where(array('pid' => $id))
            ->find();

        $imageurl = json_decode($info['pe_picture'], true);
        $img = '';
        foreach ($imageurl as $k => $v) {
            $img .= '<img style="width: 100px; margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src="'.setUrl($v).'?imageView2/1/w/200/h/200"/>';
        }
        $status = $this->pet_model->getCheck($info['pe_status']);
//        dump($info['pe_status']);
        $method = '';
        if($status == '待审核') {
            $method = '<a href="' . U('Publish/edit_status', ['id' => $info['pid'],'status' => '2']) . '">审核通过</a>'.' | '. '<a href="' . U('Publish/edit_status', ['id' => $info['pid'],'status' => '3']) . '">审核拒绝</a>';
        } elseif($status == '审核通过' && $v['pe_state'] == 1) {
            $method = '审核通过 | '. '<a href="' . U('Publish/dropList', ['id' => $v['pid']]) . '">下架</a>';
        } elseif($status == '审核拒绝') {
            $method = '审核拒绝';
        } elseif($status == '审核通过' && $v['pe_state'] == 2) {
            $method = '审核通过 | '. '<a href="' . U('Publish/upList', ['id' => $v['pid']]) . '">上架</a>';
        }
//        dump($method);
        $detail = '<dl class="dl-horizontal">
                <dt>'.'宠物类型:'.'</dt>
                <dd>'.$info['pet_variety'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>'.'宠物名称:'.'</dt>
                    <dd style="margin-right: 30%;">'.$info['pe_name'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>'.'宠物年龄:'.'</dt>
                    <dd>'.$info['pe_age'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>'.'所在城市:'.'</dt>
                    <dd>'.$info['pe_area'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>'.'宠物图片:'.'</dt>
                    <dd>'.$img.'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>'.'配种价格:'.'</dt>
                    <dd>'.$info['pe_breeding'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>'.'联系电话:'.'</dt>
                    <dd>'.$info['pe_phone'].'</dd>
                </dl>
                <div class="form-actions" style="padding-left: 100px;">
                    '.$method.'
                </div>';
//        dump($detail);
        $this->assign('detail', $detail);
        $this->display();
    }

    public function edit_status() {
        $id= I('get.id');
        $status = I('get.status');
        $rst = $this->pet_model->editStatus($id, $status);
        if($rst) {
            $this->success('', U('Publish/lists'));
        } else {
            $this->error();
        }
    }

    /**
     * 下架
     */
    public function dropList() {
        $id= I('get.id');
        $rst = $this->pet_model->where(array('pid' => $id))->save(array('pe_state' => 2));
        if($rst) {
            $this->success();
        } else {
            $this->error();
        }
    }

    /**
     * 上架
     */
    public function upList() {
        $id= I('get.id');
        $rst = $this->pet_model->where(array('pid' => $id))->save(array('pe_state' => 1));
        if($rst) {
            $this->success();
        } else {
            $this->error();
        }
    }
}