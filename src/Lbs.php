<?php
/**
 * +----------------------------------------------------------------------
 * 地理围栏过滤判断
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/
namespace Swx\Geocoder;

class Lbs {

    // 判断一个坐标是否在一个多边形内（由多个坐标围成的）
    public function is_polygon($point, $pts) {
        $N = count($pts);
        $boundOrVertex = true;
        $intersectCount = 0;
        $precision = 2e-10;
        $p1 = 0;
        $p2 = 0;
        $p = $point;
        $p1 = $pts[0];
        for ($i = 1; $i <= $N; ++$i) {
            if ($p[0] == $p1[0] && $p[1] == $p1[1]) {
                return $boundOrVertex;
            }
            $p2 = $pts[$i % $N];
            if ($p[1] < min($p1[1], $p2[1]) || $p[1] > max($p1[1], $p2[1])) {
                $p1 = $p2;
                continue;
            }
            if ($p[1] > min($p1[1], $p2[1]) && $p[1] < max($p1[1], $p2[1])) {
                if($p[0] <= max($p1[0], $p2[0])){
                    if ($p1[1] == $p2[1] && $p[0] >= min($p1[0], $p2[0])) {
                        return $boundOrVertex;
                    }
                    if ($p1[0] == $p2[0]) {
                        if ($p1[0] == $p[0]) {
                            return $boundOrVertex;
                        } else {
                            ++$intersectCount;
                        }
                    } else {
                        $xinters = ($p[1] - $p1[1]) * ($p2[0] - $p1[0]) / ($p2[1] - $p1[1]) + $p1[0];
                        if (abs($p[0] - $xinters) < $precision) {
                            return $boundOrVertex;
                        }
                        if ($p[0] < $xinters) {
                            ++$intersectCount;
                        }
                    }
                }
            } else {
                if ($p[1] == $p2[1] && $p[0] <= $p2[0]) {
                    $p3 = $pts[($i+1) % $N];
                    if ($p[1] >= min($p1[1], $p3[1]) && $p[1] <= max($p1[1], $p3[1])) {
                        ++$intersectCount;
                    } else {
                        $intersectCount += 2;
                    }
                }
            }
            $p1 = $p2;
        }
        if ($intersectCount % 2 == 0) return false;
        return true;
    }
}