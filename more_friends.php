<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';
	// Make sure the user is logged in
	if(isset($_SESSION['username'])){
		$u = $_SESSION['username'];
	} else {
	    header("Location: /needlogged");
	    exit();	
	}
	
	// Select the member from the users table
    $one = "1";
    $countfs = 0;
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    // Now make sure the user exists in the table
    if($numrows < 1){
    	header('location: /usernotexist');
    	exit();
    }
    $stmt->close();
    
    $otype = "all";

	// Initialize Some Things
	$moMoFriends = "";
	$my_friends = array();
	$their_friends = array();
	$myf = array();

	// Get Friend Array
	$sql = "SELECT DISTINCT user1, user2 
			FROM friends 
			WHERE (user1=? OR user2=?)
			AND accepted=?";	
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("sss",$log_username,$log_username,$one);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	while($row = $result->fetch_assoc()){
		array_push($my_friends, $row["user2"]);
		array_push($my_friends, $row["user1"]);
	}
	//remove your id from array
	$my_friends = array_diff($my_friends, array($log_username));
	//reset the key values
	$my_friends = array_values($my_friends);
	$myfs = join("','",$my_friends);

	// Get Friends Of Friends Array
	// Exclude Myself From Query
	foreach ($my_friends as $k => $v) {
		$sql = "SELECT user1, user2 
				FROM friends
				WHERE (user1=? OR user2=?) 
				AND accepted=? 
				AND user1!=? 
				AND user2!=?
				AND user1 NOT IN ('$myfs')
				AND user2 NOT IN ('$myfs')
				ORDER BY RAND()";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sssss",$v,$v,$one,$log_username,$log_username);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			array_push($their_friends, $row["user2"]);
			array_push($their_friends, $row["user1"]);
			
			// Remove any duplicates
			$their_friends = array_unique($their_friends);
			// Remove common friends
			$their_friends = array_diff($their_friends, $my_friends);
			// Reset array values
			$their_friends = array_values($their_friends);
		}
	}

	// This first query is just to get the total count of rows
	$sql = "SELECT COUNT(?)";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s",$their_friends);
	$stmt->execute();
	$stmt->bind_result($rows);
	$stmt->fetch();
	$stmt->close();
	// Here we have the total row count
	// This is the number of results we want displayed per page
	$page_rows = 100;
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
			$paginationCtrls .= '<a href="/friend_suggestions/'.$previous.'">Previous</a> &nbsp; &nbsp; ';
			// Render clickable number links that should appear on the left of the target page number
			for($i = $pagenum-4; $i < $pagenum; $i++){
				if($i > 0){
			        $paginationCtrls .= '<a href="/friend_suggestions/'.$i.'">'.$i.'</a> &nbsp; ';
				}
		    }
	    }
		// Render the target page number, but without it being a link
		$paginationCtrls .= ''.$pagenum.' &nbsp; ';
		// Render clickable number links that should appear on the right of the target page number
		for($i = $pagenum+1; $i <= $last; $i++){
			$paginationCtrls .= '<a href="/friend_suggestions/'.$i.'s">'.$i.'</a> &nbsp; ';
			if($i >= $pagenum+4){
				break;
			}
		}
		// This does the same as above, only checking if we are on the last page, and then generating the "Next"
	    if ($pagenum != $last) {
	        $next = $pagenum + 1;
	        $paginationCtrls .= ' &nbsp; &nbsp; <a href="/friend_suggestions/'.$next.'">Next</a> ';
	    }
	}

    $countSugg = 0;
    if((isset($_GET["otype"]) && $_GET["otype"] == "suggf_4") || ($otype == "all" && !isset($_GET["otype"]))){
    	// Build Output From Results
    	$sex = "Male";
    	$foff = array();
        if(isset($_GET["otype"])){ $otype = mysqli_real_escape_string($conn, $_GET["otype"]); }
    	if (array_key_exists('0', $their_friends)){
    		foreach ($their_friends as $k2 => $v2){
    			$sql = "SELECT * FROM users WHERE username=? LIMIT 1";
    			$stmt = $conn->prepare($sql);
    			$stmt->bind_param("s",$v2);
    			$stmt->execute();
    			$result = $stmt->get_result();
    			while($row = $result->fetch_assoc()){
    			    $countfs++;
    				$avatar = $row["avatar"];
    				$country = $row["country"];
    				$gender = $row["gender"];
    				$uname = $row["username"];
    				$unameori = urlencode($uname);
    				$unamerq = $row["username"];
    				array_push($foff,$unamerq);
    				if(strlen($uname) > 20){
    				    $uname = mb_substr($uname, 0, 16, "utf-8");
    				    $uname .= " ...";
    				}
    				if(strlen($country) > 20){
    				    $country = mb_substr($country, 0, 16, "utf-8");
    				    $country .= " ...";
    				}
    				if($gender == "f"){
    					$sex = "Female";
    				}
    				$online = $row["online"];
    				if($online == "yes"){
                        $online = "border: 2px solid #00a1ff";
                    }else{
                        $online = "border: 2px solid #999";
                    }
    				$sql = "SELECT accepted FROM friends WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1";
    				$stmt = $conn->prepare($sql);
    				$stmt->bind_param("ssss",$log_username,$unamerq,$unamerq,$log_username);
    				$stmt->execute();
    				$stmt->bind_result($zeroone);
    				$stmt->fetch();
    				$stmt->close();
    				$pcurl = "/user/".$unamerq."/".$avatar;
    				if($zeroone == "0"){
                        $friend_btn = "<p style='color: #999; margin-right: 5px;'>Friend request sent</p>";
                    }else if($zeroone == NULL || $zeroone == ""){
                        $friend_btn = '<span id="friendBtn_'.$unamerq.'"><button onclick="friendToggle(\'friend\',\''.$unamerq.'\',\'friendBtn_'.$unamerq.'\')" class="main_btn_fill" style="border: 0; border-radius: 20px; padding: 7px; margin-top: 5px;">Request as friend</button></span>';
                    }
        
                    if($avatar == NULL || $avatar == ""){
                        $pcurl = '/images/avdef.png';
                    }else{
                        $pcurl = '/user/'.$unamerq.'/'.$avatar;
                    }
        
                    $moMoFriends .= '<div><a href="/user/'.$unameori.'/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 70px; height: 70px; float: right; display: inline-block; border-radius: 50%; '.$online.'" class="lazy-bg"></div></a><p><a href="/user/'.$unameori.'/">'.$uname.'</a></p><p>'.$country.'</p>'.$friend_btn.'</div>';
    				$stmt->close();
                    if(!isset($_GET["otype"])){ $countSugg++; }
    			}
    		}
    	}
        if(isset($_GET["otype"]) && $moMoFriends == ""){
            echo "<p style='color: #999; text-align: center;'>Sorry, there are no friend suggestions in this category</p>";
            exit();
        }else if(isset($_GET["otype"]) && $moMoFriends != ""){
            echo $moMoFriends;
            exit();
        }
    }
	
	$myfriends = join("','",$my_friends);
	$foffi = join("','",$foff);
	$page_rows = $page_rows - $countfs;
    $limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
	
	$geous = array();
	if((isset($_GET["otype"]) && ($_GET["otype"] == "suggf_0" || $_GET["otype"] == "suggf_1" || $_GET["otype"] == "suggf_2" || $_GET["otype"] == "suggf_3")) || ($otype == "all" && !isset($_GET["otype"]))){
    	if($moMoFriends == "" || $countfs < 100 || isset($_GET["otype"])){
            if(isset($_GET["otype"])){ $otype = mysqli_real_escape_string($conn, $_GET["otype"]); }
    	    // SELECT USERS'S LAT AND LON COORDINATES
    		$sql = "SELECT lat, lon FROM users WHERE username = ? LIMIT 1";
    		$stmt = $conn->prepare($sql);
    		$stmt->bind_param("s",$log_username);
    		$stmt->execute();
    		$stmt->bind_result($lat,$lon);
    		$stmt->fetch();
    		$stmt->close();
    
    		if($otype == "suggf_0"){ // 5 km
    			$lat_m2 = $lat-0.03;
	    		$lat_p2 = $lat+0.03;
	    
	    		$lon_m2 = $lon-0.03;
	    		$lon_p2 = $lon+0.03;
    		}else if($otype == "suggf_1"){ // 10 km
    			$lat_m2 = $lat-0.06;
	    		$lat_p2 = $lat+0.06;
	    
	    		$lon_m2 = $lon-0.06;
	    		$lon_p2 = $lon+0.06;
    		}else if($otype == "suggf_2"){ // 50 km
    			$lat_m2 = $lat-0.3;
	    		$lat_p2 = $lat+0.3;
	    
	    		$lon_m2 = $lon-0.3;
	    		$lon_p2 = $lon+0.3;
    		}else if($otype == "suggf_3"){ // 100 km
    			$lat_m2 = $lat-0.6;
	    		$lat_p2 = $lat+0.6;
	    
	    		$lon_m2 = $lon-0.6;
	    		$lon_p2 = $lon+0.6;
    		}
    		
    		// LIST USERS NEARBY
            if(!isset($_GET["otype"])){
    		  $sql = "SELECT * FROM users WHERE username NOT IN ('$myfriends') AND username NOT IN ('$foffi') AND lat BETWEEN ? AND ? AND lon BETWEEN ? AND ? AND username != ? AND activated = ? $limit";
            }else{
                $sql = "SELECT * FROM users WHERE username NOT IN ('$myfriends') AND lat BETWEEN ? AND ? AND lon BETWEEN ? AND ? AND username != ? AND activated = ? $limit";
            }
    		$stmt = $conn->prepare($sql);
    		$stmt->bind_param("ssssss",$lat_m2,$lat_p2,$lon_m2,$lon_p2,$log_username,$one);
    		$stmt->execute();
    		$res = $stmt->get_result();
    		while($row = $res->fetch_assoc()){
    			$avatar = $row["avatar"];
    			$country = $row["country"];
    			$gender = $row["gender"];
    			$uname = $row["username"];
    			$unameori = urlencode($uname);
    			$unamerq = $uname;
    			array_push($geous,$unamerq);
    			if(strlen($uname) > 20){
    			    $uname = mb_substr($uname, 0, 16, "utf-8");
    			    $uname .= " ...";
    			}
    			if(strlen($country) > 20){
    			    $country = mb_substr($country, 0, 16, "utf-8");
    			    $country .= " ...";
    			}
    			$online = $row["online"];
    			if($online == "yes"){
                    $online = "border: 2px solid #00a1ff";
                }else{
                    $online = "border: 2px solid #999";
                }

    			$sex = "Female";
    			if($gender == "m"){
    				$sex = "Male";
    			}
    
    			$sql = "SELECT accepted FROM friends WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1";
    			$stmt = $conn->prepare($sql);
    			$stmt->bind_param("ssss",$log_username,$unamerq,$unamerq,$log_username);
    			$stmt->execute();
    			$stmt->bind_result($zeroone);
    			$stmt->fetch();
    			$stmt->close();
    			if($zeroone == "0"){
                    $friend_btn = "<p style='color: #999; margin-right: 5px;'>Friend request sent</p>";
                }else if($zeroone == NULL || $zeroone == ""){
                    $friend_btn = '<span id="friendBtn_'.$unamerq.'"><button onclick="friendToggle(\'friend\',\''.$unamerq.'\',\'friendBtn_'.$unamerq.'\')" class="main_btn_fill" style="border: 0; border-radius: 20px; padding: 7px; margin-top: 5px;">Request as friend</button></span>';
                }
    
                if($avatar == NULL || $avatar == ""){
                    $pcurl = '/images/avdef.png';
                }else{
                    $pcurl = '/user/'.$unamerq.'/'.$avatar;
                }
    
                $moMoFriends .= '<div><a href="/user/'.$unameori.'/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 70px; height: 70px; float: right; display: inline-block; border-radius: 50%; '.$online.'" class="lazy-bg"></div></a><p><a href="/user/'.$unameori.'/">'.$uname.'</a></p><p>'.$country.'</p>'.$friend_btn.'</div>';
                if(!isset($_GET["otype"])){ $countSugg++; }
    		}
    	}
        if(isset($_GET["otype"]) && $moMoFriends == ""){
            echo "<p style='color: #999; text-align: center;'>Sorry, there are no friend suggestions in this category</p>";
            exit();
        }else if(isset($_GET["otype"]) && $moMoFriends != ""){
            echo $moMoFriends;
            exit();
        }
	}
	
	$geos = join("','",$geous);
	$page_rows = $page_rows - $countfs;
    $limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
	$eaketto = array();
	// SELECT USER'S CITY
	$editarray = array();
	if(($otype == "all" && !isset($_GET["otype"])) || (isset($_GET["otype"]) && ($_GET["otype"] == "suggf_5" || $_GET["otype"] == "suggf_6" || $_GET["otype"] == "suggf_7"))){
        if(isset($_GET["otype"])){ $otype = mysqli_real_escape_string($conn, $_GET["otype"]); }

    	$sql = "SELECT state, city FROM edit WHERE username = ? LIMIT 1";
    	$stmt = $conn->prepare($sql);
    	$stmt->bind_param("s",$log_username);
    	$stmt->execute();
    	$stmt->bind_result($province, $city);
    	$stmt->fetch();
    	$stmt->close();

    	$sql = "SELECT country FROM users WHERE username = ? LIMIT 1";
    	$stmt = $conn->prepare($sql);
    	$stmt->bind_param("s",$log_username);
    	$stmt->execute();
    	$stmt->bind_result($logCountry);
    	$stmt->fetch();
    	$stmt->close();

    	if($moMoFriends == "" || $countfs < 100 || isset($_GET["otype"])){
    	    if($otype == "suggf_5" && $city != ""){
    			$sql = "SELECT u.username, u.country, u.avatar, u.gender FROM users AS u LEFT JOIN edit AS e ON u.username = e.username WHERE e.city = ? AND u.username != ? AND u.username NOT IN ('$myfriends') AND u.username NOT IN ('$foffi') AND u.username NOT IN ('$geous') AND activated = ? $limit";
	    	}else if($otype == "suggf_6" && $province != ""){
	    		$sql = "SELECT u.username, u.country, u.avatar, u.gender FROM users AS u LEFT JOIN edit AS e ON u.username = e.username WHERE e.state = ? AND u.username != ? AND u.username NOT IN ('$myfriends') AND u.username NOT IN ('$foffi') AND u.username NOT IN ('$geous') AND activated = ? $limit";
	    	}else if(!isset($_GET["otype"])){
	    		$sql = "SELECT username, country, avatar, gender FROM users WHERE country = ? AND username != ? AND username NOT IN ('$myfriends') AND username NOT IN ('$foffi') AND username NOT IN ('$geous') AND activated = ? $limit";
    		}else if(isset($_GET["otype"])){
                $sql = "SELECT username, country, avatar, gender FROM users WHERE country = ? AND username != ?  AND username NOT IN ('$myfriends') AND activated = ? $limit";
            }
    	    $stmt = $conn->prepare($sql);
    	    if(($otype == "suggf_5" || $otype == "all") && $city != ""){
    	    	$stmt->bind_param("sss",$city,$log_username,$one);
    	    }else if(($otype == "suggf_6" || $otype == "all") && $province != ""){
    	    	$stmt->bind_param("sss",$province,$log_username,$one);
    	    }else{
    	    	$stmt->bind_param("sss",$logCountry,$log_username,$one);
    	    }
    	    $stmt->execute();
    	    $res = $stmt->get_result();

    	    while($row = $res->fetch_assoc()){
    	        $countfs++;
    	        $avatar = $row["avatar"];
    			$country = $row["country"];
    			$gender = $row["gender"];
    			$uname = $row["username"];
    			$unameori = urlencode($uname);
    			$unamerq = $uname;
    			array_push($eaketto,$unamerq);
    			$online = $row["online"];
    			if(strlen($uname) > 20){
    			    $uname = mb_substr($uname, 0, 16, "utf-8");
    			    $uname .= " ...";
    			}
    			if(strlen($country) > 20){
    			    $country = mb_substr($country, 0, 16, "utf-8");
    			    $country .= " ...";
    			}
    			if($online == "yes"){
                    $online = "border: 2px solid #00a1ff";
                }else{
                    $online = "border: 2px solid #999";
                }
    
    			$sql = "SELECT accepted FROM friends WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1";
    			$stmt = $conn->prepare($sql);
    			$stmt->bind_param("ssss",$log_username,$unamerq,$unamerq,$log_username);
    			$stmt->execute();
    			$stmt->bind_result($zeroone);
    			$stmt->fetch();
    			$stmt->close();
    			if($zeroone == "0"){
                    $friend_btn = "<p style='color: #999; margin-right: 5px;'>Friend request sent</p>";
                }else if($zeroone == NULL || $zeroone == ""){
                    $friend_btn = '<span id="friendBtn_'.$unamerq.'"><button onclick="friendToggle(\'friend\',\''.$unamerq.'\',\'friendBtn_'.$unamerq.'\')" class="main_btn_fill" style="border: 0; border-radius: 20px; padding: 7px; margin-top: 5px;">Request as friend</button></span>';
                }
    
                if($avatar == NULL || $avatar == ""){
                    $pcurl = '/images/avdef.png';
                }else{
                    $pcurl = '/user/'.$unamerq.'/'.$avatar;
                }
    
                $moMoFriends .= '<div><a href="/user/'.$unameori.'/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 70px; height: 70px; float: right; display: inline-block; border-radius: 50%; '.$online.'" class="lazy-bg"></div></a><p><a href="/user/'.$unameori.'/">'.$uname.'</a></p><p>'.$country.'</p>'.$friend_btn.'</div>';
                if(!isset($_GET["otype"])){ $countSugg++; }
    	    }
    	}
        if(isset($_GET["otype"]) && $moMoFriends == ""){
            echo "<p style='color: #999; text-align: center;'>Sorry, there are no friend suggestions in this category</p>";
            exit();
        }else if(isset($_GET["otype"]) && $moMoFriends != ""){
            echo $moMoFriends;
            exit();
        }
	}
	
	$page_rows = $page_rows - $countfs;
    $limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
	
	$yearsarr = array();
	if(($otype == "all" && !isset($_GET["otype"])) || (isset($_GET["otype"]) && ($_GET["otype"] == "suggf_8" || $_GET["otype"] == "suggf_9" || $_GET["otype"] == "suggf_10" || $_GET["otype"] == "suggf_11"))){
    	if($moMoFriends == "" || $countfs < 100 || isset($_GET["otype"])){
            if(isset($_GET["otype"])){ $otype = mysqli_real_escape_string($conn, $_GET["otype"]); }
    	    $sql = "SELECT bday FROM users WHERE username = ? LIMIT 1";
    	    $stmt = $conn->prepare($sql);
    	    $stmt->bind_param("s",$log_username);
    	    $stmt->execute();
    	    $stmt->bind_result($log_b);
    	    $stmt->fetch();
    	    $stmt->close();

    	    $log_b = mb_substr($log_b, 0, 4, "utf-8");
    	    if($otype == "suggf_8"){
    	    	$logbp2 = $log_b+2;
    	    	$logbm2 = $log_b-2;
    	    }else if($otype == "suggf_9"){
    	    	$logbp2 = $log_b+5;
    	    	$logbm2 = $log_b-5;
    	    }else if($otype == "suggf_10"){
    	    	$logbp2 = $log_b+10;
    	    	$logbm2 = $log_b-10;
    	    }else{
    	    	$logbp2 = $log_b+20;
    	    	$logbm2 = $log_b-20;
    	    }

    	    $logbp2 = $logbp2."-"."01-01";
    	    $logbm2 = $logbm2."-"."01-01";
    	    
            if(!isset($_GET["otype"])){
    	       $sql = "SELECT * FROM users WHERE (bday BETWEEN ? AND ?) AND username NOT IN ('$myfriends') AND username NOT IN ('$foffi') AND username NOT IN ('$geous') AND username NOT IN('$eaketto') AND username != ? AND activated = ? $limit";
            }else{
                $sql = "SELECT * FROM users WHERE (bday BETWEEN ? AND ?) AND username NOT IN ('$myfriends') AND username != ? AND activated = ? $limit";
            }
    	    $stmt = $conn->prepare($sql);
    	    $stmt->bind_param("ssss",$logbm2,$logbp2,$log_username,$one);
    	    $stmt->execute();
    	    $res = $stmt->get_result();
    	    while($row = $res->fetch_assoc()){
    	        $countfs++;
    	        array_push($yearsarr, $row["username"]);
    	        $avatar = $row["avatar"];
    			$country = $row["country"];
    			$gender = $row["gender"];
    			$uname = $row["username"];
    			$unameori = urlencode($uname);
    			$unamerq = $uname;
    			$online = $row["online"];
    			if(strlen($uname) > 20){
    			    $uname = mb_substr($uname, 0, 16, "utf-8");
    			    $uname .= " ...";
    			}
    			if(strlen($country) > 20){
    			    $country = mb_substr($country, 0, 16, "utf-8");
    			    $country .= " ...";
    			}
    			if($online == "yes"){
                    $online = "border: 2px solid #00a1ff";
                }else{
                    $online = "border: 2px solid #999";
                }
    
    			$sql = "SELECT accepted FROM friends WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1";
    			$stmt = $conn->prepare($sql);
    			$stmt->bind_param("ssss",$log_username,$unamerq,$unamerq,$log_username);
    			$stmt->execute();
    			$stmt->bind_result($zeroone);
    			$stmt->fetch();
    			$stmt->close();
    			if($zeroone == "0"){
                    $friend_btn = "<p style='color: #999; margin-right: 5px;'>Friend request sent</p>";
                }else if($zeroone == NULL || $zeroone == ""){
                    $friend_btn = '<span id="friendBtn_'.$unamerq.'"><button onclick="friendToggle(\'friend\',\''.$unamerq.'\',\'friendBtn_'.$unamerq.'\')" class="main_btn_fill" style="border: 0; border-radius: 20px; padding: 7px; margin-top: 5px;">Request as friend</button></span>';
                }
    
                if($avatar == NULL || $avatar == ""){
                    $pcurl = '/images/avdef.png';
                }else{
                    $pcurl = '/user/'.$unamerq.'/'.$avatar;
                }
    
                $moMoFriends .= '<div><a href="/user/'.$unameori.'/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 70px; height: 70px; float: right; display: inline-block; border-radius: 50%; '.$online.'" class="lazy-bg"></div></a><p><a href="/user/'.$unameori.'/">'.$uname.'</a></p><p>'.$country.'</p>'.$friend_btn.'</div>';
                if(!isset($_GET["otype"])){ $countSugg++; }
    	    }
            if(isset($_GET["otype"]) && $moMoFriends == ""){
                echo "<p style='color: #999; text-align: center;'>Sorry, there are no friend suggestions in this category</p>";
                exit();
            }else if(isset($_GET["otype"]) && $moMoFriends != ""){
                echo $moMoFriends;
                exit();
            }
    	}
	}

	$yearsarr = join("','",$yearsarr);

    // Leave it for future purposes
	if($otype == "all"){
    	if($moMoFriends == "" || $countfs < 100){
    	    $sql = "SELECT * FROM users WHERE activated = ? AND username NOT IN ('$myfriends') AND username NOT IN ('$foffi') AND username NOT IN ('$geous') AND username NOT IN('$eaketto') AND username NOT IN('$yearsarr') AND username != ? ORDER BY RAND() $limit";
    	    $stmt = $conn->prepare($sql);
    	    $stmt->bind_param("ss",$one,$log_username);
    	    $stmt->execute();
    	    $res = $stmt->get_result();
    	    while($row = $res->fetch_assoc()){
    	        $countfs++;
    	        $avatar = $row["avatar"];
    			$country = $row["country"];
    			$gender = $row["gender"];
    			$uname = $row["username"];
    			$unameori = urlencode($uname);
    			$unamerq = $uname;
    			$online = $row["online"];
    			if(strlen($uname) > 20){
    			    $uname = mb_substr($uname, 0, 16, "utf-8");
    			    $uname .= " ...";
    			}
    			if(strlen($country) > 20){
    			    $country = mb_substr($country, 0, 16, "utf-8");
    			    $country .= " ...";
    			}
    			if($online == "yes"){
    				$online = "border: 2px solid #00a1ff";
    			}else{
    				$online = "border: 2px solid #999";
    			}
    
    			$sql = "SELECT accepted FROM friends WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1";
    			$stmt = $conn->prepare($sql);
    			$stmt->bind_param("ssss",$log_username,$unamerq,$unamerq,$log_username);
    			$stmt->execute();
    			$stmt->bind_result($zeroone);
    			$stmt->fetch();
    			$stmt->close();
    			if($zeroone == "0"){
    			    $friend_btn = "<p style='color: #999; margin-right: 5px;'>Friend request sent</p>";
    			}else if($zeroone == NULL || $zeroone == ""){
    			    $friend_btn = '<span id="friendBtn_'.$unamerq.'"><button onclick="friendToggle(\'friend\',\''.$unamerq.'\',\'friendBtn_'.$unamerq.'\')" class="main_btn_fill" style="border: 0; border-radius: 20px; padding: 7px; margin-top: 5px;">Request as friend</button></span>';
    			}
    
    			if($avatar == NULL || $avatar == ""){
    				$pcurl = '/images/avdef.png';
    			}else{
    				$pcurl = '/user/'.$unamerq.'/'.$avatar;
    			}
    
    			$moMoFriends .= '<div><a href="/user/'.$unameori.'/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 70px; height: 70px; float: right; display: inline-block; border-radius: 50%; '.$online.'" class="lazy-bg"></div></a><p><a href="/user/'.$unameori.'/">'.$uname.'</a></p><p>'.$country.'</p>'.$friend_btn.'</div>';
                if(!isset($_GET["otype"])){ $countSugg++; }
    	    }
    	}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" href="/style/style.css">
	<title><?php echo $u; ?> - Friend Suggestion</title>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
	<script src="/js/mbc.js"></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<script text="javascript">
	    function friendToggle(variable_0, variable_1, variable_2) {
            _(variable_2)["innerHTML"] = "<img src=\"/images/rolling.gif\" width=\"30\" height=\"30\">";
            var variable_3 = ajaxObj("POST", "/php_parsers/friend_system.php");
            variable_3["onreadystatechange"] = function() {
                1 == ajaxReturn(variable_3) && ("friend_request_sent" == variable_3["responseText"] ? _(variable_2)["innerHTML"] = "<p style='color: #999; margin-right: 5px;'>Friend request sent</p>" : "unfriend_ok" == variable_3["responseText"] ? _(variable_2)["innerHTML"] = "<button onclick=\"friendToggle('friend','<?php echo $u; ?>','friendBtn_" + variable_1 + "')\">Request As Friend</button>" : (alert(variable_3["responseText"]), _(variable_2)["innerHTML"] = "Try again later"))
            }, variable_3["send"]("type=" + variable_0 + "&user=" + variable_1)
        }
	</script>
</head>
<body style="overflow-x: hidden;">
	<?php require_once 'template_pageTop.php'; ?>
    <div id="pageMiddle_2">
        <div id="data_holder">
            <div>
                <div><span id="countFsug"><?php echo $countSugg; ?></span> friend suggestions</div>
            </div>
        </div>
		<button id="sort" class="main_btn_fill">Filter suggestions</button>
    	 <div id="sortTypes">
            <div class="gridDiv">
                <p class="mainHeading">Similar age</p>
                <div id="suggf_8">+/- 1-2 years</div>
                <div id="suggf_9">+/- 3-5 years</div>
                <div id="suggf_10">+/- 6-10 years</div>
                <div id="suggf_11">+/- 11-20 years</div>
            </div>

            <div class="gridDiv">
                <p class="mainHeading">Users nearby</p>
                <div id="suggf_0">0-5 km (0-3.1 miles) area</div>
                <div id="suggf_1">5-10 km (3.1-6.2 miles) area</div>
                <div id="suggf_2">10-50 km (6.2-31 miles) area</div>
                <div id="suggf_3">50-100 km (31-62.1 miles) area</div>
            </div>

            <div class="gridDiv">
                <p class="mainHeading">Close relationships</p>
                <div id="suggf_4">friends of friends</div>
            </div>
    
            <div class="gridDiv">
                <p class="mainHeading">Geolocation</p>
                <div id="suggf_5">from the same city</div>
                <div id="suggf_6">from the same province</div>
                <div id="suggf_7">from the same country</div>
            </div>

            <div class="clear"></div>
        </div>
    	<div class="clear"></div>
        <hr class="dim">
		<div id="momofdif" class="flexibleSol">
            <?php if($moMoFriends != ""){
                echo $moMoFriends;
            }else{
                echo "<p style='color: #999; text-align: center;'>Oops... we could not list you any friend suggestions</p>";
            } ?>
        </div>
		<div class="clear"></div>
		<div id="paginationCtrls" style="width: 100px; height: 10px; margin: 0 auto;"><?php echo $paginationCtrls; ?></div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
        function getCookie(e){for(var t=e+"=",s=decodeURIComponent(document.cookie).split(";"),n=0;n<s.length;n++){for(var r=s[n];" "==r.charAt(0);)r=r.substring(1);if(0==r.indexOf(t))return r.substring(t.length,r.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],s=document.createElement("link");s.id=e,s.rel="stylesheet",s.type="text/css",s.href="/style/dark_style.css",s.media="all",t.appendChild(s)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();
        function _(element){
			return document.getElementById(element);
		}

        $( "#sort" ).click(function() {
          $( "#sortTypes" ).slideToggle( 200, function() {
            // Animation complete.
          });
        });
		
        function addListener(onw, w){
            _(onw).addEventListener("click", function(){
                _("momofdif").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
                filterArts(w);
            });
        }

        for(let i = 0; i < 12; i++){
            addListener("suggf_" + i, "suggf_" + i);
        }

        function filterArts(otype){
            changeStyle(otype);
            let req = new XMLHttpRequest();
            req.open("GET", "/more_friends.php?otype=" + otype, false);
            req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            req.onreadystatechange = function(){
                if(req.readyState == 4 && req.status == 200){
                    _("momofdif").innerHTML = req.responseText;
                }
            }
            req.send();
        }

        function changeStyle(otype){
            _(otype).style.color = "red";
            for(let i = 0; i < 12; i++){
                if("suggf_" + i != otype) _("suggf_" + i).style.color = "black";
            }
        }

        changeStyle("suggf_0");
	</script>
</body>
</html>