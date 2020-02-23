<?php
  require_once 'c_array.php';
  foreach ($countries as $country) {
    echo "<option value='{$country}'>{$country}</option>";
  }
?>
