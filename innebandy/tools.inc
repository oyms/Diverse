<?php
//Tool classes


class ToolHtml extends HtmlElements {
	
	function _GetClassString($class=false){
		if($class){
			$classString=" class='$class' ";
		}else{
			$classString="";
		}
		return $classString;
	}


	function Hyperlink($link,$text, $class=false){
		$classString=$this->_GetClassString($class);
		$out="<a href='$link'$classString>$text</a>";
		return $out;
	}
	function Form($name,$content,$post=true, $class=false){
		$pageData = new DataPage();
		$url=$pageData->GetUrl();
		if($post){
			$methodString="method='post'";
		}else{
			$methodString="method='get'";
		}
		$classString=$this->_GetClassString($class);
		$out="<form name='$name' $methodString $classString action='$url'>$content</form>";
		return $out;
	}
	function JavaScriptSubmitForm ($formName,$functionName){
		//Making a javascript function wich writes to fields
		//and submits form
		$out="<script language='JavaScript' type='text/JavaScript'>\n";
		$out.="function $functionName(){ \n";
		$out.="for (var i=0;i<$functionName.arguments.length;i+=2){\n";
		$out.="eval('document.$formName.'+$functionName.arguments[i]).value=";
		$out.="$functionName.arguments[i+1];\n";
		$out.="}\n";
		$out.="document.$formName.submit();\n";
		$out.="}\n";
		$out.="</script>";
		return $out;	
	}
	function JavaScriptSubmitFormLink($functionName,$parametersArray){
		//Making link to javascript function
		//Parameterarray contains fieldnames and values in pairs
		$out="href='JavaScript:$functionName(";
		foreach ($parametersArray as $field => $value) {
			$out.=" \"$field\" , \"$value\" ,";
		}
		//Remove last comma
		$out=rtrim($out,",");
		$out.=");'";
		
		return $out;	
	}
}
	
class ToolPageLogic extends BaseClass {
//All logic and assembly

	var $playerId;
	var $messageId;
	var $eventId;
	
	function ToolPageLogic(){
		$this->_RunLogic();
	}
	
	function _RunLogic(){
		$globals = new ToolGlobals();
		$pageInfo = new DataPage();

				
		$this->playerId=$pageInfo->GetPlayerId();
		
		//Update
		$this->_UpdateData($this->playerId);
		
		//Detect client/format
		//Html, Wap, SOAP, other
		$format = $this->_DetectClient();
		
		//Create page
		$page = new ToolPageAssembler($format);
		$teamHeader = new ElementTeamHeader($pageInfo->GetTeamId(),$pageInfo->GetPlayerId());
		$page->AddElement($teamHeader);
		//Security
		
		
		//Detect service
		
		
		//Insert elements
		
		
		//Output
		$page->WriteToPage();
		
		
	}
	function _UpdateData($playerId){
		//Updates db from GET/POST-fields
		$updateObj = new ToolUpdate($playerId);
		if ((!($this->playerId)) && 
			($updateObj->GetTypeOfData() == $updateObj->DataTypeValue('player')) && 
			($updateObj->GetTypeOfChange() == $updateObj->ChangeType('insert')))
		{
			$this->playerId=$updateObj->GetId();
		}	
	}
	function _DetectClient(){
	//Finds type of client
		$globals= new ToolGlobals();
		$WML = $globals->GetFormatValue('WML');
		$HTML = $globals->GetFormatValue('HTML');

	 	if(strpos(strtoupper($_SERVER["HTTP_ACCEPT"]),"VND.WAP.WML") > 0) {        // Check whether the browser/gateway says it accepts WML.
    		$type = $WML;
  		} else {
	    	$browser=substr(trim($_SERVER["HTTP_USER_AGENT"]),0,4);
	    	
	    if(   $browser=="Noki" ||			// Nokia phones and emulators
		      $browser=="Eric" ||			// Ericsson WAP phones and emulators
		      $browser=="WapI" ||			// Ericsson WapIDE 2.0
		      $browser=="MC21" ||			// Ericsson MC218
		      $browser=="AUR " ||			// Ericsson R320
		      $browser=="R380" ||			// Ericsson R380
		      $browser=="UP.B" ||			// UP.Browser
		      $browser=="WinW" ||			// WinWAP browser
		      $browser=="UPG1" ||			// UP.SDK 4.0
		      $browser=="upsi" ||			// another kind of UP.Browser ??
		      $browser=="QWAP" ||			// unknown QWAPPER browser
		      $browser=="Jigs" ||			// unknown JigSaw browser
		      $browser=="Java" ||			// unknown Java based browser
		      $browser=="Alca" ||			// unknown Alcatel-BE3 browser (UP based?)
		      $browser=="MITS" ||			// unknown Mitsubishi browser
		      $browser=="MOT-" ||			// unknown browser (UP based?)
		      $browser=="My S" ||           // unknown Ericsson devkit browser ?
		      $browser=="WAPJ" ||			// Virtual WAPJAG www.wapjag.de
		      $browser=="fetc" ||			// fetchpage.cgi Perl script from www.wapcab.de
		      $browser=="ALAV" ||			// yet another unknown UP based browser ?
			  $browser=="Wapa")             // another unknown browser (Web based "Wapalyzer"?)
		{
	        $type = $WML;
	    }else {
	      	$type = $HTML;
	    }
	  }		
		return $type;
	}
	
