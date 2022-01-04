<?php
/**
 * +----------------------------------------------------------------------
 * 国内省市区镇经纬度反查
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace Swx\Geocoder;
use Swx\Geocoder\Lbs;

class Geocoder {
    /**
     * 当前版本号
    */
    private $version = '1.0.1';
    /**
     * 失败原因
    */
    private $error = '';
    /**
     * 结果集
    */
    private $data = [];
    /**
     * 市.围栏半径最大范围（KM）
    */
    private $city_lbs_max = 250;
    /**
     * 区.围栏半径最大范围（KM）
    */
    private $township_lbs_max = 60;
    
    /**
     * 调用入口
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2021-12-03
     * @deprecated 暂不启用
     * @global 无
     * @param float $longitude 经度
     * @param float $latitude 纬度
     * @param bool $suffix 返回时是否删除省市后缀
     * @return false.array
    */
    public function handle($longitude, $latitude, $suffix=false) {
        if (empty($longitude)) {
            $this->error = '经度为空';
            return false;
        }
        if (empty($latitude)) {
            $this->error = '纬度为空';
            return false;
        }
        $point = [$longitude, $latitude];
        $Lbs = new Lbs();

        $path = __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
        $region = require $path.'region.php';
        $status = false;
        $township = '';
        $township_code = '';

        foreach ($region as $v) {
            $km = $this->distance($longitude, $latitude, $v[5], $v[6]);
            if ($km <= $this->city_lbs_max) {
                $province = $v[1];
                $province_code = $v[2];
                $city = $v[3];
                $city_code = $v[4];
                // 找出下面的区
                $area_file = $path.'lbs_city'.DIRECTORY_SEPARATOR.$v[0].'.php';
                if (file_exists($area_file) == false) {
                    break;
                }
                $area_list = require $area_file;
                foreach ($area_list as $val) {
                    $km = $this->distance($longitude, $latitude, $val[3], $val[4]);
                    if ($km <= $this->township_lbs_max) {
                        $area = $val[1];
                        $area_code = $val[2];
                        $lbs = json_decode($val[5], true);
                        foreach ($lbs as $arr) {
                            if ($Lbs->is_polygon($point, $arr)) {
                                // 找出下面的乡镇
                                $township_file = $path.'lbs_township'.DIRECTORY_SEPARATOR.$v[0].'.php';
                                if (file_exists($township_file) == false) {
                                    $township = '';
                                    $township_code = '';
                                    break;
                                }
                                $lbs_list = require $township_file;
                                foreach ($lbs_list as $val) {
                                    $lbs = json_decode($val[2], true);
                                    foreach ($lbs as $arr) {
                                        $res = $Lbs->is_polygon($point, $arr);
                                        if ($res) {
                                            $status = true;
                                            $township = $val[0];
                                            $township_code = $val[1];
                                            break;
                                        }
                                    }
                                    if ($status) {
                                        break;
                                    }
                                }
                                if ($status) {
                                    break;
                                }
                            }
                        }
                    }
                    if ($status) {
                        break;
                    }    
                }
            }

            if ($status) {
                break;
            }
        }
        if (!isset($area_code)) {
            $this->error = '区域查询失败';
            return false;
        }
        if ($suffix) {
            $province = $this->strdel($this->strdel($this->strdel($province, '省'), '市'), '自治区');
            $city = $this->strdel($this->strdel($this->strdel($this->strdel($city, '市'), '自治州'), '地区'), '县');
        }
        $this->data = [
            'province' => $province,
            'province_code' => $province_code,
            'city' => $city,
            'city_code' => $city_code,
            'area' => $area,
            'area_code' => $area_code,
            'township' => $township,
            'township_code' => $township_code,
        ];
        return $this->data;
    }

    /**
     * 获取失败原因描述
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2021-12-03
     * @deprecated 暂不启用
     * @global 无
     * @return string
    */
    public function error() {
        return $this->error;
    }

    /**
     * 成员属性的方式读取结果集
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2021-12-03
     * @deprecated 暂不启用
     * @global 无
     * @param string $name
     * @return mixed
    */
    public function __get($name) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return false;
    }

    private function strdel($string, $keyword) {
        $max = mb_strrpos($string, $keyword);
        if ($max === false) {
            return $string;
        }
        $result = mb_substr($string, 0, mb_strrpos($string, $keyword));
        return $result;
    }

    /**
     * 计算两点之间的直线距离
     * 
     * @param float $longitude1 起点经度
     * @param float $latitude1 起点纬度
     * @param float $longitude2 终点经度
     * @param float $latitude2 终点纬度
     * @return float 
    */
    private function distance($longitude1, $latitude1, $longitude2, $latitude2){  
        $theta = $longitude1 - $longitude2;
        $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $distance = $miles * 1.609344;
        return round($distance, 4);
    }
}
