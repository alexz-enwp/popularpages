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
// if (!$_GET['override']) {
	// echo "This tool is down for maintenance, please try again later.";
	// return;
// }
?>
<fieldset>
<legend>Request/modify a popular pages report for a project</legend>

<p>To streamline the process of requesting monthly reports for projects and to provide a record of 
additions and changes, this tool uses <a href="https://en.wikipedia.org/wiki/OAuth">OAuth</a> to verify your Wikipedia username.</p>

<p>OAuth allows this program to verify your username without needing access to any of your private info.
The only information accessed is your username and basic account information such as editcount and registration date.</p>

<p>Only your username will be recorded, as a record of changes to project configurations.</p>

<p style="font-weight:bold;">To authorize this program, click:&nbsp;&nbsp;<a style="font-size:125%;" href="<?php echo $url; ?>">HERE</a>.</p> 

<p>For more information about OAuth in MediaWiki, see <a href="https://www.mediawiki.org/wiki/Special:MyLanguage/Help:OAuth">the FAQ</a> on mediawiki.org.</p>
</fieldset>
