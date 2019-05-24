function toURLParams(object) {
  var urlParams = [];

  for (var key in object) {
    var value = object[key];

    if (typeof value !== "undefined") {
      if (value.constructor === Array) {
        value.forEach(function(v) {
          urlParams.push(key + "[]=" + v);
        });
      } else {
        urlParams.push(key + "=" + value);
      }
    }
  }

  return urlParams.join("&");
}

function ajax(settings = {}) {
  var url = settings.url;
  var data = settings.data;
  var params = settings.params;
  var method = settings.method;
  var urlEncoded = settings.urlEncoded;
  var respondFile = settings.respondFile;
  var resolve = settings.resolve || function() {};
  var reject = settings.reject || function() {};

  if (params) {
    url += "?" + toURLParams(params);
  }

  var xhttp = new XMLHttpRequest();

  xhttp.onreadystatechange = function() {
    if (xhttp.readyState == 4) {
      if (xhttp.status === 200) {
        var response = true;
        var disposition = xhttp.getResponseHeader("content-disposition");
        var type = xhttp.getResponseHeader("content-type");

        if (disposition && disposition.indexOf("attachment") !== -1) {
          var filename = disposition.substring(
            disposition.indexOf("filename=") + 9
          );
          var filename = filename ? filename : "file";
          var link = document.createElement("a");
          link.href = window.URL.createObjectURL(
            new Blob([xhttp.response], { type: type })
          );
          link.download = filename;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        } else {
          try {
            response = JSON.parse(xhttp.responseText);
          } catch (error) {}
        }

        resolve(response);
      } else {
        reject(xhttp.responseText);
      }
    }
  };

  xhttp.open(method, url, true);

  if (urlEncoded) {
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    data = toURLParams(data);
  }

  if (respondFile) {
    xhttp.responseType = "blob";
  }

  xhttp.send(data);
}

function get(settings) {
  settings.method = "get";
  settings.urlEncoded =
    typeof settings.urlEncoded !== "undefined" ? settings.urlEncoded : false;
  settings.respondFile =
    typeof settings.respondFile !== "undefined" ? settings.respondFile : false;

  ajax(settings);
}

function post(settings) {
  settings.method = "post";
  settings.urlEncoded =
    typeof settings.urlEncoded !== "undefined" ? settings.urlEncoded : true;
  settings.respondFile =
    typeof settings.respondFile !== "undefined" ? settings.respondFile : false;

  ajax(settings);
}

function hasClass(element, className) {
  return element.className.split(" ").indexOf(className) !== -1;
}

function toggleClass(element, className, toggle) {
  var classes = element.className.split(" ");
  var index = classes.indexOf(className);

  if (toggle === true && index === -1) {
    classes.push(className);
  } else if (toggle === false && index !== -1) {
    classes.splice(index, 1);
  } else if (toggle !== true && toggle !== false) {
    if (index === -1) {
      classes.push(className);
    } else if (index !== -1) {
      classes.splice(index, 1);
    }
  }

  element.className = classes.join(" ");
}

function serialize(form) {
  if (!form || form.nodeName !== "FORM") {
    return;
  }

  var query = [];
  for (var i = form.elements.length - 1; i >= 0; i--) {
    if (form.elements[i].name === "" || form.elements[i].disabled) {
      continue;
    }

    switch (form.elements[i].nodeName) {
      case "INPUT":
        switch (form.elements[i].type) {
          case "text":
          case "hidden":
          case "password":
          case "button":
          case "reset":
          case "submit":
          case "number":
          case "date":
            query.push(
              form.elements[i].name +
                "=" +
                encodeURIComponent(form.elements[i].value)
            );
            break;
          case "checkbox":
          case "radio":
            if (form.elements[i].checked) {
              query.push(
                form.elements[i].name +
                  "=" +
                  encodeURIComponent(form.elements[i].value)
              );
            }
            break;
          case "file":
            break;
        }
        break;
      case "TEXTAREA":
        query.push(
          form.elements[i].name +
            "=" +
            encodeURIComponent(form.elements[i].value)
        );
        break;
      case "SELECT":
        switch (form.elements[i].type) {
          case "select-one":
            query.push(
              form.elements[i].name +
                "=" +
                encodeURIComponent(form.elements[i].value)
            );
            break;
          case "select-multiple":
            for (var j = form.elements[i].options.length - 1; j >= 0; j--) {
              if (form.elements[i].options[j].selected) {
                query.push(
                  form.elements[i].name +
                    "=" +
                    encodeURIComponent(form.elements[i].options[j].value)
                );
              }
            }
            break;
        }
        break;
      case "BUTTON":
        switch (form.elements[i].type) {
          case "reset":
          case "submit":
          case "button":
            query.push(
              form.elements[i].name +
                "=" +
                encodeURIComponent(form.elements[i].value)
            );
            break;
        }
        break;
    }
  }

  return query.join("&");
}

