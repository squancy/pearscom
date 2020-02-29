<?php
  require_once 'elist.php';

  // Protect this script from direct url access
  if ((!isset($isFriend)) || (!isset($isOwner)) || !isset($log_username) ||
    $log_username == ""){
    exit();
  }

  $pm_ui = "";

  // If visitor to profile is a friend and is not the owner can send you a pm
  if($isOwner == "No"){
    $npm .= '
      <div id="pmform">
        <div id="oall">
          <p style="margin-top: 0; color: #999;">
            Send a private message to '.$u.' <button onclick="closePM()" style="float: right;
              border: 0; background-color: transparent; margin-top: -5px; font-size: 12px;">
              X</button>
          </p>
          <div id="pmf_w">
            <input id="pmsubject" class="pmInput" onkeyup="statusMax(this,250)"
              placeholder="Subject of Private Message">
            <textarea id="pmtext" class="pmInput" onkeyup="statusMax(this,65000)"
              placeholder="Send '.$u.' a private message"></textarea></div>
            <div id="uploadDisplay_SP_pm"></div>
            <div id="pbc">
              <div id="progressBar"></div>
              <div id="pbt"></div>
            </div>
            <div id="btnsSP_pm">
            <button id="pmBtn" class="main_btn_fill fixRed" style="float: left;
              margin-top: 10px; margin-bottom: 10px;"
              onclick="postPm(\''.$u.'\',\''.$log_username.'\',\'pmsubject\',\'pmtext\')">
              Send
            </button>
            <img src="/images/camera.png" id="triggerBtn_SP_pm" class="triggerBtnreply"
              onclick="triggerUpload(event, \'fu_SP_pm\')" width="22" height="22"
              title="Upload A Photo" />
            <img src="/images/emoji.png" class="triggerBtn pmem" width="22" height="22"
              title="Send emoticons" id="emoji" onclick="openEmojiBox(\'emojiBox_pm\')"></div>
            <div class="clear"></div>
    ';
    $npm .= generateEList("none", 'emojiBox_pm', 'pmtext');
    $npm .= '</div>';
    $npm .= '</div>';
    $npm .= '
      <div id="standardUpload" class="hiddenStuff">
        <form id="image_SP_pm" enctype="multipart/form-data" method="POST">
          <input type="file" name="FileUpload_pm[]" id="fu_SP_pm" multiple="multiple"
            onchange="doUpload(\'fu_SP_pm\', \'uploadDisplay_SP_pm\', \'triggerBtn_SP\',
            \'stPic_pm\', \'upload_complete_pm\')"/>
        </form>
      </div>
    </div>
    ';
  }
?>
<script src="/js/specific/insert_emoji.js"></script>
<script src="/js/specific/p_dialog.js"></script>
<script src="/js/specific/status_max.js"></script>
<script src="/js/specific/post_reply.js"></script>
<script src="/js/specific/error_dialog.js"></script>
<script src="/js/specific/pm.js"></script>
<script type="text/javascript">
  var hasImage = "";
</script>

<?php echo $pm_ui; ?>
