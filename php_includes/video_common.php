<?php
  function genSmallVidBox($id_hsh, $prs, $vidn, $dur) {
    return "
      <a href='/video_zoom/" . $id_hsh . "'>
        <div class='nfrelv vBigDown' style='white-space: nowrap;'>
          <div id='pcgetc' data-src=\"".$prs."\" class='mainVids lazy-bg'></div>
          <div class='pcjti'>" . $vidn . "</div>
          <div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px;
            position: absolute; bottom: 25px;'>
            " . $dur . "
          </div>
        </div>
      </a>
    ";
  }

  function genLVidBox($conn, $row, $isFromSQL = true) {
    global $hshkey;

    // Get data about suggested vid
    if ($isFromSQL) {
      $id_o = $row["video"];
      $id_hsh = base64url_encode($id_o, $hshkey);
      $vdate_ = $row["video_upload"];

      $sql = "SELECT video_poster, user, dur, video_name FROM videos WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $id_o);
      $stmt->execute();
      $stmt->bind_result($vidp, $vidu, $dur, $vidn);
      $stmt->fetch();
      $stmt->close();
    } else {
      $id_o = $row["id"];
      $id_hsh = base64url_encode($id_o, $hshkey);
      $vidp = $row['video_poster'];
      $vidu = $row['user'];
      $dur = $row['dur'];
      $vidn = $row['video_name'];
    }

    // Select thumbnail
    $prs = thumbnailImg($vidu, $vidp);

    $dur = convDur($dur);
    if($vidn == ""){
      $vidn = "Untitled";
    }

    return genSmallVidBox($id_hsh, $prs, $vidn, $dur);
  }

  function thumbnailImg($u, $poster) {
    if(!$poster){
      return "/images/defaultimage.png";
    }else{
      return '/user/'.$u.'/videos/'.$poster;
    }
  }
?>
