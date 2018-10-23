<html>
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/db/import-dialog/style.css">
  </head>
  <body>
    <input type="file" hidden id="import" onchange="importButtonHandler(event)" />
    <div id="import-overlay" class="overlay">
      <form onsubmit="importSubmitHandler(event)">
        <div id="import-close" onclick="closeImportHandler(event)">&times;</div>
        <input type="text" name="table" hidden required />
        <div id="import-table-wrapper">
          <table id="import-table">
            <thead>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
        <div id="column-count"></div>
        <div id="data-count"></div>
        <button type="submit">import</button>
      </form>
    </div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var importClose = document.querySelector("#import-close");
      var importButton = document.querySelector("#import");
      var importOverlay = document.querySelector("#import-overlay");
      var importForm = importOverlay.querySelector("form");
      var importTableInput = importForm.querySelector("input[name=\"table\"]");
      var importTableHead = importForm.querySelector("#import-table thead");
      var importTableBody = importForm.querySelector("#import-table tbody");
      var columnCount = importForm.querySelector("#column-count");
      var dataCount = importForm.querySelector("#data-count");
      var url = "<?php echo BASE_URL; ?>admin/ajax.php";

      function importTable(table) {
        importTableInput.setAttribute("value", table);
        importButton.click();
      }

      function closeImportHandler(event) {
        if (event.target === importOverlay || event.target === importClose) {
          importOverlay.className = "";
          importOverlay.removeEventListener("click", this);
        }
      }

      function showImportDialog() {
        importForm.reset();
        importOverlay.className = "show";
        importOverlay.addEventListener("click", closeImportHandler);
      }

      function disableColumnHandler(event) {
        var inputs = event.target.parentNode.querySelectorAll(".column");

        for (var i = 0; i < inputs.length; i++) {
          inputs[i].disabled = !event.target.checked;
        }
      }

      function importButtonHandler(event) {
        var files = event.target.files;

        for (var i = 0; i < files.length; i++) {
          var file = files[i];
          var reader = new FileReader();

          reader.onload = function(event) {
            var lines = event.target.result.split(/\r\n|\n/);

            if (lines.length > 0) {
              var headers = lines[0].split(",");
              importTableHead.innerHTML = "<tr>" + headers.map(function (h) {
                var html = "<th>"
                  + "<input type=\"checkbox\" checked onchange=\"disableColumnHandler(event)\" />"
                  + "<input type=\"text\" name=\"field[]\" value=\"" + h + "\" required class=\"column\" />"
                  + "<input type=\"text\" name=\"name[]\" value=\"" + h + "\" required hidden class=\"column\" />"
                  + "</th>";

                return html;
              }).join("") + "</tr>";

              importTableBody.innerHTML = "";

              for (var i = 0; i <= 3; i++) {
                var values = lines[i].split(",");
                importTableBody.innerHTML += "<tr>" + values.map(function (v) { return "<td>" + v + "</td>"; }).join("") + "</tr>";
              }

              importTableBody.innerHTML += "<tr>" + headers.map(function (h) { return "<td>...</td>"; }).join("") + "</tr>";
              columnCount.innerHTML = headers.length + " columns";
              dataCount.innerHTML = (lines.length - 2) + " rows";

              showImportDialog();
            }
          };

          reader.readAsText(file);
        }
      }

      function importSubmitHandler(event) {
        event.preventDefault();

        var data = new FormData(importForm);

        data.append("action", "import-table");
        data.append("database", database);

        for (var i = 0; i < importButton.files.length; i++) {
          data.append("import", importButton.files[i]);
        }

        ajax({
          url: url,
          method: "post",
          contentType: false,
          data: data,
          resolve: function () { window.location.reload(); }
        });

        return false;
      }
    </script>
  </body>
</html>
