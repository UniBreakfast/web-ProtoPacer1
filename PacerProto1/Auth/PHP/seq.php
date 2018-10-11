<?php

function tokenGen() {
  $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $token = '';
  for ($i = 0; $i < 32; ++$i) {
    $chars = str_shuffle($chars);
    $token .= $chars[rand(0, 60)];
  }
  return $token;
}

function hashGen($pass) {
  $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $salt = '';
  for ($i = 0; $i < 22; ++$i) {
    $chars = str_shuffle($chars);
    $salt .= $chars[rand(0, 60)];
  }
  return substr(crypt($pass, '$2a$10$'.$salt), 7);
}

function hashCheck($pass, $hash) {
  if ('$2a$10$'.$hash == crypt($pass, '$2a$10$'.$hash)) return true;
  else                                                  return false;
}

?>
