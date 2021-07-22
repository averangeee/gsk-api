<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/15
 * Time: 14:17
 */

namespace App\Models\System;


use App\Models\BaseModel;

class Region extends BaseModel
{
    protected $table='region';

    protected $guarded=[];

    public function children(){
        return $this->hasMany(self::class,'parent_id','id');
    }

    //反向
    public  function getLevel($type,$id,$select=['id','parent_id','name','name_en','type','query_string'])
    {
        $where=function ($query) use($id,$type){
            if(!empty($id) && count($id)>0){
                $query->whereIn('id',$id);
            }
            if(strlen($type)>0){
                $query->where('type',$type);
            }
            $query->whereIn('type',[0,1]);
        };
        $sourceItems = $this->where($where)->orderBy('name')->get($select);
        return $this->getLevelInfo($sourceItems->toArray(),  0);
    }
    public function getLevelInfo($data,$pid = 0)
    {
        $arr=[];
        foreach ($data as $k=>$v) {
            if ($v['parent_id'] == $pid) {
                $child=self::getLevelInfo($data,  $v['id']);
                if($child){
                    $v['children']= $child;
                }
                $arr[] = $v;
            }
        }
        return $arr;
    }
}