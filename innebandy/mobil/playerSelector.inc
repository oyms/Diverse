<?php
/**
 * Classes used in player selector
 */
//To get db access
require_once("mCommonCode.inc");
class MPlayerSelector_class extends HtmlElements {
	/**
	 * PlayerData_class array
	 *
	 * @var Array
	 */
	private $players;
	public function MPlayerSelector_class(){
		$team=new Team_class();
		$playerCollection=new PlayerCollection_class($team->get_id());
		$this->players=$playerCollection->GetPlayers();
		$this->setContent($this->_renderForm(rand(100000,999999)."_form",MEVENTLIST));
	}
	public function GetControls(){
		$controls=array();
		foreach ($this->players as $player){
			$controls[]=new PlayerFormControl($player);
		}
		return $controls;
	}
	private function _renderForm($formName,$urlHere){
		$submitButtonId=rand(100000,999999)."_submit";
		$enableSubmitFunctionName="enable_$submitButtonId";
		$out="";
		$out.=$this->MakeFormStart($formName,$urlHere );
		$out.=$this->_clientScriptEnableSubmitButton($submitButtonId,$enableSubmitFunctionName,$formName);
		foreach($this->GetControls() as $control){
			$out.=$control->RenderHtml("$enableSubmitFunctionName();");
			$out.="<br/>";
		}
		$out.=$this->_submitButton($submitButtonId);
		$out.=$this->MakeFormEnd();
		return $out;
	}
	private function _submitButton($buttonId){
		$out="\n";
		$out.="<a name=\"$buttonId\">\n";
		$out.="\t<input type=\"submit\" value=\"Hvem er du?\" id=\"$buttonId\" name=\"$buttonId\" disabled>\n";
		$out.="</a>\n";
		return $out;
	}
	private function _clientScriptEnableSubmitButton($buttonId, $functionName, $formName){
		$out="\nfunction $functionName(){\n";
		$out.="\tvar button=null;\n";
		$out.="\tbutton=document.forms[\"{$formName}\"][\"$buttonId\"];\n";
		$out.="\tbutton.disabled=false;\n";
		$out.="\tbutton.value=\"Logg inn!\";\n";
		//$out.="\twindow.location=\"#$buttonId\";\n";
		$out.="}\n";
		return JavaScriptWrapper($out);
	}
}
/**
 * Renders form control for selecting a player
 *
 */
class PlayerFormControl{
	/**
	 * The player to display control and info for.
	 *
	 * @var PlayerData_class
	 */
	private $player;
	public function PlayerFormControl($player){
		$this->player=$player;
	}
	public function RenderHtml($onClickAction="",$group=""){
		global $playerIdFieldName;
		$groupName=$group==""?$playerIdFieldName:$group;
		$name=$this->player->GetName();
		$number=$this->player->GetNumber();
		if($number!=""){
			$name.=" ($number)";
		}
		$onClick=$onClickAction==""?"":" onClick=\"$onClickAction\"";
		$id=rand(100000,999999);
		$out="\n";
		$out.="<input type=\"radio\" name=\"$groupName\" value=\"{$this->player->GetId()}\" id=\"$id\"$onClick>";
		$out.="<label for=\"$id\" title=\"{$this->player->GetNotes()}\">$name</label> ";
		return $out;
	}
}
class PlayerCollection_class{
	public function PlayerCollection_class($teamId){
		
	}
	/**
	 * Queries db for players of current team.
	 * Returns array of playerData_class objects
	 *
	 * @return Array
	 */
	public function GetPlayers(){
		return $this->_getQuery();
	}
	/**
	 * Queries db for players.
	 * Returns array of PlayerData_class objects
	 *
	 * @return Array
	 */
	private function _getQuery(){
		$allPlayers = array();
		$team = new Team_class();
		$teamId = $team->get_id();
		$queryString = "
			SELECT 	players.id, players.firstName, players.lastName, players.url,
					players.residence, membership.notes, players.phone, 
					membership.number, players.age, players.email, 
					membership.teamTrainer,
					(IF(players.dubviousAge,DATE_SUB(NOW(),INTERVAL 1 DAY),players.age)) AS displayAge, 
					unix_timestamp(NOW())-unix_timestamp(players.lastLogin) AS secondsSinceLastLogin,
					FLOOR(unix_timestamp(NOW())-unix_timestamp(players.lastLogin)/(60*60*24*7)) AS weeksSinceLastLogin,
					(lastLogin>0) AS active
			FROM players INNER JOIN membership ON players.id = membership.player
			WHERE (((membership.team)=$teamId))
			ORDER BY active DESC, weeksSinceLastLogin ASC, players.lastName
		";
		$query = new DBConnector_class($queryString);
		while ($playerData = $query->GetNextRow()) {
			$allPlayers[] = new PlayerData_class(
				$team,
				$playerData['id'],
				$playerData['firstName'],
				$playerData['lastName'],
				$playerData['email'],
				$playerData['phone'],
				$playerData['number'],
				$playerData['notes'],
				$playerData['url'],
				$playerData['secondsSinceLastLogin'],
				$playerData['teamTrainer']
			);	
		}
		return $allPlayers;
	}
}

