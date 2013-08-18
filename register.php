<?php
require_once('./functions.php');
start_session($session_name, true);
$db = start_db();

if (login_check($db))
{
  header('Location: ./index.php');
  die();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === "POST")
{
  if (!isset($_POST['user']))
    $message.="ERROR: you must supply a team name<br>\n";
  if (!isset($_POST['pass']))
    $message.="ERROR: you must supply a password<br>\n";
  if (!isset($_POST['pass2']))
    $message.="ERROR: you must confirm your password<br>\n";
  if (!isset($_POST['invite']))
    $message.="ERROR: you must supply an invite token. contact the event organiser to get one.<br>\n";

  if ($message == '') //no errors so far
  {
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $pass2 = $_POST['pass2'];
    $invite = $_POST['invite'];
    $row; //this is used to interact with the invite token

    //check if username is unique
    $stmt = $db->prepare('SELECT name FROM user WHERE name = ?');
    $stmt->execute(array($user));
    if ($stmt->fetch(PDO::FETCH_ASSOC)) //there is already a user by that name.
      $message .="ERROR: that username is already taken<br>\n";
    else
    {
      //check that passwords match
      if ($pass !== $pass2)
        $message .="ERROR: your passwords do not match<br>\n";
      else
      {
        //check that invite is valid
        $stmt = $db->prepare('SELECT id, token, expire FROM invite WHERE token = ?');
        $stmt->execute(array($invite));
        if (!($row = $stmt->fetch(PDO::FETCH_ASSOC)))
          $message .="ERROR: we have no record of that invite code<br>\n";
        else
        {
          $expire = $row['expire'];
          if (time() > $expire)
            $message .= "ERROR: your token has expired please contact the person who gave it to you<br>\n";
        }
      }
    }
    if ($message == '') //still no errors
    {
      create_team($user, $pass, $db);
      login($user, $pass, $db);
      expireInvite($row['id'], $db);
      header('Location: ./index.php');
      die();
    }
  }
}
?>

<?php include_once('./head.html'); ?>
<body>
<?php include_once('./navBar.php'); ?>

<div>
  <?php echo $message."<br>"; ?>
  <form action="" method="POST">
    <input type='hidden' name='op' value='addTeam'>
    teamname: <input type='text' name='user' size='40'><br>
    password: <input type='password' name='pass' size='40'><br>
    confirm password: <input type='password' name='pass2' size='40'><br>
    invite Token: <input type='text' name='invite' size='15'><br>
    <input type='submit' value='create team'>
  </form>
</div>

