<?php

require_once('Connections/innebandybase.php');
require_once('commonCode.inc');

$collapsedFileName = 'collapsed';
$expandedFileName = 'expanded';
$collapseExpandFunctionName = 'ExpandOrCollapse';
$getListFunctionName = "GetListAsList";
$messageBoxName = 'Message';
$collapseButtonImageName = 'MessageCollapseButton';
$filterDaysFieldName = 'daysOfMessagesToView';
$saveToCookieJavaScriptFunctionName = 'SaveToCookie';
$refreshPageFunctionName = "RefreshPage";


$collapsedString=GetCollapsedString();


$collapsedArray=Collapsed(&$collapsedString);

EmptyDeletedMessages();
echo HeaderHtml();
echo HeadingAndTopLinks();
echo MessageArray();
echo UpdateHighlightJavaScript();
echo FooterHtml();



/** Functions 

 * @return int
 * @desc Reads the number of days of messages to show
*/

function GetFilterSettings(){
	global $filterDaysFieldName;
	if(isset($_COOKIE[$filterDaysFieldName])){
		$out = $_COOKIE[$filterDaysFieldName];
	}elseif(isset($_GET[$filterDaysFieldName])){
		$out = $_GET[$filterDaysFieldName];	
	}else{
		$out = 0;	
	}
	return $out;
}

function GetCollapsedString(){
	global $collapsedFileName	;
	if(isset($_COOKIE[$collapsedFileName])){
		$out = $_COOKIE[$collapsedFileName];
	}else{
		$out = $_GET[$collapsedFileName];	
	}
	$arrayOfList = explode(",",$out);
	for ($i = 0; $i < count($arrayOfList) ; $i++){
		if ($arrayOfList[$i] == ""){
			array_splice($arrayOfList,$i);
			$i--;	
		}
	}
	$out = implode(",",$arrayOfList);
	return $out;
}

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
	global $detailsFrameName, $messageDetailsFrameName, $messageDetailsFilename;
	$player = new Player_class();
	$playerId = $player->getId();
	/*
	$out = HomeIconLink('right');
	//$out .= "<h2>Meldingsliste</h2>";
	$iconNewMsg =   "<a href='{$messageDetailsFilename}?player=$playerId' target='$detailsFrameName'>";
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
			<a href='{$messageDetailsFilename}?player=$playerId' target='$detailsFrameName'>{$iconNewMsg}</a>
			</td>
			<td style='font-size: larger'>
			<a href='{$messageDetailsFilename}?player=$playerId' target='$detailsFrameName'>
			Skrive ny melding</a>
			</td>
			</tr></table>";
			

	$out .= $iconNewMsg;
	$out .= "<a href='{$messageDetailsFilename}?player=$playerId' target='$detailsFrameName'>";
	$out .= "Skrive en ny melding";
	$out .= "</a><hl>";
		*/
	$javascript = "
		function WriteNewMsg(){
			top.$messageDetailsFrameName.location = '{$messageDetailsFilename}?player=$playerId';
		}
	";
	$out = JavaScriptWrapper($javascript);
	$out .= "<input type=\"submit\" name=\"NewMsg\" value=\"Starte ny diskusjon\" onClick=\"WriteNewMsg();\">";
	$out .= FilterLinks();
	$out .= "<hr>";
	return $out;
}

