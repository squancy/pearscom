<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/pm_common.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/pagination.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';
  require_once 'elist.php';

  // Initialize any variables that the page might echo
  $u = "";
  $mail = "";
  $one = "1";
  $x = "x";
  $zero = "0";

  // Make sure the _GET username is set, and sanitize it
  $u = checkU($_GET['u'], $conn);

  // Check to see if the viewer is the account owner
  $isOwner = isOwner($u, $log_username, $user_ok);
  
  // If the user does not fit any of these criterias header them to index.php
  if(!isset($_SESSION["username"]) || $user_ok != true || $log_username == "" ||
    $u != $log_username || $_SESSION["username"] == "" || $isOwner != "Yes"){
    header('location: /index');
    exit();
  }
  
  $otype = "";
  if(isset($_GET["otype"])){
    $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
  }else{
    $otype = "sort_0";
  }

  // Select the member from the users table
  userExists($conn, $u);
  
  // Update the last read property in the db
  updateDate($conn, $log_username);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM pm WHERE (receiver=? OR sender=?) AND parent=? AND
    rdelete = ? AND sdelete = ?";
  $url_n = "/private_messages/{$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'sssss', $url_n,
    $log_username, $log_username, $x, $zero, $zero); 
  
  $countMsgs = 0;
  $clause = "ORDER BY senttime DESC";

  // Also call this code when changing sorting type
  if(isset($_GET["otype"]) || $clause != ""){
    if(isset($_GET["otype"])){
      $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
    }

    // Set the proper sql query for the sorting type
    $clause = selectClause($otype); 

    // Get the conversation
    $sql = "SELECT * FROM pm WHERE (receiver=? OR sender=?) AND parent=? AND rdelete = ?
      AND sdelete = ? $clause $limit";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $log_username, $log_username, $x, $zero, $zero);
    $stmt->execute();
    $result = $stmt->get_result();

    // Gather data about parent pm's
    if($result->num_rows > 0){
      while ($row = $result->fetch_assoc()){
        $pmid = $row["id"];
        $pmid2 = 'pm_'.$pmid;
        $wrap = 'pm_wrap_'.$pmid;
        $btid2 = 'bt_'.$pmid;
        $rt = 'replytext_'.$pmid;
        $rb = 'replyBtn_'.$pmid;
        $receiver = $row["receiver"];
        $sender = $row["sender"];
        $subject = $row["subject"];
        $message = $row["message"];
        $time_ = $row["senttime"];
        $rread = $row["rread"];
        $sread = $row["sread"];
        $mread = $row["mread"];
        $read_string = "";

        // If pm is marked as read change its style
        if($mread){
          $read_string = 'style="border: 2px solid red;"';
        }

        $time = strftime("%R, %b %d, %Y", strtotime($time_));
        $subject_new = $subject;

        $message_old = sanitizeData($row["message"]);

        // Wrap pm text if longer than 1,000 chars
        list($message, $message_old) = seeHideWrap($message, $message_old, $pmid, false,
          false);
        $message = sanitizeData($message);

        if ($sender == $log_username) {
          $toMsg = $receiver;
        } else {
          $toMsg = $sender;
        }
        
        // Get user avatar
        $pcurlk = getUserAvatar($conn, $toMsg);
        
        $style = $stylef = 'background-repeat: no-repeat; background-position: center;
          background-size: cover; width: 50px; height: 50px; border-radius: 50%;';
        $stylef .= 'float: left;';
        
        $sourceURL = "data-src=\"" . $pcurlk . "\" class='lazy-bg' style='".$style."'";
        $sourceURLFrom = "data-src=\"" . $pcurlk . "\" class='lazy-bg' style='".$stylef."'";
        
        $senderpic = "<a href='/user/".$toMsg."/'><div ".$sourceURL."></div></a>";
        $senderpic_from = "<a href='/user/".$toMsg."'><div ".$sourceURLFrom."></div>
          </a>";

        $pmids = strval($pmid);

        // Select the last message sent
        list($lastMsg, $lastSender, $lastTime) = lastMessage($conn, $pmids);

        $lastTime = strftime("%R, %b %d", strtotime($lastTime));

        if($lastMsg == ""){
          $lastMsg = $message;
        }

        if($lastTime == "01:00, Jan 01"){
          $lastTime = strftime("%R, %b %d", strtotime($time_));
        }

        if(preg_match("/<img.+>/i", $lastMsg)){
          $lastMsg = "A photo was sent";
        }

        $lastImg = wrapText($lastImage, 200);

        // Start to build our list of parent pm's
        $mail .= '
          <div class="showMessage" '.$read_string.'>
            <div id="show_in_div"><b>Subject: </b>'.$subject.'</div>
              <div class="show_pic_div">'.$senderpic.'</div>
                <button id="show_'.$pmid.'" class="fixRed main_btn_fill"
                  style="margin-top: 5px; float: left; font-size: 12px;"
                  onclick="showMessage(\''.$pmid.'\')">Show message</button>
              <div class="sendtime">
                <div class="innerSend">'.$lastMsg.'</div>
                <span class="keepDate">'.$lastTime.'</span>
              </div>
              <div class="clear"></div>
            </div>
            <div id="pm_wrap_'.$pmid.'" class="pm_wrap">
              <div class="pm_header">
        ';

        // Add button for mark as read
        $mail .= '
          <span style="display: block; text-align: center;">
            <button onclick="markRead(\''.$pmid.'\',\''.$sender.'\')" id="mark_as_read"
              class="fixRed main_btn_fill">Important</button>';

        // Add delete button
        $mail .= '
          <button id="'.$btid2.'" onclick="deletePm(\''.$pmid.'\',\''.$sender.'\',
            \''.$log_username.'\')" class="delete_pm fixRed main_btn_fill">
            Delete
          </button>
        ';

        // Add quick link
        $mail .= '
              <a href="#pmtexta_'.$pmid.'" id="godown" class="main_btn_fill fixRed"
                style="color: white; font-size: 12px; margin-left: 10px;">
                Jump to bottom
              </a>
            </span>
          </div>
          <div id="'.$pmid2.'">
          <div class="pm_post">
            <b class="pm_time">'.$time.'</b>
            <a href="/user/'.$sender.'">'.$senderpic_from.'</a>
            <p class="vmit">
              <b class="sdata" id="hide_'.$pmid.'">'.$message.''.$message_old.'</b>
            </p>
          </div>
          <div class="clear">
        </div>
        <hr class="dim">
        ';

        $stmt->close();
        
        // Gather up any replies to the parent pm's
        $sql2 = "SELECT * FROM pm WHERE parent=? ORDER BY senttime ASC";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param("i", $pmid);
        $stmt->execute();
        $result2 = $stmt->get_result();
        if($result2->num_rows > 0){
          while ($row2 = $result2->fetch_assoc()) {
            $countMsgs++;
            $rplyid = $row2["id"];
            $rsender = $row2["sender"];
            $reply = $row2["message"];
            $time2_ = $row2["senttime"];
            $time2 = strftime("%R, %b %d, %Y", strtotime($time2_));
            
            // Get reply user's avatar
            $pcurlkk = getUserAvatar($conn, $rsender);
            
            $style_r = 'background-repeat: no-repeat; background-position: center;
              background-size: cover; width: 50px; height: 50px; float: left;
              border-radius: 50%;';
            
            $sourceURL_r = "";
            if($otype == 'sort_0'){
              $sourceURL_r = "data-src=\"" . $pcurlkk . "\" class='lazy-bg'
                style='".$style_r."'";
            }else{
              $sourceURL_r = "style='background-image: url(\"$pcurlkk\");
                ".$style_r."'";
            }
            
            $senderpic_from_reply = "
              <a href='/user/".$rsender."'>
                <div ".$sourceURL_r."></div>
              </a>
            ";

            $reply_old = sanitizeData($row2["message"]);
           
            // Wrap reply text if longer than 1,000 chars
            list($reply, $reply_old) = seeHideWrap($reply, $reply_old, $rplyid, false,
              false, false);
            $reply = sanitizeData($reply);

            // If user is the sender add a delete button
            if($log_username == $rsender){
              $deletebutton = '
                <button onclick="deleteMessage(\''.$rplyid.'\',\''.$rsender.'\',
                  \''.$time2_.'\')" class="delete_s" title="Delete message">X</button>';
            }else{
                $deletebutton = "";
            }

            // Append replies to main thread
            $mail .= '
              <div class="pm_post" id="whole_'.$rplyid.'">
                <b class="pm_time">'.$deletebutton.''.$time2.'</b>
                <a href="/user/'.$rsender.'">'.$senderpic_from_reply.'</a>
                <p class="vmit">
                  <b class="sdata" id="hide_reply_'.$rplyid.'">
                    '.$reply.$reply_old.'</b>
                  </p>
              </div>
              <div class="clear">
            </div>
            <hr class="dim" id="wholle_'.$rplyid.'">';

            $stmt->close();
          }
        }

        // Each parent and child is now listed
        $mail .= '</div>';

        // Add reply textbox
        $mail .= '
          <textarea id="pmtexta_'.$pmid.'" class="pmtexta"
            onfocus="showBtnDiv_pm(\''.$pmid.'\')"
            placeholder="What&#39;s in your mind '.$log_username.'?"
            onkeyup="statusMax(this,65000)"></textarea>
          <div id="uploadDisplay_SP_msg_'.$pmid.'"></div>
            <div id="btns_SP_'.$pmid.'" class="hiddenStuff" style="width: auto;">
              <span id="swithidbr_msg_'.$pmid.'">
                <button id="pmsendBtn" class="btn_rply"
                  onclick="postPmMsg(\'pm_reply\',\''.$u.'\',\'pmtexta_'.$pmid.'\',
                    \''.$sender.'\',\''.$pmid.'\', \''.$time2_.'\')">Post</button>
              </span>
              <img src="/images/camera.png" id="triggerBtn_SP_'.$pmid.'"
                class="triggerBtnreply" onclick="triggerUpload(event, \'fu_SP_'.$pmid.'\')"
                width="22" height="22" title="Upload A Photo" />
              <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
                title="Send emoticons" id="emoji"
                onclick="openEmojiBox(\'emojiBox_pm_'.$pmid.'\')">
              <div class="clear"></div>
        ';
        $mail .= generateEList($pmid, 'emojiBox_pm_'.$pmid, 'pmtexta_'.$pmid.'');
        $mail .= '</div>';
        $mail .= '</div>';
        $mail .= '
          <div id="standardUpload" class="hiddenStuff">
            <form id="image_SP_reply_'.$pmid.'" enctype="multipart/form-data" method="POST">
              <input type="file" name="FileUpload" id="fu_SP_'.$pmid.'"
                onchange="doUpload(\''.$pmid.'\',\'fu_SP_'.$pmid.'\')">
            </form>
          </div>
          <div class="clear"></div>
        ';
      }
      
      // Produce output
      if(isset($_GET["otype"])){
        echo $mail;
        exit();
      }

    }else{
      $mail = '
        <p style="text-align: center; color: #999;">
          It seems that you have no incoming or sent private messages right now.
        </p>
      ';
    }
  }

  $countConvs = countConvs($conn, $log_username, $x, $zero);
  $countMsgs += $countConvs;
