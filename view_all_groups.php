<?php 
    require_once 'php_includes/check_login_statues.php';
    require_once 'timeelapsedstring.php';
    require_once 'headers.php';
    if($user_ok != true || $log_username == ""){
        header('Location: /index');
        exit();
    }
    $u = $_SESSION['username'];

    $isOwner = "no";
    if($u == $log_username && $user_ok == true){
        $isOwner = "yes";
    }

    $otype = "grs_0";

    // Pagination
    // This first query is just to get the total count of rows
    $sql = "SELECT COUNT(gp.id)
            FROM gmembers AS gm
            LEFT JOIN groups AS gp ON gp.name = gm.gname
            LEFT JOIN users AS u ON gm.mname = u.username 
            WHERE gm.mname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($rows);
    $stmt->fetch();
    $stmt->close();
    // Here we have the total row count
    // This is the number of results we want displayed per page
    $page_rows = 30;
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
            $paginationCtrls .= '<a href="view_all_groups.php?u='.$u.'&pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
            // Render clickable number links that should appear on the left of the target page number
            for($i = $pagenum-4; $i < $pagenum; $i++){
                if($i > 0){
                    $paginationCtrls .= '<a href="view_all_groups.php?u='.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
                }
            }
        }
        // Render the target page number, but without it being a link
        $paginationCtrls .= ''.$pagenum.' &nbsp; ';
        // Render clickable number links that should appear on the right of the target page number
        for($i = $pagenum+1; $i <= $last; $i++){
            $paginationCtrls .= '<a href="view_all_groups.php?u='.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
            if($i >= $pagenum+4){
                break;
            }
        }
        // This does the same as above, only checking if we are on the last page, and then generating the "Next"
        if ($pagenum != $last) {
            $next = $pagenum + 1;
            $paginationCtrls .= ' &nbsp; &nbsp; <a href="view_all_groups.php?u='.$u.'&pn='.$next.'">Next</a> ';
        }
    }

    if(isset($_GET["otype"]) || $otype == "grs_0"){
        if(isset($_GET["otype"])){ $otype = mysqli_real_escape_string($conn, $_GET["otype"]); }
        $my_all_list = "";
        $cond = "";
        $grCat = "";
        $grType = "";
        if($otype == "grs_0"){
            $cond = "ORDER BY gp.creation DESC";
        }else if($otype == "grs_1"){
            $cond = "ORDER BY gp.creation ASC";
        }else if($otype == "grs_2"){
            $cond = "ORDER BY gp.creator";
        }else if($otype == "grs_3"){
            $cond = "ORDER BY gp.creator DESC";
        }else if($otype == "grs_4"){
            $grCat = "1";
            $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_5"){
            $grCat = "2";
            $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_6"){
            $grCat = "3";
            $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_7"){
            $grCat = "4";
            $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_8"){
            $grCat = "5";
            $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_9"){
            $grCat = "6";
            $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_10"){
            $grCat = "7";
            $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_11"){
            $grCat = "8";
            $cond = "AND gp.cat = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_12"){
            $grType = "1";
            $cond = "AND gp.invrule = ? ORDER BY gp.name DESC";
        }else if($otype == "grs_13"){
            $grType = "0";
            $cond = "AND gp.invrule = ? ORDER BY gp.name DESC";
        }
        $sql = "SELECT gm.*, gp.*, u.*
            FROM gmembers AS gm
            LEFT JOIN groups AS gp ON gp.name = gm.gname
            LEFT JOIN users AS u ON gm.mname = u.username 
            WHERE gm.mname = ? $cond $limit";
        $stmt = $conn->prepare($sql);
        
        if($otype == "grs_0" || $otype == "grs_1"){
            $stmt->bind_param("s",$u);
        }else if($otype != "grs_0" && $otype != "grs_1" && $otype != "grs_2" && $otype != "grs_3" && $otype != "grs_12" && $otype != "grs_13"){
            $stmt->bind_param("ss",$u,$grCat);
        }else{
            $stmt->bind_param("ss",$u,$grType);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0){
          while ($row = $result->fetch_assoc()) {
            $name = $row["name"];
            $nameori = urlencode($name);
            $nameim = $name;
            $creation = $row["creation"];
            $creation_new = strftime("%b %d, %Y", strtotime($creation));
            $creator = $row["creator"];
            $creatorori = urlencode($creator);
            $agoform = time_elapsed_string($creation);
            $invrule = $row["invrule"];
            $cat = $row["cat"];
            $logo = $row["logo"];

            if($logo != "gdef.png" && $logo != NULL){
                $logo = '/groups/'.$nameim.'/'.$logo;
            }else if($logo == NULL || $logo == "gdef.png"){
                $logo = '/images/gdef.png';
            }

            if($invrule == 1){
                $invrule_new = "Public group";
            }else{
                $invrule_new = "Private group";
            }

            switch ($cat) {
                case '1':
                    $cat = "Animals";
                    break;
                
                case '2':
                    $cat = "Relationships";
                    break;

                case '3':
                    $cat = "Friends &amp; Family";
                    break;

                case '4':
                    $cat = "Freetime";
                    break;

                case '5':
                    $cat = "Sports";
                    break;

                case '6':
                    $cat = "Games";
                    break;

                case '7':
                    $cat = "Knowledge";
                    break;

                case '8':
                    $cat = "Other";
                    break;
            }

            $my_all_list .= '<a href="/group/'.$nameori.'"><div class="article_echo_2" style="width: 100%;"><div data-src=\''.$logo.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 80px; float: right; border-radius: 50%;" class="lazy-bg"></div><div><p class="title_"><b>Name: </b>'.$name.'</p>';
              $my_all_list .= '<p class="title_"><b>Creator: </b>'.$creator.'</p>';
              $my_all_list .= '<p class="title_"><b>Created: </b>'.$agoform.' ago</p>';
              $my_all_list .= '<p class="title_"><b>Type: </b>'.$invrule_new.'</p>';
              $my_all_list .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
          }
        }else{
            $my_all_list = "<p style='text-align: center; color: #999;'>Unfortunately, there are no such groups fitting the criteria<p>";
        }
        if(isset($_GET["otype"])){
                echo $my_all_list;
                exit();
              }
    }

    $sql = "SELECT COUNT(id) FROM gmembers WHERE mname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($cnt_gr);
    $stmt->fetch();
    $stmt->close();
    if($cnt_gr < 1){
        $my_all_list = "<p style='text-align: center; color: #999;'>You are not in any groups at the moment. <a href='/groups'>Create</a> your own one or <a href='/groups'>join</a> to an existing one!<p>";
    }
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
    <title>View My All Groups</title>
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
    <script src="/js/jjs.js" async></script>
