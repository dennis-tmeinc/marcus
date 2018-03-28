<?php
// signuser.php - First step of user sign in 
//   Load user info, create password hash
//   parameter:
//		user: userid, c: clientid, n: nonce
// By Dennis Chen @ TME	 - 2014-04-23
// Copyright 2017 Toronto MicroElectronics Inc.
$noredir = 1 ;
$noclosesession = true ;
require 'session.php' ;

if( empty( $_REQUEST ) ) {	// don't know why yet, _REQUEST not included _POST for a while?
	$_REQUEST = $_POST ;
}

if( !empty($_REQUEST['c']) ) {
	@$_SESSION['clientid'] = $_REQUEST['c'];
}

// main database
@$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $sql_database );

// escaped string for SQL
$esc_req=array();
foreach( $_REQUEST as $key => $value )
{
	$esc_req[$key]=$conn->escape_string($value);
}	

$db = $sql_database ;
if( !empty($_SESSION['clientid']) ) {
	$cliendid = $conn->escape_string($_SESSION['clientid']);
	$sql = "SELECT * FROM security WHERE ClientId = '$cliendid' " ;
	if( $result=$conn->query($sql) ) {
		if( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
			$db = $row['DBName'] ;
		}
	}
	else {
		$resp['errormsg'] = "Client ID error!" ;
		goto done;		
	}
}

// where to get timezone now?
$_SESSION['timezone'] = null ;

// where to get session time out value ?
if( !empty($session_timeout) ) {
	$_SESSION['timeout'] = $session_timeout ;
}
else {
	$_SESSION['timeout'] = 900 ;
}

// reconnect to new db if necessary
if( $db != $sql_database ) {
	@$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $db );
}

if( empty($conn) ) {
	$resp['errormsg'] = "Database error!" ;
	goto done;
}
$_SESSION['db'] = $db ;

function genRandstr( $len ) 
{
	$rstr = '' ;
	$hexchar="0123456789abcdefghijklmnopqrstuvwxyz" ;
	for( $i=0; $i<$$len; $i++) {
		$rstr[$i] = $hexchar[mt_rand(0,35)] ;
	}
	return $rstr ;
}


$user = $conn->escape_string($_REQUEST['user']);
$sql="SELECT * FROM app_user WHERE user_name = '$user';" ;

$resp['errormsg'] = "Wrong User ID!" ;
if( $result=$conn->query($sql)) {
	if( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
		
		$_SESSION['xuser'] = $row['user_name']  ;
		$_SESSION['xuser_type'] = $row['user_type'] ;
		if( empty($row['real_name']) )
			$_SESSION['welcome_name']=$row['user_name'] ;
		else
			$_SESSION['welcome_name'] = $row['real_name'] ;
		$nonce=genRandstr(32);
		$_SESSION['nonce'] = $nonce ;

		if( strlen( $row['user_password'] ) > 25 && $row['user_password'][0] == '$' ) {
			$keys=explode('$', $row['user_password'] );
			$_SESSION['key'] = $keys[2] ;
			$_SESSION['salt'] = $keys[3] ;
		}
		else {
			$salt=genRandstr(8);
			$_SESSION['salt'] = $salt ;
			$_SESSION['key'] = hash("md5", $row['user_name'].":".$salt.":".$row['user_password']);
		}


		unset($resp['errormsg']);
		$resp['user']=$_SESSION['xuser'] ;
		$resp['slt']=$_SESSION['salt'] ;
		$resp['keytype']=1 ;
		$resp['nonce']=$_SESSION['nonce'] ;
		$resp['res']=1 ;
	}
	$result->free();
}

done:	
	header("Content-Type: application/json");
	echo json_encode($resp) ;
?>