<?php
//Dataobjects


class DataHolder extends BaseClass{
	var $fields;
	var $id;
	var $player;
	var $dataLoaded;
	
	var $STRINGMAXLENGTH;
	
	var $DATAINT;
	var $DATAFLOAT;
	var $DATABOOL;
	var $DATASTRING;
	var $DATAHTML;
	var $DATADATE;
	var $DATAURI;
	
	var $DATATYPE;
	var $FIELDNAME;
	var $CAPTION;
	var $DBFIELD;
	var $VALUE;
	var $OLDDATA;
	var $SAFEVALUE;
	var $DEFAULTVALUE;
	
	function _SetConst(){
		$this->DATAINT=1;
		$this->DATAFLOAT=2;
		$this->DATABOOL=3;
		$this->DATASTRING=4;
		$this->DATAHTML=5;
		$this->DATADATE=6;
		$this->DATAURI=7;
		$this->DATAURL=8;
		
		$this->DATATYPE="dataType";
		$this->FIELDNAME="fieldName";
		$this->CAPTION="caption";
		$this->DBFIELD="dbField";
		$this->VALUE="value";
		$this->OLDDATA="oldData";
		$this->SAFEVALUE="safeValue";
		$this->DEFAULTVALUE="defaultValue";
		
		$this->STRINGMAXLENGTH=3000;
	}
	
	function GetSafeData($fieldName){
		return $this->fields[$fieldName][$this->SAFEVALUE];	
	}
	function GetCaption($fieldName){
		return $this->fields[$fieldName][$this->CAPTION];	
	}
	function GetFieldname($fieldName){
		return $this->fields[$fieldName][$this->FIELDNAME];	
	}
	function GetTaintedValue($fieldName){
		return $this->fields[$fieldName][$this->VALUE];	
	}
	function GetQueriedData($fieldName){
		return $this->fields[$fieldName][$this->OLDDATA];	
	}
	function GetDBFieldname($fieldName){
		return $this->fields[$fieldName][$this->DBFIELD];	
	}
	function GetDatatype($fieldName){
		return $this->fields[$fieldName][$this->DATATYPE];	
	}
	function GetDefault($fieldName){
		return $this->fields[$fieldName][$this->DEFAULTVALUE];	
	}
	function _UnTaintData(){
		//Strips possibly tainted data and puts it in the SAFEVALUE variable
		foreach ($this->fields as $field){
			if(isset($field[$this->VALUE])){
				switch ($field[$this->DATATYPE]){
					case $this->DATAINT:
						$field[$this->SAFEVALUE]=(int) $field[$this->VALUE];
						break;
					case $this->DATAFLOAT:
						$field[$this->SAFEVALUE]=(float) $field[$this->VALUE];
						break;
					case $this->DATABOOL:
						$field[$this->SAFEVALUE]=(bool) $field[$this->VALUE];
						break;
					case $this->DATASTRING:
						$field[$this->SAFEVALUE]= strip_tags($field[$this->VALUE]);
						break;
					case $this->DATAHTML:
						$field[$this->SAFEVALUE]=strip_tags( $field[$this->VALUE],'<b><i><u>');
						break;
					case $this->DATADATE:
						$field[$this->SAFEVALUE]= date('Y-m-d H:i:s',$field[$this->VALUE]);
						break;
					case $this->DATAURI:
						$field[$this->SAFEVALUE]=strip_tags( $field[$this->VALUE]);
						break;	
					case $this->DATAURL:
						$tempString=strip_tags( $field[$this->VALUE]);
						if(!(stristr($tempString,'//'))){
							$tempString='http://'.$tempString;	
						}
						$field[$this->SAFEVALUE]=$tempString;
						break;
				}
			}
		}	
	}
	