function FilterLinks(){
	global $refreshPageFunctionName;
	$currentFilterSetting = GetFilterSettings();
	$choices = array(5,14,30,0);
	$strings = array();
	$out = "\n<span class='verySmallText'>";
	foreach ($choices as $choice){
		if ($choice) {
			$text = "siste $choice dager";
			$altText = "Vis bare meldinger skrevet de siste $choice dagene. Skjul alle eldre meldinger.";
		}else{
			$text = "alle meldinger";
			$altText = "Vis alle meldinger, uansett alder.";	
		}
		if ($choice == $currentFilterSetting){
			$linkString = "\n$text\n";
		}else{
			$linkString = "\n<a href='javascript:{$refreshPageFunctionName}(0,$choice)' title='$altText' class='verySmallText'>$text</a>\n";
		}
		$strings[] = $linkString;
	}
	$out .= implode(" | ",$strings);
	$out .= "</span>";
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
	global $collapsedArray, $collapsedString, $messageBoxName, $collapseExpandFunctionName, $collapseButtonImageName;
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
		$out .= "<table id='msg{$message[id]}'><tr><td>";
		
		/* Make indent if message is child */
		for($i=0;$i<$levelIndex;$i++){
			$out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		
		/* Ends first cell */
		$out .= "\n\n</td><td";
		
		/*Ends starttag of cell */
		$out .= ">";
		
		/* +/- button for showing/hiding children */
		$collapseSymbol = "\n<img id='{$collapseButtonImageName}{$message[id]}' src='images/";
		$collapseSymbol .= $isCollapsed ? "smallPlus.gif' \nalt='Vise svar p&aring; denne'" : "smallMinus.gif' \nalt='Skjule svar p&aring; denne'";
		$collapseSymbol .= " width='8' height='8' border='0' align='middle'>";
		
		
		if (count ($message['children'])){
			$out .= "\n<a href='javascript:{$collapseExpandFunctionName}($message[id]);' \nclass='collapseSymbol'>$collapseSymbol</a>";
		}else{
			$out .= $spacerImg;
		};
		
		$out .= $isImportant ? "\n<img src='images/importantSymbol.gif' \nalt='Viktig melding' width='8' height='8' align='middle' border='0'>" : $spacerImg;
		
		$out .= "\n</td><td>";
		
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
		$out .= " ".RecentDate(strtotime($message['date']));
		
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
		if (count($message['children'])){
			$nextLevelIndex = $levelIndex;
			$styleString = ($isCollapsed) ? "style=\"display: none\";" : "style= \"display: inline\";";
			$out .= "<span id='{$messageBoxName}{$message[id]}' $styleString>";
			$out .= FormattedList($message['children'],++$nextLevelIndex);
			$out .= "</span>";
		}
	}
	return $out;
}

function QueryForMessages($replyToId,$levelIndex, $teams){
	/* Recursive query function */
	$teamFilterString = MessageQueryTeamFilterString($teams);
	$messages=array();
	$daysFilter = GetFilterSettings();
	if ($daysFilter){
		$filterQueryString = "
			AND
			messages.date > DATE_SUB(NOW(), INTERVAL $daysFilter DAY)
		";	
	}else{
		$filterQueryString = "";
	}
	$query_Msgs = "
		SELECT messages.*, players.firstName 
		FROM messages 
		LEFT JOIN players
		ON messages.author = players.id
		WHERE replyTo=$replyToId
		$teamFilterString
		$filterQueryString
		ORDER BY date DESC";
	$Msgs = new DBConnector_class($query_Msgs);
	while($row_Msgs = $Msgs->GetNextRow()){
		$thisId = $row_Msgs['id'];
		$messages[$thisId]=$row_Msgs;
		$messages[$thisId]['children']=QueryForMessages($thisId,++$levelIndex,$teams);
	}
	mysql_free_result($Msg);
	return $messages;
}

