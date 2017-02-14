<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/27
 * Time: 18:42
 */

namespace Appapi\Controller;


use Advertisement\Model\BannerImageModel;
use Advertisement\Model\BannerModel;

class CompanyBannerController extends ApibaseController
{
    private $banner_model, $banner_image_model;

    public function __construct()
    {
        parent::__construct();
        $this->banner_model = new BannerModel();
        $this->banner_image_model = new BannerImageModel();
    }

    public function bannerList() {
        //已启用的广告
        $type =  I('post.type');
        if($type == 1) {
            $where = array(
                'sign_key' => 'company-trans',
                'status' => 1,
            );
        }
        if($type == 2) {
            $where = array(
                'sign_key' => 'company-fos',
                'status' => 1,
            );
        }
        if($type == 3) {
            $where = array(
                'sign_key' => 'company-fun',
                'status' => 1,
            );
        }
        $list = $this->banner_model
            ->where($where)
            ->field('id')
            ->select();
        foreach ($list as $k => $v) {
            $ids[]= $v['id'];
        }
        $ids = implode(',', $ids);
        //已启用的广告所包含的图，限5 张
        $info = $this->banner_image_model
            ->where(array('banner_id' =>array('in', $ids)))
            ->field('title, link, image, type')
            ->order('sort_order asc')
            ->limit(5)
            ->select();
        foreach($info as $k => $v) {
            if($v['type'] == 3) {
                $data[] = array(
                    'type' => $v['type'],
                    'link' => 'https://www.mixiupet.com/Wap/Banner/artdis?id='.$v['link'],
                    'image' => setUrl($v['image']),
                );
            } else {
                $data[] = array(
                    'type' => $v['type'],
                    'link' => $v['type'],
                    'image' => setUrl($v['image']),
                );
            }
        }
        exit($this->returnApiSuccess($data));
    }
}