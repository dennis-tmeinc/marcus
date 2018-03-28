<?php
// userpriv.php 	
//		get user priviages
// Request:
//      none
//
// Return:
//      json 
//
// By Dennis Chen @ TME	 - 2018-01-09
// Copyright 2017 Toronto MicroElectronics Inc.
//
// 
//
require 'session.php' ;
header("Content-Type: application/json");

if( $session_logon ) {
	$userprivs = json_decode( file_get_contents( 'conf/userprivileges.json' ), true );
	if( !empty( $userprivs['user_privileges'][$_SESSION['user_type']] ) ) {
		$resp['res'] = 1 ;
		$resp['usertype'] = $_SESSION['user_type'] ;
		$resp['privs'] = $userprivs['user_privileges'][$_SESSION['user_type']] ;
	}
}
echo json_encode($resp);
?>