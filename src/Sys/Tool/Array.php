<?php
namespace Sys\Tool;

/**
 * 数组工具类
 */
class ArrayTool
{
    /**
     * 从数组中获取值，如果未设定时，返回默认值
     * @param  array $array   数组
     * @param  string $key     key
     * @param  mixed $default 默认值
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (is_array($array) && isset($array[$key])) {
            return $array[$key];
        } elseif (is_object($array) && isset($array->$key)) {
            return $array->$key;
        }
        return $default;
    }

    /**
     * 递归地合并一个或多个数组(不同于 array_merge_recursive)
     *
     * @return array
     */
    public function mergeDeep()
    {
        $a = func_get_args();
        for ($i = 1; $i < count($a); $i++) {
            foreach ($a[$i] as $k => $v) {
                if (isset($a[0][$k])) {
                    if (is_array($v)) {
                        if (is_array($a[0][$k])) {
                            $a[0][$k] = self::mergeDeep($a[0][$k], $v);
                        } else {
                            $v[]      = $a[0][$k];
                            $a[0][$k] = $v;
                        }
                    } else {
                        $a[0][$k] = is_array($a[0][$k]) ? array_merge($a[0][$k], [$v]) : $v;
                    }
                } else {
                    $a[0][$k] = $v;
                }
            }
        }
        return $a[0];
    }

    /**
     * 移除数组中的 null 值
     *
     * @param  array   $array
     * @return array
     */
    public static function removeNull(array $array)
    {
        return array_filter($array, function ($val) {
            return !is_null($val);
        });
    }

    /**
     * 移除数组中的 '' 值
     *
     * @param  array   $array
     * @return array
     */
    public static function removeNothing(array $array)
    {
        return array_filter($array, function ($val) {
            return '' !== $val;
        });
    }

    /**
     * 用回调函数，根据数组键&值，过滤数组中的单元
     *
     * @param  array   $array
     * @param  mixed   $callback
     * @return array
     */
    public static function filterFull(array $array, $callback)
    {
        if (!is_callable($callback)) {
            trigger_error(__FUNCTION__ . '() expects parameter 2 to be a valid callback', E_USER_ERROR);
        }

        return $array = array_filter($array, function ($val) use (&$array, $callback) {
            $key = key($array);
            next($array);
            return (bool) $callback($key, $val);
        });
    }

    /**
     * 用回调函数，根据数组键值，过滤数组中的单元
     *
     * @param  array    $array
     * @param  callable $callback
     * @return array
     */
    public static function filterByKey(array &$array, $callback)
    {
        if (!is_callable($callback)) {
            trigger_error(__FUNCTION__ . '() expects parameter 2 to be a valid callback', E_USER_ERROR);
        }

        return $array = array_filter($array, function ($val) use (&$array, $callback) {
            $key = key($array);
            next($array);

            return (bool) $callback($key);
        });
    }

    /**
     * 将数据转换为字符，并在同一行输出
     *
     * @param  array    $array
     * @param  string   $separator
     * @param  string   $prefx
     * @return string
     */
    public static function joinInline(array $array, $separator = ', ', $prefx = '')
    {
        $tmp = [];
        foreach ($array as $key => $val) {
            $tmp[] = "$prefx$key: " . (is_array($val) ? self::joinInline($val, $separator) : $val);
        }
        return implode($separator, $tmp);
    }

    /**
     * 数据转 stdClass
     *
     * @param  array       $array
     * @return \stdClass
     */
    public static function toStd(array $array)
    {
        $std = new \stdClass;
        foreach ($array as $key => $val) {
            if (!$key) {
                continue;
            }
            $std->$key = is_array($val) ? self::toStd($val) : $val;
        }
        return $std;
    }

    // 把一个对象结构递归变成一数组结构
    public static function o2a($d)
    {
        if (is_object($d)) {
            if (method_exists($d, 'getArrayCopy')) {
                $d = $d->getArrayCopy();
            } elseif (method_exists($d, 'getArrayIterator')) {
                $d = $d->getArrayIterator()->getArrayCopy();
            } elseif (method_exists($d, 'toArray')) {
                $d = $d->toArray();
            } else
            // Gets the properties of the given object
            // with get_object_vars function
            {
                $d = get_object_vars($d);
            }
        }
        /*
         * Return array converted to object
         * Using __FUNCTION__ (Magic constant)
         * for recursive call
         */
        if (is_array($d)) {
            return array_map(__FUNCTION__, $d);
        }
        // Return array
        return $d;
    }
}
