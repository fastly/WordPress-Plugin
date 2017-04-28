  ## always cache these images & static assets
  if (req.request == "GET" && req.url.ext ~ "(?i)(css|js|gif|jpg|jpeg|bmp|png|ico|img|tga|wmf)") {
    remove req.http.cookie;
  }

  if (req.request == "GET" && req.url ~ "(xmlrpc.php|wlmanifest.xml)") {
    remove req.http.cookie;
  }

  ### do not cache these files:
  ## never cache the admin pages, or the server-status page
  if (req.request == "GET" && (req.url ~ "(wp-admin|bb-admin|server-status)")) {
    set req.http.X-Pass = "1";
  } else if (req.http.X-Requested-With == "XMLHttpRequest" && req.url !~ "recent_reviews") {

  # Do not cache ajax requests except for recent reviews
    set req.http.X-Pass = "1";
  }

  if (req.url ~ "nocache" ||
      req.url ~ "(control.php|wp-comments-post.php|wp-login.php|bb-login.php|bb-reset-password.php|register.php)") {
    set req.http.X-Pass = "1";
  }

  # Remove wordpress except on non-cacheable paths
  if (!req.http.X-Pass && req.http.Cookie:wordpress_test_cookie) {
    remove req.http.Cookie:wordpress_test_cookie;
  }

  ### do not cache authenticated sessions
  if (req.http.Cookie && req.http.Cookie ~ "(wordpress_|PHPSESSID)") {
    set req.http.X-Pass = "1";
  }

  if (!req.http.X-Pass && req.http.Cookie) {
    set req.http.Cookie = ";" req.http.Cookie;
    set req.http.Cookie = regsuball(req.http.Cookie, "; +", ";");
    set req.http.Cookie = regsuball(req.http.Cookie, ";(vendor_region|PHPSESSID|themetype2)=", "; \1=");
    set req.http.Cookie = regsuball(req.http.Cookie, ";[^ ][^;]*", "");
    set req.http.Cookie = regsuball(req.http.Cookie, "^[; ]+|[; ]+$", "");

    if (req.http.Cookie == "") {
      remove req.http.Cookie;
    }
  }
