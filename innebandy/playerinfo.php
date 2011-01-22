<?PHP
require_once('commonCode.inc');
require_once('playerInfoCode.inc');

//---------------------
//  Classes
//---------------------


/**
 * Reads current team from post data
 * Verifies that user may see this team.
 */
class TeamOfList_class extends OtherTeam_class {
	var $teamOfListId;
	/**
	* @return TeamOfList_class
	* @desc Information regarding the team
	*/
	function TeamOfList_class (){
		$this->teamOfListId = $this->_determineTeamId();
		$this->OtherTeam_class($this->teamOfListId);
	}
	function _determineTeamId(){
		global $teamChooserFieldName;
		$player = new Player_class();
		$logonTeam = new Team_class();
		$out = GetPostGetData($teamChooserFieldName);
		if (! $out > 0){
			$out = $logonTeam->get_id();
		}
		// Check if player is allowed to see this team.
		if (! $player->getIsMemberInTeam($out)){
			$out = $logonTeam->get_id();	
		}
		return $out;
	}	
}

class TeamChooser_class extends Output_class  {
	var $content;
	/**
	* @return TeamChooser_class
	* @desc Heading of list and form controls for changing team
	*/
	function TeamChooser_class(){
		$out = "<h1>Spillerliste for&nbsp;";
		$out .= $this->_TeamChooserForm();
		$out .= "</h1>\n";
		$this->content = $out;
	}
	/**
	* @return html
	* @desc Returns name of team or (if player has multiple memberships) a form for choosing other teams
	*/
	function GetContent(){
		return $this->content;	
	}
	/**
	* @return html
	* @desc Returns name of team or form for choosing another team
	*/
	function _TeamChooserForm(){
		global $playerIdFieldName, $teamChooserFieldName, $teamChooserFormName;
		$currentTeam = new TeamOfList_class();
		$formObj = new FormData_class();
		$submitFunc = $formObj->GetTeamFunctionName();
		$player = new Player_class();
		$playerId = $player->getId();
		if ($player->multipleTeams){
			$teams = $player->getTeams();
			$teamsInfo = $player->GetTeamsData();
			$out = "
				\n<form name=\"$teamChooserFormName\">
  				<select name=\"$teamChooserFieldName\" 
				onChange=\"{$submitFunc}(this.value);\">
				";
			foreach ($teams as $team){
				$longName = $teamsInfo[$team]["longName"];
				$selectedString = ($team == $currentTeam->get_id()) ? " selected " : "";
				$out .= "
					<option value=\"$team\"$selectedString>$longName</option>
					";
			}
  			$out.="
  				</select>
				</form>
  				";
		}else{
			$out = $currentTeam->getName("long");
		}
		return $out;
	}	
}


