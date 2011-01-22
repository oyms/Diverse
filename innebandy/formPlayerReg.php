<?php 
	
	require_once('Connections/innebandybase.php');
	require_once('commonCode.inc');

	/* Finner team */

	$authUserName=$_SERVER['PHP_AUTH_USER'];
	
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_teamInfo = "SELECT id FROM teams WHERE userName='$authUserName';";
	$teamInfo = mysql_query($query_teamInfo, $innebandybase) or die(mysql_error());
	$row_teamInfo = mysql_fetch_assoc($teamInfo);
	$totalRows_teamInfo = mysql_num_rows($teamInfo);
	
	$teamId=$row_teamInfo['id'];
	
	mysql_free_result($teamInfo);

/* Henter EventTypes */

mysql_select_db($database_innebandybase, $innebandybase);
$query_eventTypes = "
	SELECT id, type, iconFile, requiredCount 
	FROM eventTypes
	ORDER BY listOrder
	";
$eventTypes = new DBConnector_class($query_eventTypes);

function DrawReminderCheckBoxes($reminderValue,$eventQuery){
	$output="";
	$output .= "<input name='reminders_hidden' type='hidden' id='reminders_hidden' value='" . $reminderValue . "'>"; 
	$output .= "<table><tr>";
	$chkIndex=0;
	$numberOfEvents = $eventQuery->GetNumberOfRows();
	$eventsPerRow = ($numberOfEvents % 3 == 0) ? 3 : 4;
	while ($event = $eventQuery->GetNextRow()){
		$output .= (($chkIndex % $eventsPerRow == 0)&&($chkIndex>0)) ? "</tr><tr>" : "";
		$eventId = $event["id"];
		$checkValue = pow (2, $eventId);
		($reminderValue & $checkValue) == $checkValue ? $checked = "" : $checked = "checked";
		$output .= "<td>";
		$output .= $event["type"];
		$output .= "<input name='reminder" . $chkIndex . "_chk' type='checkbox' value='" . $checkValue . "' " . $checked . ">";
		$output .= "&nbsp;</td>";
		$chkIndex +=1;
	}
	
	$output .= "</tr></table>";
	$output .= "\n";
	$output .= "<script language='JavaScript' type='text/JavaScript'>
function setReminderValue() {
	value = 0;
	for (var chkIndex=0 ; chkIndex < " . $chkIndex . " ; chkIndex++){
		chkBoxName = 'reminder' + chkIndex + '_chk';
		chkBox = eval('document.forms[0].' + chkBoxName);
		if (! chkBox.checked){
			value += eval(chkBox.value);
		}
	}
	document.forms[0].reminders_hidden.value=value;
}
</script>";
	
	
	return $output;
}

