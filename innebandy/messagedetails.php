<?php

require_once('Connections/innebandybase.php');
require_once('commonCode.inc');

/* global variables */
$deleteButtonName="delete_btn";
$deleteHiddenName="deleteMsg_hidden";
$submitButtonName="submit_btn";
$subjectFieldName="subject_txt";
$replyToFieldName="replyTo_hidden";
$expiresFieldName="expires_cmb";
$importantFieldName="important_chk";
$bodyFieldName="messageText_txt";
$headerJavaScript="";

UpdateLastLogin();
RegisterForm();

echo(HeaderHtml());
echo(FormerMessages());
echo(CurrentMessage());
echo(MessageForm());
echo(LaterMessages());
echo(FooterHtml());




/* ---------
   Classes 
   ---------*/
   


class Message_class{
	var $id, $dateVal, $expires, $teams, $subject, $messageText;
	var $replyTo, $deleted, $expiredOrDeleted, $important, $parentDeleted;
	var $authorId, $authorFirstName, $authorLastName, $authorEmail;
	var $authorNumber, $authorTeamTrainer, $authorNoMessageByEmail;
	var $visible, $repliesArray, $checkedForChildren;
	function Message_class($id = false){
		if($id){
			$this->QueryValues($id);
		}
	}
	function InsertValues(
				$id, $dateVal, $expires, $teams, $subject, $messageText, 
				$replyTo, $deleted, $expiredOrDeleted, $important, $authorId, $authorFirstName, 
				$authorLastName, $authorEmail, $authorNumber, $parentDeleted,
				$authorTeamTrainer, $authorNoMessageByEmail){
		$this->id=$id;
		$this->dateVal=$dateVal;
		$this->expires=$expires;
		$this->teams=$teams;
		$this->subject=$subject;
		$this->messageText=$messageText;
		$this->replyTo=$replyTo;
		$this->deleted=$deleted;
		$this->expiredOrDeleted=$expiredOrDeleted;
		$this->important=$important;
		$this->authorId=$authorId;
		$this->authorFirstName=$authorFirstName;
		$this->authorLastName=$authorLastName;
		$this->authorEmail=$authorEmail;
		$this->authorNumber=$authorNumber;
		$this->authorTeamTrainer=$authorTeamTrainer;
		$this->authorNoMessageByEmail=$authorNoMessageByEmail;
		$this->parentDeleted=$parentDeleted;
	}
	function InsertValuesByArray($row_Msg){
		$this->id=$row_Msg['msgId'];
		$this->dateVal=$row_Msg['date'];
		$this->expires=$row_Msg['expires'];
		$this->teams=$row_Msg['teams'];
		$this->subject=$row_Msg['subject'];
		$this->messageText=$row_Msg['messageText'];
		$this->replyTo=$row_Msg['replyTo'];
		$this->deleted=$row_Msg['deleted'];
		$this->expiredOrDeleted=$row_Msg['expiredOrDeleted'];
		$this->important=$row_Msg['important'];
		$this->authorId=$row_Msg['playerId'];
		$this->authorFirstName=$row_Msg['firstName'];
		$this->authorLastName=$row_Msg['lastName'];
		$this->authorEmail=$row_Msg['email'];
		$this->authorNumber=$row_Msg['number'];
		$this->authorTeamTrainer=$row_Msg['teamTrainer'];
		$this->authorNoMessageByEmail=$row_Msg['noMessageByEmail'];
		$this->parentDeleted=$row_Msg['parentDeleted'];
	}
	function QueryValues($id){
		global $database_innebandybase, $innebandybase;
		mysql_select_db($database_innebandybase, $innebandybase);
		$query_Msg = $this->_getQuery($id,"current");
		$Msg = mysql_query($query_Msg, $innebandybase) or die(mysql_error());
		//$row_Msg = $this->_teamFilter(mysql_fetch_assoc($Msg));
		$this->visible = true;
		$row_Msg = mysql_fetch_assoc($Msg);
		$this->InsertValuesByArray($row_Msg);
		mysql_free_result($Msg);
	}
	function _teamFilter($row_Msg){
		$out = false;
		if ($row_Msg){
			/* Filter teams */
			$player = new Player_class();
			$playerTeams = $player->getTeams();
			$messageTeams = explode(",",$row_Msg['teams']);
			if(count(array_diff($playerTeams,$messageTeams)) < count($playerTeams)){
				$out = $row_Msg;
				$this->visible = true;
			}
		}
		return $out;
	}
	function _getQuery($id, $searchType="current", $maxRows=100, $startAtRow=1){
		/* Get current team */
		$currentTeam = new Team_class();
		$teamId = $currentTeam->get_id();
		$player = new Player_class();
		$teams = $player->getTeams();
		
		$teamsQueryString = MessageQueryTeamFilterString($teams);
		
		/* Make query string */
		$query_Msg = "
			SELECT 	messages.*, players.*, membership.*,
					((expires <= NOW())OR(messages.deleted)) AS expiredOrDeleted,
					messages.id AS msgId, players.id AS playerId
			FROM messages 
			LEFT JOIN players ON messages.author = players.id
			LEFT JOIN membership ON players.id = membership.player
			";
		switch ($searchType){
			case "current":
				$query_Msg .= "WHERE (messages.id=$id $teamsQueryString)";
			break;
			case "next":
				$query_Msg .= "WHERE (messages.replyTo=$id $teamsQueryString)";
			break;
		}
		$query_Msg .= "
			GROUP BY msgId ";
			
		return $query_Msg;
	}
	function FindPreviousMessage(){
		$out = false;
		if ($this->id && $this->replyTo){
			global $database_innebandybase, $innebandybase;
			mysql_select_db($database_innebandybase, $innebandybase);
			$query_Msg=$this->_getQuery($this->replyTo,"current");
			$Msg = mysql_query($query_Msg, $innebandybase) or die(mysql_error());
			$row_Msg = $this->_teamFilter(mysql_fetch_assoc($Msg));
			if ($row_Msg){
				$out = new Message_class();
				$out->InsertValuesByArray($row_Msg);
			}
			mysql_free_result($Msg);
		}
		return $out;
	}
	function FindRepliesToMessage($maxNumberOfMsg=10000,$firstMsg=1){
		$messages = array();
		if($this->id){
			global $database_innebandybase, $innebandybase;
			mysql_select_db($database_innebandybase, $innebandybase);
			$query_Msg=$this->_getQuery($this->id,"next", $maxNumberOfMsg,$firstMsg);
			$Msg = mysql_query($query_Msg, $innebandybase) or die(mysql_error());
			while ($row_Msg=$row_Msg = $this->_teamFilter(mysql_fetch_assoc($Msg))){
				$newMsg = new Message_class();
				$newMsg->InsertValuesByArray($row_Msg);
				$messages[] = $newMsg;
			}
		}
		$this->repliesArray = $messages;
		$this->checkedForChildren = true;
		return $messages;
	}
	function NumberOfChildren(){
		if($this->checkedForChildren){
			$out = count($this->repliesArray);
		}else{
			$out = count ($this->FindRepliesToMessage());
		}
		return $out;
	}
	function getDate($type="friendly", $dateField="create"){
		$out = "";
		$dateVal="";
		switch ($dateField){
			case "create":
				$dateVal=$this->dateVal;
			break;
			case "expire":
				$dateVal=$this->expires;
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
	function getDaysToExpire(){
		$expireDate = $this->getDate("timestamp","expire");
		$rightNow = time();
		$out = floor(($expireDate-$rightNow)/(60*60*24));
		return $out;	
	}
	function getTeams(){ 
		$out = explode(",",$this->teams);
		return $out;
	}
	function getReplyTo(){
		return $this->replyTo;
	}
	function getIsImportant(){
		return $this->important;	
	}
	function getTextBody(){
		return $this->messageText;
	}
	function getIsWrittenBySelf(){
		$out = false;
		$player = new Player_class();
		if($this->authorId == $player->id){
			$out = true;
		}
		return $out;
	}
	function getIsVisible(){
		return $this->visible;
	}
	function getParentDeleted(){
		return $this->parentDeleted;
	}
	function getAuthor($type="friendly"){
		$out = false;
		if ($this->authorId){
			switch ($type){
				case "friendly":
					if($this->getIsWrittenBySelf()){
						$out ="deg";
					}else if($this->authorTeamTrainer){
						$out = "oppmann ".$this->authorFirstName;
					}else{
						$out = $this->authorFirstName." ".$this->authorLastName;
					}
				break;
				case "email":
					$player = new Player_class();
					if($this->authorId == $player->id){
						$out ="";
					}else{
						$out = $this->authorEmail;
					}
				break;
				case "id":
					$out = $this->authorId;
				break;
				case "fullemailaddress":
					$out = $this->authorFirstName." ".$this->authorLastName." <".$this->authorEmail.">";
				break;
			}
		}
		return $out;
	}
	function IconTag($imgUrl){
		$out = "<img src='$imgUrl' width='60' height='33' border='0'>";	
		return $out;
	}
	function _makeNavLinks($playerId, $current = false){
		global $playerIdFieldName;
		$msgId = $this->id;
		$formerMsg = $this->replyTo > 0;
		$separator = '<td align="center" valign="middle">|</td>';
		$out = "<table><tr>";
		$link = $current ? "#messageForm" : "$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$playerId&message=$msgId#messageForm";
		$out .= $this->_makeLinkInCell("Kommenter denne",$link, "images/messageIconsComment.gif");
		$out .= $separator;
		if ($formerMsg) {
			$link = $current ? "#previousMessages" : "$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$playerId&message=$msgId#previousMessages";
			$out .= $this->_makeLinkInCell("Se meldingene denne kommenterer",$link,"images/messageIconsParents.gif");
			$out .= $separator;			
		}
		if ($this->NumberOfChildren()){
			$link = $current ? "#otherReplies" : "$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$playerId&message=$msgId#otherReplies";
			$out .= $this->_makeLinkInCell("Se kommentarer til denne",$link,"images/messageIconsChildren.gif");
			$out .= $separator;
		}
		$out .= $this->_makeLinkInCell("Starte ny diskusjon","$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$playerId#messageForm", "images/messageIconsNewMessage.gif");
		$out .= "</tr></table>";
		return $out;
	}
	function _makeLinkInCell($text, $link, $iconFile=false){
		$icon = $iconFile ? ($this->IconTag($iconFile)) : "";
		$out = "<td align='center' valign='middle'><a href='$link'>$icon<br>$text</a></td>";
		return $out;
	}
	function WriteHtml($current=true){
		global $deleteButtonName,$deleteHiddenName,$playerIdFieldName;
		$player= new Player_class();
		$ownMsg = $this->getIsWrittenBySelf();
		$deletable = ($ownMsg || $player->getIsTrainer());
		$id = $player->getId();
		$email = $this->getAuthor("email");
		
		/* Determine string for css class of body txt */
		if ($current){
			$classString = " class=currentMessageCell";
		}else{
			$classString = " class=otherMessageCell";
		}
		
		/* Is message deleted? (Not likely) */
		if($this->getIsDeleted()){
			$out = "<p class=notCurrentMessage>[Slettet melding: <em>$this->subject</em> skrevet av ".$this->getAuthor("friendly")."]</p><hr>";
		}else{
			/* Write message */
			$class = $current ? "currentMessage" : "notCurrentMessage";
			/* Heading */
			$out .= "
					<a name='message$this->id'></a>
					<span class=$class>";
			$out .= $current ? "<h2>" : "<h3>";
			$out .= $current ? "" : 
				("<a href='".$_SERVER['SCRIPT_NAME']."?$playerIdFieldName=".$id."&message=".$this->id."#currentMessage'>") ;
			$out .= $this->subject ;
			$out .= $current ? "" : "</a>" ;
			$out .= $current ? "</h2>" : "</h3>";
			
			/* Starttable */
			$out .= "
				<table width='96%'><tr><td>
				";
						
			/* Author and date */
			$out .= "<tr><td>";
				$out .= "<address class=smallCenteredText>";
				$out .= $this->important ? 
					"<span class=importantMessage>Viktig melding! </span>" : "";
				$out .= "Skrevet av ";
				$out .= $email ? "<a href='mailto:$email'>" : "";
				$out .= $this->getAuthor("friendly");
				$out .= $email ? "</a>" : "";
				$out .= " ".$this->getDate("friendly")."</address>";
			$out .= "</td></tr>";
			
			/* Message is deleted */
			if ($this->getParentDeleted()){
				$out .= "<tr><td>";
				$out .= "<p><em>Meldingen som denne er 
						et svar p&aring; er slettet.</em></p>";
				$out .= "</td></tr>";
			}
			
			/* Message body */
			$out .= "
				<tr><td$classString";
			$out .= $this->important ? " id=\"importantMessageCell\">" : ">";
			$out .= "
				".str_replace("\n","<br>",$this->messageText)."
				";
			$out .= "</td></tr>
			";
			
			/* expire date */
			$out .= "<tr><td>";
			$out .= "<p class=smallCenteredText>
				Meldingen utg&aring;r ".
				$this->getDate("friendly","expire").".</p>";
			$out .= "</td></tr>";
			
			/* If message is deletable, write delete-button */
			if ($deletable){
				$out .= "<tr><td>";
				
				$out .= "<form action =
					'$_SERVER[SCRIPT_NAME]?$playerIdFieldName=$id#messageForm' 
					method='post' 
					name='msg$this->id_form' 
					id='msg$this->id_form'>";
				$out .= "
					<br>
					<input name='$deleteHiddenName' 
						type='hidden' 
						id='deleteMsg_hidden' 
						value='$this->id'>
					<input name='$deleteButtonName' 
						type='submit' 
						id='delete_btn' 
						value='Slett melding'>
					";
							/* Close form tag if msg is deletable */
				$out .= "</form>";
				$out .= "</td></tr>";
			}
			
			/* Make navigational links */
			$out .= "<tr><td>";
			$out .= $this->_makeNavLinks($id, $current);
			$out .= "</td></tr>";
			
			/* Close table */
			$out .= "
				</table>
				";
			
			$out .= "<hr>";
			$out .= "</span>
					";
		}
		return $out;
	}
	function getSubject(){
		return $this->subject;
	}
	function getIsDeleted(){
		return $this->expiredOrDeleted;
	}
}
class CurrentMessage_class extends Message_class {
	var $noCurrent;
	function CurrentMessage_class(){
		$this->Message_class();
		$this->id = & $GLOBALS['_transient']['static']['currentmessage_class']->id;
		$this->dateVal=& $GLOBALS['_transient']['static']['currentmessage_class']->dateVal;
		$this->expires=& $GLOBALS['_transient']['static']['currentmessage_class']->expires;
		$this->teams=& $GLOBALS['_transient']['static']['currentmessage_class']->teams;
		$this->subject=& $GLOBALS['_transient']['static']['currentmessage_class']->subject;
		$this->messageText=& $GLOBALS['_transient']['static']['currentmessage_class']->messageText;
		$this->replyTo=& $GLOBALS['_transient']['static']['currentmessage_class']->replyTo;
		$this->deleted=& $GLOBALS['_transient']['static']['currentmessage_class']->deleted;
		$this->parentDeleted=& $GLOBALS['_transient']['static']['currentmessage_class']->parentDeleted;
		$this->important=& $GLOBALS['_transient']['static']['currentmessage_class']->important;
		$this->authorId=& $GLOBALS['_transient']['static']['currentmessage_class']->authorId;
		$this->authorFirstName=& $GLOBALS['_transient']['static']['currentmessage_class']->authorFirstName;
		$this->authorLastName=& $GLOBALS['_transient']['static']['currentmessage_class']->authorLastName;
		$this->authorEmail=& $GLOBALS['_transient']['static']['currentmessage_class']->authorEmail;
		$this->authorNumber=& $GLOBALS['_transient']['static']['currentmessage_class']->authorNumber;
		$this->authorTeamTrainer=& $GLOBALS['_transient']['static']['currentmessage_class']->authorTeamTrainer;
		$this->authorNoMessageByEmail=& $GLOBALS['_transient']['static']['currentmessage_class']->authorNoMessageByEmail;
		$this->visible=& $GLOBALS['_transient']['static']['currentmessage_class']->visible;
		$this->repliesArray=& $GLOBALS['_transient']['static']['currentmessage_class']->repliesArray;
		$this->checkedForChildren=& $GLOBALS['_transient']['static']['currentmessage_class']->checkedForChildren;
		if (! $this->id){
			$this->_retrieveId();
			if (! $this->noCurrent){
				$this->QueryValues($this->id);
			}
		}
	}
	function setId($newId){
		$this->QueryValues($newId);
		$this->noCurrent=true;
	}
	function _retrieveId(){
		global $messageIdFieldName;
		if($_GET[$messageIdFieldName]){
			$this->id=$_GET[$messageIdFieldName];
			$this->noCurrent = false;
		}else if($_POST[$messageIdFieldName]){
			$this->id=$_POST[$messageIdFieldName];
			$this->noCurrent = false;
		}else{
			$this->id=false;
			$this->noCurrent=true;
		}
	}
	function getIsCurrent(){
		return ((! $this->noCurrent) && $this->getIsVisible());
	}
	function getId(){
		return $this->id;
	}
	function getTeamId(){
		$teamId=0;
		$teams=$this->getTeams();
		if($this->id&&count($teams)>0){
			$teamId=$teams[0];
		}else{
			$currentTeam = new Team_class();
			$teamId = $currentTeam->get_id();
		}
		return $teamId;
	}
}
/* ---------
   Functions 
   ---------*/
function FormerMessages(){
	$out = "";
	$messages = array();
	$curMsg = new CurrentMessage_class();
	if($curMsg->getIsCurrent()){
		$maxNumberOfMsg = 10;
		$numberOfMsgIndex = 1;
		$currentId = $curMsg->getId();
		/* Find previous */
		while (($nextMsg = $curMsg->FindPreviousMessage()) && ($numberOfMsgIndex++ < $maxNumberOfMsg)){
			$curMsg = $nextMsg;
			$messages[]=$curMsg;
		}
		/* Output previous */
		if (count($messages)){
			$out.="<a name='previousMessages'><h2>Tidligere meldinger i denne diskusjonen</h2>";
			for ($i=count($messages)-1; $i >=0; $i--){
				$out .= $messages[$i]->WriteHtml(false);
			}
			$out.="</a>";
		}
	}
	return $out;
}

function LaterMessages(){
	$out = "";
	$curMsg = new CurrentMessage_class();
	if($curMsg->getIsCurrent()){
		$maxNumberOfMsg = 10;
		$firstMsg = 1;
		$messages = $curMsg->FindRepliesToMessage($maxNumberOfMsg,$firstMsg);
		if (count($messages)){
			$out.="<a name='otherReplies'><h2>Andre kommentarer til meldingen</h2>";
			for ($i=0; $i < count($messages); $i++){
				$out .= $messages[$i]->WriteHtml(false);
			}
			$out.="</a>";
		}
	}
	return $out;
}
function CurrentMessage(){
	$out="";
	$curMsg = new CurrentMessage_class();
	if (($curMsg->getIsCurrent()) &&($curMsg->getIsVisible())){
		$out .= "<a name='currentMessage'></a>";
		$out .= $curMsg->WriteHtml(true);
		$out .= "";
	}
	return $out;
}

function HeaderHtml(){
	global $headerJavaScript;
	$curMsg = new CurrentMessage_class();
	$teamId=$curMsg->getTeamId();
	$headerJavaScript .= $curMsg->getIsCurrent() ? HighlightMessageJavaScript() : "";
	$headerJavaScript .= GetCurrentMsgJavaScript ();
	$pageTitle = $curMsg->getSubject();
	$pageTitle = $pageTitle == "" ? "Melding" : $pageTitle;
	$startPage = "
	<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
	<html>
	<head>
	".RenderCSSLink($teamId)."
	<title>$pageTitle</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
	$headerJavaScript
	</head>
	<body>
	";
	return $startPage;
}


function RefreshMsgListJavaScript($msg=false){
	global $messageListFrameName;
	$script = "";
	$script .= "
		if(top.$messageListFrameName.RefreshPage){
			top.$messageListFrameName.RefreshPage();
		}
	";
	$out = JavaScriptWrapper($script);
	return $out;
}
function HighlightMessageJavaScript (){
	global $messageListFrameName;
	$curMsg = new CurrentMessage_class();
	$msgId = $curMsg->getId();
	$script = "
		if(top.$messageListFrameName.Highlight){
				top.$messageListFrameName.Highlight($msgId);
			}";
	return JavaScriptWrapper($script);
}
function GetCurrentMsgJavaScript (){
	$curMsg = new CurrentMessage_class();
	$curMsgId = $curMsg->getId();	
	$script = "
		function GetCurrentMsg(){
			return $curMsgId;
		}
	";
	return JavaScriptWrapper($script);
}

function FooterHtml(){
	$endPage = "
	</body>
	</html>
	";
	return $endPage;
}
function MakeTeamController(){
	global $database_innebandybase, $innebandybase,$teamsFieldName;
	$teamsFieldNameArray = $teamsFieldName."[]";
	$out = "";
	$curMsg = new CurrentMessage_class();
	$player = new Player_class();
	$playerTeams = $player->getTeams();
	$msgTeams = $curMsg->getTeams();
	if(! $curMsg->getIsCurrent()){
		$possibleTeams=$playerTeams;
	}else{
		$possibleTeams = array_intersect($msgTeams,$playerTeams);
	}
	switch (count($possibleTeams)){
		case 0:
			$out = false;
		break;
		case 1:
			$out = "<input name='$teamsFieldNameArray' type='hidden' id='teams_hidden' value='$possibleTeams[0]'>";
		break;
		default:
			mysql_select_db($database_innebandybase, $innebandybase);
			$teamsString = implode(",",$possibleTeams);
			$query_Teams = "SELECT id, longName FROM teams WHERE id IN ($teamsString)";
			$Teams = mysql_query($query_Teams,$innebandybase) or die(mysql_error());
			$out .= "<tr><td>Synlig for: <br>";
			$out .= "<select name='$teamsFieldNameArray' size='".min(count($possibleTeams),4)."' multiple id='teams_cmb'>";
			if (! $curMsg->getIsCurrent()){
				$selectedValues = array($player->getCurrentTeam()); 
			}else{
				$selectedValues = $msgTeams;
			}
			while ($row_Teams=mysql_fetch_assoc($Teams)){
				$selected = (in_array($row_Teams['id'],$selectedValues)) ? " selected" : "";
				$out .= "<option value='$row_Teams[id]'$selected>$row_Teams[longName]</option>";
			}
			$out .= "</select></td><tr>";
		break;
	}
	return $out;
}
function MessageForm(){
	global $submitButtonName;
	global $subjectFieldName,$replyToFieldName,$expiresFieldName,$importantFieldName,$bodyFieldName;

	$out = "";
	$player = new Player_class();
	$playerId = $player->getId();
	$curMsg = new CurrentMessage_class();
	$heading = $curMsg->getIsCurrent() ? "Kommentere<br><em style='font-size: smaller'>&laquo;".$curMsg->getSubject()."&raquo;</em>" : "Starte diskusjon";
	$teamController = MakeTeamController();
	$replyTo = $curMsg->getIsCurrent() ? $curMsg->getId() : 0;
	$newSubject = $curMsg->getIsCurrent() ? ">".$curMsg->getSubject() : "";
	$resetIdentButton = ResetIdentityButton();
	$newDiscussionLink = $curMsg->getIsCurrent() ? "<a href='$_SERVER[SCRIPT_NAME]?player=$playerId#messageForm' class = 'smallCenteredText'>Klikk her for &aring; starte en ny diskusjon i stedet for &aring; svare p&aring; denne</a>" : "";
	$actionLink = "$_SERVER[SCRIPT_NAME]?player=$playerId#currentMessage";
	$homeLink = HomeIconLink("right");
	if ($curMsg->getIsCurrent()){
		$formerMsgExpire = $curMsg->getDaysToExpire();
		$expireControlString = "
			<option value=\"$formerMsgExpire\" selected>samtidig som meldingen over</option>
			<option value=\"366\">om et &aring;r</option>
		";	
	}else{
		$expireControlString = "
			<option value=\"366\" selected>om et &aring;r</option>
		";	
	}
	$out .= <<<EOF
	
<form name="message_form" method="post" action="">
	  $homeLink
	  <h2><a name="messageForm"></a>$heading</h2>
	  <p>$resetIdentButton</p>
	  $newDiscussionLink
	  <table width="90%"  border="0">
		<tr valign="middle">
		  <td colspan="3">
		  	<label>Emne:<br>
		  		<input name="$subjectFieldName" 
					value="$newSubject" 
					type="text" 
					id="subject_txt2" 
					tabindex="1" 
					size="50" 
					maxlength="50">
		  	</label>
		  </td>
		</tr>
		<tr valign="middle">
		  <td width="35%"><label>Denne meldingen slettes<br>
			<select name="$expiresFieldName" id="expires_cmb">
			  $expireControlString
			  <option value="183">om et halvt &aring;r</option>
			  <option value="60">om noen m&aring;neder</option>
			  <option value="28">om fire uker</option>
			  <option value="14">om fjorten dager</option>
			  <option value="7">om en uke</option>
			  <option value="3">om tre dager</option>
			  <option value="1">i morgen</option>
			</select>
	</label>
		  </td>
		  <td width="6%">&nbsp;</td>
		</tr>
		$teamController
		<tr valign="middle">
		  <td colspan="3"><textarea name="$bodyFieldName" cols="50" rows="10" wrap="physical" tabindex="2"></textarea></td>
		</tr>
		<tr valign="middle">
		  <td colspan="2" valign="middle"><label class="smallCenteredText">
		  <input name="$importantFieldName" type="checkbox" id="important_chk" value="1">
				Dette er en viktig melding</label></td>
		  <td align="right">
		  <input name="cancel_button" type="button" id="cancel_btn" value="Avbryt" onClick = "reset();">		  
		  <input name="$submitButtonName" type="submit" id="Submit_btn" value="Send melding" tabindex="3">
		  <input name="$replyToFieldName" type="hidden" id="replyTo_hidden" value="$replyTo">
		  </td>
		</tr>
  </table>
</form>
<hr>
		
EOF
;
	return $out;
}
function RegisterForm(){
	global $deleteButtonName,$deleteHiddenName,$submitButtonName,$database_innebandybase, $innebandybase;
	global $subjectFieldName,$replyToFieldName,$expiresFieldName,$importantFieldName,$bodyFieldName,$teamsFieldName;
	global $headerJavaScript;

	/* register form data and submitting to db */
	/* delete message */
	if ($_POST[$deleteButtonName]){
		$msgId=$_POST[$deleteHiddenName];
		mysql_select_db($database_innebandybase, $innebandybase);
		$query_DelMsg="UPDATE messages SET deleted=1 WHERE id=$msgId";
		$DelMsg = mysql_query($query_DelMsg, $innebandybase) or die(mysql_error());
		$headerJavaScript = RefreshMsgListJavaScript();
	}else if($_POST[$submitButtonName]){
		$curMsg = new CurrentMessage_class();
		$player = new Player_class();
		$author = $player->getId();
		$authorName = $player->getName("First");
		$subject = safeHTML($_POST[$subjectFieldName]);
		$subject = $subject == "" ? "Melding fra $authorName" : $subject;
		$replyTo = $_POST[$replyToFieldName] ? $_POST[$replyToFieldName] : 0;
		$expires = $_POST[$expiresFieldName];
		$important = $_POST[$importantFieldName] ? 1: 0;
		$body = safeHTML($_POST[$bodyFieldName]);
		
		// Finds the teams-field of the new message
		if (count($_REQUEST[$teamsFieldName])){
			$teams = implode(",",$_REQUEST[$teamsFieldName]);
		}else{
			$playerTeams = $player->getTeams();
			$commonTeams = array_intersect($curMsg->getTeams(),$playerTeams);
			$teams = implode (",",$commonTeams);
		}
		
		$msgExists = MessageExcists($author, $teams, $subject, $body);
		
		if (! $msgExists){
			mysql_select_db($database_innebandybase, $innebandybase);
			$query_InsertMsg="
				INSERT INTO messages
				SET	date = NOW(),
					expires = DATE_ADD(NOW(), INTERVAL $expires DAY),
					author = $author,
					subject = '$subject',
					messageText = '$body',
					replyTo = '$replyTo',
					deleted = 0,
					important = $important,
					teams = '$teams'";
			$InsertMsg = mysql_query($query_InsertMsg, $innebandybase) or die(mysql_error());
			$newMsgId = mysql_insert_id();
			$curMsg->setId($newMsgId);
			SendEmail($newMsgId);
			$headerJavaScript = RefreshMsgListJavaScript($newMsgId);
		}
	}
}
/**
 * Checks if message with given properties allready is registered in base.
 * Helps to avoid duplicates.
 *
 * @param string $author
 * @param string $teams
 * @param string $subject
 * @param string $body
 * @return boolean
 */
function MessageExcists($author, $teams, $subject, $body){
	$searchMsgString = "
		SELECT COUNT(id) AS NumberOfMsg
		FROM messages
		WHERE author = '$author'
		AND teams = '$teams'
		AND subject = '$subject'
		AND messageText = '$body'
	";
	$searchMsgQuery = new DBConnector_class($searchMsgString);
	if ($searchMsgQuery->GetSingleValue('NumberOfMsg') > 0){
		$out = true;	
	}else{
		$out = false;	
	}
	return $out;
}
function SendEmail($msgId){
	global $database_innebandybase, $innebandybase;
	/* Send email about the new Message */
	$noMsgFilter = 0; /* Send no email */
	$importantMsgFilter = 1;
	$allMsgFilter = 2; /* Allways send email */
	
	mysql_select_db($database_innebandybase, $innebandybase);
	$Message = new Message_class($msgId);
	$previousId = $Message->getReplyTo();
	$msgTeams = implode(",",$Message->getTeams());
	if($previousId){
		$prevMessage = new Message_class($previousId);
		$prevAuthorId = $prevMessage->getAuthor("id");
		$query_PreviousAuthor= "
		SELECT MAX(membership.noMessageByEmail)
		FROM membership 
			LEFT JOIN messages 
				ON membership.player = messages.author
			WHERE (((messages.id)=$previousId) AND ((membership.team) IN ($msgTeams)))";
		$PreviousAuthor = mysql_query($query_PreviousAuthor, $innebandybase) or die(mysql_error());
		if ($row_PreviousAuthor=mysql_fetch_assoc($PreviousAuthor)){
			$PrevAuthorCondString="OR(players.id=$prevAuthorId)";
		}else{
			$PrevAuthorCondString="";
		}
	}else{
		$PrevAuthorCondString="";
	}

	$importanceLevel = $Message->getIsImportant() ? $importantMsgFilter : $allMsgFilter;
	
	$addressArray = array();
	$query_Addresses = "
		SELECT DISTINCT
			CONCAT(	players.firstName , ' ' , 
					players.lastName, ' <', 
					players.email,'>') AS emailAddress
		FROM players 
		LEFT JOIN membership 
			ON players.id = membership.player
		WHERE (membership.team IN ($msgTeams) 
			AND players.email != ''
			AND membership.noMessageByEmail >= $importanceLevel)
			$PrevAuthorCondString
		ORDER BY players.lastName";
		$Addresses = mysql_query($query_Addresses,$innebandybase) or die(mysql_error());
	while ($row_Addresses = mysql_fetch_assoc($Addresses)){
		$addressArray[] = $row_Addresses['emailAddress'];
	}
	if(count($addressArray)){
		/* Sends e-mail */
		$email_importance = "Importance: ";
		$email_importance .=  $Message->getIsImportant() ? "High" : "Normal";
		$email_contenttype="Content-Type: text/plain; charset=iso-8859-1";
		$email_from = "From: ".$Message->getAuthor("fullemailaddress");
		$email_to = "";
		$email_body_prelude = "Denne meldingen er lagt inn i innebandysidene. Du kan reservere deg mot disse meldingene.\r\n---\r\n";
		$email_body = $Message->getTextBody();
		$email_subject = $Message->getSubject();
		$email_expire = "Expiry-Date: ".date ("D, d M Y H:i:s +0100",$Message->getDate("timestamp","expire"));
		$email_addresses = "Bcc: ".implode(",",$addressArray);
		$email_headers = "$email_importance\r\n$email_contenttype\r\n$email_from\r\n$email_addresses\r\n$email_expire";
		mail($email_to,$email_subject,$email_body_prelude.$email_body,$email_headers);
	}
}

function safeHTML($text) { 
       $text = stripslashes($text); 
       $text = strip_tags($text, '<b><i><u><a>'); 
       $text = eregi_replace ("<a [^>]*href *= *([^ ]+)[^>]*>", "<a href=\\1>", $text); 
       $text = eregi_replace ("<([b|i|u])[^>]*>", "<\\1>", $text);
       $text = str_replace("'","&#39;",$text);
       return $text; 
} 
 ?>