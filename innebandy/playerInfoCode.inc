<?php

/**
 * Common code to playerInfo.php and playerinfoGenerator.php
 *
 * @author oyvind.skaar
 * @package defaultPackage
 */
require_once('commonCode.inc');

/**
 * Provides a link to open the list of players in CSV format
 *
 */
class CSVFileLink_class{
	function RenderLink(){
		global $playerIdFieldName,$teamChooserFieldName;
		$teamId=GetPostGetData($teamChooserFieldName);
		$player=new Player_class();
		$formData=new FormData_class();
		$url="playerinfoGenerator.php?$playerIdFieldName={$player->getId()}&$teamChooserFieldName=$teamId&{$formData->dateFieldName}={$formData->GetCurrentDate()}";
		$out=sprintf("<a href=\"%s\" title=\"%s\">%s</a>",
			$url,
			"Spillerliste som kan åpnes i Excel",
			"Åpne listen i Excel");
		return $out;
	}
}


/**
 * Queries and presents a list of players for the html page.
 * uses TeamOfList_class to get current team info
 *
 */
class ListOfPlayers_class extends ListOfPlayersBase_class{
	function ListOfPlayers_class(){
		$team = new TeamOfList_class();		
		$this->teamId = $team->get_id();
		$dataObj = new FormData_class();
		$this->_MakeQuery($dataObj);
		$this->_MakeTable();
	}
	function _MakeTable(){
		$out = "\n";
		$out .= "<table>";
		if (count($this->members)){
			$out .= $this->members[0]->GetHeaderRow();
		}
		foreach ($this->members as $member){
			$out .= "\n\t";
			$out .= $member->GetTableRow();
		}
		$out .= "</table>\n";
		$this->content = $out;
	}
}
/**
 * Provides a list rendered as CSV
 *
 */
class CSVListOfPlayers_class extends ListOfPlayersBase_class {
	/**
	 * Constructor for the csv list class
	 *
	 * @param FormDataCSV_class $formData
	 * @return CSVListOfPlayers_class
	 */
	function CSVListOfPlayers_class($formData){
		$this->teamId=$formData->GetTeamId();
		$this->_MakeQuery($formData);
		$this->content=$this->_MakeTable($formData);
	}
	/**
	 * Creates table of team members
	 *
	 * @param FormDataCSV_class $formData
	 * @return Array
	 */
	function _MakeTable($formData){
		$out="";
		$friendlyDate=norDate($formData->GetCurrentDate(),true,false);
		$columnNames=array("Fornavn","Etternavn","Draktnummer","Fødselsnummer","Telefon","E-post","Bosted","Relativ aktivitet siden $friendlyDate");
		$columnSeparator=";";
		$lineSeparator="\n";
		$out.=$this->_createTerminatedLine($columnNames,$columnSeparator,$lineSeparator);
		foreach ($this->GetTeamMembers() as $member){
			$memberData=array(
				$member->firstName,
				$member->lastName,
				$member->number,
				$member->age,
				$member->phone,
				$member->email,
				$member->residence,
				sprintf("%0d",$member->GetRelativeActivity()*100)."%"
			);
			$out.=$this->_createTerminatedLine($memberData,$columnSeparator,$lineSeparator);
		}
		return $out;
	}
	/**
	 * Converts an array of strings to a concatenated string
	 *
	 * @param string() $columnArray
	 * @param string $columnSeparator
	 * @param string $lineSeparator
	 * @return string
	 */
	function _createTerminatedLine($columnArray,$columnSeparator,$lineSeparator){
		$out="";
		for($n=0;$n<count($columnArray);$n++){
			if((!strpos($columnArray[$n],$columnSeparator)===false)||(!strpos($columnArray[$n],$lineSeparator)===false)){
				$columnArray[$n]="\"{$columnArray[$n]}\"";
			}
		}
		$out=implode($columnSeparator,$columnArray);
		$out.=$lineSeparator;
		return $out;
	}
}
/**
 * Base class for players lists
 *
 */
