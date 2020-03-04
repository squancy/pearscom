<?php
  // Check to see if the user is not logged in
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/sentToFols.php';
  require_once 'm_array.php';
  
  if(!$user_ok || !$log_username) {
    exit();
  }

  class EditInfo {
    public function __construct() {
      $this->nameArr = ['j' => 'job', 'ta' => 'about', 'pro' => 'profession', 'city' => 'city',
        'state' => 'state', 'mobile' => 'mobile', 'hometown' => 'hometown', 'fmovie' =>
        'fav_movie', 'fmusic' => 'fav_music', 'pstatus' => 'par_status', 'elemen' => 'elemen',
        'high' => 'high', 'uni' => 'uni', 'politics' => 'politics', 'religion' => 'religion',
        'language' => 'language', 'nd_day' => 'nd_day', 'nd_month' => 'nd_month', 'interest' =>
        'interest', 'notemail' => 'notemail', 'website' => 'website', 'address' => 'address',
        'degree' => 'degree', 'quotes' => 'quotes'];
      foreach ($this->nameArr as $name => $val) {
        $this->{$name} = htmlentities($_POST[$name]);
      }
    }

    public function checkEmail($conn, $log_username) {
      $sql = "SELECT email FROM users WHERE username=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $log_username);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($row = $result->fetch_assoc()){
        $this->email_user = $row["email"];
      }
      $stmt->close();
    }

    public function errorHandling() {
      global $months;
      if($this->notemail == $this->email_user){
        echo "Please do not add your log in email address";
        exit();
      }else if($this->nd_month && !$this->nd_day){
        echo "Please add your full name day";
        exit();
      }else if($this->nd_day && !$this->nd_month){
        echo "Please add your full name day";
        exit();
      }else if($this->nd_day && $this->nd_day){
        if(!is_numeric($this->nd_day) || !in_array($this->nd_month, $months)){
          echo "Please give a valid name day";
          exit();
        }
      }

      if(!$this->j && !$this->ta && !$this->pro && !$this->city && !$this->state &&
        !$this->mobile && !$this->hometown && !$this->fmovie && !$this->fmusic &&
        !$this->pstatus && !$this->elemen && !$this->high && !$this->uni && !$this->politics &&
        !$this->religion && !$this->language && !$this->nd_day && !$this->nd_month &&
        !$this->interest && !$this->notemail && !$this->website && !$this->address &&
        !$this->degree && !$this->quotes){
        echo "Please fill in at least 1 field";
        exit();
      }else if(strlen($this->j) > 150 || strlen($this->ta) > 1000 || strlen($this->pro) > 150
        || strlen($this->city) > 150 || strlen($this->state) > 150 ||
        strlen($this->mobile) > 150 || strlen($this->hometown) > 150 ||
        strlen($this->fmovie) > 400 || strlen($this->fmusic) > 400 ||
        strlen($this->pstatus) > 150 || strlen($this->elemen) > 150 ||
        strlen($this->high) > 150 || strlen($this->uni) > 150 ||
        strlen($this->politics) > 150 || strlen($this->religion) > 150 ||
        strlen($this->language) > 150 || strlen($this->interest) > 150 ||
        strlen($this->notemail) > 150 || strlen($this->website) > 150 ||
        strlen($this->address) > 150 || strlen($this->degree) > 150 ||
        strlen($this->quotes) > 400){
        echo "You reached the maximum character limit";
        exit();
      }else if($this->nd_day < 1 || $this->nd_day > 31){
        echo "Invalid name day";
        exit();
      }
    }

    public function pushToDb($conn, $log_username) {
      $sql = "INSERT INTO edit(username, job, about, profession, city, state, mobile, hometown,
        fav_movie, fav_music, par_status, elemen, high, uni, politics, religion, nd_day,
        nd_month, interest, notemail, website, language, address, degree, quotes, changemade)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssssssssssssssissssssss", $log_username, $this->j, $this->ta,
        $this->pro, $this->city, $this->state, $this->mobile, $this->hometown, $this->fmovie,
        $this->fmusic, $this->pstatus, $this->elemen, $this->high, $this->uni, $this->politics,
        $this->religion, $this->nd_day, $this->nd_month, $this->interest, $this->notemail,
        $this->website, $this->language, $this->address, $this->degree, $this->quotes);
      $stmt->execute();
      $stmt->close();
    }

    public function updateWhole($conn, $log_username) {
      foreach ($this->nameArr as $name => $val) {
        if ($this->{$name}) {
          $sql = "UPDATE edit SET ".$val."=? WHERE username=?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("ss", $this->{$name}, $log_username);
          $stmt->execute();
          $stmt->close();
        }
      }
    }
  }

  if(isset($_POST["job"]) || isset($_POST["ta"]) || isset($_POST["pro"]) ||
    isset($_POST["city"]) || isset($_POST["state"]) || isset($_POST["mobile"]) ||
    isset($_POST["hometown"]) || isset($_POST["fmovie"]) || isset($_POST["fmusic"]) ||
    isset($_POST["pstatus"]) || isset($_POST["elemen"]) || isset($_POST["high"]) ||
    isset($_POST["uni"]) || isset($_POST["politics"]) || isset($_POST["religion"]) ||
    isset($_POST["language"]) || isset($_POST["nd_day"]) && isset($_POST["nd_month"]) ||
    isset($_POST["interest"]) || isset($_POST["notemail"]) || isset($_POST["website"]) ||
    isset($_POST["address"]) || isset($_POST["degree"]) || isset($_POST["quotes"])){

    $eInfo = new EditInfo();

    // Check email for security purposes
    $eInfo->checkEmail($conn, $log_username);

    // Error handling
    $eInfo->errorHandling($conn, $log_username);

    // Insert into database
    $eInfo->pushToDb($conn, $log_username);

    // Update db
    $eInfo->updateWhole($conn, $log_username);

    // Notifications
    $sendNotif = new SendToFols($conn, $log_username, $log_username);

    $app = "Edited Profile Information <img src='/images/qm.png' class='notfimg'>";
    $note = $log_username.' edited his/her profile on:
      <br /><a href="/user/'.$log_username.'/">'.$log_username.'&#39;s Profile</a>';

    $sendNotif->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "edit_success";
    exit();
  }
?>
