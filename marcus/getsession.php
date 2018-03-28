<?php
// getsession.php - Retrieve session timeout
// Requests:
//      none
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-08-15
// Copyright 2017 Toronto MicroElectronics Inc.

$noupdatextime = true ;
require 'session.php' ;
header("Content-Type: application/json");
if( $session_logon ) {
	$resp['res'] = 1 ;	
	$resp['timeout'] = $_SESSION['xtime'] + $_SESSION['timeout'] - $session_time ;
	$resp['user'] = $_SESSION['user'] ;
	$resp['user_type'] = $_SESSION['user_type'] ;
	if( !empty( $_REQUEST['privilege'] ) ) {
		$resp['privilege'] = get_privilege( $_REQUEST['privilege'] );
	}
}
else {	
	$resp['timeout'] = 0 ;
}
echo json_encode( $resp ) ;
return ;
?>