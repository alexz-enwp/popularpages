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
require_once( "commonphp/template.php" );
templatetop("WikiProject Popular page graphs", array( 'config.css' ), array('jquery.js', 'graph.js', 'projectinfo.js'), '');
require_once('commonphp/GlobalFunctions.php');
header('X-UA-Compatible: edge');
date_default_timezone_set('UTC');
require_once('commonphp/mysql.php');

$db = new mysqli('enwiki.labsdb', $my_user, $my_pass, 'p50380g50816__pop_stats');

echo '<div id="status" style="margin-top: 0.5%;"></div>';

echo "<canvas id='graph' width='1' height='1'>
Your browser does not support the HTML &lt;canvas&gt; element. You will need to upgrade to a newer version
or use a different browser.
</canvas>";

echo '<div id="stat-box"></div>';

$startmonth = isset($_GET['start']) ? $_GET['start'] : false;
$endmonth = isset($_GET['end']) ? $_GET['end'] : false;
$title = isset($_GET['title']) ? $_GET['title'] : false;
$redir = isset($_GET['redir']);
$stats = isset($_GET['stats']);
$otitle = htmlspecialchars( $title, ENT_QUOTES);

$res = $db->query("SHOW TABLES LIKE 'pop_%'");

$rows = array();
while ($row = $res->fetch_array()) {
	$table = $row[0];
	$my = explode( 'pop_', $table);
	$my = $my[1];
	$ts = strptime( $my, '%b%y');
	$month = $ts['tm_mon']+1;
	$year = $ts['tm_year']+1900;
	$dt = new DateTime();
	$dt->setDate( $year, $month, 1);
	$rows[] = $dt;
}
sort( $rows );
$months = array();
foreach ($rows as $dt) {
	$months[] = $dt->format('My');
} 
$months = json_encode($months);
$t = json_encode($title);
$s = json_encode($startmonth);
$e = json_encode($endmonth);
$st = json_encode($stats);
echo "<script type='text/javascript'>
var monthlist = $months;
var title = $t;
var start = $s;
var end = $e;
var showstats = $st;
</script>";

$datepickerS =  '<label for="start"><b>Start month:</b></label>' . "\n";
$datepickerS .= '<select id="start" name="start">' . "\n";
$i = 0;
foreach ($rows as $dt) {
	$ddate = $dt->format('F Y');
	$code = $dt->format('My');
	if ($code == $startmonth || (!$startmonth && $i == 0)) {
		$sel = ' selected="selected"';
	} else {
		$sel = '';
	}
	$datepickerS.= "<option value='$code'$sel>$ddate</option>\n";
	$i++;
}
$datepickerS .= "</select>";
$datepickerE =  '<label for="end"><b>End month:</b></label>' . "\n";
$datepickerE .= '<select id="end" name="end">' . "\n";
$i = 1;
foreach ($rows as $dt) {
	$ddate = $dt->format('F Y');
	$code = $dt->format('My');
	if ($code == $endmonth || (!$endmonth && $i == count($rows)-1)) {
		$sel = ' selected="selected"';
	} else {
		$sel = '';
	}
	$datepickerE.= "<option value='$code'$sel>$ddate</option>\n";
	$i++;
}
$datepickerE .= "</select>";

$it = '';
if ($title) {
	$it = "value='$otitle' ";
}

$redirsel = '';
if ($redir) {
	$redirsel = ' checked="checked"';
}
$statssel = '';
if ($stats) {
	$statssel = ' checked="checked"';
}

echo "<form method='GET' action='graph.php' id='graph-form'>
<fieldset>
<legend>Select a page to graph</legend>
<label for='title'><b>Title: </b></label>
<input type='text' size='30' id='title' name='title' $it/>
<label for='redir'>Follow redirects</label><input id='redir' name='redir' type='checkbox'$redirsel /><br /><br />
$datepickerS $datepickerE
<br />
<label for='stats'>Show statistics</label><input id='stats' name='stats' type='checkbox' value='show'$statssel />
<br />
<input type='Submit' value='Submit' id='graph-sub' />
</fieldset>
</form>
";
templatebottom();
