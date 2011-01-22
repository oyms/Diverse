<?PHP
/* Displays details about chosen event */
require_once('commonCode.inc');

{
/*
----------------------------
	main
----------------------------
*/
	UpdateLastLogin();
	$mainOut  = HeaderHtml();
	$mainOut .= RegisterConfirmation();
	$mainOut .= EventDetails();
	$mainOut .= FooterHtml();
	
	echo $mainOut;
}


/*
----------------------------
	classes
----------------------------
*/

class CurrentEvent_class{
	/* Information about the chosen event */
	var $eventId;
	var $startDateTime, $endDateTime, $createDate, $lastEditDate;
	var $subjectString, $locationString, $notesString, $url, $isCancelled, $eventRemedies;
	var $eventTeam, $eventNotInCurrentTeam, $emailIsSent, $minimumCount, $maximumCount, $hasMaxCount;
	var $allReplies, $replyTypes, $myReplyRankingIndex, $onWaitingList;
	var $remedyRepliesString;
	var $myReplyId, $myReplyNotes, $myReplyType, $myReplyFirstDate, $myReplyLastDate, $myReplyValue, $myReplyRemedies;
	var $eventTypeId, $eventTypeName, $eventTypeDescription, $eventTypeInvitationString;
	var $birthdayList, $eventTeamShortName;
	function CurrentEvent_class(){
		/* Bind local variables to class globals */
		$this->eventId = & $GLOBALS['_transient']['static']['currentevent_class']->eventId;
		$this->startDateTime = & $GLOBALS['_transient']['static']['currentevent_class']->startDateTime;
		$this->endDateTime = & $GLOBALS['_transient']['static']['currentevent_class']->endDateTime;
		$this->createDate = & $GLOBALS['_transient']['static']['currentevent_class']->createDate;
		$this->lastEditDate = & $GLOBALS['_transient']['static']['currentevent_class']->lastEditDate;
		$this->subjectString = & $GLOBALS['_transient']['static']['currentevent_class']->subjectString;
		$this->locationString = & $GLOBALS['_transient']['static']['currentevent_class']->locationString;
		$this->notesString = & $GLOBALS['_transient']['static']['currentevent_class']->notesString;
		$this->url = & $GLOBALS['_transient']['static']['currentevent_class']->url;
		$this->isCancelled = & $GLOBALS['_transient']['static']['currentevent_class']->isCancelled;
		$this->eventRemedies = & $GLOBALS['_transient']['static']['currentevent_class']->eventRemedies;
		$this->eventTeam = & $GLOBALS['_transient']['static']['currentevent_class']->eventTeam;
		$this->remedyRepliesString = & $GLOBALS['_transient']['static']['currentevent_class']->remedyRepliesString;
		$this->eventNotInCurrentTeam = & $GLOBALS['_transient']['static']['currentevent_class']->eventNotInCurrentTeam;
		$this->emailIsSent = & $GLOBALS['_transient']['static']['currentevent_class']->emailIsSent;
		$this->minimumCount = & $GLOBALS['_transient']['static']['currentevent_class']->minimumCount;
		$this->maximumCount = & $GLOBALS['_transient']['static']['currentevent_class']->maximumCount;
		$this->hasMaxCount = & $GLOBALS['_transient']['static']['currentevent_class']->hasMaxCount;
		$this->allReplies = & $GLOBALS['_transient']['static']['currentevent_class']->allReplies;
		$this->replyTypes = & $GLOBALS['_transient']['static']['currentevent_class']->replyTypes;
		$this->myReplyRankingIndex = & $GLOBALS['_transient']['static']['currentevent_class']->myReplyRankingIndex;
		$this->onWaitingList = & $GLOBALS['_transient']['static']['currentevent_class']->onWaitingList;
		$this->myReplyId = & $GLOBALS['_transient']['static']['currentevent_class']->myReplyId;
		$this->myReplyNotes = & $GLOBALS['_transient']['static']['currentevent_class']->myReplyNotes;
		$this->myReplyType = & $GLOBALS['_transient']['static']['currentevent_class']->myReplyType;
		$this->myReplyFirstDate = & $GLOBALS['_transient']['static']['currentevent_class']->myReplyFirstDate;
		$this->myReplyLastDate = & $GLOBALS['_transient']['static']['currentevent_class']->myReplyLastDate;
		$this->myReplyValue = & $GLOBALS['_transient']['static']['currentevent_class']->myReplyValue;
		$this->myReplyRemedies = & $GLOBALS['_transient']['static']['currentevent_class']->myReplyRemedies;
		$this->eventTypeId = & $GLOBALS['_transient']['static']['currentevent_class']->eventTypeId;
		$this->eventTypeName = & $GLOBALS['_transient']['static']['currentevent_class']->eventTypeName;
		$this->eventTypeDescription = & $GLOBALS['_transient']['static']['currentevent_class']->eventTypeDescription;
		$this->eventTypeInvitationString = & $GLOBALS['_transient']['static']['currentevent_class']->eventTypeInvitationString;
		$this->birthdayList = & $GLOBALS['_transient']['static']['currentevent_class']->birthdayList;
		$this->eventTeamShortName = & $GLOBALS['_transient']['static']['currentevent_class']->eventTeamShortName;
		
		/* Initialize if necessary */
		if (! $this->eventId){
			if($this->_determineId()){
				$this->_makeQueries();
			}	
		}
		
	}
	/**
	* @return void
	* @desc Resets all variables and requeries all data
	*/
	function _reinitialize(){
		$this->_makeQueries();
	}
	/**
	* @return eventId
	* @desc Sets eventId from post or get
	*/
	function _determineId(){
		global $eventFieldname;
		if ($_POST[$eventFieldname]){
			$this->eventId = $_POST[$eventFieldname];
		}else if($_GET[$eventFieldname]){
			$this->eventId = $_GET[$eventFieldname];
		}
		return $this->eventId;
	}
	/**
	* @return void
	* @desc Queries database for all values
	*/
	function _makeQueries(){
		$this->_makeQueryEventDetails();
		$this->_makeQueryReplyTypes();
		$this->_makeQueryMyAnswer();
		$this->_makeQueryAllAnswers();
		$this->_defineBirthdayList();
		$this->_findIfInOtherTeam();
		$this->remedyRepliesString = $this->_calculateRemedyReplies();
	}
	/**
	* @return array
	* @desc Calculates propability of remedies being brought to event
	*/
	function _calculateRemedyReplies(){
		$remedyReplies = array();
		$allReplies = $this->GetAllReplies();
		$remedyObj = new Remedies_class();
		$remedyIds = $remedyObj->GetArrayOfIds($this->GetEventRemedies());
		
		// Count remedy-types
		foreach ($remedyIds as $remedyId){
			$remedyReplies[$remedyId]['id'] =	$remedyId;
			$remedyReplies[$remedyId]['values'] = array();
			$remedyReplies[$remedyId]['certain'] = 0;
			$remedyReplies[$remedyId]['finalValue'] = 0;
		}
		
		// Count replies
		foreach ($allReplies as $reply){
			$type = $reply['type'];
			$value = $reply['attentionValue'];
			$remedyValue = $reply['remedies'];
			if(($value*$remedyValue > 0) && $type !=4){
				if ($type == 2){
					$value = 1;	
				}
				$remediesReported = $remedyObj->GetArrayOfIds($remedyValue);
				foreach ($remediesReported as $remedyReported){
					if($value == 1){
						$remedyReplies[$remedyReported]['certain'] += 1;	
					}else{
						$remedyReplies[$remedyReported]['values'][]=$value;
					}
				}
			}
		}
		
		// Count remedies
		$certainRemedies = array();
		$multipleRemedies = array();
		$missingRemedies = array();
		$uncertainRemedies = array();
		
		foreach($remedyReplies as $remedy){
			$shortName = $remedyObj->GetShortName($remedy['id']);
			if ($remedy['certain'] > 0){
				if($remedy['certain'] > 1){
					$multipleRemedies[] = NorwegianTextNumbers($remedy['certain'])." som har med ".$shortName;
				}else{
					$certainRemedies[] = $shortName;
				}
			}elseif (count($remedy['values']) > 0){
				$remedy['finalValue'] = $this->_calculateProbabilityOfSingleInstance($remedy['values']);
				$uncertainRemedies[] = (round($remedy['finalValue']*20)*5)."% sjanse for at noen har med ".$shortName;
			}else{
				$missingRemedies[] = $shortName;
			}
		}
		// Construct output string
		$output = "";
		if (count($certainRemedies) > 0){
			$output .= "Det er ordnet med ".SeparatedList($certainRemedies).". ";	
		}
		if(count($multipleRemedies) > 0) {
			$output .= "Til alt overm&aring;l er det ".SeparatedList($multipleRemedies).". ";	
		}
		if(count($uncertainRemedies)>0){
			$output .= "Det er ".SeparatedList($uncertainRemedies).". ";
		}
		if(count($missingRemedies)>0){
			$output .= "<em>Vi mangler ".SeparatedList($missingRemedies)."! </em>";	
		}
			
		return $output;
	}
	/**
	* @return float
	* @param float[] $probArray
	* @desc Returns probability of instance happening once given P's in array
	*/
	function _calculateProbabilityOfSingleInstance($probArray){
		$out = 0;
		if(count($probArray)==0){
			$out = 0;
		}elseif (count($probArray)==1){
			$out = $probArray[0];
		}else{
			$probThis = array_pop($probArray);
			$probRest = $this->_calculateProbabilityOfSingleInstance($probArray);
			$out = $probThis+$probRest-($probThis*$probRest);
		}
		return $out;	
	}
	/**
	* @return void
	* @desc Gets information on the event
	*/
	function _makeQueryEventDetails(){
		$queryString = "
			SELECT 
				events.dateStart,
				events.dateEnd,
				events.location,
				events.description,
				events.type,
				events.cancelled,
				events.notes,
				events.url,
				events.team, 
				events.notified,
				events.remediesNeeded,
		
				eventTypes.id AS typeId,
				eventTypes.type AS typeName,
				eventTypes.description AS typeDescription,
				eventTypes.invitationString AS typeInvitation,
		
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
			WHERE events.id=".$this->eventId."
		";
		$query = new DBConnector_class($queryString);
		$details = $query->GetNextRow();
		
		$this->startDateTime = strtotime($details['dateStart']);
		$this->endDateTime = strtotime($details['dateEnd']);
		$this->locationString = $details['location'];
		$this->subjectString = $details['description'];
		$this->isCancelled = $details['cancelled'];
		$this->notesString = $details['notes'];
		$this->url = $details['url'];
		$this->eventRemedies = $details['remediesNeeded'];
		$this->eventTeam = $details['team'];
		$this->emailIsSent = $details['notified'];
		$this->eventTypeId = $details['typeId'];
		$this->eventTypeName = $details['typeName'];
		$this->eventTypeDescription = $details['typeDescription'];
		$this->eventTypeInvitationString = $details['typeInvitation'];
		$this->minimumCount = $details['minimum'];
		$this->maximumCount = $details['maximum'];
		$this->hasMaxCount = ($details['hasMaxCount']==1);
	}
	/**
	* @return void
	* @desc Gets information on current players answer
	*/
	function _makeQueryMyAnswer(){
		$player = new Player_class();
		$playerId = $player->getId();
		$eventId = $this->GetId();
		$out = "";
		if ($playerId){
			$queryString = "
				SELECT
					attention.id, attention.type, notes, date, initialDate, remedies,
					registeredBy,
					IF (attention.customValue IS NULL, attentionType.value, attention.customValue) AS myValue
				FROM attention
				LEFT JOIN attentionType
				ON attentionType.id = attention.type
				WHERE player = $playerId
					AND event = $eventId
			";
			$query = new DBConnector_class($queryString);
			// Checks for duplicate confirmations
			$numberOfReplies = $query->GetNumberOfRows();
			if ($numberOfReplies > 0){	
				if ($numberOfReplies == 1){
					// One confirmation found
					$details = $query->GetNextRow();
					
					$this->myReplyId = $details['id'];
					$this->myReplyType = $details['type'];
					$this->myReplyNotes = $details['notes'];
					$this->myReplyFirstDate = $details['initialDate'];
					$this->myReplyLastDate = $details['date'];
					$this->myReplyValue = $details['myValue'];
					$this->myReplyRemedies = $details['remedies'];
				}else{
					// Duplicate confirmations
					$this->_removeDuplicateConfirmations();
				}
				$out = true;
			}else{
				/* No confirmation made yet */
				$out = false;
			}
		}
		return $out;
		
	}
	/**
	* @return void
	* @desc Deletes everyone but last confirmation of current player to current event
	*/
	function _removeDuplicateConfirmations(){
		$player = new Player_class();
		$playerId = $player->getId();
		$eventId = $this->GetId();
		if($playerId){
			// Get first create date of confirmations;
			$createQueryString = "
				SELECT 
					MIN(initialDate) AS first,
					MAX(date) AS last
				FROM attention
				WHERE player = $playerId
					AND event = $playerId
			";
			$createQuery = new DBConnector_class($createQueryString);
			$createQueryRow = $createQuery->GetNextRow();
			$firstCreateDate = $createQueryRow['first'];
			$lastEditDate = $createQueryRow['last'];
			
			// Delete everything but the last attention
			$deleteQueryString = "
				DELETE
				FROM attention
				WHERE player = $playerId
					AND event = $playerId
					AND date != '$lastEditDate'
			";
			$deleteQuery = new DBConnector_class($deleteQueryString);
			
			// Update createdate
			$updateQueryString = "
				UPDATE attention
				SET initialDate = '$firstCreateDate'
				WHERE player = $playerId
					AND event = $playerId
			";
			$updateQuery = new DBConnector_class($updateQueryString);
			
			// Run new selectQuery
			$this->_makeQueryMyAnswer();
		}
	}
	/**
	* @return void
	* @desc Gets information on all players answers
	*/
	function _makeQueryAllAnswers(){
		$queryString = "
			SELECT 
				attention.id,
				attention.type,
				attention.notes,
				attention.date,
				attention.initialDate,
				attention.registeredBy,
				attention.remedies,
		
				players.id AS playerId,
				players.firstName,
				players.lastName,
				players.picture,
				players.residence,
				players.phone,
				players.email,
				players.url,
				
				membership.number,
				membership.teamTrainer,
				
				(IF(players.dubviousAge,DATE_SUB(NOW(),INTERVAL 1 DAY),players.age)) AS playerAge,	
				(IF (attention.customValue IS NULL, attentionType.value, attention.customValue)) AS attentionValue,
				'$this->eventTeam' AS team
		
			FROM attention
			LEFT JOIN attentionType
				ON attentionType.id = attention.type
			LEFT JOIN players 
				ON attention.player = players.id
			LEFT JOIN membership 
				ON players.id = membership.player 
				AND membership.team = ".$this->eventTeam."
			WHERE attention.event = ".$this->eventId."
			ORDER BY ";
		$queryString .= $this->GetHasMaxLimit() ? "attention.initialDate ASC" : "attentionValue DESC, players.lastName ASC";
		$query = new DBConnector_class($queryString);
		$this->_distributeReplies($query->GetQuery());
	}
	/**
	* @return boolean
	* @desc Returns true if start and end times of event are considered real
	*/
	function GetHasTime(){
		$start = $this->startDateTime;
		$end = $this->endDateTime;
		if (date("H:i",$start)==date("H:i",$end) && date("H:i",$end)=="00:00"){
			$out = false;
		}else{
			$out = true;
		}
		return $out;
	}
	function GetVcalLink(){
		global $eventFieldname;
		$eventId = $this->GetId();
		$out =  "<a 
			href='vcal-generator.php?{$eventFieldname}={$eventId}' 
			name='vcalendar' 
			title='Last ned hendelsen til kalenderen din. Det forutsettes at programmet du bruker (Outlook, Notes eller lignende) støtter dette formatet.'>";
		return $out;	  	
	}
	function _defineBirthdayList(){
		$team = new Team_class();
		$teamId = $team->get_id();
		$startDate = strtotime(date("d-M-Y", $this->startDateTime ));
		$endDate = strtotime(date("d-M-Y", $this->endDateTime ));
		
		$daysBetween = DateDiff('d',$startDate,$endDate);
		$players = array();
		
		for ($dateAdd = 0; $dateAdd < $daysBetween+1 ; $dateAdd++){
			$players = array_merge($players,$this->_queryForBirthdays(DateAdd('d',$dateAdd,$startDate)));
		}
		
		$this->birthdayList = SeparatedList($players);
	}
	function _queryForBirthdays($day){
		$out = array();
		$teamId = $this->eventTeam;
		$formattedDate = date("Y-m-d", $day);
		$queryString = "
			SELECT CONCAT(firstName,' ',lastName,' (',
				IF((DATE_FORMAT('$formattedDate','%Y') - DATE_FORMAT(age,'%Y')) > 0
				,(DATE_FORMAT('$formattedDate','%Y') - DATE_FORMAT(age,'%Y'))
				,'f&oslash;dt i g&aring;r?'),
				')'
				) AS fullName
			FROM players
			LEFT JOIN membership ON players.id=membership.player
			WHERE DATE_FORMAT(age,'%d%m') = DATE_FORMAT('$formattedDate','%d%m')
				AND (dubviousAge != '1' OR ISNULL(dubviousAge))
				AND membership.team = $teamId
				AND (DATE_FORMAT('$formattedDate','%Y') - DATE_FORMAT(age,'%Y')) > 0
			ORDER BY age ASC
		";
		$query = new DBConnector_class($queryString);
		while($row = $query->GetNextRow()){
			$out[] = $row['fullName'];	
		}
		return $out;
	}
	/**
	* @return void
	* @desc determins if event is in other team-space than the one logged in
	*/
	function _findIfInOtherTeam(){
		$team = new Team_class();
		$teamId = $team->get_id();
		if ($this->eventTeam != $teamId){
			$player = new Player_class();
			$this->eventNotInCurrentTeam = true;
			if ($player->getId()){
				$teamData = $player->GetTeamsData();
				$this->eventTeamShortName = $teamData[$this->eventTeam]['shortName'];
			}else{
				$this->eventTeamShortName = "Ukjent lag!";
			}
		}else{
			$this->eventNotInCurrentTeam = false;	
		}
	}
	
