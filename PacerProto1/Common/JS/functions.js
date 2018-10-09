  // functions container object
const f = {
  request: function request(type, url, callback, reportcb, falldata, fallcb) {
    const request = new XMLHttpRequest();
    request.open(type, url, true);
    request.onload = () => {
      if (callback) {
        if (request.status >= 200 && request.status < 400) {
          if (!request.response.startsWith('<?php')) {
            if (request.response !== '') callback(request.response);
            else callback();
          }
        }
        else {
          if (reportcb) reportcb('request.status is ' + request.status);
          falldata ? callback(falldata) : callback();
        }
      }
      else if (fallcb) falldata ? fallcb(falldata) : fallcb();
    }
    request.onerror =
      e => reportcb(type + ' request to '+ url + ' produced ' + e);
    request.ontimeout =
      () => reportcb(type + ' request to '+ url + ' timed out!');
    request.send();
  }
}

f.GET  = f.request.bind(this, 'GET');
f.POST = f.request.bind(this, 'POST');
