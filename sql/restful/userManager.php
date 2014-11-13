<?php
/**
 * Created by PhpStorm.
 * User: jerod
 * Date: 5/15/14
 * Time: 11:34 AM
 */
$testing = true;

// echo "In userManager.php <br>";
require_once('UserSql.php');
require_once('../auth/auth.php');

$userSql = new UserSql();
if (!isset($_SERVER['HTTP_Authorization'])){
    header('Content-Type: application/json');
    header('HTTP/1.1 500 Error');
    $result['error'] = "No HTTP_Authorization header in request";
    $json = json_encode($result);
    echo $json;
    return;
} else {
    if($_SERVER['HTTP_Authorization'] === ''){
        header('Content-Type: application/json');
        header('HTTP/1.1 500 Error');
        $result['error'] = "No access Token in request";
        $json = json_encode($result);
        echo $json;
        return;
    } else {
        $scAccessToken = explode(' ', $_SERVER['HTTP_Authorization'])[1];
        if(! validStoryAccessToken($scAccessToken) && ! $testing){
            header('Content-Type: application/json');
            header('HTTP/1.1 500 Error');
            $result['error'] = "Access Token not valid";
            $json = json_encode($result);
            echo $json;
            return;
        } else {
            switch ($_SERVER['REQUEST_METHOD']) {
                case "GET" :
                    // Data is contained in the url
                    // ex. http://localhost/api/user/22
                    //      or
                    //     http://localhost/api/user/jer0dh
                    $requestingUserId = getUserIdFromAccessToken($scAccessToken);
                    $isRequestingUserAdmin = isAdmin($requestingUserId);

                    $arr = explode("api/user/", $_SERVER['REQUEST_URI']);
                    if (isset($arr[1]) && $arr[1] !== "") {
                        $id = $arr[1];
                        $result = $userSql->getUser($id);
                        if(isset($result['success'])){
                            if( !$isRequestingUserAdmin && !($requestingUserId == $id)){
                                unset($result['success'][0]['user_email']);
                            }
                        }
                    } else {
                        // Get all users
                        $result = $userSql->getUsers();
                        if(isset($result['success'])){
                            if(!$isRequestingUserAdmin){
                                for($i=0;$i<=count($result['success']);$i+=1){
                                    unset($result['success'][$i]['user_email']);
                                }
                            }
                        }
                    }
                    break;

                case "POST" :
                    //if data is sent via JSON, it will not be in $_POST
                    //TODO: POST for userManager.php
//		echo " POST request - add new story";
//                    $rawData = file_get_contents("php://input");
//                    $jsonData = json_decode($rawData, true);  //decode the raw json data and return (true) associative array
//                    if ( !isset($jsonData['story'])){
//                        $result['error'] = "Cannot add story: need story";
//                    } else {
//                        $story = $jsonData['story'];
//
//                        $result = $userSql->newStory($story);
//                    }
//                    break;

                case "PUT" :
                    // update user
                    $rawData = file_get_contents("php://input");
                    $jsonData = json_decode($rawData, true);  //decode the raw json data and return (true) associative array
                    if ( !isset($jsonData['userId'])){
                        $result['error'] = "Cannot update user: need userId";
                    } else {
                        $requestingUserId = getUserIdFromAccessToken($scAccessToken);
                        $isRequestingUserAdmin = isAdmin($requestingUserId);
                        if ($isRequestingUserAdmin || ($requestingUserId == $jsonData['userId'])) {
                             $result = $userSql->updateUser($jsonData);
                        } else {
                            $result['error'] = "Cannot update user: not enough permissions";
                        }
                    }

                    break;

                case "DELETE" :
                    // Data is contained in the url
                    // ex. http://localhost/api/story/22
                    //TODO: DELETE for userManager.php
                    $arr = explode("api/story/", $_SERVER['REQUEST_URI']);
                    if (isset($arr[1]) && $arr[1] !== "") {
                        $id = $arr[1];
//			echo "DELETING story with id of " . $id;
                        $result = $storySql->deleteStory($id);
                    } else {
                        $result['error'] = "Unable to Delete Story: need id of story";
                    }
                    break;

                default:
//		echo "In default of switch";
                    header('HTTP/1.1 405 Method Not Allowed');
                    $result['error'] = "Unknown method used to access service.";
            }
            if(isset($result)){
                $json = json_encode($result);
                header('Content-Type: application/json');
                if (isset ($result['error'])) {
                    header('HTTP/1.1 500 Error');
                }else {
                    header('HTTP/1.1 200 OK');
                }
                echo $json;
                return;
            }
        }
    }
}