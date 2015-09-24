<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of okofen
 *
 * @author Sabine
 */
class okofen
{
  private $quantity_per_minute = 0;
  private $size = 0;
  private $quantity_per_month = array();
  private $SiloDataCalculated = false;
  private $SiloStatusCalculated = false;
  private $quantity_per_month_year = array();
  private $heating_per_month_year = array();
  private $hot_water_per_month_year = array();
  
  //put your code here
  
  /**
   * Set up the DB, etc...
   */
  public function init()
  {
    // connect to MySQL
    require_once('common.inc.php');
    bootstrap();

    // load the configuration variables for the silo
    $this->size = $GLOBALS['conf']['silo']['size']; // in kg
  }
  
  private function calculateSiloConsumptionData()
  {
    if ($this->SiloDataCalculated)
      return;
    
    $this->SiloDataCalculated = true;
    
    $this->calculateWoodQuantityPerWormDriveMinute();
    $this->calculateAverageConsumptionPerMonth();  
    
    $this->calculateConsumptionPerMonth();
  }
  
  /**
   * Find the last time the silo has been completely emptied, 
   * and calculate how many minutes were used between this date and
   * the last time the silo has been filed before.
   * 
   * Use this to calculate how much a minute consumes.
   */
  private function calculateWoodQuantityPerWormDriveMinute()
  {
    $sql = "SELECT fill_date 
            FROM pellets 
            WHERE was_empty=1 
            ORDER BY fill_date 
            DESC LIMIT 1";
    
    $result = mysql_query($sql);
    $row = mysql_fetch_assoc($result);
    if (empty($row))
    {
      // Not enough data yet
      return false;
    }      

    $end_date = $row['fill_date'];
      
    // Now get the last time the silo has been filed before that:
    $sql = "SELECT fill_date 
            FROM pellets 
            WHERE fill_date < '$end_date' 
            ORDER BY fill_date 
            DESC LIMIT 1";      

    $result = mysql_query($sql);
    $row = mysql_fetch_assoc($result);
    if (empty($row))
    {
      // Not enough data yet
      return false;
    }      

    $start_date = $row['fill_date'];

    $sql = "SELECT COUNT(*) AS nb_minutes_of_worm_drive
            FROM data
            WHERE PE1MotorRA = 1
            AND Datum BETWEEN '$start_date' AND '$end_date'";
    $result = mysql_query($sql);
    $row = mysql_fetch_assoc($result);
    if (empty($row))
    {
      // Not enough data yet
      return false;
    }
    
    $nb_minutes_of_worm_drive = $row['nb_minutes_of_worm_drive'];
    
    $this->quantity_per_minute = $this->size / $nb_minutes_of_worm_drive;

    return true;
  }
  
  
  /**
   * Builds an average usage per month, so that we can tell how much
   * minutes we are likely to consume in the next few weeks.
   */
  private function calculateAverageConsumptionPerMonth()
  {
    // Now lets calculate the average quantity of wood used per month 
    // (we take the last 12 months, not counting the current month)
    $sql = "SELECT YEAR(Datum) AS y, MONTH(Datum) AS m, COUNT(*) AS nb_minutes_of_worm_drive
            FROM data
            WHERE PE1MotorRA = 1
            AND Datum < '".date('Y-m-1')."'
            GROUP BY YEAR(Datum), MONTH(Datum)
            ORDER BY y DESC, m DESC";
    
    $result = mysql_query($sql);
    $minutes_per_month = array();

    $this->quantity_per_month = array();
    while ($row = mysql_fetch_assoc($result))
    {
      $month = $row['m'];
      $minutes = $row['nb_minutes_of_worm_drive'];
      if (empty($this->quantity_per_month[$month]))
        $this->quantity_per_month[$month] = $minutes * $this->quantity_per_minute;
    }

    for ($m = 1; $m <= 12; $m++)
    {
      if (!isset($this->quantity_per_month[$m]))
      {
        if ($m == 1) $prevMonth = 12; else $prevMonth = $m - 1;
        if ($m == 12) $nextMonth = 1; else $nextMonth = $m + 1;
        
        if (isset($this->quantity_per_month[$prevMonth]) &&
            isset($this->quantity_per_month[$nextMonth]))
        {
          $this->quantity_per_month[$m] = ($this->quantity_per_month[$prevMonth]+$this->quantity_per_month[$nextMonth]) / 2;
        }
        else if (isset($this->quantity_per_month[$prevMonth]))
          $this->quantity_per_month[$m] = $this->quantity_per_month[$prevMonth];
        else if (isset($this->quantity_per_month[$nextMonth]))
          $this->quantity_per_month[$m] = $this->quantity_per_month[$nextMonth];
        else
          $this->quantity_per_month[$m] = 0;
      }
    }

    return true;
  }
  
