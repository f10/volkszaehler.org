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

include('smartmeter.incl.php');

init_db(true);

// time=<timestamp>
// port=P{ABCD}{0-7}
// uuid=<01234567-89AB-CDEF...>
// 
if(!preg_match('/^([timeportuuidPA-D0-9\-a-fA-F\=\&]*)$/',$_SERVER['QUERY_STRING']))
	die("");

$controllertime = $_GET['time']*1;
$port = $_GET['port'];
$uuid = validate_uuid($_GET['uuid']);

// all required parameters present?
if ($uuid == '') {
	echo "Parameter uuid is required - aborting";
	exit();
} 
if ($controllertime == '') {
	echo "Parameter time is required - aborting";
	exit();
} 
if ($port == '') {
	echo "Parameter port is required - aborting";
	exit();
} 

if($CONFIG['data_storage'] == 'mysql') {
	logToMySQL($controllertime,$port,$uuid);
}
elseif($CONFIG['data_storage'] == 'pgsql') {
	logToPgSQL($controllertime,$port,$uuid);
}


function logToMySQL($time,$port,$uuid) {

	$result = mysql_query("SELECT id FROM channels where uuid='$uuid' and channel='$port'");
	$data = mysql_fetch_assoc($result);
	
	if (mysql_num_rows($result) != 1) {
		echo "uuid has not been approved yet - Aborting!";
		exit();
	}
	
	// if transfered timestamp is older than now - 1 day
	// the controller faild with NTP and starts with 1970
	// so we take the local server time instead of transfered timestamp
	if($time<(time()-24*60*60)) {
		$time = time();
	}
	
	$result = mysql_query("	INSERT INTO
						pulses
					SET
						id='$data[id]',
						time=FROM_UNIXTIME($time),
						numb=1
					ON DUPLICATE KEY UPDATE
						numb=numb+1");
	echo mysql_error();
}

function logToPgSQL($time,$port,$uuid) {}



?>