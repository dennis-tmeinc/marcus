<?php
// firebase.php	- firebase RESTful function
// By Dennis Chen @ TME	 - 2017-08-02
// Copyright 2017 Toronto MicroElectronics Inc.
// reference:
//   https://firebase.google.com/docs/reference/rest/database

function firebase_idtoken()
{
	if( !empty($_SESSION['firetoken']) ){
		return $_SESSION['firetoken'] ;
	}
	
	$token_file = session_save_path().'/sess_firebasetoken' ;
	@$token = json_decode( file_get_contents( $token_file ) );

	if( empty($token) || time() > $token->expire  ) {
		firebase_lock();
		$fb_access = json_decode( file_get_contents('https://tme-marcus.firebaseio.com/access.json')); 
		$acc = json_decode( openssl_decrypt( substr($fb_access->server, 0, -32), 'aes-256-ctr', substr($fb_access->server, -32)));
		$marcuslogin="https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key={$acc->appcfg->apiKey}" ;
		@$marcus_token = file_get_contents( $marcuslogin, false, stream_context_create(array(
			'http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/json',
				'content' => json_encode( $acc->access )
			)
		)));
		@$token = json_decode( $marcus_token ) ;
		if( !empty( $token ) ) {
			if( !empty( $token->expiresIn ) ) {
				$token->expire = time() + $token->expiresIn - 300 ;
			}
			else {
				$token->expire = time() + 1800 ;
			}
			file_put_contents( $token_file, json_encode( $token ) );
		}
		firebase_unlock();
	}
	
	if( !empty($token) && !empty( $token->idToken )) {
		return $token->idToken ;
	}
	else 
		return false ;
}

// firebase_rest
//   rest api for firebase database
// input
//		$marcuspath : path from root for tme-marcus project
//     	$content    : content to save onto database
//		$method    : 'GET', 'PUT' or 'POST'
function firebase_rest( $marcuspath, $content=NULL, $method=NULL )
{
	$marcusurl =  "https://tme-marcus.firebaseio.com/${marcuspath}.json" ;
	$idtoken = firebase_idtoken();
	if( !empty( $idtoken )) {
		$marcusurl .= "?auth=$idtoken" ;
	}
	
	$http_opt = array();
	if( isset( $content ) ) {
		$http_opt['method'] = 'PUT' ;
		$http_opt['header'] = 'Content-type: application/json' ;
		if( is_string( $content ) && strlen( $content ) > 1 && ( $content[0] == '{' || $content[0] == '[' ) ) {
			$http_opt['content'] = $content  ;
		}
		else {
			$http_opt['content'] = json_encode( $content ) ;
		}
	}
	if( !empty( $method ) ) {
		$http_opt['method'] = $method ;
	}
		
	if( empty( $http_opt ) ) {
		return file_get_contents( $marcusurl );
	}
	else {
		return file_get_contents( $marcusurl, false, stream_context_create(array(
			'http' => $http_opt 
		)));
	}
}

// firebase_event
//   data stream api for firebase database
// input
//		$marcuspath : path from root for tme-marcus project
//     	$callback   : callback function when event available
function firebase_event( $marcuspath, $callback = NULL )
{
	$marcusurl =  "https://tme-marcus.firebaseio.com/${marcuspath}.json" ;
	$idtoken = firebase_idtoken();
	if( !empty( $idtoken )) {
		$marcusurl .= "?auth=$idtoken" ;
	}
	
	$fevent = fopen( $marcusurl, 'r', false, stream_context_create(array(
		'http' => array(
			'method' => 'GET' ,
			'header' => 'Accept: text/event-stream' 
			)
	)));
	
	if( $fevent ) {
		$event = NULL ;
		$data = NULL ;
		// read one event
		while( ($line=fgets( $fevent, 1000000 )) !== false ) {
			$line = trim($line);
			if( strlen($line)<=0 ) {
				// empty line, end of data?
				if( !empty($event) && !empty( $data ) ) {
					if( is_callable($callback) ) {
						if( !call_user_func( $callback, $event, trim($data) ) ) {
							break;
						}
					}
					else {
						fclose( $fevent );
						return $data ;
					}
				}
				$data = NULL ;
				$event = NULL ;
				set_time_limit( 100 );
			}
			else if( !empty($data) ) {
				// append more data
				$data .= $line ;
			}
			else if( substr( $line, 0, 6) === "event:" ) {
				$event = trim( substr( $line, 6 ));
			}
			else if( substr( $line, 0, 5) === "data:" ) {
				$data = substr( $line, 5 );
			}
			else {
				$data = NULL ;
				$event = NULL ;
			}
		}
		fclose( $fevent );
		return true ;
	}
	return false ;
}

// exclusive lock for firebase op
$firebase_lockcount = 0 ;
$firebase_lockfile = false ;
function firebase_lock()
{
	global $firebase_lockcount, $firebase_lockfile ;
	if( $firebase_lockcount <= 0 ) {
		$firebase_lockfile = fopen( session_save_path().'/sess_firebaselock', 'c+' );
		flock( $lfile, LOCK_EX );
		$firebase_lockcount = 0 ;
	}
	$firebase_lockcount++ ;
}

function firebase_unlock()
{
	global $firebase_lockcount, $firebase_lockfile ;
	if( $firebase_lockfile ) {
		flock( $firebase_lockfile, LOCK_UN );
		if( --$firebase_lockcount <= 0 ) {
			fclose( $firebase_lockfile );
			$firebase_lockfile = false ;
		}
	}
}

?>