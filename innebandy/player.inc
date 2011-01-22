<?php
class Player_class {
	var $firstName,$lastName,$id,$teams,$teamsData,$currentTeam;
	var $residence,$phone,$email,$htmlMail,$age,$url;
	var $lastLogin,$userName,$password,$deleted;
	var $multipleTeams,$emailAddress,$teamTrainer;
	var $number,$notes,$reminders,$noMessageByEmail;
	function Player_class(){
		$this->firstName = & $GLOBALS['_transient']['static']['player_class']->firstName;
		$this->lastName = & $GLOBALS['_transient']['static']['player_class']->lastName;
		$this->id = & $GLOBALS['_transient']['static']['player_class']->id;
		$this->teams = & $GLOBALS['_transient']['static']['player_class']->teams;
		$this->currentTeam = & $GLOBALS['_transient']['static']['player_class']->currentTeam;
		$this->multipleTeams = & $GLOBALS['_transient']['static']['player_class']->multipleTeams;
		$this->teamsData = & $GLOBALS['_transient']['static']['player_class']->teamsData;
		$this->emailAddress = & $GLOBALS['_transient']['static']['player_class']->emailAddress;
		$this->teamTrainer = & $GLOBALS['_transient']['static']['player_class']->teamTrainer;
		$this->residence = & $GLOBALS['_transient']['static']['player_class']->residence;
		$this->phone = & $GLOBALS['_transient']['static']['player_class']->phone;
		$this->email = & $GLOBALS['_transient']['static']['player_class']->email;
		$this->htmlMail = & $GLOBALS['_transient']['static']['player_class']->htmlMail;
		$this->age = & $GLOBALS['_transient']['static']['player_class']->age;
		$this->url = & $GLOBALS['_transient']['static']['player_class']->url;
		$this->lastLogin = & $GLOBALS['_transient']['static']['player_class']->lastLogin;
		$this->userName = & $GLOBALS['_transient']['static']['player_class']->userName;
		$this->password = & $GLOBALS['_transient']['static']['player_class']->password;
		$this->deleted = & $GLOBALS['_transient']['static']['player_class']->deleted;
		$this->number = & $GLOBALS['_transient']['static']['player_class']->number;
		$this->teamTrainer = & $GLOBALS['_transient']['static']['player_class']->teamTrainer;
		$this->notes = & $GLOBALS['_transient']['static']['player_class']->notes;
		$this->reminders = & $GLOBALS['_transient']['static']['player_class']->reminders;
		$this->noMessageByEmail = & $GLOBALS['_transient']['static']['player_class']->noMessageByEmail;
		
		
		if (! $this->id){
			$this->_retrieveId();
			if($this->getId()){
				$this->_makeQuery();
			}
		}
	}
	function _retrieveId(){
		global $playerIdFieldName;
		if($this->id = $_GET[$playerIdFieldName] ){
		}else if($this->id = $_POST[$playerIdFieldName]){
		}else if($this->id = $_COOKIE[$playerIdFieldName]){
		}else{
			$this->id = false;
		}
		//Id is verified in _makeQuery
	}
	
	function getId(){
		return $this->id;
	}
	function getNumber($type="raw"){
		switch ($type){
			default:
				$out = $this->number;	
		}
		return $out;
	}
	function getName($type="full"){
		$out="";
		switch ($type){
			case "first":
				$out = $this->firstName;
			break;
			case "last":
				$out = $this->lastName;
			break;
			default:
				$out = $this->firstName." ".$this->lastName;
		}
		return $out;
	}
	
