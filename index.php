<?php
require_once('./functions.php');
start_session($session_name, true);
$db = start_db();
?>
<?php include_once('./head.html'); ?>
<body>
<?php include_once('./navBar.php'); ?>
<div>
<h2> score summary: </h2>
<?php $summary = get_summary($_SESSION['user_id'], $db); ?>
your current score is: <?php echo $summary['total_score']; ?> <br>
it has been <?php echo $summary['last_score']; ?> since your last capture <br>

<h2> services: </h2>
the following tokens must be placed available to score hosting points: <br>
<table border = "1">
<tr>
<th> Token </th>
<th> service </th>
<th> external IP </th>
<th> user </th>
<th> password </th>
</tr>
<?php
foreach ($summary['retrieve_tokens'] as $row)
{
  echo "<tr>";
  echo "<td> " . $row['token'] . " </td>";
  echo "<td> " . $row['service'] . " </td>";
  echo "<td> " . $row['ip'] . " </td>";
  echo "<td> " . $row['user'] . " </td>";
  echo "<td> " . $row['pass'] . " </td>";
  echo "</tr>";
}
?>
</table>

in order to deny your opposition retrival points you need to either DOS their services or replace thier tokens with your team token to gain their retrival points for yourself <br>
your team token is <b> <?php echo $summary['team_token']; ?>
</div>
</body>
</html>
