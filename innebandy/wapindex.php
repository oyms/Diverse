<?php require_once('Connections/innebandybase.php');

header('Content-type: text/vnd.wap.wml');
/*header('Content-type: txt/plain');*/

require_once('Connections/innebandybase.php');

	/* Finner navn på headerfile */

	$authUserName=$_SERVER['PHP_AUTH_USER'];
	
	mysql_select_db($database_innebandybase, $innebandybase);
	$query_teamHeaderfile = "SELECT id, headerFile FROM teams WHERE userName='$authUserName';";
	$teamHeaderfile = mysql_query($query_teamHeaderfile, $innebandybase) or die(mysql_error());
	$row_teamHeaderfile = mysql_fetch_assoc($teamHeaderfile);
	$totalRows_teamHeaderfile = mysql_num_rows($teamHeaderfile);

	$teamId=$row_teamHeaderfile['id'];
	
	mysql_free_result($teamHeaderfile);


mysql_select_db($database_innebandybase, $innebandybase);
$query_NextEvent = "SELECT events.id, events.description, events.dateStart, events.dateEnd, events.location, eventTypes.requiredCount, events.cancelled, events.notes, events.team FROM eventTypes INNER JOIN events ON eventTypes.id = events.type WHERE (((events.dateStart)>Now()) AND (events.team = $teamId)) ORDER BY events.dateStart LIMIT 1;";
$NextEvent = mysql_query($query_NextEvent, $innebandybase) or die(mysql_error());
$row_NextEvent = mysql_fetch_assoc($NextEvent);
$totalRows_NextEvent = mysql_num_rows($NextEvent);

mysql_select_db($database_innebandybase, $innebandybase);
$query_PlayerInfo = "SELECT players.firstName, players.lastName, players.phone, attention.notes, attentionType.type
FROM (players LEFT JOIN attention ON players.id = attention.player) LEFT JOIN attentionType ON attention.type = attentionType.id
WHERE (((attention.event)=".$row_NextEvent['id'].")) 
ORDER BY attentionType.value DESC;";
$PlayerInfo = mysql_query($query_PlayerInfo, $innebandybase) or die(mysql_error());
$row_PlayerInfo = mysql_fetch_assoc($PlayerInfo);
$totalRows_PlayerInfo = mysql_num_rows($PlayerInfo);

mysql_select_db($database_innebandybase, $innebandybase);
$query_attentionsNext = 
"SELECT attention.type, Count(attention.id) AS countAttentions, events.id
FROM (events LEFT JOIN attention ON events.id = attention.event) LEFT JOIN attentionType ON attention.type = attentionType.id
GROUP BY attention.type, events.id, attentionType.value
HAVING (((events.id)=".$row_NextEvent['id']."))
ORDER BY attentionType.value DESC;";
$attentionsNext = mysql_query($query_attentionsNext, $innebandybase) or die(mysql_error());
$row_attentionsNext = mysql_fetch_assoc($attentionsNext);
$totalRows_attentionsNext = mysql_num_rows($attentionsNext);


mysql_select_db($database_innebandybase, $innebandybase);
$query_attentionTypes = "SELECT * FROM attentionType;";
$attentionTypes = mysql_query($query_attentionTypes, $innebandybase) or die(mysql_error());
$row_attentionTypes = mysql_fetch_assoc($attentionTypes);
$totalRows_attentionTypes = mysql_num_rows($attentionTypes);

mysql_select_db($database_innebandybase, $innebandybase);
$query_confirmed = "SELECT events.id, attention.type, Count(attention.id) AS confirmedCount FROM events INNER JOIN attention ON events.id = attention.event GROUP BY events.id, attention.type HAVING (((events.id)=".$row_NextEvent['id'].") AND ((attention.type)=2));";
$confirmed = mysql_query($query_confirmed, $innebandybase) or die(mysql_error());
$row_confirmed = mysql_fetch_assoc($confirmed);
$totalRows_confirmed = mysql_num_rows($confirmed);