class ListOfPlayersBase_class{
	var $teamId, $members, $content;
	/**
	 * Constructor
	 *
	 * @param FormDataBase_class $dataObj
	 */
	function _MakeQuery($dataObj){
		$teamId = $this->teamId;
		$dateOfStatistics= $dataObj->GetCurrentDate();
		$dateStringForQuery = date("Y-m-d",$dateOfStatistics);
		$this->members = array();
		$activePlayers = array();
		
		$commonSelectString = "
			players.id, firstName, lastName, residence, age, dubviousAge,
			phone, email, players.url, players.picture,
			membership.number, membership.notes,
			membership.teamTrainer,
			(membership.number *1) AS realNumber, 
			(membership.number IS NOT NULL 
				&& membership.number != ''
				&& REPLACE(membership.number,',','.') > 0) AS hasNumber,
			(membership.notes != '') AS hasMotto";
		$commonFromString = "membership
				LEFT JOIN players ON players.id = membership.player";
		$commonWhereString = "WHERE (((membership.team)='$teamId'))";
		//$commonOrderString = "ORDER BY lastName,firstName,age DESC";
				
		$commonOrderString = $dataObj->GetSortString();

		$queryAllPlayersString = "
			SELECT $commonSelectString,
				0 AS activity
			FROM $commonFromString
			$commonWhereString
			$commonOrderString
		";
		$queryAllPlayers = new DBConnector_class($queryAllPlayersString);
		$queryActivePlayersString = "
			SELECT $commonSelectString,
				SUM(attentionType.value +0.1) AS activity
			FROM $commonFromString
				LEFT JOIN attention ON attention.player = players.id
				LEFT JOIN events ON attention.event = events.id
				LEFT JOIN attentionType ON attentionType.id = attention.type
			$commonWhereString
				AND (events.team = '$teamId')
				AND ((events.dateStart < NOW()) AND (events.dateStart > '$dateStringForQuery'))
			GROUP BY membership.id 
			$commonOrderString
		";
		$queryActivePlayers = new DBConnector_class($queryActivePlayersString);
		
		
		if ($dataObj->GetCurrentSortOrder() == "activity"){
			$activePlayerList = array();
			while ($queryRow = $queryActivePlayers->GetNextRow()){
				$this->members[] = new TeamMember_class($queryRow);
				$activePlayerList[] = $queryRow['id'];
			}
			while ($queryRow = $queryAllPlayers->GetNextRow()){
				if (! in_array($queryRow['id'],$activePlayerList)){
					$this->members[] = new TeamMember_class($queryRow);
				}	
			}
		}else{
			while ($queryRow = $queryActivePlayers->GetNextRow()){
				$activePlayers[$queryRow['id']]=$queryRow;
			}
			while ($queryRow = $queryAllPlayers->GetNextRow()){
				if(array_key_exists($queryRow['id'],$activePlayers)){
					$this->members[] = new TeamMember_class($activePlayers[$queryRow['id']]);
				}else{
					$this->members[] = new TeamMember_class($queryRow);	
				}
			}
		}
	}
	/**
	 * Returns the content to be rendered
	 *
	 * @return string
	 */
	function GetContent(){
		return $this->content;
	}
	/**
	 * Returns an array of player objects.
	 *
	 * @return TeamMember_class
	 */
	function GetTeamMembers(){
		return $this->members;
	}
}
class TeamMember_class{
	var $id,$firstName,$lastName,$activity,$email;
	var $phone,$url,$number,$notes, $maxActivity, $trainer, $hasPicture;
	var $residence, $age, $dubviousAge;
	function TeamMember_class($playerInfo){
		$this->maxActivity = & $GLOBALS['_transient']['static']['teammember_class']->maxActivity;
		$this->_RegisterData($playerInfo);	
	}
	function _RegisterData($playerInfo){
		$this->id = $playerInfo['id'];
		$this->email = $playerInfo['email'];
		$this->firstName = $playerInfo['firstName'];
		$this->lastName = $playerInfo['lastName'];
		$this->phone = $playerInfo['phone'];
		$this->url = $playerInfo['url'];
		$this->number = $playerInfo['number'];
		$this->notes = $playerInfo['notes'];
		$this->activity = $playerInfo['activity'];
		$this->trainer = $playerInfo['teamTrainer'] == 1;
		$this->hasPicture = $playerInfo['picture'] != "";
		$this->residence=$playerInfo['residence'];
		$this->age=$playerInfo['age'];
		$this->dubviousAge=$playerInfo['dubviousAge'];
		
		if ($this->activity > $this->maxActivity){
			$this->maxActivity = $this->activity;	
		}
	}
	function GetHeaderRow(){
		$dataObj = new FormData_class();
		$out = "<tr>";
		$out .= "<td></td>";
		$out .= "<th>".$dataObj->ColumnLabel("number")."</th>";
		$out .= "<th>".$dataObj->ColumnLabel("name")."</th>";
		$out .= "<th>".$dataObj->ColumnLabel("phone")."</th>";
		$out .= "<th>".$dataObj->ColumnLabel("mail")."</th>";
		$out .= "<th>".$dataObj->ColumnLabel("motto")."</th>";
		$out .= "<th></th>";
		if ($this->maxActivity > 0){
			$out .= "<th>".$dataObj->ColumnLabel("activity")."</th>";
		}
		$out .= "</tr>";
		return $out;	
	}
	function GetTableRow(){
		$out = "<tr>";
		
		//Selector control
		$checkedString = $this->GetIsSelected() ? " checked " : "";
		global $playerInfoFileName, $playerInfoFieldName;
		global $playerIdFieldName, $teamChooserFieldName;
		$formObj = new FormData_class();
		$player = new Player_class();
		$playerId = $player->getId();
		$team = new TeamOfList_class();
		$teamId = $team->get_id();
		$linkHref = $formObj->SubjectPlayerLink($this->id);
		$out .= "
			<td>
				<input 
					name=\"selectorPlayer\" 
					type=\"radio\" 
					value=\"{$this->id}\"
					$checkedString 
					onClick=\"{$linkHref}\"
				>
			</td>";
		
		//Shirt and number
		$numberTitle = "P&aring; drakta vi bl&oslash;r for st&aring;r det $this->number";
		if ($this->number == "") {
			$numberTitle .= "ingenting.";	
		}
		if ($this->number && is_numeric(str_replace(",",".",$this->number))){
			$out .= "
				<td 
					class='playerNumber'
					width='32' 
					height='32'
					title='$numberTitle' 
				>{$this->number}</td>";
		}else{
			
			$out .= "<td width='32' height='32' title='$numberTitle'>&nbsp;</td>";
		}
		
		//Name
		$out .= "<td>{$this->firstName} {$this->lastName}";
		if ($this->hasPicture){
			$out .= "
				<img
					src='images/DummyPortrait.gif'
					width = '21'
					height = '24'
					align = 'absmiddle'
					alt = '$this->firstName har registrert et bilde av seg selv.'
					title = 'J&aring;lebukken $this->firstName har ogs&aring; registrert et selvportrett.'
				>
			";	
		}
		$out .= "</td>";
		
		//Phone
		if($this->phone){
			$out .= "<td><a href='tel:{$this->phone}'>{$this->phone}</a></td>";
		}else{
			$out .= "<td/>";
		}
		
		//E-mail
		if($this->email != ""){
			$out .= "<td><a href=\"mailto:{$this->email}\" title=\"E-post til {$this->firstName}\">{$this->email}</a></td>";
		}else{
			$out .= "<td></td>";	
		}
		
		//Motto
		
		$out .= "<td>";
		if ($this->trainer){
			$out .= "

				<img 
					src='images/ikonoppmann.gif' 
					alt='$this->firstName er oppmann'
					title='$this->firstName er oppmann' 
					width='35' 
					height='24' 
					border='0'
					align='texttop'>

			";		
		}
		$out .= " {$this->notes}";
		if ($this->id == $playerId) {
			$out .= $this->_EditSelfLink();	
		}
		$out .= "</td>";
		
		//Url
		$out .= $this->_GetUrlIcon();
		
		//Activity
		if ($this->maxActivity > 0){
			$out .= "<td>";
			$out .= $this->GetRelativeActivityAsText();
			$out .= "</td>";
		}
		
		$out .= "</tr>";
		return $out;
	}
	function _EditSelfLink(){
		global $detailsFrameName,$editPlayerFileName, $playerIdFieldName;
		$player = new Player_class();
		$playerId = $player->getId();
		$locationStr = $editPlayerFileName."?".$playerIdFieldName."=".$playerId;
		$out = " (<a href='$locationStr' target='$detailsFrameName' title='Rediger opplysningene om deg selv'>";
		$out .= "endre...";
		$out .= "</a>)";
		return $out;
	}
	function _GetUrlIcon(){
		global $playerInfoFrameName;
		if ($this->url != ""){
			$out = "<td width = '32'>";	
			$title = GenitiveNor($this->firstName)." webside";
			$out .= GetURLGlobeIcon("http://".$this->url, $title, $playerInfoFrameName);
			$out .= "</td>";
		}else{
			$out = "<td></td>";	
		}
		return $out;
	}
	function GetId(){
		return $this->id;	
	}
	function GetIsSelected(){
		$dataObj = new FormData_class();
		$selectedId = $dataObj->GetCurrentSubject();
		if ($selectedId == $this->GetId()){
			$out = true;
		}else{
			$out = false;	
		}
		return $out;
	}
	/**
	 * Relative activity defined as activity diveded by max activity
	 *
	 * @return float
	 */
	function GetRelativeActivity(){
		if ($this->maxActivity == 0){
			$out = 0;	
		}else{
			$out = $this->activity/$this->maxActivity;	
		}
		return $out;
	}
	/**
	 * Relative activity expressed verbally by
	 * bracketing expression
	 *
	 * @return string
	 */
	function GetRelativeActivityAsText(){
		$activity = $this->GetRelativeActivity();
		if ($activity == 1){
			$out = "maksimal!";	
		}elseif ($activity > 0.75){
			$out = "meget h&oslash;y";
		}elseif ($activity > 0.5){
			$out = "h&oslash;y";
		}elseif ($activity > 0.25){
			$out = "moderat";
		}elseif ($activity > 0.1){
			$out = "lav";
		}elseif ($activity > 0){
			$out = "praktisk talt ingen";
		}else{
			$out = "ingen";	
		}
		return $out;
	}	
}

