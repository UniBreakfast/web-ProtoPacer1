// pubsub hub - is a Hub module to use in Publish/Subscribe pattern
const hub = (() => {

  // private storage for existing subscriptions with callbacks for event handling
  const subs = {}

  // publish a named event for somebody subscribed to act upon given data
  function pub(e_name, data) {
    if (subs[e_name] && subs[e_name].length)
      subs[e_name].map(item=>item).forEach(cb => cb(data));
    else console.log('"'+e_name+'" event published without any subscribers');
    return this;
  }

  // subscribe to be called with a callback upon a certain event
  function sub(e_name, cb) {
    if (!subs[e_name]) subs[e_name] = [cb];
    else subs[e_name].push(cb);
    return this;
  }

  // unsubscribe certain callback from a certain event or whole event
  function unsub(e_name, cb) {
    if (!cb && subs[e_name]) delete subs[e_name];
    else {
      if (subs[e_name]) {
        var item = subs[e_name];
        item.splice(item.indexOf(cb), 1);
      }
      if (!item.length) delete subs[e_name];
    }
    return this;
  }

  // subscribe to be called only once with a callback upon a certain event
  function sub1(e_name, cb) {
    function temporary(data) {
      cb(data);
      unsub(e_name, temporary);
    }
    sub(e_name, temporary);
    return this;
  }

  // list registered event subscriptions or callback handlers for events
  const log = e_name => {
    if (e_name) console.log(subs[e_name]);
    else        console.log(subs);
  }

  // private storage for group subscriptions with callbacks for event handling
  const gr_subs = {}

  // try running callbacks if all publishers signed
  function gr_try(e_name) {
    if (!gr_subs[e_name]) return;
    var gr_e = gr_subs[e_name];
    if (gr_e.busy) return;
    else gr_e.busy = true;

    if (gr_e.pubs_got >= gr_e.pubs_req && gr_e.cb.length) {
      while (gr_e.cb.length) {
        var cb = gr_e.cb.pop();
        cb(gr_e.data);
        if (gr_e.subs_left>0) {
          if  (gr_e.cb_)  gr_e.cb_.push(cb);
          else gr_e.cb_ = [cb];
        }
        gr_e.subs_left--;
      }
      if (!gr_e.subs_left) {
        if (gr_e.once) delete gr_subs[e_name];
        else {
          gr_e.pubs_got = 0;
          gr_e.subs_left = gr_e.subs_req;
          gr_e.cb = gr_e.cb_;
          gr_e.cb_ = [];
          gr_e.data = {};
        }
      }
    }
    gr_e.busy = false;
    return this;
  }

  // creation of new named group subscribtion event
  function gr_init(e_name) {
    gr_subs[e_name] = { pubs_got: 0, cb: [], cb_: [], data: {} };
  }

  // setting named group subscribtion event with requirements
  function gr_set(e_name, pubs_req, subs_req, once) {
    if (!gr_subs[e_name]) gr_init(e_name);
    var gr_e = gr_subs[e_name];
    gr_e.pubs_req  = pubs_req;
    gr_e.subs_req  = subs_req;
    gr_e.subs_left = subs_req;
    gr_e.once = once;

    gr_try(e_name);
    return this;
  }

  // setting named group subscribtion event for one round
  function gr_set1(e_name, pubs_req, subs_req) {
    //return gr_set(e_name, pubs_req, subs_req, true);
    gr_set(e_name, pubs_req, subs_req, true);
    return this;
  }

  // subscribe a callback for a full group of event publishes
  function gr_sub(e_name, cb) {
    if (!gr_subs[e_name]) gr_init(e_name);

    gr_subs[e_name].cb.push(cb);

    gr_try(e_name);
    return this;
  }

  // publish one event of a group
  function gr_pub(e_name, data_key, data_value) {
    if (!gr_subs[e_name]) gr_init(e_name);

    gr_subs[e_name].pubs_got++;
    if (data_key) gr_subs[e_name].data[data_key] = data_value;

    gr_try(e_name);
    return this;
  }

  // subscribe to and publish to a group event at once
  function gr_subpub(e_name, cb, data_key, data_value) {
    gr_sub(e_name, cb);
    gr_pub(e_name, data_key, data_value);
    return this;
  }

  // unsubscribe whole group event or certain callback from it
  function gr_unsub(e_name, cb) {
    if (gr_subs[e_name]) {
      if (!cb) delete gr_subs[e_name];
      else {
        var item = gr_subs[e_name].cb;
        item.splice(item.indexOf(cb), 1);
        item = gr_subs[e_name].cb_;
        item.splice(item.indexOf(cb), 1);
      }
    }
    return this;
  }

  // show named group event(s) subscribtions status
  const gr_log = e_name => {
    if (e_name) console.log(gr_subs[e_name]);
    else        console.log(gr_subs);
  }


  // public methods for hub object: publish (triggers event handlers), subscribe, unsubscribe, list
  const hub = {log, gr_log};

  hub.pub   =   pub.bind(hub);
  hub.sub   =   sub.bind(hub);
  hub.sub1  =  sub1.bind(hub);
  hub.unsub = unsub.bind(hub);

  hub.gr_set    =    gr_set.bind(hub);
  hub.gr_set1   =   gr_set1.bind(hub);
  hub.gr_pub    =    gr_pub.bind(hub);
  hub.gr_sub    =    gr_sub.bind(hub);
  hub.gr_unsub  =  gr_unsub.bind(hub);
  hub.gr_subpub = gr_subpub.bind(hub);

  return hub;
})();

// aliases for conventional meaning
hub.do       = hub.sub;
hub.do1      = hub.sub1;
hub.go       = hub.pub;
hub.off      = hub.unsub;
hub.gr_do    = hub.gr_sub;
hub.gr_go    = hub.gr_pub;
hub.gr_do_go = hub.gr_subpub;
hub.gr_off   = hub.gr_unsub;
