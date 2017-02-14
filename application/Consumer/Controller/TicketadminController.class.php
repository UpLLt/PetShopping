<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/30
 * Time: 上午11:25
 */

namespace Consumer\Controller;


use Common\Controller\AdminbaseController;
use Consumer\Model\TicketModel;

class TicketadminController extends AdminbaseController
{

    protected $ticket_model;

    public function __construct()
    {
        parent::__construct();
        $this->ticket_model = new TicketModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $count = $this->ticket_model->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->ticket_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        $categorys = '';
        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a class="" href="' . U('Ticketadmin/edit', ['id' => $v['id']]) . '">编辑</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Ticketadmin/manages', ['id' => $v['id']]) . '">' . ($v['status'] == 1 ? '启用' : '禁用') . '</a>';

            $result[$k]['store_img'] = json_decode($result[$k]['store_img'], true);
            $result[$k]['store_img']['thumb'];

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['price'] . '</td>
            <td>' . $this->ticket_model->getTypeStr( $result[$k]['ttype']) . '</td>
            <td>' . $result[$k]['describe'] . '</td>
            <td>' . $result[$k]['validity'] . '天</td>
            <td>' . ($result[$k]['status'] == 1 ? '<span class="text-error">禁用</span>' : '<span class="text-info">启用</span>') . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';
//            <td>' . ($result[$k]['ttype'] == 1 ? '全场通用' : '店铺通用') . '</td>
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }


    public function add()
    {
        $this->display();
    }

    public function add_post()
    {

        if ($this->ticket_model->create()) {
            $result = $this->ticket_model->add();
            if ($result) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error($this->ticket_model->getError());
        }
    }

    public function edit($id)
    {
        if (empty($id)) $this->error('error');
        $this->assign('data', $this->ticket_model->find($id));
        $this->display();
    }

    public function edit_post()
    {
        if ($this->ticket_model->create()) {
            $result = $this->ticket_model->save();

            if ($result === false)
                $this->error('error');
            $this->success('success');
        } else {
            $this->error($this->ticket_model->getError());
        }
    }


    public function manages()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');
        $status = $this->ticket_model->where(['id' => $id])->getField('status');
        $result = $this->ticket_model->where(['id' => $id])->save(['status' => ($status == '1' ? 2 : 1)]);
        if ($result === false) $this->error('save error');
        else $this->success('success');
    }
}