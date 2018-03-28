<?php
// subsessionkill.php - killsub session(s)
// Requests:
//      subid : sub session id or array of sub session ids
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-08-22
// Copyright 2017 Toronto MicroElectronics Inc.

$noclosesession = true ;
require 'session.php' ;
require_once 'mservice.php' ;

header("Content-Type: application/json");

if( !empty($_SESSION['logon']) && !empty( $_REQUEST['subid'] ) ){
	
	$endingSessions = array() ;	

	// stop MServer session by stop monitor subject status (MDU_SUBJECT_STATUS_STOP(07))
	if( is_array( $_REQUEST['subid'] ) ) {
		foreach ( $_REQUEST['subid'] as $subid ) {
			if( isset($_SESSION['mdusession'][$subid]) ) {
				unset( $_SESSION['mdusession'][$subid] );
				$endingSessions[] = $subid ;
			}
		}
	}
	else {
		$subid = $_REQUEST['subid'] ;
		if( isset($_SESSION['mdusession'][$subid]) ) {
			unset( $_SESSION['mdusession'][$subid] );
			$endingSessions[] = $subid ;
		}
	}
	
	session_write_close();
	ignore_user_abort();
	
	// $resp['endsess'] = json_encode( $endingSessions );
	$resp['res'] = 1 ; //success

	foreach( $endingSessions as $subid ) {

		// @$mdu = $_SESSION['mdusession'][$subid]['mduid'] ;
		// stop MServer session by stop monitor subject status (MDU_SUBJECT_STATUS_STOP(07))
		// mservice_cmd( 7, $subid, $mdu );
		// end session, CMD_SESSION_END(9999)
		// mservice_cmd( 9999, $subid );
			
		mservice_cmd( 9999, $subid );
	}
}

if( !empty($resp) ) 
	echo json_encode( $resp ) ;

?>