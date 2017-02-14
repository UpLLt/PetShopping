<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/20
 * Time: 下午5:03
 */

namespace Wap\Controller;


use App\Model\DocumentsModel;
use Think\Controller;

class DocumentController extends Controller
{
    private $document_model;

    public function __construct()
    {
        parent::__construct();
        $this->document_model = new DocumentsModel();
    }

    public function index()
    {
        $class_name = I('get.name');
        if (!$class_name) $this->display();
        $result = $this->document_model->getDocument($class_name);
        $this->assign('data', $result);
        $this->display();
    }
}