// Create a simple map plan which will be used to display the elements visually
var simpleLevelPlan = `
......................
..#................#..
..#..............=.#..
..#.........o.o....#..
..#.@......#####...#..
..#####............#..
......#++++++++++++#..
......##############..
......................`;

// Create class level where it turns the level plan into an object
var Level = class Level {
    constructor(plan) {
        /* 
            Make sure there are no whitespaces, split every row at new lines and map the 
            resulting rows into an array 
        */
        let rows = plan.trim().split("\n").map(l => [...l]);

        // Get length and width, declare startActors which will hold the actors of the map
        this.height = rows.length;
        this.width = rows[0].length;
        this.startActors = [];

        // Map through at every row attaching y index, then at every element attaching x index
        this.rows = rows.map((row, y) => {
            return row.map((ch, x) => {
                // LevelChars is an object which decides the character an actor or not
                let type = levelChars[ch];

                // If the character is not an actor we return its type
                if (typeof type == "string") return type;

                // Otherwise we push it to the startActors array by creating a class from it
                this.startActors.push(
                    type.create(new Vec(x, y), ch));
                return "empty";
            });
        });
    }
}

// Create touches prototype which will decide player's relation to other elements
Level.prototype.touches = function(pos, size, type){
    // Get a boundary around the player made up of 1 x 1 grids which will function as a hitzone
    var xStart = Math.floor(pos.x);
    var xEnd = Math.ceil(pos.x + size.x);
    var yStart = Math.floor(pos.y);
    var yEnd = Math.ceil(pos.y + size.y);

    // We loop through every single grid to see whether it touched another of a specific type
    for(var y = yStart; y < yEnd; y++){
        for(var x = xStart; x < xEnd; x++){
            /* 
                If any of the statements are true, the player is outside of any kind of relation with other actors
            */
            let isOutside = x < 0 || x >= this.width || y < 0 || y >= this.height;

            /* 
                If the player is outside he/she touched a wall, otherwise it touched something else of a given type (seen as a parameter)
            */
            let here = isOutside ? "wall" : this.rows[y][x];
            if(here == type) return true;
        }
    }
    return false;
}

// Create State class which will define the current state of the game
var State = class State {
    constructor(level, actors, status) {
        this.level = level;
        this.actors = actors;
        this.status = status;
    }

    /* 
        Create start static function for running the game on the current level (class level) 
        with the given actors and a status of "playing"
    */
    static start(level) {
        return new State(level, level.startActors, "playing");
    }

    // Get function to find the player class in the actors array
    get player() {
        return this.actors.find(a => a.type == "player");
    }
}

/* 
    Create an update function for State prototypes to update actors and to check collision and game win or lost
*/
State.prototype.update = function(time, keys){
    // Update all actors by calling update on them, then create a newState with the updated actors
    let actors = this.actors.map(actor => actor.update(time, this, keys));
    let newState = new State(this.level, actors, this.status);

    // Check whether the game status is playing or not (in this case just return)
    if(newState.status != "playing") return newState;

    /* 
        Check that the player has touched any kind of lava with the touches function, if so, return a new State with the status of "lost"
    */
    let player = newState.player;
    if(this.level.touches(player.pos, player.size, "lava")){
        return new State(this.level, actors, "lost");
    }

    // If no collision or game lost detected check that the player has collided with any other elements
    for(let actor of actors){
        if(actor != player && overlap(actor, player)){
            newState = actor.collide(newState);
        }
    }
    return newState;
}

/* 
    Checks whether two actors collide both on x and y axis by comparing stictly their position and size to each other
*/
function overlap(actor1, actor2){
    return actor1.pos.x + actor1.size.x > actor2.pos.x &&
           actor1.pos.x < actor2.pos.x + actor2.size.x &&
           actor1.pos.y + actor1.size.y > actor2.pos.y &&
           actor1.pos.y < actor2.pos.y + actor2.size.y;
}

// Create Vec class which will be used to define coordinates, sizes etc.
var Vec = class Vec {
    constructor(x, y) {
        this.x = x;
        this.y = y;
    }

    // Will add another Vec object to the current one
    plus(other) {
        return new Vec(this.x + other.x, this.y + other.y);
    }

    // Will increase every coordinate by a given factor
    times(factor) {
        return new Vec(this.x * factor, this.y * factor);
    }
}

