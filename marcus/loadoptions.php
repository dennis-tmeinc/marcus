<?php
// loadoptions.php - Get global options
// Requests:
//      none
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2018-11-15
// Copyright 2013 Toronto MicroElectronics Inc.

require 'session.php' ;
header("Content-Type: application/json");

if( $session_logon ){
	$fn = "conf/options" ;
	if( file_exists ( $fn ) ){
		echo file_get_contents( $fn );
		return ;
	}
}
echo json_encode( $resp ) ;
return ;
?>