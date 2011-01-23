<?php
require_once('Connections/innebandybase.php');

$attentionCustomValueFieldName = "CustomValue";
$attentionFieldName = "Confirm";
$attentionNotesFieldName = "ConfirmNotes";
$detailsFrameName = "persons";
$editPlayerFileName = "formPlayerReg.php";
$eventDetailsFileName = "eventdetails.php";
$eventFieldname = "Event";
$eventListFileName = "eventlist.php";
$headerFrameName = "header";
$icalGeneratorFilename = "ical-generator.php";
$detailsAnchorName = "details";
$listFrameName="events";
$messageDetailsFrameName = "persons";
$messageDetailsFilename = "messagedetails.php";
$messageIdFieldName = "message";
$messageListFileName = "messagelist.php";
$messageListFrameName = "msgList";
$phoneListFrameName = "phoneList";
$playerIdFieldName="player";
$playerInfoFileName = "playerinfo.php";
$playerInfoFrameName = "playerinfo";
$playerInfoFieldName = "subjectPlayer";
$playerListFileName = "playerlist.php";
$teamChooserFieldName = "team";
$teamsFieldName="teams";


define('LINEFEED', "\n");
define('HOMEURL','skaar.freeshell.org/innebandy/');

/** -----------
*	Functions
*  -----------
*/

/**
 * Redirects user with server or client script, 
 * depending on wether headers are sent or not.
 * Snipped from http://www.edoceo.com/creo/php-redirect.php
 *
 * @param string $to
 * @param integer $code
 */
function Redirect($to,$code=301){
  $location = null;
  $sn = $_SERVER['SCRIPT_NAME'];
  $cp = dirname($sn);
  if (substr($to,0,4)=='http') $location = $to; // Absolute URL
  else
  {
    $schema = $_SERVER['SERVER_PORT']=='443'?'https':'http';
    $host = strlen($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];
    if (substr($to,0,1)=='/') $location = "$schema://$host$to";
    elseif (substr($to,0,1)=='.') // Relative Path
    {
      $location = "$schema://$host/";
      $pu = parse_url($to);
      $cd = dirname($_SERVER['SCRIPT_FILENAME']).'/';
      $np = realpath($cd.$pu['path']);
      $np = str_replace($_SERVER['DOCUMENT_ROOT'],'',$np);
      $location.= $np;
      if ((isset($pu['query'])) && (strlen($pu['query'])>0)) $location.= '?'.$pu['query'];
    }
  }

  $hs = headers_sent();
  if ($hs==false)
  {
    if ($code==301) header("301 Moved Permanently HTTP/1.1"); // Convert to GET
    elseif ($code==302) header("302 Found HTTP/1.1"); // Conform re-POST
    elseif ($code==303) header("303 See Other HTTP/1.1"); // dont cache, always use GET
    elseif ($code==304) header("304 Not Modified HTTP/1.1"); // use cache
    elseif ($code==305) header("305 Use Proxy HTTP/1.1");
    elseif ($code==306) header("306 Not Used HTTP/1.1");
    elseif ($code==307) header("307 Temorary Redirect HTTP/1.1");
    else trigger_error("Unhandled redirect() HTTP Code: $code",E_USER_ERROR);
    header("Location: $location");
    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
  }
  elseif (($hs==true) || ($code==302) || ($code==303))
  {
    // todo: draw some javascript to redirect
    $cover_div_style = 'background-color: #ccc; height: 100%; left: 0px; position: absolute; top: 0px; width: 100%;'; 
    echo "<div style='$cover_div_style'>\n";
    $link_div_style = 'background-color: #fff; border: 2px solid #f00; left: 0px; margin: 5px; padding: 3px; ';
    $link_div_style.= 'position: absolute; text-align: center; top: 0px; width: 95%; z-index: 99;';
    echo "<div style='$link_div_style'>\n";
    echo "<p>Please See: <a href='$to'>".htmlspecialchars($location)."</a></p>\n";
    echo "</div>\n</div>\n";
  }
  exit(0);
}

/**
 * Encoding for e-mail and such
 *
 * @param string $sText
 * @param boolean $bEmulate_imap_8bit
 * @return string
 */
function encodeQuotedPrintable($sText,$bEmulate_imap_8bit=true) {
  // split text into lines
  $aLines=explode(chr(13).chr(10),$sText);

  for ($i=0;$i<count($aLines);$i++) {
   $sLine =& $aLines[$i];
   if (strlen($sLine)===0) continue; // do nothing, if empty

   $sRegExp = '/[^\x09\x20\x21-\x3C\x3E-\x7E]/e';

   // imap_8bit encodes x09 everywhere, not only at lineends,
   // for EBCDIC safeness encode !"#$@[\]^`{|}~,
   // for complete safeness encode every character :)
   if ($bEmulate_imap_8bit)
     $sRegExp = '/[^\x20\x21-\x3C\x3E-\x7E]/e';

   $sReplmt = 'sprintf( "=%02X", ord ( "$0" ) ) ;';
   $sLine = preg_replace( $sRegExp, $sReplmt, $sLine ); 

   // encode x09,x20 at lineends
   {
     $iLength = strlen($sLine);
     $iLastChar = ord($sLine{$iLength-1});

     //              !!!!!!!!   
     // imap_8_bit does not encode x20 at the very end of a text,
     // here is, where I don't agree with imap_8_bit,
     // please correct me, if I'm wrong,
     // or comment next line for RFC2045 conformance, if you like
     if (!($bEmulate_imap_8bit && ($i==count($aLines)-1)))
        
     if (($iLastChar==0x09)||($iLastChar==0x20)) {
       $sLine{$iLength-1}='=';
       $sLine .= ($iLastChar==0x09)?'09':'20';
     }
   }    // imap_8bit encodes x20 before chr(13), too
   // although IMHO not requested by RFC2045, why not do it safer :)
   // and why not encode any x20 around chr(10) or chr(13)
   if ($bEmulate_imap_8bit) {
     $sLine=str_replace(' =0D','=20=0D',$sLine);
     //$sLine=str_replace(' =0A','=20=0A',$sLine);
     //$sLine=str_replace('=0D ','=0D=20',$sLine);
     //$sLine=str_replace('=0A ','=0A=20',$sLine);
   }

   // finally split into softlines no longer than 76 chars,
   // for even more safeness one could encode x09,x20
   // at the very first character of the line
   // and after soft linebreaks, as well,
   // but this wouldn't be caught by such an easy RegExp                 
   preg_match_all( '/.{1,73}([^=]{0,2})?/', $sLine, $aMatch );
   $sLine = implode( '=' . chr(13).chr(10), $aMatch[0] ); // add soft crlf's
  }

  // join lines into text
  return implode(chr(13).chr(10),$aLines);
}
/**
 * Returns data from post or get.
 * POST takes precedence before GET.
 * Returns empty string if no form data is defined.
 *
 * @param string $formfieldName
 * @return string
 */
function GetPostGetData($formfieldName){
	if (array_key_exists($formfieldName,$_POST) && $_POST[$formfieldName] != ""){
		$out = $_POST[$formfieldName];
	}elseif (array_key_exists($formfieldName,$_GET)){
		$out = $_GET[$formfieldName];
	}else{
		$out = "";
	}
	return $out;
}

/**
 * provides a link to detail view for that player.
 *
 * @param integer $playerId
 * @param string $firstName
 * @param integer $teamId
 * @param string $align
 * @param string $size
 * @return string
 */
