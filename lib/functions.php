<?php

/**
 * 判断数组中是否全部设置
 * @param array $array
 * @param string $keys
 * @return bool
 */
function isset_r(array $array, string $keys): bool {
    $keys = explode(',', $keys);
    foreach ($keys as $key) {
        if (!isset($array[$key])) {
            return false;
        }
    }
    return true;
}

