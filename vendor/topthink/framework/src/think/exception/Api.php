<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\exception;

use Exception;
use think\App;
use think\console\Output;

use think\Request;
use think\Response;
use Throwable;

/**
 * 系统异常处理类
 */
class Api
{


    private  $geocoderUrl       = 'http://api.map.baidu.com/geocoder/v2';
    private  $gpsKey       = 'NGQncXcBA5MOrjhXy80fWygXENcMPzvG';

    protected $isJson = false;


    private  function geoconv(&$lng,&$lat){
        $url = $this->geoconvUrl.'/?coords='.$lng.','.$lat.'&from=1&to=5&ak='.$this->gpsKey;
        $res = json_decode(self::get_func($url));
        $lng = $res->result[0]->x;
        $lat = $res->result[0]->y;
    }

    public  function geocoder($lng,$lat){
        $this->geoconv($lng,$lat);
        $url = $this->geocoderUrl.'/?location='.$lat.','.$lng.'&output=json&pois=1&ak='.$this->gpsKey;
        return json_decode(self::get_func($url));
    }

    public  function geocoderJuli($lng1,$lat1,$lng2,$lat2){
        // $this->geoconv($lng,$lat);
        $url = "https://api.map.baidu.com/distance?ak=$this->gpsKey&origin=" . $lat1 . "," . $lng1."&destination=" . $lat2 . "," . $lng2;

        return json_decode(self::get_func($url));
    }

    public function getDistance($lat1, $lng1, $lat2, $lng2) {
        // 地球半径，单位：米
        $earthRadius = 6371000;

        // 将角度转换为弧度
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);
        // 计算纬度和经度的差值
        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;
        // 使用Haversine公式计算距离
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));
        return $angle * $earthRadius;
    }

}