function PlayerInfoLink($playerId=0,$firstName="",$teamId=0, $align="absmiddle", $size="large"){
	global $playerInfoFileName, $playerInfoFrameName, $playerInfoFieldName;
	global $playerIdFieldName, $teamChooserFieldName, $detailsAnchorName;
	if($size == "large"){
		$iconName = "playerinfo.gif";
		$iconWidth = 26;
		$iconHeight = 32;
	}else{
		$iconName = "playerinfoSmall.gif";
		$iconWidth = 10;
		$iconHeight = 12;
	}
	$iconPath = 'images/'.$iconName;
	$hrefPath = $playerInfoFileName;
	$thisPlayer = new Player_class();
	$thisPlayerId = $thisPlayer->getId();
	if ($teamId == 0){
		$teamId = $thisPlayer->getCurrentTeam();	
	}
	$hrefPath .= "?{$playerIdFieldName}={$thisPlayerId}";
	$hrefPath .= "&{$teamChooserFieldName}={$teamId}";
	if ($playerId){
		if ($firstName==""){
			$infoPlayer = new OtherPlayer_class($playerId,$teamId);
			$firstName = $infoPlayer->GetName("first");
		}
		$altTxt = "Informasjon om {$firstName}.";
		$titleTxt = "&Aring;pne infosiden for &aring; se mer informasjon om {$firstName}.";
		$hrefPath .= "&{$playerInfoFieldName}={$playerId}";
	}else{
		$altTxt = "Spillerinformasjon";
		$titleTxt = "&Aring;pne infosiden for &aring; se mer informasjon om spillerne.";
	}
	$hrefPath .= "#{$detailsAnchorName}";
	$out = "\n<a href=\"{$hrefPath}\" ";
	$out .= "title=\"$titleTxt\" ";
	$out .= "target=\"$playerInfoFrameName\" ";
	$out .= ">";
	if ($size=="text"){
		$out = "Spillerliste";
	}else{
		$out .= "<img ";
		$out .= "src=\"$iconPath\" ";
		$out .= "width=\"$iconWidth\" height=\"$iconHeight\" ";
		$out .= "align=\"$align\" alt=\"$altTxt\" ";
		$out .= "border=\"0\" ";
		$out .= ">";
	}
	$out .= "</a>\n";
	return $out;
}

/**
* @return html
* @desc Returns html to render icon with link to url, if url is present.
*/
function GetURLGlobeIcon($webUrl, $alt = "", $target=""){
	if ($webUrl != ""){
		if ($alt == ""){
			$alt = "Klikk her for &aring; g&aring; til <$webUrl>";
		}
		$targetString = ($target != "") ? " target='$target'" : "";

		$out = "<a 
			href='$webUrl' target='info' title='$alt'><img src='images/globe.gif' 
			alt='$alt'$targetString
			width='32' height='32' border='0' align='left'>
		</a>
		";
	}else{
		$out = "";	
	}
	return $out;
}

/**
 * @return string
 * @param string $name
 * @desc Returns genitive form of name
*/
function GenitiveNor($name){
	$lastChar = substr($name,-1);
	$isInStr = strpos("sz",$lastChar);
	if ($isInStr === false){
		$out = $name."s";
	}else{
		$out = $name."&apos;";
	}
	return $out;
}

function RSSlink($type = "standard", $includePassword=false){
	global $playerIdFieldName;
	$player = new Player_class();
	$playerId = $player->getId();
	$logonString = "";
	if ($includePassword){
		$team = new Team_class();
		$userName = $team->getName('userName');
		$password = $team->getName('password');
		$logonString = "{$userName}:{$password}@";
	}
	$playerFieldString = $playerId ? "?$playerIdFieldName=$playerId" : "";
	$hrefString = "http://".$logonString.HOMEURL."rssfeed.php{$playerFieldString}";
	$targetString = "_blank";
	$longTitle = $team->description;
	$shortTitle = $team->shortName;
	switch($type){
		case 'standard':
			$out = "<a href=\"{$hrefString}\" target=\"{$targetString}\"";
			$out .= " title=\"{$longTitle}\"";
			$out .= ">";
			$out .= "RSS";
			$out .= "</a>";
			break;
		case 'alternate':
			$out = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"{$shortTitle}\" href=\"{$hrefString}\" />";
			break;
		}
	
	return $out;	
}

function IcalLink(){
	global $icalGeneratorFilename;
	$player = new Player_class();
	$playerId = $player->getId();
	$url=HOMEURL."$icalGeneratorFilename?player=$playerId";
	$output="webcal://$url";
	$output= "<link rel=\"alternate\" type=\"text/calendar\" title=\"Kalender\" href=\"{$output}\" />";
	return $output;
}

function StringCleaning($string, $javaScript=false){
	$out = "";
	$badstrings = array(">", "<", "\"", "&");
	$goodstringsHtml  = array("&lt;", "	&gt;", "&quot;", "&amp;");
	$goodstringsJavaScript = array("unescape('%3E')", "unescape('%3C')", "unescape('%22')", "unescape('%26')");
	$goodstrings = $javaScript ? $goodstringsJavaScript : $goodstringsHtml;
	$out = str_replace($badstrings, $goodstrings, $string);
	return $out;
}

function validate_mail($email) {
	$result = true;
	$pattern = 
	"/^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,})$/si";
	if (! preg_match($pattern, $email)) {
		$result=false;
	}
	return $result;
}

function buttonToPhoneList($playerId, $eventId=0, $teamId=0){
	global $teamChooserFieldName, $playerIdFieldName, $eventFieldname, $phoneListFrameName;
	$out = "
		<form name=\"phonelist\" method=\"post\" 
			action=\"phoneList.php\" target=\"$phoneListFrameName\">
  		<input name=\"$playerIdFieldName\" type=\"hidden\" 
			value=\"$playerId\">
		<input name=\"$eventFieldname\" type=\"hidden\" 
			value=\"$eventId\">
		<input name=\"$teamChooserFieldName\" type=\"hidden\" 
			value=\"$teamId\">
  		<input type=\"submit\" name=\"Submit\" value=\"Melde p&aring; andre spillere\">
		</form>
	";
	return $out;
}

/*
 * @return string
 * @param int $number
 * @param boolean $ordinals
 * @desc Returns the number as a String in norwegian, if number is [0,12] and integer.
*/
function NorwegianTextNumbers($number, $ordinals = false, $intetkj = false,$html=true){
	
	if($html){
		$ordinalNumbers = array(
			0 => "neppe",
			1 => "f&oslash;rste", 2 => "andre",  3=> "tredje",
			4 => "fjerde", 5 => "femte", 6 => "sjette",
			7 => "syvende", 8 => "&aring;ttende", 9 => "niende",
			10 => "tiende", 11 => "ellevte", 12 => "tolvte"
		);
		$regularNumbers = array(
			0 => "ingen",
			1 => "&eacute;n", 2 => "to",  3=> "tre",
			4 => "fire", 5 => "fem", 6 => "seks",
			7 => "syv", 8 => "&aring;tte", 9 => "ni",
			10 => "ti", 11 => "ellve", 12 => "tolv"
		);
	}else{
		$ordinalNumbers = array(
			0 => "neppe",
			1 => "f�rste", 2 => "andre",  3=> "tredje",
			4 => "fjerde", 5 => "femte", 6 => "sjette",
			7 => "syvende", 8 => "�ttende", 9 => "niende",
			10 => "tiende", 11 => "ellevte", 12 => "tolvte"
		);
		$regularNumbers = array(
			0 => "ingen",
			1 => "�n", 2 => "to",  3=> "tre",
			4 => "fire", 5 => "fem", 6 => "seks",
			7 => "syv", 8 => "�tte", 9 => "ni",
			10 => "ti", 11 => "ellve", 12 => "tolv"
		);
	}
	if(is_numeric($number)){
		$numericValue = (int) $number;
	}else{
		$numericValue = 0;	
	}
	$strings = $ordinals ? $ordinalNumbers : $regularNumbers;
	
	
	if( ($numericValue < 0) || ($numericValue > 12) ){
		$out = $ordinals ? $numericValue . "." : $number;
	}else{
		$out = $strings[$numericValue];
		
		//Special cases
		if ($intetkj){
			if (! $ordinals){
				if ($numericValue == 0){
					$out = "intet";
				}
				if ($numericValue == 1){
					$out = "ett";
				}		
			}
		}
	}
	return $out;
}

/*
 * @return float
 * @param 'w'|'d'|'h'|'n'|'s' $interval
 * @param date $date1
 * @param date $date2
 * @desc Get the interval between two dates
*/

