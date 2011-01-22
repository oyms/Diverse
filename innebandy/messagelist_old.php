<?php

require_once('Connections/innebandybase.php');
require_once('commonCode.inc');

$collapsedFileName = 'collapsed';
$expandedFileName = 'expanded';
$collapseExpandFunctionName = 'ExpandOrCollapse';

$collapsedString=$_GET[$collapsedFileName];

$collapsedArray=Collapsed(&$collapsedString);

EmptyDeletedMessages();
echo HeaderHtml();
echo HeadingAndTopLinks();
echo MessageArray();
echo UpdateHighlightJavaScript();
echo FooterHtml();



/* Functions */

function EmptyDeletedMessages(){
	/* Finds all deleted and expired messages */
	global $database_innebandybase, $innebandybase;	
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_Msgs = "
		SELECT id, replyTo
		FROM messages
		WHERE (((messages.deleted)=1) 
			OR ((messages.expires)<Now()))
	";
	$Msgs = new DBConnector_class($query_Msgs);
	if ($Msgs->GetNumberOfRows()){
		/* Update children */
		while($row_Msgs = $Msgs->GetNextRow()){
			$query_childrenUpdate = "
				UPDATE messages
				SET parentDeleted = 1,
					replyTo = $row_Msgs[replyTo]
				WHERE replyTo = $row_Msgs[id]
			";
			$childrenUpdate = new DBConnector_class($query_childrenUpdate);
		}
		/* Delete messages */
		$query_DeleteMessages = "
			DELETE
			FROM messages
			WHERE (((messages.deleted)=1) 
				OR ((messages.expires)<Now()))
		";
		$DeleteMessages = new DBConnector_class($query_DeleteMessages);
	}
}

function HeadingAndTopLinks(){
	global $detailsFrameName, $messageDetailsFrameName;
	$player = new Player_class();
	$playerId = $player->getId();
	/*
	$out = HomeIconLink('right');
	//$out .= "<h2>Meldingsliste</h2>";
	$iconNewMsg =   "<a href='messagedetails.php?player=$playerId' target='$detailsFrameName'>";
	$iconNewMsg .=  '<img src="images/messageIconsNewMessage.gif" 
					width="60" height="33" border="0" align="absmiddle"
					alt="Starte en diskusjon">';
	$iconNewMsg .=  "</a>";
	$iconEventList = '<img src="images/spacer.gif" 
					width="14" height="10" border="0">
					<img src="images/ikonstart.gif"
					alt="Tilbake til hendelseslisten" 
					width="36" height="36" border="0" align="absmiddle">
					<img src="images/spacer.gif" 
					width="14" height="10" border="0">';
	$iconSpacer = '<img src="images/spacer.gif" 
					width="24" height="10" border="0">';

	$out .= "
			<table><tr>
			<td style='font-size: larger'>
			<a href='eventlist.php?player=$playerId'>
			Tilbake til hendelseslisten</a>$iconSpacer 
			</td>
			<td>
			<a href='messagedetails.php?player=$playerId' target='$detailsFrameName'>{$iconNewMsg}</a>
			</td>
			<td style='font-size: larger'>
			<a href='messagedetails.php?player=$playerId' target='$detailsFrameName'>
			Skrive ny melding</a>
			</td>
			</tr></table>";
			

	$out .= $iconNewMsg;
	$out .= "<a href='messagedetails.php?player=$playerId' target='$detailsFrameName'>";
	$out .= "Skrive en ny melding";
	$out .= "</a><hl>";
		*/
	$javascript = "
		function WriteNewMsg(){
			top.$messageDetailsFrameName.location = 'messagedetails.php?player=$playerId';
		}
	";
	$out = JavaScriptWrapper($javascript);
	$out .= "<input type=\"submit\" name=\"NewMsg\" value=\"Starte ny diskusjon\" onClick=\"WriteNewMsg();\"><hr>";
	return $out;
}

function Collapsed($listOfCollapsed){
	global $expandedFileName;
	$collapsedArray=explode(",",$listOfCollapsed);
	$indexOfExpanded=array_search($_GET[$expandedFileName],$collapsedArray);
	if(! ($indexOfExpanded===false)){
		array_splice($collapsedArray,$indexOfExpanded,1);
	}
	$listOfCollapsed=implode(",",$collapsedArray);
	return $collapsedArray;
}

