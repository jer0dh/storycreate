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
        $stmt = $db->prepare("SELECT user_id as id, username as name, email FROM users WHERE ".$searchTerm['field']." = :value");
        $stmt->bindValue(':value', $user, PDO::PARAM_STR);
        $stmt->execute();
        $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

        $r = array();
        if (count($results) == 0 ) {
            $r['error'] = "User Not Found";
            return $r;
        }
        $r['success'] = $results;
        return $r;
    }


    public function getUsers() {
        $db = $this->getDbConnection();

        $stmt = $db->prepare("SELECT user_id as id, username as name, email FROM users");
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

    /**
     * returns the story saved into database. The returned id of the story will be the value the database gave it upon insertion.
     *
     *
     * NOTE: On Date conversions: When sending dates from JavaScript:
     * h = new Date(); // h is a date with value: Wed May 14 2014 10:03:06 GMT-0500 (Central Daylight Time)
     * i = JSON.stringify(h);  // i is now a quoted string with value: ""2014-05-14T15:03:06.823Z""
     * j = JSON.parse(i);  // j is now a string with value: "2014-05-14T15:03:06.823Z"
     *
     * j is the format we need the date to be to insert into SQL database.
     *
     * @param $story
     *    // an associative array
     * @return array
     *   NOTE: date will be in the format of a string like "2014-05-14T15:03:06.823Z".  If a JavaScript date is needed, run new Date("2014-05-14T15:03:06.823Z");
     */
    public function newStory($story) {
        $results = array();
        // if id is already set, check if existing story
        if (isset($story['id']) && $story['id'] > 0 && $this->isStoryExists($story['id'])) {
           $results['error'] = 'Cannot add new Story: Story already exists. Use PUT to update a story';
           return $results;
        }

        $db = $this->getDbConnection();
        $stmt = $db->prepare("INSERT INTO stories SET story_title = :title, story_description = :description, date_created = STR_TO_DATE(:dateCreated, '%Y-%m-%dT%H:%i:%s'), date_modified = STR_TO_DATE(:dateModified, '%Y-%m-%dT%H:%i:%s'), is_public = :isPublic");
        $stmt->bindValue(':title', $story['title'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $story['description'], PDO::PARAM_STR);
        $stmt->bindValue(':dateCreated', $story['dateCreated'], PDO::PARAM_STR);
        $stmt->bindValue(':dateModified', $story['dateModified'], PDO::PARAM_STR);
        $stmt->bindValue(':isPublic', $story['isPublic'], PDO::PARAM_BOOL);
        $stmt->execute();
        $last = $db->lastInsertId();
        $story['id'] = $last;

        // update the story content.  Delete first then re add
        $storyContent = isset($story['storyContent']) ? $story['storyContent'] : [];
        if(count($storyContent) > 0) {
            // delete existing content
            $stmt = $db->prepare("DELETE FROM story_content WHERE story_id = :storyId");
            $stmt->bindValue(':storyId', $story['id'], PDO::PARAM_INT);
            $stmt->execute();

            // add Story Content to SQL table
            for($i = 0; $i < count($storyContent); $i++) {
                $stmt = $db->prepare("INSERT INTO  story_content (story_id, user_id, content, cdate, corder) VALUES(:storyId, :userId, :content, :date, :corder)");
                $stmt->bindValue(':storyId', $story['id'], PDO::PARAM_INT);
                $stmt->bindValue(':userId', $storyContent[$i]['userId'], PDO::PARAM_INT);
                $stmt->bindValue(':content', $storyContent[$i]['content'], PDO::PARAM_STR);
                $stmt->bindValue(':date', $storyContent[$i]['date'], PDO::PARAM_STR);
                $stmt->bindValue(':corder', $i, PDO::PARAM_INT);
                $stmt->execute();
            }


        }

        $results['story'] = $story;
        $r = array();
        $r['success'] = $results;
        return $r;
    }

    /**
     * updates the database with the $story associative array.
     *
     * if $story['storyContent'] does not exist, then only the story meta is updated. If $story['storyContent'] does exist, then previous storyContent of story is deleted from database and new storyContent is added.
     *
     * @param $story
     * @return array
     */
    public function updateStory($story){
        //
        $db = $this->getDbConnection();
        $results = array();
        // make sure id is set
        if (!isset($story['id'])){
            $results['error'] = "Cannot update Story: id does not exist.";
            return $results;
        }
        // make sure id is not zero and that the story exists
        if ($story['id'] == 0 || !$this->isStoryExists($story['id'])) {
            $results['error'] = 'Cannot update Story: Story does not exist or id = 0. Use POST to INSERT a story';
            return $results;
        }

        // update Story Meta
        $stmt = $db->prepare("UPDATE stories SET story_title = :title, story_description = :description, date_created = STR_TO_DATE(:dateCreated, '%Y-%m-%dT%H:%i:%s'), date_modified = STR_TO_DATE(:dateModified, '%Y-%m-%dT%H:%i:%s'), is_public = :isPublic WHERE story_id = :storyId");
        $stmt->bindValue(':storyId', $story['id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $story['title'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $story['description'], PDO::PARAM_STR);
        $stmt->bindValue(':dateCreated', $story['dateCreated'], PDO::PARAM_STR);
        $stmt->bindValue(':dateModified', $story['dateModified'], PDO::PARAM_STR);
        $stmt->bindValue(':isPublic', $story['isPublic'], PDO::PARAM_BOOL);
        $stmt->execute();

        //Update Story Content if $story['storyContent'] exists
        if(isset ($story['storyContent'])){
            // delete existing story content
            $stmt = $db->prepare("DELETE FROM story_content WHERE story_id = :storyId");
            $stmt->bindValue(':storyId', $story['id'], PDO::PARAM_INT);
            $stmt->execute();

            $storyContent = $story['storyContent'];

            // Insert story content, using $i for the corder field
            for($i=0; $i < count($storyContent); $i++) {
                $stmt = $db->prepare("INSERT INTO  story_content (story_id, user_id, content, cdate, corder) VALUES(:storyId, :userId, :content, STR_TO_DATE(:date, '%Y-%m-%dT%H:%i:%s'), :corder)");
                $stmt->bindValue(':storyId', $story['id'], PDO::PARAM_INT);
                $stmt->bindValue(':userId', $storyContent[$i]['userId'], PDO::PARAM_INT);
                $stmt->bindValue(':content', $storyContent[$i]['content'], PDO::PARAM_STR);
                $stmt->bindValue(':date', $storyContent[$i]['date'], PDO::PARAM_STR);
                $stmt->bindValue(':corder', $i, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        $results['success'] = "Story with id: " . $story['id'] . " updated.";
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