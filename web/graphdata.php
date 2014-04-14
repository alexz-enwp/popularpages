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
$PROJECT = 'popularpages';
require_once('commonphp/mysql.php');
require_once('commonphp/GlobalFunctions.php');

function titleData($title, $redir) {
	$data = array();
	$params = "action=query&format=php&titles=" . urlencode($title);
	if ($redir) {
		$params .= '&redirects=1';
	}
	$ch = curl_init( 'https://en.wikipedia.org/w/api.php' );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'Tool Labs - Popular pages' );
	curl_setopt( $ch, CURLOPT_ENCODING, 'gzip' );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	$res = curl_exec( $ch );
	if ( $res ) {
		$result = unserialize( $res );
		$keys = array_keys( $result['query']['pages'] );
		$key = $keys[0];
		$data['title'] = $result['query']['pages'][$key]['title'];
		$data['ns'] = $result['query']['pages'][$key]['ns'];
		if ( array_key_exists( 'invalid', $result['query']['pages'][$key] ) ) {
			$data['error'] = "\"$t\" is not a valid page title";
		}
	} else {
		$data['error'] = "Request failed, please try again later";
	}
	curl_close($ch);
	return $data;

}

if ($_POST['title'] && $_POST['monthlist']) {
	header( "Content-Type: application/json; charset=utf-8" );
	$db = new mysqli('enwiki.labsdb', $my_user, $my_pass, 'p50380g50816__pop_stats');
	$msg = array();
	$data = titleData($_POST['title'], false);
	if (isset($data['error'])) {
		$msg['error'] = $data['error'];
		echo json_encode( $msg );
		return;
	}
	$ns = intval($data['ns']);
	$title =  str_replace( ' ', '_', $_POST['title'] );
	if ($ns != 0) {
		$bits = explode (':' , $title, 2);
		$title = $bits[1];
	}
	$title = $db->real_escape_string($title);
	$query = "SELECT hits, project_assess FROM pop_<month> WHERE ns=$ns AND title='$title'";
	$msg['data'] = array();
	$months = explode('|', $_POST['monthlist']);
	foreach ($months as $m) {
		$msg['data'][$m] = array();
		$q = str_replace( '<month>', $m, $query);
		$res = $db->query($q);
		if (!$res) {
			$msg['error'] = 'MySQL error';
			echo json_encode( $msg );
			return;
		}
		if ($res->num_rows === 0) {
			$msg['data'][$m]['error'] = 'noresult';
		} else {
			$qdata = $res->fetch_array();
			$msg['data'][$m]['hits'] = $qdata[0];
			$msg['data'][$m]['pa'] = json_decode($qdata[1], true);
		}
	}
	echo json_encode( $msg );
	return;
}
if ($_POST['action']) {
	if ($_POST['action'] == 'normalize') {
		$data = titleData($_POST['title'], $_POST['redir'] == 'true');
		$result = array();
		if (isset($data['error'])) {
			$result['error'] = $data['error'];
		} else {		
			$result['title'] = $data['title'];
		}
		echo json_encode($result);
	}
}
