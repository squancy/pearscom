<?php
	// Check to see if the user is not logged in
	require_once '../php_includes/check_login_statues.php';
	require_once '../php_includes/perform_checks.php';
	require_once '../php_includes/insertImage.php';
	require_once '../php_includes/sentToFols.php';
	require_once '../safe_encrypt.php';
  require_once '../php_includes/ind.php';

  // Make sure file is not accessed directly
	if($user_ok != true || !$log_username) {
		exit();
	}

  // Set article id
  $ar = "";
  if(isset($_POST["arid"]) && $_POST["arid"] != ""){
    $ar = $_POST["arid"];
  }else if(isset($_SESSION["id"]) && !empty($_SESSION["id"])){
    $ar = $_SESSION["id"];
  }

  class PostGeneral {
    public function __construct($type, $account_name, $data, $image, $conn) {
      $this->type = preg_replace('#[^a-z]#', '', $type);
      $this->account_name = mysqli_real_escape_string($conn, $account_name);
      $this->data = htmlentities($data);
      $this->image = $image;
    }

    public function checkForEmpty($conn) {
      if(strlen($this->data) < 1 && $this->image == "na"){
        mysqli_close($conn);
        echo "data_empty";
        exit();
      }
    }

    public function performImage($img) {
      $pImage = new InImage();
      $pImage->doInsert($img);
    }

    public function typeCheck($conn) {
      if($this->type != ("a" || "c")){
        mysqli_close($conn);
        echo "type_unknown";
        exit();
      }
    }

    public function setData() {
      if($this->data == "||na||" && $this->image != "na"){
        $this->data = '<img src="/permUploads/'.$this->image.'" /><br>';
      }else if($this->data != "||na||" && $this->image != "na"){
        $this->data = $this->data.'<br /><img src="/permUploads/'.$this->image.'" /><br>';
      }
    }

    public function pushToDb($conn, $log_username, $ar) {
      $sql = "INSERT INTO article_status(account_name, author, type, data, artid, postdate) 
				VALUES(?,?,?,?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssi", $this->account_name, $log_username, $this->type, $this->data,
        $ar);
      $stmt->execute();
      $stmt->close();

      $id = mysqli_insert_id($conn);

      $sql = "UPDATE article_status SET osid=? WHERE id=? AND artid = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iii", $id, $id, $ar);
      $stmt->execute();
      $stmt->close();
    }
  }

  class PostReply extends PostGeneral {
    public function __construct($osid, $account_name, $data, $image, $conn) {
      $this->osid = preg_replace('#[^0-9]#', '', $osid);
      $this->account_name = mysqli_real_escape_string($conn, $account_name);
      $this->data = htmlentities($data);
      $this->image = $image;
    } 

    public function pushToDb($conn, $log_username, $ar) {
      $b = 'b';
      $sql = "INSERT INTO article_status(osid, account_name, author, type, data, artid,
        postdate) VALUES(?,?,?,?,?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("issssi", $this->osid, $this->account_name, $log_username, $b,
        $this->data, $ar);
      $stmt->execute();
      $row = $stmt->num_rows;
      if($row < 1){
        $id = mysqli_insert_id($conn);
      }
      $stmt->close(); 
      return $id;
    }
  }

  function getPostTime($conn, $ar) {
    $sql = "SELECT post_time FROM articles WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ar);
    $stmt->execute();
    $stmt->bind_result($ptime);
    $stmt->fetch();
    $stmt->close();
    return $ptime;
  }

  class DeleteGeneral {
    public function __construct($statusid) {
      $this->statusid = preg_replace('#[^0-9]#', '', $statusid);
    } 

    public function checkEmptyId($conn) {
      if(!isset($this->statusid) || !$this->statusid){
        mysqli_close($conn);
        echo "status id is missing";
        exit();
      }
    }

    public function userOwnsComment($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($row = $result->fetch_assoc()) {
        $this->account_name = $row["account_name"]; 
        $this->author = $row["author"];
        $this->data = $row["data"];
      }
      $stmt->close();
    }

    public function checkForImg() {
      if(preg_match('/<img.+src=[\'"](?P<src>.+)[\'"].*>/i', $this->data, $has_image)){
        $source = '../'.$has_image['src'];
        if (file_exists($source)) {
          unlink($source);
        }
      }
    }

    public function delComment($conn, $sql, $param1, ...$values) { 
			$stmt = $conn->prepare($sql);
			$stmt->bind_param($param1, ...$values);
			$stmt->execute();
			$stmt->close();
    }
  }

  class ShareComment {
    public function __construct($id) {
      $this->id = preg_replace('#[^0-9]#', '', $id);
    }

    public function checkId($conn) {
      if(!isset($this->id) || !$this->id){
        mysql_close($conn);
        echo "fail";
        exit();
      }
    }

    public function postExists($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      if($numrows < 1){
        mysqli_close($conn);
        echo "fail";
        exit();
      }
    }

    private function postToStatus($conn, $log_username, $data) {
      $a = 'a';
      $sql = "INSERT INTO status(account_name, author, type, data, postdate)
        VALUES(?,?,?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssss", $log_username, $log_username, $a, $data);
      $stmt->execute();
      $stmt->close();
    } 

    private function updateDb($conn, $id) {
      $sql = "UPDATE status SET osid=? WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ii", $id, $id);
      $stmt->execute();
      $stmt->close();
    }

    public function insertToDb($conn, $sql, $param1, $log_username, ...$values) {
      $sql = "SELECT * FROM article_status WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $result = $stmt->get_result();
      while($row = $result->fetch_assoc()){
        /*
          TODO: $data must be one line, otherwise <br> gets inserted into style in db
          -> fix issue by creating a separate table for shares and inserting the necessary
          information instead of pushing hardcoded HTML to the database
        */
        $data = '
          <div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared via <a href="/user/'.$row["author"].'/">'.$row["author"].'</a></p></div><hr class="dim"><div id="share_data">'.$row["data"].'</div>
        ';
        $stmt->close();
        
        $this->postToStatus($conn, $log_username, $data);
        $id = mysqli_insert_id($conn);
        $this->updateDb($conn, $id);
      }
    }
  }

	if (isset($_POST['action']) && $_POST['action'] == "status_post"){
    $statPost = new PostGeneral($_POST['type'], $_POST['user'], $_POST['data'],
      $_POST['image'], $conn);

    // Make sure posted data is not empty
    $statPost->checkForEmpty($conn);

    // Validate image, if any
    if($statPost->image != "na"){
      $valImg = new InImage();
      $valImg->doInsert($statPost->image);
    }

		// Make sure type is either a or c
    $statPost->typeCheck($conn);

    // Only image or only text or both
    $statPost->setData();

		// Make sure account name exists (the profile being posted on)
    userExists($conn, $statPost->account_name);
    
		// Insert the status post into the database now
    $statPost->pushToDb($conn, $log_username, $ar);

		$a = 'a';

		// Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $ptime = getPostTime($conn, $ar);

    $app = "Article Status Post <img src='/images/post.png' class='notfimg'>";
    $note = $log_username.' posted on: <br />
      <a href="/articles/'.$ptime.'/'.$log_username.'/#status_'.$id.'">Below an article</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);

		mysqli_close($conn);
		echo "post_ok|$id";
		exit();
	}

	if (isset($_POST['action']) && $_POST['action'] == "status_reply"){
    $replyPost = new PostReply($_POST['sid'], $_POST['user'], $_POST['data'], $_POST['image'],
      $conn);

    // Validate image, if any
    if($replyPost->image != "na"){
      $valImg = new InImage();
      $valImg->doInsert($replyPost->image);
    }

    if (!$ar) {
      $ar = indexId($conn, $replyPost->osid, "article_status", "artid");
    }

    // Only image or only text or both
    $replyPost->setData();

		// Make sure account name exists (the profile being posted on)
    userExists($conn, $replyPost->account_name);

    // Insert reply to db
    $id = $replyPost->pushToDb($conn, $log_username, $ar);
    
    // Send notif about reply
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $ptime = getPostTime($conn, $ar);

    $app = "Article Status Reply <img src='/images/reply.png' class='notfimg'>";
    $note = $log_username.' posted on: <br />
      <a href="/articles/'.$ptime.'/'.$log_username.'/#status_'.$osid.'">Below an article</a>';

    $sendReply->sendNotif($log_username, $app, $note, $conn);

		mysqli_close($conn);
		echo "reply_ok|$id";
		exit();
	}

	if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
    $delStat = new DeleteGeneral($_POST['statusid']);

    // Make sure id is not empty and set
    $delStat->checkEmptyId($conn);

		// Check to make sure this logged in user actually owns that comment
    $sql = "SELECT account_name, author, data FROM article_status WHERE id=? AND artid=?
      LIMIT 1";
    $delStat->userOwnsComment($conn, $sql, 'ii', $delStat->statusid, $ar);
    if ($delStat->author == $log_username || $delStat->account_name == $log_username) {
      $delStat->checkForImg();

      // Delete status
      $sql = "DELETE FROM article_status WHERE osid=? AND artid = ?";
      $delStat->delComment($conn, $sql, 'ii', $delStat->statusid, $ar);
    }
  
    mysqli_close($conn);
    echo "delete_ok";
    exit();
  }

	if (isset($_POST['action']) && $_POST['action'] == "delete_reply"){
    $delReply = new DeleteGeneral($_POST['replyid']);

    // Make sure id is not empty and set
    $delReply->checkEmptyId($conn);

		// Check to make sure this logged in user actually owns that comment
    $sql = "SELECT osid, account_name, author FROM article_status WHERE id=? AND artid = ?
      LIMIT 1";
    $delReply->userOwnsComment($conn, $sql, 'ii', $delReply->statusid, $ar);
    if ($delReply->author == $log_username || $delReply->account_name == $log_username) {
      $delReply->checkForImg();
      echo 'asd';

      // Delete reply
			$sql = "DELETE FROM article_status WHERE id=? AND artid = ?";
      $delReply->delComment($conn, $sql, 'ii', $delReply->statusid, $ar);
    }

    mysqli_close($conn);
    echo "delete_ok";
    exit();
	}

	if(isset($_POST['action']) && $_POST['action'] == "share"){
    $shareComm = new ShareComment($_POST['id']);

    // Check if id is set and valid
    $shareComm->checkId($conn);

    if ($ar == "") {
      $ar = indexId($conn, $id, "article_status", "artid");
    }

    // Make sure post exists in db
    $sql = "SELECT author, data FROM article_status WHERE id=? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'i', $shareComm->id);

    // Insert share to db
    $sql = "SELECT * FROM article_status WHERE id=? LIMIT 1";
    $shareComm->insertToDb($conn, $sql, 'i', $log_username, $shareComm->id);

		mysqli_close($conn);
		echo "share_ok";
		exit();
	}
?>
