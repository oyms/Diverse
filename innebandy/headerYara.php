<?php require_once('Connections/innebandybase.php'); ?> 
<?php require_once('commonCode.inc');
	/* Leser i URL evt Cookie om PlayerID er satt. */ 
	if ($_GET[$playerIdFieldName]){
		$PlayerId=$_GET[$playerIdFieldName];
	}else if ($_COOKIE[$playerIdFieldName]){
		$PlayerId=$_COOKIE[$playerIdFieldName];
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
$query_PlayerInfo = sprintf("SELECT players.id, players.firstName, players.lastName, players.residence, membership.notes, players.phone, membership.number, players.age, players.email, players.url
FROM players INNER JOIN membership ON players.id = membership.player
WHERE (((players.id)='%s') AND ((membership.team)='%s'));", $PlayerId, $teamId);


$PlayerInfo = mysql_query($query_PlayerInfo, $innebandybase) or die(mysql_error());
$row_PlayerInfo = mysql_fetch_assoc($PlayerInfo);
$totalRows_PlayerInfo = mysql_num_rows($PlayerInfo);



?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>

<?php	
echo RenderCSSLink($teamId);
echo "
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
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
  <!--DWLayoutTable-->
  <tr> 
    <td width="103" background="images/YaraHeaderBackground.gif" ><img src="images/spacer.gif" width="40" height="40"></td>
    <td width="736"><font size="+1"><a href="http://www.innebandy.no/spilleregler.asp" target="_top">Regler</a> 
      </font>|<font size="+1">&nbsp; </font><font size="+1"><a href="mailto:skaar@bigfoot.com" target="_blank">Vevmester</a> 
      | <a href="javascript:Favourite();">Lagre som favoritt</a>
      <?PHP 	
	//RSS-link
	echo  " | ", RSSlink(); 
  	echo ("\n | \n"); 
  	echo HomeIconLink("absmiddle");
	?>
	</font></td>
    <td width="458" rowspan="2" align="right" valign="middle"> 
      <?php 
		if ($PlayerId){
			echo
			'
			<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="108" height="110">
			<param name="movie" value="images/draktGeneric.swf?name='.$row_PlayerInfo['lastName'].'&number='.$row_PlayerInfo['number'].'">
			<param name="quality" value="high">
			<param name="WMODE" value="transparent">
			<embed src="images/draktGeneric.swf?name='.$row_PlayerInfo['lastName'].'&number='.$row_PlayerInfo['number'].'" width="108" height="110" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" wmode="transparent"></embed></object>';
		} 
	?>    </td>
  </tr>
  
  <tr> 
    <td width="103">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr> 
    <td height="1"><img src="images/spacer.gif" alt="" width="103" height="1"></td>
    <td></td>
    <td></td>
  </tr>
</table>
</body>
</html>
<?php
mysql_free_result($PlayerInfo);
?>