class FormDataBase_class{
	var $sortNo,$sortName,$sortPhone,$sortMail,$sortMotto,$sortActivity;
	var $dateFieldName, $sortFieldName;
	function FormDataBase_class(){		
		$this->sortNo = "number";
		$this->sortName = "name";
		$this->sortPhone = "phone";
		$this->sortMail = "mail";
		$this->sortMotto = "motto";
		$this->sortActivity = "activity";
		$this->dateFieldName = "dateField";
		$this->sortFieldName = "sortorderField";
	}
	function GetCurrentDate(){
		$dateField = GetPostGetData($this->dateFieldName);
		if ($dateField > 0){
			$out = $dateField;	
		}else{
			$out = time()-(60*60*24*30*2);	
		}
		return $out;
	}
	function GetSortOrderFieldName(){
		return $this->sortFieldName;	
	}
	/**
	* @return string
	* @desc Returns sort order 
	*/
	function GetCurrentSortOrder(){
		$cookieName = "playerInfoSortOrder";
		$sortOrder = GetPostGetData($this->GetSortOrderFieldName());	
		if ($sortOrder==""){
			$sortOrder = $_COOKIE[$cookieName];
		}
		setcookie($cookieName,$sortOrder,time()+(60*60*24*7));
		return $sortOrder;
	}
}

