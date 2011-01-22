<?php
class DateControl_class {
	var $noDateIndicatorString, $noDateLabel, $dateLabel, $roundOffValue;
	var $name, $currentValue, $timeControl, $earliestDate, $latestDate;
	var $dependents, $gmt;
	var $optional;
	function DateControl_class() {
		$this->noDateIndicatorString = "Ingen dato er satt";
		$this->noDateLabel = "ingen dato";
		$this->dateLabel = "dato";
		$this->roundOffValue = 5;
		$this->dependents = array();
		$this->name = "DateControlObject".floor(rand()*1000);
		$this->currentValue = $this->earliestDate = $this->latestDate = time();
		$this->optional = false;
		$this->timeControl = false;
		$this->gmt = false;
		
	}
	/**
	* @return timstamp
	* @param timestamp $timestamp
	* @desc Converts a timestamp from unix to javascript (multiplies by 1000)
	*/
	function ConvertTimestampUnixToJavascript($timestamp){
		return $timestamp * 1000;	
	}
	/**
	* @return timestamp
	* @param timestamp $timestamp
	* @desc Converts a timestamp from javascript to unix (divides by 1000)
	*/
	function ConvertTimestampJavascriptToUnix($timestamp){
		return $timestamp / 1000;	
	}
	/**
	* @return void
	* @param boolean $value
	* @desc Sets datevalue to be GMT (instead of local time)
	*/
	function SetGMT($value=false){
		$this->gmt = $value;	
	}
	/**
	* @return string
	* @desc Returns string used when no date is chosen
	*/
	function GetNoDateIndicatorString(){
		return $this->noDateIndicatorString;	
	}
	/**
	* @return void
	* @param unknown $newString
	* @desc Set the label of the control, providing it is optional.
	*/
	function SetNoDateLabel($newString="ingen dato"){
		$this->noDateLabel= $newString;	
	}
	/**
	* @return void
	* @param string $newString
	* @desc Set label to be displayed next to the date controls, providing it is optional.
	*/
	function SetDateLabel($newString="dato"){
		$this->dateLabel = $newString;	
	}
	/**
	* @return void
	* @param string $newString
	* @desc Set name of date control. The name of the field with the date value
	*/
	function SetName($newString){
		$this->name = $newString;
	}
	/**
	* @return string
	* @desc The name of the control and date-field
	*/
	function GetName(){
		return $this->name;	
	}
	/**
	* @return void
	* @param int $newValue
	* @desc Set the round off value (the resolution of the minutes control)
	*/
	function SetRoundOffValue($newValue=5){
		$this->roundOffValue = $newValue;	
	}
	/**
	* @return void
	* @param timestamp $timeStampValue
	* @desc Set date to be displayed in control
	*/
	function SetCurrentValue($timeStampValue){
		$this->currentValue = $timeStampValue;
	}
	/**
	* @return void
	* @param timestamp $timeStampValue
	* @desc Set lower bound of date control
	*/
	function SetEarliestDate($timeStampValue){
		$this->earliestDate = $timeStampValue;
	}
	/**
	* @return void
	* @param timestamp $timeStampValue
	* @desc Set upper bound of date control
	*/
	function SetLatestDate($timeStampValue){
		$this->latestDate = $timeStampValue;
	}
	/**
	* @return void
	* @param int $newValue
	* @desc Set whether the date control is optional. 0: not optional, 1: optional set to no date, 2: optional set to date
	*/
	function SetOptional($newValue=false){
		$this->optional = $newValue;	
	}
	/**
	* @return void
	* @param boolean $newValue
	* @desc Whether the control will set time or date only.
	*/
	function SetTimeControl($newValue=false){
		$this->timeControl = $newValue;	
	}
	/**
	* @return void
	* @param string $dependentControl
	* @param boolean $mainDateConnection
	* @param timestamp $minControl
	* @param timestamp $maxControl
	* @desc Adds other control to be dependent of this
	*/
	function SetDependency($dependentControl,$mainDateConnection=false,$minControl="no",$maxControl="no"){
		$this->dependents[$dependentControl]['name'] = $dependentControl;
		$this->dependents[$dependentControl]['mainConnection']=$mainDateConnection;
		$this->dependents[$dependentControl]['minControl'] = $minControl;
		$this->dependents[$dependentControl]['maxControl'] = $maxControl;
	}
	/**
	* @return html
	* @desc Returns code for formcontrols without the form tag.
	*/
	function RenderControl(){
		$out = $this->_RenderCode();
		return $out;
	}
	function Control($name,$currentValue=false,$timeControl=false,$earliestDate=false,$latestDate=false,$optional=0,$controlAfter=""){
		$this->name = $name;
		$this->timeControl = $timeControl;
		$this->earliestDate = $earliestDate;
		$this->latestDate = $latestDate;
		$this->optional = $optional;
		$out = $this->_RenderCode(
			$this->name,
			$this->ConvertTimestampUnixToJavascript($this->currentValue),
			$this->timeControl,
			$this->ConvertTimestampUnixToJavascript($this->earliestDate),
			$this->ConvertTimestampUnixToJavascript($this->latestDate),
			$this->optional,
			$controlAfter,
			$this->roundOffValue);
		return $out;
	}
	/**
	* @return javascript
	* @desc Renders code to control other controls
	*/
	function _DependentCode(){
		$nameOfField = $this->name;		
			//Open function
			$script .= "
			
			
			function {$nameOfField}_UpdateOtherControl(){
				var newCurrent = {$nameOfField}_GetHiddenField();
				var timeDifference = newCurrent.valueOf()-{$nameOfField}_previousDate.valueOf();

			";
			foreach ($this->dependents as $dependent){
				$controlAfter = $dependent['name'];
				$script .= "
					if(typeof({$controlAfter}_RenderControls) != 'undefined'){
				";
				if (! ($dependent['minControl'] === "no")){
					$offset = $this->ConvertTimestampUnixToJavascript($dependent['minControl']);
					$script .= "
						{$controlAfter}_earliestDate = {$nameOfField}_DateObject(newCurrent.valueOf() + {$offset});
						{$controlAfter}_currentDate = {$nameOfField}_DateObject(Math.max({$controlAfter}_currentDate.valueOf(),{$controlAfter}_earliestDate.valueOf()));
					";
				}
				if (! ($dependent['maxControl'] === "no")){
					$offset = $this->ConvertTimestampUnixToJavascript($dependent['maxControl']);
					$script .= "
						{$controlAfter}_latestDate = {$nameOfField}_DateObject(newCurrent.valueOf() + {$offset});
						{$controlAfter}_currentDate = {$nameOfField}_DateObject(Math.min({$controlAfter}_currentDate.valueOf(),{$controlAfter}_latestDate.valueOf()));
					";
				}
				if ($dependent['mainConnection']){
					$script .= "
						{$controlAfter}_currentDate = {$nameOfField}_DateObject(Math.min({$nameOfField}_DateObject({$controlAfter}_GetHiddenField()).valueOf()+timeDifference,{$controlAfter}_latestDate.valueOf()));
					";
				}
				
					//End of if
				$script .= "
					{$controlAfter}_RenderControls(true);
					}
				";

			}

			//Close function
			$script .= "
				{$nameOfField}_previousDate = {$nameOfField}_GetHiddenField();
			}
			";	
			
			
			
			return $script;	
	}
	/**
	* @return javascript
	* @desc Functions to set and get dates in hidden date field
	*/
	function _DateFieldControllers(){
		$nameOfField = $this->name;
		$gmt = $this->gmt;
		$script = "
			function {$nameOfField}_GetHiddenField(){
				var field = {$nameOfField}_GetField('date');
				";
		if ($gmt) {
			$script .= "out = {$nameOfField}_DateObject(field.value*1000);";
		}else{
			$script .= "out = {$nameOfField}_DateObjectFromLocalTime(field.value*1000);";
			}
		$script .= "
				return out;
			}
			function {$nameOfField}_SetHiddenField(dateValue){
				var field = {$nameOfField}_GetField('date');
				";
		if ($gmt) {
			$script .= "field.value = (dateValue.valueOf())/1000;";
		}else{
			$script .= "field.value = {$nameOfField}_LocalTimeStampFromDateObject(dateValue)/1000;";
			}
		$script .= "	
			}	
		";
		return $script;
	}
	