  /**
   * Calculate how many minutes have been used since the last
   * time the silo has been filed
   */
  private function calculateCurrentSiloStatus()
  {
    if ($this->SiloStatusCalculated)
      return;
        
    $this->SiloStatusCalculated = true;
        
    // Get the last time the silo has been filed:
    $sql = "SELECT fill_date 
            FROM pellets 
            ORDER BY fill_date 
            DESC LIMIT 1";      

    $result = mysql_query($sql);
    $row = mysql_fetch_assoc($result);
    if (empty($row))
    {
      // Not enough data yet
      return false;
    }      
    
    $fill_date = $row['fill_date'];

    // Now get how many minutes we've been consuming
    $sql = "SELECT COUNT(*) AS nb_minutes_of_worm_drive
            FROM data
            WHERE PE1MotorRA = 1
            AND Datum >=  '$fill_date'";
    $result = mysql_query($sql);
    $row = mysql_fetch_assoc($result);
    if (empty($row))
    {
      // Not enough data yet
      return false;
    }
    
    $nb_minutes_of_worm_drive = $row['nb_minutes_of_worm_drive'];
    $this->woodUsed = $nb_minutes_of_worm_drive * $this->quantity_per_minute;
    
    // What is the last known minute ?
    $sql = "SELECT YEAR(Datum) AS y, MONTH(Datum) AS m, DAY(Datum) AS d
            FROM data
            ORDER BY Datum DESC LIMIT 1";
    $result = mysql_query($sql);
    $row = mysql_fetch_assoc($result);
    if (empty($row))
    {
      // Not enough data yet
      return false;
    }        
    
    $today = $row;
    $woodLeft = $this->size - $this->woodUsed;

    while ($woodLeft > 0)
    {
      if (isset($today['d']))
      {
        $woodThisMonth = ((30 - $today['d'])/30) * $this->quantity_per_month[$today['m']];
        unset($today['d']);
      }
      else // next months
        $woodThisMonth = $this->quantity_per_month[$today['m']];
      
      $woodLeft -= $woodThisMonth;
      
      if ($woodLeft <= 0)
      {
        $today['d'] = 1 + round(30 * (-1 * $woodLeft) / $this->quantity_per_month[$today['m']]);
        break;
      }
      $today['m']++;
      
      if ($today['m'] == 13)
      {
        $today['m'] = 12;
        $today['y']++;
      }
    }
    
    $this->estimatedFillDate = new DateTime();
    $this->estimatedFillDate->setDate($today['y'], $today['m'], $today['d']);
  }
  
  /**
   * Calculate how many minutes have been used since the last
   * time the silo has been filed
   * 
   * returns the percentage of the silo left.
   */
  public function getCurrentSiloStatus()
  {
    $this->calculateSiloConsumptionData();
    $this->calculateCurrentSiloStatus(); 
    
    return array(
        'current_kg' => $this->size - $this->woodUsed,
        'current_t' => round(($this->size - $this->woodUsed) / 100) / 10,
        'current_%' => round(100 * ($this->size - $this->woodUsed) / $this->size),
        'estimatedFillDate' => $this->estimatedFillDate
    );
  }
  
  

