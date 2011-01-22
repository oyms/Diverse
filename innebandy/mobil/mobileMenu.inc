<?php
/**
 * Provides a navigational menu
 * to be used in the mobile pages.
 *
 */
class MobileMenu_class{
	function MobileMenu_class(){
		
	}
	/**
	 * The menu to be rendered at the top of the page
	 *
	 * @return string
	 */
	function RenderTopMenu(){
		return "Placeholder<hr/>";
	}
	/**
	 * A link (anchor) to the HOMEPAGE
	 *
	 * @return string
	 */
	function RenderLinkToNonMobileHomePage(){
		include_once("../commonCode.inc");
		$out="\n<hr/><a href=\"http://".HOMEURL."\" title=\"Til ordin&aelig;r forside\">Til ordin&aelig;r (ikke mobil) hjemmeside</a>\n";
		return $out;
	}
}
?>