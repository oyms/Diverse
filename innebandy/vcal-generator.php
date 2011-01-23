<?php 
require_once('Connections/innebandybase.php');
require_once('commonCode.inc');
//Queries

//Get event id
$eventId = "0";
if (isset($HTTP_GET_VARS['Event'])) {
   $eventId= (get_magic_quotes_gpc()) ? $HTTP_GET_VARS['Event'] : addslashes($HTTP_GET_VARS['Event']);
}

/* Get event details */
mysql_select_db($database_innebandybase, $innebandybase);
$query_EventInfo = sprintf("
	SELECT dateStart, dateEnd, location, 
		description, type, cancelled, 
		notes, url, team, remediesNeeded
	FROM events 
	WHERE id = $eventId");
$EventInfo = mysql_query($query_EventInfo, $innebandybase) or die(mysql_error());
$row_EventInfo = mysql_fetch_assoc($EventInfo);
$totalRows_EventInfo = mysql_num_rows($EventInfo);

/* Get team name */
mysql_select_db($database_innebandybase, $innebandybase);
$query_TeamName = sprintf("SELECT longName FROM teams WHERE id = '%s'", $row_EventInfo['team']);
$TeamName = mysql_query($query_TeamName, $innebandybase) or die(mysql_error());
$row_TeamName = mysql_fetch_assoc($TeamName);
$totalRows_TeamName = mysql_num_rows($TeamName);

$teamNameValue = $row_TeamName["longName"];

/* Define vars */
$outputFilename="Hendelse{$eventId}.vcs";
$homePageUrl= HomePageUrl($eventId);
$eventDescription=str_replace("\n","\r",$row_EventInfo['notes']);
$eventDescription.=" <$homePageUrl>";
$eventDescription .= " ".RemediesReminder($row_EventInfo['remediesNeeded']);
$eventName=$teamNameValue . ": " . $row_EventInfo['description'];
$eventLocation=$row_EventInfo['location'];
$eventStart=ConvertDateTime($row_EventInfo['dateStart']);
$eventEnd=ConvertDateTime($row_EventInfo['dateEnd']);
$eventUrl= $row_EventInfo['url'] != "" ? "ATTACH;VALUE=URL:$row_EventInfo[url]\n" : "";
$eventCategories="Innebandy,Fritid";
$eventUid="innebandyhendelse-".$eventId;
$eventAttendeesString=GetAttendees($eventId);

header("Content-Type: text/x-vCalendar; charset=utf-8");
header("Content-Disposition: inline; filename=$outputFilename");

function HomePageUrl($eventId){
	$homePageUrl  = "http://".HOMEURL;
	$homePageUrl .= "?Event=";
	$homePageUrl .=	$eventId;
	return $homePageUrl;
}

function RemediesReminder($remediesValue){
	global $eventId;
	if ($remediesValue > 0){
		$player = new Player_class();
		$playerId = $player->getId();
		$ownRemediesQueryString = "
			SELECT remedies
			FROM attention
			WHERE player = $playerId AND event = $eventId
		";
		$ownRemediesQuery = new DBConnector_class($ownRemediesQueryString);
		$ownRemedies = $ownRemediesQuery->GetSingleValue("remedies");
		
		$remedies = new Remedies_class();
		$outValue .= $remedies->GetStringDescribingNeed($remediesValue, " Vi trenger ");
		
		if ($ownRemedies > 0){
			$outValue .= $remedies->GetStringDescribingNeed($ownRemedies," Ikke glem " );	
		}
	}
	$outValue = html_entity_decode($outValue);
	return $outValue;	
}

function ConvertDateTime($inString){
	$outString="";
	
	$outString .= date("Ymd\THi00",strtotime($inString));
	return $outString;
}
function GetAttendees($eventId){
	$outString="";
	global $database_innebandybase, $innebandybase, $homePageUrl;
	
	/* Get event organizer */
	$organizerQueryString = 
		"SELECT email, firstName, lastName
		FROM eventChangeLog LEFT JOIN players ON players.id = eventChangeLog.player
		WHERE eventChangeLog.event = $eventId
		ORDER BY eventChangeLog.date DESC
		";
	$organizerQuery = new DBConnector_class($organizerQueryString);
	$organizerData=$organizerQuery->GetNextRow();
	$organizer = $organizerData['email'];
	$organizerCommonName= $organizerData['firstName']." ".$organizerData['lastName'];
	
	/* Get attendees */
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_allAttentions = "	SELECT 	players.id AS playerId, players.firstName, 
										players.lastName, players.email,
										attention.id, attentionType.type, attention.notes, 
										attentionType.value, attention.registeredBy
								FROM 	(attention LEFT JOIN attentionType 
										ON attention.type = attentionType.id)
										INNER JOIN players ON attention.player = players.id
								WHERE (((attention.event)=".$eventId."));";
	$allAttentions = mysql_query($query_allAttentions, $innebandybase) or die(mysql_error());
	$row_allAttentions = mysql_fetch_assoc($allAttentions);
	$totalRows_allAttentions = mysql_num_rows($allAttentions);
	
	// Generate list
	do {
		$commonName = "$row_allAttentions[firstName] $row_allAttentions[lastName]";
		$outString .= "ATTENDEE";
		if ($row_allAttentions['registeredBy'] > 0){
			$otherPlayer = new OtherPlayer_class($row_allAttentions['registeredBy']);
			$outString .= ";SENT-BY=";
			if ($otherPlayer->GetEmail() != ""){
				$outString .= "MAILTO:".$otherPlayer->GetEmail();
			}else{
				$outString .= $homePageUrl;	
			}
			
		}
		$outString .= ";CN=\"$commonName\"";
		$outString .= ";ROLE=REQ-PARTICIPANT";
		$outString .= ";RSVP=FALSE";
		$outString .= ";PARTSTAT=";
		switch ($row_allAttentions['value']) {
			case 1:
				$outString .= "ACCEPTED";
				break;
			case 0:
				$outString .= "DECLINED";
				break;
			default:
				$outString .= "TENTATIVE";
				break;
		}
		$outString .= ":";
		if ($row_allAttentions['email'] != ""){
			$outString .= "MAILTO:";
			$outString .= "$row_allAttentions[email]";
		}else{
			$outString .= $homePageUrl;
		}
		$outString .= "\n";
	} while ($row_allAttentions = mysql_fetch_assoc($allAttentions));
	$outString .= "ORGANIZER";
	if ($organizer != ""){
	  if($organizerCommonName!=' '){
	  		$outString .= ";CN=\"$organizerCommonName\"";
	  	}
		$outString .= ":MAILTO:$organizer";	
	}else{
		$outString .= ":".$homePageUrl;
	}
	$outString .= "\n";
	$outString .= "CONTACT;ENCODING=QUOTED-PRINTABLE:".encodeQuotedPrintable($organizerCommonName)."\n";
	
	
	return $outString;
}



?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID: <?php echo $teamNameValue . "\n"; ?>
BEGIN:VTIMEZONE
TZID:Europe/Oslo
LAST-MODIFIED:20100314T200000Z
BEGIN:DAYLIGHT
DTSTART:20100328T020000
TZOFFSETTO:+0200
TZOFFSETFROM:+0100
TZNAME:CET
END:DAYLIGHT
BEGIN:STANDARD
DTSTART:20101031T030000
TZOFFSETTO:+0100
TZOFFSETFROM:+0200
TZNAME:CEST
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
SUMMARY;ENCODING=QUOTED-PRINTABLE;LANGUAGE=NO-BOK:<?php echo encodeQuotedPrintable(trim($eventName)) . "\n"; ?>
DESCRIPTION;ENCODING=QUOTED-PRINTABLE;LANGUAGE=NO-BOK:<?php echo encodeQuotedPrintable(trim($eventDescription)) . "\n"; ?>
LOCATION;ENCODING=QUOTED-PRINTABLE:<?php echo encodeQuotedPrintable(trim($eventLocation)) . "\n"; ?>
DTSTART;TZID=Europe/Oslo:<?php echo $eventStart . "\n"; ?>
DTEND;TZID=Europe/Oslo:<?php echo $eventEnd . "\n"; ?>
<?php echo $eventAttendeesString ; ?>
<?php echo $eventUrl; ?>
CATEGORIES;ENCODING=QUOTED-PRINTABLE:<?php echo encodeQuotedPrintable($eventCategories) . "\n"; ?>
CLASS: PRIVATE
PRIORITY:3
URL: <?php echo $homePageUrl . "\n"; ?>
UID: <?php echo $eventUid . "\n"; ?>
END:VEVENT
END:VCALENDAR