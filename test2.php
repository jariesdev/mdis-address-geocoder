<?php
$db = './storage/NCR_MANILA.mdb';

$dbName = $db;
$driver = 'MDBTools';

$dbh = odbc_connect("DRIVER=$driver; DBQ=$dbName;", '', '');
if ($dbh === false) {
    echo 'unable to connect.';
    exit;
}
// Table with column num, name test
$sql = "SELECT * FROM NCR_MANILA";  // The rules are the same as above
//$sth = odbc_exec($dbh, $sql);


// LOCK_EX will prevent anyone else writing to the file at the same time
// PHP_EOL will add linebreak after each line
//$txt = "data-to-add";
//file_put_contents('sample.txt', $txt.PHP_EOL, FILE_APPEND | LOCK_EX);

// Second option is this
//$myfile = fopen("./sample.txt", "a") or die("Unable to open file!");

// Because of binding to the column, only the judgment result will be returned to $ flg
//$values = [];  // Array to assign the value
//while ($flg = odbc_fetch_object($sth)) {
//    $txt = sprintf("%s :  %s\n", $flg->REFID, $flg->STREET);
//    fwrite($myfile, PHP_EOL.$txt);
//}
//fclose($myfile);

$tables = odbc_tables($dbh, null,null,null,'TABLE');
while (($row = odbc_fetch_array($tables))) {
    print_r($row);
}
