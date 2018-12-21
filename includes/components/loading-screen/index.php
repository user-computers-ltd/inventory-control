<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/loading-screen/style.css">
  </head>
  <body>
    <div id="loading-screen-overlay">
      <div class="loader"></div>
      <div class="message"></div>
    </div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var loadingScreenOverlay = document.querySelector("#loading-screen-overlay");
      var loadingMessage = loadingScreenOverlay.querySelector(".message");

      function toggleLoadingScreen(toggle) {
        toggleClass(loadingScreenOverlay, "show", toggle);

        if (!toggle) {
          loadingMessage.innerHTML = "";
        }
      }

      function setLoadingMessage(message) {
        loadingMessage.innerHTML = message;
      }
    </script>
  </body>
</html>
