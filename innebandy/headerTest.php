<?php require_once('Connections/innebandybase.php'); ?> 
<?php
	/* Leser i URL evt Cookie om PlayerID er satt. */ 
	if ($_GET["Player"]){
		$PlayerId=$_GET["Player"];
	}else if ($_COOKIE["Player"]){
		$PlayerId=$_COOKIE["Player"];
	}else{
		$PlayerId=0;
	}
	
/* Finner team */

$authUserName=$_SERVER['PHP_AUTH_USER'];

mysql_select_db($database_innebandybase, $innebandybase);
$query_teamInfo = "SELECT * FROM teams WHERE userName='$authUserName';";
$teamInfo = mysql_query($query_teamInfo, $innebandybase) or die(mysql_error());
$row_teamInfo = mysql_fetch_assoc($teamInfo);
$totalRows_teamInfo = mysql_num_rows($teamInfo);

$teamId=$row_teamInfo['id'];
$teamName=$row_teamInfo['longName'];
$teamPassword=$row_teamInfo['password'];
$teamUserName=$row_teamInfo['userName'];

mysql_free_result($teamInfo);

/* Spørring etter PlayerInfo */
	
$colname_PlayerInfo = $PlayerId;
mysql_select_db($database_innebandybase, $innebandybase);
$query_PlayerInfo = sprintf("SELECT players.id, players.firstName, players.lastName, players.residence, membership.notes, players.phone, membership.number, players.age, players.age, players.email, players.url
FROM players INNER JOIN membership ON players.id = membership.player
WHERE (((players.id)=%s) AND ((membership.team)=%s));", $PlayerId, $teamId);

$PlayerInfo = mysql_query($query_PlayerInfo, $innebandybase) or die(mysql_error());
$row_PlayerInfo = mysql_fetch_assoc($PlayerInfo);
$totalRows_PlayerInfo = mysql_num_rows($PlayerInfo);


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>

<?php	echo "
	<script language='JavaScript' type='text/JavaScript'>
	var PlayerId=$PlayerId;
	
	function Favourite () {
			window.external.AddFavorite('http://$teamUserName:$teamPassword@skaar.freeshell.org/innebandy/', '$teamName');
	}
	</script>
		 ";
?>

<script language="JavaScript" type="text/JavaScript" src="functions.js"></script>


<title>Overskrift</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="innebandy.css" rel="stylesheet" type="text/css" />
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
 <h1>Team &laquo;Testing&raquo;</h1>
 <p><?php
 echo ($row_PlayerInfo['firstName']);
 ?></p>
</body>
</html>
<?php
mysql_free_result($PlayerInfo);
?>