// Create Player class for managing the player and its events
var Player = class Player {
    constructor(pos, speed) {
        this.pos = pos;
        this.speed = speed;
    }

    // Will get a get function that will return a type
    get type() {
        return "player";
    }

    /* 
        Create static create function which will create a new player in the air (achiving the dropping) effect and a velocity of 0
    */
    static create(pos) {
        return new Player(pos.plus(new Vec(0, -0.5)),
            new Vec(0, 0));
    }
}

// Every player instance (prototype) will get a constant size
Player.prototype.size = new Vec(0.8, 1.5);

// Define constant variables for player motions
const playerXSpeed = 7;
const gravity = 30;
const jumpSpeed = 17;

/* 
    Create update function for Player prototype which will handle vertical and horizontal moving, takes 3 args: time, state and the object of keys
*/
Player.prototype.update = function(time, state, keys){
    /* 
        Set xSpeed to 0, then decide whether the left or right arrow is being pressed and add or subtract from xSpeed
    */
    let xSpeed = 0;
    if(keys.touchLeftStart) xSpeed -= playerXSpeed;
    if(keys.touchRightStart) xSpeed += playerXSpeed;

    /* 
        Set movedX to the distance is travelled: speed * time, after check that a wall is touched or not: if not, we set the position to the distance travelled else not
    */
    let pos = this.pos;
    let movedX = pos.plus(new Vec(xSpeed * time, 0));
    if(!state.level.touches(movedX, this.size, "wall")){
        pos = movedX;
    }

    // Define the vertical speed by the y speed + time * gravity, then count movedY similarly
    let ySpeed = this.speed.y + time * gravity;
    let movedY = pos.plus(new Vec(0, ySpeed * time));

    // Check that if we hit a wall or not, if not, we set the position to the vetical speed
    if(!state.level.touches(movedY, this.size, "wall")){
        pos = movedY;
    // Otherwise, if the player jumped and nothing blocked him/her we subtarct jumpSpeed
    }else if(keys.touchUpStart && ySpeed > 0){
        ySpeed -= jumpSpeed;
    // Else somthing blocked our way so we set the vertical speed to 0
    }else{
        ySpeed = 0;
    }
    return new Player(pos, new Vec(xSpeed, ySpeed));
}

// Create actor Lava class for managing different types of lavas and its events
var Lava = class Lava {
    constructor(pos, speed, reset) {
        this.pos = pos;
        this.speed = speed;
        this.reset = reset;
    }

    // Create get type function, will return a type
    get type() {
        return "lava";
    }

    /* 
        Create static create function which will return a new Lava class at a given position with a given speed (reset for dropping lava) depending on its type
    */
    static create(pos, ch) {
        if (ch == "=") {
            return new Bird(pos, new Vec(2, 0));
        } else if (ch == "|") {
            return new Lava(pos, new Vec(0, 2));
        } else if (ch == "v") {
            return new Lava(pos, new Vec(0, 3), pos);
        }
    }
}

// Declare size of Lava prototypes
Lava.prototype.size = new Vec(1, 1);

// Collide function on the Lava class prototype will return a new state with the status of "lost"
Lava.prototype.collide = function(state){
    return new State(state.level, state.actors, "lost");
}

/* 
    Add update function to the Lava class which will check that the given lava block has collided with another grid or not, if so, does the proper computation for each type of lava
*/
Lava.prototype.update = function(time, state){
    let newPos = this.pos.plus(this.speed.times(time));
    if(!state.level.touches(newPos, this.size, "wall")){
        return new Lava(newPos, this.speed, this.reset);
    }else if(this.reset){
        return new Lava(this.reset, this.speed, this.reset);
    }else{
        return new Lava(this.pos, this.speed.times(-1));
    }
}

class Bird extends Lava {
    get type(){
        return "bird";
    }
};

Bird.prototype.update = function(time, state){
    let newPos = this.pos.plus(this.speed.times(time));
    if(!state.level.touches(newPos, this.size, "wall")){
        return new Bird(newPos, this.speed, this.reset);
    }else if(this.reset){
        return new Bird(this.reset, this.speed, this.reset);
    }else{
        return new Bird(this.pos, this.speed.times(-1));
    }
}

// Create Coin class for managing collectable and wobbling coins on the map
var Coin = class Coin {
    constructor(pos, basePos, wobble) {
        this.pos = pos;
        this.basePos = basePos;
        this.wobble = wobble;
    }

    // Will return its type
    get type() {
        return "coin";
    }

    // Define a static create function which will create a wobbling coin 
    static create(pos) {
        let basePos = pos.plus(new Vec(0.2, 0.1));
        return new Coin(basePos, basePos,
            Math.random() * Math.PI * 2);
    }
}

