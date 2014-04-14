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
templatetop("WikiProject Popular pages lists", array( 'config.css' ), array(), '<a href="." title="Popular pages">Popular pages</a>');
require_once('commonphp/GlobalFunctions.php');
date_default_timezone_set('UTC');
?>
		<br />
		<div class="result notice">
		This page is still in development. Data for a few projects is not currently available.
		</div>
<?php
$assesstemplates = array(
'unassessed' => '<td class="assess unassessed"><a href="//en.wikipedia.org/wiki/Category:Unassessed_$1_articles" title="Category:Unassessed $2 articles">Unassessed</a></td>',
'' => '<td class="assess unassessed"><a href="//en.wikipedia.org/wiki/Category:Unassessed_$1_articles" title="Category:Unassessed $2 articles">Unassessed</a></td>',
'template' => '<td class="assess template"><a href="//en.wikipedia.org/wiki/Category:Template-Class_$1_articles" title="Category:Template-Class $2 articles">Template</a></td>',
'category' => '<td class="assess category"><a href="//en.wikipedia.org/wiki/Category:Category-Class_$1_articles" title="Category:Category-Class $2 articles">Category</a></td>',
'disambig' => '<td class="assess disambig"><a href="//en.wikipedia.org/wiki/Category:Disambig-Class_$1_articles" title="Category:Disambig-Class $2 articles">Disambig</a></td>',
'file' => '<td class="assess file"><a href="//en.wikipedia.org/wiki/Category:File-Class_$1_articles" title="Category:File-Class $2 articles">File</a></td>',
'image' => '<td class="assess file"><a href="//en.wikipedia.org/wiki/Category:Image-Class_$1_articles" title="Category:Image-Class $2 articles">Image</a></td>',
'book' => '<td class="assess book"><a href="//en.wikipedia.org/wiki/Category:Book-Class_$1_articles" title="Category:Book-Class $2 articles">Book</a></td>',
'list' => '<td class="assess list"><a href="//en.wikipedia.org/wiki/Category:List-Class_$1_articles" title="Category:List-Class $2 articles">List</a></td>',
'non-article' => '<td class="assess na"><a href="//en.wikipedia.org/wiki/Category:NA-Class_$1_articles" title="Category:NA-Class $2 articles">NA</a></td>',
'blank' => '<td class="assess na"><a href="//en.wikipedia.org/wiki/Category:NA-Class_$1_articles" title="Category:NA-Class $2 articles">NA</a></td>',
'stub' => '<td class="assess stub"><a href="//en.wikipedia.org/wiki/Category:Stub-Class_$1_articles" title="Category:Stub-Class $2 articles">Stub</a></td>',
'start' => '<td class="assess start"><a href="//en.wikipedia.org/wiki/Category:Start-Class_$1_articles" title="Category:Start-Class $2 articles">Start</a></td>',
'c' => '<td class="assess c"><a href="//en.wikipedia.org/wiki/Category:C-Class_$1_articles" title="Category:C-Class $2 articles">C</a></td>',
'b' => '<td class="assess b"><a href="//en.wikipedia.org/wiki/Category:B-Class_$1_articles" title="Category:B-Class $2 articles">B</a></td>',
'ga' => '<td class="assess ga"><a href="//en.wikipedia.org/wiki/Category:GA-Class_$1_articles" title="Category:GA-Class $2 articles">GA</a></td>',
'a' => '<td class="assess a"><a href="//en.wikipedia.org/wiki/Category:A-Class_$1_articles" title="Category:A-Class $2 articles">A</a></td>',
'fa' => '<td class="assess fa"><a href="//en.wikipedia.org/wiki/Category:FA-Class_$1_articles" title="Category:FA-Class $2 articles">FA</a></td>',
'fl' => '<td class="assess fl"><a href="//en.wikipedia.org/wiki/Category:FL-Class_$1_articles" title="Category:FL-Class $2 articles">FL</a></td>',
'portal' => '<td class="assess portal"><a href="//en.wikipedia.org/wiki/Category:Portal-Class_$1_articles" title="Category:Portal-Class $2 articles">Portal</a></td>',
'future' => '<td class="assess future"><a href="//en.wikipedia.org/wiki/Category:Future-Class_$1_articles" title="Category:Future-Class $2 articles">Future</a></td>',
'merge' => '<td class="assess merge"><a href="//en.wikipedia.org/wiki/Category:Merge-Class_$1_articles" title="Category:Merge-Class $2 articles">Merge</a></td>',
'needed' => '<td class="assess needed"><a href="//en.wikipedia.org/wiki/Category:Needed-Class_$1_articles" title="Category:Needed-Class $2 articles">Needed</a></td>'
);