	/*
	//Early processing
	$globals = new ToolGlobals();
	$update = new ToolUpdate();
	$pageData = new DataPage();
	$pageInfo = new InfoPage();
	$webPage = new ToolPageAssembler(false);	
	$teamHeader = new ElementTeamHeader($pageData->GetTeamId(),$pageData->GetPlayerId());
	
	//To be changed
	$event=new InfoEvent(1,1);
	
	
	$webPage->AddElement($teamHeader);
	//Security
	if (! $player->identOk) {
	    //Select player-id
	    $identChooser= new ElementChooseIdentity($pageInfo);
		$webPage->AddElement($identChooser);
	}else{
		//Proceed with services
		$webPage->AddElement($topMenu);
	
		switch ($pageInfo->service){
			case $globals->GetServiceValue('frontPage'):
				$webPage->AddElement(new ElementFrontPage($pageInfo,$player));
				break;
			case $globals->GetServiceValue('chooseIdent'):
				$webPage->AddElement($identChooser);
				break;
		}
				

	}


	
	//Drawing page
	$webPage->WriteToPage(); */
}



class ToolPageAssembler extends HtmlElements {
	//assembles webPage
	var $elements;
	var $format;
	function ToolPageAssembler($format){
		$this->format=$format;
		$this->contentString=$this->_AssemblePage($this->format);
	}
	function AddElement($element){
		$this->elements[]=$element;
		$this->contentString=$this->_AssemblePage($this->format);
	}
	function _AssembleMessageToUser($elements){
		$out="";
		foreach ($elements as $element){
			$out.=$element->messageToUser;
			if ($element->messageToUser){
				$out.=" ";
			}	
		}
		return $out;
	}
	function _AssemblePage($format){
		$pageInfo=new DataPage();
		$playerInfo=new DataPlayer();
		$playerInfo->MakeSelectQuery($pageInfo->GetPlayerId());
		$globals=new ToolGlobals();
		$this->messageToUser=$this->_AssembleMessageToUser($this->elements);
		
		switch ($format){
			case $globals->GetFormatValue('HTML'):
				$out=$this->_AssembleHtmlPage($pageInfo,$playerInfo);
				break;	
			case $globals->GetFormatValue('WML'):
				$out=$this->_AssembleWmlPage($pageInfo,$playerInfo);
				break;
			case $globals->GetFormatValue('SOAP'):
				$out="";
				break;
		}
		return $out;

	}
	function _AssembleHtmlPage($pageInfo,$playerInfo){
		//Headertags
		$out=$this->_MakeHtmlHead($pageInfo,$playerInfo);
		//Insert elements here
		$out.= $this->_MakeHtmlBody($this->elements);
		//Footer tags
		$out.=$this->_MakeHtmlFoot();	
		
		return $out;
	}
	function _AssembleWmlPage($pageInfo,$playerInfo){
		return null;	
	}
	function _MakeHtmlHead($pageInfo,$playerInfo){
		$statusBarFunctionString="<script language='JavaScript' type='text/JavaScript'>window.status='".$this->messageToUser."';</script>";
		$out="<HTML><HEAD><TITLE>";
		$out.=$pageInfo->GetTeamId()." ";
		if($playerInfo->identOk){
			$out.="(".$playerInfo->GetFullName().") ";	
		}
		$out.=$pageInfo->GetSvcLongName(false)." ";
		$out.="</TITLE>";
		$out.="
		<link rel='shortcut icon' href='/innebandy/innebandy.ico' /> 
		<link rel='author'  href='http://skaar.freeshell.org/' />
		<link rel='home'  href='http://skaar.freeshell.org/innebandy/' />
		<link rel='copyright'  href='javascript:alert(\"Copyright 2002-2003, Skaar.\")' />
		<meta http-equiv='author' content='Øyvind Skaar' />
		";
		$out.=$statusBarFunctionString;
		$out.="</HEAD><BODY leftmargin='0' topmargin='0' marginwidth='0' marginheight='0'>";
		
		return $out;
	}
	function _MakeHtmlFoot(){
		$out="<hr><ADDRESS>&copy; 2003 &Oslashyvind Skaar</ADDRESS>";
		$out.="</BODY></HTML>";
		return $out;
	}
	function _MakeHtmlBody($elementArray){
		$out="";
		foreach ($elementArray as $element){
			$out.=$element->contentString;
		}	
		return $out;
	}
}


	
	


