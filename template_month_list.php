<?php
  $months = array('Jan.', 'Feb.', 'Mar.', 'Apr.', 'May', 'June', 'July', 'Aug.', 'Sept.',
    'Oct.', 'Nov.', 'Dec.');
  foreach ($months as $month) {
    echo "<option value='{$month}'>{$month}</option>";
  }
?>