Function DateDiff ($interval,$date1,$date2) {
    // get the number of seconds between the two dates 
$timedifference = $date2 - $date1;

    switch ($interval) {
        case 'w':
            $retval = $timedifference/604800;
            break;
        case 'd':
            $retval = $timedifference/86400;
            break;
        case 'h':
            $retval =$timedifference/3600;
            break;
        case 'n':
            $retval = $timedifference/60;
            break;
        case 's':
            $retval = $timedifference;
            break;           
    }
    return $retval;

}

/**
 * @return date
 * @param 'yyyy'|'q'|'m'|'y'|'d'|'w'|'ww'|'h'|'n'|'s' $interval
 * @param int $number
 * @param date $date
 * @desc Adds an interval to a date
*/
function DateAdd($interval, $number, $date) {

    $date_time_array = getdate($date);
    $hours = $date_time_array['hours'];
    $minutes = $date_time_array['minutes'];
    $seconds = $date_time_array['seconds'];
    $month = $date_time_array['mon'];
    $day = $date_time_array['mday'];
    $year = $date_time_array['year'];

    switch ($interval) {
    
        case 'yyyy':
            $year+=$number;
            break;
        case 'q':
            $year+=($number*3);
            break;
        case 'm':
            $month+=$number;
            break;
        case 'y':
        case 'd':
        case 'w':
            $day+=$number;
            break;
        case 'ww':
            $day+=($number*7);
            break;
        case 'h':
            $hours+=$number;
            break;
        case 'n':
            $minutes+=$number;
            break;
        case 's':
            $seconds+=$number; 
            break;            
    }
       $timestamp= mktime($hours,$minutes,$seconds,$month,$day,$year);
    return $timestamp;
}


function makeLink($url, $text, $title="", $target="console"){
	if ($url){
		$out = "
				<a href='$url' ";
		$out .= ($title == "") ? "" : "title='$title' ";
		$out .= ($target == "") ? "" : "target='$target'";
		$out .= ">$text</a>
				";
	}else{
		$out = "";	
	}
	return $out;
} 

function JavaScriptWrapper($scriptString){
	$out = '
	<script language="JavaScript" type="text/JavaScript">
	<!--	
	';
	$out .= $scriptString;
	$out .= '
	//-->
	</script>
	';
	return $out;
}
function HomeIconLink($align="right"){
	$out = "";
	$out = '
		<a href="./" target="_top">
		<img src="images/homeIcon.gif" 
			alt="Tilbake til forsiden" 
			width="47" 
			height="32" 
			border="0"
			align="'.$align.'">
		</a>
	';
	return $out;
}
/**
 * @return SQL
 * @param int[] $teams
 * @desc Returns string to but in WHERE cluse of query to get messages of given team
*/
function MessageQueryTeamFilterString($teams){
	$teamFilterString = "AND (
		messages.teams = 0
	";
	foreach ($teams as $team){
		$teamFilterString .= "
			OR messages.teams = '$team'
			OR messages.teams LIKE '%,$team'
			OR messages.teams LIKE '$team,%'
			OR messages.teams LIKE '%,$team,%'
		";
	}
	$teamFilterString .= ")";
	return $teamFilterString;
}
function NewsArrow($direction ="left", $align="baseline"){
	$out = "<img 
			src='images/NyhetPil.gif' 
			alt='Nyhet!' 
			width='120'
			height='60' 
			align='$align'>";
	return $out;
}

/**
 * @return string
 * @param datestring $dateValue
 * @param boolean $textualDate
 * @desc Formats a datestring as an norwegian date with weekday
*/
function norDate ($dateValue, $textualDate=false, $html=true) {
	if ($dateValue < 1000000){
		// $dateValue is probably a datestring
		$dateStamp=strtotime($dateValue);
	}else{
		$dateStamp=$dateValue;	
	}
	
	$hours = abs(($dateStamp-time())/3600);
	$sameDay = (date("d m Y")==(date("d m Y",$dateStamp)));
	$sameYear = (abs(time()-$dateStamp)<(60*60*24*300));
	$norDays = $html?
		array('mandag','tirsdag','onsdag','torsdag','fredag','l&oslash;rdag','s&oslash;ndag'):
		array('mandag','tirsdag','onsdag','torsdag','fredag','l�rdag','s�ndag');

	if($sameDay && ($hours < 16)){
		$returnVal = "i dag (".$norDays[strftime ("%u",$dateStamp)-1].")";
	}else{
		$norMonths = array('januar','februar','mars','april','mai','juni','juli','august','september','oktober','november','desember');
		$returnVal = $norDays[strftime ("%u",$dateStamp)-1];
		$dateNumber = strftime ("%e",$dateStamp);
		$returnVal .= $textualDate ? ' '.NorwegianTextNumbers($dateNumber,true,false,$html) : " {$dateNumber}.";
		$returnVal .= $html?'&nbsp;':' ';
		$returnVal .= $norMonths[strftime ("%m",$dateStamp)-1];
	}
	if (! $sameYear){
		$returnVal .= $html?'&nbsp;':' ';
		$returnVal .= date("Y",$dateStamp);
	}
	return $returnVal;
}

/**
 * @return string
 * @param timestamp $timeStampValue
 * @desc Formats a time as textual Norwegian
*/
function norTime($timeStampValue,$html=true){
	$hours = date("g",$timeStampValue);
	$minutes = round(date("i", $timeStampValue)/5,0)*5;
	if ($minutes > 15){
		$hours = (($hours++) % 12)+1;
	}
	$strings = $html?
		array(
		0 => "",
		5 => "fem over ",
		10 => "ti over ",
		15 => "kvart over ",
		20 => "ti p&aring; halv ",
		25 => "fem p&aring; halv ",
		30 => "halv ",
		35 => "fem over halv ",
		40 => "ti over halv ",
		45 => "kvart p&aring; ",
		50 => "ti p&aring; ",
		55 => "fem p&aring; "
		):
		array(
		0 => "",
		5 => "fem over ",
		10 => "ti over ",
		15 => "kvart over ",
		20 => "ti p� halv ",
		25 => "fem p� halv ",
		30 => "halv ",
		35 => "fem over halv ",
		40 => "ti over halv ",
		45 => "kvart p� ",
		50 => "ti p� ",
		55 => "fem p� "
		);
	$out = $strings[$minutes].NorwegianTextNumbers($hours, false, true,$html);
	return $out;
}

function UpdateLastLogin(){
	global $innebandybase, $database_innebandybase;
	$player = new Player_class();
	$playerId = $player->getId();
	if($playerId){
		$query_updateLogin = "UPDATE players
							  SET lastLogin = NOW()
							  WHERE id='$playerId'";
		$update = new DBConnector_class($query_updateLogin);
	}
}

