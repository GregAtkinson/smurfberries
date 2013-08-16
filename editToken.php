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

$message = '';
$type = 'ERROR';

//perform script or show form?
if ($_SERVER['REQUEST_METHOD'] === "POST")
{
  //check for cancel
  if (isset($_POST['cancel']))
  {
    header('Location: ./admin.php');
    die();
  }

  //validate form
  if (!isset($_POST['type']))
    fail('input error', 'token type must be set');

  $id = $_POST['id'];
  $type = $_POST['type'];
  $hash = $_POST['hash'];
  $value = $_POST['value'];
  $host = $_POST['host'];
  $service = '';
  $uname = '';
  $pass = '';
  if($type == 'r')
  {
    $service = $_POST['service'];
    $uname = $_POST['uname'];
    $pass = $_POST['pass'];
    if(!isset($host))
      $message .= "ERROR: host value must be set<br>\n";
    if(!isset($service))
      $message .= "ERROR: service value must be set<br>\n";
  }

  if(!isset($hash))
    $message .= "ERROR: Token value must be set<br>\n";
  if(!preg_match('/^[1-9][0-9]{0,5}$/', $value))
    $message .= "ERROR: value must be an integer between 1-99999.<br>\n";

  if($message == '') //no errors where encoutered
  {
    $stmt = $db->prepare('UPDATE token SET hash = ?, value = ?, host = ?, service = ?, uname = ?, pass = ? WHERE id = ?');
    $stmt->execute(array($hash,$value,$host,$service,$uname,$pass,$id));
    header('Location: ./admin.php');
    die();
  }
}

//Get data for form.
$id = $_GET['id'];
$stmt = $db->prepare('SELECT hash, value, host, service, uname, pass, type FROM token WHERE id = ?');
$stmt->bindParam(1, $id, PDO::PARAM_INT);
$stmt->execute();
$token = $stmt->fetch(PDO::FETCH_ASSOC);
$hash = $token['hash'];
$value = $token['value'];
$host = $token['host'];
$service = $token['service'];
$uname = $token['uname'];
$pass = $token['pass'];
$type = $token['type'];
?>

<form action="" method="POST">
<table>
<tr><td>id</td><td><input type="text" name="id" value= '<?php echo $id; ?>' readonly></td></tr>
<tr><td>Token:</td><td><input type="text" name="hash" value='<?php echo $hash; ?>'</td></tr>
<tr><td>Points:</td><td><input type="text" name="value" value='<?php echo $value; ?>'</td></tr>
<tr><td>Host:</td><td><input type="text" name="host" value='<?php echo $host; ?>'</td></tr>
<?php if($type == 'r'): ?>
<tr><td>Service:</td><td><input type="text" name="service" value='<?php echo $service; ?>'</td></tr>
<tr><td>User:</td><td><input type="text" name="uname" value='<?php echo $uname; ?>'</td></tr>
<tr><td>Password:</td><td><input type="text" name="pass" value='<?php echo $pass; ?>'</td></tr>
<?php endif; ?>
</table>
<input type="hidden" name="type" value='<?php echo $type;?>'>
<input type="submit" name="edit" value="Save">
<input type="submit" name="cancel" value="Cancel">
</form>





