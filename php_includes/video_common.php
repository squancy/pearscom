<?php
  function genSmallVidBox($id_hsh, $prs, $vidn, $dur, $isnewornot) {
    return "
      <a href='/video_zoom/" . $id_hsh . "'>
        <div class='nfrelv vBigDown' style='white-space: nowrap;'>
          <div id='pcgetc' data-src=\"".$prs."\" class='mainVids lazy-bg'></div>
          <div class='pcjti'>" . $vidn . "</div>
          <div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px;
            position: absolute; bottom: 25px;'>
            " . $dur . "
          </div>
          " . $isnewornot  . "
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

      $sql = "SELECT video_poster, user, dur, video_name, video_upload FROM videos WHERE id=?
        LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $id_o);
      $stmt->execute();
      $stmt->bind_result($vidp, $vidu, $dur, $vidn, $vdate_);
      $stmt->fetch();
      $stmt->close();
    } else {
      $id_o = $row["id"];
      $id_hsh = base64url_encode($id_o, $hshkey);
      $vidp = $row['video_poster'];
      $vidu = $row['user'];
      $dur = $row['dur'];
      $vidn = $row['video_name'];
      $vdate_ = $row['video_upload'];
    }

    $curdate = date("Y-m-d");
    $ud = mb_substr($vdate_, 0,10, "utf-8");

    // Check if video is uploaded 1 day ago or before
    $sql = "SELECT DATEDIFF(?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $curdate, $ud);
    $stmt->execute();
    $stmt->bind_result($isnew);
    $stmt->fetch();
    $stmt->close();
    $isnewornot = "";
    if($isnew <= 1){
      $isnewornot = "
        <div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 45px;
          position: absolute; bottom: 25px;'>New</div>
      ";
    }

    // Select thumbnail
    $prs = thumbnailImg($vidu, $vidp);

    $dur = convDur($dur);
    if($vidn == ""){
      $vidn = "Untitled";
    }

    return genSmallVidBox($id_hsh, $prs, $vidn, $dur, $isnewornot);
  }

  function thumbnailImg($u, $poster) {
    if(!$poster){
      return "/images/defaultimage.png";
    }else{
      return '/user/'.$u.'/videos/'.$poster;
    }
  }
?>
