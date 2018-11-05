<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/error-bar/style.css">
  </head>
  <body>
    <div id="error-bar" onclick="hideErrorBar()"></div>
    <script>
    var errorBar = document.querySelector("#error-bar");

    function showErrorBar(message) {
      errorBar.innerHTML = message;
      errorBar.className += " show";
      setTimeout(hideErrorBar, 5000);
    }

    function hideErrorBar() {
      errorBar.className = errorBar.className.replace(" show", "");
    }
    </script>
  </body>
</html>
