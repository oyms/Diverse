<?php require_once('Connections/innebandybase.php'); ?>
<?php
mysql_select_db($database_innebandybase, $innebandybase);
$query_hentePlayerData = "SELECT * FROM players;";
$hentePlayerData = mysql_query($query_hentePlayerData, $innebandybase) or die(mysql_error());
$row_hentePlayerData = mysql_fetch_assoc($hentePlayerData);
$totalRows_hentePlayerData = mysql_num_rows($hentePlayerData);



mysql_free_result($hentePlayerData);



 do { 
	$playerId=$row_hentePlayerData['id'];
	$playerNotes=$row_hentePlayerData['notes'];
	$playerNumber=$row_hentePlayerData['number'];
	
	$query_updatemembership = "UPDATE membership
								SET notes='$playerNotes',
								number='$playerNumber'
								WHERE player='$playerId';";
	$updatemembership = mysql_query($query_updatemembership, $innebandybase) or die(mysql_error());
	$row_updatemembership = mysql_fetch_assoc($updatemembership);
	$totalRows_updatemembership = mysql_num_rows($updatemembership);
	
	mysql_free_result($updatemembership);
	
	 } while ($row_hentePlayerData = mysql_fetch_assoc($hentePlayerData));
	 
	 

?>