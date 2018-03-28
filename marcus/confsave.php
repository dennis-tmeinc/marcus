<?php
// confsave.php - save new conf (notification)
// Requests:
//      subid : sub sessiion id
//		saveroc: if roc changed 
//		camera: camera id (if roc changed)
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-08-16
// Copyright 2017 Toronto MicroElectronics Inc.

require 'session.php' ;
require_once 'firebase.php' ;
require_once 'mservice.php' ;

if( $session_logon ){

	// _REQUEST missing fix on POST
	if( empty( $_REQUEST ) ) {
		$_REQUEST = $_POST ;
	}
	
	if( empty( $_SESSION['mdusession'][$_REQUEST['subid']] ) ) {
		goto done;
	}

	$unitid = $_SESSION['mdusession'][$_REQUEST['subid']]['mduid'] ;
	$fireid = $_SESSION['mdusession'][$_REQUEST['subid']]['fireid'] ;
	
	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	
	$newconf = firebase_rest("units/$fireid/conf");
	$confstr = $conn->escape_string($newconf);
	$ts = time();
	$sql = "UPDATE mdu SET `config_data` = '$confstr', `config_timestamp` = $ts  WHERE id = '$unitid' " ;
	if( $conn->query($sql) ) {
		// need to inform mservice, MDU_CONFIG_CHANGED
		if( empty( $_REQUEST['saveroc'] ) || $_REQUEST['saveroc']=='false' ) {
			// MDU_CONFIG_CHANGED_BY_WEB(03)
			mservice_cmd( 3, $_REQUEST['subid'], $unitid);
		}
		else {
			// MDU_CONFIG_ROC_CHANGED_BY_WEB(08)
			mservice_cmd( 8, $_REQUEST['subid'], $unitid, $_REQUEST['camera'] );
		}
		$resp['res']=1 ; //success
	}
}
done:
header("Content-Type: application/json");
echo json_encode( $resp ) ;
return ;
?>