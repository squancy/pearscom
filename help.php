<?php
	require_once "php_includes/check_login_statues.php";
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';

	$u = "";
	if(isset($_SESSION['username'])){
		$u = $_SESSION['username'];
	}

	// Ajax calls this code to execute
	if(isset($_POST["p"]) && isset($_POST["d"])){
		// Clean the variables
		$p = preg_replace('#[^a-z ]#i', '', $_POST['p']);
		$d = $_POST['d'];
		$d = htmlspecialchars($d);
		$d = htmlentities($d);
		$d = mysqli_real_escape_string($conn, $d);

		// Form data error handling
		if ($p == "" || $d == "") {
			echo 'Please fill all the form data';
			exit();
		} else if ($p != "Other" && $p != "Cannot Log In" && $p != "Cannot Sign Up" &&
      $p != "Found a Bug" && $p != "Stolen Account" && $p != "Harmful Content" &&
      $p != "General Question"){
		    echo "Please give a valid category!";
		    exit();
		}

		// Connect to the database
		require_once 'php_includes/conn.php';

		// Insert into database
		$sql = "INSERT INTO problem_report(selected_problem, discuss_problem, username,
      report_time) VALUES (?,?,?,NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss", $p, $d, $log_username);
		$stmt->execute();
		$stmt->close();
		echo "send_success";
		exit();
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Help & Support Centre</title>
	<meta charset="utf-8">
	<meta lang="en">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Help &amp; Support on Pearscom.
    If you need any help feel free to check our support page or ask a question about the
    problem.">
	<script src="/js/jjs.js"></script>
	<script src="/text_editor.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />

	<script src="/js/main.js"></script>
	<script src="/js/ajax.js" async></script>
  <meta name="description" content="Help and support for Pearscom users, learn how to upload
    your images, files and videos, sign up or log in. If you have any problem feel free to
    ask and share your question.">
  <meta name="keywords" content="pearscom help, pearscom support, help, pearscom, support,
    question, problems, pearcon, pcom, pears community help, learn pearscom, ask pearscom,
    ask, learn">
  <meta name="author" content="Pearscom">

	<script src="/js/specific/dd.js" defer></script>
	<script src="/js/specific/mode.js" defer></script>
	<script src="/js/specific/help.js" defer></script>
	<script src="/js/specific/status_max.js" defer></script>
	<style type="text/css">
    #pageMiddle_2{
      padding: 30px; font-size: 14px; margin-bottom: 10px !important;
    }

    select, textarea {
      background-color: white;
    }

    @media only screen and (max-width: 768px){
      #pageMiddle_2{
        width: 80%;
        padding: 20px;
      }
    }

    @media only screen and (max-width: 500px){
      #pageMiddle_2{
        width: calc(100% - 20px);
      }
    }
  </style>
