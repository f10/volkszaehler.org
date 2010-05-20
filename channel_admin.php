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

if(!$CONFIG['allow_channel_edit']) {
	echo 'ERROR: channel edit is not allowed in config.php';
	exit();
}

$uuid = validate_uuid($_GET['uuid']);
init_db();

if($CONFIG['data_storage'] == 'mysql') {
	
	// new Channel
	if($_POST['channel'] AND $_POST['resolution'] AND $_POST['function']) {
		
		
		$POST = sql_protect($_POST);
		
		$sql = "	INSERT INTO channels
				SET
					channel='$POST[channel]',
					resolution='$POST[resolution]',
					function='".$POST['function']."',
					uuid='$uuid'";
		
		mysql_query($sql);
		
	}
	
	// delete Channel
	
	if($_GET['mode'] == 'deleteChannel') {
		
		$id = $_GET['id']*1;
		
		$sql = "	DELETE FROM channels
				WHERE
					uuid='$uuid' AND
					id=$id";
		mysql_query($sql);
	}
	
	// show Channels
	$sql = "	SELECT * 
			FROM
				channels
			WHERE
				uuid='$uuid'";
	
	$result = mysql_query($sql);
	
	echo '<form method="POST" action="">
		<table>
			<tr style="font-weight:bold;">
				<td>id</td>
				<td>Port</td>
				<td>Aufl&ouml;sung</td>
				<td>Funktion</td>
				<td></td>
			</tr>';
	
	while($data = mysql_fetch_assoc($result)) {
		
		$data = user_out($data);
		
		echo '
			<tr>
				<td>'.$data['id'].'</td>
				<td>'.$data['channel'].'</td>
				<td>'.$data['resolution'].'</td>
				<td>'.$data['function'].'</td>
				<td>[ <a href="?uuid='.$uuid.'&mode=deleteChannel&id='.$data['id'].'" onclick="JavaScript: return confirm(\'wirklich l&ouml;schen?\')">l&ouml;schen</a> ]</td>
			</tr>';
		
	}
	
	echo '
			<tr>
				<td>neu:</td>
				<td><input name="channel" value="" size="5" /></td>
				<td><input name="resolution" /></td>
				<td><input name="function" /></td>
				<td><input type="submit" value="anlegen" /></td>
			</tr>
		</table>
		</form>';
	
}


?>

<font color="red">Hinweis: Du solltest das Editieren der Kan&auml;le in der config.php wieder deaktivieren, falls das weiterhin nicht n&ouml;tig ist.</font>