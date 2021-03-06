<?php
/**
 * Created by PhpStorm.
 * Users: R_Rod
 * Date: 21/12/2018
 * Time: 00:16
 */

include_once (__DIR__ . "/model/Users.php");

include_once (__DIR__ . "/model/PHPMailer/PHPMailer/src/PHPMailer.php");
include_once (__DIR__ . "/model/PHPMailer/PHPMailer/src/Exception.php");
include_once (__DIR__ . "/model/PHPMailer/PHPMailer/src/SMTP.php");
//hope it works
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//server escola
//require '/var/www/html/api/composer/vendor/autoload.php';
require_once (__DIR__ . "/composer/vendor/autoload.php");
include_once "db.php";

header("Content-Type: application/json");

$input = json_decode(file_get_contents('php://input'));

$conn = connDB();

function milliseconds() {
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
}

if(!empty($input->username) && !empty($input->email) && !empty($input->password)){

    //getting the variables from the input
    $username = $input->username;
    $email = $input->email;
    $password = $input->password;

    $time_milis = milliseconds();
    $sql = "SELECT * FROM users WHERE username='$username'";
    $check = $conn->query($sql);
    //checking if there is another user with the username the user typed
    $data = [];
    if($check->num_rows == 0){

        $sql2 = "SELECT * FROM users WHERE email='$email'";
        $checkEmail = $conn->query($sql2);
        if($checkEmail->num_rows == 0){
            //password hashing
            $passwordHash = password_hash($password,PASSWORD_BCRYPT);
            //defining the length of the validation code
            $length = 6;

            //creating the validation code wich will be sent by email i hope
            $validation_code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);

            $user = new Users('', 1, 1, 1, $email, $username, $passwordHash, 'No description yet.', 0, 0, $validation_code,$time_milis);
            $sql3 = "INSERT INTO users (title_id, usertype_id, medal_id, email, username, password, description, reputation, active, validation_code, regist_date) 
VALUES('1', '1', '1', '$email', '$username', '$passwordHash', 'No description yet.', '0', false, '$validation_code','$time_milis')";
            $regist = $conn->query($sql3);
            if($regist){
                try{
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();

                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
//Enable SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
                    $mail->SMTPDebug = 0;
//Set the hostname of the mail server
                    $mail->Host = 'tls://smtp.gmail.com:587';
// use
                    $mail->Username = "hardwarehamlet.mail@gmail.com";
                    $mail->Password = "hardwarehamlet.arm";
                    //$mail->Host = gethostbyname('smtp.gmail.com');
// if your network does not support SMTP over IPv6
//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
                    $mail->Port = 587;
//Set the encryption system to use - ssl (deprecated) or tls
                    $mail->SMTPSecure = 'tls';
//Whether to use SMTP authentication
                    $mail->SMTPAuth = true;
                    $mail->SMTPAutoTLS = false;

                    $mail->setFrom('hardwarehamlet.mail@gmail.com', 'HardwareHamlet');
                    $mail->addCC('hardwarehamlet.mail@gmail.com');
                    $mail->addBCC('hardwarehamlet.mail@gmail.com');
                    $mail->addReplyTo('hardwarehamlet.mail@gmail.com', 'HardwareHamlet');
                    $mail->addAddress($email , $username);
                    $mail->isHTML(true);
                    $mail->WordWrap = 50;
                    $mail->Subject = 'Welcome to HardwareHamlet! Activate your account ' . $username . '!';
                    $mail->msgHTML("
<h2>Bem vindo ao HardwareHamlet!</h2>
<p>É com grande entusiasmo que te damos as boas vindas à nossa comunidade! Para que possas usufruir dos nossos serviços em toda a sua extensão,
confirma o teu email clicando neste link: </p>
<a href='http://194.210.139.1/api/accountActivation.php?username=$username&validation_code=$validation_code'>Click here to validate your account.</a>
<p>Vemo-nos lá!</p>
<h4>A Equipa do HardwareHamlet</h4>");
                    $mail->AltBody = 'Welcome to Hardware Hamlet! In order to officialy join our hamlet, 
        you need to confirm your account by entering this code when requested: ' . $validation_code . ". Cheers! The HardwareHamlet Team.";
                    if($mail->send()){
                        $data = ["request_type" => "register", "result" => "successful and email sent"];
                    }else $data = ["request_type" => "register", "result" => "successful but email not sent"];
                }
                catch(Exception $e){
                    $data = ["request_type" => "register", "result" => "something went bad. ". $mail->ErrorInfo];;
                }
            } else{
                //json response body failure
                $data = ["request_type" => "register", "result" => "failure " . $username . " " . $email . " " . $passwordHash . " " . $validation_code];
            }
        }else{
            $data = ["request_type" => "register", "result" => "email already exists"];
        }
    } else {
        $data = ["request_type" => "register", "result" => "username already exists"];
    }
}
$conn->close();
echo json_encode($data);

