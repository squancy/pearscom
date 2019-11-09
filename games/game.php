<!DOCTYPE html>
<html>
<head>
	<title>Logic Game</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script type="text/javascript">
		function _(e){
			return document.getElementById(e);
		}

		function autorand(min, max) {
		    return Math.floor(Math.random() * (max - min + 1)) + min;
		}

		var level = 0;

		function chooseLevel(wh){
			_("levels").innerHTML = "<p>You choosed level "+wh+". Now, let's play! <button onclick='takeUser(\"1\",\"no\")' style='padding: 15px; font-size: 18px;'>1</button> <button onclick='takeUser(\"2\",\"no\")' style='padding: 15px; font-size: 18px;'>2</button> <button onclick='takeUser(\"3\",\"no\")' style='padding: 15px; font-size: 18px;'>3</button></p>";
			level = wh;
		}

		function getAll(){
			var x = document.getElementsByClassName("greendots");
			var alldots = x.length;
			return alldots;
		}

		function selectLevel(){
			if(level == 1){
				setTimeout(function(){ autoOne(); }, 1500);
			}else if(level == 2){
				setTimeout(function(){ autoTwo(); }, 1500);
			}else{
				setTimeout(function(){ autoThree(); }, 1500);
			}
		}

		function takeUser(wh,isrob){ // 2, yes
			var alldots = getAll();
			for(var i=0; i<wh; i++){
				var cur = alldots - i;
				_("bubble_"+cur).style.backgroundColor = "grey";
				_("bubble_"+cur).classList.remove("greendots");
			}
			if(isrob == "no"){
				selectLevel();
			}else{
				console.log("all dots: "+alldots);
				console.log("wh: "+wh);
				if(alldots-wh == 1){
					_("levels").innerHTML = "<p>Oops... You lost the match! <button onclick='retry()' style='padding: 15px; font-size: 18px;'>Play Again</button></p>";
				}
			}
		}

		function autoOne(){
			var num = autorand(1,3);
			var alldots = getAll();
			if(num >= alldots){
				_("levels").innerHTML = "<p>Congrats! You won the match! <button onclick='retry()' style='padding: 15px; font-size: 18px;'>Play Again</button></p>";
			}
			for(var i=0; i<num; i++){
				var cur = alldots - i;
				_("bubble_"+cur).style.backgroundColor = "grey";
				_("bubble_"+cur).classList.remove("greendots");
			}
		}

		function autoTwo(){
			var wh = autorand(1,4);
			var alldots = getAll();
			if(wh >= 2){
				if(alldots%4 == 0){
					var zrand = 3;
					takeUser(zrand);
				}else if(alldots%4 == 1){
					var zrand = autorand(1,3);
					takeUser(zrand,"yes");
				}else if(alldots%4 == 2){
					var zrand = 1;
					takeUser(zrand,"yes");
				}else{
					var zrand = 2;
					takeUser(zrand,"yes");
				}
			}else{
				var num = autorand(1,3);
				if(num >= alldots){
					_("levels").innerHTML = "<p>Congrats! You won the match! <button onclick='retry()' style='padding: 15px; font-size: 18px;'>Play Again</button></p>";
				}
				for(var i=0; i<num; i++){
					var cur = alldots - i;
					_("bubble_"+cur).style.backgroundColor = "grey";
					_("bubble_"+cur).classList.remove("greendots");
				}
			}
		}

		function autoThree(){
			var alldots = getAll();
			if(alldots%4 == 0){
				var zrand = 3;
				takeUser(zrand,"yes");
			}else if(alldots%4 == 1){
				var zrand = autorand(1,3);
				takeUser(zrand,"yes");
			}else if(alldots%4 == 2){
				var zrand = 1;
				takeUser(zrand,"yes");
			}else{
				var zrand = 2;
				takeUser(zrand,"yes");
			}
		}

		function retry(){
			for(var i=30; i>0; i--){
				_("bubble_"+i).style.backgroundColor = "green";
				_("bubble_"+i).classList.add("greendots");
			}
			_("levels").innerHTML = '<p>Choose a level: <button onclick="chooseLevel(\'1\')" style="padding: 15px; font-size: 18px;">1</button> <button onclick="chooseLevel(\'2\')" style="padding: 15px; font-size: 18px;">2</button> <button onclick="chooseLevel(\'3\')" style="padding: 15px; font-size: 18px;">3</button></p>'
		}
	</script>
</head>
<body>
	<h1>Logic Game</h1>
	<?php for($i=30; $i>0; $i--){ 
		echo "<div id='bubble_".$i."' class='greendots' style='background-color: green; width: 30px; height: 30px; border-radius: 50%; float: left; margin-right: 5px; margin-bottom: 5px;'></div>";
	} ?>
	<div style="clear: both;"></div>
	<br><span id="levels"><p>Choose a level: <button onclick="chooseLevel('1')" style="padding: 15px; font-size: 18px;">1</button> <button onclick="chooseLevel('2')" style="padding: 15px; font-size: 18px;">2</button> <button onclick="chooseLevel('3')" style="padding: 15px; font-size: 18px;">3</button></p></span>
</body>
</html>