function DescribeHistory($eventId, $hrAbove=true, $hrBelow=false){
	$player = new Player_class();
	$playerId = $player->getId();
	$out = "";
	
	if($eventId){
		/* Hente lag-id til hendelsen */
		$query_getTeamOfEvent = "SELECT team FROM events WHERE id = $eventId";
		$getTeamOfEvent = new DBConnector_class($query_getTeamOfEvent);
		$teamId = $getTeamOfEvent->GetSingleValue('team');
		
		/* Hente hele loggen */
		$query_changeLog = "
			SELECT (eventChangeLog.player='$playerId') AS self, 
				eventChangeLog.date, membership.teamTrainer,
				players.firstName, players.lastName, players.email, players.id,
				((UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(eventChangeLog.date))/3600) AS hoursOld
			FROM (eventChangeLog LEFT JOIN players ON eventChangeLog.player = players.id) LEFT JOIN membership ON players.id = membership.player
			WHERE (((eventChangeLog.event)=$eventId) AND ((membership.team)=$teamId))
			ORDER BY eventChangeLog.date
		";
		
		$changeLog = new DBConnector_class($query_changeLog);
		$totalRows_changeLog = $changeLog->GetNumberOfRows();
	
		if ($totalRows_changeLog){
			while($row_changeLog=$changeLog->GetNextRow()) {
				$log[]=$row_changeLog;
			}
			
			// Define variables
			
			
			$createDateString = RecentDateTime($log[0]['date'],$log[0]['hoursOld']);
			$hasBeenChanged = ($totalRows_changeLog > 1);
			$creator = PlayerName($log[0]['firstName'],$log[0]['lastName'],$log[0]['email'],$log[0]['teamTrainer'],$log[0]['self']);
			if ($hasBeenChanged) {
				$lastIndex = sizeof($log)-1;
				$editDateString = RecentDateTime($log[$lastIndex]['date'],$log[$lastIndex]['hoursOld']);
				$editor = PlayerName($log[$lastIndex]['firstName'],$log[$lastIndex]['lastName'],$log[$lastIndex]['email'], $log[$lastIndex]['teamTrainer'],$log[$lastIndex]['self']);
				$sameDate = ($editDateString == $createDateString);
				$samePlayer = ($editor == $creator);
				$editDateStringFinal = $sameDate ? "samme dag" : $editDateString;
			}
			
			if ($hrAbove) {$out .= "<hr>";}
			
			$out .= "<p class='smallCenteredText'>
					 <span class='serifOldstyle'>";
			$out .= "Hendelsen ble opprettet ";
			$out .= $createDateString;
			
			if ($hasBeenChanged && $samePlayer){
				$out .= " og endret ";
				$out .= $editDateStringFinal;
				$out .= " av ";
				$out .= $editor;
			}else{
				$out .= " av ";
				$out .= $creator;
				if ($hasBeenChanged){
					$out .= ".";
					$out .= "<br>Den ble sist endret ";
					$out .= $editDateStringFinal;
					$out .= " av ";
					$out .= $editor;
				}			
			}
			$out .= ".";
			$out .= "</span>
					 </p>";
			if ($hrBelow) {$out .= "<hr>";}
		}
	}
	return $out;
}

class EditorOfEvent_class {
	var $eventId, $allChanges, $teamId;
	function EditorOfEvent_class($eventid){
		$this->eventId = $eventid;
		$this->teamId = $this->_GetTeamOfEvent();
		$this->allChanges = $this->_GetChangeLog();
	}
	function _GetChangeLog(){
		$player = new Player_class();
		$playerId = $player->getId();
		$eventId = $this->eventId;
		$teamId = $this->teamId;
		/* Hente hele loggen */
		$query_changeLog = "
			SELECT (eventChangeLog.player='$playerId') AS self, 
				eventChangeLog.date, membership.teamTrainer,
				players.firstName, players.lastName, players.email,
				((UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(eventChangeLog.date))/3600) AS hoursOld
			FROM (eventChangeLog LEFT JOIN players ON eventChangeLog.player = players.id) LEFT JOIN membership ON players.id = membership.player
			WHERE (((eventChangeLog.event)=$eventId) AND ((membership.team)=$teamId))
			ORDER BY eventChangeLog.date
		";
		
		
		$changeLog = new DBConnector_class($query_changeLog);
	
		return $changeLog->GetAllRows();
	}
	function _GetTeamOfEvent(){
		/* Hente lag-id til hendelsen */
		$eventId = $this->eventId;
		
		$query_getTeamOfEvent = "SELECT team FROM events WHERE id = $eventId";
		$getTeamOfEvent = new DBConnector_class($query_getTeamOfEvent);
		$teamId = $getTeamOfEvent->GetSingleValue('team');
		return $teamId;
	}
	function _GetCreator(){
		return $this->allChanges[0];
	}
	function _GetLastEditor(){
		return $this->allChanges[count($this->allChanges)-1];
	}
	function GetNameOfCreator(){
		$creatorInfo = $this->_GetCreator();
		$out = $creatorInfo['firstName']." ".$creatorInfo['lastName'];
		return $out;
	}
	function GetEmailOfCreator(){
		$creatorInfo = $this->_GetCreator();
		$out = $creatorInfo['email'];
		return $out;
	}
	function GetNameOfEditor(){
		$creatorInfo = $this->_GetLastEditor();
		$out = $creatorInfo['firstName']." ".$creatorInfo['lastName'];
		return $out;
	}
	function GetEmailOfEditor(){
		$creatorInfo = $this->_GetLastEditor();
		$out = $creatorInfo['email'];
		return $out;
	}
	function GetLastEditTime(){
		$edit=$this->_GetLastEditor();
		return $edit["date"];
	}
	function GetHistoryString($html=true){
		$totalRows_changeLog = count($this->allChanges);
		if ($totalRows_changeLog){
			foreach ($this->allChanges as $row_changeLog) {
				$log[]=$row_changeLog;
			}
			if ($hrAbove&&$html) {$out .= "<hr>";}
			
			$createDateString = RecentDateTime($log[0]['date'],$log[0]['hoursOld'],$html);
			
			if($html){
				$out .= "<p class='smallCenteredText'>
					 <span class='serifOldstyle'>";
			}
			$out .= "Hendelsen ble opprettet ";
			$out .= $createDateString;
			$out .= " av ";
			$out .= PlayerName($log[0]['firstName'],$log[0]['lastName'],$log[0]['email'],$log[0]['teamTrainer'],$log[0]['self'],$html);
			$out .= ". ";
	
			if ($totalRows_changeLog > 1) {
				$lastIndex = sizeof($log)-1;
				
				$editDateString = RecentDateTime($log[$lastIndex]['date'],$log[$lastIndex]['hoursOld'],$html);
				
				$out .= $html?"<br>Den ble sist endret ":"\nDen ble sist endret ";
				if($editDateString == $createDateString){
					$out .= "samme dag";
				}else{
					$out .= $editDateString;
				}
				$out .= " av ";
				$out .= PlayerName($log[$lastIndex]['firstName'],$log[$lastIndex]['lastName'],$log[$lastIndex]['email'], $log[$lastIndex]['teamTrainer'],$log[$lastIndex]['self']);
				$out .= ". ";
			}
			if($html){
				$out .= "</span>
						 </p>";
				if ($hrBelow) {$out .= "<hr>";}
			}
		}
		return $out;
	}
}

function UpdateEvent($eventId){

	global $innebandybase;
	$query_updateEvent="
	UPDATE events
	SET notified = 1
	WHERE id = $eventId";

	$updateEvent = mysql_query($query_updateEvent, $innebandybase) or die(mysql_error());
	$row_updateEvent = mysql_fetch_assoc($updateEvent);
	$totalRows_updateEvent = mysql_num_rows($updateEvent);
	
	
	mysql_free_result($updateEvent);
}

function DescriptivePlayerName($playerId, $team){
	$player = new Player_class();
	$selfId = $player->getId();
	$self = ($playerId == $selfId);
	if ($self){
		$out = "deg";	
	}else{
		$queryString = "
			SELECT players.firstName, players.lastName,
				players.email, membership.teamTrainer
			FROM players LEFT JOIN membership ON membership.player=players.id
			WHERE players.id = '$playerId' AND membership.team = '$team'
		";
		
		$query = new DBConnector_class($queryString);
		$player_row = $query->GetNextRow();
		$out = PlayerName($player_row['firstName'],$player_row['lastName'],$player_row['email'],$player_row['teamTrainer'],$self);
	}
	return $out;
}

