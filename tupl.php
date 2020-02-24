<?php
  require_once 'safe_encrypt.php';

  // Generate a unique img name
  function imgHash($uname, $fileExt){
    $time = time();
    $hshkey = openssl_random_pseudo_bytes(64);
    $gather = $uname.$time;
    $gather = urlencode($gather);
    $gather = str_shuffle($gather);
    $gather = hash('gost',$gather);
    $gather = substr($gather, 0, 20);
    $gather = str_shuffle($gather);
    $gather = base64url_encode($gather,$hshkey);
    $gather = str_shuffle($gather);
    $fname = $gather.".".$fileExt;
    
    return $fname;
  }
?>
