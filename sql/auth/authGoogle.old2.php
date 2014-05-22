<?php


define("DB_USERS_HOST", "localhost");
define("DB_AUTHUSERS_HOST", "localhost");
define("DB_USERS", "story2");
define("TB_USERS", "users");
define("DB_AUTHUSERS", "auth");
define("TB_AUTHUSERS", "authusers");
define("DB_USERS_USER", "root");
define("DB_USERS_PASS", "");
define("DB_AUTHUSERS_USER", "root");
define("DB_AUTHUSERS_PASS", "");

$cA_Users = array('host'=>DB_USERS_HOST, 'database'=> DB_USERS, 'user'=>DB_USERS_USER, 'pass'=>DB_USERS_PASS);
$cA_AuthUsers = array('host'=>DB_AUTHUSERS_HOST, 'database'=> DB_AUTHUSERS, 'user'=>DB_AUTHUSERS_USER, 'pass'=>DB_AUTHUSERS_PASS);
// result is stored here.
$result = array();


/**
 * with given connection Array containing host, database, user, and password returns a PDO connection
 *
 * @param $cA
 * @return PDO
 */
function getDbConnection($cA) {
    $db = new PDO('mysql:host='.$cA['host'].';dbname='.$cA['database'].';charset=utf8', $cA['user'], $cA['pass']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $db;
}


/**
 * Checks if google id is already in users table..aka..google user has logged in before
 * google id's are stored in user database with "google-" preceding it.
 *
 * @param $googleId
 * @return bool
 */
function existingId($googleId) {
    global $result, $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("SELECT * FROM " . TB_USERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', "google-" . $googleId, PDO::PARAM_STR);
    $stmt->execute();
    $exists = ($stmt->rowCount()!== 0) ? "true" : "false";
    $result['existingID'] = "returned " . $exists;
    return ($stmt->rowCount()!== 0);
}


/**
 * Returns true if $accesstoken is already in TB_AUTHUSERS
 *
 * @param $accessToken
 * @return bool
 */
function existingAccessToken($accessToken) {
    global $result, $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("SELECT * FROM " . TB_AUTHUSERS . " WHERE access_token = :accessToken");
    $stmt->bindValue(':accessToken', $accessToken, PDO::PARAM_STR);
    $stmt->execute();
    $exists = ($stmt->rowCount()!== 0) ? "true" : "false";
    $result['existingAccessToken'] = "returned " . $exists;
    return ($stmt->rowCount()!== 0);
}

function deleteAccessToken($accessToken){
    global $result, $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("DELETE FROM " . TB_AUTHUSERS . " WHERE access_token = :accessToken");
    $stmt->bindValue(':accessToken', $accessToken, PDO::PARAM_STR);
    $stmt->execute();
    $result['deleteAccessToken'] = "Deleted " . $stmt->rowCount() . " rows.";
    return;
}

/**
 * Adds record in TB_AUTHUSERS table with access-token, google id (with "google-" preceding), and timout
 *
 * @param $googleMeta
 */
function addAuthUser($googleMeta) {
    //TODO : add math to add timeout with current time
    global $result, $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("INSERT INTO " . TB_AUTHUSERS . " (access_token, user_id, timeout ) VALUES (:accessToken, :id, :timeout)");
    $stmt->bindValue(':accessToken', $googleMeta['access-token'], PDO::PARAM_STR);
    $stmt->bindValue(':id', "google-" . $googleMeta['id'], PDO::PARAM_STR);
    $stmt->bindValue(':timeout', $googleMeta['timeout'], PDO::PARAM_INT);
    $stmt->execute();
    $result['addAuthUser'] = "addAuthUser: inserted " . $stmt->rowCount();
    return;
}
function generateUniqueUsername($name){
    global $result, $cA_Users;

    $name = preg_replace('/\s+/', '', $name);  // remove any whitespace
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare('SELECT username FROM ' . TB_USERS . ' WHERE username LIKE :username');
    $stmt->bindValue(':username', $name . "%", PDO::PARAM_STR);
    $stmt->execute();
    $numOfUsers = $stmt->rowCount();
    $result['generateUniqueUsername'] = "found LIKE rowcount " . $numOfUsers;
    if ($numOfUsers == 0){
        return $name;
    }
    $count = 1;
    $suffux = $numOfUsers;
    $uName = "";
    while($count !== 0){
        $uName = $name . $suffux;
        $stmt = $db->prepare('SELECT username FROM ' .TB_USERS . ' WHERE username = :username');
        $stmt->bindValue(':username', $uName, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->rowCount();
        ++$suffux;
        }


    return $uName;
    }

function addUser($googleMeta){
    //TODO : test addUser function
    global $result, $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("INSERT INTO " . TB_USERS . " (user_id, name, username ) VALUES (:id, :name, :username)");
    $stmt->bindValue(':id', "google-" . $googleMeta['id'], PDO::PARAM_STR);
    $stmt->bindValue(':name', $googleMeta['name'], PDO::PARAM_STR);
    $username = generateUniqueUsername($googleMeta['name']);
    $stmt->bindValue(':username', $username, PDO::PARAM_INT);
    $stmt->execute();
    $result['addAuthUser'] = "addAuthUser: inserted " . $stmt->rowCount();
    return;
}

function getGoogleMeta($accessToken){
    //TODO : getGoogleMeta function
    $meta = array('access-token'=>$accessToken, 'id'=> '44454831', 'timeout' => 3600, 'name' => "John Doe");
    global $result;
    if(!function_exists("curl_init")){
        $result['curlError'] = "curl_init not found";
    }
    $url = "https://www.googleapis.com/plus/v1/people/me";
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_HEADER, 'Authorization: Bearer ' . $accessToken);
    curl_setopt($ch, CURLOPT_URL, $url  );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $res = json_decode($res, true);
    var_dump($res);
    $meta['id'] = $res['id'];
    $meta['name'] = $res['displayName'];
    $url = "https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=" . $accessToken;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url  );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $res = json_decode($res, true);
    $meta['timeout'] = $res['expires_in'];
    return $meta;
}

/**
 * Takes an $accessToken and curl's googleapis.com to see if it is valid
 *
 * @param $accessToken
 * @return bool
 */
function validAccessToken($accessToken) {
    global $result;
    if(!function_exists("curl_init")){
        $result['curlError'] = "curl_init not found";
    }
    $url = "https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=" . $accessToken;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url  );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $res = json_decode($res, true);
    $valid = isset($res['issued_to']) ? "true" : "false";
    $result['validAccessToken'] = "Returned " . $valid;
    return isset ($res['issued_to']);
}



