<?php
// mdusession.php - update mdu monitoring session
// Requests:
//      unitids : array of unit ids to monitor
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-08-16
// Copyright 2017 Toronto MicroElectronics Inc.

require 'session.php' ;
require_once 'firebase.php' ;
header("Content-Type: application/json");

if( $session_logon ){

	
	if( !empty( $_REQUEST['unitids'] ) ) {
		header("x-ids:" . json_encode( $_REQUEST['unitids']  ) );
	}
	$resp['res']=1 ; //success
}

echo json_encode( $resp ) ;
return ;
?>