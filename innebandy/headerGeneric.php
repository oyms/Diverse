<?php 
require_once('Connections/innebandybase.php'); 
require_once('commonCode.inc');

$player = new Player_class();
$team = new Team_class();

$PlayerId = $player->getId();
$teamId = $team->get_id();
$teamName = $team->getName("long");
$teamPassword = $team->getName("password");
$teamPassword = $team->getName("userName");
	

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
<style type="text/css">
<!--
h1 {
	padding-bottom: 0px;
}
-->
</style>
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table class="headerBackground" width="100%" height="100%">
  <!--DWLayoutTable-->
  <tr> 
    <td width="14" height="14"></td>
    <td width="566"></td>
    <td width="80"></td>
    <td width="126"></td>
    <td width="207" rowspan="3" align="right" valign="top"> 
      <?php 
		if ($PlayerId){
			$lastName = $player->getName("last");
			$lastNameCapitals = preg_replace("/([a-z\xE0-\xFF])/e","chr(ord('\\1')-32)",$lastName);
			$number = $player->getNumber("raw");
			echo
			'
			<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="108" height="110">
			<param name="movie" value="images/draktGeneric.swf?name='.$lastNameCapitals.'&number='.$number.'">
			<param name="quality" value="high">
			<param name="WMODE" value="transparent">
			<embed src="images/draktGeneric.swf?name='.$lastName.'&number='.$number.'" width="108" height="110" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" wmode="transparent"></embed></object>';
		} 
	?></td>
  </tr>
  <tr>
    <td height="75"></td>
    <td colspan="2" valign="middle">
	<h1><?php 
		echo($teamName);
		if($row_PlayerInfo['firstName']){
				echo(" &ndash; ".$player->getName("first")) ;
			}
		
		?>
		
	</h1>
	<p><font size="+1">
	<?php
	
	// Links to homepage and results
	
	$homepageLink = $team->getWebPageLink();
	$resultsPageLink = $team->getResultsPageLink();
	
	if($homepageLink){
		echo $homepageLink, " | ";
	}
	if($resultsPageLink){
		echo $resultsPageLink, " | ";
	}
	
	//RSS-link
	echo RSSlink(), " | ";

  	echo HomeIconLink("absmiddle");
  	echo ("\n | \n"); 
	
	?>
<a href="mailto:skaar@bigfoot.com" target="_blank">Vevmester</a> | <a href="javascript:Favourite();">Lagre
       som favoritt</a></font></p></td>
    <td valign="middle">&nbsp;</td>
  </tr>
  <tr>
    <td height="155"></td>
    <td>&nbsp;</td>
    <td></td>
    <td></td>
  </tr>
  <tr> 
    <td height="3" colspan="2"><img src="images/spacer.gif" alt="" width="12" height="1"></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
</table>
</body>
</html>