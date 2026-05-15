<?php
require_once("tables.php");

if (isset($_GET["action"])) {
    $action = $_GET["action"];
    $id = $_GET["id"];

    if ($action == "delete") {
        mysqli_query($conn, "delete from $table where id = '$id'");
        header("location:$pageName");
    }
    if ($action == "active") {
        mysqli_query($conn, "update $table set status=1 where id = '$id'");
        header("location:$pageName");
    }
    if ($action == "deactive") {
        mysqli_query($conn, "update $table set status=0 where id = '$id'");
        header("location:$pageName");
    }
    if ($action == "admin") {
        mysqli_query($conn, "update $table set role=1 where id = '$id'");
        header("location:$pageName");
    }
    if ($action == "manager") {
        mysqli_query($conn, "update $table set role=0 where id = '$id'");
        header("location:$pageName");
    }
}

$sql = "select * from $table order by id desc";
$result = mysqli_query($conn, $sql);


if (isset($_SESSION["msg"])) {
    echo $_SESSION["msg"];
    unset($_SESSION["msg"]);
}
