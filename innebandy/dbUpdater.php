<?php

require_once('commonCode.inc');
$query = "
	UPDATE teams
	SET longName = 'B�rum kommune Fotball'
	WHERE id = 7
";

$queryObj = new DBConnector_class($query);

echo $query;

?>