// Define static size of every Coin prototype
Coin.prototype.size = new Vec(0.6, 0.6);

// Add collide function to Coin class as well in order to manage coin actors
Coin.prototype.collide = function(state){
    // Filter the actors array but without the coin collected just now
    let filtered = state.actors.filter(a => a != this);
    let status = state.status;

    /* 
        Check that if the game has been won or not by checking the number of coins left is 0, if so, return a new state with a game status of "won"
    */
    if(!filtered.some(a => a.type == "coin")) status = "won";
    return new State(state.level, filtered, status);
}

const wobbleSpeed = 8, wobbleDist = 0.07;

// Add an update function to Coin prototype which will make the wobbling happen
Coin.prototype.update = function(time){
    let wobble = this.wobble + time * wobbleSpeed;
    let wobblePos = Math.sin(wobble) * wobbleDist;
    return new Coin(this.basePos.plus(new Vec(0, wobblePos)), this.basePos, wobble);
}

// Create levelChars object that holds every possible types of elements (returns class or string)
var levelChars = {
    ".": "empty",
    "#": "wall",
    "+": "lava",
    "@": Player,
    "o": Coin,
    "=": Bird,
    "|": Lava,
    "v": Lava
};

/* 
    Create function elt that will create a DOM element of a given name, set attributes to it and will append every child to the parent element (dom)
*/
function elt(name, attrs, ...children) {
    let dom = document.createElement(name);
    for (let attr of Object.keys(attrs)) {
        dom.setAttribute(attr, attrs[attr]);
    }
    for (let child of children) {
        dom.appendChild(child);
    }
    return dom;
}

var today = new Date(0, 0, 0, 0, 0, 0, 0);
var t;
function startTime(stopped = false) {
    var m = today.getMinutes();
    var s = today.getSeconds();
    var mi = today.getMilliseconds();
    s += 1;
    today = new Date(0, 0, 0, 0, m, s, mi);
    if(s >= 60){
        s = 0; 
        m += 1;
        today = new Date(0, 0, 0, 0, m, s, mi);
    }
    m = checkTime(m);
    s = checkTime(s);
    x.innerHTML = m + ":" + s;
    if(!stopped) t = setTimeout(startTime, 1000);
}

let ms = 0;
var m;
function countMs(){
    ms += 1;
    m = setTimeout(countMs, 10);
}

function checkTime(i) {
    if (i < 10) i = "0" + i;
    return i;
}

function drawBtns(type){
    let touch = document.body.appendChild(elt("div", {class: "touch"}));
    touch.style.top = window.innerHeight - 80 + "px";
    touch.style.zIndex = "2";
    let ctrl = document.createElement("img");
    ctrl.className = "ctrl";
    if(type == "left"){
        touch.style.left = "30px";
        ctrl.src = "images/left.png";
    }else{
        touch.style.right = "30px";
        ctrl.src = "images/right.png";
    }
    ctrl.style.marginLeft = "12px";
    touch.appendChild(ctrl);
    document.body.appendChild(touch);
    return touch;
}

let touchLeft = drawBtns("left");
let touchRight = drawBtns("right");

// Create DOMDisplay class that will draw the given level with the help of other functions
var DOMDisplay = class DOMDisplay {
    constructor(parent, level) {
        /* 
            Create dom (div) element as an outer holder with the elt function, get child elements with drawGric function
        */
        this.dom = elt("div", {
            class: "game"
        }, drawGrid(level));
        // Create actorLayer which will hold the actors in it for further re-drawings
        this.actorLayer = null;
        parent.appendChild(this.dom);
        this.dom.appendChild(elt("div", {id: "timer"}));
        this.dom.style.maxHeight = window.innerHeight + "px";
        this.dom.style.maxWidth = window.innerWidth + "px";
    }

    clear() {
        this.dom.remove();
    }
}

function flipHorizontally(context, around) {
  context.translate(around, 0);
  context.scale(-1, 1);
  context.translate(-around, 0);
}

