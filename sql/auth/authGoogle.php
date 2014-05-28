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
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("SELECT * FROM " . TB_USERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', "google-" . $googleId, PDO::PARAM_STR);
    $stmt->execute();
    return ($stmt->rowCount()!== 0);
}

function existingAuthUsersId($googleId) {
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("SELECT * FROM " . TB_AUTHUSERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', "google-" . $googleId, PDO::PARAM_STR);
    $stmt->execute();
    return ($stmt->rowCount()!== 0);
}
function deleteAuthUsersId($googleId){
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("DELETE FROM " . TB_AUTHUSERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', "google-" . $googleId, PDO::PARAM_STR);
    $stmt->execute();
    return;
}
/**
 * Returns true if $accesstoken is already in TB_AUTHUSERS
 *
 * @param $accessToken
 * @return bool
 */
function existingAccessToken($accessToken) {
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("SELECT * FROM " . TB_AUTHUSERS . " WHERE access_token = :accessToken");
    $stmt->bindValue(':accessToken',$accessToken, PDO::PARAM_STR);
    $stmt->execute();
    return ($stmt->rowCount()!== 0);
}

function deleteAccessToken($accessToken){
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("DELETE FROM " . TB_AUTHUSERS . " WHERE access_token = :accessToken");
    $stmt->bindValue(':accessToken', $accessToken, PDO::PARAM_STR);
    $stmt->execute();
    return;
}

function createStoryAccessToken(){
    $loop = true;
    $accessToken = 0;
    while ($loop) {
        $accessToken = md5(rand());
        if(!existingStoryAccessToken($accessToken)){
            $loop = false;
        }
    }
    return $accessToken;
}

function existingStoryAccessToken($accessToken){
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("SELECT * FROM " . TB_AUTHUSERS . " WHERE story_access_token = :accessToken");
    $stmt->bindValue(':accessToken',$accessToken, PDO::PARAM_STR);
    $stmt->execute();
    return ($stmt->rowCount()!== 0);
}

/**
 * Adds record in TB_AUTHUSERS table with access-token, google id (with "google-" preceding), and timout
 *
 * @param $googleMeta
 * @return int|string $storyAccessToken
 */
function addAuthUser($googleMeta) {
    //TODO : add math to add timeout with current time
    global $cA_AuthUsers;
//    removeExpiredAuthUser();
    $now = new DateTime();
    $timeout = intval($now->format('U')) + intval($googleMeta['timeout']);
    $storyAccessToken = createStoryAccessToken();
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("INSERT INTO " . TB_AUTHUSERS . " (access_token, user_id, story_access_token, timeout ) VALUES (:accessToken, :id, :storyAccessToken, :timeout)");
    $stmt->bindValue(':accessToken', $googleMeta['access-token'], PDO::PARAM_STR);
    $stmt->bindValue(':id', "google-" . $googleMeta['id'], PDO::PARAM_STR);
    $stmt->bindValue(':storyAccessToken', $storyAccessToken, PDO::PARAM_STR);
    $stmt->bindValue(':timeout', $timeout, PDO::PARAM_INT);
    $stmt->execute();
    return $storyAccessToken;
}
function generateUniqueUsername($name){
    global  $cA_Users;

    $name = preg_replace('/\s+/', '', $name);  // remove any whitespace
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare('SELECT username FROM ' . TB_USERS . ' WHERE username LIKE :username');
    $stmt->bindValue(':username', $name . "%", PDO::PARAM_STR);
    $stmt->execute();
    $numOfUsers = $stmt->rowCount();
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
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("INSERT INTO " . TB_USERS . " (user_id, name, username ) VALUES (:id, :name, :username)");
    $stmt->bindValue(':id', "google-" . $googleMeta['id'], PDO::PARAM_STR);
    $stmt->bindValue(':name', $googleMeta['name'], PDO::PARAM_STR);
    $username = generateUniqueUsername($googleMeta['name']);
    $stmt->bindValue(':username', $username, PDO::PARAM_INT);
    $stmt->execute();
    return;
}
//TODO: create StoryCreate Access Token for authUsers table to keep from passing around google's access token over the wire



