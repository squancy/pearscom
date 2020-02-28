<?php
  function genWorkTypes($values, $selected = NULL) {
    $result = "";
    foreach ($values as $value) {
      $v = strtolower($value[0]);
      if ($value == $selected) {
        $result .= "<option value='{$v}' selected>{$value}</option>";
      } else {
        $result .= "<option value='{$v}'>{$value}</option>";
      }
    }
    return $result;
  }
?>
