<?php
// confload.php - load conf (notification)
// Requests:
//		subid : sub mission id
//      unitid : unit id (optional)
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-08-22
// Copyright 2017 Toronto MicroElectronics Inc.

require 'session.php' ;
require_once 'firebase.php' ;

if( $session_logon ){
	
	if( !empty( $_REQUEST['unitid'] ) ) {
		$unitid = $_REQUEST['unitid'] ;
	}
	else if( !empty( $_REQUEST['subid'] ) ) {
		@$unitid = $_SESSION['mdusession'][$_REQUEST['subid']]['mduid'] ;
	}
	
	if( empty( $unitid ) ) {
		goto done;
	}
	
	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	$sql = "SELECT * FROM mdu WHERE id = '". $conn->escape_string($unitid) ."'" ;
	if($result=$conn->query($sql)) {
		if( $row = $result->fetch_assoc() ) {
			if( !empty($row['extra']) && !empty($row['config_data']) ) {
				$fireid = $row['extra'] ;
				firebase_rest( "units/$fireid/conf" , $row['config_data'] );
				firebase_rest( "units/$fireid/ts" , array( '.sv' => "timestamp" ) ) ;
				$resp['fireid'] = $row['extra'] ;
				$resp['res']=1 ; //success
			}
		}
	}
}

done:
header("Content-Type: application/json");
echo json_encode( $resp ) ;
return ;
?>