class ToolUpdate extends DataHolder {
	//Controls if changeData is sent vi Post or Get
	var $dataTypes;
	var $changeTypes;
	var $newId;
	
	var $fieldNameDataType;
	var $fieldNameChangeType;
	

	function ToolUpdate($playerId=false){
		$this->SetConstants();
		$this->fields=array(
			'typeOfData'=>array($this->FIELDNAME => $this->fieldNameDataType ,$this->DATATYPE => $this->DATAINT),
			'typeOfChange'=>array($this->FIELDNAME => $this->fieldNameChangeType ,$this->DATATYPE => $this->DATAINT)
		);
		$this->dataTypes=array('player'=>1,'event'=>2,'message'=>3,'answer'=>4,'goals'=>5,'seasons'=>6, 'email'=>7);
		$this->changeTypes=array('noChange'=>0,'update'=>1,'insert'=>2,'delete'=>3);
		$this->_RetrieveGetAndPost();
		$this->_UnTaintData();
		
		if(	($this->fields['typeOfData'][$this->SAFEVALUE]) && 
			($this->fields['typeOfChange'][$this->SAFEVALUE]) ) {
			switch ($this->fields['typeOfData'][$this->SAFEVALUE]){
				case $this->dataTypes['player']:
					
					break;
				case $this->dataTypes['event']:
					$data = new DataEvent($playerId);
					break;
				case $this->dataTypes['message']:
					
					break;
				case $this->dataTypes['goals']:
					
					break;
				case $this->dataTypes['seasons']:
					
					break;
				case $this->dataTypes['answer']:
					
					break;
				case $this->dataTypes['email']:
					
					break;
			}
			switch ($this->fields['typeOfChange'][$this->SAFEVALUE]){
				case $this->changeTypes['update']:
					$data->MakeUpdateQuery($playerId);
					$this->newId=$data->GetSafeData('id');
					break;
				case $this->changeTypes['insert']:
					$this->newId = $data->MakeInsertQuery($playerId);
					break;
				case $this->changeTypes['delete']:
					$data->MakeDeleteQuery($playerId);
					$this->newId=null;
					break;
			}
		}
	}	
	function SetConstants(){
		$this->fieldNameDataType='dataType';
		$this->fieldNameChangeType='changeType';
	}
	
	function GetId(){
		return $this->newId;	
	}
	function GetTypeOfData(){
		return 	$this->fields['typeOfData'][$this->SAFEVALUE];
	}
	function GetTypeOfChange(){
		return 	$this->fields['typeOfChange'][$this->SAFEVALUE];
	}
	function DataTypeValue($type){
		return $this->dataTypes[$type];	
	}
	function ChangeType($type){
		return $this->changeTypes[$type];
	}
}

