<?php

define("TIMEOUT", 3600);
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
 * Checks if id is already in users table..aka..user has logged in before
 * users logging in from third-party vendors have id's with a prefix.  Ex. "google-" preceding it.
 *
 * @param $id
 * @return bool
 */
function existingId($id) {
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("SELECT * FROM " . TB_USERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    return ($stmt->rowCount()!== 0);
}

function existingAuthUsersId($id) {
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("SELECT * FROM " . TB_AUTHUSERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    return ($stmt->rowCount()!== 0);
}
function deleteAuthUsersId($id){
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("DELETE FROM " . TB_AUTHUSERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);
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
function addAuthUser($ary) {
    global $cA_AuthUsers;
//    removeExpiredAuthUser();
    $storyAccessToken = createStoryAccessToken();
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("INSERT INTO " . TB_AUTHUSERS . " (access_token, user_id, story_access_token, timeout ) VALUES (:accessToken, :id, :storyAccessToken, :timeout)");
    $stmt->bindValue(':accessToken', $ary['access-token'], PDO::PARAM_STR);
    $stmt->bindValue(':id', $ary['id'], PDO::PARAM_STR);
    $stmt->bindValue(':storyAccessToken', $storyAccessToken, PDO::PARAM_STR);
    $stmt->bindValue(':timeout', $ary['timeout'], PDO::PARAM_INT);
    $stmt->execute();
    return $storyAccessToken;
}
function generateUniqueUsername($name){
    global  $cA_Users;

    $name = preg_replace('/\s+/', '', $name);  // remove any whitespace
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare('SELECT user_name FROM ' . TB_USERS . ' WHERE user_name LIKE :username');
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
        $stmt = $db->prepare('SELECT user_name FROM ' .TB_USERS . ' WHERE user_name = :username');
        $stmt->bindValue(':username', $uName, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->rowCount();
        ++$suffux;
    }
    return $uName;
}

function addUser($ary){
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("INSERT INTO " . TB_USERS . " (user_id, name, user_name ) VALUES (:id, :name, :username)");
    $stmt->bindValue(':id', $ary['id'], PDO::PARAM_STR);
    $stmt->bindValue(':name', $ary['name'], PDO::PARAM_STR);
    $username = generateUniqueUsername($ary['name']);
    $stmt->bindValue(':username', $username, PDO::PARAM_INT);
    $stmt->execute();
    return;
}
function getUserName($id){
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("SELECT user_name FROM " . TB_USERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result[0]['user_name'];
}

function getUserId($name){
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("SELECT user_id FROM " . TB_USERS . " WHERE user_name = :name OR user_email = :name2");
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':name2', $name, PDO::PARAM_STR);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result[0]['user_id'];
}

function deleteUsersId($id){
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("DELETE FROM " . TB_USERS . " WHERE user_id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    return;
}

/**
 * Takes associative array with user_id and optionally access-token (from third party), timeout (length in seconds),
 * and name (Full name of user if logging in from third-party.  Returns an array with story create access token, user_id,
 * and user_name.
 *
 * @param $ary
 * @return array|string
 */
function auth_logon($ary){

    $result = array();
    if (! isset($ary['id'])) {
        return $result['error'] = "Logon function: id not set";
    }
    // check to make sure third party access-token is not in authusers table, delete if is
    // if no third-party access token, then default it to 'local'
    if (isset ($ary['access-token'])) {
        if (existingAccessToken($ary['access-token'])){
            deleteAccessToken($ary['access-token']);
        }
    } else {
        $ary['access-token'] = 'local';
    }
    // check to make sure existing id not in Authusers table, delete if is
    if(existingAuthUsersId($ary['id'])){
        deleteAuthUsersId($ary['id']);
    }

    // create timeout
    if (! isset ($ary['timeout'])) {
        $ary['timeout'] = TIMEOUT;
    }
    $nw = new DateTime();
    $ary['timeout'] = intval($nw->format('U')) + intval($ary['timeout']);

    // Add user to AuthUsers
    try {
        $scAccessToken = addAuthUser($ary);
    } catch (Exception $e) {
        return $result['error'] = "Logon function: Adding AuthUser: " . $e->getMessage();
    }

    try {
        // Add to storyCreate user database (logon.php users will already be there, but maybe not third-party logons
        if (! existingId($ary['id'])){
            // add user
            if (! isset ($ary['name'])) {
                $ary['name'] = "Story User";
            }
            addUser($ary);
            // test that user really created
            if (! existingId($ary['id'])) {
                throw new Exception("addUser returned but user not created.");
            }
        }

    } catch (Exception $e) {
        return $result['error'] = "Logon function: Adding User: " . $e->getMessage();
    }

    $username = getUserName($ary['id']);

    $result['scAccessToken'] = $scAccessToken;
    $result['id'] = $ary['id'];
    $result['user_name'] = $username;

    return $result;
}

function validTimeout($timeout){
    $nw = new DateTime();
    if(intval($nw->format('U')) <= $timeout){
        return true;
    } else {
        return false;
    }
}

function validStoryAccessToken($accessToken){
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("SELECT * FROM " . TB_AUTHUSERS . " WHERE story_access_token = :accessToken");
    $stmt->bindValue(':accessToken',$accessToken, PDO::PARAM_STR);
    $stmt->execute();
    if (! ($stmt->rowCount()!== 0)) {
        return false;
    } else {
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return validTimeout($result[0]['timeout']);
    }
}

function getUserIdFromAccessToken($accessToken){
    global $cA_AuthUsers;
    $db = getDbConnection($cA_AuthUsers);
    $stmt = $db->prepare("SELECT * FROM " . TB_AUTHUSERS . " WHERE story_access_token = :accessToken");
    $stmt->bindValue(':accessToken',$accessToken, PDO::PARAM_STR);
    $stmt->execute();
    if (! ($stmt->rowCount()!== 0)) {
        return 0;
    } else {
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result[0]['user_id'];
    }
}

function isAdmin($userId) {
    global $cA_Users;
    $db = getDbConnection($cA_Users);
    $stmt = $db->prepare("SELECT admin FROM " . TB_USERS . " WHERE user_id = :userId");
    $stmt->bindValue(':userId', $userId, PDO::PARAM_STR);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result[0]['admin'] ? true : false;
}