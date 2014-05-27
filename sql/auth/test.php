<?php
/**
 * Created by PhpStorm.
 * User: jerod
 * Date: 5/27/14
 * Time: 1:28 PM
 */

require_once('authGoogle.php');

$googleMeta = array(
    'access-token'      =>  "test-123456",
    'id'                =>  "1234567890",
    'timeout'           =>  "3600",
    'name'              =>  "John Doe"
);

$googleMeta2 = array(
    'access-token'      =>  "test-1234sds56",
    'id'                =>  "1234567890",
    'timeout'           =>  "3600",
    'name'              =>  "John Doe"
);
echo "Adding authUser John Doe <br>";

//addAuthUser($googleMeta);

echo "Testing if authUser (Id) John Doe Exists<br>";

if(existingAuthUsersId($googleMeta['id'])){
    echo "Existing id  of John Doe found in authusers<br>";
    echo "Deleting id of John Doe<br>";
    deleteAuthUsersId($googleMeta['id']);
    echo "Testing if delete worked:<br>";
    if(existingAuthUsersId($googleMeta['id'])){
        echo "Existing id  of John Doe found in authusers<br>";
    } else {
        echo "<em>ERROR:</em>John Doe not found in authuser table<br>";
    }
} else {
    echo "<em>ERROR:</em>John Doe not found in authuser table<br>";
}

if(existingAuthUsersId($googleMeta['id'])){
    echo "Existing id  of John Doe found in authusers<br>";
} else {
    echo "<em>ERROR:</em>John Doe not found in authuser table<br>";
}
//echo "checking if user id found in user table<br>";
//if(existingId($googleMeta['id'])){
//    echo "User ID found in User table<br>";
//
//
//} else {
//    echo "User ID not found in User table<br>";
//}
//
//echo "Adding user to User table<br>";
//addUser($googleMeta);
//
//if(existingId($googleMeta['id'])){
//    echo "User ID found in User table<br>";
//} else {
//    echo "<em>ERROR:</em> User ID not found in User table<br>";
//}
//
//
//echo " Now deleting John Doe from authUsers<br>";
//deleteAccessToken($googleMeta['access-token']);
//
//echo " now testing if John Doe really deleted<br>";
//
//if(existingAccessToken($googleMeta['access-token'])){
//    echo "Existing Access token of John Doe found<br>";
//} else {
//    echo "John Doe not found in authuser table<br>";
//}

echo "DONE\n";