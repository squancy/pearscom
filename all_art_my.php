<?php
  /*
    List all the articles that belong to the user.
    Might search, order or filter their own articles.
  */

  require_once 'php_includes/check_login_statues.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'phpmobc.php';
  require_once 'ccov.php';
  require_once 'headers.php';

  $u = "";
  if(isset($_GET["u"])){
    $u = mysqli_real_escape_string($conn, $_GET["u"]);
  }else{
    header('Location: /index');
    exit();
  }
 
  // Check if user-agent is mobile
  $ismobile = mobc();

  $isOwner = "No";
  if($u == $log_username && $user_ok == true){
    $isOwner = "Yes";
  }

  $one = "1";
  $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss",$u,$one);
  $stmt->execute();
  $stmt->store_result();
  $stmt->fetch();
  $numrows = $stmt->num_rows;

  if($numrows < 1){
    header('location: /usernotexist');
    exit();
  }

  
  // Pagination
  // This first query is just to get the total count of rows
  $sql = "SELECT COUNT(id) FROM articles WHERE written_by=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$u);
  $stmt->execute();
  $stmt->bind_result($rows);
  $stmt->fetch();
  $stmt->close();
  // Here we have the total row count
  // This is the number of results we want displayed per page
  $page_rows = 21;
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
        $paginationCtrls .= '<a href="/all_articles/'.$u.'&pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
        // Render clickable number links that should appear on the left of the target page number
        for($i = $pagenum-4; $i < $pagenum; $i++){
            if($i > 0){
                $paginationCtrls .= '<a href="/all_articles/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
            }
        }
    }
    // Render the target page number, but without it being a link
    $paginationCtrls .= ''.$pagenum.' &nbsp; ';
    // Render clickable number links that should appear on the right of the target page number
    for($i = $pagenum+1; $i <= $last; $i++){
        $paginationCtrls .= '<a href="/all_articles/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
        if($i >= $pagenum+4){
            break;
        }
    }
    // This does the same as above, only checking if we are on the last page, and then generating the "Next"
    if ($pagenum != $last) {
        $next = $pagenum + 1;
        $paginationCtrls .= ' &nbsp; &nbsp; <a href="/all_articles/'.$u.'&pn='.$next.'">Next</a> ';
    }
  }

  // Get the user's all articles ordered by otype
  $catgs = "";
  
  $all_articles = "";
  $otype = "date_0";

  if(isset($_GET["otype"]) || $otype == "date_0"){
  if(isset($_GET["otype"])){ $otype = mysqli_real_escape_string($conn, $_GET["otype"]); }

  if($otype == "date_0"){
    $sql = "SELECT * FROM articles WHERE written_by = ? ORDER BY post_time DESC $limit";
  }else if($otype == "date_1"){
    $sql = "SELECT * FROM articles WHERE written_by = ? ORDER BY post_time ASC $limit";
  }else if($otype == "catgs_0"){
    $catgs = "School";
  }else if($otype == "catgs_1"){
    $catgs = "Business";
  }else if($otype == "catgs_2"){
    $catgs = "Learning";
  }else if($otype == "catgs_3"){
    $catgs = "My Dreams";
  }else if($otype == "catgs_4"){
    $catgs = "Money";
  }else if($otype == "catgs_5"){
    $catgs = "Sports";
  }else if($otype == "catgs_6"){
    $catgs = "Technology";
  }else if($otype == "catgs_7"){
    $catgs = "Video Games";
  }else if($otype == "catgs_8"){
    $catgs = "TV Programmes";
  }else if($otype == "catgs_9"){
    $catgs = "Hobbies";
  }else if($otype == "catgs_10"){
    $catgs = "Music";
  }else if($otype == "catgs_11"){
    $catgs = "Freetime";
  }else if($otype == "catgs_12"){
    $catgs = "Travelling";
  }else if($otype == "catgs_13"){
    $catgs = "Books";
  }else if($otype == "catgs_14"){
    $catgs = "Politics";
  }else if($otype == "catgs_15"){
    $catgs = "Movies";
  }else if($otype == "catgs_16"){
    $catgs = "Lifestyle";
  }else if($otype == "catgs_17"){
    $catgs = "Food";
  }else if($otype == "catgs_18"){
    $catgs = "Knowledge";
  }else if($otype == "catgs_19"){
    $catgs = "Language";
  }else if($otype == "catgs_20"){
    $catgs = "Experiences";
  }else if($otype == "catgs_21"){
    $catgs = "Love";
  }else if($otype == "catgs_22"){
    $catgs = "Recipes";
  }else if($otype == "catgs_23"){
    $catgs = "Personal Stories";
  }else if($otype == "catgs_24"){
    $catgs = "Product Review";
  }else if($otype == "catgs_25"){
    $catgs = "History";
  }else if($otype == "catgs_26"){
    $catgs = "Religion";
  }else if($otype == "catgs_27"){
    $catgs = "Entertaintment";
  }else if($otype == "catgs_28"){
    $catgs = "News";
  }else if($otype == "catgs_29"){
    $catgs = "Animals";
  }else if($otype == "catgs_30"){
    $catgs = "Environment";
  }else if($otype == "catgs_31"){
    $catgs = "Issues";
  }else if($otype == "catgs_32"){
    $catgs = "The Future";
  }

  if($otype != "date_0" && $otype != "date_1"){
    $sql = "SELECT * FROM articles WHERE written_by = ? AND category = ? ORDER BY title DESC $limit";
  }
  $stmt = $conn->prepare($sql);
  if($otype == "date_0" || $otype == "date_1"){
    $stmt->bind_param("s",$u);
  }else{
    $stmt->bind_param("ss",$u,$catgs);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $written_by = $row["written_by"];
        $wb_original = $written_by;
        $title = stripslashes($row["title"]);
        $title = str_replace('\'', '&#39;', $title);
        $title = str_replace('\'', '&#34;', $title);
        $tags = $row["tags"];
        $post_time_ = $row["post_time"];
        $pt = base64url_encode($post_time_,$hshkey);
        $posttime = strftime("%b %d, %Y", strtotime($post_time_));
        $cat = $row["category"];

        $cover = chooseCover($cat);

        $all_articles .= '<a href="/articles/'.$pt.'/'.$wb_original.'"><div class="article_echo_2" style="width: 100%;">'.$cover.'<div><p class="title_"><b>Author: </b>'.$written_by.'</p>';
        $all_articles .= '<p class="title_"><b>Title: </b>'.$title.'</p>';
        $all_articles .= '<p class="title_"><b>Posted: </b>'.$posttime.'</p>';
        $all_articles .= '<div id="tag_wrap"><p class="title_"><b>Tags: </b>'.$tags.'</p></div>';
        $all_articles .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
    }
  }else{
    if($isOwner == "Yes"){
        $haveornot = "<p style='text-align: center; color: #999;'>You have not written any articles so far</p>";
    }else{
        $haveornot = "<p style='text-align: center; color: #999;'>".$u." has not written any articles so far</p>";
    }
    if(isset($_GET["otype"])){
        echo "<p style='text-align: center; color: #999;'>There are no articles fitting the criteria</p>";
    }
  }
  $stmt->close();
  if(isset($_GET["otype"])){ echo $all_articles; }
  if(isset($_GET["otype"])){ exit(); }
}
  // Get how many articles has the user written
  $sql = "SELECT COUNT(id) FROM articles WHERE written_by = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s",$u);
  $stmt->execute();
  $stmt->bind_result($count_art);
  $stmt->fetch();
  $stmt->close();

  // Decide who is viewing the page
  $count_text = "";
  if($count_art == 1){
    $count_text = "<span>".$count_art."</span> article";
  }else if($count_art > 1 || $count_art == 0){
    $count_text = "<span>".$count_art."</span> articles";
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $u; ?>&#39;s all articles</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="See <?php echo $u; ?>'s all articles">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <script src="/text_editor.js" async></script>
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/jjs.js"></script>
  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
  <style type="text/css">
    @media only screen and (max-width: 1000px){ 
      #fts{
        width: 90% !important;
      }

      #sButton{
        width: 10% !important;
      }

      .longSearches{
        width: calc(90% - 15px) !important;
        }

      @media only screen and (max-width: 500px){
        #fts {
        width: 85% !important;
      }

      #sButton {
        width: 15% !important;
      }

      .longSearches{
        width: calc(100% - 30px) !important;
        }
    }
  }
  </style>
