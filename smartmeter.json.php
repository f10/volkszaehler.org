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
 * windowSize = integer								(1)
 * windowInterval = {SECOND|MINUTE|HOUR|DAY|MONTH|YEAR}		(HOUR)
 * windowStart = {auto|'YYYY-MM-DD'} 					(auto)
 * windowEnd = {auto|LAST|timestamp}					(LAST)
*/


include('smartmeter.incl.php');
init_db();
define('DATA_TABLE','pulses');


// uuid
$uuid = validate_uuid($_GET['uuid']);


// mode
$mode = $_GET['mode'];

// header data
$json = array();
$json['source'] = 'volkszaehler.org';
$json['version'] = '0.1';
$json['storage'] = $CONFIG['data_storage'];
$json['channels'] = array();


if($CONFIG['data_storage'] == 'mysql') {
	loadFromMySQL();
}
elseif($CONFIG['data_storage'] == 'csv') {
	loadFromCSV();
}



// only proof of concept
function loadFromCSV() {
	
	global $mode,$json,$uuid;
	
	
	if($mode == 'getChannels') {
		$filename = './data/'.$uuid.'_channels.csv';
		$path = realpath($filename);
	
		if(!strstr($path,realpath('./'))) {
			echo "ERROR: file does not exist.";
			return;
		}
		
		$channels = file($path);
		
		foreach($channels as $channel) {
			$channel_data = explode(';',chop($channel));
			$json['channels'][] = array('id'=>$channel_data[0],'resolution'=>$channel_data[2]*1,'function'=>$channel_data[1]);
		}
		echo json_encode($json);
	}
	elseif($mode == 'getPulses') {
		
		// pulses
		$filename = './data/'.$uuid.'_pulses.csv';
		$path = realpath($filename);
		
		if(!strstr($path,realpath('./'))) {
			echo "ERROR: file does not exist.";
			return;
		}
		
		// header data
		$json['type'] = 'pulses';
		$json['windowStart'] = 0;
		$json['windowEnd'] = 0;
		
		$lines = file($path);
		$pulses = array();
		
		foreach($lines as $line) {
			$pulse_data = explode(';',chop($line));
			
			$pulses[$pulse_data[1]][] = array($pulse_data[0]*1,1);
			
			// min timestamp
			if($json['windowStart'] == 0 OR $json['windowStart'] > $pulse_data[0])
				$json['windowStart'] = $pulse_data[0]*1;
			
			// max timestamp
			if($json['windowEnd'] == 0 OR $json['windowEnd'] < $pulse_data[0])
				$json['windowEnd'] = $pulse_data[0]*1;
		}
		
		
		// channels
		$filename = './data/'.$uuid.'_channels.csv';
		$path = realpath($filename);
	
		if(!strstr($path,realpath('./'))) {
			echo "ERROR: file does not exist.";
			return;
		}
		
		$channels = file($path);
		
		foreach($channels as $channel) {
			$channel_data = explode(';',chop($channel));
			if(count($pulses[$channel_data[0]]))
				$json['channels'][] = array('id'=>$channel_data[0],'resolution'=>$channel_data[2]*1,'function'=>$channel_data[1],'pulses'=>$pulses[$channel_data[0]]);
		}
		
		
		echo json_encode($json);
		
	}
	else {
		echo 'ERROR: no mode selected.';
	}
	
}


function loadFromMySQL() {
	
	global $mode,$json,$uuid,$windowSize,$windowEnd,$windowStart,$windowInterval,$ids;


	if($mode == 'getPulses') {
	
		// windowSize
		$windowSize = $_GET['windowSize']*1;
		
		// windowInterval
		if(		$_GET['windowInterval'] == 'SECOND' || 
				$_GET['windowInterval'] == 'MINUTE' || 
				$_GET['windowInterval'] == 'HOUR' || 
				$_GET['windowInterval'] == 'DAY' || 
				$_GET['windowInterval'] == 'MONTH' ||
				$_GET['windowInterval'] == 'YEAR')
			$windowInterval = $_GET['windowInterval'];
		
		// windowStart
		if(!$_GET['windowStart']) $windowStart = 'auto';
		
		if(!$_GET['windowStart'] || $_GET['windowStart'] == 'auto') $windowStart = 'auto';
		else $windowStart = $_GET['windowStart'] * 1;
		
		if(!$_GET['windowEnd'] || $_GET['windowEnd'] == 'LAST') $windowEnd = 'LAST';
		else $windowEnd = $_GET['windowEnd'] * 1;
		
		
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
		
		if(count($ids)) {
			
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
								DATE_FORMAT(time,\'%Y-%m-%d %H:%i:%s\') as time,
								UNIX_TIMESTAMP(time) as timestamp,
								numb,
								resolution
							FROM 
								'.DATA_TABLE.'
							INNER JOIN
								channels
							ON
								'.DATA_TABLE.'.id=channels.id AND
								uuid="'.$uuid.'"
							WHERE 
								'.DATA_TABLE.'.id='.$id.' AND
								time>=\''.windowStart().'\' AND
								time<=\''.windowEnd().'\'
							ORDER BY
								time';
				//time>=DATE_SUB(\''.windowEnd().'\', INTERVAL '.$windowSize.' '.$windowInterval.')
				//echo '/*'.$sql.'*/';
				$result = mysql_query($sql);
				
				while($data = mysql_fetch_assoc($result)) {
					
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


function windowStart() {
	
	global $ids,$windowStart;
	
	if($windowStart == 'auto') {
		// not implemented yet
	}
	elseif($windowStart > 0) {
		return date('Y-m-d H:i:s',$windowStart);
	}
	
}

function windowEnd() {
	
	global $ids,$windowEnd;
	
	if($windowEnd == 'LAST') {
		if(!count($ids))
			return;
		$ids_for_sql = implode(',',$ids);
		
		$sql = 'SELECT MAX(time) AS windowEnd FROM pulses WHERE id IN ('.$ids_for_sql.')';
		$result = mysql_query($sql);
		$data = mysql_fetch_assoc($result);
		
		return $data['windowEnd'];
	}
	elseif($windowEnd > 0) {
		return date('Y-m-d H:i:s',$windowEnd);
	}
	
}

?>
