<?php
  /*
    General function for pagination. Used on most pages.
  */

  function pagination($conn, $sql, $params1, $params2, $url_prev, $url_n) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($params1, $params2);
    $stmt->execute();
    $stmt->bind_result($rows);
    $stmt->fetch();
    $stmt->close();

    $page_rows = 21;
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
        $paginationCtrls .= '<a href="'.$url_prev.'&pn='.$previous.'">Previous</a>
            &nbsp;&nbsp;';

        for($i = $pagenum - 4; $i < $pagenum; $i++){
          if($i > 0){
            $paginationCtrls .= '<a href="'.$url_n.'&pn='.$i.'">'.$i.'</a> &nbsp;';
          }
        }
      }
      $paginationCtrls .= ''.$pagenum.' &nbsp; ';
      for($i = $pagenum + 1; $i <= $last; $i++){
        $paginationCtrls .= '<a href="'.$url_n.'&pn='.$i.'">'.$i.'</a> &nbsp;';
        if($i >= $pagenum + 4){
          break;
        }
      }
      if($pagenum != $last) {
        $next = $pagenum + 1;
        $paginationCtrls .= '&nbsp;&nbsp;<a href="'.$url_n.'&pn='.$next.'">Next</a>';
      }
    }
    return [$paginationCtrls, $limit];
  }
?>