	function GetTeamId(){
		return $this->eventTeam;	
	}
	
	function _makeQueryReplyTypes(){
		if (count($this->replyTypes)==0){
			$queryString = "
				SELECT id, type, shortName, value
				FROM attentionType
				ORDER BY listOrder ASC
			";
			$query = new DBConnector_class($queryString);
			while($reply=$query->GetNextRow()){
				$thisReplyType = array();
				$thisReplyType['longName']=$reply['type'];
				$thisReplyType['shortName']=$reply['shortName'];
				$thisReplyType['value']=$reply['value'];
				$thisReplyType['id']=$reply['id'];
				$thisReplyType['listOrder']=$reply['listOrder'];
				$this->replyTypes[] = $thisReplyType;
			}
		}
	}
	
	function GetIsSafeOnWaitingList(){
		$maxLim = $this->maximumCount;
		$myReplyIndex = $this->myReplyRankingIndex;
		$hasReplied = $this->GetIsConfirmed();
		
		$out = ($hasReplied && ($myReplyIndex < $maxLim));
		
		return $out;
		
	}
	
	/**
	* @return int
	* @desc Returns number in list of replies to event.
	*/
	function GetMyReplyRanking(){
		return $this->myReplyRankingIndex+1;	
	}
	
	function _distributeReplies($allReplies){
		$noIndex = 4;
		$yesIndex = 2;
		$replyIndex = 0;
		$confirmReplyIndex = 0;
		$player = new Player_class();
		$playerId = $player->getId();
		$this->allReplies = array(); //To delete old data
		while($reply = mysql_fetch_assoc($allReplies)){
		
			$age = $reply['playerAge'];
			$reply['playerAge'] = date("dmy",strtotime($age));
			$reply['RankIndex'] = $replyIndex;
			$this->allReplies[$reply['type']][]=$reply;
			$this->allReplies['all'][]=$reply;
			
			if ($this->maximumCount > 0) {
				if ($reply['playerId']==$playerId){
					$this->myReplyRankingIndex = $replyIndex;
					if($confirmReplyIndex >= $this->maximumCount){
						if($reply['type'] != $noIndex){
							$this->onWaitingList = true;
						}
					}	
				}
				
				if ($reply['type']==$yesIndex){
					$confirmReplyIndex++;	
				}
			}
			
			$replyIndex++;
		}
	}
	/**
	* @return float
	* @param "min"|"max" $limit
	* @desc Calculates the probability of sufficient (or too many) players attending
	*/
	function GetProbabilityOfSufficientReplies($limit = "min"){
		$minimum = ($limit=="min") ? $this->minimumCount : $this->maximumCount+1;
		$confirmedCount = 0; //Number of players certainly attending
		$possibles = array();

		
		//Create array of replies with values <0,1> and count values==1
		foreach($this->allReplies['all'] as $reply){
			$value = $reply['attentionValue'];
			if($value > 0){
				if($value==1){
					$confirmedCount++;	
				}else{
					$possibles[] = $value;	
				}
			}
		}
		
		$newMinLimit = $minimum-$confirmedCount;
		if ($newMinLimit < 1){
			//Enough has replied their definite attendence
			$out = 1;
		}elseif (count($possibles) < $newMinLimit){
			//There are not enough maybes to fill the quota
			$out = 0;	
		}else{
			$emptyArray = array();
			$counter = 0;
			$out = $this->_permutationsOfPossibleAttentions($emptyArray,$possibles,$emptyArray,$newMinLimit,$counter);
		}
		return $out;
	}
	/**
	* @return float
	* @param array $arrayOfPast
	* @param array $arrayOfFuture
	* @param array $arrayOfAll
	* @param int $minLimit
	* @param int $recursiveCount
	* @desc Calculates probability of events to happen
	*/
	function _permutationsOfPossibleAttentions($arrayOfPast, $arrayOfFuture, $inverseArray, $minLimit, &$recursiveCount){
		$out = 0;
		$maxCount = 100000; 
		if ($newCount < $maxCount ){
			if (count($arrayOfPast)+1 >= $minLimit){
				$sufficientCount = true;	
			}
			
			$newArrayOfFuture = $arrayOfFuture;
			for($index=0; $index < count($arrayOfFuture) ; $index ++){
				$current = $arrayOfFuture[$index];
				$newArrayOfPast = $arrayOfPast;
				$newArrayOfPast[] = $current;
				$newArrayOfFuture = array_slice($newArrayOfFuture,1);
			
				if ($sufficientCount){
					$out += $this->_calculateProbability($newArrayOfPast,array_merge($newArrayOfFuture,$inverseArray));	
				}
				$recursiveValue= $this->_permutationsOfPossibleAttentions($newArrayOfPast,$newArrayOfFuture,$inverseArray,$minLimit,++$recursiveCount);
				
				if ($recursiveValue == -1){
					//To many recursions. Exit function!
					return $recursiveValue;
				}else{
					$out += $recursiveValue;	
				}
				$inverseArray[]=$current;
			}
		}else{
			//Giving up
			$out = -1;	
		}
		return $out;	
	}
	function _calculateProbability($combinationArray,$inverseArray){
		$out = count ($combinationArray) ? 1: 0;
		foreach ($combinationArray as $element){
			$out *= $element;	
		}
		foreach ($inverseArray as $inverseElement){
			$out *= (1-$inverseElement);	
		}
		return $out;
	}
	/**
	* @return int
	* @desc Get Id of current (chosen) event
	*/
	function GetId(){
		return $this->eventId;	
	}
	/**
	* @return string
	* @desc Get heading of event
	*/
	function GetSubject(){
		return $this->subjectString;	
	}
	/**
	* @return html
	* @desc A swf replycounter
	*/
	function ReplyCounter(){
		$minLimit = $this->minimumCount;
		$maxLimit = $this->maximumCount;
		$confirmedCount = $this->GetNumberOfConfirmed();
		$expectance = $this->GetExpectationOfAttendeesCount(false);
		$names = array();
		$numbers = array();
		$counter = 0;
		
		//Get string of names and numbers to send to the flash-movie
		foreach ($this->GetAllReplies(2) as $reply){
			$counter++;
			$names[] = "player{$counter}Name=".$reply['firstName'];
			if(is_numeric($reply['number'])){
				$numbers[] = "player{$counter}Number=".$reply['number'];
			}
		}
		if (count($names)){
			$listOfNamesAndNumbers = implode("&",$names);
		}
		if (count($numbers)){
			$listOfNamesAndNumbers .= "&".implode("&",$numbers);
		}
		if ($listOfNamesAndNumbers) {
			$listOfNamesAndNumbers = "&".$listOfNamesAndNumbers;	
		}
		
		//Start writing code to insert Flash movie
		$out = "";
		$out .= "
			<object 
				classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' 
				codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0' 
				width='400' 
				height='70'>
			<param name='movie' value='images/replyCounter.swf?minLimit=$minLimit&maxLimit=$maxLimit&confirmedCount=$confirmedCount&Expectance=$expectance$listOfNamesAndNumbers'>
			<param name='quality' value='autohigh'>
			<param name='WMODE' value='transparent'>
			<embed 
				src='images/replyCounter.swf?minLimit=$minLimit&maxLimit=$maxLimit&confirmedCount=$confirmedCount&Expectance=$expectance$listOfNamesAndNumbers'
				quality='autohigh' 
				wmode='transparent'
				pluginspage='http://www.macromedia.com/go/getflashplayer' 
				type='application/x-shockwave-flash' 
				width='400' 
				height='70'>
			</embed>
			</object>";
		return $out;
	}
	function GetBirthdayList(){
		return $this->birthdayList;	
	}
	function GetReplyTypes(){
		return $this->replyTypes;	
	}
	function GetAllReplies($type=0){
		if ($type){
			$out = $this->allReplies[$type];
		}else{
			$out = $this->allReplies['all'];
		}
		return $out;	
	}
	/**
	* @return boolean
	* @desc Check if this event is confirmed by current player
	*/
	function GetIsConfirmed(){
		return ($this->myReplyId > 0);	
	}
	function GetMyConfirmId(){
		return $this->myReplyId;	
	}
	function GetMyConfirmType(){
		return $this->myReplyType;	
	}
	function GetMyConfirmNotes(){
		return $this->myReplyNotes;
	}
	function GetMyConfirmRemedies(){
		return $this->myReplyRemedies;	
	}
	function GetWebPageUrl(){
		return $this->url;	
	}
	/**
	* @return boolean
	* @desc Whether any replies to event has been entered
	*/
	function GetHasConfirmations(){
		$out = count($this->allReplies['all']) > 0;
		return $out;
	}
	/**
	* @return boolean
	* @desc Whether a max limit for number of attendees has been set.
	*/
	function GetHasMaxLimit(){
		return $this->hasMaxCount;	
	}
	/**
	* @return string
	* @desc Returns a string stating the maximum limit of event. Returns an empty string if no limit is set.
	*/
	function GetMaxLimitWarningString(){
		$out = "";
		$safeOnWaitingList = $this->GetIsSafeOnWaitingList();
		if ($this->GetHasMaxLimit()){
			$max = $this->GetMaxLimit();
			$out .= "Det er satt en &oslash;vre grense p&aring; "; 
			$out .= NorwegianTextNumbers($max)." ";
			$out .= $max > 1 ? "deltakere" : "deltaker";
			$out .= " p&aring; dette arrangementet. <br>";
			$out .= $max >1 ? "De " : "Den ";
			$out .= "f&oslash;rste som registrerer svar ";
			$out .= "kommer f&oslash;rst i k&oslash;en";
			if ($this->GetIsConfirmed()){
				$out .= "\n.<br>Du er den ".NorwegianTextNumbers($this->GetMyReplyRanking(),true)." som har svart";
			}
			if ($safeOnWaitingList){
				$out .= " s&aring; du har sikker plass om du &oslash;nsker. ";	
			}else{
				$out .= ". Spillere med <strong>uthevede navn</strong> har svart f&oslash;r deg. ";
			}
		}
		if ($this->onWaitingList){
			$out .= "<br>Desverre har du svart for sent til &aring; komme med, 
					 slik svarene er gitt n&aring;.";	
		}
		return $out;	
	}
	/**
	* @return html
	* @desc Returns a summary og confirmations
	*/
	function SumUpTable(){
		
		$probabilityMin = $this->GetProbabilityOfSufficientReplies("min");
		
		$confirmedCount = $this->GetNumberOfConfirmed();
		$roundedExpectation = $this->GetExpectationOfAttendeesCount(true);
		//Determine exactstring
		if ($confirmedCount == $roundedExpectation) {
			$exactString = "s&aring; langt ";
			if ($probabilityMin >= 0 && $probabilityMin <0.8){
				$missingNumberOfConfirmations = $this->GetMinLimit() - $roundedExpectation;
				$exactString .= " (under forutsetning av at det meldes p&aring; ";
				$exactString .= NorwegianTextNumbers($missingNumberOfConfirmations,false);
				$exactString .= " til) ";
			}
		}else{
			$exactString = "sannsynligvis ";	
		}
		
		switch ($roundedExpectation){
			case 0:
				$expectationString .= " Det er nemlig ikke forventet at noen kommer. ";
				break;
			case 1:
				$expectationString .= " Det kommer antagelig bare &eacute;n. ";
				break;
			default: 
				$expectationString .= " Det kommer $exactString".NorwegianTextNumbers(($roundedExpectation), false)." stykker. ";
		}
		
		//Calculate probability for sufficient.
		$probabilityString = "";
		$rounding = 5;
		switch ($probabilityMin){
			case -1:
				break;
			case 0:
				$probabilityString .= " Det trengs flere p&aring;meldinger. ";
				break;
			case 1:
				$probabilityString .= " Hendelsen kan gjennomf&oslash;res. ";
				break;
			default:
				$exactPercentMin = $probabilityMin * 100;
				$somewhatRoundedPercentMin=round($probabilityMin*10000)/100;
				$roundedPercent = round($probabilityMin*(100/$rounding))*$rounding;
				$roundedPercent = ($roundedPercent == 0) ? "ingen" : $roundedPercent."%";
				$disclaimerString = ($exactPercentMin == $roundedPercent) ? "temmelig n&oslash;yaktig " : "omtrent ";
				$probabilityString .= " Det er ";
				$probabilityString .= "<span title='Noks&aring; n&oslash;yaktig {$somewhatRoundedPercentMin}%'>";
				$probabilityString .= ($roundedPercent == "100%") ? "s&aring; godt som garantert " : "{$disclaimerString}{$roundedPercent} sjanse for ";
				$probabilityString .= "</span>";
				$probabilityString .= "at det kommer mange nok. ";	
		}
		if($this->GetHasMaxLimit()){
			$probabilityMax = $this->GetProbabilityOfSufficientReplies("max");
			switch ($probabilityMax){
				case -1:
					break;
				case 0:
					$probabilityString .= " Det er heldigvis ingen fare for at det kommer for mange. ";
					break;
				case 1:
					$probabilityString .= " Det kommer tydeligvis for mange folk! ";
					break;
				default:
					$exactPercentMax = $probabilityMax * 100;
					$somewhatRoundedPercentMax=round($probabilityMax*10000)/100;
					$roundedPercent = round($probabilityMax*20)*5;
					$disclaimerString = ($exactPercentMax == $roundedPercent) ? "temmelig n&oslash;yaktig " : "omtrent ";
					$probabilityString .= " Det er <span title='Noks&aring; n&oslash;yaktig {$somewhatRoundedPercentMax}%'>{$disclaimerString}{$roundedPercent}% sjanse</span> for at det kommer for mange. ";	
			}	
		}
		
		$out = "
			<h3>Forel&oslash;pig oppsummering</h3>
		";
		$out .= $this->GetExpectationOfAttendeesCount(false) ? $this->ReplyCounter() : "";
		$out .= "
			<table width = '95%'>";
		
		$out2 = "
			<tr><td>";
		$summaryReplyArray = array();
		$colCount = 0;
		foreach ($this->replyTypes as $type){
			$count = count( $this->allReplies[ $type['id'] ] );
			if ($count){
				$summaryReplyArray[] = "$type[shortName]:</em> ".$count;
			}
		}
		$out2 .= SeparatedList($summaryReplyArray);
		
		$out2 .= "</td></tr>";
		
		
		$out .= "
			<tr><td>";
		
		$allConfirmed = count ($this->allReplies[2])+count ($this->allReplies[3])+count ($this->allReplies[5]);
		if ($this->GetHasMaxLimit() && ($allConfirmed > $this->maximumCount )){
			$out .= "Det er fare for at for mange m&oslash;ter opp. ";
		}else{
			$out .= $this->_approximatePercentConfirm();
		}
		$out .= " $expectationString";
		$out .= "</td>";
		
		$out .= "</tr>";
		
		$out .= $out2;
		
		$out .= "
			<tr><td>$probabilityString ".$this->remedyRepliesString."</td></tr>
			";		
		$out .= "</table>";
		return $out;
	}

