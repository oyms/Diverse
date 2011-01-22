<?PHP
require_once('commonCode.inc');

$applyChangesButtonName = "changeReplies";
$teamChooserFormName="chooseTeam_form";



//---------------------
//  Classes
//---------------------

class RegisterChanges_class {
	var $updates, $newPosts, $changesMade;
	var $eventId;
	/**
	* @return RegisterChanges_class
	* @desc Checks if changes are made to the replies and registers these.
	*/
	function RegisterChanges_class(){
		global $eventFieldname;
		
		// Player registration
		$NewPlayer = new NewPlayerReg_class();
		$this->changesMade = $NewPlayer->RegisterNewPlayer();
		
		$eventId = $_POST[$eventFieldname];
		$this->eventId = $eventId;
		
		$this->changesMade = false;
		if ($this->_checkIfButtonIsSent()){

			if ($eventId == 0){
				$numberOfEvents = 0;	
			}else{
				$numberOfEvents = count(explode(",",$eventId));
			}
			if ($numberOfEvents > 0){
				$events = explode(",",$eventId);
				$changes = $this->_updateListOfChanges($events);
				$this->changesMade = $this->_submitToDB();
			}
		}
		return $changesMade;
	}
	function GetIfChangesAreMadeToDB(){
		return $this->changesMade;	
	}
	function GetSingleEventId(){
		$eventId = $this->eventId;
		$events = explode(",",$eventId);
		return $events[0];	
	}
	function _checkIfButtonIsSent(){
		global $applyChangesButtonName;
		$out = (key_exists($applyChangesButtonName,$_POST));
		return $out;
	}
	function _submitToDB(){
		$player = new Player_class();
		$playerId = $player->getId();
		$changesMade = false;
		//Updates
		if (count($this->updates)){
			foreach ($this->updates as $update){
				$value = $update['value'];
				$replyId = $update['id'];
				$queryUpdate_string = "
					UPDATE attention
					SET type = '$value', date=NOW(), registeredBy='$playerId',
					customValue=NULL
					WHERE id = '$replyId'	
				";
				$queryUpdate = new DBConnector_class($queryUpdate_string);
				$changesMade = true;
			}
		}
		//New posts
		if (count($this->newPosts)){
			foreach ($this->newPosts as $newPost){
				$queryInsert_string = "INSERT INTO attention";
				$queryInsert_string .= "
					SET event = '$newPost[event]',
					player = '$newPost[player]',
					type = '$newPost[value]',
					date = NOW(),
				 	initialDate = NOW(),
					registeredBy = '$playerId'
				";
				$queryInsert = new DBConnector_class($queryInsert_string);
				$changesMade = true;
			}
		}
		return $changesMade;
	}
	
	/**
	* @return void
	* @param int[] $eventId
	* @desc Sets arrays with keys [id] and [value] and [player] and [event]
	*/
	function _updateListOfChanges($events){
		
		$team = new TeamOfList_class();
		$teamId = $team->get_id();
		
		$query_players_string = "
			SELECT players.id 
			FROM players 
				LEFT JOIN membership 
				ON membership.player = players.id
			WHERE membership.team = '$teamId'
		";
		
		$query_players = new DBConnector_class($query_players_string);
		$players = $query_players->GetAllRows();
		
		$eventId = implode(",",$events);
		
		$query_attention_string = "
			SELECT players.id, attention.event, attention.type, attention.id AS attId
			FROM players LEFT JOIN membership ON players.id=membership.player
			LEFT JOIN attention ON players.id = attention.player
			WHERE membership.team='$teamId' AND attention.event IN ($eventId)
		";
		
		$query_attention = new DBConnector_class($query_attention_string);
		
		while ($attention = $query_attention->GetNextRow()){
			$attentions[$attention['id']][$attention['event']] = $attention;
		}
		
		$results = array();
		
		foreach ($players as $player){
			foreach ($events as $event){
				$oldReply = $attentions[$player['id']][$event]['type'];
				$control = new ReplyControls_class($player['id'],$event,$oldReply);
				$newReply = $control->GetNewReply();
				if ($newReply > 0){
					if ($oldReply > 0){
						//Update
						$this->updates[] = array(
							'id'=>$attentions[$player['id']][$event]['attId'], 
							'value' => $newReply);
					}else{
						//Create new
						$this->newPosts[] = array(
							'player'=>$player['id'],
							'event'=>$event,
							'value'=>$newReply);
					}
				}	
			}	
		}
	}
}

