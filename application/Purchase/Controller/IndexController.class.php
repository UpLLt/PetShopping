<?php
namespace Purchase\Controller;

use Common\Controller\AdminbaseController;
use Common\Model\OrderModel;
use Common\Model\PetModel;
use Common\Model\PetTypeModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;
use Purchase\Model\SellPetModel;
use Think\Controller;

class IndexController extends AdminbaseController
{
    private $sell_pet_model, $pet_typemodel, $wallet_model, $wallet_bill_model;

    public function __construct()
    {
        parent::__construct();
        $this->sell_pet_model = new SellPetModel();
        $this->pet_typemodel = new PetTypeModel();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
    }

    public function lists()
    {
        $fields = [
            'se_phone' => ["field" => "a.se_phone", "operator" => "=", 'datatype' => 'string'],
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
        $this->_lists($where);
        $this->display();
    }

    private function _lists($where)
    {

        $count = $this->sell_pet_model
            ->count();
        $page = $this->page($count, 20);
        $result = $this->sell_pet_model
            ->alias('a')
            ->join('left join ego_member as b on a.mid = b.id')
            ->join('left join ego_pet_type as c on a.pet_variety = c.pet_variety_id')
            ->join('')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*, b.nickname,b.username, c.pet_variety')
            ->order('a.create_time desc')
            ->where($where)
            ->select();
        $tablebody = '';

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a class="" href="' . U('Index/info', ['id' => $v['id']]) . '">详情</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Index/delete', ['id' => $v['id']]) . '">删除</a>';

            $ststus[$k]['str_mamage'] = '';
            if($v['status'] == SellPetModel::SELL_PET_WAIT) {
                $ststus[$k]['str_mamage'] = '待审核 | '.'<a href="'.U('Index/edit_status', ['id' => $v['id'], 'status' => SellPetModel::SELL_PET_OK]).'">审核通过</a>'.' | '.'<a href="'.U('Index/edit_status', ['id' => $v['id'], 'status' => SellPetModel::SELL_PET_REFUSE]).'">审核拒绝</a>';
            }
            if($v['status'] == SellPetModel::SELL_PET_OK && !empty($v['se_deal_price'])) {
                $ststus[$k]['str_mamage'] = '审核通过 | '.'<a href="'.U('Index/edit_status', ['id' => $v['id'], 'status' => SellPetModel::SELL_PET_COMPLETE]).'">已完成</a>'.' | '.'<a data-toggle="modal" data-target="#myModal"  class="add_ext" name="'.$v['id'].'"  onclick="">定价</a>';
            } elseif($v['status'] == SellPetModel::SELL_PET_OK && empty($v['se_deal_price'])) {
                $ststus[$k]['str_mamage'] = '审核通过 | '.'<a data-toggle="modal" data-target="#myModal"  class="add_ext" name="'.$v['id'].'"  onclick="">定价</a>';
            }elseif($v['status'] == SellPetModel::SELL_PET_REFUSE) {
                $ststus[$k]['str_mamage'] = '审核拒绝';
            } elseif($v['status'] == SellPetModel::SELL_PET_COMPLETE) {
                $ststus[$k]['str_mamage'] = '已完成';
            }


            $tablebody .= '<tr>
                                <td>' . ($k + 1) . '</td>
                                <td>' . $v['nickname'] . '</td>
                                <td>' . $v['username'] . '</td>
                                <td>' .  $this->sell_pet_model->petTypetoString($v['pet_type']) .'</td>
                                <td>' . $v['se_age'] . '</td>
                                <td>' . ($v['se_count_male'] + $v['se_count_female']) . '</td>
                                <td>' . $v['se_price'].'元/每只' . '</td>
                                <td>' . $v['se_total_price'] . '</td>
                                <td>' . ($v['se_deal_price'] ? $v['se_deal_price'] : '-') . '</td>
                                <td>' . $v['se_phone'] . '</td>
                                <td>' . $v['se_address'] . '</td>
                                <td>' . date('Y-m-d H:i:s', $v['create_time']) . '</td>
                                <td>' . $ststus[$k]['str_mamage'] . '</td>
                                <td>' . $result[$k]['str_manage'] . '</td>
                           </tr>';
        }


        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }

    /**
     * 修改状态
     */
    public function edit_status() {
        $id = I('get.id');
        $status = I('get.status');

        $this->sell_pet_model->startTrans();
        $iscommit = true;
        $rst = $this->sell_pet_model->editstatus($id, $status);
        if(!$rst) {
            $iscommit = false;
            $error = '3';
        }
        if($status ==  SellPetModel::SELL_PET_COMPLETE) {
            $info = $this->sell_pet_model->where(array('id' => $id))->field('mid, se_deal_price')->find();//dump($info);exit;
            //添加流水
            $before  = $this->wallet_model->getBalance($info['mid']);
            $bill = $this->wallet_bill_model->addBill($info['mid'], $info['se_deal_price'], $before,'卖宠所得', WalletBillModel::BILL_TYPE_IN);
            if(!$bill) {
                $iscommit = false;
                $error = '1';
            }
            //增加余额
            if($this->wallet_model->addMoney($info['mid'], $info['se_deal_price']) == false) {
                $iscommit = false;
                $error = '2';
            }
        }
        if($iscommit) {
            $this->sell_pet_model->commit();
            $this->success();
        } else {
            $this->sell_pet_model->rollback();
            $this->error($error);
        }
    }