	/**
	* @return int
	* @param boolean $rounded
	* @desc Calculates the expected number of attendees;
	*/
	function GetExpectationOfAttendeesCount($rounded=true){
		foreach ($this->allReplies['all'] as $reply){
				$expectation += $reply['attentionValue'];
		}
		$out = $rounded ? floor($expectation) : $expectation;
		return $out;
	}
	function GetNumberOfConfirmed(){
		$replyTypeYes = 2;
		$out = count($this->allReplies[$replyTypeYes]);
		return $out;
	}
	/**
	* @return string
	* @desc Returns a describing string of approximation of confirmations based on the values of confirmtypes.
	*/
	function _approximatePercentConfirm(){
		$accumulatedValue = 0;
		$outValue = 0;
		$outString = "";
		$minVal = $this->minimumCount;
		$expectation = $this->GetExpectationOfAttendeesCount(false);
		if ($this->GetIsSufficientConfirmed()){
			$outString = "Det kommer helt sikkert nok folk.";
		}else{
			$outValue = $expectation / $minVal;
			if ($outValue >= 0.95){
				$outString = "Det blir noks&aring; sikkert mange nok.";	
			}elseif ($outValue < 0.3){
				$outString = "Det er temmelig tvilsomt at det blir mange nok.";
			}else{
				$percentVal = round($outValue,1)*100;
				$outString = "Det er i beste fall forventet at omtrent {$percentVal}%
					 av det n&oslash;dvendige minimum m&oslash;ter opp. ";
			}
		}
		
		return $outString;
	}
	
