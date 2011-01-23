<?php 

require_once('Connections/innebandybase.php');
require_once('mylib.inc');
require_once('commonCode.inc');

/* Alle grupper */

mysql_select_db($database_innebandybase, $innebandybase);
$query_AllGroups = "SELECT id, password, userName, longName FROM teams;";
$AllGroups = mysql_query($query_AllGroups, $innebandybase) or die(mysql_error());
$row_AllGroups = mysql_fetch_assoc($AllGroups);
$totalRows_AllGroups = mysql_num_rows($AllGroups);

/* Finner navn p� team */
$authUserName=$_SERVER['PHP_AUTH_USER'];

mysql_select_db($database_innebandybase, $innebandybase);
$query_teamHeaderfile = "SELECT id, longName, headerFile FROM teams WHERE userName='$authUserName';";
$teamHeaderfile = mysql_query($query_teamHeaderfile, $innebandybase) or die(mysql_error());
$row_teamHeaderfile = mysql_fetch_assoc($teamHeaderfile);
$totalRows_teamHeaderfile = mysql_num_rows($teamHeaderfile);

$teamHeaderFilename = $row_teamHeaderfile['headerFile'];
$teamId=$row_teamHeaderfile['id'];
$teamName=$row_teamHeaderfile['longName'];

mysql_free_result($teamHeaderfile);

/* Definere svarvarianter */
mysql_select_db($database_innebandybase, $innebandybase);
$query_replyVariants = "SELECT * FROM attentionType ORDER BY listOrder DESC";
$replyVariants = mysql_query($query_replyVariants, $innebandybase) or die(mysql_error());
$totalRows_replyVariants = mysql_num_rows($replyVariants);

$attentionIndex=0;
 while ($row_replyVariants = mysql_fetch_assoc($replyVariants));  {
    $attentionTypes[$attentionIndex]['id']=$row_replyVariants['id'];
    $attentionTypes[$attentionIndex]['type']=$row_replyVariants['type'];
    $attentionTypes[$attentionIndex]['shortName']=$row_replyVariants['shortName'];
    $attentionIndex++;
}

mysql_free_result($replyVariants);

/* Felles e-postvariabler */
$email_importance="Importance: Low";
$email_boundary="-------******-------"; 
$email_contenttype='Content-Type: multipart/alternative; boundary="'.$email_boundary.'"';
$email_mimemessage="This is a multi-part message in MIME format.";
$email_contenttype1="Content-Type: text/plain; charset=iso-8859-1";
$email_contenttype2="Content-Type: text/html; charset=iso-8859-1";
$email_mainUrl=HOMEURL;
$email_pageUrl="eventdetails.php";

