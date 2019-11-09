<?php
    require_once '../sec_session_start.php';
    sec_session_start();
    if(isset($_POST["ctype"])){
        $idman = $_POST["ctype"];
        $val = "yes_$idman";
        setcookie("cookieset_", $val, strtotime( '+30 days' ), "/", "", "", TRUE);
        echo "agree";
    }
?>