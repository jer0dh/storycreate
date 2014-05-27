<?php
/**
 * Created by PhpStorm.
 * User: jerod
 * Date: 5/23/14
 * Time: 3:01 PM
 */

$state = 0;
session_start();

if(!isset($_SESSION['gState'])) {
    $state = md5(rand());
    $_SESSION['gState'] = $state;
} else {
    $state = $_SESSION['gState'];
}

echo '<div data-state="' . $state .'">hi '. $state . '</div>';


