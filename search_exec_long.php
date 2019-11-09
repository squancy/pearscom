<?php
	require_once 'timeelapsedstring.php';
	require_once 'php_includes/check_login_statues.php';
	$output = "";
	$u = "";
	$count = 0;
	$one = "1";
	if(isset($_GET['search'])){
		$u = mysqli_real_escape_string($conn, $_GET["search"]);	
		if ($u == ""){
			// They tried to defeat our security
			header('Location: index.php');
			exit();
		}
		$u_search = "%$u%";

		// This first query is just to get the total count of rows
		$sql = "SELECT COUNT(id) FROM users 
		        WHERE username LIKE ? AND activated = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$u_search,$one);
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
				$paginationCtrls .= '<a href="search_exec_long.php?search='.$u.'&pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
				// Render clickable number links that should appear on the left of the target page number
				for($i = $pagenum-4; $i < $pagenum; $i++){
					if($i > 0){
				        $paginationCtrls .= '<a href="search_exec_long.php?search='.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
					}
			    }
		    }
			// Render the target page number, but without it being a link
			$paginationCtrls .= ''.$pagenum.' &nbsp; ';
			// Render clickable number links that should appear on the right of the target page number
			for($i = $pagenum+1; $i <= $last; $i++){
				$paginationCtrls .= '<a href="search_exec_long.php?search='.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
				if($i >= $pagenum+4){
					break;
				}
			}
			// This does the same as above, only checking if we are on the last page, and then generating the "Next"
		    if ($pagenum != $last) {
		        $next = $pagenum + 1;
		        $paginationCtrls .= ' &nbsp; &nbsp; <a href="search_exec_long.php?search='.$u.'&pn='.$next.'">Next</a> ';
		    }
		}
		include("php_includes/conn.php");	
		$sql = "SELECT * FROM users 
		        WHERE username LIKE ? AND activated = ?
				ORDER BY username ASC $limit";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$u_search,$one);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			while ($row = $result->fetch_assoc()){
				$uname = $row["username"];
				$country = $row["country"];
				$avatar = $row["avatar"];
				$isonline = $row["online"];
				$bday = $row["bday"];
				$signupdate = $row["signup"];
				$uname_original = $uname;
				$mfor = time_elapsed_string($signupdate);
				$age = floor((time() - strtotime($bday)) / 31556926);
				if(strlen($uname) > 36){
					$uname = substr($uname, 0, 33);
					$uname .= ' ...';
				}

				if($isonline == "yes"){
					$isonline = "border: 2px solid rgb(0, 161, 255);";
				}else{
					$isonline = "border: 2px solid grey;";
				}

				if($avatar == NULL){
					$pcurl = "/images/avdef.png";
				}else{
					$pcurl = "/user/".$uname."/".$avatar;
				}

				$output .= '
							<a href="/user/'.$uname.'/">
								<div class="lazy-bg genBg sepDivs" data-src=\''.$pcurl.'\' style="width: 50px; height: 50px; border-radius: 50%; float: left; margin-right: 5px; '.$isonline.'"></div>
							</a>
							<div class="flexibleSol" style="justify-content: space-evenly; flex-wrap: wrap;" id="sLong">
								<p><a href="/user/'.$uname.'/">'.$uname.'</a></p>
								<p>'.$country.'</p>
								<p>'.$age.' years old</p>
								<p>Member for '.$mfor.'</p>
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
	<title>Search for users</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="/js/jjs.js" async></script>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<script src="/js/lload.js"></script>
</head>
<body>
	<?php require_once 'template_pageTop.php' ?>
	<div id="pageMiddle_2">
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
</body>
</html>