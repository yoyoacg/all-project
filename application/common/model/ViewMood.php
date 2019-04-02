<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29
 * Time: 14:13
 */

namespace app\common\model;


use think\Model;

class ViewMood extends Model
{
    protected $table='view_mood';

    public function user(){
        return $this->belongsTo('User','user_id','id')->bind('nickname,avatar');
    }

}