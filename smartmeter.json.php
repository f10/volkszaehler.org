<?php
/*
* Copyright (c) 2010 by Florian Ziegler <fz@f10-home.de>
* 
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License (either version 2 or
* version 3) as published by the Free Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*
* For more information on the GPL, please go to:
* http://www.gnu.org/copyleft/gpl.html
*/


/** Parameters
 * uuid
 * mode = {getPulses,getChannels}
 * ids = comma separated string of ids
 * windowStart = {timestamp}
 * windowEnd = {timestamp}
*/


include('smartmeter.incl.php');
init_db();


// uuid
$uuid = validate_uuid($_GET['uuid']);

// mode
$mode = $_GET['mode'];

// json response data
$json = loadJSONHeader();


if($CONFIG['data_storage'] == 'mysql') {
	loadFromMySQL($uuid,$mode);
}


function loadJSONHeader() {
	
	global $CONFIG;
	
	$json = array();
	
	$json['source'] = 'volkszaehler.org';
	$json['version'] = '0.1';
	$json['storage'] = $CONFIG['data_storage'];
	$json['channels'] = array();
	
	return $json;
}


function loadFromMySQL($uuid,$mode) {
	
	global $json;
	
	$lessPulsesThreshold = 10;
	
	if($mode == 'getPulses') {
		
		// windowStart
		$windowStart = $_GET['windowStart'] * 1;
		
		// windowEnd
		$windowEnd = $_GET['windowEnd'] * 1;
		
		$timeFormatSQL = '%Y-%m-%d %H:%i:%s';
		
		$windowGroupingSQL = 'time ';
		
		if($_GET['windowGrouping'] != '-' AND $_GET['windowGrouping'] != '') {
			// windowGrouping
			switch($_GET['windowGrouping']) {
				case 'minute':
					$windowGroupingSeconds = 60;
					$windowGroupingSQL = 'YEAR(time),MONTH(time),DAY(time),HOUR(time),MINUTE(time)';
					$timeFormatSQL = '%Y-%m-%d %H:%i:00';
					break;
				case 'hour':
					$windowGroupingSeconds = 60*60;
					$windowGroupingSQL = 'YEAR(time),MONTH(time),DAY(time),HOUR(time)';
					$timeFormatSQL = '%Y-%m-%d %H:00:00';
					break;
				case 'day':
					$windowGroupingSeconds = 24*60*60;
					$windowGroupingSQL = 'YEAR(time),MONTH(time),DAY(time)';
					$timeFormatSQL = '%Y-%m-%d 00:00:00';
					break;
				case 'month':
					$windowGroupingSeconds = 30.5*24*60*60;
					$windowGroupingSQL = 'YEAR(time),MONTH(time)';
					$timeFormatSQL = '%Y-%m';
					break;
				case 'year':
					$windowGroupingSeconds = 365*30.5*24*60*60;
					$windowGroupingSQL = 'YEAR(time)';
					$timeFormatSQL = '%Y';
					break;
			}
		}
		
		// load ids into array
		$ids_array = explode(',',$_GET['ids']);
		foreach($ids_array as $id) {
			$id *= 1;
			if($id != 0)
				$ids[] = $id;
		}
		
		// header data
		$json['type'] = 'pulses';
		$json['windowStart'] = 0;
		$json['windowEnd'] = 0;
		
		foreach($ids as $id) {
			
			// data about the channel (id, resolution, function ...)
			$sql = '	SELECT *
					FROM
						channels
					WHERE
						id='.$id.' AND
						uuid="'.$uuid.'"';
			$result = mysql_query($sql);
			$data_channel = mysql_fetch_assoc($result);
			
			// pulses for this channel
			$pulses = array();
			
			$sql = '	SELECT
							UNIX_TIMESTAMP(DATE_FORMAT(time,\''.$timeFormatSQL.'\')) as timestamp,
							SUM(numb) as numb,
							resolution
						FROM 
							pulses
						INNER JOIN
							channels
						ON
							pulses.id=channels.id AND
							uuid="'.$uuid.'"
						WHERE 
							pulses.id='.$id.' AND
							UNIX_TIMESTAMP(time)>='.$windowStart.' AND
							UNIX_TIMESTAMP(time)<='.$windowEnd.'
						GROUP BY
						'.$windowGroupingSQL.'
						ORDER BY
							time';
			//echo '/*'.$sql.'*/';
			$result = mysql_query($sql);
			while($data = mysql_fetch_assoc($result)) {
			
				if($windowGroupingSeconds AND $data['numb'] < $lessPulsesThreshold) {
					
					$sql1 = 'SELECT UNIX_TIMESTAMP(time) AS timestamp FROM pulses WHERE id='.$id.' AND time>FROM_UNIXTIME('.$data[timestamp].') ORDER BY time LIMIT 2';
					$result1 = mysql_query($sql1);
					$difference = 0;
					while($data1 = mysql_fetch_assoc($result1)) {
						if(!$difference)
							$difference -= $data1['timestamp'];
						else
							$difference += $data1['timestamp'];
					}
					
					if($difference AND $windowGroupingSeconds/$difference<$lessPulsesThreshold) {
						// numb for this interval is faked
						$data['numb'] = round($windowGroupingSeconds/$difference,2);
					}
				}
				
				// array of pulses: timestamp=>count
				$pulses[] = array($data['timestamp']*1,$data['numb']*1);
				
				// min timestamp
				if($json['windowStart'] == 0 OR $json['windowStart'] > $data['timestamp'])
					$json['windowStart'] = $data['timestamp']*1;
				
				// max timestamp
				if($json['windowEnd'] == 0 OR $json['windowEnd'] < $data['timestamp'])
					$json['windowEnd'] = $data['timestamp']*1;
			}
			
			$json['channels'][] = array('id'=>$data_channel['id']*1,'resolution'=>$data_channel['resolution']*1,'function'=>$data_channel['function'],'pulses'=>$pulses);
		}
		
		echo json_encode($json);
	}
	elseif($mode == 'getChannels') {
		
		// header data
		$json['type'] = 'channels';
		
		$sql = '	SELECT *
				FROM
					channels
				WHERE
					uuid="'.$uuid.'"';
		$result = mysql_query($sql);
		
		while($data_channel = mysql_fetch_assoc($result)) {
			
			$json['channels'][] = array('id'=>$data_channel['id']*1,'resolution'=>$data_channel['resolution']*1,'function'=>$data_channel['function']);
			
		}
		
		echo json_encode($json);
		
	}
	else {
		echo 'ERROR: no mode selected.';
	}
}

?>
