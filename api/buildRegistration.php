<?php
/**
 * Created by PhpStorm.
 * User: R_Rod
 * Date: 31/12/2018
 * Time: 15:14
 */
//include_once (__DIR__ . "/model/Build.php");
//include_once (__DIR__ . "/model/Component.php");
include_once (__DIR__ . "/model/Build_Components.php");
include_once "db.php";

header("Content-Type: application/json");

$input = json_decode(file_get_contents('php://input'));

$build_components = array();

$conn = connDB();
$data = [];

if(!empty($input->build_id) &&!empty($input->user_id) && !empty($input->build_type_id) && !empty($input->build_name) && !empty($input->description) && !empty($input->cpu_description) && !empty($input->gpu_description)
    && !empty($input->ram_description) && !empty($input->components)){

    $sql = "SELECT * FROM builds WHERE build_name = '$input->build_name'";
    $checkExisting = $conn->query($sql);

    if ($checkExisting->num_rows == 0){
        $insert = "INSERT INTO builds(build_id,user_id, build_type_id, build_name, description, cpu_description, gpu_description, ram_description, price, likes) VALUES('$input->build_id','$input->user_id','$input->build_type_id','$input->build_name', '$input->description', '$input->cpu_description', '$input->gpu_description', '$input->ram_description', '0' ,'0')";
        $query1 = $conn->query($insert);

        $build_components = $input->components;
        if($query1){
            for($i = 0; $i < count($build_components); $i++){
                $component = $build_components[$i];
                $insertBuildComponent = "INSERT INTO build_components(build_id,component_id,quantity) VALUES ('$input->build_id' , '$component->component_id' , '$component->quantity')";
                $query2 = $conn->query($insertBuildComponent);
            }
            if($query2){
                //update the price of the build
                $updatePrice = "UPDATE builds SET price = (SELECT round ((SELECT SUM(price) FROM components WHERE component_id IN (SELECT component_id FROM build_components WHERE build_id = '$input->build_id')),2)) WHERE build_id= '$input->build_id'";
                $queryUpdatePrice = $conn->query($updatePrice);
                if($queryUpdatePrice){
                    //json response body success
                    $data = ["request-type" => "build registration", "result" => "successfull"];
                    $newBuildComponent = new Build_Components($input->build_id,$component->component_id ,$component->quantity);
                } else{
                    //json response body failure
                    $data = ["request-type" => "build registration", "result" => "Failure. Couldn't set the price"];
                }

            }else{
                //json response body failure
                $data = ["request-type" => "build registration", "result" => "Failure. Couldn't regist the component"];
            }
        }
    } else {
        //json response body failure
        $data = ["request-type" => "build registration", "result" => "A build with this name already exists"];
    }
    $conn->close();
}

echo json_encode($data);
