<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'safe_encrypt.php';
	require_once 'ccov.php';
	require_once 'headers.php';
	// Check if the user is logged in
	$u = "";
	$one = "1";
	if(isset($_SESSION['username'])){
		$u = mysqli_real_escape_string($conn, $_SESSION["username"]);
	}else{
		header('Location: /needlogged');
	}

	// Check if the user exists in the database
	$sql = "SELECT * FROM users WHERE username = ? AND activated = ? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	if($numrows < 1){
		header('Location: /usernotexist');
		exit();
	}

	$stmt->close();

	$otype = "aff";

	// Normal sugg. by the users's friends without limit
	// Get all friends
	$all_friends = array();
	$sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		array_push($all_friends, $row["user1"]);
	}
	$stmt->close();

	$sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND accepted=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		array_push($all_friends, $row["user2"]);
	}
	$stmt->close();

	$sugglist = "";
	$friendstags = array();
	$friendsGR = join("','", $all_friends);

	// Pagination
	// This first query is just to get the total count of rows
	$sql = "SELECT COUNT(id) FROM articles WHERE written_by IN ('$friendsGR')";
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	$stmt->bind_result($rows);
	$stmt->fetch();
	$stmt->close();
	// Here we have the total row count
	// This is the number of results we want displayed per page
	$page_rows = 21;
	// This tells us the page number of our last page
	$last = ceil($rows/$page_rows);
	// This makes sure $last cannot be less than 1
	if($last < 1){
		$last = 1;
	}
	// Establish the $pagenum variable
	$pagenum = 1;
	// Get pagenum from URL vars if it is present, else it is = 1
	if(isset($_GET['pn'])){
		$pagenum = preg_replace('#[^0-9]#', '', $_GET['pn']);
	}
	// This makes sure the page number isn't below 1, or more than our $last page
	if ($pagenum < 1) { 
	    $pagenum = 1; 
	} else if ($pagenum > $last) { 
	    $pagenum = $last; 
	}
	// This sets the range of rows to query for the chosen $pagenum
	$limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
	// Establish the $paginationCtrls variable
	$paginationCtrls = '';
	// If there is more than 1 page worth of results
	if($last != 1){
		/* First we check if we are on page one. If we are then we don't need a link to 
		   the previous page or the first page so we do nothing. If we aren't then we
		   generate links to the first page, and to the previous page. */
		if ($pagenum > 1) {
	        $previous = $pagenum - 1;
			$paginationCtrls .= '<a href="/article_suggestions?pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
			// Render clickable number links that should appear on the left of the target page number
			for($i = $pagenum-4; $i < $pagenum; $i++){
				if($i > 0){
			        $paginationCtrls .= '<a href="/article_suggestions?pn='.$i.'">'.$i.'</a> &nbsp; ';
				}
		    }
	    }
		// Render the target page number, but without it being a link
		$paginationCtrls .= ''.$pagenum.' &nbsp; ';
		// Render clickable number links that should appear on the right of the target page number
		for($i = $pagenum+1; $i <= $last; $i++){
			$paginationCtrls .= '<a href="/article_suggestions?pn='.$i.'">'.$i.'</a> &nbsp; ';
			if($i >= $pagenum+4){
				break;
			}
		}
		// This does the same as above, only checking if we are on the last page, and then generating the "Next"
	    if ($pagenum != $last) {
	        $next = $pagenum + 1;
	        $paginationCtrls .= ' &nbsp; &nbsp; <a href="/article_suggestions?pn='.$next.'">Next</a> ';
	    }
	}
	
	$sql = "SELECT lat, lon FROM users WHERE username = ? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$log_username);
	$stmt->execute();
	$stmt->bind_result($lat,$lon);
	$stmt->fetch();
	$stmt->close();

	$lat_m2 = $lat-0.7;
	$lat_p2 = $lat+0.7;

	$lon_m2 = $lon-0.7;
	$lon_p2 = $lon+0.7;

	$countSugg = 0;

    if(isset($_GET["otype"]) || $otype == "aff"){
    	$countSugg = 0;
    	if(isset($_GET["otype"])){ $otype = mysqli_real_escape_string($conn, $_GET["otype"]); }
	    $sql = "SELECT * FROM articles WHERE written_by IN ('$friendsGR') ORDER BY RAND() $limit";
	if($otype == "afn"){
	    $sql = "SELECT a.*,u.* FROM users AS u LEFT JOIN articles AS a ON a.written_by = u.username WHERE a.written_by NOT IN ('$friendsGR') AND lat BETWEEN ? AND ? AND lon BETWEEN ? AND ? AND a.written_by != ? ORDER BY RAND() $limit";

	}else if($otype == "afr"){
	    $sql = "SELECT a.*,u.* FROM users AS u LEFT JOIN articles AS a ON a.written_by = u.username WHERE a.written_by NOT IN ('$friendsGR') AND lat NOT BETWEEN ? AND ? AND lon NOT BETWEEN ? AND ? AND a.written_by != ? ORDER BY RAND() $limit";
	}

	$stmt = $conn->prepare($sql);
	if($otype == "afn" || $otype == "afr"){
	    $stmt->bind_param("sssss",$lat_m2,$lat_p2,$lon_m2,$lon_p2,$log_username);
	}
	$stmt->execute();
	$result2 = $stmt->get_result();
	while($row = $result2->fetch_assoc()){
		$wb = $row["written_by"];
		$tit = $row["title"];
		$tit = str_replace('\'', '&#39;', $tit);
		$tit = str_replace('\'', '&#34;', $tit);
		$tag = $row["tags"];
		array_push($friendstags, $row["tags"]);
		$pt_ = $row["post_time"];
		$pt = strftime("%b %d, %Y", strtotime($pt_));
		$pt_ = base64url_encode($pt_,$hshkey);
		$wb_ori = urlencode($wb);
		$cat = $row["category"];

		$cover = chooseCover($cat);

		$sugglist .= '<a href="/articles/'.$pt_.'/'.$wb_ori.'"><div class="article_echo_2" style="width: 100%;">'.$cover.'<div><p class="title_"><b>Author: </b>'.$wb.'</p>';
        $sugglist .= '<p class="title_"><b>Title: </b>'.$tit.'</p>';
        $sugglist .= '<p class="title_"><b>Posted: </b>'.$pt.'</p>';
        $sugglist .= '<p class="title_"><p class="title_"><b>Tags: </b>'.$tag.'</p>';
        $sugglist .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
        $countSugg++;
	}
    }
	if($result2->num_rows < 1){
	    $sugglist = "<p style='text-align: center; color: #999;'>Unfortunately, there are no articles fitting the criteria</p>";
	}
	if(isset($_GET["otype"])){
		echo $sugglist."!|||!".$countSugg;
		exit();
	}
	$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $u; ?> - More Suggestions</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
	<script src="/jquery_in.js" async></script>
	<script src="/text_editor.js" async></script>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
