<?php
// session.php - load/initial user session
// By Dennis Chen @ TME	 - 2017-07-25
// Copyright 2017 Toronto MicroElectronics Inc.

function array_to_xml($array, &$xml) {
	foreach($array as $key => $value) {
		if(is_array($value) || is_object($value)) {
			$subnode = $xml->addChild("$key");
			array_to_xml($value, $subnode);
		}
		else {
			$xml->addChild("$key",htmlspecialchars("$value"));
		}
	}
}

?>