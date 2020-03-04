/*
  Defines functions for user actions on the main article page.
  TODO: more clever logic & merge the 2 funcs together
*/

// Like article
function toggleHeart(type,p,u,elem){
  var ajax = ajaxObj("POST","/php_parsers/heart_system.php");
  ajax.onreadystatechange = function(){
    if(ajaxReturn(ajax) == true){
      if(ajax.responseText == "heart_success"){
        _(elem).innerHTML = `
          <a href="#" onclick="return false;"
            onmousedown="toggleHeart('unheart', '${ID}', '${UNAME}', 'heartBtn')">
            <img src="/images/heart.png" width="18" height="18" title="Dislike"
              class="icon_hover_art">
          </a>`;
        let cnt = _("cntHeart").innerText;
        cnt = Number(cnt) + 1;
        _("cntHeart").innerText = cnt;
      }else if(ajax.responseText == "unheart_success"){
        _(elem).innerHTML = `
          <a href="#" onclick="return false;"
            onmousedown="toggleHeart('heart', '${ID}', '${UNAME}', 'heartBtn')">
            <img src="/images/heart_b.png" width="18" height="18" title="Like"
              class="icon_hover_art">
          </a>`;
        let cnt = _("cntHeart").innerText;
        cnt = Number(cnt) - 1;
        _("cntHeart").innerText = cnt;
      }else{
        prepareDialog();
        showDialog();
        _(elem).innerHTML = 'Try again later';
      }
    }
  }
  ajax.send("type="+type+"&p="+p+"&u="+u);
}

function toggleFav(type,p,u,elem){
  var ajax = ajaxObj("POST","/php_parsers/fav_system.php");
  ajax.onreadystatechange = function(){
    if(ajaxReturn(ajax) == true){
      if(ajax.responseText == "fav_success"){
        _(elem).innerHTML = `
          <a href="#" onclick="return false;"
            onmousedown="toggleFav('unfav', '${ID}', '${UNAME}', 'favBtn')">
            <img src="/images/star.png" width="20" height="20" title="Favourite"
              class="icon_hover_art">
          </a>`;
      }else if(ajax.responseText == "unfav_success"){
        _(elem).innerHTML = `
          <a href="#" onclick="return false;"
            onmousedown="toggleFav('fav', '${ID}', '${UNAME}', 'favBtn')">
            <img src="/images/star_b.png" width="20" height="20" title="Unfavourite"
              class="icon_hover_art">
          </a>`;
      }else{
        prepareDialog();
        showDialog();
        _(elem).innerHTML = 'Try again later';
      }
    }
  }
  ajax.send("type="+type+"&p="+p+"&u="+u);
}

function deleteArt(p,u){
  var conf = confirm("Are you sure you want to delete this article?");
  if(conf != true){
    return false;
  }

  var x = _("deleteBtn_art");
  var y = _("big_view_article");
  x.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  var ajax = ajaxObj("POST","/php_parsers/art_del.php");
  ajax.onreadystatechange = function(){
    if(ajaxReturn(ajax) == true){
      if(ajax.responseText == "delete_success"){
        y.innerHTML = `
          <p class="success_green" style="text-align: center;">
            You have successfully deleted this article
          </p>`;
      }else{
        prepareDialog();
        showDialog();
        y.innerHTML = 'Try again later';
      }
    }
  }
  ajax.send("p="+p+"&u="+u);
}

