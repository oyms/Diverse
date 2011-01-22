<?php 
require_once('commonCode.inc');

$main = new MainOutput_class();
echo $main->RenderHtml();

class MainOutput_class {
	var $outPut;
	
	function MainOutput_class(){
		$editButtons = new EditButtons_class();
		$out = $editButtons->GetButtons();
		$birthdays = new Birthday_class();
		$out .= $birthdays->PlayerOfTheDay();
		$out .= $this->_PlayerList();
		$this->outPut = $this->_MainWrapper($this->_FormWrapper($out));	
	}
	function _PlayerList(){
		$list = new PlayerList_class();
		$out = $list->GetList();
		return $out;	
	}
	

	function _MainWrapper($bodyString){
		$team = new Team_class();
		$teamId = $team->get_id();
		$out = "<html>\n";
		$out .= "<head>\n";
		$out .= "<title>Hvem er du?</title>\n";
		$out .= RenderCSSLink($teamId);
		$out .= "</head>\n";
		$out .= "<body>\n";
		$out .= $bodyString;
		$out .= "\n</body>";
		$out .= "\n</html>";
		return $out;	
	}
	function _FormWrapper($data){
		$out = "\n
			<form 
				action='' 
				method='post' 
				name='PlayerList_form' 
				id='PlayerList_form'>
		";
		$out .= $data;
		$out .= "
			</form>
		";
		return $out;	
	}
	function RenderHtml(){
		return $this->outPut;
	}
}

class EditButtons_class{
	var $editPlayerButtonName, $setPlayerIdFunctionName;
	function EditButtons_class(){
		$this->editPlayerButtonName = 'personEdit_button';
		$this->setPlayerIdFunctionName = 'SetPlayerId';	
	}
	function GetSetPlayerIdFunctionName(){
		return $this->setPlayerIdFunctionName;	
	}	
	function GetButtons(){
		global $playerIdFieldName, $editPlayerFileName;
		$playerFormFilename = $editPlayerFileName;
		$player = new Player_class();
		$playerId = $player->getId();
		$disabledValueString = $playerId ? "" : "disabled"; 
		$scriptString = "
				var $playerIdFieldName = '$playerId';
				function OpenForm(edit){
					var locationString = '$playerFormFilename';
					if (edit){
						locationString += '?{$playerIdFieldName}='+$playerIdFieldName;
					}
					location = locationString;
				}
				function {$this->setPlayerIdFunctionName}(id){
					$playerIdFieldName = id;
					if(id > 0){
						document.getElementById('{$this->editPlayerButtonName}').disabled = false;
					}
				}
			";
		$out = JavaScriptWrapper($scriptString);
		$out .= "
			<input 
				name='personReg_button' 
				type='button' 
				id='personReg_button' 
				onClick='OpenForm(false);'
				value='Registrere ny spiller'>
			<input 
				name='$this->editPlayerButtonName'
				type='button' 
				id='$this->editPlayerButtonName'
				onClick='OpenForm(true);' 
				value='Redigere/Slette min info'
				$disabledValueString>
  			<hr>
		";
		return $out;
	}
}
class Instructions_class {
	function _Instructions(){
		$emailSymbol = $this->_EmailSymbol();
		$playerInfoSymbol = PlayerInfoLink();
		$out = "
			<p>
				Finn deg selv i listen under, eller registrer deg med knappen over.
				Klikk {$emailSymbol} for å sende e-post. Klikk {$playerInfoSymbol} for
				en komplett spillerliste med kontaktinformasjon.
			</p>
		";
		return $out;	
	}
	function _EmailSymbol(){
		$team = new Team_class();
		$teamId = $team->get_id();
		$teamName = $team->getName("short");
		$emailImg = "
			<img src='images/epostikon.gif' 
			alt='e-post til alle!' 
			width='29' 
			height='18' 
			border='0' 
			align='texttop'>
		";
		$queryString = "
			SELECT email, firstName, lastName  
			FROM players INNER JOIN membership ON players.id = membership.player
			WHERE (((membership.team)=$teamId)) 
			ORDER BY age DESC
		";
		$query = new DBConnector_class($queryString);
		$recipients = array();
		while ($recipient = $query->GetNextRow()) {
			$recipients[] = $recipient['firstName']." ".$recipient['lastName'].
				"<".$recipient['email'].">";
		}
		$listOfRecipients = implode(';',$recipients);
		$out = "
			<a href='mailto:{$listOfRecipients}?subject={$teamName}'>
				{$emailImg}
			</a>
		";
		return $out;
	}
	function GetInstructions(){
		return $this->_Instructions();	
	}
}
class PlayerSelector_class{
	var $id, $firstName,$lastName,$email,$phone;
	var $number,$notes,$web,$lastLogin,$teamTrainer;
	var $objectId;
	
