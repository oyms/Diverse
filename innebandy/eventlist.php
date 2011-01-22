<?php

require_once('Connections/innebandybase.php');
require_once('commonCode.inc');

$editEventButtonId = "editEvent_button";
$createEventButtonId = "newEvent_button";
$selectedEventHiddenFieldName = "selectedEvent_hidden";

echo HeaderHtml();
echo HeadingAndTopLinks();
echo EventList();
echo IcalLinks();
echo FooterHtml();


/* Classes */

class EventInList_class{
	var $id, $startDate, $endDate, $subject, $numberOfConfirmed, $numberOfMaybe;
	var $location, $myAnswer, $cancelled, $type, $team, $otherTeam;
	var $myAnswerRegisteredBy;
	var $answerLiterals, $typeLiterals, $notes, $teamShortName;
	var $numberOfAnswers, $minimumCount, $maximumCount, $hasMaxCount;
	function EventInList_class($eventArray){
		$thisTeam = new Team_class();
		$player = new Player_class();
		$this->answerLiterals = & $GLOBALS['_transient']['static']['eventinlist_class']->answerLiterals;
		$this->typeLiterals = & $GLOBALS['_transient']['static']['eventinlist_class']->typeLiterals;
		if (! count($this->answerLiterals)){
			$this->answerLiterals = $this->_GetAnswerLiteralsFromDB();
		}
		if (! count($this->typeLiterals)){
			$this->typeLiterals = $this->_GetTypeLiteralsFromDB();
		}
		if ($eventArray){
			$this->id = $eventArray['id'];
			$this->startDate = $eventArray['dateStart'];
			$this->endDate = $eventArray['dateEnd'];
			$this->subject = $eventArray['description'];
			$this->numberOfConfirmed = $eventArray['confirmed'];
			$this->location = $eventArray['location'];
			$this->cancelled = $eventArray['cancelled'];
			$this->type = $eventArray['type'];
			$this->team = $eventArray['team'];
			$this->notes = $eventArray['notes'];
			$this->maximumCount = $eventArray['maximum'];
			$this->minimumCount = $eventArray['minimum'];
			$this->hasMaxCount = $eventArray['hasMaxCount'];
			
			$this->otherTeam = !($this->team==$thisTeam->get_id());
			$teamsData = $player->GetTeamsData();
			$this->teamShortName = $teamsData[$this->team]['shortName'];
		}
	}
	function _GetAnswerLiteralsFromDB(){
		$out = array();
		global $database_innebandybase, $innebandybase;
		mysql_select_db($database_innebandybase, $innebandybase);
		$query_Answers="SELECT * FROM attentionType";
		$Answers = mysql_query($query_Answers, $innebandybase) or die(mysql_error());
		while($row_Answers = mysql_fetch_assoc($Answers)){
			$out[$row_Answers['id']] = $row_Answers['shortName'];	
		}	
		return $out;
	}
	function _GetTypeLiteralsFromDB(){
		$out = array();
		global $database_innebandybase, $innebandybase;
		mysql_select_db($database_innebandybase, $innebandybase);
		$query_Types="SELECT * FROM eventTypes";
		$Types = mysql_query($query_Types, $innebandybase) or die(mysql_error());
		while($row_Types = mysql_fetch_assoc($Types)){
			$out[$row_Types['id']]['name'] = $row_Types['type'];	
			$out[$row_Types['id']]['count'] = $row_Types['requiredCount'];	
			$out[$row_Types['id']]['invitation'] = $row_Types['invitationString'];		
			$out[$row_Types['id']]['description'] = $row_Types['description'];		
			$out[$row_Types['id']]['iconFile'] = $row_Types['iconFile'];	
		}	
		return $out;
	}
	function GetMaximumCount(){
		$out = 0;
		if ($this->GetHasMaxLimit()){
			$out = $this->maximumCount;
			$min = $this->GetMinimumCount();	
			if ($out < $min){
				$out = $min;	
			}
		}
		return $out;
	}
	function GetHasMaxLimit(){
		return $this->hasMaxCount;	
	}
	function SetMaximumCount($max){
		$this->maximumCount = $max;	
	}
	function GetMinimumCount(){
		return $this->minimumCount;	
	}
	function SetMinimumCount($min){
		$this->minimumCount = $min;	
	}
	function GetIsOtherTeam(){
		return $this->otherTeam;	
	}
	function GetIsSufficient(){
		$out = ($this->GetMinimumCount() <= $this->numberOfConfirmed);
		return $out;
	}
	function GetTypeInvitation(){
		$out = $this->typeLiterals[$this->type]['invitation'];
		return $out;
	}
	function GetTypeDescription(){
		$out = $this->typeLiterals[$this->type]['description'];
		return $out;		
	}
	function GetTypeIconFile(){
		$out = $this->typeLiterals[$this->type]['iconFile'];
		return $out;
	}
	function GetPercentOfRequired(){
		$required = $this->GetMinimumCount();
		$count = $this->GetNumberOfConfirmed();
		$out = "";
		if ($required){
			$out = ($count/$required)*100;
		}
		return $out;
	}
	function GetIsTooManyPlayers(){
		$out = false;
		if ($this->GetHasMaxLimit()){
			if ($this->GetNumberOfConfirmed()>$this->GetMaximumCount()){
				$out = true;	
			}	
		}
		return $out;
	}
	function GetStatusOfPlayersCount(){
		if ($this->GetNumberOfAnswers()){
			if ($this->GetIsSufficient()){
				/* Enough players */
				$out = 2;
			}else{
				/* Not yet enough players */
				$out = 1;
			}
		}else{
			/*No replies to event */
			$out = 0;
		}
		return $out;
	}
	function GetTeamShortName(){
		return $this->teamShortName;	
	}
	function GetIsCancelled(){
		return $this->cancelled;
	}
	function GetLinkUrlAndTarget(){
		global $detailsFrameName, $eventFieldname, $playerIdFieldName;
		$player = new Player_class();
		$playerId = $player->getId();
		$thisId = $this->GetId();
		$url = $this->GetLinkUrl();
		$out = "href='{$url}' target='$detailsFrameName'";
		return $out;
	}
	function GetLinkUrl(){
		global $detailsFrameName, $eventFieldname, $playerIdFieldName;
		$player = new Player_class();
		$playerId = $player->getId();
		$thisId = $this->GetId();
		$out = "eventdetails.php?{$playerIdFieldName}={$playerId}&{$eventFieldname}={$thisId}";
		return $out;
	}
	function GetId(){
		return $this->id;	
	}
	function GetSubject(){
		return $this->subject;	
	}
	function GetNotes(){
		return $this->notes;	
	}
	function GetNumberOfConfirmed(){
		return $this->numberOfConfirmed;	
	}
	function GetNumberOfMaybe(){
		return $this->numberOfMaybe;
	}
	function GetNumberOfAnswers(){
		return $this->numberOfAnswers;
	}
	function SetNumberOfAnswers($count){
		$this->numberOfAnswers = $count;
	}
	function GetConfirmedAndMaybe(){
		$out = "";
		$maybe = $this->GetNumberOfMaybe();
		$confirmed = $this->GetNumberOfConfirmed();
		if($maybe){
			$out .= $confirmed ? $confirmed : "0";
			$out .= "<span class=smallText>+$maybe</span>";
		}else{
			$out = $confirmed;
		}
		return $out;
	}
	function SetConfirmed($count){
		$this->numberOfConfirmed = $count;
	}
	function SetMaybe($count){
		$this->numberOfMaybe = $count;
	}
	function GetLocation(){
		return $this->location;	
	}
	function SetMyAnswer($answerType, $registeredBy=0){
		$player = new Player_class();
		$playerId = $player->getId();
		$this->myAnswer=$answerType;
		if ($registeredBy >0 && $registeredBy != $playerId){
			$this->myAnswerRegisteredBy = $registeredBy;	
		}
	}
	function GetMyAnswerRegisteredBy(){
		$out = $this->myAnswerRegisteredBy;
		$player = new Player_class();
		if ($out == $player->getId()){
			$out = 0;	
		}	
		return $out;
	}
	function GetMyAnswer($inWords = true){
		$answer = $this->myAnswer;
		if ($answer){
			$regByOther = ($this->GetMyAnswerRegisteredBy() > 0 );
			$out = $regByOther ? "(" : "";
			$out .= $inWords ? $this->answerLiterals[$answer] : $answer;
			$out .= $regByOther ? ")" : "";
		}else{
			$out = "";
		}
		return $out;	
	}
	function GetDate($type="short", $dateField="start"){
		$out = "";
		$dateVal="";
		switch ($dateField){
			case "start":
				$dateVal=$this->startDate;
			break;
			case "end":
				$dateVal=$this->endDate;
			break;
		}
		switch ($type){
			case "short":
				$out = date("d/m",strtotime($dateVal));
				if(strtotime($dateVal)-time() > (60*60*24*180)){
					$out .= date(" Y",strtotime($dateVal));
				}else if(date("G:i",strtotime($dateVal))!="0:00"){
					$out .= date(" G:i",strtotime($dateVal));
				}
			break;
			case "friendly":
				$out = norDate($dateVal);
			break;
			case "timestamp":
				$out = strtotime($dateVal);
			break;
			default:
				$out = $dateVal;
		}
		return $out;
	}
}


