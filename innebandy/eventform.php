<?php 
require_once('Connections/innebandybase.php'); 
require_once('commonCode.inc');

$weekdayJavaScriptName = "weekdayscript";
$onChangeDateFieldsScript = "setonchangeScript";

/* Navn på felt */
$numberOfEventsFieldName="numberOfEvents_cmb";
$maxFieldName = "maximumPlayersCount";
$minFieldName = "minimumPlayersCount";
$minRadioFieldName = "minimumPlayersCountDefault";
$maxRadioFieldName = "maximumPlayersCountDefault";
$typeFieldNameAndId = "type_select";
$eventIdHidden = 'eventId_hidden';
$fieldNameStart = "startTidspunkt";
$fieldNameEnd = "sluttTidspunkt";
$fieldNameNotify = "epostTidspunkt";

/* Other globals */
$defaultEventType = 2;

/* Establish identity */
$player = new Player_class();
$playerId = $player->getId();
$multipleTeams = $player->getHasMultipleMemberships();
$eventToEditId = $_POST[$eventIdHidden] ? $_POST[$eventIdHidden] : $_GET[$eventIdHidden];

/* Finne remedy-verdi */
$readRemedies = new Remedies_class();
$remedyValuefromForm = $readRemedies->GetValueFromFields(true);


 /* Logisk start */


