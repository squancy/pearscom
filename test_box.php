<?php
    require_once 'php_includes/check_login_statues.php';
    require_once 'php_includes/conn.php';
    require_once 'timeelapsedstring.php';
    require_once 'phpmobc.php';
    require_once 'safe_encrypt.php';
    require_once 'durc.php';
    require_once 'headers.php';
    require_once 'ccov.php';
    require_once 'elist.php';

    $ismobile = mobc();
    $newsfeed = "";
    $u = $log_username;
    $one = "1";
    $zero = "0";
    $a = "a";
    $b = "b";
    $c = "c";
    $all_friends = array();
    $newsfeed = array();
    function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 3959) {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);
        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
        $angle = atan2(sqrt($a), $b);
        $number = $angle * $earthRadius;
        $number = round($number);
        return $number;
    }
    // Select user's lat and lon
    $sql = "SELECT lat, lon FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $stmt->bind_result($lat, $lon);
    $stmt->fetch();
    $stmt->close();
    $lat_m2 = $lat - 0.7;
    $lat_p2 = $lat + 0.7;
    $lon_m2 = $lon - 0.7;
    $lon_p2 = $lon + 0.7;
    // Select the member from the users table
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $u, $one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    // Now make sure the user exists in the table
    if ($numrows < 1) {
        header('location: /usernotexist');
        exit();
    }

    // Old system only allows to reply on friends' posts/replies

        // $isFriend = false;
        /*$ownerBlockViewer = false;
        $viewerBlockOwner = false;
        if ($u != $log_username && $user_ok == true) {
            $friend_check = "SELECT id FROM friends WHERE user1=? AND user2=? AND accepted=? OR user1=? AND user2=? AND accepted=? LIMIT 1";
            $stmt = $conn->prepare($friend_check);
            $stmt->bind_param("ssssss", $log_username, $u, $one, $u, $log_username, $one);
            $stmt->execute();
            $stmt->store_result();
            $stmt->fetch();
            $numrows = $stmt->num_rows;
            if ($numrows > 0) {
                $isFriend = true;
            }
            $stmt->close();
        }*/
    $isFriend = true;
    // Start getting data for the news feed
    // Select friends
    $sql = "SELECT user1, user2 FROM friends WHERE (user2=? OR user1=?) AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $u, $u, $one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row["user1"] != $u) {
            array_push($all_friends, $row["user1"]);
        }
        if ($row["user2"] != $u) {
            array_push($all_friends, $row["user2"]);
        }
    }
    $stmt->close();
    // Selects followings
    $curar = join("','", $all_friends);
    $sql = "SELECT following FROM follow WHERE follower = ? AND following NOT IN('$curar')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row["following"] != $u) {
            array_push($all_friends, $row["following"]);
        }
    }
    $stmt->close();
    $friendsCSV = join("','", $all_friends);
    $sql = "SELECT COUNT(id)
        			FROM status
        			WHERE author IN ('$friendsCSV') AND (type=? OR type=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $a, $c);
    $stmt->execute();
    $stmt->bind_result($feedrcnt);
    $stmt->fetch();
    $stmt->close();
    $val = "";
    $lmit = "";

    // Check if there is any available feed from users nearby
    $sql = "SELECT COUNT(id) FROM users WHERE lat BETWEEN ? AND ? AND lon BETWEEN ? AND ? AND username != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss",$lat_m2,$lat_p2,$lon_m2,$lon_p2,$log_username);
    $stmt->execute();
    $stmt->bind_result($cnt_near);
    $stmt->fetch();
    $stmt->close();

     // Select user's country
    $sql = "SELECT country FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($ucountry);
    $stmt->fetch();
    $stmt->close();

    // Select friends' of friends
    $loggedUserFriends = array();
    $sql = "SELECT user1, user2 FROM friends WHERE (user2=? OR user1=?) AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$u,$u,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row["user1"] != $u){array_push($loggedUserFriends, $row["user1"]);}
        if ($row["user2"] != $u){array_push($loggedUserFriends, $row["user2"]);}
    }
    $stmt->close();
    $loggedString = join("','",$loggedUserFriends);

    $loggedFoF = array(); // AND user1 NOT IN('$curar') AND user2 NOT IN('$curar')
    $sql = "SELECT user2, user1 FROM friends WHERE (user1 IN('$loggedString') OR user2 IN('$loggedString')) AND accepted = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if(!in_array($row["user1"], $curar) && !in_array($row["user1"], $loggedUserFriends) && $row["user1"] != $u){array_push($loggedFoF, $row["user1"]);}
        if(!in_array($row["user2"], $curar) && !in_array($row["user2"], $loggedUserFriends) && $row["user2"] != $u){array_push($loggedFoF, $row["user2"]);}
    }
    $stmt->close();
    $loggedFoF = join("','",$loggedFoF);
    // Choose most liked status posts with no friends
    $idstat = "";
    if($cnt_near < 1){
        $stat_stat = array();
        $sql = "SELECT s.status, COUNT(*) AS x FROM status_likes AS s LEFT JOIN users AS u ON u.username = s.username WHERE u.country = ? GROUP BY s.status ORDER BY x DESC LIMIT 65000";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$ucountry);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
            array_push($stat_stat, $row["status"]);
        }
        $stmt->close();
        $idstat = join("','",$stat_stat);
    }

    $statuslist = "<p>Recommended status posts from your friends & followings</p>";
	        // Select posts from friends and followings
	        if($friendsCSV != ""){
	        	$sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
	        			FROM status AS s
	        			LEFT JOIN users AS u ON u.username = s.author
	        			WHERE s.author IN ('$friendsCSV') OR s.author IN('$loggedFoF') AND (s.type=? OR s.type=?) AND s.author != ? LIMIT 6,65000";
	        }else if($cnt_near > 0){
	        	$sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
	        			FROM status AS s
	        			LEFT JOIN users AS u ON u.username = s.author
	        			WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type=?) AND s.author != ? LIMIT 6,65000";
	        }else if($idstat != ""){
                $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                        FROM status AS s
                        LEFT JOIN users AS u ON u.username = s.author
                        WHERE s.id IN ('$idstat') AND s.author != ? LIMIT 65000";
            }else{
                $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                        FROM status AS s
                        LEFT JOIN users AS u ON u.username = s.author WHERE s.author != ? ORDER BY RAND() LIMIT 65000";
            }
	        $stmt = $conn->prepare($sql);
	        if($friendsCSV != ""){
	        	$stmt->bind_param("sss",$a,$c, $log_username);
	        }else if($cnt_near > 0){
	        	$stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $log_username);
	        }else{
	            $stmt->bind_param("s",$log_username);
	        }
	        $stmt->execute();
	        $result = $stmt->get_result();
	        if ($result->num_rows > 0) {
	            while ($row = $result->fetch_assoc()) {
	            	$statuslist = "";
	                $statusid = $row["id"];
                $account_name = $row["account_name"];
                $author = $row["author"];
                $postdate_ = $row["postdate"];
                $postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
                $data = $row["data"];
                $avatar = $row["avatar"];
                if ($avatar == NULL) {
                    $pcurl = '/images/avdef.png';
                } else {
                    $pcurl = '/user/' . $author . '/' . $avatar;
                }
                $flat = $row["lat"];
                $flon = $row["lon"];
                $ison = $row["online"];
                $fuco = $row["country"];
                $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
                $isonimg = '';
                if ($ison == "yes") {
                    $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
                } else {
                    $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
                }
                $funames = $author;
                if (strlen($funames) > 20) {
                    $funames = mb_substr($funames, 0, 16, "utf-8");
                    $funames.= " ...";
                }
                if (strlen($fuco) > 20) {
                    $fuco = mb_substr($fuco, 0, 16, "utf-8");
                    $fuco.= " ...";
                }
                $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $author, $author, $one);
                $stmt->execute();
                $stmt->bind_result($numoffs);
                $stmt->fetch();
                $stmt->close();
                $user_image_status = '<a href="/user/' . $author . '/"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; ' . $mgin . ' background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob"></div><div class="infostdiv"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left;" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
                $agoform = time_elapsed_string($postdate_);
                $data_old = $row["data"];
                $data_old = nl2br($data_old);
                $data_old = str_replace("&amp;", "&", $data_old);
                $data_old = stripslashes($data_old);
                $pos = strpos($data_old, '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
                $isex = false;
                $sec_data = "";
                $first_data = "";
                if (strpos($data_old, '<img src="/permUploads/') !== false) {
                    $split = explode('<img src="/permUploads/', $data_old);
                    clearstatcache();
                    $sec_data = '<img src="/permUploads/' . $split[1];
                    $first_data = $split[0];
                    $img = str_replace('"', '', $split[1]); // remove double quotes
                    $img = str_replace('/>', '', $img); // remove img end tag
                    $img = str_replace(' ', '', $img); // remove spaces
                    $img = str_replace('<br>', '', $img); // remove spaces
                    $img = trim($img);
                    $fn = "permUploads/" . $img; // file name with dynamic variable in it
                    if (file_exists($fn)) {
                        $isex = true;
                    }
                }
                if (strlen($data) > 1000) {
                    if ($pos === false && $isex == false) {
                        $data = mb_substr($data, 0, 1000, "utf-8");
                        $data.= " ...";
                        $data.= '&nbsp;<a id="toggle_feed_' . $statusid . '" onclick="opentext(\'' . $statusid . '\',\'feed\')">See More</a>';
                        $data_old = '<div id="lessmore_feed_' . $statusid . '" class="lmml"><p id="status_text">' . $data_old . '&nbsp;<a id="toggle_feed_' . $statusid . '" onclick="opentext(\'' . $statusid . '\',\'feed\')">See Less</a></p></div>';
                    } else {
                        $data_old = "";
                    }
                } else {
                    $data_old = "";
                }
                $data = nl2br($data);
                $data = str_replace("&amp;", "&", $data);
                $data = stripslashes($data);
                if (strpos($data, '<img src=\"permUploads/"') === true) {
                    $data.= '<br>';
                }
                // Add share button
                $shareButton = "";
                if ($log_username != "" && $author != $log_username && $account_name != $log_username) {
                    $shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus(\'' . $statusid . '\');" id="shareBlink">';
                }
                $isLike = false;
                if ($user_ok == true) {
                    $like_check = "SELECT id FROM status_likes WHERE username=? AND status=? LIMIT 1";
                    $stmt = $conn->prepare($like_check);
                    $stmt->bind_param("si", $log_username, $statusid);
                    $stmt->execute();
                    $stmt->store_result();
                    $stmt->fetch();
                    $numrows = $stmt->num_rows;
                    if ($numrows > 0) {
                        $isLike = true;
                    }
                }
                $stmt->close();
                // Add status like button
                $likeButton = "";
                $likeText = "";
                if ($isLike == true) {
                    $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\'' . $statusid . '\',\'likeBtn_feed_' . $statusid . '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
                    $likeText = '<span style="vertical-align: middle;">Dislike</span>';

                } else {
                    $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\'' . $statusid . '\',\'likeBtn_feed_' . $statusid . '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
                    $likeText = '<span style="vertical-align: middle;">Like</span>';

                }
                // GATHER UP ANY STATUS REPLIES
                $status_replies = "";
                // all 1 line
                $sql2 = "SELECT s.*, u.avatar, u.country, u.lat, u.lon
        				 	FROM status AS s
        				 	LEFT JOIN users AS u ON u.username = s.author
        				 	WHERE s.osid = ?
        				 	AND s.type=?
        				 	ORDER BY s.postdate DESC";
                $stmt = $conn->prepare($sql2);
                $stmt->bind_param("is", $statusid, $b);
                $stmt->execute();
                $result2 = $stmt->get_result();
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $statusreplyid = $row2["id"];
                        $replyauthor = $row2["author"];
                        $replydata = $row2["data"];
                        $replydata = nl2br($replydata);
                        $replypostdate_ = $row2["postdate"];
                        $replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
                        $avatar2 = $row2["avatar"];
                        $replydata = str_replace("&amp;", "&", $replydata);
                        $replydata = stripslashes($replydata);
                        if ($avatar2 == NULL) {
                            $pcurl = '/images/avdef.png';
                        } else {
                            $pcurl = 'user/' . $replyauthor . '/' . $avatar2;
                        }
                        $flat = $row["lat"];
                        $flon = $row["lon"];
                        $ison = $row["online"];
                        $fuco = $row["country"];
                        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
                        $isonimg = '';
                        if ($ison == "yes") {
                            $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
                        } else {
                            $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
                        }
                        $funames = $replyauthor;
                        if (strlen($funames) > 20) {
                            $funames = mb_substr($funames, 0, 16, "utf-8");
                            $funames.= " ...";
                        }
                        if (strlen($fuco) > 20) {
                            $fuco = mb_substr($fuco, 0, 16, "utf-8");
                            $fuco.= " ...";
                        }
                        $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sss", $replyauthor, $replyauthor, $one);
                        $stmt->execute();
                        $stmt->bind_result($numoffs);
                        $stmt->fetch();
                        $stmt->close();
                        $user_image_reply = '<a href="/user/' . urlencode($replyauthor) . '/"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob"></div><div class="infotsrdiv"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
                        $data_old_reply = $row2["data"];
                        $data_old_reply = nl2br($data_old_reply);
                        $data_old_reply = str_replace("&amp;", "&", $data_old_reply);
                        $data_old_reply = stripslashes($data_old_reply);
                        $isex = false;
                        $sec_data = "";
                        $first_data = "";
                        if (strpos($data_old_reply, '<img src="/permUploads/') !== false) {
                            $split = explode('<img src="/permUploads/', $data_old_reply);
                            clearstatcache();
                            $sec_data = '<img src="/permUploads/' . $split[1];
                            $first_data = $split[0];
                            $img = str_replace('"', '', $split[1]); // remove double quotes
                            $img = str_replace('/>', '', $img); // remove img end tag
                            $img = str_replace(' ', '', $img); // remove spaces
                            $img = str_replace('<br>', '', $img); // remove spaces
                            $img = trim($img);
                            $fn = "permUploads/" . $img; // file name with dynamic variable in it
                            if (file_exists($fn)) {
                                $isex = true;
                            }
                        }
                        if (strlen($replydata) > 1000) {
                            if ($isex == false) {
                                $replydata = mb_substr($replydata, 0, 1000, "utf-8");
                                $replydata.= " ...";
                                $replydata.= '&nbsp;<a id="toggle_feed_r_' . $statusreplyid . '" onclick="opentext(\'' . $statusreplyid . '\',\'feed_r\')">See More</a>';
                                $data_old_reply = '<div id="lessmore_feed_r_' . $statusreplyid . '" class="lmml"><p id="status_text">' . $data_old_reply . '&nbsp;<a id="toggle_feed_r_' . $statusreplyid . '" onclick="opentext(\'' . $statusreplyid . '\',\'feed_r\')">See Less</a></p></div>';
                            } else {
                                $data_old_reply = "";
                            }
                        } else {
                            $data_old_reply = "";
                        }
                        $replydata = nl2br($replydata);
                        $replydata = str_replace("&amp;", "&", $replydata);
                        $replydata = stripslashes($replydata);
                        $replyDeleteButton = '';
                        if ($replyauthor == $log_username || $account_name == $log_username) {
                            $replyDeleteButton = '<span id="srdb_' . $statusreplyid . '"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\'' . $statusreplyid . '\',\'reply_feed_' . $statusreplyid . '\',\'feed\',\'feed\');" title="Delete Comment">X</button ></span>';
                        }
                        $agoformrply = time_elapsed_string($replypostdate_);
                        //$stmt->close();
                        $isLike_reply = false;
                        if ($user_ok == true) {
                            $like_check_reply = "SELECT id FROM reply_likes WHERE username=? AND reply=? LIMIT 1";
                            $stmt = $conn->prepare($like_check_reply);
                            $stmt->bind_param("si", $log_username, $statusreplyid);
                            $stmt->execute();
                            $stmt->store_result();
                            $stmt->fetch();
                            $numrows = $stmt->num_rows;
                            if ($numrows > 0) {
                                $isLike_reply = true;
                            }
                        }
                        $stmt->close();
                        // Add reply like button
                        $likeButton_reply = "";
                        $likeText_reply = "";
                        if ($isLike_reply == true) {
                            $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\'' . $statusreplyid . '\',\'likeBtn_reply_feed_' . $statusreplyid . '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
                            $likeText_reply = '<span style="vertical-align: middle;">Dislike</span>';
                        } else {
                            $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\'' . $statusreplyid . '\',\'likeBtn_reply_feed_' . $statusreplyid . '\')"><img src="/images/nf.png" width="18" height="18" title="Like" class="like_unlike"></a>';
                            $likeText_reply = '<span style="vertical-align: middle;">Like</span>';
                        }
                        // Count reply likes
                        $sql = "SELECT COUNT(id) FROM reply_likes WHERE reply = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $statusreplyid);
                        $stmt->execute();
                        $stmt->bind_result($rpycount);
                        $stmt->fetch();
                        $stmt->close();
                        $rpycl = '' . $rpycount;
                        $status_replies.= '
        					<div id="reply_feed_' . $statusreplyid . '" class="reply_boxes">
                                <div>' . $replyDeleteButton . '
                                <p id="float">
                                    <b class="sreply">Reply: </b>
                                    <b class="rdate">
                                        <span class="tooLong">' . $replypostdate . '</span> (' . $agoformrply . ' ago)
                                    </b>
                                </p>' . $user_image_reply . '
                                <p id="reply_text">
                                    <b class="sdata" id="hide_feed_r_' . $statusreplyid . '">' . $replydata . '' . $data_old_reply . '</b>
                                </p>

                                <hr class="dim">

                                <span id="likeBtn_reply_feed_' . $statusreplyid . '" class="likeBtn">
                                    ' . $likeButton_reply . '
                                    <span style="vertical-align: middle;">'.$likeText_reply.'</span>
                                </span>
                                <div style="float: left; padding: 0px 10px 0px 10px;">
                                    <b class="ispan" id="ipanr_' . $statusreplyid . '">' . $rpycl . ' likes</b>
                                </div>
                                <div class="clear"></div>
                                </div>
                            </div>';
                    }
                }
	                // Count likes
	                $sql = "SELECT COUNT(id) FROM status_likes WHERE status = ?";
	                $stmt = $conn->prepare($sql);
	                $stmt->bind_param("i", $statusid);
	                $stmt->execute();
	                $stmt->bind_result($count);
	                $stmt->fetch();
	                $stmt->close();
	                $cl = '' . $count;
	                // Count the replies
	                $b = "b";
	                $sql = "SELECT COUNT(id) FROM status WHERE type = ? AND account_name = ? AND osid = ?";
	                $stmt = $conn->prepare($sql);
	                $stmt->bind_param("ssi", $b, $u, $statusid);
	                $stmt->execute();
	                $stmt->bind_result($countrply);
	                $stmt->fetch();
	                $stmt->close();
	                $crply = '' . $countrply;
	                $showmore = "";

                    $dec = "";
                    $urlId = "";
                    if($row["type"] != "b"){
                        $dec = "post";
                        $urlId = "status";
                    }else{
                        $dec = $urlId = "reply";
                    }

	                if ($countrply > 0) {
	                    $showmore = '<div class="showrply"><a id="showreply_feed_' . $statusid . '" onclick="showReply(' . $statusid . ',' . $crply . ',\'feed\')">Show replies (' . $crply . ')</a></div>';
	                }
	                $statuslist.= '<div id="status_' . $statusid . '" class="status_boxes">
                        <div>
                            <p id="status_date">
                                <b class="status_title">Post: </b>
                                <b class="pdate">
                                    <span class="tooLong">' . $postdate . '</span> (' . $agoform . ' ago)
                                </b>
                            </p>' . $user_image_status . '
                            <div id="sdata_' . $statusid . '">
                                <p id="status_text">
                                    <b class="sdata" id="hide_feed_' . $statusid . '">' . $data . '' . $data_old . '</b>
                                </p>
                            </div>
                            <hr class="dim">
                        
                            <span id="likeBtn_feed_' . $statusid . '" class="likeBtn">
                                ' . $likeButton . '
                                <span style="vertical-align: middle;">Like</span>
                            </span>
                            <div class="shareDiv">
                                ' . $shareButton . '
                                <span style="vertical-align: middle;">Share</span>
                            </div>
                            <div class="indinf">
                                <a href="/user/'.$account_name.'/#'.$urlId.'_'.$statusid.'" style="color: #999; padding: 0px 10px 0px 10px; vertical-align: middle;">Status '.$dec.'
                                </a>
                            </div>
                            <div style="float: left; padding: 0px 10px 0px 10px;">
                                <b class="ispan" id="ipanf_' . $statusid . '">
                                    ' . $cl . ' likes
                                </b>
                            </div>
                            <div class="clear"></div>
                    </div>
                    ' . $showmore . '
                    <span id="allrply_feed_' . $statusid . '" class="hiderply">' . $status_replies . '</span>
                    </div>';
	                // all 1 line
	                if ($isFriend == true && $row["type"] != "b") {
	                    $statuslist.= '<textarea id="replytext_feed_' . $statusid . '" class="replytext" onfocus="showBtnDiv_reply(\'' . $statusid . '\',\'feed\')" placeholder="Write a comment..."></textarea>';
	                    $statuslist.= '<div id="uploadDisplay_SP_reply_feed_' . $statusid . '"></div>';
	                    $statuslist.= '<div id="btns_SP_reply_feed_' . $statusid . '" class="hiddenStuff">';
	                    $statuslist.= '<span id="swithidbr_feed_' . $statusid . '"><button id="replyBtn_feed_' . $statusid . '" class="btn_rply" onclick="replyToStatus(\'' . $statusid . '\',\'' . $u . '\',\'replytext_feed_' . $statusid . '\',this)">Reply</button></span>';
	                    $statuslist.= '<img src="/images/camera.png" id="triggerBtn_SP_reply_feed_" class="triggerBtnreply" onclick="triggerUpload_reply(event, \'fu_SP_reply_feed_\')" width="22" height="22" title="Upload A Photo" />';
	                    $statuslist.= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_reply(' . $statusid . ', \'feed\')">';
	                    $statuslist.= '<div class="clear"></div>';
	                    $statuslist.= generateEList($statusid, 'emojiBox_reply_feed_' . $statusid . '', 'replytext_feed_' . $statusid . '');
	                    $statuslist.= '</div>';
	                    $statuslist.= '<div id="standardUpload_reply" class="hiddenStuff">';
	                    $statuslist.= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
	                    $statuslist.= '<input type="file" name="FileUpload" id="fu_SP_reply_feed_" onchange="doUpload_reply(\'fu_SP_reply_feed_\', \'' . $statusid . '\')" accept="image/*"/>';
	                    $statuslist.= '</form>';
	                    $statuslist.= '</div>';
	                    $statuslist.= '<div class="clear"></div>';
	                }
	                array_push($newsfeed,$statuslist);
	            }
	        } else {
	                $statuslist = "<p style='font-size: 14px; text-align: left;'>Your friends have not posted or replied anything yet ...</p>";
	            //}
	    }

	    $statuslist.= "<hr>";
    	$stmt->close();

	    // Start getting photo posts for news feed
	    $sql = "SELECT COUNT(id) FROM photos_status
	    			WHERE author IN ('$friendsCSV') AND (type=? OR type=?)";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss", $a, $c);
	    $stmt->execute();
	    $stmt->bind_result($photorcnt);
	    $stmt->fetch();
	    $stmt->close();

        // Choose most liked photo posts with no friends
        $idphot = "";
        if($cnt_near < 1){
            $phot_stat = array();
            $sql = "SELECT s.status, COUNT(*) AS x FROM photo_stat_likes AS s LEFT JOIN users AS u ON u.username = s.username WHERE u.country = ? GROUP BY s.status ORDER BY x DESC LIMIT 65000";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s",$ucountry);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()){
                array_push($phot_stat, $row["status"]);
            }
            $stmt->close();
            $idphot = join("','",$phot_stat);
        }

	    $statphol = "<p>Recommended photo posts from your friends & followings</p>";

		    if($friendsCSV != ""){
		        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
		                FROM photos_status AS s
		                LEFT JOIN users AS u ON u.username = s.author
		                WHERE s.author IN ('$friendsCSV') OR s.author IN('$loggedFoF') AND (s.type=? OR s.type=?) AND s.author != ? LIMIT 6,65000";
		    }else if($cnt_near > 0){
		        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
		                FROM photos_status AS s
		                LEFT JOIN users AS u ON u.username = s.author
		                WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type=?) AND s.author != ? LIMIT 6,65000";
		    }else if($idphot != ""){
                $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                        FROM photos_status AS s
                        LEFT JOIN users AS u ON u.username = s.author
                        WHERE s.id IN ('$idphot') AND s.author != ? LIMIT 65000";
            }else{
                $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                        FROM photos_status AS s
                        LEFT JOIN users AS u ON u.username = s.author WHERE s.author != ?
                        ORDER BY RAND() LIMIT 65000";
            }
		    $stmt = $conn->prepare($sql);
		    if($friendsCSV != ""){
		        $stmt->bind_param("ss",$a,$c);
		    }else if($cnt_near > 0){
		        $stmt->bind_param("ssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c);
		    }else{
		        $stmt->bind_param("s", $log_username);
		    }
		    $stmt->execute();
		    $result = $stmt->get_result();
		    if ($result->num_rows > 0) {
		        while ($row = $result->fetch_assoc()) {
		            $statphol = "";
		             $statusid = $row["id"];
            $phot = $row["photo"];
            $account_name = $row["account_name"];
            $author = $row["author"];
            $postdate_ = $row["postdate"];
            $postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
            $data = $row["data"];
            $avatar = $row["avatar"];
            if ($avatar == NULL) {
                $pcurl = '/images/avdef.png';
            } else {
                $pcurl = '/user/' . $author . '/' . $avatar;
            }
            $flat = $row["lat"];
            $flon = $row["lon"];
            $ison = $row["online"];
            $fuco = $row["country"];
            $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
            $isonimg = '';
            if ($ison == "yes") {
                $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
            } else {
                $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
            }
            $funames = $author;
            if (strlen($funames) > 20) {
                $funames = mb_substr($funames, 0, 16, "utf-8");
                $funames.= " ...";
            }
            if (strlen($fuco) > 20) {
                $fuco = mb_substr($fuco, 0, 16, "utf-8");
                $fuco.= " ...";
            }
            $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $author, $author, $one);
            $stmt->execute();
            $stmt->bind_result($numoffs);
            $stmt->fetch();
            $stmt->close();
            $user_image_status = '<a href="/user/' . $author . '/"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; ' . $mgin . ' background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob"></div><div class="infostdiv"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left;" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
            $agoform = time_elapsed_string($postdate_);
            $data_old = $row["data"];
            $data_old = nl2br($data_old);
            $data_old = str_replace("&amp;", "&", $data_old);
            $data_old = stripslashes($data_old);
            $pos = strpos($data_old, '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
            $isex = false;
            $sec_data = "";
            $first_data = "";
            if (strpos($data_old, '<img src="/permUploads/') !== false) {
                $split = explode('<img src="/permUploads/', $data_old);
                clearstatcache();
                $sec_data = '<img src="/permUploads/' . $split[1];
                $first_data = $split[0];
                $img = str_replace('"', '', $split[1]); // remove double quotes
                $img = str_replace('/>', '', $img); // remove img end tag
                $img = str_replace(' ', '', $img); // remove spaces
                $img = str_replace('<br>', '', $img); // remove spaces
                $img = trim($img);
                $fn = "permUploads/" . $img; // file name with dynamic variable in it
                if (file_exists($fn)) {
                    $isex = true;
                }
            }
            if (strlen($data) > 1000) {
                if ($pos === false && $isex == false) {
                    $data = mb_substr($data, 0, 1000, "utf-8");
                    $data.= " ...";
                    $data.= '&nbsp;<a id="toggle_phot_' . $statusid . '" onclick="opentext(\'' . $statusid . '\',\'phot\')">See More</a>';
                    $data_old = '<div id="lessmore_phot_' . $statusid . '" class="lmml"><p id="status_text">' . $data_old . '&nbsp;<a id="toggle_phot_' . $statusid . '" onclick="opentext(\'' . $statusid . '\',\'phot\')">See Less</a></p></div>';
                } else {
                    $data_old = "";
                }
            } else {
                $data_old = "";
            }
            $data = nl2br($data);
            $data = str_replace("&amp;", "&", $data);
            $data = stripslashes($data);
            if (strpos($data, '<img src=\"permUploads/"') === true) {
                $data.= '<br>';
            }
            // Add share button
            $shareButton = "";
            if ($log_username != "" && $author != $log_username && $account_name != $log_username) {
                $shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus_phot(\'' . $statusid . '\',\'' . $phot . '\');" id="shareBlink">';
            }
            $isLike = false;
            if ($user_ok == true) {
                $like_check = "SELECT id FROM photo_stat_likes WHERE username=? AND status=? LIMIT 1";
                $stmt = $conn->prepare($like_check);
                $stmt->bind_param("si", $log_username, $statusid);
                $stmt->execute();
                $stmt->store_result();
                $stmt->fetch();
                $numrows = $stmt->num_rows;
                if ($numrows > 0) {
                    $isLike = true;
                }
            }
            $stmt->close();
            // Add status like button
            $likeButton = "";
            $likeText = "";
            if ($isLike == true) {
                $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike_phot(\'unlike\',\'' . $statusid . '\',\'likeBtn_phot' . $statusid . '\',\'' . $phot . '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a>';
                $likeText = '<span style="vertical-align: middle;">Dislike</span>';

            } else {
                $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike_phot(\'like\',\'' . $statusid . '\',\'likeBtn_phot' . $statusid . '\',\'' . $phot . '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a>';
                $likeText = '<span style="vertical-align: middle;">Like</span>';

            }
            // GATHER UP ANY STATUS REPLIES
            $status_replies = "";
            // all 1 line
            $sql2 = "SELECT a.*, u.avatar, u.country, u.lat, u.lon
        				 	FROM photos_status AS a
        				 	LEFT JOIN users AS u ON u.username = a.author
        				 	WHERE a.osid = ?
        				 	AND a.type=?
        				 	ORDER BY a.postdate DESC";
            $stmt = $conn->prepare($sql2);
            $stmt->bind_param("is", $statusid, $b);
            $stmt->execute();
            $result2 = $stmt->get_result();
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $statusreplyid = $row2["id"];
                    $replyauthor = $row2["author"];
                    $replydata = $row2["data"];
                    $replydata = nl2br($replydata);
                    $replypostdate_ = $row2["postdate"];
                    $replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
                    $avatar2 = $row2["avatar"];
                    $replydata = str_replace("&amp;", "&", $replydata);
                    $replydata = stripslashes($replydata);
                    if ($avatar2 == NULL) {
                        $pcurl = '/images/avdef.png';
                    } else {
                        $pcurl = 'user/' . $replyauthor . '/' . $avatar2;
                    }
                    $flat = $row["lat"];
                    $flon = $row["lon"];
                    $ison = $row["online"];
                    $fuco = $row["country"];
                    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
                    $isonimg = '';
                    if ($ison == "yes") {
                        $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
                    } else {
                        $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
                    }
                    $funames = $replyauthor;
                    if (strlen($funames) > 20) {
                        $funames = mb_substr($funames, 0, 16, "utf-8");
                        $funames.= " ...";
                    }
                    if (strlen($fuco) > 20) {
                        $fuco = mb_substr($fuco, 0, 16, "utf-8");
                        $fuco.= " ...";
                    }
                    $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $replyauthor, $replyauthor, $one);
                    $stmt->execute();
                    $stmt->bind_result($numoffs);
                    $stmt->fetch();
                    $stmt->close();
                    $user_image_reply = '<a href="/user/' . urlencode($replyauthor) . '/"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob"></div><div class="infotsrdiv"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
                    $data_old_reply = $row2["data"];
                    $data_old_reply = nl2br($data_old_reply);
                    $data_old_reply = str_replace("&amp;", "&", $data_old_reply);
                    $data_old_reply = stripslashes($data_old_reply);
                    $isex = false;
                    $sec_data = "";
                    $first_data = "";
                    if (strpos($data_old_reply, '<img src="/permUploads/') !== false) {
                        $split = explode('<img src="/permUploads/', $data_old_reply);
                        clearstatcache();
                        $sec_data = '<img src="/permUploads/' . $split[1];
                        $first_data = $split[0];
                        $img = str_replace('"', '', $split[1]); // remove double quotes
                        $img = str_replace('/>', '', $img); // remove img end tag
                        $img = str_replace(' ', '', $img); // remove spaces
                        $img = str_replace('<br>', '', $img); // remove spaces
                        $img = trim($img);
                        $fn = "permUploads/" . $img; // file name with dynamic variable in it
                        if (file_exists($fn)) {
                            $isex = true;
                        }
                    }
                    if (strlen($replydata) > 1000) {
                        if ($isex == false) {
                            $replydata = mb_substr($replydata, 0, 1000, "utf-8");
                            $replydata.= " ...";
                            $replydata.= '&nbsp;<a id="toggle_phot_r_' . $statusreplyid . '" onclick="opentext(\'' . $statusreplyid . '\',\'phot_r\')">See More</a>';
                            $data_old_reply = '<div id="lessmore_phot_r_' . $statusreplyid . '" class="lmml"><p id="status_text">' . $data_old_reply . '&nbsp;<a id="toggle_phot_r_' . $statusreplyid . '" onclick="opentext(\'' . $statusreplyid . '\',\'phot_r\')">See Less</a></p></div>';
                        } else {
                            $data_old_reply = "";
                        }
                    } else {
                        $data_old_reply = "";
                    }
                    $replydata = nl2br($replydata);
                    $replydata = str_replace("&amp;", "&", $replydata);
                    $replydata = stripslashes($replydata);
                    $replyDeleteButton = '';
                    if ($replyauthor == $log_username || $account_name == $log_username) {
                        $replyDeleteButton = '<span id="srdb_phot_' . $statusreplyid . '"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\'' . $statusreplyid . '\',\'reply_phot_' . $statusreplyid . '\',\'phot\',\'' . $phot . '\');" title="Delete Comment">X</button ></span>';
                    }
                    $agoformrply = time_elapsed_string($replypostdate_);
                    //$stmt->close();
                    $isLike_reply = false;
                    if ($user_ok == true) {
                        $like_check_reply = "SELECT id FROM photo_reply_likes WHERE username=? AND reply=? LIMIT 1";
                        $stmt = $conn->prepare($like_check_reply);
                        $stmt->bind_param("si", $log_username, $statusreplyid);
                        $stmt->execute();
                        $stmt->store_result();
                        $stmt->fetch();
                        $numrows = $stmt->num_rows;
                        if ($numrows > 0) {
                            $isLike_reply = true;
                        }
                    }
                    $stmt->close();
                    // Add reply like button
                    $likeButton_reply = "";
                    $likeText_reply = "";
                    if ($isLike_reply == true) {
                        $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_phot(\'unlike\',\'' . $statusreplyid . '\',\'likeBtn_reply_phot' . $statusreplyid . '\',\'' . $phot . '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
                        $likeText_reply = '<span style="vertical-align: middle;">Dislike</span>';
                    } else {
                        $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_phot(\'like\',\'' . $statusreplyid . '\',\'likeBtn_reply_phot' . $statusreplyid . '\',\'' . $phot . '\')"><img src="/images/nf.png" width="18" height="18" title="Like" class="like_unlike"></a>';
                        $likeText_reply = '<span style="vertical-align: middle;">Like</span>';
                    }
                    // Count reply likes
                    $sql = "SELECT COUNT(id) FROM photo_reply_likes WHERE reply = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $statusreplyid);
                    $stmt->execute();
                    $stmt->bind_result($rpycount);
                    $stmt->fetch();
                    $stmt->close();
                    $rpycl = '' . $rpycount;
                    $status_replies.= '
        					<div id="reply_phot_' . $statusreplyid . '" class="reply_boxes">
                                <div>' . $replyDeleteButton . '
                                <p id="float">
                                    <b class="sreply">Reply: </b>
                                    <b class="rdate">
                                        <span class="tooLong">' . $replypostdate . '</span> (' . $agoformrply . ' ago)
                                    </b>
                                </p>' . $user_image_reply . '
                                <p id="reply_text">
                                    <b class="sdata" id="hide_phot_r_' . $statusreplyid . '">' . $replydata . '' . $data_old_reply . '</b>
                                </p>
                                
                                <hr class="dim">

                                <span id="likeBtn_reply_phot' . $statusreplyid . '" class="likeBtn">
                                    ' . $likeButton_reply . '
                                    <span style="vertical-align: middle;">'.$likeText_reply.'</span>
                                </span>
                                <div style="float: left; padding: 0px 10px 0px 10px;">
                                    <b class="ispan" id="ipanr_phot_' . $statusreplyid . '">' . $rpycl . ' likes</b>
                                </div>
                                <div class="clear"></div>
                                </div>
                            </div>';
                }
            }
		            // Count likes
		            $sql = "SELECT COUNT(id) FROM photo_stat_likes WHERE status = ?";
		            $stmt = $conn->prepare($sql);
		            $stmt->bind_param("i", $statusid);
		            $stmt->execute();
		            $stmt->bind_result($count);
		            $stmt->fetch();
		            $stmt->close();
		            $cl = '' . $count;
		            // Count the replies
		            $b = "b";
		            $sql = "SELECT COUNT(id) FROM photos_status WHERE type = ? AND osid = ?";
		            $stmt = $conn->prepare($sql);
		            $stmt->bind_param("si", $b, $statusid);
		            $stmt->execute();
		            $stmt->bind_result($countrply);
		            $stmt->fetch();
		            $stmt->close();
		            $crply = '' . $countrply;
		            $showmore = "";
		            if ($countrply > 0) {
		                $showmore = '<div class="showrply"><a id="showreply_phot_' . $statusid . '" onclick="showReply(' . $statusid . ',' . $crply . ',\'phot\')">Show replies (' . $crply . ')</a></div>';
		            }

                    $dec = "";
                    $urlId = "";
                    if($row["type"] != "b"){
                        $dec = "post";
                        $urlId = "status";
                    }else{
                        $dec = $urlId = "reply";
                    }

		            $statphol.= '<div id="status_' . $statusid . '" class="status_boxes">
                    <div>
                        <p id="status_date">
                            <b class="status_title">Post: </b>
                            <b class="pdate">
                                <span class="tooLong">' . $postdate . '</span> (' . $agoform . ' ago)
                            </b>
                        </p>
                        ' . $user_image_status . '
                        <div id="sdata_' . $statusid . '">
                            <p id="status_text">
                                <b class="sdata" id="hide_phot_' . $statusid . '">' . $data . '' . $data_old . '
                                </b>
                            </p>
                        </div>

                        <hr class="dim">

                        <span id="likeBtn_phot' . $statusid . '" class="likeBtn">
                            ' . $likeButton . '
                            <span style="vertical-align: middle;">Like</span>
                        </span>
                        <div class="shareDiv">
                            ' . $shareButton . '
                            <span style="vertical-align: middle;">Share</span>
                        </div>
                        <span class="indinf">
                            <a href="/photo_zoom/'.$account_name.'/'.$phot.'#'.$urlId.'_'.$statusid.'" style="color: #999; padding: 0px 10px 0px 10px; vertical-align: middle;">Photo '.$dec.'
                            </a>
                        </span>
                        <div style="float: left; padding: 0px 10px 0px 10px;">
                            <b class="ispan" id="ipan_phot_' . $statusid . '">
                                ' . $cl . ' likes
                            </b>
                        </div>
                        <div class="clear"></div>
                    </div>
                    ' . $showmore . '<span id="allrply_phot_' . $statusid . '" class="hiderply">' . $status_replies . '</span>
                    </div>';
		            // all 1 line
		            if ($isFriend == true && $row["type"] != "b") {
		                $statphol.= '<textarea id="replytext_phot_' . $statusid . '" class="replytext" onfocus="showBtnDiv_reply(\'' . $statusid . '\',\'phot\')" placeholder="Write a comment..."></textarea>';
		                $statphol.= '<div id="uploadDisplay_SP_reply_phot_' . $statusid . '"></div>';
		                $statphol.= '<div id="btns_SP_reply_phot_' . $statusid . '" class="hiddenStuff">';
		                $statphol.= '<span id="swithidbr_phot_' . $statusid . '"><button id="replyBtn_phot_' . $statusid . '" class="btn_rply" onclick="replyToStatus_phot(\'' . $statusid . '\',\'' . $u . '\',\'replytext_phot_' . $statusid . '\',this,\'' . $phot . '\')">Reply</button></span>';
		                $statphol.= '<img src="/images/camera.png" id="triggerBtn_SP_reply_phot_" class="triggerBtnreply" onclick="triggerUpload_reply(event, \'fu_SP_reply_phot_\')" width="22" height="22" title="Upload A Photo" />';
		                $statphol.= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_reply(' . $statusid . ', \'phot\')">';
		                $statphol.= '<div class="clear"></div>';
		                $statphol.= generateEList($statusid, 'emojiBox_reply_phot_' . $statusid . '', 'replytext_phot_' . $statusid . '');
		                $statphol.= '</div>';
		                $statphol.= '<div id="standardUpload_reply" class="hiddenStuff">';
		                $statphol.= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
		                $statphol.= '<input type="file" name="FileUpload" id="fu_SP_reply_phot_" onchange="doUpload_reply(\'fu_SP_reply_phot_\', \'' . $statusid . '\')" accept="image/*"/>';
		                $statphol.= '</form>';
		                $statphol.= '</div>';
		                $statphol.= '<div class="clear"></div>';
		            }
		            array_push($newsfeed,$statphol);
		        }
		        /*echo '<div class="test" style="display: none;">' . $statphol . "</div>";*/
	            //exit();
		    } else {
		        $statphol = "<p style='font-size: 14px; text-align: left;'>Your friends have not posted or replied anything yet ...</p>";
		    }
		    $statphol .= "<hr>";
		    $stmt->close();


		// Start getting article posts for news feed
	    $sql = "SELECT COUNT(id)
	    			FROM article_status
	    			WHERE author IN ('$friendsCSV') AND (type=? OR type=?)";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss", $a, $c);
	    $stmt->execute();
	    $stmt->bind_result($artrcnt);
	    $stmt->fetch();
	    $stmt->close();

        // Choose most liked photo posts with no friends
        $idart = "";
        if($cnt_near < 1){
            $art_stat = array();
            $sql = "SELECT s.status, COUNT(*) AS x FROM art_stat_likes AS s LEFT JOIN users AS u ON u.username = s.username WHERE u.country = ? GROUP BY s.status ORDER BY x DESC LIMIT 65000";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s",$ucountry);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()){
                array_push($art_stat, $row["status"]);
            }
            $stmt->close();
            $idart = join("','",$art_stat);
        }

	    $statartl = "<p>Recommended article posts from your friends & followings</p>";

		    if($friendsCSV != ""){
		        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
		                FROM article_status AS s
		                LEFT JOIN users AS u ON u.username = s.author
		                WHERE s.author IN ('$friendsCSV') AND (s.type=? OR s.type=?) AND s.author != ? LIMIT 6,65000";
		    }else if($cnt_near > 0){
		        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
		                FROM article_status AS s
		                LEFT JOIN users AS u ON u.username = s.author
		                WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type=?) AND s.author != ? LIMIT 6,65000";
		    }else if($idart != ""){
                $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                        FROM article_status AS s
                        LEFT JOIN users AS u ON u.username = s.author
                        WHERE s.id IN ('$idart') AND s.author != ? LIMIT 65000";
            }else{
                $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                        FROM article_status AS s
                        LEFT JOIN users AS u ON u.username = s.author WHERE s.author != ?
                        ORDER BY RAND() LIMIT 65000";
            }
		    $stmt = $conn->prepare($sql);
		    if($friendsCSV != ""){
		        $stmt->bind_param("ss",$a,$c);
		    }else if($cnt_near > 0){
		        $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $log_username);
		    }else{
		        $stmt->bind_param("s", $log_username);
		    }
		    $stmt->execute();
		    $result = $stmt->get_result();
		    if ($result->num_rows > 0) {
		        while ($row = $result->fetch_assoc()) {
		            $statartl = "";
		            $statusid = $row["id"];
            $arid = $row["artid"];
            $account_name = $row["account_name"];
            $author = $row["author"];
            $postdate_ = $row["postdate"];
            
            $sql = "SELECT post_time FROM articles WHERE id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i",$arid);
            $stmt->execute();
            $stmt->bind_result($apt);
            $stmt->fetch();
            $stmt->close();
            
            $pd = base64url_encode($apt,$hshkey);
            $postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
            $data = $row["data"];
            $avatar = $row["avatar"];
            if ($avatar == NULL) {
                $pcurl = '/images/avdef.png';
            } else {
                $pcurl = '/user/' . $author . '/' . $avatar;
            }
            $flat = $row["lat"];
            $flon = $row["lon"];
            $ison = $row["online"];
            $fuco = $row["country"];
            $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
            $isonimg = '';
            if ($ison == "yes") {
                $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
            } else {
                $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
            }
            $funames = $author;
            if (strlen($funames) > 20) {
                $funames = mb_substr($funames, 0, 16, "utf-8");
                $funames.= " ...";
            }
            if (strlen($fuco) > 20) {
                $fuco = mb_substr($fuco, 0, 16, "utf-8");
                $fuco.= " ...";
            }
            $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $author, $author, $one);
            $stmt->execute();
            $stmt->bind_result($numoffs);
            $stmt->fetch();
            $stmt->close();
            $user_image_status = '<a href="/user/' . $author . '/"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; ' . $mgin . ' background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob"></div><div class="infostdiv"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left;" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
            $agoform = time_elapsed_string($postdate_);
            $data_old = $row["data"];
            $data_old = nl2br($data_old);
            $data_old = str_replace("&amp;", "&", $data_old);
            $data_old = stripslashes($data_old);
            $pos = strpos($data_old, '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
            $isex = false;
            $sec_data = "";
            $first_data = "";
            if (strpos($data_old, '<img src="/permUploads/') !== false) {
                $split = explode('<img src="/permUploads/', $data_old);
                clearstatcache();
                $sec_data = '<img src="/permUploads/' . $split[1];
                $first_data = $split[0];
                $img = str_replace('"', '', $split[1]); // remove double quotes
                $img = str_replace('/>', '', $img); // remove img end tag
                $img = str_replace(' ', '', $img); // remove spaces
                $img = str_replace('<br>', '', $img); // remove spaces
                $img = trim($img);
                $fn = "permUploads/" . $img; // file name with dynamic variable in it
                if (file_exists($fn)) {
                    $isex = true;
                }
            }
            if (strlen($data) > 1000) {
                if ($pos === false && $isex == false) {
                    $data = mb_substr($data, 0, 1000, "utf-8");
                    $data.= " ...";
                    $data.= '&nbsp;<a id="toggle_art_' . $statusid . '" onclick="opentext(\'' . $statusid . '\',\'art\')">See More</a>';
                    $data_old = '<div id="lessmore_art_' . $statusid . '" class="lmml"><p id="status_text">' . $data_old . '&nbsp;<a id="toggle_art_' . $statusid . '" onclick="opentext(\'' . $statusid . '\',\'art\')">See Less</a></p></div>';
                } else {
                    $data_old = "";
                }
            } else {
                $data_old = "";
            }
            $data = nl2br($data);
            $data = str_replace("&amp;", "&", $data);
            $data = stripslashes($data);
            if (strpos($data, '<img src=\"permUploads/"') === true) {
                $data.= '<br>';
            }
            // Add share button
            $shareButton = "";
            if ($log_username != "" && $author != $log_username && $account_name != $log_username) {
                $shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus_art(\'' . $statusid . '\');" id="shareBlink">';
            }
            $isLike = false;
            if ($user_ok == true) {
                $like_check = "SELECT id FROM art_stat_likes WHERE username=? AND status=? LIMIT 1";
                $stmt = $conn->prepare($like_check);
                $stmt->bind_param("si", $log_username, $statusid);
                $stmt->execute();
                $stmt->store_result();
                $stmt->fetch();
                $numrows = $stmt->num_rows;
                if ($numrows > 0) {
                    $isLike = true;
                }
            }
            $stmt->close();
            // Add status like button
            $likeButton = "";
            $likeText = "";
            if ($isLike == true) {
                $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike_art(\'unlike\',\'' . $statusid . '\',\'likeBtn_art' . $statusid . '\',\'' . $arid . '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
                $likeText = '<span style="vertical-align: middle;">Dislike</span>';
            } else {
                $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike_art(\'like\',\'' . $statusid . '\',\'likeBtn_art' . $statusid . '\',\'' . $arid . '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a>';
                $likeText = '<span style="vertical-align: middle;">Like</span>';
            }
            // GATHER UP ANY STATUS REPLIES
            $status_replies = "";
            // all 1 line
            $sql2 = "SELECT a.*, u.avatar, u.country, u.lat, u.lon
        				 	FROM article_status AS a
        				 	LEFT JOIN users AS u ON u.username = a.author
        				 	WHERE a.osid = ?
        				 	AND a.type=?
        				 	ORDER BY a.postdate DESC";
            $stmt = $conn->prepare($sql2);
            $stmt->bind_param("is", $statusid, $b);
            $stmt->execute();
            $result2 = $stmt->get_result();
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $statusreplyid = $row2["id"];
                    $replyauthor = $row2["author"];
                    $replydata = $row2["data"];
                    $replydata = nl2br($replydata);
                    $replypostdate_ = $row2["postdate"];
                    $replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
                    $avatar2 = $row2["avatar"];
                    $replydata = str_replace("&amp;", "&", $replydata);
                    $replydata = stripslashes($replydata);
                    if ($avatar2 == NULL) {
                        $pcurl = '/images/avdef.png';
                    } else {
                        $pcurl = 'user/' . $replyauthor . '/' . $avatar2;
                    }
                    $flat = $row["lat"];
                    $flon = $row["lon"];
                    $ison = $row["online"];
                    $fuco = $row["country"];
                    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
                    $isonimg = '';
                    if ($ison == "yes") {
                        $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
                    } else {
                        $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
                    }
                    $funames = $replyauthor;
                    if (strlen($funames) > 20) {
                        $funames = mb_substr($funames, 0, 16, "utf-8");
                        $funames.= " ...";
                    }
                    if (strlen($fuco) > 20) {
                        $fuco = mb_substr($fuco, 0, 16, "utf-8");
                        $fuco.= " ...";
                    }
                    $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $replyauthor, $replyauthor, $one);
                    $stmt->execute();
                    $stmt->bind_result($numoffs);
                    $stmt->fetch();
                    $stmt->close();
                    $user_image_reply = '<a href="/user/' . urlencode($replyauthor) . '/"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob"></div><div class="infotsrdiv"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
                    $data_old_reply = $row2["data"];
                    $data_old_reply = nl2br($data_old_reply);
                    $data_old_reply = str_replace("&amp;", "&", $data_old_reply);
                    $data_old_reply = stripslashes($data_old_reply);
                    $isex = false;
                    $sec_data = "";
                    $first_data = "";
                    if (strpos($data_old_reply, '<img src="/permUploads/') !== false) {
                        $split = explode('<img src="/permUploads/', $data_old_reply);
                        clearstatcache();
                        $sec_data = '<img src="/permUploads/' . $split[1];
                        $first_data = $split[0];
                        $img = str_replace('"', '', $split[1]); // remove double quotes
                        $img = str_replace('/>', '', $img); // remove img end tag
                        $img = str_replace(' ', '', $img); // remove spaces
                        $img = str_replace('<br>', '', $img); // remove spaces
                        $img = trim($img);
                        $fn = "permUploads/" . $img; // file name with dynamic variable in it
                        if (file_exists($fn)) {
                            $isex = true;
                        }
                    }
                    if (strlen($replydata) > 1000) {
                        if ($isex == false) {
                            $replydata = mb_substr($replydata, 0, 1000, "utf-8");
                            $replydata.= " ...";
                            $replydata.= '&nbsp;<a id="toggle_art_r_' . $statusreplyid . '" onclick="opentext(\'' . $statusreplyid . '\',\'art_r\')">See More</a>';
                            $data_old_reply = '<div id="lessmore_art_r_' . $statusreplyid . '" class="lmml"><p id="status_text">' . $data_old_reply . '&nbsp;<a id="toggle_art_r_' . $statusreplyid . '" onclick="opentext(\'' . $statusreplyid . '\',\'art_r\')">See Less</a></p></div>';
                        } else {
                            $data_old_reply = "";
                        }
                    } else {
                        $data_old_reply = "";
                    }
                    $replydata = nl2br($replydata);
                    $replydata = str_replace("&amp;", "&", $replydata);
                    $replydata = stripslashes($replydata);
                    $replyDeleteButton = '';
                    if ($replyauthor == $log_username || $account_name == $log_username) {
                        $replyDeleteButton = '<span id="srdb_' . $statusreplyid . '"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\'' . $statusreplyid . '\',\'reply_art_' . $statusreplyid . '\',\'art\',\'' . $arid . '\');" title="Delete Comment">X</button ></span>';
                    }
                    $agoformrply = time_elapsed_string($replypostdate_);
                    //$stmt->close();
                    $isLike_reply = false;
                    if ($user_ok == true) {
                        $like_check_reply = "SELECT id FROM art_reply_likes WHERE username=? AND reply=? LIMIT 1";
                        $stmt = $conn->prepare($like_check_reply);
                        $stmt->bind_param("si", $log_username, $statusreplyid);
                        $stmt->execute();
                        $stmt->store_result();
                        $stmt->fetch();
                        $numrows = $stmt->num_rows;
                        if ($numrows > 0) {
                            $isLike_reply = true;
                        }
                    }
                    $stmt->close();
                    // Add reply like button
                    $likeButton_reply = "";
                    $likeText_reply = "";
                    if ($isLike_reply == true) {
                        $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_art(\'unlike\',\'' . $statusreplyid . '\',\'likeBtn_reply_art' . $statusreplyid . '\',\'' . $arid . '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
                        $likeText_reply = '<span style="vertical-align: middle;">Dislike</span>';
                    } else {
                        $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_art(\'like\',\'' . $statusreplyid . '\',\'likeBtn_reply_art' . $statusreplyid . '\',\'' . $arid . '\')"><img src="/images/nf.png" width="18" height="18" title="Like" class="like_unlike"></a>';
                        $likeText_reply = '<span style="vertical-align: middle;">Like</span>';
                    }
                    // Count reply likes
                    $sql = "SELECT COUNT(id) FROM art_reply_likes WHERE reply = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $statusreplyid);
                    $stmt->execute();
                    $stmt->bind_result($rpycount);
                    $stmt->fetch();
                    $stmt->close();
                    $rpycl = '' . $rpycount;
                    $status_replies.= '
        					<div id="reply_art_' . $statusreplyid . '" class="reply_boxes">
                                <div>' . $replyDeleteButton . '
                                <p id="float">
                                    <b class="sreply">Reply: </b>
                                    <b class="rdate">
                                        <span class="tooLong">' . $replypostdate . '</span> (' . $agoformrply . ' ago)
                                    </b>
                                </p>' . $user_image_reply . '
                                <p id="reply_text">
                                    <b class="sdata" id="hide_art_r_' . $statusreplyid . '">' . $replydata . '' . $data_old_reply . '</b>
                                </p>

                                <hr class="dim">

                                <span id="likeBtn_reply_art' . $statusreplyid . '" class="likeBtn">
                                    ' . $likeButton_reply . '
                                    <span style="vertical-align: middle;">'.$likeText_reply.'</span>
                                </span>
                                <div style="float: left; padding: 0px 10px 0px 10px;">
                                    <b class="ispan" id="ipanr_art_' . $statusreplyid . '">' . $rpycl . ' likes</b>
                                </div>
                                <div class="clear"></div>
                                </div>
                            </div>';
                }
            }
            // Count likes
            $sql = "SELECT COUNT(id) FROM art_stat_likes WHERE status = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $statusid);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            $cl = '' . $count;
            // Count the replies
            $b = "b";
            $sql = "SELECT COUNT(id) FROM article_status WHERE type = ? AND osid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $b, $statusid);
            $stmt->execute();
            $stmt->bind_result($countrply);
            $stmt->fetch();
            $stmt->close();
            $crply = '' . $countrply;
            $showmore = "";
            if ($countrply > 0) {
                $showmore = '<div class="showrply"><a id="showreply_art_' . $statusid . '" onclick="showReply(' . $statusid . ',' . $crply . ',\'art\')">Show replies (' . $crply . ')</a></div>';
            }

            $dec = "";
                $urlId = "";
                if($row["type"] != "b"){
                    $dec = "post";
                    $urlId = "status";
                }else{
                    $dec = $urlId = "reply";
                }

            $statartl.= '<div id="status_' . $statusid . '" class="status_boxes">
                    <div>
                        <p id="status_date">
                            <b class="status_title">Post: </b>
                            <b class="pdate">
                                <span class="tooLong">' . $postdate . '</span> (' . $agoform . ' ago)
                            </b>
                        </p>' . $user_image_status . '
                        <div id="sdata_' . $statusid . '">
                            <p id="status_text">
                                <b class="sdata" id="hide_art_' . $statusid . '">' . $data . '' . $data_old . '
                                </b>
                            </p>
                        </div>

                        <hr class="dim">

                        <span id="likeBtn_art' . $statusid . '" class="likeBtn">
                            ' . $likeButton . '
                            <span style="vertical-align: middle;">Like</span>
                        </span>
                        <div class="shareDiv">
                            ' . $shareButton . '
                            <span style="vertical-align: middle;">Share</span>
                        </div>
                        <span class="indinf">
                            <a href="/articles/'.$pd.'/'.$account_name.'#'.$urlId.'_'.$statusid.'" style="color: #999; padding: 0px 10px 0px 10px; vertical-align: middle;">Article '.$dec.'</a>
                        </span>
                        <div style="float: left; padding: 0px 10px 0px 10px;">
                            <b class="ispan" id="ipan_' . $statusid . '">' . $cl . ' likes</b>
                        </div>
                        <div class="clear"></div>
                    </div>
                    ' . $showmore . '<span id="allrply_art_' . $statusid . '" class="hiderply">' . $status_replies . '</span>
                    </div>';
            // all 1 line
            if ($isFriend == true && $row["type"] != "b") {
                $statartl.= '<textarea id="replytext_art_' . $statusid . '" class="replytext" onfocus="showBtnDiv_reply(\'' . $statusid . '\',\'art\')" placeholder="Write a comment..."></textarea>';
                $statartl.= '<div id="uploadDisplay_SP_reply_art_' . $statusid . '"></div>';
                $statartl.= '<div id="btns_SP_reply_art_' . $statusid . '" class="hiddenStuff">';
                $statartl.= '<span id="swithidbr_art_' . $statusid . '"><button id="replyBtn_art_' . $statusid . '" class="btn_rply" onclick="replyToStatus_art(\'' . $statusid . '\',\'' . $u . '\',\'replytext_art_' . $statusid . '\',this,\'' . $arid . '\')">Reply</button></span>';
                $statartl.= '<img src="/images/camera.png" id="triggerBtn_SP_reply_art_" class="triggerBtnreply" onclick="triggerUpload_reply(event, \'fu_SP_reply_art_\')" width="22" height="22" title="Upload A Photo" />';
                $statartl.= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_reply(' . $statusid . ', \'art\')">';
                $statartl.= '<div class="clear"></div>';
                $statartl.= generateEList($statusid, 'emojiBox_reply_art_' . $statusid . '', 'replytext_art_' . $statusid . '');
                $statartl.= '</div>';
                $statartl.= '<div id="standardUpload_reply" class="hiddenStuff">';
                $statartl.= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
                $statartl.= '<input type="file" name="FileUpload" id="fu_SP_reply_art_" onchange="doUpload_reply(\'fu_SP_reply_art_\', \'' . $statusid . '\')" accept="image/*"/>';
                $statartl.= '</form>';
                $statartl.= '</div>';
                $statartl.= '<div class="clear"></div>';
		            }
		            array_push($newsfeed,$statartl);
		        }
		        /*echo '<div class="test" style="display: none;">' . $statartl . "</div>";*/
	            //exit();
	    } else {
	        $statartl = "<p style='font-size: 14px; text-align: left;'>Your friends have not posted or replied anything yet ...</p>";
	    }
	    $stmt->close();

	    // Start getting video posts for news feed
	    $sql = "SELECT COUNT(id)
	    			FROM video_status
	    			WHERE author IN ('$friendsCSV') AND (type=? OR type=?)";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("ss", $a, $c);
	    $stmt->execute();
	    $stmt->bind_result($vidrcnt);
	    $stmt->fetch();
	    $stmt->close();

        $idvid = "";
        if($cnt_near < 1){
            $vid_id = array();
            $sql = "SELECT s.status, COUNT(*) AS x FROM video_status_likes AS s LEFT JOIN users AS u ON u.username = s.username WHERE u.country = ? GROUP BY s.status ORDER BY x DESC LIMIT 65000";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s",$ucountry);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()){
                array_push($vid_id, $row["status"]);
            }
            $stmt->close();
            $idvid = join("','",$vid_id);
        }

	    $statvidl = "<p>Recommended video posts from your friends & followings</p>";

		    if($friendsCSV != ""){
		        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
		                FROM video_status AS s
		                LEFT JOIN users AS u ON u.username = s.author
		                WHERE s.author IN ('$friendsCSV') OR s.author IN('$loggedFoF') AND (s.type=? OR s.type=?) AND s.author != ? LIMIT 6,65000";
		    }else if($cnt_near > 0){
		        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
		                FROM video_status AS s
		                LEFT JOIN users AS u ON u.username = s.author
		                WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type=?) AND s.author != ? LIMIT 6,65000";
		    }else if($idvid != ""){
                $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                        FROM video_status AS s
                        LEFT JOIN users AS u ON u.username = s.author
                        WHERE s.id IN ('$idvid') AND s.author != ? LIMIT 65000";
            }else{
                $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                        FROM video_status AS s
                        LEFT JOIN users AS u ON u.username = s.author WHERE s.author != ?
                        ORDER BY RAND() LIMIT 65000";
            }
		    $stmt = $conn->prepare($sql);
		    if($friendsCSV != ""){
		        $stmt->bind_param("sss",$a,$c,$log_username);
		    }else if($cnt_near > 0){
		        $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $log_username);
		    }else{
		        $stmt->bind_param("s", $log_username);
		    }
		    $stmt->execute();
		    $result = $stmt->get_result();
		    if ($result->num_rows > 0) {
		        while ($row = $result->fetch_assoc()) {
		            $statvidl = "";
		            $vidi = $row["vidid"];
            $account_name = $row["account_name"];
            $author = $row["author"];
            $postdate_ = $row["postdate"];
            $postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
            $data = $row["data"];
            $avatar = $row["avatar"];
            if ($avatar == NULL) {
                $pcurl = '/images/avdef.png';
            } else {
                $pcurl = '/user/' . $author . '/' . $avatar;
            }
            $flat = $row["lat"];
            $flon = $row["lon"];
            $ison = $row["online"];
            $fuco = $row["country"];
            $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
            $isonimg = '';
            if ($ison == "yes") {
                $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
            } else {
                $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
            }
            $funames = $author;
            if (strlen($funames) > 20) {
                $funames = mb_substr($funames, 0, 16, "utf-8");
                $funames.= " ...";
            }
            if (strlen($fuco) > 20) {
                $fuco = mb_substr($fuco, 0, 16, "utf-8");
                $fuco.= " ...";
            }
            $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $author, $author, $one);
            $stmt->execute();
            $stmt->bind_result($numoffs);
            $stmt->fetch();
            $stmt->close();
            $user_image_status = '<a href="/user/' . $author . '/"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; margin-bottom: 10px; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob"></div><div class="infostdiv"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left;" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
            $agoform = time_elapsed_string($postdate_);
            $data_old = $row["data"];
            $data_old = nl2br($data_old);
            $data_old = str_replace("&amp;", "&", $data_old);
            $data_old = stripslashes($data_old);
            $pos = strpos($data_old, '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
            $isex = false;
            $sec_data = "";
            $first_data = "";
            if (strpos($data_old, '<img src="/permUploads/') !== false) {
                $split = explode('<img src="/permUploads/', $data_old);
                clearstatcache();
                $sec_data = '<img src="/permUploads/' . $split[1];
                $first_data = $split[0];
                $img = str_replace('"', '', $split[1]); // remove double quotes
                $img = str_replace('/>', '', $img); // remove img end tag
                $img = str_replace(' ', '', $img); // remove spaces
                $img = str_replace('<br>', '', $img); // remove spaces
                $img = trim($img);
                $fn = "permUploads/" . $img; // file name with dynamic variable in it
                if (file_exists($fn)) {
                    $isex = true;
                }
            }
            if (strlen($data) > 1000) {
                if ($pos === false && $isex == false) {
                    $data = mb_substr($data, 0, 1000, "utf-8");
                    $data.= " ...";
                    $data.= '&nbsp;<a id="toggle_vid_' . $statusid . '" onclick="opentext(\'' . $statusid . '\',\'vid\')">See More</a>';
                    $data_old = '<div id="lessmore_vid_' . $statusid . '" class="lmml"><p id="status_text">' . $data_old . '&nbsp;<a id="toggle_vid_' . $statusid . '" onclick="opentext(\'' . $statusid . '\',\'vid\')">See Less</a></p></div>';
                } else {
                    $data_old = "";
                }
            } else {
                $data_old = "";
            }
            $data = nl2br($data);
            $data = str_replace("&amp;", "&", $data);
            $data = stripslashes($data);
            if (strpos($data, '<img src=\"permUploads/"') === true) {
                $data.= '<br>';
            }
            // Add share button
            $shareButton = "";
            if ($log_username != "" && $author != $log_username && $account_name != $log_username) {
                $shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus_vid(\'' . $statusid . '\',\'' . $vidi . '\');" id="shareBlink">';
            }
            $isLike = false;
            if ($user_ok == true) {
                $like_check = "SELECT id FROM video_status_likes WHERE username=? AND status=? LIMIT 1";
                $stmt = $conn->prepare($like_check);
                $stmt->bind_param("si", $log_username, $statusid);
                $stmt->execute();
                $stmt->store_result();
                $stmt->fetch();
                $numrows = $stmt->num_rows;
                if ($numrows > 0) {
                    $isLike = true;
                }
            }
            $stmt->close();
            // Add status like button
            $likeButton = "";
            $likeText = "";
            if ($isLike == true) {
                $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike_vid(\'unlike\',\'' . $statusid . '\',\'likeBtn_vid' . $statusid . '\',\'' . $vidi . '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
                $likeText = '<span style="vertical-align: middle;">Dislike</span>';
            } else {
                $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike_vid(\'like\',\'' . $statusid . '\',\'likeBtn_vid' . $statusid . '\',\'' . $vidi . '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a>';
                $likeText = '<span style="vertical-align: middle;">Like</span>';
            }
            // GATHER UP ANY STATUS REPLIES
            $status_replies = "";
            // all 1 line
            $sql2 = "SELECT a.*, u.avatar, u.country, u.lat, u.lon
    				 	FROM video_status AS a
    				 	LEFT JOIN users AS u ON u.username = a.author
    				 	WHERE a.osid = ?
    				 	AND a.type=?
    				 	ORDER BY a.postdate DESC";
            $stmt = $conn->prepare($sql2);
            $stmt->bind_param("is", $statusid, $b);
            $stmt->execute();
            $result2 = $stmt->get_result();
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $statusreplyid = $row2["id"];
                    $replyauthor = $row2["author"];
                    $replydata = $row2["data"];
                    $replydata = nl2br($replydata);
                    $replypostdate_ = $row2["postdate"];
                    $replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
                    $avatar2 = $row2["avatar"];
                    $replydata = str_replace("&amp;", "&", $replydata);
                    $replydata = stripslashes($replydata);
                    if ($avatar2 == NULL) {
                        $pcurl = '/images/avdef.png';
                    } else {
                        $pcurl = 'user/' . $replyauthor . '/' . $avatar2;
                    }
                    $flat = $row["lat"];
                    $flon = $row["lon"];
                    $ison = $row["online"];
                    $fuco = $row["country"];
                    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
                    $isonimg = '';
                    if ($ison == "yes") {
                        $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
                    } else {
                        $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
                    }
                    $funames = $replyauthor;
                    if (strlen($funames) > 20) {
                        $funames = mb_substr($funames, 0, 16, "utf-8");
                        $funames.= " ...";
                    }
                    if (strlen($fuco) > 20) {
                        $fuco = mb_substr($fuco, 0, 16, "utf-8");
                        $fuco.= " ...";
                    }
                    $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $replyauthor, $replyauthor, $one);
                    $stmt->execute();
                    $stmt->bind_result($numoffs);
                    $stmt->fetch();
                    $stmt->close();
                    $user_image_reply = '<a href="/user/' . urlencode($replyauthor) . '/"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; margin-bottom: 10px; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob"></div><div class="infotsrdiv"><div style="background-image: url(\'' . $pcurl . '\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
                    $data_old_reply = $row2["data"];
                    $data_old_reply = nl2br($data_old_reply);
                    $data_old_reply = str_replace("&amp;", "&", $data_old_reply);
                    $data_old_reply = stripslashes($data_old_reply);
                    $isex = false;
                    $sec_data = "";
                    $first_data = "";
                    if (strpos($data_old_reply, '<img src="/permUploads/') !== false) {
                        $split = explode('<img src="/permUploads/', $data_old_reply);
                        clearstatcache();
                        $sec_data = '<img src="/permUploads/' . $split[1];
                        $first_data = $split[0];
                        $img = str_replace('"', '', $split[1]); // remove double quotes
                        $img = str_replace('/>', '', $img); // remove img end tag
                        $img = str_replace(' ', '', $img); // remove spaces
                        $img = str_replace('<br>', '', $img); // remove spaces
                        $img = trim($img);
                        $fn = "permUploads/" . $img; // file name with dynamic variable in it
                        if (file_exists($fn)) {
                            $isex = true;
                        }
                    }
                    if (strlen($replydata) > 1000) {
                        if ($isex == false) {
                            $replydata = mb_substr($replydata, 0, 1000, "utf-8");
                            $replydata.= " ...";
                            $replydata.= '&nbsp;<a id="toggle_vid_r_' . $statusreplyid . '" onclick="opentext(\'' . $statusreplyid . '\',\'vid_r\')">See More</a>';
                            $data_old_reply = '<div id="lessmore_vid_r_' . $statusreplyid . '" class="lmml"><p id="status_text">' . $data_old_reply . '&nbsp;<a id="toggle_vid_r_' . $statusreplyid . '" onclick="opentext(\'' . $statusreplyid . '\',\'vid_r\')">See Less</a></p></div>';
                        } else {
                            $data_old_reply = "";
                        }
                    } else {
                        $data_old_reply = "";
                    }
                    $replydata = nl2br($replydata);
                    $replydata = str_replace("&amp;", "&", $replydata);
                    $replydata = stripslashes($replydata);
                    $replyDeleteButton = '';
                    if ($replyauthor == $log_username || $account_name == $log_username) {
                        $replyDeleteButton = '<span id="srdb_' . $statusreplyid . '"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\'' . $statusreplyid . '\',\'reply_vid_' . $statusreplyid . '\',\'vid\',\'' . $vidi . '\');" title="Delete Comment">X</button ></span>';
                    }
                    $agoformrply = time_elapsed_string($replypostdate_);
                    //$stmt->close();
                    $isLike_reply = false;
                    if ($user_ok == true) {
                        $like_check_reply = "SELECT id FROM video_reply_likes WHERE user=? AND reply=? LIMIT 1";
                        $stmt = $conn->prepare($like_check_reply);
                        $stmt->bind_param("si", $log_username, $statusreplyid);
                        $stmt->execute();
                        $stmt->store_result();
                        $stmt->fetch();
                        $numrows = $stmt->num_rows;
                        if ($numrows > 0) {
                            $isLike_reply = true;
                        }
                    }
                    $stmt->close();
                    // Add reply like button
                    $likeButton_reply = "";
                    $likeText_reply = "";
                    if ($isLike_reply == true) {
                        $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_vid(\'unlike\',\'' . $statusreplyid . '\',\'likeBtn_reply_vid' . $statusreplyid . '\',\'' . $vidi . '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
                        $likeText_reply = '<span style="vertical-align: middle;">Dislike</span>';
                    } else {
                        $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_vid(\'like\',\'' . $statusreplyid . '\',\'likeBtn_reply_vid' . $statusreplyid . '\',\'' . $vidi . '\')"><img src="/images/nf.png" width="18" height="18" title="Like" class="like_unlike"></a>';
                        $likeText_reply = '<span style="vertical-align: middle;">Like</span>';
                    }
                    // Count reply likes
                    $sql = "SELECT COUNT(id) FROM video_reply_likes WHERE reply = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $statusreplyid);
                    $stmt->execute();
                    $stmt->bind_result($rpycount);
                    $stmt->fetch();
                    $stmt->close();
                    $rpycl = '' . $rpycount;
                    $status_replies.= '
    					<div id="reply_vid_' . $statusreplyid . '" class="reply_boxes">
                            <div>' . $replyDeleteButton . '
                            <p id="float">
                                <b class="sreply">Reply: </b>
                                <b class="rdate">
                                    <span class="tooLong">' . $replypostdate . '</span> (' . $agoformrply . ' ago)
                                </b>
                            </p>' . $user_image_reply . '
                            <p id="reply_text">
                                <b class="sdata" id="hide_vid_r_' . $statusreplyid . '">' . $replydata . '' . $data_old_reply . '</b>
                            </p>

                            <hr class="dim">

                            <span id="likeBtn_reply_vid' . $statusreplyid . '" class="likeBtn">
                                ' . $likeButton_reply . '
                                <span style="vertical-align: middle;">'.$likeText_reply.'</span>
                            </span>
                            <div style="float: left; padding: 0px 10px 0px 10px;">
                                <b class="ispan" id="ipanr_vid_' . $statusreplyid . '">' . $rpycl . ' likes</b>
                            </div>
                            <div class="clear"></div>
                            </div>
                        </div>';
                }
            }
            // Count likes
            $sql = "SELECT COUNT(id) FROM video_status_likes WHERE status = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $statusid);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            $cl = '' . $count;
            // Count the replies
            $b = "b";
            $sql = "SELECT COUNT(id) FROM video_status WHERE type = ? AND osid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $b, $statusid);
            $stmt->execute();
            $stmt->bind_result($countrply);
            $stmt->fetch();
            $stmt->close();
            $crply = '' . $countrply;
            $showmore = "";
            if ($countrply > 0) {
                $showmore = '<div class="showrply"><a id="showreply_vid_' . $statusid . '" onclick="showReply(' . $statusid . ',' . $crply . ',\'vid\')">Show replies (' . $crply . ')</a></div>';
            }

            $dec = "";
                $urlId = "";
                if($row["type"] != "b"){
                    $dec = "post";
                    $urlId = "status";
                }else{
                    $dec = $urlId = "reply";
                }

            $pd = base64url_encode($vidi,$hshkey);
            $statvidl.= '<div id="status_' . $statusid . '" class="status_boxes">
                    <div>
                        <p id="status_date">
                            <b class="status_title">Post: </b>
                            <b class="pdate">
                                <span class="tooLong">' . $postdate . '</span> (' . $agoform . ' ago)
                            </b>
                        </p>' . $user_image_status . '
                        <div id="sdata_' . $statusid . '">
                            <p id="status_text"><b class="sdata" id="hide_vid_' . $statusid . '">' . $data . '' . $data_old . '</b>
                            </p>
                        </div>

                        <hr class="dim">

                        <span id="likeBtn_vid' . $statusid . '" class="likeBtn">
                            ' . $likeButton . '
                            <span style="vertical-align: middle;">Like</span>
                        </span>
                        <div class="shareDiv">
                            ' . $shareButton . '
                            <span style="vertical-align: middle;">Share</span>
                        </div>
                        <span class="indinf">
                            <a href="/video_zoom/'.$pd.'#'.$urlId.'_'.$statusid.'" style="color: #999; padding: 0px 10px 0px 10px; vertical-align: middle;">Video '.$dec.'</a>
                        </span>
                        <div style="float: left; padding: 0px 10px 0px 10px;"> 
                            <b class="ispan" id="ipan_vid_' . $statusid . '">' . $cl . ' likes</b>
                        </div>
                        <div class="clear"></div>
                </div>
                ' . $showmore . '<span id="allrply_vid_' . $statusid . '" class="hiderply">' . $status_replies . '</span>
                </div>';
            // all 1 line
            if ($isFriend == true && $row["type"] != "b") {
                $statvidl.= '<textarea id="replytext_vid_' . $statusid . '" class="replytext" onfocus="showBtnDiv_reply(\'' . $statusid . '\',\'vid\')" placeholder="Write a comment..."></textarea>';
                $statvidl.= '<div id="uploadDisplay_SP_reply_vid_' . $statusid . '"></div>';
                $statvidl.= '<div id="btns_SP_reply_vid_' . $statusid . '" class="hiddenStuff">';
                $statvidl.= '<span id="swithidbr_vid_' . $statusid . '"><button id="replyBtn_vid_' . $statusid . '" class="btn_rply" onclick="replyToStatus_vid(\'' . $statusid . '\',\'' . $u . '\',\'replytext_vid_' . $statusid . '\',this,\'' . $vidi . '\')">Reply</button></span>';
                $statvidl.= '<img src="/images/camera.png" id="triggerBtn_SP_reply_vid_" class="triggerBtnreply" onclick="triggerUpload_reply(event, \'fu_SP_reply_vid_\')" width="22" height="22" title="Upload A Photo" />';
                $statvidl.= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_reply(' . $statusid . ', \'vid\')">';
                $statvidl.= '<div class="clear"></div>';
                $statvidl.= generateEList($statusid, 'emojiBox_reply_vid_' . $statusid . '', 'replytext_vid_' . $statusid . '');
                $statvidl.= '</div>';
                $statvidl.= '<div id="standardUpload_reply" class="hiddenStuff">';
                $statvidl.= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
                $statvidl.= '<input type="file" name="FileUpload" id="fu_SP_reply_vid_" onchange="doUpload_reply(\'fu_SP_reply_vid_\', \'' . $statusid . '\')" accept="image/*"/>';
                $statvidl.= '</form>';
                $statvidl.= '</div>';
                $statvidl.= '<div class="clear"></div>';
		            }
		            array_push($newsfeed,$statvidl);
		        }
		        /*echo '<div class="test" style="display: none;">' . $statvidl . "</div>";*/
	            //exit();
	    } else {
	        $statvidl = "<p style='font-size: 14px; text-align: left;'>Your friends have not posted or replied anything yet ...</p>";
	    }
	    $statvidl.= '<br>';
	    $stmt->close();
	    
	    // Get group posts for news feed

        $idgr = "";
        if($cnt_near < 1){
            $gr_id = array();
            $sql = "SELECT s.gpost, COUNT(*) AS x FROM group_status_likes AS s LEFT JOIN users AS u ON u.username = s.username WHERE u.country = ? GROUP BY s.gpost ORDER BY x DESC LIMIT 65000";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s",$ucountry);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()){
                array_push($gr_id, $row["gpost"]);
            }
            $stmt->close();
            $idgr = join("','",$gr_id);
        }
	    
    if($friendsCSV != ""){
        $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
                FROM grouppost AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE s.author IN ('$friendsCSV') OR s.author IN('$loggedFoF') AND s.type=? AND s.author != ? LIMIT 6,65000";
    }else if($cnt_near > 0){
        $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
                FROM grouppost AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND s.type = ? AND s.author != ?
                GROUP BY s.author ORDER BY s.pdate DESC LIMIT 6,65000";
    }else if($idgr != ""){
        $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
                FROM grouppost AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE s.id IN ('$idphot')AND s.author != ? LIMIT 65000";
    }else{
        $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
                FROM grouppost AS s
                LEFT JOIN users AS u ON u.username = s.author WHERE s.author != ?
                ORDER BY RAND() LIMIT 65000";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
        $stmt->bind_param("ss",$zero, $log_username);
    }else if($cnt_near > 0){
        $stmt->bind_param("ssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $zero);
    }else{
        $stmt->bind_param("s", $log_username);
    }
  $stmt->execute();
  $result_new = $stmt->get_result();
  if ($result_new->num_rows > 0){
    while ($row = $result_new->fetch_assoc()) {
        $mainPosts = "";
      $g = $row["gname"];
      $post_id = $row["grouppost_id"];
      $post_auth = $row["author"];
      $post_type = $row["type"];
      $post_data = $row["data"];
      $post_date_ = $row["pdate"];
      $post_date = strftime("%R, %b %d, %Y", strtotime($post_date_));
      $post_avatar = $row["avatar"];
      $fuco = $row["country"];
  $ison = $row["online"];
  $flat = $row["lat"];
  $flon = $row["lon"];
  $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
  $isonimg = '';
  if($ison == "yes"){
      $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
  }else{
      $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
  }
  if($avatar != ""){
    $friend_pic = '/user/'.$post_auth.'/'.$avatar.'';
  } else {
    $friend_pic = '/images/avdef.png';
  }
  $funames = $post_auth;
  if(strlen($funames) > 20){
      $funames = mb_substr($funames, 0, 16, "utf-8");
      $funames .= " ...";
  }
  if(strlen($fuco) > 20){
      $fuco = mb_substr($fuco, 0, 16, "utf-8");
      $fuco .= " ...";
  }
  $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss",$post_auth,$post_auth,$one);
  $stmt->execute();
  $stmt->bind_result($numoffs);
  $stmt->fetch();
  $stmt->close();
      $avatar_pic = '/user/'.$post_auth.'/'.$post_avatar;
      $user_image = "";
      $agoform = time_elapsed_string($post_date_);
      if($post_auth == $log_username){
        $class = "round";
    }else{
        $class = "margin-bottom: 7px;";
    }

      if($post_avatar != NULL){
        $user_image = '<a href="/user/'.$post_auth.'"><div style="background-image: url(\''.$avatar_pic.'\'); background-repeat: no-repeat; background-size: cover; margin-bottom: 5px; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob"></div><div class="infostdiv"><div style="background-image: url(\''.$avatar_pic.'\'); background-repeat: no-repeat; float: left; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block;"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
      }else{
        $user_image = '<a href="/user/'.$post_auth.'"><img src="/images/avdef.png" alt="'.$post_auth.'" width="50" height="50" padding-bottom: 3px; margin-bottom: 5px;" style="'.$class.' tshov bbmob"><div class="infostdiv"><img src="'.$friend_pic.'"><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
      }

      $statusDeleteButton = '';
      if($post_auth == $log_username){
        $statusDeleteButton = '<span id="sdb_'.$post_id.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" onclick="return false;" onmousedown="deleteStatus(\''.$post_id.'\',\'status_'.$post_id.'\');" title="Delete Post And Its Replies">X</button></span> &nbsp; &nbsp;';
      }

      // Add share button
      $shareButton = "";
      if($log_username != "" && $post_auth != $log_username){
        $shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus_gr(\''.$post_id.'\',\''.$g.'\');" id="shareBlink">';
      }

      $isLike = false;
      if($user_ok == true){
        $like_check = "SELECT id FROM group_status_likes WHERE username=? AND gpost=? AND gname = ? LIMIT 1";
        $stmt = $conn->prepare($like_check);
        $stmt->bind_param("sis",$log_username,$post_id,$g);
        $stmt->execute();
        $stmt->store_result();
        $stmt->fetch();
        $numrows = $stmt->num_rows;
      if($numrows > 0){
              $isLike = true;
        }
        $stmt->close();
        }
      // Add status like button
      $likeButton = "";
      $likeText = "";
      if($isLike == true){
        $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike_gr(\'unlike\',\''.$post_id.'\',\'likeBtn_gr_'.$post_id.'\',\''.$g.'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
        $likeText = '<span style="vertical-align: middle;">Dislike</span>';
      }else{
        $likeButton = '<a href="#" onclick="return false;" onmousedown="toggleLike_gr(\'like\',\''.$post_id.'\',\'likeBtn_gr_'.$post_id.'\',\''.$g.'\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a>';
        $likeText = '<span style="vertical-align: middle;">Like</span>';
      }

      $post_data_old = $row["data"];
      $post_data_old = nl2br($post_data_old);
	    $post_data_old = str_replace("&amp;","&",$post_data_old);
	    $post_data_old = stripslashes($post_data_old);
      $pos = strpos($data_old,'<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
    		    
    $isex = false;
	$sec_data = "";
	$first_data = "";
	if(strpos($post_data_old,'<img src="/permUploads/') !== false){
	    $split = explode('<img src="/permUploads/',$post_data_old);
	    clearstatcache();
        $sec_data = '<img src="/permUploads/'.$split[1];
        $first_data = $split[0];
        $img = str_replace('"','',$split[1]); // remove double quotes
        $img = str_replace('/>','',$img); // remove img end tag
        $img = str_replace(' ','',$img); // remove spaces
        $img = str_replace('<br>','',$img); // remove spaces
        $img = trim($img);
        $fn = "permUploads/".$img; // file name with dynamic variable in it
        if(file_exists($fn)){
            $isex = true;
        }
	}
	if(strlen($post_data) > 1000){
	    if($pos === false && $isex == false){
		    $post_data = mb_substr($post_data, 0,1000, "utf-8");
			$post_data .= " ...";
			$post_data .= '&nbsp;<a id="toggle_gr_'.$post_id.'" onclick="opentext(\''.$post_id.'\',\'gr\')">See More</a>';
			$post_data_old = '<div id="lessmore_gr_'.$post_id.'" class="lmml"><p id="status_text">'.$post_data_old.'&nbsp;<a id="toggle_gr_'.$post_id.'" onclick="opentext(\''.$post_id.'\',\'gr\')">See Less</a></p></div>';
	    }else{
	        $post_data_old = "";
	    }
	}else{
		$post_data_old = "";
	}
        $post_data = nl2br($post_data);
		$post_data = str_replace("&amp;","&",$post_data);
		$post_data = stripslashes($post_data);
      // <b class="ispan">('.$cl.')</b> <span id="likeBtn">'.$likeButton.'</span> <div id="isornot_div">'.$isLikeOrNot.'</div>
      // '.$showmore.'<span id="allrply_'.$post_id.'" class="hiderply">'.$status_replies.'</span>
      
      // Get replies and user images using inner loop
      $status_replies = "";
      $sql_b = 'SELECT g.*, u.avatar, u.online, u.country, u.lat, u.lon
           FROM grouppost AS g
           LEFT JOIN users AS u ON u.username = g.author
          WHERE g.pid = ? AND g.type = ? ORDER BY g.pdate DESC';
      $stmt = $conn->prepare($sql_b);
      $stmt->bind_param("is",$post_id,$one);
      $stmt->execute();
      $result_old = $stmt->get_result();
      if($result_old->num_rows > 0){
        while ($row2 = $result_old->fetch_assoc()) {
          $statusreplyid = $row2["id"];
          $reply_auth = $row2["author"];
          $reply_data = $row2["data"];
          $reply_date_ = $row2["pdate"];
          $reply_date = strftime("%R, %b %d, %Y", strtotime($reply_date_));
          $reply_avatar = $row2["avatar"];
          $fucor = $row2["country"];
        $ison = $row2["online"];
        $flat = $row2["lat"];
        $flon = $row2["lon"];
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = '';
        if($ison == "yes"){
            $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
        }else{
            $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
        }
        if($avatar2 != ""){
          $friend_pic = '/user/'.$reply_auth.'/'.$avatar2.'';
        } else {
          $friend_pic = '/images/avdef.png';
        }
        $funames = $reply_auth;
        if(strlen($funames) > 20){
            $funames = mb_substr($funames, 0, 16, "utf-8");
            $funames .= " ...";
        }
        if(strlen($fucor) > 20){
            $fucor = mb_substr($fucor, 0, 16, "utf-8");
            $fucor .= " ...";
        }
        $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss",$reply_auth,$reply_auth,$one);
        $stmt->execute();
        $stmt->bind_result($numoffs);
        $stmt->fetch();
        $stmt->close();
          $re_avatar_pic = '/user/'.$reply_auth.'/'.$reply_avatar;
          if($reply_avatar != NULL){
            $reply_image = '<a href="/user/'.$reply_auth.'/"><div style="background-image: url(\''.$re_avatar_pic.'\'); background-repeat: no-repeat; background-size: cover; margin-bottom: 5px; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob"></div><div class="infotsrdiv"><div style="background-image: url(\''.$re_avatar_pic.'\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; float: left; display: inline-block;" class="tshov"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fucor.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
          }else{
            $reply_image = '<a href="/user/'.$reply_auth.'/"><img src="/images/avdef.png" alt="'.$reply_auth.'" style="margin-bottom: 5px;" width="50" height="50" class="tsrhov bbmob"><div class="infotsrdiv"><img src="'.$re_avatar_pic.'"><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fucor.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
          }

          $replyDeleteButton = '';
          if($reply_auth == $log_username){
            $replyDeleteButton = '<span id="srdb_'.$statusreplyid.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\''.$statusreplyid.'\',\'reply_gr_'.$statusreplyid.'\',\'group\',\''.$g.'\');" title="Delete Comment">X</button ></span>';
          }
          $agoformrply = time_elapsed_string($reply_date_);
          $data_old_reply = $row2["data"];
          $data_old_reply = nl2br($data_old_reply);
	    $data_old_reply = str_replace("&amp;","&",$data_old_reply);
	    $data_old_reply = stripslashes($data_old_reply);
          $isex = false;
    	$sec_data = "";
    	$first_data = "";
    	if(strpos($data_old_reply,'<img src="/permUploads/') !== false){
    	    $split = explode('<img src="/permUploads/',$data_old_reply);
    	    clearstatcache();
            $sec_data = '<img src="/permUploads/'.$split[1];
            $first_data = $split[0];
            $img = str_replace('"','',$split[1]); // remove double quotes
            $img = str_replace('/>','',$img); // remove img end tag
            $img = str_replace(' ','',$img); // remove spaces
            $img = str_replace('<br>','',$img); // remove spaces
            $img = trim($img);
            $fn = "permUploads/".$img; // file name with dynamic variable in it
            if(file_exists($fn)){
                $isex = true;
            }
    	}
		if(strlen($reply_data) > 1000){
		    if($isex == false){
			    $reply_data = mb_substr($reply_data, 0,1000, "utf-8");
				$reply_data .= " ...";
				$reply_data .= '&nbsp;<a id="toggle_gr_r_'.$statusreplyid.'" onclick="opentext(\''.$statusreplyid.'\',\'gr_r\')">See More</a>';
				$data_old_reply = '<div id="lessmore_gr_r_'.$statusreplyid.'" class="lmml"><p id="status_text">'.$data_old_reply.'&nbsp;<a id="toggle_gr_r_'.$statusreplyid.'" onclick="opentext(\''.$statusreplyid.'\',\'gr_r\')">See Less</a></p></div>';
		    }else{
		        $data_old_reply = "";
		    }
		}else{
			$data_old_reply = "";
		}
        $reply_data = nl2br($reply_data);
		$reply_data = str_replace("&amp;","&",$reply_data);
		$reply_data = stripslashes($reply_data);
          $isLike_reply = false;
          if($user_ok == true){
            $like_check_reply = "SELECT id FROM group_reply_likes WHERE username=? AND gpost=? AND gname=? LIMIT 1";
            $stmt = $conn->prepare($like_check_reply);
            $stmt->bind_param("sis",$log_username,$statusreplyid,$g);
            $stmt->execute();
            $stmt->store_result();
            $stmt->fetch();
            $numrows = $stmt->num_rows;
          if($numrows > 0){
                  $isLike_reply = true;
            }
          }
          
          // Add reply like button
          $likeButton_reply = "";
          $likeText_reply = "";
          if($isLike_reply == true){
            $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_gr(\'unlike\',\''.$statusreplyid.'\',\'likeBtn_reply_gr_'.$statusreplyid.'\',\''.$g.'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" title="Dislike"></a>';
            $likeText_reply = '<span style="vertical-align: middle;">Dislike</span>';
          }else{
            $likeButton_reply = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_gr(\'like\',\''.$statusreplyid.'\',\'likeBtn_reply_gr_'.$statusreplyid.'\',\''.$g.'\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a>';
            $likeText_reply = '<span style="vertical-align: middle;">Like</span>';
          }

            // Count reply likes
            $sql = "SELECT COUNT(id) FROM group_reply_likes WHERE gpost = ? AND gname = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is",$statusreplyid,$g);
            $stmt->execute();
            $stmt->bind_result($rpycount);
            $stmt->fetch();
            $stmt->close();
            $rpycl = ''.$rpycount;

          // Build replies
          $status_replies .= '
          <div id="reply_gr_'.$statusreplyid.'" class="reply_boxes">
            <div>'.$replyDeleteButton.'
            <p id="float">
                <b class="sreply">Replied: </b>
                <span class="tooLong">'.$reply_date.'</span> ('.$agoformrply.' ago)</b>
            </p>'.$reply_image.'
            <p id="reply_text">
                <b class="sdata" id="hide_gr_r_'.$statusreplyid.'">'.$reply_data.''.$data_old_reply.'</b>
            </p>

            <hr class="dim">

            <span id="likeBtn_reply_gr_'.$statusreplyid.'" class="likeBtn">
                '.$likeButton_reply.'
                <span style="vertical-align: middle;">'.$likeText_reply.'</span>
            </span>
            <div style="float: left; padding: 0px 10px 0px 10px;">
                <b class="ispan" id="ipan_gr_reply_'.$statusreplyid.'">'.$rpycl.' likes</b>
            </div>
            <div class="clear"></div>
            </div>
          </div>';
          //$stmt->close(); <b class="ispan">('.$rpycl.')</b><span id="likeBtn_reply">'.$likeButton_reply.'</span>
           // </div><div id="isornot_div_rly">'.$isRpyLikeOrNot.'</div>
        }
      }

      // Count likes
      $sql = "SELECT COUNT(id) FROM group_status_likes WHERE gname = ? AND gpost = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si",$g,$post_id);
      $stmt->execute();
      $stmt->bind_result($count);
      $stmt->fetch();
      $stmt->close();
      $cl = ''.$count;

      // Count the replies
      $sql = "SELECT COUNT(id) FROM grouppost WHERE type = ? AND gname = ? AND pid = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssi",$one,$g,$post_id);
      $stmt->execute();
      $stmt->bind_result($countrply);
      $stmt->fetch();
      $stmt->close();

      $crply = ''.$countrply;

      $showmore = "";
      if($countrply > 0){
        $showmore = '<div class="showrply"><a id="showreply_gr_'.$post_id.'" onclick="showReply('.$post_id.','.$crply.',\'gr\')">Show replies ('.$crply.')</a></div>';
      }
      
      if(strlen($post_auth) > 12){
        $post_auth = mb_substr($post_auth, 0, 8, "utf-8");
        $post_auth .= ' ...';
      }

      $dec = "";
        $urlId = "";
        if($row["type"] != "1"){
            $dec = "post";
            $urlId = "status";
        }else{
            $dec = $urlId = "reply";
        }

      // Build threads
      $mainPosts .= '<div id="status_'.$post_id.'" class="status_boxes">
                <div>'.$statusDeleteButton.'
                    <p id="status_date">
                        <b class="status_title">Post: </b>
                        <span class="tooLong">'.$post_date.'</span> ('.$agoform.' ago)</b>
                    </p>'.$user_image.'
                    <div id="sdata_'.$post_id.'">
                    <p id="status_text">
                        <b class="sdata" id="hide_gr_'.$post_id.'">'.$post_data.''.$post_data_old.'</b>
                    </p>
                </div>

                <hr class="dim">

                <span id="likeBtn_gr_'.$post_id.'" class="likeBtn">
                    '.$likeButton.'
                    <span style="vertical-align: middle;">Like</span>
                </span>
                <div class="shareDiv">
                    ' . $shareButton . '
                    <span style="vertical-align: middle;">Share</span>
                </div>
                <span class="indinf">
                    <a href="/group/'.$g.'/#'.$urlId.'_'.$post_id.'" style="color: #999; padding: 0px 10px 0px 10px; vertical-align: middle;">Group '.$dec.'</a>
                </span>
                <div style="float: left; padding: 0px 10px 0px 10px;">
                    <b class="ispan" id="ipan_gr_'.$post_id.'">'.$cl.' likes</b>
                </div>
                <div class="clear"></div>
            </div>
            '.$showmore.'<span id="allrply_gr_'.$post_id.'" class="hiderply">'.$status_replies.'</span>
            </div>';
      $mainPosts .= '</div><div class="clear">';
      // Time to build the Reply To section
      if($isFriend != false && $row["type"] != 1){
          $mainPosts .= '<textarea id="replytext_gr_'.$post_id.'" class="replytext" placeholder="Write a comment ..." onfocus="showBtnDiv_reply(\''.$post_id.'\',\'gr\')"></textarea><div class="clear"></div>';
      $mainPosts .= '<div id="uploadDisplay_SP_reply_gr_'.$post_id.'"></div>';
      $mainPosts .= '<div id="btns_SP_reply_gr_'.$post_id.'" class="hiddenStuff">';
        $mainPosts .= '<span id="swithidbr_gr_' . $post_id . '"><button id="replyBtn_gr_'.$post_id.'" class="btn_rply" onclick="replyPost(\''.$post_id.'\',\''.$g.'\')">Reply</button></span>';
        $mainPosts .= '<img src="/images/camera.png" id="triggerBtn_SP_reply_gr_" class="triggerBtnreply" onclick="triggerUpload_reply(event, \'fu_SP_reply_gr_\')" width="22" height="22" title="Upload A Photo" />';
        $mainPosts .= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_reply('.$post_id.', \'gr\')">';
        $mainPosts .= '<div class="clear"></div>';
        $mainPosts .= generateEList($post_id, 'emojiBox_reply_gr_' . $post_id . '', 'replytext_gr_' . $post_id . '');
      $mainPosts .= '</div>';
      $mainPosts .= '<div id="standardUpload_reply" class="hiddenStuff">';
        $mainPosts .= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
        $mainPosts .= '<input type="file" name="FileUpload" id="fu_SP_reply_gr_" onchange="doUpload_reply(\'fu_SP_reply_gr_\', \''.$post_id.'\')" accept="image/*"/>';
        $mainPosts .= '</form>';
      $mainPosts .= '</div>';
      $mainPosts .= '<div class="clear"></div>';
      array_push($newsfeed,$mainPosts);
    }
}
  }else{
    $mainPosts = "<p style='font-size: 14px !important; color: #999;'>There are no posts or comments recorded. Be the first one who post something!</p>";
  }

    if($_POST["num"] == 0){
        shuffle($newsfeed);
    }
    
    $cnt = count($newsfeed);
    
    if(isset($_POST["limit_min"]) && isset($_POST["limit_max"])){
        $chunk = "";
        if($limit_max > $cnt){
            $limit_max = $cnt;
        }
        $limit_min = mysqli_real_escape_string($conn, $_POST["limit_min"]);
        $limit_max = mysqli_real_escape_string($conn, $_POST["limit_max"]);
        for($i=$limit_min; $i<$limit_max; $i++){
            $chunk .= $newsfeed[$i];
        }
        echo $chunk;
        exit();
    }
?>