function HeaderHtml(){
	$player = new Player_class();
	$teamId=$player->getCurrentTeam();
	$pageTitle = "Meldingsliste";
	$startPage = "
	<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<html>
	<head>
	".RenderCSSLink($teamId)."
	<title>$pageTitle</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
	<meta http-equiv=\"refresh\" content=\"600\">
	".RefreshJavaScript()."
	".HighlightMessageJavaScript()."
	".ExpandCollapseJavaScriptFunction()."
	".SaveToCookieJavascript()."
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
	global $messageDetailsFilename;
	$player = new Player_class();
	$playerId = $player->getId();
	$detailsFileName=$messageDetailsFilename;
	$msgAnchorName="currentMessage";
	$out = "";
	$out .= "$detailsFileName?player=$playerId&message=$msgId#$msgAnchorName";
	return $out;
}
function MsgAStartTag($link, $titleString=""){
	global $detailsFrameName;
	return "<a href=\"$link\" target=\"$detailsFrameName\" title=\"$titleString\">";
}
function SaveToCookieJavascript(){
	global $saveToCookieJavaScriptFunctionName;
	$script = "
		function {$saveToCookieJavaScriptFunctionName}(name,value,expires){
			if (expires > 0){
				var expiresString = '; Expires='+expires.toGMTString();
			}else{
				var expiresString = '';
			}
			document.cookie = name+'='+value+expiresString;
		}
	";
	$out = JavaScriptWrapper($script);
	return $out;
}
function ExpandCollapseJavaScriptFunction(){
	global $collapseExpandFunctionName, $messageBoxName, $collapseButtonImageName, $collapsedArray;
	global $getListFunctionName, $collapsedFileName, $saveToCookieJavaScriptFunctionName;
	$collapsedList = implode(",",$collapsedArray);
	$script = "
		var collapsedArray = new Array($collapsedList);
		function {$collapseExpandFunctionName}(msgId){
			var obj = document.getElementById('$messageBoxName'+msgId);
			var img = document.getElementById('$collapseButtonImageName'+msgId);
			if (obj.style.display == 'none'){
				obj.style.display = 'inline';
				img.src = 'images/smallMinus.gif';
				img.alt = 'Skjule svarene p'+unescape('%E5')+' denne';
				SubtractFromList(msgId);
			}else{
				obj.style.display = 'none';
				img.src = 'images/smallPlus.gif';
				img.alt = 'Vise svarene p'+unescape('%E5')+' denne';
				AddToList(msgId);
			}
		}
		function AddToList(msgId){
			collapsedArray.push(msgId);
			SaveCollapsedValueToCookie();
		}
		function SubtractFromList(msgId){
			for (var i = 0 ; i < collapsedArray.length ; i++){
				if (collapsedArray[i] == msgId) {
					collapsedArray.splice(i,1);
					i--;
				}
			}
			SaveCollapsedValueToCookie();
		}
		function {$getListFunctionName}(){
			return collapsedArray.join(',');
		}
		function SaveCollapsedValueToCookie(){
			var value = {$getListFunctionName}();
			var expires = new Date();
			expires.setDate(expires.getDate()+30);
			{$saveToCookieJavaScriptFunctionName}('{$collapsedFileName}',value,expires);
		}
	";
	$out = JavaScriptWrapper($script);
	return $out;
}
function RefreshJavaScript(){
	global $collapsedString,$playerIdFieldName,$collapsedFileName, $getListFunctionName;
	global $saveToCookieJavaScriptFunctionName, $filterDaysFieldName, $refreshPageFunctionName;
	$daysToView = GetFilterSettings();
	$player = new Player_class();
	$playerId = $player->getId();
	$out .= "
	function {$refreshPageFunctionName}(msgId,daysToView){
		var msgNo, anchorTxt;
		var collapseString = {$getListFunctionName}();
		var msgNo = $refreshPageFunctionName.arguments[0];
		if (msgNo) {
			anchorTxt = '#msg'+msgNo;
		}else{
			anchorTxt = '';
		}
		if ($refreshPageFunctionName.arguments.length < 2){
			var filterString= '{$filterDaysFieldName}={$daysToView}';
		}else{
			var filterString= '{$filterDaysFieldName}='+daysToView;
			{$saveToCookieJavaScriptFunctionName}('{$filterDaysFieldName}',daysToView);
		}
		location = '$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$playerId&$collapsedFileName='+collapseString+anchorTxt+'&'+filterString;
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