/**
 * @return html
 * @param string $first
 * @param string $last
 * @param url $email
 * @param boolean $trainer
 * @param boolean $self
 * @desc Returns full name (unless trainer) of player with email link
*/
function PlayerName($first,$last,$email,$trainer, $self=false, $html=true){
	$startLinkTag="";
	$endLinkTag="";
	if($email != ""&&$html){
		$startLinkTag = '<a href="mailto:'.$email.'">';
		$endLinkTag = '</a>';
	}
	if($self){
		$out .= "deg";
	}else if ($trainer){
		$out .= "oppmann ".$startLinkTag.$first.$endLinkTag;
	}else{
		$out .= "$startLinkTag$first $last$endLinkTag";
	}
	return $out;
}
/**
 * @return string
 * @param datestring $date
 * @param float $hours
 * @desc Returns date formatted as norwegian date. If $hours is sufficiently low, it describes the date as recent
*/
function RecentDateTime($date,$hours,$html=true){
	if ($hours < 0.5){
		$out = "nettopp";	
	}else if($hours < 3){
		$out = "for litt siden";
	}else if ($hours < 6){
		$out = "for noen timer siden";
	}else{
		$out = norDate($date,false,$html);
	}
	return $out;
}
/**
 * @return string
 * @param timestamp $date
 * @desc Formats Norwegian date and time
*/
function RecentDate($date,$html=true){
	$rightNow = time();
	$hours = abs($rightNow - $date)/(60*60);
	$days = (strtotime(date("d-F-Y",$rightNow))-strtotime(date("d-F-Y",$date)))/(60*60*24);
	if ($hours < 0.5){
		$out = "nettopp";	
	}elseif ($hours < 3){
		$out = "for litt siden";
	}else{
		$isRecent = true;
		if ($hours < 96) {
			$hoursDisplay = norTime($date,$html);
		}else{
			$hoursDisplay = "";
		}
		if ($days == 1){
			$daysDisplay =$html? "i g&aring;r":"i g�r";
		}elseif($days == 0){
			$daysDisplay = "i dag";
		}elseif($days == 2){
			$daysDisplay = $html?"i forig&aring;rs":"i forig�rs";
		}else{
			$daysDisplay = norDate($date,true,$html);
			$isRecent = false;
		}
		if ($hoursDisplay == ""){
			$out = $daysDisplay;
		}else{
			$out = 	$isRecent ? $hoursDisplay." ".$daysDisplay : $daysDisplay." klokken ".$hoursDisplay;
		}
	}
	return $out;
}
/**
 * @return html
 * @desc Display button to reset ident-cookie and goto player list
*/
function ResetIdentityButton(){
	global $playerIdFieldName;
	$player = new Player_class();
	if ($player->getId()){
		$buttonText = "Er du ikke ".$player->getName("full")."?";
	}else{
		$buttonText = "Hvem er du?";	
	}
	$out = '
	<script language="JavaScript" type="text/JavaScript">
		function NoPlayer (){
			document.cookie="'.$playerIdFieldName.'=0";
			top.document.location.href="index.php";
		}
	</script>
	<input 
		name="newPlayer_button" 
		type="button" 
		id="newPlayer_button" 
		value="'.$buttonText.'" 
		onClick="NoPlayer()">
	';
	return $out;
}

/**
 * @return html
 * @param array() $source
 * @param string $lastPairSeparator
 * @param string $separator
 * @desc Outputs list on the form "element1, element2 $lastPairSeparator element3"
*/
function SeparatedList($sourceArray, $lastPairSeparator = " og ", $separator = ", "){
	$numberOfElements = count($sourceArray);
	switch ($numberOfElements){
		case 0:
			$out = "";
		break;
		case 1:
			$out = $sourceArray[0];
		break;
		case 2:
			$out = $sourceArray[0].$lastPairSeparator.$sourceArray[1];
		break;
		default:
			$elementIndex=0;
			$out = "";
			foreach ($sourceArray as $element){
				if ( ($elementIndex > 0) && ($elementIndex < ($numberOfElements-1)) ){
					$out .= $separator;
				}elseif ($elementIndex==$numberOfElements-1){
					$out .= $lastPairSeparator;	
				}
				$out .= $sourceArray[$elementIndex++];
			}
	}
	return $out;
}

function GetCSSName($teamId){
  $cssName="";
  if($teamId)
  {
	  $queryString="
	  	SELECT styleSheet
	  	FROM teams
		WHERE id='{$teamId}'
		";
	  $queryObj = new DBConnector_class($queryString);
	  $cssName=$queryObj->GetSingleValue("styleSheet");
  }
  return $cssName;
}
function RenderCSSLink($teamId){
	$output="\n";
	$styleSheetName=GetCSSName($teamId);
	$cssPrefix="css/";
	$commonCssName="common.css";
	$output .="\t<link href='{$cssPrefix}{$commonCssName}' rel='stylesheet' type='text/css'/>\n";
	if($styleSheetName){
		$output .="\t<link href='{$cssPrefix}{$styleSheetName}' rel='stylesheet' type='text/css'/>\n";
	}
	return $output;
}

/*---------------*/
/*    Classes    */
/*---------------*/


require_once('player.inc');
require_once('DateControl.inc');

class DBConnector_class {
	var $queryString, $numberOfRows, $query;
	function DBConnector_class($sqlString){
		$this->queryString = $sqlString;
		$this->_makeQuery();
	}
	function _makeQuery(){
		global $database_innebandybase, $innebandybase;
		mysql_select_db($database_innebandybase, $innebandybase);
		$results = mysql_query($this->queryString , $innebandybase) or die($this->_errorInQuery());
		$this->query = $results;
		$this->numberOfRows = mysql_num_rows($results);
	}
	/**
	 * Closes the database connection
	 *
	 */
	function Close(){
		global $innebandybase;
		mysql_close($innebandybase);
	}
	/**
	* @return mysql_query_object
	* @desc Returns the query object itself
	*/
	function GetQuery(){
		return $this->query;	
	}
	/**
	* @return int
	* @desc Returns the result of mysql_insert_id()
	*/
	function GetLastAutoIncrement(){
		return mysql_insert_id();	
	}	
	/**
	* @return integer
	* @desc Number of rows in result
	*/
	function GetNumberOfRows(){
		return $this->numberOfRows;	
	}
	/**
	* @return Array()
	* @desc Returns next row of result as ass. array 
	*/
	function GetNextRow(){
		return mysql_fetch_assoc($this->query);	
	}
	/**
	* @return array
	* @desc Returns array with all rows
	*/
	function GetAllRows(){
		$out = array();
		while ($currentArray = $this->GetNextRow()){
			if(count($currentArray)){
				$out[]=$currentArray;	
			}
		}
		return $out;
	}
	function GetArrayOfAllRowsOneValue($key){
		$out = array();
		while($row = $this->GetNextRow()){
			$out[] = $row[$key];
		}
		return $out;	
	}
	/**
	* @return string|float|int
	* @param string $valueString
	* @desc Returns the value from the next row by the key passed
	*/
	function GetSingleValue($valueString){
		$out = "";
		if ($valueString){
			$row = $this->GetNextRow();
			$out = $row[$valueString];
		}
		return $out;	
	}
	/**
	* @return void
	* @desc Prints querystring and error 
	*/
	function _errorInQuery(){
		$errorString = "Feilmelding: ".mysql_error();
		$errorString .= "\nSiste sp�rring: ".$this->queryString;
		$errorStringClean = htmlentities($errorString,ENT_QUOTES);
		$output  = "\n<p>Det er problemer med databasen.</p>\n";
		$output .= "<p><a title='$errorStringClean'href='javascript:location.reload();'>Fors�k igjen om du vil.</a></p>\n";
		$output .= "\n---\n";
		$output .= "<hr/>\n";
		$output .= $errorStringClean;
		echo $output;
	}
}


class Team{
	var $id, $shortName, $longName, $loginName, $loginPassword, $headerFile, $otherHomePage;
	var $teamTrainers, $resultsUrl;
}

