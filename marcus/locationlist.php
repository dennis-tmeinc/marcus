<?php
// locationlist.php - Get list of all location ids
// Requests:
//      none
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2013-06-24
// Copyright 2013 Toronto MicroElectronics Inc.

require 'session.php' ;
header("Content-Type: application/json");

if( $session_logon ){

	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	$sql = "SELECT distinct location_id from mdu" ;
	if($result=$conn->query($sql)) {
		$resp['locationlist'] = array(); 
		while( $row = $result->fetch_array() ) {
			$resp['locationlist'][]=array( 
				'id' => $row[0],
				'name' => $row[0]
			);
		}
		$result->free();
		$resp['res']=1 ; //success
	}
}
echo json_encode( $resp ) ;
return ;
?>