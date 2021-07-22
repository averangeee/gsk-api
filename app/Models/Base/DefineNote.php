<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/29
 * Time: 16:03
 */

namespace App\Models\Base;


use App\Models\BaseModel;

class DefineNote extends BaseModel
{
    protected $table='define_note';

    //反向
    public  function getLevel($type,$id,$select=['id','parent_id','des','status','type'])
    {
        $where=function ($query) use($id,$type){
            if(!empty($id) && count($id)>0){
                $query->whereIn('id',$id);
            }
            if(strlen($type)>0){
                $query->where('type',$type);
            }
        };
        $sourceItems = $this->where($where)->orderBy('des')->get($select);
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