function editArt(){
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
  var x = _("edit_btn_art");
	var y = _("big_view_article");
  y.innerHTML = `
    <p style="text-align: center; margin-top: 0;">
      You are now in editor mode
    </p>
    <hr class="dim">
    <textarea name="title" id="title" type="text" maxlength="100" style="width: 98%;"
      placeholder="Article Title"></textarea>
    <div class="toolbar">
      <a onclick="execCmd('bold')">
        <i class="fa fa-bold"></i>
      </a>
      <a onclick="execCmd('italic')">
        <i class="fa fa-italic"></i>
      </a>
      <a onclick="execCmd('underline')">
        <i class="fa fa-underline"></i>
      </a>
      <a onclick="execCmd('strikeThrough')">
        <i class="fa fa-strikethrough"></i>
      </a>
      <a onclick="execCmd('justifyLeft')">
      <i class="fa fa-align-left"></i>
      </a>
      <a onclick="execCmd('justifyCenter')">
        <i class="fa fa-align-center"></i>
      </a>
      <a onclick="execCmd('justifyRight')">
        <i class="fa fa-align-right"></i>
      </a>
      <a onclick="execCmd('justifyFull')">
        <i class="fa fa-align-justify"></i>
      </a>
      <a onclick="execCmd('cut')">
        <i class="fa fa-cut"></i>
      </a>
      <a onclick="execCmd('copy')">
        <i class="fa fa-copy"></i>
      </a>
      <a onclick="execCmd('indent')">
        <i class="fa fa-indent"></i>
      </a>
      <a onclick="execCmd('outdent')">
        <i class="fas fa-outdent"></i>
      </a>
      <a onclick="execCmd('subscript')">
        <i class="fa fa-subscript"></i>
      </a>
      <a onclick="execCmd('superscript')">
        <i class="fa fa-superscript"></i>
      </a>
      <a onclick="execCmd('undo')">
        <i class="fa fa-undo"></i>
      </a>
      <a onclick="execCmd('redo')">
        <i class="fa fa-repeat"></i>
      </a>
      <a onclick="execCmd('insertUnorderedList')">
        <i class="fa fa-list-ul"></i>
      </a>
      <a onclick="execCmd('insertOrderedList')">
        <i class="fa fa-list-ol"></i>
      </a>
      <a onclick="execCmd('insertParagraph')">
        <i class="fa fa-paragraph"></i>
      </a>

      &nbsp;

      <select class="ssel sselArt" style="width: 85px; margin-top: 5px; margin-right: 5px;
        background-color: #fff;" onchange="execCmdWithArg('formatBlock', this.value)"
        class="font_all">
        <option value="H1">H1</option>
        <option value="H2">H2</option>
        <option value="H3">H3</option>
        <option value="H4">H4</option>
        <option value="H5">H5</option>
        <option value="H6">H6</option>
      </select>
      <a onclick="execCmd('insertHorizontalRule')">HR</a>
      <a onclick="execCmd('createLink', prompt('Enter URL', 'https'))">
        <i class="fa fa-link"></i>
      </a>
      <a onclick="execCmd('unlink')">
        <i class="fa fa-unlink"></i>
      </a>
      <a onclick="toggleSource()">
        <i class="fa fa-code"></i>
      </a>
      <a onclick="toggleEdit()">
        <i class="fas fa-edit"></i>
      </a>

      &nbsp;

      <select onchange="execCmdWithArg('fontName', this.value)" class="ssel sselArt"
        style="width: 85px; margin-top: 5px; margin-right: 5px; background-color: #fff;"
        id="font_name">
        <option value="Arial">Arial</option>
        <option value="Comic Sans MS">Comic Sans MS</option>
        <option value="Courier">Courier</option>
        <option value="Georgia">Georgia</option>
        <option value="Helvetica">Helvetica</option>
        <option value="Arial Black">Arial Black</option>
        <option value="Times New Roman">Times New Roman</option>
        <option value="Courier New">Courier New</option>
      </select>

      &nbsp;

      <select class="ssel sselArt" style="width: 85px; margin-top: 5px; margin-right: 5px;
        background-color: #fff;" onchange="execCmdWithArg('formatSize', this.value)"
        class="font_all">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
      </select>
      <br>
      <span>
        Fore Color:
        <input type="color" style="vertical-align: middle; margin-top: -4px;"
          onchange="execCmdWithArg('foreColor', this.value)" id="fcolor"/>
      </span>
      <span>
        Background Color:
          <input type="color" style="vertical-align: middle; margin-top: -4px;" 
            onchange="execCmdWithArg('hiliteColor', this.value)" id="bcolor"/>
      </span>
      <a onclick="execCmd('selectAll')">
        <i class="fa fa-reply-all"></i>
      </a>
    </div>

    <form id="editArtForm">
      <textarea style="display:none;" name="myTextArea" id="myTextArea" cols="100" rows="14">
      </textarea>
      <iframe name="richTextField" id="richTextField" allowtransparency="true"
        style="background: #fff;"></iframe>
    </form>

    <br />

    <p style="font-size: 14px;">
      Further help about editing &amp; writing a well-received, clear and formal article:
      <button class="main_btn_fill fixRed" onclick="openAHelp()">See help</button>
    </p>

    <hr class="dim">

    <p style="font-size: 14px; margin-top: 0px;">
      Attach images to your article in order to make visually better
      (number of attachable images is ${IMG_COUNT})&nbsp;
      <button class="main_btn_fill fixRed" onclick="openIHelp()">Get informed</button>
    </p>
  `;
  
  let empty = [];
  for(let i = 0; i < IMG_ARR.length; i++) {
    if (IMG_ARR[i] == "") {
      empty.push(i + 1);
    }
  }

  for(let i = 0; i < empty.length; i++){
    y.innerHTML += `
      <div id='au${empty[i]}'>
        <img src="/images/addimg.png"
          onclick="triggerUpload(event, 'art_upload${empty[i]}')"
          class="triggerBtnreply mob_square" />
      </div>
      <span id='aimage${empty[i]}'></span>
      <input type="file" name="file_array" id='art_upload${empty[i]}'
        onchange="doUploadGen('art_upload${empty[i]}', 'au${empty[i]}', '${empty[i]}')"
        accept="image/*" style="display: none;" />
    `;
  }

  y.innerHTML += `
    <div class="clear"></div>
    <hr class="dim">
    <button class="main_btn_fill fixRed" style="display: block; margin: 0 auto;"
      onclick="saveEditArt('${ID}', '${UNAME}')">
      Save article
    </button>
    <span id="astatus"></span>
  `;

  richTextField.document.designMode = "On";
  window.frames['richTextField'].document.body.innerHTML = CONTENT;
  var doc = frames["richTextField"].document;
  $("#richTextField").contents().find('html').html(CONTENT);
  
  // Set innerHTML of the body tag
  _("title").innerHTML = TITLE;
}

