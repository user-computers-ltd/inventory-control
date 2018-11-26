<html>
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/db/create-import-dialog/style.css">
  </head>
  <body>
    <input type="file" hidden id="create-import" onchange="createImportButtonHandler(event)" />
    <div id="create-import-overlay" class="overlay">
      <form onsubmit="createImportSubmitHandler(event)">
        <div id="create-import-close" onclick="closeCreateImportHandler(event)">&times;</div>
        <label>table name:</label>
        <input type="text" name="table" required />
        <div id="create-import-table-wrapper">
          <table id="create-import-table">
            <thead>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
        <div id="column-count"></div>
        <div id="data-count"></div>
        <div id="create-import-error"></div>
        <button type="submit">create & import</button>
      </form>
    </div>
    <div id="create-import-loading"></div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var createImportClose = document.querySelector("#create-import-close");
      var createImportButton = document.querySelector("#create-import");
      var createImportOverlay = document.querySelector("#create-import-overlay");
      var createImportForm = createImportOverlay.querySelector("form");
      var createImportTableHead = createImportForm.querySelector("#create-import-table thead");
      var createImportTableBody = createImportForm.querySelector("#create-import-table tbody");
      var createImportError = createImportForm.querySelector("#create-import-error");
      var createImportLoading = document.querySelector("#create-import-loading");
      var columnCount = createImportForm.querySelector("#column-count");
      var dataCount = createImportForm.querySelector("#data-count");
      var columnTypes = <?php echo json_encode($columnTypes); ?>;
      var url = "<?php echo BASE_URL; ?>admin/ajax.php";

      function createAndImportTable() {
        createImportButton.click();
      }

      function closeCreateImportHandler(event) {
        if (event.target === createImportOverlay || event.target === createImportClose) {
          createImportOverlay.className = "";
          createImportOverlay.removeEventListener("click", this);
        }
      }

      function showCreateImportDialog() {
        createImportForm.reset();
        createImportOverlay.className = "show";
        createImportOverlay.addEventListener("click", closeCreateImportHandler);
        createImportError.innerHTML = "";
      }

      function disableCreateImportColumnHandler(event) {
        var inputs = event.target.parentNode.querySelectorAll(".column");

        for (var i = 0; i < inputs.length; i++) {
          inputs[i].disabled = !event.target.checked;
        }
      }

      function createImportButtonHandler(event) {
        var files = event.target.files;

        for (var i = 0; i < files.length; i++) {
          var file = files[i];
          var reader = new FileReader();

          reader.onload = function(event) {
            var result = event.target.result;
            var lines = result.substring(result.indexOf("\"") + 1, result.lastIndexOf("\"")).split(/\"\r\n\"|\"\n\"/);

            if (lines.length > 0) {
              var firstLine = lines[0];
              var headers = firstLine.split("\",\"");

              createImportTableHead.innerHTML = "<tr>" + headers.map(function (h) {
                var html = "<th>"
                  + "<input type=\"checkbox\" onchange=\"disableCreateImportColumnHandler(event)\" />"
                  + "<input type=\"text\" name=\"field[]\" value=\"" + h + "\" disabled required class=\"column\" />"
                  + "<input type=\"text\" name=\"name[]\" value=\"" + h + "\" required disabled hidden class=\"column\" />"
                  + "<select name=\"type[]\" disabled required class=\"column\">";
                for (var i = 0; i < columnTypes.length; i++) {
                  html += "<option value=\"" + columnTypes[i] + "\">" + columnTypes[i] + "</option>";
                }
                html += "</select>"
                  + "<input type=\"text\" name=\"length[]\" placeholder=\"length\" disabled class=\"column\" />"
                  + "<input type=\"text\" name=\"extra[]\" placeholder=\"extra\" disabled class=\"column\" />"
                  + "</th>";

                return html;
              }).join("") + "</tr><tr>" + headers.map(function (h) { return "<th>" + h + "</th>"; }).join("") + "</tr>";

              createImportTableBody.innerHTML = "";

              for (var i = 1; i <= 5 && i < lines.length; i++) {
                var line = lines[i].replace(/,,/g, ",\"\",").replace(/,,/g, ",\"\",");
                var values = line.split("\",\"");

                createImportTableBody.innerHTML += "<tr>" + values.map(function (v) { return "<td>" + v + "</td>"; }).join("") + "</tr>";
              }

              if (lines.length > 4) {
                createImportTableBody.innerHTML += "<tr>" + headers.map(function (h) { return "<td>...</td>"; }).join("") + "</tr>";
              }

              columnCount.innerHTML = headers.length + " columns";
              dataCount.innerHTML = (lines.length - 1) + " rows";

              showCreateImportDialog();
            }
          };

          reader.readAsText(file);
        }
      }

      function createImportSubmitHandler(event) {
        event.preventDefault();

        var data = new FormData(createImportForm);

        data.append("action", "create-import-table");
        data.append("database", database);

        for (var i = 0; i < createImportButton.files.length; i++) {
          data.append("import", createImportButton.files[i]);
        }

        toggleClass(createImportLoading, "show", true);

        ajax({
          url: url,
          method: "post",
          contentType: false,
          data: data,
          resolve: function () { window.location.reload(); },
          reject: function (message) {
            toggleClass(createImportLoading, "show", false);
            createImportError.innerHTML = message;
          }
        });

        return false;
      }
    </script>
  </body>
</html>
