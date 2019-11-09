<?php
	require_once 'timeelapsedstring.php';
	require_once 'php_includes/check_login_statues.php';
	require_once 'safe_encrypt.php';
	require_once 'headers.php';
	require_once 'ccov.php';

	$output = "";
	$u = "";
	$count = 0;
	if(isset($_GET['search'])){
		$u = mysqli_real_escape_string($conn, $_GET["search"]);	
		if ($u == ""){
			// They tried to defeat our security
			header('Location: index.php');
			exit();
		}
		$u_search = "$u%";

		$clause = "written_by LIKE ? OR title LIKE ? OR tags LIKE ? OR category LIKE ?";
		$inputText = "Search for articles by their author, title, category or tags";
		if(isset($_GET["inmy"]) && $_GET["inmy"] == "yes"){
			$clause = "(written_by = ?) AND (title LIKE ? OR tags LIKE ? OR category LIKE ?)";
			$inputText = "Search in your articles by their title, category or tags";
		}

		// This first query is just to get the total count of rows
		$sql = "SELECT COUNT(id) FROM articles 
		        WHERE $clause";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$u_search,$u_search,$u_search,$u_search);
		$stmt->execute();
		$stmt->bind_result($rows);
		$stmt->fetch();
		$stmt->close();
		// Here we have the total row count
		// This is the number of results we want displayed per page
		$page_rows = 36;
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
				$paginationCtrls .= '<a href="/search_articles/'.$u.'&pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
				// Render clickable number links that should appear on the left of the target page number
				for($i = $pagenum-4; $i < $pagenum; $i++){
					if($i > 0){
				        $paginationCtrls .= '<a href="/search_articles/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
					}
			    }
		    }
			// Render the target page number, but without it being a link
			$paginationCtrls .= ''.$pagenum.' &nbsp; ';
			// Render clickable number links that should appear on the right of the target page number
			for($i = $pagenum+1; $i <= $last; $i++){
				$paginationCtrls .= '<a href="/search_articles/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
				if($i >= $pagenum+4){
					break;
				}
			}
			// This does the same as above, only checking if we are on the last page, and then generating the "Next"
		    if ($pagenum != $last) {
		        $next = $pagenum + 1;
		        $paginationCtrls .= ' &nbsp; &nbsp; <a href="/search_articles/'.$u.'&pn='.$next.'">Next</a> ';
		    }
		}
	
		$sql = "SELECT * FROM articles WHERE $clause $limit";
		$stmt = $conn->prepare($sql);
		if(isset($_GET["inmy"]) && $_GET["inmy"] == "yes"){
			$stmt->bind_param("ssss",$log_username,$u_search,$u_search,$u_search);
		}else{
			$stmt->bind_param("ssss",$u_search,$u_search,$u_search,$u_search);
		}
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			while ($row = $result->fetch_assoc()){
				$written_by = $row["written_by"];
				$wbori = urlencode($written_by);
				$title = $row["title"];
				$tags = $row["tags"];
				$cat = $row["category"];
				$post_time = $row["post_time"];
				$pt = strftime("%b %d, %Y", strtotime($post_time));
				$agoform = time_elapsed_string($post_time);

				if(strlen($tags) > 20){
					$tags = mb_substr($tags, 0, 17, "utf-8");
					$tags .= '...';
				}

				if(strlen($title) > 20){
					$title = mb_substr($title, 0, 17, "utf-8");
					$title .= '...';
				}
				
				$post_time = base64url_encode($post_time,$hshkey);

				$cover = chooseCover($cat);

				$cover = preg_replace('/<img src="\/images\/\w+\/(\w+)\.jpg"\s+class="cover_art">/', "/images/art_cover/$1.jpg", $cover);

				$output .= '<a href="/articles/'.$post_time.'/'.$wbori.'">
								<div class="lazy-bg genBg sepDivs" data-src=\''.$cover.'\' style="width: 50px; height: 50px; border-radius: 50%; float: left; margin-right: 5px;"></div>
							</a>
							<div class="flexibleSol" style="justify-content: space-evenly; flex-wrap: wrap;" id="sLong">
								<p><a href="/user/'.$wbori.'/">'.$written_by.'</a></p>
								<p>'.$title.'</p>
								<p>'.$tags.'</p>
								<p>'.$cat.'</p>
								<p>Published '.$agoform.' ago</p>
							</div>
							<div class="clear"></div>
							<hr class="dim">';

				$count++;
			}
		} else {
			// No results from search
			$output = "<p class='txtc' style='color: #999;'>Unfortunately, no results were found</p>";
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Pearscom - Search for articles</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="/js/jjs.js" async></script>
	<script src="/js/main.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<script src="/js/ajax.js" async></script>
	<script src="/js/create_down.js" async></script>
	<style type="text/css">
        @media only screen and (max-width: 1000px){ 
          #searchArt{
            width: 90% !important;
          }

          #artSearchBtn{
            width: 10% !important;
          }

          @media only screen and (max-width: 500px){
            #searchArt {
            width: 85% !important;
          }

          #artSearchBtn {
            width: 15% !important;
          }
        }
    }
    </style>
	<script type="text/javascript">
		let inmy = "<?php echo $_GET["inmy"]; ?>";
		function getLSearchArt() {
		  var e = _("searchArt").value;
		  if ("" == e) {
		    return _("artSearchResults").style.display = "none", false;
		  }
		  var r = encodeURI(e);
		  if(inmy == "yes") window.location = "/search_articles/" + r + "&inmy=yes";
		  else window.location = "/search_articles/" + r;
		}
	</script>
</head>
<body>
	<?php require_once 'template_pageTop.php' ?>
	<div id="pageMiddle_2">
		<div id="artSearch">
			<div id="artSearchInput">
		    	<input id="searchArt" type="text" autocomplete="off" placeholder="<?php echo $inputText; ?>">
		    	<div id="artSearchBtn" onclick="getLSearchArt()"><img src="/images/searchnav.png" width="17" height="17"></div>
		    </div>
		    <div class="clear"></div>
		</div>
		<br>
		<div id="long_search" class="genWhiteHolder">
			<?php 
				$time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
			    $time = number_format((float)$time, 2, '.', '');
			    echo "<p style='font-size: 18px; color: #999; margin-top: 0;' class='txtc'>About ".$count." match(es) found in {$time} seconds</p>";
				echo $output;
			?>
		</div>
		<div id="pagination_controls"><?php echo $paginationCtrls; ?></div>
	</div>
	<?php require_once 'template_pageBottom.php' ?>
	<script type="text/javascript">
        /*function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],r=document.createElement("link");r.id=e,r.rel="stylesheet",r.type="text/css",r.href="/style/dark_style.css",r.media="all",t.appendChild(r)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();*/
    </script>
</body>
</html>