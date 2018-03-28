<?php
// marcusevent.php - MService callback processor
// Requests:
//      xml : XML contents from MService
// Return:
//      none
// By Dennis Chen @ TME	 - 2017-08-16
// Copyright 2017 Toronto MicroElectronics Inc.

include_once 'conf/config.php' ;
include_once "servicelog.php" ;

header("Content-Type: application/xml");

$xmlresp = new SimpleXMLElement('<mwebc><status>Error</status></mwebc>') ;

if( empty($_REQUEST['xml']) ) {
	$xmlresp->status="ErrorNoXML" ;
	$xmlresp->errormsg='No XML parameter!' ;
	goto done ;
}

// log mevent
$now = DateTime::createFromFormat('U.u', microtime(true));
$log  = $now->format("Y-m-d H:i:s.u") . " - marcusevent.php - from: ". $_SERVER['REMOTE_ADDR']. "\n" .
		$_REQUEST['xml'] . "\n" ;

@$mwebc = simplexml_load_string($_REQUEST['xml']) ;

if( empty($mwebc) ) {
	$xmlresp->status='ErrorXML' ;
	$xmlresp->errormsg='XML document error!';
	goto done ;
}

if( empty( $mwebc->session ) ) {
	$xmlresp->status='ErrorXMLSession' ;
	$xmlresp->errormsg='No session in XML document';
	goto done ;
}

$xmlresp->session = (string) $mwebc->session ;

$noredir = true ;
$noupdatextime = true ;
$sa = explode( '-', (string)($mwebc->session), 2 );
$session_id = $sa[0] ;
include_once "session.php" ;


if( !$session_logon ) {
	$xmlresp->status='ErrorSession' ;
	$xmlresp->errormsg='Session closed or invalid.' ;
	goto done ;	
}

if( !empty( $mwebc->client ) && strcmp( $_SESSION["clientid"], $mwebc->client ) !== 0 ) {
	$xmlresp->status='ErrorClientID' ;
	$xmlresp->errormsg='The Client ID provided is not valid.' ;
	goto done ;	
}

if( empty( $mwebc->command ) ) {
	$xmlresp->status='ErrorCommand' ;
	$xmlresp->errormsg='No command provided.' ;
	goto done ;
}

$xmlresp->command = (string) $mwebc->command ;

if( empty( $sa[1] ) ) {
	@$subsess = $_SESSION['mdusession'][0] ;
}
else {
	@$subsess = $_SESSION['mdusession'][$sa[1]] ;
}

if( empty( $subsess ) ) {
	$xmlresp->status='ErrorSession' ;
	$xmlresp->errormsg='Sub session closed or invalid.' ;
	goto done ;		
}

if( empty( $mwebc->mdu ) || empty( $subsess['mduid'] ) || strcmp( $subsess['mduid'], (string)$mwebc->mdu ) !== 0 ) {
	$xmlresp->status='ErrorMDUID' ;
	$xmlresp->errormsg='The MDU ID provided is not valid.' ;
	goto done ;	
}

$xmlresp->mdu = (string) $mwebc->mdu ;

$unitid = $subsess['mduid'] ;
$fireid = $subsess['fireid'] ;

if( empty( $fireid ) ) {
	$xmlresp->status='ErrorMDUID' ;
	$xmlresp->errormsg='The MDU ID is not in use.' ;
	goto done ;		
}

// new timestamp
if( empty( $mwebc->ts ) ) {
	$ts = (string)$_SERVER['REQUEST_TIME'];
}
else {
	$ts = (string)$mwebc->ts ;
}

$camera = 0;
if( isset( $mwebc->camera ) ) {
	@$camera = (int) ($mwebc->camera) ;
}
else if( isset( $mwebc->mdup->cam ) ) {
	@$camera = (int) ($mwebc->mdup->cam) ;
}

if( empty( $camera ) ) {
	$camera = 0;
}

$eventlockfile = "ev" . md5($mwebc->mdu . '.' . $mwebc->command . '.' . $camera) ;
$eventlock = session_lock( $eventlockfile ) ;
$eo = fread( $eventlock, 1000 );
fseek( $eventlock, 0);
ftruncate($eventlock,0);
fwrite($eventlock, $ts);
session_unlock( $eventlock );

if( $ts == $eo ) {
	$xmlresp->status='OK' ;
	$xmlresp->message='Processed event!' ;
	goto done ;	
}

require_once 'firebase.php' ;

function flushResp( $resp )
{
	ignore_user_abort(true);
	ob_start();
	echo $resp->asXML() ;

	// forced to close connection
	header('Connection: close');
	header('Content-Length: '.ob_get_length());

	ob_end_flush();
	ob_flush();
	flush();
}

function updateCamImg( $cmd )
{
	global $unitid, $camera, $ts ;
	
	// update img ts
	$mservice_lockfile = session_lock( "i$cmd.$unitid.$camera" ) ;
	ftruncate( $mservice_lockfile, 0 );
	fwrite($mservice_lockfile, $ts);
	session_unlock( $mservice_lockfile );
}

