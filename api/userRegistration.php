<?php
/**
 * Created by PhpStorm.
 * Users: R_Rod
 * Date: 21/12/2018
 * Time: 00:16
 */

include_once (__DIR__ . "/model/Users.php");
include_once (__DIR__ . "/model/Email.php");
include_once "db.php";
header("Content-Type: application/json");

$input = json_decode(file_get_contents('php://input'));
$conn = connDB();
if(!empty($input->username) && !empty($input->email) && !empty($input->password)){

    //getting the variables from the input
    $username = $input->username;
    $email = $input->email;
    $password = $input->password;

    $sql = "SELECT * FROM users WHERE username='$username'";
    $check = $conn->query($sql);
    //checking if there is another user with the username the user typed
$data = [];
    if($check->num_rows == 0){
        //password hashing
        $passwordHash = password_hash($password,PASSWORD_BCRYPT);
        //defining the length of the validation code
        $length = 6;

        //creating the validation code wich will be sent by email
        $validation_code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
        $user = new Users('', 1, 1, 1, $email, $username, $passwordHash, 'No description yet.', 0, 0, $validation_code);
        $sql = "INSERT INTO users VALUES('', '1', '1', '1', '$email', '$username', '$passwordHash', 'No description yet.', '0', 'false', '$validation_code')";
        $regist = $conn->query($sql);
        if($regist){
            //json response body success
            $data = ["request-type" => "register", "result" => "successfull"];
            $email = new Email($email,$username,$user->validation_code);
        } else{
            //json response body failure
            $data = ["request-type" => "register", "result" => "failure " . $username . " " . $email . " " . $passwordHash];
        }
        endConnDB($conn);
    } else {
        $data = ["request-type" => "register", "result" => "Username already exists"];
    }
}
echo json_encode($data);