<?php
  require_once 'perform_checks.php';
  /*
    Common functions for status pages.
  */

  function genUserImage($author, $friend_pic, $funames, $isonimg, $fuco, $dist, $numoffs,
    $mgin = false, $aClass = '') {
    if($mgin) {
      $mgin = 'margin-left: -11px;';
    } else {
      $mgin = '';
    }

    return '
      <a href="/user/'.$author.'/">
        <div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat;
          background-size: cover; '.$mgin.' background-position: center; width: 50px;
          margin-bottom: 5px; height: 50px; display: inline-block;"
          class="tsrhov bbmob lazy-bg '.$aClass.'">
        </div>

        <div class="infotsrdiv">
          <div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat;
            background-size: cover; background-position: center; width: 60px;
            height: 60px; display: inline-block; float: left;" class="tsrhov lazy-bg
            '.$aClass.'">
          </div>

          <span style="float: left; margin-left: 2px;">
            <u>'.$funames.'</u>
            &nbsp;'.$isonimg.'<br>
            <img src="/images/pcountry.png" width="12" height="12">
            &nbsp;'.$fuco.'<br>
            <img src="/images/udist.png" width="12" height="12">
            &nbsp;Distance: '.$dist.' km<br>
            <img src="/images/fus.png" width="12" height="12">
            &nbsp;Friends: '.$numoffs.'
          </span>
        </div>
      </a>
    ';
  }

  function clearImg($data_old) {
    if(strpos($data_old,'<img src="/permUploads/') !== false){
      $split = explode('<img src="/permUploads/', $data_old);
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
        return true;
      }
    }
    return false;
  }

  function isOn($ison) {
    if($ison == "yes"){
      return "<img src='/images/wgreen.png' width='12' height='12'>";
    }else{
      return "<img src='/images/wgrey.png' width='12' height='12'>";
    }
  }

  function avatarImg($author, $avatar) {
    if($avatar != ""){
      return '/user/'.$author.'/'.$avatar;
    } else {
      return '/images/avdef.png';
    }
  }

  function sanitizeData($data_old) {
    $data_old = nl2br($data_old);
    $data_old = str_replace("&amp;", "&", $data_old);
    $data_old = stripslashes($data_old);
    return $data_old;
  }

  function seeHideWrap($data, $data_old, $statusid, $pos, $isex, $isStatus = true) {
    if(strlen($data) > 1000){
      if($pos === false && $isex === false){
        if($isStatus) {
          $postfix = '';
        } else {
          $postfix = '_reply';
        }
        $data = wrapText($data, 1000);
        $data .= '&nbsp;
          <a id="toggle'.$postfix.'_'.$statusid.'" onclick="opentext'.$postfix.'(\''.$statusid.'\')">See More</a>';
        $data_old = '
          <div id="lessmore'.$postfix.'_'.$statusid.'" class="lmml">
            <p id="status_text">
              '.$data_old.'&nbsp;
              <a id="toggle'.$postfix.'_'.$statusid.'" onclick="opentext'.$postfix.'(\''.$statusid.'\')">See Less</a>
            </p>
          </div>';
      } else {
        $data_old = "";
      }
    } else {
      $data_old = "";
    }
    return [$data, $data_old];
  }

  function genDelBtn($author, $log_username, $account_name, $statusid, $isStatus = true,
    $space = true, $serverSide = "/php_parsers/article_status_system.php") {
    if($author == $log_username || $account_name == $log_username){
      if($isStatus) {
        $id = "sdb_".$statusid; 
        $what = 'Post';
        $fname = 'deleteStatus';
        $half = 'status_';
      } else {
        $id = "srdb_".$statusid; 
        $what = 'Reply';
        $fname = 'deleteReply';
        $half = 'reply_';
      }

      return '
        <span id="'.$id.'">
          <button onclick="Confirm.render("Delete '.$what.'?","delete_post","post_1")" 
          class="delete_s" onclick="return false;"
          onmousedown="'.$fname.'(\''.$statusid.'\',\''.$half.''.$statusid.'\', \''.$serverSide.'\');">X</button>
        </span>
        &nbsp;&nbsp;';
    }else if ($space) {
      return "&nbsp;&nbsp;&nbsp;";
    }
    return '';
  }

  function genShareBtn($log_username, $author, $statusid,
    $serverSide = '/php_parsers/article_status_system.php', $isGr = '', $key = '') {
    if($log_username != "" && $author != $log_username){
      return '
        <img src="/images/black_share.png" width="18" height="18" onclick="return false;"
          onmousedown="shareStatus(\'' . $statusid . '\', \''.$serverSide.'\', \''.$isGr.'\',
          \''.$key.'\');"
          id="shareBlink" style="vertical-align: middle;">';
    }
    return '';
  }

  function genStatLikeBtn($isLike, $statusid, $isStatus = true, $extraArg = false,
    $serverSide = '/php_parsers/like_system_art.php') {
    if($isStatus) {
      $postfix = '';
    } else {
      if (!$extraArg && $serverSide == '/php_parsers/like_system_art.php') {
        $serverSide = '/php_parsers/like_reply_system_art.php';
      }
      $postfix = '_reply';
    }

    $likeButton = "";
    $likeText = "";
    if($isLike == true){
      $likeButton = '
        <a href="#" onclick="return false;" 
          onmousedown="toggleLike'.$postfix.'(\'unlike\',\''.$statusid.'\',\'likeBtn'.$postfix.'_'.$statusid.'\', \''.$extraArg.'\', \''.$serverSide.'\')">
          <img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"
          style="vertical-align: middle;">
        </a>';
      $likeText = '<span style="vertical-align: middle;">Dislike</span>';
    }else{
      $likeButton = '
        <a href="#" onclick="return false;"
          onmousedown="toggleLike'.$postfix.'(\'like\',\''.$statusid.'\',\'likeBtn'.$postfix.'_'.$statusid.'\', \''.$extraArg.'\', \''.$serverSide.'\')">
          <img src="/images/nf.png" width="18" height="18" class="like_unlike"
          style="vertical-align: middle;">
        </a>';
      $likeText = '<span style="vertical-align: middle;">Like</span>';
    }
    return [$likeButton, $likeText];
  }

  function userLiked($user_ok, $conn, $statusid, $log_username, $isStatus = true, $db = '',
    $field = '') {
    if($user_ok){
      if ($db == "" && $field == "") {
        if ($isStatus) {
          $db = 'art_stat_likes';
          $field = 'status';
        } else {
          $db = 'art_reply_likes';
          $field = 'reply'; 
        }
      }
      $like_check = "SELECT id FROM ".$db." WHERE username=? AND ".$field."=? LIMIT 1";
      $stmt = $conn->prepare($like_check);
      $stmt->bind_param("si", $log_username, $statusid);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      if($numrows > 0){
        return true;
      }
      $stmt->close();
    }
    return false;
  }

  function getAllLikes($db, $field, $statusid, $conn, $isMore = false, $keys = '', ...$params) {
    if (!$isMore) {
      $sql = "SELECT COUNT(id) FROM ".$db." WHERE ".$field ." = ?";
    } else {
      $sql = "SELECT COUNT(id) FROM ".$db." WHERE ".$isMore;
    }
    $stmt = $conn->prepare($sql);
    if (!$isMore) {  
      $stmt->bind_param("i", $statusid);
    } else {
      $stmt->bind_param("{$keys}", ...$params);
    }
    $stmt->execute();
    $stmt->bind_result($likecount);
    $stmt->fetch();
    $stmt->close();
    return $likecount;
  }

  function genLog($u, $statusid, $likeButton, $likeText, $isStatus = true, $shareButton = '') {
    if($isStatus) {
      $postfix = '';
      $more = '
        <div class="shareDiv">
          ' . $shareButton . '
          <span style="vertical-align: middle;">Share</span>
        </div>
      ';
    } else {
      $postfix = '_reply';
      $more = '';
    }

    if($u != ""){
      return '
        <span id="likeBtn'.$postfix.'_'.$statusid.'" class="likeBtn">'
          .$likeButton.'
          <span style="vertical-align: middle;">'.$likeText.'</span>
        </span>' . $more;
    } 
    return '';
  }

  function countReplies($u, $statusid, $ar, $conn, $db = 'article_status', $plus = 'artid') {
    $b = 'b';
    $sql = "SELECT COUNT(id) FROM ".$db." WHERE type = ? AND account_name = ?
          AND osid = ? AND ".$plus." = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $b, $u, $statusid, $ar);
    $stmt->execute();
    $stmt->bind_result($countrply);
    $stmt->fetch();
    $stmt->close();
    return $countrply;
  }

  function genShowMore($crply, $statusid) {
    if($crply > 0){
      return '
        <div class="showrply">
          <a id="showreply_'.$statusid.'" onclick="showReply('.$statusid.','.$crply.')">
            Show replies ('.$crply.')
          </a>
        </div>';
    }
  }

  function numOfPosts($conn, $db, $field, $param) {
    $sql = "SELECT COUNT(id) FROM ".$db." WHERE ".$field." = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $param);
    $stmt->execute();
    $stmt->bind_result($countRs);
    $stmt->fetch();
    $stmt->close();
    if($countRs > 0){
      return '<p style="color: #999; text-align: center;">'.$countRs.' comments recorded</p>';
    }
    return '';
  }
?>
