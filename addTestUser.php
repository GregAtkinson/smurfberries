<?php
require './PasswordHash.php';
$hasher = new PasswordHash(8,false);
$hash = $hasher->HashPassword('Boobies');

$name = 'team1';

include('smurfberries.conf');

$db= new mysqli($db_host, $db_std_user, $db_std_pass, $db_name, $db_port);
if (mysqli_connect_errno())
  echo "MySQL connect" .mysqli_connect_error();

$stmt = $db->prepare('INSERT INTO user (name, pass) VALUES (?,?)');
$stmt->bind_param('ss',$name,$hash);
$stmt->execute();
$stmt->close();
$db->close();
echo 'DONE';