	/**
	* @return boolean
	* @desc Returns true if sufficient number of confirmed.
	*/
	function GetIsSufficientConfirmed(){
		$yesType = 2;
		$minVal = $this->minimumCount;
		$out = (count($this->allReplies[$yesType]) >= $minVal);
		return $out;
	}
	
	/**
	* @return int
	* @desc Returns the maximum number of attendees. Returns 0 if no limit is set.
	*/
	function GetMaxLimit(){
		return $this->maximumCount;	
	}
	function GetMinLimit(){
		return $this->minimumCount;
	}
	/**
	* @return string
	* @desc gets friendlydate of last reply
	*/
	function GetReplyDateString(){
		$out = "";
		$date = $this->GetMyConfirmDate("lastedit");
		$dateFirst = $this->GetMyConfirmDate("create");
		$hours = (time()-$date)/(60*60);
		$createEditSameDay = (strtotime(date("d-F-Y",$date)) == strtotime(date("d-F-Y",$dateFirst)));
		if ($date) {
			// Puts in first replydate if there is a max limit on event
			if($this->GetHasMaxLimit() && ! $createEditSameDay){
				$hoursFirst = (time()-$dateFirst)/(60*60);
				$out .= "Du svarte f&oslash;rste gang ";
				$out .= RecentDateTime($dateFirst, $hoursFirst);
				$out .= ". Ditt siste svar var ";
			}else{
				$out .= "&nbsp;Du svarte ";
			}
			$out .= RecentDateTime($date,$hours);
			
			$out .= ":&nbsp;";
		}
		return $out;
	}
	/**
	* @return date
	* @param create|lastedit $type
	* @desc Gets datestamp of confirmation
	*/
	function GetMyConfirmDate($type="create"){
		if ($type=="create"){
			$out = strtotime($this->myReplyFirstDate);
		}else{
			$out = strtotime($this->myReplyLastDate);	
		}
		return $out;
	}
	/**
	* @return string
	* @desc Gets descriptive string of event dependent on whether confirmation is entered
	*/
	function GetInvitationOrDescription(){
		if($this->GetIsConfirmed()){
			$out = $this->eventTypeDescription;
		}elseif($this->GetIsCancelled()){
			$out = "";
		}else{
			$out = $this->eventTypeInvitationString;
		}
		return $out;
	}
	function GetDurationInHours(){
		$start = $this->startDateTime;
		$end = $this->endDateTime;
		$out = round(($end-$start)/3600,1);	
		return $out;
	}
		/**
	* @return string
	* @desc Returns short name of team only if event is not in current team
	*/
	function GetNameOfTeam(){
		return $this->eventTeamShortName;	
	}
	function GetIsCancelled(){
		return $this->isCancelled;	
	}
	function GetEventType(){
		return $this->eventTypeId;	
	}
	function GetEventRemedies(){
		return $this->eventRemedies;	
	}
	function GetStartsAndEndsInSameDay(){
		$start = getdate($this->startDateTime);
		$end = getdate($this->endDateTime);
		$out = ($start['yday'].$start['year']) == ($end['yday'].$end['year']);
		return $out;
	}
	/**
	* @return html
	* @desc Returns table with form fields for replying to event
	*/
	function MyConfirmFormFields(){
		global $attentionFieldName, $attentionNotesFieldName, $attentionCustomValueFieldName;
		
		$hasReplied = $this->GetMyConfirmId();
		$noReplyIndicator = "Du har ikke svart";
		$htmlRange1Id = "probabilityRange";
		$htmlRange2Id = "probabilityRange2";
		
		$replyTypeYes = '2';
		$replyTypeNo = '4';
		$replyTypeDontKnow = '1';
		$replyTypeMaybeYes = '3';
		$replyTypeMaybeNo = '5';
		
		$currentReplyType = $this->GetMyConfirmType();
		$currentReplyNotes = $this->GetMyConfirmNotes();
		$allReplyTypes = $this->replyTypes;
		
		if ($hasReplied && ($currentReplyType!=$replyTypeDontKnow)){
			$myReplyValue = ($this->myReplyValue)*100;
		}else{
			if ($currentReplyType==$replyTypeDontKnow){
				$myReplyValue = "' '";
			}else{	
				$myReplyValue = "'$noReplyIndicator'";
			}
		}
		
		$customValueString = "
				<p id='$htmlRange1Id'></p>
				<p id='$htmlRange2Id'>
					Sannsynligheten er: 
						<input 
							type='text' 
							name='$attentionCustomValueFieldName' 
							id='$attentionCustomValueFieldName'
							size='2' maxlength='5'
							onBlur='ValidateCustomValue();'
							tabindex='3'
							value='$myReplyValue'>
					%.
				</p>
			";
		
		$javaScriptString = "
			function SetCustomValue(){
				rng1 = document.getElementById('$htmlRange1Id');
				rng2 = document.getElementById('$htmlRange2Id');
				customTxt = document.getElementById('$attentionCustomValueFieldName');
				typeCmb = document.getElementById('$attentionFieldName');

				
				typeId = typeCmb.value;
				
				customValue = DefaultValue(typeId);
				customTxt.value = customValue;
		
				HideShowText(customValue);

			}
			function HideShowText(customValue){
				rng1 = document.getElementById('$htmlRange1Id');
				rng2 = document.getElementById('$htmlRange2Id');
				customTxt = document.getElementById('$attentionCustomValueFieldName');
				typeCmb = document.getElementById('$attentionFieldName');
				if (customValue == '$noReplyIndicator'){
					//No reply yet
					rng1.style.display='inline';
					rng2.style.display='none';
					rng1.innerText='Du har ikke svart enn'+unescape('%E5')+'.';
				}else if (customValue === ' ' || customValue ===''){
					//Don't know
					rng1.style.display='inline';
					rng2.style.display='none';
					rng1.innerText='Du vet ikke hvor sannsynlig det er at du kommer.';
				}else if (customValue >= 100){
					//Absolute certain
					rng1.style.display='inline';
					rng2.style.display='none';
					rng1.innerText='Det er helt sikkert at du kommer.'
				}else if (customValue <= 0){
					//Certainly not
					rng1.style.display='inline';
					rng2.style.display='none';
					rng1.innerText='Du kommer helt sikkert ikke.'
				}else{
					//Percentage
					rng1.style.display='none';
					rng2.style.display='inline';
					rng1.innerText=''
				}
			}
			function InitializeForm(){
				HideShowText($myReplyValue);
			}
			function ValidateCustomValue(){
				rng1 = document.getElementById('$htmlRange1Id');
				rng2 = document.getElementById('$htmlRange2Id');
				customTxt = document.getElementById('$attentionCustomValueFieldName');
				typeCmb = document.getElementById('$attentionFieldName');
		
				valueString = new String(customTxt.value);
				var newValue = valueString.replace(',','.');
				
				if (newValue == '' || newValue == ' '){
					//Don't know
					typeCmb.value = $replyTypeDontKnow;
					newValue = ' ';
					lastSensibleValue = newValue;
				}else{
			
					if (newValue != newValue*1){
						//Not a number
						newValue = lastSensibleValue;
						
					}
					
					newValue /= 100;		
					
					if (newValue >= 1){
						//Absolute certain
						typeCmb.value = $replyTypeYes;
						newValue = 1;
					}else if(newValue <= 0){
						//Absolute certainly not
						typeCmb.value = $replyTypeNo;
						newValue = 0;
					}else if (newValue > 0.5){
						//Maybe yes
						typeCmb.value = $replyTypeMaybeYes;
					}else if (newValue < 0.5){
						//Maybe no
						typeCmb.value = $replyTypeMaybeNo;
					}
					lastSensibleValue = newValue * 100;	
					valueString = new String(lastSensibleValue);
					customTxt.value = valueString.replace('.',',');
				}
				HideShowText(lastSensibleValue);
			}
			function DefaultValue(id){
				var arrayOfValues = new Array();";
					foreach ($allReplyTypes as $replyType){
						$replyValue = $replyType['value']=='' ? ' ' : $replyType['value'];
						$javaScriptString .= "
						arrayOfValues[{$replyType[id]}] = '$replyValue';
						";	
					}
		$javaScriptString .= "
				out = arrayOfValues[id];
				if (out != ' '){
					out = out*100;
				}else{
					out = ' ';
				}
				return out;
			}
			var lastSensibleValue = $myReplyValue;
			InitializeForm();
		
		";
		$javaScriptString = JavaScriptWrapper($javaScriptString);
		
		$replyCombo = "
			<select 
				name='$attentionFieldName' 
				id='$attentionFieldName'
				onChange='SetCustomValue();'
				tabindex='1'>
			";
			foreach($allReplyTypes as $replyType){
				$replyCombo .= "
					<option value='$replyType[id]'";
				$replyCombo .= ($replyType['id']==$currentReplyType) ? " selected>" : ">";
				$replyCombo .= $replyType['longName']."</option>";
			}
		$replyCombo .= "
	      </select>
		";
		if (! $this->GetIsCancelled()){
			$out .= "
				<table><tr>";
			$out .= "<td class=normalText>Ditt svar:</td>";
			$out .= "<td>";
			$out .= $replyCombo;
			$out .= "</td>";
			$out .= "<td>
				<input name='attentionSubmit_button' 
				type='submit' 
				id='attentionSubmit_button' 
				value='Registrere svar'
				tabindex='4'> 
				</td></tr>
				";
			$out .= "<tr><td class=normalText>Kommentar:</td>";

			
			$out .= "<td colspan='1'>
				<input name='$attentionNotesFieldName' 
				type='text' 
				id='attentionNotes_text' 
				value='$currentReplyNotes' 
				size='28' 
				maxlength='255'
				tabindex='2'>
				</td>
				";
			$out .= "<td>$customValueString</td>";
			$out .= "</tr>";
			
			$remediesObj = new Remedies_class();
			$remedyControls = $remediesObj->RenderReplyNeededRemediesControl($this->GetEventRemedies(),$this->GetMyConfirmRemedies());
			if ($this->GetEventRemedies() > 0){
				$out .= "\n<tr>$remedyControls</tr>\n";
			}
			$out .= "\n</table>\n";
			
			
			$out .= $javaScriptString;
		}else{
			$out = "<p>Denne hendelsen er avlyst.</p>";	
		}		
		return $out;
	}
	/**
	* @return html
	* @desc Returns table with time and notes
	*/
	function TimeAndNotesInTable(){
		$endsAnotherDay = ! ($this->GetStartsAndEndsInSameDay() || $this->GetDurationInHours() < 4);
		$showTimes = $this->GetHasTime();
		$out = "
			<table cellpadding = '4'><tr valign='top'>
			";
		// Date  and time start
		$out .= "<td class=normalText ";
		$out .= ($showTimes && ! $endsAnotherDay) ? " rowspan = '2'" : "";
		$out .= $endsAnotherDay ? ">" : " colspan='2'>";
		$out .= norDate(date("Y-m-d",$this->startDateTime));
		if ($showTimes){
			$out .= "<br>
				";
			$out .= date("G:i",$this->startDateTime);
		}
		if ($endsAnotherDay){
			//New cell with end date and time
			$out .= "</td><td>&ndash;</td>
				<td class=normalText ";
			$out .= $showTimes ? " rowspan = '1'>" : ">";
			$out .= norDate(date("Y-m-d",$this->endDateTime));
			if ($showTimes){
				$out .= " <br>
					";
				$out .= date("G:i",$this->endDateTime);
			}
		}else{
			// Only add endtime in same cell		
			if ($showTimes){
				$out .= "&#0150;";
				$out .= date("G:i",$this->endDateTime);
			}
		}
		$out .= "</td>";
		
		//Location
		$out .= "
				<td class=normalText >";
		$out .= $this->locationString;
		$out .= "</td></tr>
				<tr>";
			
		// Notes
		$out .= "<td";
		$out .= $showTimes ? " colspan='3'": " colspan='4'";
		$out .= ">";
		
		$out .= $this->notesString;
		$out .= "</td>";
		
		//Birthdays
		$birthdayPlayers = $this->GetBirthdayList();
		if ($birthdayPlayers){
			$out .= "
				<tr><td colspan='3'>
				";	
			$out .= "Om du m&oslash;ter opp, ikke glem &aring; gratulere $birthdayPlayers med dagen!";
			$out .= "</td></tr>";
		}
		$out .= "
			</tr></table>
			";
		return $out;
	}
}

