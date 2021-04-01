<?php

function env_parser($filename) {
    $content = file_get_contents($filename);
    if (!$content)
	return;

    $lines = explode("\n", $content);
    foreach($lines as $line) {
	$line = trim($line);
	if (empty($line) || $line[0] == '#')
	    continue;
	$parts = explode('=', $line);
	if (count($parts) != 2)
	    continue;
	$var = trim($parts[0]);
	$val = trim($parts[1]);

	//is val quoted ?
	$vlen = strlen($val);
    	if (($val[0] === '"' && $val[$vlen-1] === '"') || ($val[0] === '\'' && $val[$vlen-1] === '\''))
	    $val = substr($val, 1, $vlen-2);

	switch($val) {
	    case 'true':
		$val = true;
		break;
	    case 'false':
		$val = false;
		break;
	}

	define($var, $val);
//	echo "$var = $val\n";
    }
}
