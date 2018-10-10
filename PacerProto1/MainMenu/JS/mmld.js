const mmld = function main_menu_load_data() {
  if (f.APIcookie.get('user')) {
    var user_id = f.APIcookie.get('user').split('|')[0];
    function place_ud(response, login, confidence) {
      [login, confidence] = response.split('|');
      document.getElementById('user')      .innerHTML = login;
      document.getElementById('confidence').innerHTML = confidence;
    }
    f.POST('MainMenu/PHP/mmld.php?userid='+user_id, place_ud, alert);
  }
}

mmld();
