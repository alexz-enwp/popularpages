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

// TODO:
// Check that the category doesn't already belong to another project

$(document).ready(function(){

	var mode = 'new';
	var position = 1;
	var current = {}
	
	$('input').on('keydown', function (e){
		if (e.keyCode == 13){
			doNext();
		}
	});
	
	$('#next-button').on('click', function() {
		doNext()
	});
	
	override = false;
	if (typeof $('#project-name').val() != 'undefined' && $('#project-name').val() != 'WikiProject Example') {
		if (typeof window.history.replaceState != "undefined") {
			window.history.replaceState( {}, document.getElementsByTagName('title')[0].innerHTML, '/popularpages/config.php?proj='+encodeURIComponent($('#project-name').val()));
		}
		override = true;
		$('#next-button').click();
	} else if (typeof $('#project-name').val() != 'undefined' && typeof window.history.replaceState != "undefined") {
		window.history.replaceState( {}, document.getElementsByTagName('title')[0].innerHTML, '/popularpages/config.php');
	}

	function doNext() {
		switch(position){
		case 1:
			checkProjectName();
			break;
		case 2:
			checkListPage();
			break;
		case 3:
			checkCategory();
			break;
		case 4:
			checkLimit();
			break;
		}
	}
	
	$('#prev-button').on('click', function() {
		switch(position){
		case 2:
			position = 1;
			$('#prev-button').attr('disabled', true);
			$('#project-name').attr('disabled', false);
			$('#listpage-section').hide('slow', function() { $('#project-name').focus() });
			break;
		case 3:
			position = 2;
			$('#listpage').attr('disabled', false);
			$('#category-section').hide('slow', function() { $('#listpage').focus() });	
			break;
		case 4:
			position = 3;
			$('#category').attr('disabled', false);
			$('#limit-section').hide('slow', function() { $('#category').focus() });
			break;
		case 5:
			position = 4;
			$('#limit').attr('disabled', false);
			$('#submit-button').attr('disabled', true);
			$('#next-button').attr('disabled', false);
			$('#all-done').hide('slow', function() { $('#limit').focus() });
			break;
		}
	});
	
	$('#submit-button').on('click', function() {
		$('#spinner2').css('visibility', 'visible')
		$('#submit-button').attr('disabled', true);
		url = "configbackend.php";
		if (mode == 'new') {
			var category = $('#category').val().match(/^([a-zA-Z]+)-Class (.+) articles$/)[2];
			params = {
				'action': 'submit',
				'category': category,
				'proj_name': $('#project-name').val(),
				'listpage': $('#listpage').val(),
				'lim':  $('#limit').val()
			}
			$.post(url, params)
			.done(function(data) {
				$('#spinner2').css('visibility', 'hidden')
				if (data['result'] == "error") {
					error("final", data['error']);
					return;
				}
				if (data['result'] == "success") {
					success("final", "Project successfully added!");
					$('#extra-info').show('slow');
					return;
				}
			});
		} else {
			params = {
				'action': 'submitedit',
				'proj_name': $('#project-name').val(),
			}
			if (current['listpage'] != $('#listpage').val()) {
				params['listpage'] = $('#listpage').val()
			}
			if (current['category'] != $('#category').val()) {
				category = $('#category').val().match(/^([a-zA-Z]+)-Class (.+) articles$/)[2];
				params['category'] = category;
			}
			if (parseInt(current['lim']) != parseInt($('#limit').val())) {
				params['lim'] = $('#limit').val(); 
			}
			$.post(url, params)
			.done(function(data) {
				$('#spinner2').css('visibility', 'hidden')
				if (data['result'] == "error") {
					error("final", data['error']);
					return;
				}
				if (data['result'] == "success") {
					success("final", "Project modified!");
					return;
				}
			});
		}
	});
	
	$(document).on( 'click', '#enable-edit', enableEdit);
	
	function enableEdit() {
		// Change to edit mode
		$('#spinner').css('visibility', 'visible')
		var pname = $('#project-name').val();
		if (pname.indexOf('Wikipedia:') == 0) {
			pname = pname.slice(10);
		}
		mode = 'edit';
		position = 2;
		//Pre-fill fields
		url = "configbackend.php";
		var data = {'action':'getprojectinfo',
			'project':pname
		}
		$.post(url, data)
		.fail(function(data) {
			error("project", "Unknown error. Try again later. If the problem persists, <a href='//en.wikipedia.org/wiki/User_talk:Mr.Z-man'>report it</a>");
		})
		.done(function(data) {
			if (data['result'] == 'error') {
				error("project", "MySQL error. Try again later. If the problem persists, <a href='//en.wikipedia.org/wiki/User_talk:Mr.Z-man'>report it</a>");
			} else {
				cat = data['category'].replace('_', ' ');
				cat = "B-Class "+cat+" articles"
				listpage = data['listpage'];
				listpage = listpage.split(pname+'/')[1];
				lim = data['lim'];
				$('#listpage').val(listpage)
				$('#category').val(cat);
				$('#limit').val(lim)
				current = {'category':cat,
					'listpage':listpage,
					'lim':lim
				}
				mode = 'edit';
				position = 2;
				$('#pname').text(pname);
				success("project", "Each setting is pre-filled with its current value");
				$('#prev-button').attr('disabled', false);
				$('#project-name').attr('disabled', true);
				$('#listpage-section').show('slow', function() { $('#listpage').focus() });
			}
		})
		return false;
	}


	function error(section, text) {
		$('#spinner').css('visibility', 'hidden')
		$('#'+section+'-result').html(text);
		$('#'+section+'-result').removeClass('success notice')
		$('#'+section+'-result').addClass('result error')
	}
	
	function notice(section, text) {
		$('#spinner').css('visibility', 'hidden')
		$('#'+section+'-result').html(text);
		$('#'+section+'-result').removeClass('success error')
		$('#'+section+'-result').addClass('result notice')
	}
	
	function success(section, text) {
		$('#spinner').css('visibility', 'hidden')
		$('#'+section+'-result').html(text);
		$('#'+section+'-result').removeClass('error notice')
		$('#'+section+'-result').addClass('result success')
	}
	
	function checkProjectName() {
		mode = 'new';
		$('#spinner').css('visibility', 'visible')
		var pname = $('#project-name').val();
		if (pname.indexOf('Wikipedia:') == 0) {
			pname = pname.slice(10);
		}
		// Check that it's not already signed up
		url = "configbackend.php";
		var data = {'action':'projectexists',
			'project':pname
		}
		$.post(url, data)
		.fail(function(data) {
			error("project", "Unknown error. Try again later. If the problem persists, <a href='//en.wikipedia.org/wiki/User_talk:Mr.Z-man'>report it</a>");
		})
		.done(function(data) {
			if (data['result'] == "error") {
				error("project", data['error']);
				return;
			}
			if (data['result'] == "true") {
				if (!override) {
					editlink = $('<a></a>').text('Edit?').attr('id', 'enable-edit').attr('href', '#')
					note = $('<span></span>').text("Project is already signed up. ").append(editlink)
					notice("project", note);
				} else {
					enableEdit()
				}
				return;
			}
			// Check that the project actually exists
			title = "Wikipedia:"+pname;
			var data = {'action':'query',
				'redirects':'1',
				'indexpageids':'1',
				'format':'json',
				'titles':title
			}
			url = "https://en.wikipedia.org/w/api.php?callback=?"
			$.getJSON(url, data)
			.done(function(data) {
				var pid = data['query']['pageids'][0];
				if (pid == "-1") {
					error("project", "No project found with that name!");
					return;
				}
				var title = data['query']['pages'][pid]['title'];
				pname = title.slice(10);
				$('#project-name').val(pname);
				success('project', '');
				position = 2;
				$('#prev-button').attr('disabled', false);
				$('#project-name').attr('disabled', true);
				$('#pname').text(title);
				$('#listpage-section').show('slow', function() { $('#listpage').focus() });			
			})
			.fail(function(data) {
				error("project", "Unknown error. Try again later. If the problem persists, <a href='//en.wikipedia.org/wiki/User_talk:Mr.Z-man'>report it</a>");
			})
		});
	}
	
	function checkListPage() {
		if (mode == 'edit' && current['listpage'] == $('#listpage').val()) {
			success('listpage', '');
			position = 3;
			$('#listpage').attr('disabled', true);
			$('#category-section').show('slow', function() { $('#category').focus() });
		} else {
			// Check the page doesn't exist but is a valid title
			$('#spinner').css('visibility', 'visible')
			var pname = $('#project-name').val();
			title = "Wikipedia:"+pname;
			title += '/' + $('#listpage').val()
				var data = {'action':'query',
				'indexpageids':'1',
				'format':'json',
				'titles':title
			}
			url = "https://en.wikipedia.org/w/api.php?callback=?"
			$.getJSON(url, data)
			.done(function(data) {
				var pid = data['query']['pageids'][0];
				if (pid != "-1") {
					error("listpage", "Page already exists!");
					return;
				}
				if (data['query']['pages'][pid]['invalid'] === "") {
					error("listpage", "Invalid title!");
					return;
				}
				success('listpage', '');
				position = 3;
				$('#listpage').attr('disabled', true);
				$('#category-section').show('slow', function() { $('#category').focus() });			
			})
			.fail(function(data) {
				error("listpage", "Unknown error. Try again later. If the problem persists, <a href='//en.wikipedia.org/wiki/User_talk:Mr.Z-man'>report it</a>");
			})
		}
	}
	
	function checkCategory() {
		if (mode == 'edit' && current['category'] == $('#category').val()) {
			success('category', '');
			position = 4;
			$('#category').attr('disabled', true);
			$('#limit-section').show('slow', function() { $('#limit').focus() });
		} else {
			$('#spinner').css('visibility', 'visible')
			var catname = $('#category').val();
			if (catname.indexOf('Category:') == 0) {
				catname = catname.slice(9);
			}
			// Check that it follows the correct format
			var patt = new RegExp('^([a-zA-Z]+)-Class (.+) articles$');
			if (!patt.test(catname)) {
				error("category", "This doesn't appear to be an assessment category!");
				return;
			}
			// Check that it's not already used by a known project
			params = {'action':'catcheck',
				'cat':$('#category').val().match(/^([a-zA-Z]+)-Class (.+) articles$/)[2]
			}
			url = "configbackend.php";
			$.post(url, params)
			.fail(function(data) {
				error("category", "Unknown error. Try again later. If the problem persists, <a href='//en.wikipedia.org/wiki/User_talk:Mr.Z-man'>report it</a>");
			})
			.done(function(data) {
				if (data['result'] == "error") {
					error("project", data['error']);
					return;
				}
				if (data['result'] == "true") {
					error("category", "This category is already associated with a known project: "+data['project']);
					return;
				}	
				// Check that the category exists
				title = "Category:"+catname;
				var data = {'action':'query',
					'indexpageids':'1',
					'format':'json',
					'titles':title
				}
				url = "https://en.wikipedia.org/w/api.php?callback=?"
				$.getJSON(url, data)
				.done(function(data) {
					var pid = data['query']['pageids'][0];
					if (pid == "-1") {
						error("category", "Category not found!");
						return;
					}
					var title = data['query']['pages'][pid]['title'];
					catname = title.slice(9);
					$('#category').val(catname);
					success('category', '');
					position = 4;
					$('#category').attr('disabled', true);
					$('#limit-section').show('slow', function() { $('#limit').focus() });			
				})
				.fail(function(data) {
					error("category", "Unknown error. Try again later. If the problem persists, <a href='//en.wikipedia.org/wiki/User_talk:Mr.Z-man'>report it</a>");
				})
			});
		}
	}
	
	function checkLimit() {
		// Validate the number
		var limit = parseInt($('#limit').val());
		if (limit <= 0 || limit > 1000 || isNaN(limit) ) {
			error('limit', 'Limit out of valid range!');
		} else {
			success('limit', '');
			position = 5;
			$('#limit').attr('disabled', true);
			$('#submit-button').attr('disabled', false);
			$('#next-button').attr('disabled', true);
			if (mode == 'edit') {
				$('#all-done').html('<p class="field-name">All set! Click "Submit" below to make the changes.</p>')
			} else {
				$('#all-done').html('<p class="field-name">All set! Click "Submit" below to add the project.</p>')
			}
			$('#all-done').show('slow');
		}
	}
	
});
