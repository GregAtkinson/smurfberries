<?php
  $logged_in = login_check($db);
  if ($logged_in)
    $name = $_SESSION['teamname'];
  else
    $name = 'guest';
?>

<div id="titlebar"> <img src='images/smurfberries.png'/></div>
<div id="navbar">
  <span>welcome <?php echo $name; ?></span>
  <div id="navLinks">
    <a href="./index.php">Home</a>
    <a href="#score">Scoreboard</a>
    <?php if(!$logged_in): ?>
    <a href="./login.php">Login</a>
    <?php else: ?>
    <a href="./login.php">Logoff</a>
    <?php endif; ?>
  </div>
</div>

