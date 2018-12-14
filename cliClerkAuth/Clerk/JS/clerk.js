'use strict';

// data-model object with methods to feed itself with JSON to collect data
const clerk = (()=>{

  var clerk_php = 'Clerk/PHP/clerk.php';

  // 'Clerk/PHP/clerk.php' is set by default
  function setPath(path_to_clerk_php) { clerk_php = path_to_clerk_php }

  let task_queue = [], wip = false;
  f.cookie.remove('wip');

  function begin_work() {
    wip = true;
    f.cookie.set('wip', '1');
  }
  function end_work() {
    wip = false;
    f.cookie.remove('wip');
  }
  function do_a_task() {
    begin_work();
    if (task_queue.length) task_queue.shift()(do_a_task);
    else end_work();
  }
  function add_task(func) {
    if (wip || f.cookie.get('wip')) task_queue.push(func);
    else if (task_queue.length) {
      task_queue.push(func);
      task_queue.shift()(do_a_task);
    }
    else {
      begin_work();
      func(do_a_task);
    }
  }

  //function TypicalTask(arg1, arg2) {
  //  function func(cb) {
  //    setTimeout(()=>{log(arg1+' '+arg2); cb()}, 8000);
  //  }
  //  add_task(func);
  //}

  function SignUp(login, pass) {
    function do_SignUp(cb_next_task) {
      if (login && pass)
        f.POST(clerk_php+'?task=reg'+'&login='+login+'&pass='+pass,
               response => {
          log(JSON.parse(response));
          cb_next_task();
        }, log);
      else {
        log(new Response(102, 'E', 'Not enough credentials to register!'));
        cb_next_task();
      }
    }

    add_task(do_SignUp);
  }

  function SignIn(login, pass) {
    function do_SignIn(cb_next_task) {
      if (login && pass)
        f.POST(clerk_php+'?task=login'+'&login='+login+'&pass='+pass,
               response => {
          response = JSON.parse(response);      let d;
          if (d = response.data) {
            f.cookie.set('userid', d.userid, d.expire);
            f.cookie.set('token',  d.token,  d.expire);
          }
          log(response);
          cb_next_task();
        }, log);
      else {
        log(new Response(106, 'E', 'Not enough credentials to sign in!'));
        cb_next_task();
      }
    }

    add_task(do_SignIn);
  }

  function isSignedIn() {
    function do_SignIn(cb_next_task) {
      const userid = f.cookie.get('userid'), token = f.cookie.get('token');
      if (userid && token)
        f.POST(clerk_php+'?task=check'+'&userid='+userid+'&token='+token,
               response => {
          response = JSON.parse(response);      let d;
          if (d = response.data) {
            f.cookie.set('userid', userid,  d.expire);
            f.cookie.set('token',  d.token, d.expire);
          }
          else if (drop_sess_on_deny) abandon(1);
          log(response);
          cb_next_task();
        }, log);
      else {
        log(new Response(109, 'F', 'No complete session cookie found'));
        cb_next_task();
      }
    }

    add_task(do_SignIn);
  }

  function SignOut() {
    function do_SignOut(cb_next_task) {
      const userid = f.cookie.get('userid'), token = f.cookie.get('token');
      if (userid && token) {
        f.POST(clerk_php+'?task=logout'+'&userid='+userid+'&token='+token, 0,
               log);
        f.cookie.remove('userid');
        f.cookie.remove('token');
        log(new Response(111, 'S', 'Signed out'));
        cb_next_task();
      }
      else {
        log(new Response(113, 'I', 'You are not signed in!'));
        cb_next_task();
      }
    }

    add_task(do_SignOut);
  }

  function abandon(silent) {
    f.cookie.remove('userid');
    f.cookie.remove('token');
    if (!silent) log(new Response(126, 'S', 'Session cookies - no more!'));
  }

  function ChangePassword(login, oldpass, newpass) {
    function do_ChangePassword(cb_next_task) {
      if (login && oldpass && newpass)
        f.POST(clerk_php+'?task=newpass'+
               '&login='+login+'&oldpass='+oldpass+'&newpass='+newpass,
               response => {
          log(JSON.parse(response));
          cb_next_task();
        }, log);
      else {
        log(new Response(117,'E','Not enough credentials to change password!'));
        cb_next_task();
      }
    }

    add_task(do_ChangePassword);
  }

  function ChangeLogin(oldlogin, pass, newlogin) {
    function do_ChangeLogin(cb_next_task) {
      if (oldlogin && pass && newlogin)
        f.POST(clerk_php+'?task=rename'+
               '&oldlogin='+oldlogin+'&pass='+pass+'&newlogin='+newlogin,
               response => {
          log(JSON.parse(response));
          cb_next_task();
        }, log);
      else {
        log(new Response(121, 'E', 'Not enough credentials to change login!'));
        cb_next_task();
      }
    }

    add_task(do_ChangeLogin);
  }

  function UnRegister(login, pass) {
    function do_UnRegister(cb_next_task) {
      if (login && pass)
        f.POST(clerk_php+'?task=unreg'+'&login='+login+'&pass='+pass,
               response => {
          log(JSON.parse(response));
          cb_next_task();
        }, log);
      else {
        log(new Response(125, 'E', 'Not enough credentials to unregister!'));
        cb_next_task();
      }
    }

    add_task(do_UnRegister);
  }

  function getData(table, fields, own=0) {
    function do_getData(cb_next_task) {
      if (table) {
        const userid = f.cookie.get('userid'), token = f.cookie.get('token');
        let creds = (userid && token) ?
            '&userid='+userid+'&token='+token+'&own='+own : '';
        f.POST(clerk_php+'?task=get'+'&table='+table+'&fields='+
               JSON.stringify(fields)+creds, response => {
          response = JSON.parse(response);      let d;
          if (d = response.data) {
            if (d.token) {
              f.cookie.set('userid', userid,  d.expire);
              f.cookie.set('token',  d.token, d.expire);
            }
            if (d.headers) {
              log(d.headers);
              log(d.rows);
            }
          }
          else if (drop_sess_on_deny) abandon(1);
          log(response);
          cb_next_task();
        }, log);
      }
      else {
        log(new Response(134, 'E', 'No table name provided to get data from'));
        cb_next_task();
      }
    }

    add_task(do_getData);
  }

  function accessList(own, table) {
    function do_accessList(cb_next_task) {
      if (own === undefined || own > 1) own = 2;
      else own = own ? 1 : 0;
      table = table || '';

      const userid = f.cookie.get('userid'), token = f.cookie.get('token');
      let creds = (userid && token) ?
          '&userid='+userid+'&token='+token+'&own='+own : '';

      f.POST(clerk_php+'?task=list'+creds+'&table='+table, response => {
        response = JSON.parse(response);      let d;
        if (d = response.data) {
          if (d.token) {
            f.cookie.set('userid', userid,  d.expire);
            f.cookie.set('token',  d.token, d.expire);
          }
          if (d.list) log(d.list);
        }
        else if (drop_sess_on_deny) abandon(1);
        log(response);
        cb_next_task();
      }, log);
    }

    add_task(do_accessList);
  }

  const clerk = {setPath,
                 SignUp, SignIn, isSignedIn, SignOut, abandon,
                 ChangePassword, ChangeLogin, UnRegister,
                 accessList, getData/*, TypicalTask*/}

  return clerk;
})();
