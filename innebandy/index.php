<?php
//ini_set("display_errors",1);
//error_reporting(E_ALL);
 // Because this script sends out HTTP header information, the first characters in the file must be the <? PHP tag.


header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Expires: 0");

require_once('notify.inc');
require_once('commonCode.inc');



/* Redirect */

/*          */



  $htmlredirect = "index.html";                          // relative URL to your HTML file

  $wmlredirect = "http://skaar.freeshell.org/innebandy/wapindex.php";         // ABSOLUTE URL to your WML file



  if(strpos(strtoupper($_SERVER["HTTP_ACCEPT"]),"VND.WAP.WML") > 0) {        // Check whether the browser/gateway says it accepts WML.

    $br = "WML";

  }

  else {

    $browser=substr(trim($_SERVER["HTTP_USER_AGENT"]),0,4);

    if($browser=="Noki" ||			// Nokia phones and emulators

      $browser=="Eric" ||			// Ericsson WAP phones and emulators

      $browser=="WapI" ||			// Ericsson WapIDE 2.0

      $browser=="MC21" ||			// Ericsson MC218

      $browser=="AUR " ||			// Ericsson R320

      $browser=="R380" ||			// Ericsson R380

      $browser=="UP.B" ||			// UP.Browser

      $browser=="WinW" ||			// WinWAP browser

      $browser=="UPG1" ||			// UP.SDK 4.0

      $browser=="upsi" ||			// another kind of UP.Browser ??

      $browser=="QWAP" ||			// unknown QWAPPER browser

      $browser=="Jigs" ||			// unknown JigSaw browser

      $browser=="Java" ||			// unknown Java based browser

      $browser=="Alca" ||			// unknown Alcatel-BE3 browser (UP based?)

      $browser=="MITS" ||			// unknown Mitsubishi browser

      $browser=="MOT-" ||			// unknown browser (UP based?)

      $browser=="My S" ||                       // unknown Ericsson devkit browser ?

      $browser=="WAPJ" ||			// Virtual WAPJAG www.wapjag.de

      $browser=="fetc" ||			// fetchpage.cgi Perl script from www.wapcab.de

      $browser=="ALAV" ||			// yet another unknown UP based browser ?

      $browser=="Wapa")                         // another unknown browser (Web based "Wapalyzer"?)

        {

        $br = "WML";

    }

    else {

      $br = "HTML";

    }

  }



  if($br == "WML") {

     	header("Status: 302 Moved Temporarily");       // Force the browser to load the WML file   

		header("Location: ".$wmlredirect);

		exit;

  }else{

  	$thisTeam = new Team_class();

  	$headerFile = $thisTeam->get_headerFile();

  	$rssLink = RSSlink("alternate",true);
	
	$icalLink = IcalLink();

  	$eventUrl = GetDetailsUrl();

  	if (($eventUrl == $playerListFileName) && ! GetPlayerIsDefined()){

  		$eventListUrl = "eventfiller.html";

  		$messageListUrl = "images/score.jpg";

  	}else{

  		$eventListUrl = $eventListFileName;

  		$messageListUrl = $messageListFileName;	

  	}

    echo <<<EOT

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd" />

<html>

<head>

<LINK REL="shortcut icon" HREF="/favicon.ico" />

<link rel="apple-touch-icon" href="images/apple-touch-icon.png"/>

<link rel="author"  href="http://skaar.freeshell.org/" />

<link rel="home"  href="http://skaar.freeshell.org/innebandy/index.html" />

<link rel="copyright"  href="javascript:alert('Copyright 2002-2003, Skaar.')" />

$rssLink

$icalLink


<title>Hvem kommer?&iquest; - $teamName</title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<meta http-equiv="author" content="�yvind Skaar" />

</head>



<frameset rows="100,*" cols="*" framespacing="1"" frameborder="NO" border="1" bordercolor="#0000FF">

  <frame src="$headerFile" name="$headerFrameName" scrolling="no" id="header" >

  <frameset rows="*" cols="*,*" framespacing="1" frameborder="yes" border="1" bordercolor="#0000FF">

    <frame src="$eventUrl"  name="$detailsFrameName" frameborder="yes" bordercolor="#0000FF" id="$detailsFrameName">

    <frameset rows = "70%,*", cols="*", framespacing = "1", frameborder="yes" border="1" bordercolor="#0000FF">

	    <frame src="$eventListUrl"  name="$listFrameName" id="$listFrameName">

		<frame src="$messageListUrl" name="$messageListFrameName" id="$messageListFrameName">

  	</frameset>

  </frameset>

</frameset>

<noframes><body>

<h1>Innebandy</h1>

<p>Du m&aring; skaffe deg en nettleser som st&oslash;tter rammesider.</p>

</body></noframes>

</html>



EOT

;}





function GetDetailsUrl(){

	global $eventDetailsFileName, $playerListFileName, $messageListFileName, $playerListFileName;

	global $playerIdFieldName,$eventFieldname,$attentionFieldName,$messageIdFieldName;

	global $messageDetailsFilename;

	

	$player = new Player_class();

	$playerId = $player->getId();

	if($playerId && isset($_GET[$eventFieldname]) ){

		$eventId = $_GET[$eventFieldname];

		$vars = "?{$playerIdFieldName}={$playerId}";

		$vars .= "&{$eventFieldname}={$eventId}";

		if(isset($_GET[$attentionFieldName])){

			$reply = $_GET[$attentionFieldName];

			$vars .= "&{$attentionFieldName}={$reply}";

		}

		$filename = $eventDetailsFileName;

	}elseif($playerId && isset($_GET[$messageIdFieldName])){

		$messageId = $_GET[$messageIdFieldName];

		$vars = "?{$playerIdFieldName}={$playerId}";

		$vars .= "&{$messageIdFieldName}={$messageId}";

		$filename = $messageDetailsFilename;

	}else{

		$vars = "";

		$filename = $playerListFileName;	

	}

	$out = $filename.$vars;



	return $out;

	

}



function GetPlayerIsDefined(){

	$player = new Player_class();

	$playerId = $player->getId();

	return ($playerId > 0);	

}

?>