	function _RetrieveGetAndPost(){
		$this->dataloaded=false;
		foreach ($this->fields as $field){
			if(isset($_GET[$field[$this->FIELDNAME]])){
				$this->field[$this->VALUE]=$_GET[$field[$this->FIELDNAME]];
				$this->dataLoaded=true;
			}elseif (isset($_POST[$field[$this->FIELDNAME]])){
				$this->field[$this->VALUE]=$_POST[$field[$this->FIELDNAME]];
				$this->dataLoaded=true;
			}
		}
	}
	function _RetrieveDatabaseData($selectQuery, $oneRow=true){
		$query = new ToolQueryFromDB($selectQuery);
		if($oneRow){
			//retrieve one row of data 	
			$allData=$query->GetNextRow();
			foreach ($this->fields as $field){
				$field[$this->OLDDATA]=$allData[$field[$this->DBFIELD]];
			}
		}else{
			//retrieve array of data	
		}
		
	}
	function _UpdateData($table,$fields,$includedFields,$id){
		$queryString="
		UPDATE $table ".$this->_MakeSetString($fields,$includeFields)." 
		WHERE id=$id";
		$query=new ToolQueryFromDB($queryString,2);
		
	}
	function _InsertQuery($table,$fields,$includedFields){
		$queryString="INSERT INTO $table ".$this->_MakeSetString($fields,$includedFields);
		$query=new ToolQueryFromDB($queryString,3);
		return $query->lastIndex;
	}
	function _DeleteData($table,$id){
		$queryString="DELETE FROM $table WHERE id=$id";
		$query=new ToolQueryFromDB($queryString,4);		
	}
	
	function _MarkForDeletion($table,$id){
		$queryString="UPDATE $table SET ";
		$queryString.=$this->fields['delete'][$this->FIELDNAME];
		$queryString.=" = 1";
		$query=new ToolQueryFromDB($queryString,2);
	}
	
	function _MakeSetString($fields, $includedFields){
		//To generate queries for update and insert
		$out="";
		$lines=array();
		foreach($includedFields as $field){
			$lines[]="SET ".$fields[$field][$this->DBFIELD]." = ".$fields[$field][$this->SAFEVALUE];
		}
		$out = implode($lines," , ");
		return $out;
	}
}


class DataIdentity extends DataHolder {
	//Establishes identity (if it exists)
	//Gets last login
	//Sets new logindate
	var $lastLogin;
	var $playerId;
	
	function DataIdentity(){
		$this->playerId=$this->_FindId();
	}
	function _FindId(){
		//establishing player-id
		$global=new ToolGlobals();
		$playerIdFieldName=$global->GetFieldName('playerId');
		
		if($_GET[$playerIdFieldName]){
			return $_Get[$playerIdFieldName];
		}elseif($_POST[$playerIdFieldName]){
			return $_POST[$playerIdFieldName];
		}elseif($_COOKIE[$playerIdFieldName]){
			return $_COOKIE[$playerIdFieldName];
		}else{
			return false;
		}
	}
	function GetId(){
		//PlayerId of user
		return $this->playerId;
	}
	function IdentityEstablished(){
		//Whether identity is known
		if ($this->playerId){
			$out=true;
		}else{
			$out=false;
		}
		return $out;	
	}
	function WriteCookie(){
		//If id is established; writes id to cookie
		if($this->IdentityEstablished()){
			$global=new ToolGlobals();
			$playerIdFieldName=$global->GetFieldName('playerId');
			$expire = $global->GetTimeToCookieExpire();
			return setcookie($playerIdFieldName,$this->playerId,$expire);
		}else{
			return false;	
		}
	}
	function _GetLastLoginFieldName(){
		//Fieldname as defined in db
		return 'lastLogin';	
	}
	function GetLastLogin(){
		//Query db for last login of player
		$id=$this->playerId;
		$fieldName=$this->_GetLastLoginFieldName();
		$query="
			SELECT $fieldName FROM players
			WHERE id=$id
		";
		$result = new ToolQueryFromDB($query);
		$resultRow=$result->GetNextRow();
		$this->lastLogin=$resultRow[$fieldName];
		return $this->lastLogin;		
	}
	function WriteNewLogin(){
		//Update db with new logindate
		$id=$this->playerId;
		$newLogin = $this->LocalNow();
		$fieldName=$this->_GetLastLoginFieldName();
		$query="
			UPDATE players
			SET $fieldName=$newLogin
			WHERE id=$id
		";
		$result = new ToolQueryFromDB($query,2);
	}	

}



