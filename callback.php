<?php
/**
 * Set the callback variable to what jQuery sends over
 */
$callback = (string)$_GET['callback'];
if (!$callback) $callback = 'callback';

 
// connect to MySQL
require_once('common.inc.php');
bootstrap();



$start = @$_GET['start'];
if ($start && !preg_match('/^[0-9]+$/', $start)) {
	die("Invalid start parameter: $start");
}
$end = @$_GET['end'];
if ($end && !preg_match('/^[0-9]+$/', $end)) {
	die("Invalid end parameter: $end");
}
if (!$end) $end = time() * 1000;

$startTime = gmstrftime('%Y-%m-%d %H:%M:%S', $start / 1000);
$endTime = gmstrftime('%Y-%m-%d %H:%M:%S', $end / 1000);

$value = $_GET['value'];
switch ($value)
{
	case 'TChaudiere':	      $sql = "SELECT unix_timestamp(Datum) * 1000, KTIst"; break;
	case 'TDepart':           $sql = "SELECT unix_timestamp(Datum) * 1000, HK1VLIst"; break;
	case 'ECS1 T demarrage':  $sql = "SELECT unix_timestamp(Datum) * 1000, WW1EinTIst"; break; // DHW1 On Temperature 
	case 'PE1 T flamme':	    $sql = "SELECT unix_timestamp(Datum) * 1000, PE1FRTIst / 6"; break; // PE1 Comb Chamber T
	
	case 'TInt': 	$sql = "SELECT unix_timestamp(Datum) * 1000, HK1RTIst"; break;
	default:
	case 'TExt': 	$sql = "SELECT unix_timestamp(Datum) * 1000, AT"; break;
}

$sql = $sql . " FROM data ";

if (!empty($_GET['start']) && !empty($_GET['end']))
	$sql = $sql . " WHERE Datum BETWEEN '$startTime' AND '$endTime'";

$sql = $sql . " ORDER BY Datum"; //"								LIMIT 0, 5000";
	
if ($value == 'Pellets')
{
	$sql = "SELECT unix_timestamp(MAX(Datum)) * 1000, COUNT(*) * 0.37789 
					FROM data
					WHERE PE1MotorRA = 1
					GROUP BY TO_DAYS(Datum)"; // Implicit order by when grouping
}

// Send the output
header('Content-Type: text/javascript');
echo "$callback([";

$result = mysql_query($sql);
$bFirst = true;

while ($row = mysql_fetch_array($result, MYSQL_NUM)) 
{
	if (!$bFirst) echo ',';
	echo '[' . implode(',', $row) . ']'; 
	$bFirst = false;
}

echo "]);";
