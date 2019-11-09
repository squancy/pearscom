// Shorten document.getElementById()
function _(el) {
    return document.getElementById(el);
}

// Preset data
callback();
setTimeout(callback, 3000);

_("hStocks").innerHTML = "<div class='hStocks nonStockCont'><div class='stockCont infoHolder' style='border-radius: 5px;'><img src='/images/rolling.gif'></div></div>";

function toggleTrade(id){
    if(_(id).style.display == 'flex'){
        _(id).style.display = 'none';
    }else{
        _(id).style.display = 'flex';
    }
}

// Create a callback function that will constantly pull out fresh data from the outer resource
function callback() {
    _("hStocks").innerHTML = "";

    // Preset ajax request for client-server communication
    let xml = new XMLHttpRequest();
    xml.open('POST', 'main.php', false);
    xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xml.onreadystatechange = function() {
        if (xml.readyState == 4 && xml.status == 200) {
            let resp = xml.responseText;

            // Transform back the string into an array by splitting the string at every |||
            let tmp = resp.split("|||");
            for (let i = 0; i < tmp.length; i++) {
                // Parse the current element of the array as a JSON file => accessing elements
                tmp[i] = tmp[i].replace(/\s+/g, '');
                let json = JSON.parse(tmp[i]);

                // Save the required data for further usage
                let min = json.min;
                let max = json.max;
                let change = json.chg;
                let open = json.open;
                let close = json.last;
                let kotesek = json.kotesdb;
                let ticker = json.ticker;
                let tradesData = json.kotesek;
                let forgalom = json.forgalom;
                
                if(typeof tradesData === 'object'){
                    tradesData = Object.keys(tradesData).map(i => tradesData[i]);
                }
                
                let dataArr = Object.keys(json.imgdata.data).map(i => json.imgdata.data[i]);

                if(json.imgdata.data.length < 1){
                    _('hStocks').innerHTML = '<p style="color: #fdee38; text-align: center;">Warning: The stock data provider failed to hand the necessary data required for the analyzation</p>';
                    return;
                }
                
                
                // Call function for analyzing the current stock
                let indicators = analyzeStock(dataArr);

                // Check if the current stock pays a dividend or not
                let dividend = "";
                if (ticker == "MTELEKOM" || ticker == "MOL") {
                    dividend = "(dividend paying stock)";
                }

                // Save the required data got back from analyzeStock()
                let rsi = indicators[0];
                let momentum = indicators[1];
                let stochastic = indicators[2];
                let perK = stochastic[2].toFixed(2);
                let perD = stochastic[3].toFixed(2);
                let ema3 = indicators[3];
                let ema9 = indicators[4];
                let ema14 = indicators[5];

                // Save the 3 values for momentum indicator
                let mOne = momentum[0];
                let mTwo = momentum[1];
                let mThree = momentum[2]

                /*
                  Get the last element of EMA and price arrays in order to compare them
                  later in the technical analysis
                */

                let lastPrice = dataArr[dataArr.length - 1].close;
                let ema3Last = Number(ema3[ema3.length - 1]);
                let ema9Last = Number(ema9[ema9.length - 1]);
                let ema14Last = Number(ema14[ema14.length - 1]);

                // Create arrays for storing the last 3 values of moving averages
                let ema3arr = [],
                    ema9arr = [],
                    ema14arr = [];

                for (let i = 0; i < 3; i++) {
                    ema3arr.push(ema3[i]);
                    ema9arr.push(ema9[i]);
                    ema14arr.push(ema14[i]);
                }

                // Use destructuring to extract individual values from the array
                let ema3One, ema3Two, ema3Three, ema9One, ema9Two, ema14One, ema14Two, ema14Three;
                [ema3One, ema3Two, ema3Three] = ema3arr;
                [ema9One, ema9Two, ema9Three] = ema9arr;
                [ema14One, ema14Two, ema14Three] = ema14arr;
                /*
                  Declare the required variables for holding the output text after the
                  comparison with the help of the techical analysis
                */

                // Declare output variables and points
                let rsiText1, rsiText2, stochText, ema3Text, ema9Text, ema14Text;
                let pointsSell = 0,
                    pointsBuy = 0;

                // Create output for RSI
                /*
                  1. RSI is extremely close to the threshold value of the overbought range
                  2. RSI is high in the overbought value
                  3. RSI is in the neutral zone
                  4. RSI is extremely close to the threshold value of the oversold range
                */
                if (rsi >= 79.5 && rsi <= 80.5) {
                    rsiText1 = "<span class='oversold'>Strong sell</span> (" + rsi + ")";
                    pointsSell += 2;
                } else if (rsi >= 80) {
                    rsiText1 = "<span class='oversold'>Sell</span> (" + rsi + ")";
                    pointsSell++;
                } else if (rsi < 80 && rsi > 20) {
                    rsiText1 = "<span class='neutral'>Neutral</span> (" + rsi + ")";
                } else if (rsi <= 20.5 && rsi >= 19.5) {
                    rsiText1 = "<span class='overbought'>Strong buy</span> (" + rsi + ")";
                    pointsBuy += 2;
                } else {
                    rsiText1 = "<span class='overbought'>Buy</span> (" + rsi + ")";
                    pointsBuy++;
                }

                // Create output for RSI Low with the same logic as standard RSI
                if (rsi >= 69.5 && rsi <= 70.5) {
                    rsiText2 = "<span class='oversold'>Strong sell</span> (" + rsi + ")";
                    if (pointsSell < 1) pointsSell += 1.5;
                } else if (rsi >= 70) {
                    rsiText2 = "<span class='oversold'>Sell</span> (" + rsi + ")";
                    if (pointsSell < 1) pointsSell += 0.75;
                } else if (rsi < 70 && rsi > 30) {
                    rsiText2 = "<span class='neutral'>Neutral</span> (" + rsi + ")";
                } else if (rsi <= 30.5 && rsi >= 29.5) {
                    rsiText2 = "<span class='overbought'>Strong buy</span> (" + rsi + ")";
                    if (pointsBuy < 1) pointsBuy += 1.5;
                } else {
                    rsiText2 = "<span class='overbought'>Buy</span> (" + rsi + ")";
                    if (pointsBuy < 1) pointsBuy += 0.75;
                }

                // Create output for Momentum
                /*
                  The last 3 values are calculated and the program compares the position of these values to the base line                           (100)
                  1. First two values are below the base line, 3rd one is above / first value is below the base line, last two                      is above (engraves the base line from below)
                  2. First two values are above the base line, 3rd one is below / first value is above the base line, last two                      is below (engraves the base line from above)
                  3. Values are fluctuating somewhere between 100 and 110
                  4. Values are fluctuating somewhere between 90 and 100
                  5. Values are far above / below the base line
                */
                if ((mOne <= 100 && mTwo <= 100 && mThree > 100 && mThree < 105) || (mOne <= 100 && mTwo > 100 && mTwo <= 105 && mThree > 100 && mThree <= 105)) {
                    momText = "<span class='overbought'>Strong buy</span> (" + momentum.join(", ") + ")";
                    pointsBuy += 2;
                } else if ((mOne > 100 && mTwo > 100 && mThree < 100 && mThree >= 95) || (mOne > 100 && mTwo < 100 && mTwo >= 95 && mThree < 100 && mThree >= 95)) {
                    momText = "<span class='oversold'>Strong sell</span> (" + momentum.join(", ") + ")"
                    pointsSell += 2;
                } else if (mThree >= 100 && mThree < 110) {
                    momText = "<span class='oversold'>Sell sign</span> (" + momentum.join(", ") + ")";
                    pointsSell += 0.75;
                } else if (mThree < 100 && mThree >= 90) {
                    momText = "<span class='overbought'>Buy sign</span> (" + momentum.join(", ") + ")";
                    pointsBuy += 0.75;
                } else {
                    momText = "<span class='neutral'>Neutral</span> (" + momentum.join(", ") + ")";
                }

                // Create output for EMA3
                /*
                  With the same logic as before the program looks for engravings but this time the comparisons are between the                   values themselves, not between the values and a base line
                */
                if ((ema3One < close && ema3Two < close && ema3Three >= close) || (ema3One < close && ema3Two >= close && ema3Three >= close)) {
                    ema3Text = "<span class='overbought'>Strong Buy </span> (" + ema3Last + ")";
                    pointsBuy += 2;
                } else if ((ema3One > close && ema3Two <= close && ema3Three <= close) || (ema3One > close && ema3Two > close && ema3Three <= close)) {
                    ema3Text = "<span class='oversold'>Strong Sell</span> (" + ema3Last + ")";
                    pointsSell += 2;
                } else {
                    ema3Text = "<span class='neutral'>Neutral</span> (" + ema3Last + ")";
                }

                // Create output for EMA9
                if ((ema9One < close && ema9Two < close && ema9Three >= close) || (ema9One < close && ema9Two >= close && ema9Three >= close)) {
                    ema9Text = "<span class='overbought'>Strong Buy</span> (" + ema9Last + ")";
                    pointsBuy += 2;
                } else if ((ema9One > close && ema9Two <= close && ema9Three <= close) || (ema9One > close && ema9Two > close && ema9Three <= close)) {
                    ema9Text = "<span class='oversold'>Strong sell</span> (" + ema9Last + ")";
                    pointsSell += 2;
                } else {
                    ema9Text = "<span class='neutral'>Neutral</span> (" + ema9Last + ")";
                }

                // Create output for EMA14
                if ((ema14One < close && ema14Two < close && ema14Three >= close) || (ema14One < close && ema14Two >= close && ema14Three >= close)) {
                    ema14Text = "<span class='overbought'>Strong buy</span> (" + ema14Last + ")";
                    pointsBuy += 2;
                } else if ((ema14One > close && ema14Two <= close && ema14Three <= close) || (ema14One > close && ema14Two > close && ema14Three <= close)) {
                    ema14Text = "<span class='oversold'>Strong sell</span> (" + ema14Last + ")";
                    pointsSell += 2;
                } else {
                    ema14Text = "<span class='neutral'>Neutral</span> (" + ema14Last + ")";
                }

                // Create output for Stochastic
                /*
                  Checking for engravings between %K and %D but since the program only analyses when %K is below %D or vice versa it is not certain that their engraving point was there as well (the user may need to manually check the two values)
                  For this reason Stochastic is weighted less then other indicators or moving averages
                */
                if (perK >= 80 && perD >= 80 && perK >= perD) {
                    stochText = "<span class='oversold'>Sell</span> (" + perK + ", " + perD + ")";
                    pointsSell += 1;
                } else if (perK >= 80 && perD >= 80 && perK < perD) {
                    stochText = "<span class='oversold'>Sell sign</span> (" + perK + ", " + perD + ")";
                    pointsSell += 0.75;
                } else if (perK < 80 && perD < 80 && perK > 20 && perD > 20) {
                    stochText = "<span class='neutral'>Neutral</span> (" + perK + ", " + perD + ")";
                } else if (perK <= 20 && perD <= 20 && perK <= perD) {
                    stochText = "<span class='overbought'>Buy</span> (" + perK + ", " + perD + ")";
                    pointsBuy += 1;
                } else if (perK <= 20 && perD <= 20 && perK > perD) {
                    stochText = "<span class='overbought'>Buy sign</span> (" + perK + ", " + perD + ")";
                    pointsBuy += 0.75;
                } else {
                    stochText = "<span class='neutral'>Neutral</span> (" + perK + ", " + perD + ")";
                }

                /* 
                  Compares the closing price of today with the closing price of yesterday and decides whether the stock                         increased or decreased
                */
                let bigPrice, summary, closeBefore = dataArr[dataArr.length - 2];
                if (closeBefore.close > close) {
                    let percent = (close / (closeBefore.close / 100) - 100).toFixed(2);
                    bigPrice = "<span class='oversold'>" + close + " (" + percent + "%)</span>";
                } else if (closeBefore.close < close) {
                    let percent = (close / (closeBefore.close / 100) - 100).toFixed(2);
                    bigPrice = "<span class='overbought'>" + close + " (+" + percent + "%)</span>"
                } else {
                    bigPrice = "<span class='neutral'>" + close + "</span>"
                }

                // Logic for displaying summary
                /*
                  Compares buy and sell points and based on their difference it gives the appropriate result 
                */
                let isPos = pointsBuy - pointsSell;
                let isNeg = pointsSell - pointsBuy;

                if (isPos > isNeg && (isPos - isNeg >= 11)) {
                    summary = "<span class='overbought'>Strong buy</span>";
                } else if (isPos - isNeg >= 9) {
                    summary = "<span class='overbought'>Buy</span>";
                } else if (isPos - isNeg >= 7) {
                    summary = "<span class='overbought'>Buy sign</span>";
                } else if (isNeg - isPos >= 11) {
                    summary = "<span class='oversold'>Strong sell</span>";
                } else if (isNeg - isPos >= 9) {
                    summary = "<span class='oversold'>Sell</span>";
                } else if (isNeg - isPos >= 7) {
                    summary = "<span class='oversold'>Sell sign</span>";
                } else {
                    summary = "<span class='neutral'>Neutral</span>";
                }
                
                // Create output for trades
                let tradesOutput = '';
                let avgVolume = 0;
                for(let tradeRow of tradesData){
                    let direction = tradeRow[1];
                    let tradePrice = tradeRow[3];
                    let tradeVolume = tradeRow[4];
                    avgVolume += Number(tradeVolume);
                    let styleClass = '';
                    if(direction == '+') styleClass = 'overbought';
                    else if(direction == '-') styleClass = 'oversold';
                    else styleClass = 'neutral';
                    tradesOutput += `<p>
                        Price: <span class='${styleClass}'>${tradePrice}</span> 
                        &#9679; Volume: ${tradeVolume}
                        &#9679; Value: ${prettyPrint(Number(tradeVolume) * Number(tradePrice))}</p>`;
                }
                avgVolume /= tradesData.length;
            

                // Output the result in the HTML page
                _("hStocks").innerHTML += `
                    <div class="stockCont">
                      <div class="title">${ticker} ${dividend} ${bigPrice}</div>
                      <div class="flexCont">
                        <div class="min">Min price: ${min}</div>
                        <div class="max">Max price: ${max}</div>
                        <div class="change">Change: ${change}</div>
                        <div class="open">Open price: ${open}</div>
                        <div class="close">Close/current price: ${close}</div>
                        <div class="trades">Trades: ${kotesek}</div>
                        <div class="volume">Volume: ${prettyPrint(forgalom)}</div>
                        <div class="volume">Average volume today: ${prettyPrint(Math.floor(avgVolume))}</div>
                      </div>
                      <div class="flexCont">
                        <div class="rsi">RSI: ${rsiText1}</div>
                        <div class="rsiLow">RSI low: ${rsiText2}</div>
                        <div class="rsiLow">Momentum: ${momText}</div>
                        <div class="rsiLow">Stochastic: ${stochText}</div>
                        <div class="rsiLow">EMA3: ${ema3Text}</div>
                        <div class="rsiLow">EMA9: ${ema9Text}</div>
                        <div class="rsiLow">EMA14: ${ema14Text}</div>
                      </div>
                      <div class="summary">Summary: ${summary}</div>
                      <br>
                      <div class="checkTrades" id="checkTrades" onclick="toggleTrade('allTrades_${ticker}')">Check Trades</div>
                      <div class="flexCont allTrades" id="allTrades_${ticker}">${tradesOutput}</div>
                    </div>
                  `;
            }
        }
    }
    xml.send("refresh=now");
}

