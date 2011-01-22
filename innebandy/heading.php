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

/* Spørring etter PlayerInfo */
	
$colname_PlayerInfo = $PlayerId;
mysql_select_db($database_innebandybase, $innebandybase);
$query_PlayerInfo = sprintf("SELECT * FROM players WHERE id = %s", $colname_PlayerInfo);
$PlayerInfo = mysql_query($query_PlayerInfo, $innebandybase) or die(mysql_error());
$row_PlayerInfo = mysql_fetch_assoc($PlayerInfo);
$totalRows_PlayerInfo = mysql_num_rows($PlayerInfo);


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>

<?php	echo '
	<script language="JavaScript" type="text/JavaScript">
	var PlayerId='.$PlayerId.';
	</script>
		 ';
?>

<script language="JavaScript" type="text/JavaScript" src="functions.js"></script>


<title>Overskrift</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="innebandy.css" rel="stylesheet" type="text/css">
</head>

<body background="images/Background1.jpg" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
  <!--DWLayoutTable-->
  <tr> 
    <td width="120" height="43">&nbsp;</td>
    <td width="381">&nbsp;</td>
    <td width="270" rowspan="3" align="right" valign="middle"> 
	<?php 
		if ($PlayerId){
			echo
			'
			<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="108" height="110">
			<param name="movie" value="images/drakt.swf?name='.$row_PlayerInfo['lastName'].'&amp;number='.$row_PlayerInfo['number'].'">
			<param name="quality" value="high">
			<param name="WMODE" value="transparent">
			<embed src="images/drakt.swf?name='.$row_PlayerInfo['lastName'].'&amp;number='.$row_PlayerInfo['number'].'" width="108" height="110" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" wmode="transparent"></embed></object>';
		} 
	?>
</td>
  </tr>
  <tr> 
    <td width="120">&nbsp;</td>
    <td><a href="http://www.sportsresultater.no/Table.asp?TournamentId=234792" target="_blank"><font size="+1">Tabell 
      og terminliste</font></a><font size="+1"> | <a href="http://www.baerum.kommune.no/bkbil/innebandy/" target="_blank">Lagets 
      hjemmeside</a> | <a href="http://www.innebandy.no/regelv.shtml" target="_top">Regler</a> 
      | <a href="http://skaar.freeshell.org/" target="_blank">Webmaster</a></font></td>
  </tr>
  <tr> 
    <td width="120">&nbsp;</td>
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



