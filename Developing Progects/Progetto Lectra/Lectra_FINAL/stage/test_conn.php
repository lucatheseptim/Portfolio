

<html>
<head>
    <title>Connecting MySQL Server</title>
</head>
<body>
<?php
phpinfo();

$dbhost = '127.0.0.1';
$dbuser = 'ced';
$dbpass = 'ThYmuMinJH-OUjiN3....';
$dbname = 'lectra_stage';

/*
$mysqli_connection = new MySQLi($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli_connection->connect_error) {
    echo "Not connected, error: " . $mysqli_connection->connect_error;
}
else {
    echo "Connected.";
}*/

// $conn=odbc_connect($dbname,$dbuser,$dbpass);
$conn=odbc_connect("AS400","BARONIR","BARONIR");
$dbh = new PDO("odbc:DRIVER={iSeries Access ODBC Driver};SYSTEM=172.31.212.240;PROTOCOL=TCPIP", 'BARONIR', 'BARONIR');
// First parameter is your DSN name, second is database username and third is database password

// $sql = "SELECT * FROM lectra_stage.materiali";
$sql = "SELECT * FROM AZGRPCAMI.MMA1PF LIMIT 10";
// $ret = odbc_exec($conn,$sql);
$ret = $dbh->query($sql, PDO::FETCH_ASSOC);

// Mysql (Funziona)
// $mysql = mysqli_connect("localhost", "ced", "ThYmuMinJH-OUjiN3....");
// $res = mysqli_query($mysql, "SHOW DATABASES");
// echo(print_r($res,1));
// mysqli_close($mysql);



// $query = $pdo->query($sql);
// $query->execute();
// while($row=$query->fetch(PDO::FETCH_ASSOC)) {
//         echo(print_r($row,1));
// }

// while ($row = odbc_fetch_row($rs)) {
if($ret){
    foreach ($ret as $row){
        // code...
        echo(print_r($row,1));
    }
    // $firstname = odbc_result($rs,"FirstName"); // second parameter contain a column name
    // $lastname = odbc_result($rs,"LastName");
}
odbc_close($conn);

?>



</body>
</html>
