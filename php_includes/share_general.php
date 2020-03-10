<?php
  require_once '../ccov.php';
  
  class ShareComment {
    public function __construct($id, $isCustom = false) {
      $this->id = preg_replace('#[^0-9]#', '', $id);
      $this->isCustom = $isCustom;
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
        if (!$this->isCustom) {
          $data = '
            <div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared via <a href="/user/'.$row["author"].'/">'.$row["author"].'</a></p></div><hr class="dim"><div id="share_data">'.$row["data"].'</div>';
        } else {
          $data = call_user_func($this->isCustom, $row);
        }

        $stmt->close();
        
        $this->postToStatus($conn, $log_username, $data);
        $id = mysqli_insert_id($conn);
        $this->updateDb($conn, $id);
      }
    }
  }

?>