/**
 * Class for storing player data
 *
 */
class PlayerData_class{
	private $team, $id,$firstName,$lastName,$email,$phone,$number,$notes,$url,$secondsSinceLastLogin,$teamTrainer;
	/**
	 * Constructor for player class.
	 * All data must be defined in this constructor
	 *
	 * @param Team_class $team
	 * @param integer $id
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $email
	 * @param string $phone
	 * @param string $number
	 * @param string $notes
	 * @param string $url
	 * @param integer $secondsSinceLastLogin
	 * @param boolean $teamTrainer
	 * @return PlayerData_class
	 */
	public function PlayerData_class(
		$team, $id,$firstName,$lastName,$email,$phone,$number,$notes,$url,$secondsSinceLastLogin,$teamTrainer)
	{
		$this->team=$team;
		$this->id=$id;
		$this->firstName=$firstName;
		$this->lastName=$lastName;
		$this->email=$email;
		$this->phone=$phone;
		$this->number=$number;
		$this->notes=$notes;
		$this->url=$url;
		$this->secondsSinceLastLogin=$secondsSinceLastLogin;
		$this->teamTrainer=$teamTrainer;
	}
	/**
	 * The player id
	 *
	 * @return integer
	 */
	public function GetId(){
		return $this->id;
	}
	/**
	 * The full name of the player
	 * on the form First Last
	 *
	 * @return unknown
	 */
	public function GetName(){
		return "$this->firstName $this->lastName";
	}
	/**
	 * The player e-mail
	 * Use HasEmail to check if value is empty
	 *
	 * @return string
	 */
	public function GetEmail(){
		return $this->email;
	}
	/**
	 * Checks if e-mail is empty string
	 *
	 * @return boolean
	 */
	public function HasEmail(){
		return($this->email!="");
	}
	/**
	 * The phone number of the player
	 *
	 * @return string
	 */
	public function GetPhone(){
		return $this->phone;
	}
	/**
	 * The jersey number
	 *
	 * @return string
	 */
	public function GetNumber(){
		return $this->number;
	}
	/**
	 * The notes of the players membership
	 *
	 * @return string
	 */
	public function GetNotes(){
		return $this->notes;
	}
	/**
	 * The address of the players home page.
	 * use HasUrl to check if this is empty.
	 *
	 * @return string
	 */
	public function GetUrl(){
		return $this->url;
	}
	/**
	 * Checks if user has a home page
	 *
	 * @return unknown
	 */
	public function HasUrl(){
		return ($this->url!="");
	}
	/**
	 * A descriptive text describing time since last login.
	 *
	 * @return string
	 */
	public function TimeSinceLastlogin(){
		return $this->_secondsInText($this->secondsSinceLastLogin,$this->firstName);
	}
	/**
	 * True if player is trainer of the team
	 *
	 * @return boolean
	 */
	public function IsTeamTrainer(){
		return ($this->teamTrainer==true);
	}
	private function _secondsInText($secondsCount, $name){
		$outStr="";
		if ($secondsCount=="") {
				//Null
				$outStr="$name har ikke v&aelig;rt innom i historisk tid.";
		}elseif($secondsCount < 3600){
				//Under en time
				$outStr="$name var nettopp innom sidene.";
		}elseif($secondsCount < 43200){
				//Halvt døgn
				$outStr="$name har nylig v&aelig;rt innom og kikket.";
		}elseif($secondsCount < 259200){
				//Tre døgn
				$outStr="$name har v&aelig;rt innom i det siste.";
		}elseif($secondsCount < 1036800){
				//12 døgn
				$outStr="Det er noen dager siden $name sist var innom.";
		}elseif($secondsCount < 3024000){
				//5 uker
				$outStr="Det er noen uker siden $name var innom sist.";
		}else{
				$outStr="Det er lenge siden $name kikket p&aring; disse sidene sist.";
		}
		return $outStr;
	}
}
?>