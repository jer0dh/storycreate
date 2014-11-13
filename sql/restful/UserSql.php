<?php
/**
 * Created by PhpStorm.
 * User: jerod
 * Date: 5/13/14
 * Time: 10:34 AM
 */

class UserSql {

    // database connector
    /**
     * @var PDO
     */
    private $db;

    private function getDbConnection(){
        if(isset($this->db)) { return $this->db; }
        $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->db = $db;
        return $db;
    }
    private function isUserExists($userId){
        $db = $this->getDbConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :userId");
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return ($stmt->rowCount()!= 0);
    }
    private function getPdoType($var){
        $type = gettype($var);
        switch ($type) {
            case "string":
                return PDO::PARAM_STR;
            case "integer":
                return PDO::PARAM_INT;
            case "boolean":
                return PDO::PARAM_INT;
            default:
                return PDO::PARAM_STR;
        }
    }
    public function __construct(){
        require_once('db.php');
        $this->db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }


    public function getUser($user) {
        $db = $this->getDbConnection();
        //determine if $user is user ID or user name.
        $searchTerm = [];
        if (is_numeric($user)){
            $searchTerm['field'] = 'user_id';
        } else {
            $searchTerm['field'] = 'username';
        }
        $stmt = $db->prepare("SELECT user_id, user_name, user_email FROM users WHERE ".$searchTerm['field']." = :value");
        $stmt->bindValue(':value', $user, PDO::PARAM_STR);
        $stmt->execute();
        $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

        $r = array();
        if (count($results) == 0 ) {
            $r['error'] = "User Not Found";
            return $r;
        }
        $r['success'] = $results[0];
        return $r;
    }


    public function getUsers() {
        $db = $this->getDbConnection();

        $stmt = $db->prepare("SELECT user_id, user_name, user_email FROM users");
        $stmt->execute();
        $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

        $r = array();
        if (count($results) == 0 ) {
            $r['error'] = "Users Not Found";
            return $r;
        }
        $r['success'] = $results;
        return $r;
    }

    public function updateUser($user){
        //
        $db = $this->getDbConnection();
        $results = array();
        // make sure id is set
        if (!isset($user['userId'])){
            $results['error'] = "Cannot update User: userId does not exist.";
            return $results;
        }
        // make sure id is not zero and that the story exists
        $userId = $user['userId'];
        if ($userId == 0 || !$this->isUserExists($userId)) {
            $results['error'] = 'Cannot update User: UserId '. $userId.' does not exist or id = 0. Use POST to INSERT a new User';
            return $results;
        }
        //todo: check if email address or user_name exists on records where user_id != $userId

        // update User
        unset($user['userId']);
        unset($user['admin']);  //users can't make themselves admin
        $stmtString = 'UPDATE users SET ';
        $comma = false;
        foreach($user as $key => $value){
            if ($comma) { $stmtString .= ', ';}
            $stmtString .= $key . ' = :' . $key .'1';
            $comma = true;
        }
        $stmtString .= ' WHERE user_id = :user_id';
        // echo $stmtString;

        $stmt = $db->prepare($stmtString);

        foreach($user as $key => $value){
         //   echo 'bindValue(\':' . $key . '1\', ' . $value . ', ' . $this->getPdoType($value);
            $stmt->bindValue(':'.$key.'1', $value, $this->getPdoType($value));
        }
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        try {
        $stmt->execute();
        } catch (Exception $e) {
            $result['error'] = 'In UpdateUser: ' . $e->getMessage();
        }
        ob_start();
        $stmt->debugDumpParams();
        $results['success'] = "User with id: " . $userId . " updated with" . ob_get_flush();
        return $results;
    }


    public function deleteStory($id){
        $db = $this->getDbConnection();
        $results = array();
        // check if story exists
        if( !$this->isStoryExists($id) ){
            $results['error'] = "Cannot Delete Story: Story with id: ". $id ." does not exist";
            return $results;
        }

        // delete story content
        $stmt = $db->prepare("DELETE FROM story_content WHERE story_id = :storyId");
        $stmt->bindValue(':storyId', $id, PDO::PARAM_INT);
        $stmt->execute();
        $storyContentDeleted = $stmt->rowCount();

        // delete story meta
        $stmt = $db->prepare("DELETE FROM stories WHERE story_id = :storyId");
        $stmt->bindValue(':storyId', $id, PDO::PARAM_INT);
        $stmt->execute();
        $storiesDeleted = $stmt->rowCount();

        $results['success'] = $storiesDeleted . " story deleted.  id of " . $id .". " . $storyContentDeleted . " lines of story content deleted.";

        return $results;
    }
}