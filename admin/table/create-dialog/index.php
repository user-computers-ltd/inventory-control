<html>
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/table/create-dialog/style.css">
  </head>
  <body>
    <input type="file" hidden id="create" onchange="createButtonHandler(event)" />
    <div id="create-overlay">
      <form onsubmit="createSubmitHandler(event)">
        <div id="create-close" onclick="closeCreateHandler(event)">&times;</div>
        <label for="create-field">field:</label>
        <input type="text" name="field" id="create-field" required />
        <label for="create-type">type:</label>
        <select name="type" id="create-type" required>
          <?php foreach ($columnTypes as $type) { echo "<option value=\"$type\">$type</option>"; } ?>
        </select>
        <label for="create-length">length:</label>
        <input type="text" name="length" id="create-length" />
        <label for="create-extra">extras:</label>
        <input type="text" name="extra" id="create-extra" />
        <button type="submit">create</button>
      </form>
    </div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var createClose = document.querySelector("#create-close");
      var createButton = document.querySelector("#create");
      var createOverlay = document.querySelector("#create-overlay");
      var createForm = createOverlay.querySelector("form");
      var columnTypes = <?php echo json_encode($columnTypes); ?>;
      var url = "<?php echo BASE_URL; ?>admin/ajax.php";

      function closeCreateHandler(event) {
        if (event.target === createOverlay || event.target === createClose) {
          createOverlay.className = "";
          createOverlay.removeEventListener("click", this);
        }
      }

      function showCreateDialog() {
        createForm.reset();
        createOverlay.className = "show";
        createOverlay.addEventListener("click", closeCreateHandler);
      }

      function disableColumnHandler(event) {
        var inputs = event.target.parentNode.querySelectorAll(".column");

        for (var i = 0; i < inputs.length; i++) {
          inputs[i].disabled = !event.target.checked;
        }
      }

      function createButtonHandler(event) {
        var files = event.target.files;

        for (var i = 0; i < files.length; i++) {
          var file = files[i];
          var reader = new FileReader();

          reader.onload = function(event) {
            var lines = event.target.result.split(/\r\n|\n/);

            if (lines.length > 0) {
              var headers = lines[0].split(",");
              createColumnHead.innerHTML = "<tr>" + headers.map(function (h) {
                var html = "<th>"
                  + "<input type=\"checkbox\" checked onchange=\"disableColumnHandler(event)\" />"
                  + "<input type=\"text\" name=\"field[]\" value=\"" + h + "\" required class=\"column\" />"
                  + "<input type=\"text\" name=\"name[]\" value=\"" + h + "\" required hidden class=\"column\" />"
                  + "<select name=\"type[]\" required class=\"column\">";
                for (var i = 0; i < columnTypes.length; i++) {
                  html += "<option value=\"" + columnTypes[i] + "\">" + columnTypes[i] + "</option>";
                }
                html += "</select>"
                  + "<input type=\"text\" name=\"length[]\" placeholder=\"length\" class=\"column\" />"
                  + "<input type=\"text\" name=\"extra[]\" placeholder=\"extra\" class=\"column\" />"
                  + "</th>";

                return html;
              }).join("") + "</tr>";

              createColumnBody.innerHTML = "";

              for (var i = 0; i <= 3; i++) {
                var values = lines[i].split(",");
                createColumnBody.innerHTML += "<tr>" + values.map(function (v) { return "<td>" + v + "</td>"; }).join("") + "</tr>";
              }

              createColumnBody.innerHTML += "<tr>" + headers.map(function (h) { return "<td>...</td>"; }).join("") + "</tr>";
              columnCount.innerHTML = headers.length + " columns";
              dataCount.innerHTML = (lines.length - 2) + " rows";

              showCreateDialog();
            }
          };

          reader.readAsText(file);
        }
      }

      function createSubmitHandler(event) {
        event.preventDefault();
        var field = createForm.querySelector("input[name='field']").value;
        var type = createForm.querySelector("select[name='type']").value;
        var length = createForm.querySelector("input[name='length']").value;
        var extra = createForm.querySelector("input[name='extra']").value;

        if (length) {
          type += "(" + length + ")";
        }

        post({
          url: url,
          data: { action: "create-column", database: database, table: table, field: field, type: type, extra: extra },
          resolve: reloadPage,
          reject: showErrorBar
        });
        return false;
      }
    </script>
  </body>
</html>
