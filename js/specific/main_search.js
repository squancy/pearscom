/*
  TODO: merge search functions with other pages
*/

var mobilecheck = mobilecheck();

function getLSearchArt() {
  var e = _('searchArt').value;
  if (e == '') {
      _('artSearchResults').style.display = 'none';
      return false;
  }

  var a = encodeURI(e);
  window.location = '/search_articles/' + encodeURI(e);
}

function getArt(e) {
  if (e == '') {
    _('artSearchResults').style.display = 'none';
    return false;
  }

  _('artSearchResults').style.display = 'block';
  if (_('artSearchResults').innerHTML == '') {
    _('artSearchResults').innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  }

  let a = encodeURI(e);
  let req = new XMLHttpRequest();
  req.open('POST', '/art_exec.php', true);
  req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  req.onreadystatechange = function () {
    if (req.readyState == 4 && req.status == 200) {
      var response = req.responseText;
      if (response != '') {
        _('artSearchResults').innerHTML = response;
      }
    }
  }
  req.send('a=' + encodeURI(e));
}

function showBas(e) {
  if (!mobilecheck) {
    _('pc_' + e).style.display = 'block';
  }
}

function getWA(){
  window.location=`/user/${LOGNAME}&wart=yes`;
}

function hideBas(e){
  _("pc_" + e).style.display="none"
}
