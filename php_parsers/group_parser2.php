<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/file_handlers.php';
  require_once '../php_includes/del_general.php';
  require_once '../php_includes/insertImage.php';
  require_once '../php_includes/post_general.php';
  require_once '../php_includes/share_general.php';
  require_once '../safe_encrypt.php';
  require_once '../tupl.php';

  $one = "1";
  $zero = "0";

  $u = $_SESSION['username'];

  // Do not access script directly
  if (!$user_ok || !$log_username) {
    exit();
  }

  function alreadyMember($conn, $gS, $uS) {
    $one = '1';
    $sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $gS, $uS, $one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    $stmt->close();
    if($numrows < 1){
      echo "You are not a member of this group";
      exit();
    }
  }

  function gnameTaken($conn, $gname){
    $sql = "SELECT id FROM groups WHERE name=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $gname);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    return $stmt->num_rows;
  }

  function errMsg($msg, $isCool) {
    if ($isCool) {
      return '
        <img src="/images/wrong.png" width="13" height="13">
        <span class="tooltiptext">'.$msg.'</span>
      ';
    }
    return $msg;
  }
  
  class GroupCheck {
    public function __construct($conn, $p1) {
      $this->p1 = mysqli_real_escape_string($conn, $p1);
      $this->conn = $conn;
    }

    public function checkGname($isCool = true) {
      $ish = strpos($this->p1, "#");
      $ish2 = strpos($this->p1, "%23");
      $ish3 = strpos($this->p1, "&#35;");
      $gname_check = gnameTaken($this->conn, $this->p1);

      if (is_numeric($this->p1[0])) {
        echo errMsg('Group name cannot begin with a number', $isCool);
        exit();
      } else if ($gname_check) {
        echo errMsg('Group name is taken', $isCool);
        exit();
      } else if (strlen($this->p1) < 3 || strlen($this->p1) > 100) {
        echo errMsg('Group name must be between 3 and 100 characters', $isCool);
        exit();
      } else if ($ish || $ish2 || $ish3) {
        echo errMsg('Group name cannot contain a hashtag sign', $isCool);
        exit();
      }
    }

    public function checkGCat($isCool = true) {
      if (!$this->p1) {
        echo errMsg('Please choose a category', $isCool);
        exit();
      } else if ($this->p1 < 1 || $this->p1 > 8) {
        echo errMsg('Please choose a valid category', $isCool);
        exit();
      }
    }

    public function checkType($isCool = true) {
      if($this->p1 !== "0" && $this->p1 !== "1"){
        echo errMsg('Please choose a type', $isCool);
        exit();
      }else if($this->p1 < 0 || $this->p1 > 1){
        echo errMsg('Please choose a valid type', $isCool);
        exit();
      }
    }
  }

  class CreateGroup {
    public function insertToDb($conn, $name, $gicon, $inv, $cat, $log_username) {
      $sql = "INSERT INTO groups (name, creation, logo, invrule, cat, creator)       
        VALUES(?,NOW(),?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssss", $name, $gicon, $inv, $cat, $log_username);
      $stmt->execute();
      $stmt->close();
    }

    public function addMember($conn, $name, $log_username) {
      $one = '1';
      $sql = "INSERT INTO gmembers (gname, mname, approved, admin)       
        VALUES(?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssss", $name, $log_username, $one, $one);
      $stmt->execute();
      $stmt->close();
    }
  }

  class JoinGroup {
    public function __construct($group, $name) {
      $this->group = $group;
      $this->name = $name;
    }

    public function errorCheck() {
      if (!$this->name || !$this->group){
        exit();
      }
    }

    public function getInvRule($conn) {
      $sql = "SELECT id, invrule FROM groups WHERE name=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $this->group);
      $stmt->execute();
      $result = $stmt->get_result();
      if($result->num_rows < 1){
        exit();
      } else {
        if ($row = $result->fetch_assoc()) {
          return $row["invrule"];
        }
      }
    }

    public function insertIntoDb($conn, $rule) {
      $sql = "INSERT INTO gmembers (gname, mname, approved)
              VALUES(?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $this->group, $this->name, $rule);
      $stmt->execute();
      $stmt->close();
    }
  }

  class ManageMember {
    public function __construct($conn, $g, $u) {
      $this->g = mysqli_real_escape_string($conn, $g);
      $this->u = mysqli_real_escape_string($conn, $u);
    }

    public function errorCheck() {
      if(!$this->g || !$this->u){
        exit();
      }
    }

    public function requestExists($conn, $sql) {
      $zero = '0';
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $this->g, $this->u, $zero);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      $stmt->close();
      if($numrows < 1){
        exit();
      }
    }

    public function handleMember($conn, $sql, $n) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $n, $this->u, $this->g);
      $stmt->execute();
      $stmt->close();
    }
  }

  class QuitGroup {
    public function __construct($conn, $u, $gS) {
      $this->uS = $u;
      $this->gS = mysqli_real_escape_string($conn, $gS);
    }

    public function checkErrors() {
      if(!$this->gS || !$this->uS){
        echo "Group name or username does not exist";
        exit();
      }
    }

    public function isLastMem($conn) {
      $sql = "SELECT COUNT(id) FROM gmembers WHERE gname = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $this->gS);
      $stmt->execute();
      $stmt->bind_result($numrows);
      $stmt->fetch();
      $stmt->close();
      return $numrows;
    }

    public function delGr($conn) {
      $sqlRem = "DELETE FROM groups WHERE name = ?";
      $stmt = $conn->prepare($sqlRem);
      $stmt->bind_param("s", $this->gS);
      $stmt->execute();
      $stmt->close();
    }

    public function delMem($conn) {
      $sql = "DELETE FROM gmembers WHERE mname=? AND gname=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $this->uS, $this->gS);
      $stmt->execute();
      $stmt->close();
    }
  }

  class AddAdmin {
    public function __construct($conn, $n, $g, $u) {
      $this->n = mysqli_real_escape_string($conn, $n);
      $this->gS = mysqli_real_escape_string($conn, $g);
      $this->uS = $u;
    }

    public function emptyCheck() {
      if(!$this->gS || !$this->uS || !$this->n){
        exit();
      }
    }

    public function alreadyAdmin($conn) {
      $one = '1';
      $zero = '0';
      $sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? AND
        admin = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssss", $this->gS, $this->n, $one, $zero);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      $stmt->close();
      if($numrows < 1){
        echo "This user is already a moderator";
        exit();
      }
    }

    public function addAsAdmin($conn) {
      $one = '1';
      $sql = "UPDATE gmembers SET admin=? WHERE gname=? AND mname=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $one, $this->gS, $this->n);
      $stmt->execute();
      $stmt->close();
    }
  }

  class UploadLogo {
    public function updateDb($conn, $db_file_name, $gS, $uS) {
      $sql = "UPDATE groups SET logo=? WHERE name=? AND creator=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $db_file_name, $gS, $uS);
      $stmt->execute();
      $stmt->close();
    }
  }

  class PostGroup extends PostGeneral {
    public function __construct($conn, $data, $g, $img) {
      $this->data = htmlentities($data);
      $this->g = mysqli_real_escape_string($conn, $g);
      $this->image = $img;
    }

    public function groupExists($conn) {
      $sql = "SELECT id FROM groups WHERE name = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $this->g);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      $stmt->close();
      if($numrows < 1){
        echo "Group does not exist";
        exit();
      }
    }

    public function typeCheck($conn, $n = "0") {
      if ($this->type != $n) {
        mysqli_close($conn);
        echo "type_unknown";
        exit();
      }
    }
  }

  class ReplyGroup extends PostGroup {
    public function __construct($conn, $sid, $g, $data, $img, $u) {
      $this->g = mysqli_real_escape_string($conn, $g);
      $this->sid = preg_replace('#[^0-9]#', '', $sid);
      $this->data = htmlentities($data);
      $this->image = $img;
      $this->u = $u;
    }

    public function typeCheck($conn, $n = "1") {
      parent::typeCheck($conn, $n);

      // also check for empty sid
      if (!$this->sid) {
        echo 'sid is missing';
        exit();
      }
    }
  }

  class ChangeDes {
    public function __construct($text, $gr) {
      $this->text = htmlentities($text);
      $this->gr = $gr;
    }

    public function updateDes($conn) {
      $sql = "UPDATE groups SET des = ? WHERE name = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $this->text, $this->gr);
      $stmt->execute();
      $stmt->close();
    }
  }

  // Check group name
  if(isset($_POST["gnamecheck"])){
    $gnameCheck = new GroupCheck($conn, $_POST['gnamecheck']);
    $gnameCheck->checkGname();
  }

  // Check group category
  if(isset($_POST["catcheck"])){
    $catCheck = new GroupCheck($conn, (int) $_POST["catcheck"]);
    $catCheck->checkGCat();
  }

  // Check group type
  if(isset($_POST["typecheck"])){
    $typeCheck = new GroupCheck($conn, (int) $_POST["typecheck"]);
    $typeCheck->checkType();
  }

  // Create new group
  if(isset($_POST["action"]) && $_POST['action'] == "new_group"){
    $gnameCheck = new GroupCheck($conn, $_POST['name']);
    $invCheck = new GroupCheck($conn, (int) $_POST['inv']);
    $catCheck = new GroupCheck($conn, (int) $_POST['cat']);

    // Check everything now on the server side
    $gnameCheck->checkGname(false);
    $catCheck->checkGCat(false);
    $invCheck->checkType(false);

    // Add group to database
    $gicon = "gdef.png";
    $addGr = new CreateGroup();
    $addGr->insertToDb($conn, $gnameCheck->p1, $gicon, $invCheck->p1, $catCheck->p1,
      $log_username);
    $addGr->addMember($conn, $gnameCheck->p1, $log_username);

    // Manage directory and gr logo
    if (!file_exists("../groups")) {
      mkdir("../groups", 0755);
    }

    if (!file_exists("../groups/$gnameCheck->p1")) {
      mkdir("../groups/$gnameCheck->p1", 0755);
    }

    $gLogo = '../images/gdef.png';
    $gLogo2 = "../groups/$gnameCheck->p1/gdef.png"; 
    if (!copy($gLogo, $gLogo2)) {
      echo "failed to create logo";
    }

    // Insert notifications to all friends of the post author
    $sendNotif = new SendToFols($conn, $log_username, $log_username);

    $app = "Recently Created Group <img src='/images/ngroup.png' class='notfimg'>";
    $note = $log_username.' created a new group: <br />
      <a href="/group/'.$gnameCheck->p1.'">Check it now</a>';

    $sendNotif->sendNotif($log_username, $app, $note, $conn);

    echo "group_created|$gnameCheck->p1";
    exit();
  }

  // Join Group Request
  if(isset($_POST["action"]) && $_POST['action'] == "join_group"){
    $joinGr = new JoinGroup($_POST['g'], $u);

    // Make sure vars are set
    $joinGr->errorCheck();

    // Insert to db
    $rule = $joinGr->getInvRule($conn);
    $joinGr->insertIntoDb($conn, $rule);

    // Insert notifications to all friends of the post author
    $sendNotif = new SendToFols($conn, $log_username, $log_username);

    $app = "Join To New Group <img src='/images/joing.png' class='notfimg'>";
    $note = $log_username.' joined to a new group called '.$joinGr->group.': <br />
      <a href="/group/'.$joinGr->group.'">Check it now</a>';

    $sendNotif->sendNotif($log_username, $app, $note, $conn);

    if ($rule) {
      echo "refresh_now";
      exit();
    } else {
      echo "pending_approval";
      exit();    
    }
  }

  // Approve member
  if(isset($_POST["action"]) && $_POST['action'] == "approve_member"){
    $appMem = new ManageMember($conn, $_POST['g'], $_POST['u']);

    // Check for errors
    $appMem->errorCheck();

    // Make sure request exists
    $sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? LIMIT 1";
    $appMem->requestExists($conn, $sql);

    // Add request to database
    $sql = "UPDATE gmembers SET approved=? WHERE mname=? AND gname=? LIMIT 1";
    $appMem->handleMember($conn, $sql, '1');

    echo "member_approved";
    exit();
  }

  // Decline member
  if(isset($_POST["action"]) && $_POST['action'] == "decline_member"){
    $decMem = new ManageMember($conn, $_POST['g'], $_POST['u']);

    // Check for errors
    $decMem->errorCheck();

    // Make sure request exists
    $sql = "SELECT id FROM gmembers WHERE gname=? AND mname=? AND approved=? LIMIT 1";
    $decMem->requestExists($conn, $sql);
    
    // Remove from database
    $sql = "DELETE FROM gmembers WHERE approved = ? AND mname=? AND gname=? LIMIT 1";
    $decMem->handleMember($conn, $sql, '0');

    echo "member_declined";
    exit();
  }

  if(isset($_POST["action"]) && $_POST['action'] == "quit_group"){
    $qGr = new QuitGroup($conn, $u, $_POST['g']);

    // Empty check
    $qGr->checkErrors();

    // Make sure already member
    alreadyMember($conn, $qGr->gS, $qGr->uS);

    // Check if user is the last member
    $numrows = $qGr->isLastMem($conn);
  
    // If yes, delete group
    if($numrows < 2){
      $qGr->delGr($conn);
    }

    // Remove group dir and files
    $files = glob('../groups/'.$qGr->gS.'/*'); 
    unlinkFiles($files);
    remDir('../groups/'.$qGr->gS);

    // Remove from the database
    $qGr->delMem($conn);

    echo "was_removed";
    exit();
  }

  if(isset($_POST["action"]) && $_POST['action'] == "add_admin"){
    $addAdmin = new AddAdmin($conn, $_POST['n'], $_POST['g'], $u);
    $addAdmin->emptyCheck();
  
    // Make sure already member
    alreadyMember($conn, $addAdmin->gS, $addAdmin->uS);
  
    // Check if user is not already an admin
    $addAdmin->alreadyAdmin($conn);

    // Set as admin
    $addAdmin->addAsAdmin($conn);

    echo "admin_added";
    exit();
  }

  if (isset($_FILES["avatar"]["name"]) && $_FILES["avatar"]["tmp_name"] != ""){
    $uS = $u;
    $gS =  $_SESSION["gname"];

    $logoImg = new ManageImage("avatar", $log_username);
    
    // Check for errors
    $logoImg->checkErrors();

    // Move img
    $loc = "../groups/$gS/$logoImg->db_file_name";
    $logoImg->moveRes($loc);

    // Resize img
    $logoImg->resizeImg(500, 500, $loc, $loc);

    // Update db
    $uploadLogo = new UploadLogo();
    $uploadLogo->updateDb($conn, $logoImg->db_file_name, $gS, $uS);

    mysqli_close($conn);
    header("location: ../group/$gS");
    exit();
  }

  // Add new post
  if(isset($_POST['action']) && $_POST['action'] == "new_post"){
    $grPost = new PostGroup($conn, $_POST['data'], $_POST['g'], $_POST['image']);

    // Make sure post data is not empty
    $grPost->checkForEmpty($conn);

    // Move the image(s) to the permanent folder
    if($grPost->image != "na"){
      $valImg = new InImage();
      $valImg->doInsert($grPost->image);
    }

    // Make sure account name exists (the profile being posted on)
    userExists($conn, $u);

    // Group exists?
    $grPost->groupExists($conn);

    // Set post data with text + img
    $grPost->setData();

    // Insert the status post into the database now
    $sql = "INSERT INTO grouppost(pid, gname, author, type, data, pdate)
      VALUES(?,?,?,?,?,NOW())";
    $grPost->pushToDb($conn, $sql, 'sssss', '0', $grPost->g, $u, '0', $grPost->data);

    $sql = "UPDATE grouppost SET pid=? WHERE id=? LIMIT 1";
    $grPost->updateId($conn, $sql, 'ii', $grPost->id, $grPost->id);

    // Insert notifications to all friends of the post author
    $sendNotif = new SendToFols($conn, $u, $log_username);

    $app = "Group Status Post <img src='/images/post.png' class='notfimg'>";
    $note = $log_username.' posted on '.$grPost->g.' group: <br />
      <a href="/group/'.$grPost->g.'/#status_'.$grPost->id.'">Check it now</a>';

    $sendNotif->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "post_ok|$grPost->id";
    exit();
  }

  // Reply to post
  if(isset($_POST['action']) && $_POST['action'] == "post_reply"){
    $replyGr = new ReplyGroup($conn, $_POST['sid'], $_POST['g'], $_POST['data'],
      $_POST['image'], $u);

    // Make sure post data is not empty
    $replyGr->checkForEmpty($conn);

    // Move the image(s) to the permanent folder
    if($replyGr->image != "na"){
      $valImg = new InImage();
      $valImg->doInsert($replyGr->image);
    }

    // Make sure account name exists (the profile being posted on)
    userExists($conn, $u);

    // Group exists?
    $replyGr->groupExists($conn);

    // Append potential img + text
    $replyGr->setData();

    // Insert the status into the database now
    $sql = "INSERT INTO grouppost(pid, gname, author, type, data, pdate)
      VALUES(?,?,?,?,?,NOW())";
    $replyGr->pushToDb($conn, $sql, 'issss', $replyGr->sid, $replyGr->g, $u, '1',
      $replyGr->data);

    // Insert notifications to all friends of the post author
    $sendNotif = new SendToFols($conn, $u, $log_username);

    $app = "Group Status Reply <img src='/images/reply.png' class='notfimg'>";
    $note = $log_username.' commented on '.$replyGr->g.' group: <br />
      <a href="/group/'.$replyGr->g.'/#status_'.$replyGr->sid.'">Check it now</a>';

    $sendNotif->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "reply_ok|$replyGr->id";
    exit();
  }

  if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
    $delStat = new DeleteGeneral($_POST['statusid']);

    // Check for errors
    $delStat->checkEmptyId($conn);

    // Check to make sure this logged in user actually owns that comment
    $sql = "SELECT * FROM grouppost WHERE id=? LIMIT 1";
    $delStat->userOwnsComment($conn, $sql, 'i', $delStat->statusid);
    if ($delStat->author == $log_username) {
      $delStat->checkForImg();

      // Del post
      $sql = "DELETE FROM grouppost WHERE pid=?";
      $delStat->delComment($conn, $sql, 'i', $delStat->statusid);

      mysqli_close($conn);
      echo "delete_ok";
      exit();
    }
    exit();
  }

  if (isset($_POST['action']) && $_POST['action'] == "delete_reply"){
    $delReply = new DeleteGeneral($_POST['replyid']);

    // Error check
    $delReply->checkEmptyId($conn);

    // Account check
    $sql = "SELECT * FROM grouppost WHERE id=? LIMIT 1";
    $delReply->userOwnsComment($conn, $sql, 'i', $delReply->statusid);

    if ($delReply->author == $log_username) {
      // delete comment
      $sql = "DELETE FROM grouppost WHERE id=?";
      $delReply->delComment($conn, $sql, 'i', $delReply->statusid);

      mysqli_close($conn);
      echo "delete_ok";
      exit();
    }
    exit();
  }


  // Change gr description
  if (isset($_POST['text']) && isset($_POST["gr"])){
    $changeDes = new ChangeDes($_POST['text'], $_POST['gr']);

    // Make sure gr exists
    $grExists = new PostGroup($conn, NULL, $changeDes->gr, NULL);
    $grExists->groupExists($conn);

    // Update des
    $changeDes->updateDes($conn);

    echo "des_save_success|$changeDes->text";
    exit();
  }

  // Share group status
  if(isset($_POST["action"]) && $_POST["action"] == "share"){
    $shareComm = new ShareComment($_POST['id']);

    // Check if id is set and valid
    $shareComm->checkId($conn);

    $group = mysqli_real_escape_string($conn, $_POST["group"]);

    // Make sure post exists in db
    $sql = "SELECT data, author FROM grouppost WHERE id = ? AND gname = ? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'is', $shareComm->id, $group);

    // Insert to db
    $shareComm->insertToDb($conn, $sql, 'is', $log_username, $shareComm->id, $group);

    mysqli_close($conn);
    echo "share_ok";
    exit();
  }
?>
