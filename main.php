<?php
    // Get stock data as a JSON file from an outer resource (Portfolio)
    if(isset($_POST["refresh"]) && $_POST["refresh"] == "now"){

		// Gather URLs into an array
		$aURLs = array("https://data.portfolio.hu/all/json/4IG:interval=1M",
					"https://data.portfolio.hu/all/json/MOL:interval=1M",
					"https://data.portfolio.hu/all/json/ESTMEDIA:interval=1M",
					"https://data.portfolio.hu/all/json/FUTURAQUA:interval=1M",
					"https://data.portfolio.hu/all/json/WABERERS:interval=1M",
					"https://data.portfolio.hu/all/json/MTELEKOM:interval=1M");

    	// Initialize curl for async (multi) use
		$mh = curl_multi_init();

		// Create array for curl handlers
    	$aCurlHandles = array();

    	foreach ($aURLs as $id=>$url) {
			// Initialize a new curl instance at every iteration
        	$ch = curl_init();

			// Setup options
        	curl_setopt($ch, CURLOPT_URL, $url);

			// Save output for further usage with curl_multi_getcontent
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        	curl_setopt($ch, CURLOPT_HEADER, 0);

        	$aCurlHandles[$url] = $ch;
        	curl_multi_add_handle($mh,$ch);
    	}

    	$active = null;

		// Execute curl requests
    	do {
       		$mrc = curl_multi_exec($mh, $active);
    	}
    	while ($mrc == CURLM_CALL_MULTI_PERFORM);

    	while ($active && $mrc == CURLM_OK) {
        	if (curl_multi_select($mh) != -1) {
            	do {
                	$mrc = curl_multi_exec($mh, $active);
            	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
        	}
    	}
    	$html = "";

		// Iterate through the handles and get content
    	foreach ($aCurlHandles as $url=>$ch) {
			// Append it to $html with a delimeter at the end
        	$html .= curl_multi_getcontent($ch)."|||";

			// Remove handler
        	curl_multi_remove_handle($mh, $ch);
    	}

		// Close curl connection
    	curl_multi_close($mh);
		echo $html;
		exit();
	}
?>
<html>
    <head>
        <title>Stock AI</title>
        <meta charset="utf-8">
        <meta lang="en">
		<link rel="stylesheet" type="text/css" href="/style/style_ai.css">
        <link rel="icon" type="image/x-icon" href="/images/favstock.png">
        <meta name="description" content="An open-source service for analysing the most important Hungarian stocks.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
    </head>
    <body>
		<header>
			<a href="/main.php">Stock AI</a>
			<div class="headerRight">
				<a href="/downloads.php">Downloads</a>
				<a href="/cont.php">Contribute</a>
				<a href="/about.php">Information</a>
			</div>
		</header>
		<br><br><br><br>
        <div class="hStocks" id="hStocks"></div>
		<?php require_once 'template_pageBottom.php'; ?>
        <script src="/analysis.js" type="text/javascript"></script>
    </body>
</html>