// Analyze stocks with technical analysis
function analyzeStock(data) {
    // Returns an array holding all the information about the analysis
    return [RSI(data, 14), momentum(data, 14), stochastic(data, 6), movingAvg(data, 3, "ema"), movingAvg(data, 9, "ema"), movingAvg(data, 14, "ema")];
}

function momentum(data, n) {
    /*
      Formula: (closing price of today - closing price n days before) / (closing price n days before) * 100 + 100
    */
    let result = [];
    for (let i = 0; i < 3; i++) {
        let h = (data.length) - (n + i + 1);
        let todayClose = data[data.length - (i + 1)].close;
        let nBeforeClose = data[h].close;
        let e = ((todayClose - nBeforeClose) / (nBeforeClose) * 100 + 100).toFixed(2);
        result.push(e);
    }
    
    return result;
}

function stochastic(data, n) {
    /*
        Formula: %K = 100 * ((Z - Ln) / (Hn - Ln))
        where Z is the last closing price, Ln is the lowest price during the n period and Hn is the highest price during the n         period
    */

    let stochData = [];
    let dataxBackup = data;
    data.slice(Math.max(data.length - n, 1));

    for (let i = 1; i <= n; i++) {
        // Dynamically count the last n elements of the array and constantly stepping i elements back from the end

        // Get the closing price for today
        let Z = data[data.length - 1].close;

        // Collect lowest prices during the n period
        let lowestPrices = Array.from(data.map(c => c.low));

        // Collect highest prices during the n period
        let highestPrices = Array.from(data.map(c => c.high));

        // Select the lowest (Ln) price from the lowest prices and the highest price (Hn) from the highest prices during the n period
        let Ln = Math.min(...lowestPrices);
        let Hn = Math.max(...highestPrices);
        
        // Use the formula to calculate Stochastic
        let perK = 100 * ((Z - Ln) / (Hn - Ln));
        stochData.push(perK);

        data.pop();
        data.unshift(dataxBackup[n - i]);
    }

    // Get %D with the 3 day moving average
    let perD = movingAvg(stochData, 3, "sma");
    stochData.push(...perD);

    // Return an array of 4 elements where the first three elements are for the %K and the last is for the %D
    return stochData;
}

