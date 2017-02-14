<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/26
 * Time: 14:53
 */

namespace Advertisement\Controller;


use Common\Controller\AdminbaseController;
use Portal\Model\PostsModel;

class ArticleController extends AdminbaseController
{
    private $posts_model;

    public function __construct()
    {
        parent::__construct();
        $this->posts_model = new PostsModel();
    }

    public function artList()
    {
        $artlists = $this->posts_model
            ->field('id, post_title, post_content, post_date')
            ->order('post_date desc')
            ->select();
        //dump($artlists);
        $lists = '';
        foreach ($artlists as $k => $v) {
            $v['str_manage'] = '<a class="js-ajax-delete" href="' . U('Article/delete', ['id' => $v['id']]) . '">删除</a>';
            $v['str_manage'] .= ' | ';
            $v['str_manage'] .= '<a class="" href="' . U('Article/edit', ['id' => $v['id']]) . '">编辑</a>';
            $v['str_manage'] .= ' | ';
            $v['str_manage'] .= '<a class="" href="' . U('Article/art_push', ['id' => $v['id']]) . '">推送</a>';

            $lists .= '<tr>
                <td>' . ($k + 1) . '</td>
                <td>' . $v['id'] . ' </td>
                <td>' . $v['post_title'] . '</td>
                <td>' . $v['post_date'] . '</td>
                <td>' . $v['str_manage'] . '</td>
            </tr>';
        }
        $this->assign('lists', $lists);
        $this->display();
    }

    public function art_push()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('empty');

        $posts = $this->posts_model->find($id);

        $msg_title = '咪咻商城';
        $msg_content = $posts['post_title'];

        vendor('JPush.JPush');
        $AppKey = C('Jpush_AppKey');
        $AppKey = 'f60ee52c21da440ec74a656b';
        $MasterSecret = '05ad692b48e0612f1d7b1c09';

        // 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等

        try {
            $client = new \JPush($AppKey, $MasterSecret);

            $result = $client->push()
                ->setPlatform(['android', 'ios'])
//                ->setAudience('all')
//                ->setAudience('275')
                ->addAlias(['275'])
                ->setNotificationAlert('咪咻商城')
                ->addAndroidNotification($msg_content, $msg_title, 1, ["key1" => "value1", "key2" => "value2"])
                ->addIosNotification($msg_content, 'iOS sound', \JPush::DISABLE_BADGE, true, 'iOS category', ["key1" => "value1", "key2" => "value2"])
                ->setMessage($msg_content, $msg_title, 'type', ["key1" => "value1", "key2" => "value2"])
                ->setOptions(100000, 3600, null, true)
                ->send();

            $this->success('Success');

        } catch (\APIRequestException $e) {
            $this->error($e->getMessage());
        }
    }

    public function add()
    {

        $this->display();
    }

    public function add_post()
    {
        $data['post_title'] = I('post.post_title');
        $data['post_content'] = htmlspecialchars_decode(I('post.post_content'));
        $data['post_date'] = date('Y-m-d H:i:s');
        $data['post_keywords'] = '无';
        if (empty($data['post_title'])) {
            $this->error('标题不能为空');
        }
        if (empty($data['post_content'])) {
            $this->error('内容不能为空');
        }
        $rst = $this->posts_model->add($data);
        if ($rst) {
            $this->success('', U('Advertisement/Article/artList'));
        } else {
            $this->error();
        }
    }

    public function edit()
    {
        $id = I('get.id');
        $info = $this->posts_model
            ->where(['id' => $id])
            ->field('id, post_title, post_content, post_date')
            ->find();
//        dump($info);
        $this->assign('info', $info);
        $this->display();
    }

    public function edit_post()
    {
        $id = I('post.id');
        $data['post_title'] = I('post.post_title');
        $data['post_content'] = htmlspecialchars_decode(I('post.post_content'));
        $data['post_date'] = date('Y-m-d H:i:s');
        $data['post_keywords'] = '无';
        if (empty($data['post_title'])) {
            $this->error('标题不能为空');
        }
        if (empty($data['post_content'])) {
            $this->error('内容不能为空');
        }
        $rst = $this->posts_model->where(['id' => $id])->save($data);
        if ($rst) {
            $this->success('', U('Advertisement/Article/artList'));
        } else {
            $this->error();
        }
    }

    public function delete()
    {
        $id = I('get.id');
        $rst = $this->posts_model->where(['id' => $id])->delete();
        if ($rst) {
            $this->success();
        } else {
            $this->error();
        }
    }


}