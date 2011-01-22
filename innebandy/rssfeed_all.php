<?PHP

include_once("commonCode.inc");

/*
---------------
	Main
---------------
*/
Main();


/*
---------------
	Classes
---------------
*/

class AllData_class {
	var $lastDate, $messages, $events, $updateFlag, $player;
	function AllData_class(){
		$this->lastDate = & $GLOBALS['_transient']['static']['alldata']->lastDate;
		$this->messages = & $GLOBALS['_transient']['static']['alldata']->messages;
		$this->events = & $GLOBALS['_transient']['static']['alldata']->events;
		$this->updateFlag = & $GLOBALS['_transient']['static']['alldata']->updateFlag;
		$this->player= new Player_class();
		if ($this->updateFlag < 1){
			$this->messages = $this->_queryMessages();
			$this->events = $this->_queryEvents();
			$this->updateFlag = 1;
		}
	}
	function _queryMessages(){
		$team = new Team_class();
		$teamId = array($this->player->getTeams());
		$teamQuery = MessageQueryTeamFilterString($teamId);
		$query_string = "
			SELECT messages.*, players.email 
			FROM messages LEFT JOIN players ON messages.author = players.id
			WHERE (messages.date >DATE_SUB(NOW(), INTERVAL 3 MONTH)) $teamQuery
			ORDER BY date DESC
		";
		$query = new DBConnector_class($query_string);
		return $query->GetAllRows();
	}
	function _queryEvents(){
		$player=$this->player;
		$teamsString = implode(",",$player->getTeams());
		$teamQuery = "events.team IN ($teamsString)";
		$query_string = "
			SELECT 
				events.*,eventTypes.type AS eventType , eventTypes.description AS eventDescription , eventChangeLog.player,
  				teams.longName AS teamName,
				GREATEST(MAX(attention.date),MAX(eventChangeLog.date)) AS changeDate
			FROM events
			LEFT JOIN attention ON events.id = attention.event
			LEFT JOIN eventTypes ON events.type = eventTypes.id
			LEFT JOIN eventChangeLog ON events.id=eventChangeLog.event
			LEFT JOIN teams ON events.team=teams.id
			WHERE ($teamQuery)
			GROUP BY events.id
			HAVING events.dateStart > NOW()
			ORDER BY events.dateStart DESC
		";
		$query = new DBConnector_class($query_string);
		$events = $query->GetAllRows();
		$confirmations = $this->_Confirmed($teamsString,'2');

		for ($eventIndex = 0; $eventIndex < count($events); $eventIndex++){
			$events[$eventIndex]['confirmedCount'] = $confirmations[$events[$eventIndex]['id']];	
		}
		
		return $events;
	}
	
