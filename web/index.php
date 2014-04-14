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
templatetop("WikiProject Popular pages lists", array( 'config.css' ));
require_once('commonphp/GlobalFunctions.php');
?>

		<br />
		<ul style="font-size: 125%; list-style-type:square; line-height:150%">
		<li><a href="view.php" title="View data">View lists of the hitcount data</a></li>
		<li><a href="graph.php" title="Track results">Make a graph of pageviews vs. month for a page</a></li>
		<li><a href="config.php" title="Config edit">Request an on-wiki list for a project or change project configuration</a></li>
		<li><a href="history.php" title="Config history">See recent additions and changes to project configuration</a></li>
		<li><a href="list.php" title="Project list">View the current list of projects</a></li>
		</ul>
	<p style="text-align:center"><a href="//en.wikipedia.org/wiki/User:Mr.Z-man/Popular_pages_FAQ" title="FAQ">Popular pages FAQ</a></p>
<?php
templatebottom();