class ReplyControls_class {
	var $playerId, $eventId, $currentAnswer;
	var $controlName = "Reply_cmb";
	var $controlSeparator = "-";
	function ReplyControls_class($playerId, $eventId, $currentAnswer = null){
		$this->playerId = $playerId;
		$this->eventId = $eventId;
		$this->currentAnswer = $currentAnswer;	
	}
	/**
	* @return string
	* @desc Name of control
	*/
	function GetControlName(){
		$controlName = 	 $this->controlName
				.$this->playerId
				.$this->controlSeparator
				.$this->eventId;
		return $controlName;
	}
	function GetNewReply(){
		// 0 means no reply
		if (key_exists($this->GetControlName(),$_POST)){
			if ($_POST[$this->GetControlName()] == $this->currentAnswer){
				$out = 0;	
			}else{
				$out = $_POST[$this->GetControlName()];
			}
		}else{
			$out = 0;
		}
		return $out;
	}
	/**
	* @return html
	* @desc Draws a combo for replies
	*/
	function RenderControl($permitBlank = false){
		$replyLit = new AnswerLiterals_class();
		$out = "
			<select 
				name=\"".$this->GetControlName()."\"> 
				";
		if($this->currentAnswer == 0 || $permitBlank){
			$out .= "
				<option value=\"0\"";
			 $out .= ($this->currentAnswer == 0) ? "selected" : "";
			 $out .= "> </option>
			";	
		}
		
		for($replyCounter = 1; $replyCounter < $replyLit->GetNumberOfReplyTypes(); $replyCounter++){
			$replyId =  $replyLit->GetIdFromOrder($replyCounter);
			$replyName = $replyLit->GetShortName($replyId);
			$selectedString = ($this->currentAnswer == $replyId) ? " selected " : "";
			$out .= "
				<option value = \"$replyId\"$selectedString>$replyName</option>
			";	
		}
		return $out;	
	}	
}