class DataPage extends DataHolder  {
	
	//Information on URL and logonId
	
 	var $urlHere;
	var $teamId;
	var $playerId;
	
	function DataPage(){
		$this->urlHere=$this->_GetUrl();
		$this->teamId=$this->_GetTeamId();
		$this->playerId=$this->_GetPlayerId();
		
		//debug:
		if(! $this->teamId){
			$this->teamId=1;
		}
		
	} 
	

	
	function _GetUrl(){
		return 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']; 
	}
	
	function _GetTeamId(){
		//Finding team-id
		//in login-information
		//debug
		//remove comment in line underneath
		//$loginName=$_SERVER['PHP_AUTH_USER'];
		//debug
		//replacing login-name
		$loginName='Evig';
		
		return $loginName;
	}
	
	function _GetPlayerId(){
		$idObject = new DataIdentity();
		return $idObject->GetId();
	}
	
	
	function GetUrl(){
		return $this->urlHere;
	}
	function GetTeamId(){
		return $this->teamId;	
	}
	function GetPlayerId(){
		return  $this->playerId;
	}
}


class DataEvent extends DataHolder{
	//All data about an event

	function DataEvent($playerId=false){
		if($id){
			$this->id=$id;
		}
		if($player){
			$this->player=$PlayerId;
		}
		$this->_DefineFieldNames();
		$this->_RetrieveGetAndPost();
		$this->_UnTaintData();
		$this->_ValidateData();
	}
	function _DefineFieldNames(){
		//Set virtual constants
		$this->_SetConst();
		$INT=$this->DATAINT;
		$FLOAT=$this->DATAFLOAT;
		$BOOL=$this->DATABOOL;
		$STRING=$this->DATASTRING;
		$HTML=$this->DATAHTML;
		$DATE=$this->DATADATE;
		$URI=$this->DATAURI;
		$URL=$this->DATAURL;
		//
		$TYPE=$this->DATATYPE;
		$FIELD=$this->FIELDNAME;
		$CAPT=$this->CAPTION;
		$DB=$this->DBFIELD;
		$DEFAULT=$this->DEFAULTVALUE;
		//
		$this->fields=array(
			'id'=>array($TYPE=>$INT,$FIELD=>'eventId',$CAPT=>null, $DB=>'id'),
			'startDate'=>array($TYPE=>$DATE,$FIELD=>'eventStartDate',$CAPT=>'Startdato', $DB=>'dateStart'),
			'endDate'=>array($TYPE=>$DATE,$FIELD=>'eventEndDate',$CAPT=>'Sluttdato', $DB=>'dateEnd'),
			'location'=>array($TYPE=>$STRING,$FIELD=>'eventLocation',$CAPT=>'Sted', $DB=>'location'),
			'description'=>array($TYPE=>$STRING,$FIELD=>'eventDescription',$CAPT=>'Hendelse', $DB=>'description'),
			'type'=>array($TYPE=>$INT,$FIELD=>'eventType',$CAPT=>'Type hendelse', $DB=>'type', $DEFAULT=>2),
			'typeName'=>array($TYPE=>$STRING,$FIELD=>'eventTypeName',$CAPT=>'Type hendelse', $DB=>'typeName'),
			'requiredCount'=>array($TYPE=>$INT,$FIELD=>'requiredCount',$CAPT=>'Ønsket antall', $DB=>'requiredCount'),
			'confirmed'=>array($TYPE=>$INT,$FIELD=>'confirmedCount',$CAPT=>'Antall som kommer', $DB=>'confirmed'),
			'cancelled'=>array($TYPE=>$BOOL,$FIELD=>'eventCancelled',$CAPT=>'Avlyst', $DB=>'cancelled', $DEFAULT=>false),
			'notes'=>array($TYPE=>$STRING,$FIELD=>'eventNotes',$CAPT=>'Merknader', $DB=>'notes'),
			'url'=>array($TYPE=>$URL,$FIELD=>'eventUrl',$CAPT=>'Web-side', $DB=>'url'),
			'team'=>array($TYPE=>$INT,$FIELD=>'eventTeam',$CAPT=>'Lag', $DB=>'team'),
			'notified'=>array($TYPE=>$BOOL,$FIELD=>'eventNotified',$CAPT=>'P&aring;minnelse er sendt', $DB=>'notified', $DEFAULT=>false),
			'myReply'=>array($TYPE=>$INT,$FIELD=>'eventReply',$CAPT=>null, $DB=>'myReply'),
			'myReplyLongName'=>array($TYPE=>$STRING,$FIELD=>'eventReplyLongName',$CAPT=>'Svar', $DB=>'myReplyLongName'),
			'myReplyShortname'=>array($TYPE=>$STRING,$FIELD=>'eventReplyShortname',$CAPT=>'Antall uker', $DB=>'myReplyShortname'),
			'myReplyNote'=>array($TYPE=>$INT,$STRING=>'eventReplyNote',$CAPT=>'Antall uker', $DB=>'myReplyNote'),
			'recurrence'=>array($TYPE=>$INT,$FIELD=>'eventRecurrence',$CAPT=>'Antall uker', $DB=>null, $DEFAULT=>1),
			'author'=>array($TYPE=>$INT,$FIELD=>'eventAuthor',$CAPT=>'Hendelse er registrert av', $DB=>'author'),
			'authorName'=>array($TYPE=>$STRING,$FIELD=>'eventAuthorName',$CAPT=>'Hendelse er registrert av', $DB=>'authorName'),
			'createDate'=>array($TYPE=>$DATE,$FIELD=>'eventCreateDate',$CAPT=>'Opprettet', $DB=>'createDate'),
			'lastEditor'=>array($TYPE=>$INT,$FIELD=>'eventEditor',$CAPT=>'Sist endret av', $DB=>'lastEditor'),
			'editorName'=>array($TYPE=>$STRING,$FIELD=>'eventEditorName',$CAPT=>'Hendelse ble sist endret av', $DB=>'editorName'),
			'dateLastEdit'=>array($TYPE=>$DATE,$FIELD=>'eventDateLastEdit',$CAPT=>'Ble sist endret', $DB=>'dateLastEdit'),
			'deleted'=>array($TYPE=>$BOOL,$FIELD=>'eventDelete',$CAPT=>'Slett hendelse',$DB=>'deleted', $DEFAULT=>false),
			'changeMessage'=>array($TYPE=>$STRING,$FIELD=>'eventChangeMessage',$CAPT=>'Beskjed til de påmeldte',$DB=>null)
		);
	}
	