class Team_class extends Team  {
	var $allEvents, $comingEvents;
	function Team_class(){
		$this->id = & $GLOBALS['_transient']['static']['team_class']->id;
		$this->shortName = & $GLOBALS['_transient']['static']['team_class']->shortName;
		$this->longName = & $GLOBALS['_transient']['static']['team_class']->longName;
		$this->loginName = & $GLOBALS['_transient']['static']['team_class']->loginName;
		$this->loginPassword = & $GLOBALS['_transient']['static']['team_class']->loginPassword;
		$this->headerFile = & $GLOBALS['_transient']['static']['team_class']->headerFile;
		$this->otherHomePage = & $GLOBALS['_transient']['static']['team_class']->otherHomePage;
		$this->resultsUrl =  & $GLOBALS['_transient']['static']['team_class']->resultsUrl;
		$this->teamTrainers = & $GLOBALS['_transient']['static']['team_class']->teamTrainers;
		if(! $this->id){
			$this->_makeQuery();
			$this->_findTrainers();
		}
	}
	function _makeQuery(){
		/* Get Logon name */
		$authUserName=$_SERVER['PHP_AUTH_USER'];
		/* Get team info */
		mysql_select_db($database_innebandybase, $innebandybase);
		$query_TeamInfo = "
		SELECT * 
		FROM teams 
		WHERE userName='$authUserName'
		";
		$TeamInfo = new DBConnector_class($query_TeamInfo);
		$row_TeamInfo = $TeamInfo->GetNextRow();
		$this->id = $row_TeamInfo['id'];
		$this->shortName = $row_TeamInfo['shortName'];
		$this->longName = $row_TeamInfo['longName'];
		$this->loginName = $row_TeamInfo['userName'];
		$this->loginPassword = $row_TeamInfo['password'];
		$this->headerFile = $row_TeamInfo['headerFile'];
		$this->otherHomePage = $row_TeamInfo['otherHomePage'];
		$this->resultsUrl = $row_TeamInfo['resultsUrl'];
	}
	function _findTrainers(){
		global $database_innebandybase, $innebandybase;
		$teamId = $this->get_id();
		$array_trainers = array();
		$query_trainers = "
			SELECT membership.player, players.firstName, players.lastName, membership.number
			FROM membership 
			LEFT JOIN players 
				ON membership.player = players.id
			WHERE (((membership.team)=$teamId) AND ((membership.teamTrainer)=1))
		";
		mysql_select_db($database_innebandybase, $innebandybase);
		$trainers = mysql_query($query_trainers, $innebandybase) or die(mysql_error());
		while($row_trainers = mysql_fetch_assoc($trainers)){
			$array_trainers[] = $row_trainers['player'];
		}
		$this->teamTrainers = $array_trainers;
	}
	
	function isTrainer($playerId){
		$out = false;
		if (in_array($playerId,$this->teamTrainers)){
			$out = true;
		}
		return $out;
	}
	
	function get_id(){
		return $this->id;
	}
	function get_headerFile(){
		$out = $this->headerFile;
		if (! $out){
			$out="headerGeneric.php";
		}
		return $out;
	}
	function getTrainer(){
		return $this->teamTrainer;
	}
	function getWebPage(){
		return $this->otherHomePage;	
	}
	function getWebPageLink(){
		if($this->otherHomePage){
			$out = makeLink($this->getWebPage(),"Hjemmeside",$this->getName("long"));
		}else{
			$out = "";	
		}
		return $out;
	}
	function getResultsPageLink(){
		if($this->otherHomePage){
			$out = makeLink($this->getResultsPage(),"Tabell og resultater");
		}else{
			$out = "";	
		}
		return $out;
	}
	function getResultsPage(){
		return $this->resultsUrl;	
	}
	/**
	* @return string
	* @param 'short|long'userName'password' $version
	* @desc Returns name of team
	*/
	function getName($version="short"){
		switch ($version){
			case "short":
				$out = $this->shortName;
			break;
			case "long":
				$out = $this->longName;
			break;
			case "userName":
				$out = $this->loginName;
			break;
			case "password":
				$out = $this->loginPassword;
			break;
			default:
				$out = $this->longName;
		}
		return $out;
	}
		/**
	* @return void
	* @desc Sets array with all events
	*/
	function _getAllEvents(){
		$teamId = $this->get_id();
		if(!$GLOBALS['_transient']['static']['otherteam_class'][$teamId]->eventsAreCollected){
			$this->allEvents = & $GLOBALS['_transient']['static']['otherteam_class'][$teamId]->allEvents;
			$this->comingEvents = & $GLOBALS['_transient']['static']['otherteam_class'][$teamId]->comingEvents;
			if(!$this->allEvents){
				$eventQuery_string = "
					SELECT *,
					IF(dateStart > NOW(), 1, 0) AS future
					FROM events WHERE team = '$teamId' ORDER BY dateStart ASC
				";
				
				$eventQuery = new DBConnector_class($eventQuery_string);
				
				while ($eventRow=$eventQuery->GetNextRow()){
					$this->allEvents[$eventRow["id"]] = $eventRow;
					if ($eventRow["future"]==1){
						$this->comingEvents[] = $eventRow["id"];	
					}
				}
			}
			
			$GLOBALS['_transient']['static']['otherteam_class'][$teamId]->eventsAreCollected=true;
		}
	}
	/**
	* @return int
	* @param int $nr
	* @desc Returns id of next event
	*/
	function getNextEvent($number=0){
		$this->_getAllEvents();
		if (count($this->comingEvents)){
			$out = $this->comingEvents[$number];	
		}else{
			$out = 0;
		}
		return $out;
	}
	/**
	* @return string
	* @param int $id
	* @param string $fieldName
	* @desc Returns information on any event within team history
	*/
	function getEventInfo($id, $fieldName){
		$this->_getAllEvents();
		return $this->allEvents[$id][$fieldName];	
	}
	/**
	* @return boolean
	* @param int $eventId
	* @desc Tells if event is of this team
	*/
	function getEventIsWithinTeam($eventId){
		$this->_getAllEvents();
		$out =  array_key_exists($eventId,$this->allEvents);
		return $out;	
	}
}

class OtherTeam_class extends Team_class {
	var $allEvents, $comingEvents;
	function OtherTeam_class($id){
		$this->id = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->id;
		$this->shortName = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->shortName;
		$this->longName = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->longName;
		$this->loginName = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->loginName;
		$this->loginPassword = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->loginPassword;
		$this->headerFile = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->headerFile;
		$this->otherHomePage = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->otherHomePage;
		$this->resultsUrl =  & $GLOBALS['_transient']['static']['otherteam_class'][$id]->resultsUrl;
		$this->teamTrainers = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->teamTrainers;
		$this->allEvents = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->allEvents;
		$this->comingEvents = & $GLOBALS['_transient']['static']['otherteam_class'][$id]->comingEvents;
		if($id != $this->id){
			$this->id = $id;
			$this->_makeQuery();
			$this->_findTrainers();
		}
	}
	function _makeQuery(){
				
		/* Get team info */

		$query_TeamInfo = "
		SELECT * 
		FROM teams 
		WHERE id='".$this->get_id()."'
		";
		
		$TeamInfo = new DBConnector_class($query_TeamInfo);
		
		$row_TeamInfo = $TeamInfo->GetNextRow();
		$this->shortName = $row_TeamInfo['shortName'];
		$this->longName = $row_TeamInfo['longName'];
		$this->loginName = $row_TeamInfo['loginName'];
		$this->loginPassword = $row_TeamInfo['loginPassword'];
		$this->headerFile = $row_TeamInfo['headerFile'];
		$this->otherHomePage = $row_TeamInfo['otherHomePage'];
		$this->resultsUrl = $row_TeamInfo['resultsUrl'];
		
	}
}

class Output_class {
	var $heading;
	var $main;
	var $title;
	/**
	* @return void
	* @desc Send headers to client to inhibit caching
	*/
	function SendNoCacheHeaders(){
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		header("Expires: 0");	
	}
	/**
	* @return html
	* @desc Wraps the content of heading in head-tag
	*/
	function _outputHeading(){
		
		$out = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">";
		$out .= "<link rel=\"shortcut icon\" href=\"/favicon.ico\" />";
		$out .= "<link rel=\"author\"  href=\"http://skaar.freeshell.org/\" />";
		$out .= "<link rel=\"home\"  href=\"http://skaar.freeshell.org/innebandy/index.html\" />";
		$out .= "<link rel=\"copyright\"  href=\"javascript:alert('Copyright 2002-2004, Skaar.')\" />";
		$out .= "<head>\n";
		$out .= $this->heading;
		$out .= "\n<title>$this->title</title>";
		$out .= "\n</head>";
		return $out;
	}
	/**
	* @return html
	* @desc Wraps the content of body in body-tag
	*/
	function _outputMain(){
		$out = "\n<body>";
		$out .= "\n$this->main";
		$out .= "\n</body>";
		return $out;
	}
	/**
	* @return html
	* @desc returns a wrapped html-page
	*/
	function Output(){
		$this->SendNoCacheHeaders();
		
		$out = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
		$out .= "<link href=\"innebandy.css\" rel=\"stylesheet\" type=\"text/css\">";
		
		$out .= $this->_outputHeading();
		$out .= $this->_outputMain();
		return $out;
	}
	/**
	* @return void
	* @param string $headerLine
	* @desc To add a line to the header
	*/
	function AddToHeader($headerLine){
		$this->heading .= "\n$headerLine";
	}
	/**
	* @return void
	* @param string $title
	* @desc Sets the title of the html-document
	*/
	function SetPageTitle($title){
		$this->title = $title;
	}
	/**
	* @return void
	* @param string $content
	* @param boolean $noNewLine
	* @desc Adds content to the main variable to be output by Output-function
	*/
	function AddToMain($content, $noNewLine=false){
		$this->main .= $noNewLine ? "" : "\n";
		$this->main .= $content;	
	}
	/**
	* @return html
	* @param string $scriptString
	* @desc Wraps code in javascript-tags
	*/
	function JavaScriptWrapper($scriptString){
		$out = '
		<script language="JavaScript" type="text/JavaScript">
		<!--	
		';
		$out .= $scriptString;
		$out .= '
		//-->
		</script>
		';
		return $out;	
	}
}

