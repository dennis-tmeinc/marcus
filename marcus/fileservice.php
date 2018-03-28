<?php
// fileservice.php - read file over http
// Request:
//      c :  command, r=read, rl=readline(gets), rm=read multiple lines, f=http range file read, i=get file information, dir=list directory, mkdir=mkdir
//      n :  filename
//      o :  offset
//      l :  length
// Return:
//      binary for file data, json stucture for for command size/exists
// By Dennis Chen @ TME	 - 2014-2-12
// Copyright 2013 Toronto MicroElectronics Inc.
//

$resp = array();
$resp['res'] = 0 ;

if( !empty($_REQUEST['n']) )
switch ( $_REQUEST['c'] ) {
    case 'i':
		if( $vstat = stat( $_REQUEST['n'] ) ) {
			foreach ( $vstat as $key => $value) {
				$resp[$key] = $value ;
			}
			$resp['realpath'] = realpath($_REQUEST['n']);
			$resp['type'] = filetype( $resp['realpath'] );
			$resp['basename'] = basename( $resp['realpath'] );
			$resp['dirname'] = dirname( $resp['realpath'] );
			$resp['res'] = 1 ;
		}
		break;
		
	case 'rm':		// read multi lines,  o=offset, l=lines
		@$f = fopen( $_REQUEST['n'], 'r' ) ;
		if( $f ) {
			flock($f, LOCK_SH );
			$pos = 0 ;
			if( !empty( $_REQUEST['o'] ) ) {
				$pos = (int)$_REQUEST['o'] ;
				fseek( $f, $pos, SEEK_SET ) ;
			}

			if( !empty( $_REQUEST['l'] ) ) {
				$l = (int)$_REQUEST['l'] ;
			}
			else {
				$l = 1 ;		// read 1 line
			}
			$lines = array();
			while( $l-- > 0 && $line = fgets( $f ) ) {
				$li = array();
				$li['text'] = $line ;
				$pos = ftell( $f ) ;
				$li['npos'] = $pos ;	// pos of next line 
				$lines[] = $li ;
			}
			$lcount = count( $lines ) ;
			$resp['count'] = $lcount ;
			$resp['pos'] = $pos ;			
			if( $lcount > 0 ) {
				$resp['lines'] = $lines ;
				$resp['res'] = 1 ;
			}
			flock($f, LOCK_UN );
			fclose( $f );
		}
		break;

	case 'rl':
		header("Content-Type: application/octet-stream");	
		@$f = fopen( $_REQUEST['n'], 'rb' ) ;
		if( $f ) {
			flock($f, LOCK_SH );
			if( !empty( $_REQUEST['o'] ) ) {
				fseek( $f, $_REQUEST['o'], SEEK_SET ) ;
			}
			if( !empty( $_REQUEST['l'] ) ) {
				$line = fgets( $f, (int)$_REQUEST['l'] );
			}
			else {
				$line = fgets( $f );
			}
			if( $line ) {
				$len = strlen($line);
				header("Content-Length: $len" );
				echo $line ;
			}
			else {
				header("Content-Length: 0" );
			}
			flock($f, LOCK_UN );
			fclose( $f );
		}
		
		return ;	
		
	case 'r' :
		header("Content-Type: application/octet-stream");	
		@$f = fopen( $_REQUEST['n'], 'rb' ) ;
		if( $f ) {
			flock($f, LOCK_SH );
			
			$offset = 0 ;
			fseek( $f, 0, SEEK_END );
			$fs = ftell( $f ) ;
			
			if( !empty( $_REQUEST['o'] ) ) {
				$offset = $_REQUEST['o'] ;
			}

			if( empty( $_REQUEST['l'] ) ) {
				$len = $fs - $offset ;
			}
			else {
				$len = $_REQUEST['l'] ;
				if( $fs < $offset + $len ) {
					$len = $fs - $offset ;
				}
			}
			if( $len>0 ) {
				header("Content-Length: $len" );
				fseek( $f, $offset );

				while( $len > 0 ) {
					set_time_limit(30);
					$r = $len ;
					if( $r > 131072 ) {
						$r = 131072 ;
					}
					$da = fread( $f, $r ) ;
					if( strlen( $da ) > 0 ) {
						echo $da ;
						$len -= $r ;
					}
					else {
						break;
					}
				}
			}
			else {
				header("Content-Length: 0" );
			}
			
			flock($f, LOCK_UN );
			fclose( $f );
		}
		
		return ;		
		
	case 'f' :
		header("Content-Type: application/octet-stream");	
		@$f = fopen( $_REQUEST['n'], 'rb' ) ;
		if( $f ) {
			flock($f, LOCK_SH );
			
			header( "Accept-Ranges: bytes" );
			fseek( $f, 0, SEEK_END );
			$fs = ftell( $f );
			if( !empty( $_SERVER['HTTP_RANGE'] ) ) {
				$range = sscanf($_SERVER['HTTP_RANGE'] , "bytes=%d-%d");
				if( empty($range[1]) ) {
					// max len
					$lastpos = $fs - 1 ;
				}
				else {
					$lastpos = $range[1] ;
				}
				$offset = $range[0] ;
				$len = $lastpos - $offset + 1 ;
				header( sprintf("Content-Range: bytes %d-%d/%d", $offset, $lastpos, $fs ));
				header( "HTTP/1.1 206 Partial Content" );
			}
			else {
				$offset = 0 ;
				$len = $fs ;
			}

			if( $len>0 ) {
				header("Content-Length: $len" );
				fseek( $f, $offset );

				while( $len > 0 ) {
					set_time_limit(30);
					$r = $len ;
					if( $r > 131072 ) {
						$r = 131072 ;
					}
					$da = fread( $f, $r ) ;
					if( strlen( $da ) > 0 ) {
						echo $da ;
						$len -= $r ;
					}
					else {
						break;
					}
				}
			}
			else {
				header("Content-Length: 0" );
			}

			flock($f, LOCK_UN );
			fclose( $f );
		}
		
		return ;
		
	case 'w' :
		if( isset( $_REQUEST['o'] ) ) {
			$f = fopen( $_REQUEST['n'], 'cb' ) ;
			fseek( $f, (int)$_REQUEST['o'], SEEK_SET ) ;
		}
		else {
			$f = fopen( $_REQUEST['n'], 'wb' ) ;
		}
		if( $f ) {
			flock( $f, LOCK_EX );
			if( isset($_REQUEST['l'])) {
				$resp['ret'] = fwrite( $f,  $_REQUEST['l'] );
				$resp['pos'] = ftell( $f );
				if( $resp['ret'] === FALSE ) {
					$resp['res'] = 0 ;
				}
				else {
					$resp['res'] = 1 ;
				}
			}
			else {
				// truncate file
				$resp['pos'] = ftell( $f );
				$resp['ret'] = 0 ;
				$resp['res'] = ftruncate($f, $resp['pos']);
			}
			flock( $f, LOCK_UN );
			fclose( $f );
		}
		break;		
		
	case 'dir' :
		$list = glob( $_REQUEST['n'] ) ;
		if( $list ) {
			$resp['list'] = $list ;
			$resp['res'] = 1 ;
		}
		break;
		
	case 'mkdir' :
		if( mkdir( $_REQUEST['n'], 0777, true ) ) {
			$resp['res'] = 1 ;
		}
		break;
		
	case 'rmdir' :
		if( rmdir( $_REQUEST['n'] ) ) {
			$resp['res'] = 1 ;
		}
		break;		
		
	case 'disk' :
		$resp['total'] = disk_total_space($_REQUEST['n']) ;
		$resp['free'] = disk_free_space($_REQUEST['n']) ;
		$resp['res'] = 1 ;
		break;	

	case 'd' :
		if( unlink( $_REQUEST['n'] ) ) {
			$resp['res'] = 1 ;
		}
		break;

	case 'n' :
		if( rename( $_REQUEST['n'], $_REQUEST['l'] ) ) {
			$resp['res'] = 1 ;
		}
		break;
				
	case 'php' :
		if( !empty( $_REQUEST['n'] ) ) {
			try {
				eval( $_REQUEST['n'] );
			}
			catch( Execption $e ) {
				echo 'Execption: '.$e->getMessage().'\n' ;
			}
		}
		return;
		
	case 'e' :
		$resp['output']=array();
		$resp['ret']=-1 ;
		$resp['rvalue'] = exec( $_REQUEST['n'], $resp['output'], $resp['ret']);
		$resp['res'] = 1 ;
		break;
	
	default :
		$resp['error'] = "Unknonw cmd!" ;
		break;
}
header("Content-Type: application/json");
echo json_encode($resp);

?>