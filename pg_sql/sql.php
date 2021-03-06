<?php
function sql_connect(&$conn) {
  // If database connection has not been opened yet, open it
  if(!isset($conn['connection'])) {
    // connect
    $conn['connection']=
      pg_connect("dbname={$conn['name']} user={$conn['user']} password={$conn['passwd']} host={$conn['host']}");

    // check for successful connection
    if(!$conn['connection']) {
      debug("db connection failed", "sql");

      call_hooks("sql_connection_failed", $conn);

      // still no valid connection - exit
      if(!$conn['connection']) {
        print "db connection failed\n";
        exit;
      }
    }

    // Set a title for debugging
    if(!isset($conn['title']))
      $conn['title']=print_r($conn['connection'], 1);

    // save time of connection start
    $conn['date']=time();

    // inform other modules about successful database connection
    if($conn['connection'])
      call_hooks("sql_connect", $conn);
    
    // log opening of connection
    debug("db connection {$conn['connection']} opened", "sql");
  }
}

function sql_query($qry, &$conn=0) {
  global $db;

  // If no database connection is supplied use default
  if(!$conn)
    $conn=&$db;

  if(!$conn) {
    print "NO DATABASE\n";
    exit;
  }

  // check if connection was opened too long
  if(isset($conn['date'])&&(time()-$conn['date']>3600)) {
    debug("connection {$conn['connection']} opened too long, closing", "sql");
    $conn['date']=null;
    pg_close($conn['connection']);
    unset($conn['connection']);
  }

  // check for database connection
  sql_connect($conn);

  // Rewrite SQL query
  call_hooks("pg_sql_query", $qry, $conn);

  // Do we want debug information?
  if(isset($conn['debug'])&&($conn['debug']))
    debug("CONN {$conn['title']}: ".$qry, "sql");

  // Query
  $res=pg_query($conn['connection'], $qry);

  // There was an error - call hooks to inform about error
  if($res===false) {
    // if postgresql connection died ...
    if(pg_connection_status($conn['connection'])==PGSQL_CONNECTION_BAD) {
      debug("sql connection died", "sql");
      pg_close($conn['connection']);
      unset($conn['connection']);

      call_hooks("sql_connection_failed", $conn);

      // if connection is back, retry query
      if(isset($conn['connection'])&&
         (pg_connection_status($conn['connection'])==PGSQL_CONNECTION_OK)) {
        $res=pg_query($conn['connection'], $qry);

        if($res!==false) {
          debug("sql retry successful", "sql");
          return $res;
        }
      }
      else {
        print "sql connection died\n";
        exit;
      }
    }

    $error=pg_last_error();
    call_hooks("sql_error", $db, $qry, $error);

    // If we want debug information AND we have an error, tell about it
    if(isset($conn['debug'])&&($conn['debug']))
      debug("CONN {$conn['title']}: ".pg_last_error(), "sql");
  }

  return $res;
}

function postgre_escape($str) {
  return "E'".strtr($str, array("'"=>"\\'", "\\"=>"\\\\"))."'";
}

function array_to_hstore($array) {
  $replace=array("'"=>"\\'", "\""=>"\\\\\"", "\\"=>"\\\\\\\\");
  $ret=array();
  foreach($array as $k=>$v) {
    $ret[]="\"".strtr($k, $replace)."\"=>\"".strtr($v, $replace)."\"";
  }

  return "E'".implode(", ", $ret)."'::hstore";
}

function parse_hstore($text) {
  return eval("return array($text);");
}

function pg_decode_array($str) {
  if(!preg_match("/^\{(.*)\}$/", $str, $m))
    return null;
  $str=$m[1];

  $ret=array();
  $mode=0;
  $curr="";
  $p=0;

  while($p<mb_strlen($str)) {
    $chr=mb_substr($str, $p, 1);

    if($mode==0) {
      if($chr=="\"")
	$mode=10;
      else {
        $mode=1;
	$curr.=$chr;
      }
    }
    elseif($mode==1) {
      if($chr==",") {
	$mode=0;
	$ret[]=$curr;
	$curr="";
      }
      else
	$curr.=$chr;
    }
    elseif($mode==10) {
      if($chr=="\"") {
	$mode=20;
      }
      elseif($chr=="\\")
	$mode=11;
      else
	$curr.=$chr;
    }
    elseif($mode==11) {
      $curr.=$chr;
      $mode=10;
    }
    elseif($mode==20) {
      if($chr==",") {
	$ret[]=$curr;
	$curr="";
	$mode=0;
      }
      else
	print "pg_decode_array(): Error at position $p (mode $mode), can't read '$chr'\n";
    }
    $p++;
  }

  if($mode==0)
    ;
  else if(($mode==1)||($mode==20)) {
    $ret[]=$curr;
  }
  else {
    print "pg_decode_array(): Error at end of string (mode $mode)\n";
  }

  return $ret;
}

function pg_encode_array($arr, $type="text") {
  $arr=array_map("postgre_escape", $arr);
  return "Array[".implode(", ", $arr)."]::{$type}[]";
}
