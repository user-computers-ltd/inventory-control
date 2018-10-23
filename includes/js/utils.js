function toURLParams(object) {
  var urlParams = [];

  for (var key in object) {
    var value = object[key];

    if (value) {
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
