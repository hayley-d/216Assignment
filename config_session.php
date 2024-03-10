<?php
//set to true
//makes it more secure and helps prevent session fixation attacks and session hijacking attacks
ini_set('session.use_only_cookies',1);
ini_set('session.use_strict_mode',1);

session_set_cookie_params([
    'lifetime'=>1800,
    'domain'=>'localhost',
    'path'=>'/',
    'secure'=>true,
    'httponly'=>true
]);

session_start();

if(isset($_SESSION['user_id']))
{
    //regenerate cookie id to prevent
    if(!isset($_SESSION['last_regeneration'])){
        regenerate_session_id_login();
    }
    else{
        //update the session id after 30 min
        $interval = 60 * 30;
        if(time() - $_SESSION['last_regeneration'] >= $interval){
            regenerate_session_id_login();
        }
    }
}
else{
    //regenerate cookie id to prevent
    if(!isset($_SESSION['last_regeneration'])){
        regenerate_session_id();
    }
    else{
        //update the session id after 30 min
        $interval = 60 * 30;
        if(time() - $_SESSION['last_regeneration'] >= $interval){
            regenerate_session_id();
        }
    }
}


function regenerate_session_id_login(){

    session_regenerate_id(true);
    $user_id = $_SESSION['user_id'];
    $newSessionId = session_create_id();
    $sessionId = $newSessionId . '_' . $user_id;
    session_id($sessionId);
    $_SESSION['last_regeneration']=time();
}

function regenerate_session_id(){
    //regenerate the session id to make it more secure
    session_regenerate_id(true);
    //set the last regen to the current time so we know when to regenerate the session id
    $_SESSION['last_regeneration']=time();
}