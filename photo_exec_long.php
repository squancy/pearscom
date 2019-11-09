<?php
	require_once 'timeelapsedstring.php';
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';

	$output = "";
	$u = "";
	$count = 0;

	if(isset($_GET['search']) && isset($_GET["uU"])){
		$u = mysqli_real_escape_string($conn, $_GET["search"]);
		$uU = mysqli_real_escape_string($conn, $_GET["uU"]);
		if ($u == ""){
			// They tried to defeat our security
			header('Location: /index');
			exit();
		}
		$u_search = "%$u%";

		// This first query is just to get the total count of rows
		$sql = "SELECT COUNT(id) FROM photos 
		        WHERE user = ? AND gallery LIKE ? OR description LIKE ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$uU,$u_search,$u_search);
		$stmt->execute();
		$stmt->bind_result($rows);
		$stmt->fetch();
		$stmt->close();
		// Here we have the total row count
		// This is the number of results we want displayed per page
		$page_rows = 30;
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
				$paginationCtrls .= '<a href="/photo_search/'.$u.'&pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
				// Render clickable number links that should appear on the left of the target page number
				for($i = $pagenum-4; $i < $pagenum; $i++){
					if($i > 0){
				        $paginationCtrls .= '<a href="/photo_search/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
					}
			    }
		    }
			// Render the target page number, but without it being a link
			$paginationCtrls .= ''.$pagenum.' &nbsp; ';
			// Render clickable number links that should appear on the right of the target page number
			for($i = $pagenum+1; $i <= $last; $i++){
				$paginationCtrls .= '<a href="/photo_search/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
				if($i >= $pagenum+4){
					break;
				}
			}
			// This does the same as above, only checking if we are on the last page, and then generating the "Next"
		    if ($pagenum != $last) {
		        $next = $pagenum + 1;
		        $paginationCtrls .= ' &nbsp; &nbsp; <a href="/photo_search/'.$u.'&pn='.$next.'">Next</a> ';
		    }
		}
		include("php_includes/conn.php");	
		$sql = "SELECT * FROM photos 
		        WHERE user = ? AND (gallery LIKE ? OR description LIKE ?) $limit";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss",$uU,$u_search,$u_search);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			while ($row = $result->fetch_assoc()){
				$filename = $row["filename"];
				$gallery = $row["gallery"];
				$description = $row["description"];
				$uploaddate = $row["uploaddate"];
				$ud = strftime("%b %d, %Y", strtotime($uploaddate));
				
				if(strlen($description) > 20){
				    $description = mb_substr($description, 0, 17, "utf-8");
				    $description .= "...";
				}
				
				if($description == ""){
				    $description = "No description given";
				}
				
				$uds = time_elapsed_string($uploaddate);
                $pcurl = '/user/'.$uU.'/'.$filename.'';
                
				$output .= '<a href="/photo_zoom/'.urlencode($uU).'/'.$filename.'">
								<div class="lazy-bg genBg sepDivs" data-src=\''.$pcurl.'\' style="width: 50px; height: 50px; border-radius: 50%; float: left; margin-right: 5px;"></div>
							</a>
							<div class="flexibleSol" style="justify-content: space-evenly; flex-wrap: wrap;" id="sLong">
								<p>'.$gallery.'</p>
								<p>'.$description.'</p>
								<p>Published '.$uds.' ago</p>
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
	<title>Search for photos</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="/js/jjs.js" async></script>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
	<script src="/js/lload.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
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
		function getLSearchArt(){
		      var u = _("searchArt").value;
		      if(u == ""){
		        _("artSearchResults").style.display = "none";
		        return false;
		      }
		      var x = encodeURI(u);
		      window.location = "/photo_search/"+x+"&uU=<?php echo $uU; ?>";
		    }
	</script>
</head>
<body>
	<?php require_once 'template_pageTop.php' ?>
	<div id="pageMiddle_2">
		<div id="artSearch">
			<div id="artSearchInput">
		    	<input id="searchArt" type="text" autocomplete="off" placeholder="Search for photos by their gallery name or description">
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
		<div class="clear"></div>
		<div id="pagination_controls" style="margin-top: 30px; margin-bottom: 30px;"><?php echo $paginationCtrls; ?></div>
	</div>
	<?php require_once 'template_pageBottom.php' ?>
	<script type="text/javascript">
	    function getCookie(cname) {
            var name = cname + "=";
            var decodedCookie = decodeURIComponent(document.cookie);
            var ca = decodedCookie.split(';');
            for(var i = 0; i <ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }
	 	function setDark(){
            var cssId = 'thisClassDoesNotExist';
            if (!document.getElementById(cssId)){
                var head  = document.getElementsByTagName('head')[0];
                var link  = document.createElement('link');
                link.id   = cssId;
                link.rel  = 'stylesheet';
                link.type = 'text/css';
                link.href = '/style/dark_style.css';
                link.media = 'all';
                head.appendChild(link);
            }
        }
        var isdarkm = getCookie("isdark");
        if(isdarkm == "yes"){
            setDark();
        }
    </script>
</body>
</html>