class PlayerDetails_class{
	var $content, $isSubject, $subjectId, $teamId;
	function PlayerDetails_class(){
		$this->_setVars();
		if ($this->isSubject){
			$this->content = $this->_heading();
			$this->content .= $this->_mainTable();
			$this->content .= $this->_footer();
		}
	}
	function _setVars(){
		$formObj = new FormData_class();
		$this->subjectId = $formObj->GetCurrentSubject();
		$this->isSubject = ($this->subjectId > 0);
		$this->teamId = $formObj->GetCurrentTeam();
	}
	function _heading(){
		global $detailsAnchorName;
		$out = "\n<hr><a name='$detailsAnchorName'>\n";
		return $out;	
	}
	function _footer(){
		$out = "\n</a>\n";
		return $out;	
	}
	function _mainTable(){
		$out  = "\n<table cellpadding='10' width='100%' border = '0' title = 'Informasjon om spilleren'>";
		$out .= "\n<tr>";
		$out .= "\n<td valign='top'>";
		$out .= $this->_PersonalInfo();
		$out .= "\n</td>";
		$out .= "\n<td valign='top'>";
		$out .= $this->_Statistics();
		$out .= "\n</td>";
		$out .= "\n</tr>";
		$out .= "\n</table>\n";
		return $out;	
	}
	function _PersonalInfo(){
		$self = new Player_class();
		$out = "";
		$ageQueryString = "
			IF((DAYOFYEAR(NOW()) < DAYOFYEAR(age)),YEAR(NOW())-YEAR(age)-1,YEAR(NOW())-YEAR(age)) AS numberOfYears
		";
		$queryString = "
			SELECT players.*, membership.*, players.id AS playerId,
			$ageQueryString
			FROM players LEFT JOIN membership 
			ON players.id = membership.player
			WHERE (((players.id)={$this->subjectId}) AND ((membership.team)={$this->teamId}))
		";
		$queryObj = new DBConnector_class($queryString);
		$playerRow = $queryObj->GetNextRow();
		
		$firstName = $playerRow['firstName'];
		$fullName = $firstName." ".$playerRow['lastName'];
		$pictureUrl = $playerRow['picture'];
		$age = $playerRow['numberOfYears'];
		$number = $playerRow['number'];
		$phone = $playerRow['phone'];
		$email = $playerRow['email'];
		$web = $playerRow['url'];
		$address = $playerRow['residence'];
		$motto = $playerRow['notes'];
		$trainer = $playerRow['teamTrainer'];
		$isSelf = ($self->getId() == $playerRow['playerId']);
		$genetivePronomen = $isSelf ? "Din" : GenitiveNor($firstName);
		
		// Picture
		if ($playerRow['picture'] != ""){
			$out .= "\n<img src = '{$pictureUrl}' title ='Portrett av $fullName' alt='$fullName' align='left'>";
		}
		//Name
		$out .= "<h1>$fullName</h1>";
		//Age
		if ($age > 5 && $age < 150){
			$out .= "<p title='";
			$out .= $isSelf ? "Du" : $firstName;
			$out .= " er $age &aring;r gammel";
			$out .= "'>$age &aring;r</p>";
		}
		//Number
		$out .= $number != "" ? "<p title='Nummeret p&aring; drakten til $fullName'><em>Draktnummer:</em> $number</p>" : "";
		//Phone
		$out .= $phone != "" ? "<p title='Telefonnummer'><em>Telefon:</em><a href='tel:$phone'>$phone</a></p>" : "";
		//Address
		$out .= $address != "" ? "<p title='$genetivePronomen hjemmeadresse'><em>Bosted:</em> $address</p>" : "";
		//email
		if ($email != ""){
			$out .= "<p><em>E-postadresse: </em>";
			$out .= "<a href='mailto:$email' title= '$genetivePronomen e-postadresse'>$email</a>";
			$out .= "</p>";	
		}
		//Web
		if ($web != ""){
			$out .= "<p><em>Hjemmeside: </em>";
			$out .= "<a href='http://$web' title= '$genetivePronomen hjemmeside' target='hjemmeside'>http://$web</a>";
			$out .= "</p>";	
		}
		//Motto
		$out .= $motto != "" ? "<p title='Kampspr&aring;k'><em>Motto:</em><br>&laquo;{$motto}&raquo;</p>" : "";
		//Trainer
		if ($trainer){
			$out .= "<p><em>";
			$out .= $isSelf ? "Du" : $firstName;
			$out .= " er oppmann.</em></p>";	
		}
		//Edit self
		if ($isSelf){
			global $detailsFrameName,$editPlayerFileName, $playerIdFieldName;
			$locationStr = $editPlayerFileName."?".$playerIdFieldName."=".$self->getId();
			$out .= "<p><a href='$locationStr' target='$detailsFrameName' title='Rediger opplysningene om deg selv'>";
			$out .= "Endre opplysningene (i hovedvinduet)";
			$out .= "</a></p>";
		}
		return $out;
	}
	function _Statistics(){
		$out = "";
		$formData = new FormData_class();
		$teamId = $formData->GetCurrentTeam();
		$dateOfForm = $formData->GetCurrentDate();
		$subjectId = $this->subjectId;
		$queryStringLastLogon = "
			SELECT *,
			(lastLogin IS NULL) AS noLogin
			from players WHERE id = $subjectId
		";
		$queryLastLogon = new DBConnector_class($queryStringLastLogon);
		$playerInfo = $queryLastLogon->GetNextRow();
		$lastLogon = strtotime($playerInfo['lastLogin']);
		$noLogon = $playerInfo['noLogin'];
		$firstName = $playerInfo['firstName'];
		$self = new Player_class();
		$selfId = $self->getId();
		$isSelf = $selfId == $subjectId;
		
		$queryStringFirstActivity = "
			SELECT MIN(events.dateStart) AS first
			FROM attention LEFT JOIN events ON events.id=attention.event
			WHERE attention.player = $subjectId AND events.team = $teamId
		";
		$queryFirstActivity = new DBConnector_class($queryStringFirstActivity);
		$firstActivityDate = strtotime($queryFirstActivity->GetSingleValue('first'));
		$dateOfStatistics = max($firstActivityDate,$dateOfForm);
		if ($dateOfStatistics != $dateOfForm){
			$out .= JavaScriptWrapper($formData->GetAlterDateFunctionName()."($dateOfStatistics)");	
		}
		
		$dateOfStatisticsQueryForm = date("Y-m-d",$dateOfStatistics);
		$queryEditsString = "
			SELECT DISTINCT eventChangeLog.event
			FROM eventChangeLog INNER JOIN events ON eventChangeLog.event = events.id
			WHERE ((eventChangeLog.player=$subjectId) 
				AND ((eventChangeLog.date)>'$dateOfStatisticsQueryForm') 
				AND ((events.team)=$teamId))
			ORDER BY eventChangeLog.event
		";
		$queryEdit = new DBConnector_class($queryEditsString);
		$numberOfEdits = $queryEdit->GetNumberOfRows();
		
		
		$queryMessagesString = "
			SELECT COUNT(id) AS numberOfMessages
			FROM messages
			WHERE author = '$subjectId'
				AND teams LIKE '%$teamId%'
				AND date > '$dateOfStatisticsQueryForm'
		";
		$queryMessages = new DBConnector_class($queryMessagesString);
		$numberOfMessages = $queryMessages->GetSingleValue("numberOfMessages");
		
		//Date choser
		$dateCntrlName = "dateControl";
		$dateControl = new DateControl_class();
		$dateControl->SetCurrentValue($dateOfStatistics);
		$dateControl->SetEarliestDate($firstActivityDate);
		$dateControl->SetName($dateCntrlName);
		$out .= "<h1 title='Husk at disse dataene bare sier noe om registreringen som er gjort p&aring; sidene, men ikke n&oslash;dvendigvis noe om hvem som faktisk har m&oslash;tt opp...'>Historikk fra ";
		$out .= $dateControl->RenderControl();
		$changeDateFunction = $formData->GetDateFunctionName();
		$out .= "<input type='button' 
					name='ChangeDate' 
					value='&lt;'
					onClick='{$changeDateFunction}(\"$dateCntrlName\");'
					title='Oppdatere statistikken med datoen som er valgt'>";
		$out .= "</h1>";
		
		//First activity
		if ($firstActivityDate > 0){
			$out .= "<p><em>";
			$out .= $isSelf ? "Du" : $firstName;
			$out.= " har v&aelig;rt med siden</em> ";
			$out .= RecentDate($firstActivityDate);
			if ($noLogon==0){
				$out .= "<em> og var sist inne p&aring; sidene </em>";
				$out .= RecentDate($lastLogon);	
			}
			$out .= ".</p>";	
		}
		
		
		if($numberOfEdits+$numberOfMessages > 0){
			$out .= "\n<p>";
			if($numberOfEdits > 0){
				$out .= $isSelf ? "Du" : $firstName;
				$out .= " har opprettet eller endret ".NorwegianTextNumbers($numberOfEdits)." hendelse";
				$out .= $numberOfEdits > 1 ? "r siden " : " siden ";
				$out .= RecentDate($dateOfStatistics);
				if ($numberOfMessages == 0){
					$out .= ". ";
				}else{
					$out .= ", ";	
				}
			}
			if($numberOfMessages > 0) {
				$out .= "\n";
				if ($numberOfEdits == 0){
					$out .= $isSelf ? "Du" : $firstName;
					$out .= " har ";
				}else{
					$out .= " og har ogs&aring; ";
				}
				$out .= NorwegianTextNumbers($numberOfMessages)." melding";
				$out .= $numberOfMessages > 1 ? "r" : "";
				$out .= " i diskusjonsforumet som er skrevet etter ";
				$out .= RecentDate($dateOfStatistics);
				$out .= ". \n";
			}
			$out .= "\n</p>\n";	
		}
		
		//Last events
		$lastEventsQueryString = "
			SELECT events.dateStart, events.description,
				attention.notes AS attentionNotes,
				attentionType.type AS attentionTypeLongName,
				IF(attention.customValue IS NULL, attentionType.value, attention.customValue) AS attentionValue
			FROM events LEFT JOIN attention ON (attention.event = events.id AND attention.player = $subjectId)
				LEFT JOIN attentionType ON attention.type = attentionType.id
			WHERE events.dateStart < NOW()
				AND events.team = $teamId
				AND events.cancelled != 1
			ORDER BY events.dateStart DESC
			LIMIT 5
		";
		
		$lastEventsQuery = new DBConnector_class($lastEventsQueryString);
		$numberOfLastEvents = $lastEventsQuery->GetNumberOfRows();
		if ($numberOfLastEvents > 0){
			$lastEventsString = "";
			$lastEventsString .= "\n<h2>De siste ";
			$lastEventsString .= NorwegianTextNumbers($numberOfLastEvents);
			$lastEventsString .= " hendelsene:</h2>\n";
			$lastEventsString .= "<table>\n";
			$hasAnswered = false;
			while ($lastEvent = $lastEventsQuery->GetNextRow()){
				$lastEventsString .= "<tr>";
				$lastEventsString .= "<td>".norDate($lastEvent['dateStart'])."</td>";
				$lastEventsString .= "<td><em>$lastEvent[description]</em></td>";
				if ($lastEvent['attentionTypeLongName']==""){
					$myAnswer = "<em>Intet svar</em>";
					$attentionValue = "Ingenting er registrert.";
				}else{
					$myAnswer = $lastEvent['attentionTypeLongName'];
					$hasAnswered = true;
					$attentionValue = ($lastEvent['attentionValue']*100)."% sikkert";
				}
				$lastEventsString .= "<td title='$attentionValue'>$myAnswer</td>";
				$lastEventsString .= $lastEvent['attentionNotes']=="" ? "<td></td>" : "<td><em>($lastEvent[attentionNotes])</em></td>";
				$lastEventsString .= "</tr>\n";
			}
			$lastEventsString .= "\n</table>\n";
		
			if ($hasAnswered){
				$out .= $lastEventsString;
			}else{
				$out .= "<p>Det er ikke svart p&aring; de";
				$out .= $numberOfLastEvents == 1 ? "n siste hendelsen" : " siste ".NorwegianTextNumbers($numberOfLastEvents)." hendelsene.";
				$out .= "</p>"; 
			}
		}
		
		$summaryObj = new Replies_class($subjectId,$teamId,$dateOfStatistics);
		
		//Weekdays
		$weekdayTable = $summaryObj->RenderWeekdayTable();
		if ($weekdayTable != ""){
			$out .= "\n<h2>Fordeling per ukedag</h2>";
			$out .= $weekdayTable;
		}
		
		//Summary
		$out .= "\n<h2>Oppsummering</h2>\n";
		$out .= $summaryObj->RenderTableOfReplies();
		
		if ($firstActivityDate == 0){
			$out = "Ingen statistikk er tilgjengelig";	
		}
		return $out;
	}
	function GetContent(){
		return $this->content;
	}
}
class Replies_class{
	var $playerId, $teamId, $dateValue;
	var $repliesAll, $numberOfEvents, $repliesPerWeekday, $eventsPerWeekday; 
	var $replyTypes, $eventTypes, $weekdays;
	/**
	* @return Replies_class
	* @param int $playerId
	* @param int $teamId
	* @param timestamp $dateValue
	* @desc Gathers and displays information on all replies
	*/
	function Replies_class($playerId, $teamId, $dateValue){
		$this->playerId = $playerId;
		$this->teamId = $teamId;
		$this->dateValue = $dateValue;
		$this->_getLabels();
		$this->_getData();
	}
	function _GrayscaleHex($ratio){
		$decValue=round((1-$ratio)*255);
		$hex = dechex($decValue);
		$out = "#$hex$hex$hex";
		return $out;
	}
	function RenderWeekdayTable(){
		$out = "\n<table>";
		// Header row
		$out .= "<tr>";
		foreach ($this->weekdays as $weekdayId => $weekdayName) {
			if ($this->eventsPerWeekday[$weekdayId]>0){
				$out .= "<td align='center'>$weekdayName</td>";
			}
		}
		$out .= "</tr>\n";
		
		//Data row
		$out .= "<tr>";
		foreach ($this->weekdays as $weekdayId => $weekdayName) {
			if ($this->eventsPerWeekday[$weekdayId]>0){
				$averageAnswer = 0;
				$numberOfReplies = count($this->repliesPerWeekday[$weekdayId]);
				$numberOfEvents = $this->eventsPerWeekday[$weekdayId];
				foreach ($this->repliesPerWeekday[$weekdayId] as $reply){
					$averageAnswer += $reply;
				}
				$averageAnswer /= $numberOfReplies;
				$replyPercentage = floor(($numberOfReplies / $numberOfEvents)*100);
				$cellBackground = $this->_GrayscaleHex($averageAnswer);
				$textColor = $averageAnswer > 0.6 ? $this->_GrayscaleHex(0) : $this->_GrayscaleHex(1);
				
				$out .= "<td align='center' title='Svarandel: $replyPercentage%' bgcolor='$cellBackground'>";
				$out .= "<font color='$textColor'>$numberOfEvents&nbsp;hendelse";
				if ($numberOfEvents > 1){
					$out .= "r";
				}
				if ($numberOfReplies > 0){
					$out .= ".<br>";
					$averagePercentage = (round($averageAnswer*100));
					if ($averagePercentage > 0){
						$out .= $numberOfReplies > 1 ? "Snitt:&nbsp;" : "";
						$out .= "{$averagePercentage}%&nbsp;sikker.";
					}else{
						$out .= "Kom";
						$out .= $numberOfReplies > 1 ? " aldri" : " ikke";
					}
				}else{
					$out .= ".";	
				}
				$out .= "</font></td>";
			}
		}
		$out .= "</tr>\n";
		
		$out .= "\n</table>";
		if ($this->numberOfEvents == 0){
			$out = "";	
		}
		return $out;
	}
	function RenderTableOfReplies(){
		$out = "";
		$out .= "\n<table>\n";
		
		// First row
		$out .= "<tr>";
		$out .= "<td></td><td align='center'>Antall</td>";
		$replyTypesCount = array();
		foreach ($this->replyTypes as $replyId => $replyType){
			$replyTypesCount[$replyId] = $this->GetNumberOfReplies($replyId);
			if ($replyTypesCount[$replyId] > 0){
				$out .= "<td align='center'>$replyType</td>";
			}
		}
		$out .= "<td align='center'><em>Totalt</em></td>";
		$out .= "</tr>";
		
		//Eventtypes
		$numberOfActiveEventTypes = 0;
		foreach ($this->eventTypes as $eventId => $eventName){
			$numberOfEvents = $this->GetNumberOfEvents($eventId);
			$repliesOnThisEvent = 0;
			if ($numberOfEvents > 0){
				$numberOfActiveEventTypes ++;
				$out .= "\n<tr>";
				$out .= "<td>$eventName</td>";
				$out .= "<td align='center' title='Totalt ".NorwegianTextNumbers($numberOfEvents)." ganger har det v&aelig;rt &laquo;$eventName&raquo;.'>$numberOfEvents</td>";
				foreach ($this->replyTypes as $replyId => $replyType){
					if ($replyTypesCount[$replyId] > 0){
						$numberOfReplies = $this->repliesAll[$eventId][$replyId];
						$repliesOnThisEvent += $numberOfReplies;
						$out .= "<td align='center'";
						if ($numberOfReplies > 0){
							$out .= "title='Det er svart &laquo;$replyType&raquo; til ".NorwegianTextNumbers($numberOfReplies)." av de ".NorwegianTextNumbers($numberOfEvents)." hendelsene.'>";
							$out .= $numberOfReplies;
							$percentage = floor(($numberOfReplies/$numberOfEvents)*100);
							$out .= " ($percentage%)";	
						}else{
							$out .= "title='Det er ikke svart &laquo;$replyType&raquo; til noen av disse ".NorwegianTextNumbers($numberOfEvents)." hendelsene.' >";	
						}
						$out .= "</td>";
					}
				}
				$out .= "<td align='center' title='Totalt ".NorwegianTextNumbers($repliesOnThisEvent)." svar p&aring; hendelser av typen &laquo;$eventName&raquo;.'><em>";
				if ($repliesOnThisEvent > 0){
					$out .= $repliesOnThisEvent;
					$percentage = floor(($repliesOnThisEvent/$numberOfEvents)*100);	
					$out .= " ($percentage%)";	
				}
				$out .= "</em></td>";
				$out .= "</tr>";	
			}
		}
		//Grand total
		if ($numberOfActiveEventTypes > 1){
			$out .= "\n<tr><td><em>Totalt</em></td>";
			$out .= "<td align='center' title='".NorwegianTextNumbers($this->numberOfEvents)." hendelser alt i alt.'><em>$this->numberOfEvents</em></td>";
			$numberOfReplies = 0;
			foreach ($this->replyTypes as $replyId => $replyType){
				if ($replyTypesCount[$replyId] > 0){
					$numberOfReplies += $replyTypesCount[$replyId];
					$out .= "<td align='center' title='Totalt ".NorwegianTextNumbers($replyTypesCount[$replyId])." antall &laquo;$replyType&raquo;'><em>";
					$out .= $replyTypesCount[$replyId];
					$percentage = floor(($replyTypesCount[$replyId]/$this->numberOfEvents)*100);
					$out .= " ($percentage%)";	
					$out .= "</em></td>";	
				}
			}
			$out .= "<td align='center' title='Totalt ".NorwegianTextNumbers($numberOfReplies)." svar til alle ".NorwegianTextNumbers($this->numberOfEvents)." hendelsene.'>";
			$out .= $numberOfReplies;
			$percentage = floor(($numberOfReplies/$this->numberOfEvents)*100);
			$out .= " ($percentage%)";	
			$out .= "</td></tr>";
		}
		
		$out .= "\n</table>\n";
		if ($this->numberOfEvents = 0){
			$out = "Ingen hendelser er registrert i perioden";	
		}
		return $out;	
	}
	function _getLabels(){
		$eventTypesQueryString = "
			SELECT * FROM eventTypes ORDER BY id ASC
		";
		$eventTypesQuery = new DBConnector_class($eventTypesQueryString);
		$this->eventTypes = array();
		while ($eventType = $eventTypesQuery->GetNextRow()){
			$this->eventTypes[$eventType['id']] = $eventType['type'];	
		}
		$replyTypesQueryString = "
			SELECT * FROM attentionType ORDER By listOrder ASC
		";
		$replyTypesQuery = new DBConnector_class($replyTypesQueryString);
		$this->replyTypes = array();
		while($replyType = $replyTypesQuery->GetNextRow()){
			$this->replyTypes[$replyType['id']] = $replyType['shortName'];	
		}
		$this->weekdays = array("2"=>"mandag","3"=>"tirsdag","4"=>"onsdag","5"=>"torsdag","6"=>"fredag","7"=>"l&oslash;rdag","1"=>"s&oslash;ndag");
	}
	function _getData(){
		$dateString = date("Y-m-d",$this->dateValue);
		$eventsQueryString = "
			SELECT events.id, attention.type AS attentionType, events.type AS eventType,
				DAYOFWEEK(events.dateStart) AS weekday,
				IF(attention.customValue IS NULL, attentionType.value, attention.customValue) AS attentionValue,
				IF(events.minimumCount IS NULL, eventTypes.requiredCount,events.minimumCount) AS minCount
			FROM events LEFT JOIN attention ON (attention.event = events.id AND attention.player = {$this->playerId})
				LEFT JOIN attentionType ON attention.type = attentionType.id
				LEFT JOIN eventTypes ON events.type = eventTypes.id
			WHERE events.dateStart < NOW()
				AND events.dateStart > '{$dateString}'
				AND events.team = {$this->teamId}
				AND events.cancelled != 1
			ORDER BY events.dateStart ASC
		";
		$eventsQuery = new DBConnector_class($eventsQueryString);
		$this->numberOfEvents = $eventsQuery->GetNumberOfRows();
		$this->repliesAll = array();
		$this->repliesPerWeekday = array();
		$this->eventsPerWeekday = array();
		while ($event = $eventsQuery->GetNextRow()){
			$this->repliesAll[$event['eventType']][$event['attentionType']*1] ++;
			if ($event['attentionType'] > 0){
				$this->repliesPerWeekday[$event['weekday']][] = $event['attentionValue'];
			}
			$this->eventsPerWeekday[$event['weekday']] ++;
		}
	}
	function GetNumberOfReplies($replyType){
		$out = 0;
		foreach($this->repliesAll as $eventType){
			$out += $eventType[$replyType];
		}
		return $out;
	}
	function GetNumberOfEvents($eventType){
		$out = 0;
		foreach($this->repliesAll[$eventType] as $replyType){
			$out += $replyType;	
		}
		return $out;	
	}
}
class Main_class{
	var $content, $title, $teamId;
	function Main_class(){
		$chooser = new TeamChooser_class();
		$details = new PlayerDetails_class();
		$list = new ListOfPlayers_class();
		$formObj = new FormData_class();
		$team = new TeamOfList_class();
		$this->title = "Spillerliste for ".$team->getName("long");
		$this->teamId=$team->get_id();
		$this->content  = $formObj->RenderHiddenForm();
		$this->content .= $chooser->GetContent();
		$this->content .= $list->GetContent();
		$this->content .= $this->_getCSVLink();
		$this->content .= $details->GetContent();
	}
	function GetContent(){
		$out = $this->_HTMLWrapper($this->content, $this->title);
		return $out;	
	}
	function _getCSVLink(){
		$link=new CSVFileLink_class();
		return "<hr/>".$link->RenderLink();
	}
	function _HTMLWrapper($content, $title){
		$out = "<html>\n";
		$out .= "<head>\n";
		$out .= "<title>$title</title>\n";
		$out .= RenderCSSLink($this->teamId);
		$out .= "</head>\n";
		$out .= "\n<body>\n";
		$out .= $content;
		$out .= "\n</body>\n";
		$out .= "\n</html>";
		return $out;	
	}
}
//-------------------------
// Main
//-------------------------

$body = new Main_class();
echo $body->GetContent(); 

?>