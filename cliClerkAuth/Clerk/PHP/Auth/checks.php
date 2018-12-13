<?php

function randStr() {
  $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $str = '';
  for ($i = 0; $i < 32; ++$i) {
    $chars = str_shuffle($chars);
    $str  .= $chars[rand(0, 60)];
  }
  return $str;
}

function hashStr($str) {
  $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $salt = '';
  for ($i = 0; $i < 22; ++$i) {
    $chars = str_shuffle($chars);
    $salt .= $chars[rand(0, 60)];
  }
  return $str ? substr(crypt($str, '$2a$10$'.$salt), 7) : '';
}

function hashCheck($str, $hash) {
  return !($str.$hash) || ('$2a$10$'.$hash == crypt($str, '$2a$10$'.$hash)) ?
    true : false;
}

function bfp() {
  global $checkIP, $checkAgent;
  if ($checkIP) {
    $addr = $_SERVER['REMOTE_ADDR'];
    $addr = substr($addr, 0, strrpos($addr, '.'));
  } else $addr = '';
  $agent = $checkAgent ? $_SERVER['HTTP_USER_AGENT'] : '';
  return $addr.$agent;
}

function bfpCheck($hash) { return hashCheck(bfp(), $hash); }

?>