if ($_POST['delete_hidden']) {/* Slette hendelse */
	
	mysql_select_db($database_innebandybase, $innebandybase);
	
	$query_playerInfo = "SELECT * FROM players WHERE id=".$playerId.";";
	$playerInfo = mysql_query($query_playerInfo, $innebandybase) or die(mysql_error());
	$row_playerInfo = mysql_fetch_assoc($playerInfo);
	$totalRows_playerInfo = mysql_num_rows($playerInfo);
	
	$query_deleteEvent = "DELETE FROM events WHERE id=".$eventToEditId.";";
	$deleteEvent = mysql_query($query_deleteEvent, $innebandybase) or die(mysql_error());
	$row_deleteEvent = mysql_fetch_assoc($deleteEvent);
	$totalRows_deleteEvent = mysql_num_rows($deleteEvent);
	
	$query_deleteAtts = "DELETE FROM attention WHERE event=".$eventToEditId.";";
	$deleteAtts = mysql_query($query_deleteAtts, $innebandybase) or die(mysql_error());
	$row_deleteAtts = mysql_fetch_assoc($deleteAtts);
	$totalRows_deleteAtts = mysql_num_rows($deleteAtts);
	
	/* Sende e-post om nødvendig */
	if ($_POST['toField_hidden']){
		$email_to=$_POST['toField_hidden'];
		$email_from="From: ".$row_playerInfo['firstName']." ".
			$row_playerInfo['lastName'].' <'.
			$row_playerInfo['email'].'>';
		$email_replyto="reply-to: ".$row_playerInfo['email'];
		$email_expiryDate="Expiry-Date: ".date ("D, d M Y H:I:s +0100",strtotime($_POST['end_hidden']));
		$email_importance="Importance: High";
		$email_header=$email_from."\n".$email_expiryDate."\n".$email_importance."\n".$email_replyto;
		$email_subject="Innebandy: hendelse er slettet";
		$email_body='Hendelsen "'.$_POST['description_text'].'" '.$_POST['start_hidden'].' er slettet fra databasen på <http://skaar.freeshell.org/innebandy/>.';
		$email_body.="\n\n";
		$email_body.=strip_tags($_POST['message_text']);
		
		mail($email_to,$email_subject,$email_body,$email_header);
	}
	
	
	mysql_free_result($deleteAtts);
	mysql_free_result($deleteEvent);
	mysql_free_result($playerInfo);
	
	UpdateChangeLog($eventToEditId, $_POST['playerId_hidden'], true);
	
	header("Status: 302 Moved Temporarily");
	header("Location: {$playerListFileName}?{$playerIdFieldName}=".$_POST['playerId_hidden']);
	exit;

}else if($eventToEditId){/* Endre eksisterende hendelse */
	$dateStartValue=date('Y-m-d H:i:00',$_POST[$fieldNameStart]);
	$dateEndValue=date('Y-m-d H:i:00',$_POST[$fieldNameEnd]);
	
	
	$cancelledValue=($_POST['cancelled_checkbox']=='on') ? 1 : 0;
	$oldCancelledValue= $_POST['cancelled_hidden'] ? 1 : 0;
	
	mysql_select_db($database_innebandybase, $innebandybase);
	
	$query_oldValues='SELECT * FROM events WHERE id='.$eventToEditId.';';
	$oldValues = mysql_query($query_oldValues, $innebandybase) or die(mysql_error());
	$row_oldValues = mysql_fetch_assoc($oldValues);
	$totalRows_oldValues = mysql_num_rows($oldValues);
	
	$oldDescription = $row_oldValues ['description'];
	$oldStartDate = $row_oldValues ['dateStart'];
	
	$minAndMax = GetNewMinAndMax();	
	
	

	$query_updateEvent='
			UPDATE events
			SET dateStart = "'.$dateStartValue.'",
				dateEnd = "'.$dateEndValue.'",
				location = "'.strip_tags($_POST['location_text']).'",
				description = "'.strip_tags($_POST['description_text']).'",
				type = "'.$_POST['type_select'].'",
				cancelled = '.$cancelledValue.',
				notes = "'.strip_tags($_POST['notes_text']).'",
				team = "'.$_POST[$teamsFieldName].'",
				url = "'.strip_tags($_POST['url_text']).'",
				minimumCount = '.$minAndMax['min'].',
				maximumCount = '.$minAndMax['max'].',
				'.GetNotificationQueryString().',
				remediesNeeded = "'.$remedyValuefromForm.'"
			WHERE id = '.$eventToEditId.';';
	
	$updateEvent = new DBConnector_class($query_updateEvent);

	
	if (($dateStartValue != $oldStartDate) || ($cancelledValue != $oldCancelledValue)) { /* Fjerner attentions knyttet til hendelsen om nødvendig */
		
		$query_deleteAtts = "DELETE FROM attention WHERE event=".$eventToEditId.";";
		$deleteAtts = mysql_query($query_deleteAtts, $innebandybase) or die(mysql_error());
		$row_deleteAtts = mysql_fetch_assoc($deleteAtts);
		$totalRows_deleteAtts = mysql_num_rows($deleteAtts);
		mysql_free_result($deleteAtts);
	}
	
	/* Sjekke om e-post må sendes */
	if(($dateStartValue != $_POST['start_hidden']) || ($cancelledValue != $oldCancelledValue)){
		if ($_POST['toField_hidden']){
		
			$query_playerInfo = "SELECT * FROM players WHERE id='$playerId'";
			$playerInfo = mysql_query($query_playerInfo, $innebandybase) or die(mysql_error());
			$row_playerInfo = mysql_fetch_assoc($playerInfo);
			$totalRows_playerInfo = mysql_num_rows($playerInfo);
		
			$email_to=$_POST['toField_hidden'];
			$email_from="From: ".$row_playerInfo['firstName']." ".
				$row_playerInfo['lastName'].' <'.
				$row_playerInfo['email'].'>';
			$email_replyto="reply-to: ".$row_playerInfo['email'];
			$email_expiryDate="Expiry-Date: ".date ("D, d M Y H:I:s +0100",strtotime($_POST['end_hidden']));
			$email_importance="Importance: High";
			$email_header=$email_from."\n".$email_expiryDate."\n".$email_importance."\n".$email_replyto;
			$email_subject="Innebandy: ".$oldDescription." ".$oldStartDate." er ";
			$email_subject.=$cancelledValue ? "avlyst" : "endret";
			$email_body="Nye data fra <http://skaar.freeshell.org/innebandy/>:\n";
			$email_body.=$cancelledValue ? "Hendelsen er avlyst.\n" : "";
			$email_body.=$_POST['description_text']."\n".$dateStartValue." til ".$dateEndValue."\n";
			$email_body.=$_POST['location_text']."\n".$_POST['notes_text'];
			$email_body.="\n".$_POST['url_text']."\n";
			$email_body.="\n\n";
			$email_body.= $cancelledValue ? "" : "Du sto oppført på denne hendelsen, men er fjernet fra listen. Logg deg inn for å fortelle om du kan komme med endrede tidspunkt.";
			$email_body.="\n\n";
			$email_body.=strip_tags($_POST['message_text']);
			
			mail($email_to,$email_subject,$email_body,$email_header);
			
			mysql_free_result($playerInfo);
		}
	}
	
	UpdateChangeLog($eventToEditId, $playerId);
	
	header("Status: 302 Moved Temporarily");
	header("Location: {$eventDetailsFileName}?{$playerIdFieldName}={$playerId}&{$eventFieldname}={$eventToEditId}");
	exit;

}else if($_POST['playerId_hidden']){
	/* Legge inn ny hendelse */
	
	
	/* Finne antall hendelser */
	$numberOfEvents = $_POST[$numberOfEventsFieldName];
	if (($numberOfEvents < 1) || ($numberOfEvents > 10)){
		$numberOfEvents = 1;
	}
	
	$firstStartTime=($_POST[$fieldNameStart]);
	$firstEndTime=date($_POST[$fieldNameEnd]);
	
	for ($eventIndex=0 ; $eventIndex < $numberOfEvents ; $eventIndex ++){
		/* find time of event */
		$timeDifference = (60*60*24*7)*$eventIndex;
		$timeFormatString = "Y-n-d G:i:00";
		$startTimeString = date($timeFormatString,($firstStartTime + $timeDifference));
		$endTimeString = date($timeFormatString,($firstEndTime + $timeDifference));

		$minAndMax = GetNewMinAndMax();	
		
		$query_insertEvent ='
			INSERT INTO events SET
			id=NULL,
			dateStart="'.$startTimeString.'",
			dateEnd="'.$endTimeString.'",
			location="'.strip_tags($_POST['location_text']).'",
			description="'.strip_tags($_POST['description_text']).'",
			type="'.$_POST['type_select'].'",
			cancelled="'.$_POST['cancelled_checkbox'].'",
			minimumCount = '.$minAndMax['min'].',
			maximumCount = '.$minAndMax['max'].',
			notes="'.strip_tags($_POST['notes_text']).'",
			url="'.strip_tags($_POST['url_text']).'",
			team="'.$_POST[$teamsFieldName].'",
			'.GetNotificationQueryString($timeDifference).',
			remediesNeeded = "'.$remedyValuefromForm.'"
			';
		$insertEvent = new DBConnector_class($query_insertEvent);
				
		$newEventId = $insertEvent->GetLastAutoIncrement();
		
	
		UpdateChangeLog($newEventId, $_POST['playerId_hidden']);
	}

	header("Status: 302 Moved Temporarily");
	header("Location: {$eventDetailsFileName}?{$playerIdFieldName}={$playerId}&{$eventFieldname}={$newEventId}");
	exit;

}else{/* Ordinært skjema */


$EventId = ($_GET['Event']) ? $_GET['Event'] : 0;
$PlayerId = ($_GET['Player']);
$Fields = array();

function parseDate($dateValue){
	$returnValues=array();
	$returnValues[0] = strftime ("%Y",strtotime ($dateValue));
	$returnValues[1] = strftime ("%m",strtotime ($dateValue));
	$returnValues[2] = strftime ("%d",strtotime ($dateValue));
	$returnValues[3] = strftime ("%H",strtotime ($dateValue));
	$returnValues[4] = strftime ("%M",strtotime ($dateValue));
	return $returnValues;
}

?>
<?php
/* Fyller menyvalget for hendelsestype */
mysql_select_db($database_innebandybase, $innebandybase);
$query_eventTypes = "
	SELECT 	id, 
			type,
			requiredCount,
			maxCount 
	FROM eventTypes
	ORDER BY listOrder
	";
$eventTypes = new DBConnector_class($query_eventTypes);
$eventTypesAll = $eventTypes->GetAllRows();
$eventTypesById = array();

foreach($eventTypesAll as $eType){
	$eventTypesById[$eType['id']]=$eType;	
}

/* Finner navn på team */

	$authUserName=$_SERVER['PHP_AUTH_USER'];
	
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_teamHeaderfile = "SELECT id FROM teams WHERE userName='$authUserName';";
	$teamHeaderfile = mysql_query($query_teamHeaderfile, $innebandybase) or die(mysql_error());
	$row_teamHeaderfile = mysql_fetch_assoc($teamHeaderfile);
	$totalRows_teamHeaderfile = mysql_num_rows($teamHeaderfile);
	
	$teamHeaderFilename = $row_teamHeaderfile['headerFile'];
	$teamId=$row_teamHeaderfile['id'];
	
	mysql_free_result($teamHeaderfile);


$query_eventDetails = "
	SELECT events.*, 
		eventTypes.type AS typeName,
		eventTypes.requiredCount,
		eventTypes.maxCount 
	FROM events 
	LEFT JOIN eventTypes
	ON events.type = eventTypes.id
	WHERE events.id = $EventId
	";
$eventDetails = new DBConnector_class($query_eventDetails);
$row_eventDetails = $eventDetails->GetNextRow();

//Is default max and min values?
$defaultMaxValue = ($row_eventDetails['maximumCount'] == $row_eventTypes['maxCount']);
$defaultMinValue = ($row_eventDetails['minimumCount'] == $row_eventTypes['requiredCount']);


mysql_select_db($database_innebandybase, $innebandybase);

$query_allAnswers = "SELECT players.email, players.firstName, players.lastName 
					FROM players INNER JOIN attention ON players.id = attention.player 
					WHERE (((attention.event)=$EventId) AND players.email != '') 
					ORDER BY players.age ASC
					";
$allAnswers = new DBConnector_class($query_allAnswers);
$row_allAnswers = $allAnswers->GetNextRow();
$totalRows_allAnswers = $allAnswers->GetNumberOfRows();

/* Definerer variabler til feltene */

$Start=parseDate($row_eventDetails['dateStart']);
$End=parseDate($row_eventDetails['dateEnd']);

$currentEventTeamId = $row_eventDetails['team'];

$Fields['PlayerId']=$PlayerId;
$Fields['Event']=$EventId;
$Fields['OldStart']=$row_eventDetails['dateStart'];
$Fields['OldEnd']=$row_eventDetails['dateEnd'];
$Fields['StartDate']=$Start[2];
$Fields['StartMonth']=$Start[1];
$Fields['StartYear']=$Start[0];
$Fields['StartHours']=$Start[3];
$Fields['StartMinutes']=$Start[4];
$Fields['EndDate']=$EventId ? $End[2] : '';
$Fields['EndMonth']=$EventId ? $End[1] : '';
$Fields['EndYear']=$EventId ? $End[0] : '';
$Fields['EndHours']=$End[3];
$Fields['EndMinutes']=$End[4];
$Fields['Notified'] = $row_eventDetails['notified'];
$Fields['Description']=$row_eventDetails['description'];
$Fields['Location']=$row_eventDetails['location'];
$Fields['Type']= ($row_eventDetails['type'] != "") ? $row_eventDetails['type'] : $defaultEventType;
$Fields['Cancelled']=$row_eventDetails['cancelled'];
$Fields['Notes']=$row_eventDetails['notes'];
$Fields['Url']=$row_eventDetails['url'];
$Fields['remediesNeeded'] = $row_eventDetails['remediesNeeded'];


if ( $row_eventDetails['dateNotification'] > 0 ) {
	$Fields['NotifyDate']=strtotime($row_eventDetails['dateNotification']);
}else{
	$Fields['NotifyDate']=0;
}


$playerCount=$totalRows_allAnswers;
$addresses="";
/*Lager liste over e-postadresser*/

if ($playerCount){
	do {
		$addresses .= "$row_allAnswers[firstName] $row_allAnswers[lastName]<$row_allAnswers[email]>,";
	} while ($row_allAnswers = mysql_fetch_assoc($allAnswers));
}


$PageHeading = $EventId ? "Redigere, slette eller avlyse {$Fields[Description]}" : "Registrere en ny hendelse";

?>
<html>
<head>
<title><?php echo $PageHeading ; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript" type="text/JavaScript">
<!--
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
        if (p<1 || p==(val.length-1)) errors+='- '+nm+' must contain an e-mail address.\n';
      } else if (test!='R') { num = parseFloat(val);
        if (isNaN(val)) errors+='- '+nm+' må inneholde et tall.\n';
        if (test.indexOf('inRange') != -1) { p=test.indexOf(':');
          min=test.substring(8,p); max=test.substring(p+1);
          if (num<min || max<num) errors+='- '+nm+' må inneholde et tall mellom '+min+' og '+max+'.\n';
    } } } else if (test.charAt(0) == 'R') errors += '- '+nm+' må fylles ut.\n'; }
  } if (errors) alert('Følgende feil er funnet:\n'+errors);
  document.MM_returnValue = (errors == '');
}