    /**
     * 改价
     */
    public function changePrice() {
        $where['id'] = I('post.id');
        $data['se_deal_price'] = I('post.se_deal_price');
        $rst = $this->sell_pet_model->where($where)->save($data);
        if($rst) {
            $this->success('修改成功');
        } else {
            $this->error('失败');
        }
    }

    /**
     * 删除
     */
    public function delete() {
        $where['id'] = I('get.id');
        $rst = $this->sell_pet_model->where($where)->delete();
        if($rst) {
            $this->success();
        } else {
            $this->error();
        }
    }

    public function info() {
        $where['a.id'] = I('get.id');
        $list = $this->sell_pet_model
            ->alias('a')
            ->join('left join ego_member as b on a.mid = b.id')
            ->join('left join ego_pet_type as c on a.pet_variety = c.pet_variety_id')
            ->where($where)
            ->field('a.*, b.nickname, c.pet_variety')
            ->find();
//        dump($list);
        foreach(json_decode($list['se_pic'], true) as $k => $v) {
            $card_img .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src = "'.setUrl($v).'?imageView2/1/w/150/h/150">';
        }
        foreach(json_decode($list['se_ppic'], true) as $k => $v) {
            $pet_img .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src = "'.setUrl($v).'?imageView2/1/w/150/h/150">';
        }
        $ststus[$k]['str_mamage'] = '';
        if($list['status'] == SellPetModel::SELL_PET_WAIT) {
            $ststus['str_mamage'] = '待审核 | '.'<a href="'.U('Index/edit_status', ['id' => $list['id'], 'status' => SellPetModel::SELL_PET_OK]).'">审核通过</a>'.' | '.'<a href="'.U('Index/edit_status', ['id' => $list['id'], 'status' => SellPetModel::SELL_PET_REFUSE]).'">审核拒绝</a>';
        }
        if($list['status'] == SellPetModel::SELL_PET_OK) {
            $ststus['str_mamage'] = '审核通过 | '.'<a href="'.U('Index/edit_status', ['id' => $list['id'], 'status' => SellPetModel::SELL_PET_COMPLETE]).'">已完成</a>'.' | '.'<a data-toggle="modal" data-target="#myModal"  class="add_ext" name="'.$list['id'].'"  onclick="">改价</a>';
        } elseif($list['status'] == SellPetModel::SELL_PET_REFUSE) {
            $ststus['str_mamage'] = '审核拒绝';
        } elseif($list['status'] == SellPetModel::SELL_PET_COMPLETE) {
            $ststus['str_mamage'] = '已完成';
        }
        $detail = '<dl class="dl-horizontal">
                <dt>用户昵称:</dt>
                    <dd>'.$list['nickname'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>宠物分类:</dt>
                    <dd style="margin-right: 30%;">'.($list['pet_variety'] == PetModel::PET_TYPE_CAT ? '猫':'狗').'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>宠物品种:</dt>
                    <dd style="margin-right: 30%;">'.$list['pet_variety'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>宠物年龄:</dt>
                    <dd>'.$list['se_age'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>疫苗情况:</dt>
                    <dd>'.$list['se_vaccine'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>驱虫情况:</dt>
                    <dd>'.$list['se_insert'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>出售条数:</dt>
                    <dd style="margin-right: 30%;">'.$list['se_count_male'].'公'.$list['se_count_female'].'母'.'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>意向价格:</dt>
                    <dd style="margin-right: 30%;">'.$list['se_price'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>成交价格:</dt>
                    <dd style="margin-right: 30%;">'.$list['se_deal_price'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>卖家电话:</dt>
                    <dd style="margin-right: 30%;">'.$list['se_phone'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>描述:</dt>
                    <dd style="margin-right: 30%;">'.$list['se_describe'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>详细地址:</dt>
                    <dd style="margin-right: 30%;">'.$list['se_address'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>身份证号:</dt>
                    <dd style="margin-right: 30%;">'.$list['se_card'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>身份证照片:</dt>
                    <dd style="margin-right: 30%;">'.$card_img.'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>宠物照片:</dt>
                    <dd style="margin-left: 190px;;">'.$pet_img.'</dd>
                </dl><dd style="margin-right: 30%; margin-left: 10%">'.$ststus['str_mamage'].' | '.'<a class="js-ajax-delete" href="' . U('Index/delete', ['id' => $list['id']]) . '">删除</a>'.'</dd>';

        $this->assign('detail', $detail);
        $this->display();
    }
}