</head>
<body style="background-color: #fafafa; height: auto;">
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
    <p style="font-size: 22px; color: #999; margin-top: 0;">Help &amp; Support Centre</p>
    <br>
    <div class="collection" id="ccSu">
      <p style="font-size: 18px;" id="signup">How can I sign up?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="suDD">
      <p>
        You will find a detailed guide, answers to problems and essential information about
        signing up.
      </p>
      <p>Getting any <span style="color: red;">red</span> or black errors</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; To make things easier we tell you what is wrong at that time when
        you type something on the sign up page. Your <b>name</b> cannot be taken
        or begin with a number and it also has to be between 3 and 80
        characters. <b>Email</b> must contain an &#34;@&#34; sign, it has to be
        valid and mustn&#39;t be taken like your username.  Please note that you
        have to give a vaild email because the activation email for your account
        will be sent there. In addition we can also send you notifications,
        letters etc. and if you forget your password we will also send it to
        your email address.  Your <b>password</b> has to be very secure, long
        but easily memorizable and unguessable for other people. We do not
        recommend that your password would be your name, birhtday or one of your
        friends&#39; name. If you successfully got your password you have to
        confirm it again in the interest of to make it sure you did not make any
        typos. Keep in mind that your 2 password fields do not match it will
        cause an error.  Your <b>gender</b> must be your real one (which only
        can be male or female) and we would be graeful if you did not abuse with
        this option. You also need to pick a <b>country</b>. To find your
        friends easier and to ensure your geolocation datas you have to add your
        current <b>country</b>. Your <b>birthday</b> must be between 1st
        January, 1899 and the current date. There are not any age limits or
        restrictions in connection with your age. The last thing you need to add
        is your <b>timezone</b>. Your timezone will also determine your location
        and with this data you will get time information connected to the local
        timezone. At the end of the form you have to agree that we can access
        your geolocation for security reasons, to find your friends easier and
        to ensure your manually given country is valid. With this data we will
        be able to calculate the distance and the locations between certain
        users. If you still need help please send us a report about your problem
        <a href="#">here.</a>
      </p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Please keep in mind that any of the datas given in the signing up part can be
        changed later on, however we highly recommend to give your valid and real information
        for the first time
      </p>
    </div>

    <div class="collection" id="ccLo">
      <p style="font-size: 18px;" id="login">How can I log in?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="loDD">
      <p>After you signed up you have to log in to access your account. This article will help you about it.</p>
      <p>The &#34;<i>Login unsuccessful, please try again</i>&#34; error</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; This message is for when your username or password is not vaild. Please double
        check you did not make any typos and try again. If you do not have an account you need
        to <a href="signup.php">sign up</a> first. If you forgot your password plase visit <a
        href="forgot_pass.php">this</a> page after you logged out. Another problem can be that
        if you already signed up but you have not activated your account yet. In this case you
        have to check your email and spam inbox seeking for our <b>account activation</b>
        letter. If you do not find it please send us a letter <a
        href="help.php#report">here.</a> Please note that our system automatically deletes
        those accounts that is not acivated and older than 2 days. If you still need help
        please send us a report about your problem <a href="#">here.</a>
      </p>
      <p>
        The &#34;<span style="color: red;"><i>You have timed out please refresh your
        browser</i></span>&#34; error
      </p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; If you get this error that means you waited too long and you have to refresh
        your browser to log in again. We want to validate that you are not a robot and you are
        a vaild human user.
      </p>
    </div>
    <div class="collection" id="ccGe">
      <p style="font-size: 18px;" id="geo">How does the geolocation system work?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="geDD">
      <p>
        On Pearscom we look up for your geolocation by security purposes, to count the distance
        between two users and to prefer those people who lives near you.
      </p>
      <p>Geolocation at signing up</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; When you first sign up we have to get your location (especially your longitude
        and latitude) in order to set up the user environment, your possible friends and your
        personal datas. If it succeed without any errors you should see your longitude,
        latitude and a map with your location. Otherwise you got an error which can be quite
        different from each other (you can see the list of errors down below).
      </p>
      <p>List of different errors you can during the signing up</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; <i>Geolocation is not supported by this browser</i> - getting this error means
        that you do not allow any kind of geolocation accessing in your browser. It could be a
        standard setting or a manually set feature. In order to avoid any errors please allow
        geolocation in your browser!
      </p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; <i>User denied the request for Geolocation</i> - you probably manually denied
        geolocation in your browser or it could be also a standard setting in the browser.
        Please allow geolocation when it asks for it!
      </p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; <i>The request to get user location timed out</i> - it may occurs when you have
        a slow connection and you reached the maximum time limit or our system gets too many
        requests at the same time. Please try again later with a better internet connection!
      </p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; <i>An unknown error occurred</i> - if you get this error something abnormal has
        happened. It could be a misconfiguration or an error with our - or maybe your - system.
        The most you can do is to refresh the browser and try again later!
      </p>
      <p>Geolocation after signing up and in the user interface</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Once you signed up your location will be saved. You have the chance to change
        your location (as many times as you want) when you travel, move or live somewhere else.
        This can be done in your main profile page at the <i>update geolocation</i> section.
      </p>
      <p style="font-size: 14px; color: red;">
        Keep in mind that we will NOT abuse with your personal geolocation information! Report
        any kind of data stealing and abusing!
      </p>
    </div>

    <div class="collection" id="ccGr">
      <p style="font-size: 18px;" id="groups">What are groups?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="grDD">
      <p>What are groups?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Groups are small communities where people can share their ideas, write posts and
        replies to each other, send emojis and photos. You can create a group or join to an
        existing one and without any restriction.
      </p>
      <p>How can I create a group?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Firstly you need to click on the click on the menu icon on the top navigation
        bar. There click on the <i>Groups</i> section and then you will see a button says
        &#34;Create a group&#34;. There you need to give a name, a category and a join type for
        your group. The <b>name</b> has to be between 3 and 100 characters and it cannot begin
        with a number. A <b>category</b> will specify what is your group about. You can choose
        from 8 categories (Animals, Relations, Friends &amp; Family, Freetime, Sports, Games,
        Knowledge, Other) at the moment. This will give a general image about your group and
        its topic. At the end you need to give a join type. Here you can decide your group is
        closed (request to join) or public (by simply joining). On the <a
        href="/groups">groups</a> page you can read about it in more detailed. If you do not
        get any <span style="color: red;">red</span> errors you can create your group by
        clicking on the &#34;Create group&#34; at the bottom.
      </p>
      <p>How can I join to a group?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; You can easily join to a group by clicking on the &#34;Join group&#34; bottom on
        the groups&#39; page. After you did it - depending on the group type - you can
        instantly join (if it is a public group) or your request will be sent to the group
        admin who will approve or decline it.
      </p>
      <p>How can I quit from a group?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; If you are no longer want to be a member of a group you can quit. Just click on
        the &#34;Quit group&#34; button. Then you will be asked you want to quit or not. If you
        click on Yes you will no longer be a member of that group. Please keep in mind that if
        you are the only member in the group and you quit your group will be also deleted
        because an empty group has no sense.</i>
      </p>
      <p>How can I approve or decline a member?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; If you are an admin in a closed group you can approve or decline join requests.
        If you have at least one pending approval you will see the name of the person and two
        buttons. &#34;Approve&#34; or &#34;Decline&#34;. Decide which one you choose by
        clicking on the right button.
      </p>
    </div>

    <div class="collection" id="ccNo">
      <p style="font-size: 18px;" id="notaf">What are notifications and friend requests?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="noDD">
      <p>Notifications</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Everytime when one fo your friends post, reply, share etc. something you get a
        notification which you can quickly check by clicking on the rounded envelope icon on
        the top navigation bar.
      </p>
      <p>Friend Requests</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Whenever you request someone as a friend he/she will get a friend request that
        an icon with a red background number will alert on the top. If he/she accepts it you
        will be friends, if declines you will not. Please do NOT add starngers as friends only
        if you wish to meet new people. Otherwise only add those people who you know or who are
        your family members &amp; relatives.
      </p>
    </div>

    <div class="collection" id="ccIn">
      <p style="font-size: 18px;" id="invf">How can I invite my friends to Pearscom?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="inDD">
      <p>How can I invite my friends to Pearscom?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; First and foremost click on the menu icon at the top navigation bar and then on
        the &#39;Invite Friends&#39; section then you will see a form that you need to fill in
        properly in order to send the invitation to the proper person. Give the person&#39;s
        email address - which must be valid otherwise we will not be able to send it - and
        below it you can write a short message or letter why you want to see him/her on
        Pearscom or anything you want. If everything is right your friend will get an
        invitation email from you. Then he/she can decide that he/she joins or not.<br
        /><b>Important: </b>do NOT give not valid, unauthorized or someone else&#39;s email
        address. Continuous spam sending will may occur a ban! Please keep in mind and do not
        abuse with this section.
      </p>
    </div>
    <div class="collection" id="ccPh">
      <p style="font-size: 18px;" id="photos">How can I manage my photos?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="phDD">
      <p>How can I upload photos?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Select the &#34;See My Photos&#34; section in the dropdown menu. Then choose
        your photos&#39;s gallery - currently choosable galleries: <i>Myself, Family, Pets,
        Friends, Games, Freetime, Sports, Knowledge, Hobbies, Working, Relations, Other</i> -
        and the description of the photo (the maximum character limit is 1,000). If you&#39;re
        done you can upload your photo.
      </p>
      <p>What type of photos can I upload?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; You are allowed to upload png, jpg, jpeg and gif images up to 5MB. If you get
        any errors please try again a few minutes later or get help from us by sending a
        report.
      </p>
      <p>How can I check my photos in big and comment below it?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Just click on that photo that you want to see in big. Then you can check the
        description about the photo and the upload date. Here you can also comment, send other
        photos &amp; emojis below it and check your related photos just like on the main photos
        page.
      </p>
      <p>How can I delete a photo?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Click on the photo you want to delete and you will see a &#34;Delete Photo&#34;
        button at the right bottom (only if you are the owner of the image). Be careful with
        deleting images! <i>Once if you deleted it we cannot reset!</i>
      </p>
    </div>

    <div class="collection" id="ccAr">
      <p style="font-size: 18px;" id="art">How can I manage my articles?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="arDD">
      <p>What are articles?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; One of the best functions on Pearscom is the article writing. You can share it
        with your friends, search for new ones for your interest or write an article on your
        own. Share your knowledge, interests, hobbies and freetime through it, entertain and
        help to other people!
      </p>
      <p>How to write a good article?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; If you do not know how to write a well-orginized, nice and tidy article you will
        need to read through our guides and tutorials or search for it on the Internet. Before
        you start doing anything you should be familiar with the rules of article writing.
        There is a quick walkthrough where on the main articles page (access it by clicking on
        the <i>Write Article</i> button on your profile page) moreover, there are also some
        links where you can find / read more information in the topic. It is very important to
        <b>NOT</b> write confused, messy and junk articles but to create and writes those ones
        that provde information, entertainment or anything useful in a certain topic for
        different readers.
      </p>
      <p>How can I create an article?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Firstly you need to go to your own profile page that you can access by clicking
        on your profile picture on the top navbar. In the articles section you will see a
        &#34;Write Article&#34; button. Click on it and now you can start writing your article
        by giving a title, a content, tags and a category for it. The title will describe your
        article&#39;s topic in a few words. We do not recommend to give an extremely long title
        because it is quite uncomfortable for the readers. Use the content box instead of it
        down below. You can also give tags for your article (at least 1 is compulsory but more
        is recommended) which will summerize your article and the topic. For instance:
        <i>holiday, family, waterball, relaxing, fun etc.</i> At last you need to give a
        category that is necessary for the cover picture and it also take part in the
        searching. The currently choosable categories are: <i>School, Business, Learning, My
        Dreams, Money, Sports, Technology, Video Games, TV programmes, Hobbies, Music,
        Freetime, Travelling, Books, Politics, Movies, Lifestyle, Food, Knowledge, Language,
        Experiences, Love, Recipes, Personal Stories, Product Review, History, Religion,
        Entertainment, Issues, The Future.</i>
      </p>
      <p>How can I format an article?</p>
      <p>
        To make your article looks better for the readers and to make it more picturesque and
        comfortable you can form and edit your articles like in any text editor. This paragraph
        will help you in it.
      </p>
      <p style="margin-left: 20px;">
        <i class='fa fa-bold' title="Make text bold"></i> - Makes text bold e.g. <b>bold</b><br
        />
        <i class='fa fa-italic' title="Make text italic"></i> - Makes text italic e.g.
        <i>italic</i><br />
        <i class='fa fa-underline' title="Make text underline"></i> - Makes text underline e.g.
        <u>underline</u><br />
        <i class='fa fa-strikethrough' title="Make text strikethrough"></i> - Makes text
        strikethrough e.g. <s>strikethrough</s><br />
        <i class='fa fa-align-left' title="Align text left"></i> - Align text left e.g. <b
        style="text-align: left; font-weight: normal;">left align</b><br />
        <i class='fa fa-align-center' title="Align text center"></i> - Align text center<br />
        <i class='fa fa-align-right' title="Align text right"></i> - Align text right <br />
        <i class='fa fa-align-justify' title="Align text justify"></i> - Align text justify<br
        />
        <i class='fa fa-cut' title="Cut something to the clipboard"></i> - Cut something to the
        clipboard<br />
        <i class='fa fa-copy' title="Copy something to the clipboard"></i> - Copy something to
        the clipboard<br />
        <i class='fa fa-indent' title="Indent text with a Tab"></i> - Indent text with a <span
        class="highlight">Tab</span><br />
        <i class='fas fa-indent' title="Dedent text with a Tab"></i> - Dedent text with a <span
        class="highlight">Tab</span><br />
        <i class='fa fa-subscript' title="Subscript text"></i> - Subscript text e.g
        <sub>subscript text</sub><br />
        <i class='fa fa-superscript' title="Superscript text"></i> - Superscript text e.g
        <sup>superscript text</sup><br />
        <i class='fa fa-undo' title="Undo something"></i> - Undo something<br />
        <i class='fas fa-redo' title="Redo something"></i> - Repeat something<br />
        <i class='fa fa-list-ul' title="Create unordered list"></i> - Create unordered list<br
        />
        <i class='fa fa-list-ol' title="Create ordered list"></i> - Create ordered list<br />
        <i class='fa fa-paragraph' title="Insert paragraph"></i> - Insert paragraph<br />
        <select class="font_all" title="Insert headings">
          <option value="H1">H1</option>
          <option value="H2">H2</option>
          <option value="H3">H3</option>
          <option value="H4">H4</option>
          <option value="H5">H5</option>
          <option value="H6">H6</option>
        </select>
        - Insert headings<br />
        <a title="Insert horizontal rule">HR</a> - Insert horizontal rule<br />
        <i class='fa fa-link' title="Create link"></i> - Create link<br />
        <i class='fa fa-unlink' title="Unlink something"></i> - Unlink something<br />
        <i class='fa fa-code' title="Code view"></i> - Code view<br />
        <i class="fas fa-edit"></i> - Toggles between edit and normal mode<br />
        <select class="font_all">
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option value="6">6</option>
          <option value="7">7</option>
        </select>
        - Select font size<br />
        <select id="font_name" title="Select font style">
          <option value="Arial">Arial</option>
          <option value="Comic Sans MS">Comic Sans MS</option>
          <option value="Courier">Courier</option>
          <option value="Georgia">Georgia</option>
          <option value="Helvetica">Helvetica</option>
          <option value="Thaoma">Thaoma</option>
          <option value="Palatino Linotype">Palatino Linotype</option>
          <option value="Arial Black">Arial Black</option>
          <option value="Lucida Sans Unicode">Lucida Sans Unicode</option>
          <option value="Trebuchet MS">Times New Roman</option>
          <option value="Lucida Console">Times New Roman</option>
          <option value="Courier New">Times New Roman</option>
        </select>
        - Select font style<br />
        <input type="color" title="Change fore- and background color" /> - Change fore- and
        background color<br />
        <i class="fa fa-reply-all"></i> - Select all
      </p>
      <p>Where can I see all my articles?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; You can view your 6 newest articles by clicking on the &#34;See My Articles&#34;
        section in the dropdown menu or you can also view it in the &#34;My All Articles&#34;
        section. Here every of your articles are listed on the page.
      </p>
      <p>How to add pictures to my article?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; In order to make your article visually better, more picturesque and specified
        attach up to 5 images with it. It is an optional step, therefore you do not need to
        attach images - it is up to you. Despite of this we highly recommend that to give at
        least 1-2 images to the written article in order to make it better for the readers. If
        you add less than 5 images only those will appear, there will be no blank ones. The
        rules for the extenstions, size, demensions are the same as the standard image
        uploading that you can check in that certain <a href="/help#photos">paragraph.</a>
      </p>
      <p>How can I delete my article?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; You need to click on that article that you want to delete and then click on the
        button which says &#34;Delete Article&#34;. Be careful! <i>Once if you deleted your
        article we cannot reset it. All the text, attached images and the whole article will be
        lost forever!</i>
      </p>
      <p>How can I leave a comment below my or my friends&#39; articles?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Click on that article where you want to comment and then type something to the
        textarea. <i>Please do not spam other people&#39;s articles with junk posts and harmful
        content!</i>. The opportunities are the same as on every page: you can also attach
        images and emojis to written text!
      </p>
    </div>

    <div class="collection" id="ccVi"> 
      <p style="font-size: 18px;" id="videos">How can I manage my videos?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="viDD">
      <p>How do videos &amp; audio files work?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; You can freely upload your videos to Pearscom from your computer without paying
        anything. Like, share and post below your friends&#39; videos and start to explore
        other ones!
      </p>
      <p>How can I upload a video?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Before anything click on the &#34;See My Videos&#34; button on the dropdown
        menu. Then logically fill in at least the &#34;Choose a video&#34; section where you
        have to pick a video file from your computer. The video name, description and poster
        are optional (but highly recommended). In the yellow help box you can get some
        information about video uploading but if you still need any help feel free to send us a
        message or report.
      </p>
      <p>What is the difference between short and long view?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Short view only gives you a quick glimpse of your videos and there are only the
        videos displayed. Long view gives you more detailed information about the videos e.g.:
        it shows you the description, the name, the upload date and the video.
      </p>
      <p>How can I comment below the videos?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; First click on the <img src="images/bigger.png" width="20" height="20"
        title="Click on me to see the video in big"> icon at the top right corner. Then it will
        navigate you to another page where you will see the video in big and below you can
        leave a comment.
      </p>
      <p>What file extensions are allowed and what is the video size limit?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Allowed formats: mp3, mp4, mp4 h.264 (aac or mp3) wave, flac, ogg, webm. The
        video size limit is 10MB.
      </p>
    </div>

    <div class="collection" id="ccBe">
      <p style="font-size: 18px;" id="goodb">What are the moral roles on Pearscom?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="beDD">
      <p>
        It is very important to have a good behavior and to be intelligent on Pearscom. We try
        to create a quite good and fun community so you have to follow all of these rules and
        statements down below.
      </p>
      <p style="margin-left: 20px;">&bull; Do not send <i>harmful</i> messages, posts,
      comments.</p>
      <p style="margin-left: 20px;">&bull; Do not <i>spam</i> anything.</p>
      <p style="margin-left: 20px;">&bull; Do not <i>ask</i> for anyone&#39;s password or email
      address.</p>
      <p style="margin-left: 20px;">&bull; Be <i>friendly and help</i> to other people.</p>
      <p style="margin-left: 20px;">&bull; Do not try to <i>steal</i> any accounts.</p>
    </div>

    <div class="collection" id="ccCu">
      <p style="font-size: 18px;" id="editp">How can I customize my profile?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="cuDD">
      <p style="text-align: justify;">
        You can edit and customize your profile quite easily on your main profile page. We
        separeted all the information into 5 smaller parts (<i>General Information, Personal
        Information, Contact Information, Education &amp; Jobs, About me</i>) to make your
        profile very well-organized and comfortable for readers &amp; viewers. You do not need
        to fill in all gaps and give all information about you, nonetheless we highly recommend
        that to give as much as you can. It will help you and also us in friends searching and
        in a lot of other things, too.
      </p>
      <p>How can I edit my profile?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; First go to your profile page and scroll down until you see a button says
        &#34;Edit Profile&#34;. Then click on it and you will see that 4 links appeared down
        (Education, Profession, Cities, About me, Contact). Click on that link that you want to
        open and fill in the appeared gaps. After it, click on the &#34;Submit&#34; button and
        you are done! Please do NOT give any confidental or private information about yourself
        like your password, emails, credit card numbers stc. Unfortunately, we cannot take any
        responsibility for any kind of these published information!
      </p>
      <p>Can I change my personal information later?</p>
      <p style="text-align: justify; margin-left: 20px;">&bull; Of course you can as many times
      as you want so you can be up to date.</p>
    </div>
    
    <div class="collection" id="ccPp">
      <p style="font-size: 18px;" id="cprofile">How can I change my profile photo?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="ppDD">
      <p style="text-align: justify;">
        To be more recognizable for your friends we highly recommend that to change your
        profile photo first from the cute smiling pear. If you have the default profile photo
        then it will be very hard to make difference between the people.
      </p>
      <p>How can I change my profile photo?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Before everything go to your profile page and you will see your current profile
        photo. To change it hover your mouse on the photo (or click on the picture if you are
        on mobile) and a &#34;Change Avatar&#34; button will appear. Click on it and you will
        see a &#34;Choose file&#34; button where you need to pick your photo and
        &#34;Upload&#34; it. If you want to see what file extensions are allowed read the
        &#34;<i>What files can I upload as a profile photo?</i>&#34; part.
      </p>
      <p>What files can I upload as a profile photo?</p>
      <p style="text-align: justify; margin-left: 20px;">&bull; Allowed file extensions: jpg,
      jpeg, png, gif.</p>
      <p>What is the file limit?</p>
      <p style="text-align: justify; margin-left: 20px;">&bull; The maxmimum limit is 5MB.</p>
    </div>
    
    <div class="collection" id="ccBg">
      <p style="font-size: 18px;" id="cback">How can I change my background?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="bgDD">
      <p style="text-align: justify; margin-left: 20px;">
        &bull; First of all go to your profile page and scroll down until you see a title which
        says &#34;Background&#34;. There you will see two buttons (<i>Choose a file</i> and
        <i>Upload Background</i>) which will be important when you want to upload a file from
        your computer. If you do not want to, click on the <i>Show >>></i> button and you will
        see some built-in backgrounds (exactly 9). You can choose whichever you want and after
        you clicked on it the page will be automatically refreshed hopefully now with your new
        background.
      </p>
      <p>What file extensions are allowed?</p>
      <p style="text-align: justify; margin-left: 20px;">&bull; Allowed file extensions: jpg,
      jpeg, png.</p>
      <p>What is the file limit?</p>
      <p style="text-align: justify; margin-left: 20px;"> &bull;  The maximum file limit is
      5MB.</p>
    </div>
    
    <div class="collection" id="ccPm">
      <p style="font-size: 18px;" id="pm">How does private messaging work?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="pmDD">
      <p>
        Private messages can be very useful when you want to chat with only one of your friends
        at the same time. You can send text messages, emojis and photos to each other not
        mentioned that you can mark as read a message or delete if you no longer want to chat
        with that person. If you want to check your private messages click on the two message
        bubbles on the top navigation bar. However, if you have unread messages a number will
        appear next to the icon which will show the number of unread messages to be up to date.
      </p>
      <p>How can I send private messages?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; First of all you need to click on your avatar on the top navigation bar when a
        slide in menu will apear. In that menu you can see your friends in a list with some
        icons. Click on the envelope icon next to their avatar and it will navigate you to a
        form. After it you can give a title for your message and start to write the message.
        Don&#39;t forget, you can also send emojis and photos, too!
      </p>
      <p>How can I check my private messages?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; You have to click on the two message bubbles on the top navigation bar where you
        can see all your current conversations. Click on the &#34;Show message&#34; button to
        see the all the messages.
      </p>
      <p>If you do not have time to read a message or a whole conversation don&#39;t worry, you
      can <i>mark as read!</i></p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Firstly you have to go to your private message inbox and click on the &#34;Mark
        as read&#34; button on that conversation that you want to read later.
      </p>
      <p>How can I delete my conversation?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Go to your private messages, click on the &#34;Show Message&#34; button and then
        click on the &#34;Delete whole converstaion&#34; button. Keep in mind that <i>once you
        deleted a message we will not be able not reset it. It will be deleted forever!</i>
      </p>
    </div>

    <div class="collection" id="ccSe">
      <p style="font-size: 18px;" id="sett">What can I change in settings?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="seDD">
      <p>
        Changing your private settings can be very important and urgent when you - for example
        - move to another country and you need to change your location or anything in
        connection with your confident information.
      </p>
      <p>How can I change my settings?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Firstly you need to click on the &#34;Profile Settings&#34; button in the
        dropdown menu. Then you need to agree with all of the rules and statements that is
        written there (read it through once and later you just need to press agree). If you
        agreed a form will appear where everything is explained in very in detailed. For
        security reasons you need to add your current password everywhere to make sure you are
        the real account owner and to reduce the number of stolen and hacked accounts. Thank
        you for your understanding and please do not abuse with this option!
      </p>
      <p>How many times can I change my profile settings?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Anytime if you want but please only change your settings when it is really
        needed!
      </p>
    </div>

    <div class="collection" id="ccFo">
      <p style="font-size: 18px;" id="fpass">Forgotten password?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="foDD">
      <p>
        If you forgot your password for any reason we can send a temorary password for you that
        you can log in with (obviously later it is highly recommended to change the temporary
        password to your custom one).
      </p>
      <p style="text-align: justify; margin-left: 20px;">
      <p>How can I get a temporary password?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Click on the <a href="/login">Log In</a> icon at the top menu and you will see a
        log in form. At the very bottom you will see a title which says &#34;Forgot your
        password?&#34; and click on it. After it you need to enter your email address where we
        will send your temporary password. Then if you did not get any errors check your email
        inbox in a few minutes.
      </p>
      <p>Why do I get errors?</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; A possible error can be <i>Sorry that email address is not in our system</i>.
        This means that you made a typeo in your email address or you are not in the system so
        you have not signed up yet (you can sign up <a href="/signup">here</a>). Another error
        can be <i>Unfortunately we could not send your email</i>. This time maybe our system
        found your email not vaild or just made a mistake. When you get this error please check
        that you have given a vaild email address and try again. The 3rd error can be the <i>An
        unknown error occurred</i>. If you get this error wait a few minutes and try again.
      </p>
    </div>
    
    <div class="collection" id="ccFl">
      <p style="font-size: 18px;" id="follow">Who are followers and followings?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo" id="flDD">
      <p>Everything that you need to know about the whole follow-system.</p>
      <p>Followings</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; You can follow any user who has already activated his/her account on the certain
        user&#39;s profile page. If you wish to follow a user you will get notifications in
        order to keep up to date you. However you cannot send a private message to the user,
        post or reply on the profile page. On the other hand, you are able to <i>unfollow</i>
        users if you no longer want to follow and get information about him/her. Despite of it,
        if you want to get a closer relationship with the user <i>request as friend.</i>
      </p>
      <p>Followers</p>
      <p style="text-align: justify; margin-left: 20px;">
        &bull; Your followers are those people who found you attractive and interesting so they
        started to follow you in order to get information. You can see both your followers and
        following on the profile page to check who likes and interact with you. Please keep in
        mind that you cannot delete or unfollow your followings!
      </p>
    </div>
    <div class="collection" id="ccRe">
      <p style="font-size: 18px;" id="report">Having a problem?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo", id="reDD">
      <?php if($u != ""){ ?>
      <p>If you are experiencing any sort of issue not mentioned above feel free to send us
      problem report.</p>
      <form name="problem_fix" onsubmit="return false;">
        <select onfocus="emptyElement('status')" id="problem_select">
          <option value="" selected="true" disabled="true">What is the problem?</option>
          <option value="Found a Bug">Found a Bug</option>
          <option value="Stolen Account">Stolen Account</option>
          <option value="Harmful Content">Harmful Content</option>
          <option value="Cannot Log In">Cannot Log In</option>
          <option value="Cannot Sign Up">Cannot Sign Up</option>
          <option value="General Question">General Question</option>
          <option value="Other">Other (please specify the problem below)</option>
        </select>

        <textarea placeholder="Be specific, discuss your problem" id="discuss_problem"
        onkeyup="statusMax(this, 1000)" style="border-radius: 3px;"></textarea>
        <div class="clear"></div>

        <button id="help_submit" class="main_btn" onclick="sendProb()">Report problem</button>
        <div class="clear"></div>
        <br>
        <span id="status"></span>
      </form>
      <?php }else{ ?>
        <p>You have to be <a href="/login">logged in</a> to report a problem!</p>
      <?php } ?>
    </div>
  </div>
	<?php require_once 'template_pageBottom.php'; ?>
</body>
</html>