class HtmlOutput_class extends Output_class  {
	/**
	* @return HtmlOutput_class
	* @desc Renders the output and provides main structure of page
	*/
	var $output;
	function HtmlOutput_class(){
		$this->output = $this->_generateHtml();
	}
	function _generateHtml(){
		$team = new TeamOfList_class();
		$this->SetPageTitle($team->getName("long").": Spillerliste");
		//Header and teamchooser
		$heading = new TeamChooser_class();
		$this->AddToMain($heading->GetContent());
		$list = new PlayerList_class();
		$this->AddToMain($list->GetContent());
	}
	function AddUpdateScript($eventId=0){
		global $detailsFrameName, $listFrameName;
		
		if ($eventId > 0){
			$script_string .= "
				winRef1 = self.opener.top.frames['$detailsFrameName'];
				if(winRef1){
					winRef1.history.go(0);
				}
				winRef2 = self.opener.top.frames['$listFrameName'];
				if(winRef2){
					winRef2.history.go(0);
				}
				
			";
		$this->AddToHeader($this->JavaScriptWrapper($script_string));
		}
	}
}

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
		if ($_POST[$teamChooserFieldName] > 0){
			$out = $_POST[$teamChooserFieldName];
		}else if ($_GET[$teamChooserFieldName] > 0){
			$out = $_GET[$teamChooserFieldName];
		}else{
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
		$out = "<h1>Spillerliste for ";
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
		$player = new Player_class();
		$playerId = $player->getId();
		if ($player->multipleTeams){
			$teams = $player->getTeams();
			$teamsInfo = $player->GetTeamsData();
			$out = "
				\n<form name=\"$teamChooserFormName\" 
				method=\"post\" 
				action=\"\">
				<input type=\"hidden\" name=\"$playerIdFieldName\" value=\"$playerId\">
  				<select name=\"$teamChooserFieldName\" 
				onChange=\"document.forms['$teamChooserFormName'].submit();\">
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
  				<input type=\"submit\" 
  				name=\"Chosen\" value=\"&laquo;\">
				</form>
  				";
		}else{
			$out = $currentTeam->getName("long");
		}
		return $out;
	}	
}
class PlayerList_class {
	var $content;
	var $event;
	function PlayerList_class(){
		$this->event = $this->_GetEventId();
		
		$this->content = $this->_RenderList($this->event);
	}
	/**
	* @return html
	* @desc Returns complete list
	*/
	function GetContent(){
		return $this->content;	
	}
	function _SubmitButton(){
		global $applyChangesButtonName;
		$out = "
			<input type=\"reset\" value=\"Tilbakestill alle felt\">
			<input type=\"submit\" 
  				name=\"$applyChangesButtonName\" 
				value=\"Lagre endringer\">
		";
		return $out;	
	}
	/**
	* @return html
	* @param int $teamId
	* @param list of int $eventId
	* @desc Renders the list with players and replies
	*/
	function _RenderList($eventId){
		
		global $eventFieldname, $teamChooserFieldName, $playerIdFieldName;
		
		$team = new TeamOfList_class();
		$teamId = $team->get_id();
		$answers = new AnswerLiterals_class();
		$playerLoggedOn = new Player_class();
		$playerId = $playerLoggedOn->getId();
		
		
		if ($eventId == 0){
			$numberOfEvents = 0;	
		}else{
			$numberOfEvents = count(explode(",",$eventId));
		}
		
		
		//Make query
		$query_players_string = "
			SELECT
				players.id, players.firstName, players.lastName, players.residence,
				players.phone, players.email, players.age, players.dubviousAge,
				players.url, membership.number, membership.notes AS membershipNotes,
				membership.teamTrainer
			FROM players LEFT JOIN membership ON players.id=membership.player
			WHERE membership.team='$teamId' AND players.id != '$playerId'
			ORDER BY players.lastName, players.firstName
		";
		
		$query_attention_string = "
			SELECT players.id, attention.event, attention.type, attention.notes
			FROM players LEFT JOIN membership ON players.id=membership.player
			LEFT JOIN attention ON players.id = attention.player
			WHERE membership.team='$teamId' AND attention.event IN ($eventId)
		";

		
		$query_players = new DBConnector_class($query_players_string);
		$players = $query_players->GetAllRows();
		
		if ($numberOfEvents > 0){
			$attentions = array();
			$query_attention = new DBConnector_class($query_attention_string);
			while ($attention = $query_attention->GetNextRow()){
				$attentions[$attention['id']][$attention['event']] = $attention;	
			}
					
			$query_events_string = "
				SELECT events.id AS eventId, events.dateStart, eventTypes.type AS eventType
				FROM events LEFT JOIN eventTypes ON events.type = eventTypes.id 
				WHERE events.id IN ($eventId)
				ORDER BY events.dateStart
			";
			
			$query_events = new DBConnector_class($query_events_string);
			while ($eventRow = $query_events->GetNextRow()){
				$events[$eventRow['eventId']] = $eventRow;	
			}
		}
		
		//Start of form
		
		$out = "\n<form method=\"POST\">";
		
		$out .= "\n<input type=\"hidden\" name=\"$eventFieldname\" value=\"$eventId\">";
		$out .= "\n<input type=\"hidden\" name=\"$teamChooserFieldName\" value=\"$teamId\">";
		$out .= "\n<input type=\"hidden\" name=\"$playerIdFieldName\" value=\"$playerId\">";
		
		
		//Draw table
		$out .= "\n<table>";
		
		$numberOfTextCols = 6;
		$numberOfCols = $numberOfTextCols + $numberOfEvents;
		
		if ($numberOfEvents > 0){
			$out .= "\n<tr><td colspan=$numberOfCols align='right'>";
			$out .= "\n".$this->_SubmitButton();
			$out .= "</td></tr>";	
		}
		
		//Headerrow
		$out .= "
			<tr>
				<td></td><th>Navn</th>
				<th>Telefon</th><th>E-post</th>
				<th>Adresse</th><th>Motto</th>";
		
		if ($numberOfEvents > 0){
			foreach ($events as $event){
				$out .= "<th class=\"smallCenteredText\">";
				$out .= $event['eventType']."<br>".strftime("%e/%m %R",strtotime($event['dateStart']));
				$out .= "</th>";	
			}	
		}
		
		$out .= "
			</tr>";
		
		$rowIndex = 0;
		
		foreach ($players as $player){
			
			$styleString = ($rowIndex++ % 2 == 0) ? " class=\"lightColoredBackground\"" : "";
			
			$out .= "\n<tr$styleString>";	
			
			//Number
			$out .= "<td>$player[number]</td>";
			
			//Name
			$hasUrl = ($player['url'] != "");
			$out .= "<td>";
			$out .= $hasUrl ? "<a href=\"http://$player[url]\">" : "";
			$out .= $player['firstName']." ".$player['lastName'];
			$out .= $hasUrl ? "</a>" : "";
			$out .= "</td>";
			
			//Phone
			$out .= "<td>$player[phone]</td>";
			
			//E-mail
			$hasMail = ($player['email'] != "");
			$out .= "<td>";
			$out .= $hasMail ? "<a href=\"mailto:$player[email]\">" : "";
			$out .= $player['email'];
			$out .= $hasMail ? "</a>" : "";
			$out .= "</td>";
			
			//Address
			$out .= "<td>$player[residence]</td>";
			
			//Notes
			$out .= "<td>$player[membershipNotes]</td>";
			
			//Replies
			if ($numberOfEvents > 0){
				
			foreach ($events as $event){
				
				$Reply_cmb = new ReplyControls_class($player['id'],$event['eventId'],$attentions[$player['id']][$event['eventId']]['type']);
				
				$out .= "<td align=\"center\">";
				$out .= $Reply_cmb->RenderControl();
				$out .= "</td>";	
			}	
		}
			
			
			$out .= "\n</tr>";
		}
		if ($numberOfEvents > 0){
			$out .= "\n<tr><td colspan='$numberOfCols' align='right'>";
			$out .= "\n".$this->_SubmitButton();
			$out .= "</td></tr>";	

			$out .= "\n";
			$newPlayer = new NewPlayerReg_class();
			$out .= $newPlayer->RenderFields($eventId, $numberOfTextCols);
		
			$out .= "\n<tr><td colspan='$numberOfCols' align='right'>";
			$out .= "\n".$this->_SubmitButton();
			$out .= "</td></tr>";	
		}
		
		$out .= "\n</table>";
		
		
		
		$out .= "\n</form>";
		
		return $out;
	}
	/**
	* @return int
	* @desc Returns the id of the chosen or next event. Returns 0 if no valid event is chosen
	*/
	function _GetEventId(){
		global $eventFieldname;
		$team = new TeamOfList_class();
		$out = 0;
		if ($_POST[$eventFieldname]>0){
			$out = $_POST[$eventFieldname];
		}elseif ($_GET[$eventFieldname]>0){
			$out = $_GET[$eventFieldname];
		}
		if ($out > 0){
			//check if event is in current team
			$out = $team->getEventIsWithinTeam($out) ? $out : 0;
		}
		if ($out==0){
			$numberOfEvents = 3;
			$eventIdArray = array();
			
			for($eventIndex = 0; $eventIndex < $numberOfEvents; $eventIndex++){		
				$nextEvent = $team->getNextEvent($eventIndex);
				if ($nextEvent>0){
					$eventIdArray[]=$team->getNextEvent($eventIndex);
				}
			}
			$out = (count ($eventIdArray) >0 ) ? implode(",",$eventIdArray) : 0;
		}
		return $out;
	}
}

