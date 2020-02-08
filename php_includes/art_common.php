<?php
  /*
    General functions needed for pages that work with articles.
  */

  function isAdded($conn, $log_username, $p, $u, $user_ok, $db) {
    if($user_ok){
      $heart_check = "SELECT id FROM ".$db." WHERE username=? AND art_time=? AND
        art_uname=? LIMIT 1";
      $stmt = $conn->prepare($heart_check);
      $stmt->bind_param("sss", $log_username, $p, $u);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      $stmt->close();
      if($numrows > 0){
        return true;
      }
    }
    return false;
  }

  function genHeartBtn($isHeart, $p, $u) {
    if($isHeart){
      $heartButton = '
        <a href="#" onclick="return false;"
          onmousedown="toggleHeart(\'unheart\', \''.$p.'\', \''.$u.'\', \'heartBtn\')">
          <img src="/images/heart.png" width="18" height="18" title="Dislike"
            class="icon_hover_art">
        </a>';
      $isHeartOrNot = 'You liked this article';
    }else{
      $heartButton = '
        <a href="#" onclick="return false;"
          onmousedown="toggleHeart(\'heart\', \''.$p.'\', \''.$u.'\', \'heartBtn\')">
          <img src="/images/heart_b.png" width="18" height="18" title="Like"
            class="icon_hover_art">
        </a>';
      $isHeartOrNot = 'You did not like this article, yet';
    }
    return [$heartButton, $isHeartOrNot];
  }

  function countHearts($conn, $p, $u) {
    $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_time=? AND art_uname=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $p, $u);
    $stmt->execute();
    $stmt->bind_result($heart_count);
    $stmt->fetch();
    $stmt->close();
    return $heart_count;
  }

  function countFavs($conn, $opt, $log_username) {
    $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ? AND art_time = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $log_username, $opt);
    $stmt->execute();
    $stmt->bind_result($cnt_fav);
    $stmt->fetch();
    $stmt->close();
    return $cnt_fav;
  }

  function genFavBtn($isFav, $p, $u) {
    if($isFav){
      $favButton = '
        <a href="#" onclick="return false;"
          onmousedown="toggleFav(\'unfav\', \''.$p.'\', \''.$u.'\', \'favBtn\')">
          <img src="/images/star.png" width="20" height="20" title="Unfavourite"
          class="icon_hover_art">
        </a>';
      $isFavOrNot = "You added as favourite this article";
    }else{
      $favButton = '
        <a href="#" onclick="return false;"
          onmousedown="toggleFav(\'fav\', \''.$p.'\', \''.$u.'\', \'favBtn\')">
          <img src="/images/star_b.png" width="20" height="20" title="Favourite"
            class="icon_hover_art">
        </a>';
      $isFavOrNot = "You did not add as favourite this article, yet";
    }
    return [$favButton, $isFavOrNot];
  } 

  function genButtons($log_username, $written_by_ma, $p, $u) {
    if($log_username == $written_by_ma){
		  $delBtn = '
        <button onclick="deleteArt(\''.$p.'\', \''.$u.'\')" id="deleteBtn_art"
          class="main_btn_fill fixRed">Delete Article</button>';
      $editBtn = '
        <button class="main_btn_fill fixRed" onclick="editArt()" id="edit_btn_art"
          class="main_btn_fill fixRed">Edit article</button>
      ';
      return [$delBtn, $editBtn];
	  }
    return ['', ''];
  }

  function getNumOfArts($conn, $u) {
    $sql = "SELECT COUNT(id) FROM articles WHERE written_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->bind_result($count_art);
    $stmt->fetch();
    $stmt->close();
    return $count_art;
  }

  function genFullBox($row) {
    global $hshkey;
    $wb = $row["written_by"];
    $tit = stripslashes($row["title"]);
    $tit = str_replace('\'', '&#39;', $tit);
    $tit = str_replace('\'', '&#34;', $tit);
    $tag = $row["tags"];
    $pt_ = $row["post_time"];
    $opt = $pt_;
    $pt = strftime("%b %d, %Y", strtotime($pt_));
    $pt_ = base64url_encode($pt_, $hshkey);
    $wb_ori = urlencode($wb);
    $cat = $row["category"];
    $cover = chooseCover($cat);
  
    if(!function_exists('genArtBox')) {
      function genArtBox($post_time_, $written_by_original, $cover, $written_by, $title,
        $post_time, $tags, $cat) {
        return '
          <a href="/articles/'.$post_time_.'/'.$written_by_original.'">
            <div class="article_echo_2" style="width: 100%;">
              '.$cover.'
              <div>
                <p class="title_">
                  <b>Author: </b>'.$written_by.'
                </p>
                <p class="title_">
                  <b>Title: </b>'.$title.'
                </p>
                <p class="title_">
                  <b>Posted: </b>'.$post_time.'
                </p>
                <div id="tag_wrap">
                  <p class="title_">
                    <b>Tags: </b>'.$tags.'
                  </p>
                </div>
                <p class="title_">
                  <b>Category: </b>'.$cat.'
                </p>
              </div>
            </div>
          </a>
        '; 
      }
    }
    return genArtBox($pt_, $wb_ori, $cover, $wb, $tit, $pt, $tag, $cat);
  }
?>
