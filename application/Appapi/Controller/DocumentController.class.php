<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/20
 * Time: 下午5:09
 */

namespace Appapi\Controller;


use App\Model\DocumentsModel;

class DocumentController extends ApibaseController
{

    private $document_model;

    public function __construct()
    {
        parent::__construct();
        $this->document_model = new DocumentsModel();
    }

    public function lists()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $key = I('post.key');
        $this->checkparam([$key]);
        $result = $this->document_model->getDocument($key);
        if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请求的页面不存在'));
        $url = $this->geturl('/Wap/Document/index/name/' . $key);
        exit($this->returnApiSuccess($url));
    }
}