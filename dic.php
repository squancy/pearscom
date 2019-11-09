<!DOCTYPE html>
<html>
<head>
	<title>Loquela</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Makes learning foreign words fun.">
	<link rel="icon" href="/images/wfav.png" type="image/x-icon"/>
	<link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
    <meta name="apple-mobile-web-app-title" content="Pearscom">
    <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
    <meta name="theme-color" content="#282828" />
	<link rel="stylesheet" type="text/css" href="/style/style_lin.css">
</head>
<body>
	<div class="outer">
		<div class="middle">
			<div id="wordToTrans"></div>
			<input type="text" name="word" id="text" placeholder="Write solution">
			<div id="searchOutput"></div>
			<div class="btnCollect">
				<button class="show" id="show">Show solution</button>
				<button id="correct" class="show" data-show="false">Correct words</button>
				<button id="missed" class="show" data-show="false">Missed words</button>
			</div>
			<div class="btnCollect add">
			    <button class="greyBtn" id="addWords">Add words</button>
			</div>
			<div class="btnCollect add">
			    <textarea placeholder="Words you want to learn" class="inputWords hide" id="txtArea"></textarea>
			    <button class="greyBtn hide" id="submitBtn">Submit</button>
			</div>
			<p class="info hide" id="infoText">Note: accepted format is foreign word<sub>1</sub>#word<sub>1</sub>, ... ,foreign word<sub>n</sub>#word<sub>n</sub></p>
			<span class="hide" id="statusText"></span>
			<div class="btnCollect"><div id="solution"></div></div>
			<div id="corrTable" class="cmTable"></div>
			<div id="missTable" class="cmTable"></div>
			<div id="sentence"></div>
		</div>
	</div>
	<script src="/loq.js" type="text/javascript"></script>
</body>
</html>