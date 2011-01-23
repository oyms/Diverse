#!/usr/bin/perl
if(!$ENV{'QUERY_STRING'}){$do="15"}
else{$do=$ENV{'QUERY_STRING'}}
print "Content-type: text/html\n\n<HTML><TITLE>View Log</TITLE><BODY><H2>LOGS</H2><TABLE border=1>";
open(LOG, "/var/log/httpd/error_log") || die("Could not open log");
@line = <LOG>;
close(LOG);
print $line[1];
$num = @line;
for($done=0;$done<$do;$done++){
$num--;
if($line[$num] =~ /^\[.*:..:.*\]/){
$line[$num] =~ tr/\[/ /;
($time, $typ, $cli, $info) = split (/\]/, $line[$num]);
}
else{$info=$line[$num]}
if($line[$num] =~ /\(2\)/){
$cli = "";
$info=$cli
}
if($tm ne $time){print "<TR><TD colspan=4><HR></TD></TR>"}
print "<TR><TD width=100>$time</TD><TD>$typ</TD><TD width=50>$cli</TD><TD>$info</TD></TR>";
$tm=$time;
}
print "</TABLE></BODY></HTML>";
exit;