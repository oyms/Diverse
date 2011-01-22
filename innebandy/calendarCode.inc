<?php
//----------
//Functions
//----------
/**
 * Converts a date (as string) into a proper date format for ICAL without time zone
 *
 * @param string $inString
 * @return string
 */
function ConvertDateTimeToVCalFormat($inString){
	$outString="";
	
	$outString .= date("Ymd\THi00",strtotime($inString));
	return $outString;
}

//----------
//Classes
//----------
/**
 * Time zone information (Europe/Oslo) to include in a ICAL file
 *
 */
class CalendarTimeZone{
  var $timeZoneName="Europe/Oslo";
  function CalendarTimeZone(){}
  /**
   * Returns the string "Europe/Oslo"
   *
   * @return string
   */
  function Name(){
    return $this->timeZoneName;
  }
  /**
   * Renders the time zone for use in VCAL
   *
   * @return string
   */
  function Definition(){
	  $output=
"BEGIN:VTIMEZONE
TZID:".$this->Name()."
X-LIC-LOCATION:".$this->Name()."
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
";
return $output;
	}
}
/**
 * Provides access to coming events for current user
 *
 */
class EventFactory{
	var $currentTeam;
	var $currentPlayer;
	var $events;
	var $eventTypes;
	var $teams;
	function EventFactory(){
		$this->currentPlayer=new Player_class();
		$this->currentTeam=new Team_class();
		$this->_queryEventTypes();
		$this->_queryEvents();
	}
	function _queryEventTypes(){
		$eventTypeQueryString="SELECT * FROM eventTypes";
		$query=new DBConnector_class($eventTypeQueryString);
		while($type=$query->GetNextRow()){
			$this->eventTypes[$type["id"]]=$type;
		}
	}
	/**
	 * Provides an param array with data on an event type
	 *
	 * @param int $id
	 * @return array
	 */
	function GetEventType($id){
		return $this->eventTypes[$id];	
	}
	/**
	 * Gets data on a given event type
	 *
	 * @param int $id
	 * @param string $field
	 * @return string
	 */
	function GetEventTypeData($id,$field){
		$type=$this->GetEventType($id);
		return $type[$field];
	}
	/**
	 * Gets name of a given event type
	 *
	 * @param int $id
	 * @return string
	 */
	function GetEventTypeName($id){
		return $this->GetEventTypeData($id,"type");
	}
	function _queryEvents(){
		$this->teams=array();
		$this->events=array();
		$player=$this->currentPlayer;
		$team=$this->currentTeam;
		$eventQueryString="
			SELECT 
			events.id, dateStart,dateEnd,location,events.description,
			events.type,cancelled,notes,url,team,remediesNeeded,
			IF(events.minimumCount IS NULL, eventTypes.requiredCount, events.minimumCount) AS minimum,
			IF(events.maximumCount IS NULL, eventTypes.maxCount, events.maximumCount) AS maximum
			FROM events
			LEFT JOIN eventTypes
				ON events.type = eventTypes.id
			WHERE dateEnd>NOW() AND team ";
		if($player->getId()>0){
			$eventQueryString.=" in (".implode(",",$player->getTeams()).")";
		}else{
			$eventQueryString.="=".$team->get_id();
		}
		$query=new DBConnector_class($eventQueryString);
		while($eventData=$query->GetNextRow()){
			$teamId=$eventData["team"];
			if(!$this->teams[$teamId]){
				$this->teams[$teamId]=new OtherTeam_class($teamId);
			}
			$eventData["eventTeam"]=$this->teams[$teamId];
			$eventData["eventType"]=$this->GetEventType($eventData["type"]);
			$event=new CalendarEvent($eventData);
			$this->events[]=$event;
		}
	}
	/**
	 * Gets an array of events
	 *
	 * @return CalendarEvent[]
	 */
	function GetEvents(){
		return $this->events;
	}
}

/**
 * Contains data about an event.
 *
 */
