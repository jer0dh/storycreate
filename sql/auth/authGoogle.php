<?php
/**
 * Created by PhpStorm.
 * User: jerod
 * Date: 5/22/14
 * Time: 9:52 AM
 */

<?php
  require_once 'Google/Client.php';
  require_once 'Google/Service/Books.php';
  $client = new Google_Client();
  $client->setApplicationName("Client_Library_Examples");
  $client->setDeveloperKey("YOUR_APP_KEY");