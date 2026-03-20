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
use app\common\CurlHelper;
/**
 * 系统异常处理类
 */
class Apis
{

    private static $geoconvUrl = 'https://api.map.baidu.com/geoconv/v1';
    private static $geocoderUrl       = 'https://api.map.baidu.com/geocoder/v2';
    private static $gpsKey       = 'lqbuXpVyr4bMhd4C4QklVKwuLdEcLred';

    // 地球长半轴
    private static $a = 6378245.0;
    // 扁率
    private static $ee = 0.00669342162296594323;

    protected $isJson = false;


    public static function geoconv(&$lng,&$lat){
        $url = 'https://api.map.baidu.com/geoconv/v1/?coords='.$lng.','.$lat.'&from=1&to=5&ak=DQfCWlViamxsOsOQh3PkxN4aEDxlBldy';



        // curl_close($ch);
        // $response = Http::get("http://api.map.baidu.com/geoconv/v1/?coords=120.8145994208448,31.2772433954103&from=1&to=5&ak=NGQncXcBA5MOrjhXy80fWygXENcMPzvG"); // 获取响应内容
        // $output = $response->getBody()->getContents();
        // exit(json_encode($output));
        $res = json_decode(static::get_func($url));
        $lng = $res->result[0]->x;
        $lat = $res->result[0]->y;
    }

    public static function geocoder($lng,$lat,$address = false){

        // // 百度
        // static::geoconv($lng,$lat);
        // // return array($gcj02);
        // // $url = 'https://api.map.baidu.com/geocoder/v2/?location='.$lat.','.$lng.'&output=json&pois=1&ak=DQfCWlViamxsOsOQh3PkxN4aEDxlBldy';
        // $url = 'https://api.map.baidu.com/reverse_geocoding/v3/?ak=DQfCWlViamxsOsOQh3PkxN4aEDxlBldy&extensions_poi=1&entire_poi=1&sort_strategy=distance&output=json&coordtype=bd09ll&location='.$lat.','.$lng;


        // 高德
        //

        // $gcj02 = static::wgs84ToGcj02($lng, $lat);

        // $lat = $gcj02['lat'];
        // $lng = $gcj02['lng'];
        // $params = [
        //     'location' => "$lng,$lat",
        //     'key' => 'ab41652a8b47a62c018473028fcbe13f',
        //     'get_poi' => 1, // 获取周边POI信息
        //     'poi_options' => 'radius=500;page_size=5' // 周边500米，最多5个POI
        // ];

        // $url = 'https://restapi.amap.com/v3/geocode/regeo?' . http_build_query($params);
        // $addlist = json_decode(static::get_func($url),true);
        // $address = $addlist['regeocode']['formatted_address'];
        // // 腾讯地图

        $gcj02 = static::wgs84ToGcj02s($lng, $lat);
        if($address){
            $url = 'https://apis.map.qq.com/ws/geocoder/v1?location='.$gcj02['lat'].','.$gcj02['lng'].'&key=P2ABZ-MD3KZ-KVFXR-TQPQZ-GSFY6-TOFNB';
        }else{
            $url = 'https://apis.map.qq.com/ws/geocoder/v1?location='.$lat.','.$lng.'&key=P2ABZ-MD3KZ-KVFXR-TQPQZ-GSFY6-TOFNB';
        }
        // $addlist = json_decode(static::get_func($url),true);
        // $address = $addlist['result']['formatted_addresses']['standard_address'];

        return json_decode(static::get_func($url));
    }

    public static function geocoderJuli($lng1,$lat1,$lng2,$lat2){
        // $this->geoconv($lng,$lat);
        $url = "https://api.map.baidu.com/distance?ak=DQfCWlViamxsOsOQh3PkxN4aEDxlBldy&origin=" . $lat1 . "," . $lng1."&destination=" . $lat2 . "," . $lng2;

        return json_decode(static::get_func($url));
    }

    public static function getDistance($lat1, $lng1, $lat2, $lng2) {
        // 地球半径，单位：米
        $earthRadius = 6371000;

        // 将角度转换为弧度
        $latFrom = deg2rad((float)$lat1);
        $lngFrom = deg2rad((float)$lng1);
        $latTo = deg2rad((float)$lat2);
        $lngTo = deg2rad((float)$lng2);
        // 计算纬度和经度的差值
        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;
        // 使用Haversine公式计算距离
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));
        return $angle * $earthRadius;
    }
    public static function get_func($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    /**
     * 将WGS84坐标转换为GCJ02坐标
     * @param float $lat 纬度
     * @param float $lng 经度
     * @return array [lat, lng] 转换后的坐标
     */
    public static function wgs84ToGcj02s($lat, $lng) {
        $params = [
            'locations' => "$lng,$lat",
            'type' => 1, // 1:WGS84转GCJ02
            'key' => 'P2ABZ-MD3KZ-KVFXR-TQPQZ-GSFY6-TOFNB'
        ];

        $url = 'https://apis.map.qq.com/ws/coord/v1/translate?' . http_build_query($params);
        $response = json_decode(static::get_func($url),true);
        // return $response;
        if ($response['status'] == 0 && !empty($response['locations'])) {
            $location = $response['locations'][0];
            return array('lat'=>$location['lat'], 'lng'=>$location['lng']);
        }

        // throw new Exception("坐标转换失败: " . ($response['message'] ?? '未知错误'));
    }



     /**
     * 判断坐标是否在中国境内（粗略判断）
     * @param float $lng 经度
     * @param float $lat 纬度
     * @return bool
     */
    public static function outOfChina($lng, $lat) {
        return ($lng < 72.004 || $lng > 137.8347) ||
               ($lat < 0.8293 || $lat > 55.8271);
    }

    /**
     * 转换纬度
     * @param float $x
     * @param float $y
     * @return float
     */
    private static function transformLat($x, $y) {
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * M_PI) + 20.0 * sin(2.0 * $x * M_PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * M_PI) + 40.0 * sin($y / 3.0 * M_PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * M_PI) + 320 * sin($y * M_PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    /**
     * 转换经度
     * @param float $x
     * @param float $y
     * @return float
     */
    private static function transformLng($x, $y) {
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * M_PI) + 20.0 * sin(2.0 * $x * M_PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * M_PI) + 40.0 * sin($x / 3.0 * M_PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * M_PI) + 300.0 * sin($x / 30.0 * M_PI)) * 2.0 / 3.0;
        return $ret;
    }

    /**
     * WGS-84 转换为 GCJ-02
     * @param float $lng 经度
     * @param float $lat 纬度
     * @return array ['lng' => 转换后的经度, 'lat' => 转换后的纬度]
     */
    public static function wgs84ToGcj02($lng, $lat) {
        if (self::outOfChina($lng, $lat)) {
            return ['lng' => $lng, 'lat' => $lat];
        }

        $dLat = self::transformLat($lng - 105.0, $lat - 35.0);
        $dLng = self::transformLng($lng - 105.0, $lat - 35.0);
        $radLat = $lat / 180.0 * M_PI;
        $magic = sin($radLat);
        $magic = 1 - self::$ee * $magic * $magic;
        $sqrtMagic = sqrt($magic);

        $dLat = ($dLat * 180.0) / ((self::$a * (1 - self::$ee)) / ($magic * $sqrtMagic) * M_PI);
        $dLng = ($dLng * 180.0) / (self::$a / $sqrtMagic * cos($radLat) * M_PI);

        $mgLat = $lat + $dLat;
        $mgLng = $lng + $dLng;

        return ['lng' => $mgLng, 'lat' => $mgLat];
    }


}
