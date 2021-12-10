<?php

//生成asset_id
function gen_asset_id($appId) {
    return gen_id_help($appId, 0);
}

// 生成nonce值
function gen_nonce() {
    $randId1 = gen_rand_id();
    $randId2 = gen_rand_id();
    $content = sprintf("%d#%d#%d", $randId1, $randId2, nanosectime());
    $sign    = sign_to_int($content);
    $nonce   = $sign & 0x7fffffffffffffff;
    return $nonce;
}

// 生成伪唯一ID
function gen_rand_id() {
    $nano     = nanosectime();
    $randNum1 = mt_rand(0, mt_getrandmax());
    $randNum2 = mt_rand(0, mt_getrandmax());
    $shift1   = mt_rand(0, 15) + 2;
    $shift2   = mt_rand(0, 7) + 1;
    $randId   = (($randNum1 >> $shift1) + ($randNum2 >> $shift2) + ($nano >> 1)) & 0x7fffffffffffffff;
    return $randId;
}

function nanosectime() {
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    $nanotime = sprintf("%d%06d", $msectime, mt_rand(0, 999999));
    return (int)$nanotime;
}

function gen_id_help($baseId, $flag) {
    $content = sprintf("%d#%d#%d", $baseId, $flag, nanosectime());
    $s       = sign_to_int($content);
    $r1      = gen_rand_id();
    $r2      = gen_rand_id();
    $lk      = $baseId;

    $id = ($lk & 0x0000000000fffff);
    $id += (($r2 & 0x000000000000fff0 >> 4) << 20);
    if ($flag == 1) {
        $id += (0x0000000000000001 << 32);
    }
    $id += (($r1 & 0x00000000000000ff) << 33);
    $id += (($s & 0x000000000000ffff) << 41);
    $id += (($r2 & 0x000000000000000f) << 57);
    return $id;
}

// 对字符串Hash后转化为整数
function sign_to_int($content) {
    $digest = md5($content);
    $seg1   = hexdec('0x' . substr($digest, 0, 4));
    $seg2   = hexdec('0x' . substr($digest, 4, 4));
    $seg3   = hexdec('0x' . substr($digest, 8, 4));
    $seg4   = hexdec('0x' . substr($digest, 12, 4));
    $sign1  = $seg1 + $seg3;
    $sign2  = $seg2 + $seg4;
    $sign   = ($sign1 & 0x00000000ffffffff) | ($sign2 << 32);
    return $sign;
}