<?php
/*
	Copyright 2014 Alex Zaddach. (mrzmanwiki@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$PROJECT = 'popularpages';
	require_once('commonphp/GlobalFunctions.php');
	require_once('commonphp/mysql.php');
	header( "Content-Type: application/json; charset=utf-8" );
	$result = array();	
	$db = new mysqli('tools-db', $my_user, $my_pass, 's51401__pop_data');
	if ($db->connect_error) {
		$result['result'] = 'error';
		$result['error'] = 'MySQL connection failed';
		echo(json_encode($result));
		exit(0);
	}
	if ($_POST['action'] == 'submit' || $_POST['action'] == 'submitedit') {
		require_once('/data/project/popularpages/lib/web/oauth.php');
		$o = OAuth::startOAuth('/data/project/popularpages/oauth.ini');
		if ($o[1]) {
			$result['result'] = 'error';
			$result['error'] = $o[1];
			echo(json_encode($result));
			return;
		}
		$auth = $o[0];
		if (!$auth->isAuthorized()) { 
			$result['result'] = 'error';
			$result['error'] = 'Cannot verify authorization. Your session may have expired. Try reloading the page';
			echo(json_encode($result));
			return;
		} else {
			$ret = $auth->getID();
			if ($ret[0] == 'error') {
				$result['result'] = 'error';
				$result['error'] = $ret[1];
				echo(json_encode($result));
				return;
			}
			$userinfo = $ret[1];
			if ($userinfo['blocked']) {
				$result['result'] = 'error';
				$result['error'] = 'To prevent abuse, blocked users are unable to use this tool.';
				echo(json_encode($result));
				return;
			} elseif ($userinfo['editcount'] < 150) {
				$result['result'] = 'error';
				$result['error'] = 'To prevent misuse, users must have at least 150 edits to use this tool.';
				echo(json_encode($result));
				return;
			}
		}
	}
	
switch($_POST['action']) {
	case "projectexists":
		$proj = $db->real_escape_string($_POST['project']);
		$res = $db->query("SELECT * FROM project_config WHERE name='$proj'");
		$rows = $res->num_rows;
		if ($rows == 0) {
			$result['result'] = 'false';
		} else {
			$result['result'] = 'true';
		}
		break;
	case "submit":
		// Match normalization as done by the Python script:
		// Strip quote marks and colons, could cause problems
		$category = str_replace( array(' ', "'", '"', ':'), array('_', '', '', ''), $_POST['category']);
		$category = $db->real_escape_string($category);
		$name = $db->real_escape_string($_POST['proj_name']);
		$listpage = $db->real_escape_string($_POST['proj_name'].'/'.$_POST['listpage']);
		$lim = $db->real_escape_string($_POST['lim']);
		$query = "INSERT INTO project_config (category, name, listpage, lim, removed) VALUES ('$category', '$name', '$listpage', $lim, 0)";
		
		$res = $db->query($query);
		// Update the history table
		$username = $db->real_escape_string($userinfo['username']);
		$changes = array(
			'category' => array('', $category),
			'name' => array('', $name),
			'listpage' => array('', $listpage),
			'lim' => array('', $lim),
			'removed' => array('', 0)
		);
		$changes = $db->real_escape_string(json_encode($changes));
		$query2 = "INSERT INTO config_history (new, name, username, changes) VALUES (1, '$name', '$username', '$changes')";
		$res2 = $db->query($query2);
		if ($res && $res2) {
			$result['result'] = 'success';
		} else {
			$result['result'] = 'error';
			$result['error'] = 'MySQL error';
		}
		break;
	case "submitedit":
		
		$proj = $db->real_escape_string($_POST['proj_name']);
		$res = $db->query("SELECT category, listpage, lim FROM project_config WHERE name='$proj'");
		if (!$res) {
			$result['result'] = 'error';
			$result['error'] = 'MySQL error';
			break;
		}
		$row = $res->fetch_array();
		$changes = array();
		$query = "UPDATE project_config SET ";
		$updates = array();
		if (isset($_POST['category'])) {
			// Match normalization as done by the Python script:
			// Strip quote marks and colons, could cause problems
			$category = str_replace( array(' ', "'", '"', ':'), array('_', '', '', ''), $_POST['category']);
			$category = $db->real_escape_string($category);
			$updates[] = "category = '$category'";
			$changes['category'] = array($db->real_escape_string($row[0]), $category);
		}
		if (isset($_POST['listpage'])) {
			$listpage = $db->real_escape_string($_POST['proj_name'].'/'.$_POST['listpage']);
			$updates[] = "listpage = '$listpage'";
			$changes['listpage'] = array($db->real_escape_string($row[1]), $category);
		}
		if (isset($_POST['lim'])) {
			$lim = $db->real_escape_string($_POST['lim']);
			$updates[] = "lim = '$lim'";
			$changes['lim'] = array($db->real_escape_string($row[2]), $lim);
		}
		if (!$updates) {
			$result['result'] = 'error';
			$result['error'] = 'No changes made';
			break;
		}
		$args = implode( ', ' , $updates );
		$query .= $args . " WHERE name='$proj'";
		$res = $db->query($query);		
		// Update the history table
		$username = $db->real_escape_string($userinfo['username']);
		$changes = $db->real_escape_string(json_encode($changes));
		$query2 = "INSERT INTO config_history (new, name, username, changes) VALUES (0, '$proj', '$username', '$changes')";
		$res2 = $db->query($query2);
		if ($res && $res2) {
			$result['result'] = 'success';
		} else {
			$result['result'] = 'error';
			$result['error'] = 'MySQL error';
		}
		break;
	case 'catcheck':
		$cat = $db->real_escape_string($_POST['cat']);
		$res = $db->query("SELECT name FROM project_config WHERE category='$cat'");
		$rows = $res->num_rows;
		if ($rows == 0) {
			$result['result'] = 'false';
		} else {
			$result['result'] = 'true';
			$t = $res->fetch_array();
			$result['project'] = htmlspecialchars($t[0]);
		}
		break;
	case "search":
		$like = $db->real_escape_string($_POST['like']);
		$query = "SELECT name FROM project_config WHERE name LIKE '%$like%' LIMIT 10";
		$res = $db->query($query);
		$rows = $res->num_rows;
		$result['result'] = array();
		for ($i=0; $i<$rows; $i++) {
			$row = $res->fetch_array();
			$result['result'][] = $row[0];
		}
		break;
	case "getprojectinfo":
		$proj = $db->real_escape_string($_POST['project']);
		$res = $db->query("SELECT category, listpage, lim FROM project_config WHERE name='$proj'");
		if ($res) {
			$row = $res->fetch_array();
			$result['result'] = 'success';
			$result['category'] = $row[0];
			$result['listpage'] = $row[1];
			$result['lim'] = $row[2];
		} else {
			$result['result'] = 'error';
			$result['error'] = 'MySQL error';
		}
		break;
	}
	
	echo(json_encode($result));
}
