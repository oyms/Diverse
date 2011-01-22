<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_innebandybase = "ol.freeshell.org";
$database_innebandybase = "skaar";
$username_innebandybase = "skaar";
$password_innebandybase = "topscore";
$innebandybase = mysql_pconnect($hostname_innebandybase, $username_innebandybase, $password_innebandybase) or die(mysql_error());
@mysql_select_db("skaar",$innebandybase);
?>