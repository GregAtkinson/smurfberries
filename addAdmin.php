//TODO this file is not fit to be part of the final release it needs to make use of the functions file.

<?php if (isset($_POST['pass1'])): ?>
<?php
require('./PasswordHash.php');
include_once('./smurfberries.conf');
if ($_POST['pass1'] == $_POST['pass2'])
{
  $uname = $_POST['uname'];
  $pass = $_POST['pass1'];
  $hasher = new PasswordHash(8,false);
  $hash = $hasher->HashPassword($pass);
  $admin= true;

  $db= new mysqli($db_host, $db_std_user, $db_std_pass, $db_name, $db_port);
  if (mysqli_connect_errno())
    echo "MySQL connect" .mysqli_connect_error();

  $stmt = $db->prepare('INSERT INTO user(name, pass, isadmin) VALUES (?,?,?)');
  $stmt->bind_param('ssi', $uname, $hash, $admin);
  $stmt->execute();
  $stmt->close();
  $db->close();
  echo "done";
}
else
echo "error passwords dont match";
?>
<?php else: ?>
<h1>Create Admin account</h1>
<form action='addAdmin.php' method='POST' >
username: <input type='text' name='uname' size='40'><br>
password: <input type='password' name='pass1' size ='40'><br>
confirm password: <input type='password' name='pass2' size ='40'><br>
<input type='submit' value='create admin account'>
</form>
<?php endif;?>
