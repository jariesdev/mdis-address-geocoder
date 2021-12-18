<?php
$db = './storage/NCR_MANILA.mdb';

$dbName = $db;
$driver = 'MDBTools';

$dbh = new  PDO("odbc:Driver=$driver;DBQ=$dbName;");

// Table with column num, name test
$sql = "SELECT REFID, STREET FROM NCR_MANILA";  // The rules are the same as above
$sth = $dbh->prepare($sql);
$sth->execute();
// Bind to each column
$sth->bindColumn(1, $fname);
$sth->bindColumn(2, $lname);

// Because of binding to the column, only the judgment result will be returned to $ flg
$values = [];  // Array to assign the value
while ($flg = $sth->fetch(PDO::FETCH_BOUND)) {
    $values[] = [$fname, $lname];
    echo $fname, ' :  ', $lname, "\n";
}
