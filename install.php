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

if($CONFIG['data_storage'] == 'mysql') {

	init_db(true);
	
	$sql = "	CREATE TABLE IF NOT EXISTS `channels` (
				`function` char(100) collate utf8_unicode_ci default NULL,
				`resolution` int(11) default '1000',
				`id` int(11) NOT NULL auto_increment,
				`uuid` char(36) collate utf8_unicode_ci default NULL,
				`channel` varchar(255) collate utf8_unicode_ci NOT NULL,
				PRIMARY KEY  (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
	
	mysql_query($sql);
	
	$sql = "	CREATE TABLE IF NOT EXISTS `pulses` (
				`id` int(11) NOT NULL,
				`time` datetime NOT NULL,
				`numb` tinyint(4) unsigned NOT NULL,
				PRIMARY KEY  (`id`,`time`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	
	mysql_query($sql);
}



?>