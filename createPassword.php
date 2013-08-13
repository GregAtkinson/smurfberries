<?php
require('./PasswordHash.php');

if (isset($_POST['pass']))
{
  $pass = $_POST['pass'];
  $hasher = new PasswordHash(8,false);
  $hash = $hasher->HashPassword($pass);
  echo "hash for " . $pass . " is: " . $hash;
}
else 
{
  echo "<form action='createPassword.php' method='POST'>";
  echo "<input type='password' name='pass' size='40'>";
  echo "<input type='submit' value='create hash'>";
  echo "</form>";
}
