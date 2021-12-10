<?php
require_once("Utils.php");

for ($i=0;$i<1000;$i++) {
    echo gen_rand_id()."\n";
}

for ($i=0;$i<1000;$i++) {
    echo gen_nonce()."\n";
}

for ($i=0;$i<1000;$i++) {
    echo gen_asset_id(100000)."\n";
}