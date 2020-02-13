function startLazy() {
  let lazyImages = [].slice.call(document.querySelectorAll(
    ".lazy-bg"));
  let active = false;

  var lazyLoad = function(isFrom = false) {
    if (active === false) {
      active = true;

      setTimeout(function() {
        lazyImages.forEach(function(lazyImage) {
          if (((lazyImage.getBoundingClientRect().top <=
                window.innerHeight && lazyImage
                .getBoundingClientRect().bottom >= 0) &&
              getComputedStyle(lazyImage).display !== "none"
              ) || isFrom) {
            if ((!lazyImage.classList.contains('noRound') &&
                !lazyImage.classList.contains('recbgs')) ||
              isFrom) {
              let url = lazyImage.getAttribute("data-src");
              lazyImage.style.backgroundImage = "url(\"" +
                url + "\")";
              lazyImage.classList.remove("lazy-bg");

              if (lazyImage.classList.contains('noRound')) {
                lazyImage.style.borderRadius = 0;
              }

              lazyImages = lazyImages.filter(function(
              image) {
                return image !== lazyImage;
              });

              if (lazyImages.length === 0) {
                document.removeEventListener("scroll",
                  lazyLoad);
                window.removeEventListener("resize",
                  lazyLoad);
                window.removeEventListener(
                  "orientationchange", lazyLoad);
              }
            }
          }
        });

        active = false;
      }, 200);
    }
  };

  document.addEventListener("scroll", lazyLoad);
  window.addEventListener("resize", lazyLoad);
  window.addEventListener("orientationchange", lazyLoad);
  if (document.getElementsByClassName('innerView')[0] != null) {
    document.getElementsByClassName('innerView')[0].addEventListener(
      'scroll', lazyLoad);
  }

  if (document.getElementById('user_template_img') != null) {
    document.getElementById('user_template_img').addEventListener(
      'click',
      function(e) {
        lazyLoad(true);
      });
  }
  let isPWA = window.matchMedia('(display-mode: standalone)').matches;
  if (document.body.scrollTop === 0 || isPWA) {
    if ((window.location.pathname == '/' && window.innerWidth >
      400) || (isPWA && window.location.pathname == '/index.php'))
      lazyLoad(true);
    else lazyLoad();
  }
}
document.addEventListener("DOMContentLoaded", startLazy);
