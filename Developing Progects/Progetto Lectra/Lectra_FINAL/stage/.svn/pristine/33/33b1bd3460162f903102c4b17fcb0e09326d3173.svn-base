<?php





require_once ('connector.php');

$config_path= "/var/www/service/stage/";
$config = new Configurator($config_path);

//as400
$as400 = new AS400Connector($config);
$sql="SELECT * FROM AZGRPCAMI.MMA1PF ORDER BY NUMMM1 DESC LIMIT 1";
$res= $as400->query($sql);

echo("sql<br/>");
echo($sql);

echo("db<br/>");
echo("<pre>");
print_r($as400);
echo("</pre>");



echo("res<br/>");
echo("<pre>");
print_r($res);
echo("</pre>");

/* $sql="SELECT * FROM AZGRPCAMI.MMA1PF ORDER BY NUMMM1 DESC LIMIT 1";

       Dbg::d("sql",$sql,1);
       $res= $this->as400->query($sql);
       Dbg::d("this->as400",$this->as400,1);
       Dbg::d("res",$res,1);




//bi
$db = new MysqlConnector($config);
$sql= "SELECT * FROM redemption_view LIMIT 1 ";

echo("sql<br/>");
echo($sql);

echo("db<br/>\"");
echo("<pre>");
print_r($db);
echo("</pre>");

$res= $db->query($sql);

echo("res<br/>");
echo("<pre>");
print_r($res);
echo("</pre>");


Dbg::d("res", $res ,1);


