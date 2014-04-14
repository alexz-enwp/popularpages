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
templatetop("WikiProject Popular pages project list", array( 'config.css' ));
require_once('commonphp/GlobalFunctions.php');
require_once('commonphp/mysql.php');
?>
		<p>This is a list of all projects currently set up with the popular pages bot and the configurable details for each.</p>
		<table>
		<tr><th>Project</th><th>List subpage</th><th style="width:6em">Page size</th><th>Category</th><th>Links</th></tr>
<?php
date_default_timezone_set('UTC');
$db = new mysqli('tools-db', $my_user, $my_pass, 's51401__pop_data');
$res = $db->query("SELECT name, listpage, category, lim FROM project_config WHERE removed=0 ORDER BY name");
$index = 0;
while ($row = $res->fetch_assoc()) {
	$index++;
	$proj = htmlspecialchars( $row['name'], ENT_QUOTES );
	$eproj = wfUrlencode( $row['name'] );
	$bits = explode( '/', $row['listpage'] );
	$bits = array_reverse( $bits );
	$subname = '/' . $bits[0];
	$esubname = wfUrlencode( $subname );
	$subname = htmlspecialchars( $subname, ENT_QUOTES );
	$subpagelink = "<a title='$subname' href='https://en.wikipedia.org/wiki/Wikipedia:$eproj$esubname'>$subname</a>";
	$projlink = "<a title='$proj' href='https://en.wikipedia.org/wiki/Wikipedia:$eproj'>$proj</a>";
	$class = '';
	if ( $index%2 != 0 ) {
		$class = ' class="row-odd"';
	}
	$limit = $row['lim'];
	$category = htmlspecialchars( $row['category'], ENT_QUOTES );
	$histlink = "<a title='History' href='history.php?proj=$eproj'>hist</a>";
	$editlink = "<a title='Edit' href='config.php?proj=$eproj'>edit</a>";
	echo "<tr style='font-size:90%'><td$class>$projlink</td><td$class>$subpagelink</td><td$class>$limit</td>";
	echo "<td>$category</td><td>($histlink&nbsp;|&nbsp;$editlink)</td></tr>";
}

echo "</table>";
templatebottom();
