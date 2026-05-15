<?php

function printArray($str)
{
    echo "<pre>";
    print_r($str);
    echo "</pre>";
}
function getSaveValue($conn, $str)
{
    $str = trim($str);
    $str = mysqli_real_escape_string($conn, $str);
    $str = htmlentities($str);
    return $str;
}
function getFullName($conn, $table = "", $id = "",$column="title")
{
    $sql = " SELECT * FROM  `$table` WHERE `status`='1' AND `id`='$id' ";
    $res = mysqli_query($conn, $sql);
    if (mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        return str_replace("-"," ",$row["$column"]);
    } else {
        return "---";
    }
}

function getStatusCount($conn, $table, $col='status', $val='1' ){
    $res = mysqli_query($conn, "select * from $table where $col = '$val'");
    return mysqli_num_rows($res);
}