	/**
	* @return xml
	* @desc Returns tagged content from db
	*/
	function GetAllData(){
		global $messageIdFieldName,$eventFieldname,$playerIdFieldName;
		$eventIdHidden = 'eventId_hidden';
		
		$playerId = $this->player->getId();
		$out = "";
		$items = array();
		$dates = array();
		$team = new Team_class();
		
		$sourceUrl = "http://".HOMEURL;
		$source = htmlentities($team->getName("long"));
		
		
		
		//Return events
		foreach ($this->events as $eventArray){
			
			$title  = $eventArray['description'];
			$title .= " ".norDate($eventArray['dateStart']);
			$title = str_replace("&nbsp;"," ",$title);
			$link = "http://".HOMEURL."eventdetails.php";
			$link .= "?$eventFieldname=".$eventArray['id'];
			$dateOfEvent = strtotime($eventArray['changeDate']);
			$guid = "Event".$eventArray['id'];
			if($playerId){
				$comments  = "http://".HOMEURL."eventform.php";
				$comments .= "?$eventIdHidden=".$eventArray['id'];
				$comments .= "&$playerIdFieldName=$playerId";
			}else{
				$comments = $link;	
			}
			$author = new OtherPlayer_class($eventArray['player']);
			$authorEmail = $author->GetEmail();

			$description  = $eventArray['eventType']." ";
			$description .= norDate($eventArray['dateStart']);
			$description .= " kl ".date("h:m",strtotime($eventArray['dateStart']));
			$description .= ", ".$eventArray['location'].". ";
			$description .= "Det er ";
			$description .= NorwegianTextNumbers(($eventArray['confirmedCount']*1));
			if ($eventArray['confirmedCount']==1){
				$plural1 = "t";
				$plural2 = "";
			}else{
				$plural1 = "de";
				$plural2 = "er";		
			}	
			$description .= " bekrefte$plural1 påmelding$plural2. ";
			$description .= $eventArray['notes'];
			
			$encLink = "http://".HOMEURL."vcal-generator.php?$eventFieldname=".$eventArray['id'];
			$encSize = "600";
			$encType = "text/x-vCalendar";
			$encProperty = "url=\"$encLink\" length=\"$encSize\" type=\"$encType\"";
			
			$subject = $eventArray['eventDescription'];
			$sourceTag = Tag("source",utf8_encode($eventArray['teamName']), "url=\"$sourceUrl\"", false, false);
			
			$itemString  = $sourceTag;
			$itemString .= Tag("title", $title, "", false);
			$itemString .= Tag("link",$link, "", false);
			$itemString .= Tag("description", $description, "", false);
			$itemString .= Tag("author", $authorEmail, "", false);
			$itemString .= Tag("comments", $comments, "", false);
			$itemString .= Tag("guid", $guid, "", false);
			$itemString .= Tag("pubDate", date("r",$dateOfEvent), "", false);
			$itemString .= Tag("enclosure", "", $encProperty, false, false);
			$itemString .= Tag("subject", $subject, "", false);
			
			$items[] = Tag("item", $itemString, "", true,false);
			$dates[] = 2 * time() - strtotime($eventArray['dateStart']); //Reverse date

			
			$this->_updateLastDate($dateOfMsg);
 		}
 		//Return messages
		foreach ($this->messages as $message){
			$title = $message['subject'];
			$link = "http://".HOMEURL."messagedetails.php";
			$link .= "?$messageIdFieldName=".$message['id'];
			$link .= "#currentMessage";
			$description = $message['messageText'];
			$author = $message['email'];
			$comments = "http://".HOMEURL."messagedetails.php";
			$comments .= "?$messageIdFieldName=".$message['id'];
			$comments .= "#messageForm";
			$guid = "Message".$message['id'];
			$dateOfMsg = strtotime($message['date']);
			$pubDate = date("r", $dateOfMsg);
			$subject = "Dette er en melding";

			$itemString  = $sourceTag;
			$itemString .= Tag("title",$title, "", false);
			$itemString .= Tag("link", $link, "", false);
			$itemString .= Tag("description", $description, "", false);
			$itemString .= Tag("author", $author, "", false);
			$itemString .= Tag("comments", $comments, "", false);
			$itemString .= Tag("guid", $guid, "", false);
			$itemString .= Tag("pubDate", $pubDate, "", false);
			$itemString .= Tag("subject", $subject, "", false);
			
			$items[] = Tag("item", $itemString, "", true, false);
			$dates[] = $dateOfMsg;

			
			$this->_updateLastDate($dateOfMsg);
		}
		
		array_multisort($dates, SORT_ASC, $items);
		$out = implode("",$items);
		return $out;
	}
	function _updateLastDate($dateStamp){
		if ($dateStamp > $this->lastDate){
			$this->lastDate = $dateStamp;	
		}	
	}
	/**
	* @return int[]
	* @param string $teamString
	* @param int $attentionType
	* @desc Returns array of confirmedCounts indexed by eventId
	*/
	function _Confirmed($teamString, $attentionType=2){
		$allEvents = array();	
		if ($teamString){
			if($attentionType){
				$attentionString = "HAVING (((attention.type)=$attentionType) 
					AND";
			}else{
				/* Get all answers */
				$attentionString = "HAVING (((attention.type) > 0) 
					AND";
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
				GROUP BY events.id, attention.type, events.team, events.dateStart
				$attentionString 
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
	/**
	* @return timestamp
	* @desc Returns the last date of any data
	*/
	function GetLastDate(){
		return $this->lastDate;	
	}
}


/*
---------------
	Functions
---------------
*/

/**
 * @return void
 * @desc The main flow of the page
*/

function Main(){
	$data = new AllData_class();
	
	GenerateHeader();
	$out = ChannelPropertiesTags();
	$out .= $data->GetAllData();
	$out = MainXMLWrapper($out);
	echo $out;
}


function DefineEntities(){
	$out = "
		<!ENTITY aring	\"&#229;\">
		<!ENTITY Aring	\"&#197;\">
		<!ENTITY Aelig	\"&#198;\">
		<!ENTITY aelig	\"&#230;\">
		<!ENTITY oslash	\"&#248;\">
		<!ENTITY Oslash	\"&#216;\">
		<!ENTITY nbsp	\"&#160;\">
		<!ENTITY eacute \"&#225;\">
		<!ENTITY sect   \"&#167;\">
	";
	return $out;
}
/**
 * @return xml
 * @param string $tagname
 * @param string $content
 * @param string $properties
 * @desc Wraps content in a xml tag with given name. Properties must be a valid string.
*/
function Tag($tagname, $content="", $properties="", $newLines = true, $escape = true){
	$contentString = $escape ? htmlentities($content) : $content;
	$out = $newLines ? "\n" : "";
	$out .= "<$tagname";
	if ($properties != ""){
		$out .= " $properties";	
	}	
	if ($content != ""){
		$out .= ">";
		$out .= $newLines ? "\n" : "";
		$out .= $contentString;
		$out .= $newLines ? "\n" : "";
		$out .= "</$tagname>";
	}else{
		$out .= "/>";	
	}
	return $out;
}

/**
 * @return void
 * @desc Sends content header information to server
*/

function GenerateHeader(){
	header("Content-Type: text/xml");
}

/**
 * @return xml
 * @param xml $contentString
 * @desc Wraps string in xml header and rss and channel tags
*/
function MainXMLWrapper($contentString){							
	$entities .= DefineEntities();
	$out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$out .= "<!DOCTYPE rss 
							PUBLIC \"-//Netscape Communications//DTD RSS 0.91//EN\" 
							\"http://my.netscape.com/publish/formats/rss-0.91.dtd\"
							[
							$entities
							]>";
	$content = Tag("channel", $contentString, "",true, false);
	$out .= Tag("rss",$content,"version=\"2.0\"", true, false);
	return $out;
}
/**
 * @return xml
 * @desc Returns all the tags in the channel except item
*/
function ChannelPropertiesTags(){
	$team = new Team_class();
	$data = new AllData_class();
	
	$trainer = new OtherPlayer_class($team->getTrainer(),$team->get_id());
	
	$title = $team->getName("short");
	$link = "http://".HOMEURL;
	$description = $team->getName("long");
	$pubDate = date("r",$data->GetLastDate());
	$lastBuildDate = date("r");
	$generator = "masekopproboten";
	$managingEditor = $trainer->GetEmail();
	
	$out .= Tag("title",$title, "", false);
	$out .= Tag("link",$link, "", false);
	$out .= Tag("description", $description);
	$out .= Tag("language", "no", "", false);
	$out .= Tag("pubDate", $pubDate, "", false);
	$out .= Tag("lastBuidDate", $lastBuildDate, "", false);
	$out .= Tag("docs", "http://blogs.law.harvard.edu/tech/rss");
	$out .= Tag("generator", $generator, "", false);
	$out .= Tag("managingEditor", $managingEditor, "", false);
	$out .= Tag("webMaster", "skaar@bigfoot.com", "", false);
	$out .= ImageTag();
    return $out;
}
function ImageTag(){
	$url = "http://".HOMEURL."images/Ball.gif";
	$link = "http://".HOMEURL;
	$width = "20";
	$height = "20";
	$title = "Hvem kommer?";
	$description = "En generalisering av en ball, her representert ved en innebandyball.";
	
	$out  = Tag("url", $url, "", false);
	$out .= Tag("link", $link, "", false);
	$out .= Tag("title", $title, "", false);
	$out .= Tag("width", $width, "", false);
	$out .= Tag("height", $height, "", false);
	$out .= Tag("description", $description, "", false);

	$out = Tag("image",$out, "", true, false);
	
	return $out;
}
?>