var CanvasDisplay = class CanvasDisplay {
  constructor(parent, level) {
    this.canvas = document.createElement("canvas");
    this.canvas.width = Math.min(window.innerWidth, level.width * scale);
    this.canvas.height = Math.min(window.innerHeight, level.height * scale);
    parent.appendChild(this.canvas);
    this.cx = this.canvas.getContext("2d");
    this.level = level;
    this.flipPlayer = false;

    this.viewport = {
      left: 0,
      top: 0,
      width: this.canvas.width / scale,
      height: this.canvas.height / scale
    };
  }

  clear() {
    this.canvas.remove();
  }
}

CanvasDisplay.prototype.syncState = function(state) {
  this.updateViewport(state);
  this.clearDisplay(state.status);
  this.drawBackground(state.level);
  this.drawActors(state.actors);
};

CanvasDisplay.prototype.updateViewport = function(state) {
  let view = this.viewport, marginY = view.width / 7, marginX = view.width / 3;
  let player = state.player;
  let center = player.pos.plus(player.size.times(0.5));
  if(window.innerWidth / window.innerHeight >= 2.51){
    marginY = view.width / 10;
    }

  if (center.x < view.left + marginX) {
    view.left = Math.max(center.x - marginX, 0);
  } else if (center.x > view.left + view.width - marginX) {
    view.left = Math.min(center.x + marginX - view.width,
                         state.level.width - view.width);
  }
  if (center.y < view.top + marginY) {
    view.top = Math.max(center.y - marginY, 0);
  } else if (center.y > view.top + view.height - marginY) {
    view.top = Math.min(center.y + marginY - view.height,
                        state.level.height - view.height);
  }
};

CanvasDisplay.prototype.clearDisplay = function(status) {
  if (status == "won") {
    this.cx.fillStyle = "rgb(68, 191, 255)";
    document.body.style.backgroundColor = "rgb(68, 191, 255)";
  } else if (status == "lost") {
    this.cx.fillStyle = "rgb(44, 136, 214)";
    document.body.style.backgroundColor = "rgb(44, 136, 214)";
  } else {
    this.cx.fillStyle = "rgb(52, 166, 251)";
    document.body.style.backgroundColor = "rgb(52, 166, 251)";
  }
  this.cx.fillRect(0, 0,
                   this.canvas.width, this.canvas.height);
};

var otherSprites = document.createElement("img");
otherSprites.src = "images/otherSpirites.png";

CanvasDisplay.prototype.drawBackground = function(level) {
  let {left, top, width, height} = this.viewport;
  let xStart = Math.floor(left);
  let xEnd = Math.ceil(left + width);
  let yStart = Math.floor(top);
  let yEnd = Math.ceil(top + height);

  for (let y = yStart; y < yEnd; y++) {
    for (let x = xStart; x < xEnd; x++) {
      let tile = level.rows[y][x];
      if (tile == "empty") continue;
      let screenX = (x - left) * scale;
      let screenY = (y - top) * scale;
      let tileX = tile == "lava" ? scale : 0;
      this.cx.drawImage(otherSprites,
                        tileX,         0, scale, scale,
                        screenX, screenY, scale, scale);
    }
  }
};

var playerSprites = document.createElement("img");
playerSprites.src = "images/spirites.png";
var playerXOverlap = 4;

CanvasDisplay.prototype.drawPlayer = function(player, x, y,
                                              width, height){
  width += playerXOverlap * 2;
  x -= playerXOverlap;
  if (player.speed.x != 0) {
    this.flipPlayer = player.speed.x < 0;
  }

  let tile = 8;
  if (player.speed.y != 0) {
    tile = 9;
  } else if (player.speed.x != 0) {
    tile = Math.floor(Date.now() / 60) % 8;
  }

  this.cx.save();
  if (this.flipPlayer) {
    flipHorizontally(this.cx, x + width / 2);
  }
  let tileX = tile * width;
  this.cx.drawImage(playerSprites, tileX, 0, width, height,
                                   x,     y, width, height);
  this.cx.restore();
};

CanvasDisplay.prototype.drawActors = function(actors) {
  for (let actor of actors) {
    let width = actor.size.x * scale;
    let height = actor.size.y * scale;
    let x = (actor.pos.x - this.viewport.left) * scale;
    let y = (actor.pos.y - this.viewport.top) * scale;
    if (actor.type == "player") {
      this.drawPlayer(actor, x, y, width, height);
    } else {
      let tileX = (actor.type == "coin" ? 2 : 1) * scale;
      if(actor.type == "bird"){
        this.cx.drawImage(otherSprites, 51.4, 0, 32, height, x - 5, y, 32, height);
      }else{
        this.cx.drawImage(otherSprites,
                        tileX, 0, width, height,
                        x,     y, width, height);
      }
    }
  }
};

