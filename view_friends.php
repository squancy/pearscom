<?php
    require_once 'php_includes/check_login_statues.php';
    require_once 'timeelapsedstring.php';
    require_once 'headers.php';
    // Initialize any variables that the page might echo
    $u = "";
    $one = "1";
    $c = "c";
    $a = "a";
    $b = "b";
    $one = "1";
    $max = 14;
    // Make sure the _GET username is set and sanitize it
    if(isset($_GET["u"])){
        $u = mysqli_real_escape_string($conn, $_GET["u"]);
    }else{
        header('Location: /index');
        exit();
    }

    // This first query is just to get the total count of rows
    $sql = "SELECT COUNT(id) FROM friends WHERE user1 = ? OR user2 = ? AND accepted = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$u,$u,$one);
    $stmt->execute();
    $stmt->bind_result($rows);
    $stmt->fetch();
    $stmt->close();
    // Here we have the total row count
    // This is the number of results we want displayed per page
    $page_rows = 70;
    // This tells us the page number of our last page
    $last = ceil($rows/$page_rows);
    // This makes sure $last cannot be less than 1
    if($last < 1){
        $last = 1;
    }
    // Establish the $pagenum variable
    $pagenum = 1;
    // Get pagenum from URL vars if it is present, else it is = 1
    if(isset($_GET['pn'])){
        $pagenum = preg_replace('#[^0-9]#', '', $_GET['pn']);
    }
    // This makes sure the page number isn't below 1, or more than our $last page
    if ($pagenum < 1) { 
        $pagenum = 1; 
    } else if ($pagenum > $last) { 
        $pagenum = $last; 
    }
    // This sets the range of rows to query for the chosen $pagenum
    $limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
    // Establish the $paginationCtrls variable
    $paginationCtrls = '';
    // If there is more than 1 page worth of results
    if($last != 1){
        /* First we check if we are on page one. If we are then we don't need a link to 
           the previous page or the first page so we do nothing. If we aren't then we
           generate links to the first page, and to the previous page. */
        if ($pagenum > 1) {
            $previous = $pagenum - 1;
            $paginationCtrls .= '<a href="/view_friends/'.$u.'&pn='.$previous.'&otype='.$otype.'">Previous</a> &nbsp; &nbsp; ';
            // Render clickable number links that should appear on the left of the target page number
            for($i = $pagenum-4; $i < $pagenum; $i++){
                if($i > 0){
                    $paginationCtrls .= '<a href="/view_friends/'.$u.'&pn='.$i.'&otype='.$otype.'">'.$i.'</a> &nbsp; ';
                }
            }
        }
        // Render the target page number, but without it being a link
        $paginationCtrls .= ''.$pagenum.' &nbsp; ';
        // Render clickable number links that should appear on the right of the target page number
        for($i = $pagenum+1; $i <= $last; $i++){
            $paginationCtrls .= '<a href="/view_friends/'.$u.'&pn='.$i.'&otype='.$otype.'">'.$i.'</a> &nbsp; ';
            if($i >= $pagenum+4){
                break;
            }
        }
        // This does the same as above, only checking if we are on the last page, and then generating the "Next"
        if ($pagenum != $last) {
            $next = $pagenum + 1;
            $paginationCtrls .= ' &nbsp; &nbsp; <a href="/view_friends/'.$u.'&pn='.$next.'&otype='.$otype.'">Next</a> ';
        }
    }

    // Select the member from the users table
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$one);
    $stmt->execute();
    $result = $stmt->get_result();

    // Now make sure the user exists in the table
    if($result->num_rows < 1){
        header('location: /usernotexist');
        exit();
    }

    // Check to see if the viewer is the account owner
    $isOwner = "No";
    if($u == $log_username && $user_ok == true){
        $isOwner = "Yes";
    }