class FormDataCSV_class extends FormDataBase_class {
	var $teamIdFieldName;
	function FormDataCSV_class(){
		//Base constructor
		$this->FormDataBase_class();
		$this->teamIdFieldName="team";
	}
	/**
	 * Gets team id of team to list
	 *
	 * @return integer
	 */
	function GetTeamId(){
		$teamId=GetPostGetData($this->teamIdFieldName);
		if($teamId==""||!$this->_verifyTeam($teamId)){
			//Fallback to current team
			$team=new Team_class();
			$teamId=$team->get_id();
		}
		return $teamId;
	}
	/**
	 * Verifies that the current player is member of team
	 *
	 * @param integer $team
	 */
	function _verifyTeam($teamId){
		$player=new Player_class();
		return $player->getIsMemberInTeam($teamId);
	}
	/**
	 * Returns the sort string to use in the query.
	 * hard coded
	 *
	 * @return string
	 */
	function GetSortString(){
		return "Order by lastName ASC ,firstName ASC";
	}
}

/**
 * Reads form data on current page
 *
 */
class FormData_class extends FormDataBase_class{
	var $formName, $sortOrderFunctionName, $subjectFunctionName;
	var $dateFunctionName, $alterDateFunctionName, $teamFunctionName;
	function FormData_class(){
		//Base constructor
		$this->FormDataBase_class();
		//Setting members
		$this->formName = "pageData_form";
		$this->sortOrderFunctionName = "NewSortOrder";
		$this->subjectFunctionName = "SubjectPlayer";
		$this->dateFunctionName = "NewDate";
		$this->teamFunctionName = "NewTeam";
		$this->alterDateFunctionName = "alterDate";
	}
	function RenderHiddenForm(){
		global $playerInfoFieldName,$teamChooserFieldName, $detailsAnchorName;
		$formName = $this->GetFormName();
		$sortOrder = $this->GetSortOrderFieldName();
		$dateField = $this->dateFieldName;
		$currentSubject = GetPostGetData($playerInfoFieldName);
		$currentSortOrder = $this->GetCurrentSortOrder();
		$currentDate = $this->GetCurrentDate();
		$currentTeam = $this->GetCurrentTeam();
		$formString = "\n<form
			method='post'
			action='#$detailsAnchorName'
			name='$formName' 
			id='$formName'>
		";
		$formString .= "<input name='$playerInfoFieldName' id='$playerInfoFieldName' type='hidden' value='$currentSubject'>";
		$formString .= "<input name='$sortOrder' id='$sortOrder' type='hidden' value='$currentSortOrder'>";
		$formString .= "<input name='$dateField' id='$dateField' type='hidden' value='$currentDate'>";
		$formString .= "<input name='$teamChooserFieldName' id='$teamChooserFieldName' type='hidden' value='$currentTeam'>";
		$formString .= "\n</form>\n";
		
		$scriptString = "
			function {$this->sortOrderFunctionName}(sortOrder){
				document.getElementById('$sortOrder').value = sortOrder;
				document.getElementById('$formName').submit();
			}
			function {$this->subjectFunctionName}(playerId){
				document.getElementById('$playerInfoFieldName').value = playerId;
				document.getElementById('$formName').submit();
			}
			function {$this->teamFunctionName}(teamId){
				document.getElementById('$teamChooserFieldName').value = teamId;
				document.getElementById('$playerInfoFieldName').value = 0;
				document.getElementById('$formName').submit();
			}
			function {$this->dateFunctionName}(dateCntrlObjId){
				dateValue = document.getElementById(dateCntrlObjId).value;
				{$this->alterDateFunctionName}(dateValue);
				document.getElementById('$formName').submit();
			}
			function {$this->alterDateFunctionName}(dateValue){
				document.getElementById('$dateField').value = dateValue;
			} 
		";
		$out = JavaScriptWrapper($scriptString);
		$out .= $formString;
		return $out;
	}

