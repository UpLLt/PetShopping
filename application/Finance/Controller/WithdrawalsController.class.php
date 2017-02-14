<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/8/6
 * Time: 16:32
 */

namespace Finance\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\WithdrawalsModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\MemberModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;

class WithdrawalsController extends AdminbaseController
{

    private $wallet_bill_model ,$wallet_model , $member_model , $com_score_model , $com_record_model ,$withdrawals_model ;

    public function __construct()
    {
        parent::__construct();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
        $this->member_model = new MemberModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_record_model = new ComRecordModel();
        $this->withdrawals_model = new WithdrawalsModel();
    }


    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {

        $fields = array(
            'start_time' => array("field" => "a.create_time", "operator" => ">", 'datatype' => 'time'),
            'end_time' => array("field" => "a.create_time", "operator" => "<", 'datatype' => 'time'),
            'keyword' => array("field" => "b.username", "operator" => "like", 'datatype' => 'string'),
            'select_source'=>array("field" => "a.wi_status", "operator" => "=", 'datatype' => 'string')
        );
        $where_ands = array();
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

        
        $select_source = I('select_source');
        $cource = "";
        $source_array = [ WithdrawalsModel::EXAMINE_WAIT,WithdrawalsModel::EXAMINE_ADONT,WithdrawalsModel::EXAMINE_ERROR,WithdrawalsModel::WITHDRA_SUCCESS];

        foreach ($source_array as $v) {
            if ($select_source && $select_source == $v)
                $cource .= '<option  selected="selected"  value="' . $v . '"> ' . $this->withdrawals_model->getStatusValus($v) . '</option>';
            else
                $cource .= '<option value="' . $v . '"> ' . $this->withdrawals_model->getStatusValus($v) . '</option>';
        }

        $this->assign('source_model', $cource);

        $join   = 'LEFT JOIN '.C('DB_PREFIX').'member as b on a.mid = b.id';
        $count  = $this->withdrawals_model
                     ->alias('a')
                     ->join($join)
                     ->where($where)
                     ->order('a.id desc')
                     ->count();
        $page = $this->page($count,C('PAGE_NUMBER'));

        $result = $this->withdrawals_model
                       ->alias('a')
                       ->join($join)
                       ->where($where)
                       ->limit($page->firstRow . ' , '. $page->listRows)
                       ->field('a.*,b.username')
                       ->order('a.id desc')
                       ->select();


        $categorys = '';
        foreach ($result as $k => $v) {

        //class="js-ajax-dialog-btn"
            if(  $result[$k]['wi_status'] == WithdrawalsModel::EXAMINE_WAIT ){
                $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Withdrawals/wi_success', array('id' => $v['id'] , 'status' => WithdrawalsModel::EXAMINE_ADONT)) . '">'. $this->withdrawals_model->getStatusValus(WithdrawalsModel::EXAMINE_ADONT). '</a>';
                $result[$k]['str_manage'] .= ' | ';
                $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Withdrawals/wi_error', array('id' => $v['id'] , 'status' => WithdrawalsModel::EXAMINE_ERROR)) . '">'. $this->withdrawals_model->getStatusValus(WithdrawalsModel::EXAMINE_ERROR). '</a>';
            }
            if(  $result[$k]['wi_status'] == WithdrawalsModel::EXAMINE_ADONT ){
                $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Withdrawals/wi_with', array('id' => $v['id'])) . '">'. $this->withdrawals_model->getStatusValus(WithdrawalsModel::EXAMINE_ADONT). '</a>';
            }

            if(  $result[$k]['wi_status'] == WithdrawalsModel::EXAMINE_ERROR ){
                $result[$k]['str_manage'] .= $this->withdrawals_model->getStatusValus(WithdrawalsModel::EXAMINE_ERROR);;
            }

            if(  $result[$k]['wi_status'] == WithdrawalsModel::WITHDRA_SUCCESS ){
                $result[$k]['str_manage'] .= $this->withdrawals_model->getStatusValus(WithdrawalsModel::WITHDRA_SUCCESS);
            }

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['username'] . '</td>
            <td>' . $result[$k]['wi_money'] . '</td>
            <td>' . $this->withdrawals_model->getBankStr($result[$k]['wi_bank']) . '</td>
            <td>' . $result[$k]['wi_bank_card'] . '</td>
            <td>' . $result[$k]['wi_bank_name'] . '</td>
            <td>' . $this->withdrawals_model->getStatusValus($result[$k]['wi_status']) . '</td>
            <td>' . $result[$k]['action_id'] . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['update_time']) . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';


        }
        
        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }


    public function wi_success(){
        $id = I('id');
        $status = I('status');

        $withdrawals = $this->withdrawals_model->where(['id'=> $id ])->find();

        $users  = D('users')->where('id='.sp_get_current_admin_id())->field('user_login')->find();

        if( $withdrawals['wi_status'] == WithdrawalsModel:: EXAMINE_WAIT ){
                $data = [
                    'update_time' => time(),
                    'action_id' => $users['user_login'],
                    'wi_status' => $status,
                ];
            $result = $this->withdrawals_model->where( 'id='.$id )->save($data);
        }

        if( $result ){
            $this->success('success');
        }else{
            $this->error('error');
        }

    }


    public function wi_error(){

        $id = I('id');
        $withdrawals = $this->withdrawals_model->where(['id'=> $id ])->find();
        $banlanc = $this->wallet_model->getBalance($withdrawals['mid']);

        $users  = D('users')->where('id='.sp_get_current_admin_id())->field('user_login')->find();
        if( $withdrawals['wi_status'] != WithdrawalsModel:: EXAMINE_WAIT ) $this->error('error');

            $data = [
                'update_time' => time(),
                'action_id' => $users['user_login'],
                'wi_status' => WithdrawalsModel::EXAMINE_ERROR,
            ];
            $this->withdrawals_model->startTrans();
            $is_commit = true;
            $result = $this->withdrawals_model->where( 'id='.$id )->save($data);
            if( $result === false ) $is_commit = false;
            $banlanc = $this->wallet_model->getBalance();

            $wallet_bill = $this->wallet_bill_model->addBill($withdrawals['mid'],$withdrawals['wi_money'],$banlanc,WalletBillModel::BUY_WITHDRAWALS_REFUND,WalletBillModel::BILL_TYPE_IN);

            if( !$wallet_bill ) $is_commit = false;

            $wallet = $this->wallet_model->addMoney($withdrawals['mid'],$withdrawals['wi_money']);
            if( !$wallet ) $is_commit = false;

            if( $is_commit == true ){

                $this->withdrawals_model->commit();
                $this->success('success');

            }else{

                $this->withdrawals_model->rollback();
                $this->error('error');

            }


    }


    public function wi_with(){
        $id = I('id');
        $withdrawals = $this->withdrawals_model->where(['id'=> $id ])->find();
        $users  = D('users')->where('id='.sp_get_current_admin_id())->field('user_login')->find();
        if( $withdrawals['wi_status'] == WithdrawalsModel:: EXAMINE_ADONT ){
            $data = [
                'update_time' => time(),
                'action_id' => $users['user_login'],
                'wi_status' => WithdrawalsModel:: WITHDRA_SUCCESS,
            ];
            $result = $this->withdrawals_model->where( 'id='.$id )->save($data);
        }

        if( $result ){
            $this->success('success');
        }else{
            $this->error('error');
        }

    }

}