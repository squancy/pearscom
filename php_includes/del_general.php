<?php
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
?>
