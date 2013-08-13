<?php
require('./PasswordHash.php');
include('./papaSmurf.conf.php');
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

function close_session($sid)
{
  global $db_host, $db_std_user, $db_std_pass, $db_name, $db_port;

  $db= new mysqli($db_host, $db_std_user, $db_std_pass, $db_name, $db_port);
  if (mysqli_connect_errno())
    fail('MySQL connect', mysqli_connect_error());

  ($stmt = $db->prepare('DELETE FROM session WHERE sid = ?')) || fail('MySQL prepare', $db->error);
  $stmt->bind_param('s', $sid);
  $stmt->execute();
  session_destroy();
  $stmt->close();
  $db->close();
}

/* this function is just in case magic quotes is enabled on our server
 * as it would screw with our users logins and/or passwords
 */
function get_post_var($var)
{
  $val = $_POST[$var];
  if (get_magic_quotes_gpc())
    $val = stripslashes($val);
  return $val;
}

function login_check()
{
  global $db_host, $db_std_user, $db_std_pass, $db_name, $db_port;

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
  $db= new mysqli($db_host, $db_std_user, $db_std_pass, $db_name, $db_port);
  if (mysqli_connect_errno())
    fail('MySQL connect', mysqli_connect_error());

  $stmt = $db->prepare('SELECT sid, ip, userAgent FROM session WHERE user_id = ?');
  $stmt->bind_param('s', $user_id);
  $stmt->execute();
  $stmt->bind_result($db_sid, $db_ip, $db_userAgent);
  //we allow more than one session per user so we need to loop through the resu$
  while($stmt->fetch())
  {
    if($user_sid == $db_sid)
    {
      if ($user_ip == $db_ip)
      {
        if ($userAgent == $db_userAgent)
        {
          $stmt->close();
          $db->close();
          return true;
        }
      }
    }
  }
  $stmt->close();
  $db->close();
  return false;
}

function admin_check()
{
  global $db_host, $db_std_user, $db_std_pass, $db_name, $db_port;
  $user_id = $_SESSION['user_id'];

  $db= new mysqli($db_host, $db_std_user, $db_std_pass, $db_name, $db_port);
  if (mysqli_connect_errno())
    fail('MySQL connect', mysqli_connect_error());
  ($stmt = $db->prepare('SELECT isadmin FROM user WHERE id = ?')) || fail('MySQL prepare', $db->error);
  $stmt->bind_param('s', $user_id) || fail('MySQL bind_param', $db->error);
  $stmt->execute() || fail('MySQL execute', $db->error);
  $stmt->bind_result($isadmin);
  $stmt->close();
  $db->close();

  if (isadmin == true)
    return true;
  else
    return false;
}

function login($user, $pass)
{
  global $db_host, $db_std_user, $db_std_pass, $db_name, $db_port, $hash_cost_log2, $hash_portable, $maxLoginAttempts;

  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $hash = '*';
  $db= new mysqli($db_host, $db_std_user, $db_std_pass, $db_name, $db_port);
  if (mysqli_connect_errno())
    fail('MySQL connect', mysqli_connect_error());
  ($stmt = $db->prepare('SELECT id, pass FROM user WHERE name = ?')) || fail('MySQL prepare', $db->error);
  $stmt->bind_param('s', $user) || fail('MySQL bind_param', $db->error);
  $stmt->execute() || fail('MySQL execute', $db->error);
  $stmt->bind_result($user_id, $hash) || fail('MySQL bind_result', $db->error);
  if (!$stmt->fetch() && $db->errno)
    fail('MySQL fetch', $db->error);
  $stmt->close();
  //check for brute force attempt
  $now = time();
  $past = $now - 7200; //2 hours ago
  ($stmt= $db->prepare('SELECT COUNT(time) AS numAttempts FROM loginAttempt WHERE user_id = ? AND time > ?')) || fail('MySQL prepare', $db->error);
  $stmt->bind_param('is',$user_id, $past);
  $stmt->execute();
  $stmt->bind_result($numAttempts) ||fail('MySQL bind_result', $db->error);
  if (!$stmt->fetch() && $db->errno)
    fail('MySQL fetch', $db->error);

  if ($numAttempts >= $maxLoginAttempts)
    fail('too many failed logins');

  $stmt->close();
  //check login credentials
  if ($hasher->checkPassword($pass, $hash))
  {
    $sid = $hasher->get_random_bytes(30);
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $user_agent_hash = md5($userAgent);
    $ip = $_SERVER['REMOTE_ADDR'];
    //store details in database so they can be validated later.
    ($stmt = $db->prepare('INSERT INTO session (sid, user_id, userAgent, ip) VALUES (?,?,?,?)')) || fail ('MySQL prepare', $db->error);
    $stmt->bind_param('siss',$sid, $user_id, $user_agent_hash, $ip) || fail('MySQL bind_param', $db->error);
    $stmt->execute() || fail('MySQL execute', $db->error);
    $stmt->close();
    $db->close();

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
  $stmt->bind_param('is', $user_id, $now) || fail('MySQL bind_param', $db->error);
  $stmt->execute() ||fail('MySQL execute', $db->error);
  return false;
  }
}

function create_team($name, $pass)
{
  global $db_host, $db_std_user, $db_std_pass, $db_name, $db_port;

  $db= new mysqli($db_host, $db_std_user, $db_std_pass, $db_name, $db_port);
  if (mysqli_connect_errno())
    fail('MySQL connect', mysqli_connect_error());
  ($stmt = $db->prepare('INSERT INTO user (name, pass) VALUES (?,?)')) || fail('MySQL prepare, $db->error);
  $stmt->bind_param('ss',$name,$pass) || fail('MySQL bind_param', $db->error);
  $stmt->execute() || fail('MySQL execute', $db->error);
  $stmt->close();
  $db->close();
}