function execCmd(command){
  richTextField.document.execCommand(command, false, null);
}

function execCmdWithArg(command, arg){
  richTextField.document.execCommand(command, false, arg);
}

function toggleSource(){
  if(showingSourceCode){
    // Show source code
    richTextField.document.getElementsByTagName('body')[0].innerHTML =
      richTextField.document.getElementsByTagName('body')[0].textContent;
    showingSourceCode = false;
  }else{
    richTextField.document.getElementsByTagName('body')[0].textContent =
      richTextField.document.getElementsByTagName('body')[0].innerHTML;
    showingSourceCode = true;
  }
}

function toggleEdit(){
  if(isInEditMode){
    richTextField.document.designMode = 'Off';
    isInEditMode = false;
  }else{
    richTextField.document.designMode = 'On';
    isInEditMode = true;
  }
}

function doUploadGen(data, holder, num){
  var s = _(data).files[0];
    if (s.name == "") {
      return false;
    }

    if ("image/jpeg" != s.type && "image/gif" != s.type && "image/png" != s.type
      && "image/jpg" != s.type) {
      genDialogBox();
      return;
    }

    _(holder).innerHTML = `
      <img src="/images/whup.jpg" width="100" height="100" class="triggerBtnreply mob_square"
        style="margin-left: 0px;">
    `;

    // Attach img to form data
    var formData = new FormData;
    formData.append("stPic", s);
    var xhr = new XMLHttpRequest;

    // Register handlers for uploading & updating the progress bar
    xhr.addEventListener("load", function load(event){
      completeHandlerGen(event, holder, num)
    }, false);
    xhr.addEventListener("error", function error(event){
      errorHandlerGen(event, holder, num)
    }, false);
    xhr.addEventListener("abort", function abort(event){
      abortHandlerGen(event, holder, num)
    }, false);
    xhr.open("POST", "/php_parsers/photo_system.php");
    xhr.send(formData);
}

