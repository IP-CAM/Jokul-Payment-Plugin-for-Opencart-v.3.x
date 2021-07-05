<?php

// Heading
$_['heading_title']                = 'Jokul - General Configuration';
                                   
// Text                            
$_['text_payment']                 = 'Payment';
$_['text_success']                 = 'Success: You have modified Jokul configuration!';
$_['text_jokul']        = '<a onclick="window.open(\'https://jokul.doku.com\');"><img src="view/image/payment/doku.png" alt="DOKU" title="DOKU" /><br /></a>';
$_['text_edit']              	   = 'Edit configuration';
$_['text_extension']	    		= 'Extensions';
// Parameter                       
$_['server_params']                = 'Payment Server Parameter';
$_['opencart_params']              = 'Opencart Server Parameter';
$_['paymentchannel_params']        = 'Payment Channel Parameter';
                                   
// Entry                           
$_['entry_server_set']             		= 'Environment:';
$_['entry_mallid']          			= 'Client ID:';
$_['entry_shared']          			= 'Secret Key:';
$_['entry_status']                 		= 'Status:';

// Error
$_['error_permission']             = 'Warning: You do not have permission to modify the Jokul payment configuration!';
$_['error_server_set']             = 'Server target has to be set!';
$_['error_companyid']              = 'Warning: You do not have permission to modify the Jokul payment configuration!';
$_['error_mallid']                 = 'Client ID Required!';
$_['error_shared']                 = 'Secret Key Required!';
$_['error_doku_name']              = 'Payment Method Name Required!';

// URL                             
$myserverpath = explode ( "/", $_SERVER['PHP_SELF'] );
if ( $myserverpath[1] <> 'admin' ) 
{
    $serverpath = '/' . $myserverpath[1];    
    for ($i = 2; $i < count($myserverpath)-2; $i++) {
        $serverpath = $serverpath . '/' .  $myserverpath[$i];
    }

}
else
{
    $serverpath = '';
}

if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443)
{
    $myserverprotocol = "https";
}
else
{
    $myserverprotocol = "http";    
}

if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
    $port = ":$_SERVER[SERVER_PORT]";
} else {
    $port = '';
}

$myservername                = $_SERVER['SERVER_NAME'] .$port. $serverpath;
$_['url_title']              = 'Set this URL to your Jokul Back Office';
$_['url_notify']             = $myserverprotocol.'://'.$myservername.'/index.php?route=extension/payment/jokul/notify';

?>
