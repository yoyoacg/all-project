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
            'cover' => $data['cover']??substr($data['imgs'],0,strpos($data['imgs'],',')),
            'content'=>mb_substr(strip_tags($data['content']),1,50),
            'address'=>$data['address'],
            'lon'=>$data['lon'],
            'lat'=>$data['lat'],
            'love'=>$data['love'],
        ];
        return $result;
    }

    public function getMood($id)
    {
        $data = ViewMood::get($id);
        $user = User::get($data['user_id']);
        $len = strpos($data['img'],',');
        $result = [
            'content' => $data['content'],
            'avatar' => $user['avatar'],
            'nickname' => $user['nickname'],
        ];
        if($len){
            $result['cover'] = substr($data['img'],0,$len);
        }else{
            $result['cover'] = $data['img'];
        }
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
            'cover' => substr($view['imgs'],0,strpos($view['imgs'],',')),
            'avatar' => $user['avatar'],
            'nickname' => $user['nickname'],
        ];
        return $result;
    }


}