<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/18
 * Time: 9:47
 */
    namespace Community\Controller;
    use Common\Controller\AdminbaseController;
    use Community\Model\ComKnowModel;

    class KnowController extends AdminbaseController
    {

        private $Comknow_model;

        public function __construct()
        {

            parent::__construct();
            $this->Comknow_model = new ComKnowModel();
        }
        /*
         * 宠物问答列表
         */
        public function index() {
            /*if($_POST) {
                $where['']
            }*/
            $fields = array(
                'keyword' => array("field" => "ego_com_know.kno_title", "operator" => "like", 'datatype' => 'string')
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
            $count = $this->Comknow_model->where($where)->count();
            $page = $this->page($count, C("PAGE_NUMBER"));
            $rst = $this->Comknow_model
                ->where($where)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->order('kno_id desc')
                ->select();

            foreach ($rst as $k => $v) {
                $title = '<a href="' . U('Know/detail', array('id' => $v['kno_id'])) . '">'.$v['kno_title'].'</a>';
                $action = '<a class="js-ajax-delete"  href="' . U('Know/delete', array('id' => $v['kno_id'])) . '">'.'删除'.'</a>'.' | '.'<a href="' . U('Know/edit', array('id' => $v['kno_id'])) . '">'.'编辑'.'</a>';

                $list .= '<tr>
                    <td>' . ($k + 1) . '</td>
                    <td>' . $title  .' </td>
                    <td>' . $v['kno_keyword'] . '</td>
                    <td>' . $action . '</td>
                </tr>';
            }
            $this->assign('list', $list);
            $this->assign("Page", $page->show());
            $this->display();
        }
        /*
         * 删除
         */
        public function delete() {
            $id = intval(I('get.id'));
//            dump($id);exit;
            if(empty($id)) {
                $this->error('error');
            }
            $rst = $this->Comknow_model->delete($id);
            if($rst) {
                $this->success('success', U('Know/index'));
            }
        }

        /*
         * 详情
         */
        public function detail() {
            $id = I('get.id');
            $rst = $this->Comknow_model->detail($id);
//            dump($rst);

            $detail = '<dl class="dl-horizontal">
                <dt>'.'问答标题:'.'</dt>
                <dd>'.$rst['kno_title'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>'.'问答内容:'.'</dt>
                    <dd style="margin-right: 30%;">'.$rst['kno_content'].'</dd>
                </dl>
                <dl class="dl-horizontal">
                    <dt>'.'问答关键字:'.'</dt>
                    <dd>'.$rst['kno_keyword'].'</dd>
                </dl>
                <div class="form-actions" style="padding-left: 100px;">
                    <a class="btn btn-primary js-ajax-delete" href="' . U('Know/delete', array('id' => $rst['kno_id'])) . '">'.'删除'.'</a>
                    <a class="btn" href="' . U('Know/edit', array('id' => $rst['kno_id'])) . '">'.'编辑'.'</a>
                </div>';

            $this->assign('detail', $detail);
//            $this->assign('rst', $rst);
            $this->display();
        }

        /*
         * 添加/编辑页面
         */
        public function edit() {
            $id = I('get.id');
            if(empty($id)) {
                $title = '新增问答';
                $button = '添加';
            } else {
                $title = '编辑';
                $button = '保存';
                $rst = $this->Comknow_model->where(array('kno_id' => $id))->find();
                $this->assign('rst', $rst);
            }

            $this->assign('button', $button);
            $this->assign('title', $title);
            $this->display();
        }

        /*
         * 添加、修改
         */
        public function add() {
            $post = I('post.post');
//            dump($post);
            $data['kno_title'] = $post['kno_title'];
            $data['kno_content'] = $post['kno_content'];
            $data['kno_keyword'] = $post['kno_keyword'];
            if(mb_strlen($data['kno_title']) > 30) {
                $this->error('标题长度超过30个字');
            }
            if(empty($post['kno_id'])) {
                $rst = $this->Comknow_model->add($data);
            } else {
                $rst = $this->Comknow_model->updateKnow($post['kno_id'], $data);
            }
            if($rst) {
                $this->success('success', U('Know/index'));
            } else {
                $this->error('error');
            }
        }
    }