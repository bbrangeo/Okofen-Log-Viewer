<?php

  require_once 'okofen.php';

  $silo = new okofen();
  $silo->init();
  
  $siloData = $silo->getCurrentSiloStatus();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Oköfen</title>
</head>

<body>
<fieldset style="width: 300px"><legend>Silo</legend>
    <span style="font-size: 50px"><?php echo $siloData['current_%']; ?>%</span> <span>(reste <?php echo $siloData['current_t']; ?> t)</span><br/>
    <span style="font-style: italic">Prochain remplissage: <?php echo $siloData['estimatedFillDate']->format('d/m/Y'); ?></span>
</fieldset>

<fieldset style="width: 935px"><legend>Pelets</legend>
    
    <?php echo $silo->buildUsageCalendarTable('pellets'); ?>
    
</fieldset>
<fieldset style="width: 935px"><legend>Heating</legend>
    
    <?php echo $silo->buildUsageCalendarTable('heating'); ?>
    
</fieldset>
<fieldset style="width: 935px"><legend>Hot water</legend>
    
    <?php echo $silo->buildUsageCalendarTable('hot_water'); ?>
    
</fieldset>
</body>
</html>