/* Functions */

/**
 * Renders links to ical
 *
 * @return html
 */
function IcalLinks(){
	global $icalGeneratorFilename;
	$player = new Player_class();
	$playerId = $player->getId();
	$url=HOMEURL."$icalGeneratorFilename?player=$playerId";
	$toolTipDownload="Last ned kalenderen som en iCal-fil.";
	$toolTipSubscribe="Abonner på kalenderen. Det forutsetter at du har et program som kan gjøre dette.";
	$output ="\n<hr>\n";
	$output.="<span class='verySmallText'><a href='http://$url' title='$toolTipDownload'>Last ned kalender</a>";
	$output.=" | ";
	$output.="<a href='webcal://$url' title='$toolTipSubscribe'>Abonner på kalender</a></span>";
	return $output;
}

function HeadingAndTopLinks(){
	global $detailsFrameName , $editEventButtonId, $detailsFrameName;
	global $createEventButtonId , $selectedEventHiddenFieldName;
	$player = new Player_class();
	$playerId = $player->getId();
	$out = "";//HomeIconLink('right');
	/*
	$out .= "<h2>Hendelsesliste</h2>
	";
	*/
	$out .= "
		<form 
			action='selectEvent_form' 
			method='get'
			name='events_form' 
			id='events_form'>
	";
	$out .= "
    	<input 
			name='$createEventButtonId'
			type='button' 
			id='$createEventButtonId' 
			onClick='top.{$detailsFrameName}.location.href = \"eventform.php?Player={$playerId}\";' 
			value='Registrere ny hendelse'>
    	<input 
			name='$editEventButtonId' 
			type='button' 
			id='$editEventButtonId' 
			value='Redigere hendelse' 
			onClick='top.{$detailsFrameName}.location.href = \"eventform.php?Player={$playerId}&Event=\"+currentHighlight;'
			disabled='true'>";
 	$out .= "\n<hr>\n";
 	$out .= "
		<input 
			name='$selectedEventHiddenFieldName' 
			type='hidden' 
			value='0' 
			id='$selectedEventHiddenFieldName'>
		</form>
	";
	/* Links to messagelist */
	//$out .= MessageListLink();
	
	return $out;
}