	function _ValidateData(){
		
	}
	
	function MakeSelectQuery($id, $playerId=false){
		$query=array();
		$query[]="
		SELECT 	events.*, eventTypes.type AS ".$this->fields['typeName'][$this->DBFIELD].", 
				eventTypes.requiredCount AS ".$this->fields['requiredCount'][$this->DBFIELD]."
		FROM events LEFT JOIN eventTypes ON events.type = eventTypes.id
		WHERE (((events.id)=$id))";
		
		//Get number of confirmed
		$query[]="
		SELECT Count(attention.id) AS ".$this->fields['confirmed'][$this->DBFIELD]."
		FROM attention JOIN attentionType 
		WHERE ((attention.type = attentionType.id) 
				AND (attentionType.value=1)) 
				AND (attention.event='$id')
		GROUP BY attentionType.value, attention.event";
		
		//Get log-data
		//Author
		$query[]="
		SELECT 	log.player AS ".$this->fields['author'][$this->DBFIELD].", 
				log.date as ".$this->fields['createDate'][$this->DBFIELD].", 
				CONCAT(players.firstName, ' ' , players.lastName) AS ".$this->fields['authorName'][$this->DBFIELD]."
		FROM eventChangeLog log JOIN players
		WHERE ((log.event LIKE '202') AND (log.player=players.id))
		ORDER BY date ASC
		LIMIT 1
		";
		
		//Last editor
		$query[]="
		SELECT 	log.player AS ".$this->fields['lastEditor'][$this->DBFIELD].", 
				log.date as ".$this->fields['dateLastEdit'][$this->DBFIELD].", 
				CONCAT(players.firstName, ' ' , players.lastName) AS ".$this->fields['editorName'][$this->DBFIELD]."
		FROM eventChangeLog log JOIN players
		WHERE ((log.event LIKE '202') AND (log.player=players.id))
		ORDER BY date DESC
		LIMIT 1
		";
		//Get my answer
		if($playerId){
			$query[]="
			SELECT 	type.type AS ".$this->fields['myReply'][$this->DBFIELD]." , 
					type.longName AS ".$this->fields['myReplyLongName'][$this->DBFIELD]." , 
					type.shortName as ".$this->fields['myReplyShortName'][$this->DBFIELD]." , 
					type.value AS ".$this->fields['myReplyValue'][$this->DBFIELD].", 
					attention.notes AS ".$this->fields['myReplyNote'][$this->DBFIELD]."
			FROM attention LEFT JOIN attentionType type ON attention.type = type.id
			WHERE (((attention.event)=$id) AND ((attention.player)=$playerId))
			LIMIT 1
			";	
		}
		foreach ($query as $queStr){
			$this->_RetrieveDatabaseData($queStr,true);	
		}	
	}

