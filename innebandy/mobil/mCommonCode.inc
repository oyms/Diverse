<?php
define("MEVENTLIST","eventList.php");
define("MLOGINPAGE","index.php");
define("MEVENTDETAILS","eventDetails.php");
define("MMESSAGELIST","messageList.php");
define("MMESSAGEDETAILS","messageDetails.php");
define("MSTYLESHEET","mobile.css");
include_once("../base.inc");
include_once("../commonCode.inc");
include_once("./mobileMenu.inc");
/*-------------
/Functions
/-------------*/

function MNameOfCurrentPage(){
	$scriptName=$_SERVER['SCRIPT_NAME'];
	$posOfSlash=strrpos($scriptName,"/");
	$returnValue="";
	if($posOfSlash===false){
		$returnValue=$scriptName;
	}else{
		$returnValue=substr($scriptName,$posOfSlash+1);
	}
	return $returnValue;
}

/*-------------
/Classes
/-------------*/

/**
 * Base class for rendering a standard page
 * for mobile users
 *
 */
class MMainBase_class{
	private $content, $title, $redirectPerformed;
	/**
	 * Constructor
	 * Runs Redirect method
	 *
	 * @return MMainBase_class
	 */
	protected function MMainBase_class(){
		$this->Redirect();
	}
	public function GetContent(){
		$out = $this->_HTMLWrapper($this->content, $this->title);
		return $out;	
	}
	/**
	 * Defines the page title
	 *
	 * @param string $title
	 */
	protected function SetTitle($title){
		$this->title=$title;
	}
	/**
	 * Defines the page body
	 * between the top and bottom navigation
	 *
	 * @param string $content
	 */
	protected function SetContent($content){
		$this->content=$content;
	}
	/**
	 * Runs AutoForward
	 *
	 */
	protected function Redirect(){
		include_once("autoForward.inc");
		$redirector=new AutoForward_class();
		$this->redirectPerformed=$redirector->Redirect();
	}
	/**
	 * True if redirect is performed. The rest of content parsing and rendering may be skipped.
	 *
	 * @return unknown
	 */
	protected function HasRedirected(){
		return($this->redirectPerformed===true);
	}
	private function _HTMLWrapper($content, $title){
		$out = "<html>\n";
		$out .= "<head>\n";
		$out .= "<title>$title</title>\n";
		$out .= $this->_renderCSSLink();
		$out .= "</head>\n";
		$out .= "\n<body>\n";
		$out .= $this->_topNavBar();
		$out .= "\n";
		$out .= $content;
		$out .= "\n";
		$out .= $this->_bottomNavBar();
		$out .= "\n</body>\n";
		$out .= "\n</html>";
		return $out;	
	}
	private function _topNavBar(){
		$navBar=new MobileMenu_class();
		return $navBar->RenderTopMenu();
	}
	private function _bottomNavBar(){
		$navBar=new MobileMenu_class();
		return $navBar->RenderLinkToNonMobileHomePage();
	}
	private function _renderCSSLink(){
		$output="\n";
		$styleSheetName=MSTYLESHEET;
		$cssPrefix="";
		$commonCssName="common.css";
		$output .="\t<link href='../{$cssPrefix}{$commonCssName}' rel='stylesheet' type='text/css'/>\n";
		if($styleSheetName){
			$output .="\t<link href='{$styleSheetName}' rel='stylesheet' type='text/css'/>\n";
		}
		return $output;
	}
}

?>