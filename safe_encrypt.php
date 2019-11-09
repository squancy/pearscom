<?php
    function base64url_encode(string $data, string $key): string
    {
        $method = 'AES-256-CBC';
        $ivSize = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivSize);
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        $encrypted = base64_encode($iv . $encrypted);
        if(strpos($encrypted, "/") !== false){
        	$encrypted = str_replace("/", "_sH_", $encrypted);
        }
        if(strpos($encrypted, "+") !== false){
        	$encrypted = str_replace("+", "_Ps_", $encrypted);
        }
        if(strpos($encrypted, "=") !== false){
        	$encrypted = str_replace("=", "_qE_", $encrypted);
        }
        return $encrypted;
    }

    function base64url_decode(string $data, string $key): string
    {   
        if(strpos($data, "_sH_") !== false){
        	$data = str_replace("_sH_", "/", $data);
        }
        if(strpos($data, "_Ps_") !== false){
        	$data = str_replace("_Ps_", "+", $data);
        }
        if(strpos($data, "_qE_") !== false){
        	$data = str_replace("_qE_", "=", $data);
        }
        $method = 'AES-256-CBC';
        $data = base64_decode($data);
        $ivSize = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivSize);
        $data = openssl_decrypt(substr($data, $ivSize), $method, $key, OPENSSL_RAW_DATA, $iv);
        return $data;
    }

    $hshkey = "§7/%\'+9<q!54_%>/=1_1=-()AS*[sdd]18";
    
    /*echo base64url_encode("asd", $hshkey);
    echo base64url_decode("dACDmSusbXp92ioIf2sLjLqiWBXiy02IdAcUXazfWtA_qE_", $hshkey);*/
?>