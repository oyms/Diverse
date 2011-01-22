<?php
require_once("commonCode.inc");
require_once('playerInfoCode.inc');

$main=new CSVListMain_class();

header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
header ( "Cache-Control: no-cache, must-revalidate" );
header ( "Pragma: no-cache" );
header ( "Content-type: text/csv" );
header ( "Content-Disposition: attachment; filename=spillerliste_{$main->GetTeamName("short")}.csv" );
header ( "Content-Description: PHP Generated XLS Data" ); 
header ( "Content-Language: no" );


print $main->GetOutput();


//---------------------
//  Classes
//---------------------

/**
 * main logic for creating the csv list
 */
class CSVListMain_class{
	var $output;
	var $list;
	var $formData;
	function CSVListMain_class(){
		$player=new Player_class();
		$this->formData=new FormDataCSV_class();
		$this->list=new CSVListOfPlayers_class($this->formData);
		$this->output=$this->list->GetContent();
	}
	function GetOutput(){
		return $this->output;
	}
	function GetTeamName($version="long"){
		$team=new OtherTeam_class($this->formData->GetTeamId());
		return $team->getName($version);
	}
}

/**
 * Priovides read access to form data
 *
 */
class CSVFormData_class{
	var $player;
}
?>