</head>
<body>
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
		<div id="data_holder">
            <div>
                <div><span id="countArtsS"><?php echo $countSugg; ?></span> suggested articles</div>
            </div>
        </div>
		<button id="sort" class="main_btn_fill">Filter Suggestions</button>
		<div id="sortTypes">
            <div class="gridDivS">
            	<p class="mainHeading">Related</p>
            	<div id="aff">Articles from friends</div>
            </div>
            <div class="gridDivS">
            	<p class="mainHeading">Geolocation</p>
            	<div id="afn">Articles from nearby users</div>
            </div>
            <div class="gridDivS">
            	<p class="mainHeading">Random</p>
            	<div id="afr">Articles from random users</div>
            </div>
        </div>
        <div class="clear"></div>
        <hr class="dim">
		<div id="userFlexArts" class="flexibleSol">
		    <?php echo $sugglist; ?>
		</div>
		<div class="clear"></div>
		<div id="paginationCtrls"><?php echo $paginationCtrls; ?></div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
		$( "#sort" ).click(function() {
          $( "#sortTypes" ).slideToggle( 200, function() {
            // Animation complete.
          });
        });

        function addListener(onw, w){
	        _(onw).addEventListener("click", function(){
	            _("userFlexArts").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
	            filterArts(w);
	        });
	    }

	    addListener("aff", "aff");
	    addListener("afn", "afn");
	    addListener("afr", "afr");

	    function filterArts(otype){
	        changeStyle(otype);
	        let req = new XMLHttpRequest();
	        req.open("GET", "/more_sugg_art.php?otype=" + otype, false);
	        req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	        req.onreadystatechange = function(){
	            if(req.readyState == 4 && req.status == 200){
	            	let data = req.responseText.split("!|||!");
	                _("userFlexArts").innerHTML = data[0];
	                _("countArtsS").innerHTML = data[1];
	            }
	        }
	        req.send();
	    }

	    function changeStyle(otype){
	        _(otype).style.color = "red";
	        if(otype != "aff") _("aff").style.color = "black";
	        if(otype != "afr") _("afr").style.color = "black";
	        if(otype != "afn") _("afn").style.color = "black";
	    }

	    changeStyle("aff");
	</script>
</body>
</html>