function MessageArray(){
	$replyToId = 0;
	$levelIndex = 0;
	$player = new Player_class();
	$teams = $player->getTeams();
	$messages = QueryForMessages($replyToId,$levelIndex,$teams);
	return FormattedList($messages,$levelIndex);
}

function CollapseLink($msgId, $collapse=true){
	global $collapsedFileName,$expandedFileName,$collapsedString,$playerIdFieldName;
	$player = new Player_class();
	$playerId = $player->getId();
	$newCollapseString = $collapsedString;
	if ($collapse){
		$newCollapseString .= ",$msgId";
		$expandString = "";
	}else{
		$expandString = "&".$expandedFileName."=$msgId";
	}
	$linkString = "$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$playerId&$collapsedFileName=$newCollapseString$expandString";
	return $linkString;
}

function IsBroadcast ($teamsString) {
	$out = false;
	$player = new Player_class();
	if ($player->getHasMultipleMemberships()){
		if (count(explode(",",$teamsString)) > 1){
			$out = true;	
		}	
	}
	return $out;
}

function MessageFromOneOtherTeam($teamsString){
	$out = false;
	$player = new Player_class();
	if ($player->getHasMultipleMemberships()){
		$team = new Team_class();
		$thisTeamId = $team->get_id();
		$teamsArray = explode(",",$teamsString);
		if (count($teamsArray)==1){
			if ($teamsArray[0] != $thisTeamId){
				$out = true;	
			}
		}
	}
	return $out;
}

