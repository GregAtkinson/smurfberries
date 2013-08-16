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

$type='ERROR';
$message = '';


//check wether to display form or run script
if($_SERVER['REQUEST_METHOD'] === 'POST')
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

  $type = $_POST['type'];
  if ($type == 'c')
  {
    if (!(isset($_POST['token']) && isset($_POST['value'])))
       $message .= "ERROR: token and points fields must be entered for a capture token.\n";
    else
    {
      $hash = $_POST['token'];
      $value = $_POST['value'];
      $host = $_POST['host'];

      if(!preg_match('/^[1-9][0-9]{0,5}$/', $value))
        $message .= "ERROR: value must be an integer between 1-99999.";
      else
      {
        $stmt = $db->prepare('INSERT INTO token (hash, value, host, type) VALUES (?,?,?,?)');
        $stmt->bindParam(1, $hash, PDO::PARAM_STR);
        $stmt->bindParam(2, $value, PDO::PARAM_INT);
        $stmt->bindParam(3, $host, PDO::PARAM_STR);
        $stmt->bindParam(4, $type, PDO::PARAM_STR);
        $stmt->execute();
        header('Location: ./admin.php');
        die();
      }
    }
  }
  elseif ($type =='r')
  {
    if (!(isset($_POST['token']) && isset($_POST['value']) && isset($_POST['host'])))
       $message .= "ERROR: token, points, and host fields must be entered for a retrieve token.\n";
    else
    {
      $hash = $_POST['token'];
      $value = $_POST['value'];
      $host = $_POST['host'];
      $service = $_POST['service'];
      $user = $_POST['user'];
      $pass = $_POST['pass'];

      if(!preg_match('/^[1-9][0-9]{0,5}$/', $value))
        $message .= "ERROR: value must be an integer between 1-99999.";
      else
      {
        $stmt = $db->prepare('INSERT INTO token (hash, value, host, type) VALUES (?,?,?,?)');
        $stmt = $db->prepare('INSERT INTO token (hash, value, host, service, uname, pass, type) VALUES (?,?,?,?,?,?,?)');
        $stmt->bindParam(1, $hash, PDO::PARAM_STR);
        $stmt->bindParam(2, $value, PDO::PARAM_INT);
        $stmt->bindParam(3, $host, PDO::PARAM_STR);
        $stmt->bindParam(4, $service, PDO::PARAM_STR);
        $stmt->bindParam(5, $user, PDO::PARAM_STR);
        $stmt->bindParam(6, $pass, PDO::PARAM_STR);
        $stmt->bindParam(7, $type, PDO::PARAM_STR);
        $stmt->execute();
        header('Location: ./admin.php');
        die();
      }
    }
  }
  else
    $message .= "ERROR: unknown token type";
}
else //assume request method is GET
{
  if(!isset($_GET['type']))
  {
    header("Location: ./admin.php");
    die();
  }
  else
    $type = $_GET['type'];
}

include_once('./head.html');
?>
<body>
<?php include_once('./navBar.php');

//validate type and write headings
if ($type == 'c')
  echo "<h1>Add Capture Token </h1>";
else if ($type == 'r')
  echo "<h1>Add retrieve Token </h1>";
else
  fail("input error");
?>

<p> <?php echo $message; ?>
<form action="" method ="POST">
  <input type="hidden" name="type" value='<?php echo $type; ?>' >
  <table>
    <tr> <td> Token: </td> <td><input type="text" name="token" value='<?php echo generate_token(); ?>' size ="30"> </td></tr>
    <tr> <td> Points: </td> <td><input type="text" name="value" size="5"></td></tr>
    <tr> <td> host: </td> <td><input type="text" name="host" size="30"</td></tr>

    <?php if($type == "r"): ?>
    <tr> <td> service: </td> <td><input type="text" name="service" size="30"</td></tr>
    <tr> <td> username: </td> <td><input type="text" name="user" size="40"</td></tr>
    <tr> <td> password: </td> <td><input type="text" name="pass" size="40"</td></tr>
    <?php endif; ?>
  </table>
  <input type="submit" name="addToken" value="add">
  <input type="submit" name="cancel" value="cancel">
</form>
</body>
</html>

