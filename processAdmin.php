<?php
require_once('./functions.php');
start_session($session_name, true);
$db = start_db();

if (!(login_check() && admin_check()))
{
  header('Location: ./index.php');
  die();
}
if(!issset($_POST['op']))
  fail('missing params', 'post param 'op' missing);

switch($_POST['op'])
{
case "addTeam":
  $name = $_POST['name'];
  $pass = $_POST['pass'];
  create_team($name, $pass, $db);
}

