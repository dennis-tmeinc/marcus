<?php
// dayreport.php - retrive room usage day report
// Requests:
//      unitid : unit id
//		fireid : firebase id
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2013-06-24
// Copyright 2013 Toronto MicroElectronics Inc.

require 'session.php' ;
header("Content-Type: application/json");
require_once 'firebase.php' ;

if( $session_logon && !empty($_REQUEST['unitid']) ){
	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	$unitid = $conn->escape_string($_REQUEST['unitid']);
	
	$reports = array();
	$sql = "SHOW COLUMNS FROM event_statistics" ;
	$sumfileds = "" ;
	if( $result=$conn->query($sql) ) {
		$fs = 0 ;
		while( $fields = $result->fetch_assoc() ) {
			if( ++$fs > 3 ) { 
				$sumfileds .=  " SUM($fields[Field])," ;
				$reports[] = array( 
					'event' => $fields['Field'],
					'today' => 0,
					'yesterday' => 0,
					'day2' => 0,
					'day30' => 0
					);
			}
		}
		$result->free();
	}
	$sumfileds = substr($sumfileds,0,-1);

	// today statistics
	$sql = "select $sumfileds FROM event_statistics WHERE mdu_id = '$unitid' AND `_date` >=  CURDATE()" ;
	if( $result=$conn->query($sql) ) {
		if( $fields = $result->fetch_row() ) {
			for( $fs = 0 ; $fs < count( $fields ); $fs++ ) {
				$reports[$fs]['today'] = round( $fields[ $fs ], 2 ) ;
			}
		}
		$result->free();
	}
	
	// yesterday
	$sql = "select $sumfileds FROM event_statistics WHERE mdu_id = '$unitid' AND `_date` >= CURDATE() - INTERVAL 1 DAY AND `_date` <  CURDATE()" ;
	if( $result=$conn->query($sql) ) {
		if( $fields = $result->fetch_row() ) {
			for( $fs = 0 ; $fs < count( $fields ); $fs++ ) {
				$reports[$fs]['yesterday'] = round( $fields[ $fs ], 2 ) ;
			}
		}
		$result->free();
	}

	// 2 days ago
	$sql = "select $sumfileds FROM event_statistics WHERE mdu_id = '$unitid' AND `_date` >= CURDATE() - INTERVAL 2 DAY AND `_date` <  CURDATE() - INTERVAL 1 DAY" ;
	if( $result=$conn->query($sql) ) {
		if( $fields = $result->fetch_row() ) {
			for( $fs = 0 ; $fs < count( $fields ); $fs++ ) {
				$reports[$fs]['day2'] = round( $fields[ $fs ], 2 );
			}
		}
		$result->free();
	}
	
	// 30 days avg
	$sql = "select $sumfileds FROM event_statistics WHERE mdu_id = '$unitid' AND `_date` >= CURDATE() - INTERVAL 30 DAY" ;
	if( $result=$conn->query($sql) ) {
		if( $fields = $result->fetch_row() ) {
			for( $fs = 0 ; $fs < count( $fields ); $fs++ ) {
				$reports[$fs]['day30'] =  round( $fields[ $fs ] / 30, 2 ) ;
			}
		}
		$result->free();
	}

	firebase_rest( "units/$_REQUEST[fireid]/dayevents" , $reports );

	$resp['list'] = $reports ;
	$resp['res']=1 ; //success

}
echo json_encode( $resp ) ;
return ;
?>