<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/16
 * Time: 上午11:56
 */

namespace Finance\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\RechargeModel;

class RechargeController extends AdminbaseController
{
    private $recharge_model;

    public function __construct()
    {
        $this->recharge_model = new RechargeModel();
        parent::__construct();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as b on a.mid = b.id';
        $count = $this->recharge_model
            ->alias('a')
            ->join($join)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->recharge_model
            ->alias('a')
            ->join($join)
            ->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*,b.username')
            ->select();

        $tablebody = '';
        foreach ($result as $k => $v) {
//            $result[$k]['str_manage'] .= '<a class="js-ajax-btn-dialog" href="' . U('Recharge/edit', ['id' => $v['id']]) . '">编辑</a>';
//            $result[$k]['str_manage'] .= ' | ';
//            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Recharge/delete', ['id' => $v['option_key_id']]) . '">删除</a>';

            $tablebody .= '<tr>
                <td>' . ($k + 1) . '</td>
                <td>' . $v['id'] . '</td>
                <td>' . $v['out_trade_no'] . '</td>
                <td>' . $v['username'] . '</td>
                <td>' . $v['total_fee'] . '</td>
                <td>' . $this->recharge_model->payTypetoString($v['paytype']) . '</td>
                <td>' . ($v['notify_time'] ? dateDefault($v['notify_time']) : '') . '</td>
                <td>' . dateDefault($v['update_time']) . '</td>
                <td>' . dateDefault($v['create_time']) . '</td>

            </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }
}