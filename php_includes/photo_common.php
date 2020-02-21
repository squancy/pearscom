<?php
  /*
    General functions & classes that are used on pages dealing with photos
  */

  function genPhotoBox($row, $isBig = false) {
    $uder = $row["user"];
    $fname = $row["filename"];
    $description = $row["description"];
    $timed = $row["uploaddate"];
    $udp = strftime("%R, %b %d, %Y", strtotime($timed));
    $uds = time_elapsed_string($timed);
    $description = wrapText($description, 12);

    $pcurl = '/user/'.$uder.'/'.$fname;
    list($width, $height) = getimagesize('user/'.$uder.'/'.$fname);

    $styleWidth = '';
    $styleBg = '';
    if ($isBig) {
      $styleWidth = "style='width: 100%;'";
    } else {
      $styleBg = "style='background-repeat: no-repeat;
        background-position: center; background-size: cover; height: 100px;'";  
    }

    return "
      <a href='/photo_zoom/".urlencode($uder)."/".$fname."'>
        <div class='pccanvas' ".$styleWidth.">
          <div data-src=\"".$pcurl."\" class='lazy-bg' ".$styleBg.">
            <div id='photo_heading' style='width: auto !important; margin-top: 0px;
              position: static;'>".$width." x ".$height."
            </div>
          </div>
        </div>
      </a>
    ";
  }

  function countUserPhots($conn, $u) {
    $sql = "SELECT COUNT(id) FROM photos WHERE user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_all);
    $stmt->fetch();
    $stmt->close();
    return $count_all;
  }
?>
