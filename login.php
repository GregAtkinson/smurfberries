<?php
require_once('./functions.php');
start_session($session_name, true);
$db = start_db();
?>

<html>
<?php include_once('./head.html'); ?>
<body>
  <?php include_once('./navBar.php'); ?>
  <?php if (login_check($db)) : ?>
  <form action="processLogin.php" method="POST">
  <input type="hidden" name="op" value="logout">
  <input type="submit" value="logout">
  </form>
  <?php else: ?>
  <form action="processLogin.php" method="POST">
  <input type="hidden" name="op" value="login">
  teamname: <input type="text" name="user" size="50"><br>
  password: <input type="password" name="pass" size="50"><br>
  <input type="submit" value="login">
  </form>
  <?php endif; ?>
</body>
</html>