switch ( (int)$mwebc->command ) {
	case 101:	// WHOLE_BKIMG_READY
		$xmlresp->status='OK' ;
		flushResp( $xmlresp );
		$xmlresp = null ;
		
		updateCamImg( 1 );

		firebase_rest("units/$fireid/bgimg/$camera", $ts );
		break;

	case 102:	// ROC_BKIMG_READY
		$xmlresp->status='OK' ;
		flushResp( $xmlresp );
		$xmlresp = null ;

		updateCamImg( 2 );
		
		firebase_rest("units/$fireid/rocimg/$camera", $ts );
		break;
		
	case 103:	// MDU_CONFIG_CHANGED
		$xmlresp->status='OK' ;
		flushResp( $xmlresp );
		$xmlresp = null ;
		
		$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
		$sql = "SELECT * FROM mdu WHERE id = '$unitid' " ;
		if($result=$conn->query($sql)) {
			if( $row = $result->fetch_assoc() ) {
				if( !empty($row['config_data']) ) {
					firebase_rest("units/$fireid/conf" , $row['config_data'] );
					firebase_rest("units/$fireid/ts" , array( '.sv' => "timestamp" ) ) ;
				}
			}
		}
		break;
		
	case 202:	// MDU_LIVE_METADATA
	
		if( !isset( $subsess['livecam'] ) || $camera != $subsess['livecam'] ) {
			$xmlresp->message='Camera number error!' ;
			$xmlresp->camera=$subsess['livecam'] ;
			$xmlresp->status='ErrorCamera' ;
			goto done ;	
		}

		$eventlock = session_trylock( $eventlockfile."-meta" ) ;
		if( $eventlock ) {

			// try finish the request first
			$xmlresp->status='OK' ;

			// log resp
			$log .= "RESP:\n". $xmlresp->asXML(). "\n" ;

			flushResp( $xmlresp );
			$xmlresp = null ;			
		
			if( !empty($mwebc->mdup->poses->pose) ) {
				$peoples = array() ;
				foreach ( $mwebc->mdup->poses->pose as $pose ) {
					$people = array() ;
					$pts = explode( ',', (string)$pose ) ;
					for( $point = 0; $point<18; $point++ ) {
						if( !empty( $pts[$point*2] ) )
							$x = (int) $pts[$point*2] ;
						else
							$x = 0 ;
						if( !empty( $pts[$point*2+1] ) )
							$y = (int) $pts[$point*2+1] ;
						else 
							$y = 0 ;
						$people[$point] = array( 
							'x' => $x ,
							'y' => $y );
					}
					$peoples[] = $people ;
				}
				$pose = array(
					'people' => $peoples ,
					'ts' => $session_time
				);
				firebase_rest("units/$fireid/livepeople/$camera", $pose );
			}
			else {
				firebase_rest("units/$fireid/livepeople/$camera", NULL, "DELETE" );
			}
			
			ftruncate($eventlock,0);
			fwrite($eventlock, $ts);
			session_unlock( $eventlock );
		}
		else {
			$xmlresp->message='Event on process!' ;
			$xmlresp->status='OK' ;
		}
	
		break;

	case 205:	// MDU_SUBJECT_STATUS
		if( !empty( $mwebc->mdup ) ) {
			
			// try finish the request first
			$xmlresp->status='OK' ;
			// log resp
			$log .= "RESP:\n". $xmlresp->asXML(). "\n" ;
			
			flushResp( $xmlresp );
			$xmlresp = null ;			
			
			$subjectStatus = json_decode( file_get_contents("conf/subjectStatus.json"), true );
			
			if( !empty( $mwebc->mdup->unitsub ) && !empty($mwebc->mdup->rooms->room) ) {
				$units = (int) $mwebc->mdup->unitsub ;
			}
			$eventStr = "No Subject!" ; 
			if( !empty( $units ) ) {
				if( $units > 1 ) {
					$eventStr = "Total Subjects: $units <br/>" ; 
				}
				else {
					$eventStr = "Total Subject: $units <br/>" ; 
				}
				foreach( $mwebc->mdup->rooms->room as $room ) {
					if( !empty( $room->subs->sub ) ) {
						if( !empty( $room->name ) ) {
							$eventStr .= ((string) $room->name) . "<br/>" ;
						}
						else {
							$eventStr .= "Unknown Room <br/>" ;
						}						
						$eventsum = array();
						foreach( $room->subs->sub as $sub ) {
							$subtype = (int) $sub ;
							if( empty( $eventsum[ $subtype ] ) ) {
								$eventsum[ $subtype ] = 1 ;
							}
							else {
								$eventsum[ $subtype ] ++ ;
							}
						}
						foreach( $eventsum as $key => $value ) {
							if( !empty( $subjectStatus[ $key ] ) ) {
								$eventStr .= "&nbsp;&nbsp;&nbsp;&nbsp;".$subjectStatus[ $key ]." :" ;
							}
							else {
								$eventStr .= "&nbsp;&nbsp;&nbsp;&nbsp;Unknown :";
							}
							$eventStr .= " $value <br/>" ;
						}
					}
				}
			}

			firebase_rest("units/$fireid/subjectstatus", $eventStr );
			// firebase_rest("units/$fireid/subjectstatus", $mwebc->mdup->asXML() );
			firebase_rest("units/$fireid/ts" , array( '.sv' => "timestamp" ) ) ;
		}
		else {
			
			$xmlresp->status='OK' ;
			flushResp( $xmlresp );
			$xmlresp = null ;	

			firebase_rest("units/$fireid/subjectstatus", NULL, "DELETE" );
			$xmlresp->status='ErrorNoSubjectStatus' ;
		}
		break;
		
	default:
		$xmlresp->status='ErrorUnknownCommand' ;

}

done:

if( !empty($xmlresp) ) {
	$xres = $xmlresp->asXML() ;
	echo $xres ;
	
	// log resp
	$log .= "RESP:\n". $xres. "\n" ;
}

// log mevent
if( !empty($log_service) ) {
	service_log( $log );
}

return ;
?>