class NewPlayerReg_class {
	var $firstName_fieldname, $lastName_fieldname;
	var $email_fieldname, $phone_fieldname, $notes_fieldname;
	var $firstName_label, $lastName_label;
	var $email_label, $phone_label, $notes_label;
	var $error_msg, $replyValues, $fieldValues;
	
	function NewPlayerReg_class(){
		
		$this->error_msg = & $GLOBALS['_transient']['static']['newplayerreg']->error_msg;
		$this->fieldValues = & $GLOBALS['_transient']['static']['newplayerreg']->fieldValues;
		$this->replyValues = & $GLOBALS['_transient']['static']['newplayerreg']->replyValues;
		$this->firstName_fieldname = "firstName_txt";
		$this->lastName_fieldname = "lastName_txt";
		$this->email_fieldname = "email_txt";
		$this->phone_fieldname = "phone_txt";
		$this->notes_fieldname = "notes_txt";
		
		$this->firstName_label = "fornavn";
		$this->lastName_label = "etternavn";
		$this->email_label = "e-postadresse";
		$this->phone_label = "telefonnummer";
		$this->notes_label = "merknad";
		
		
		
	}
	function GetErrorMsg(){
		return $this->error_msg;	
	}
	function SetErrorMsg($msg, $append=true){
		$this->error_msg = $append ? $this->error_msg." ".$msg : $msg;
	}
	function _ResetFields(){
		$this->fieldValues = array();
		$this->replyValues = array();
		$this->SetErrorMsg("<h3>Ny spiller</h3>Her kan du legge inn en ny spiller i basen. Om du gj&oslash;r det, m&aring; du som et minimum fylle inn b&aring;de fornavn og etternavn.",false);
	}
	/**
	* @return html
	* @param int $eventId
	* @desc Returns form controls for registering new player
	*/
	function RenderFields($eventId, $numberOfTextCols){
		
		if ($eventId ==0){
			$numberOfEvents =0;	
		}else{
			$numberOfEvents = count(explode(",",$eventId));	
		}
		
		$numberOfCols = $numberOfTextCols + $numberOfEvents;
		
		$out .= "\n";
		$out .= "<tr><td colspan = \"$numberOfCols\">".$this->GetErrorMsg()."</td></tr>";
		$out .= "<tr><td valign=\"bottom\" colspan = \"$numberOfTextCols\">\n";
		$out .= $this->_TextField(
				$this->firstName_label,
				$this->firstName_fieldname,
				$this->fieldValues[$this->firstName_fieldname],
				50
			);
		$out .= $this->_TextField(
				$this->lastName_label,
				$this->lastName_fieldname,
				$this->fieldValues[$this->lastName_fieldname],
				50
			);
		$out .= $this->_TextField(
				$this->email_label,
				$this->email_fieldname,
				$this->fieldValues[$this->email_fieldname],
				50
			);
		$out .= $this->_TextField(
				$this->phone_label,
				$this->phone_fieldname,
				$this->fieldValues[$this->phone_fieldname],
				100
			);
		$out .= $this->_TextField(
				$this->notes_label,
				$this->notes_fieldname,
				$this->fieldValues[$this->notes_fieldname],
				255
			);
		$out .= "</td>\n";
		
		// Reply-controls
		if ($eventId > 0){
			$events = explode(",",$eventId);
			foreach ($events as $event){
				$reply_cmb = new ReplyControls_class(0,$event,$this->replyValues[$event]);
				$out .= "<td valign=\"bottom\">";
				$out .= $reply_cmb->RenderControl(true);
				$out .= "</td>";	
			}
		}
		
		
		$out .= "</tr>";
		return $out;
	}
	function _CheckIfValidInput(){
		if ($this->_GetFieldValues()){
			//Validate fields
			$valid = true;
			$this->SetErrorMsg("<h3 class='importantMessage'>Feil i skjemaet</h3>",false);
			//First name
			if (! $this->_ValidateField($this->firstName_fieldname,false,false)){
				$this->SetErrorMsg("Du m&aring; fylle ut fornavn!");
				$valid = false;	
			}
			//Last name
			if (! $this->_ValidateField($this->lastName_fieldname,false,false)){
				$this->SetErrorMsg("Du m&aring; fylle ut etternavn!");
				$valid = false;	
			}
			//Email
			if (! $this->_ValidateField($this->email_fieldname,true,true)){
				$this->SetErrorMsg("E-postadressen er ugyldig.");
				$valid = false;	
			}
			
		}else{
			//No values entered
			$this->_ResetFields();
			$valid = false;	
		}
		return $valid;
	}
	function _ValidateField($fieldName,$email=false,$optional=true){
		$value = $this->fieldValues[$fieldName];
		$out = true;
		if (! $optional && $value == ""){
			$out = false;
		}
		if ($out && $email && $value != ""){
			$out = validate_mail($value);	
		}
		return $out;
	}
	function _GetFieldValues(){
		global $applyChangesButtonName;
		if (key_exists($applyChangesButtonName,$_POST)){
			$fields = array($this->firstName_fieldname,
							$this->lastName_fieldname,
							$this->email_fieldname,
							$this->phone_fieldname,
							$this->notes_fieldname);
			$labels = array($this->firstName_label,
							$this->lastName_label,
							$this->email_label,
							$this->phone_label,
							$this->notes_label);
			$enteredValues = false;
			for ($fieldIndex = 0; $fieldIndex < count($fields) ; $fieldIndex++){
				$thisValue = $_POST[$fields[$fieldIndex]];
				if ($thisValue == $labels[$fieldIndex]){
					$thisValue = "";	
				}
				$this->fieldValues[$fields[$fieldIndex]] = $thisValue;
				if ($thisValue != ""){
					$enteredValues = true;	
				}
			}
		}else{
			$enteredValues = false;	
		}
		if ($enteredValues){
			$this->_SaveReplies();
		}
		
		return $enteredValues;
	}
	function _SaveReplies(){
		global $eventFieldname;
		$eventId = $_POST[$eventFieldname];
		if ($eventId > 0){
			$events = explode(",",$eventId);
			foreach ($events as $event){
				$ctrl = new ReplyControls_class(0,$event);
				$this->replyValues[$event] = $_POST[$ctrl->GetControlName()];	
			}	
		}
	}
	function RegisterNewPlayer(){
		if ($this->_CheckIfValidInput()){
			$this->_submitToDB();
		}
	}
	function _submitToDB(){
		global $eventFieldname, $teamChooserFieldName;
		$teamId = $_POST[$teamChooserFieldName];
		$eventId = $_POST[$eventFieldname];
		$events = explode(",",$eventId);
		if ($eventId == 0){
			$numberOfEvents = 0;	
		}else{
			$numberOfEvents = count($events);	
		}
		
		// Submit player
		$query_player_string = "
			INSERT INTO players
			SET
				firstName = '".$this->fieldValues[$this->firstName_fieldname]."',
				lastName = '".$this->fieldValues[$this->lastName_fieldname]."',
				phone = '".$this->fieldValues[$this->phone_fieldname]."',
				email = '".$this->fieldValues[$this->email_fieldname]."',
				dubviousAge = '1'			
		";
		
		$query_player = new DBConnector_class($query_player_string);
		$newPlayerId = $query_player->GetLastAutoIncrement();
		//Submit membership
		$query_membership_string = "
			INSERT INTO membership
			SET
				player = '$newPlayerId',
				team = '$teamId',
				notes = '".$this->fieldValues[$this->notes_fieldname]."',
				reminders = '0',
				noMessageByEmail = '1'
		";
		$query_membership = new DBConnector_class($query_membership_string);
		
		//Submit replies
		if ($numberOfEvents > 0){
			$loggedOnPlayer = new Player_class();
			$loggedOnPlayerId = $loggedOnPlayer->getId();
			
			foreach ($events as $event){
				$reply = $this->replyValues[$event];
				if ($reply > 0){
					$query_reply_string = "
						INSERT INTO attention
						SET
							event = '$event',
							player = '$newPlayerId',
							type = '$reply',
							date = NOW(),
							initialDate = NOW(),
							registeredBy = '$loggedOnPlayer'
					";
					$query_reply = new DBConnector_class($query_reply_string);
				}
			}	
		}
		
		//Reset fields
		$this->_ResetFields();
	}
	function _TextField($label, $name, $value = "", $maxLength = 0, $size = 20){
		global $teamChooserFormName;
		
		$colorLabel = "#9C9A9C";
		$colorValue = "#000000";

		$maxString = ($maxLength>0) ? " maxlength=\"$maxLength\" ": "";

		$valueString = ($value=="") ? $label : $value;	
		
		$colorString = ($valueString == $label) ? $colorLabel: $colorValue;
				
		$out = "
			<input name=\"$name\"
				id=\"$name\" 
				type=\"text\" 
				size=\"$size\" 
				$maxString 
				value=\"$valueString\"
				alt=\"$label\"
				style=\"{color: $colorString}\"
				
		";
		
		$out .= "onFocus=\"if(this.value=='$label'){this.value='';this.style.color='$colorValue';}\"\n\t\t";
		$out .= "onBlur=\"if(this.value==''){this.value='$label';this.style.color='$colorLabel';}\"\n\t\t";

		$out .= ">";

		return $out;
	}	
}


//-------------------------
// Main
//-------------------------

$submit = new RegisterChanges_class();
$listOfPlayers = new HtmlOutput_class();
if($submit->GetIfChangesAreMadeToDB()){
	$listOfPlayers->AddUpdateScript($submit->GetSingleEventId());	
}
echo $listOfPlayers->Output();


?>