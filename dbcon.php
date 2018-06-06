<?php
$host="localhost"; // Host navn
$username="*******"; // Mysql brugernavn
$password="*******"; // Mysql kodeord
$db_name="greentouch"; // Database navn



$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
   die('Connection failed: ('.$conn->connect_errno.') '.$conn->connect_error);
}
// Meta charset encoding i DB
mysqli_set_charset($conn,"utf8");
?>
