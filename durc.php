<?php
    function convDur($dur){
      $minutes = $dur / 60;
      $seconds = $dur % 60;
      if($minutes < 10 && $seconds < 10){
          return "0".floor($minutes).":0".$seconds;
      }else if($minutes < 10 && $seconds >= 10){
          return "0".floor($minutes).":".$seconds;
      }else if($minutes >= 10 && $seconds >= 10){
          return floor($minutes).":".$seconds;
      }else if($minutes >= 10 && $seconds < 10){
          return floor($minutes).":0".$seconds;
      }
    }
?>