function EventList(){
	$player = new Player_class();
	if($player->getId()){
		$events = EventArray();
		$out = FormattedList($events);
	}
	return $out;
}

function MyAnswers(){
	global $database_innebandybase, $innebandybase;
	$answersArray = array();
	$player = new Player_class();
	$playerId = $player->getId();
	$query_Answers="
		SELECT attention.type, events.id, attention.registeredBy
		FROM attention
		LEFT JOIN events ON attention.event = events.id
		WHERE player=$playerId
		AND events.dateStart > NOW()
	";
	$Answers = new DBConnector_class($query_Answers);
	while($row_Answers = $Answers->GetNextRow()){
		$answersArray[$row_Answers['id']] = 
			array('type'=>$row_Answers['type'], 'regBy' => $row_Answers['registeredBy']);
	}
	return $answersArray;
}
function EventArray(){
	$allEvents = array();
	$confirmedValue = 2;
	$myAnswers = MyAnswers();
	$player = new Player_class();
	if($player->getId()){
		$teamsString = implode(",",$player->getTeams());
		$confirmedArray = Confirmed($teamsString,2);
		$maybeArray = Confirmed($teamsString,3);
		$allAnswersArray = Confirmed($teamsString,0);
		$query_Events="
			SELECT events.*,
				IF(events.minimumCount IS NULL, eventTypes.requiredCount, events.minimumCount) AS minimum,
				IF(events.maximumCount IS NULL, eventTypes.maxCount, events.maximumCount) AS maximum,
				IF(
						(
							(events.maximumCount IS NULL)
						 AND 
							(eventTypes.maxCount IS NULL)
						) 
						OR 
							(
								(events.maximumCount=0)
							AND
								!(events.maximumCount IS NULL)
							)
		
					, 0, 1) AS hasMaxCount
			FROM events
			LEFT JOIN eventTypes
				ON events.type = eventTypes.id
			WHERE (dateEnd > NOW())
				AND (team IN ($teamsString))
			ORDER BY events.dateStart ASC
		";
		$Events = new DBConnector_class($query_Events);
		while($row_Events = $Events->GetNextRow()){
			$newEvent = new EventInList_class($row_Events);
			$newEvent->SetMyAnswer($myAnswers[$row_Events['id']]['type'],$myAnswers[$row_Events['id']]['regBy']);
			$newEvent->SetConfirmed($confirmedArray[$row_Events['id']]);
			$newEvent->SetMaybe($maybeArray[$row_Events['id']]);
			$newEvent->SetNumberOfAnswers($allAnswersArray[$row_Events['id']]);
			$allEvents[]=$newEvent;
		}
	}
	return $allEvents;
}