class AnswerLiterals_class {
	var $answers, $answersOrdered;
	function AnswerLiterals_class(){
		$this->answers = & $GLOBALS['_transient']['static']['answerliterals']->answers;
		$this->answersOrdered = & $GLOBALS['_transient']['static']['answerliterals']->answersOrdered;
		if (count ($this->answers)==0){
			$this->_GetAnswerLiteralsFromDB();
		}
	}
	function _GetAnswerLiteralsFromDB(){
		$answers_querystring = "
			SELECT * FROM attentionType ORDER BY listOrder
		";
		$answers_query = new DBConnector_class($answers_querystring);
		
		while($row_Answers = $answers_query->GetNextRow()){
			$this->answers[$row_Answers['id']] = $row_Answers;
			$this->answersOrdered[] = $row_Answers;
		}
		$this->answers[0] = "";
	}
	function GetShortName($id){
		return $this->answers[$id]["shortName"];	
	}
	function GetLongName($id){
		return $this->answers[$id]["type"];	
	}
	function GetNumberOfReplyTypes(){
		return count($this->answers);	
	}
	/**
	* @return void
	* @param unknown $order
	* @desc Returns id of attentionType from order in list (1-relative)
	*/
	function GetIdFromOrder($order){
		return $this->answersOrdered[$order-1]['id'];
	}
	
}

class OtherPlayer_class{
	var $teamId, $playerId, $playerData;
	/**
	* @return OtherPlayer_class
	* @param int $playerId
	* @desc Contains information on any given player
	*/
	function OtherPlayer_class($playerId, $teamId=0){
		
		$this->teamId = & $GLOBALS['_transient']['static']['otherplayer']->teamId;
		$this->playerId = & $GLOBALS['_transient']['static']['otherplayer']->playerId;
		$this->playerData = & $GLOBALS['_transient']['static']['otherplayer']->playerData;

		if ($teamId == 0){
			//Get current team-id
			$thisTeam = new Team_class();
			$newTeamId = $thisTeam->get_id();
		}else{
			$newTeamId = $teamId;	
		}
		if (($newTeamId != $this->teamId) || ($playerId != $this->playerId)){
			$this->teamId = $newTeamId;
			$this->playerId = $playerId;
			$this->_updateInformation();	
		}
	}	
	function _updateInformation(){
		$playerId = $this->playerId;
		$teamId = $this->teamId;
		$query_string = "
			SELECT 
				players.*, 
				membership.number, membership.teamTrainer, membership.notes,
				membership.reminders, membership.deleted, membership.noMessageByEmail
			FROM players LEFT JOIN membership ON players.id = membership.player
			WHERE players.id = '$playerId' AND membership.team = '$teamId'
		";
		$query = new DBConnector_class($query_string);
		$this->playerData = $query->GetNextRow();
	}
/**
 * @return string
 * @param "full"|"friendly"|"first"|"last" $type
 * @desc Returns name of player
*/
	function GetName($type="full"){
		$out = "";
		switch ($type){
			case "full":
				$out = $this->playerData["firstName"]." ".$this->playerData["lastName"];
			break;
			case "friendly":
				$out = PlayerName($this->playerData["firstName"],
						$this->playerData["lastName"],
						$this->playerData["email"],
						$this->playerData["teamTrainer"],
						false);
			break;
			case "first":
				$out = $this->playerData["firstName"];
			break;
			case "last":
				$out = $this->playerData["lastName"];
			break;
		}
		return $out;	
	}
	function GetEmail(){
		return $this->playerData["email"];	
	}
}

class Remedies_class{
	var $fieldNameBase, $allValues, $allValuesById, $maxValue;

	function Remedies_class(){
		
		$this->fieldNameBase = & $GLOBALS['_transient']['static']['remedies']->fieldNameBase;
		$this->allValues = & $GLOBALS['_transient']['static']['remedies']->allValues;
		$this->allValuesById = & $GLOBALS['_transient']['static']['remedies']->allValuesById;
		$this->maxValue = & $GLOBALS['_transient']['static']['remedies']->maxValue;
		
		$this->_GetData();
	}
	function _GetData(){
		if ($this->maxValue == 0){
			$queryString = "
				SELECT * FROM remedies ORDER BY listOrder
			";
			$remedyQuery = new DBConnector_class($queryString);
			$this->allValues = $remedyQuery->GetAllRows();
			$maxVal = 0;
			foreach ($this->allValues as $remedy) {
				$maxVal = max($maxVal, $remedy['id']);
				$this->allValuesById[$remedy['id']] = $remedy;
			}
			$this->maxValue = $maxVal;
			
			
			$this->fieldNameBase = "remedyField";
		}
	}
	/**
	* @return int
	* @desc Returns the value of all remedies combined
	*/
	function _GetValueOfAllRemedies(){
		$out = 0;
		$allIds = array();	
		foreach ($this->allValues as $remedy){
			$allIds[] = $remedy['id'];	
		}
		$out = $this->_GetValueFromArray($allIds);
		return $out;
	}
	/**
	* @return int
	* @param int[] $arrayOfRemedies
	* @desc Returns value to put in db from an array of remedy-id's
	*/
	function _GetValueFromArray($arrayOfRemedies){
		$out = 0;
		foreach ($arrayOfRemedies as $remedyId){
			$out += pow(2,$remedyId-1);	
		}
		return $out;	
	}