function SubmitForm() {
	document.Event_form.submit();
}

function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}

function MM_setTextOfTextfield(objName,x,newText) { //v3.0
  var obj = MM_findObj(objName); if (obj) obj.value = newText;
}

function DeleteEvent(){
	Warningtext="Er du ganske sikker på \nat du vil slette hendelsen? \nDu kan ikke angre på dette.\n\n(Du kan også avlyse hendelsen \nuten å slette den.)";
	if(confirm(Warningtext)){
		document.forms[0].delete_hidden.value="true";
		document.forms[0].submit();
	}else{
		document.forms[0].delete_hidden=false;
	}
}

//-->
</script>
<?php 
echo RenderCSSLink($currentEventTeamId);
?>
</head>

<body>
<h3><?php echo $PageHeading ; ?></h3>
<form method="post" name="Event_form" id="Event_form">
  <input name="playerId_hidden" type="hidden" id="playerId_hidden" value="<?php echo $_GET['Player']; ?>">
<input name="eventId_hidden" type="hidden" id="eventId_hidden" value="<?php echo $_GET['Event']; ?>">
  <?php 
if ($EventId){
	if ($totalRows_allAnswers){
		$EmailInstructions=" Om du endrer starttidspunktet vil det 
          g&aring; en e-post til alle spillere som har registrert et svar i hendelsen. 
          Du kan legge inn en tekst i denne meldingen i det nederste tekstfeltet. ";
	}
$CancelledValue = ($Fields['Cancelled']) ? "checked" : "unchecked";
$OldCancelledValue = $Fields['Cancelled'];
echo <<<EOT

<fieldset><legend>Slette eller avlyse hendelsen</legend>
  <table width="100%" border="0" cellspacing="0" cellpadding="8">
    <tr> 
      <td colspan="2">
          Klikk her for &aring; slette hendelsen: 
          <input name="deleteEvent_button" type="button" id="deleteEvent_button" value="Slett hendelse" name="Submit" onClick="DeleteEvent()">
        <hr width="80%">
        	For &aring; redigere hendelsen, endre feltene nedenfor og lagre 
          	med knappen nederst i skjemaet. $EmailInstructions
		<hr width="80%">
			Sett kryss her for &aring; avlyse hendelsen: 
          	<input name="cancelled_checkbox" type="checkbox" id="cancelled_checkbox" $CancelledValue>
            <input name="cancelled_hidden" type="hidden" id="oldEnd_hidden" value="$OldCancelledValue">
</td>
    </tr>
	</table>
</fieldset>

EOT;
}?>
  <input name="delete_hidden" type="hidden" id="delete_hidden" value="0">
  <fieldset><legend>Tidspunkt</legend>
	
  <table width="100%" border="0" cellspacing="0" cellpadding="8">
    <tr> 
      <td width="15%"><em>Start</em></td>
      <td>
      	<?PHP
     	$dateControlStart = new DateControl_class();
	    $rightNow = time();
		$switchSetting = 0; 
		$earliestDate = $rightNow + (60*60*2);
		$latestDate = $rightNow + (60*60*24*365.25*2);
		$currentDate = $row_eventDetails['dateStart'];
		if ($currentDate == ""){
			$currentDateStamp = $rightNow + (60*60*24*3);
			$currentDateStamp = mktime(
				18,
				0,
				0,
				date('m',$currentDateStamp),
				date('d',$currentDateStamp),
				date('Y',$currentDateStamp)
			);
		}else{
			$currentDateStamp = mktime(
				$Fields['StartHours'],
				$Fields['StartMinutes'],
				0,
				$Fields['StartMonth'],
				$Fields['StartDate'],
				$Fields['StartYear']
			);
		}
		if ($currentDateStamp > $latestDate){
			$latestDate = $currentDateStamp + (60*60*24*365.25*2);
		}elseif ($currentDateStamp < $earliestDate){
			$earliestDate = $currentDateStamp - (60*60*24*365.25*2);
		}
		
		$paddingToNotification = -60*60*4;
		
		$dateControlStart->SetName($fieldNameStart);
		$dateControlStart->SetEarliestDate($earliestDate);
		$dateControlStart->SetLatestDate($latestDate);
		$dateControlStart->SetTimeControl(true);
		$dateControlStart->SetOptional(0);
		$dateControlStart->SetCurrentValue($currentDateStamp);
		
		$dateControlStart->SetDependency($fieldNameEnd,true,0,"no");
		$dateControlStart->SetDependency($fieldNameNotify,true,"no",$paddingToNotification);
		
		echo $dateControlStart->RenderControl();
		
		//$Fields['NotifyDate']
		
		$notifyOptionalValue = $Fields['Notified'] ? 1 : 2;
		if ($Fields['NotifyDate']){
			$notifyCurrentValue = $Fields['NotifyDate'];
		}else{
			$notifyCurrentValue = max($currentDateStamp - (60*60*24*2.5), $rightNow);
		}
		
		//Notify control
		$dateControlNotify = new DateControl_class();
		$dateControlNotify->SetName($fieldNameNotify);
		$dateControlNotify->SetEarliestDate(time());
		$dateControlNotify->SetLatestDate($currentDateStamp+$paddingToNotification);
		$dateControlNotify->SetTimeControl(true);
		$dateControlNotify->SetOptional($notifyOptionalValue);
		$dateControlNotify->SetDateLabel("P&aring;minnelse sendes ikke f&oslash;r:");
		$dateControlNotify->SetNoDateLabel("Ingen p&aring;minnelse");
		$dateControlNotify->SetCurrentValue($notifyCurrentValue);
		$dateControlNotify->SetGMT(true);
		
      	?>
        
        <hr>
		</td>
    </tr>
    <tr> 
      <td><em>Slutt</em></td>
      <td>
		
	<?PHP

		$earliestDate = $currentDateStamp+(60*5);
		$latestDate += (60*60);
		$currentDate = $row_eventDetails['dateEnd'];
		if ($currentDate == ""){
			$currentDateStamp = $currentDateStamp + (60*60*1.5);
		}else{
			$currentDateStamp = mktime(
				$Fields['EndHours'],
				$Fields['EndMinutes'],
				0,
				$Fields['EndMonth'],
				$Fields['EndDate'],
				$Fields['EndYear']
			);
		}
		$dateControlEnd = new DateControl_class();
		$dateControlEnd->SetName($fieldNameEnd);
		$dateControlEnd->SetEarliestDate($earliestDate);
		$dateControlEnd->SetLatestDate($latestDate);
		$dateControlEnd->SetTimeControl(true);
		$dateControlEnd->SetOptional(0);
		$dateControlEnd->SetCurrentValue($currentDateStamp);
		
		echo $dateControlEnd->RenderControl();
		
		
      	?>
		</td>
    </tr>
    <tr>
      <td colspan="2">Legg inn denne hendelsen for
        <select name="<?php echo ($numberOfEventsFieldName); ?>"<?php
		 /* Disabled ved redigering av hendelse */
		 if ($EventId){
		 	echo(" disabled");
		 } 
		 ?>>
          <option value="1" selected>&eacute;n</option>
          <option value="2">to</option>
          <option value="3">tre</option>
          <option value="4">fire</option>
          <option value="5">fem</option>
          <option value="6">seks</option>
          <option value="7">syv</option>
          <option value="8">&aring;tte</option>
          <option value="9">ni</option>
          <option value="10">ti</option>
        </select>
