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
if (isset($_GET['proj'])) {
	setcookie ('WPPP-temp', $_GET['proj'], time()+60);
}
$PROJECT = 'popularpages';
require_once( "commonphp/template.php" );
templatetop("WikiProject Popular pages configuration", array( 'config.css' ), array( 'jquery.js', 'config.js' ), '<a href="." title="Popular pages">Popular pages</a>');
require_once('commonphp/GlobalFunctions.php');
require_once('/data/project/popularpages/lib/web/oauth.php');

function outputError($text) {
	header( "HTTP/1.1 500 Internal Server Error" );
	echo $text;
	templatebottom();
	exit(0);
}

$o = OAuth::startOAuth('/data/project/popularpages/oauth.ini');
if ($o[1]) {
	outputError($o[1]);
}

$auth = $o[0];

$err = $auth->checkAccessToken();
if ($err) {
	outputError($err);
}


if (!$auth->isAuthorized()) { // What to show if not authenticated
	$ret = $auth->getURL();
	if ($ret[0] == 'error') {
		outputError($ret[1]);
	}
	$url = $ret[1];
	require('/data/project/popularpages/lib/web/config-noauth.php');
} else { // What to show if we are authenticated
	$ret = $auth->getID();
	if ($ret[0] == 'error') {
		outputError($ret[1]);
	}
	$userinfo = $ret[1];
	if ($userinfo['blocked']) {
		echo '<p>To prevent abuse, blocked users are unable to use this tool.</p>';
	} elseif ($userinfo['editcount'] < 150) {
		echo '<p>To prevent misuse, users must have at least 150 edits to use this tool.</p>';
	} else {
		require('/data/project/popularpages/lib/web/config-newproject.php');
	}
}

templatebottom();
