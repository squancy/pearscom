<?php
	header('Location: /index');
?>
<?php
	$dir = "tempUploads/";
	foreach (glob($dir."*") as $file){
		if (filemtime($file) < time() - 86400){
			unlink($file);
	    }
	}
?>