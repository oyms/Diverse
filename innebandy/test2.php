<?php 
/*$GLOBALS['_transient']['static']['test']->v1 = 1;*/
 
class Test {
 
    function Test() {
        $this->v1 = & $GLOBALS['_transient']['static']['test']->v1;
		$this->v2 = & $GLOBALS['_transient']['static']['test']->v2;
    }
 
    function printAndIncrease() {
		$this->v2 = 9;
		$this->v1++;
        echo "$this->v1<br>$this->v2<hl>";
        $this->v1++;
    }
 
    var $v1,$v2;
}
 
$t1 = new Test();
$t1->printAndIncrease();
$t2 = new Test();
$t2->printAndIncrease();
?>