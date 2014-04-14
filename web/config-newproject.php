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
$proj = 'WikiProject Example';
if (isset($_GET['proj'])) {
	$proj = htmlspecialchars($_GET['proj'], ENT_QUOTES);
} elseif (isset($_COOKIE['WPPP-temp'])) {
	$proj = htmlspecialchars($_COOKIE['WPPP-temp'], ENT_QUOTES);
} 
?>
<fieldset>
<legend>Set up the popular pages report for a project</legend>
	<p>You can use this form to request or edit a popular pages report for a project you are associated with. Just answer a few simple questions.</p>
	
	<div id="project-section">
	<p class="field-name">What project is this? (Redirects will be automatically followed)</p>
	<label for="project-name">Wikipedia:</label>
	<input name="project-name" id="project-name" type="text" size="50" maxlength="200" value="<?php echo $proj; ?>"/>
	<div id="project-result"></div>
	
	</div>
	
	<div id="listpage-section" style="display:none">
	<p class="field-name">Where should the on-wiki list be saved to? (Page must not already exist)</p>
	<label for="listpage"><span id="pname"></span>/</label>
	<input name="listpage" id="listpage" type="text" size="50" maxlength="200" value="Popular pages"/>
	<div id="listpage-result"></div>
	
	</div>
	
	<div id="category-section" style="display:none">
	<p class="field-name">Enter the title of one of the project's assessment categories.</p>
	<label for="category">Category:</label>
	<input name="category" id="category" type="text" size="50" maxlength="200" value="B-Class example articles"/>
	<div id="category-result"></div>
	
	</div>
	
	<div id="limit-section" style="display:none">
	<p class="field-name">Set the upper limit on the number of entries in the on-wiki list. (max 1000)</p>
	<label for="limit">Limit:</label>
	<input name="limit" id="limit" type="number" min=10 max=1000 value=500 />
	<div id="limit-result"></div>
	
	</div>
	
	<div id="all-done" style="display:none"></div>
	<br />
	<input type="button" id="prev-button" value="Prev" disabled="disabled" />
	<input type="button" id="next-button" value="Next" />
	<img id="spinner" src="spinner.gif" alt="..." title="..." style="visibility:hidden" />
	<br /><br />
	<input type="button" id="submit-button" value="Submit" disabled="disabled" />
	<img id="spinner2" src="spinner.gif" alt="..." title="..." style="visibility:hidden" />
	<br />
	<div id="final-result"></div>
	<br />
	<p id="extra-info" style="display:none;">The on-wiki list will be created shortly after the beginning of next month. Please don't create the page in advance, to avoid confusing the bot.</p>
	
</fieldset>