?>
<?php
    $otype = "sort_4";
    $friendsHTML = '';
    $friends_view_all_link = '';
    $sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND accepted=? OR user2=? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss",$u,$one,$u,$one);
    $stmt->execute();
    $stmt->bind_result($friend_count);
    $stmt->fetch();
    $stmt->close();
    if($friend_count < 1){
        if($isOwner == "Yes"){
            $friendsHTML = '<p style="color: #999;" class="txtc">It seems that you have no friends currently. Check your <a href="/friend_suggestions">friend suggestions</a> in order to get new ones.</p>';
        }else{
            $friendsHTML = '<p style="color: #999;" class="txtc">'.$u.' has no friends yet</p>';
        }
    } else {
        if(isset($_GET["otype"]) || $otype != ""){
            if(isset($_GET["otype"])){
                $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
            }
            $all_friends = array();
            $sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND accepted=? $limit";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$u,$one);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                array_push($all_friends, $row["user1"]);
            }
            $stmt->close();

            $sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND accepted=? $limit";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$u,$one);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                array_push($all_friends, $row["user2"]);
            }
            $stmt->close();
            $impActive = array_reverse($all_friends);
            $imp = $impActive = join("','",$all_friends);
            $isOnLine = "";
            if($otype == "sort_0"){
                $isOnLine = "yes";
                $sql = "SELECT * FROM users WHERE username IN('$imp') AND online = ? ORDER BY username $limit";
            }else if($otype == "sort_1"){
                $isOnLine = "no";
                $sql = "SELECT * FROM users WHERE username IN('$imp') AND online = ? ORDER BY username $limit";
            }else if($otype == "sort_2"){
                $sql = "SELECT DISTINCT u.* FROM users AS u LEFT JOIN friends AS f ON u.username = f.user1 WHERE u.username IN('$imp') ORDER BY f.datemade DESC $limit";
            }else if($otype == "sort_3"){
                $sql = "SELECT DISTINCT u.* FROM users AS u LEFT JOIN friends AS f ON u.username = f.user1 WHERE u.username IN('$imp') ORDER BY f.datemade ASC $limit";
            }else if($otype == "sort_4"){
                $sql = "SELECT * FROM users WHERE username IN('$imp') ORDER BY username $limit";
            }else if($otype == "sort_5"){
                $sql = "SELECT * FROM users WHERE username IN('$imp') ORDER BY username DESC $limit";
            }else if($otype == "sort_6"){
                $sql = "SELECT * FROM users WHERE username IN('$imp') ORDER BY country $limit";
            }else if($otype == "sort_7"){
                $sql = "SELECT * FROM users WHERE username IN('$imp') ORDER BY country DESC $limit";
            }

            $stmt = $conn->prepare($sql);
            if($otype == "sort_0" || $otype == "sort_1"){
                $stmt->bind_param("s",$isOnLine);
            }
            $stmt->execute();
            $result3 = $stmt->get_result();
            while($row = $result3->fetch_assoc()) {
                $friend_username = $row["username"];
                $friend_avatar = $row["avatar"];
                $friend_online = $row["online"];
                $friend_country = $row["country"];
                $bday = $row["bday"];
                $echo_online = "";
                if($friend_online == "yes"){
                    $echo_online = '<b style="font-weight: normal; color: green;">online <img src="/images/wgreen.png" class="notfimg" style="margin-bottom: -2px;"></b>';
                }else{
                    $echo_online = '<b style="font-weight: normal; color: #999;">offline <img src="/images/wgrey.png" class="notfimg" style="margin-bottom: -2px;"></b>';
                }
                if($friend_avatar != ""){
                    $friend_pic = '/user/'.$friend_username.'/'.$friend_avatar.'';
                } else {
                    $friend_pic = '/images/avdef.png';
                }

                $age = floor((time() - strtotime($bday)) / 31556926);

                $friendsHTML .= '<div><a href="/user/'.$friend_username.'/"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; display: inline-block; float: left; width: 60px; height: 60px; border-radius: 50px; margin-bottom: 0;" class="friendpics lazy-bg"></div></a><div id="contviewf" style="width: calc(100% - 80px); margin-left: 10px;"><p><span>'.$friend_username.'<br /></span><span>'.$friend_country.'<br /></span>'.$age.' years old</p></div></div>';
            }
            $stmt->close();
            if(isset($_GET["otype"])){
                echo $friendsHTML;
                exit();
            }
        }
    }

    $sql = "SELECT COUNT(id) FROM users WHERE username IN('$imp')";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($countFriends);
    $stmt->fetch();
    $stmt->close();

    $yes = "yes";
    $sql = "SELECT COUNT(id) FROM users WHERE username IN('$imp') AND online = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$yes);
    $stmt->execute();
    $stmt->bind_result($countOFriends);
    $stmt->fetch();
    $stmt->close();
    
    $toggle = "no";
    if($u == $log_username){
        $toggle = "yes";
    }

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $u; ?>'s all friends</title>
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <meta name="description" content="Check <?php echo $u; ?>'s all friends.">
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    <script src="/js/dialog.js" async></script>
    	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
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
        let origin = "<?php echo $toggle; ?>";
    
        function getMyFLArr()
            /*Scope Closed:false | writes:true*/
            {
                var a = _('searchArt').value;
                if (a == '')
                    return false;
                var r = encodeURI(a);
                if(origin == "no") window.location = '/search_friends/' + encodeURI(a) + "&origin=<?php echo $u; ?>";
                else window.location = '/search_friends/' + encodeURI(a);
            }
        var uname = '<?php echo $u; ?>';

        function getFriends(e) {
          if ("" == e) {
            return _("frSearchResult").style.display = "none", false;
          }
          _("frSearchResult").style.display = "block";
          if ("" == _("frSearchResult").innerHTML) {
            _("frSearchResult").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
          }
          var str = encodeURI(e);
          var mypostrequest = new XMLHttpRequest;
          mypostrequest.open("POST", "/searchn_friends.php", true);
          mypostrequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          mypostrequest.onreadystatechange = function() {
            if (4 == mypostrequest.readyState && 200 == mypostrequest.status) {
              var iconValue = mypostrequest.responseText;
              if ("" != iconValue) {
                _("frSearchResult").innerHTML = iconValue;
              }
            }
          };
          mypostrequest.send("u=" + str + "&imp=<?php echo $imp; ?>");
        }
    </script>
