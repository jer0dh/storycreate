<?php

 // echo "In storyManager.php <br>";
require_once('StorySql.php');
$storySql = new StorySql();
//TODO : add checkAuth

switch ($_SERVER['REQUEST_METHOD']) {
	case "GET" :
	// Data is contained in the url
	// ex. http://localhost/api/story/22
	
		$arr = explode("api/story/", $_SERVER['REQUEST_URI']);
		if (isset($arr[1]) && $arr[1] !== "") {
			$id = $arr[1];
//			echo "Getting story with id of " . $id;
			// Get story with id of $id
            $result = $storySql->getStory($id);
		} else {
//			echo "Getting all stories";
			// Get all stories
            $result = $storySql->getStories();
		}
	break;
	
	case "POST" :
		//if data is sent via JSON, it will not be in $_POST
		
//		echo " POST request - add new story";
        $rawData = file_get_contents("php://input");
        $jsonData = json_decode($rawData, true);  //decode the raw json data and return (true) associative array
        if ( !isset($jsonData['story'])){
            $result['error'] = "Cannot add story: need story";
        } else {
            $story = $jsonData['story'];

            $result = $storySql->newStory($story);
        }
	break;
	
	case "PUT" :
//		echo " PUT request - update story";
        $rawData = file_get_contents("php://input");
        $jsonData = json_decode($rawData, true);  //decode the raw json data and return (true) associative array
        if ( !isset($jsonData['story'])){
            $result['error'] = "Cannot add story: need story";
        } else {
            $story = $jsonData['story'];
            $result = $storySql->updateStory($story);
        }
		
	break;
	
	case "DELETE" :
		// Data is contained in the url
	    // ex. http://localhost/api/story/22
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
    }
    echo $json;
    return;
}