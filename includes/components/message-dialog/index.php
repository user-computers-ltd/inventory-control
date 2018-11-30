<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/message-dialog/style.css">
  </head>
  <body>
    <div id="message-overlay" class="overlay">
      <form onsubmit="messageSubmitHandler(event)">
        <div id="message-close" onclick="closeMessageHandler(event)">&times;</div>
        <div id="message-message"></div>
        <button type="button" onclick="closeMessageHandler(event)">Cancel</button>
      </form>
    </div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var messageClose = document.querySelector("#message-close");
      var messageOverlay = document.querySelector("#message-overlay");
      var messageForm = messageOverlay.querySelector("form");
      var messageCancel = messageForm.querySelector("button[type=\"button\"]");
      var messageMessage = messageOverlay.querySelector("#message-message");
      var messageCallback = function () {};

      function closeMessageHandler(event) {
        if (event.target === messageOverlay || event.target === messageClose || event.target === messageCancel) {
          messageOverlay.className = "";
          messageOverlay.removeEventListener("click", this);
        }
      }

      function showMessageDialog(message, callback) {
        messageForm.reset();
        messageOverlay.className = "show";
        messageOverlay.addEventListener("click", closeMessageHandler);
        messageMessage.innerHTML = message;
        messageCallback = callback;
      }

      function messageSubmitHandler(event) {
        event.preventDefault();

        messageCallback();

        return false;
      }
    </script>
  </body>
</html>
