<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';
  
  if(!isset($_SESSION["username"])){
      header('location: /needlogged');
      exit();
  }

function chooseCat($cat){
      if($cat == 1){
      $cat = "Animals";
    }else if($cat == 2){
      $cat = "Relationships";
    }else if($cat == 3){
      $cat = "Friends &amp; Family";
    }else if($cat == 4){
      $cat = "Freetime";
    }else if($cat == 5){
      $cat = "Sports";
    }else if($cat == 6){
      $cat = "Games";
    }else if($cat == 7){
      $cat = "Knowledge";
    }else if($cat == 8){
      $cat = "Other";
    }
    return $cat;
  }	
  
    // Select the member from the users table
    $one = "1";
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    // Now make sure the user exists in the table
    if($numrows < 1){
      header('location: /usernotexist');
      exit();
    }
    $stmt->close();

  // Sanitize some vars
  $one = "1";

  // Get users's groups
  $myarray = array();
  $mygroups = "";
  $sql = "SELECT gm.*, g.* FROM gmembers AS gm LEFT JOIN groups AS g ON gm.gname = g.name WHERE gm.mname = ? ORDER BY gm.id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  while($row = $result->fetch_assoc()){
    $gname = $row["gname"];
    $gnameori = $gname;
    $gnameori = urlencode($gnameori);
    $gnameim = $gname;
    $logo = $row["logo"];
    $est = $row["creation"];
    $des = $row["des"];
    $creatorMy = $row["creator"];
    $est_ = strftime("%R, %b %d, %Y", strtotime($est));
    $agoform = time_elapsed_string($est);
    if($logo != "gdef.png"){
      $logo = '/groups/'.$gnameim.'/'.$logo.'';
    }else{
      $logo = '/images/gdef.png';
    }

    if($des == NULL || $des == ""){
      $des = "not given";
    }

    $cat = chooseCat($row["cat"]); 

    array_push($myarray, $gnameim);

    $mygroups .= '<a href="/group/'.$gnameori.'"><div class="article_echo_2" style="width: 100%"><div data-src=\''.$logo.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 80px; float: right; border-radius: 50%;" class="lazy-bg"></div><div><p class="title_"><b>Name: </b>'.$gname.'</p>';
    $mygroups .= '<p class="title_"><b>Creator: </b>'.$creatorMy.'</p>';
    $mygroups .= '<p class="title_"><b>Established: </b>'.$agoform.' ago</p>';
    $mygroups .= '<p class="title_"><b>Description: </b>'.$des.'</p>';
    $mygroups .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
  }
  $stmt->close();
  
  $ismyis = false;
  if($mygroups == ""){
      $mygroups = '<p style="color: #999; text-align: center;">You are not in any groups at the moment. Create your own group or join to an existing one.</p>';
      $ismyis = true;
  }
  
  $myarr = join("','",$myarray);

  // Get related groups
  // FIRST GET FRIENDS
  $isrel = false;
  $rgroups = "";
  $all_friends = array();
  $sql = "SELECT user1 FROM friends WHERE user2 = ? AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    array_push($all_friends, $row["user1"]);
  }
  $stmt->close();

  $sql = "SELECT user2 FROM friends WHERE user1 = ? AND accepted=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$one);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    array_push($all_friends, $row["user2"]);
  }
  $stmt->close();
  // Implode all friends array into a string
  $allfmy = join("','", $all_friends);
  $sql = "SELECT DISTINCT gr.* FROM gmembers AS gm LEFT JOIN groups AS gr ON gr.name = gm.gname WHERE gm.mname IN ('$allfmy') AND gm.mname != ? AND gr.creator != ? AND gr.name NOT IN ('$myarr') ORDER BY RAND() LIMIT 30";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$log_username,$log_username);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $logo = $row["logo"];
      $groupname = $row["name"];
      $gnameori = $groupname;
      $gnameori = urlencode($gnameori);
      $gnameim = $groupname;
      $est = $row["creation"];
      $creatorMy = $row["creator"];
      $est_ = strftime("%R, %b %d, %Y", strtotime($est));
      $agoform = time_elapsed_string($est);
      $des = $row["des"];

      if($logo != "gdef.png"){
        $logo = '/groups/'.$gnameim.'/'.$logo.'';
      }else{
        $logo = '/images/gdef.png';
      }

      if($des == NULL || $des == ""){
        $des = "not given";
      }

      $cat = chooseCat($row["cat"]); 

      $rgroups .= '<a href="/group/'.$gnameori.'"><div class="article_echo_2" style="width: 100%"><div data-src=\''.$logo.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 80px; float: right; border-radius: 50%;" class="lazy-bg"></div><div><p class="title_"><b>Name: </b>'.$groupname.'</p>';
      $rgroups .= '<p class="title_"><b>Creator: </b>'.$creatorMy.'</p>';
      $rgroups .= '<p class="title_"><b>Established: </b>'.$agoform.' ago</p>';
      $rgroups .= '<p class="title_"><b>Description: </b>'.$des.'</p>';
      $rgroups .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
    }
    $stmt->close();
  }else{
    $sql = "SELECT gr.* FROM groups AS gr LEFT JOIN gmembers AS gm ON gr.name = gm.gname WHERE gm.mname != ? AND gr.creator != ? AND gr.name NOT IN ('$myarr') ORDER BY RAND() LIMIT 30";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
          $logo = $row["logo"];
          $groupname = $row["name"];
          $gnameori = urlencode($groupname);
          $gnameim = $groupname;
          $est = $row["creation"];
          $creatorMy = $row["creator"];
          $est_ = strftime("%R, %b %d, %Y", strtotime($est));
          $agoform = time_elapsed_string($est);
          $des = $row["des"];
    
          if($logo != "gdef.png"){
            $logo = '/groups/'.$gnameim.'/'.$logo.'';
          }else{
            $logo = '/images/gdef.png';
          }
    
          if($des == NULL || $des == ""){
            $des = "not given";
          }
    
          
      	$cat = chooseCat($row["cat"]); 

    
          $rgroups .= '<a href="/group/"'.$gnameori.'"><div class="article_echo_2" style="width: 100%"><div style="background-image: url(\''.$logo.'\'); background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 80px; float: right; border-radius: 50%;"></div><div><p class="title_"><b>Name: </b>'.$groupname.'</p>';
          $rgroups .= '<p class="title_"><b>Creator: </b>'.$creatorMy.'</p>';
          $rgroups .= '<p class="title_"><b>Established: </b>'.$agoform.' ago</p>';
          $rgroups .= '<p class="title_"><b>Description: </b>'.$des.'</p>';
          $rgroups .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
        }
    }else{
        $isrel = true;
      $rgroups = '<p style="color: #999; text-align: center;">Unfortunately there are no related groups at the moment ...</p>';
    }
  }
  $stmt->close();

  $sql = "SELECT COUNT(id) FROM groups";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $stmt->bind_result($gr_cnt);
  $stmt->fetch();
  $stmt->close();

  $sql = "SELECT COUNT(id) FROM gmembers WHERE mname = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $log_username);
  $stmt->execute();
  $stmt->bind_result($my_cnt);
  $stmt->fetch();
  $stmt->close();

  $sql = "SELECT COUNT(id) FROM groups WHERE creator = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $log_username);
  $stmt->execute();
  $stmt->bind_result($cre_cnt);
  $stmt->fetch();
  $stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $log_username; ?> - Groups</title>
  <meta charset="utf-8">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="/js/jjs.js"></script>
  <script src="/js/create_down.js"></script>
  	  <link rel="manifest" href="/manifest.json">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-status-bar-style" content="red">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
  <style type="text/css">
        @media only screen and (max-width: 1000px){ 
          #searchArt{
            width: 90% !important;
          }

          #artSearchBtn{
            width: 10% !important;
          }

          .longSearches{
            width: calc(90% - 15px) !important;
            }

          @media only screen and (max-width: 500px){
            #searchArt {
            width: 85% !important;
          }

          #artSearchBtn {
            width: 15% !important;
          }

          .longSearches{
            width: calc(100% - 30px) !important;
            }
        }
    }
    </style>
  <script type="text/javascript">
    function createGroup() {
      var e = _("status"),
          a = _("grname").value,
          n = _("invite").value,
          r = _("gcat").value;

      if ("" == a || "" == n || "" == r) return e.innerHTML = 'Please fill in all fields', !1;
      e.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
      var t = ajaxObj("POST", "/php_parsers/group_parser2.php");
      t.onreadystatechange = function () {
          if (1 == ajaxReturn(t)) {
              var e = t.responseText.split("|");
              if ("group_created" == e[0]) {
                  var a = e[1];
                  window.location = "/group/" + encodeURI(a)
              } else  e.innerHTML = 'Unfortunately, an error occurred during the data processing'
          }
      }, t.send("action=new_group&name=" + a + "&inv=" + n + "&cat=" + r)
  }
  function checkGname() {
      var e = _("grname").value;
      if ("" != e) {
          var a = ajaxObj("POST", "/php_parsers/group_parser2.php");
          a.onreadystatechange = function () {
              1 == ajaxReturn(a) && (_("gnamestatus").innerHTML = a.responseText)
          }, a.send("gnamecheck=" + e)
      }
  }
  function checkCat() {
      var e = _("gcat").value;
      if ("" != e) {
          var a = ajaxObj("POST", "/php_parsers/group_parser2.php");
          a.onreadystatechange = function () {
              1 == ajaxReturn(a) && (_("catstatus").innerHTML = a.responseText)
          }, a.send("catcheck=" + e)
      }
  }
  function checkType() {
      var e = _("invite").value;
      if ("" != e) {
          var a = ajaxObj("POST", "/php_parsers/group_parser2.php");
          a.onreadystatechange = function () {
              1 == ajaxReturn(a) && (_("typestatus").innerHTML = a.responseText)
          }, a.send("typecheck=" + e)
      }
  }
  function getGroups(e) {
      if ("" == e) return _("grSearchResult").style.display = "none", !1;
      _("grSearchResult").style.display = "block", "" == _("grSearchResult").innerHTML && (_("grSearchResult").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">');
      var a = encodeURI(e),
          n = new XMLHttpRequest;
      n.open("POST", "/search_exec_group.php", !0), n.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), n.onreadystatechange = function () {
          if (4 == n.readyState && 200 == n.status) {
              var e = n.responseText;
              "" != e && (_("grSearchResult").innerHTML = e)
          }
      }, n.send("g=" + a)
  }
  function getLSearchGrs() {
      var e = _("searchArt").value;
      if ("" == e) return _("grSearchResult").style.display = "none", !1;
      var a = encodeURI(e);
      window.location = "/search_groups/" + a
  }

  $( "#createme" ).click(function() {
      $( "#downdiv" ).slideToggle( 200, function() {
        // Animation complete.
      });
    });
  </script>
