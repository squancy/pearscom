<html>
    <head>
        <title>Stock AI - Information</title>
        <meta charset="utf-8">
        <meta lang="en">
		<link rel="stylesheet" type="text/css" href="/style/style_ai.css">
        <link rel="icon" type="image/x-icon" href="/images/favstock.png">
        <meta name="description" content="An open-source service for analysing the most important Hungarian stocks.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
    </head>
    <body>
		<header>
			<a href="/main.php">Stock AI</a>
			<div class="headerRight">
				<a href="/downloads.php">Downloads</a>
				<a href="/cont.php">Contribute</a>
				<a href="/about.php">Information</a>
			</div>
		</header>
		<br><br><br><br>
    <div class="hStocks nonStockCont">
			<div class="stockCont infoHolder" style="border-radius: 5px;">
				<b class="heading">1. Introduction</b>
        <b>&emsp;1.1 What is Stock AI?</b>
				<p>&emsp;Stock AI is an open-source and freely distributable artificial intelligence program for analyzing the most important Hungarian stocks. With the help of the techincal analysis and by using some of the most well-known indicators and moving averages it is able to give suggestions whether a stock is worth buying / selling or not. The site is refreshed in every 3 seconds from a 3rd-party database in order to provide the most up-to-date prices.</p>

        <b>&emsp;1.2 The goal of Stock AI</b>
        <p>&emsp;The service is dedicated to help anyone interested in finances, business and the stock market in order to strengthen a currently open position and to serve as a stronghold in financial decisions.</p>

        <b>&emsp;1.3 Legal disclaimer</b>
        <p>&emsp;As stated above, the goal of the service is to serve as a stronghold and suggestion therefore making financial decisions only by relying on the service is a terrible idea. Please note that Stock AI does not take any responsibility for any sort of financial loss or winning.</p>

        <b class="heading">2. What can be found on the site?</b>
        <b>&emsp;2.1 Main page</b>
				<p>&emsp;The main page of the service contains information about some of the most important Hungarian stocks. The first section consists of general information about the certain stock, including minimum and maximum prices, volume, trades of the day and much more. Indicators and moving averages are placed in the second section, including RSI, Momentum, Stochastic, EMA etc. The third section is a simple summary of the current stock and contains a trade sign.</p>

        <b>&emsp;2.2 Downloads page</b>
				<p>&emsp;Since Stock AI is an open-source service you can legally download, edit and distribute the code. It is possible to download only individual files and images but you can get the complete service as a .tar or .zip file. On the other hand, the <a href="https://github.com/squancy/stockai">GitHub</a> page of Stock AI can be also used for this purpose.</p>

        <b>&emsp;2.3 Contribute page</b>
				<p>&emsp;It is possible to contribute to the development of the service by giving feedback and suggestions via <a href="mailto:mark.frankli@pearscom.com">e-mail</a> or by editing and committing the source code via <a href="https://github.com/squancy/stockai">GitHub</a>.</p>

        <b>&emsp;2.4 Information page</b>
				<p>&emsp;If any question arises during the use of the service make sure you check the <a href="/about.php">Information</a> page first since there is a high change that you find the information you need. You can read about Stock AI's features, restrictions and documents as well as its goal and structure.</p>

        <b class="heading">3. Techincal analysis</b>
        <b>&emsp;3.1 Currenly available stocks</b>
        <p>&emsp;4IG, MOL, Futuraqua, Estmedia, Waberer's and Mtelekom are the currently available stocks. </p>

        <b>&emsp;3.2 Indicators</b>
        <b>&emsp;&emsp;3.21 RSI (Relative Strength Index)</b>
        <p>&emsp;&emsp;Relative Strength Index (RSI) is a momentum indicator that measures the magnitude of recent price changes to evaluate overbought or oversold conditions in the price of a stock or other asset. The RSI is displayed as an oscillator (a line graph that moves between two extremes). It can have a reading from 0 to 100. On Stock AI it is calculated with a 14 day time interval and has the following trade signs: <span class="overbought">Strong buy</span>, <span class="overbought">Buy</span>, <span class="neutral">Neutral</span>, <span class="oversold">Sell</span> and <span class="oversold">Strong sell</span>.</p>

        <b>&emsp;&emsp;3.22 RSI Low</b>
        <p>&emsp;&emsp;The same as the standard RSI except that it is calculated with a 70-30 range instead of an 80-20.</p>

        <b>&emsp;&emsp;3.23 Momentum</b>
        <p>&emsp;&emsp;The Momentum Indicator (MOM) is a leading indicator measuring a security's rate-of-change. It compares the current price with the previous price from a number of periods ago.The ongoing plot forms an oscillator that moves above and below 0. It is a fully unbounded oscillator and has no lower or upper limit. On Stock AI it is calculated with a 14 day time interval and has the following trade signs: <span class="overbought">Strong buy</span>, <span class="overbought">Buy</span>, <span class="neutral">Neutral</span>, <span class="oversold">Sell</span> and <span class="oversold">Strong sell</span>.</p>

        <b>&emsp;&emsp;3.24 Stochastic</b>
        <p>&emsp;&emsp;A stochastic oscillator is a momentum indicator comparing a particular closing price of a security to a range of its prices over a certain period of time. The sensitivity of the oscillator to market movements is reducible by adjusting that time period or by taking a moving average of the result. On Stock AI it is calculated with a 6 day time interval and has the following trade signs: <span class="overbought">Buy</span>, <span class="overbought">Buy sign</span>, <span class="neutral">Neutral</span>, <span class="oversold">Sell sign</span> and <span class="oversold">Sell</span>.</p>

        <b>&emsp;3.3 Moving averages</b>
        <b>&emsp;&emsp;3.31 EMA (Exponential Moving Average)</b>
        <p>&emsp;&emsp;An exponential moving average (EMA) is a type of moving average (MA) that places a greater weight and significance on the most recent data points. The exponential moving average is also referred to as the exponentially weighted moving average. On Stock AI EMA3, EMA9 and EMA14 are calculated with a 3, 9 and 14 day time interval, respectively: <span class="overbought">Strong buy</span>, <span class="overbought">Buy</span>, <span class="neutral">Neutral</span>, <span class="oversold">Sell</span> and <span class="oversold">Strong sell</span>.</p>

        <b class="heading">4. Trade signs, weights and summary</b>
        <b>&emsp;4.1 Trade signs</b>
        <b>&emsp;&emsp;4.11 Strong sell</b>
        <p>&emsp;&emsp;Indicates a confident and strong sign to sell a certain stock. Weighted as 2x.</p>
        <b>&emsp;&emsp;4.12 Sell</b>
        <p>&emsp;&emsp;Indicates a probable sign to sell a certain stock. Weighted as 1x.</p>
        <b>&emsp;&emsp;4.13 Sell sign</b>
        <p>&emsp;&emsp;Indicates an uncertain and weak sign to sell a certain stock. Weighted as 0.75x.</p>
        <b>&emsp;&emsp;4.14 Neutral</b>
        <p>&emsp;&emsp;Indicates uncertainty and indifference. Does not have a weight.</p>
        <b>&emsp;&emsp;4.15 Buy sign</b>
        <p>&emsp;&emsp;Indicates an uncertain and weak sign to buy a certain stock. Weighted as 0.75x.</p>
        <b>&emsp;&emsp;4.16 Buy</b>
        <p>&emsp;&emsp;Indicates a probable sign to buy a certain stock. Weighted as 1x.</p>
        <b>&emsp;&emsp;4.17 Strong buy</b>
        <p>&emsp;&emsp;Indicates a confident and strong sign to buy a certain stock. Weighted as 2x.</p>
        <p>&emsp;&emsp;Please note that not every indicator and moving average has every tarde signs.</p>

        <b>&emsp;4.2 Calculation of summary and giving suggestions</b>
        <p>&emsp;Stock AI weights every trade sign with a different value and sums the buy and sell signs separately in order to return a simple overall trade sign. For further information on the topic may consider visiting the <a href="https://github.com/squancy/stockai">GitHub</a> page of the service.</p>

        <b class="heading">5. Retrieving the necessary data</b>
        <b>&emsp;5.1 Where does the server-side data come from?</b>
        <p>&emsp;Stock AI uses a 3rd-party data provider in order to retrieve the desired data and information about the stocks. For demonstration purposes you can check  an <a href="/curl.php">internal representation</a> of all the data in JSON format.</p>
			</div>
		</div>
	</body>
</html>