?>
<!DOCTYPE html>
<html>
  <head>
  <title><?php echo $u ?> - Private Message Inbox</title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <script src="/js/main.js" async></script>
  <script src="/js/jjs.js"></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/expand_retract.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script src="/js/specific/status_max.js"></script>
  <script src="/js/specific/p_dialog.js"></script>
  <script src="/js/specific/file_dialog.js"></script>
  <script src="/js/specific/upload_funcs.js"></script>
  <script src="/js/specific/error_dialog.js"></script>
  <script src="/js/specific/insert_emoji.js"></script>
  <script src="/js/specific/open_emoji.js"></script>
  <script src="/js/specific/see_hide.js"></script>
  <script src="/js/specific/dd.js"></script>
  <script src="/js/specific/pm.js"></script>
  <script src="/js/specific/filter.js"></script>
  <script type="text/javascript">
    var hasImage = '';
  </script>
  <style type="text/css">
    #status_text {
      margin-top: 0 !important;
    }
  </style>
  </head>
  <body>
    <?php include_once("template_pageTop.php"); ?>
    <div id="overlay"></div>
    <div id="dialogbox"></div>
    <div id="pageMiddle_2">
      <div id="data_holder">
        <div>
          <div><span><?php echo $countConvs; ?></span> conversations</div>
          <div><span><?php echo $countMsgs; ?></span> messages</div>
        </div>
      </div>
      <button id="sort" class="main_btn_fill">Filter Messages</button>
      <div id="sortTypes">
        <div class="gridDiv">
          <p class="mainHeading">Publish date</p>
          <div id="sort_0">Newest to oldest</div>
          <div id="sort_1">Oldest to newest</div>
        </div>
        <div class="gridDiv">
          <p class="mainHeading">Sender</p>
          <div id="sort_2">Alphabetical order</div>
          <div id="sort_3">Reverse alphabetical order</div>
        </div>
        <div class="gridDiv">
          <p class="mainHeading">Importance</p>
          <div id="sort_4">Importants to top</div>
          <div id="sort_5">Importants to bottom</div>
        </div>
        <div class="gridDiv">
          <p class="mainHeading">Randomly</p>
          <div id="sort_6">Messages by random</div>
        </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
      <hr class="dim">
      <div id="holdit"><?php echo $mail; ?></div>
      <div id="paginationCtrls" style="text-align: center; margin: 30px;">
        <?php echo $paginationCtrls; ?>
      </div>
    </div>
  <?php require_once 'template_pageBottom.php'; ?>
<script type="text/javascript">
    doDD('sort', 'sortTypes');

    const SERVER = "/pm_inbox?u=<?php echo $u; ?>&otype="; 

    function successHandler(req) {
      _("holdit").innerHTML = req.responseText;
      startLazy(true);
    }

    const BOXES = [];

    for(let i = 0; i < 7; i++){
      BOXES.push("sort_" + i);
    }
    
    for (let box of BOXES) {
      addListener(box, box, 'holdit', SERVER, successHandler);
    }

    changeStyle("sort_0", BOXES);
</script>
</body>
</html>
