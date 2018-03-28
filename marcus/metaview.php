<?php
// metaview.php - Start MetaView (live) for selected camera
// Requests:
//      subid : sub session id
//		camera : camera id (index)
//      on : 	 start/stop metaview
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-08-08
// Copyright 2017 Toronto MicroElectronics Inc.

$noclosesession = true ;
require 'session.php' ;
header("Content-Type: application/json") ;

if( $session_logon && isset( $_REQUEST['subid'] ) ){
	require_once 'mservice.php' ;
	require_once 'firebase.php' ;
	
	if( empty( $_SESSION['mdusession'][$_REQUEST['subid']] )) {
		goto done;
	}

	@$unitid = $_SESSION['mdusession'][$_REQUEST['subid']]['mduid'] ;
	@$fireid = $_SESSION['mdusession'][$_REQUEST['subid']]['fireid'] ;
	
	if( isset( $_SESSION['mdusession'][$_REQUEST['subid']]['livecam'] ) ) {
		@$xcam = $_SESSION['mdusession'][$_REQUEST['subid']]['livecam'] ;
		unset( $_SESSION['mdusession'][$_REQUEST['subid']]['livecam'] ) ;
	}
		
	$camera = 0 ;
	if(isset($_REQUEST[camera]) ) {
		$camera = (int)$_REQUEST[camera];
	}
	
	// new live cam
	if( !empty( $_REQUEST['on'] ) && $_REQUEST['on']!='false' ) {
		$_SESSION['mdusession'][$_REQUEST['subid']]['livecam'] = $camera ;
	}

	$resp['res']=1 ; //success
	session_write_close();
	
	// stop prev live cam
	if( isset( $xcam ) ) {
		// stop prev live session, MDU_LIVE_METADATA_STOP(05)
		$ret = mservice_cmd( 5, $_REQUEST['subid'], $unitid, $xcam  );
		$resp['cam-stop'] = $xcam ;
		if( $ret === false ) {
			$resp['cam-stop-error'] = "Mservice failed!" ;
		}
	}
	
	// start new live cam
	if( isset( $_SESSION['mdusession'][$_REQUEST['subid']]['livecam'] ) ) {
		@$ts = json_decode( firebase_rest("units/$fireid/livepeople/$camera/ts") );
		if( !empty( $ts ) && $session_time - $ts > 100 ) {
			firebase_rest("units/$fireid/livepeople/$camera", NULL, "DELETE" );
		}			
		// start new live session, MDU_LIVE_METADATA_START(04)
		$ret = mservice_cmd( 4, $_REQUEST['subid'], $unitid, $_SESSION['mdusession'][$_REQUEST['subid']]['livecam'] );
		$resp['cam-start'] = $_SESSION['mdusession'][$_REQUEST['subid']]['livecam'] ;
		if( $ret === false ) {
			$resp['cam-start-error'] = "Mservice failed!" ;
		}
	}
	
}
done:
if( !empty($resp) )
	echo json_encode( $resp ) ;
?>