uke(r). </td>
    </tr>
	</table>
	
  </fieldset>
  	
	<?php echo TeamComboField($currentEventTeamId); ?>

	<fieldset><legend>Annen informasjon</legend>
		
  <table width="100%" border="0" cellspacing="0" cellpadding="8">
    <tr> 
      <td><em>Beskrivelse</em></td>
      <td><input name="description_text" type="text" id="description_text" size="40" maxlength="255" value="<?php echo $Fields['Description'] ?>"></td>
    </tr>
    <tr> 
      <td><em>Sted</em></td>
      <td><input name="location_text" type="text" id="location_text" size="40" maxlength="50" value="<?php echo $Fields['Location'] ?>"></td>
    </tr>
    <tr> 
      <td><em>Type</em></td>
       <?php
      echo "<td><select name='$typeFieldNameAndId' 
      		id='$typeFieldNameAndId'
      		onChange = 'SetDefaultAndLockAll()'>";
         
	foreach ($eventTypesAll as $eType){  

          echo "
          	<option value='$eType[id]'";
          if (!(strcmp($eType['id'], $Fields['Type']))) {
          	echo "SELECTED";
          }
          echo ">$eType[type]</option>
        	";
	} 
?>
        </select></td>
    </tr>
    
    <?PHP 
    
    echo ("
    	<tr>
    	<td><em>E-postp&aring;minnelse:</em></td>
    	<td>".$dateControlNotify->RenderControl()."</td>
    	</tr>
    ");
    
    echo MinAndMaxFields(); 
    
    $remedies = new Remedies_class();
    $currentValue = $Fields['remediesNeeded'];
    $controls = $remedies->RenderEventNeededRemediesControls($currentValue);
    
    echo ($controls);
    
    ?>

    <tr> 
      <td>Merknader</td>
      <td>
<textarea name="notes_text" cols="30" rows="3" id="notes_text"><?php echo $Fields['Notes'] ?></textarea></td>
	</tr>
	<tr>
		
      <td>Web-adresse:</td>
		<td><input name="url_text" type="text" id="url_text" value="<?php echo $Fields['Url'] ?>" size="30" maxlength="255"> </td>
    </tr>
	</table>
	</fieldset>
<?php
if($EventId && $playerCount){
echo <<<EOT
	<fieldset><legend>Melding til spillere som var p&aring;meldt hendelsen</legend>
			
  <table width="100%" border="0" cellspacing="0" cellpadding="8">
    <tr>
		
		<td>
		Om du endrer starttidspunktet eller avlyser, vil det g&aring; en e-post til alle spillere 
		som hadde svart p&aring; denne hendelsen. 
		Det du skriver i feltet nedenfor vil komme med i denne meldingen. <br>
		<textarea name="message_text" cols="40" rows="6" id="message_text"></textarea>
		<input name="toField_hidden" type="hidden" value="$addresses">
		</td>
	</tr>
  </table>
  </fieldset>
EOT;
}
   ?>
  <input name="save_button" type="button" id="save_button" value="Lagre" onClick="MM_validateForm('startDate_text','','RinRange1:31','startMonth_text','','RinRange1:12','startYear_text','','RinRange2003:2006','startHours_text','','RinRange0:23','startMinutes_text','','RinRange0:59','endDate_text','','RinRange1:31','endMonth_text','','RinRange1:12','endYear_text','','RinRange2003:2006','endHours_text','','RinRange0:24','endMinutes_text','','RinRange0:59','description_text','','R','location_text','','R');if(document.MM_returnValue){SubmitForm();};return document.MM_returnValue">
  <?PHP
  if ($EventId){
  	$locationString = "\"{$eventDetailsFileName}?{$playerIdFieldName}={$playerId}&{$eventFieldname}={$EventId}\"";
  }else{
  	$locationString = "\"{$playerListFileName}\"";
  }
  echo ("
  	<input 
  		name='cancel_button' 
  		type='button' 
  		id='cancel_button' 
  		onClick='location=$locationString' 
  		value='Avbryt'>
  	"
  )
  ?>
     </form>

</body>
</html>
<?php
mysql_free_result($eventTypes);

mysql_free_result($eventDetails);

mysql_free_result($allAnswers);

}/* Enden på hovedlogikken */

