<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29
 * Time: 14:49
 */

namespace app\common\model;


use think\Model;

class ViewLove extends Model
{
    protected $table = 'view_love';

    public function getView($id)
    {
        $data = Spot::get($id);
        $result = [
            'name' => $data['name'],
            'cover' => [$data['cover']],
            'content'=>mb_substr(strip_tags($data['content']),1,50)
        ];
        return $result;
    }

    public function getMood($id)
    {
        $data = ViewMood::get($id);
        $user = User::get($data['user_id']);
        $result = [
            'content' => $data['content'],
            'cover' => explode(',', $data['img']),
            'avatar' => $user['avatar'],
            'nickname' => $user['nickname'],
        ];
        return $result;
    }

    public function getComment($id)
    {
        $comment = ViewComment::get($id);
        $view = Spot::get($comment['spot_id']);
        $user = User::get($comment['user_id']);
        $result = [
            'name' => $view['name'],
            'content' => $comment['content'],
            'cover' => explode(',', $view['imgs']),
            'avatar' => $user['avatar'],
            'nickname' => $user['nickname'],
        ];
        return $result;
    }


}