	function PlayerSelector_class(
		$id,
		$firstName,
		$lastName,
		$email,
		$phone,
		$number,
		$notes,
		$web,
		$lastLogin,
		$teamTrainer){
			
		$this->id = $id;
		$this->firstName = $firstName;
		$this->lastName = $lastName;
		$this->email = $email;
		$this->phone = $phone;
		$this->number = $number;
		$this->notes = $notes;
		$this->web = $web;
		$this->lastLogin = $lastLogin;
		$this->teamTrainer = $teamTrainer;
		$this->objectId = "playerNo".$this->id;
		
	}
	function GetId(){
		return $this->objectId;	
	}
	function GetPlayerId(){
		return $this->id;
	}
	function _IsValidNumber($registeredNumber){
		$out = is_numeric($registeredNumber);
		return $out;	
	}
	function SecondsInText($secondsCount, $name){
		$outStr="";
		if ($secondsCount=="") {
				//Null
				$outStr="$name har ikke v&aelig;rt innom i historisk tid.";
		}elseif($secondsCount < 3600){
				//Under en time
				$outStr="$name var nettopp innom sidene.";
		}elseif($secondsCount < 43200){
				//Halvt døgn
				$outStr="$name har nylig v&aelig;rt innom og kikket.";
		}elseif($secondsCount < 259200){
				//Tre døgn
				$outStr="$name har v&aelig;rt innom i det siste.";
		}elseif($secondsCount < 1036800){
				//12 døgn
				$outStr="Det er noen dager siden $name sist var innom.";
		}elseif($secondsCount < 3024000){
				//5 uker
				$outStr="Det er noen uker siden $name var innom sist.";
		}else{
				$outStr="Det er lenge siden $name kikket p&aring; disse sidene sist.";
		}
		return $outStr;
	}
	function GetSelector($mouseOverFunctionName,$mouseOutFunctionName,$selectFunctionName){
		global $messageListFrameName;
		$out = "\n<table width = '100%' height='100%' cellspacing='0' cellpadding='0' border='0'><tr><td>\n";
		$out .= "<table width = '80%' height = '100%' cellspacing='0' cellpadding='2' class='playerBox'>";
		$out .= "<tr id='{$this->objectId}' valign='middle'";
		$out .= "
			onMouseOver='{$mouseOverFunctionName}(this)'
			onMouseOut ='{$mouseOutFunctionName}(this)'
			onClick ='{$selectFunctionName}(this,{$this->id})'";
		$out .= ">\n";
		
		if ($this->number && $this->_IsValidNumber($this->number)){
			$out .= "
				<td 
					class='playerNumber'
					width='32' 
					height='32' 
				>{$this->number}</td>";
		}else{
			$out .= "<td width='32' height='32'>&nbsp;</td>";
		}
		
		$out .= "<td width='*'>";
		$out .= "<a href='javascript:{$selectFunctionName}(document.getElementById(\"{$this->objectId}\"),{$this->id})'
					title='".$this->SecondsInText($this->lastLogin, $this->firstName)."'
				>";
		$out .= "$this->firstName<br>$this->lastName";
		$out .= "</a>";	
		if ($this->teamTrainer){
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
		$out .= "</td>";
		
		
		/*
		if ($this->phone){
			$out .= "<td width = '25'>";
			$out .= "<a href='javascript:alert(\"Fon: $this->phone\")' title='$this->phone'>";
			$out .= "<img 
						src='images/tlfikon.gif' 
						alt='Telefon: $this->phone' 
						width='25' 
						height='24' 
						border='0' 
						align='texttop' >";
			$out .= "</a>";
			$out .= "</td>";	
		}
		
		if ($this->email){
			$out .= "<td width = '29'>";
			$out .= "<a href='mailto:$this->email' title='$this->email'>";
			$out .= "<img 
						src='images/epostikon.gif' 
						alt='E-post: $this->email' 
						width='29' 
						height='18' 
						border='0' 
						align='texttop' >";
			$out .= "</a>";
			$out .= "</td>";		
		}
		
		*/
		if ($this->notes != ""){
			$out .= "<td class='verySmallText' width = '*'><em>$this->notes</em></td>";
		}
		/*
		if ($this->web != ""){
			$out .= "<td width = '32'>";
			$title .= "$this->firstName";
			$lastChar = substr($this->firstName,-1);
			if ($lastChar == "s" || $lastChar == "S" || $lastChar == "z" || $lastChar == "Z"){
				$title .= "' ";
			}else{
				$title .= "s ";	
			}	
			$title .=  "webside";
			$out .= GetURLGlobeIcon("http://".$this->web, $title, $messageListFrameName);
			$out .= "</td>";	
		}
		*/
		$out .= "\n</tr></table>";
		$out .= "</td>";
		$out .= "<td>";
		$out .= "<table width = '32' height = '100%'><tr><td>";
		$out .= PlayerInfoLink($this->id,$this->firstName, 0,"left", "small");
		$out .= "</td></tr></table>";
		$out .= "</td>";
		$out .= "\n</tr></table>";
		return $out;	
	}
}
class PlayerList_class{
	var $players, $mouseOverFunctionName, $mouseOutFunctionName, $selectFunctionName;
	function PlayerList_class(){
		$this->players = $this->_getQuery();
		$this->mouseOutFunctionName = "PlayerMouseOut";
		$this->mouseOverFunctionName = "PlayerMouseOver";
		$this->selectFunctionName = "PlayerSelect";
	}
	function _getQuery(){
		$allPlayers = array();
		$team = new Team_class();
		$teamId = $team->get_id();
		$queryString = "
			SELECT 	players.id, players.firstName, players.lastName, players.url,
					players.residence, membership.notes, players.phone, 
					membership.number, players.age, players.email, 
					membership.teamTrainer,
					(IF(players.dubviousAge,DATE_SUB(NOW(),INTERVAL 1 DAY),players.age)) AS displayAge, 
					unix_timestamp(NOW())-unix_timestamp(players.lastLogin) AS secondsSinceLastLogin,
					FLOOR(unix_timestamp(NOW())-unix_timestamp(players.lastLogin)/(60*60*24*7)) AS weeksSinceLastLogin,
					(lastLogin>0) AS active
			FROM players INNER JOIN membership ON players.id = membership.player
			WHERE (((membership.team)=$teamId))
			ORDER BY active DESC, weeksSinceLastLogin ASC, players.lastName
		";
		$query = new DBConnector_class($queryString);
		while ($playerData = $query->GetNextRow()) {
			$allPlayers[] = new PlayerSelector_class(
				$playerData['id'],
				$playerData['firstName'],
				$playerData['lastName'],
				$playerData['email'],
				$playerData['phone'],
				$playerData['number'],
				$playerData['notes'],
				$playerData['url'],
				$playerData['secondsSinceLastLogin'],
				$playerData['teamTrainer']);
		}
		return $allPlayers;
	}
	function GetList(){
		$team = new Team_class();
		$teamName = $team->getName("long");
		$allplayers = $this->players;
		$out = "";
		$numberOfPlayers = count($allplayers);
		$playersPerLine = 2;
		$numberOfRows = ceil($numberOfPlayers/$playersPerLine);
		$instructions = new Instructions_class();
		$instructionsString = $instructions->GetInstructions();
		$out .= "\n<table 
			width='100%'  
			border='0' 
			cellpadding='0' 
			cellspacing='0' 
			id='spillerliste' 
			summary='Spillerliste for $teamName'>
  		<caption class='smallText'>
  			$instructionsString
 	 	</caption>\n";
		for ($rowIndex = 0 ; $rowIndex < $numberOfRows ; $rowIndex++ ){
			$out .= "\n\t<tr>";
			for ($colIndex = 0 ; $colIndex < $playersPerLine ; $colIndex++ ){
				$playerIndex = ($rowIndex*$playersPerLine)+$colIndex;
				if ($playerIndex < $numberOfPlayers){
					$out .= "\n\t\t<td>";
					$out .= $allplayers[$playerIndex]->GetSelector(
						$this->mouseOverFunctionName,
						$this->mouseOutFunctionName,
						$this->selectFunctionName);
					$out .= "</td>";
				}
			}
			$out .= "\n\t</tr>";
		}
		$out .= "\n</table>\n";
		$out .= $this->_Javascripts();
		return $out;	
	}
	function _GetIdByPlayerId($playerId){
		$out = "";
		foreach ($this->players as $playerObj) {
			if ($playerObj->GetPlayerId() == $playerId){
				$out = $playerObj->GetId();
			}
		}
		return $out;	
	}
	function _Javascripts(){
		global $playerIdFieldName, $listFrameName, $messageListFrameName;
		global $eventListFileName, $messageListFileName, $headerFrameName;
		$editButtons = new EditButtons_class();
		$updateFunction = $editButtons->GetSetPlayerIdFunctionName();
		$team = new Team_class();
		$headerFile = $team->get_headerFile();
		$player = new Player_class();
		$playerId = $player->getId();
		if ($playerId){
			$objId = $this->_GetIdByPlayerId($playerId);
			if ($objId != ""){
				$initialSelectorString = "lastObject = document.getElementById('$objId');{$this->selectFunctionName}(lastObject,$playerId);";	
			}else{
				$initialSelectorString = "//No player is selected initially.";	
			}	
		}
		$out = "
			// MouseOverEffects
			function {$this->mouseOverFunctionName}(obj){
				obj.style.backgroundColor = '#ccccff';
			}
			function {$this->mouseOutFunctionName}(obj){
				obj.style.backgroundColor = '';
				if (typeof(UpdateHighlighting) != 'undefined'){
					UpdateHighlighting();
				}
			}
			function {$this->selectFunctionName}(obj,playerId){
				{$updateFunction}(playerId);
				top.{$listFrameName}.location = '{$eventListFileName}?{$playerIdFieldName}='+playerId;
				top.{$messageListFrameName}.location = '{$messageListFileName}?{$playerIdFieldName}='+playerId;
				top.{$headerFrameName}.location = '{$headerFile}?{$playerIdFieldName}='+playerId;
				UpdateHighlighting(obj);
			}
			function UpdateHighlighting(obj){
				if (obj == null) {
					if(typeof(lastObject) != 'undefined'){
						FormatSelected(lastObject);
					}
				}else{
					if(typeof(lastObject) != 'undefined'){
						lastObject.style.backgroundColor = '';
					}
					FormatSelected(obj);
					lastObject = obj;
				}
			}
			function FormatSelected(obj){
				obj.style.backgroundColor = '#dddddd';
			}
			$initialSelectorString
		";
		return JavaScriptWrapper($out);
	}	
}
class Birthday_class{
		function PlayerOfTheDay($teamId=0){
		/* Henter mulige bursdagsbarn */
		if ($teamId == 0){
			$team = new Team_class();
			$teamId = $team->get_id();
		}
		$query_playerOfTheDay = "
									SELECT firstName, lastName, email, (YEAR(NOW()-age)-2000) AS years 
									FROM players 
											INNER JOIN membership 
											ON players.id = membership.player
									WHERE ((DAYOFMONTH(age)=DAYOFMONTH(NOW())) 
											AND (MONTH(age)=MONTH(NOW())) 
											AND (membership.team=$teamId)
											AND (players.dubviousAge < 1))
								";
		$query = new DBConnector_class($query_playerOfTheDay);
		$totalRows_playerOfTheDay = $query->GetNumberOfRows();
	
		/* Lage gratulasjoner  */
		$greetingString="";
		if ($totalRows_playerOfTheDay) {
			$greetingString  = "<p><strong>";
			$greetingString .= "<img src='images/typeIconParty.gif' title='Hurra!' alt='F&oslash;dselsdag' align='left' />"; 
			$greetingString .= "Gratulerer med dagen til ";
			$greetingCounter = 0;
			
			while ($row_playerOfTheDay = $query->GetNextRow())		 {
				$yearsOld=$row_playerOfTheDay["years"];
				$firstName=$row_playerOfTheDay[firstName];
				$titleString = $yearsOld ? 
					"$firstName er ".NorwegianTextNumbers($yearsOld)." &aring;r gammel i dag!" :
					"$firstName har antagelig f&oslash;dselsdag i dag (men vi vet ikke hvor gammel $firstName er)!";
				$greetingString .= $row_playerOfTheDay['email'] ? "<a title='$titleString' href='mailto:$row_playerOfTheDay[email]?subject=Gratulerer med dagen!'>" : "<span title='$titleString'>";
				$greetingString .= "$row_playerOfTheDay[firstName] $row_playerOfTheDay[lastName]";
				$greetingString .= $row_playerOfTheDay['email'] ? "</a>" : "</span>";
				if($greetingCounter == $totalRows_playerOfTheDay -1){
					$greetingString .= "!";
				} else if ($greetingCounter == $totalRows_playerOfTheDay -2) {
					$greetingString .= " og ";
				} else {
					$greetingString .= ", ";
				}
				$greetingCounter++ ;
			}
			$greetingString .= "</strong></p><hr>";
		}
		return $greetingString;
	}
}
?>