	function GetCurrentTeam(){
		$teamObj = new TeamOfList_class();
		return $teamObj->get_id();
	}
	function GetSortString(){
		$current = $this->GetCurrentSortOrder();
		if ($current == $this->sortNo){
			$out = "ORDER BY hasNumber DESC, realNumber ASC";
		}elseif ($current == $this->sortName){
			$out = "ORDER BY lastName, firstName ASC";	
		}elseif ($current == $this->sortPhone){
			$out = "ORDER BY phone DESC";	
		}elseif ($current == $this->sortMail){
			$out = "ORDER BY email ASC";	
		}elseif ($current == $this->sortMotto){
			$out = "ORDER BY hasMotto DESC, notes ASC";	
		}elseif ($current == $this->sortActivity){
			$out = "ORDER BY activity DESC, age ASC";	
		}else{
			$out = "ORDER BY age DESC";	
		}
		return $out;
	}
	function ColumnLabel($value){
		$currentSortOrder = $this->GetCurrentSortOrder();
		$label = $this->GetLabel($value);
		if ($currentSortOrder == $value){
			$out = $label;
		}else{
			$out = "<a href='javascript:{$this->sortOrderFunctionName}(\"$value\");' title='Sorter etter $label'>$label</a>";
		}
		return $out;
	}
	function SubjectPlayerLink($playerId){
		$out = "{$this->subjectFunctionName}('$playerId');";	
		return $out;
	}
	function GetDateFunctionName(){
		return $this->dateFunctionName;
	}
	function GetTeamFunctionName(){
		return $this->teamFunctionName;
	}
	function GetAlterDateFunctionName(){
		return $this->alterDateFunctionName;	
	}
	function GetLabel($value){
		$labels = array();
		$labels[$this->sortNo] = "&#8470;";
		$labels[$this->sortName] = "navn";
		$labels[$this->sortPhone] = "fon";
		$labels[$this->sortMail] = "e-post";
		$labels[$this->sortMotto] = "motto";
		$labels[$this->sortActivity] = "aktivitet siden ".date("d.m.Y",$this->GetCurrentDate());
		return $labels[$value];
	}
	function GetCurrentSubject(){
		global $playerInfoFieldName;
		return GetPostGetData($playerInfoFieldName);	
	}
	function GetFormName(){
		return $this->formName;	
	}

}

?>