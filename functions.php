<?php
require('./PasswordHash.php');
include('./smurfberries.conf');

function start_db()
{
  global $db_host, $db_std_user, $db_std_pass, $db_name, $db_port, $debug;

  try
  {
    $dbh = new PDO("mysql:host=$db_host;dbname=$db_name", $db_std_user, $db_std_pass);
    garbageCollector($dbh);
    return $dbh;
  }
  catch(PDOException $e)
  {
    if ($debug)
      $e->getMessage();
  }
}

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
  $stmt = $db->prepare('DELETE FROM session WHERE sid = ?');
  $stmt->execute(array($sid));
  session_destroy();
}

/*
 * This function checks the session, loginAttempts and user tables and removes
 * artifacts that should no longer be there. This function works on timestamps
 * so should be called on a regular bases
 */

function garbageCollector($db)
{
  global $loginAttemptWindow, $sessionTimeout;

  $now = time();

  //remove anything from the loginAttempt table that is older than the login window.
  $window = $now - $loginAttemptWindow;
  $stmt = $db->prepare('DELETE FROM loginAttempt WHERE time < ?');
  $stmt->execute(array($window));

  //delete any sessions where last activity > session timeout
  $window = $now - $sessionTimeout;
  $stmt = $db->prepare('DELETE FROM session WHERE lastActicvity < ?');
  $stmt->execute(array($window));
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
  $stmt->execute(array($user_id));
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
  $user_id = $_SESSION['user_id'];

  $stmt = $db->prepare('SELECT isadmin FROM user WHERE id = ?');
  $stmt->execute(array($user_id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $isadmin = $row['isadmin'];

  if ($isadmin == 1)
    return true;
  else
    return false;
}

function login($user, $pass, $db)
{
  global $hash_cost_log2, $hash_portable, $maxLoginAttempts, $loginAttemptWindow;

  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $hash = '*';
  $stmt = $db->prepare('SELECT id, pass FROM user WHERE name = ?');
  $stmt->execute(array($user));
  $row= $stmt->fetch(PDO::FETCH_ASSOC);
  $user_id = $row['id'];
  $hash = $row['pass'];

  //check for brute force attempt
  $now = time();
  $past = $now - $loginAttemptWindow;
  $stmt = $db->prepare('SELECT COUNT(time) AS numAttempts FROM loginAttempt WHERE user_id = ? AND time > ?');
  $stmt->execute(array($user_id, $past));
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
    $stmt = $db->prepare('INSERT INTO session (sid, user_id, userAgent, ip) VALUES (?,?,?,?)');
    $stmt->execute(array($sid, $user_id, $user_agent_hash, $ip));

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
  $stmt = $db->prepare('INSERT INTO loginAttempt (user_id,time) VALUES (?,?)');
  $stmt->execute(array($user_id, $now));
  return false;
  }
}

function create_team($name, $pass, $db)
{
  global $hash_cost_log2, $hash_portable;

  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $hash = $hasher->hashPassword($pass);

  $teamToken = generate_token();
  $stmt = $db->prepare('INSERT INTO user (name, pass, teamToken) VALUES (?,?,?)');
  $stmt->execute(array($name, $hash, $teamToken));
}

/*
 * This function returns the PDO stmt conntating the invite token
 * The calling function will need to iterate using fetch commands to extract "THE DATA"
 */
function get_invites($db)
{
  $stmt = $db->prepare('SELECT id, token, expire FROM invite');
  $stmt->execute();
  return $stmt;
}

function generateInvite($db)
{
  global $hash_cost_log2, $hash_portable, $invite_length, $invite_timeout;

  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $rand = $hasher->get_random_bytes(30);

  $alpha = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $token = '';
  for ($i=0; $i<$invite_length; $i++)
  {
    $value = ord($rand[$i]); // this selects the next number from the random bytes
    $token .=  $alpha [$value & 0x3f]; //this makes sure that the $value is not ouside the range of alpha (0x3f is the length of the alpha array) and selects the character
  }

  $expire = time() + $invite_timeout;
  $stmt = $db->prepare('INSERT INTO invite (token, expire) VALUES (?,?)');
  $stmt->execute(array($token, $expire));
}

function deleteInvite($id, $db)
{
  $stmt = $db->prepare('DELETE FROM invite WHERE id = ?');
  $stmt->execute(array($id));
}

function expireInvite($id, $db)
{
  $now = time();
  $stmt = $db->prepare('UPDATE invite SET expire=? WHERE id = ?');
  $stmt->execute(array($now, $id));
}

/*
 * This function returns the PDO stmt conntating the requested type of token
 * The calling function will need to iterate using fetch commands to extract "THE DATA"
 */
function get_tokens($db, $type = '', $user_id='')
{
  if ($type != '')
  {
    if ($user_id != '')
    {
      $stmt = $db->prepare('SELECT id, hash, value, host, service, uname, pass FROM token WHERE type = ? AND user_id = ?');
      $stmt->execute(array($type, $user_id));
      return $stmt;
    }
    else
    {
      $stmt = $db->prepare('SELECT id, hash, value, host, service, uname, pass FROM token WHERE type = ?');
      $stmt-> execute(array($type));
      return $stmt;
    }
  }
  else
  {
    if ($user_id != '')
    {
      $stmt = $db->prepare('SELECT id, hash, value, host, service, uname, pass FROM token WHERE user_id = ?');
      $stmt->execute(array($user_id));
      return $stmt;
    }
    else
    {
      $stmt = $db->prepare('SELECT id, hash, value, host, service, uname, pass FROM token');
      $stmt->execute();
      return $stmt;
    }
  }
}

function get_capture_tokens($db)
{
  return get_tokens($db, 'c', '');
}

function get_retrieve_tokens($db, $user_id='')
{
  return get_tokens($db, 'r', $user_id);
}

function generate_token()
{
  global $hash_cost_log2, $hash_portable,$token_length;
  $alpha = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $token = '';

  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $rand = $hasher->get_random_bytes(30);
  for ($i= 0; $i<$token_length; $i++)
  {
    $value = ord($rand[$i]); // this selects the next number from the random bytes
    $token .=  $alpha [$value & 0x3f]; //this makes sure that the $value is not ouside the range of alpha (0x3f is the length of the alpha array) and selects the character
  }
  return $token;
}

function get_summary($user_id, $db)
{
  //get score data
  $captures = $db->prepare('SELECT token.value FROM capture, token WHERE used_id = ? AND capture.token_id = token.id');
  $captures->execute(array($user_id));

  //calculate score
  $totalScore = 0;
  while($row = $captures->fetch(PDO::FETCH_ASSOC))
  {
    $totalScore += $row['value'];
  }

  //get time since last_score AND team_token
  $stmt = $db->prepare('SELECT lastCapture, teamToken FROM user WHERE id = ?');
  $stmt->execute(array($user_id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $lastCapture = $row['lastCapture'];
  $teamToken = $row['teamToken'];

  //get retrieve tokens assigned to this user
  $tokens = get_retrieve_tokens($db, $user_id);

  return array("total_score"=>$totalScore, "last_score" => $lastCapture, "retrieve_tokens" => $tokens, "team_token"=> $teamToken);
}
