<?php
require('../util/User.php');
session_start();
$conn = new mysqli("localhost", "adoptape", "u7w58d3294s", "adoptape_jabberwockdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$debug = False;

$sql = "SELECT * FROM recipes LEFT JOIN inventories ON inventories.itemid=recipes.ingredientid  AND inventories.ownerid=".$_SESSION['user']->getID()." WHERE recipes.recipeid=".$_POST['recipeID'].";";
$result = mysqli_query($conn, $sql);
$canCraft = True;
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (($row['quantity'] == null) || ($row['ingredientquantity'] > $row['quantity'])) {
            $canCraft = False;
        }
    }
}

if ($canCraft) {
    $sql = "SELECT * FROM recipes LEFT JOIN inventories ON inventories.itemid=recipes.ingredientid  AND inventories.ownerid=".$_SESSION['user']->getID()." WHERE recipes.recipeid=".$_POST['recipeID'].";";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $updatesql = "UPDATE inventories SET quantity=".$row['quantity']."-".$row['ingredientquantity']." WHERE ownerid=".$_SESSION['user']->getID()." AND itemid=".$row['ingredientid'].";";
            if ($debug) {
                echo $updatesql."<br>";
            } else {
                $updateresult = mysqli_query($conn, $updatesql);
            }
        }
    }

    $sql = "SELECT * FROM inventories WHERE ownerid=".$_SESSION['user']->getID()." AND itemid=".$_POST['recipeID'].";";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $updatesql = "UPDATE inventories SET quantity=".$row['quantity']."+1 WHERE ownerid=".$_SESSION['user']->getID()." AND itemid=".$_POST['recipeID'].";";
        if ($debug) {
            echo $updatesql."<br>";
        } else {
            $updateresult = mysqli_query($conn, $updatesql);
        }
    } else {
        $insertsql = "INSERT INTO `inventories`(`ownerid`, `quantity`, `itemid`) VALUES (".$_SESSION['user']->getID().",1,".$_POST['recipeID'].");";
        if ($debug) {
            echo $insertsql."<br>";
        } else {
            $insertresult = mysqli_query($conn, $insertsql);
        }
    }

    $unixtimestamp = time();

    $maxTransLogSql = mysqli_query($conn, "SELECT MAX(transactionid) as maxid FROM transactionhistory;");
    $maxTransLogRow = mysqli_fetch_assoc($maxTransLogSql);
    $maxTransLogID = $maxTransLogRow['maxid'] + 1;
    $transactionhistorysql = "INSERT INTO `transactionhistory`(`transactionid`, `userid`, `timestamp`, `action`, `fullaction`, `quantity`, `itemid`) VALUES (".$maxTransLogID.",".$_SESSION['user']->getID().",".$unixtimestamp.",'craft','Crafted 1 ".$_POST['recipeName']."',1,".$_POST['recipeID'].");";
    if ($debug) {
        echo $transactionhistorysql."<br>";
    } else {
        $transactionhistoryresult = mysqli_query($conn, $transactionhistorysql);
    }

    $sql = "DELETE FROM assignments WHERE ownerid=".$_SESSION['user']->getID()." AND recipeid=".$_POST['recipeID']." LIMIT 1;";
    if ($debug) {
        echo $sql."<br>";
    } else {
        $result = mysqli_query($conn, $sql);
    }

    /*
    weekly assignments: 2 points
    crafted materials: 1 point
    store crafted: 0.5 points
    */

    $points = 0;
    $pointsaction = "";

    $weeklyassignmentsql = mysqli_query($conn, "SELECT * FROM weeklyuserassignments WHERE ownerid=".$_SESSION['user']->getID().";");
    $weeklyassignmentrow = mysqli_fetch_assoc($weeklyassignmentsql);
    if (($weeklyassignmentrow['recipeid'] == $_POST['recipeID']) && ($weeklyassignmentrow['isDone'] == 0)) {
        $points = 2;
        $pointsaction = 'Crafted weekly assignment: ';

        $weeklyassignmentsql = mysqli_query($conn, "UPDATE weeklyuserassignments SET isDone=1 WHERE ownerid=".$_SESSION['user']->getID().";");
        $weeklyassignmentrow = mysqli_fetch_assoc($weeklyassignmentsql);
    } else {
        $itemtypesql = mysqli_query($conn, "SELECT type FROM items WHERE itemid=".$_POST['recipeID'].";");
        $itemtyperow = mysqli_fetch_assoc($itemtypesql);
        $itemtype = $itemtyperow['type'];
        if ($itemtype == 'a-gift') {
            $points = 0.5;
            $pointsaction = 'Crafted shop gift: ';
        } else if ($itemtype == 'b-craft') {
            $points = 1;
            $pointsaction = 'Crafted material: ';
        }
    }
    $pointsaction .= $_POST['recipeName'];

    $primaryStudentSql = mysqli_query($conn, "SELECT studentid FROM siteusersettings LEFT JOIN students ON students.studentid=siteusersettings.mainstudentid WHERE userid=".$_SESSION['user']->getID().";");
    $primaryStudentRow = mysqli_fetch_assoc($primaryStudentSql);

    $pointssql = "INSERT INTO `craftingpoints`(`timestamp`, `userid`, `studentid`, `points`, `fullaction`) VALUES (".$unixtimestamp.",".$_SESSION['user']->getID().",".$primaryStudentRow['studentid'].",".$points.",'".$pointsaction."');";
    if ($debug) {
        echo $pointssql."<br>";
    } else {
        $pointsresult = mysqli_query($conn, $pointssql);
    }
} else if ($debug) {
    echo "You can't craft ".$_POST['recipeName']." :(";
}

?>
