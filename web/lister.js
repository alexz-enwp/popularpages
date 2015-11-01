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
// loading spinner thingy

$(document).ready(function(){

	searchnum = 0;
	
	$('#searchbox').on('keyup', function() {
		if (!$('#searchbox').val()) {
			return;
		}
		$('#list-spinner').css('visibility', 'visible')
		thissearch = searchnum;
		searchnum++
		params = {
			'action':'search',
			'like':$('#searchbox').val()
		}
		url = "configbackend.php";
		$.post(url, params)
		.done(function(data) {
			if ($('#searchnum').val() > thissearch) {
				return;
			}
			names = data['result'];
			if (names.length == 0) {
				$('#autofill').html("<span class='info'>No results found</span>");
			} else {
				var output = $('<ul></ul>');
				for(var i=0; i<names.length; i++) {
					link = $('<a></a>').attr('href', '?project='+encodeURIComponent(names[i])).text(names[i])
					li = $('<li></li>').append(link)
					output.append(li);
				}
				$('#autofill').empty();
				$('#autofill').append(output);
				$('#searchnum').val(thissearch);
				$('#list-spinner').css('visibility', 'hidden')
			}
		});
	
	});


});