class ConfirmationRegistration_class extends CurrentEvent_class {
	var $dataEntered, $newReplyType, $newReplyNotes, $newReplyCustomValue;
	function ConfirmationRegistration_class(){
		if($this->_determineConfirm()){
			$this->_determineId();
			$this->_makeQueryMyAnswer();
			$this->_enterReplyInDB();	
		}
	}
	/**
	* @return boolean
	* @desc Checks if confirmation data is entered
	*/
	function _determineConfirm(){
		global $attentionFieldName, $attentionNotesFieldName;	
		if ($_POST[$attentionFieldName]){
			$this->newReplyType=$_POST[$attentionFieldName];
			$this->newReplyNotes=$_POST[$attentionNotesFieldName];
			$this->dataEntered = true;
		}else if ($_GET[$attentionFieldName]){
			$this->newReplyType=$_GET[$attentionFieldName];
			$this->newReplyNotes=$_GET[$attentionNotesFieldName];
			$this->dataEntered = true;
		}else{
			$this->dataEntered = false;
		}
		if ($this->dataEntered){
			$this->newReplyCustomValue = $this->_determineCustomValue();	
		}
		return $this->dataEntered;
	}
	/**
	* @return float
	* @desc Evaluate value and returns
	*/
	function _determineCustomValue(){
		global  $attentionCustomValueFieldName;
		$out = NULL;
		if (isset($_POST[$attentionCustomValueFieldName])){
			$tempVal = $_POST[$attentionCustomValueFieldName];
		}elseif(isset($_GET[$attentionCustomValueFieldName])){
			$tempVal = $_GET[$attentionCustomValueFieldName];
		}
		if ($tempVal){
			//Custom value is defined
			$tempVal = str_replace(",",".",$tempVal);
			if (is_numeric($tempVal)){
				$tempVal /= 100;
				if ($this->_customValueEqualsDefault($tempVal,$this->newReplyType)){
					$out = NULL;
				}else{
					$out = $tempVal;
				}
			}else{
				$out = NULL;	
			}
		}
		return $out;
	}
	function _customValueEqualsDefault($customValue, $replyTypeId){
		$typeValueQueryString = "
			SELECT value FROM attentionType WHERE id = '$replyTypeId'
		";
		$typeValueQuery = new DBConnector_class($typeValueQueryString);
		$typeValue = $typeValueQuery->GetSingleValue('value');
		return ($typeValue == $customValue);
	}
	/**
	* @return boolean
	* @desc Adds or updates confirmation in DB
	*/
	function _enterReplyInDB(){
		$type = $this->newReplyType;
		$notes = $this->newReplyNotes;
		$customValue = $this->newReplyCustomValue;
		if (is_null($customValue)){
			$customValue = "NULL";	
		}
		
		//Get value of remedies
		$remedyObj = new Remedies_class();
		$remedyValue = $remedyObj->GetValueFromFields();
		
		if ($this->GetIsConfirmed()){
			// Update current confirmation
			$confirmId = $this->GetMyConfirmId();
			$queryString = "
				UPDATE attention
				SET	type = $type,
					notes = '$notes',
					customValue = $customValue,
					remedies = $remedyValue,
					date = NOW(),
					registeredBy = NULL
				WHERE id = $confirmId
			";
		}else{
			// Create new confirmation
			$eventId = $this->GetId();
			$player = new Player_class();
			$playerId = $player->getId();
			$queryString = "
				INSERT INTO attention
				SET event = $eventId,
				player = $playerId,
				type = $type,
				notes = '$notes',
				customValue = $customValue,
				remedies = $remedyValue,
				date = NOW(),
				initialDate = NOW()
			";
		}
		$confQuery = new DBConnector_class($queryString);
		$event = new CurrentEvent_class();
		$event->_reinitialize();
	}
	
