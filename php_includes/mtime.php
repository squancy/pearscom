<?php
  /*
    Benchmark the amount of time needed for search results
  */

  function measureTime() {
    $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
    $time = number_format((float) $time, 2, '.', '');
    return "
      <p style='font-size: 18px; color: #999; margin-top: 0;' class='txtc'>
        About ".$count." match(es) found in {$time} seconds
      </p>
    ";
  }
?>
