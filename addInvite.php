<?php //start session and db
include_once('./functions.php');
start_session($session_name, true);
$db = start_db();

//validate user
if (!(login_check($db) && admin_check($db)))
{
  header('Location: ./index.php');
  die();
}

//generate and add the token
generateInvite($db);
header('Location: ./admin.php');
die();