function downloadTextFile(filename, text) {
  var element = document.createElement("a");
  var content = "data:text/plain;charset=utf-8," + encodeURIComponent(text);
  element.setAttribute("href", content);
  element.setAttribute("download", filename);

  element.style.display = "none";
  document.body.appendChild(element);

  element.click();

  document.body.removeChild(element);
}

function getTime(dateString) {
  if (dateString) {
    var parts = dateString.split("-");
    var date = parts[0];
    var month = parts[1] - 1;
    var year = parts[2];

    return new Date(year, month, date).getTime();
  } else {
    return 0;
  }
}

function setTableSortable(table) {
  var headerColumns = table.querySelectorAll("thead tr th");
  var rows = table.querySelectorAll("tbody tr");

  for (var i = 0; i < rows.length - 1; i++) {
    for (var j = 0; j < headerColumns.length; j++) {
      var cell = rows[i].getElementsByTagName("td")[j];

      if (cell) {
        var value = cell.innerText.toLowerCase();
        var dateMatches = value.match(/([0-9]+)\-([0-9]+)\-([0-9]+)/g) || [];

        if (dateMatches.length > 0) {
          value = dateMatches[0]
            .split("-")
            .reverse()
            .join("");
        } else if (hasClass(headerColumns[j], "number")) {
          value = parseFloat(value.replace(",", "")) || 0;
        }

        cell.dataset.sortvalue = value;
      }
    }
  }

  for (var i = 0; i < headerColumns.length; i++) {
    toggleClass(headerColumns[i], "sort-column", true);

    var s = function(index) {
      return function() {
        sortTable(table, index);
      };
    };
    headerColumns[i].addEventListener("click", s(i));
  }
}

function sortTable(table, columnIndex) {
  var headerColumns = table.querySelectorAll("thead tr th");
  var tbody = table.querySelector("tbody");
  var rowElements = tbody.querySelectorAll("tr");
  var rows = [];
  var sortedAsc = hasClass(headerColumns[columnIndex], "sorted-asc");

  var parseValue = hasClass(headerColumns[columnIndex], "number")
    ? parseFloat
    : function(v) {
        return v;
      };

  for (var i = 0; i < rowElements.length; i++) {
    rows.push(rowElements[i]);
  }

  for (var i = 0; i < headerColumns.length; i++) {
    if (columnIndex === i) {
      toggleClass(headerColumns[i], "sorted-asc", !sortedAsc);
      toggleClass(headerColumns[i], "sorted-desc", sortedAsc);
    } else {
      toggleClass(headerColumns[i], "sorted-asc", false);
      toggleClass(headerColumns[i], "sorted-desc", false);
    }
  }

  rows.sort(function(a, b) {
    var x = a.getElementsByTagName("td")[columnIndex];
    var y = b.getElementsByTagName("td")[columnIndex];

    if (x && y) {
      var xValue = parseValue(x.dataset.sortvalue);
      var yValue = parseValue(y.dataset.sortvalue);

      return (!sortedAsc && xValue > yValue) || (sortedAsc && xValue < yValue);
    }
  });

  tbody.innerHTML = "";

  for (var i = 0; i < rows.length; i++) {
    tbody.appendChild(rows[i]);
  }
}

window.addEventListener("load", function() {
  var tables = document.querySelectorAll("table.sortable");

  for (var i = 0; i < tables.length; i++) {
    setTableSortable(tables[i]);
  }
});