function completeHandlerGen(event, holder, num) {
  var t = event.target.responseText.split("|");
  if ("upload_complete" == t[0]) {
    if(num == "1") hasImageGen1 = t[1];
    else if(num == "2") hasImageGen2 = t[1];
    else if(num == "3") hasImageGen3 = t[1];
    else if(num == "4") hasImageGen4 = t[1];
    else if(num == "5") hasImageGen5 = t[1];
    _(holder).innerHTML = `
      <img src="/tempUploads/${t[1]}" class="triggerBtnreply mob_square"
        style="border-radius: 20px;"/>`;
  } else {
    _(holder).innerHTML = "Unfortunately an unknown error has occured";
  }
}

function errorHandlerGen(event, holder) {
  _(holder).innerHTML = "Upload Failed";
}
function abortHandlerGen(event, holder) {
  _(holder).innerHTML = "Upload Aborted";
}

function saveEditArt(p, u){
  var title = _("title").value;
  var theForm = _("editArtForm");
  var status = _("astatus");

  // Get the content of rich text area
  theForm.elements["myTextArea"].value = window.frames['richTextField'].document.body.innerHTML;
  var texta = theForm.elements["myTextArea"].value;
  texta = encodeURIComponent(texta);

  if(title == "" || theForm == ""){
    status.innerHTML = '<p style="color: red;">Please fill in all fields!</p>';
  }

  status.innerHTML='<img src="/images/rolling.gif" width="30" height="30">';
  var ajax = ajaxObj("POST","/php_parsers/edit_art_save.php");
  ajax.onreadystatechange = function(){
    if(ajaxReturn(ajax) == true){
      if(ajax.responseText == "save_success"){
        status.innerHTML = `
          <p style="color: #999;" class="txtc">
            You have successfully saved your article
          </p>
          <br>
          <a class="txtc" style="text-align: center; display: block;"
            href="/articles/${URL}/${LOGNAME}">Check out your new article</a>
        `;
      }else{
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            An error occured
          </p>
          <p>
            Unfortunately an unknown error has occured while saving your article.
            Please try again later and check everything is proper.
          </p>
          <br />
          ${genDialogBtn('closeDialog')}
        `;
        x.innerHTML = '<p style="color: #999;" class="txtc">Try again later</p>';
      }
    }
  }
  ajax.send("p="+p+"&u="+u+"&texta="+texta+"&title="+title+"&img1="+hasImageGen1+"&img2="+
    hasImageGen2+"&img3="+hasImageGen3+"&img4="+hasImageGen4+"&img5="+hasImageGen5);
}

function printContent(el){
  var restore = document.body.innerHTML;
  var print = _(el).innerHTML;
  document.body.innerHTML = print;
  window.print();
  document.body.innerHTML = restore;
}

function topFunction() {
    document.body.scrollTop = 0; // For Chrome, Safari and Opera 
    document.documentElement.scrollTop = 0; // For IE and Firefox
}

function openHelp(){
  var o = _("help_hide_div");
  if(o.style.display == 'none'){
    o.style.display = 'block';
  }else{
    o.style.display = 'none';
  }
}

function shareArticle(id){
  var ajax = ajaxObj("POST", "/php_parsers/status_system.php");
  ajax.onreadystatechange = function(){
    if(ajaxReturn(ajax) == true){
      if(ajax.responseText == "share_art_ok"){
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Share this article
          </p>
          <p>
            You have successfully shared this article which will be visible on your main
            profile page in the comment section.
          </p>
          ${genDialogBtn('closeDialog')}
        `;
      }else{
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            An error has occured
          </p>
          <p>
            Unfortunately the article sharing has failed. Please try again later.
          </p>
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>
        `;
      }
    }
  }
  ajax.send("action=share_art&id="+id);
}

function openIimgBig(img,count){
  prepareDialog();
  _("dialogbox").style.width = "auto";
  _("dialogbox").style.height = "auto";
  _("dialogbox").innerHTML = `
    <img src='/permUploads/${img}' style='width: 100%; height: auto;'>
    <br><br><br>
    <button id='vupload' style='position: absolute; right: 3px; bottom: 3px;'
      onclick='closeDialog()'>Close</button>`;
}

function closeDialog_a(){
  _("dialogbox_art").style.display = "none";
  _("overlay").style.display = "none";
  _("overlay").style.opacity = 0;
  document.body.style.overflow = "auto";
}
