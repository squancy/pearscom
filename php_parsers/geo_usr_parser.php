<?php
    require_once '../php_includes/check_login_statues.php';
    if(isset($_POST["updateLat"]) && isset($_POST["updateLon"])){
        $ulat = preg_replace('#[^0-9.,-]#', '', $_POST["updateLat"]);
        $ulon = preg_replace('#[^0-9.,-]#', '', $_POST['updateLon']);
        if($ulat == "" || $ulon == ""){
            echo "Longitude or latitude is missing asdsd";
        }
        $sql = "UPDATE users SET lat = ?, lon = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss",$ulat,$ulon,$log_username);
        $stmt->execute();
        $stmt->close();
        echo "update_geo_success";
    }
?>