	/**
	* @return html
	* @desc Draws date control
	*/
	function _RenderCode(){
		//Form controls without form tag
		$nameOfField = $this->name;
		$currentValue = $this->ConvertTimestampUnixToJavascript($this->currentValue);
		$timeControl = $this->timeControl;
		$earliestDate = $this->ConvertTimestampUnixToJavascript($this->earliestDate);
		$latestDate = $this->ConvertTimestampUnixToJavascript($this->latestDate);
		$optional = $this->optional;
		$roundOffMinutes = $this->roundOffValue;
		
		$dependentsExists = (count($this->dependents) > 0);
		
		$displayTimeString = $timeControl ? "true" : "false";
		$controls = "";
		if ($optional > 0){
			if ($optional == 1){
				$checked1 = " checked";
				$checked2 = "";	
			}else{
				$checked1 = "";
				$checked2 = "checked";
			}
			$controls .= "
			<span id='{$nameOfField}_chooserNoDate'>
				<input name='{$nameOfField}_dateSwitch' id='{$nameOfField}_dateSwitchOff' type='radio' value='false' $checked1 onChange='{$nameOfField}_chooserControlChange();' onBlur='{$nameOfField}_chooserControlChange();'>
				<span id='{$nameOfField}_chooserNoDate_labelOff' onClick='document.getElementById(\"{$nameOfField}_dateSwitchOff\").checked = true;{$nameOfField}_chooserControlChange();'>
				{$this->noDateLabel}
				</span>
			<br>
			<input name='{$nameOfField}_dateSwitch' id='{$nameOfField}_dateSwitchOn' type='radio' value='true' $checked2 onChange='{$nameOfField}_chooserControlChange();' onBlur='{$nameOfField}_chooserControlChange();'>
				<span id='{$nameOfField}_chooserNoDate_labelOn' onClick='document.getElementById(\"{$nameOfField}_dateSwitchOn\").checked = true;{$nameOfField}_chooserControlChange();'>
				{$this->dateLabel}
				</span>
			<br>
			</span>
			";	
		}
		$controls .= "
		  <select name='{$nameOfField}_dag' id='{$nameOfField}_dag' onChange='{$nameOfField}_dayControlChange();'>
		  </select>
		  <select name='{$nameOfField}_mnd' id='{$nameOfField}_mnd' onChange='{$nameOfField}_monthControlChange();'>
		  </select>
		  <select name='{$nameOfField}_aar' id='{$nameOfField}_aar' onChange='{$nameOfField}_yearControlChange();'>
		  </select>
		  <select name='{$nameOfField}_timer' id='{$nameOfField}_timer' onChange='{$nameOfField}_hourControlChange();'>
		    </select>&nbsp;
		  <select name='{$nameOfField}_minutter' id='{$nameOfField}_minutter' onChange='{$nameOfField}_minuteControlChange();'>
		  </select>
		  <input name='{$nameOfField}' type='hidden' id='{$nameOfField}'>
		  <span id='{$nameOfField}_alternateField'></span>
		";
		$convertionString = $this->gmt ? "_DateObject" : "_DateObjectFromLocalTime";
		$script = "
			var {$nameOfField}_earliestDate = {$nameOfField}{$convertionString}($earliestDate);
			var {$nameOfField}_currentDate = {$nameOfField}{$convertionString}($currentValue);
			var {$nameOfField}_previousDate = {$nameOfField}{$convertionString}($currentValue);
			var {$nameOfField}_latestDate = {$nameOfField}{$convertionString}($latestDate);
			var {$nameOfField}_displayTime = $displayTimeString;
			var {$nameOfField}_roundOffEveryMinute = $roundOffMinutes;
			var {$nameOfField}_noDateValue = '$this->noDateIndicatorString';
			
			{$nameOfField}_RenderControls(true);
			
			
			function {$nameOfField}_RenderControls(useCurrentDate){
				var dateScope = {$nameOfField}_latestDate.valueOf() - {$nameOfField}_earliestDate.valueOf();
				var minutes = {$nameOfField}_GetField('minutes');
				var hours = {$nameOfField}_GetField('hours');
				var days = {$nameOfField}_GetField('days');
				var months = {$nameOfField}_GetField('months');
				var years = {$nameOfField}_GetField('years');
				var alternate = {$nameOfField}_GetField('alternate');
				
		
				if(useCurrentDate){
				";
		if($optional){
			$script .= "
					{$nameOfField}_chooserControlChange();
			";	
		}
		
		$script .= "
				
					{$nameOfField}_SetYearControl({$nameOfField}_earliestDate,{$nameOfField}_latestDate,{$nameOfField}_currentDate);
					{$nameOfField}_SetMonthControl({$nameOfField}_currentDate,{$nameOfField}_currentDate,false,{$nameOfField}_currentDate);
					{$nameOfField}_SetDayControl({$nameOfField}_currentDate,{$nameOfField}_currentDate,false,{$nameOfField}_currentDate);
					{$nameOfField}_SetHourControl({$nameOfField}_currentDate,{$nameOfField}_currentDate,false,{$nameOfField}_currentDate);
					{$nameOfField}_SetMinutesControl({$nameOfField}_currentDate,{$nameOfField}_currentDate,false,{$nameOfField}_currentDate,{$nameOfField}_roundOffEveryMinute);
				}
				if (dateScope > (1000*60*60*24*365*2.5)){
					//Show Years
					{$nameOfField}_SetVisibility(years, true);
					{$nameOfField}_SetVisibility(months, true);
					{$nameOfField}_SetVisibility(days, true);
					if ({$nameOfField}_displayTime){
						{$nameOfField}_SetVisibility(minutes, true);
						{$nameOfField}_SetVisibility(hours, true);
					}else{
						{$nameOfField}_SetVisibility(minutes, false);
						{$nameOfField}_SetVisibility(hours, false);
					}
					{$nameOfField}_SetVisibility(alternate, false);
					{$nameOfField}_yearControlChange();
				}else if (dateScope > (1000*60*60*24*80)){
					//Show Months
					{$nameOfField}_SetMonthControl({$nameOfField}_earliestDate,{$nameOfField}_latestDate,true,{$nameOfField}_currentDate);
					{$nameOfField}_SetVisibility(years, false);
					{$nameOfField}_SetVisibility(months, true);
					{$nameOfField}_SetVisibility(days, true);
					if ({$nameOfField}_displayTime){
						{$nameOfField}_SetVisibility(minutes, true);
						{$nameOfField}_SetVisibility(hours, true);
					}else{
						{$nameOfField}_SetVisibility(minutes, false);
						{$nameOfField}_SetVisibility(hours, false);
					}
					{$nameOfField}_SetVisibility(alternate, false);
					{$nameOfField}_monthControlChange();
				}else if (dateScope > (1000*60*60*24*2)){
					//Show Days
					{$nameOfField}_SetDayControl({$nameOfField}_earliestDate,{$nameOfField}_latestDate,true,{$nameOfField}_currentDate);
					{$nameOfField}_SetVisibility(years, false);
					{$nameOfField}_SetVisibility(months, false);
					{$nameOfField}_SetVisibility(days, true);
					if ({$nameOfField}_displayTime){
						{$nameOfField}_SetVisibility(minutes, true);
						{$nameOfField}_SetVisibility(hours, true);
					}else{
						{$nameOfField}_SetVisibility(minutes, false);
						{$nameOfField}_SetVisibility(hours, false);
					}
					{$nameOfField}_SetVisibility(alternate, false);
					{$nameOfField}_dayControlChange();
				}else if (dateScope > (1000*60*60*18)){
					//Show Hours
					{$nameOfField}_SetHourControl({$nameOfField}_earliestDate,{$nameOfField}_latestDate,true,{$nameOfField}_currentDate);
					{$nameOfField}_SetVisibility(years, false);
					{$nameOfField}_SetVisibility(months, false);
					{$nameOfField}_SetVisibility(days, false);
					if ({$nameOfField}_displayTime){
						{$nameOfField}_SetVisibility(minutes, true);
						{$nameOfField}_SetVisibility(hours, true);
						{$nameOfField}_SetVisibility(alternate, false);
					}else{
						{$nameOfField}_SetVisibility(minutes, false);
						{$nameOfField}_SetVisibility(hours, false);
						{$nameOfField}_SetVisibility(alternate, true);
						alternate.innerText = 'samme dag';
					}
					{$nameOfField}_hourControlChange();
				}else if (dateScope > (1000*60*60*4)){
					//Show Minutes
					{$nameOfField}_SetMinutesControl({$nameOfField}_earliestDate,{$nameOfField}_latestDate,true,{$nameOfField}_currentDate,{$nameOfField}_roundOffEveryMinute);
					{$nameOfField}_SetVisibility(years, false);
					{$nameOfField}_SetVisibility(months, false);
					{$nameOfField}_SetVisibility(days, false);
					if ({$nameOfField}_displayTime){
						{$nameOfField}_SetVisibility(minutes, true);
						{$nameOfField}_SetVisibility(hours, false);
						{$nameOfField}_SetVisibility(alternate, false);
					}else{
						{$nameOfField}_SetVisibility(minutes, false);
						{$nameOfField}_SetVisibility(hours, false);
						{$nameOfField}_SetVisibility(alternate, true);
						alternate.innerText = {$nameOfField}_currentDate.getHours()+':'+{$nameOfField}_currentDate.getMinutes();
					}
				}
			}";
		if($optional){
			$script .= "
		function {$nameOfField}_chooserControlChange(){
				var switchField = {$nameOfField}_GetField('switch');
				var minutes = {$nameOfField}_GetField('minutes');
				var hours = {$nameOfField}_GetField('hours');
				var days = {$nameOfField}_GetField('days');
				var months = {$nameOfField}_GetField('months');
				var years = {$nameOfField}_GetField('years');
				var dateField = {$nameOfField}_GetField('date');
				if (switchField.checked){
					minutes.disabled=false;
					hours.disabled=false;
					days.disabled=false;
					months.disabled=false;
					years.disabled=false;
					dateField.value = minutes.value;	
				}else{
					minutes.disabled=true;
					hours.disabled=true;
					days.disabled=true;
					months.disabled=true;
					years.disabled=true;
					dateField.value = {$nameOfField}_noDateValue;	
				}
			}
			";	
		}
		
		$script .= "
			function {$nameOfField}_yearControlChange(){
				var selectedYear = {$nameOfField}_GetField('years').value;
				var selectedMonthDate = {$nameOfField}_DateObject({$nameOfField}_GetField('months').value);
				var selectedMonth = selectedMonthDate.getMonth();
				var newMonthSelection = selectedMonthDate.setYear(selectedYear);
				if (newMonthSelection.valueOf() > {$nameOfField}_latestDate.valueOf()){
					newMonthSelection = {$nameOfField}_DateObject({$nameOfField}_latestDate.valueOf());
				} else if (newMonthSelection.valueOf() < {$nameOfField}_earliestDate.valueOf()){
					newMonthSelection = {$nameOfField}_DateObject({$nameOfField}_earliestDate.valueOf());
				}
				var firstMonthValue = Math.max({$nameOfField}_earliestDate.valueOf(),{$nameOfField}_DateObject(newMonthSelection).setMonth(0));
				var lastMonthValue = Math.min({$nameOfField}_latestDate.valueOf(),{$nameOfField}_RoundOffDate(newMonthSelection,'years').setMonth(11));
				{$nameOfField}_SetMonthControl({$nameOfField}_DateObject(firstMonthValue),{$nameOfField}_DateObject(lastMonthValue),false,newMonthSelection);
				{$nameOfField}_monthControlChange(false);
			}
			function {$nameOfField}_monthControlChange(){
				var selectedMonth = {$nameOfField}_DateObject({$nameOfField}_GetField('months').value);
				var selectedDateNumber = {$nameOfField}_DateObject({$nameOfField}_GetField('days').value).getDate();
				var highestPossibleDate = {$nameOfField}_HighestDateOfMonth(selectedMonth);
				var firstDateValue = Math.max({$nameOfField}_earliestDate.valueOf(),{$nameOfField}_RoundOffDate(selectedMonth,'months'));
				var lastDateValue = Math.min({$nameOfField}_latestDate.valueOf(),selectedMonth.setDate(highestPossibleDate).valueOf());
				var newSelectedDateNumber = Math.max(Math.min(selectedDateNumber,highestPossibleDate,{$nameOfField}_DateObject(lastDateValue).getDate()),{$nameOfField}_DateObject(firstDateValue).getDate());
				{$nameOfField}_SetDayControl({$nameOfField}_DateObject(firstDateValue),{$nameOfField}_DateObject(lastDateValue),false,selectedMonth.setDate(newSelectedDateNumber));
				{$nameOfField}_dayControlChange();
			}
			function {$nameOfField}_dayControlChange(){
				var selectedDate = {$nameOfField}_RoundOffDate({$nameOfField}_DateObject({$nameOfField}_GetField('days').value),'days');
				var selectedMonth = {$nameOfField}_DateObject({$nameOfField}_GetField('months').value);
				if(selectedDate.getMonth() != selectedMonth.getMonth()){
					var monthField = {$nameOfField}_GetField('months');
					monthField.length = 1;
					monthField.item(0).value= selectedDate.valueOf();
					var selectedHour = {$nameOfField}_AlignDates({$nameOfField}_DateObject({$nameOfField}_GetField('hours').value),selectedDate,'days');
					var newSelectedHour = {$nameOfField}_DateObject(Math.min(Math.max(selectedHour.valueOf(),{$nameOfField}_earliestDate.valueOf()),{$nameOfField}_latestDate.valueOf()));
					var earliestTime = {$nameOfField}_DateObject(Math.max(selectedDate.valueOf(),{$nameOfField}_earliestDate.valueOf()));
					var latestTime =  {$nameOfField}_DateObject(Math.min(selectedDate.setHours(23).valueOf(),{$nameOfField}_latestDate.valueOf()));
					{$nameOfField}_SetHourControl(earliestTime,latestTime,false,newSelectedHour);
					{$nameOfField}_hourControlChange()
					{$nameOfField}_RenderControls();
				}else{
					var selectedHour = {$nameOfField}_AlignDates({$nameOfField}_DateObject({$nameOfField}_GetField('hours').value),selectedDate,'days');
					var newSelectedHour = {$nameOfField}_DateObject(Math.min(Math.max(selectedHour.valueOf(),{$nameOfField}_earliestDate.valueOf()),{$nameOfField}_latestDate.valueOf()));
					var earliestTime = {$nameOfField}_DateObject(Math.max(selectedDate.valueOf(),{$nameOfField}_earliestDate.valueOf()));
					var latestTime =  {$nameOfField}_DateObject(Math.min(selectedDate.setHours(23).valueOf(),{$nameOfField}_latestDate.valueOf()));
					{$nameOfField}_SetHourControl(earliestTime,latestTime,false,newSelectedHour);
					{$nameOfField}_hourControlChange()
				}
			}
			function {$nameOfField}_hourControlChange(){
				var selectedHour = {$nameOfField}_RoundOffDate({$nameOfField}_DateObject({$nameOfField}_GetField('hours').value),'hours');
				var selectedMinute = {$nameOfField}_AlignDates({$nameOfField}_DateObject({$nameOfField}_GetValueOfField('minutes')),selectedHour,'hours');
				var newSelectedMinutes = {$nameOfField}_DateObject(Math.min(Math.max(selectedMinute.valueOf(),{$nameOfField}_earliestDate.valueOf()),{$nameOfField}_latestDate.valueOf()));
				var earliestTime = {$nameOfField}_DateObject(Math.max(selectedHour.valueOf(),{$nameOfField}_earliestDate.valueOf()));
				var latestTime =  {$nameOfField}_DateObject(Math.min(selectedHour.setMinutes(59).valueOf(),{$nameOfField}_latestDate.valueOf()));
				{$nameOfField}_SetMinutesControl(earliestTime,latestTime,false,newSelectedMinutes,{$nameOfField}_roundOffEveryMinute);
				{$nameOfField}_minuteControlChange();
			}
			function {$nameOfField}_minuteControlChange(){
				{$nameOfField}_SetHiddenFieldFromFieldValues();
				var newSelectedMinutes = {$nameOfField}_DateObject({$nameOfField}_GetField('minutes').value);
		";
		if ($dependentsExists){
			$script .= "
				{$nameOfField}_UpdateOtherControl();
			";
		}
		$script .= "
			}
		";
		
		if ($dependentsExists){
			$script .= $this->_DependentCode();
		}
		
		
		$script .= "

			function {$nameOfField}_SetHiddenFieldFromFieldValues(){
				var selectedDate = {$nameOfField}_DateObject({$nameOfField}_GetField('minutes').value);
				{$nameOfField}_SetHiddenField(selectedDate);
			}
			function {$nameOfField}_SetMinutesControl(firstTime,lastTime,displayHour,selectedTime,roundOffEvery){
				var field = {$nameOfField}_GetField('minutes');
				var firstTimeWholeMinutes = {$nameOfField}_RoundOffDate(firstTime,'minutes');
				var lastTimeWholeMinutes = {$nameOfField}_RoundOffDate(lastTime,'minutes');
				var selectedTimeWholeMinutes = {$nameOfField}_RoundOffDate(selectedTime,'minutes');
				var timeToInsert = {$nameOfField}_DateObject(Math.floor(firstTimeWholeMinutes.valueOf()/(1000*60*roundOffEvery))*(1000*60*roundOffEvery));
				var optionsStringsArray = new Array();
				var optionsValuesArray = new Array();
				while(timeToInsert.valueOf() <= lastTimeWholeMinutes.valueOf()){
					var optionString = '';
					if (displayHour) {
						optionString += timeToInsert.getHours();
						optionString += ':';
					}
					var minutes = timeToInsert.getMinutes();
					if (minutes < 10){
						optionString += '0'+minutes;
					}else{
						optionString += minutes;
					}
					optionsStringsArray.push(optionString);
					optionsValuesArray.push(timeToInsert.valueOf());
					timeToInsert.setMinutes(timeToInsert.getMinutes()+roundOffEvery);
				}
				field.length = optionsStringsArray.length;
				for (var i = 0; i < field.length; i++){
					var minutesOption = field.item(i);
					minutesOption.text = optionsStringsArray[i];
					minutesOption.value = optionsValuesArray[i];
					if (optionsValuesArray[i] == selectedTimeWholeMinutes.valueOf()){
						minutesOption.selected = true;
					}
				}
				{$nameOfField}_currentDate = selectedTimeWholeMinutes;
			}
			function {$nameOfField}_SetHourControl(firstTime,lastTime,displayDay,selectedTime){
				var field = {$nameOfField}_GetField('hours');
				var firstTimeWholeHours = {$nameOfField}_RoundOffDate(firstTime,'hours');
				var lastTimeWholeHours = {$nameOfField}_RoundOffDate(lastTime,'hours');
				var selectedTimeWholeHours = {$nameOfField}_RoundOffDate(selectedTime,'hours');
				var timeToInsert = firstTimeWholeHours;
				var optionsStringsArray = new Array();
				var optionsValuesArray = new Array();
				while(timeToInsert.valueOf() <= lastTimeWholeHours.valueOf()){
					var optionString = '';
					if (displayDay) {
						optionString += {$nameOfField}_GetDayString(timeToInsert,false);
						optionString += ' kl ';
					} 
					optionString += timeToInsert.getHours()+':';
					optionsStringsArray.push(optionString);
					optionsValuesArray.push(timeToInsert.valueOf());
					timeToInsert.setHours(timeToInsert.getHours()+1);
				}
				field.length = optionsStringsArray.length;
				for (var i = 0; i < field.length; i++){
					var hourOption = field.item(i);
					hourOption.text = optionsStringsArray[i];
					hourOption.value = optionsValuesArray[i];
					if (optionsValuesArray[i] == selectedTimeWholeHours.valueOf()){
						hourOption.selected = true;
					}
				}
			}
			
			function {$nameOfField}_SetYearControl(firstDay,lastDay,selectedDay){
				var field = {$nameOfField}_GetField('years');
				var dateToInsert = {$nameOfField}_DateObject(firstDay.valueOf());
				var optionsStringsArray = new Array();
				var optionsValuesArray = new Array();
				while(dateToInsert.getFullYear() <= lastDay.getFullYear()){
					optionsStringsArray.push({$nameOfField}_GetYear(dateToInsert));
					optionsValuesArray.push(dateToInsert.getFullYear());
					dateToInsert.setFullYear(dateToInsert.getFullYear()+1);
				}
				field.length = optionsStringsArray.length;
				for (var i = 0; i < field.length; i++){
					var yearOption = field.item(i);
					yearOption.text = optionsStringsArray[i];
					yearOption.value = optionsValuesArray[i];
					if (optionsValuesArray[i] == selectedDay.getFullYear()){
						yearOption.selected = true;
					}
				}
			}
			function {$nameOfField}_SetMonthControl(firstDay,lastDay,displayYear,selectedDay){
				var field = {$nameOfField}_GetField('months');
				var firstDate = {$nameOfField}_DateObject({$nameOfField}_RoundOffDate(firstDay,'months'));
				var lastDate = {$nameOfField}_DateObject({$nameOfField}_RoundOffDate(lastDay,'months'));
				var selectedDate = {$nameOfField}_DateObject({$nameOfField}_RoundOffDate(selectedDay,'months'));
				var dateToInsert = firstDate;
				var optionsStringsArray = new Array();
				var optionsValuesArray = new Array();
				while(dateToInsert < {$nameOfField}_DayAfter(lastDate)){
					var monthString = {$nameOfField}_GetMonthString(dateToInsert.getMonth());
					var yearString = {$nameOfField}_GetYear(dateToInsert);
					var optionString = monthString;
					if (displayYear){
						optionString += ' '+yearString;
					}
					optionsStringsArray.push(optionString);
					optionsValuesArray.push(dateToInsert.valueOf());
					dateToInsert.setMonth(dateToInsert.getMonth()+1);
				}
				field.length = optionsStringsArray.length;
				for (var i = 0; i < field.length; i++){
					var monthOption = field.item(i);
					monthOption.text = optionsStringsArray[i];
					monthOption.value = optionsValuesArray[i];
					if (optionsValuesArray[i] == selectedDate.valueOf()){
						monthOption.selected = true;
					}
				}
				
			}
			function {$nameOfField}_SetDayControl(firstDay,lastDay,displayMonths,selectedDay){
				var field = {$nameOfField}_GetField('days');
				var selectedDate = {$nameOfField}_RoundOffDate(selectedDay,'days');
				var firstDate = {$nameOfField}_RoundOffDate(firstDay,'days');
				var lastDate = {$nameOfField}_RoundOffDate(lastDay,'days');
				var daysWithin = {$nameOfField}_NumberOfDays(firstDate,lastDate); 
				var daysBefore = {$nameOfField}_NumberOfDaysToDisplayFromPreviousMonth(firstDate,lastDate,displayMonths);
				var daysAfter = {$nameOfField}_NumberOfDaysToDisplayFromNextMonth(firstDate,lastDate,displayMonths);
				var optionsStringsArray = new Array();
				var optionsValuesArray = new Array();
				var inputString = '';
				if(daysBefore > 0){
					var dateBeforeToInsert = {$nameOfField}_DateObject(firstDate.valueOf());
					dateBeforeToInsert.setDate(dateBeforeToInsert.getDate()-daysBefore);
					for (var i = daysBefore; i > 0 ; i--){
						inputString = {$nameOfField}_GetDayString(dateBeforeToInsert,false,'short')+' ('+{$nameOfField}_GetMonthString(dateBeforeToInsert.getMonth(),'short')+')';
						optionsStringsArray.push(inputString);
						optionsValuesArray.push(dateBeforeToInsert.valueOf());
						dateBeforeToInsert.setDate(dateBeforeToInsert.getDate()+1);
					}
				}
				var dateToInsert = {$nameOfField}_DateObject(firstDate.valueOf());
				for (var j = 0; j < daysWithin ; j++){
					inputString = {$nameOfField}_GetDayString(dateToInsert,displayMonths);
					optionsStringsArray.push(inputString);
					optionsValuesArray.push(dateToInsert.valueOf());
					dateToInsert.setDate(dateToInsert.getDate()+1);
				}
				if (daysAfter > 0){
					var dateAfterToInsert = {$nameOfField}_DateObject(lastDate.valueOf());
					dateAfterToInsert.setDate(dateAfterToInsert.getDate()+1);
					for (var k = 0; k < daysAfter ; k++){
						inputString = {$nameOfField}_GetDayString(dateAfterToInsert,false,'short')+' ('+{$nameOfField}_GetMonthString(dateAfterToInsert.getMonth(),'short')+')';
						optionsStringsArray.push(inputString);
						optionsValuesArray.push(dateAfterToInsert.valueOf());
						dateAfterToInsert.setDate(dateAfterToInsert.getDate()+1);
					}
				}
				field.length = optionsStringsArray.length;
				for (var i = 0; i < field.length; i++){
					var dateOption = field.item(i);
					dateOption.text = optionsStringsArray[i];
					dateOption.value = optionsValuesArray[i];
					if (optionsValuesArray[i] == selectedDate.valueOf()){
						dateOption.selected = true;
					}
				}
			}
			function {$nameOfField}_NumberOfDaysToDisplayFromPreviousMonth(firstDay,lastDay,displayMonths){
				var ONEDAY = (24*60*60*1000);
				var firstDate = {$nameOfField}_DateObject(firstDay);
				var lastDate = {$nameOfField}_DateObject(lastDay);
				var sameMonth = ((firstDate.getMonth()==lastDate.getMonth()) && (firstDate.getYear()==lastDate.getYear()) && ! displayMonths);
				var daysToFirst = Math.floor((firstDate.valueOf()-{$nameOfField}_earliestDate.valueOf())/ONEDAY);
				var out = 0;
				if(sameMonth && daysToFirst > 0){
					var numberOfDaysNotInMonth = 31 - lastDate.getDate();
					out = Math.max(0,Math.min(numberOfDaysNotInMonth,daysToFirst));
				}
				return out;
			}
			function {$nameOfField}_NumberOfDaysToDisplayFromNextMonth(firstDay,lastDay,displayMonths){
				var ONEDAY = (24*60*60*1000);
				var firstDate = {$nameOfField}_DateObject(firstDay);
				var lastDate = {$nameOfField}_DateObject(lastDay);
				var sameMonth = ((firstDate.getMonth()==lastDate.getMonth()) && (firstDate.getYear()==lastDate.getYear()) && ! displayMonths);
				var daysToLast = Math.ceil(({$nameOfField}_latestDate.valueOf()-lastDate.valueOf())/ONEDAY);
				var out = 0;
				if(sameMonth && firstDate.getDate() > 1 && daysToLast > 0){
					out = Math.max(0,Math.min(firstDate.getDate()-1,daysToLast));
				}
				return out;
			}
			function {$nameOfField}_DayAfter(dateValue){
				var ONEDAY = (24*60*60*1000);
				var out = {$nameOfField}_DateObject(dateValue.valueOf()+ONEDAY);
				return out;
			}
			function {$nameOfField}_GetYear(dateValue){
				nowValue = new Date();
				thisYear = nowValue.getFullYear();
				out = dateValue.getFullYear();
				if (out==thisYear){
					out = 'i år';
				}else if(out==thisYear+1){
					out = 'neste år';
				}else if(out==thisYear-1){
					out = 'i fjor';
				}
				return out;
			}
			function {$nameOfField}_GetDayString(dateValue, displayMonths, form){
				var ONEDAY = (24*60*60*1000);
				var todayValue = {$nameOfField}_RoundOffDate(new Date(),'days');
				var dayValue = {$nameOfField}_RoundOffDate(dateValue,'days').valueOf();
				var out = '';
				if(dayValue == todayValue.valueOf()){
					out = 'i dag';
				}else if(dayValue - ONEDAY == todayValue.valueOf()){
					out = 'i morgen';
				}else if(dayValue + ONEDAY == todayValue.valueOf()){
					out = 'i går';
				}else{
					var dayToDescribe = {$nameOfField}_DateObject(dayValue);
					if (Math.abs(todayValue-dayValue)/(ONEDAY*365.24) < 5) {
						out = {$nameOfField}_GetWeekdayString(dayToDescribe.getDay(),form)+' ';
					}
					out += dayToDescribe.getDate()+'.';
					if(displayMonths){
						out += ' '+{$nameOfField}_GetMonthString(dayToDescribe.getMonth());
					}
				}
				return out;
			}
			function {$nameOfField}_GetDateObject(yy,mm,dd,hh,mmm,ss){
				var out = new Date();
				out.setDate(dd);
				out.setMonth(mm);
				out.setYear(yy);
				out.setHours(hh);
				out.setMinutes(mmm);
				out.setSeconds(ss);
				return out;
			}
			function {$nameOfField}_GetField(idString){
				switch(idString){
					case 'years':
						return document.getElementById('{$nameOfField}_aar');
						break;
					case 'months':
						return document.getElementById('{$nameOfField}_mnd');
						break;
					case 'days':
						return document.getElementById('{$nameOfField}_dag');
						break;
					case 'hours':
						return document.getElementById('{$nameOfField}_timer');
						break;
					case 'minutes':
						return document.getElementById('{$nameOfField}_minutter');
						break;
					case 'date':
						return document.getElementById('{$nameOfField}');
						break;
					case 'alternate':
						return document.getElementById('{$nameOfField}_alternateField');
						break;
					case 'chooser':
						return document.getElementById('{$nameOfField}_chooserNoDate');
						break;
					case 'switch':
						return document.getElementById('{$nameOfField}_dateSwitchOn');
						break;
				}
			}
			function {$nameOfField}_GetMonthString(monthIndex, form){
				if(form=='short'){
					var months = new Array('jan','feb','mar','apr','mai','jun','jul','aug','sep','okt','nov','des');
				}else{
					var months = new Array('januar','februar','mars','april','mai','juni','juli','august','september','oktober','november','desember');
				}
				return months[monthIndex];
			}
			function {$nameOfField}_GetWeekdayString(dayIndex, form){
				if(form=='short'){
					var days = new Array('sø','ma','ti','on','to','fr','lø');
				}else{
					var days = new Array('søndag','mandag','tirsdag','onsdag','torsdag','fredag','lørdag');
				}
				return days[dayIndex];
			}
			function {$nameOfField}_RoundOffDate(dateValue,unitsToRemove){
				var out = {$nameOfField}_DateObject(dateValue.valueOf());
				switch (unitsToRemove){
					case 'years':
						out.setMonth(0);
					case 'months':
						out.setDate(1);
					case 'days':
						out.setHours(0);
					case 'hours':
						out.setMinutes(0);
					case 'minutes':
						out.setSeconds(0);
					case 'seconds':
						out.setMilliseconds(0);
				}
				return out;
			}
			function {$nameOfField}_AlignDates(dateValue,targetDate,presisionUnit){
				var out = {$nameOfField}_DateObject(dateValue.valueOf());
				switch(presisionUnit){
					case 'milliseconds':
						out.setMilliseconds(targetDate.getMilliseconds());
					case 'seconds':
						out.setSeconds(targetDate.getSeconds());
					case 'minutes':
						out.setMinutes(targetDate.getMinutes());
					case 'hours':
						out.setHours(targetDate.getHours());
					case 'days':
						out.setDate(targetDate.getDate());
					case 'months':
						out.setMonth(targetDate.getMonth());
					case 'years':
						out.setFullYear(targetDate.getFullYear());
				}
				return out;
			}
			function {$nameOfField}_DateObject(dateSerial){
				var out = new Date(dateSerial * 1);
				return out;
			}
			function {$nameOfField}_HighestDateOfMonth(dateValue){
				var ONEDAY = (24*60*60*1000);
				var dateValueLastDay = {$nameOfField}_RoundOffDate(dateValue,'months');
				dateValueLastDay.setMonth(dateValueLastDay.getMonth()+1);
				var lastDate = {$nameOfField}_DateObject(dateValueLastDay.valueOf()-ONEDAY).getDate();
				return lastDate;
			}
			function {$nameOfField}_NumberOfDays(date1,date2){
				var ONEDAY = (24*60*60*1000);
				var out = Math.ceil((date2.valueOf()-date1.valueOf())/ONEDAY)+1;
				return out;
			}
			function {$nameOfField}_SetVisibility(objectToSetVisibilityOf, visible){
				if (visible){
					objectToSetVisibilityOf.style.display = 'inline';
				}else{
					objectToSetVisibilityOf.style.display = 'none';
				}
			}
			function {$nameOfField}_GetValueOfField(fieldId){
				var out = '';
				var valueOfField = {$nameOfField}_GetField(fieldId).value;
				if (valueOfField == ''){
					if (fieldId=='years'){
						out = {$nameOfField}_currentDate.getYear();
					}else{
						out = {$nameOfField}_currentDate.valueOf()
					}
				}else{
					out = valueOfField;
				}
				return out;
			}
			function {$nameOfField}_DateObjectFromLocalTime(localTimeStamp){
				var out = {$nameOfField}_DateObject(localTimeStamp);
				out.setMinutes(out.getMinutes()+out.getTimezoneOffset());
				return out;
			}
			function {$nameOfField}_LocalTimeStampFromDateObject(dateObject){
				var tempObj = {$nameOfField}_DateObject(dateObject.valueOf());
				tempObj.setMinutes(tempObj.getMinutes()-tempObj.getTimezoneOffset());
				return tempObj.valueOf()
			}
		";
		$script .= $this->_DateFieldControllers();
		
		$out = "\n$controls\n";
		$out .= JavaScriptWrapper($script);
		$out .= "\n";
		return $out;
	}
}
?>