<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/loading-screen/style.css">
  </head>
  <body>
    <div id="loading-screen-overlay">
      <div class="loader"></div>
    </div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var loadingScreenOverlay = document.querySelector("#loading-screen-overlay");

      function toggleLoadingScreen(toggle) {
        toggleClass(loadingScreenOverlay, "show", toggle);
      }
    </script>
  </body>
</html>
