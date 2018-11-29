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
  var contentType = settings.contentType;
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
        try {
          response = JSON.parse(xhttp.responseText);
        } catch (error) {}

        resolve(response);
      } else {
        reject(xhttp.responseText);
      }
    }
  };

  xhttp.open(method, url, true);

  if (contentType) {
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  }

  xhttp.send(data);
}

function get(settings) {
  settings.method = "get";
  settings.contentType = false;

  ajax(settings);
}

function post(settings) {
  settings.method = "post";
  settings.contentType = true;
  settings.data = toURLParams(settings.data);

  ajax(settings);
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
    if (form.elements[i].name === "") {
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