function UpdateChangeLog($eventId, $playerId, $delete=false){
	/* Legger inn navn og dato i eventChangeLog-tabellen */
	global $database_innebandybase, $innebandybase;
	mysql_select_db($database_innebandybase, $innebandybase);
	if(! $delete){
		/* Legg inn oppføring */
		$query_updateLog ="
			INSERT INTO eventChangeLog SET
			event = $eventId ,
			player = $playerId ,
			date = NOW()
			";
	}else{
		/* Slette alle oppføringer i loggen */
		$query_updateLog ="
			DELETE FROM eventChangeLog WHERE event=$eventId
			";
	}
	$updateLog = mysql_query($query_updateLog, $innebandybase) or die(mysql_error());
	mysql_free_result($updateLog);
}

/**
 * @return html
 * @desc Generates code for team formfield;
*/
function TeamComboField($teamId=false){
	$out = "";
	global $teamsFieldName;
	$player = new Player_class();
	$team = new Team_class();
	$currentTeamId = $teamId ? $teamId : $team->get_id();
	if($player->getHasMultipleMemberships()){
		$teams = $player->GetTeamsData();
		$out = "
			<fieldset>
				<legend>Hvilket lag er hendelsen knyttet til?</legend>
			<table><tr><td>
		";
		$out .= "<select name='$teamsFieldName'>
			";
		foreach($teams as $team){
			$out .= "<option value = '$team[id]'";
			if ($team['id']==$currentTeamId){
				$out .= " selected";
			}	
			$out .= ">$team[longName]</option>
			";
		}
		$out .= "</select>";
		$out .= "
			</td></tr></table></fieldset>
			";
	}else{
		
		$out = "
			<input name='$teamsFieldName' type='hidden' value='$currentTeamId'>
		";
	}	
	return $out;
}

