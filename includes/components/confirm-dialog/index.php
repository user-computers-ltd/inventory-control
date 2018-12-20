<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/confirm-dialog/style.css">
  </head>
  <body>
    <div id="confirm-overlay">
      <form onsubmit="confirmSubmitHandler(event)">
        <div id="confirm-close" onclick="closeConfirmHandler(event)">&times;</div>
        <div id="confirm-message"></div>
        <button type="button" onclick="closeConfirmHandler(event)">Cancel</button>
        <button type="submit">OK</button>
      </form>
    </div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var confirmClose = document.querySelector("#confirm-close");
      var confirmOverlay = document.querySelector("#confirm-overlay");
      var confirmForm = confirmOverlay.querySelector("form");
      var confirmCancel = confirmForm.querySelector("button[type=\"button\"]");
      var confirmMessage = confirmOverlay.querySelector("#confirm-message");
      var confirmCallback = function () {};

      function closeConfirmHandler(event) {
        if (event.target === confirmOverlay || event.target === confirmClose || event.target === confirmCancel) {
          confirmOverlay.className = "";
          confirmOverlay.removeEventListener("click", this);
        }
      }

      function showConfirmDialog(message, callback) {
        confirmForm.reset();
        confirmOverlay.className = "show";
        confirmOverlay.addEventListener("click", closeConfirmHandler);
        confirmMessage.innerHTML = message;
        confirmCallback = callback;
      }

      function confirmSubmitHandler(event) {
        event.preventDefault();

        confirmCallback();

        return false;
      }
    </script>
  </body>
</html>
