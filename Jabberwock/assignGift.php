<?php
require('../util/User.php');
session_start();
$conn = new mysqli("localhost", "adoptape", "u7w58d3294s", "adoptape_jabberwockdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$studentid = $_POST['studentid'];
$itemid = str_replace("unassignedgift","",$_POST['itemid']);
$giftnote = mysqli_real_escape_string($conn, $_POST['giftnote']);

$maxSql = mysqli_query($conn, "SELECT MAX(giftid) as maxid FROM giftsinventories;");
$maxRow = mysqli_fetch_assoc($maxSql);
$maxID = $maxRow['maxid'] + 1;

$sql = "INSERT INTO `giftsinventories`(`giftid`, `userid`, `studentid`, `itemid`, `note`) VALUES (".$maxID.",".$_SESSION['user']->getID().",".$studentid.",".$itemid.",'".$giftnote."');";
$result = mysqli_query($conn, $sql);

?>