do {
	$email_fullUrl="http://$row_AllGroups[userName]:$row_AllGroups[password]@$email_mainUrl";
	$teamId=$row_AllGroups['id'];
	
	/* Finner e-postadressen til oppmann */
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_teamTrainer = "SELECT  players.email
			FROM membership 
				LEFT JOIN players ON membership.player = players.id
			WHERE (((membership.team)=$teamId) AND ((membership.teamTrainer)=1))
			LIMIT 1";
	
	$teamTrainer = mysql_query($query_teamTrainer, $innebandybase) or die(mysql_error());
	$row_teamTrainer = mysql_fetch_assoc($teamTrainer);
	$totalRows_teamTrainer = mysql_num_rows($teamTrainer);
	
	if ($totalRows_teamTrainer){
		$trainerEmail = $row_teamTrainer['email'];
	}else{
		$trainerEmail = "skaar@freeshell.org";
	}
	
	
	/* Neste hendelse */ 
	mysql_select_db($database_innebandybase, $innebandybase); 
	$query_NextEvent = "
	SELECT events.id, events.type, events.description, events.dateStart, events.dateEnd, 
	events.location, eventTypes.requiredCount, events.cancelled, events.notes, 
	events.team, events.notified, events.dateNotification,
	eventTypes.invitationString AS eventTypeInvitation
	FROM eventTypes INNER JOIN events ON eventTypes.id = events.type 
	WHERE (
	((events.dateStart)>Now()) 
	AND (events.team = $teamId) 
	AND (((events.cancelled) IS NULL) OR (events.cancelled < 1)) 
	AND (dateNotification< NOW())
	AND ((events.notified < 1) OR (events.notified IS NULL)) 
	) 
	ORDER BY events.dateStart LIMIT 3
	"; 
	$NextEvent = mysql_query($query_NextEvent, $innebandybase) or die(mysql_error());  
	$totalRows_NextEvent = mysql_num_rows($NextEvent);

	
	
	/* Om noen hendelser er kandidat for p�minnelse */
	while ($row_NextEvent = mysql_fetch_assoc($NextEvent)) {
		
		$eventId=$row_NextEvent['id'];
		$eventType=$row_NextEvent['type'];
		$eventStart=date('d/m G:i',strtotime ($row_NextEvent['dateStart']));
			
		/* Finne alle spillere som allerede har svart */
		mysql_select_db($database_innebandybase, $innebandybase);
		$query_PlayerEx = "SELECT player FROM attention WHERE event ='$eventId';";
		$PlayerEx = mysql_query($query_PlayerEx, $innebandybase) or die(mysql_error());
		$row_PlayerEx = mysql_fetch_assoc($PlayerEx);
		$totalRows_PlayerEx = mysql_num_rows($PlayerEx);
		
		$PlayerEx_array=array();
		do {
			if ($row_PlayerEx['player']){
				$PlayerEx_array[]=$row_PlayerEx['player'];
			}
		} while ($row_PlayerEx = mysql_fetch_assoc($PlayerEx));
		mysql_free_result($PlayerEx);
		
		/* Finne alle spillere som har e-post og som ikke har svart
		   og som ikke har reservert seg mot henvendelser av denne typen */
		mysql_select_db($database_innebandybase, $innebandybase); 
		$query_AllPlayers = 
		"
			SELECT DISTINCT players.firstName, players.lastName, players.id, players.email,
					membership.team, membership.reminders,
					((pow(2," . $eventType . ")& membership.reminders)!=pow(2," . $eventType . ")) AS wantsReminder
			FROM (players INNER JOIN membership ON players.id = membership.player) 
			HAVING ((players.email>'0') 
			AND (membership.team=$teamId)
			AND (wantsReminder OR (membership.reminders IS NULL))";
		$query_AllPlayers .= 
				count($PlayerEx_array) ? 
				"AND (players.id NOT IN (".implode(',',$PlayerEx_array)."))" : 
				"";
		$query_AllPlayers .= ")
			ORDER BY players.lastName
		"; 
		
		
		
		$AllPlayers = mysql_query($query_AllPlayers, $innebandybase) or die(mysql_error()); 
		$row_AllPlayers	= mysql_fetch_assoc($AllPlayers); 
		$totalRows_AllPlayers = mysql_num_rows($AllPlayers);
		
		if ($totalRows_AllPlayers){
			/* Log-informasjon */
			
			$history = new EditorOfEvent_class($eventId);
			if ($history->GetEmailOfCreator() != ""){
				$fromAddress = $history->GetNameOfCreator()." <".$history->GetEmailOfCreator().">";	
			}elseif ($history->GetEmailOfEditor() != ""){
				$fromAddress = $history->GetNameOfEditor()." <".$history->GetEmailOfEditor().">";	
			}else{
				$fromAddress = "$row_AllGroups[longName] <$trainerEmail>";
			}
			
			
			$editorInfo = DescribeHistory($eventId, $row_AllPlayers['id'], false, false);

			/* Felles headere */
			$email_from = "From: $fromAddress";
			$email_subject = "$row_NextEvent[description] $eventStart";
			$email_expire = "Expiry-Date: ".date ("D, d M Y H:i:s +0100",strtotime($row_NextEvent['dateStart']));
			
			$underline = "";
			for ($i = 0; $i < strlen($row_NextEvent[description].", ".$row_NextEvent[location]." ".$eventStart); $i++){
					$underline .= "-";
			}
			
			/* For hver spiller som skal ha melding */
			do {
				$email_to = "$row_AllPlayers[firstName] $row_AllPlayers[lastName] <$row_AllPlayers[email]>";
				$email_header =  "$email_from\n$email_expire\n"; 
				$email_header .= "$email_importance\n";
				$email_header .= "$email_contenttype2";	
				
				/* Lage svarkoblinger */
				$replyLinks="";
				//$attentionTypes
				foreach ($attentionTypes as $attType){
					if($attType[id]){
						$replyLinks .= '<tr align="left">';
						$replyLinks .= "
						<td>
						 <a href='$email_fullUrl?$playerIdFieldName=$row_AllPlayers[id]&$eventFieldname=$row_NextEvent[id]&{$attentionFieldName}=$attType[id]'>$attType[type]</a>
						</td>";
						$replyLinks .= "
						<td>
						 <a href='http://$email_mainUrl?$playerIdFieldName=$row_AllPlayers[id]&$eventFieldname=$row_NextEvent[id]&{$attentionFieldName}=$attType[id]'>$attType[shortName]</a>
						</td>";
						$replyLinks .= "</tr>";
					}
				}

				/* Skrive e-posttekst */
				$email_body = <<<EOF
<html><head><!--
					
					
$row_NextEvent[description], $row_NextEvent[location] $eventStart
$underline
					
					
Hei $row_AllPlayers[firstName]!

$row_NextEvent[notes]

Du har ikke svart p� http://$email_mainUrl 
(brukernavn: $row_AllGroups[userName], passord: $row_AllGroups[password]) 
om du har anledning til � komme eller ikke.



P� denne siden kan du ogs� reservere deg mot � f� disse 
p�minnelsene, eller slette deg selv helt fra sidene. 
Klikk knappen *Redigere/slette min info* p�
venstresiden i bildet for � gj�re dette.


$underline
(Om du kan lese dette, er det antagelig fordi e-postleseren din
ikke forst�r html. Resten av e-posten vil derfor v�re tiln�rmet
uleselig.)
$underline
					
					
					
					
					
					
			
			
			
			
			
					
					
					
					
					
					-->
					<style type="text/css">
					<!--
					body {
						font-family: Arial, Helvetica, sans-serif;
						font-size: 14px;
						margin-left: 60px;
						margin-right: 60px;
					}
					h1 {
						font-family: "Times New Roman", Times, serif;
						font-weight: bold;
						font-variant: small-caps;
						color: #0000FF;
					}
					.smallCenteredText {
						font-size: xx-small;
						text-align: center;
					}
					-->
					</style>
					</head>
					<body>
					<h1>
					<a name="top"></a>$row_NextEvent[description], $row_NextEvent[location] $eventStart
					</h1>
					<p>
					<strong>Hei $row_AllPlayers[firstName]!</strong><br>
					$row_NextEvent[notes]
					</p>
					<p>
					Du har ikke svart p&aring; <a href="http://$email_mainUrl">
					http://$email_mainUrl</a> 
					(brukernavn: $row_AllGroups[userName], 
					passord: $row_AllGroups[password]) 
					om du har anledning til &aring; komme eller ikke.
					</p>
					<p>
					Du kan se all informasjon om denne hendelsen <a href="$email_fullUrl?$playerIdFieldName=$row_AllPlayers[id]&$eventFieldname=$row_NextEvent[id]">
					her</a>.
					</p>
					<p>
					<a name="replies"></a>Du kan ogs&aring; svare direkte med disse koblingene:
					<br>
					<font size="2">Om du f&aring;r feilmeldingen &laquo;<samp>Invalid syntax error</samp>&raquo;
					i tittellinjen p&aring; Internet Explorer, og &laquo;<samp>The page cannot be displayed</samp>&raquo; i
					teksten n&aring;r du bruker koblingene til venstre, bruk koblingene til h&oslash;yre eller <a href="#notes3">les om
					fiksen</a> nede i denne meldingen. </font>
					</p>
					<table width="438" border="0">
                      <tr align="left">
                        <th>Andre nettlesere</th>
                        <th>Internet Explorer</th>
                      </tr>
                      $replyLinks
                    </table>
					<p>
					<em>Disse koblingene er personlige. Det er derfor ingen god ide &aring; videresende denne meldingen til noen.</em>                     </p>
					<p>
					Du kan alltid se hvem som er p&aring;meldt neste hendelse ved &aring; bruke mobiltelefonen. Logg p&aring; med WAP p&aring; den samme adressen:
					</p>
					<p>
					<address>http://$email_mainUrl</address>
					<br>
					Brukernavn: <em>$row_AllGroups[userName]</em>
					<br>
					Passord: <em>$row_AllGroups[password]</em>
					</p>
					<p>
					<a name="notes1"></a>N&aring;r du har lagt inn ditt svar, kan du laste ned
					en kalenderfil fra siden. Denne kan lagres i kalenderen din i Outlook eller
					Notes. </p>
					<p><a name="notes2" id="notes2"></a>N&aring; kan du reservere deg mot e-postp&aring;minnelser ved � klikke
					  knappen &laquo;<em>Redigere/Slette min info</em>&raquo; i
					spillerlisten. Her kan du ogs&aring; slette deg selv fra sidene fullstendig.</p>
					<p><a name="notes3" id="notes3"></a>Microsoft har kommet med en oppdatering til Microsoft
					  Explorer som gj&oslash;r at brukernavn:passord ikke lenger kan
					  legges inn i koblinger eller skrives inn i adressefeltet,
					  slik koblingene i denne meldingen gj&oslash;r. Det betyr at om
					  du bruker Windows og Internet Explorer, vil ikke alle koblingene
					  virke som f&oslash;r.</p>
					<p>Du kan velge &aring; bruke koblingene uten innbakt brukernavn og passord, eller
					  endre innstillingene p&aring; maskinen slik at funksjonaliteten
					  er som f&oslash;r. <a href="http://support.microsoft.com/default.aspx?scid=kb;EN-US;834489">Du
					  kan lese om dette i KB-artikkel 834489</a> p&aring; http://support.microsoft.com.
					  Jeg har laget en Windows installer-fil som du kan kj&oslash;re
					  for &aring; endre disse innstillingene. <a href="http://skaar.freeshell.org/innebandy/IEpasswordInURL.msi">Last
					  den ned og kj&oslash;r den her</a>, om du vil. </p>
					<p>
					S&aring; sees vi!<br>
					<em>Hilsen masekopproboten!</em>
					</p>
					$editorInfo
					</body>
					</html>				
EOF
				;
					
				mail($email_to, $email_subject, $email_body, $email_header);
			} while ($row_AllPlayers = mysql_fetch_assoc($AllPlayers));
		}
		
		mysql_free_result($AllPlayers);
		
		/* Registrere i hendelsen at p�minnelse er sendt */
		UpdateEvent($eventId);
	}
	mysql_free_result($NextEvent); 
} while ($row_AllGroups = mysql_fetch_assoc($AllGroups));

mysql_free_result($AllGroups);

?>