	/**
	* @return boolean
	* @desc Determines if changes to db has been made
	*/
	function GetDataEntered(){
		return $this->dataEntered;
	}
}

class Confirmations_class {
	var $event;
	/**
	* @return Confirmations_class
	* @desc Contains lists of all replies to current event
	*/
	function Confirmations_class(){
	}
	/**
	* @return html
	* @desc Outputs html-table with all replies
	*/
	function GetTableOfConfirmations(){
		$out = "
			";
		$matchTypeId = 1;
		$tournamentTypeId = 6;
		$event = new CurrentEvent_class();
		$hasConfirmations = $event->GetHasConfirmations();
		$isCancelled = $event->GetIsCancelled();
		$needsCompleteInfo = (($event->GetEventType()==$matchTypeId) || ($event->GetEventType()==$tournamentTypeId));
		if ($hasConfirmations && (! $isCancelled)){
			$out .= $event->SumUpTable();
			$out .= "<h3>P&aring;meldte</h3>";
			$out .= "<table width = '95%'><tr><td>";
			// Get event types
			$types = $event->GetReplyTypes();
			//Stylesheet classes:
			$classes = array(2 => "jegkommer",3=>"jegkommerkanskje");
			foreach($types as $type){
				$replies = $event->GetAllReplies($type['id']);
				if (count($replies)){
					$out .= $this->_FormatReplies($replies,$type,$needsCompleteInfo, $classes[$type['id']]);
				}
			}
			$out .= "</td></tr></table>";
		}elseif (! $isCancelled){
			$out .= "<p>Det er ingen som har svart enn&aring;.</p>";	
		}
		return $out;	
	}
	function _FormatReplies($replies,$type,$needsCompleteInfo, $classString=""){
		$playerSelf = new Player_class();
		$selfId = $playerSelf->getId();
		$remedyObj = new Remedies_class();
		$event = new CurrentEvent_class();
		$hasMaxLim = $event->GetHasMaxLimit();
		$maxLim = $event->GetMaxLimit();
		$noReplyTypeId = 4;
		$safeOnWaitingList = ((! $hasMaxLim) || ( $event->myReplyRankingIndex < $event->GetMaxLimit() ));
		$replyIndex = 0;
		$legendString = $type[longName];
		if (count($replies)>1){
				$legendString = str_replace("Jeg", "Vi", $legendString);
		}
		$out = "
		";
		$out .= "<fieldset";
		$out .= $classString ? " class=$classString" : "";
		$out .= "><legend>$legendString";
		
		//Forbehold
		
		if (($type['id']==2) && ($event->GetIsSufficientConfirmed()==0)){
			$out .= " om det blir minst ".NorwegianTextNumbers($event->GetMinLimit(),false)." p&aring;meldte";
		}
		
		$out .= "</legend>";
		$out .= "<table>";
		if ($type['id'] != $noReplyTypeId){
			foreach ($replies as $reply){
				if ($hasMaxLim && ($replyIndex++ == $maxLim) && $type['value'] ==1){
					$out .= "
						<tr><td colspan='4'>
							<hr style='border: 1px dashed #FF0000'>
							<h5>Venteliste</h5>
						</td></tr>
					";
				}
				
				
				$out .= "
						 <tr>";
				$out .= "<td class=normalText>";
				
				//Jersey number or percentage
				$value = $reply['attentionValue'];
				if ((is_numeric($value)) && ($value < 1) && ($value > 0)){
					$out .= (round($value * 100))."%";
				}elseif (is_numeric($reply['number'])) {
					$out .= "&#8470;&nbsp;$reply[number]";
				}else{
					$out .= '<img src="images/Ball.gif">';
				}
				$out .= "</td>";
				
				//Name and email
				
				//If registration is done previous to current players And player could be on waiting list
				$isBefore = (! $safeOnWaitingList)  
						 && ($reply['RankIndex'] < $event->myReplyRankingIndex);
				
				$out .= "<td class=normalText>";
				$out .= $isBefore ? "<strong>" : "";
				if ($reply['email']){
					$out .= "<a href='mailto:$reply[email]'>
						$reply[firstName] $reply[lastName]</a>";
				}else{
					$out .= "$reply[firstName] $reply[lastName]";
				}
				$out .= $isBefore ? "</strong>" : "";
				$out .= $remedyObj->RenderReplyListIcons($reply['remedies'], $reply['firstName']);
				$out .= PlayerInfoLink($reply['playerId'],$reply['firstName'],$reply['team'],"absmiddle","small");
				$out .= "</td>";
				
				//Birthdate
				if ($needsCompleteInfo){
					$out .= "<td class=normalText>";
					$out .= "(Født: $reply[playerAge])"; 
					$out .= "</td>";	
				}
				
				//Residence
				$out .= "<td class=normalText>$reply[residence]</td>";
				
				//Phone
				if($reply[phone]){
					$out .= "<td class=normalText><a href='tel:$reply[phone]'>$reply[phone]</a></td>";
				}else{
					$out.="<td/>";
				}
				
				//Notes
				$out .= "<td>";
				if ($reply['registeredBy'] > 0 && $reply['registeredBy'] != $reply['playerId']){
					$out .= "<em>Meldt p&aring; av ";
					$out .= DescriptivePlayerName($reply['registeredBy'],$event->GetTeamId());
					$out .= "</em> ";	
				}
				
				$out .= "$reply[notes]</td>";
				
				$out .= "</tr>";
			}
		}else{
			$repliesArray = array();
			foreach ($replies as $reply){
				$playerInfo = "";
				$isBefore = ($hasMaxLim 
						 && ($reply['RankIndex'] < $event->myReplyRankingIndex));
				$playerInfo .= $isBefore ? "<strong>" : "";
				$playerInfo .= "$reply[firstName] $reply[lastName]";
				$playerInfo .= $isBefore ? "</strong>" : "";
				if($reply['remedies'])
				{
					$playerInfo .= "<span class=verySmallText> (";
					$playerInfo .= $remedyObj->RenderReplyListText($reply['remedies'], $reply['firstName']);
					$playerInfo .= ")</span>";
				}
				if ($reply['notes']){
					$playerInfo .= " ($reply[notes])";
				}
				$repliesArray[] = $playerInfo;
			}
			$out .= "<tr><td class=normalText>".SeparatedList($repliesArray)."</td></tr>";	
		}
		$out .= "</table>";
		$out .= "</fieldset>";
		
		return $out;
	}
}

