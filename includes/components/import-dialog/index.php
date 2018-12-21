<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/import-dialog/style.css">
  </head>
  <body>
    <input type="file" hidden id="import" onchange="importButtonHandler(event)" />
    <div id="import-overlay" class="overlay">
      <form onsubmit="importSubmitHandler(event)">
        <div id="import-close" onclick="closeImportHandler(event)">&times;</div>
        <h4 id="import-title"></h4>
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
      var importTableTitle = importForm.querySelector("#import-title");
      var importTableHead = importForm.querySelector("#import-table thead");
      var importTableBody = importForm.querySelector("#import-table tbody");
      var columnCount = importForm.querySelector("#column-count");
      var dataCount = importForm.querySelector("#data-count");
      var tableColumns = [];
      var importCallback = function () {};
      var importFile = null;

      function openImportDialog(settings, callback = function () {}) {
        tableColumns = settings.columns;
        importFile = settings.file;
        importTableTitle.innerHTML = settings.table;
        importCallback = callback;

        if (importFile) {
          handleImportFile(importFile);
        } else {
          importButton.click();
        }
      }

      function closeImportHandler(event) {
        if (event.target === importOverlay || event.target === importClose) {
          importOverlay.className = "";
          importOverlay.removeEventListener("click", this);
          importCallback();
        }
      }

      function showImportDialog() {
        importForm.reset();
        importOverlay.className = "show";
        importOverlay.addEventListener("click", closeImportHandler);

        updateColumnSelection();
      }

      function handleImportFile(file) {
        var reader = new FileReader();

        reader.onload = function (event) {
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

      function disableImportColumnHandler(event) {
        var inputs = event.target.parentNode.querySelectorAll(".column");

        for (var i = 0; i < inputs.length; i++) {
          inputs[i].disabled = !event.target.checked;
        }
      }

      function importButtonHandler(event) {
        var files = event.target.files;

        if (files[0]) {
          importFile = files[0];
          handleImportFile(importFile);
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

        var data = [];
        var fieldInputs = importForm.elements["field[]"];
        var nameInputs = importForm.elements["name[]"];

        for (var i = 0; i < fieldInputs.length; i++) {
          if (!fieldInputs[i].disabled) {
            data.push({ key: "field[]", value: fieldInputs[i].value });
            data.push({ key: "name[]", value: nameInputs[i].value });
          }
        }

        importCallback(data, importFile);

        return false;
      }
    </script>
  </body>
</html>