class CalendarEvent{
	var $id;
	var $dateStart;
	var $dateEnd;
	var $location;
	var $description;
	var $type;
	var $cancelled;
	var $notes;
	var $url;
	var $team;
	var $minimumCount;
	var $maximumCount;
	var $remediesNeeded;
	/**
	 * Constructor requires an paramArray from a query into events table
	 *
	 * @param string[] $eventData
	 * @return CalendarEvent
	 */
	function CalendarEvent($eventData){
		$this->id=$eventData["id"];
		$this->dateStart=$eventData["dateStart"];
		$this->dateEnd=$eventData["dateEnd"];
		$this->location=$eventData["location"];
		$this->description=trim($eventData["description"]);
		$this->type=$eventData["eventType"];
		$this->cancelled=$eventData["cancelled"];
		$this->notes=trim($eventData["notes"]);
		$this->url=$eventData["url"];
		$this->team=$eventData["eventTeam"];
		$this->minimumCount=$eventData["minimum"];
		$this->maximumCount=$eventData["maximum"];
		$this->remediesNeeded=(int)$eventData["remediesNeeded"];
	}
	/**
	 * True if event is cancelled
	 *
	 * @return boolean
	 */
	function Get_IsCancelled(){
		return $this->cancelled;
	}
	/**
	 * Gets the url to the page describing the event
	 *
	 * @return url
	 */
	function GetDetailPageUrl(){
		$url="http://".HOMEURL."?Event=$this->id";
		return $url;
	}
	/**
	 * Renders the event suitable for ical 2.0 VEVENT
	 *
	 * @return string
	 */
	function RenderAsVEvent(){
		$editor=new EditorOfEvent_class($this->id);
		$changeDate=max($editor->GetLastEditTime(),$this->newestAnswer);
		$tz=new CalendarTimeZone();
		$tzIdString=";TZID=".$tz->Name();
		$cancel=$this->Get_IsCancelled();
		if(!$cancel){
			$this->_queryAttentions();
			if($this->ownReplyType==4){
				return "";
			}
		}
		$output ="BEGIN:VEVENT\r\n";
		$output.="UID:Hendelse{$this->id}@skaar.freeshell.org/innebandy\r\n";
		$output.="DTSTAMP:".ConvertDateTimeToVCalFormat($changeDate)."\r\n";
		$output.="DTSTART$tzIdString:".ConvertDateTimeToVCalFormat($this->dateStart)."\r\n";
		$output.="DTEND$tzIdString:".ConvertDateTimeToVCalFormat($this->dateEnd)."\r\n";
		//$output.="CLASS:PRIVATE\r\n";
		if($this->contactsString){
			$output.=$this->contactsString;
		}
		$categories=$this->type["type"].",".$this->team->getName();
		$output.="CATEGORIES:".utf8_encode($categories)."\r\n";
		$description=$this->team->getName("long").", ";
		if($this->url!=""){
			$description.="<".$this->url.">";	
		}
		if($cancel){
			$description.="Hendelsen er avlyst! ";	
			$output.="SUMMARY:Avlyst: ".utf8_encode($this->description)."\r\n";
			$output.="TRANSP:TRANSPARENT\r\n";
			$output.="STATUS:CANCELLED\r\n";
			$output.="X-MICROSOFT-CDO-BUSYSTATUS:FREE\r\n";
			$output.="X-MICROSOFT-CDO-IMPORTANCE:0\r\n";
		}else{
			$description.=$this->type["description"]." ";
			$description.=trim($this->notes);
			$description.=" ".$this->repliesSummaryString;
			if($this->minimumCount){
				$description.=" Det er satt en nedre grense på ".
					NorwegianTextNumbers($this->minimumCount,false,false,false).
					" deltakere.";
			}
			if($this->maximumCount){
				$description.=" Det er satt en øvre grense på ".
					NorwegianTextNumbers($this->maximumCount,false,false,false).
					" deltakere.";
			}
			$description.=" ".$this->ownReply;
			if($this->repliesString){
				//$output.=$this->repliesString;
			}
			if($this->remediesString){
				$description.=$this->remediesString;
			}
			$output.="SUMMARY:".utf8_encode($this->description)."\r\n";
			//Set status
			$certain=$this->numberOfCertainReplies>=$this->minimumCount;
			$willCome=$this->ownReplyType==2;
			if($certain&&$willCome){
				//Confirmed
				$output.="TRANSP:OPAQUE\r\n";
				$output.="STATUS:CONFIRMED\r\n";
				$output.="X-MICROSOFT-CDO-BUSYSTATUS:BUSY\r\n";
				$output.="X-MICROSOFT-CDO-IMPORTANCE:2\r\n";
			}else{
				if($this->ownReplyType==4){
					//No show
					$output.="TRANSP:TRANSPARENT\r\n";
					$output.="STATUS:CANCELLED\r\n";
					$output.="X-MICROSOFT-CDO-BUSYSTATUS:FREE\r\n";
					$output.="X-MICROSOFT-CDO-IMPORTANCE:0\r\n";
				}else{
					$output.="TRANSP:OPAQUE\r\n";
					$output.="STATUS:TENTATIVE\r\n";
					$output.="X-MICROSOFT-CDO-BUSYSTATUS:TENTATIVE\r\n";
					$output.="X-MICROSOFT-CDO-IMPORTANCE:1\r\n";
				}
			}
		}
		if($description!=""){$description.=" ";}
		$description.="<".$this->GetDetailPageUrl().">";
		if($description!=""){$description.=" ";}
		$description.=$editor->GetHistoryString(false);
		$output.="DESCRIPTION:".utf8_encode(str_replace(",","\,",$description))."\r\n";
		$output.="LOCATION:".utf8_encode(str_replace(",","\,",$this->location))."\r\n";
		$output.="PRIORITY:3\r\n";
		$output.="URL:{$this->url}\r\n";
		$output.="ORGANIZER;CN=\"".utf8_encode($editor->GetNameOfCreator()."\":mailto:".$editor->GetEmailOfEditor())."\r\n";
		$output.="END:VEVENT\r\n";
		return $output;
	}
	var $remediesString;
	var $myAnswer;
	var $repliesString;
	var $repliesSummaryString;
	var $contactsString;
	var $newestAnswer;
	var $ownReply;
	var $ownReplyType;
	var $numberOfCertainReplies=0;
	function _queryAttentions(){
		$player=new Player_class();
		$playerId=$player->getId();
		if($playerId){
			$this->ownReply="Du har enda ikke svart på denne hendelsen. ";
		}
		$remediesLeft=$this->remediesNeeded;
		$queryString="
			SELECT 
				attention.id,event,player AS playerId,type,notes,
			 	date,initialDate,customValue,remedies,firstName,lastName,
			 	residence,phone,email,registeredBy
			FROM attention 
			LEFT JOIN players 
			ON attention.player=players.id
			WHERE event=".$this->id;
		$query=new DBConnector_class($queryString);
		$replyArray=array();
		while($reply=$query->GetNextRow()){
			if($reply["playerId"]==$playerId){
				$this->ownReplyType=$reply["type"];
				$this->ownReply=$this->_renderOwnReply($reply);
			}else{
				$this->contactsString.=$this->_renderContact($reply);
			}
			$this->repliesString.=$this->_renderParticipant($reply);
			$this->newestAnswer=max(strtotime($reply["date"]),$this->newestAnswer);
			$remediesLeft-=((int)$reply["remedies"]&$remediesLeft);
			$replyArray[$reply["type"]]++;
		}
		$lastAnswer=$this->newestAnswer?$this->newestAnswer:time();
		$remedies=new Remedies_class();
		$this->remediesString=$remedies->GetStringDescribingNeed($remediesLeft,"Vi mangler stadig ",false)."\n";
		$this->repliesSummaryString="Status per ".RecentDate($this->newestAnswer,false)." er at ".$this->_renderReplySummary($replyArray).".";
		$this->numberOfCertainReplies=$replyArray[2];
	}
	function _renderReplySummary($replyArray){
		if($replyArray){
		$replyList=array();
		$literals=new AnswerLiterals_class();
		for($n=0;$n<$literals->GetNumberOfReplyTypes();$n++){
			$replyTypeId=$literals->GetIdFromOrder($n);
			if($replyArray[$replyTypeId]){
				$replyList[]=NorwegianTextNumbers($replyArray[$replyTypeId],false,false,false)." har svart ".$literals->GetShortName($replyTypeId);
			}
		}
			return SeparatedList($replyList);
		}else{
			return "ingen har svart ennå";	
		}
	}
	function _renderParticipant($reply){
		$output="ATTENDEE;CUTYPE=INDIVIDUAL";
		if ($reply["registeredBy"] > 0){
			$otherPlayer = new OtherPlayer_class($reply["registeredBy"]);
			$output .= ";SENT-BY=";
			if ($otherPlayer->GetEmail() != ""){
				$output .= "MAILTO:".$otherPlayer->GetEmail();
			}else{
				$output .= HomePageUrl($reply["id"]);
			}
		}
		$output.=";CN=\"".utf8_encode($reply["firstName"]." ".$reply["lastName"])."\"";
		$output.=";ROLE=REQ-PARTICIPANT";
		$output.=";RSVP=FALSE";
		$output.=";PARTSTAT=";
		switch ($reply["type"]) {
		case 1:
			$output .= "ACCEPTED";
			break;
		case 0:
			$output .= "DECLINED";
			break;
		default:
			$output .= "TENTATIVE";
			break;
		}
		$output .= ":";
		$output.="mailto:".$reply["email"];
		$output.="\n";
		return  $output;
	}
	function _renderContact($reply){
		$sep="\, ";
		$newContact=$reply["firstName"]." ".$reply["lastName"];
		$newContact.=$sep.$reply["residence"].$sep.$reply["phone"];
		$newContact.=$sep.$reply["email"];
		$newContact="CONTACT:".utf8_encode($newContact)."\n";
		return $newContact;
	}
	function _renderOwnReply($reply){
		$output="Du har svart «";
		$answers=new AnswerLiterals_class();
		$output.=$answers->GetLongName($reply["type"]);
		$output.="». ";
		if($reply["remedies"]){
			$remedies=new Remedies_class();
			$output.=$remedies->GetStringDescribingNeed($reply["remedies"],"Du har lovet å ta med ",false);
		}	
		return $output;
	}
}
?>