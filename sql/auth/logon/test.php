<?php
/**
 * Created by PhpStorm.
 * User: jerod
 * Date: 6/4/14
 * Time: 2:03 PM
 */

echo password_hash('password', PASSWORD_DEFAULT);

$headers = apache_request_headers();
var_dump($headers);