try {
    /* If not POST, then return error*/
    if ($_SERVER['REQUEST_METHOD'] != "POST"){
        throw new Exception("POST request expected");
    }

    /* Get JSON data */
    $rawData = file_get_contents("php://input");
    $jsonData = json_decode($rawData, true); //decode the raw json data and return (true) associative array

    // make sure access-token is in json data
    if(!isset($jsonData['access-token']) ){
        throw new Exception("Need access-token");
    }
    // check google agrees this is a valid access-token
    if( !validAccessToken($jsonData['access-token'])) {
        throw new Exception("Google reports not valid access-token");
    }

    // $googleMeta should contain access token, google id, user name, and timeout
    $googleMeta = getGoogleMeta($jsonData['access-token']);

    // check to make sure access-token is not in authusers table, delete if is
    if (existingAccessToken($googleMeta['access-token'])){
        deleteAccessToken($googleMeta['access-token']);
    }
    addAuthUser($googleMeta);

    // check to see if google id is in users table
    if (! existingId($googleMeta['id'])){
        // add user
        addUser($googleMeta);
        if (! existingId($googleMeta['id'])){
            throw new Exception("Unable to add user to " . DB_USERS . " in table " . TB_USERS. ".");
        }
    }

    // all should be good here so return success

    $result['success'] = "Authorized by Google";
    $result['access-token'] = $googleMeta['access-token'];


} catch (Exception $e) {
    $result['error'] = $e->getMessage();
    $json = json_encode($result);
    header('Content-Type: application/json');
    header('HTTP/1.1 500 Error');
    echo $json;
    return;
}
$json = json_encode($result);
header('Content-Type: application/json');
echo $json;
return;

/* Testing

$ curl -X POST -H "application/json" -d '{"story":"help"}' http://localhost/story2/sql/auth/authGoogle.php

{"error":"Need access-token"}


$ curl -X POST -H "application/json" -d '{"access-token":"help"}' http://localhost/story2/sql/auth/authGoogle.php

{"validAccessToken":"Returned false","error":"Google reports not valid access-token"}

With valid access-token that had been inserted earlier but addUsers still not defined
$ curl -X POST -H "application/json" -d '{"access-token":"ya29.HQBqTf8FUbjLkxsAAAC5SPgM_LOOTh1MUHNkDCTITqeAVCCqG5OBSPtg0knxxQ"}' http://localhost/story2/sql/auth/authGoogle.php

{"validAccessToken":"Returned true",
"existingAccessToken":"returned true",
"deleteAccessToken":"Deleted 1 rows.",
"addAuthUser":"addAuthUser: inserted 1",
"existingID":"returned false",
"error":"Unable to add user to story2 in table users."}

with valid access-token and addUser defined
$ curl -X POST -H "application/json" -d '{"access-token":"ya29.HQBqTf8FUbjLkxsAAAC5SPgM_LOOTh1MUHNkDCTITqeAVCCqG5OBSPtg0knxxQ"}' http://localhost/story2/sql/auth/authGoogle.php

{"validAccessToken":"Returned true","existingAccessToken":"returned true","deleteAccessToken":"Deleted 1 rows.","addAuthUser":"addAuthUser: inserted 1","existingID":"returned true","success":"Authorized by Google","access-token":"ya29.HQBqTf8FUbjLkxsAAAC5SPgM_LOOTh1MUHNkDCTITqeAVCCqG5OBSPtg0knxxQ"}

getting error message with validating access token from google saying I've reached my daily limit for unauthenticated requests.
Researched but did not find much. Confirmed google console was correctly configured.

Found some better documentation on Google plus login with a flow chart and specific code.  It uses a one-time code from the client so that the
access-token is not transfered from client to server.  Server obtains it directly from google using this one-time code.  More secure


*/

