<?php
// servicelog.php  - Log Mservice activities
// Requests:
//		logstring
// By Dennis Chen @ TME	 - 2017-09-07
// Copyright 2017 Toronto MicroElectronics Inc.

function service_log( $logstring ) 
{
	static $log = false ;
	if( ! $log ) {
		$log = fopen( session_save_path() . "/servicelog.txt", 'c+' ) ;
		if( !$log ) 
			return ;
		flock( $log, LOCK_EX ) ;
		fseek( $log, 0, SEEK_END );
		$l = ftell( $log ) ;
		if( $l > 200000 ) {
			fseek( $log, -100000, SEEK_END );
			// skip a line
			fgets( $log );

			// rotate log
			$buf = fread( $log, 120000 ) ;
			if( $buf ) {
				fseek( $log, 0, SEEK_SET );
				ftruncate( $log, 0 );
				fwrite( $log, $buf );
			}
		}
	}
	else {
		flock( $log, LOCK_EX ) ;
		fseek( $log, 0, SEEK_END );
	}

	fwrite( $log, $logstring . "\n" );
	flock( $log, LOCK_UN) ;
}	
