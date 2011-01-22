<?php
//Base classes


class BaseClass {
	//Base of all classes
	var $criticalMessages;
	
	function AddCriticalMessage($messageString){
		if(! (isset($this->criticalMessages))){
			$this->criticalMessages=array();	
		}
		$this->criticalMessages[]=$messageString;
	}
	
	function GetCriticalMessages($divider=" "){
		if (isset($this->criticalMessages)){
			return implode($this->criticalMessages,$divider);
		}else{
			return false;	
		}
	}
		
	function CriticalMessagesJavascriptMessage(){
		$out="";
		$out.="<script language='JavaScript' type='text/JavaScript'>".chr(13);
		$out.="window.alert('".$this->GetCriticalMessages(chr(13))."');".chr(13);
		$out.="</script>".chr(13);
		return $out;
	}
	
	
	function GetUniqueId(){
		$name="id";
		for ($n=0;$n<10;$n++){
			$name .= chr(round(rand(97,122),0));
		}
		return $name;
	}
	function NorWeekday($dayInt){
		$days=array("s&oslash;ndag","mandag","tirsdag","onsdag","torsdag","fredag","l&oslash;rdag");
		return $days[$dayInt];	
	}
	function NorMonth($monthInt){
		$months=array("januar","februar","mars","april","mai","juni","juli",
						"august","september","oktober","november","desember");
		return $months[$monthInt];
	}
	function formatNorDate($dateString){
		$dateVal=strtotime($dateString);
		$out=$this->NorWeekday(date("w",$dateVal));
		$out.=" ".date("j",$dateVal).". ";
		$out.=$this->NorMonth(date("n",$dateVal));
		$out.=" ".date("Y",$dateVal);
		return $out;
	}
	function LocalNow(){
		//Possibly extend this function to be locale-aware
		//or daylightsavings-aware
		$offsetFromGMT=2;
		$dateString=strftime("%c",mktime(date("H")+$offsetFromGMT,date("i"),date("s"),date("m"),date("d"),date("Y")));
		return $dateString;
	}
}


/**
 * Base of classes writing HTML forms
 * Contains methods for creating form fields etc.
 *
 */
class HtmlElements extends BaseClass{
	var $contentString;
	var $messageToUser;
	
	function setContent ($content){
		$this->contentString=$content;
	}
	function WriteToPage(){
		echo($this->GetContents());
	}
	function GetContents(){
		return $this->contentString;
	}
	/**
	 * Use this with MakeFormEnd()
	 * $urlHere is used in the form action parameter
	 *
	 * @param string $formName
	 * @param string $urlHere
	 * @return string
	 */
	function MakeFormStart($formName,$urlHere){
		$formString="
  			<script language='JavaScript' type='text/JavaScript'>
			function submit$formName(){
			// iterate through arguments
			for (var i = 0; i < submit$formName.arguments.length; i+=2){
			eval('document.$formName.'+submit$formName.arguments[i]).value=submit$formName.arguments[i+1];
			}
			document.$formName.submit();
			}
			</script>
			<noscript>
			Denne siden vil ikke fungere om nettleseren ikke tillater Javascript.
			</noscript>
			<form name='$formName' method='post' action='$urlHere'>	
			";
		return $formString;
	}
	/**
	 * Renders a hidden field
	 *
	 * @param string $fieldName
	 * @param string $fieldValue
	 * @return string
	 */
	function MakeFormHiddenField($fieldName,$fieldValue){
		return "<input name='$fieldName' type='hidden' value='$fieldValue'>";
	}
	function MakeFormEnd(){
		return "</form>".chr(13);
	}
	function MakeMinimalFormComplete($playerId,$serviceId,$eventId,$messageId,$formName,$urlHere){
		$out="";
		$global= new ToolGlobals();
		$out.=$this->MakeFormStart($formName,$urlHere );
		$out.=$this->MakeFormHiddenField($global->GetFieldName('playerId'),$playerId );
		$out.=$this->MakeFormHiddenField($global->GetFieldName('serviceId'),$serviceId );
		$out.=$this->MakeFormHiddenField($global->GetFieldName('eventId'),$eventId );
		$out.=$this->MakeFormHiddenField($global->GetFieldName('messageId'),$messageId );
		$out.=$this->MakeFormEnd();
		return $out;
	}
	function FormatPlayerNumber($number){
		if(!(is_numeric(trim(str_replace(",",".",$number))))){
				$out="&infin;";	
			}else{
				$out="&#8470;&nbsp;$number";
			}
		return $out;
	}
}

?>