<?php
  $logged_in = login_check($db);
  if ($logged_in)
    $name = $_SESSION['teamname'];
  else
    $name = 'guest';
?>

<div id="titlebar"> 
  <img src='images/smurfberries.png'/>
  <div id="navbar">
    <ul>
      <span>welcome <?php echo $name; ?></span>
      <li><a href="./index.php">Home</a></li>
      <li><a href="#score">Scoreboard</a></li>
      <?php if(!$logged_in): ?>
      <li><a href="./login.php">Login</a></li>
      <?php else: ?>
      <li><a href="./login.php">Logoff</a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>


