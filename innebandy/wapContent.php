<?php 
/*header("Content-type: text/vnd.wap.wml");*/
header('Content-type: text/plain');

require_once('Connections/innebandybase.php');

mysql_select_db($database_innebandybase, $innebandybase);
$query_NextEvent = "SELECT events.id, events.description, events.dateStart, events.dateEnd, events.location, eventTypes.requiredCount, events.cancelled, events.notes FROM eventTypes INNER JOIN events ON eventTypes.id = events.type WHERE (((events.dateStart)>Now())) ORDER BY events.dateStart LIMIT 1;";
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

$norWeekDays=array ("søndag","mandag","tirsdag","onsdag","torsdag","fredag","lørdag");
do {
	$attTypes_array[$row_attentionTypes['id']]=$row_attentionTypes['type'];
} while ($row_attentionTypes = mysql_fetch_assoc($attentionTypes));

?>
<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.3//EN" "http://www.wapforum.org/DTD/wml13.dtd" >
<wml>
<card id="defaultId" title="<?php echo $row_NextEvent['description']; ?>" xml:lang="DefaultXML" newcontext="false">
<?php echo
 '<p>'.$norWeekDays[date ("w",strtotime ($row_NextEvent['dateStart']))].' '.date ("j/n-y H:i",strtotime ($row_NextEvent['dateStart']));
if ($row_NextEvent['cancelled']) {
	echo '<br/><em>Avlyst!</em>';
}
echo '<br/>'.$row_NextEvent['location'].'<br/>
Antall bekreftede: ';
echo $row_confirmed['confirmedCount'] ? $row_confirmed['confirmedCount'] : '0';
echo '</p>
<p>'.$row_NextEvent['notes'].'</p>
<p><strong>Oppsummering</strong><br/>';

do {
	echo $attTypes_array[$row_attentionsNext['type']].': '.$row_attentionsNext['countAttentions'].'<br>';
} while ($row_attentionsNext = mysql_fetch_assoc($attentionsNext));
echo '</p>';

echo '<p><strong>Alle svar</strong><br/>';

do {
	echo '<em>'.$row_PlayerInfo['firstName'].' '.$row_PlayerInfo['lastName'].'</em>: '.$row_PlayerInfo['type'].' tlf: '.$row_PlayerInfo['phone'].' '.$row_PlayerInfo['notes'].'<br>';
} while ($row_PlayerInfo = mysql_fetch_assoc($PlayerInfo));
echo '</p>';
?>
</card>
</wml><?php
mysql_free_result($PlayerInfo);

mysql_free_result($NextEvent);

mysql_free_result($attentionsNext);

mysql_free_result($attentionTypes);

mysql_free_result($confirmed);
?>