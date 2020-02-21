<?php
  function replaceChars(&$str, $what, $toWhat) {
    if(strpos($str, $what) !== false){
      $str = str_replace($what, $toWhat, $str);
    } 
  }

  function base64url_encode(string $data, string $key): string {
    $method = 'AES-256-CBC';
    $ivSize = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($ivSize);
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
    $encrypted = base64_encode($iv . $encrypted);

    replaceChars($encrypted, '/', '__sH__');
    replaceChars($encrypted, '+', '__Ps__');
    replaceChars($encrypted, '=', '__qE__');
    return $encrypted;
  }

  function base64url_decode(string $data, string $key): string {   
    replaceChars($data, '__sH__', '/');
    replaceChars($data, '__Ps__', '+');
    replaceChars($data, '__qE__', '=');

    $method = 'AES-256-CBC';
    $data = base64_decode($data);
    $ivSize = openssl_cipher_iv_length($method);
    $iv = substr($data, 0, $ivSize);
    $data = openssl_decrypt(substr($data, $ivSize), $method, $key, OPENSSL_RAW_DATA, $iv);
    return $data;
  }

  $hshkey = "§7/%\'+9<q!54_%>/=1_1=-()AS*[sdd]18";
?>
