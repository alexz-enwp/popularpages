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
templatetop("WikiProject Popular pages config history", array( 'config.css' ), array(), '<a href="." title="Popular pages">Popular pages</a>');
require_once('commonphp/GlobalFunctions.php');
require_once('commonphp/mysql.php');
?>
<br />
<fieldset>
<legend>Select a project from the list</legend>
<form method='get' action='history.php'>
<!--
<label for="searchbox">Search for a project:</label>
<input type="text" name="searchbox" id="searchbox" size="50" />
<div id="searchresults" class="searchinfo">
Search results:<input type="hidden" id="searchnum" value="0" /><img id="list-spinner" src="spinner.gif" alt="..." title="..." style="visibility:hidden" />
<div id="autofill" class="searchresults"><span class="info">(Just start typing into the search box)</span></div>
</div> 
-->
<?php
$db = mysql_connect( 'tools-db', $my_user, $my_pass );
mysql_select_db( 's51401__pop_data', $db );
$res = mysql_query('SELECT name FROM project_config ORDER BY name', $db);
$projects = array();
$defproj = isset($_GET['proj']) ? $_GET['proj'] : '';
$deflim = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
$deflim = $deflim > 500 ? 25 : $deflim;

echo '<label for="proj"><b>Project:</b></label><br />' . "\n";
echo '<select id="proj" name="proj">' . "\n";
echo "<option value=''> </option>";
while ( $row = mysql_fetch_array($res) ) {
	if ($row[0] == $defproj) {
		$sel = ' selected="selected"';
	} else {
		$sel = '';
	}
	$proj = htmlspecialchars($row[0], ENT_QUOTES);
	echo "<option value='$proj'$sel>$proj</option>\n";
}
echo "</select>";
echo '<br />';
echo '
<label for="limit"><b>List size:</b></label><br />
<input name="limit" type="number" id="limit" max="500" value="'.$deflim.'" />
<input type="hidden" name="offset" value="0" />
<br />
<input type="submit" value="Submit" />
		</form>';
?>
</fieldset>

<?php
$keymap = array(
	'category' => 'Category',
	'name' => 'Project name',
	'listpage' => 'List page',
	'lim' => 'Limit'
);
$where = '';
if ($defproj) {
	echo "<p style='font-weight:bold;'>Showing history for ".htmlspecialchars($defproj).":</p>";
	$where = "WHERE name='".mysql_real_escape_string( $defproj, $db )."'";
} else {
	echo "<p style='font-weight:bold;'>Showing history for all projects:</p>";
}
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
if ($limit <=0 || $limit > 500) {
	$limit = 25;
}
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
if ($offset < 0) {
	$offset = 0;
}
$res = mysql_query("SELECT * FROM config_history $where ORDER BY change_timestamp DESC LIMIT $limit OFFSET $offset", $db);
$rows = mysql_num_rows ( $res );
$mproj = wfUrlencode($defproj);
if ( $rows == $limit ) {
	$nextoff = $limit+$offset;
	$next = "(<a href='?proj=$mproj&amp;limit=$limit&amp;offset=$nextoff' title='Next $limit'>Next $limit</a>)";
} else {
	$next = "(Next $limit)";
}
if ( $offset > 0 ) {
	$prevoff = $offset - $limit;
	if ( $prevoff < 0 ) {
		$prevoff = 0;
	}
	$prev = "(<a href='?proj=$mproj&amp;limit=$limit&amp;offset=$prevoff' title='Previous $limit'>Previous $limit</a>)";
} else {
	$prev = "(Previous $limit)";
} 
echo "$prev&emsp;&emsp;$next";
echo "<ul>";
while ( $row = mysql_fetch_assoc($res) ) {
	echo "<li>";
	echo $row['change_timestamp']."&nbsp;(UTC)&nbsp;";
	echo "<a href='https://en.wikipedia.org/wiki/User:".wfUrlencode($row['username'])."' title=\"User:".htmlspecialchars($row['username'])."\">";
	echo htmlspecialchars($row['username'])."</a>&nbsp;";
	echo "(<a href='https://en.wikipedia.org/wiki/User_talk:".wfUrlencode($row['username'])."' title=\"User talk:".htmlspecialchars($row['username'])."\">";
	echo "talk</a>)&nbsp;";
	echo ". .&nbsp;";
	if ($row['new']) {
		echo "<abbr title='New project added'><strong>N</strong></abbr>&nbsp;";
	}
	echo "<a href='https://en.wikipedia.org/wiki/Wikipedia:".wfUrlencode($row['name'])."' title=\"Wikipedia:".htmlspecialchars($row['name'])."\">";
	echo htmlspecialchars($row['name'])."</a>&nbsp;";
	echo "(";
	$changes = json_decode($row['changes'], true);
	if ($row['new']) {
		echo "Category: \"".htmlspecialchars($changes['category'][1]).'", ';
		echo "List page: \"";
		$lp = str_replace("\\'", "'", $changes['listpage'][1]);
		echo "<a href='https://en.wikipedia.org/wiki/Wikipedia:".wfUrlencode($lp)."' title=\"Wikipedia:".htmlspecialchars($lp)."\">";
		echo htmlspecialchars($lp)."</a>, ";
		echo "Limit: {$changes['lim'][1]}";
	} elseif (isset($changes['removed']) && $changes['removed'][1]) {
		echo '<em>Project marked as removed</em>';
	} else {
		echo 'Change(s): ';
		$changelist = array();
		foreach ($changes as $key => $value) {
			$val0 = str_replace("\\'", "'", $value[0]);
			$val1 = str_replace("\\'", "'", $value[1]);
			$changelist[] = $keymap[$key].": ".htmlspecialchars($val0).' → '.htmlspecialchars($val1);
		}
		echo implode(', ', $changelist);
	}
	echo ")";
	echo "</li>\n";
}
echo "</ul>";
echo "$prev&emsp;&emsp;$next";
?>


<?php
templatebottom();
