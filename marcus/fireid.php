<?php
// fireid.php - save firebase idtoken
// Requests:
//      id : id token
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2018-08-07
// Copyright 2018 Toronto MicroElectronics Inc.

$noclosesession = true ;
require 'session.php' ;

header("Content-Type: application/json");

if( !empty($_SESSION['logon']) ){
	$_SESSION['firetoken'] = $_REQUEST['id'] ;
	$resp['res']=1 ;
}

if( !empty($resp) ) 
	echo json_encode( $resp ) ;

?>