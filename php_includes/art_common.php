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

  function genRelArt($dpp, $wb, $cover, $title, $written_bylink, $agoform, $cat) {
    return '
      <a href="/articles/'.$dpp.'/'.$wb.'">
        <div class="article_echo_2 artRelGen">
          '.$cover.'
          <div>
            <p class="title_">
              <b>Author: </b> '.$written_bylink.'
            </p>
            <p class="title_">
              <b>Title: </b>'.$title.'
            </p>
            <p class="title_">
              <b>Posted: </b>'.$agoform.' ago
            </p>
            <p class="title_">
              <b>Category: </b>'.$cat.'
            </p>
          </div>
        </div>
      </a>
    ';
  }
?>
