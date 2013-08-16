<div class = "navbar" name="navigation">
<?php
  $logged_in = login_check($db);
  if ($logged_in)
    $name = $_SESSION['teamname'];
  else
    $name = 'guest';
?>
  <ul>
  welcome <?php echo $name; ?>
  <li><a href="#home">Home</a></li>
  <li><a href="#score">Scoreboard</a></li>
  <?php if(!$logged_in): ?>
  <li><a href="./login.php">Login</a></li>
  <?php else: ?>
  <li><a href="./login.php">Logoff</a></li>
  <?php endif; ?>
  </ul>
</div>

