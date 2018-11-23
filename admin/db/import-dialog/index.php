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
        <div id="import-error"></div>
        <button type="submit">import</button>
      </form>
    </div>
    <div id="import-loading"></div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var importClose = document.querySelector("#import-close");
      var importButton = document.querySelector("#import");
      var importOverlay = document.querySelector("#import-overlay");
      var importForm = importOverlay.querySelector("form");
      var importTableInput = importForm.querySelector("input[name=\"table\"]");
      var importTableHead = importForm.querySelector("#import-table thead");
      var importTableBody = importForm.querySelector("#import-table tbody");
      var importError = importForm.querySelector("#import-error");
      var importLoading = document.querySelector("#import-loading");
      var columnCount = importForm.querySelector("#column-count");
      var dataCount = importForm.querySelector("#data-count");
      var url = "<?php echo BASE_URL; ?>admin/ajax.php";
      var tableColumns = [];

      function importTable(table, columns) {
        tableColumns = columns;
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
        importError.innerHTML = "";

        updateColumnSelection();
      }

      function disableImportColumnHandler(event) {
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
            var result = event.target.result;
            var lines = result.substring(result.indexOf("\"") + 1, result.lastIndexOf("\"")).split(/\"\r\n\"|\"\n\"/);

            if (lines.length > 0) {
              var firstLine = lines[0];
              var headers = firstLine.split("\",\"");

              importTableHead.innerHTML = "<tr>" + headers.map(function (h) {
                var containsColumn = tableColumns.indexOf(h) !== -1;
                var html = "<th>"
                  + "<input type=\"checkbox\" onchange=\"disableImportColumnHandler(event)\"" + (containsColumn && "checked") + " />"
                  + "<input type=\"text\" name=\"field[]\" value=\"" + (containsColumn ? h : "") + "\" " + (!containsColumn && "disabled") + " required hidden class=\"column\" />"
                  + "<select " + (!containsColumn && "disabled") + " required class=\"column fieldset\" onchange=\"onImportColumnSelected(event)\">"
                  + "<option value=\"\"></option>";
                for (var i = 0; i < tableColumns.length; i++) {
                  html += "<option value=\"" + tableColumns[i] + "\"" + (h === tableColumns[i] && "selected") + ">" + tableColumns[i] + "</option>";
                }
                html += "</select>"
                  + "<input type=\"text\" name=\"name[]\" " + (!containsColumn && "disabled") + " value=\"" + h + "\" required hidden class=\"column\" />"
                  + "</th>";

                return html;
              }).join("") + "</tr><tr>" + headers.map(function (h) { return "<th>" + h + "</th>"; }).join("") + "</tr>";

              importTableBody.innerHTML = "";

              for (var i = 1; i <= 5 && i < lines.length; i++) {
                var line = lines[i].replace(/,,/g, ",\"\",").replace(/,,/g, ",\"\",");
                var values = line.split("\",\"");

                importTableBody.innerHTML += "<tr>" + values.map(function (v) { return "<td>" + v + "</td>"; }).join("") + "</tr>";
              }

              if (lines.length > 4) {
                importTableBody.innerHTML += "<tr>" + headers.map(function (h) { return "<td>...</td>"; }).join("") + "</tr>";
              }

              columnCount.innerHTML = headers.length + " columns";
              dataCount.innerHTML = (lines.length - 1) + " rows";

              if (importForm.querySelectorAll(".column:disabled").length > 0) {
                showImportDialog();
              } else {
                importSubmitHandler();
              }
            }
          };

          reader.readAsText(file);
        }
      }

      function onImportColumnSelected(event) {
        event.target.parentNode.querySelector("input[name=\"field[]\"]").value = event.target.value;
        updateColumnSelection();
      }

      function updateColumnSelection() {
        var fieldset = document.querySelectorAll(".fieldset");
        var allOptions = document.querySelectorAll(".fieldset option");
        var selectedColumns = [];

        for (var i = 0; i < fieldset.length; i++) {
          selectedColumns.push(fieldset[i].value);
        }

        for (var i = 0; i < allOptions.length; i++) {
          allOptions[i].disabled = allOptions[i].value && selectedColumns.indexOf(allOptions[i].value) !== -1;
        }
      }

      function importSubmitHandler(event) {
        if (event) {
          event.preventDefault();
        }

        var data = new FormData(importForm);

        data.append("action", "import-table");
        data.append("database", database);

        for (var i = 0; i < importButton.files.length; i++) {
          data.append("import", importButton.files[i]);
        }

        toggleClass(importLoading, "show", true);

        ajax({
          url: url,
          method: "post",
          contentType: false,
          data: data,
          resolve: function () { window.location.reload(); },
          reject: function (message) {
            toggleClass(importLoading, "show", false);
            importError.innerHTML = message;
          }
        });

        return false;
      }
    </script>
  </body>
</html>
