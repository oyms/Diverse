<?php
require_once("commonCode.inc");
require_once("calendarCode.inc");

header("Content-Type: text/calendar; charset=utf-8");
header("Content-Language: no");
//header("Cache-Control: cache-request-directive=max-age=9000");
header("Content-Disposition: inline; filename=hvemkommer.ics");
//header("Last-Modified:");

$player=new Player_class();
$team=new Team_class();
$sourceName=$player->id>0?$player->getName():$team->getName();
$calendarName="Hvem kommer?¿ (".$sourceName.")";

$evFac=new EventFactory();
$events=$evFac->GetEvents();

$tz=new CalendarTimeZone();
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID: <?php echo(utf8_encode($calendarName)); ?> 
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:<?php echo(utf8_encode($calendarName)); ?> 
X-WR-TIMEZONE:<?php echo($tz->Name()); ?> 
X-WR-CALDESC:Hendelsesliste for <?php echo(utf8_encode($sourceName)); ?>. 
<?php echo($tz->Definition());?>
<?php
foreach($events as $ev){
	echo($ev->RenderAsVEvent());
}
?>
END:VCALENDAR