var scale = 20;

// Define drawGrid function that will draw a complete level
function drawGrid(level) {
    // Call elt function that will draw the outer table
    return elt("table", {
        class: "background",
        style: `width: ${level.width * scale}px`
        // Define inner table rows
    }, ...level.rows.map(row =>
        elt("tr", {
                style: `height: ${scale}px`
            },
            // Inside rows define table data 
            ...row.map(type => elt("td", {
                class: type
            })))
    ));
}

// Define drawActors function that will create each actor with a given size and style
function drawActors(actors) {
    // Loop through each actor in the actors array
    return elt("div", {}, ...actors.map(actor => {
        // Create the div holder for the actor
        let rect = elt("div", {
            class: `actor ${actor.type}`
        });

        // Add the position and the size of the actor
        rect.style.width = `${actor.size.x * scale}px`;
        rect.style.height = `${actor.size.y * scale}px`;
        rect.style.left = `${actor.pos.x * scale}px`;
        rect.style.top = `${actor.pos.y * scale}px`;
        return rect;
    }));
}

/* 
    Add a prorotype function (syncState) to the DOMDisplay class in order to redraw every actor on the map
*/
DOMDisplay.prototype.syncState = function(state) {
    // Remove the old actorLayer (array of actors in the given level)
    if (this.actorLayer) this.actorLayer.remove();

    // Redraw actors
    this.actorLayer = drawActors(state.actors);
    this.dom.appendChild(this.actorLayer);
    this.dom.className = `game ${state.status}`;
    // Scroll the player into view at every time when it is needed
    this.scrollPlayerIntoView(state);
};

/* 
    Define scrollPlayerIntoView function to move the viewport in that way the player is always at the middle (or near the middle) of the window
*/
DOMDisplay.prototype.scrollPlayerIntoView = function(state) {
    // Define variables of the window height, width and the margin (third of the window width)
    let width = this.dom.clientWidth;
    let height = this.dom.clientHeight;
    let marginY = width / 5;
    let marginX = width / 3;

    // Define directions of top, bottom, left and right and its scrolled position
    let left = this.dom.scrollLeft,
        right = left + width;
    let top = this.dom.scrollTop,
        bottom = top + height;

    // Find the center of the player
    let player = state.player;
    let center = player.pos.plus(player.size.times(0.5))
        .times(scale);

    /* 
        If the x coordinate (left) of the player is less than what we have scrolled left plus the margin correction then we want to scroll to the right (so we subtract the margin from the current position). For left scrolling, we check that the player is on rather the right side of the viewport or not, and if he/she is, we scroll the viewport right.
    */
    if (center.x < left + marginX) {
        this.dom.scrollLeft = center.x - marginX;
    } else if (center.x > right - marginX) {
        this.dom.scrollLeft = center.x + marginX - width;
    }

    /*
        The same goes on with the top and bottom coordinates, now we just need to do the inverse
    */
    if (center.y < top + marginY) {
        this.dom.scrollTop = center.y - marginY;
    } else if (center.y > bottom - marginY) {
        this.dom.scrollTop = center.y + marginY - height;
    }
};

/* 
    trackKeys function checks that if left, right or up keys are being pressed or not, it registers event handlers to key states and returns an object with the given states (true or false) for every key
*/
function trackKeys(keys){
    let down = Object.create(null);
    function track(event){
        if(keys.includes(event.type)){
            down["touchLeftStart"] = event.type == "touchstart";
        }
        event.stopPropagation();
    }
    function track2(event){
        if(keys.includes(event.type)){
            down["touchRightStart"] = event.type == "touchstart";
        }
        event.stopPropagation();
    }
    function track3(event){
        if(keys.includes(event.type)){
            down["touchUpStart"] = event.type == "touchstart";
        }
    }
    touchLeft.addEventListener("touchstart", track);
    touchLeft.addEventListener("touchend", track);
    touchRight.addEventListener("touchstart", track2);
    touchRight.addEventListener("touchend", track2);
    window.addEventListener("touchstart", track3);
    window.addEventListener("touchend", track3);
    return down;
}

const arrowKeys = trackKeys(["touchRightStart", "touchLeftStart", "touchUpStart", "touchstart", "touchend"]);

