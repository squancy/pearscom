<?php
    require_once 'php_includes/check_login_statues.php';
    require_once 'php_includes/conn.php';
    require_once 'timeelapsedstring.php';
    require_once 'phpmobc.php';
    require_once 'safe_encrypt.php';
    require_once 'durc.php';
    require_once 'headers.php';
    require_once 'elist.php';
    require_once 'ccov.php';
	require_once 'php_includes/dist.php';
	
    $isfeed = false;
    $ismobile = mobc();
    $htmlTitle = "Connect us, connect the world";
    if (isset($log_username) && $log_username != "") {
        $isfeed = true;
        $htmlTitle = "Home";
        $newsfeed = "";
        $u = $log_username;
        // Set feedcheck in users table
        $sql = "UPDATE users SET feedcheck=NOW() WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $u);
        $stmt->execute();
        $stmt->close();
        $one = "1";
        $zero = "0";
        $a = "a";
        $b = "b";
        $c = "c";
        
        $blocked_array = array();
        // Select blocked users by the viewer
        $sql = "SELECT blocker FROM blockedusers WHERE blockee = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $log_username);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            array_push($blocked_array, $row["blocker"]);
        }
        
        $all_friends = array();
   
        // Select user's lat and lon
        $sql = "SELECT lat, lon FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $log_username);
        $stmt->execute();
        $stmt->bind_result($lat, $lon);
        $stmt->fetch();
        $stmt->close();
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
        $lat_m2 = $lat - 0.7;
        $lat_p2 = $lat + 0.7;
        $lon_m2 = $lon - 0.7;
        $lon_p2 = $lon + 0.7;

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
        $afSug = $all_friends;
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

        // Check if there are users nearby
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

        $val = "";
        $lmit = "";
        $statuslist = "";
        // Select posts from friends and followings
        if($friendsCSV != ""){
            $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                    FROM status AS s
                    LEFT JOIN users AS u ON u.username = s.author
                    WHERE s.author IN ('$friendsCSV') OR ((u.lat BETWEEN ? AND ?) AND (u.lon BETWEEN ? AND ?))
                    AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                    ORDER BY s.postdate DESC LIMIT 6";
        }else if($cnt_near > 0){
            $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                    FROM status AS s
                    LEFT JOIN users AS u ON u.username = s.author
                    WHERE (u.lat BETWEEN ? AND ?) AND (u.lon BETWEEN ? AND ?) AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                    ORDER BY s.postdate DESC LIMIT 6";
        }else{
            $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                    FROM status AS s
                    LEFT JOIN users AS u ON u.username = s.author
                    WHERE u.country = ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                    GROUP BY s.author ORDER BY s.postdate DESC LIMIT 6";
        }
        $stmt = $conn->prepare($sql);
        if($friendsCSV != ""){
            $stmt->bind_param("ssssssss",$lat_m2, $lat_p2, $lon_m2, $lon_p2,$a,$c,$b,$log_username);
        }else if($cnt_near > 0){
            $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,$b,$log_username);
        }else{
            $stmt->bind_param("sssss", $ucountry, $a, $c,$b,$log_username);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
                $user_image_status = '<a href="/user/' . $author . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; ' . $mgin . ' background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block; border-radius: 50%;" class="tshov bbmob lazy-bg"></div><div class="infostdiv"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left; border-radius: 50%;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
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
                    $shareButton = '<img src="/images/black_share.png" width="18" height="18" onclick="return false;" onmousedown="shareStatus(\'' . $statusid . '\');" id="shareBlink" style="vertical-align: middle;">';
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
                        $user_image_reply = '<a href="/user/' . urlencode($replyauthor) . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block; border-radius: 50%;" class="tsrhov bbmob lazy-bg"></div><div class="infotsrdiv"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left; border-radius: 50%;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
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
                            $isRpyLikeOrNot = "<p class='ilon'>You did not like this reply, yet</p>";
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
                $statuslist.= '
                    <div id="status_' . $statusid . '" class="status_boxes">
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
                                <span style="vertical-align: middle;">'.$likeText.'</span>
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
                if ($isFriend == true && $row["type"] != "b" && !in_array($author, $blocked_array)) {
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
                    $statuslist.= '<input type="file" name="FileUpload" id="fu_SP_reply_feed_" onchange="doUpload_reply(\'fu_SP_reply_feed_\', \'' . $statusid . '\', \'triggerBtn_SP_reply_feed_\')" accept="image/*"/>';
                    $statuslist.= '</form>';
                    $statuslist.= '</div>';
                    $statuslist.= '<div class="clear"></div>';
                }
            }
        } else {
            $statuslist = "<p>Recommended status posts from your friends & followings</p><p style='font-size: 14px;'>Your friends have not posted or replied anything recently. Check your <a href='/friend_suggestions'>friend suggestions</a> to get new friends or encourage them to post & reply more!</p>";
        }
        $statuslist.= "<hr class='dim'>";
        $stmt->close();
        // Get photos from friends & nearby users
        $gallery_list = "";
        if (!empty($all_friends)) {
            $sql = "SELECT * FROM photos WHERE user IN ('$friendsCSV') ORDER BY uploaddate LIMIT 15";
        } else if($cnt_near > 0) {
            // LIST PHOTOS NEARBY
            $sql = "SELECT u.*, p.* FROM users AS u LEFT JOIN photos AS p ON u.username = p.user WHERE (u.lat BETWEEN ? AND ?) AND (u.lon BETWEEN ? AND ?) AND p.user != ? ORDER BY RAND() LIMIT 15";
        }else{
            $sql = "SELECT p.*, u.country
                    FROM photos AS p
                    LEFT JOIN users AS u ON u.username = p.user
                    WHERE u.country = ? ORDER BY p.uploaddate DESC LIMIT 15";
        }
        $stmt = $conn->prepare($sql);
        if ($cnt_near > 0) {
            $stmt->bind_param("sssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);
        }else if($cnt_near < 1){
            $stmt->bind_param("s", $ucountry);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $uder = $row["user"];
            $fname = $row["filename"];
            $description = $row["description"];
            $timed = $row["uploaddate"];
            $udp = strftime("%R, %b %d, %Y", strtotime($timed));
            $uds = time_elapsed_string($timed);
            if (strlen($description) > 12) {
                $description = mb_substr($description, 0, 8, "utf-8");
                $description.= " ...";
            }
            $pcurl = '/user/' . $uder . '/' . $fname . '';
            list($width, $height) = getimagesize('user/' . $uder . '/' . $fname . '');
            $gallery_list.= "<a href='/photo_zoom/" . urlencode($uder) . "/" . $fname . "'><div class='pccanvas'><div class='lazy-bg' data-src=\"".$pcurl."\"><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>" . $width . " x " . $height . "</div></div></div></a>";
        }
        $stmt->close();
    }
    // $gallery_list.= '<div class="clear"></div><hr class="dim">';
    // Get photo posts
    $sql = "SELECT COUNT(id) FROM photos_status
                WHERE author IN ('$friendsCSV') AND (type=? OR type=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $a, $c);
    $stmt->execute();
    $stmt->bind_result($photorcnt);
    $stmt->fetch();
    $stmt->close();
    $statphol = "";
    if($friendsCSV != ""){
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM photos_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ?
                AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                ORDER BY s.postdate DESC LIMIT 6";
    }else if($cnt_near > 0){
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM photos_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                ORDER BY s.postdate DESC LIMIT 6";
    }else{
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM photos_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.country = ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                GROUP BY s.author ORDER BY s.postdate DESC LIMIT 6";
     }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
        $stmt->bind_param("ssssssss",$lat_m2, $lat_p2, $lon_m2, $lon_p2,$a,$c,$b,$log_username);
    }else if($cnt_near > 0){
        $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,$log_username);
    }else{
        $stmt->bind_param("sssss", $ucountry, $a, $c, $b,$log_username);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
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
            $user_image_status = '<a href="/user/' . $author . '/"><div style="background-repeat: no-repeat; ' . $mgin . ' background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block; border-radius: 50%;" class="tshov bbmob lazy-bg" data-src=\''.$pcurl.'\'></div><div class="infostdiv"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left; border-radius: 50%;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
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
                    $user_image_reply = '<a href="/user/' . urlencode($replyauthor) . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block; border-radius: 50%;" class="tsrhov bbmob lazy-bg"></div><div class="infotsrdiv"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left; border-radius: 50%;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
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
            };


            $dec = "";
            $urlId = "";
            if($row["type"] != "b"){
                $dec = "post";
                $urlId = "status";
            }else{
                $dec = $urlId = "reply";
            }

            $statphol.= '
                <div id="status_' . $statusid . '" class="status_boxes">
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
                            <span style="vertical-align: middle;">'.$likeText.'</span>
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
            if ($isFriend == true && $row["type"] != "b" && !in_array($author, $blocked_array)) {
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
                $statphol.= '<input type="file" name="FileUpload" id="fu_SP_reply_phot_" onchange="doUpload_reply(\'fu_SP_reply_phot_\', \'' . $statusid . '\', \'triggerBtn_SP_reply_phot_\')" accept="image/*"/>';
                $statphol.= '</form>';
                $statphol.= '</div>';
                $statphol.= '<div class="clear"></div>';
            }
        }
    } else {
        $statphol = "<p>Recommended photo posts from your friends & followings</p><p style='font-size: 14px;'>Your friends have not posted or replied anything recently. Check your <a href='/friend_suggestions'>friend suggestions</a> to get new friends or encourage them to post & reply more!</p>";
    }
    $statphol .= '<hr class="dim">';
    $stmt->close();
    $imgs = "";
    $inc = 0;
    // Get recent articles from friends
    $mx = 0;
    if ($ismobile == true) {
        $mx = 4;
    } else {
        $mx = 8;
    }
    $sugglist = "";
    if (!empty($all_friends)) {
        $sql = "SELECT * FROM articles WHERE written_by IN ('$friendsCSV') AND written_by != ? ORDER BY post_time DESC LIMIT $mx";
    } else if ($cnt_near > 0) {
        $sql = "SELECT u.*, a.* FROM users AS u LEFT JOIN articles AS a ON u.username = a.written_by WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND a.written_by != ? ORDER BY post_time DESC LIMIT $mx";
    }else{
        $sql = "SELECT a.*, u.country
                FROM articles AS a
                LEFT JOIN users AS u ON u.username = a.written_by
                WHERE u.country = ?
                GROUP BY a.written_by ORDER BY a.post_time DESC LIMIT $mx";
    }
    $stmt = $conn->prepare($sql);

    if(!empty($all_friends)){
        $stmt->bind_param("s", $u);
    } else if ($cnt_near > 0) {
        $stmt->bind_param("sssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);
    } else {
        $stmt->bind_param("s",$ucountry);
    }
    $stmt->execute();
    $result2 = $stmt->get_result();
    if($result2->num_rows > 0){
        while ($row = $result2->fetch_assoc()) {
            ++$inc;
            $wb = $row["written_by"];
            $tit = stripslashes($row["title"]);
            $content2 = stripslashes($row["content"]);
            $content_ma = str_replace("\n", '<br>', $content2);
            $content_ma = str_replace('\'', '&#39;', $content_ma);
            $content_ma = str_replace('\'', '&#34;', $content_ma);
            $ntc = 0;
            $img1 = $row["img1"];
            $img2 = $row["img2"];
            $img3 = $row["img3"];
            $img4 = $row["img4"];
            $img5 = $row["img5"];
            $imgs.= $img1 . "|";
            if ($img1 != "") {
                $pcurl = '/permUploads/' . $img1;
                $img1 = '<div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 60px; float: left; margin-right: 5px; margin-bottom: 5px;" class="lazy-bg"></div>';
                ++$ntc;
            }
            if ($img2 != "") {
                $pcurl = '/permUploads/' . $img2;
                $img2 = '<div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 60px; float: left; margin-right: 5px; margin-bottom: 5px;" class="lazy-bg"></div>';
                ++$ntc;
            }
            if ($img3 != "") {
                ++$ntc;
            }
            if ($img4 != "") {
                ++$ntc;
            }
            if ($img5 != "") {
                ++$ntc;
            }
            $tit = str_replace('\'', '&#39;', $tit);
            $tit = str_replace('\'', '&#34;', $tit);
            $tag = $row["tags"];
            $pt_ = $row["post_time"];
            $opt = $pt_;
            $pt = strftime("%b %d, %Y", strtotime($pt_));
            $pt_ = base64url_encode($pt_, $hshkey);
            $wb_ori = urlencode($wb);
            $cat = $row["category"];
            $num = 0;
            if ($ismobile != true) {
                $num = 16;
            } else {
                $num = 10;
            }
            if (strlen($wb) > 16) {
                $wb = mb_substr($wb, 0, 8, "utf-8");
                $wb.= " ...";
            }
            if (strlen($tit) > $num) {
                $tit = mb_substr($tit, 0, $num - 4, "utf-8");
                $tit.= " ...";
            }
            if (strlen($tag) > 14) {
                $tag = mb_substr($tag, 0, 16, "utf-8");
                $tag.= " ...";
            }
            $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ? AND art_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $log_username, $opt);
            $stmt->execute();
            $stmt->bind_result($cnt_fav);
            $stmt->fetch();
            $stmt->close();
            $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ? AND art_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $log_username, $opt);
            $stmt->execute();
            $stmt->bind_result($cnt_heart);
            $stmt->fetch();
            $stmt->close();
            $cover = chooseCover($cat);

            /*$sugglist .= '<a href="/articles/'.$pt_.'/'.$wb_ori.'"><div class="pcsmae">'.$cover.'<img src="/images/star.png" width="18" height="18"> <b>'.$cnt_fav.'</b><br><img src="/images/heart.png" width="17" height="17"> <b>'.$cnt_heart.'</b></div></a>';*/
            $sugglist.= '<div class="newsfar"><div id="pcbk"><b>Title: </b>' . $tit . '<br><b>Author: </b><a href="/user/' . $wb . '/">' . $wb . '</a><br><b>Publsihed: </b>' . $pt . '<br><b>Category: </b>' . $cat . '<br><a href="/articles/' . $pt_ . '/' . $wb_ori . '">Read article >>></a></div><div style="float: right;" class="pclti">' . $cover . '<img src="/images/star.png" style="width: 18px !important; height: 18px !important;"> <b>' . $cnt_fav . '</b><br><img src="/images/heart.png" style="width: 17px !important; height: 17px !important;"> <b>' . $cnt_heart . '</b></div><div class="clear"></div><hr class="dim"><div id="pcbkt"><div id="pcs_' . $inc . '" class="wrapCont">' . $content_ma . '</div></div></div>';
        }
    }else{
        $sugglist = "<p>Recommended articles from your friends & followings</p><p style='font-size: 14px;'>Your friends have not written any articles recently. Encourage them to write more, share their knowledge and entertain other people!</p>";
    }
    $stmt->close();
    // $sugglist.= '<div class="clear"></div><hr class="dim">';
    // Get article posts
    $sql = "SELECT COUNT(id)
                FROM article_status
                WHERE author IN ('$friendsCSV') AND (type=? OR type=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $a, $c);
    $stmt->execute();
    $stmt->bind_result($artrcnt);
    $stmt->fetch();
    $stmt->close();
    $statartl.= "";
    if($friendsCSV != ""){
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM article_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? 
                AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                 ORDER BY s.postdate DESC LIMIT 6";
    }else if ($cnt_near > 0){
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM article_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                 ORDER BY s.postdate DESC LIMIT 6";
    }else{
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM article_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.country = ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                GROUP BY s.author ORDER BY s.postdate DESC LIMIT 6";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
        $stmt->bind_param("ssssssss",$lat_m2, $lat_p2, $lon_m2, $lon_p2,$a,$c,$b,$log_username);
    }else if($cnt_near > 0){
        $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c, $b,$log_username);
    }else{
        $stmt->bind_param("sssss",$ucountry,$a,$c, $b,$log_username);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
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
            $user_image_status = '<a href="/user/' . $author . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; ' . $mgin . ' background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob lazy-bg"></div><div class="infostdiv"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
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
                    $user_image_reply = '<a href="/user/' . urlencode($replyauthor) . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob lazy-bg"></div><div class="infotsrdiv"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
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
            };

            $dec = "";
            $urlId = "";
            if($row["type"] != "b"){
                $dec = "post";
                $urlId = "status";
            }else{
                $dec = $urlId = "reply";
            }

            $statartl.= '
                <div id="status_' . $statusid . '" class="status_boxes">
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
                            <span style="vertical-align: middle;">'.$likeText.'</span>
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
            if ($isFriend == true && $row["type"] != "b" && !in_array($author, $blocked_array)) {
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
                $statartl.= '<input type="file" name="FileUpload" id="fu_SP_reply_art_" onchange="doUpload_reply(\'fu_SP_reply_art_\', \'' . $statusid . '\', \'triggerBtn_SP_reply_art_\')" accept="image/*"/>';
                $statartl.= '</form>';
                $statartl.= '</div>';
                $statartl.= '<div class="clear"></div>';
            }
        }
    } else {
        $statartl = "<p>Recommended article posts from your friends & followings</p><p style='font-size: 14px;'>Your friends have not posted or replied anything recently. Check your <a href='/friend_suggestions'>friend suggestions</a> to get new friends or encourage them to post & reply more!</p>";
    }
    $stmt->close();
    $statartl .= '<hr class="dim">';
    // Give friend suggestions
    // Initialize Some Things
    $moMoFriends = "";
    $their_friends = array();
    $myf = array();
    $countfs = 0;
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

    $foff = array();
    $loggedFoF = array_diff($loggedFoF, $afSug);
    $loggedFoF = join("','",$loggedFoF);
    $sql = "SELECT * FROM users WHERE username IN('$loggedFoF') AND activated = ? ORDER BY RAND() LIMIT 4";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$one);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $avatar = $row["avatar"];
        $country = $row["country"];
        $gender = $row["gender"];
        $uname = $row["username"];
        $unameori = urlencode($uname);
        $unamerq = $row["username"];
        array_push($foff, $unamerq);
        if (strlen($uname) > 14) {
            $uname = mb_substr($uname, 0, 10, "utf-8");
            $uname.= " ...";
        }
        $online = $row["online"];
        if ($online == "yes") {
            $online = "<b style='font-weight: normal; color: green;'>online</b>";
        } else {
            $online = "<b style='font-weight: normal; color: #999;'>offline</b>";
        }
         if ($avatar == NULL || $avatar == "") {
                $pcurl = '/images/avdef.png';
        } else {
            $pcurl = '/user/' . $unamerq . '/' . $avatar;
        }
        $moMoFriends.= '<div><a href="/user/' . $unameori . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; border-radius: 50%;" class="lazy-bg"></div></a><p style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"><a href="/user/' . $unameori . '/">' . $uname . '</a></div>';
    }
    $stmt->close();
    $myfriends = join("','", $my_friends);
    $foffi = join("','", $foff);
    $page_rows = $page_rows - $countfs;
    $geous = array();
        // LIST USERS NEARBY
        $sql = "SELECT * FROM users WHERE username NOT IN ('$curar') AND username NOT IN ('$foffi') AND lat BETWEEN ? AND ? AND lon BETWEEN ? AND ? AND username != ? AND activated = ? LIMIT 3";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username,$one);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $avatar = $row["avatar"];
            $country = $row["country"];
            $gender = $row["gender"];
            $uname = $row["username"];
            $unameori = urlencode($uname);
            $unamerq = $uname;
            array_push($geous, $unamerq);
            if (strlen($uname) > 14) {
                $uname = mb_substr($uname, 0, 10, "utf-8");
                $uname.= " ...";
            }
            $online = $row["online"];
            if ($online == "yes") {
                $online = "<b style='font-weight: normal; color: green;'>online</b>";
            } else {
                $online = "<b style='font-weight: normal; color: #999;'>offline</b>";
            }
            if ($avatar == NULL || $avatar == "") {
                $pcurl = '/images/avdef.png';
            } else {
                $pcurl = '/user/' . $unamerq . '/' . $avatar;
            }
            $moMoFriends.= '<div><a href="/user/' . $unameori . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; border-radius: 50%;" class="lazy-bg"></div></a><p style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"><a href="/user/' . $unameori . '/">' . $uname . '</a></p></div>';
        }
    $geos = join("','", $geous);

    $eaketto = array();
    // SELECT USER'S CITY
    $editarray = array();
    $sql = "SELECT * FROM edit WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        array_push($editarray, $row["job"], $row["about"], $row["profession"], $row["city"], $row["state"], $row["mobile"], $row["hometown"], $row["fav_movie"], $row["fav_music"], $row["elemen"], $row["high"], $row["uni"], $row["politics"], $row["religion"], $row["interest"], $row["language"], $row["degree"], $row["quotes"]);
    }
    $editarray = array_filter($editarray);
    $editarr = join("','", $editarray);
    $editarr = urlencode($editarr);
    $stmt->close();
        $sql = "SELECT u.username, u.country, u.avatar, u.gender FROM users AS u LEFT JOIN edit AS e ON u.username = e.username WHERE u.username != ? AND e.username != ? AND (e.job IN ('$editarr') OR e.about IN ('$editarr') OR e.profession IN ('$editarr') OR e.state IN ('$editarr') OR e.mobile IN ('$editarr') OR e.hometown IN ('$editarr') OR e.fav_movie IN ('$editarr') OR e.fav_music IN ('$editarr') OR e.elemen IN ('$editarr') OR e.high IN ('$editarr') OR e.uni IN ('$editarr') OR e.politics IN ('$editarr') OR e.religion IN ('$editarr') OR e.interest IN ('$editarr') OR e.language IN ('$editarr') OR e.degree IN ('$editarr') OR e.quotes IN ('$editarr')) AND u.username NOT IN ('$curar') AND e.username NOT IN ('$curar') AND u.username NOT IN ('$geos') AND e.username NOT IN ('$geos') AND u.username NOT IN ('$foffi') AND e.username NOT IN ('$foffi') AND activated = ? LIMIT 3";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $log_username, $log_username, $one);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $avatar = $row["avatar"];
            $country = $row["country"];
            $gender = $row["gender"];
            $uname = $row["username"];
            $unameori = urlencode($uname);
            $unamerq = $uname;
            array_push($eaketto, $unamerq);
            $online = $row["online"];
            if (strlen($uname) > 20) {
                $uname = mb_substr($uname, 0, 16, "utf-8");
                $uname.= " ...";
            }
            if ($online == "yes") {
                $online = "<b style='font-weight: normal; color: green;'>online</b>";
            } else {
                $online = "<b style='font-weight: normal; color: #999;'>offline</b>";
            }
            if ($avatar == NULL || $avatar == "") {
                $pcurl = '/images/avdef.png';
            } else {
                $pcurl = '/user/' . $unamerq . '/' . $avatar;
            }
            $moMoFriends.= '<div><a href="/user/' . $unameori . '/"><div class="lazy-bg" data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; border-radius: 50%;"></div></a><p style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;" class="lazy-bg"><a href="/user/' . $unameori . '/">' . $uname . '</a></p></div>';
        }
    
        $ageArr = array();

        $sql = "SELECT country, bday FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $log_username);
        $stmt->execute();
        $stmt->bind_result($log_c, $log_b);
        $stmt->fetch();
        $stmt->close();
        $log_b = mb_substr($log_b, 0, 4, "utf-8");
        // Query user's age to decide the maximum age difference
        $sql = "SELECT bday FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$log_username);
        $stmt->execute();
        $stmt->bind_result($loggedUserBday);
        $stmt->fetch();
        $stmt->close();

        if($log_b <= 15){
            $logbp2 = $log_b + 2;
            $logbm2 = $log_b - 2;
        }else if($log_b > 15 && $log_b <= 20){
            $logbp2 = $log_b + 4;
            $logbm2 = $log_b - 4;
        }else if($log_b > 20 && $log_b <= 30){
            $logbp2 = $log_b + 7;
            $logbm2 = $log_b - 7;
        }else if($log_b > 30 && $log_b <= 40){
            $logbp2 = $log_b + 10;
            $logbm2 = $log_b - 10;
        }else{
            $logbp2 = $log_b + 11;
            $logbm2 = $log_b - 11;
        }

        $logbp2 = $logbp2."-"."01-01";
        $logbm2 = $logbm2."-"."01-01";

        $sql = "SELECT * FROM users WHERE username NOT IN ('$curar') AND username NOT IN ('$foffi') AND username NOT IN ('$eaket') AND username NOT IN ('$geos') AND country = ? AND (bday BETWEEN ? AND ?) AND activated = ? $limit";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $log_c, $logbp2, $logbm2, $one);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $avatar = $row["avatar"];
            $country = $row["country"];
            $gender = $row["gender"];
            $uname = $row["username"];
            $unameori = urlencode($uname);
            $unamerq = $uname;
            $online = $row["online"];
            array_push($ageArr, $row["username"]);
            if (strlen($uname) > 20) {
                $uname = mb_substr($uname, 0, 16, "utf-8");
                $uname.= " ...";
            }
            if ($online == "yes") {
                $online = "<b style='font-weight: normal; color: green;'>online</b>";
            } else {
                $online = "<b style='font-weight: normal; color: #999;'>offline</b>";
            }
            if ($avatar == NULL || $avatar == "") {
                $pcurl = '/images/avdef.png';
            } else {
                $pcurl = '/user/' . $unamerq . '/' . $avatar;
            }
            $moMoFriends.= '<div><a href="/user/' . $unameori . '/"><div class="lazy-bg" data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; border-radius: 50%;"></div></a><p style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"><a href="/user/' . $unameori . '/">' . $uname . '</a></p></div>';
        }

    $ageArr = join("','",$ageArr);

        $sql = "SELECT * FROM users WHERE username NOT IN ('$curar') AND username NOT IN ('$foffi') AND username NOT IN ('$eaket') AND username NOT IN ('$geos') AND username NOT IN ('$ageArr') AND username != ? AND activated = ? ORDER BY RAND() $limit";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $log_username, $one);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $avatar = $row["avatar"];
            $country = $row["country"];
            $gender = $row["gender"];
            $uname = $row["username"];
            $unameori = urlencode($uname);
            $unamerq = $uname;
            $online = $row["online"];
            if (strlen($uname) > 20) {
                $uname = mb_substr($uname, 0, 16, "utf-8");
                $uname.= " ...";
            }
            if ($online == "yes") {
                $online = "<b style='font-weight: normal; color: green;'>online</b>";
            } else {
                $online = "<b style='font-weight: normal; color: #999;'>offline</b>";
            }
            if ($avatar == NULL || $avatar == "") {
                $pcurl = '/images/avdef.png';
            } else {
                $pcurl = '/user/' . $unamerq . '/' . $avatar;
            }
            $moMoFriends.= '<div><a href="/user/' . $unameori . '/"><div class="lazy-bg" data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; border-radius: 50%;"></div></a><p style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"><a href="/user/' . $unameori . '/">' . $uname . '</a></p></div>';
        }

    if($moMoFriends == "<p>Friend suggestions & people who may like</p>"){
        $moMoFriends = "<p>Friend suggestions & people who may like</p><p style='font-size: 14px;'>Unfortunately, there are no available friend suggestions. Come back later ...";
    }
    // $moMoFriends.= '<span><div class="clear"></div></span><hr class="dim">';
    // Get videos for news feed
    $relvids = "";
    if($friendsCSV != ""){
        $sql = "SELECT * FROM videos WHERE user IN('$friendsCSV') ORDER BY RAND() LIMIT 12";
    }else if($cnt_near > 0){
        $sql = "SELECT v.* FROM videos AS v LEFT JOIN users AS u ON u.username = v.user WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? ORDER BY v.video_upload DESC LIMIT 12";
    }else{
        $sql = "SELECT v.*, u.country
                FROM videos AS v
                LEFT JOIN users AS u ON u.username = v.user
                WHERE u.country = ?
                GROUP BY v.user ORDER BY v.video_upload DESC LIMIT 6";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
        $stmt->bind_param("ss",$a,$c);
    }else if($cnt_near > 0){
        $stmt->bind_param("ssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2);
    }else{
        $stmt->bind_param("s",$ucountry);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        while ($row = $result->fetch_assoc()) {
            $vid = $row["id"];
            $vid = base64url_encode($vid, $hshkey);
            $vuser = $row["user"];
            $vvname = $row["video_name"];
            $vdescription = $row["video_description"];
            $vposter = $row["video_poster"];
            $vfile = $row["video_file"];
            $vdate_ = $row["video_upload"];
            $vdate = strftime("%b %d, %Y", strtotime($vdate_));
            $dur = $row["dur"];
            $dur = convDur($dur);
            if ($vvname == NULL) {
                $vvname = "Untitiled";
            }
            if (strlen($vvname) > 18) {
                $vvname = mb_substr($vvname, 0, 14, "utf-8");
                $vvname.= " ...";
            }
            if ($vposter != NULL) {
                $pcurlo = '/user/' . $vuser . '/videos/' . $vposter . '';
            } else {
                $pcurlo = '/images/defaultimage.png';
            }
            $uds = time_elapsed_string($vdate_);
            $relvids.= "<a href='/video_zoom/" . $vid . "'><div class='nfrelv'><div data-src=\"".$pcurlo."\" class='lazy-bg' id='pcgetc'></div><div class='pcjti'>" . $vvname . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 15px;'>" . $dur . "</div></div></a>";
        }
    }else{
        $relvids = "<p>Recommended videos from ".$part."</p><p style='font-size: 14px;'>Your friends have not uploaded any videos yet. Encourage them to upload videos & share their memories by sending them a private message!";
    }
    // $relvids.= '<div class="clear"></div><hr class="dim">';
    $stmt->close();
    // Get video status
    $statvidl.= "";
    if($friendsCSV != ""){
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM video_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ?
                AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                ORDER BY s.postdate DESC LIMIT 6";
    }else if($cnt_near > 0){
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM video_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                 ORDER BY s.postdate DESC LIMIT 6";
    }else{
        $sql = "SELECT s.*, u.avatar, u.online, u.country, u.lat, u.lon
                FROM video_status AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.country = ? AND (s.type=? OR s.type=? OR s.type = ?) AND s.author != ?
                GROUP BY s.author ORDER BY s.postdate DESC LIMIT 6";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
        $stmt->bind_param("ssssssss",$lat_m2, $lat_p2, $lon_m2, $lon_p2,$a,$c,$b,$log_username);
    }else if($cnt_near > 0){
        $stmt->bind_param("ssssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $a, $c,$b,$log_username);
    }else{
        $stmt->bind_param("ssss",$ucountry,$a,$c,$b,$log_username);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusid = $row["id"];
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
            $user_image_status = '<a href="/user/' . $author . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; margin-bottom: 10px; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob lazy-bg"></div><div class="infostdiv"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
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
                    $user_image_reply = '<a href="/user/' . urlencode($replyauthor) . '/"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; margin-bottom: 10px; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob lazy-bg"></div><div class="infotsrdiv"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>' . $funames . '</u>&nbsp;' . $isonimg . '<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;' . $fuco . '<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: ' . $dist . ' miles<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: ' . $numoffs . '</span></div></a>';
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
            };
            $pd = base64url_encode($vidi,$hshkey);

            $dec = "";
            $urlId = "";
            if($row["type"] != "b"){
                $dec = "post";
                $urlId = "status";
            }else{
                $dec = $urlId = "reply";
            }

            $statvidl.= '
                <div id="status_' . $statusid . '" class="status_boxes">
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
                            <span style="vertical-align: middle;">'.$likeText.'</span>
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
            if ($isFriend == true && $row["type"] != "b" && !in_array($author, $blocked_array)) {
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
                $statvidl.= '<input type="file" name="FileUpload" id="fu_SP_reply_vid_" onchange="doUpload_reply(\'fu_SP_reply_vid_\', \'' . $statusid . '\', \'triggerBtn_SP_reply_vid_\')" accept="image/*"/>';
                $statvidl.= '</form>';
                $statvidl.= '</div>';
                $statvidl.= '<div class="clear"></div>';
            }
        }
    } else {
        $statvidl = "<p>Recommended video posts from your friends & followings</p><p style='font-size: 14px;'>Your friends have not posted or replied anything recently. Check your <a href='/friend_suggestions'>friend suggestions</a> to get new friends or encourage them to post & reply more!</p>";
    }
    $statvidl.= '<hr class="dim">';
    $stmt->close();
    
    // Get group posts for news feed
    $mainPosts = "";
    if($friendsCSV != ""){
        $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
                FROM grouppost AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE s.author IN ('$friendsCSV') OR u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ?
                AND (s.type=? OR s.type = ?) AND s.author != ?
                 ORDER BY s.pdate DESC LIMIT 6";
    }else if($cnt_near > 0){
        $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
                FROM grouppost AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND (s.type=? OR s.type = ?) AND s.author != ?
                 ORDER BY s.pdate DESC LIMIT 6";
    }else{
        $sql = "SELECT s.*, s.id AS grouppost_id, u.avatar, u.online, u.country, u.lat, u.lon
                FROM grouppost AS s
                LEFT JOIN users AS u ON u.username = s.author
                WHERE u.country = ? AND (s.type=? OR s.type = ?) AND s.author != ?
                GROUP BY s.author ORDER BY s.pdate DESC LIMIT 6";
    }
    $stmt = $conn->prepare($sql);
    if($friendsCSV != ""){
        $stmt->bind_param("sssssss",$lat_m2, $lat_p2, $lon_m2, $lon_p2,$zero,$one,$log_username);
    }else if($cnt_near > 0){
        $stmt->bind_param("sssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $zero,$one,$log_username);
    }else{
        $stmt->bind_param("ssss", $ucountry, $zero,$one,$log_username);
    }
  $stmt->execute();
  $result_new = $stmt->get_result();
  if ($result_new->num_rows > 0){
    while ($row = $result_new->fetch_assoc()) {
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
        $user_image = '<a href="/user/'.$post_auth.'"><div data-src=\''.$avatar_pic.'\' style="background-repeat: no-repeat; background-size: cover; margin-bottom: 5px; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tshov bbmob lazy-bg"></div><div class="infostdiv"><div data-src=\''.$avatar_pic.'\' style="background-repeat: no-repeat; float: left; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block;" class="lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
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
            $reply_image = '<a href="/user/'.$reply_auth.'/"><div data-src=\''.$re_avatar_pic.'\' style="background-repeat: no-repeat; background-size: cover; margin-bottom: 5px; background-position: center; width: 50px; height: 50px; display: inline-block;" class="tsrhov bbmob lazy-bg"></div><div class="infotsrdiv"><div data-src=\''.$re_avatar_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; float: left; display: inline-block;" class="tshov lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fucor.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
          }else{
            $reply_image = '<a href="/user/'.$reply_auth.'/"><img src="/images/avdef.png" alt="'.$reply_auth.'" style="margin-bottom: 5px;" width="50" height="50" class="tsrhov bbmob lazy-bg"><div class="infotsrdiv"><img src="'.$re_avatar_pic.'"><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fucor.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
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
      $mainPosts .= '
            <div id="status_'.$post_id.'" class="status_boxes">
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
                    <span style="vertical-align: middle;">'.$likeText.'</span>
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
      if($isFriend == true && $row["type"] != 1 && !in_array($post_auth, $blocked_array)){
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
        $mainPosts .= '<input type="file" name="FileUpload" id="fu_SP_reply_gr_" onchange="doUpload_reply(\'fu_SP_reply_gr_\', \''.$post_id.'\', \'triggerBtn_SP_reply_gr_\')" accept="image/*"/>';
        $mainPosts .= '</form>';
      $mainPosts .= '</div>';
      $mainPosts .= '<div class="clear"></div>';
    }
}
  }else{
    $mainPosts = "<p>Recommended group posts from your friends & followings</p><p style='font-size: 14px;'>Your friends have not posted or replied anything yet recently. Check your <a href='/friend_suggestions'>friend suggestions</a> to get new friends or encourage them to post & reply more!</p>";
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pearscom - <?php echo $htmlTitle; ?></title>
  <meta charset="utf-8">
  <meta lang="en">
  <meta name="robots" content="index, follow">
  <meta name="copyright" content="Pearscom">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="shortcut icon" href="/images/newfav.png" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Welcome to Pearscom! Sign up or log in - if you already own an account - and upload your photos, videos, write articles, get new friends and message with them. Start exploring Pearscom now!">
  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
  <script src="/js/jjs.js" async></script>
    <script src="/js/main.js"></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/lload.js"></script>
  <meta name="author" content="Pearscom">
  <meta name="keywords" content="Pearscom, pearscom, pear, pears, pearscom welcome, connect us, connect the world, pears community, pearscommunity, pearscomm">
  <script type="application/ld+json"> { "@context" : "http://schema.org", "@type" : "Article", "name" : "Pearscom", "author" : { "@type" : "Person", "name" : "Pearscom, Mark Frankli" }, "image" : "https://www.pearscom.com/images/newfav.png", "articleSection" : "Keep contact with your friends", "articleBody" : "Pearscom helps you to keep contant with your friends and in sharing your joys and sorrows with other people", "url" : "http://www.pearscom.com/", "publisher" : { "@type" : "Organization", "name" : "Pearscom" } } </script>
  <style type="text/css">
      @media only screen and (max-width: 768px){
        #logacccooks{
          margin-top: 36px !important;
      }
      }
  </style>
<script type="text/javascript">
    var ifed = "<?php echo $isfeed; ?>";
    var hasImage = "";

    function doUpload_reply(e, o, w) {
        var t = _(e).files[0];
        if ("" == t.name) return !1;
        if ("image/jpeg" != t.type && "image/gif" != t.type && "image/png" != t.type && "image/jpg" != t.type) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        _(w).style.display = "none";
        var l = new FormData;
        l.append("stPic_reply", t);
        var i = new XMLHttpRequest;
        i.upload.addEventListener("progress", progressHandler_reply, !1), i.addEventListener("load", completeHandler_reply, !1), i.addEventListener("error", errorHandler_reply, !1), i.addEventListener("abort", abortHandler_reply, !1), i.open("POST", "/php_parsers/photo_system.php"), i.send(l)
    }

    function progressHandler_reply(e) {
        var o = e.loaded / e.total * 100,
            t = "<p>" + Math.round(o) + "% uploaded please wait ...</p>";
        _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = "<b>Your uploading image status</b><p>" + t + "</p>", document.body.style.overflow = "hidden"
    }
    
    if('serviceWorker' in navigator){
        navigator.serviceWorker.register('/sw.js')
            .then(function(){
                console.log('Worker Registered');
            });
    }

    function completeHandler_reply(o) {
        var t = o.target.responseText.split("|");
        "upload_complete_reply" == t[0] ? (hasImage = t[1], _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Your uploading image</p><p>You have successfully uploaded your image. Click on the <i>Close</i> button and now you can post your reply.</p><img src="tempUploads/' + t[1] + '" class="statusImage"><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("uploadDisplay_SP_reply_" + e).innerHTML = t[0])
    }

    function errorHandler_reply(e) {
        _('overlay').style.opacity = 0.5;
        _('dialogbox').style.display = 'block';
        _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">An unknown error has occured</p><p>Unfortunately an unknown error has occured meanwhile your uploading. Please try again later.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = 'hidden';
    }

    function abortHandler_reply(e) {
        _('overlay').style.opacity = 0.5;
        _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">Your image has been aborted</p><p>Unfortunately your image has been aborted meanwhile uploading. Please try again later.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = 'hidden';
    }

    function replyToStatus(e, o, t, l) {
        var i = _(t).value;
        if ("" == i && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var r = "";
        "" != i && (r = i.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == r && "" != hasImage ? (i = "||na||", r = '<img src="permUploads/' + hasImage + '" />') : "" != r && "" != hasImage ? r += '<br /><img src="permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_feed_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var s = ajaxObj("POST", "/php_parsers/status_system.php");
        s.onreadystatechange = function() {
            if (1 == ajaxReturn(s)) {
                var o = s.responseText.split("|");
                if ("reply_ok" == o[0]) {
                    var l = o[1];
                    i = i.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />"), _("status_" + e).innerHTML += '<div id="reply_' + l + '" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_" + l + "','feed', 'feed');\" title=\"Delete Comment\">X</button></span><br />" + r + "</div></div>", _("swithidbr_feed_" + e).innerHTML = '<button id="replyBtn_feed_' + e + '" class="btn_rply" onclick="replyToStatus(\'' + e + "','<?php echo $u; ?>','replytext_feed_" + e + "',this)\">Reply</button>", _(t).value = "", _("triggerBtn_SP_reply_feed_").style.display = "block", _("btns_SP_reply_feed_" + e).style.display = "none", _("uploadDisplay_SP_reply_feed_" + e).innerHTML = "", _("replytext_feed_" + e).style.height = "40px", _("fu_SP_reply_feed_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, s.send("action=status_reply&sid=" + e + "&user=" + o + "&data=" + i + "&image=" + hasImage)
    }

    function deleteReply(e, o, t, l) {
        if (1 != confirm("Are you sure you want to delete this reply?")) return !1;
        var i = t;
        "phot" == t ? t = "photo_status_system" : "art" == t ? (i = "arid", t = "article_status_system") : t = "vid" == t ? "video_status_parser" : "feed" == t ? "status_system" : "group_parser2";
        var r = ajaxObj("POST", "/php_parsers/" + t + ".php");
        r.onreadystatechange = function() {
            1 == ajaxReturn(r) && ("delete_ok" == r.responseText ? _(o).style.display = "none" : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status post deletion. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, r.send("action=delete_reply&replyid=" + e + "&" + i + "=" + l)
    }

    function toggleLike(e, o, t) {
        var l = ajaxObj("POST", "/php_parsers/like_system.php");
        l.onreadystatechange = function() {
            if (1 == ajaxReturn(l))
                if ("like_success" == l.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\'' + o + "','likeBtn_feed_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipanf_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == l.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\'' + o + "','likeBtn_feed_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanf_" + o).innerText = --e + " likes"
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, l.send("type=" + e + "&id=" + o)
    }

    function toggleLike_reply(e, o, t) {
        var l = ajaxObj("POST", "/php_parsers/like_reply_system.php");
        l.onreadystatechange = function() {
            if (1 == ajaxReturn(l))
                if ("like_reply_success" == l.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\'' + o + "','likeBtn_reply_feed_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipanr_" + o).innerText = ++e + " likes";
                } else if ("unlike_reply_success" == l.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\'' + o + "','likeBtn_reply_feed_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanr_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", _(t).innerHTML = "Try again later"
        }, l.send("type=" + e + "&id=" + o)
    }
    window.onbeforeunload = function() {
        if ("" != hasImage) return "You have not posted your image"
    };
    var us = "less";

    function showReply(e, o, t) {
        "less" == us ? (_("showreply_" + t + "_" + e).innerText = "Hide replies (" + o + ")", _("allrply_" + t + "_" + e).style.display = "block", us = "more") : "more" == us && (_("showreply_" + t + "_" + e).innerText = "Show replies (" + o + ")", _("allrply_" + t + "_" + e).style.display = "none", us = "less")
    }

    function closeDialog() {
        _("dialogbox").style.display = "none", _("overlay").style.display = "none", _("overlay").style.opacity = 0, document.body.style.overflow = "auto"
    }

    function shareStatus(e) {
        var o = ajaxObj("POST", "/php_parsers/status_system.php");
        o.onreadystatechange = function() {
            1 == ajaxReturn(o) && ("share_ok" == o.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, o.send("action=share&id=" + e)
    }

    function shareStatus_gr(e, o) {
        var t = ajaxObj("POST", "/php_parsers/group_parser2.php");
        t.onreadystatechange = function() {
            1 == ajaxReturn(t) && ("share_ok" == t.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, t.send("action=share_status&id=" + e + "&group=" + o)
    }
    hasImage = "";

    function openEmojiBox_reply(e, id) {
        var o = _("emojiBox_reply_" + id + "_" + e);
        "block" == o.style.display ? o.style.display = "none" : o.style.display = "block"
    }

    function insertEmoji(e, o) {
        var t = document.getElementById(e);
        if (t) {
            var l = t.scrollTop,
                i = 0,
                r = t.selectionStart || "0" == t.selectionStart ? "ff" : !!document.selection && "ie";
            if ("ie" == r) {
                t.focus();
                var s = document.selection.createRange();
                s.moveStart("character", -t.value.length), i = s.text.length
            } else "ff" == r && (i = t.selectionStart);
            var n = t.value.substring(0, i),
                a = t.value.substring(i, t.value.length);
            if (t.value = n + o + a, i += o.length, "ie" == r) {
                t.focus();
                var p = document.selection.createRange();
                p.moveStart("character", -t.value.length), p.moveStart("character", i), p.moveEnd("character", 0), p.select()
            } else "ff" == r && (t.selectionStart = i, t.selectionEnd = i, t.focus());
            t.scrollTop = l
        }
    }

    function triggerUpload_reply(e, o) {
        e.preventDefault(), _(o).click()
    }

    function replyToStatus_art(e, o, t, l, i) {
        var r = _(t).value;
        if ("" == r && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var s = "";
        "" != r && (s = r.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == s && "" != hasImage ? (r = "||na||", s = '<img src="/permUploads/' + hasImage + '" />') : "" != s && "" != hasImage ? s += '<br /><img src="/permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_art_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var n = ajaxObj("POST", "/php_parsers/article_status_system.php");
        n.onreadystatechange = function() {
            if (1 == ajaxReturn(n)) {
                var o = n.responseText.split("|");
                if ("reply_ok" == o[0]) {
                    var l = o[1];
                    r = r.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />"), _("status_" + e).innerHTML += '<div id="reply_art_' + l + '" class="reply_boxes_" style="font-size: 14px !important;"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_art_" + l + "', 'art', '" + i + '\');" title="Delete Comment">X</button></span><br />' + s + "</div></div>", _("swithidbr_art_" + e).innerHTML = '<button id="replyBtn_art_' + e + '" class="btn_rply" onclick="replyToStatus_art(\'' + e + "','<?php echo $u; ?>','replytext_art_" + e + "',this,'" + i + "')\">Reply</button>", _(t).value = "", _("triggerBtn_SP_reply_art_").style.display = "block", _("btns_SP_reply_art_" + e).style.display = "none", _("uploadDisplay_SP_reply_art_" + e).innerHTML = "", _("replytext_art_" + e).style.height = "40px", _("fu_SP_reply_art_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, n.send("action=status_reply&sid=" + e + "&user=" + o + "&data=" + r + "&image=" + hasImage + "&arid=" + i)
    }

    function replyToStatus_phot(e, o, t, l, i) {
        var r = _(t).value;
        if ("" == r && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var s = "";
        "" != r && (s = r.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == s && "" != hasImage ? (r = "||na||", s = '<img src="/permUploads/' + hasImage + '" />') : "" != s && "" != hasImage ? s += '<br /><img src="/permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_phot_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var n = ajaxObj("POST", "/php_parsers/photo_status_system.php");
        n.onreadystatechange = function() {
            if (1 == ajaxReturn(n)) {
                var o = n.responseText.split("|");
                if ("reply_ok" == o[0]) {
                    var l = o[1];
                    r = r.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />"), _("status_" + e).innerHTML += '<div id="reply_phot_' + l + '" class="reply_boxes_" style="font-size: 14px !important;"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_phot_" + l + "', 'phot', '" + i + '\');" title="Delete Comment">X</button></span><br />' + s + "</div></div>", _("swithidbr_phot_" + e).innerHTML = '<button id="replyBtn_phot_' + e + '" class="btn_rply" onclick="replyToStatus_phot(\'' + e + "','<?php echo $u; ?>','replytext_phot_" + e + "',this,'" + i + "')\">Reply</button>", _(t).value = "", _("triggerBtn_SP_reply_phot_").style.display = "block", _("btns_SP_reply_phot_" + e).style.display = "none", _("uploadDisplay_SP_reply_phot_" + e).innerHTML = "", _("replytext_phot_" + e).style.height = "40px", _("fu_SP_reply_phot_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, n.send("action=status_reply&sid=" + e + "&user=" + o + "&data=" + r + "&image=" + hasImage + "&phot=" + i)
    }

    function replyPost(e, o) {
        var t = "replytext_gr_" + e,
            l = _(t).value;
        if ("" == l && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var i = "";
        "" != l && (i = l.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == i && "" != hasImage ? (l = "||na||", i = '<img src="/permUploads/' + hasImage + '" />') : "" != i && "" != hasImage ? i += '<br /><img src="/permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_gr_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var r = ajaxObj("POST", "/php_parsers/group_parser2.php");
        r.onreadystatechange = function() {
            if (1 == ajaxReturn(r)) {
                var l = r.responseText.split("|");
                if ("reply_ok" == l[0]) {
                    var s = l[1];
                    _("status_" + s).innerHTML += '<div id="reply_gr_' + s + '" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_' + s + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + s + "','reply_gr_" + s + "','group','" + o + '\');" title="Delete Comment">X</button></span><br />' + i + "</div></div><br /><br />", _("swithidbr_gr_" + e).innerHTML = '<button id="replyBtn_gr_' + e + '" class="btn_rply" onclick="replyPost(\'' + e + "','" + o + "')\">Reply</button>", _(t).value = "", _("replyBtn_gr_" + e).disabled = !1, _(t).value = "", _("triggerBtn_SP_reply_gr_").style.display = "block", _("btns_SP_reply_gr_" + e).style.display = "none", _("replytext_gr_" + e).style.height = "40px", _("fu_SP_reply_gr_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, r.send("action=post_reply&sid=" + e + "&data=" + l + "&g=" + o + "&image=" + hasImage)
    }

    function replyToStatus_vid(e, o, t, l, i) {
        var r = _(t).value;
        if ("" == r && "" == hasImage) return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", !1;
        var s = "";
        "" != r && (s = r.replace(/\n/g, "<br />").replace(/\r/g, "<br />")), "" == s && "" != hasImage ? (r = "||na||", s = '<img src="/permUploads/' + hasImage + '" />') : "" != s && "" != hasImage ? s += '<br /><img src="/permUploads/' + hasImage + '" />' : hasImage = "na", _("swithidbr_vid_" + e).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
        var n = ajaxObj("POST", "/php_parsers/video_status_parser.php");
        n.onreadystatechange = function() {
            if (1 == ajaxReturn(n)) {
                var o = n.responseText.split("|");
                if ("reply_ok" == o[0]) {
                    var l = o[1];
                    r = r.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />"), _("status_" + e).innerHTML += '<div id="reply_vid_' + l + '" class="reply_boxes_" style="font-size: 14px !important;"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_vid_" + l + "', 'vid', '" + i + '\');" title="Delete Comment">X</button></span><br />' + s + "</div></div><br /><br /><br />", _("swithidbr_vid_" + e).innerHTML = '<button id="replyBtn_vid_' + e + '" class="btn_rply" onclick="replyToStatus_vid(\'' + e + "','<?php echo $u; ?>','replytext_vid_" + e + "',this,'" + i + "')\">Reply</button>", _(t).value = "", _("triggerBtn_SP_reply_vid_").style.display = "block", _("btns_SP_reply_vid_" + e).style.display = "none", _("uploadDisplay_SP_reply_vid_" + e).innerHTML = "", _("replytext_vid_" + e).style.height = "40px", _("fu_SP_reply_vid_").value = "", hasImage = ""
                } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
            }
        }, n.send("action=status_reply&sid=" + e + "&user=" + o + "&data=" + r + "&image=" + hasImage + "&vid=" + i)
    }

    function shareStatus_art(e) {
        var o = ajaxObj("POST", "/php_parsers/article_status_system.php");
        o.onreadystatechange = function() {
            1 == ajaxReturn(o) && ("share_ok" == o.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, o.send("action=share&id=" + e)
    }

    function shareStatus_phot(e, o) {
        var t = ajaxObj("POST", "/php_parsers/photo_status_system.php");
        t.onreadystatechange = function() {
            1 == ajaxReturn(t) && ("share_ok" == t.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, t.send("action=share&id=" + e + "&phot=" + o)
    }

    function shareStatus_vid(e, o) {
        var t = ajaxObj("POST", "/php_parsers/video_status_parser.php");
        t.onreadystatechange = function() {
            1 == ajaxReturn(t) && ("share_ok" == t.responseText ? (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden") : (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"))
        }, t.send("action=share&id=" + e + "&vid=" + o)
    }

    function toggleLike_art(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_system_art.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_art(\'unlike\',\'' + o + "','likeBtn_art" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipan_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_art(\'like\',\'' + o + "','likeBtn_art" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipan_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&arid=" + l)
    }

    function toggleLike_gr(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/gr_like_system.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_gr(\'unlike\',\'' + o + "','likeBtn_gr_" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_gr_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipan_gr_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_gr(\'like\',\'' + o + "','likeBtn_gr_" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_gr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                _("ipan_gr_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&group=" + l)
    }

    function toggleLike_reply_gr(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/gr_like_system_reply.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success_reply" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_gr(\'unlike\',\'' + o + "','likeBtn_reply_gr_" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_gr_reply_" + o).innerText.replace("(", "")).replace(")", "").replace("likes").replace(" ", "");
                    e = Number(e);
                    _("ipan_gr_reply_" + o).innerText = ++e + " likes";
                } else if ("unlike_success_reply" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_gr(\'like\',\'' + o + "','likeBtn_reply_gr_" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_gr_reply_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                _("ipan_gr_reply_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&group=" + l)
    }

    function toggleLike_phot(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_photo_system.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_phot(\'unlike\',\'' + o + "','likeBtn_phot" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_phot_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipan_phot_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_phot(\'like\',\'' + o + "','likeBtn_phot" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_phot_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipan_phot_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&phot=" + l)
    }

    function toggleLike_vid(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_system_video.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_vid(\'unlike\',\'' + o + "','likeBtn_vid" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipan_vid_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipan_vid_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_vid(\'like\',\'' + o + "','likeBtn_vid" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipan_vid_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipan_vid_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&vid=" + l)
    }

    function toggleLike_reply_vid(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_system_video.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_vid(\'unlike\',\'' + o + "','likeBtn_reply_vid" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipanr_vid_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace("");
                    e = Number(e);
                    _("ipanr_vid_" + o).innerText = ++e + " likes";
                } else if ("unlike_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_vid(\'like\',\'' + o + "','likeBtn_reply_vid" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipanr_vid_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanr_vid_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&vid=" + l)
    }

    function toggleLike_reply_art(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_reply_system_art.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_reply_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_art(\'unlike\',\'' + o + "','likeBtn_reply_art" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipanr_art_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipanr_art_" + o).innerText = ++e + " likes";
                } else if ("unlike_reply_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_art(\'like\',\'' + o + "','likeBtn_reply_art" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>'
                e = (e = (e = _("ipanr_art_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanr_art_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&arid=" + l)
    }

    function toggleLike_reply_phot(e, o, t, l) {
        var i = ajaxObj("POST", "/php_parsers/like_reply_photo_system.php");
        i.onreadystatechange = function() {
            if (1 == ajaxReturn(i))
                if ("like_reply_success" == i.responseText) {
                    _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_phot(\'unlike\',\'' + o + "','likeBtn_reply_phot" + o + "','" + l + '\')"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
                    var e = (e = _("ipanr_phot_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
                    e = Number(e);
                    _("ipanr_phot_" + o).innerText = ++e + " likes";
                } else if ("unlike_reply_success" == i.responseText) {
                _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply_phot(\'like\',\'' + o + "','likeBtn_reply_phot" + o + "','" + l + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" title="Like"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
                e = (e = (e = _("ipanr_phot_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
                e = Number(e);
                _("ipanr_phot_" + o).innerText = --e + " likes";
            } else _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden"
        }, i.send("type=" + e + "&id=" + o + "&phot=" + l)
    }

    function showBtnDiv_reply(e, o) {
        0 == mobilecheck && (_("replytext_" + o + "_" + e).style.height = "130px"), _("btns_SP_reply_" + o + "_" + e).style.display = "block"
    }
    window.onbeforeunload = function() {
        if ("" != hasImage) return "You have not posted your image"
    };
    var stat = "less";

    function opentext(e, o) {
        "less" == stat ? (_("lessmore_" + o + "_" + e).style.display = "block", _("toggle_" + o + "_" + e).innerText = "See Less", _("hide_" + o + "_" + e).style.display = "none", stat = "more") : "more" == stat && (_("lessmore_" + o + "_" + e).style.display = "none", _("toggle_" + o + "_" + e).innerText = "See More", _("hide_" + o + "_" + e).style.display = "block", stat = "less")
    }
  </script>
</head>
<body style="background-color: #fafafa;">
  <?php require_once 'template_pageTop.php'; ?>
  <?php if($isfeed == false){ ?>
      <div id="pearHolder" class="seekhide"></div>
        <section id="startContent">
            <div>
                <p>Connect us, connect the world</p><br>
                <p>Join to Pearscom now and get a pear.</p><br>
                <button class="main_btn" onclick="location.href='/login'">Log In</button>
                <button class="main_btn main_btn_fill" onclick="location.href='/signup'">Sign Up</button>
                <p class="centerBox">By signing up you agree our <a href="/policies" class="rlink">Privacy and Policy</a>, how we collect and use your data and accept the use of <a href="policies" class="rlink">cookies</a> on the site.</p>
            </div>
        </section>
        <div id="pearHolder" class="hideseek"></div>
        <div id="changingWords"><div class="wordsStyle"><span class="wordsBg">Share your ideas</span></div></div>
        <div class="clear"></div>
    <?php }else{ ?>
        <div id="dialogbox"></div>
        <div id="overlay"></div>
        <div id="pageMiddle_index" style="background-color: transparent;">
            <div id="newsfeed">
                <div id="sli"><?php echo $statuslist; ?></div>
                <div id="gal" class="ppForm"><?php echo $gallery_list; ?></div>
                <div class="clear"></div><hr class="dim">
                <div id="sphl"><?php echo $statphol; ?></div>
                <div id="sgl" class="ppForm"><?php echo $sugglist; ?></div>
                <div class="clear"></div><hr class="dim">
                <div id="astat"><?php echo $statartl; ?></div>
                <div id="sug"><div class="nfmo ppForm"><?php echo $moMoFriends; ?></div></div>
                <span><div class="clear"></div></span><hr class="dim">
                <div id="rel" class="ppForm"><?php echo $relvids; ?></div>
                <div class="clear"></div><hr class="dim">
                <div id="svid"><?php echo $statvidl; ?></div>
                <div id="mp"><?php echo $mainPosts; ?></div><hr class="dim">
                <p style="display: none;" id="mr"></p>
            </div>
            <div id="pcload"></div>
        </div>
    <?php } ?>
  <?php if(!$isfeed){ require_once 'template_pageBottom.php'; } ?>

  <script type="text/javascript">
var isf = "<?php echo $isfeed; ?>";

function getCookie(e) {
    for (var t = e + "=", n = decodeURIComponent(document.cookie).split(";"), i = 0; i < n.length; i++) {
        for (var o = n[i];
            " " == o.charAt(0);) o = o.substring(1);
        if (0 == o.indexOf(t)) return o.substring(t.length, o.length)
    }
    return "";
}

function setDark() {
    var e = "thisClassDoesNotExist";
    if (!document.getElementById(e)) {
        var t = document.getElementsByTagName("head")[0],
            n = document.createElement("link");
        n.id = e, n.rel = "stylesheet", n.type = "text/css", n.href = "/style/dark_style.css", n.media = "all", t.appendChild(n)
    }
}
var isdarkm = getCookie("isdark");

if(isf){
    var CheckIfScrollBottom = debouncer(function() {
        if(getDocHeight() < (getScrollXY()[1] + window.innerHeight + 100)) {
           1 == isn && (_("pcload").innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style=\'display: block; margin: 0 auto; margin-top: 5px; margin-bottom: 5px;\'>');
    
                var e = 0;
                if (0 != num) e = 12 * num + 1;
                var t = e + 12,
                    n = new ajaxObj("POST", "test_box.php");
                n.onreadystatechange = function() {
                    1 == ajaxReturn(n) && ("" != n.responseText ? (1 == num && (_("mr").style.display = "block"), _("pcload").innerHTML = "", _("newsfeed").innerHTML += n.responseText) : (_("pcload").innerHTML = '<p style="color: #999; text-align: center;">This is the end of your news feed. Come back later ...</p>', isn = !1))
                }, n.send("limit_min=" + e + "&limit_max=" + t + "&num=" + num), inc++, num++
        }
    },500);
    
    document.addEventListener('scroll',CheckIfScrollBottom);
}

function debouncer(a,b,c){var d;return function(){var e=this,f=arguments,g=function(){d=null,c||a.apply(e,f)},h=c&&!d;clearTimeout(d),d=setTimeout(g,b),h&&a.apply(e,f)}}
function getScrollXY(){var a=0,b=0;return"number"==typeof window.pageYOffset?(b=window.pageYOffset,a=window.pageXOffset):document.body&&(document.body.scrollLeft||document.body.scrollTop)?(b=document.body.scrollTop,a=document.body.scrollLeft):document.documentElement&&(document.documentElement.scrollLeft||document.documentElement.scrollTop)&&(b=document.documentElement.scrollTop,a=document.documentElement.scrollLeft),[a,b]}
function getDocHeight(){var a=document;return Math.max(a.body.scrollHeight,a.documentElement.scrollHeight,a.body.offsetHeight,a.documentElement.offsetHeight,a.body.clientHeight,a.documentElement.clientHeight)}



if (1 == isf) {
    function randtext() {
        for (var e = "", t = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", n = 0; n < 5; n++) e += t.charAt(Math.floor(Math.random() * t.length));
        return e
    }
    
    var inc = 0,
        num = 0,
        isn = !0;
    1 == isn && $(window).scroll(function() {

    }), window.innerWidth > 808 && (_("cp").style.width = "300px", _("cp").style.right = "0px"), 0 == mobilecheck && (_("cp").addEventListener("mouseover", function() {
        _("cp").style.overflowY = "auto", document.body.style.overflowY = "auto"
    }), _("cp").addEventListener("mouseout", function() {
        _("cp").style.overflowY = "hidden", document.body.style.overflowY = "auto"
    }));
    var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName("body")[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight || e.clientHeight || g.clientHeight;
    _("pageMiddle_index").style.overflow = "auto";
    for (var cut = 0, is = "<?php echo $imgs; ?>", isa = is.split("|"), j = (inc = 0, 0); j < isa.length - 1; j++) {
        ++inc, cut = "" != isa[j] ? 90 : 400;
        var t = _("pcs_" + inc).innerText;
        if (t.length > 90) {
            var xt = t.substr(0, cut);
            _("pcs_" + inc).innerText = xt + " ..."
        }
    }
}

    if(!isf){
        let keepLoop = 0;
        let testArr = ["Upload videos and photos", "Create unique content", "Keep contact with your friends", "Chat with other people", "Write and read articles", "Talk in groups", "Search for people nearby", "Share your ideas"];
        let contDiv = document.getElementById("changingWords");

        setInterval(getWord, 2000);
        function getWord(){
            if(keepLoop >= testArr.length) keepLoop = 0;
            let newDiv = document.createElement("div");
            let newSpan = document.createElement("span");
            newDiv.appendChild(newSpan);
            newSpan.className = "wordsBg";
            newSpan.innerHTML = testArr[keepLoop];
            newDiv.className = "wordsStyle";
            newSpan.id = 'fadeSpan';
            newSpan.style.display = "none";
            contDiv.replaceChild(newDiv, contDiv.childNodes[0]);
            $("#fadeSpan").fadeIn(500)
            keepLoop++;
        }
    }
  </script>
</body>
</html>