$importancetemplates = array(
'top' => '<td class="import top"><a href="//en.wikipedia.org/wiki/Category:Top-importance_$1_articles" title="Category:Top-importance $2 articles">Top</a></td>',
'high' => '<td class="import high"><a href="//en.wikipedia.org/wiki/Category:High-importance_$1_articles" title="Category:High-importance $2 articles">High</a></td>',
'mid' => '<td class="import mid"><a href="//en.wikipedia.org/wiki/Category:Mid-importance_$1_articles" title="Category:Mid-importance $2 articles">Mid</a></td>',
'low' => '<td class="import low"><a href="//en.wikipedia.org/wiki/Category:Low-importance_$1_articles" title="Category:Low-importance $2 articles">Low</a></td>',
'bottom' => '<td class="import bottom"><a href="//en.wikipedia.org/wiki/Category:Bottom-importance_$1_articles" title="Category:Bottom-importance $2 articles">Bottom</a></td>',
'no' => '<td class="import no"><a href="//en.wikipedia.org/wiki/Category:No-importance_$1_articles" title="Category:No-importance $2 articles">No</a></td>',
'na' => '<td class="import na"><a href="//en.wikipedia.org/wiki/Category:NA-importance_$1_articles" title="Category:NA-importance $2 articles">NA</a></td>',
'unknown' => '<td class="import unknown"><a href="//en.wikipedia.org/wiki/Category:Unknown-importance_$1_articles" title="Category:Unknown-importance $2 articles">???</a></td>'
);

if ($_GET && isset($_GET['proj'])) {
	$proj = $_GET['proj'];
	require('commonphp/mysql.php');
	$db = mysql_connect( 'tools-db', $my_user, $my_pass );
	mysql_select_db( 's51401__pop_data', $db );
	$mproj = mysql_real_escape_string( $proj, $db );
	$res = mysql_query("SELECT name, category FROM project_config WHERE category='$mproj'", $db);
	if ( mysql_num_rows( $res ) != 1 ) {
		$proj = htmlspecialchars( $proj, ENT_QUOTES );
		errormsg( "Invalid project category '$proj'" );
	} else {
		$row = mysql_fetch_row( $res );
		$projname = htmlspecialchars( $row[0], ENT_QUOTES );
		$category = htmlspecialchars( $row[1], ENT_QUOTES );
		$category2 = str_replace( '_', ' ', $category);
		$dt = new DateTime();
		if ( isset( $_GET['month'] ) ) {
			$my = $_GET['month'];
			$ts = strptime( $my, '%b%y');
			if ( $ts ) {
				$month = $ts['tm_mon']+1;
				$year = $ts['tm_year']+1900;
				$dt = new DateTime();
				$dt->setDate( $year, $month, 1);
			}
			$table = 'pop_' . $dt->format('My');
		}
		$dt = date_create( $dt->format('Y-m-').'01' );
		$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
		if ($limit <=0 || $limit > 1500) {
			$limit = 100;
		}
		$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
		if ($offset < 0) {
			$offset = 0;
		}
		$table = 'pop_' . $dt->format('My');
		$viewform = $dt->format( 'F Y' );
		$urlform = $dt->format('My');
		echo "<h3>Pageview statistics for $projname &ndash; $viewform</h3>";
		$month = intval( $dt->format('m') );
		$year = intval( $dt->format('Y') );
		$today = new DateTime();
		if ( $dt->format('mY') == $today->format('mY') ) {
			$daysinmonth = intval( $today->format('j') );
		} else {
			$months = array( 1=>31, 2=>28, 3=>31, 4=>30, 5=>31, 6=>30, 7=>31, 8=>31, 9=>30, 10=>31, 11=>30, 12=>31 );
			$daysinmonth = $months[$month];
			if( $month == 2 && $year % 4 == 0 ) {
				$daysinmonth++;
			}
		}
		$db2 = mysql_connect( 'enwiki.labsdb', $my_user, $my_pass );
		mysql_select_db( 'p50380g50816__pop_stats', $db2 );
		if ( isset( $_GET['count'] ) && $_GET['count'] == 'true') {
			$query = "SELECT SUM(hits), COUNT(*) FROM $table WHERE project_assess LIKE '%\"{$mproj}\"%'";
			$res = mysql_query( $query, $db2 );
			$row = mysql_fetch_row($res);
			$value = (float) $row[0];
			$pagesval = (float) $row[1];
			$dispval = number_format( $value );
			$disppages = number_format( $pagesval );
			echo "<div style='border: 2px solid darkblue; padding: 1em; width:30%' id='countbox'>";
			echo "Total hits for project: <b>$dispval</b><br />";
			echo "Total pages in project: <b>$disppages</b>";
			echo "</div>";
		}
		$query = "SELECT ns, title, hits, project_assess FROM $table WHERE project_assess LIKE '%\"{$mproj}\":%' ORDER BY hits DESC LIMIT $limit OFFSET $offset";
		$res = mysql_query( $query, $db2 );
		$rows = mysql_num_rows ( $res );
		if ( $rows == $limit ) {
			$nextoff = $limit+$offset;
			$next = "(<a href='?proj=$mproj&amp;month=$urlform&amp;limit=$limit&amp;offset=$nextoff' title='Next $limit'>Next $limit</a>)";
		} else {
			$next = "(Next $limit)";
		}
		if ( $offset > 0 ) {
			$prevoff = $offset - $limit;
			if ( $prevoff < 0 ) {
				$prevoff = 0;
			}
			$prev = "(<a href='?proj=$mproj&amp;month=$urlform&amp;limit=$limit&amp;offset=$prevoff' title='Previous $limit'>Previous $limit</a>)";
		} else {
			$prev = "(Previous $limit)";
		} 
		echo "$prev&emsp;&emsp;$next<br />";
		echo "<a style='font-size:85%' href='#list-options' title='Change list options'>Change list options</a>";
		if ($rows != 0) {
			$row = mysql_fetch_assoc( $res );
			$useImportance = false;
			$pa_test = json_decode($row['project_assess'], true);
			if ($pa_test[$mproj][1]) {
				$useImportance = true;
			}
			$note = "<abbr title='Click the hitcount for an article to graphically show historical data'>*</abbr>";
			echo "<table class='result-table'>
				<tr><th>Rank</th><th>Page</th><th>Views$note</th><th>Views (per day average)</th><th>Assessment</th>";
			if ( $useImportance ) {
				echo '<th>Importance</th>';
			}
			unset ($output);
			echo "</tr>\n";
			$rank = 0+$offset;
			do {
				$rank++;
				echo "<tr>";
				echo "<td>$rank</td>";
				$title = $row['title'];
				$etitle = str_replace('_', ' ', htmlspecialchars( $title, ENT_QUOTES ) );
				$urltitle = wfUrlencode( $title );
				$link = "<a href='http://en.wikipedia.org/wiki/$urltitle' title='$etitle'>$etitle</a>";
				echo "<td class='res-t'>$link</td>";
				$hits = number_format( $row['hits'] );
				echo "<td><a href='graph.php?title=$urltitle&amp;start=Jul09&amp;end=$urlform' title='Track across months'>$hits</a></td>";
				$avg = number_format( round(  $row['hits']/$daysinmonth ) );
				echo "<td>$avg</td>";
				$projectassess = json_decode($row['project_assess'], true);
				$assessment = $projectassess[$mproj];
				echo str_replace(array('$1', '$2'), array($category, $category2), $assesstemplates[strtolower($assessment[0])]);
				if ($useImportance) {
					echo str_replace(array('$1', '$2'), array($category, $category2), $importancetemplates[strtolower($assessment[1])]);
				}
				unset ($output);
				echo "</tr>\n";				
			} while ( $row = mysql_fetch_assoc($res) );
			echo '</table>';
		} else{
			echo '<p style="font-size:110%; border: 1px solid grey"><i>No results to display</i></p>';
		}
		echo "$prev&emsp;&emsp;$next";
	}
}
?>	
		<fieldset id='list-options'>
		<legend>Select options for the list</legend>
		<form method='get' action='view.php'>
