<?php
// subsessionstart.php - Start a sub session relate to unit id
// Requests:
//      unitid : unit id
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-08-22
// Copyright 2017 Toronto MicroElectronics Inc.

$noclosesession = true ;
require 'session.php' ;
require_once 'firebase.php' ;
require_once 'mservice.php' ;

header("Content-Type: application/json");

if( !empty($_SESSION['logon']) && !empty( $_REQUEST['unitid'] ) ){
	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	$unitid = $conn->escape_string($_REQUEST['unitid']);
	$sql = "SELECT * FROM mdu WHERE id = '$unitid' " ;
	if($result=$conn->query($sql)) {
		if( $row = $result->fetch_assoc() ) {
			if( empty( $row['config_data'] ) ) {
				$resp['errmsg'] = "This MDU has not been setup!" ;
				goto done;
			}
			
			if( empty($row['extra']) ) {
				$newid = firebase_rest("units", array( 'ts' => array( '.sv' => "timestamp" ) ), "POST" );
				$id = json_decode( $newid );
				$fireid = $id->name ;
				$sql = "UPDATE mdu SET `extra` = '$fireid' WHERE id = '$unitid'" ;
				$conn->query($sql);
			}
			else {
				$fireid =$row['extra'] ;
				// firebase_rest( "units/$fireid/ts" , array( '.sv' => "timestamp" ) ) ;
			}

			$unitid = $row['id'] ;
			
			// setup new sub session
			if( empty( $_SESSION['mduserial'] ) ) {
				$_SESSION['mduserial'] = 10 ;
				$_SESSION['mdusession'] = array();
			}

			$subid = ++$_SESSION['mduserial'] ;
			$_SESSION['mdusession'][$subid] = array(
				'mduid' => $unitid ,
				'fireid' => $fireid
			);

			session_write_close();

			$resp['location_id'] = $row['location_id'] ;
			$resp['name'] = $row['unit_id'] ;
			$resp['unitid'] = $unitid ;
			$resp['fireid'] = $fireid ;
			$resp['subid'] = $subid ;
			$resp['res'] = 1 ; //success

			// start MServer session by start monitor subject status (MDU_SUBJECT_STATUS_START)
			//	MDU_SUBJECT_STATUS_START (6)
			$mduoffline=true ;
			$sret = mservice_cmd( 6, $subid, $unitid );
			if( $sret && is_string($sret) ) {
				@$srsp = simplexml_load_string( $sret );
				if( !empty($srsp) && isset( $srsp->status ) ) {
					$status = (string) $srsp->status ;
					if( substr( (string) $srsp->status, 0, 5 ) != "Error" ) {
						$mduoffline=false ;
					}
				}
			}
			if( $mduoffline ) {
				firebase_rest("units/$fireid/subjectstatus", "MDU error or offline!" );
				firebase_rest("units/$fireid/ts" , array( '.sv' => "timestamp" ) ) ;
			}

			// may need push event statistics data 

		}
		$result->free();
	}
}
done:
if( !empty( $resp ) )
	echo json_encode( $resp ) ;
return ;
?>