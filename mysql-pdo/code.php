<?php
$mysql_default=null;

class PDO_extended extends PDO {
  public function quoteIdent($field) {
    return "`" . strtr($field, array("`" => "``")) . "`";
  }
}

function sql($query, $mysql_data=null) {
  global $mysql_default;
  
  if(!$mysql_data)
    $mysql_data=$mysql_default;

  if($mysql_data['debug']&1)
    print "<!-- SQL-Query: $query -->\n";
  if($mysql_data['debug']&2) {
    global $path_config;
    global $current_user;

    file_put_contents("$path_config/.debug.log",
      timestamp()."\t".
      $current_user->id.":\n".
      $query."\n",
      FILE_APPEND|LOCK_EX);
  }

  if(!$res=$mysql_data['linkid']->query($query)) {
    global $path_config;
    global $current_user;

    file_put_contents("$path_config/.debug.log",
      timestamp()."\t".
      $current_user->id.":\n".
      "ERROR executing query \"{$query}\"\n" . print_r($mysql_data['linkid']->errorInfo(), 1) . "\n",
      FILE_APPEND|LOCK_EX);

    print "<pre>" . print_r($mysql_data['linkid']->errorInfo(), 1) . "</pre>";
    exit;
  }

  return $res;
}

function sql_connect(&$mysql_data=null) {
  global $mysql_default;
  global $design_hidden;

  if(!$mysql_data) {
    $mysql_data=$mysql_default;
    $mysql_default=0;
  }

  if($design_hidden)
    $mysql_data['debug']=0;

  if(!$mysql_data['linkid'] = new PDO_extended("mysql:host={$mysql_data['host']};dbname={$mysql_data['db']};charset=utf8", $mysql_data['user'], $mysql_data['passwd'])) {
    echo "Fehler beim Verbindungsaufbau!<br>";
    exit;
  }

  $mysql_data['linkid']->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  if(!$mysql_default)
    $mysql_default=$mysql_data;

  return $mysql_data;
}

function sql_close($mysql_data=null) {
  global $mysql_default;

  if(!$mysql_data)
    $mysql_data=$mysql_default;

  unset($mysql_data['linkid']);
}

function sql_fetch_assoc($res) {
  return $res->fetch();
}

function sql_fetch($res) {
  return $res->fetch();
}

function sql_num_rows($res) {
  return $res->fetch();
}

function sql_quote($str, $mysql_data=null) {
  global $mysql_default;

  if(!$mysql_data)
    $mysql_data=$mysql_default;

  return $mysql_data['linkid']->quote($str);
}

function sql_quote_ident($str, $mysql_data=null) {
  global $mysql_default;

  if(!$mysql_data)
    $mysql_data=$mysql_default;

  return $mysql_data['linkid']->quoteIdent($str);
}

function sql_build_set($data, $exclude=array()) {
  $str=array();
  foreach($data as $k=>$v) {
    if(!in_array($k, $exclude)) {
      if($v)
        $str[]="$k=\"$v\"";
      else
        $str[]="$k=null";
    }
  }

  return $str;
}

$mysql=sql_connect($mysql);
