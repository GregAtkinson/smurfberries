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

?>

<?php include_once('./head.html'); ?>
<body>
<?php include_once('./navBar.php'); ?>

<div>
  <h2> Add Team </h2>
  <form action = processAdmin.php>
  <input type='hidden' name='op' value='addTeam'>
  teamname: <input type='text' name='user' size='40'><br>
  password: <input type='text' name='pass' size='40'><br> //TODO find a beter process for this currently the use case is team submits name:pass to admin how creates acccount better would be admin subits one time token to each team which allows the registration of a new user via webform.
  <input type='submit' value='create team'>
  </form>
  NOTE: this could be done better admin creates one time token which is passed to team to allow them to create a new team via registration form.
</div>

<div>
  <h2> Capture tokens </h2>
  <table class="tokenTable">
  <tr>
  <th>&nbsp;Action</th>
  <th>&nbsp;ID</th>
  <th>&nbsp;Token</th>
  <th>&nbsp;Points</th>
  <th>&nbsp;Host</th>
  <?php
  $tokens = get_capture_tokens($db);
  while($row = $tokens->fetch(PDO::FETCH_ASSOC))
  {
    echo "<tr>\n";
    echo "<td> <a href='editToken.php?id=".$row['id']."'> <img src='images/edit_btn.png' height='15' width='16' border='0' alt='edit' title='Edit'/></a>&nbsp;<a href='deleteToken.png?id=".$row['id']."'><img src='images/delete_btn.png' height='15' width='16' border='0' alt='delete' title='Delete'/></a></td>\n";
    echo "<td>".$row['id']."</td>\n";
    echo "<td>".$row['hash']."</td>\n";
    echo "<td>".$row['value']."</td>\n";
    echo "<td>".$row['host']."</td>\n";
    echo "</tr>\n";
  }
  ?>
  </table>
  <form action="addToken.php" method="GET">
    <input type="hidden" name="type" value="c">
    <input type="submit" value="add">
  </form>
</div>
<div>
  <h2> Retrieve tokens </h2>
  <table>
  <tr>
  <th>&nbsp;Action</th>
  <th>&nbsp;Token</th>
  <th>&nbsp;Points</th>
  <th>&nbsp;Host</th>
  <th>&nbsp;service</th>
  <th>&nbsp;user</th>
  <th>&nbsp;password</th>
  <?php
  $tokens = get_retrieve_tokens($db);
  while($row = $tokens->fetch(PDO::FETCH_ASSOC))
  {
    echo "<tr>\n";
    echo "<td> <a href='editToken.php?id=".$row['id']."'> <img src='images/edit_btn.png' height='15' width='16' border='0' alt='edit' title='Edit'/></a>&nbsp;<a href='deleteToken?id=".$row['id']."'><img src='images/delete_btn.png' height='15' width='16' border='0' alt='delete' title='Delete'/></a></td>\n";
    echo "<td>".$row['hash']."</td>\n";
    echo "<td>".$row['value']."</td>\n";
    echo "<td>".$row['host']."</td>\n";
    echo "<td>".$row['service']."</td>\n";
    echo "<td>".$row['uname']."</td>\n";
    echo "<td>".$row['pass']."</td>\n";
    echo "</tr>\n";
  }
  ?>
  </table>
  <form action="addToken.php" method="GET">
    <input type="hidden" name="type" value="r">
    <input type="submit" value="add">
  </form>


</body>
</html>