function RSI(data, n) {
    /*
        Formula: 100 - [100 / (1 + U/D)]
        where U indicates the prices occurred during increasing
        and D indicates those that occurred during descreasing
    */

    let increasedArr = 0,
        descreasedArr = 0,
        incCount = 0,
        decCount = 0;
    for (let i = 0; i < n; i++) {
        // Check if the data[i + 1] element is undefied or not
        if (data.length > i + 1) {
            // If not check/collect the prices occurred during decreasing over the n period
            if (data[i].close > data[i + 1].close) {
                descreasedArr += data[i + 1].close;
                decCount++;
            } else if (data[i].close < data[i + 1].close) {
                // Otherwise, push it to the increased ones
                increasedArr += data[i + 1].close;
                incCount++;
            }
        }
    }

    // Count averages from prices
    let avgInc = increasedArr / incCount;
    let avgDec = descreasedArr / decCount;
    
    // Return the value of RSI with the precision of 4 decimals
    return (100 - (100 / (1 + avgInc / avgDec))).toFixed(2);
}

function movingAvg(optional, k, type) {
    let i, avgArr = [],
        sum = 0;
    /*
        Formula: Sum(k) / Count(k) where k indicates the elements passed in
    */
    
    if (type == "sma") {
        // Loop from zero to array.lenght - moving average period
        // For every element we count the SMA from the surronding prices
        for (i = 0; i <= k; i++) {
            for (let n = i; n < i + k; n++) {
                if(!optional[n].hasOwnProperty("close")){
                    return "fuck";
                }
                sum += optional[n].close;
            }
            avgArr.push(sum / k);
            sum = 0;
        }
    } else if (type == "ema") {
        /*
            Formula: (last price * x%) + (last EMA * (100 - x%))
            where x% = 2 / (1 + N) where N = period
        */
        let nPeriod = 2 / (1 + k);
        let lastPrice;

        // Choose time interval for the 1st element of SMA
        let tInt;
        if (nPeriod >= 0.4) tInt = 3;
        else if (nPeriod >= 0.2) tInt = 5;
        else tInt = 7;

        for (let i = 0; i < k; i++) {
            // Decide whether the last EMA exists or not
            // If not, default to the first closing price over the n period, otherwise lastPrice is already set to lastEMA
            lastPrice = lastPrice || Number(movingAvg(optional, tInt, "sma")[0]);

            // Count EMA with the formula
            lastEMA = (optional[i].close * nPeriod) + (lastPrice * (1 - nPeriod));
            lastPrice = lastEMA;
            avgArr.push(lastEMA.toFixed(2));
        }
    }
    return avgArr;
}

// Simple function for printing large numbers in a prettier form (58126391 => 58,126,391)
function prettyPrint(num) {
    let result = [];
    let formatNum = String(num).split("").reverse();
    for (let i = 0; i < formatNum.length; i++) {
        if (i != 0 && i != formatNum.length - 1 && (i + 1) % 3 == 0) {
            result[i] = "," + formatNum[i];
        } else {
            result[i] = formatNum[i];
        }
    }
    return result.reverse().join("");
}
