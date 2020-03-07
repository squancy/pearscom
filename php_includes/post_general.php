<?php
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

    private function manageDb($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $stmt->close();
    }

    public function pushToDb($conn, $sql, $param1, ...$values) {
      $this->manageDb($conn, $sql, $param1, ...$values);
      $this->id = mysqli_insert_id($conn);
    }

    public function updateId($conn, $sql, $param1, ...$values) {
      $this->manageDb($conn, $sql, $param1, ...$values);
    }
  }

  class PostReply extends PostGeneral {
    public function __construct($osid, $account_name, $data, $image, $conn) {
      $this->osid = preg_replace('#[^0-9]#', '', $osid);
      $this->account_name = mysqli_real_escape_string($conn, $account_name);
      $this->data = htmlentities($data);
      $this->image = $image;
    } 

    public function pushToDb($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $row = $stmt->num_rows;
      $stmt->close(); 
      if($row < 1){
        $this->id = mysqli_insert_id($conn);
      }
    }
  }
?>