function Confirmed($teamString, $attentionType=2){
	$allEvents = array();	
	if ($teamString){
		if($attentionType){
			$attentionString = "WHERE attention.type=$attentionType
				";
		}else{
			/* Get all answers */
			$attentionString = "";
		}
		$query_Events="
			SELECT events.id, 
				Count(attention.id) AS confirmedCount, 
				attention.type, 
				events.team, 
				events.dateStart
			FROM attention 
			LEFT JOIN events 
				ON attention.event = events.id
			$attentionString 
			GROUP BY events.id, events.team, events.dateStart
			HAVING (
				((events.team) In ($teamString)) 
				AND ((events.dateStart)>Now()))
		";
		$Events = new DBConnector_class($query_Events);
		while($row_Events = $Events->GetNextRow()){
			$allEvents[$row_Events['id']]=$row_Events['confirmedCount'];
		}
	}
	return $allEvents;
}

function FormattedList($eventsArray){
	if (count($eventsArray)){
		/* Make output of eventlist */
		$out = "
			<table width='95%'>
			";
		$out .= ListHeaderRow();
		foreach ($eventsArray as $event){
			$out .= EventRow($event);	
		}
		$out .= "
			</table>
		";
	}else{
		$out = "<p>Det er ingen hendelser i listen. Klikk over for &aring; legge til.</p>";	
	}
	return $out;
}

function MouseOverEffectsJavaScript(){
	$script = "
		// MouseOverEffects
		function  EventMouseOver(obj){
			obj.style.backgroundColor = '#ccccff';
		}
		function EventMouseOut(obj){
			obj.style.backgroundColor = '';
			if (typeof(UpdateHighlighting) != 'undefined'){
				UpdateHighlighting();
			}
		}
	";
	return JavaScriptWrapper($script);
}

