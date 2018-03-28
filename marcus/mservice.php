<?php
// mservice.php  - Mservice interface
// Requests:
// Return:
//      XML element return from MService
// By Dennis Chen @ TME	 - 2017-08-16
// Copyright 2017 Toronto MicroElectronics Inc.

function mservice_cmd( $cmd, $subid, $mduid=NULL, $camera=NULL  )
{
	$xml = new SimpleXMLElement('<mwebc></mwebc>') ;
	$xml->session = session_id().'-'.$subid ;
	if( !empty( $_SESSION['clientid'] ) )
		$xml->client = $_SESSION['clientid'] ;
	$xml->command = $cmd ;
	if( isset( $mduid ) ) {
		$xml->mdu = $mduid ;
	}
	else if( isset( $_SESSION['mdusession'][$subid]['mduid'] ) ) {
		$xml->mdu = $_SESSION['mdusession'][$subid]['mduid'] ;
	}
	if( isset( $camera ) ) {
		$xml->camera = (int)$camera ;
	}
	
	global $mservice_callback , $marcus_service ;
	if( empty( $mservice_callback ) ) {
		$xml->callbackurl = "http://". $_SERVER['HTTP_HOST'] . ":80". dirname($_SERVER['REQUEST_URI']).'/marcusevent.php' ;
	}
	else {
		$xml->callbackurl = $mservice_callback ;
	}

	$opts =  array('http' =>
		array(
			'method'  => 'GET',
			'timeout' => 2.5
		)
	);
	$ret = file_get_contents( $marcus_service.'?xml='.rawurlencode($xml->asXML()), false, stream_context_create($opts) ) ;

	// log mevent
	global $log_service ;
	if( $log_service ) {
		include_once "servicelog.php" ;
		$now = DateTime::createFromFormat('U.u', microtime(true));
		$log = $now->format("Y-m-d H:i:s.u") . " - Mservice - From: ". $_SERVER['REMOTE_ADDR']. "\n" . 
				$xml->asXML() . "Ret: ".$ret ."\n" ;
		service_log( $log );
	}
	return $ret ;
}
