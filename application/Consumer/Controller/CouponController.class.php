<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/30
 * Time: 上午11:19
 */

namespace Consumer\Controller;


use Common\Controller\AdminbaseController;
use Community\Model\ComLevelModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\TicketModel;

class CouponController extends AdminbaseController
{
    private $coupon_model;
    private $com_level_model;
    private $ticket_model;
    private $com_score_model;

    public function __construct()
    {
        parent::__construct();
        $this->coupon_model = new CouponModel();
        $this->com_level_model = new ComLevelModel();
        $this->ticket_model = new TicketModel();
        $this->com_score_model = new ComScoreModel();
    }


    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $where['coupon_number'] = $keyword;
            $_GET['keyword'] = $keyword;
        }

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b ON a.tid = b.id';
        $count = $this->coupon_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->coupon_model
            ->alias('a')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->join($join)
            ->where($where)
            ->field('a.*,b.price,b.ttype')
            ->order('a.coupon_id desc')
            ->select();

        if (!empty($keyword)) dump($result);
        $categorys = '';

        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Coupon/delete', array('coupon_id' => $v['coupon_id'])) . '">删除</a>';

            $result[$k]['store_img'] = json_decode($result[$k]['store_img'], true);
            $result[$k]['store_img']['thumb'];

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['coupon_number'] . '</td>
            <td>' . $result[$k]['mid'] . '</td>
            <td>' . $result[$k]['price'] . '</td>
            <td>' . (time() > $v['expiration_time'] ? '过期' : ($this->coupon_model->getStatusValues($result[$k]['cou_status']))) . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['expiration_time']) . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';
//            <td>' . ($result[$k]['ttype'] == 1 ? '全场通用' : '店铺通用') . '</td>
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }


    public function add(){

        $lists = $this->ticket_model->where(array('status'=> 2 ))->select();
        $category = '';
        foreach( $lists as $k => $v ){
            $category .= '<option value="'.$v['id'].'"> '.$v['describe'] .'</option>';
        }
        $this->assign( 'category',$category );
        $this->display();

    }

    public function add_post(){

        $level   = I('post.sco_level');
        $ticket  = I('post.ticket_id');
        $com_score = $this->com_score_model->where(['sco_level'=>$level])->field('sco_member_id')->select();
        $ticket_mo = $this->ticket_model->where(['id' => $ticket])->field('validity,ttype')->find();

        foreach( $com_score as $k=> $v ){
            $data = [
                'mid'=> $v['sco_member_id'],
                'tid'=> $ticket,
                'coupon_number' =>$this->coupon_model->getCouponnumber(),
                'create_time' => time(),
                'expiration_time' => strtotime("+".$ticket_mo['validity']." day"),
                'cou_type' =>$ticket_mo['ttype'],
                'cou_status'=>CouponModel::STATUS_VALIDITY,
            ];
            $this->coupon_model->add($data);
        }

        $this->success('success');
    }


    public function delete(){

        $coupon_id = intval( I('coupon_id') );
        $result = $this->coupon_model->delete($coupon_id);
        if( $result ){
            $this->success('success');
        }else{
            $this->error('error');
        }

    }

}