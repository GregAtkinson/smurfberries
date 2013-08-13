<?php
include_once('./functions.php');
start_session('_s', true);
if (!(login_check() && admin_check()))
{
header('Location: ./index.php');
die();
}
?>
you are allowed to see this page //TODO remove line after testing
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
  <table>
  <tr>
  <td>&nbsp;Token</td>
  <td?&nbsp;Points</td>
  <td>&nbsp;Host</td>
  <tr>
  </table>
  <?php
  $tokens = get_capture_tokens();
  foreach($token as $row)
  {
    echo "<form method='GET' action='update.php'\n";
    echo "  <input type='text' value=".$row['token']." name='token'>\n";
    echo "  <input type='text' value=".$row['points']." name='points'>\n";
    echo "  <input type='text' value=".$row['host']." name='host'>\n";
    echo "  <input type='image' src='images/update.png' alt='Update Row' title='Update Row'>\n";
    echo "<a href='delete.php?token=".$row['token']."><image title='Delete Row' alt='Delete' src='images/delete.png'/></a></form>\n";
  }