	function MakeInsertQuery($playerId){
		//Insert logic for recurring events
		$newId;
		$includedFields=array(
				'startDate','endDate','location','description',
				'type','cancelled','notes','url','team');		
		$newId=$this->_InsertQuery('events',$this->fields,$includedFields);
		$this->_UpdateLog($newId,$playerId);
		return $newId;
	}
	function MakeUpdateQuery($playerId){
		$id=$this->fields['id'][$this->SAFEVALUE];
		$includedFields=array(
				'startDate','endDate','location','description',
				'type','cancelled','notified','notes','url','team');
		$this->_UpdateData('events',$this->fields,$includedFields,$id);
		$this->_UpdateLog($id,$playerId);
	}
	function MakeDeleteQuery($playerId){
		$id=$this->fields['id'][$this->SAFEVALUE];
		$this->_MarkForDeletion('events',$id);
		$this->_UpdateLog($this->fields['id'][$this->$SAFEVALUE],$playerId);
	}
	function _UpdateLog($eventId,$playerId){
		$fields=array(
				'player'=>array($this->DBFIELD=>'player',$this->SAFEVALUE=>$playerId),
				'event'=>array($this->DBFIELD=>'event',$this->SAFEVALUE=>$eventId),
				'date'=>array($this->DBFIELD=>'date',$this->SAFEVALUE=>$this->LocalNow())
		);
		$includedFields=array('player','event','date');
		$this->_InsertQuery('eventChangeLog',$fields,$includedFields);
	}

}

class DataPlayer extends DataHolder{
	//All data about a player
	var $teams;
	
