<?php
    // Get stock data as a JSON file from an outer resource (Portfolio) 
    if(isset($_POST["refresh"])){
        $arr = array();

        // Create an array with the names of the most important Hungarian stocks
        $stockNames = ["4IG", "AUTOWALLIS", "DUNAHOUSE", "ESTMEDIA", "FUTURAQUA", "GSPARK", "KONZUM", "MOL", "MTELEKOM", "OPUS", "PANNERGY", "WABERERS"];

        // Dynamically get the content of the JSON file
        for($i = 0; $i < count($stockNames); $i++){
            $content = file_get_contents('https://data.portfolio.hu/all/json/'.$stockNames[$i].':interval=1D');
            array_push($arr, $content);
        }

        // Output the result of the array as chunks of strings separated by a specific delimiter (|||) since ajax cannot handle arrays
        foreach ($arr as $key) {
            echo $key."|||";
        }
        exit();
    }
?>
<html>
    <head>
        <title>stockai</title>
    </head>

    <body>
        <script>
            setInterval(fetchData, 5000)

            function fetchData(){
                let req = new XMLHttpRequest();
                req.open("POST", "stockai.php", false)
				req.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
                req.onreadystatechange = function(){
					if(req.readyState == 4 && req.status == 200){
						console.log(req.responseText)
					}                    
                }
				req.send("refresh=y")        
            }            
        </script>
    </body>
</html>
