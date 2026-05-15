


<?php 
require_once("../assets/inc/config.php");
require_once("../assets/inc/functions.php");
$option = "";
$category_id = getSaveValue($conn, $_POST["category_id"]);
$sql = "select * from sub_categories where category_id= '$category_id' AND status='1'";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res)>0){
    while($row = mysqli_fetch_array($res)){
        $option .= ' <option value="'.$row["id"].'">'.$row["title"].'</option>';
    }
}else{
    $option =  ' <option value="0">No Category Found</option>';
}
echo $option;



?>