class ToolGlobals {
	var $fieldNames;
	var $svc;
	var $timeToCookieExpire;
	var $formats;
	function Globals(){
		$this->fieldNames=array(
			"playerId"=>"Player",
			"eventId" => "Event",
			"serviceId"=>"Service",
			"messageId"=>"Message",
			"answerId"=>"Answer"
		);
		$this->timeToCookieExpire=(60*60*24*30);
		$this->svc=array(
			"FrontPage"=>1,
			"EventDetails"=>2,
			"ChooseIdent"=>3,
			"Help"=>4,
			"Messages"=>5,
			"MessageCompose"=>6,
			"PlayerEdit"=>7,
			"EventEdit"=>8,
			"GoalsEdit"=>9,
			"PlayerList"=>10
		);
		$this->formats=array(
			"HTML"=> 1,
			"WML" => 2,
			"SOAP"=> 3
		);
	}
	function GetFieldName($field){
		return $this->fieldNames[$field];	
	}	
	function GetTimeToCookieExpire(){
		return $this->timeToCookieExpire;
	}
	function GetServiceValue($service){
		return $this->svc[$service];	
	}
	function GetFormatValue($format){
		return $this->formats[$format];
	}
}


class ToolEmail extends HtmlElements  {
	var $headers;
	var $body;
	var $addressees;
	var $subject;
	var $from;
	function ToolEmail($pageInfo,$playerInfo){
		$this->from=$pageInfo->teamName;
		if(isset($playerInfo)){
			$this->from.="(".$playerInfo->playerFullName.")";
			$this->from.="<".$playerInfo->playerEmail.">";
		}else{
			$this->from.="<ikke@svar>";
		}
	}
	function AddHeader($headerName,$value){
		$this->headers[]="$headerName: $value";
	}
	function SetBody($bodyString){
		$this->body=$bodyString;
	}
	function SetSubject($subjectString){
		$this->subject=$subjectString;
	}
	function AddToAddressee($name,$address="ikke@svar.no"){
		$this->addressees[]="$name <$address>";
	}
	function SetAllFields($to,$subject,$body,$headers){
		$this->addressees=$to;
		$this->subject=$subject;
		$this->body=$body;
		$this->headers=$headers;
	}
	function SendMail(){
		$headers=implode("\n",$this->headers);
		$addressees=implode(",",$this->addressees);
		return mail($addressees,$this->subject,$this->body,$headers);
	}
}

class ToolQueryFromDB  extends BaseClass{
	var $queryString;
	var $queryType;
	var $recordset;
	var $allRows;
	var $numberOfRows;
	var $nextRowIndex;
	var $lastIndex;
	
	//constructor
	function ToolQueryFromDB($stringToBeQueried, $queryType=1){
		$this->queryType=$queryType;
		$this->queryString=$stringToBeQueried;
		switch($this->queryType){
			//This switch is possibly superficial
			case 1:
				//Selectquery
				$this->MakeQuery($stringToBeQueried);
				break;
			case 2:
				//AlterQuery
				$this->MakeQuery($stringToBeQueried);
				break;
			case 3:
				//InsertQuery
				$this->MakeQuery($stringToBeQueried);
				break;
			case 4:
				//Delete query
				$this->MakeQuery($stringToBeQueried);
				break;
		}
	}
	function MakeQuery($qs){
		//Getting data from DB
		global $database_innebandybase,$innebandybase;
		
		$this->queryString=$qs;
		mysql_select_db($database_innebandybase, $innebandybase);
		$this->recordset=mysql_query($this->queryString , $innebandybase) or die(mysql_error());
		$this->numberOfRows=mysql_num_rows($this->recordset);
		$this->_PopulateRowArray();
		$this->nextRowIndex=0;
		
		$this->lastIndex= mysql_insert_id($innebandybase);
	}
	function _PopulateRowArray(){
		//Get all rows and putting them in an indexed array
		for ($rowIndex=0;$rowIndex<$this->numberOfRows;$rowIndex++){
			$this->allRows[$rowIndex]=mysql_fetch_assoc($this->recordset);
		}	
	}
	function GetNextRow(){
		if ($this->numberOfRows) {
	    	if ($this->nextRowIndex < $this->numberOfRows) {
	    	    $result=$this->allRows[$this->nextRowIndex];
	    	}else{
				$result=$this->allRows[0];
			}
			$this->nextRowIndex=($this->nextRowIndex++)%($this->numberOfRows);
		}else{
			$result=false;
		}
		return $result;
	}
	
}



?>