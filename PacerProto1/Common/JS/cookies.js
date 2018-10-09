;
f.APIcookie = (function () {
  function extend () {
    var result = {};
    for (var i = 0; i < arguments.length; i++) {
      let attributes = arguments[i];
      for (var key in attributes) result[key] = attributes[key];
    }
    return result;
  }

  const API = {};
  const decode = s => s.replace(/(%[0-9A-Z]{2})+/g, decodeURIComponent);

  function set (key, value, attributes) {
    attributes = extend({path: '/'}, API.defaults, attributes);

    if (typeof attributes.expires === 'number')
      attributes.expires = new Date(new Date()*1 + attributes.expires * 864e+5);

    attributes.expires=attributes.expires? attributes.expires.toUTCString() : '';

    try {
      var result = JSON.stringify(value);
      if (/^[\{\[]/.test(result)) value = result;
    } catch (e) {}

    value = encodeURIComponent(String(value))
      .replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g, decodeURIComponent);

    key = encodeURIComponent(String(key))
      .replace(/%(23|24|26|2B|5E|60|7C)/g, decodeURIComponent)
      .replace(/[\(\)]/g, escape);

    var stringifiedAttributes = '';
    for (var attributeName in attributes) {
      if (!attributes[attributeName]) continue;

      stringifiedAttributes += '; ' + attributeName;
      if (attributes[attributeName] === true) continue;

      stringifiedAttributes += '=' + attributes[attributeName].split(';')[0];
    }
    return (document.cookie = key + '=' + value + stringifiedAttributes);
  }

  function get (key, json) {
    var jar = {};
    var cookies = document.cookie ? document.cookie.split('; ') : [];
    for (var i = 0; i < cookies.length; i++) {
      let parts = cookies[i].split('=');
      let cookie = parts.slice(1).join('=');

      if (!json && cookie.charAt(0) === '"') cookie = cookie.slice(1, -1);

      try {
        let name = decode(parts[0]);
        cookie = decode(cookie);
        if (json) cookie = JSON.parse(cookie);
        jar[name] = cookie;
      } catch (e) {}
    }
    return key ? jar[key] : jar;
  }

  API.defaults = {};
  API.set      = set;
  API.get      = key => get(key, false);
  API.getJSON  = key => get(key, true);
  API.remove   = (key, attributes) =>
    set(key, '', extend(attributes, { expires: -1 }));

  return API;
})();
