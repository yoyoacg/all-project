<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     * 
     */
    public function index()
    {
        $this->success('请求成功');
    }
    public function getip(){
        $ip = $this->request->ip();
        $data=['ip'=>$ip];
        $this->result('',$data);
    }

}
