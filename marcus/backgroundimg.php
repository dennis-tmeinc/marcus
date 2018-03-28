<?php
// backgroundimg.php - Get bacground image of selected camera
// Requests:
//      subid : sub session id
//		camera : camera id ( index )
//      roc : 	 roc backound / or full backgound img
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2013-06-24
// Copyright 2013 Toronto MicroElectronics Inc.

require 'session.php' ;
require_once 'firebase.php' ;
require_once 'mservice.php' ;

header("Content-Type:image/jpeg") ;

if( $session_logon ){
	if( empty( $_SESSION['mdusession'][$_REQUEST['subid']]  )) {
		return ;
	}

	$unitid = $_SESSION['mdusession'][$_REQUEST['subid']]['mduid'] ;
	$fireid = $_SESSION['mdusession'][$_REQUEST['subid']]['fireid'] ;
	
	if( empty( $_REQUEST['camera'] ) ) {
		$camera = 0 ;
	}
	else {
		$camera = (int) $_REQUEST['camera'] ;
	}
	if( empty($_REQUEST['roc']) || $_REQUEST['roc'] == 'false' ) {
		$roc = false ;
	}
	else {
		$roc = true ;
	}
	if( $roc ) {
		$firepath = "units/$fireid/rocimg/$camera" ;
		$ms_cmd = 2 ;		// ROC_BKIMG_REQUEST
	}
	else {
		$firepath = "units/$fireid/bgimg/$camera" ;
		$ms_cmd = 1 ;		// WHOLE_BKIMG_REQUEST
	}
	
	firebase_event( $firepath, function( $event, $data ){
		static $event_count = 0 ;
		static $val = NULL ;
		static $ts = 0 ;
		global $unitid , $camera, $ms_cmd   ;
		if( $event == "put" ) {
			$da = json_decode( $data );
			if( $da->path == "/" ) {
				if( $event_count == 0 ) {
					$val = $da->data ;
					$ts = time();
					mservice_cmd( $ms_cmd, $_REQUEST['subid'], $unitid, $camera );
				}
				else {
					if( $da->data != $val ) {
						return false ;		// new img time stamp detected, stop event loop
					}
				}
			}
			$event_count++ ;
		}
		// let keep alive event to stop event loop
		return ( time() - $ts < 10 )  ;
	});

	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	$sql = "SELECT * FROM camera WHERE mdu_id = '$unitid' AND id = $camera" ;
	if($result=$conn->query($sql)) {
		if( $row = $result->fetch_assoc() ) {
			if( $roc ) {
				@$img = $row['roc_background_img_path'];
			}
			else {
				@$img = $row['whole_background_img_path'];
			}
		}
		$result->free();
	}

	// if img not available , use default img
	if( empty( $img ) ) {
		if( $roc ) {
			$img = "res/backgroundRoc.jpg" ;
		}
		else {
			$img = "res/backgroundWhole.jpg" ;
		}
	}
			
	if( !empty( $img ) ) {
		if( !empty($remote_fileserver) ) {
			$img = $remote_fileserver . "?c=r&n=" . urlencode ( $img ) ;
		}
		$imgdata = file_get_contents( $img ) ;
		header("Content-Length:".strlen($imgdata) );
		echo $imgdata ;
	}
}

return ;
?>