function FormattedList($messages,$levelIndex){
	global $collapsedArray, $collapsedString;
	$player = new Player_class();
	$team = new Team_class();
	$spacerImg = "<img src='images/spacer.gif' width='8' height='8' border='0'>";
	$out = '';
	foreach ($messages as $message){
		/* Checks if children is hidden */
		$isCollapsed = in_array($message['id'],$collapsedArray);
		/* Checks if message is important */
		$isImportant = $message['important'];
		/* Checks if message is sent by self */
		$sentBySelf = ($message['author'] == $player->getId());
		/* Checks if message is sent by trainer */
		$sentByTrainer = $team->isTrainer($message['author']);
		/* Checks if message is visible in multiple teams */
		$broadcast = IsBroadcast($message['teams']);
		/* Checks if message is visible only in one of the other teams */
		$foreignMessage = MessageFromOneOtherTeam($message['teams']);
		
		/* Make anchor */
		$out .= "<a name='msg$message[id]'>";
		
		/* Start drawing table */
		$out .= "<table id='msg$message[id]'><tr><td>";
		
		/* Make indent if message is child */
		for($i=0;$i<$levelIndex;$i++){
			$out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		
		/* Ends first cell */
		$out .= "</td><td";
		
		/*Ends starttag of cell */
		$out .= ">";
		
		/* +/- button for showing/hiding children */
		$collapseSymbol = "<img src='images/";
		$collapseSymbol .= $isCollapsed ? "smallPlus.gif' alt='Vise svar p&aring; denne'" : "smallMinus.gif' alt='Skjule svar p&aring; denne'";
		$collapseSymbol .= " width='8' height='8' border='0' align='middle'>";
		
		
		if (count ($message['children'])){
			$out .= "<a href='".CollapseLink($message['id'], (! $isCollapsed))."' class='collapseSymbol'>$collapseSymbol</a>";
		}else{
			$out .= $spacerImg;
		};
		
		$out .= $isImportant ? "<img src='images/importantSymbol.gif' alt='Viktig melding' width='8' height='8' align='middle' border='0'>" : $spacerImg;
		
		$out .= "</td><td>";
		
		/* Format based on author and importance */
		if ($sentBySelf){
			$out .= "<em>";
		}else if ($sentByTrainer){
			$out .= "<span class=messageFromTrainer>";
		}
				
		/* Write the message itself */
		$out .= MsgAStartTag(MessageLink($message['id']), StringCleaning($message['messageText'], false));
		
		$out .= $message['subject'];
		$out .= "</a> ";
		
		/* Author */
		$out .= $sentBySelf ? "" : "<em>$message[firstName]</em>";
		if ($foreignMessage){
			$teamsData = $player->GetTeamsData();
			$out .= "(".$teamsData[$message['teams']]['shortName'].")";
		}
		
		/* Date */
		$out .= " ".norDate($message['date']);
		
		$out .= $broadcast ? " [flere lag] " : "";
		
		/* End format based on author and importance */
		if ($sentBySelf){
			$out .= "</em>";
		}else if ($sentByTrainer){
			$out .= "</span>";
		}
		
		/* Close table tag */
		$out .= "</td></tr></table>\r\n";
		
		/* Close anchor tag */
		$out .= "</a>";
		
		/* Get children */
		if (count($message['children']) && (! $isCollapsed)){
			$nextLevelIndex = $levelIndex;
			$out .= FormattedList($message['children'],++$nextLevelIndex);
		}
	}
	return $out;
}

function QueryForMessages($replyToId,$levelIndex, $teams){
	/* Recursive query function */
	global $database_innebandybase, $innebandybase;
	$teamFilterString = MessageQueryTeamFilterString($teams);
	$messages=array();
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_Msgs = "
		SELECT messages.*, players.firstName 
		FROM messages 
		LEFT JOIN players
		ON messages.author = players.id
		WHERE replyTo=$replyToId
		$teamFilterString
		ORDER BY date DESC";
		
	$Msgs = mysql_query($query_Msgs, $innebandybase) or die(mysql_error());
	while($row_Msgs = mysql_fetch_assoc($Msgs)){
		$thisId = $row_Msgs['id'];
		$messages[$thisId]=$row_Msgs;
		$messages[$thisId]['children']=QueryForMessages($thisId,++$levelIndex,$teams);
	}
	mysql_free_result($Msg);
	return $messages;
}

function HeaderHtml(){
	$pageTitle = "Meldingsliste";
	$startPage = "
	<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<link href=\"innebandy.css\" rel=\"stylesheet\" type=\"text/css\">
	<html>
	<head>
	<title>$pageTitle</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
	<meta http-equiv=\"refresh\" content=\"600\">
	".RefreshJavaScript()."
	".HighlightMessageJavaScript()."
	</head>
	<body>
	";
	return $startPage;
}

function FooterHtml(){
	$endPage = "
	</body>
	</html>
	";
	return $endPage;
}

function MessageLink($msgId){
	$player = new Player_class();
	$playerId = $player->getId();
	$detailsFileName="messagedetails.php";
	$msgAnchorName="currentMessage";
	$out = "";
	$out .= "$detailsFileName?player=$playerId&message=$msgId#$msgAnchorName";
	return $out;
}
function MsgAStartTag($link, $titleString=""){
	global $detailsFrameName;
	return "<a href=\"$link\" target=\"$detailsFrameName\" title=\"$titleString\">";
}

function RefreshJavaScript(){
	global $collapsedString,$playerIdFieldName,$collapsedFileName;
	$player = new Player_class();
	$playerId = $player->getId();
	$out .= "
	function RefreshPage(){
		var msgNo, anchorTxt;
		var msgNo = RefreshPage.arguments[0];
		if (msgNo) {
			anchorTxt = '#msg'+msgNo;
		}else{
			anchorTxt = '';
		}
		location = '$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$playerId&$collapsedFileName=$collapsedString'+anchorTxt;
	}";
	$out = JavaScriptWrapper($out);
	return $out;
}

function HighlightMessageJavaScript(){
	$script = "";
	$script .= "
		var currentHighlight = 0;
		function Highlight(messageId){
			if(currentHighlight){
				var oldRow = document.getElementById('msg'+currentHighlight);
				if (oldRow){
					oldRow.style.backgroundColor = '';
				}
			}
			currentHighlight = messageId;
			var newRow = document.getElementById('msg'+messageId);
			if (newRow){
				newRow.style.backgroundColor = '#eeeeee';
			}
			
		}
	";
	$out = JavaScriptWrapper($script);
	return $out;
}
function UpdateHighlightJavaScript(){
	global $detailsFrameName;
	$script = "
		// Update highlighting
		if(top.$detailsFrameName.GetCurrentMsg){
			Highlight(top.$detailsFrameName.GetCurrentMsg());
		}
	";
	return JavaScriptWrapper($script);
}
?>