</head>
<body>
    <?php include_once("template_pageTop.php"); ?>
    <div id="pageMiddle_2">
        <div id="artSearch">
            <div id="artSearchInput">
                <input id="searchArt" type="text" autocomplete="off" onkeyup="getFriends(this.value)" placeholder="Search among friends">
                <div id="artSearchBtn" onclick="getMyFLArr()"><img src="/images/searchnav.png" width="17" height="17"></div>
            </div>
            <div class="clear"></div>
        </div>

        <div id="frSearchResult" class="longSearches"></div>

        <div id="data_holder">
            <div>
                <div><span><?php echo $countFriends; ?></span> friends</div>
                <div><span><?php echo $countOFriends; ?></span> online</div>
            </div>
        </div>

        <button id="sort" class="main_btn_fill">Filter Friends</button>
        <div id="sortTypes">
            <div class="gridDiv">
                <p class="mainHeading">Activity</p>
                <div id="sort_0">Online</div>
                <div id="sort_1">Offline</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Relation length</p>
                <div id="sort_2">new friends to oldest</div>
                <div id="sort_3">old friends to newest</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Username</p>
                <div id="sort_4">Alphabetical order</div>
                <div id="sort_5">Reverese alphabetical order</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Country</p>
                <div id="sort_6">Alphabetical order</div>
                <div id="sort_7">Reverse alphabetical order</div>
            </div>
            <div class="clear"></div>
        </div>
        <div class="clear"></div>
        <hr class="dim">
      <div id="momofdif" class="flexibleSol">
        <?php echo $friendsHTML; ?>
        </div>
      <div class="clear"></div>
    </div>
    </div>
    <?php require_once 'template_pageBottom.php'; ?>
    <script type="text/javascript">
        function getCookie(e){for(var t=e+"=",r=decodeURIComponent(document.cookie).split(";"),a=0;a<r.length;a++){for(var s=r[a];" "==s.charAt(0);)s=s.substring(1);if(0==s.indexOf(t))return s.substring(t.length,s.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],r=document.createElement("link");r.id=e,r.rel="stylesheet",r.type="text/css",r.href="/style/dark_style.css",r.media="all",t.appendChild(r)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();

        $( "#sort" ).click(function() {
          $( "#sortTypes" ).slideToggle( 200, function() {
            // Animation complete.
          });
        });

    for(let i = 0; i < 8; i++){
        addListener("sort_" + i, "sort_" + i);
    }

    function addListener(onw, w){
        _(onw).addEventListener("click", function(){
            _("momofdif").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
            filterArts(w);
        });
    }

    function filterArts(otype){
        changeStyle(otype);
        let req = new XMLHttpRequest();
        req.open("GET", "/view_friends.php?u=<?php echo $u; ?>&otype=" + otype, false);
        req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        req.onreadystatechange = function(){
            if(req.readyState == 4 && req.status == 200){
                _("momofdif").innerHTML = req.responseText;
            }
        }
        req.send();
    }

    function changeStyle(otype){
        _(otype).style.color = "red";
        for(let i = 0; i < 8; i++){
            if("sort_" + i != otype) _("sort_" + i).style.color = "black";
        }
    }

    changeStyle("sort_4");
    </script>
</body>
</html>