<?php
  function genDayVals($selected = NULL) {
    $result = "";
    for ($i = 1; $i < 32; $i++) {
      if ($i == $selected) {
        $result .= "<option value='{$i}' selected>{$i}</option>";
      } else {
        $result .= "<option value='{$i}'>{$i}</option>";
      }
    }
    return $result;
  }
?>
