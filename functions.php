<?php
require('./PasswordHash.php');
include('./smurfberries.conf');

function start_db()
{
  global $db_host, $db_std_user, $db_std_pass, $db_name, $db_port, $debug;

  try
  {
    $dbh = new PDO("mysql:host=$db_host;dbname=$db_name", $db_std_user, $db_std_pass);
    if ($debug)
      echo "connected to database";
    return $dbh;

  }
  catch(PDOException $e)
  {
    if ($debug)
      $e->getMessage();
  }
}


/*
 * this is not required it will close at the end of the php script
function stop_db($db)
{
  try
  {
    $db = null;
  }
  catch(PDOException $e)
  {
    if ($debug)
      $e->getMessage();
  }

}
 */

function fail($pub, $pvt = '')
{
  global $debug;
  $msg = $pub;
  if ($debug && $pvt !== '')
    $msg .= ": $pvt";
  /* The $pvt debugging messages may contain chacters that need to
   * be escaped or quoted when producing HTML output.
   * Also debug should be set to false on a production system
   */
  exit("An error occured ($msg).\n");
}

function start_session($session_name, $secure)
{
  //make sure session cookie is not available through javascript
  $httponly = true;

  //hash alg to use for session id (not too important just needs to produce som$
  $session_hash = 'sha512';

  //check that we selected a vaild hash alg
  if (in_array($session_hash, hash_algos()))
    ini_set('session.hash_function', $session_hash);
  //how many bit per charcacter of the hash
  // valid options are '4' (0-9,a-f) '5' (0-9,a-v), and '6' (0-9,a-z,A-Z, "-", $
  ini_set('session.hash_bits_per_character',5);

  //force session to only use cookies not URL params.
  ini_set('session.use_only_cookies',1);

  //get session cookie params
  $cookieParams = session_get_cookie_params();
  //set some additional params
  session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
  //change the session name
  session_name($session_name);
  //now start session
  session_start();
  // This line reginerates session and deletes old one.
  // it also generates a new encryption key in the database.
  session_regenerate_id(true);
}

function close_session($sid, $db)
{
  ($stmt = $db->prepare('DELETE FROM session WHERE sid = ?')) || fail('MySQL prepare', $db->error);
  $stmt->bindParam(1,$sid,PDO::PARAM_STR);
  $stmt->execute();
  session_destroy();
}

function login_check($db)
{
  //quick check for valid logged_in flag
  if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] == false)
  return false;
  //check that manditory info exists
  if (!(isset($_SESSION['sid']) && isset($_SESSION['user_id'])))
    return false;
  //now check that session is valid by validating data with database
  $user_id = $_SESSION['user_id'];
  $user_sid = $_SESSION['sid'];
  $user_ip = $_SERVER['REMOTE_ADDR'];
  $userAgent = md5($_SERVER['HTTP_USER_AGENT']);

  //db data
  $stmt = $db->prepare('SELECT sid, ip, userAgent FROM session WHERE user_id = ?');
  $stmt->bindParam(1, $user_id, PDO::PARAM_STR);
  $stmt->execute();
  //we allow more than one session per user so we need to loop through the resu$
  while($row = $stmt->fetch(PDO::FETCH_ASSOC))
  {
    if($user_sid == $row['sid'])
    {
      if ($user_ip == $row['ip'])
      {
        if ($userAgent == $row['userAgent'])
          return true;
      }
    }
  }
  return false;
}

function admin_check($db)
{
  ($stmt = $db->prepare('SELECT isadmin FROM user WHERE id = ?')) || fail('MySQL prepare', $db->error);
  $stmt->bindParam(1, $user_id, PDO::PARAM_STR) || fail('MySQL bindParam', $db->error);
  $stmt->execute() || fail('MySQL execute', $db->error);
  $isadmin = $stmt->fetch(PDO::FETCH_ASSOC);


  if ($isadmin == true)
    return true;
  else
    return false;
}

function login($user, $pass, $db)
{
  global $hash_cost_log2, $hash_portable, $maxLoginAttempts;

  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $hash = '*';
  $stmt = $db->prepare('SELECT id, pass FROM user WHERE name = ?');
  $stmt->bindParam(1,$user, PDO::PARAM_STR) || fail('MySQL bindParam', $db->error);
  $stmt->execute() || fail('MySQL execute', $db->error);
  $row= $stmt->fetch(PDO::FETCH_ASSOC);
  $user_id = $row['id'];
  $hash = $row['pass'];

  //check for brute force attempt
  $now = time();
  $past = $now - 7200; //2 hours ago
  ($stmt= $db->prepare('SELECT COUNT(time) AS numAttempts FROM loginAttempt WHERE user_id = ? AND time > ?')) || fail('MySQL prepare', $db->error);
  $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
  $stmt->bindParam(2, $past, PDO::PARAM_STR);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $numAttempts= $row['numAttempts'];

  if ($numAttempts >= $maxLoginAttempts)
    fail('too many failed logins');

  //check login credentials
  if ($hasher->checkPassword($pass, $hash))
  {
    $sid = $hasher->get_random_bytes(30);
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $user_agent_hash = md5($userAgent);
    $ip = $_SERVER['REMOTE_ADDR'];
    //store details in database so they can be validated later.
    ($stmt = $db->prepare('INSERT INTO session (sid, user_id, userAgent, ip) VALUES (?,?,?,?)')) || fail ('MySQL prepare', $db->error);
    $stmt->bindParam(1, $sid, PDO::PARAM_STR);
    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $user_agent_hash, PDO::PARAM_STR);
    $stmt->bindParam(4, $ip, PDO::PARAM_STR);
    $stmt->execute() || fail('MySQL execute', $db->error);


    //set session vars
    $_SESSION['logged_in'] = true;
    $_SESSION['sid'] = $sid;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['teamname'] = $user;
    return true;
  }
  else
  {
  //INCORRECT Password log attempt in database
  ($stmt = $db->prepare('INSERT INTO loginAttempt (user_id,time) VALUES (?,?)')) || fail ('MySQL prepare', $db->error);
  $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
  $stmt->bindParam(2, $now, PDO::PARAM_STR);
  $stmt->execute() ||fail('MySQL execute', $db->error);
  return false;
  }
}

function create_team($name, $pass, $db)
{
  ($stmt = $db->prepare('INSERT INTO user (name, pass) VALUES (?,?)')) || fail('MySQL prepare', $db->error);
  $stmt->bindParam(1,$name, PDO::PARAM_STR);
  $stmt->bindParam(2, $pass, PDO::PARAM_STR);
  $stmt->execute() || fail('MySQL execute', $db->error);

}

function get_tokens($type = '*', $db)
{
  $stmt = $db-prepare('SELECT hash, value, host FROM token WHERE type = ?');
  $stmt-> bindParam(1, $type, PDO::PARAM_STR);
  $stmt-> execute();
  return $stmt();
}

function get_capture_tokens()
{
  return get_tokens('c');
}
