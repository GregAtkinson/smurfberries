<?php
include_once('./functions.php');
start_session($session_name, true);
$db = start_db();

//check if user is allowed to see use this page
if (!(login_check($db) && admin_check($db)))
{
  header('Location: ./index.php');
  die();
}

$stmt = $db->prepare('DELETE FROM token WHERE id = ?');
$stmt->execute(array($_GET['id']));
header('Location: ./admin.php');
die();


