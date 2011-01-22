<?php

require_once('Connections/innebandybase.php');
require_once('commonCode.inc');


$playerAgeControl = new DateControl_class();
$playerAgeFieldName = 'playerAge_text';
$playerAgeValue = $_POST[$playerAgeFieldName];
if ($playerAgeValue == $playerAgeControl->GetNoDateIndicatorString()){
	$dateValue='NULL';
	$dubviousAge = 1;
}else{
	$dateTimestamp = $playerAgeValue;
	$dateValue = "\"".date('Y-m-d',$dateTimestamp)."\"";
	$dubviousAge = 0;
}

/* Validere dato gammel kode
if (ValidateDateString($_POST['playerAge_text'])){
	$dateValue = '"'.date('Y-m-d',strtotime ($_POST['playerAge_text'])).'"';
	$dubviousAge = 0;
}else{
	$dateValue='NULL';
	$dubviousAge = 1;
}

*/
function ValidateDateString($dateStr){
		$out = true;
		$timeStamp = strtotime($dateStr);
		
		if($timeStamp === -1){
			$out = false;
		}
		if($timeStamp == 0){
			$out = false;
		}
		if(date("Y") - strftime("%Y",$timeStamp) < 1){
			$out = false;
		}
		
		return $out;
}

if($_POST['oppmann_chk']==1){
	$teamTrainer=1;
}else{
	$teamTrainer=0;
}

if($_POST['messages_cmb']){
/* 1 only important and replies to own, 0 no messages, 2 all messages */
	$noMessages=$_POST['messages_cmb'];
}else{
	$noMessages=0;
}

