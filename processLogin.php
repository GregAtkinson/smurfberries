<?php
include_once('./functions.php');
start_session('_s', true);
if (isset($_POST['op']))
{
  if ($_POST['op'] === 'logout')
  {
    if (!login_check())
      echo "ERROR allready logged out<br>/n";
    else
    {
      $sid = $_SESSION['sid'];
      //unset all session data
      $_SESSION = array();
      //get sesssion params
      $params = session_get_cookie_params();
      //delete actual cookie
      setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
      //close session NOTE session is destroyed in the close session function
      close_session($sid);
      //redirect to homepage
      header('Location: ./index.php');
    }
  }
  else if ($_POST['op'] === 'login')
  {
    $user = get_post_var('user');
    // users cant be trusted so sanity check the input before proceding
    if (!preg_match('/^[a-zA-Z0-9_]{1,60}$/', $user))
      fail('Invalid username');

    $pass = get_post_var('pass');
    // users cant be trusted so check length of the password
    // our hashing algorithum only uses the first 72 chars anyway
    if (strlen($pass) > 72)
      fail('The supplied password is too long');
    if(login( $user, $pass))
      header('Location: ./index.php');
    else
      fail('Login unsuccessfull');
  }
}