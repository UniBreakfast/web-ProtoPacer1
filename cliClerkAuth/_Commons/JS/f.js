'use strict';

// functions container object for common functions I use
const f = {
  // aliases
  byID : document.getElementById        .bind(document),
  byCls: document.getElementsByClassName.bind(document),
  newEl: document.createElement         .bind(document),

  // to request do f.request(type, url, callback, reportcb, falldata, fallcb)
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
          else if (reportcb)
            reportcb('php file content returned instead of php-response');
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
  },

  str_to_style: (css, id) => {
    var style = f.newEl('style');
    style.textContent = css;
    if (id) style.id = id;
    document.head.appendChild(style);
  }
}

// to request do f.GET(url, callback, reportcb, falldata, fallcb)
f.GET  = f.request.bind(this, 'GET');
// to request do f.POST(url, callback, reportcb, falldata, fallcb)
f.POST = f.request.bind(this, 'POST');

function Response(code, type, text) {
  switch (type) {
    case 'S': type = 'SUCCESS';   break;
    case 'F': type = 'FAIL';      break;
    case 'E': type = 'ERROR';     break;
    case 'I': type = 'INFO';      break;
  }
  this.msg = {code, type, text}
}

//let log = console.log; "plus"
function log(subj, func) {
  if (typeof subj == 'function' && func === undefined) {
    function log(subject) { subj(subject) }
    return log;
  }
  if (subj === undefined) return log;
  func = func || console.log;
  if (subj.msg) func(subj.msg.type+' '+subj.msg.code+': '+subj.msg.text);
  else          func(subj);
}

JSON.tryparse = str => {
  try { return JSON.parse(str) }
  catch (e) { return str }
}
