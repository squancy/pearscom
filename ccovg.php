<?php
  function chooseCat($cat){
      $rcat = "";
    if($cat == 1){
      $rcat = "Animals";
    }else if($cat == 2){
      $rcat = "Relationships";
    }else if($cat == 3){
      $rcat = "Friends &amp; Family";
    }else if($cat == 4){
      $rcat = "Freetime";
    }else if($cat == 5){
      $rcat = "Sports";
    }else if($cat == 6){
      $rcat = "Games";
    }else if($cat == 7){
      $rcat = "Knowledge";
    }else if($cat == 8){
      $rcat = "Other";
    }
    
    return $rcat;
  }
?>
