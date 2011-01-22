<?php
/**
 * PlayerSelector
 */
include_once("mCommonCode.inc");
include_once("playerSelector.inc");

class MainPlayerSelector_class extends MMainBase_class {
	public function MainPlayerSelector_class(){
		//Base constructor
		$this->MMainBase_class();
		if(!$this->HasRedirected()){
			$this->_createContent();
			$team=new Team_class();
			$this->SetTitle($team->getName("short")." - Innlogging");
		}
	}
	private function _createContent(){
		$form=new MPlayerSelector_class();
		$this->SetContent($form->GetContents());
	}
}

try{
$main=new MainPlayerSelector_class();
echo $main->GetContent();
}
catch (Exception $e){
	echo($e->getMessage());
	echo(print_r($e->getTrace()));
}
?>