<?php

require_once('auth.php');

function printUsers(){
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("SELECT * FROM " . TB_USERS);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return build_table($result);
}
function printAuthUsers(){
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("SELECT * FROM " . TB_AUTHUSERS);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return build_table($result);
}

function build_table($array){

    // start table
    $html = '<table>';
    // header row
    $html .= '<tr>';
    foreach($array[0] as $key=>$value){
        $html .= '<th>' . $key . '</th>';
    }
    $html .= '</tr>';
    // data rows
    foreach( $array as $key=>$value){
        $html .= '<tr>';
        foreach($value as $key2=>$value2){
            $html .= '<td>' . $value2 . '</td>';
        }
        $html .= '</tr>';
    }
    // finish table and return it
    $html .= '</table>';
    return $html;

}

echo "testing auth.php <br>";

$testUser = array();
$testUser = [ 'id' => 'test-12345678', 'access-token' => 'test-ac123cess456tok789en', 'name'=>'Test User',
    'timeout'=> 3600];
echo "testuser is <br>";
var_dump( $testUser );

echo "AuthUsers table is <br>";
echo printAuthUsers();

echo "existingAccessToken returns " . (existingAccessToken($testUser['access-token'])? "true":"false") . "<br>";
echo "existingAuthUsersId returns " . (existingAuthUsersId($testUser['id'])? "true":"false") . "<br>";

echo "Users Table <br>";
echo printUsers();

echo "existingId returns " . (existingId($testUser['id'])? "true":"false") . "<br>";

echo "adding Test user to AuthUsers";
$now = new DateTime();
$testUser['timeout'] = intval($now->format('U')) + intval($testUser['timeout']);
try {
    $ScAccessToken = addAuthUser($testUser);
} catch (Exception $e) {
    echo "logon function: Adding AuthUser: " . $e->getMessage();
}
echo printAuthUsers();

echo "existingAccessToken returns " . (existingAccessToken($testUser['access-token'])? "true":"false") . "<br>";
echo "existingAuthUsersId returns " . (existingAuthUsersId($testUser['id'])? "true":"false") . "<br>";

echo "existingId returns " . (existingId($testUser['id'])? "true":"false") . "<br>";

echo "addUser run <br>";
addUser($testUser);

echo printUsers();

echo "getUserName is <br>";

$username = getUserName($testUser['id']);

echo "username returns " . $username . "<br>";

echo "now deleting test user from AuthUsers and Users <br>";

deleteAuthUsersId($testUser['id']);
deleteUsersId($testUser['id']);

echo printAuthUsers();
echo printUsers();