mysql_select_db($database_innebandybase, $innebandybase);
$query_allPlayers = "SELECT * 
					 FROM players INNER JOIN membership 
					 ON players.id = membership.player
					 WHERE membership.team=$teamId
					 ORDER BY lastName ASC;";
$allPlayers = mysql_query($query_allPlayers, $innebandybase) or die(mysql_error());
$row_allPlayers = mysql_fetch_assoc($allPlayers);
$totalRows_allPlayers = mysql_num_rows($allPlayers);

$norWeekDays=array ("søndag","mandag","tirsdag","onsdag","torsdag","fredag","lørdag");
do {
	$attTypes_array[$row_attentionTypes['id']]=$row_attentionTypes['type'];
} while ($row_attentionTypes = mysql_fetch_assoc($attentionTypes));

echo '<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.3//EN" "http://www.wapforum.org/DTD/wml13.dtd" >
<wml>
<card id="Neste" title="'.$row_NextEvent['description'].'" ordered="false" newcontext="false">
<do name="Liste" type="go" label="tlf-liste" optional="true">
<go href="#liste" method="get" sendreferer="false"/>
</do>
';

echo '<p>'.$norWeekDays[date ("w",strtotime ($row_NextEvent['dateStart']))].' '.date ("j/n-y H:i",strtotime ($row_NextEvent['dateStart']));
if ($row_NextEvent['cancelled']) {
	echo '<br/><em>Avlyst!</em>';
}
echo '<br/>'
.$row_NextEvent['location']
.'<br/>Antall bekreftede: ';
echo $row_confirmed['confirmedCount'] ? $row_confirmed['confirmedCount'] : '0';
echo '</p>
	 ';
echo $row_NextEvent['notes'] ?'<p>'.$row_NextEvent['notes'].'</p>' : '';
echo '
	 ';
echo '<p><strong>Oppsummering</strong></p>';

echo '
<p>
<table columns="2">';
do {
	echo '
		  <tr>
		  <td>'.$attTypes_array[$row_attentionsNext['type']]
	.':
		  </td>
		  <td>'
	.$row_attentionsNext['countAttentions']
	.'
		</td>
		</tr>';
} while ($row_attentionsNext = mysql_fetch_assoc($attentionsNext));

echo '
</table>
</p>
';

echo '<p><strong>Alle svar</strong><br/>';

do {
	echo '<em>'.$row_PlayerInfo['firstName'].' '.$row_PlayerInfo['lastName'].'</em>:<br/>
	'.$row_PlayerInfo['type'].' 
	(tlf: '.$row_PlayerInfo['phone'].') 
	'.$row_PlayerInfo['notes'].'<br/>';
} while ($row_PlayerInfo = mysql_fetch_assoc($PlayerInfo));
echo '</p>';

echo '</card>
<card id="liste" title="Telefonliste" ordered="false" newcontext="false"> 
<do name="Neste" type="go" label="'.$row_NextEvent['description'].'" optional="true">
<go href="#neste" method="get" sendreferer="false"/>
</do>
';

echo '<p>
<table title="Alle sammen" columns="2" align="L">';
$everyOther=true;
do {
	$everyOther= (! $everyOther);
	echo '
<tr>
<td>';
	echo $everyOther ? '<em>' : '';
	echo $row_allPlayers['firstName'].' '.$row_allPlayers['lastName'];
	echo $everyOther ? '</em>' : '';
	echo '</td>
<td>';
	echo $everyOther ? '<em>' : '';
	echo $row_allPlayers['phone'];
	echo $everyOther ? '</em>' : '';
	echo'</td>
</tr>';
} while ($row_allPlayers = mysql_fetch_assoc($allPlayers));
echo '</table>
</p>';

echo '</card>';
echo '</wml>'; 
mysql_free_result($PlayerInfo); 
mysql_free_result($NextEvent); 
mysql_free_result($attentionsNext); 
mysql_free_result($attentionTypes); 
mysql_free_result($confirmed);
mysql_free_result($allPlayers);
?>
