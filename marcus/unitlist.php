<?php
// locationlist.php - Get list of all location ids
// Requests:
//      locationid : location id
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2013-06-24
// Copyright 2013 Toronto MicroElectronics Inc.

include_once 'session.php' ;
header("Content-Type: application/json");

if( $session_logon && isset( $_REQUEST['locationid'] ) ){

	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	
	$locationid = $conn->escape_string($_REQUEST['locationid']);
	
	$sql = "SELECT id, unit_id from mdu WHERE location_id = '$locationid' " ;
	if($result=$conn->query($sql)) {
		$resp['unitlist'] = array(); 
		while( $row = $result->fetch_array() ) {
			$resp['unitlist'][]=array( 
				'id' => $row['id'],
				'name' => $row['unit_id']
			);
		}
		$result->free();
		$resp['res']=1 ; //success
	}
}
echo json_encode( $resp ) ;
return ;
?>