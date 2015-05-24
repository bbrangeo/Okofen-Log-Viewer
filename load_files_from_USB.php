<?php
// Will load all CSV files that are in inputfiles
// Once loaded the files are copied in backup.

require_once('common.inc.php');
bootstrap();

if (!is_dir('backup'))
	mkdir ('backup');

ImportFiles();

echo '<br>ok';

function ImportFiles()
{
	$files = glob('inputfiles/*.csv');
	foreach ($files as $afile)
	{
		echo $afile . '<br>';

		$rows = file($afile);
		$header_row = array_shift($rows);

		foreach ($rows as $row)
		{
			$data = explode(';', $row);
			$dummy = array_pop($data); // remove the last item - empty
			$date = array_reverse(explode('.', array_shift($data)));

			$time = array_shift($data);

			$dt = implode('-', $date) . ' ' . $time;
			array_unshift($data, $dt);

			$sql = "REPLACE INTO data (Datum, AT, KTIst, KTSoll, BR, HK1VLIst, HK1VLSoll, HK1RTIst, HK1RTSoll, HK1Pumpe, WW1EinTIst, WW1AusTIst, WW1Soll, WW1Pumpe, Zubrp1Pumpe, PE1KT, PE1FRTIst, PE1FRTSoll, PE1Einschublaufzeit, PE1Pausenzeit, PE1Luefterdrehzahl, PE1Saugzugdrehzahl, PE1UnterdruckIst, PE1UnterdruckSoll, PE1Status, PE1MotorES, PE1MotorRA, PE1MotorRES1, PE1MotorTURBINE, PE1MotorZUEND, PE1MotorUW, PE1MotorAV, PE1MotorRES2, PE1MotorMA, PE1MotorRM, PE1MotorSM)
					VALUES
					('".implode("', '", $data)."')";

			$result = mysql_query($sql, $GLOBALS['db']);
			if (!$result)
			    die('Requête invalide : ' . $sql . '<br>' . mysql_error());
		}

		rename($afile, str_replace('inputfiles', 'backup', $afile));
	}
}