/*
----------------------------
	functions
----------------------------
*/

/**
 * @return html
 * @desc Checks if confirmation is entered and saves this in db. Returns javascript code if changes are made in db 
*/
function RegisterConfirmation(){
	$out = "";
	$reg = new ConfirmationRegistration_class();
	if ($reg->GetDataEntered()){
		$out .= RefreshEventListJavaScript();
	}
	return $out;
}

/**
 * @return html
 * @desc Outputs html for main body of page and form
*/
function EventDetails(){
	global $playerIdFieldName, $eventFieldname;
	$player = new Player_class();
	$playerId = $player->getId();
	$event = new CurrentEvent_class();
	$eventId = $event->GetId(); 
	// Start form tag
	$out =  "
		<form 
			method='post' 
			name='eventDetails_form' 
			class='topOfForm'>
		";
	// Playerid field
	$out .= "
		<input 
			name='$playerIdFieldName' 
			type='hidden' 
			id='playerId_hidden' 
			value='$playerId'>
		";
	// Eventid field
	$out .= "
		<input 
			name='$eventFieldname' 
			type='hidden' 
			id='eventId_hidden' 
			value='$eventId'>
		";
	$out .= DetailsMainHeading();
	if ($playerId > 0){
		$out .= DetailsDateAndVCF();
		$out .= $event->MyConfirmFormFields();
	}
		
	// End form tag
	$out .= "\n</form>";
	
	
	
	$out .= DetailsConfirmationList();
	
	
	//Button to open phonelist
	if(! $event->GetIsCancelled()){
		$out .= buttonToPhoneList($playerId,$eventId,$event->eventTeam);
	}
	
	$out .= DetailsBottomOfPage();

	
	
	return $out;
}