  /**
   * Builds a usage per month
   */
  private function calculateConsumptionPerMonth()
  {
    // Lets calculate the quantity of wood used per month 
    $sql = "SELECT YEAR(Datum) AS y, MONTH(Datum) AS m, COUNT(*) AS nb_minutes_of_worm_drive
            FROM data
            WHERE PE1MotorRA = 1
            GROUP BY YEAR(Datum), MONTH(Datum)
            ORDER BY y ASC, m ASC";
    
    $result = mysql_query($sql);
    $minutes_per_month = array();

    $this->quantity_per_month_year = array();
    while ($row = mysql_fetch_assoc($result))
    {
      $year = $row['y'];
      $month = $row['m'];
      $minutes = $row['nb_minutes_of_worm_drive'];
      if (empty($this->quantity_per_month_year[$year][$month]))
        $this->quantity_per_month_year[$year][$month] = $minutes * $this->quantity_per_minute;
    }


    $sql = "SELECT YEAR(Datum) AS y, MONTH(Datum) AS m, COUNT(*) AS nb_minutes_of_ww_pumpe
            FROM data
            WHERE WW1Pumpe = 1
            GROUP BY YEAR(Datum), MONTH(Datum)
            ORDER BY y ASC, m ASC";
    
    $result = mysql_query($sql);
    $minutes_per_month = array();

		$max = 0;

    $this->hot_water_per_month_year = array();
    while ($row = mysql_fetch_assoc($result))
    {
      $year = $row['y'];
      $month = $row['m'];
      $minutes = $row['nb_minutes_of_ww_pumpe'];
      if (empty($this->hot_water_per_month_year[$year][$month]))
        $this->hot_water_per_month_year[$year][$month] = $minutes;
        
      if ($max < $minutes)
      	$max = $minutes;
    }

		$this->percentize($this->hot_water_per_month_year, $max);

    $sql = "SELECT YEAR(Datum) AS y, MONTH(Datum) AS m, COUNT(*) AS nb_minutes_of_hk_pumpe
            FROM data
            WHERE HK1Pumpe = 1
            GROUP BY YEAR(Datum), MONTH(Datum)
            ORDER BY y ASC, m ASC";
    
    $result = mysql_query($sql);
    $minutes_per_month = array();
		$max = 0;

    $this->heating_per_month_year = array();
    while ($row = mysql_fetch_assoc($result))
    {
      $year = $row['y'];
      $month = $row['m'];
      $minutes = $row['nb_minutes_of_hk_pumpe'];
      if (empty($this->heating_per_month_year[$year][$month]))
        $this->heating_per_month_year[$year][$month] = $minutes;
        
      if ($max < $minutes)
      	$max = $minutes;        
    }
		$this->percentize($this->heating_per_month_year, $max);

    return true;
  }
  
	private function percentize(&$theArray, $max)
	{
		foreach ($theArray as $y => $subArray)
		{
			foreach ($subArray as $m => $v)
				$theArray[$y][$m] = 100 * $v / $max;
		}
	}
	
  private function buildUsageCalendar($data, $unit, $colorscale = false)
  {
  	$str = '<table>';

		$str .= "<tr><td>Year</td>";

		$months = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec');

		$colors = array(100 => '#57BB8A',
										 90 => '#67C195',
										 80 => '#78C8A1',
										 70 => '#89CFAD',
										 60 => '#9AD6B8',
										 50 => '#ABDDC4',
										 40 => '#BBE3D0',
										 30 => '#CCEADB',
										 20 => '#DDF1E7',
										 10 => '#EEF8F3',
										  0 => '#FFFFFF');

		foreach ($months as $k => $name)
		{
			$str .= "<td style=\"width:70px; text-align: right\">$name</td>";
		}

		$str .= "</tr>";


  	foreach ($data as $year => $monthsUsage)
  	{
  		$str .= "<tr><td>$year</td>";

			foreach ($months as $m => $name)
  		{
  			if (isset($monthsUsage[$m]))
  				$qty = number_format($monthsUsage[$m], 0, ',', ' ');
  			else
	  			$qty = 0;
	  			
	  		if ($colorscale)
					$color = $colors[round($qty / 10) * 10];
	  		else
	  			$color = $colors[0];
	  			
	  		$str .= "<td style=\"background-color: $color;width:70px; text-align: right\">$qty $unit</td>";
	  	}
	  	
	  	$str .= "</tr>";
  	}
  	
  	$str .= "</table>";
  	
  	return $str;
  }  
  
  public function buildUsageCalendarTable($type = 'pellets')
  {
  	switch ($type)
  	{
  		case 'pellets': return $this->buildUsageCalendar($this->quantity_per_month_year, ' kg');
  		case 'heating': return $this->buildUsageCalendar($this->heating_per_month_year, '%', true);
  		case 'hot_water': return $this->buildUsageCalendar($this->hot_water_per_month_year, '%', true);
  	}
  	
  }
}
