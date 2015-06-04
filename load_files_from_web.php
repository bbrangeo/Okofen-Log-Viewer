<?php
// Will load all CSV files that are in inputfiles
// Once loaded the files are copied in backup.

require_once('common.inc.php');

bootstrap();

// Note: only the last 4 days are stored on the heater - so cron this at regular interval...
echo 'Copying files from the server<br>';

CopyFilesFromWeb();

echo '<br>ok';

function CopyFilesFromWeb()
{
	// First copy the files from the web
	$indexpage = file('http://'.$GLOBALS['conf']['Okofen_IP'].'/logfiles/pelletronic/');

	foreach ($indexpage as $line)
	{
		$matches = array();

		if (preg_match('@[^/]+(/logfiles/pelletronic/touch_[0-9]+.csv).*@', $line, $matches))
			copy ('http://'.$GLOBALS['conf']['Okofen_IP'] . $matches[1], 'inputfiles/' . basename($matches[1]));
	}
}

// Very ugly bug it's just to get things working
include('load_files_from_USB.php');

