<?php
include_once("mCommonCode.inc");
include_once("eventList.inc");

class Main_class extends MMainBase_class{
	public function Main_class(){
		$this->MMainBase_class();
		$team=new Team_class();
		$this->SetTitle($team->getName('short'." - Hendelser"));
	}
}

$main=new Main_class();
echo($main->GetContent());

?>