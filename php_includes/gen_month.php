<?php
  function genMonthVals($selected = NULL) {
    $result = "";
    for ($i = 1; $i <= 12; ++$i) {
      $currentMonth = date('F', mktime(0, 0, 0, $i, 1));
      if ($currentMonth == $selected) {
        $result .= "<option value='{$currentMonth}' selected>{$currentMonth}</option>";
      } else {
        $result .= "<option value='{$currentMonth}'>{$currentMonth}</option>";
      }
    }
    return $result;
  }
?>
