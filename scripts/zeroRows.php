<?php
require_once("scriptsConfig.php");
$bu = new BidderUtils;
$cCntTables = array(
	'cCntDay',
	'cCntHour',
	'cCntMinute',
	'cCntMonth',
	'cCntYear',
);
$cntTables = array(
	'cntDay',
	'cntHour',
	'cntMinute',
	'cntMonth',
	'cntYear',
	'exCntDay',
	'exCntHour',
	'exCntMinute',
	'exCntMonth',
	'exCntYear',
	'plCntDay',
	'plCntHour',
	'plCntMinute',
	'plCntMonth',
	'plCntYear',
);

$cntMetrics = array(
	'bidRequests',
	'bids',
	'wins',
	'cost',
	'views',
	'clicks',
	'revenue',
);
$cCntMetrics = $cntMetrics;
array_shift($cCntMetrics);

$conds = array();
foreach ( $cntMetrics as $cntMetric )
	$conds[] = "($cntMetric = 0 or $cntMetric is null)";
$cConds = $conds;
array_shift($cConds);
$conds = implode(" and ", $conds);
$cConds = implode(" and ", $cConds);

$mm = new Mmodel;
foreach ( $cCntTables as $cCntTable ) {
	$now = date("Y-m-d G:i:s");
	/*	echo "$now: $cCntTable\n";	*/
	$sql = "select count(*) from $cCntTable where $cConds";
	$cnt = $mm->getInt($sql);
	$s = $cnt == 1 ? "" : "s";
	echo "$cCntTable: $cnt empty row$s\n";
	if ( $cnt !== 0 ) {
		echo "mys -v 'select * from $cCntTable where $cConds'\n";
		echo "mys 'delete from $cCntTable where $cConds'\n";
	}
	echo "---------------------------------------\n";
}

foreach ( $cntTables as $cntTable ) {
	$now = date("Y-m-d G:i:s");
	/*	echo "$now: $cntTable\n";	*/
	$sql = "select count(*) from $cntTable where $conds";
	$cnt = $mm->getInt($sql);
	$s = $cnt == 1 ? "" : "s";
	echo "$cntTable: $cnt empty row$s\n";
	if ( $cnt !== 0 ) {
		echo "mys -v 'select * from $cntTable where $conds'\n";
		echo "mys 'delete from $cntTable where $conds'\n";
	}
	echo "---------------------------------------\n";
}
