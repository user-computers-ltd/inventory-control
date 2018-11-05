<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/prompt-dialog/style.css">
  </head>
  <body>
    <div id="prompt-overlay" class="overlay">
      <form onsubmit="promptSubmitHandler(event)">
        <div id="prompt-close" onclick="closePromptHandler(event)">&times;</div>
        <div id="prompt-message"></div>
        <input type="text" name="prompt" required />
        <div id="prompt-button-panel">
          <button type="button" onclick="closePromptHandler(event)">Cancel</button>
          <button type="submit">OK</button>
        </div>
      </form>
    </div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var promptClose = document.querySelector("#prompt-close");
      var promptOverlay = document.querySelector("#prompt-overlay");
      var promptForm = promptOverlay.querySelector("form");
      var promptMessage = promptOverlay.querySelector("#prompt-message");
      var promptCancel = promptForm.querySelector("button[type=\"button\"]");
      var promptInput = promptForm.querySelector("input[name=\"prompt\"]");
      var promptCallback = function () {};

      function closePromptHandler(event) {
        if (event.target === promptOverlay || event.target === promptClose || event.target === promptCancel) {
          promptOverlay.className = "";
          promptOverlay.removeEventListener("click", this);
        }
      }

      function showPromptDialog(message, callback) {
        promptForm.reset();
        promptOverlay.className = "show";
        promptOverlay.addEventListener("click", closePromptHandler);
        promptMessage.innerHTML = message;
        promptCallback = callback;
      }

      function promptSubmitHandler(event) {
        event.preventDefault();

        promptCallback(promptInput.value);

        return false;
      }
    </script>
  </body>
</html>