if (isset($_GET[$playerIdFieldName])) {
  $colname_SpillerInfo = (get_magic_quotes_gpc()) ? $_GET[$playerIdFieldName] : addslashes($_GET[$playerIdFieldName]);
}else{
	$colname_SpillerInfo = "0";
};
mysql_select_db($database_innebandybase, $innebandybase);
$query_SpillerInfo = sprintf("
	SELECT players.id, players.firstName, players.lastName, 
			players.residence, membership.notes, players.phone,
			membership.number, players.age, players.dubviousAge, 
			players.email, players.url, players.picture, membership.reminders,
			membership.teamTrainer, membership.noMessageByEmail
	FROM players INNER JOIN membership ON players.id = membership.player
	WHERE (((players.id)=%s) 
			AND ((membership.team)=%s));", $colname_SpillerInfo, $teamId);

$SpillerInfo = mysql_query($query_SpillerInfo, $innebandybase) or die(mysql_error());
$row_SpillerInfo = mysql_fetch_assoc($SpillerInfo);
$totalRows_SpillerInfo = mysql_num_rows($SpillerInfo);
if (isset($_GET[$playerIdFieldName])) {
	$id = $row_SpillerInfo['id'];
	$fornavn = $row_SpillerInfo['firstName'];
	$etternavn = $row_SpillerInfo['lastName'];
	$draktnummer = $row_SpillerInfo['number'];
	$bosted = $row_SpillerInfo['residence'];
	$telefon = $row_SpillerInfo['phone'];
	$epostadresse = $row_SpillerInfo['email'];
	$fodselsdato = $row_SpillerInfo['age'];
	$ingenfodselsdato = $row_SpillerInfo['dubviousAge'];
	$merknader = $row_SpillerInfo['notes'];
	$url = $row_SpillerInfo['url'];
	$remindersValue = $row_SpillerInfo['reminders'];
	$teamTrainer = $row_SpillerInfo['teamTrainer'];
	$noMessageByEmail = $row_SpillerInfo['noMessageByEmail'];
	$bilde = $row_SpillerInfo['picture'];
}else{
	$id = "";
	$fornavn = "";
	$etternavn = "";
	$draktnummer = "";
	$bosted = "";
	$telefon = "";
	$epostadresse = "";
	$fodselsdato = "";
	$merknader = "";
	$url = "";
	$remindersValue = 0;
	$teamTrainer = 0;
	$noMessageByEmail = 1;
	$bilde = "";
}
?>
<html>
<head>
<title>Spillerregistrering</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_validateForm() { //v4.0
  var i,p,q,nm,test,num,min,max,errors='',args=MM_validateForm.arguments;
  for (i=0; i<(args.length-2); i+=3) { test=args[i+2]; val=MM_findObj(args[i]);
    if (val) { nm=val.name; if ((val=val.value)!="") {
      if (test.indexOf('isEmail')!=-1) { p=val.indexOf('@');
        if (p<1 || p==(val.length-1)) errors+='- '+nm+' skal inneholde en e-postadresse.\n';
      } else if (test!='R') { num = parseFloat(val);
        if (isNaN(val)) errors+='- '+nm+' must contain a number.\n';
        if (test.indexOf('inRange') != -1) { p=test.indexOf(':');
          min=test.substring(8,p); max=test.substring(p+1);
          if (num<min || max<num) errors+='- '+nm+' must contain a number between '+min+' and '+max+'.\n';
    } } } else if (test.charAt(0) == 'R') errors += '- '+nm+' skal fylles ut.\n'; }
  } if (errors) alert('Denne/disse feil ble oppdaget:\n'+errors);
  document.MM_returnValue = (errors == '');
}
//-->
</script>
<?php echo '
<script language="JavaScript" type="text/JavaScript">
<!--
function DeletePlayer(){
	Warningtext="Er du ganske sikker på \nat du vil slette '.$fornavn.' '.$etternavn.'? \nDu kan ikke angre på dette.";
	if(confirm(Warningtext)){
		document.forms[0].deletePlayer_hidden.value="true";
		document.forms[0].submit();
	}else{
		document.forms[0].deletePlayer_hidden=false;
	}
}
//-->
</script>
';
echo RenderCSSLink($teamId);
?>
</head>

<body>

<?php 
echo SelectPlayerFromOtherTeam();



?>

<form action="registerPlayer.php" method="post" name="newPlayer_form" id="newPlayer_form">
  <?php if ($_GET[$playerIdFieldName]){
			echo '
				<input name="deletePlayer_button" type="button" 
				id="deletePlayer_button" value="Slette spiller fra databasen"
				onClick="DeletePlayer()">
				<input name="deletePlayer_hidden" type="hidden" value="">';
			 
			echo DeleteOtherMembership();
			 
			echo '<p>Eller rediger feltene nedenfor og klikk knappen nederst i vinduet.</p>
			    <hr>
				';
		}else{
			echo '	<p>I skjemaet nedenfor <em>m&aring;</em> du fylle ut fornavn og etternavn. Om du 
  					legger inn e-postadressen, vil du f&aring; meldinger om endringer i hendelser 
  					du har registrert deg i. 
					Du vil ogs&aring; f&aring; p&aring;minnelser fra systemet.</p>
					<hr>';
		}
  ?>
  <fieldset><legend>Navn</legend> 
  <table width="100%" border="0" cellpadding="0">

    <tr title='Du m&aring; fylle ut fornavnet ditt.'> 
      <td><em>Fornavn:</em></td>
      <td><input name="playerId_hidden" type="hidden" id="playerId_hidden" value="<?php echo $id ?>"> 
	  		<input name="teamId_hidden" type="hidden" id="teamId_hidden" value="<?php echo $teamId ?>">
        <input name="playerFirstName_text" type="text" id="playerFirstName_text" size="50" maxlength="50" value="<?php echo $fornavn ?>"></td>
    </tr>
    <tr title='Du m&aring; fylle ut etternavnet ditt.'> 
      <td><em>Etternavn:</em></td>
      <td><input name="playerLastName_text" type="text" id="playerLastName_text" size="50" maxlength="50" value="<?php echo $etternavn ?>"></td>
    </tr>
	</table>
	</fieldset>
	<fieldset><legend>Kontaktinformasjon</legend>
	<table width="100%" border="0" cellpadding="0">
	 <tr title='Skriv inn e-postadressen din for at du skal kunne motta p&aring;minnelser og for at andre p&aring; laget skal kunne sende deg beskjeder.'> 
      <td>Epostadresse:</td>
      <td><input name="playerEmail_text" type="text" id="playerEmail_text" size="50" maxlength="50" value="<?php echo $epostadresse ?>"></td>
    </tr>
	<tr title='Om du ikke vil motta p&aring;minnelser per e-post om nye hendelser, fjerner du kryssene her.'>
	<td>E-postp&aring;minnelser: </td>
	<td>
	<?php echo DrawReminderCheckBoxes($remindersValue,$eventTypes) ?>
	</td>
	</tr>
	<tr title='Om du ikke vil motta p&aring;minnelser per e-post fra meldingslisten, fjerner du kryssene her.'>
	  <td>E-post fra meldingslisten: </td>
	  <td>
      <select name="messages_cmb" id="messages_cmb">
        <option value="1"<?php if($noMessageByEmail==1){echo(" selected");}?>>Bare viktige meldinger og svar til mine innlegg</option>
        <option value="0"<?php if($noMessageByEmail==0){echo(" selected");}?>>Ingen meldinger</option>
        <option value="2"<?php if($noMessageByEmail==2){echo(" selected");}?>>Alle meldinger</option>
      </select></td>
	  </tr>
	</table>
	
	</fieldset>
	<fieldset><legend>Annen informasjon</legend>
	  <table width="100%" border="0" cellpadding="0">
    <tr title='Om det st&aring;r et tall p&aring; ryggen av skjorta di, kan du skrive det inn her.'> 
      <td>Draktnummer:</td>
      <td><input name="playerNumber_text" type="text" id="playerNumber_text" size="20" maxlength="20" value="<?php echo $draktnummer ?>"></td>
    </tr>
    <tr title='Om det er aktuelt &aring; kj&oslash;re sammen til kamp eller trening er det greit for de andre p&aring; laget &aring; vite omtrent hvor du bor.'> 
      <td>Bosted:</td>
      <td><input name="playerResidence_text" type="text" id="playerResidence_text" size="50" maxlength="50" value="<?php echo $bosted ?>"></td>
    </tr>
    <tr title='Telefonnummeret andre kan n&aring; deg p&aring;.'> 
      <td>Telefon:</td>
      <td><input name="playerPhone_text" type="text" id="playerPhone_text" size="50" maxlength="50" value="<?php echo $telefon ?>"></td>
    </tr>
   
    <tr title='F&oslash;dselsdato legges inn i deltakerlistene til kamper, slik at dette kan skrives av n&aring;r dommerkortet skal fylles ut. I tillegg vil det st&aring; p&aring; sidene om du har f&oslash;dselsdag.'> 
      <td>F&oslash;dselsdato:</td>
      <td>
      <?php
function BirthDayControls($currentDate, $noDateSet){
	$dateControl = new DateControl_class();	
	$rightNow = time();
	$switchSetting = $noDateSet ? 1 : 2; 
	$earliestDate = $rightNow - (60*60*24*365.25*110);
	$latestDate = $rightNow - (60*60*24*365.25*12);
	if ($currentDate == ""){
		$currentDateStamp = $rightNow - (60*60*24*365.25*29);
	}else{
		$currentDateStamp = strtotime($currentDate);
	}
	$dateControl->SetName('playerAge_text');
	$dateControl->SetCurrentValue($currentDateStamp);
	$dateControl->SetTimeControl(false);
	$dateControl->SetEarliestDate($earliestDate);
	$dateControl->SetLatestDate($latestDate);
	$dateControl->SetOptional($switchSetting);
	$out = $dateControl->RenderControl();
	return $out;
}
      
      
      echo BirthDayControls($fodselsdato, $ingenfodselsdato);?>
      </td>
    </tr>
    <tr title='Har du et motto, kan du skrive det inn her.'> 
      <td>Motto:</td>
      <td><input name="playerNotes_text" type="text" id="playerNotes_text" size="50" value="<?php echo $merknader ?>"></td>
    </tr>
    <tr title='Om du har en hjemmeside kan du legge inn adressen her. Skriv inn alt etter http:// i adressen.'>
      <td>Web-adresse:</td>
      <td>http:// 
        <input name="playerUrl_text" type="text" id="playerUrl_text" size="40" value="<?php echo $url ?>"></td>
    </tr>
    <tr title='Om du er oppmann for laget, kan du sette kryss i denne ruten.'>
      <td>Oppmann/trener:</td>
      <td><input type="checkbox" name="oppmann_chk" value="1" <?php 
	  if ($teamTrainer){
		  echo "checked";
		  } ?>></td>
    </tr>
    <tr title='Her kan du skrive eller lime inn URL (adresse) til et bilde av deg selv. Bildet må ligge tilgjengelig et sted på Internet.	Du m&aring; legge inn hele adressen med protokoll (vanligvis http://). Bildet m&aring; v&aelig;re av typen jpg, gif eller png, og b&oslash;r ikke ha for store dimensjoner. Klikk test-knappen for &aring; se om adressen fungerer.'>
    	<td>Adresse til selvportrett:</td>
    	<td>
    		<input name="playerPicture_text" type="text" id="playerPicture_text" size="20" value="<?php echo $bilde ?>">
    		<input name="testPicture_button" type="button" value="&lt; Test bilde" onClick="swapImage();">
    	</td>
    </tr>
  </table>
  	<script language="javascript">
		function swapImage(){
			document.getElementById('portrett').src=document.getElementById('playerPicture_text').value;
		}
	</script>
  </fieldset>
  <p> 
    <input name="playerSubmit_button" type="submit" id="playerSubmit_button3" onClick="MM_validateForm('playerFirstName_text','','R','playerLastName_text','','R','playerEmail_text','','NisEmail');setReminderValue();return document.MM_returnValue" value="Lagre informasjon">
    <input name="playerCancel_button" type="button" id="playerCancel_button" onClick="MM_goToURL('self','playerlist.php');return document.MM_returnValue" value="Avbryt">
  </p>
</form>
<p><img src="images/DummyPortrait.gif" alt="Selvportrett" name="portrett" width="193" height="222" border="0" align="right" id="portrett" title="Ser du slik ut?"></p>
</body>
</html>
<?php
mysql_free_result($SpillerInfo);

/**
 * @return html
 * @desc Insert controls and logic for selecting a player from another team.
*/
function SelectPlayerFromOtherTeam(){
	global $colname_SpillerInfo;
	
	$activateSelectorButtonName = "btn_getplayerfromotherteams";
	$otherTeamIdFieldName = "txt_otherTeamId";
	$otherTeamPasswordFieldName = "txt_otherTeamPasswd";
	$otherTeamSelectButtonName = "btn_GetOtherTeam";
	$otherTeamPlayersSelector = "cmb_otherPlayer";
	$otherTeamPlayerSelectButtonFieldname = "btn_SelectOtherPlayer";	
	
	$thisTeam = new Team_class();
	$thisTeamId = $thisTeam->get_id();
	$thisTeamName = $thisTeam->getName("short");
	$out = "";
	
	if (isset($_POST[$otherTeamPlayerSelectButtonFieldname])){
		// Player is selected
		
		// Add membership
		$addQueryString = "
			INSERT INTO membership
			SET 
				player = '$_POST[$otherTeamPlayersSelector]',
				team = '$thisTeamId'
		";
		$addQuery = new DBConnector_class($addQueryString);
		$out .= "<p>Spilleren er meldt inn.</p>";
		$javaScriptRedirectString = "document.location='formPlayerReg.php?Player=$_POST[$otherTeamPlayersSelector]';";
		$out .= JavaScriptWrapper($javaScriptRedirectString);
		$out .= "
			<hr>
			";
		
	}elseif (isset($_POST[$otherTeamSelectButtonName])){
		// Team is possibly selected
		
		//Query for players in this team
		$playersThisTeamQueryString = "
			SELECT player
			FROM membership
			WHERE team = '$thisTeamId'
		";
		$playersThisTeam = new DBConnector_class($playersThisTeamQueryString);
		$listOfPlayerId = implode(",",$playersThisTeam->GetArrayOfAllRowsOneValue('player'));
		
		$playerFilterString = $playersThisTeam->GetNumberOfRows() ? "AND players.id NOT IN ($listOfPlayerId)" : "";
		
		//Query for player list
		$playerQueryString = "
			SELECT firstName, lastName, players.id, teams.longName
			FROM players
			LEFT JOIN membership
			ON players.id = membership.player
			LEFT JOIN teams
			ON membership.team = teams.id 
			WHERE teams.userName ='$_POST[$otherTeamIdFieldName]'
				AND teams.password = '$_POST[$otherTeamPasswordFieldName]'
				AND membership.team != '$thisTeamId'
				$playerFilterString
			ORDER BY players.lastName
		";
		
		$playersQuery = new DBConnector_class($playerQueryString);
		$allPlayers = $playersQuery->getAllRows();
		
		$newTeamName = $allPlayers[0][longName];
		
		if ($playersQuery->GetNumberOfRows()){
			$javaScriptEnableString = "
				function enableButton(){
					var firstNames = Array(".(count($allPlayers)-1).");
			";
			foreach ($allPlayers as $player){
				$javaScriptEnableString .= "
					firstNames[$player[id]]='$player[firstName]';";	
			}
			$javaScriptEnableString .= "
					var cmb = document.getElementById('$otherTeamPlayersSelector');
					var btn = document.getElementById('$otherTeamPlayerSelectButtonFieldname');
					if (cmb.value==0){
						btn.value='Velg en spiller i listen';
						btn.disabled = true;
					}else{
						var id = eval(cmb.value);
						btn.value='Meld '+firstNames[id]+' inn i $thisTeamName';
						btn.disabled = false;
					}
				}
			";
			
			$out .= JavaScriptWrapper($javaScriptEnableString);
			$out .= "<form method = 'post'>";
			$out .= "<p>Velg en spiller fra $newTeamName, og klikk knappen.</p>";
			$out .= "<p>";
			$out .= "<select 
						name='$otherTeamPlayersSelector'
						id = '$otherTeamPlayersSelector'
						onChange='enableButton();'>
  					<option value = '0'>Velg en spiller fra $newTeamName</option>";
  			foreach ($allPlayers as $player){
  				$out .= "
  					<option value = '$player[id]'>$player[firstName] $player[lastName]</option>";
  			}
			$out .= "</select>";
			$out .= "&nbsp;";
			$out .= "<input 
					name='$otherTeamPlayerSelectButtonFieldname' 
					id='$otherTeamPlayerSelectButtonFieldname'
					type='submit'
					value='Meld spiller inn $thisTeamName'>";
			$out .= "</p>";
			$out .= "</form>";
			$out .= JavaScriptWrapper("enableButton();");
			$out .= "<hr>
				";
		}else{
			//Empty query
			$out .= SelectOtherTeamControllers(
				$otherTeamSelectButtonName, 
				$otherTeamIdFieldName, 
				$otherTeamPasswordFieldName, 
				"Ingen spillere ble funnet. 
					Kanskje har du skrevet galt brukernavn eller passord. 
					Om du ikke husker brukernavn eller passord, 
					f&aring;r du sp&oslash;rre en voksen. ");	
		}
		
		
	}elseif(! $colname_SpillerInfo){
		// Player isn't defined. In create player mode
		$out .= SelectOtherTeamControllers($otherTeamSelectButtonName, $otherTeamIdFieldName, $otherTeamPasswordFieldName);	
	}
	return $out;
}
/**
 * @return html
 * @param string $otherTeamSelectButtonName
 * @param string $otherTeamIdFieldName
 * @param string $otherTeamPasswordFieldName
 * @param string $errorMessage
 * @desc Draws controllers for getting playerlist from other team;
*/
function SelectOtherTeamControllers($otherTeamSelectButtonName, $otherTeamIdFieldName, $otherTeamPasswordFieldName, $errorMessage=""){
	$spanId01 = "otherTeamLink";
	$spanId02 = "otherTeamControls";
	$javascriptFunctionName = "OpenOtherTeamControls";
	$team = new Team_class();
	$teamName = $team->getName("long");
	$teamShortName = $team->getName("short");
	$out .= "<span id='$spanId01'>";
	$out .= "Om du allerede er registrert p&aring; disse sidene for et annet lag, 
			kan du <a href='javascript:{$javascriptFunctionName}()'>melde deg inn i $teamShortName her</a>.";
	$out .= "</span>";
	$out .= "<span id='$spanId02'>";
	$out .= "
		<form method='post'>
		";
	$out .= "<h3>Hente eksisterende spiller</h3>";
	if ($errorMessage != ""){
		$out .= "<p class=importantMessage>$errorMessage</p>
			";
	}
	$out .= "<p>Om du allerede har en spiller definert i systemet 
		p&aring; et annet lag, kan du melde denne inn i $teamName.
		Skriv inn brukernavn og passord til det aktuellet laget
		nedenfor, og klikk knappen.</p>";
	
	$out .= "<table><tr valign='bottom'>";
	$out .= "<td>Brukernavn: 
				<input name='$otherTeamIdFieldName' 
				type='text'>
			</td>";
	$out .= "<td>Passord: 
				<input name='$otherTeamPasswordFieldName' 
				type='password'>
			</td>";
	$out .= "<td>
		<input name='$otherTeamSelectButtonName' 
		type='submit' 
		value='Hent spillerliste'>
			</td>";
	$out .= "</tr></table>";
	
	$out .= "</form>
		</span>
		<hr>
		";
	$out .= "<h3>Opprette ny spiller</h3>";
	$javascript = "
		function $javascriptFunctionName(){
			document.getElementById('$spanId01').style.display = 'none';
			document.getElementById('$spanId02').style.display = 'inline';
		}
		document.getElementById('$spanId02').style.display = 'none';
	";
	$out .= JavaScriptWrapper($javascript);
	return $out;
}
/**
 * @return html
 * @desc Insert controls and logic for deleting memberships in other teams (if any)
*/
function DeleteOtherMembership(){
	//To be inserted into a form
	global $colname_SpillerInfo;
	
	$membershipToDeleteFieldName = "cmb_deleteMembership";
	$membershipToDeleteButtonName = "btn_deleteMembership";

	
	if ($_GET[$membershipToDeleteFieldName] > 0){
		//Delete mebership	
		$membershipId = $_GET[$membershipToDeleteFieldName];
		$nameQueryString = "
			SELECT teams.longName, teams.id
			FROM teams
			LEFT JOIN membership
			ON teams.id = membership.team
			WHERE membership.id = '$membershipId'
		";
		$nameQuery = new DBConnector_class($nameQueryString);
		$nameQuery_row = $nameQuery->GetNextRow();
		$oldTeamName = $nameQuery_row['longName'];
		$oldTeamId = $nameQuery_row['id'];
		
		$findAttentionsQueryString = "
			SELECT attention.id
			FROM attention
			LEFT JOIN events
			ON events.id=attention.event
			WHERE attention.player = '$colname_SpillerInfo'
			AND events.team = '$oldTeamId'
		";
		$findAttentions = new DBConnector_class($findAttentionsQueryString);
		$attentionString = implode(",",$findAttentions->GetArrayOfAllRowsOneValue('id'));
		
		if ($attentionString){
			$deleteAttentionsString = "
				DELETE 
				FROM attention
				WHERE id IN ($attentionString)
				";
			$deleteAttentions = new DBConnector_class($deleteAttentionsString);
		}
		
		$deleteMembershipString = "
			DELETE FROM membership WHERE id = '$membershipId'";
		
		$deleteMembership = new DBConnector_class($deleteMembershipString);
		
		$out .= "
			<p>Medlemsskapet ";
		$out .= $oldTeamName ? "i $oldTeamName " : "";
		$out .= " er sagt opp. Slett spilleren ved &aring; klikke knappen over.</p>
			";
	}
	
	$thisTeam = new Team_class();
	$thisTeamId = $thisTeam->get_id();
	
	// Determining if there are other memberships
	$findMembershipsQueryString = "
		SELECT membership.id,
				teams.shortName,
				teams.longName
		FROM membership
		LEFT JOIN teams
			ON membership.team = teams.id
		WHERE membership.player='$colname_SpillerInfo'
			AND membership.team != '$thisTeamId'
		
	";
	
	$membershipsQuery = new DBConnector_class($findMembershipsQueryString);
	if ($membershipsQuery->GetNumberOfRows()){
		$allMemberships = $membershipsQuery->getAllRows();
		//Draw form fields
		$out .= "<hr><table><tr>
			<td colspan='2'>
				Meld deg ut av et av de andre lagene
				ved &aring; velge medlemskapet under
			</td></tr>
		";	
		$out .= "<tr>
			<td>
			<select 
				name='$membershipToDeleteFieldName' 
				id='$membershipToDeleteFieldName'
				onChange='EnablemembershipDeleteButton()'>
  			<option value = '0' selected>Velg et medlemsskap</option>
			";
		foreach ($allMemberships as $membership){
			$out .= "<option value = $membership[id]>$membership[longName]</option>
			";	
		}
		$out .= "
			</select>
			</td>
			<td>
			<input 
				name='$membershipToDeleteButtonName' 
				type='button' 
				id='$membershipToDeleteButtonName' 
				value='Velg i listen til venstre'
				disabled=true
				onClick='SubmitDeletemembership()'>
			</td>
		</tr></table><hr>
		";
		$javaScriptEnableString .= "
				function EnablemembershipDeleteButton(){
					var cmb = document.getElementById('$membershipToDeleteFieldName');
					var btn = document.getElementById('$membershipToDeleteButtonName');
					if (cmb.value==0){
						btn.value='Velg et medlemskap';
						btn.disabled = true;
					}else{
						var id = eval(cmb.value);
						btn.value='Meld deg ut';
						btn.disabled = false;
					}
				}
				function SubmitDeletemembership(){
					var cmb = document.getElementById('$membershipToDeleteFieldName');
					document.location='formPlayerReg.php?Player={$colname_SpillerInfo}&{$membershipToDeleteFieldName}='+cmb.value;
				}
			";
			
		$out .= JavaScriptWrapper($javaScriptEnableString);
		
		
	}
	
	
	return $out;
}

?>

