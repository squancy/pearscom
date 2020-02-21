<?php
  /*
    General function for pagination. Used on most pages.
  */

  // Implement custom bind_param with variable num of parameters
  class BindParam {
    private $values = array(), $types = '';

    public function add($type, &$value) {
      $this->values[] = $value;
      $this->types .= $type;
    }

    public function get() {
      return array_merge(array($this->types), $this->values);
    }
  }

  function pagination($conn, $sql, $params1, $url_n) {
    $postfix = '';
    $pnum = 21;

    // If we are on photos page set page rows to 40
    if (substr($url_n, 0, 7) == '/photos') {
      $pnum = 40;
    }

    // If we are doing pagination on friend requests append 'f' -> '?pnf'
    if ($url_n == '/notifications' && $params1 == 'si') {
      $postfix = 'f';
    }

    // Append the appropriate query symbol to URL
    if ($url_n == '/article_suggestions' || $url_n == '/notifications') {
      $url_n .= '?';
    } else {
      $url_n .= '&';
    }

    $stmt = $conn->prepare($sql);
    $bindParam = new BindParam;

    // Dynamic params after the 4th argument
    $values = array_slice(func_get_args(), 4);
    if ($values) {
      for($i = 0; $i < count($values); $i++) {
        $bindParam->add($params1[$i], $values[$i]);
      }
      call_user_func_array(array($stmt, 'bind_param'), $bindParam->get());
    }
    $stmt->execute();
    $stmt->bind_result($rows);
    $stmt->fetch();
    $stmt->close();

    $page_rows = $pnum;
    $last = ceil($rows / $page_rows);
    if($last < 1){
      $last = 1;
    }

    $pagenum = 1;
    if(isset($_GET['pn'])){
      $pagenum = preg_replace('#[^0-9]#', '', $_GET['pn']);
    }

    if($pagenum < 1) { 
      $pagenum = 1; 
    } else if($pagenum > $last) { 
      $pagenum = $last; 
    }

    $limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;

    $paginationCtrls = '';
    // If there is more than 1 page worth of results
    if($last != 1){
      if($pagenum > 1) {
        $previous = $pagenum - 1;
        $paginationCtrls .= '<a href="'.$url_n.'pn'.$postfix.'='.$previous.'">Previous</a>
            &nbsp;&nbsp;';

        for($i = $pagenum - 4; $i < $pagenum; $i++){
          if($i > 0){
            $paginationCtrls .= '<a href="'.$url_n.'pn'.$postfix.'='.$i.'">'.$i.'</a> &nbsp;';
          }
        }
      }
      $paginationCtrls .= ''.$pagenum.' &nbsp; ';
      for($i = $pagenum + 1; $i <= $last; $i++){
        $paginationCtrls .= '<a href="'.$url_n.'pn'.$postfix.'='.$i.'">'.$i.'</a> &nbsp;';
        if($i >= $pagenum + 4){
          break;
        }
      }
      if($pagenum != $last) {
        $next = $pagenum + 1;
        $paginationCtrls .= '&nbsp;&nbsp;<a href="'.$url_n.'pn'.$postfix.'='.$next.'">Next</a>';
      }
    }
    return [$paginationCtrls, $limit];
  }
?>
