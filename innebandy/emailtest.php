<?PHP
// Read POST request params into global vars 
$to      = "skaar@start.no";
$from    = "skaar@freeshell.org"; 
$subject = "testing"; 
$message = "
	<html>
	<head>
	/* Dette er en kommentar
	
		som går over flere linjer */
	</head>
	<body>
	Ingen særlig substans
	</body>
	</html>
";


$headers = "From: $from";


// Send the message 
$ok = mail($to, $subject, $message, $headers); 
if ($ok) { 
 echo "<p>Mail sent! Yay PHP!</p>"; 
} else { 
 echo "<p>Mail could not be sent. Sorry!</p>"; 
} 
?>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             