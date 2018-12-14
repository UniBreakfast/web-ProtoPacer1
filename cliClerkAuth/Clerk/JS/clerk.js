'use strict';

// data-model object with methods to feed itself with JSON to collect data
const clerk = (()=>{

  var clerk_php = 'Clerk/PHP/clerk.php';

  // 'Clerk/PHP/clerk.php' is set by default
  function setPath(path_to_clerk_php) { clerk_php = path_to_clerk_php }

  function SignUp(login, pass) {
    if (login && pass)
      f.POST(clerk_php+'?task=reg'+'&login='+login+'&pass='+pass,
             response => log(JSON.parse(response)), log);
    else log(new Response(102, 'E', 'Not enough credentials to register!'));
  }

  function SignIn(login, pass) {
    if (login && pass)
      f.POST(clerk_php+'?task=login'+'&login='+login+'&pass='+pass,
             response => {
        response = JSON.parse(response);      let d;
        if (d = response.data) {
          f.cookie.set('userid', d.userid, d.expire);
          f.cookie.set('token',  d.token,  d.expire);
        }
        log(response);
      }, log);
    else log(new Response(106, 'E', 'Not enough credentials to sign in!'));
  }

  function isSignedIn() {
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
    }, log);
    else log(new Response(109, 'F', 'No complete session cookie found'));
  }

  function SignOut() {
    const userid = f.cookie.get('userid'), token = f.cookie.get('token');
    if (userid && token) {
      f.POST(clerk_php+'?task=logout'+'&userid='+userid+'&token='+token, 0,log);
      f.cookie.remove('userid');
      f.cookie.remove('token');
      log(new Response(111, 'S', 'Signed out'));
    }
    else log(new Response(113, 'I', 'You are not signed in!'));
  }

  function abandon(silent) {
    f.cookie.remove('userid');
    f.cookie.remove('token');
    if (!silent) log(new Response(126, 'S', 'Session cookies - no more!'));
  }

  function ChangePassword(login, oldpass, newpass) {
    if (login && oldpass && newpass)
      f.POST(clerk_php+'?task=newpass'+
             '&login='+login+'&oldpass='+oldpass+'&newpass='+newpass,
             response => log(JSON.parse(response), log));
    else log(new Response(117, 'E',
                          'Not enough credentials to change password!'));
  }

  function ChangeLogin(oldlogin, pass, newlogin) {
    if (oldlogin && pass && newlogin)
      f.POST(clerk_php+'?task=rename'+
             '&oldlogin='+oldlogin+'&pass='+pass+'&newlogin='+newlogin,
             response => log(JSON.parse(response), log));
    else log(new Response(121, 'E',
                          'Not enough credentials to change login!'));
  }

  function UnRegister(login, pass) {
    if (login && pass)
      f.POST(clerk_php+'?task=unreg'+'&login='+login+'&pass='+pass,
             response => log(JSON.parse(response), log));
    else log(new Response(125, 'E', 'Not enough credentials to unregister!'));
  }

  function getData(table, fields, own=0) {
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
    }, log);
  }

  function accessList(own, table) {
    if (own === undefined || own > 1) own = 2;
    else own = own ? 1 : 0;
    const userid = f.cookie.get('userid'), token = f.cookie.get('token');
    let creds = (userid && token) ?
        '&userid='+userid+'&token='+token+'&own='+own : '';
    table = table || '';
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
    }, log);  }

  const clerk = {setPath,
                 SignUp, SignIn, isSignedIn, SignOut, abandon,
                 ChangePassword, ChangeLogin, UnRegister,
                 accessList, getData}

  return clerk;
})();
