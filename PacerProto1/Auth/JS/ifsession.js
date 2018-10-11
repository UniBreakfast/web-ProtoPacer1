(() => {
  var inside = (!location.pathname.endsWith('login.htm') &&
                !location.pathname.endsWith('register.htm'));
  var user = f.APIcookie.get('user');
  if (user===undefined) {
    if (inside) location.replace('login.htm');
  }
  else {
    var user_id = user.substring(0, user.indexOf('|'))
    const incb = function inside_callback(response, new_token) {
      if (response.includes('|')) [response, new_token] = response.split('|');
      if (response == 'invalid') {
        f.APIcookie.remove('user');
        location.replace('login.htm');
      }
      else if (response == 'valid')
        f.APIcookie.set('user', user_id+'|'+new_token, {expires: 2.5});
      else alert(response);
    }

    const outcb = function outside_callback(response, new_token) {
      if (response.includes('|')) [response, new_token] = response.split('|');
      if (response == 'valid') {
        user = user_id+'|'+new_token;
        f.APIcookie.set('user', user, {expires: 2.5});
      }
      var prompt = 'You are already logged in. ' +
                   'Would you like to log out now and proceed to ' +
                   location.pathname;
      if (response == 'invalid' || (response == 'valid' && confirm(prompt))) {
        f.POST('Auth/PHP/logout.php?cookie='+user);
        f.APIcookie.remove('user');
      }
      else if (response == 'valid') location.replace(mainpage);
    }
    const reportcb = response => alert(response);
    f.POST('Auth/PHP/ifsession.php?cookie='+user,
           inside? incb : outcb, reportcb);
  }
})();
