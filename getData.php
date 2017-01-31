<?php
function getDataSql(  $range ) {
		$datetime_from = time() - $range;
		$datetime_to   = time();

		$rangeSelector = "DATE_FORMAT(`date`,'%Y-%c-%d %H:%i:00')";
		if ( strtotime( $datetime_to ) - strtotime( $datetime_from ) > 60 * 60 * 25 ) {
			$rangeSelector = "concat(DATE_FORMAT(`date`,'%Y-%c-%d %H:'),TIME_FORMAT(SEC_TO_TIME(((DATE_FORMAT(`date`,'%i') div 15)*15)*60),'%i'),':00') ";
		}
		if ( strtotime( $datetime_to ) - strtotime( $datetime_from ) > 60 * 60 * 24 * 8 ) {
			$rangeSelector = "DATE_FORMAT(`date`,'%Y-%c-%d %H:00:00')";
		}
		if ( strtotime( $datetime_to ) - strtotime( $datetime_from ) > 60 * 60 * 24 * 31 ) {
			$rangeSelector = "DATE_FORMAT(`date`,'%Y-%c-%d 00:00:00')";
		}
		if ( strtotime( $datetime_to ) - strtotime( $datetime_from ) > 60 * 60 * 24 * 31 * 12 ) {
			$rangeSelector = "DATE_FORMAT(`date`,'%Y-%c-00 00:00:00')";
		}
		$datetime_from = DB::mysqlTime( $datetime_from );
		$datetime_to   = DB::mysqlTime();

		$query = "SELECT channel_id,`value`,`max`,`min`,`date`, stat FROM (
				SELECT CONCAT({$rangeSelector},channel_id) AS `period_key`,channel_id,TRUNCATE(AVG(`value`),2)AS `value`,TRUNCATE(MAX(`value`),2)AS `max`,TRUNCATE(MIN(`value`),2)AS `min`,{$rangeSelector} AS `date` , stat
				FROM ad4eth_values FORCE INDEX (`chan`) 
				WHERE  `date`>'{$datetime_from}' AND `date`<'{$datetime_to}' 
				GROUP BY period_key
			) as tmp;";

		return $query;
	}
  
    ini_set('memory_limit', -1);
		$query = Ad4eth::getDataSql($channelIds,$range);
		$rychlostVetraChannel = $channelIds[0];
		$smerVetraChannel = $channelIds[1];
    	$res = DB::query($query);
		$data = array();
		for (; $tmp = $res->fetch_object();) {
			$data[] = $tmp;
		}
		require_once __DIR__.'/graphRenderer.php';
