<?php
// cameralist.php - Get list camera for a unit
// Requests:
//      unitid : unit id
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-08-04
// Copyright 2013 Toronto MicroElectronics Inc.

require 'session.php' ;
header("Content-Type: application/json");

if( $session_logon && !empty( $_REQUEST['unitid'] ) ){

	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	$unitid = $conn->escape_string($_REQUEST['unitid']);
	
	$sql = "SELECT config_data FROM mdu WHERE id = '$unitid' " ;
	if( $result=$conn->query($sql) ) {
		if( $row = $result->fetch_assoc() ) {
			$conf = json_decode( $row['config_data'], true );
			$cameras=array();
			for( $i=0; $i < count( $conf['cameras'] ); $i++ ) {
				@$cam = $conf[ 'cameras' ][$i] ;
				@$room = $conf['rooms'][$cam['roomIndex']] ;
				if( !empty( $cam['enable'] ) ) {
					$cameras[] = array(
						'value' => $i ,
						'name' => $room['name'] . ' - '. $cam['name'] 
						);
				}
			}
			$resp['cameras'] = $cameras ;
			$resp['res']=1 ; //success
		}
		$result->free();
	}
}
echo json_encode( $resp ) ;
return ;
?>