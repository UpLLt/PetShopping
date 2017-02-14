<?php
namespace Advertisement\Controller;


use Advertisement\Model\BannerImageModel;
use Advertisement\Model\BannerModel;
use Common\Controller\AdminbaseController;
use Think\Controller;

class IndexController extends AdminbaseController
{
    private $banner_model;
    private $banner_image_model;


    public function __construct()
    {
        parent::__construct();
        $this->banner_model = new BannerModel();
        $this->banner_image_model = new BannerImageModel();
    }

    public function billboard()
    {
        $this->_lists();
        $this->display();
    }

    public function _lists()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $where['name'] = ['like', "%$keyword%"];
            $_GET['keyword'] = $keyword;
        }
        $count = $this->banner_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->banner_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        $categorys = '';
        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] = '<a href="' . U('Index/edit', ['id' => $v['id']]) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Index/delete', ['id' => $v['id']]) . '">删除</a>';

            $categorys .= '<tr>
                            <td>' . ($k + 1) . '</td>
                            <td>' . $result[$k]['name'] . '</td>
                            <td>' . $result[$k]['sign_key'] . '</td>
                            <td>' . ($v['type'] == 1 ? 'APP' : 'PC') . '</td>
                            <td>' . ($v['status'] == 1 ? '启用' : '禁用') . '</td>
                            <td>' . $result[$k]['str_manage'] . '</td>
                        </tr>';
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
        $upload_iamges = upload_img('Advertisement');
        $data = I('post.post');
        $banner_image_array = [];
        if (!empty($_POST['title'])) {
            foreach ($_POST['title'] as $key => $val) {
                $image = [
                    'title'      => $val,
                    'image'      => $upload_iamges[$key],
                    'link'       => $_POST['link'][$key],
                    'sort_order' => $_POST['sort_order'][$key],
                    'type'       => $_POST['type'][$key],
                ];
                $banner_image_array[] = $image;
            }
        }

        $iscommit = true;
        $this->banner_model->startTrans();
        if (!$this->banner_model->create($data)) $this->error($this->banner_model->getError());
        $result = $this->banner_model->add($data);
        if (!$result) $iscommit = false;

        $InsID = $this->banner_model->getLastInsID();
        foreach ($banner_image_array as $k => $v) {
            $banner_image_array[$k]['banner_id'] = $InsID;
            $b = $this->banner_image_model->add($banner_image_array[$k]);
            if (!$b) $iscommit = false;
            unset($b);
        }

        if ($iscommit) {
            $this->banner_model->commit();
            $this->success('success');
        } else {
            $this->banner_model->rollback();
            $this->error('error');
        }
    }

    public function delete()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $this->banner_model->delete($id);
        $this->banner_image_model->where(['banner_id' => $id])->delete();

        $this->success('success');
    }

    public function edit()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $result = $this->banner_model
            ->find($id);
        $banner_image = $this->banner_image_model->where(['banner_id' => $id])->select();


        $categorys = '';
        foreach ($banner_image as $k => $v) {
            if($v['type'] == 1) {
                $select = '<select name="type[]" id=""><option value="1" selected>活体宠物</option><option value="2">宠物商品</option><option value="3">活动页面</option></select>';
            }
            if($v['type'] == 2) {
                $select = '<select name="type[]" id=""><option value="1" >活体宠物</option><option value="2" selected>宠物商品</option><option value="3">活动页面</option></select>';
            }
            if($v['type'] == 3) {
                $select = '<select name="type[]" id=""><option value="1" >活体宠物</option><option value="2">宠物商品</option><option value="3" selected>活动页面</option></select>';
            }
            $categorys .= '<tr >
                        <td><input required name="title[]" value="' . $v['title'] . '"></td>
                        <td><input name="link[]" style="width:100px;" value="' . $v['link'] . '"></td>
                        <td>
                            <input name="images[]" type="hidden" value="' . $v['image'] . '">
                            <img width="100" src="' . setUrl($v['image']) . '"/>
                        </td>
                        <td><input name="sort_order[]" value="'.$v['sort_order'].'" style="width:100px;"></td>
                         <td>'.$select.'</td>
                        <td><a class="btn btn-danger btn-small"  href="javascript:;" onclick="deletetr(this)"><span>删除</span></a></td>
                        </tr>';
        }


        $this->assign('data', $result);
        $this->assign('categorys', $categorys);
        $this->display();
    }

    public function edit_post()
    {
        /*$upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './data/upload/banner/'; // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        // 上传文件
        $info = $upload->upload();
        if ($info) {
            foreach ($info as $file) {
                $url = '/data/upload/banner/' . $file['savepath'] . $file['savename'];
                $upload_iamges[] = array('image' => $url);
            }
        }*/

        $upload_iamges = upload_img('Advertisement');//dump($_POST['images']);dump($upload_iamges);exit;
        $data = I('post.post');
        $banner_image_array = [];
        if (!empty($_POST['title'])) {
            $i = 0;
            foreach ($_POST['title'] as $key => $val) {
                $image = [
                    'title'      => $val,
                    'image'      => $_POST['images'][$key] ? $_POST['images'][$key] : $upload_iamges[$i],
                    'link'       => $_POST['link'][$key],
                    'sort_order' => $_POST['sort_order'][$key],
                    'type'       => $_POST['type'][$key],
                ];
                if (!$_POST['images'][$key]) {
                    $i += 1;
                }
                $banner_image_array[] = $image;
            }
        }
        $iscommit = true;
        $this->banner_model->startTrans();
        $banner_id = I('post.id');//dump($data);exit;
        if ($this->banner_model->where(['id' => $banner_id])->save($data) === false) $iscommit = false;
        $this->banner_image_model->where(['banner_id' => $banner_id])->delete();



        foreach ($banner_image_array as $k => $v) {
            $banner_image_array[$k]['banner_id'] = $banner_id;
            $b = $this->banner_image_model->add($banner_image_array[$k]);
            if (!$b) $iscommit = false;
            unset($b);
        }

        if ($iscommit) {
            $this->banner_model->commit();
            $this->success('success');
        } else {
            $this->banner_model->rollback();
            $this->error('error');
        }
    }
}