	function DataPlayer($playerId=false){
		if($playerId){
			$this->player=$PlayerId;
		}
		$this->_DefineFieldNames();
		$this->_RetrieveGetAndPost();
		$this->_UnTaintData();
		$this->_ValidateData();
	}
	function _DefineFieldNames(){
		//Set virtual constants
		$this->_SetConst();
		$INT=$this->DATAINT;
		$FLOAT=$this->DATAFLOAT;
		$BOOL=$this->DATABOOL;
		$STRING=$this->DATASTRING;
		$HTML=$this->DATAHTML;
		$DATE=$this->DATADATE;
		$URI=$this->DATAURI;
		$URL=$this->DATAURL;
		//
		$TYPE=$this->DATATYPE;
		$FIELD=$this->FIELDNAME;
		$CAPT=$this->CAPTION;
		$DB=$this->DBFIELD;
		$DEFAULT=$this->DEFAULTVALUE;
		//
		$this->fields=array(
			'id'=>array($TYPE=>$INT,$FIELD=>'playerId',$CAPT=>null, $DB=>'id'),
			'firstName'=>array($TYPE=>$STRING,$FIELD=>'playerFirstName',$CAPT=>'Fornavn', $DB=>'firstName'),
			'lastName'=>array($TYPE=>$STRING,$FIELD=>'playerLastName',$CAPT=>'Etternavn', $DB=>'lastName'),
			'picture'=>array($TYPE=>$URI,$FIELD=>'playerPicture',$CAPT=>'Bilde', $DB=>'picture'),
			'residence'=>array($TYPE=>$STRING,$FIELD=>'playerResidence',$CAPT=>'Adresse', $DB=>'residence'),
			'phone'=>array($TYPE=>$STRING,$FIELD=>'playerPhone',$CAPT=>'Telefon', $DB=>'phone'),
			'email'=>array($TYPE=>$STRING,$FIELD=>'playerEmail',$CAPT=>'E-post', $DB=>'email'),
			'htmlMail'=>array($TYPE=>$BOOL,$FIELD=>'playerHtmlMail',$CAPT=>'Jeg kan motta formatert e-post', $DB=>'htmlMail', $DEFAULT=true),
			'age'=>array($TYPE=>$DATE,$FIELD=>'playerBirthDate',$CAPT=>'Fødselsdato', $DB=>'age'),
			'url'=>array($TYPE=>$URL,$FIELD=>'playerUrl',$CAPT=>'Hjemmeside', $DB=>'url'),
			'lastLogin'=>array($TYPE=>$DATE,$FIELD=>null,$CAPT=>'Sist pålogget', $DB=>'lastLogin'),
			'userName'=>array($TYPE=>$STRING,$FIELD=>'playerUserName',$CAPT=>'Brukernavn', $DB=>'userName'),
			'password'=>array($TYPE=>$STRING,$FIELD=>'playerPassword',$CAPT=>'Passord', $DB=>'password'),
			'mainTeam'=>array($TYPE=>$INT,$FIELD=>'playerMainTeam',$CAPT=>'Lag', $DB=>null),
			'currentMembership'=>array($TYPE=>$INT,$FIELD=>'playerCurrentMembership',$CAPT=>null, $DB=>'membershipId'),
			'number'=>array($TYPE=>$STRING,$FIELD=>'playerNumber',$CAPT=>'Draktnummer', $DB=>'number'),
			'notes'=>array($TYPE=>$HTML,$FIELD=>'playerNotes',$CAPT=>'Merknad', $DB=>'notes'),
			'reminders'=>array($TYPE=>$INT,$FIELD=>'playerReminderFilter',$CAPT=>'Jeg vil ha påminnelser om', $DB=>'reminders', $DEFAULT=>0),
			'withdrawalFromTeam'=>array($TYPE=>$INT,$FIELD=>'playerWithdrawal',$CAPT=>'Meld meg ut av laget',$DB=>null, $DEFAULT=>false),
			'deleted'=>array($TYPE=>$BOOL,$FIELD=>'playerDelete',$CAPT=>'Slett spiller',$DB=>'deleted', $DEFAULT=>false)
		);
	}
	
	function _GetTeams($playerId){
		$this->teams=array();
		$queryString="
		SELECT 	teams.id AS teamId, teams.shortName, teams.longName, 
				teams.userName, teams.password, 
				membership.id AS membershipId, membership.number, membership.teamTrainer,
				membership.notes, membership.reminders
		FROM teams JOIN membership
		WHERE 	(teams.id=membership.team) AND 
				(membership.player=$playerId) AND 
				((membership.deleted Is Null)OR(membership.deleted=0))
		";	
		$query=new ToolQueryFromDB($queryString);
		foreach ($query->allRows as $row){
			$this->teams[]=$row;	
		}
		
	}
	
	
	function _ValidateData(){
		
	}
	
	function MakeSelectQuery(){

		$query=array();
		//Get playerinfo
		$query[]="
		
		";


		foreach ($query as $queStr){
			$this->_RetrieveDatabaseData($queStr,true);	
		}	
		
		$this->_GetTeams($id);
	}

