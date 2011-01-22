<?php

require_once('commonCode.inc');
$query = "
	UPDATE teams
	SET longName = 'Bærum kommune Fotball'
	WHERE id = 7
";

$queryObj = new DBConnector_class($query);

echo $query;

?>