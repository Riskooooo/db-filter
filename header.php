<?php
session_start();

require_once 'vendor/autoload.php';

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;

//Configure Dropbox Application
$app = new DropboxApp("APP KEY", "APP SECRET"); // Remplacer par vos identifiants - Replace with your credentials !

//Configure Dropbox service
$dropbox = new Dropbox($app);

//DropboxAuthHelper
$authHelper = $dropbox->getAuthHelper();

//Callback URL
$callbackUrl = "https://test-gd-image.test/"; // Remplacer par votre URL - Replace with your URL
?>