/**
 * @return html
 * @desc Lists all replies to event
*/
function DetailsConfirmationList(){
	$list = new Confirmations_class();
	$out = $list->GetTableOfConfirmations();
	return $out;
}
function DetailsBottomOfPage(){
	$event = new CurrentEvent_class();
	$eventId = $event->GetId();
	$out = DescribeHistory($eventId,true);
	return $out;
}
/**
 * @return html
 * @desc Beneath Heading
*/
function DetailsDateAndVCF(){
	$homeLinkString = HomeIconLink("right");
	$out = $homeLinkString;
	$event = new CurrentEvent_class();
	$eventId = $event->GetId();
	$linkString = $event->GetVcalLink();
	$allreadyAnswered = $event->GetIsConfirmed();
	if ($allreadyAnswered){
  		$lastReplyString = $event->GetReplyDateString();
  		$out .= "
	  		<span class = 'smallCenteredText'>
  			<table width='90%'><tr valign = 'bottom'><td>
	  		$linkString
	  			<img	src='images/VCL-downloadIcon.gif' 
	  					alt='Laste ned en kalenderfil.' 
	  					width='66' 
	  					height='42' 
	  					border='0'></a>
  			</td><td>
	  		$lastReplyString	
  			</td></tr></table> 
	  		</span>  
	  		<hr>
  		";
	}
	return $out;
}

/**
 * @return html
 * @desc Top of detailspage
*/
function DetailsMainHeading(){
	$event = new CurrentEvent_class();
	$remedyObj = new Remedies_class();
	$remedyString = $remedyObj->GetStringDescribingNeed($event->GetEventRemedies());
	
	
	$webPageUrl = $event->GetWebPageUrl();
	$out = "
		";
	$out .= ResetIdentityButton();
	$out .= "<h2>";
	$out .= GetURLGlobeIcon($webPageUrl);
	$out .= $webPageUrl !="" ? "<a href=\"$webPageUrl\" title=\"Informasjon\" target=\"info\">" : "";
	$out .= $event->GetSubject();
	$out .= $event->eventNotInCurrentTeam ? " (".$event->GetNameOfTeam().")" : "";
	$out .= $event->GetIsCancelled() ? ' <font color="#FF0000"> Avlyst!</font>' : "";
	$out .= $webPageUrl != "" ? "</a>" : "";
	$out .= "</h2>
		";
	
	if (! $event->GetIsCancelled()){
		$out .= "<p class=smallCenteredText>".$event->GetInvitationOrDescription()." $remedyString
			";
		if ($event->GetHasMaxLimit()){
			$out .= "<br>";
			$out .= $event->GetMaxLimitWarningString();	
		}
		$out .= "</p>";
		$out .= "<hr>";
	}
	
	$out .= $event->TimeAndNotesInTable();
	$out .= "<hr>";
	return $out;
}
/**
 * @return html
 * @desc Outputs HTML-headers for page including start of body tag.
*/
function HeaderHtml(){
	$event = new CurrentEvent_class();
	if ($event->GetId()){
		$pageTitle = $event->GetSubject();
	}else{
		$pageTitle = "Ingen hendelse er valgt";	
	}
	$startPage = "
	<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<html>
	<head>
	".RenderCSSLink($event->GetTeamId())."
	<title>$pageTitle</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
	<meta http-equiv=\"refresh\" content=\"600\">
	".GetCurrentEventJavaScript ()."
	".HighlightEventJavaScript ()."
	</head>
	<body>
	";
	return $startPage;
}
/**
 * @return html
 * @desc Outputs ending tags of page including </body>
*/
function FooterHtml(){
	$endPage = "
	
	</body>
	</html>";
	return $endPage;
}


/**
 * @return html
 * @desc Outputs javascript function to put in header which returns id of current event.
*/
function GetCurrentEventJavaScript (){
	$event = new CurrentEvent_class();
	$eventId = $event->GetId();
	$script = "
		function GetCurrentEvent(){
			return $eventId;
		}
	";
	return JavaScriptWrapper($script);
}
/**
 * @return html
 * @param boolean $event
 * @desc Outputs javascript, which requests refresh of eventlist
*/
function RefreshEventListJavaScript($event=false){
	global $listFrameName;
	$script = "";
	$script .= "
		if(top.$listFrameName){
			if(top.$listFrameName.RefreshEventPage){
				top.$listFrameName.RefreshEventPage();
			}
		}
	";
	$out = JavaScriptWrapper($script);
	return $out;
}
/**
 * @return html
 * @desc Outputs javascript to put in header which refreshes highlighting of chosen event in eventlist
*/
function HighlightEventJavaScript (){
	global $listFrameName;
	$event = new CurrentEvent_class();
	$eventId = $event->GetId();
	$script = "
		if(top.$listFrameName){
			if(top.$listFrameName.HighlightEvent){
				top.$listFrameName.HighlightEvent($eventId);
			}
		}";
	return JavaScriptWrapper($script);
}

?>