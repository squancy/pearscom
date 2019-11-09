<?php
		$aURLs = array("https://data.portfolio.hu/all/json/4IG:interval=1M", "https://data.portfolio.hu/all/json/MOL:interval=1M", "https://data.portfolio.hu/all/json/WABERERS:interval=1M", "https://data.portfolio.hu/all/json/FUTURAQUA:interval=1M", "https://data.portfolio.hu/all/json/MTELEKOM:interval=1M", "https://data.portfolio.hu/all/json/ESTMEDIA:interval=1M"); // array of URLs
    $mh = curl_multi_init(); // init the curl Multi

    $aCurlHandles = array(); // create an array for the individual curl handles

    foreach ($aURLs as $id=>$url) { //add the handles for each url
        #$ch = curl_setup($url,$socks5_proxy,$usernamepass);
        $ch = curl_init(); // init curl, and then setup your options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // returns the result - very important
        curl_setopt($ch, CURLOPT_HEADER, 0); // no headers in the output

        $aCurlHandles[$url] = $ch;
        curl_multi_add_handle($mh,$ch);
    }

    $active = null;
    //execute the handles
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
/* This is the relevant bit */
        // iterate through the handles and get your content
    foreach ($aCurlHandles as $url=>$ch) {
        $html .= curl_multi_getcontent($ch)."|||"; // get the content
                // do what you want with the HTML
        curl_multi_remove_handle($mh, $ch); // remove the handle (assuming  you are done with it);
    }
/* End of the relevant bit */

    curl_multi_close($mh); // close the curl multi handler
	echo $html;
?>
