<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 14:16
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\common\model\storage\Tag;

/**
 * 收纳类
 * Class Storage
 * @package app\api\controller
 */
class Storage extends Api
{
    protected $noNeedLogin = '*';

    public function get_tag()
    {
        $band_id = $this->request->post('band_id');
        if(empty($band_id)) $this->error();
        $list = Tag::whereIn('type', ['normal', $band_id])
            ->column('id,name,img,num');
        foreach ($list as $k => $v) {
            $list[$k]['num'] = \app\common\model\storage\Storage::where(['tag_id' => $v['id'], 'bind_id' => $band_id])->count('id');
            if (!empty($v['img'])) {
                $list[$k]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . $v['img'];
            }
        }
        $this->result('success', array_values($list), 200);
    }

    /**
     * 添加标签
     */
    public function add_tag()
    {
        $band_id = $this->request->post('band_id');
        $name = $this->request->post('name');
        if(empty($band_id)||empty($name)) $this->error();
        $data = [
            'type' => $band_id,
            'name' => $name
        ];
        if (Tag::create($data)) {
            $this->result('success', null, 200);
        } else {
            $this->error('fail');
        }
    }

    /**
     * 获取i列表
     */
    public function get_list()
    {
        $tag_id = $this->request->post('tag_id');
        $band_id = $this->request->post('band_id');
        $order = 'create_time';
        $sort = 'DESC';
        $where = [];
        if ($tag_id) {
            $where['tag_id'] = $tag_id;
        }
        if ($band_id) {
            $where['bind_id'] = $band_id;
        } else {
            $this->error('fail');
        }
        $list = \app\common\model\storage\Storage::where($where)
            ->order($order, $sort)
            ->column('id,name,desc,img,create_time');
        $this->result('success', array_values($list), 200);
    }

    public function add_storage()
    {
        $band_id = $this->request->post('band_id');
        $name = $this->request->post('name');
        $tag_id = $this->request->post('tag_id');
        $desc = $this->request->post('desc', '');
        $img = $this->request->post('img', '');
        if (empty($band_id) || empty($name) || empty($tag_id)) {
            $this->error('缺少参数');
        }
        $data = [
            'tag_id' => $tag_id,
            'name' => $name,
            'bind_id' => $band_id,
            'desc' => mb_substr($desc, 0, 250),
            'img' => $img,
            'create_time' => date('Y-m-d H:i:s')
        ];
        if (\app\common\model\storage\Storage::create($data)) {
            $this->success('success', null, 200);
        } else {
            $this->error();
        }

    }


}