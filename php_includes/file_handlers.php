<?php
  function unlinkFiles($files) {
    foreach($files as $file){
      if(is_file($file)) {
        unlink($file); 
      }
    }
  }

  function remDir($path) {
    if(is_dir($path) && file_exists($path)){
      rmdir($path);
    }
  }

  function renameFiles($files, $addDir) {
    foreach($files as $file){
      if(is_file($file)){
        $file_ori = $file;
        $file = substr($file, $lena);
        $file = "$file";
        $wto = "user/{$un}/{$addDir}{$file}";
        rename($file_ori, $wto);
      }
    }
  }
?>
