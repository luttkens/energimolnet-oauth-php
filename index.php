<?php
/**
 * Simple example of how to implement Energimolnet and OAuth 2.0
 *
 * Check www.energimolnet.se for more information
 *
 * @author      Magnus Lüttkens <luttkens@energimolnet.se>
 * @version     1.0-dev
 */


require_once('Client.php');
require('GrantType/IGrantType.php');
require('GrantType/AuthorizationCode.php');
require('GrantType/RefreshToken.php');

use OAuth2\Client;

const CLIENT_ID     = 'demoapp';
const CLIENT_SECRET = 'demopass';

/*
 * For this script the redirect_uri is the same as the url to this file.
 */
const REDIRECT_URI = '<For this script, se the URL to this file>';
const API_URL = 'https://app.energimolnet.se/api/1.1/';
const AUTHORIZATION_ENDPOINT = 'https://app.energimolnet.se/oauth2/authorize';
const TOKEN_ENDPOINT = 'https://app.energimolnet.se/oauth2/grant';

session_start();

if (!isset($_SESSION['access_token'])) $_SESSION['access_token'] = "";
if (!isset($_SESSION['refresh_token'])) $_SESSION['refresh_token'] = "";

$client = new Client(CLIENT_ID, CLIENT_SECRET);

if (isset($_GET['forget'])){
    $_SESSION['access_token'] = "";
    $_SESSION['refresh_token'] = "";
}elseif (isset($_GET['authorize'])){
    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, REDIRECT_URI);
    header('Location: ' . $auth_url);
    die("Redirecting...");
}elseif (isset($_GET['code']) && empty($_SESSION['access_token'])){
    $params = array('code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI);
    $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code', $params);

    $_SESSION['access_token'] = $response['result']['access_token'];
    $_SESSION['refresh_token'] = $response['result']['refresh_token'];

    $client->setAccessToken($_SESSION['access_token']);
    $response = $client->fetch(API_URL."users/me");
    $result = $response['result'];

}elseif (isset($_GET['error'])){
    echo "<p>Seems like you didn't grant us access.</p>";
}else{
    $client->setAccessToken($_SESSION['access_token']);
    $response = $client->fetch(API_URL."users/me");
    $result = $response['result'];
    if ($response['code']==401 && $response['result']['error'] == 'invalid_grant'){
        $params = array('refresh_token' => $_SESSION['refresh_token'], 'client_id' => CLIENT_ID, 'client_secret' => CLIENT_SECRET, 'grant_type' => 'refresh_token');
        $response = $client->getAccessToken(TOKEN_ENDPOINT, 'refresh_token', $params);

        // Re-initiate the request if new access_token was received
        if($response['code']==200){
            $_SESSION['access_token'] = $response['result']['access_token'];
            $client->setAccessToken($_SESSION['access_token']);
            $response = $client->fetch(API_URL."users/me");
            $result = $response['result'];
        }else
            unset($result);
    }
}

if (isset($result)){
    echo "Now we can show your data {$result['username']}!<p>";
    echo "If you want to be forgoten <a href='?forget' >click here</a>";
}else
    echo "You must grant access. <a href='?authorize' >Click here to grant access</a>";


