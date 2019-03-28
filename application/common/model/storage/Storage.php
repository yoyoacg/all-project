<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 14:45
 */
namespace app\common\model\storage;

use think\Model;

class Storage extends Model
{
    protected $table='storage';

    public function tag(){
        return $this->belongsTo('Tag','tag_id','id','','LEFT')
            ->bind('name tagName,img tagImg');
    }

}