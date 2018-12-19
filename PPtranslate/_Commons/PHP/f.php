<?php

# just a convenient object with utilitary functions
class f
{
  # shortens the query for obscurity
  private static function _acronym($string) {
    $string = preg_replace('/\s+/', ' ', $string);
    $words = explode(' ', $string);
    $acronym = '"';
    foreach ($words as $word) {
      $word = trim($word);
      $acronym .= $word[0].' ';
    }
    return trim($acronym).'"';
  }

  # generic query wrapper to use differently in specific cases
  private static function _query($query, $db, $params) {
    $stmt = mysqli_stmt_init($db);
    $q = f::_acronym($query);
    if (!mysqli_stmt_prepare($stmt, $query)) exit("$q Statement failed!");

    foreach($params as &$param) {
      $param_types .= $param[1];
      $p_binds[]   = &$param[0];
    }
    array_unshift($p_binds, $stmt, $param_types);
    call_user_func_array('mysqli_stmt_bind_param', $p_binds);
    mysqli_stmt_execute($stmt) or exit("$q Query failed!");

    for ($i = 0; $i < mysqli_stmt_field_count($stmt); $i++)
      $r_binds[] = &$row[$i];
    array_unshift($r_binds, $stmt);
    call_user_func_array('mysqli_stmt_bind_result', $r_binds);
    while (mysqli_stmt_fetch($stmt))
      $result[] = array_map(function($field) { return $field; }, $row);
    mysqli_stmt_close($stmt);
    return array($q, $result);
  }

  # adds a record and returns id of inserted record afterwards
  static function putRecord($db, $query, $params) {
    f::_query($query, $db, $params);
    return mysqli_insert_id($db);
  }

  # simpy executes the query without returning anything
  static function setValues($db, $query, $params) {
    f::_query($query, $db, $params);
  }

  # simpy executes the query without returning anything
  static function execute($db, $query, $params) {
    f::_query($query, $db, $params);
  }

  # retrieves a single field value from a database
  static function getValue($db, $query, $params) {
    list($q, $result) = f::_query($query, $db, $params);
    if ($fields = count($result[0])) {
      $rows   = count($result);
      if ($rows>1 and $fields>1)
        exit("$q Query retrieved $rows rows with $fields fields "
             ."instead of one value!");
      if ($rows>1)
        exit("$q Query retrieved $rows rows instead of one value!");
      if ($fields>1)
        exit("$q Query retrieved $fields fields instead of one value!");
      return $result[0][0];
    }
  }

  # retrieves multiple rows from a database with one value each
  static function getValues($db, $query, $params) {
    list($q, $result) = f::_query($query, $db, $params);
    if ($fields = count($result[0])) {
      $rows   = count($result);
      if ($fields>1) exit("$q Query retrieved $rows row(s) "
                          ."with $fields fields each instead of one field value per row!");
      return array_map(function($row) { return $row[0]; }, $result);
    }
  }

  # retrieves a row of fields values from a database
  static function getRecord($db, $query, $params) {
    list($q, $result) = f::_query($query, $db, $params);
    if ($fields = count($result[0])) {
      $rows   = count($result);
      if ($rows>1) exit("$q Query retrieved $rows rows instead of one!");
      return $result[0];
    }
  }

  # retrieves multiple records with fieds from a database
  static function getRecords($db, $query, $params) {
    list($q, $result) = f::_query($query, $db, $params);
    return $result;
  }

  # returns list of values for provided $_REQUEST keys
  static function request($str1=null, $str2=null, $str3=null, $str4=null,
                          $str5=null, $str6=null, $str7=null, $str8=null) {
    $values = array();
    for ($i=1; $i<9; $i++) {
      $str = ${'str'.$i};
      if ($str) $values[] = trim($_REQUEST[$str]);
      else break;
    }
    if ($values) return $values;
  }

  # checks string for validity to a certain schema
  static function strCheck($str, $min=1, $max=0, $schema=0) {
    if (!is_string($str)) return 'not a string';
    if ($min and mb_strlen($str)<$min)
      return "string is too short, minimum is $min characters";
    if ($max and mb_strlen($str)>$max)
      return "string is too long, maximum is $max characters";
    if ($schema) {
      switch ($schema) {
        case 1:
        case "ERU-1": {
          $schema = "/[^A-Za-zА-Яа-яр-уЁёЇїІіЄєҐґ0-9’'\- ]/";
          if (preg_match($schema, $str))
            return "allowed only english, russian, ukrainian letter, ', -, "
                  ."numbers and NO spaces!";
        } break;
        default: {}
      }
    }
    //return 'check passed!'; // expected null by default
  }
}

function qp($p1=null, $t1=null, $p2=null, $t2=null, $p3=null, $t3=null,
            $p4=null, $t4=null, $p5=null, $t5=null, $p6=null, $t6=null) {
  for ($i=1; $i<7; $i++) {
    $param = ${'p'.$i};   $type = ${'t'.$i};
    if ($param and $type) $params[] = array($param, $type);
    else break;
  }
  return $params;
}

?>
