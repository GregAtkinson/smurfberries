<?php
include('./papaSmurf.conf.php');

function fail($pub, $pvt = '')
{
  global $debug;
  $msg = $pub;
  if ($debug && $pvt !== '')
    $msg .= ": $pvt";
  /* The $pvt debugging messages may contain chacters that need to
   * be escaped or quoted when producing HTML output.
   * Also debug should be set to false on a production system
   */
  exit("An error occured ($msg).\n");
}

$db = new mysqli($db_host, $db_adm_user, $db_adm_pass, $db_name, $db_port);
if (mysqli_connect_errno())
  fail('MySQL connect', mysqli_connect_error());

//get array of current tables
$db->query('SET forign_key_checks = 0');
if ($result = $db->query("SHOW TABLES"))
{
  while($row = $result->fetch_array(MYSQLI_NUM))
  {
    echo "dropping ". $row[0]. "...";
    if ($db->query('DROP TABLE IF EXISTS '.$row[0]))
      echo "    sucess</br>\n";
    else
      fail('MySQL Query', $db->error());
  }
}

//now create tables.
$table_stmts = array();
$table_stmts['user'] =
'CREATE TABLE user
(
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  pass VARCHAR(60) NOT NULL,
  isadmin BOOLEAN NOT NULL DEFAULT 0,
  lastCapture VARCHAR(30),
  UNIQUE(name),
  PRIMARY KEY(id)
)';

$table_stmts['token'] =
'CREATE TABLE token
(
  id INT NOT NULL AUTO_INCREMENT,
  hash CHAR(40) NOT NULL,
  value INT NOT NULL,
  type CHAR(1) NOT NULL,
  PRIMARY KEY(id)
)';

$table_stmts['capture'] =
'CREATE TABLE capture
(
  time VARCHAR(30) NOT NULL,
  team_id INT NOT NULL,
  token_id INT NOT NULL
)';

$table_stmts['loginAttempt'] =
'CREATE TABLE loginAttempt
(
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  time VARCHAR(30) NOT NULL,
  PRIMARY KEY(id)
)';

$table_stmts['session'] =
'CREATE TABLE session
(
  sid VARCHAR(30) NOT NULL,
  user_id INT NOT NULL,
  ip VARCHAR(20) NOT NULL,
  userAgent VARCHAR(40),
  UNIQUE(sid),
  PRIMARY KEY(sid)
)';

foreach($table_stmts as $name=>$stmt)
{
  if ($db->query($stmt))
    echo $name . " table created<br>\n";
  else
    echo "ERROR: " . $name . " table creation FAILED:  " . $db->error . "<br>\n";
}
