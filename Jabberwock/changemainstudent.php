<?php
require('../util/User.php');
session_start();
$conn = new mysqli("localhost", "adoptape", "u7w58d3294s", "adoptape_jabberwockdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "UPDATE siteusersettings SET mainstudentid='".$_POST['newstudentid']."' WHERE userid=".$_SESSION['user']->getID().";";
$result = mysqli_query($conn, $sql);
?>