/* Registrere data i basen */
if ($_POST['playerId_hidden']){
	/* Endre eksisterende data eller slette spiller*/
	if ($_POST['deletePlayer_hidden']){
		require_once('Connections/innebandybase.php');
		$query_Update = '
			DELETE FROM players WHERE id='.$_POST['playerId_hidden'];
		$query_Update2 = '
			DELETE FROM attention WHERE player='.$_POST['playerId_hidden'];
		$query_Update3 = '
			DELETE FROM membership WHERE player='.$_POST['playerId_hidden'];

			/* Sletter spillerens medlemskap i alle lag */
			$Update3 = mysql_query($query_Update3, $innebandybase) or die(mysql_error());
			$row_Update3 = mysql_fetch_assoc($Update3);
			$totalRows_Update3 = mysql_num_rows($Update3); 

			/* Sletter alle bekreftelser spilleren har i ulike hendelser */
			$Update2 = mysql_query($query_Update2, $innebandybase) or die(mysql_error());
			$row_Update2 = mysql_fetch_assoc($Update2);
			$totalRows_Update2 = mysql_num_rows($Update2); 
			
			/* Sletter spiller fra spillertabellen */
			$Update = mysql_query($query_Update, $innebandybase) or die(mysql_error());
			$row_Update = mysql_fetch_assoc($Update);
			$totalRows_Update = mysql_num_rows($Update); 
		

			
			$playerId= 0;
			$ResultText='Spilleren er slettet fra basen.';
			setcookie("Player",0);
			
			mysql_free_result($Update);
			mysql_free_result($Update2);
			mysql_free_result($Update3);
	}else{
		$query_Update = '
			UPDATE players
			SET firstName = "'.strip_tags($_POST['playerFirstName_text']).'",
			lastName = "'.strip_tags($_POST['playerLastName_text']).'",
			residence = "'.strip_tags($_POST['playerResidence_text']).'",
			phone = "'.strip_tags($_POST['playerPhone_text']).'",
			email = "'.strip_tags($_POST['playerEmail_text']).'",
			age = '.$dateValue.',
			url = "'.strip_tags($_POST['playerUrl_text']).'",
			picture = "'.strip_tags($_POST['playerPicture_text']).'",
			dubviousAge = '.$dubviousAge.',
			lastLogin = NOW()
			WHERE id = "'.$_POST['playerId_hidden'].'"';
			
		$query_UpdateMembership = '
			UPDATE membership
			SET
			number = "'.strip_tags($_POST['playerNumber_text']).'",
			reminders = '.$_POST['reminders_hidden'].',
			teamTrainer = '.$teamTrainer.',
			noMessageByEmail = '.$noMessages.',
			notes = "'.strip_tags($_POST['playerNotes_text']).'"
			WHERE (player="'.$_POST['playerId_hidden'].'") AND (team="'.$_POST['teamId_hidden'].'")';
			$Update = mysql_query($query_Update, $innebandybase) or die(mysql_error());
			$row_Update = mysql_fetch_assoc($Update);
			$totalRows_Update = mysql_num_rows($Update); 

			$UpdateMembership = mysql_query($query_UpdateMembership, $innebandybase) or die(mysql_error());
			$row_UpdateMembership = mysql_fetch_assoc($UpdateMembership);
			$totalRows_UpdateMembership = mysql_num_rows($UpdateMembership); 
			
			$playerId= $_POST['playerId_hidden'];
			$ResultText=strip_tags($_POST['playerFirstName_text'].' '.$_POST['playerLastName_text'].'  er oppdatert.'); 
			
			mysql_free_result($Update);
			mysql_free_result($UpdateMembership);
	}
}else{
	/* Registrere ny spiller */
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_MaxId = "SELECT MAX(id) FROM players;";
	$MaxId = mysql_query($query_MaxId, $innebandybase) or die(mysql_error());
	$row_MaxId = mysql_fetch_assoc($MaxId);
	$totalRows_MaxId = mysql_num_rows($MaxId);
 
	$playerId=$row_MaxId['MAX(id)']+1;	

  	$query_InsertPlayer = '
	    INSERT INTO players SET
		id='.$playerId.',
		firstName="'.strip_tags($_POST['playerFirstName_text']).'",
		lastName="'.strip_tags($_POST['playerLastName_text']).'",
		residence="'.strip_tags($_POST['playerResidence_text']).'",
		phone="'.strip_tags($_POST['playerPhone_text']).'",
		email="'.strip_tags($_POST['playerEmail_text']).'",
		age='.$dateValue.',
  		picture = "'.strip_tags($_POST['playerPicture_text']).'",
		url="'.strip_tags($_POST['playerUrl_text']).'",
		dubviousAge = '.$dubviousAge.',
		lastLogin = NOW()
		;';
		
	$query_InsertMembership='
		INSERT INTO membership
		SET 
		number="'.strip_tags($_POST['playerNumber_text']).'",
		reminders = '.$_POST['reminders_hidden'].',
		notes="'.strip_tags($_POST['playerNotes_text']).'",
		noMessageByEmail = '.$noMessages.',
		player="'.$playerId.'",
		teamTrainer = '.$teamTrainer.',
		team="'.$_POST['teamId_hidden'].'"
		';
		
        $InsertPlayer = mysql_query($query_InsertPlayer, $innebandybase) or die(mysql_error());
		$row_InsertPlayer = mysql_fetch_assoc($InsertPlayer);
		$totalRows_InsertPlayer = mysql_num_rows($InsertPlayer); 

        $InsertMembership = mysql_query($query_InsertMembership, $innebandybase) or die(mysql_error());
		$row_InsertMembership = mysql_fetch_assoc($InsertMembership);
		$totalRows_InsertMembership = mysql_num_rows($InsertMembership); 
	
		$ResultText=strip_tags($_POST['playerFirstName_text'].' '.$_POST['playerLastName_text'].'  er lagt inn i databasen.');

	mysql_free_result($InsertPlayer);
	mysql_free_result($InsertMembership);
	mysql_free_result($MaxId);
}





?>
<html>
<head>

<meta http-equiv=REFRESH content=4;url="playerlist.php?Player=<?php echo $playerId ?>">
<link href="innebandy.css" rel="stylesheet" type="text/css">
</head>


<body>
<h1>Databasen er oppdatert</h1>

<hr>
<p><?php echo $ResultText; ?> </p>
<p><a href="playerlist.php?Player=<?php echo $playerId ;?>">Klikk her for &aring; g&aring; tilbake.</a></p>
<p><img src="images/score.jpg" width="300" height="273"></p>

</body>
</html>