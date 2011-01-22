<?php

//Elements that comprise the web page
class ElementTopMenu extends HtmlElements{
	//Navigational menu
	var $service;
	var $uniqueId;
	var $urlHere;
	var $playerId;
	var $playerWebPage;
	var $eventId;
	var $messageId;
	
	function ElementTopMenu($pageInfo, $playerInfo){
		$this->service=$pageInfo->service;
		$this->urlHere=$pageInfo->urlHere;
		$this->uniqueId=$this->GetUniqueId();
		$this->playerId=$playerInfo->playerId;
		$this->eventId=$pageInfo->eventId;
		$this->messageId=$pageInfo->messageId;
		$this->playerWebPage=$playerInfo->playerWebPage;
		$this->contentString=$this->_DefineOutput($pageInfo);
	}
	function _DefineOutput($pageInfo){
		//Temporary placeholdercode
		$out="";
		$formName="Form$this->uniqueId";
		$functionString="submit$formName";
		$formString=$this->MakeMinimalFormComplete($this->playerId,
					$this->service,$this->eventId ,$this->messageId,
					$formName ,$this->urlHere );
		$out=$formString;
		$out.="
		<table align='right' border='0' cellspacing='0' cellpadding='0'>
  			<tr>";
		foreach ($pageInfo->svcArray as $svc){
			//Entering a link to scv;
			if($pageInfo->GetSvcInMenu($svc[0])){
				$out.="<td>
					<a href='Javascript:$functionString(\"".SERVICEIDFIELDNAME."\",\"$svc[0]\");'>$svc[1]</a></td>";
				$out.="<td>|</td>";
			}
		}
  		$out.="	</tr>
		</table>
		";
		return $out;
	}
}


class ElementEventList extends HtmlElements {
	
}

class ElementEventDetails extends HtmlElements {
	
}

class ElementTopScoreList extends HtmlElements {
	function ElementTopScoreList ($teamId, $limitList=0){
		
	}
}

class ElementGoalRegistration extends HtmlElements {
		
}

class ElementEventReplyForm extends HtmlElements {
	
}

class ElementEventReplyList extends HtmlElements {
	
}

class ElementMessageList extends HtmlElements {
	
}

class ElementMessageDetails extends HtmlElements {
	
}

class ElementPlayerDetails extends HtmlElements {
	
}

class ElementPlayerDetailsForm extends HtmlElements {
	
}


class ElementTeamHeader extends HtmlElements{
	//Header on top of page
	var $teamHeaderFile;
	var $teamId;
	var $playerId;
	var $teamName;
	var $playerLastName;
	var $playerNumber;
	
	
	function ElementTeamHeader($teamId, $playerId){
		$this->_SetVariables($teamId,$playerId);
		$this->contentString="<p><h1>Lag: ".$this->teamName."</h1></p>";
		$this->contentString=$this->_MakeHtml($this->teamHeaderFile);
	}
	
	function _SetVariables($teamId,$playerId){
		$playerData = new DataPlayer($playerId);
		$playerData->MakeSelectQuery($playerId);
		
		$teamData = new DataTeam($teamId);
		
		$this->teamHeaderFile = $teamData->GetHeaderFile();
		$this->teamId = $teamId;
		$this->playerId = $playerId;
		$this->teamName = $teamData->GetLongname();
		$this->playerLastName = $playerData->GetQueriedData('lastName');
		$this->playerNumber = $playerData->GetQueriedData('number');
	}
	
	function _MakeHtml($filePath){
		$file=$this->_RetrieveHeaderFile($filePath);
		$out="<table width='100%' height='100' border='0'
			 cellpadding='0' cellspacing='0'>
			 <tr><td>";
		$out.=$this->_ExtractBody($file);
		$out.="</td></tr></table>";
		return $out;
	}
	function _RetrieveHeaderFile($filePath){
		$out =implode(file($filePath)," ");
		return $out;
	}
	function _ExtractBody($string){
		$out=$string;
		$startBodyTag=max(strpos($out,"<body"),strpos($out,"<BODY"));
		if($startBodyTag){
			$start=strpos($out,">",$startBodyTag)+1;
		}else{
			$start=0;
		}
		$endBodyTag=max(strpos($out,"</body>"),strpos($out,"</BODY>"));
		if($endBodyTag){
			$end=$endBodyTag;
		}else{
			$end=strlen($out);
		}
		return trim(substr($out,$start,$end-$start));
	}
}

