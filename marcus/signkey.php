<?php
// signkey.php - Second step of user sign in 
//   Checking user password
// By Dennis Chen @ TME	 - 2013-07-12
// Copyright 2013 Toronto MicroElectronics Inc.

	$noredir = true ;
	$noclosesession = true ;	
	require_once 'session.php' ;

	header("Content-Type: application/json");
	
	if( empty( $_REQUEST ) ) {
		$_REQUEST = $_POST ;
	}
	
	$resp=array();
	$resp['res']=0 ;
	$resp['page']="#" ;

	$savesess=$_SESSION ;
	session_unset();
	
	if( !empty($savesess['xuser']) && !empty($_REQUEST['user']) && $savesess['xuser'] == $_REQUEST['user'] ) {
	    $ha1=$savesess['key'] ;
		$salt=$savesess['salt'] ;
		$nonce=$savesess['nonce'] ;
		$ha2=hash("md5", $_REQUEST['cnonce'] .":". $savesess['xuser'] .":". $nonce );
		$rescmp=hash("md5", $ha1 . ":" . $ha2 . ":" . $nonce . ":" . $_REQUEST['cnonce'] );
		if( $_REQUEST['result'] == $rescmp ) { // Matched!!!
		
			// what should be copied to new session
			$_SESSION['user']=$savesess['xuser'];
			$_SESSION['user_type']=$savesess['xuser_type'];
			if( !empty($savesess['clientid']) ) {
				$_SESSION['clientid']=$savesess['clientid'];
			}
			$_SESSION['welcome_name'] = $savesess['welcome_name'];
			$_SESSION['db'] = $savesess['db'];
			$_SESSION['timezone'] = $savesess['timezone'];
			$_SESSION['timeout'] = $savesess['timeout'] ;
			$_SESSION['xtime'] = $session_time ;
			$_SESSION['release']=file_get_contents("release") ;
			if( empty($_SESSION['release']) )
				$_SESSION['release'] = "V0.0.1" ;	// known last release
			$_SESSION['remote']=$_SERVER['REMOTE_ADDR'] ;
						
		    $resp['res']=1 ;
			$resp['user']=$_SESSION['user'] ;
			if( !empty($savesess['lastpage']) ) {
				$resp['page']=$savesess['lastpage'];
			}
			else {
				$resp['page']="dashboard.php" ;
			}
			
			// update user last login time
			$now = new DateTime() ;

			// reconnect MySQL (don't remove this one)
			@$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
		    $sql="UPDATE app_user SET last_logon = '".$now->format("Y-m-d H:i:s")."' WHERE user_name = '".$_SESSION['user']."';" ;
			$conn->query($sql);
		}
	}

	session_write_close();
	
	ignore_user_abort(true);
	ob_start();
	echo json_encode($resp);
	
	// forced to close connection
	header('Connection: close');
	header('Content-Length: '.ob_get_length());

	ob_end_flush();
	ob_flush();
	flush();
	
	if( $resp['res'] ) {
		
		//require_once 'firebase.php';
			
		// do some background task for this session
		$content = array( 
			'ts' => array( '.sv' => "timestamp" ),
			'user' => $resp['user'],
			'client' => $_SESSION['clientid'] );
		//firebase_rest( "units/logins", $content, 'POST' ) ;
	}

	// clean old session files
	$xtime = time() ;
	// clean old session files
	foreach (glob( session_save_path().'/*') as $filename) {
		$mtime = $xtime - filemtime($filename) ;
		if( $mtime > 86420 ) {
			@unlink($filename);
		}
		else if( $mtime > 7200 && filesize( $filename ) < 3 ) {
			@unlink($filename);
		}
	}

	return ;
?>