</head>
<body>
    <?php require_once 'template_pageTop.php'; ?>
    <div id="pageMiddle_2">
        <div id="data_holder">
            <div>
                <div><span><?php echo $my_cnt; ?></span> groups as member</div>
                <div><span><?php echo $cre_cnt; ?></span> created groups</div>
            </div>
        </div>
        <button id="sort" class="main_btn_fill">Filter Groups</button>
        <div id="sortTypes">
            <div class="gridDiv">
                <p class="mainHeading">Establishment date</p>
                <div id="grs_0">Newest to oldest</div>
                <div id="grs_1">Oldest to newest</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Category</p>
                <div id="grs_4">Animals</div>
                <div id="grs_5">Relationships</div>
                <div id="grs_6">Friends &amp; Family</div>
                <div id="grs_7">Freetime</div>
                <div id="grs_8">Sports</div>
                <div id="grs_9">Games</div>
                <div id="grs_10">Knowledge</div>
                <div id="grs_11">Others</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Creator</p>
                <div id="grs_2">Alphabetical order</div>
                <div id="grs_3">Reverse alphabetical order</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Type</p>
                <div id="grs_12">Public groups</div>
                <div id="grs_13">Private groups</div>
            </div>
            <div class="clear"></div>
        </div>
        <div class="clear"></div>
        <hr class="dim">
        <hr id="sepHr">
        <div id="userFlexArts" class="flexibleSol"><?php echo $my_all_list; ?></div>
        <div class="clear"></div>
        <div id="paginationCtrls" style="text-align: center;"><?php echo $paginationCtrls; ?></div>
    </div>
    <?php require_once 'template_pageBottom.php'; ?>
    <script type="text/javascript">
var pn="<?php echo $pagenum; ?>";function getCookie(e){for(var n=e+"=",t=decodeURIComponent(document.cookie).split(";"),o=0;o<t.length;o++){for(var i=t[o];" "==i.charAt(0);)i=i.substring(1);if(0==i.indexOf(n))return i.substring(n.length,i.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var n=document.getElementsByTagName("head")[0],t=document.createElement("link");t.id=e,t.rel="stylesheet",t.type="text/css",t.href="/style/dark_style.css",t.media="all",n.appendChild(t)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();
   
    for(let i = 0; i < 14; i++){
        addListener("grs_" + i, "grs_" + i);
    }

    function addListener(onw, w){
        _(onw).addEventListener("click", function(){
            _("userFlexArts").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
            filterArts(w);
        });
    }

    function filterArts(otype){
        changeStyle(otype);
        let req = new XMLHttpRequest();
        req.open("GET", "/view_all_groups.php?otype=" + otype, false);
        req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        req.onreadystatechange = function(){
            if(req.readyState == 4 && req.status == 200){
                _("userFlexArts").innerHTML = req.responseText;
            }
        }
        req.send();
    }

    function changeStyle(otype){
        _(otype).style.color = "red";
        for(let i = 0; i < 14; i++){
            if("grs_" + i != otype) _("grs_" + i).style.color = "black";
        }
    }

    changeStyle("grs_0");

    
    $( "#sort" ).click(function() {
          $( "#sortTypes" ).slideToggle( 200, function() {
            // Animation complete.
          });
        });
    </script>
</body>
</html>