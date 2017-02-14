<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/24
 * Time: 下午4:08
 */

namespace Issue\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\OrderModel;
use Common\Model\OrderPetModel;
use Common\Model\RegionModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\MemberModel;


/**
 * 宠物订单管理
 * Class OrderPetController
 * @package Issue\Controller
 */
class OrderPetController extends AdminbaseController
{
    private $order_pet_model;
    private $order_model;
    private $coupon_model;
    private $com_sco_model;
    private $member_model;
    private $region_model;

    public function __construct()
    {
        $this->order_model = new OrderModel();
        $this->order_pet_model = new OrderPetModel();

        $this->coupon_model = new CouponModel();
        $this->com_sco_model = new ComScoreModel();
        $this->member_model = new MemberModel();
        $this->region_model = new RegionModel();
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
            'keyword'  => ["field" => "a.order_sn", "operator" => "like", 'datatype' => 'string'],
            'username' => ["field" => "b.username", "operator" => "like", 'datatype' => 'string'],
            'status'   => ["field" => "a.status", "operator" => "=", 'datatype' => 'string'],
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

        if(  $where ){
            $where = $where . ' and '.'a.order_type='.OrderModel::ORDER_TYPE_PET;
        }else{
            $where['a.order_type'] = OrderModel::ORDER_TYPE_PET;
        }

        $join  = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as b on a.mid = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'order_pet as c on a.id = c.order_id';
//        $join3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_pet as d on b.product_pet_id = b.order_id';
        $count = $this->order_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->order_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->where( $where )
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*,b.username,c.snapshot')
            ->order('create_time desc')
            ->select();

//        dump($result);


        $tablebody = '';
        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a class="" href="' . U('OrderPet/info', ['id' => $v['id']]) . '">详情</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('OrderPet/delete', ['id' => $v['id']]) . '">删除</a>';

            if ($v['status'] == OrderModel::STATUS_PAY_SUCCESS) {
                $result[$k]['str_manage'] .= ' | ';
//                $result[$k]['str_manage'] .= '<a class="" href="' . U('OrderPet/send', ['id' => $v['id']]) . '">发货</a>';
                $result[$k]['str_manage'] .= '<a data-toggle="modal" data-target="#myModal"  class="add_ext"  onclick="" name="'.$v['id'].'">发货</a>';
            }

            $snapshot = json_decode($v['snapshot'], true);

            $tablebody .= '<tr>
                                <td>' . ($k + 1) . '</td>
                                <td>' . $v['order_sn'] . '</td>
                                <td>' . $v['username'] . '</td>
                                <td>' . $snapshot['pet_name'] . '</td>
                                <td>' . $v['order_price'] . '</td>
                                <td>' . $this->order_model->getStatustoString($v['status']) . '</td>
                                <td>' . date('Y-m-d H:i:s', $v['create_time']) . '</td>
                                <td>' . $result[$k]['str_manage'] . '</td>
                           </tr>';
        }

        $this->assign('statusOption', $this->order_model->getStatusOption(I('status')));
        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }


    public function info()
    {
        $id = I('get.id');
        if (empty($id)) $this->error('error');

        $data = $this->order_model->find($id);
        if (!$data) $this->error('error');
        $data_member = $this->member_model->find($data['mid']);


        $data['address'] = json_decode($data['address'], true);

        $data['address']['address'] = $this->region_model->getNamForCode($data['address']['province']).'/'.$this->region_model->getNamForCode($data['address']['city']).'/'.$this->region_model->getNamForCode($data['address']['country']).'/'.$data['address']['address'];

        $data['status'] = $this->order_model->getStatustoString($data['status']);

        $categorys = '';
        $result = $this->order_pet_model
            ->where(['order_id' => $id])
            ->select();

        $total = '';
        foreach ($result as $k => $v) {

            $v['snapshot'] = json_decode($v['snapshot'], true);
            $result[$k]['every_price'] = $v['snapshot']['pet_price'];
            $total += $result[$k]['every_price'];
            $result[$k]['every_price'] = number_format($result[$k]['every_price'], 2);

            $categorys .= '<tr>
                <td>' . ($k + 1) . '</td>
                <td>' . $v['snapshot']['pet_name'] . '</td>
                <td>' . $v['snapshot']['pet_price'] . '</td>
                <td>' . $v['quantity'] . '</td>
                <td>' . $result[$k]['every_price'] . '</td>
                ';
        }

        if ($data['coupon_id'])
            $coupon_price = $this->coupon_model->getCouponValue($data['coupon_id']);

        if ($data['score'])
            $data['score'] = $this->com_sco_model->sconToMoney($data['score']);

        $this->assign('total_price', number_format($total, 2));
        $this->assign('coupon_price', number_format($coupon_price, 2));
        $this->assign('categorys', $categorys);
        $this->assign('data_order', $data);
        $this->assign('data_member', $data_member);
        $this->display();
    }


    public function delete()
    {
        $id = intval(I("get.id"));
        if (empty($id)) $this->error('empty');
        if ($this->order_model->delete($id) !== false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }


    public function add_order_log()
    {
        $order_id = intval(I('order_id'));
        $logistics_number = I('post.logistics_number');
        $logistics_company = I('post.logistics_company');

        if (empty($order_id) || empty($logistics_company) || empty($logistics_number))
            $this->error('数据不能为空');

        $result = $this->order_model
            ->where(['id' => $order_id])
            ->save(['logistics_number' => $logistics_number, 'logistics_company' => $logistics_company]);

        if ($result === false) {
            $this->error('失败');
        } else {
            $this->success('成功');
        }
    }


    /**
     * 发货
     *
     * @param $id
     */
    public function send($id)
    {
        if (empty($id)) $this->error('empty');

        $result = $this->order_model->find($id);
        if (!$result) $this->error('订单不存在');

        if ($result['status'] != OrderModel::STATUS_PAY_SUCCESS) {
            $this->error('订单未付款');
        }

        if ($this->order_model->setStatus($id, OrderModel::STATUS_SEND) === false) {
            $this->error('失败');
        } else {
            $this->success('成功');
        }

    }

    /**
     * 发货并填写运单号
     */
    public function sendNum() {
        $id = I('post.order_id');
        $logistics_number = I('post.logistics_number');
        $logistics_company = I('post.logistics_company');

        if (empty($id)) $this->error('empty');
        if(empty($logistics_number)) $this->error('运单号为空');
        if(empty($logistics_company)) $this->error('快递公司为空');

        $result = $this->order_model->find($id);
        if (!$result) $this->error('订单不存在');

        if ($result['status'] != OrderModel::STATUS_PAY_SUCCESS) {
            $this->error('订单未付款');
        }

        $where['id'] = $id;
        $data = array(
            'logistics_number' => $logistics_number,
            'logistics_company' => $logistics_company,
            'status' => OrderModel::STATUS_SEND,
        );
        $rst = $this->order_model->where($where)->save($data);
        if ($rst === false) {
            $this->error('失败');
        } else {
            $this->success('成功');
        }
    }
}