<?php
namespace Demo\Controller;

use Common\Controller\AdminbaseController;
use Think\Controller;

class IndexController extends Controller
{

    private $comment_model;

    public function __construct()
    {
        parent::__construct();
        $this->comment_model = new CommentModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        $count = $this->comment_model
            ->alias('a')
            ->join($join)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->comment_model
            ->alias('a')
            ->join($join)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*,b.name as product_name')
            ->select();

        $tablebody = '';
        foreach ($result as $k => $v) {
            $status_value = $v['status'] ? '禁用' : '启用';
            $result[$k]['str_manage'] = '<a class="js-ajax-dialog-btn" href="' . U('Evaluation/check', ['id' => $v['id']]) . '">' . $status_value . '</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Evaluation/delete', ['id' => $v['id']]) . '">删除</a>';

            $tablebody .= '<tr>
                                <td>' . ($k + 1) . '</td>
                                <td>' . $v['full_name'] . '</td>
                                <td>' . $v['product_name'] . '</td>
                                <td>' . ($v['status'] ? '启用' : '禁用') . '</td>
                                <td>' . $v['content'] . '</td>
                                <td>' . date('Y-m-d', $v['create_time']) . '</td>
                                <td>' . $result[$k]['str_manage'] . '</td>
                           </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }
}