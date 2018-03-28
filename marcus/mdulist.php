<?php
// mdulist.php - Get full list of all mdu
// Requests:
//      none
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-09-14
// Copyright 2013 Toronto MicroElectronics Inc.

require 'session.php' ;
header("Content-Type: application/json");

if( $session_logon ){
	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	$sql = "SELECT `id`, `location_id`, `unit_id` FROM `mdu` ";
	if($result=$conn->query($sql)) {
		$resp['mdulist'] = array(); 
		while( $row = $result->fetch_assoc() ) {
			$resp['mdulist'] []=$row ;
		}
		$result->free();
		$resp['res']=1 ; //success
	}
}
echo json_encode( $resp ) ;
return ;
?>