</head>
<body>
  <?php require_once 'template_pageTop.php'; ?>
  <div id="pageMiddle_2">
    <div id="artSearch">
        <div id="artSearchInput">
            <input id="searchArt" type="text" class="lsearch" autocomplete="off" onkeyup="getGroups(this.value)" placeholder="Search for groups by their name or category">
            <div id="artSearchBtn" onclick="getLSearchGrs()"><img src="/images/searchnav.png" width="17" height="17"></div>
        </div>
        <div class="clear"></div>
    </div>
    <div id="grSearchResult" class="longSearches"></div>
    <div id="data_holder">
        <div>
            <div><span><?php echo $gr_cnt; ?></span> groups</div>
            <div><span><?php echo $my_cnt; ?></span> groups as member</div>
            <div><span><?php echo $cre_cnt; ?></span> created groups</div>
        </div>
    </div>

    <button class="grlongbtn main_btn_fill" id="createme">Create group</button>
    <div class="createcontent" id="downdiv" style="display: none; margin-top: 10px;">
        <form id="groupsform" name="groupsform" class="styleform" style="padding: 30px;" onclick="return false;">
            <input type="text" id="grname" placeholder="Give a name for your group" onblur="checkGname()">
            <span class="signupStats" id="gnamestatus" style="right: 10px;"></span>

            <select id="gcat" onblur="checkCat()" style="width: calc(50% - 3px);">
                <option value="" disabled="true" selected="true">Choose category</option>
                <option value="1">Animals</option>
                <option value="2">Relationships</option>
                <option value="3">Friends &amp; Family</option>
                <option value="4">Freetime</option>
                <option value="5">Sports</option>
                <option value="6">Games</option>
                <option value="7">Knowledge</option>
                <option value="8">Other</option>
            </select>
            <span class="signupStats" id="catstatus" style="right: 10px;"></span>

            <select id="invite" onblur="checkType()" style="width: calc(50% - 3px);">
                <option value="" disabled="true", selected="true">Choose type</option>
                <option value="0">Private group</option>
                <option value="1">Public group</option>
            </select>
            <span class="signupStats" id="typestatus" style="right: 10px;"></span>
            <br /><br />

            <button id="newGroupBtn" onclick="createGroup()" class="main_btn redBtn">Submit</button>
            <div id="status" style="text-align: center; color: #999; margin-top: 20px;"></div>
        </form>
    </div>
    <hr class="dim">
    <div class="collection" id="ccSu" style="border-bottom: 1px solid rgba(0, 0, 0, 0.05);">
      <p style="font-size: 18px;" id="grinfo">What should I know about groups?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="suDD" style="font-size: 14px;">
        <p style="margin-bottom: 0px;">&bull; Creating a group: you can easily create a group by clicking on the Create group button. Then you need to add 3 things. A name for your group, a category and a join type.</p>
        <p style="margin-left: 20px;">&bull; Group name: this will describe the topic of your group (e.g. Car fans). Please note that it has to be between 3 and 100 characters and it mustn&#39;t contain any special characters. We also recommend that to choose a short and clean group name. For further informations please visit our <a href="/help#groups">help</a> page.
            <br /><br />&bull; Group category: this will describe your group in a little bit more detailed (e.g. if your group name is Car fans then you can choose freetime for a category). Users will also find it easier to search for groups in a certain category for their interests.
            <br /><br />&bull; Join type: in this option you can decide that your group will be public or private. It's a very important option, so decide carefully. If you choose the &#34;By simply joining (public group)&#34; option your group will be public which means everyone can join to it without any requests or approvals. We recommend this option for everyone who wants to create a great community as a group where strange people can meet each other etc. The other option is the &#34;By request to join (private group)&#34; one. By choosing this option you create a private and closed group where only and only those people can join who send a request and you accept that. This can be very useful for families and friends or for student groups where only they can communicate.</p>
        <p>&bull; Why groups are incredibly powerful: You can join to public groups where you can meet new people, talk with each other or check some more related groups which you might interested in. There are private groups, too where your family, friends or relatives can communicate. In addition you are able to send tons of emojis, images and text. If you like someone&#39;s comment or post you can like it. The admins can change the group of avatars which is highly recommended to be something different than the default image to be more recignizable for the others.
            <br /><br />&bull; Permissions in groups: in every group there are 3 types of members. The admin - who is the creator of the group - moderators - who are semi-admins - and simple members - who only have some laws.
            <br />
        </p>
        <p style="margin-left: 20px;">&bull; Admins: he/she is the creator and the leader of the group who has every permissions. This position cannot be changed and a group can have only <b>ONE</b> admin. He/she can change the avatar of the group, accept or decline pending approvals and kick members. Every admin has their name with yellow color.
            <br /><br />&bull; Moderators: they are promoted members by the admin and they also have every permissions except changing the avatar. There can have an unlimited number of moderators in a group and they can also kick other members and accept or decline pending approvals. Every moderator has their name with blue color.
            <br /><br />&bull; Members: members are the bottom of the rank level therefore they can only post messages and nothing more. Every member has their name with grey color.
            <br /><br />&bull; Please keep in mind that if you join to a group you will be a part of a community therefore please do not spam, kick members randomly or anything like this. Try to keep your group(s) and Pearscom clean and friendly! Thanks!</p>
        <p>&bull; Questions &amp; feedback: if you have any questions visit the <a href="/help#groups">help</a> page of groups or if you want to send a more specified message or feedback you can do it by clicking <a href="/help#report">here.</a></p>
    </div>
    <p style='font-size: 18px; padding-bottom: 0px; text-align: center;'><a href="/view_all_groups">My groups</a></p>
    <div id="userFlexArts" class="flexibleSol">
        <?php echo $mygroups; ?>
    </div>
    <div class="clear"></div>
    <hr class="dim">
    
    <p style='font-size: 18px; padding-bottom: 0px; text-align: center;'>Suggested groups</p>
    <div id="userFlexArts" class="flexibleSol">
      <?php echo $rgroups; ?>
    </div>
</div>
<div class="clear"></div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    function getCookie(e){for(var r=e+"=",t=decodeURIComponent(document.cookie).split(";"),s=0;s<t.length;s++){for(var i=t[s];" "==i.charAt(0);)i=i.substring(1);if(0==i.indexOf(r))return i.substring(r.length,i.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var r=document.getElementsByTagName("head")[0],t=document.createElement("link");t.id=e,t.rel="stylesheet",t.type="text/css",t.href="/style/dark_style.css",t.media="all",r.appendChild(t)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();

function doDD(first, second){
        $( "#" + first ).click(function() {
          $( "#" + second ).slideToggle( "fast", function() {
            
          });
        });
      }

      doDD("ccSu", "suDD");
  </script>
</body>
</html>
