<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/8/6
 * Time: 16:32
 */

namespace Finance\Controller;


use Common\Controller\AdminbaseController;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\MemberModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;

class AReportController extends AdminbaseController
{

    private $wallet_bill_model, $wallet_model, $member_model, $com_score_model, $com_record_model;

    public function __construct()
    {
        parent::__construct();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
        $this->member_model = new MemberModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_record_model = new ComRecordModel();
    }


    public function lists()
    {

        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $fields = [
            'start_time'    => ["field" => "a.create_time", "operator" => ">", 'datatype' => 'time'],
            'end_time'      => ["field" => "a.create_time", "operator" => "<", 'datatype' => 'time'],
            'keyword'       => ["field" => "b.username", "operator" => "like", 'datatype' => 'string'],
            'select_iae'    => ["field" => "a.bill_type", "operator" => "=", 'datatype' => 'string'],
            'select_source' => ["field" => "a.bill_source", "operator" => "=", 'datatype' => 'string'],
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

        $select_iae = I('select_iae');
        $iae_array = [WalletBillModel::BILL_TYPE_IN, WalletBillModel::BILL_TYPE_OUT];
        $in = '';
        foreach ($iae_array as $v) {
            if ($select_iae && $select_iae == $v)
                $in .= '<option  selected="selected"  value="' . $v . '"> ' . $this->wallet_bill_model->getStatusValues($v) . '</option>';
            else
                $in .= '<option value="' . $v . '"> ' . $this->wallet_bill_model->getStatusValues($v) . '</option>';
        }
        $this->assign('iae_model', $in);

        $select_source = I('select_source');
        $cource = "";
        $source_array = $this->wallet_bill_model->source();

        foreach ($source_array as $v) {
            if ($select_source && $select_source == $v)
                $cource .= '<option  selected="selected"  value="' . $v . '"> ' . $this->wallet_bill_model->getSourceValues($v) . '</option>';
            else
                $cource .= '<option value="' . $v . '"> ' . $this->wallet_bill_model->getSourceValues($v) . '</option>';
        }

        $this->assign('source_model', $cource);

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as b on a.mid = b.id';
        $count = $this->wallet_bill_model
            ->alias('a')
            ->join($join)
            ->where($where)
            ->order('a.id desc')
            ->count();
        $page = $this->page($count, C('PAGE_NUMBER'));

        $result = $this->wallet_bill_model
            ->alias('a')
            ->join($join)
            ->where($where)
            ->limit($page->firstRow . ' , ' . $page->listRows)
            ->field('a.*,b.username')
            ->order('a.id desc')
            ->select();


        $categorys = '';
        foreach ($result as $k => $v) {

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['username'] . '</td>
            <td>' . $result[$k]['bill_amt'] . '</td>
            <td>' . $result[$k]['bill_before'] . '</td>
            <td>' . $result[$k]['bill_after'] . '</td>
            <td>' . $this->wallet_bill_model->getStatusValues($result[$k]['bill_type']) . '</td>
            <td>' . $v['bill_source'] . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
        </tr>';


        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }


}