</head>
<body style="overflow-x: hidden;">
  <?php require_once 'template_pageTop.php'; ?>
  <div id="pageMiddle_2">
    <?php if($_SESSION["username"] != ""){ ?>
    <div id="artSearch">
        <div id="artSearchInput">
            <input id="fts" class="lsearch" type="text" autocomplete="off" onkeyup="getArt(this.value)" placeholder="Search in your articles by their title, category or tags">
            <div id="sButton" class="lsearchBtn" onclick="getLAll()"><img src="/images/searchnav.png" width="17" height="17"></div>
        </div>
        <div class="clear"></div>
    </div>
    <div id="artSearchResults" class="longSearches"></div>
    <?php } ?>
    <div id="data_holder">
        <div>
            <div><?php echo $count_text; ?></div>
        </div>
    </div>

    <button id="sort" class="main_btn_fill">Filter Articles</button>
    <div id="sortTypes">
        <div class="gridDiv">
            <p class="mainHeading">Publish date</p>
            <div id="date_0">Newest to oldest</div>
            <div id="date_1">Oldest to newest</div>
        </div>
        <div class="gridDiv">
            <p class="mainHeading">Category (1)</p>
            <div id="catgs_0">School</div>
            <div id="catgs_1">Business</div>
            <div id="catgs_2">Learning</div>
            <div id="catgs_3">My dreams</div>
            <div id="catgs_4">Money</div>
            <div id="catgs_5">Sports</div>
            <div id="catgs_6">Technology</div>
            <div id="catgs_7">Video games</div>
            <div id="catgs_8">Tv programmes</div>
            <div id="catgs_9">Hobbies</div>
            <div id="catgs_10">Music</div>
        </div>
        <div class="gridDiv">
            <p class="mainHeading">Category (2)</p>
            <div id="catgs_11">Freetime</div>
            <div id="catgs_12">Travelling</div>
            <div id="catgs_13">Books</div>
            <div id="catgs_14">Politics</div>
            <div id="catgs_15">Movies</div>
            <div id="catgs_16">Lifestyle</div>
            <div id="catgs_17">Food</div>
            <div id="catgs_18">Knowledge</div>
            <div id="catgs_19">Language</div>
            <div id="catgs_20">Experiences</div>
            <div id="catgs_21">Love</div>
        </div>
        <div class="gridDiv">
            <p class="mainHeading">Category (2)</p>
            <div id="catgs_22">Recipes</div>
            <div id="catgs_23">Personal stories</div>
            <div id="catgs_24">Product review</div>
            <div id="catgs_25">History</div>
            <div id="catgs_26">Religion</div>
            <div id="catgs_27">Entertainment</div>
            <div id="catgs_28">News</div>
            <div id="catgs_29">Animals</div>
            <div id="catgs_30">Environment</div>
            <div id="catgs_31">Issues</div>
            <div id="catgs_32">The future</div>
        </div>
        <div class="clear"></div>
    </div>
    <div class="clear"></div>
    <hr class="dim">
    <div style="text-align: center;"><?php echo $haveornot; ?></div>
    <div class="clear"></div>
    <?php echo $error; ?>
    <div id="userFlexArts" class="flexibleSol">
        <?php echo $all_articles; ?>
    </div>
    <div class="clear"></div>
    <div id="paginationCtrls" style="text-align: center;"><?php echo $paginationCtrls; ?></div>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    let uPHP = "<?php echo $u; ?>";
    function getCookie(e){for(var t=e+"=",s=decodeURIComponent(document.cookie).split(";"),n=0;n<s.length;n++){for(var r=s[n];" "==r.charAt(0);)r=r.substring(1);if(0==r.indexOf(t))return r.substring(t.length,r.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],s=document.createElement("link");s.id=e,s.rel="stylesheet",s.type="text/css",s.href="/style/dark_style.css",s.media="all",t.appendChild(s)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();

  $( "#sort" ).click(function() {
      $( "#sortTypes" ).slideToggle( 200, function() {
        // Animation complete.
      });
    });


  function getLAll() {
    var e = _("fts").value;
    if (e == "") return _("artSearchResults").style.display = "none", !1;
    var a = encodeURI(e);
    window.location = "/search_articles/" + a + "&inmy=yes";
  }

  function getArt(e) {
    if ("" == e) return _("artSearchResults").style.display = "none", !1;
    _("artSearchResults").style.display = "block", "" == _("artSearchResults").innerHTML && (_("artSearchResults").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">');
    var a = encodeURI(e),
        t = new XMLHttpRequest;
    t.open("POST", "/art_all_exec.php", !0), t.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), t.onreadystatechange = function () {
        if (4 == t.readyState && 200 == t.status) {
            var e = t.responseText;
            "" != e && (_("artSearchResults").innerHTML = e)
        }
    }, t.send("a=" + a + "&phpu=" + uPHP);
  }

  addListener("date_0", "date_0");
  addListener("date_1", "date_1");
   
  for(let i = 0; i < 33; i++){
    addListener("catgs_" + i, "catgs_" + i);
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
    req.open("GET", "/all_art_my.php?u=<?php echo $u; ?>&otype=" + otype, false);
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
    for(let i = 0; i < 33; i++){
        if("catgs_" + i != otype) _("catgs_" + i).style.color = "black";
    }
    if(otype != "date_0") _("date_0").style.color = "black";
    if(otype != "date_1") _("date_1").style.color = "black";
  }

  changeStyle("date_0");
  </script>
</body>
</html>
