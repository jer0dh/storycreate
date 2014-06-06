<?php

/**  Using code from:
 * A simple, clean and secure PHP Login Script / MINIMAL VERSION
 * For more versions (one-file, advanced, framework-like) visit http://www.php-login.net
*/

if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    // if you are using PHP 5.3 or PHP 5.4 you have to include the password_api_compatibility_library.php
    // (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
    require_once("phpminimal/libraries/password_compatibility_library.php");
}

// include the configs / constants for the database connection
require_once("phpminimal/config/db.php");

// load the login class
require_once("phpminimal/classes/Login.php");

// jhTech code - added since Angular post won't easily end up in $_POST
if(stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $_POST = json_decode(file_get_contents("php://input"), true);
}
// jhTech code ends


$login = new Login();

   // jhTech code starts here


$result = array();

if (isset($login->messages[0]) && $login->messages[0] == "success") {

    // add code to talk to authentication tables, create access-token, etc
    require_once('../auth.php');

    // get userID from user_name or user_email
    // todo: possible check to make sure @ is not used in user_name to make sure unique values
    $user_name = $_POST['user_name'];
    $user_id = getUserId($user_name);
    $ary = [ 'id'=> $user_id ];
    $result = auth_logon($ary);

    if (isset($result['scAccessToken'])) {
        header('Content-Type: application/json');
        $result['success'] = implode('\n', $login->messages);
        echo json_encode($result);
    } else {
        returnError();
    }

   } else {
    returnError();
}


function returnError(){
   global $login, $result;
   header('HTTP/1.1 500 Error');
   header('Content-Type: application/json; charset=UTF-8');
	$arr = array('error'=> implode("\n",$login->errors));
    if(isset($result['error'])){
        $arr['error'] .= ': ' . $result['error'];
    }
	echo json_encode($arr);

}