function MinAndMaxFields(){
	global $maxFieldName , $minFieldName , $minRadioFieldName;
	global $maxRadioFieldName, $typeFieldNameAndId; 
	global $eventTypesById , $row_eventDetails , $defaultMaxValue , $defaultMinValue;
	global $Fields;
	
	$currentType = $Fields['Type'];

	
	$currentMin = $defaultMinValue ? $eventTypesById[$currentType]['requiredCount'] : $row_eventDetails['minimumCount'];
	$currentMax = $defaultMaxValue ? $eventTypesById[$currentType]['maxCount'] : $row_eventDetails['maximumCount'];
	
	//Make JavaScript
	$maxValues = "";
	$minValues = "";
	foreach ($eventTypesById as $eType){
		$maxValues .= "
			arrayOfValues[{$eType[id]}]='$eType[maxCount]';";
		$minValues .= "
			arrayOfValues[{$eType[id]}]='$eType[requiredCount]';";
	}
	$scriptString = "
		function GetMaxOrMinValues(max,id){
			if (max){
				var arrayOfValues = new Array();
				$maxValues
			}else{
				var arrayOfValues = new Array();
				$minValues
			}
			return arrayOfValues[id];
		}
		
		function SetDefaultAndLock(max, lock){
			var currentType = document.getElementById('$typeFieldNameAndId').value;
			if (max){
				var textField = document.getElementById('maxCount');
				var cmbDefaultField = document.getElementById('chkMaxDefaultValue');
				var defaultValue = GetMaxOrMinValues(true,currentType);
			}else{
				var textField = document.getElementById('minCount');
				var cmbDefaultField = document.getElementById('chkMinDefaultValue');
				var defaultValue = GetMaxOrMinValues(false,currentType);
			}
			textField.value = defaultValue;
			if (lock){
				textField.disabled = true;
				cmbDefaultField.checked = true;
			}else{
				textField.disabled = false;
				cmbDefaultField.checked = false;
			}
		}
		
		function SetDefaultAndLockAll(){
			SetDefaultAndLock(true, true);
			SetDefaultAndLock(false, true);
		}
	";
	//End of JavaScript
	
	$out  = "<tr>
		";
	$out .= "<td>Minste antall<br>spillere:</td>
			 <td>
		
			<table><tr>
			<td><input name='$minRadioFieldName' 
				type='radio' 
				value='1'
				id='chkMinDefaultValue'
				onClick='SetDefaultAndLock(false, true);'";
	$out .= $defaultMinValue ? " checked" : "";
	$out .= ">
			 <span onClick='document.getElementById(\"chkMinDefaultValue\").checked=true;SetDefaultAndLock(false, true);'>Standard</span></td>
			 <td>&nbsp;</td>
			 <td><input name='$minRadioFieldName' 
				type='radio' 
				value='0'
				id='chkMinCustomValue'
				onClick='SetDefaultAndLock(false, false);'
				";
	$out .= $defaultMinValue ? "" : " checked";
	$disabledString = $defaultMinValue ? "disabled" : "";
	$out .= ">  
			 <span onClick='document.getElementById(\"chkMinCustomValue\").checked=true;SetDefaultAndLock(false, false);'>
			 Annen verdi: 
			 </span>
			 </td>
				<td><input name='$minFieldName' 
					value = '$currentMin'
					type='text' 
					id='minCount' 
					$disabledString 
					size='6' 
					maxlength='3'></td>
			</tr></table>
			";
	$out .= "</td></tr><tr>";
	$out .= "<td>Maksimalt<br>antall spillere:</td>
		  	<td>
			<table><tr>
			 
			<td><input name='$maxRadioFieldName' 
					type='radio' 
					value='1' 
					id='chkMaxDefaultValue'
					onClick='SetDefaultAndLock(true, true);'";
	$out .= $defaultMaxValue ? " checked" : "";		
	$out .= "> 
			 <span onClick='document.getElementById(\"chkMaxDefaultValue\").checked=true;SetDefaultAndLock(true, true);'>Standard</td>
			 </span>
			 <td>&nbsp;</td>
		  	 <td><input name='$maxRadioFieldName' 
					type='radio' 
					value='0'
					id='chkMaxCustomValue'
					onClick='SetDefaultAndLock(true, false);'";
	$out .= $defaultMaxValue ? "" : " checked";			
	$disabledString = $defaultMaxValue ? "disabled" : "";
	$out .= ">  
			 <span onClick='document.getElementById(\"chkMaxCustomValue\").checked=true;SetDefaultAndLock(true, false);'>Annen verdi: </td>
			 </span>
				<td><input name='$maxFieldName' 
					value = '$currentMax'
					type='text' 
					id='maxCount' 
					$disabledString 
					size='6' 
					maxlength='3'></td>
			</tr></table>";
	$out .= "
		</td></tr>
		";
	
	$out .= JavaScriptWrapper($scriptString); 
	
	return $out;
}

