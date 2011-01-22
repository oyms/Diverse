<?php require_once('commonCode.inc'); 

$team = new Team_class();
$headerFile = $team->get_headerFile();
$headerUrl = 'http://'.HOMEURL.$headerFile;

header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Expires: 0");

header("302 Moved Temporarily");
header("Location: ".$headerUrl);
exit;

?>