<?php
require('commonphp/mysql.php');
$db = mysql_connect( 'tools-db', $my_user, $my_pass );
mysql_select_db( 's51401__pop_data', $db );
$res = mysql_query('SELECT category, name FROM project_config ORDER BY name', $db);
$projects = array();
$defproj = isset($_GET['proj']) ? $_GET['proj'] : false;
$deflim = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$defmonth = isset($_GET['month']) ? $_GET['month'] : false;

while ( $row = mysql_fetch_assoc($res) ) {
	$projects[$row['category']] = array_slice( $row, 1 );
}
echo '<label for="proj"><b>Project:</b></label><br />' . "\n";
echo '<select id="proj" name="proj">' . "\n";
foreach ( $projects as $key => $info ) {
	$proj = htmlspecialchars($info['name'], ENT_QUOTES);
	if ($key == $defproj) {
		$sel = ' selected="selected"';
	} else {
		$sel = '';
	}
	$key = htmlspecialchars($key, ENT_QUOTES);
	echo "<option value='$key'$sel>$proj</option>\n";
}
echo "</select>";
echo '<br /><br />';
echo '<label for="month"><b>Month:</b></label><br />' . "\n";
echo '<select id="month" name="month">' . "\n";
$db2 = mysql_connect( 'enwiki.labsdb', $my_user, $my_pass );
mysql_select_db( 'p50380g50816__pop_stats', $db2 );
$res = mysql_query( "SHOW TABLES LIKE 'pop_%'", $db2 );
$rows = array();
while ($row = mysql_fetch_row($res)) {
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
rsort( $rows );
foreach ($rows as $dt) {
	$ddate = $dt->format('F Y');
	$code = $dt->format('My');
	if ($code == $defmonth) {
		$sel = ' selected="selected"';
	} else {
		$sel = '';
	}
	echo "<option value='$code'$sel>$ddate</option>\n";
}
$countchecked =  '';
if ( isset( $_GET['count'] ) && $_GET['count'] == 'true' ) {
	$countchecked = 'checked="checked"';
}
echo "</select>";
echo '<br /><br />';
echo '
<label for="limit"><b>List size:</b></label><br />
<input name="limit" type="number" id="limit" max="1500" value="'.$deflim.'" /><br />
<label for="includecount"><b>Show a summary count of the views for all pages in the project:</b></label>&nbsp;
<input name="count" type="checkbox" id="includecount" value="true" '.$countchecked.' />
<input type="hidden" name="offset" value="0" />
<br /><br />
<input type="submit" value="Submit" />
		</form>
		</fieldset>
';
templatebottom();