function GetNewMinAndMax(){
	global $typeFieldNameAndId,$maxFieldName,$minFieldName,$minRadioFieldName,$maxRadioFieldName;
	$eventType = $_POST[$typeFieldNameAndId];
	$out = array();
	
	// Determine defaults;
	$typeDefaultQueryString = "
		SELECT requiredCount, maxCount
		FROM eventTypes
		WHERE id='$eventType'
	";
	$typeDefault = new DBConnector_class($typeDefaultQueryString);
	$typeDefaultRow = $typeDefault->GetNextRow();
	//Get minValue
	if(    ($_POST[$minRadioFieldName]==0)
		&& (is_numeric($_POST[$minFieldName]))
		&& ($_POST[$minFieldName] != "")
		&& ($_POST[$minFieldName] != $typeDefaultRow['requiredCount'])
		){
		//Special value
		$out['min'] = $_POST[$minFieldName];
		$newMin = $out['min'];
	}else{
		//Default value	
		$out['min']='NULL';
		$newMin = $typeDefaultRow['requiredCount'];
	}
	
	//Get maxValue
	if(    ($_POST[$maxRadioFieldName]==0)
		&& (is_numeric($_POST[$maxFieldName]))
		&& ($_POST[$maxFieldName] != "")
		&& (($_POST[$maxFieldName] >= $newMin)||($_POST[$maxFieldName]==0))
		&& ($_POST[$maxFieldName] != $typeDefaultRow['maxCount'])
		){
		//Special value
		$out['max'] = $_POST[$maxFieldName];
		$newMax = $out['max'];
	}else{
		//Default value	
		$out['max']='NULL';
		$newMax = $typeDefaultRow['maxCount'];
	}
	
	// Assure that max > min
	if (($newMax > 0) && ($newMax < $newMin)){
		$out['max'] = 'NULL';	
	}
	
	return $out;
}
function WeekdayJavaScript(){
	global $weekdayJavaScriptName, $onChangeDateFieldsScript;
	$fieldname = "weekdayOfEvent_txt";
	$dayField = "startDate_text";
	$monthField="startMonth_text";
	$yearField="startYear_text";
	$script = "
		function $weekdayJavaScriptName(){
			var txtfield = document.getElementById('$fieldname');
			var yearField = document.getElementById('$yearField');
			var monthField = document.getElementById('$monthField');
			var dayField = document.getElementById('$dayField');
	
			var weekdays = new Array('mandag','tirsdag','onsdag',
				'torsdag','fredag','l%F8rdag','s%F8ndag');
			var dateValue = new Date(yearField.value,
									monthField.value-1,
									dayField.value-1);
			txtfield.innerText = unescape(weekdays[dateValue.getDay()]);
		}
		function $onChangeDateFieldsScript(){
			document.getElementById('$yearField').onchange=$weekdayJavaScriptName();
			document.getElementById('$monthField').onchange=$weekdayJavaScriptName();
			document.getElementById('$dayField').onchange=$weekdayJavaScriptName();
		}
	";
	$fieldString = "
		<span id=\"$fieldname\">dag</span>:
	";
	$out = JavaScriptWrapper($script);
	$out .= $fieldString;
	
	return $out;
}
function GetNotificationQueryString($timeDifference = 0){
	global $fieldNameNotify;
	$dateDummyObj = new DateControl_class();
	if ($_POST[$fieldNameNotify] != $dateDummyObj->GetNoDateIndicatorString()){
		$dateNotifiedValue = date('Y-m-d H:i:00',$_POST[$fieldNameNotify]+$timeDifference);
		$noNotify = false;
	}else{
		$noNotify = true;	
	}
	
	if ($noNotify){
		$notifyString = "
			notified = 1
		";	
	}else{
		$notifyString = "
			notified = 0,
			dateNotification = '$dateNotifiedValue'
		";	
	}
	return $notifyString;
}
?>