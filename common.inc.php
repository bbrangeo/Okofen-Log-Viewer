<?php

// Bootstrap

function bootstrap()
{
	set_time_limit(0);

	initDB();
}

function initDB()
{
	$GLOBALS['db'] = mysql_connect('localhost', 'root', '');
	if (!$GLOBALS['db'])
	{
		die('Impossible de se connecter : ' . mysql_error());
	}

	// Rendre la base de données foo, la base courante
	$db_selected = mysql_select_db('okofen', $GLOBALS['db']);
	if (!$db_selected)
	{
		die ('Impossible de sélectionner la base de données : ' . mysql_error());
	}

	mysql_set_charset('utf8', $GLOBALS['db']);
}
