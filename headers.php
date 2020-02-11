<?php
  header('X-Powered-By: PHP/7.1.15');
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
  header('X-Content-Type-Options: nosniff');
  header("X-XSS-Protection: 1; mode=block");
  header("Content-Security-Policy: default-src https: 'unsafe-eval' * blob: 'unsafe-inline';
    object-src 'none'; frame-ancestors https://www.pearscom.com; font-src 'self' data:;
    img-src https://www.pearscom.com https://maps.googleapis.com data:");
  header('Referrer-Policy: no-referrer, strict-origin-when-cross-origin');
  header('Public-Key-Pins: max-age=1296000; includeSubDomains;
    pin-sha256="oO+llhra8ivcCOlAIrletxRgtAEq5jZGwgqhPM+sFFI=";
    pin-sha256="YLh1dUR9y6Kja30RrAn7JKnbQG/uEtLMkBgFF2Fuihg=";
    pin-sha256="Vjs8r4z+80wjNcr1YKepWQboSIRi63WsWXhIMN+eWys="');
  header('Access-Control-Allow-Origin: https://pearscom.com');
  header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
  header("Pragma: no-cache"); // HTTP 1.0.
  header("Expires: 0"); // Proxies.
?>