/**
 * @return HTML
 * @param EventInList_class $event
 * @desc HTML to render one row in eventlist
*/
function EventRow($event){
	global $detailsFrameName;
	$eventId = $event->GetId();
	$linkString = $event->GetLinkUrlAndTarget();
	$url = $event->GetLinkUrl();
	$otherTeam = $event->GetIsOtherTeam();
	$pastMaxLimit = $event->GetIsTooManyPlayers();
	$isCancelled = $event->GetIsCancelled();
	$out .= "<tr id='event{$eventId}'";
	$out .= " class='hslice";
	$out .= $otherTeam ? " EventOtherTeam'" : "'";
	$out .= " onMouseOver='EventMouseOver(this)'
			  onMouseOut = 'EventMouseOut(this)'
			  onClick = 'top.$detailsFrameName.location.href=\"{$url}\"'>";
	
	$sufficientConfirmed = $event->GetIsSufficient();
	/* Ball */
	if (! $isCancelled){
		if(! $pastMaxLimit){
			switch ($event->GetStatusOfPlayersCount()){
				case 0:
					$out .= TableCellWrapper("");
				break;
				case 1:
					$percentage = round($event->GetPercentOfRequired());
					$altString = $percentage ? "Enda ikke mange nok sikre p&aring;meldinger. ({$percentage}% av minimum.)" : "Det er ingen sikre p&aring;meldinger...";
					$out .= TableCellWrapper(
					'<img src="images/BallQuestionMark.gif" 
						width="20" 
						height="20" 
						border="0"
						alt = "Usikker hendelse"
						title = "'.$altString.'">',false,$linkString);
				break;
				case 2:
					$out .= TableCellWrapper(
					'<img src="images/Ball.gif" 
						width="20" 
						height="20" 
						border="0"
						alt = "Sikker hendelse"
						title = "Det er mange nok som har meldt seg p&aring;.">',false,$linkString);
				break;
			}
		}else{
			// Too many players
			$out .= TableCellWrapper(
					'<img src="images/BallExclamation.gif" 
						width="20" 
						height="20" 
						border="0"
						alt = "Full hendelse"
						title = "Det er for mange p&aring;meldte.">',false,$linkString);	
		}
	}else{
		$out .= TableCellWrapper(
				'<img src="images/BallCancelled.gif" 
					width="20" 
					height="20" 
					border="0"
					alt = "Avlyst hendelse"
					title = "Hendelsen er avlyst!">',false,$linkString);
	}
	/* Type icon */
	$typeIcon = $event->GetTypeIconFile();
	if ($typeIcon != ""){
		$typeIconAltString = ($event->GetMyAnswer() == "" && (! $isCancelled)) ? $event->GetTypeInvitation() : $event->GetTypeDescription();
		$typeDescription = $event->GetTypeDescription();
		$typeIconCode = "
			<img src='$typeIcon' alt='$typeIconAltString' title='$typeDescription'width='24' height='24' border='0'>
			";
	}else{
		$typeIconCode = "";
	}
	$typeIconWrappedInRelLink="<a rel='feedurl' href='eventinfo.php?Event={$eventId}'>{$typeIconCode}</a>";
	$out .= TableCellWrapper($typeIconWrappedInRelLink,true);
	
	/* Start */
	if ($isCancelled){
		$cancelClassString = " class='endtime cancelledEventInList'";
	}else{
		$cancelClassString = " class='endtime'";
	}
	$dateTitle = RecentDate($event->GetDate("timestamp", "start"));
	$dateString .= $sufficientConfirmed ? "<strong>" : "";
	$dateString .= "<span $cancelClassString>".
		$event->GetDate("short","start")
		."</span>";
	$dateString .= $sufficientConfirmed ? "</strong>" : "";
	$out .= TableCellWrapper($dateString, false, "", $dateTitle);
	
	/* Description */
	$description = "<span class='entry-title'>";
	$description .= $event->GetSubject();
	$description .= $otherTeam ? " <em>(".$event->GetTeamShortName().")</em>" : "";
	$description .= "</span>";
	$out .= TableCellWrapper($description, false, $linkString, $event->GetNotes());
	
	if($isCancelled){
		$out .= "<td colspan='3' class=cancelledEventInList align='center' title='Avlyst!'>".$event->GetLocation()."</td>";
	}else{
		/* Confirmed count */
		$confirmedCountTitle = "Det er ".NorwegianTextNumbers($event->GetNumberOfAnswers());
		$confirmedCountTitle .= " som har svart p&aring; invitasjonen.";
		if ($event->GetNumberOfAnswers() > 1){
			$confirmedCountTitle .= " Av disse har ".NorwegianTextNumbers($event->GetNumberOfConfirmed());
			$confirmedCountTitle .= " sagt &laquo;ja&raquo;.";
			if ($event->GetNumberOfMaybe() > 0){
				$confirmedCountTitle .= " Det er ".NorwegianTextNumbers($event->GetNumberOfMaybe());
				$confirmedCountTitle .= " som har sagt &laquo;jeg kommer antagelig&raquo;.";
			}
		}
		$out .= TableCellWrapper($event->GetConfirmedAndMaybe(),true, "", $confirmedCountTitle);
		/* Location */
		$locationTitle = $event->GetLocation()." er stedet der det hele finner sted.";
		$out .= TableCellWrapper($event->GetLocation(),true, "", $locationTitle);
		/* My answer */
		if( $event->GetMyAnswer() == "") {
			$myAnswerTitle =  "Du har ikke svart p&aring; denne invitasjonen";
		}else{
			$myAnswerTitle = "Du har svart &laquo;".$event->GetMyAnswer()."&raquo; p&aring; denne invitasjonen.";
		} 
		$out .= TableCellWrapper($event->GetMyAnswer(),true, "", $myAnswerTitle);
	}
	$out .= "</tr>";
	return $out;
}

function TableCellWrapper($string,$center=false, $linkString="" , $title = false){
	$alignString = $center ? " align='center'" : "";
	$titleString = $title ? " title='$title'" : "";
	$out = "<td{$alignString} class=eventList $titleString>";
	$out .= $linkString ? "<a {$linkString}{$titleString}>" : "";
	$out .= $string;
	$out .= $linkString ? "</a>" : "";
	$out .= "</td>";
	return $out;	
}

function ListHeaderRow(){
	$out = '
	<tr> 
      <td><div align="center"></div></td>
      <td></td>
      <td><img src="images/ikonstart.gif" alt="Starttidspunkt" width="36" height="36"></td>
	  <td><div align="center"><img src="images/ikonbeskrivelse.gif" alt="Beskrivelse" width="46" height="32"></div></td>
      <td><div align="center"><img src="images/ikonantall.gif" alt="P&aring;meldte" width="37" height="32"></div></td>
      <td><div align="center"><img src="images/ikonsted.gif" alt="Sted" width="43" height="32"></div></td>
      <td><div align="center"><img src="images/ikonsvar.gif" alt="Mitt svar" width="32" height="32"></div></td>
    </tr>
	';
	return $out;	
}


function HeaderHtml(){
	$player = new Player_class();
	$teamId=$player->getCurrentTeam();
	$pageTitle = "Hendelsesliste";
	$startPage = "
	<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<html>
	<head>
	".RenderCSSLink($teamId)."
	<title>$pageTitle</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
	<meta http-equiv=\"refresh\" content=\"600\">
	".RefreshJavaScript()."
	".HighlightEventJavaScript()."
	".MouseOverEffectsJavaScript()."
	</head>
	<body>
	";
	return $startPage;
}

function FooterHtml(){
	$refreshScript = UpdateHighlightJavaScript();
	$endPage = "
		$refreshScript
	</body>
	</html>
	";
	return $endPage;
}


function RefreshJavaScript(){
	global $playerIdFieldName;
	$player = new Player_class();
	$playerId = $player->getId();
	$out .= "
	function RefreshEventPage(){
		var eventNo, anchorTxt;
		eventNo = RefreshEventPage.arguments[0];
		if (eventNo) {
			anchorTxt = '#event'+msgNo;
		}else{
			anchorTxt = '';
		}
		location = '$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$playerId'+anchorTxt;
	}";
	$out = JavaScriptWrapper($out);
	return $out;
}
function HighlightEventJavaScript(){
	global $editEventButtonId;
	$script = "";
	$script .= "
		var currentHighlight = 0;
		function HighlightEvent(eventId){
			if(currentHighlight){
				var oldRow = document.getElementById('event'+currentHighlight);
				if (oldRow){
					oldRow.style.backgroundColor = '';
				}
			}
			currentHighlight = eventId;
			var newRow = document.getElementById('event'+eventId);
			editBtn = document.getElementById('{$editEventButtonId}');
			if (newRow){
				editBtn.disabled=false;
				newRow.style.backgroundColor = '#dddddd';
			}else{
				editBtn.disabled=true;
			}
			
			
		}
	";
	$out = JavaScriptWrapper($script);
	return $out;
}
function UpdateHighlightJavaScript(){
	global $detailsFrameName;
	$script = "
		// Update highlighting
		UpdateHighlighting();
		function UpdateHighlighting(){
			if(top.$detailsFrameName.GetCurrentEvent){
				HighlightEvent(top.$detailsFrameName.GetCurrentEvent());
			}
		}
	";
	return JavaScriptWrapper($script);
}

function MessageListLink(){
	$out = "<table><tr><td style='font-size: larger'>";
	$player = new Player_class();
	$playerId = $player->getId();
	$starttag = "<a href=messagelist.php?player=$playerId>";
	$endtag = "</a>";
	if ($newMsg = GetNumberOfNewMsg()){
		$numberString = NorwegianTextNumbers($newMsg, false);
		$out .= $starttag;
		$out .= "Det er skrevet $numberString melding";
		$out .= ($newMsg > 1) ? "er " : " ";
		$out .= "siden sist du var her.";
		$out .= $endtag;
	}else{
		$out .= "
			$starttag
				G&aring; til meldingsliste
			$endtag
		";
	}
	$out .= "</td>";
	$out .= "</tr></table>";
	return $out;
}

function GetNumberOfNewMsg(){
	global $innebandybase, $database_innebandybase;
	$player = new Player_class();
	$playerId = $player->getId();
	$teams = $player->getTeams();
	$teamsQueryString = MessageQueryTeamFilterString($teams);
	$lastLogin = $player->getDate("default","lastLogin");
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_Msgs = "
		SELECT messages.id
		FROM messages
		WHERE messages.date > '$lastLogin'
		$teamsQueryString
	";
	$Msgs = mysql_query($query_Msgs,$innebandybase) or die(mysql_error());
	$out = mysql_num_rows($Msgs);
	return $out;
}
?>