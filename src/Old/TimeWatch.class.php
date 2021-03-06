<?php
/*------------------------------------------------------------*/
class TimeWatch extends BidderUI {
	/*------------------------------------------------------------*/
	protected function before() {
		parent::before();
		date_default_timezone_set("Asia/Jerusalem");
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	public function index() {
		$this->show();
	}
	/*------------------------------------------------------------*/
	public function export() {
		$user = $this->loginName;
		$month = $_REQUEST['month'];
		$conds = "user = '$user' and month = '$month'";
		$fields = array(
			'null as weekday',
			'date',
			'timeIn',
			'timeOut',
			'timeIn2',
			'timeOut2',
			'timeIn3',
			'timeOut3',
		);
		$fieldList = implode(", ", $fields);
		$sql = "select $fieldList from timewatch where $conds order by date";
		$rows = $this->Mmodel->getRows($sql);
		foreach ($rows as $key => $row ) {
			$rows[$key]['weekday'] = date("D", strtotime($row['timeIn']));
			$rows[$key]['timeIn'] = $this->timeFmt($row['timeIn']);
			$rows[$key]['timeOut'] = $this->timeFmt($row['timeOut']);
			$rows[$key]['timeIn2'] = $this->timeFmt($row['timeIn2']);
			$rows[$key]['timeOut2'] = $this->timeFmt($row['timeOut2']);
			$rows[$key]['timeIn3'] = $this->timeFmt($row['timeIn3']);
			$rows[$key]['timeOut3'] = $this->timeFmt($row['timeOut3']);

			$minutes = $this->totalTime($row);
			$rows[$key]['minutes'] = $minutes;
			$rows[$key]['totalTime'] = $this->totalTimeFmt($minutes);
		}
		$totalMinutes = Mutils::arrayColumn($rows, "minutes");
		$totalMinutes = array_sum($totalMinutes);
		$totalTime = $this->totalTimeFmt($totalMinutes);

		$rows[] = array(
			'Total:',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			$totalMinutes,
			$totalTime,
		);
		$this->exportToExcel($rows, "timewatch.$user.$month");
	}
	/*------------------------------------------------------------*/
	public function show() {
		$user = $this->loginName;
		$month = @$_REQUEST['month'];
		if ( ! $month )
			$month = date("Y-m");
		$conds = "user = '$user' and month = '$month'";
		$sql = "select * from timewatch where $conds order by timeIn";
		$rows = $this->Mmodel->getRows($sql);
		foreach ($rows as $key => $row ) {
			$rows[$key]['weekday'] = date("D", strtotime($row['timeIn']));
			$rows[$key]['timeInFmt'] = $this->timeFmt($row['timeIn']);
			$rows[$key]['timeOutFmt'] = $this->timeFmt($row['timeOut']);
			$rows[$key]['timeIn2Fmt'] = $this->timeFmt($row['timeIn2']);
			$rows[$key]['timeOut2Fmt'] = $this->timeFmt($row['timeOut2']);
			$rows[$key]['timeIn3Fmt'] = $this->timeFmt($row['timeIn3']);
			$rows[$key]['timeOut3Fmt'] = $this->timeFmt($row['timeOut3']);

			$totalTime = $this->totalTime($row);
			$rows[$key]['totalTime'] = $totalTime;
			$rows[$key]['totalTimeFmt'] = $this->totalTimeFmt($totalTime);
		}
		$totalTime = array_sum(Mutils::arrayColumn($rows, "totalTime"));
		$totalTimeFmt = $this->totalTimeFmt($totalTime);
		$this->Mview->showTpl("timewatch/month.tpl", array(
			'rows' => $rows,
			'month' => $month,
			'today' => date("Y-m-d"),
			'yesterday' => date("Y-m-d", time() - 24*60*60),
			'totalTimeFmt' => $totalTimeFmt,
		));
	}
	/*------------------------------------------------------------*/
	private function totalTimeFmt($totalMinutes) {
		$minutes = $totalMinutes % 60 ;
		$hours = ( $totalMinutes - $minutes ) / 60 ;
		$totalTimeFmt = sprintf("%d:%02d", $hours, $minutes);
		return($totalTimeFmt);
	}
	/*------------------------------------------------------------*/
	private function timeFmt($datetime) {
		$timeFmt = substr($datetime, 11, 8);
		$zero = "00:00:00";
		if ( $timeFmt == $zero )
			return("");
		$timeFmt = substr($timeFmt, 0, 5);
		return($timeFmt);
	}
	/*------------------------------------------------------------*/
	public function summary() {
		$user = $this->loginName;
	
		$sql = "select distinct month from timewatch order by 1";
		$months = $this->Mmodel->getStrings($sql);

		$summary = array();
		foreach ( $months as $key => $month ) {
			$sql = "select * from timewatch where month = '$month' order by timeIn";
			$rows = $this->Mmodel->getRows($sql);
			foreach ($rows as $key => $row ) {
				$totalTime = $this->totalTime($row);
				$rows[$key]['totalTime'] = $totalTime;
			}
			$monthMinutes = Mutils::arrayColumn($rows, 'totalTime');
			$monthMinutes = array_sum($monthMinutes);
			$summary[] = array(
				'month' => $month,
				'totalTime' => $monthMinutes,
				'time' => $this->totalTimeFmt($monthMinutes),
			);
		}
		$totals = Mutils::arrayColumn($summary, 'totalTime');
		$totalTime = array_sum($totals);
		$totalTimeFmt = $this->totalTimeFmt($totalTime);
		$this->Mview->showTpl("timewatch/months.tpl", array(
			'rows' => $summary,
			'totalTime' => $totalTimeFmt,
		));
	}
	/*------------------------------------------------------------*/
	// totalTime in minutes
	public function totalTime($row) {
		$totalTime = 0;
		$t1 = $this->minuteDiff($row['timeOut'], $row['timeIn']);
		$t2 = $this->minuteDiff($row['timeOut2'], $row['timeIn2']);
		$t3 = $this->minuteDiff($row['timeOut3'], $row['timeIn3']);
		$totalTime = $t1 + $t2 + $t3;
		$today = date("Y-m-d");
		if ( $row['date'] != $today )
			return($totalTime);
		$now = date("Y-m-d H:i:s");
		if ( $row['timeIn'] && ! $row['timeOut'] )
			$totalTime += $this->minuteDiff($now, $row['timeIn']);
		elseif ( $row['timeIn2'] && ! $row['timeOut2'] )
			$totalTime += $this->minuteDiff($now, $row['timeIn2']);
		elseif ( $row['timeIn3'] && ! $row['timeOut3'] )
			$totalTime += $this->minuteDiff($now, $row['timeIn3']);
		return($totalTime);
	}
	/*------------------------------*/
	private function minuteDiff($timeOut, $timeIn) {
		$nullTime = "0000-00-00 00:00:00";
		if ( ! $timeOut || ! $timeIn ||
				$timeIn == $nullTime || $timeOut  == $nullTime )
			return(0);
		$outSecs = strtotime($timeOut);
		$inSecs = strtotime($timeIn);

		$secsDiff = $outSecs - $inSecs ;
		$minuteDiff = round($secsDiff/60);
		return($minuteDiff);
	}
	/*------------------------------------------------------------*/
	public function in() {
		$month = date("Y-m");
		$today = date("Y-m-d");
		$now = date("Y-m-d H:i:s");
		$user = $this->loginName;
		$sql = "select * from timewatch where user = '$user' and date = '$today'";
		$row = $this->Mmodel->getRow($sql);
		if ( $row ) {
			if ( $row['timeIn3'] ) {
				$this->Mview->error("timeIn3 already taken");
				$this->redir();
				return;
			}
			elseif ( $row['timeIn2'] )
				$fname = 'timeIn3';
			elseif ( $row['timeIn'] )
				$fname = 'timeIn2';
			else
				$fname = 'timeIn';

			$this->dbUpdate("timewatch", $row['id'], array(
				$fname => $now,
			));
		} else {
			$this->dbInsert("timewatch", array(
				'user' => $user,
				'month' => $month,
				'date' => $today,
				'timeIn' => $now,
			));
		}
		$this->redir();
	}
	/*------------------------------------------------------------*/
	public function out() {
		$today = date("Y-m-d");
		$now = date("Y-m-d H:i:s");
		$user = $this->loginName;
		$sql = "select * from timewatch where user = '$user' and date = '$today'";
		$row = $this->Mmodel->getRow($sql);
		if ( ! $row ) {
			$this->Mview->error("No entry for today");
			$this->redir();
			return;
		}

		if ( $row['timeIn3'] )
			$fname = 'timeOut3'; // possibly running over previous
		elseif ( $row['timeIn2'] )
			$fname = 'timeOut2';
		else
			$fname = 'timeOut';

		$row[$fname] = $now;
		$totalTime = $this->totalTime($row);

		$this->dbUpdate("timewatch", $row['id'], array(
			$fname => $now,
			'totalTime' => $totalTime,
		));
		$this->redir();
	}
	/*------------------------------------------------------------*/
	public function edit() {
		$row = $this->Mmodel->getById("timewatch", $_REQUEST['id']);
		$row['timeIn'] = $this->timeFmt($row['timeIn']);
		$row['timeOut'] = $this->timeFmt($row['timeOut']);
		$row['timeIn2'] = $this->timeFmt($row['timeIn2']);
		$row['timeOut2'] = $this->timeFmt($row['timeOut2']);
		$row['timeIn3'] = $this->timeFmt($row['timeIn3']);
		$row['timeOut3'] = $this->timeFmt($row['timeIn3']);
		$this->Mview->showTpl("timewatch/edit.tpl", array(
			'row' => $row,
		));
	}
	/*------------------------------------------------------------*/
	private function timeScan($str) {
		if ( ! $str )
			return(null);
		$hm = explode(":", $str);
		if ( count($hm) != 2 )
			return(false);
		$h = $hm[0];
		$m = $hm[1];
		if ( ! is_numeric($h) || ! is_numeric($m) )
			return(false);
		if ( $h < 0 || $h > 23 || $m < 0 || $m > 59 )
			return(false);
		$datetime = "$str:00";
		return($datetime);
	}
	/*------------------------------*/
	public function update() {
		$id = $_REQUEST['id'];
		$row = $_REQUEST;
		$date = $row['date'];
		$dbRow = $this->Mmodel->getById("timewatch", $row['id']);
		$fnames = $this->Mmodel->columns("timewatch");
		foreach ( $fnames as $fname ) {
			$substr = substr($fname, 0, 6);
			if ( $substr != "timeIn" && $substr != "timeOu" )
				continue;
			if ( $row[$fname] == "null" ) {
				// typed 'null'
				$row[$fname] = null;
			} else {
				$time = $this->timeScan($row[$fname]);
				if ( $time === false ) {
					Mview::print_r($row[$fname], "row[$fname]", basename(__FILE__), __LINE__);
					return;
				}
				if ( $time )
					$row[$fname] = "$date $time";
			}
		}
		$this->dbUpdate("timewatch", $id, $row);
		$this->redir();
	}
	/*------------------------------------------------------------*/
	public function delete() {
		$this->dbDelete("timewatch", $_REQUEST['id']);
		$this->redir();
	}
	/*------------------------------------------------------------*/
	/*------------------------------------------------------------*/
	private function redir() {
		$this->redirect("/timewatch");
	}
	/*------------------------------------------------------------*/
}
