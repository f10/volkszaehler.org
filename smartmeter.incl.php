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

	include ('config.php');

	function init_db(){
		global $CONFIG,$db,$db_select, $db_server, $db_name, $db_user, $db_password;
		
		if($CONFIG['data_storage'] == 'mysql') {
			$db = MYSQL_CONNECT($CONFIG['mysql']['db_server'], $CONFIG['mysql']['db_user'], $CONFIG['mysql']['db_password']) or die ("Konnte keine Verbindung zur Datenbank herstellen");
			$db_select = MYSQL_SELECT_DB($CONFIG['mysql']['db_name']);
		}
		elseif($CONFIG['data_storage'] == 'pgsql') {
			// I don't know yet
		}
		elseif($CONFIG['data_storage'] == 'csv') {
			// check if csv file is read/writable ???
		}
	}
	
	function validate_uuid($uuid) {
		
		global $CONFIG;
		if($uuid == '' AND $CONFIG['default_uuid'])
			$uuid = $CONFIG['default_uuid'];
		
		# parse uuid for correct format
		if(!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid)) {
			echo "uuid has an invalid format - aborting!";
			exit;
		}
		
		return $uuid;
	}
	
	function create_account() {
		
		// max 10 retries
		for($i=0;$i<10;$i++) {
			$uuid = uuid();
			
			// check uuid in db
			$sql = 'SELECT * FROM channels WHERE uuid=\''.$uuid.'\'';
			$result = mysql_query($sql);
			
			// return uuid if it does not exists
			if(mysql_num_rows($result) == 0) {
				return $uuid;
			}
			// else try again
		}
		
		echo 'Bei der Erzeugung einer uuid ist ein Fehler aufgetreten';
		exit();
	}
	
	function random_uuid() {
		
		$chars = md5(uniqid(mt_rand(), true));
		$uuid  = substr($chars,0,8) . '-';
		$uuid .= substr($chars,8,4) . '-';
		$uuid .= substr($chars,12,4) . '-';
		$uuid .= substr($chars,16,4) . '-';
		$uuid .= substr($chars,20,12);
		
		return $uuid;
	}
	
	// Protect from SQL-Injection
	function sql_protect($Values) {
		if(!is_array($Values)) {
			$reget_string = true;
			$Values = Array($Values);
		}
		while(list($key,$val) = each($Values)) {
			// stripslashes, falls noetig
			if (get_magic_quotes_gpc())
				$val = stripslashes($val);
			// quotieren, falls kein integer
			if (!is_numeric($val))
				$Values[$key] = mysql_real_escape_string($val);
		}
		if($reget_string)
			return $Values[0];
		else
			return $Values;
	}
	
	
	// Protect from XSS
	function user_out($Values) {
		if(is_array($Values)) {
			while(list($key,$val) = each($Values)) {
				$Values[$key] = htmlentities($val,ENT_QUOTES);
			}
		}
		else
			$Values = htmlentities($Values,ENT_QUOTES);
		return $Values;
	}

?>
