const ifs_mmld = function main_menu_load_data() {
  var user = f.APIcookie.get('user');
  if (user===undefined) location.replace('login.htm');
  else {
    var user_id = user.substring(0, user.indexOf('|'))
    const cb = function callback(response, new_token) {
      /////////////////////////////////////////////////
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


  if (user) {
    var user_id = user.split('|')[0];
    function place_ud(response, login, confidence) {
      [login, confidence] = response.split('|');
      document.getElementById('user')      .innerHTML = login;
      document.getElementById('confidence').innerHTML = confidence;
    }
    //f.POST('MainMenu/PHP/mmld.php?userid='+user_id, place_ud, alert);
    f.POST('MMwAuth/PHP/ifs_mmld.php?userid='+user_id+
           '&user='+user, place_ud, alert);
  }
}

ifs_mmld();