/* 
    runAnimation function will continuously run requestAnimationFrame until the difference between two frames is more than 100 milliseconds, this time it returns false
*/
function runAnimation(frameFunc){
    let lastTime = null;
    function frame(time){
        if(lastTime != null){
            let timeStep = Math.min(time - lastTime, 100) / 1000;
            if(frameFunc(timeStep) === false) return;
        }
        lastTime = time;
        requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
}

/*
    runLevel function will get a level and a display constructor as a paramater and will display it,
    returns a new Promise and runs the prewritten runAnimation function inside of it, which will decide whether the game should play the frames or not, if not, it clears the level, resolves the promise and returns false
*/
let isPaused = false;

function makePaused(){
    let layout = document.body.appendChild(document.createElement("div"));
    layout.id = "layout";
    clearTimeout(t);
    clearTimeout(m);
    let pause = document.createElement("img");
    pause.src = "images/pause.png";
    pause.id = "layoutPaused";
    document.body.appendChild(pause);
    pause.addEventListener("load", () => {
        pause.style.marginLeft = (pause.clientWidth / 2 * -1) + "px";
        pause.style.marginTop = (pause.clientHeight / 2 * -1) + "px";
    });
}

function runLevel(level, Display, playerLives){
    let display = new Display(document.body, level);
    let state = State.start(level);
    let ending = 1;
    clearDate();
    let oldOnes = document.getElementsByClassName("lives");
    for(let life of Array.from(oldOnes)){
        life.remove();
    }
    for(let i = 0; i < playerLives + 1; i++){
        let leftPx = (i + 1) * 35;
        let x = document.body.insertBefore(document.createElement("img"), document.getElementById("right"));
        x.className = "lives";
        x.style.left = leftPx + "px";
        x.src = "images/lives.png"
    }
    let cnvs = document.getElementsByTagName("canvas");
    if(cnvs.length > 1){
        for(let i = 0; i < cnvs.length; i++){
            if(i != 0) cnvs[i].remove();
        }
    }
    return new Promise(resolve => {
        window.addEventListener("keydown", pauseGame);

        function pauseGame(event){
            if(event.key === "Escape" && isPaused == false){
                isPaused = true;
                makePaused();
            }else if(event.key === "Escape" && isPaused == true){
                document.querySelector("#layout").remove();
                document.querySelector("#layoutPaused").remove();
                isPaused = false;
                paused();
                startTime();
            }
        }

        function paused(){
            runAnimation(time => {
                state = state.update(time, arrowKeys);
                display.syncState(state);
                if(isPaused == true) return false;
                if(state.status == "playing"){
                    return true;
                }else if(ending > 0){
                    ending -= time;
                    return true;
                }else{
                    display.clear();
                    resolve(state.status);
                    return false;
                }
            });
        }
        paused();
    });
}

// Function for drawing the brown screen between levels in the case when the game is won
function drawWon(level, plans, Display, playerLives){
    // Clear timeout of t which will stop the timer
    clearTimeout(t);
    clearTimeout(m);

    // Calculate milliseconds
    let mills = calcMills(ms);

    // Create container and background
    drawCommon("won", level, mills);

    // Calculate time in secs from the measured time in the current level
    let timeInSecs = calcTime(document.getElementById("timer").innerHTML);
    let stars = 0;

    // Decide the amount of stars the player gets
    stars = chooseStar(level, timeInSecs);
    let starHolder = document.createElement("div");
    for(let i = 0; i < stars; i++){
        let x = starHolder.appendChild(document.createElement("img"));
        x.src = "images/star.png";
    }
    starHolder.id = "starHolder";
    starHolder.className = "center";
    starHolder.style.width = (100 * stars) + "px";
    wonBg.appendChild(starHolder);

    // Add retry and continue button
    let btnHolder = document.createElement("div");
    btnHolder.className = "center";
    btnHolder.style.width = "100px";
    drawCont(btnHolder);
    drawRetry(btnHolder, GAME_LEVELS, CanvasDisplay, level, playerLives);
}

function calcMills(ms){
    let mills = (ms + 300) % 100;
    let x = document.getElementById("timer").innerHTML.split(":");
    return x[1] + "." + mills;
}

function drawRetry(btnHolder, GAME_LEVELS, CanvasDisplay, level, playerLives){
    let retryBtn = document.createElement("p");
    retryBtn.innerHTML = "Retry";
    btnHolder.appendChild(retryBtn);
    retryBtn.addEventListener("click", () => {
        wonBg.remove();
        document.querySelector("canvas").remove();
        runGame(GAME_LEVELS, CanvasDisplay, level, playerLives);
        setT();
        clearDate();
    });
}

function drawCont(btnHolder){
    let contBtn = document.createElement("p");
    contBtn.innerHTML = "Continue";
    btnHolder.appendChild(contBtn);
    wonBg.appendChild(btnHolder);
    contBtn.addEventListener("click", () => {
        wonBg.remove();
        setT();
        clearDate();
    });
}

function drawLost(level){
    clearTimeout(t);
    clearTimeout(m);
    drawCommon("lost");
    let btnHolder = document.createElement("div");
    btnHolder.className = "center";
    btnHolder.style.width = "100px";
    wonBg.appendChild(btnHolder);
    drawRetry(btnHolder, GAME_LEVELS, CanvasDisplay, level);
}

function drawCommon(type, level, mills){
    // Create wonBg container which will contain other elements
    let wonBg = document.createElement("div");
    wonBg.id = "wonBg";

    // Add text and append it to wonBg
    let wonP = document.createElement("p");
    let typeText;
    if(type == "won") typeText =  "Level " + (level + 1) + " completed!";
    else typeText = "Oops... you died";
    let wonText = document.createTextNode(typeText);
    wonP.id = "wonText";
    wonP.appendChild(wonText);
    wonBg.appendChild(wonP);
    if(type == "won") wonP.innerHTML += "<br>" + mills;
    if(type == "lost"){
        // Draw the dead figure
        let dead = document.createElement("img");
        dead.src = "images/dead.png";
        dead.id = "dead";
        wonBg.appendChild(dead);
    }
    document.body.appendChild(wonBg);
}

function calcTime(element){
    let halves = element.split(":");
    let firstHalf = halves[0];
    if(firstHalf[0] == "0") firstHalf = firstHalf[1];
    let secondHalf = halves[1];
    let timeInSecs = firstHalf * 60 + secondHalf;
    return timeInSecs;
}

function chooseStar(level, timeInSecs){
    let stars = 0;
    if(level == 0 && timeInSecs <= 16){
        stars = 3;
    }else if(level == 0 && timeInSecs <= 20){
        stars = 2;
    }else if(level == 0 && timeInSecs > 20){
        stars = 1;
    }else if(level == 1 && timeInSecs <= 41){
        stars = 3;
    }else if(level == 1 && timeInSecs <= 45){
        stars = 2;
    }else if(level == 1 && timeInSecs > 45){
        stars = 1;
    }else if(level == 2 && timeInSecs <= 54){
        stars = 3;
    }else if(level == 2 && timeInSecs <= 58){
        stars = 2;
    }else if(level == 2 && timeInSecs > 58){
        stars = 1;
    }else if(level == 3 && timeInSecs <= 84){
        stars = 3;
    }else if(level == 3 && timeInSecs <= 86){
        stars = 2;
    }else if(level == 3 && timeInSecs > 86){
        stars = 1;
    }else if(level == 4 && timeInSecs <= 72){
        stars = 3;
    }else if(level == 4 && timeInSecs <= 76){
        stars = 2;
    }else if(level == 4 && timeInSecs > 76){
        stars = 1;
    }
    return stars;
}

function clearDate(){
    today = new Date(0, 0, 0, 0, 0, 0);
    return true; 
}

function setT(){
    t = setTimeout(startTime, 1000);
    return true;
}

// Use async/await function to handle promises and level changes in relation with runLevel
async function runGame(plans, Display, lvl, pl){
    let playerLives = pl || 2;
    for(let level = lvl || 0; level < plans.length;){
        let status = await runLevel(new Level(plans[level]), Display, playerLives);
        if(playerLives <= 0){
            level = 0;
            playerLives = 2;
        }else if (status == "won"){
            drawWon(level, plans, Display, playerLives);
            level++;
        }else if(status == "lost"){
            --playerLives;
        }
    }
}

runGame(GAME_LEVELS, CanvasDisplay);
var x = document.createElement("div");
var clock = document.createElement("img");
clock.src = "images/clockh.png";
clock.id = "clockh";
x.id = "timer";
document.body.insertBefore(x, document.getElementById("right"));
document.body.insertBefore(clock, x);
startTime();

window.oncontextmenu = function(event) {
     event.preventDefault();
     event.stopPropagation();
     return false;
};

countMs();