	/**
	* @return int[]
	* @param int $remedyValue
	* @desc Returns array of int's from value from db
	*/
	function _GetArrayFromValue($remedyValue){
		$out = array();
		for($possibleRemedy = 1 ; $possibleRemedy <= $this->maxValue ; $possibleRemedy++){
			if($this->_GetIsRemedyInValue($remedyValue, $possibleRemedy)){
				$out[] = $possibleRemedy;	
			}
		}
		return $out;
	}
	/**
	* @return boolean
	* @param int $remedyValue
	* @param int $remedyId
	* @desc Returns true if remedyId is included in value
	*/
	function _GetIsRemedyInValue($remedyValue, $remedyId){
		$idValue = pow(2,$remedyId-1);
		$out = (($remedyValue & $idValue) == $idValue);
		return $out;	
	}
	/**
	* @return html
	* @param int $value
	* @desc Returns html of controls of each remedy
	*/
	function _RenderTablesWithControls($valueSelected, $valueVisible, $lineBreakEvery=2){
		$out = "\n";
		$javaScript = "
			function ToggleCheck(item){
				item.checked = !item.checked;
			}
		";
		$out .= JavaScriptWrapper($javaScript);
		$remedies = $this->_GetArrayFromValue($valueVisible);
		$remedyCount = 0;
		foreach ($remedies as $remedy){
			$remedyCount++;
			
			$out .= "\t";
			if ($remedyCount > $lineBreakEvery){
				$remedyCount =1;
				$out .= "<br>";
			}
			$fileName = $this->GetIconFileName($remedy);
			$width = $this->GetIconSizeX($remedy);
			$height = $this->GetIconSizeY($remedy);
			$description = str_replace(" ","&nbsp;",$this->GetDescription($remedy));
			$longName = str_replace(" ","&nbsp;",$this->GetLongName($remedy));
			$selected = $this->_GetIsRemedyInValue($valueSelected, $remedy) ? " checked" : "";
			$controlName = $this->fieldNameBase.$remedy;
			//Icon
			$out .= "<span onClick='ToggleCheck(document.getElementById(\"$controlName\"));'>";
			$out .= "<img src=\"$fileName\" width = \"$width\" height = \"$height\" alt = \"$description\" align=\"absmiddle\">";
			//Short name
			$out .= "$longName";
			$out .= "</span>";
			//Check box
			$out .= "<input type=\"checkbox\" name=\"$controlName\" id=\"$controlName\" value=\"$remedy\"$selected>";
			$out .= "&nbsp;&nbsp;\n";
		}
		$out .= "\n";
		return $out;	
	}
	/**
	* @return URL
	* @param int $id
	* @desc Returns url of iconfile of given remedy
	*/
	function GetIconFileName($id){
		return $this->allValuesById[$id]['iconFile'];
	}
	/**
	* @return int
	* @param int $id
	* @desc Returns width of icon of given remedy
	*/
	function GetIconSizeX($id){
		return $this->allValuesById[$id]['iconFileSizeX'];	
	}
	/**
	* @return int
	* @param int $id
	* @desc Returns height of icon of given remedy
	*/
	function GetIconSizeY($id){
		return $this->allValuesById[$id]['iconFileSizeY'];	
	}
	/**
	* @return string
	* @param int $id
	* @desc Returns short name of given remedy
	*/
	function GetShortName($id){
		return $this->allValuesById[$id]['shortName'];	
	}
	/**
	* @return string
	* @param int $id
	* @desc Returns long name of given remedy
	*/
	function GetLongName($id){
		return $this->allValuesById[$id]['longName'];	
	}
	/**
	* @return string
	* @param int $id
	* @desc Returns description of given remedy
	*/
	function GetDescription($id){
		return $this->allValuesById[$id]['description'];	
	}
	
	/**
	* @return html
	* @param int $value
	* @desc Renders html to use in event edit or create page (row with two cells)
	*/
	function RenderEventNeededRemediesControls($valueOfSelected=0){
		$out = "\n";
		$out .= "<tr>\n";
		$out .= "<td valign=\"top\">Noen m&aring; ta med seg:</td>\n";
		$out .= "<td valign=\"top\">".$this->_RenderTablesWithControls($valueOfSelected, $this->_GetValueOfAllRemedies(),2)."</td>\n";
		$out .= "\n</tr>\n";
		return $out;
	}
	/**
	* @return html
	* @param int $visibleValue
	* @param int $selectedValue
	* @desc Renders html to use in reply form (row with three cells)
	*/
	function RenderReplyNeededRemediesControl($visibleValue, $selectedValue){
		$out = "\n";
		$out .= "<tr>";
		$out .= "<td class=normalText>Jeg tar med:</td>";
		$out .= "<td colspan=\"2\">\n";
		$out .= $this->_RenderTablesWithControls($selectedValue,$visibleValue,3);
		$out .= "\n</td>\n";
		$out .= "</tr>";
		return $out;	
	}
	/**
	* @return int
	* @param boolean $post
	* @desc Reads data from Post or Get and returns value to put in DB
	*/
	function GetValueFromFields($post = true){
		$out = 0;
		$remedyids = array();
		foreach ($this->allValues as $remedy){
			$remedyId = $remedy['id'];
			$fieldvalue = 0;
			$fieldName = $this->fieldNameBase.$remedyId;
			if ($post){
				$fieldvalue = $_POST[$fieldName];
			}else{
				$fieldvalue = $_GET[$fieldName];
			}
			if ($fieldvalue > 0){
				$remedyids[] = $fieldvalue;	
			}
		}
		$out = $this->_GetValueFromArray($remedyids);
		return $out;	
	}
	/**
	* @return html
	* @param int $visibleValue
	* @param string $name
	* @desc Renders images of remedies with alt-text.
	*/
	function RenderReplyListIcons($visibleValue, $name=""){
		$remedies = $this->_GetArrayFromValue($visibleValue);
		
		$out = "\n";
		
		foreach ($remedies as $remedy){
			$altString = ($name=="") ? $this->GetDescription($remedy) : "$name har med ".strtolower($this->GetLongName($remedy));
			$fileName = $this->GetIconFileName($remedy);
			$width = $this->GetIconSizeX($remedy);
			$height = $this->GetIconSizeY($remedy);
			$out .= "<img src=\"$fileName\" width=\"$width\" height=\"$height\" alt=\"$altString\" align=\"absmiddle\">";
		}
		
		$out .= "\n";
		return $out;	
	}
	/**
	* @return html
	* @param int $visibleValue
	* @param string $name
	* @desc Renders text of remedies with alt-text.
	*/
	function RenderReplyListText($visibleValue, $name=""){
		$remedies = $this->_GetArrayFromValue($visibleValue);
		$out = "";
		if(count($remedies))
		{
			$out.=($name=="")?"Vedkommende":$name;
			$out.=" sitter p&aring; ";
			$remedyStrings=array();
			foreach ($remedies as $remedy){
				$remedyStrings[] = strtolower($this->GetShortName($remedy));
			}
			$out.=SeparatedList($remedyStrings);
		}
		$out .= "";
		return $out;	
	}
	/**
	* @return string
	* @param int $neededValue
	* @param string $preString
	* @desc Returns the string "Noen m� ha med " + list of remedies
	*/
	function GetStringDescribingNeed($neededValue, $preString = " Noen m&aring; ha med ",$html=true){
		$out = "";
		if ($neededValue > 0){
			$remedies = $this->_GetArrayFromValue($neededValue);
			$remedyNames = array();
			foreach($remedies as $remedy){
				$remedyNames[] = $this->GetShortName($remedy);	
			}
			$remedynameListString = SeparatedList($remedyNames);
			$out .= "{$preString}{$remedynameListString}. ";
		}
		if(!$html){
			$out=html_entity_decode($out);
		}
		return $out;	
	}
	/**
	* @return int[]
	* @param int $value
	* @desc Returns an array of int
	*/
	function GetArrayOfIds($value){
		return $this->_GetArrayFromValue($value);	
	}
	
}

class ReadMessages_class{
	var $cookieNameDate, $cookieNameList;
	var $listOfReadMsgs;
	function ReadMessages_class(){
		$this->cookieNameDate = "MsgReadDate";
		$this->cookieNameList = "MsgReadList";
		$this->listOfReadMsgs = $this->_UpdateListOfUnReadMsgs();
	}
	function _UpdateListOfUnReadMsgs(){
		$out = array();
		$dateOfOldMsg = $this->GetDateOfOldMsg();
		$queryString = "
			SELECT id
			FROM messages
			WHERE date >= FROM_UNIXTIME($dateOfOldMsg)
		";
		$queryObj = new DBConnector_class($queryString);
		while($nextMsg = $queryObj->GetNextRow()){
			$out[] = $nextMsg['id'];
		}
		return $out;
	}
	function GetDateOfOldMsg(){
		$out = 0;
		if(isset($_COOKIE[$this->cookieNameDate])){
			$out = $_COOKIE[$this->cookieNameDate];
		}else{
			$out = $this->GetLastReadDate();
		}
		return $out;	
	}
	function MessageIsRead($msgId){
		
		return $out;	
	}
	function SetReadStatus($msgId){
		
	}
	function UpdateLastReadDate(){
		$player = new Player_class();
		$playerId = $player->getId();
		if ($playerId > 0){
			$updateString = "
				UPDATE players
				SET lastMessageRead = NOW()
				WHERE id = '$playerId'
			";
			$alterObj = new DBConnector_class($updateString);
		}
	}
	function GetLastReadDate(){
		$player = new Player_class();
		$playerId = $player->getId();
		$queryString = "
			SELECT lastMessageRead
			FROM players
			WHERE id = '$playerId'
		";
		$queryObj = new DBConnector_class($queryString);
		$date = strtotime($queryObj->GetNextRow('lastMessageRead'));
		return $out;
	}
	
}


?>