class ElementChooseIdentity extends HtmlElements{
	var $playersQuery;
	var $teamId;
	var $formName;
	function ElementChooseIdentity($pageInfo){
		$this->teamId=$pageInfo->teamId;
		$this->formName="form".$this->GetUniqueId();
		$this->contentString=$this->_MakeOutput($pageInfo,$this->_MakeQuery());
		$this->messageToUser="Hvem er du?";
	}
	function _MakeQuery(){
		$playerListQueryString="SELECT players.*, membership.*
		FROM players INNER JOIN membership ON players.id = membership.player
		WHERE (((membership.team)=$this->teamId))
		ORDER BY players.lastName";
		$playerListQuery=new ToolQueryFromDB($playerListQueryString);
		return $playerListQuery;
	}
	
	function _MakeOutput($pageInfo,$playerQuery){
		$global=new ToolGlobals();
		$out="";
		$out.=$this->MakeFormStart($this->formName,$pageInfo->urlHere);
		$out.=$this->MakeFormHiddenField($global->GetFieldName('playerId'),false);
		$out.=$this->MakeFormHiddenField($global->GetFieldName('eventId'),$pageInfo->eventId);
		$out.=$this->MakeFormHiddenField($global->GetFieldName('serviceId'),$pageInfo->service);
		$out.=$this->MakeFormHiddenField($global->GetFieldName('messageId'),$pageInfo->messageId);
		
		$maxColumns=3;
		$idealPlayersPerColumn=6;
		$rowsCompleted=0;
		$allPlayers=$playerQuery->allRows;
		$columns=min($maxColumns,round(count($allPlayers)/$idealPlayersPerColumn));
		$rowsPerColumn=floor(count($allPlayers)/$columns);
		//Outher table
		$out.="<table><tr valign='top'>";
		$rowsThisColumn=$rowsPerColumn;
		for($columnIndex=0;$columnIndex<$columns;$columnIndex++){
			if((count($allPlayers)-$rowsThisColumn-$rowsCompleted)<$columns){
				$rowsThisColumn=count($allPlayers)-$rowsCompleted;
			}
			$out.="<td>";
			
			//Table start
			$out.="<table>";
			//HeaderRow
			$out.="<tr><td></td><th>Drakt</th><th>Navn</th><th>Merknad</th></tr>";

			for($playerIndex=$rowsCompleted;$playerIndex<$rowsThisColumn+$rowsCompleted;$playerIndex++){
				$player=$allPlayers[$playerIndex];
				$functionString="javascript:submit".$this->formName;
				$functionString.="(\"".$global->GetFieldName('playerId')."\",$player[id]);";
				//SingleRow
				
				$out.="<label><tr>";
				$out.="<td>";
				$out.="<input name='PlayerSelect_radio' type='radio' onClick='$functionString' value='$player[id]' id='player_$player[id]'>";
				$out.="</td>";
				$out.="<td width='15'>".$this->FormatPlayerNumber($player['number'])."</td>";
				$out.="<td>$player[firstName] $player[lastName]</td>";
				$out.="<td>$player[notes]</td>";
				$out.="</tr></label>";
			}
			//Inner table end
			$out.="</table>";
			$out.="</td>";
			$rowsCompleted+=$rowsThisColumn;
		}
		$out.="</tr></table>";
		$out.=$this->MakeFormEnd();
		
		return $out;
	}
}

class ElementFrontPage extends HtmlElements {
	function ElementFrontPage($pageInfo,$playerInfo){
		$this->contentString=$this->WriteHeader($pageInfo,$playerInfo);
		$this->contentString.=$this->NextEvent($playerInfo->nextEvent,$playerInfo);
			
	}
	function WriteHeader($pageInfo,$playerInfo){
		$playerNumber=$playerInfo->GetCurrentPlayerNumber($pageInfo);
		$out="<h1>".$pageInfo->teamName."</h1>";
		$out.="<p>".$this->FormatPlayerNumber($playerNumber)." ";
		$out.=$playerInfo->playerFullName."</p>";
		$out.="<hr/>";
		
		return $out;
	}
	
	function NextEvent ($eventId,$playerInfo){
		$event= new InfoEvents($eventId,$playerInfo);
		return $event->contentString;
	}
}

class ElementChangeIdentity{
	var $output;
	function ElementChangeIdentity(){
		$pageData=new DataPage();
		$playerId=$pageData->GetPlayerId();
		$player=new DataPlayer($playerId);
		$player->MakeSelectQuery();
		$fullName=$player->GetFullName();
		
		$html=new ToolHtml();
		$link="";
		$text="";
		$this->output=$html->Hyperlink($link,$text,false);
	}
	function ReturnString(){
		return $this->output;	
	}
}


?>