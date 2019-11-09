<?php
    require_once 'php_includes/conn.php';
    $pm = "CREATE TABLE IF NOT EXISTS pm (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            receiver VARCHAR(100) NOT NULL,
            sender VARCHAR(100) NOT NULL,
            senttime DATETIME NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            sdelete ENUM('0','1') NOT NULL DEFAULT '0',
            rdelete ENUM('0','1') NOT NULL DEFAULT '0',
            parent VARCHAR(255) NOT NULL,
            hasreplies ENUM('0','1') NOT NULL DEFAULT '0',
            rread ENUM('0','1') NOT NULL DEFAULT '0',
            sread ENUM('0','1') NOT NULL DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";

    $query = mysqli_query($conn, $pm);
        if($query === TRUE){
            echo '<h3>pm table created OK :)</h3>';
        }else{
            echo '<h3>pm table NOT created :(</h3>';
        }
?>