	function MakeInsertQuery($id){
		$newId;
		$this->_UpdateLog($newId,$playerId);			
		return $newId;
	}
	function MakeUpdateQuery($playerId){

	}
	function MakeDeleteQuery($playerId){

	}
	function RegisterLogin(){
		
	}
	function GetFullName(){
		$out= $this->GetQueriedData('firstName');
		$out .= " ";
		$out .= $this->GetQueriedData('lastName');
		return $out;
	}
}

class DataMessage extends DataHolder {
	
}

class DataPlayerList extends DataHolder {
	
}

class DataMessageList extends DataHolder {
	
}

class DataEventList extends DataHolder {
	
}

class DataSeasons extends DataHolder{
	
}

class DataTopScorerList extends DataHolder {
	
}

class DataTeamsAndMembership extends DataHolder {
	var $numberOfMemberships;
	var $memberships;
	var $activeTeamIndex;
	
	function DataTeamsAndMembership($playerId=false){
		
	}
	
	function GetNumberOfMemberships(){
		return $this->numberOfMemberships;	
	}
	function GetMembership($index){
		return $this->memberships[$index];	
	}
	function GetActiveTeam(){
		return $this->memberships[$this->activeTeamIndex];	
	}
	
	function _GetCurrentTeamId($playerId){
		$loginName=$_SERVER['PHP_AUTH_USER'];
		$sqlString="SELECT id ,longName,headerFile FROM teams WHERE userName='$loginName'";
		$query=new ToolQueryFromDB($sqlString,1);
		$resultRow= $query->GetNextRow();
		return $resultRow['id'];
	}
}

class DataTeam extends DataHolder {
	function DataTeam($teamid){
		$this->_DefineFieldNames();
		$this->_MakeSelectQuery($teamid);
	}	
	function _DefineFieldNames(){
		//Set virtual constants
		$this->_SetConst();
		$globals = new ToolGlobals();
		$INT=$this->DATAINT;
		$FLOAT=$this->DATAFLOAT;
		$BOOL=$this->DATABOOL;
		$STRING=$this->DATASTRING;
		$HTML=$this->DATAHTML;
		$DATE=$this->DATADATE;
		$URI=$this->DATAURI;
		$URL=$this->DATAURL;
		//
		$TYPE=$this->DATATYPE;
		$FIELD=$this->FIELDNAME;
		$CAPT=$this->CAPTION;
		$DB=$this->DBFIELD;
		$DEFAULT=$this->DEFAULTVALUE;
		//
		$this->fields=array(
			'teamId'=>array($TYPE=>$INT,$FIELD=>'id',$CAPT=>null, $DB=>'id'),
			'shortName'=>array($TYPE=>$STRING,$FIELD=>null,$CAPT=>null, $DB=>'shortName'),
			'longName'=>array($TYPE=>$STRING,$FIELD=>null,$CAPT=>null, $DB=>'longName'),
			'userName'=>array($TYPE=>$STRING,$FIELD=>null,$CAPT=>null, $DB=>'userName'),
			'password'=>array($TYPE=>$STRING,$FIELD=>null,$CAPT=>null, $DB=>'password'),
			'headerFile'=>array($TYPE=>$STRING,$FIELD=>null,$CAPT=>null, $DB=>'headerFile')
		);
	}
	function _MakeSelectQuery($id){
		$query=array();
		$query[]="
		SELECT 	* FROM teams WHERE id=$id;
		";
		foreach ($query as $queStr){
			$this->_RetrieveDatabaseData($queStr,true);	
		}	
	}
	function GetShortName(){
		return $this->GetQueriedData('shortname');
	}
	function GetLongname(){
		return $this->GetQueriedData('longName');	
	}
	function GetUserName(){
		return $this->GetQueriedData('userName');	
	}
	function GetPassword(){
		return $this->GetQueriedData('password');	
	}
	function GetHeaderFile(){
		return $this->GetQueriedData('headerFile');	
	}
}

?>