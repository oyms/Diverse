<?php
/**
 * Provides autoforwarding to and from index.php (Playerselector).
 */

/**
 * Checks if player id is stored.
 * Forwards as appropriate.
 *
 */
class AutoForward_class{
	/**
	 * A player object
	 *
	 * @var Player_class
	 */
	private $player;
	public function AutoForward_class(){
		$this->player=new Player_class();
	}
	/**
	 * True if player is defined (through POST, GET or cookie)
	 *
	 * @return boolean
	 */
	private function _playerIsDefined(){
		return $this->player->getId()>0;
	}
	/**
	 * Performs a forward or backward to
	 * player list or event list as needed. Returns
	 * true if redirect is performed.
	 * Redirects by client script if headers are sent, 
	 * by headers otherwise.
	 *
	 * @return boolean
	 */
	public function Redirect(){
		$didRedirect=false;
		$pageName=MNameOfCurrentPage();
		$isLoginPage=($pageName==MLOGINPAGE||$pageName=="");
		if($this->_playerIsDefined()){
			//Forward to eventList
			if($isLoginPage){
				Redirect(MEVENTLIST,301);
				$didRedirect=true;
			}
		}else{
			//Forward (backward) to playerList
			if(!$isLoginPage){
				Redirect(MLOGINPAGE,307);
				$didRedirect=true;
			}
		}
		return $didRedirect;
	}
}
?>