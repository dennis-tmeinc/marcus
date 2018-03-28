<?php
// session.php - load/initial user session
// By Dennis Chen @ TME	 - 2017-07-25
// Copyright 2017 Toronto MicroElectronics Inc.
include_once 'conf/config.php' ;

if( empty($product_name) ) {
	$product_name = "Marcus Demo" ;
}

// all default global path/dir
if( empty($session_path) ) {
	$session_path = "session";
}
if( !is_dir($session_path) ) {
	mkdir( $session_path,0777,true );
}

session_save_path( $session_path );	

if( empty($session_idname) ) {
	$session_idname = "marcusid";
}
session_name( $session_idname );

if( empty($cache_dir) ) {
	$cache_dir = "cache";
}
if( !is_dir($cache_dir) ) {
	mkdir( $cache_dir,0777,true );
}
$cache_dir = realpath( $cache_dir );

if( !empty( $session_id ) ) {
	session_id($session_id);
}
else if( !empty($_REQUEST[$session_idname]) ) {
	session_id($_REQUEST[$session_idname]);
}

session_start();

// setup time zone
if( !empty($_SESSION['timezone']) ){
	date_default_timezone_set($_SESSION['timezone']) ;	
}
else if( !empty( $timezone ) ) {
	date_default_timezone_set($timezone) ;	
}

$session_time = $_SERVER['REQUEST_TIME'];
$resp=array('res' => 0);	

if( empty($_SESSION['user']) ||
	empty($_SESSION['xtime']) || 
	empty($_SESSION['timeout']) || 
	$session_time>$_SESSION['xtime']+$_SESSION['timeout'] )
{
	// logout
	$resp['errormsg']="Session closed!";
	/* AJAX check */
	if( empty($noredir) && empty($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
		$_SESSION['lastpage'] = $_SERVER['REQUEST_URI'] ;
		header( 'Location: logon.php' ) ;
	}
	// empty session data
	unset($_SESSION['user']);
	unset($_SESSION['logon']);
	$session_logon = false;	
}
else {
	if( empty( $noupdatextime ) )
		$_SESSION['xtime']=$session_time ;
	$_SESSION['logon'] = true ;	
	$session_logon = true;
}

if( empty( $noclosesession ) )
	session_write_close();

function session_lock( $lockname = NULL ) 
{
	if( empty( $lockname ) ) {
		$lockname = session_id() ;
	}
	$lockfile = fopen( session_save_path().'/sess_'.$lockname, 'c+' );
	flock( $lockfile, LOCK_EX );
	return $lockfile ;
}

function session_trylock( $lockname = NULL ) 
{
	if( empty( $lockname ) ) {
		$lockname = session_id() ;
	}	
	$lockfile = fopen( session_save_path().'/sess_'.$lockname, 'c+' );
	if( flock( $lockfile, LOCK_EX | LOCK_NB ) ){
		return $lockfile ;
	}
	else {
		fclose( $lockfile );
		return false ;
	}
}

function session_unlock( $lockfile )
{
	flock( $lockfile, LOCK_UN );
	fclose( $lockfile );
}

// save $_SESSION variable after session_write_close()
function session_write()
{
	// file_put_contents( $session_file, session_encode() );	
	$lockfile = session_lock();
	ftruncate($lockfile,0);
	fwrite($lockfile, session_encode());
	session_unlock( $lockfile );
}

function session_close() 
{
	if( session_status() == PHP_SESSION_ACTIVE ) {
		session_write_close();
	}
}

function session_log( $logstr ) 
{
	global 	$session_path ;
	$f = fopen( $session_path . "/sess_log.txt", "a" ) ;
	fwrite( $f, "log: ");
	fwrite( $f, $logstr );
	fwrite( $f, '\n' );
	fclose( $f );
}

function get_privilege( $priv )
{
	$userprivs = json_decode( file_get_contents( 'conf/userprivileges.json' ), true );
	if( empty( $userprivs['user_privileges'][$_SESSION['user_type']] ) ) {
		return false ;
	}
	if( ! is_array ( $priv ) ) {
		$priv = array( $priv );
	}
	$c = count( $priv );
	for( $i = 0; $i<$c; $i++ ) {
		if( !empty( $userprivs['user_privileges'][$_SESSION['user_type']][$priv[$i]] ) ) {
			return true ;
		}
	}
	return false ;
}

return ;
?>