	function getDate($type="friendly", $dateField="lastLogin"){
		$out = "";
		$dateVal="";
		switch ($dateField){
			case "birthDate":
				$dateVal=$this->age;
			break;
			case "lastLogin":
				$dateVal=$this->lastLogin;
			break;
		}
		switch ($type){
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
	
	/**
	* @return int[]
	* @desc Returns array of team-id
	*/
	function getTeams(){
		return $this->teams;
	}
	function getCurrentTeam(){
		return $this->currentTeam;
	}
	/**
	* @return boolean
	* @param int $teamId
	* @desc Returns true if player is member of team with given id
	*/
	function getIsMemberInTeam($teamId){
		$myTeams = $this->getTeams();
		$out = false;
		
		foreach ($myTeams as $team){
			if($team == $teamId){
				$out=true;
			}
		}
		return $out;
	}
	function getHasMultipleMemberships(){
		return $this->multipleTeams;	
	}
	function getIsTrainer(){
		return $this->teamTrainer;	
	}
	
	/**
	* @return string[]
	* @desc Returns array from query of all teams
	*/
	function GetTeamsData(){
		if (count($this->teamsData)<1){
			$this->_getTeamsData();	
		}	
		return $this->teamsData;
	}
	
	function _getTeamsData(){
		$myTeamsString = implode(",",$this->teams);
		$query_teams = "
			SELECT * FROM teams WHERE id IN ($myTeamsString)
		";
		$teams = new DBConnector_class($query_teams);
		while ($row_teams = $teams->GetNextRow()){
			$this->teamsData[$row_teams['id']] = $row_teams;
		} 	
	}

	
	function _makeQuery(){
		global $playerIdFieldName;
		if($this->id){
			/* Get current team log-on */
			$currentTeam = new Team_class();
			$teamId = $currentTeam->get_id();
			$this->currentTeam = $teamId;
			/* Get memberships */
			$query_Memberships = "
				SELECT DISTINCT team 
				FROM membership
				LEFT JOIN teams ON membership.team = teams.id 
				WHERE player = $this->id
				ORDER BY teams.longName
				";
			$Memberships = new DBConnector_class($query_Memberships);
			
			$memberOfCurrent = false;
			while ($row_Memberships = $Memberships->GetNextRow()){
				$this->teams[]=$row_Memberships['team'];
				if ($teamId == $row_Memberships['team']){
					$memberOfCurrent = true;	
				}
			}
			
			$this->multipleTeams = ($Memberships->GetNumberOfRows()>1) ? true : false;
			
			if (! $memberOfCurrent){
				/* Not a member of current team */
				$this->id = 0;
				setcookie($playerIdFieldName,0,time()+(60*60*24*30));
			}else{
				setcookie($playerIdFieldName,$this->id,time()+(60*60*24*30));
				/* Get player and membership info */
				$query_PlayerInfo = "
					SELECT players.*, membership.*
					FROM players LEFT JOIN membership 
								 ON players.id = membership.player
					WHERE (((players.id)=$this->id) AND ((membership.team)=$teamId))";
				$PlayerInfo = new DBConnector_class($query_PlayerInfo);
				$row_PlayerInfo = $PlayerInfo->GetNextRow();
				
				$this->firstName = $row_PlayerInfo['firstName'];
				$this->lastName = $row_PlayerInfo['lastName'];
				$this->residence = $row_PlayerInfo['residence'];
				$this->phone = $row_PlayerInfo['phone'];
				$this->email = $row_PlayerInfo['email'];
				$this->htmlMail = $row_PlayerInfo['htmlMail'];
				$this->age = $row_PlayerInfo['age'];
				$this->url = $row_PlayerInfo['url'];
				$this->lastLogin = $row_PlayerInfo['lastLogin'];
				$this->userName = $row_PlayerInfo['userName'];
				$this->password = $row_PlayerInfo['password'];
				$this->deleted = $row_PlayerInfo['deleted'];
				$this->number = $row_PlayerInfo['number'];
				$this->teamTrainer = $row_PlayerInfo['teamTrainer'];
				$this->notes = $row_PlayerInfo['notes'];
				$this->reminders = $row_PlayerInfo['reminders'];
				$this->noMessageByEmail= $row_PlayerInfo['noMessageByEmail'];
			}
		}
	}
}
?>