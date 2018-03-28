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
		//$firepath = "units/$fireid/rocimg/$camera" ;
		$ms_cmd = 2 ;		// ROC_BKIMG_REQUEST
	}
	else {
		//$firepath = "units/$fireid/bgimg/$camera" ;
		$ms_cmd = 1 ;		// WHOLE_BKIMG_REQUEST
	}

	$mservice_lockfilename = session_lock_filename( "i$ms_cmd.$unitid.$camera" );
	$mservice_lockfile = fopen( $mservice_lockfilename, 'c+' );
	
	flock( $mservice_lockfile, LOCK_SH );
	$xts = (int)fread( $mservice_lockfile, 100 );
	flock( $mservice_lockfile, LOCK_UN );
	
	mservice_cmd( $ms_cmd, $_REQUEST['subid'], $unitid, $camera );

	// wait maximum 20s for mservice call back 
	$xt = time();
	while( time()-$xt < 20 ) {
		flock( $mservice_lockfile, LOCK_SH );
		fseek( $mservice_lockfile, 0 );
		$ts = (int)fread( $mservice_lockfile, 100 );
		flock( $mservice_lockfile, LOCK_UN );
		if( $ts!=$xts )  {
			break ; 
		}
		usleep(100000);
	}
	fclose( $mservice_lockfile );
	
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