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


$CONFIG['data_storage'] = 'mysql';		// mysql|pgsql|csv|sqlite
$CONFIG['passthru'] = false; 			// Array('http://volkszaehler.org/httplog.php','http://volkszaehler.org/http2.php');
$CONFIG['default_uuid'] = '';			// you can define a default uuid which is used if no uuid is tranfered - also as to be set in smartmeter.html
$CONFIG['allow_channel_edit'] = false;	// set to true to manage channels via channel_admin.php


// options for mysql storage
// you can # differentiate readonly and write user or set both to the same user/password
$CONFIG['mysql']['db_server'] = 'localhost';
$CONFIG['mysql']['db_name'] = 'smartmeter';
$CONFIG['mysql']['readonly']['db_user'] = 'sm_read_user';
$CONFIG['mysql']['readonly']['db_password'] = 'secret';
$CONFIG['mysql']['write']['db_user'] = 'sm_write_user';
$CONFIG['mysql']['write']['db_password'] = 'secret';


// options for csv storage
$CONFIG['csv']['path'] = 'data';



?>