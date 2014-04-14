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
/*
Would be nice:
* multiple titles, would need:
  * legend
  * title in fancy boxes
  * some way of handing overlapping points wrt boxes
*/

$(document).ready(function(){


function formSubmit() {
	$('#graph-sub').prop('disabled', 'disabled');
	$('#graph-form').unbind('submit');
	var request = {'action':'normalize',
		'title':$('#title').val(),
		'redir':$('#redir').prop('checked')
	}
	$.post("graphdata.php", request,
		function(data){
			if (data.error) {
				errormsg(data.error);
				$('#graph-form').submit(formSubmit);
				$('#graph-sub').removeProp('disabled');
			} else {
				$('#title').val(data.title);
				$('#graph-form').submit();
			}
		}, 
		"json"
	);	
	return false;
}

$('#graph-form').submit(formSubmit);

// when the scale is modified, gecko and webkit seem to have massively different definitions of an em
// This is a convienence function to handle switching the scale
CanvasRenderingContext2D.prototype.addText = function(text, x, y, rotation) {
	rotation = typeof(rotation) != 'undefined' ? rotation : 0;
	this.save();
	this.translate(x, y);
	this.scale(120.0/width, 70.0/height);
	this.rotate(rotation);
	this.fillText(text, 0, 0);
	this.restore();
}

if (!title || !start || !end) {
	return;
}
startpos = $.inArray(start, monthlist)
if ( startpos == -1 ) {
	errormsg('Error: Invalid start month');
	return;
}
endpos = $.inArray(end, monthlist)
if ( endpos == -1 ) {
	errormsg('Error: Invalid end month');
	return;
}
if ( endpos <= startpos) {
	errormsg('Error: End month must come after start month');
	return;
}

rescale = false;
statusMsg("Setting up graph");
width = document.documentElement.clientWidth - 300;
labeltextsize = width/668.0;
smalltextsize = width/1002.0;
height = 0.6*width;
$("#graph").attr('height', height);
$("#graph").attr('width', width);
var canvas = document.getElementById('graph');
c = canvas.getContext('2d');
initCanvas();
addTitle();
fancyBoxes(canvas);
xaxis = setupXAxis(startpos, endpos);
finishEverything();
});

// Set up the canvas - anything that doesn't need any request-specific data
function initCanvas() {
	c.scale(width/120.0, height/70.0); // Grid = 120x70
	// Border and background color
	c.strokeStyle = '#190094';
	c.lineWidth = 0.4;
	c.lineJoin = 'round';
	c.fillStyle = '#D3D1E0';
	c.fillRect(1, 1, 118, 68);
	c.strokeRect(1, 1, 118, 68);
	// Axes
	c.lineWidth = 0.15;
	c.strokeStyle = 'black';
	c.beginPath()
	c.moveTo(15,7);
	c.lineTo(15, 57);
	c.lineTo(115, 57);
	c.stroke();
	// axis labels
	c.fillStyle = 'black';
	c.font = '800 '+labeltextsize+'em Helvetica, sans-serif';
	c.addText("Views", 5, 32, Math.PI * 1.5);
	c.addText("Month", 60, 68);
} 

function addTitle() {
	c.save()
	c.textBaseline = 'top';
	c.font = '900 '+labeltextsize+'em Verdana, sans-serif';
	c.shadowColor = '#B0ACC2';
	c.shadowOffsetX = 2;
	c.shadowOffsetY = 2;
	c.addText('Pageviews for "'+title.replace(/\_/g, ' ')+'"', 18, 2);
	c.restore();
}

function fancyBoxes(canvas) {
	pointinfo = [];
	currentlyopen = [];
	$('#graph').mousemove(function(e) {
		var xpos = (e.clientX - canvas.offsetLeft + document.body.scrollLeft + document.documentElement.scrollLeft) / (width/120.0);
		var ypos = (e.clientY - canvas.offsetTop + document.body.scrollTop + document.documentElement.scrollTop) / (height/70.0);
		for (var i=0; i<pointinfo.length; i++) {
			var pt = pointinfo[i];
			if (Math.abs(xpos - pt.X) <= 1 &&  Math.abs(ypos - pt.Y) <= 1) {
				if (pt.X == currentlyopen[0] && pt.Y == currentlyopen[1]) {
					break;
				}
				currentlyopen = [pt.X, pt.Y];
				
				if ($('#moreinfo').length == 0) {
					doABox(pt, canvas);
				} else {
					$('#moreinfo').slideUp('fast', function() {
						$('#moreinfo').remove();					
						doABox(pt, canvas);
					});
				}
				break;
			}
		}
	});
	$('#graph').click(function(e) {
		$('#moreinfo').slideUp('fast', function() {
			$('#moreinfo').remove();
			currentlyopen = [];
		});		
	});

}

function doABox(pt, canvas) {
	var div = $('<div />').css({
		'display': 'none', 
		'background-color': '#F5F8FF',
		'font-size': '1.1em',
		'border': '0.3em ridge #00146E',
		'border-radius': '0.5em',
		'-moz-border-radius': '0.5em',
		'padding': '0.3em',
		'position': 'absolute'
	});
	div.attr('id', 'moreinfo');
	var content = '<span style="font-weight:bold; text-decoration:underline;">'+pt.month.replace(/(\D{3})(\d{2})/, "$1 20$2")+'</span>'
	content += '<br /><span style="font-weight:bold">Total hits:</span>&nbsp;'+addCommas(pt.hits);
	var month = pt.month.substr(0,3);
	content+= '<br /><span style="font-weight:bold">Average hits per day:</span>&nbsp;'+addCommas(Math.round(pt.hits/daysinmonth[month]));
	content+='<br />'
	var table = $('<table />').css('border-top', '1px solid black').addClass('fancybox');
	for (var cat in pt.data) {
		var proj = projectinfo[cat];
		if (typeof(proj) == "undefined") {
			continue;
		}
		var projd = proj;
		if (proj.indexOf('WikiProject') != -1) {
			projd = proj.split('WikiProject ')[1];
		}
		var url = 'http://en.wikipedia.org/wiki/Wikipedia:'+encodeURIComponent(proj);
		var link = $('<a />').attr('title', proj).text(projd).attr('href', url);
		var td1 = $('<td class="projname" />').append(link);
		assess = assesstemplates[pt.data[cat][0].toLowerCase()].replace('$1', cat);
		assess = assess.replace('$2', cat.replace(/\_/g, ' '));
		var td2 = $(assess);
		var td3 = $('<td></td>');
		if (pt.data[cat][1]) {
			importance = importancetemplates[pt.data[cat][1].toLowerCase()].replace('$1', cat);
			importance = importance.replace('$2', cat.replace(/\_/g, ' '));
			td3 = $(importance);
		}
		var row = $('<tr />').append(td1).append(td2).append(td3);
		table.append(row)
	}
	div.html(content);
	$("body").append(div);
	$('#moreinfo').append(table);
	var left = pt.X * (width/120.0) + canvas.offsetLeft;
	var top = pt.Y * (height/70.0) + canvas.offsetTop;
	if (pt.X > 60) {
		left-=div.outerWidth();
	}
	div.css({
		'top': top+'px',
		'left': left+'px'
	});
	div.slideDown('fast');
}

// Set up the X-axis with months
function setupXAxis(start, end) {
	// determine months
	var monthsingraph = new Array();
	for (var i=start; i<=end; i++) {
		monthsingraph.push(monthlist[i]);
	}
	spacebetween = 100.0/(monthsingraph.length - 1);
	var locations = {};
	c.beginPath();
	c.moveTo(15, 56);
	curx = 15;
	c.textAlign = 'end';
	c.font = '800 '+smalltextsize+'em Helvetica, sans-serif';
	// add marks
	for (var i=0; i<monthsingraph.length; i++) {
		var monthdisp = monthsingraph[i].replace(/(\D{3})(\d{2})/, "$1 20$2");
		locations[monthsingraph[i]] = i*spacebetween;
		c.lineTo(curx, 58);
		// Add grid line
		if (i != 0) {
			c.stroke();
			c.beginPath();
			c.moveTo(curx, 56);
			c.strokeStyle = '#B5B5B5';
			c.lineTo(curx, 7);
			c.stroke();
			// Reset for the label and next point
			c.beginPath();
		}
		c.strokeStyle = 'black';
		// if there are more than 36 months, skip every other label
		if (monthsingraph.length <= 36 || i%2 == 0) {
			c.addText(monthdisp, curx+1, 59, -0.25*Math.PI); //label
		}
		curx+=spacebetween;
		c.moveTo(curx, 56);
	}
	c.stroke();
	locations.index = monthsingraph;
	return locations;
}

// The sychronous part of the script ends here
function finishEverything() {
	yaxis = {};
	statusMsg("Retrieving data");
	months = [];
	for (var i=0; i<xaxis.index.length; i++) {
		months.push(xaxis.index[i]);
	}
	months = months.join('|');
	var request = {'monthlist':months, 'title':title};
	$.post("graphdata.php", request, function(data) {
		if (data.error) {
			errormsg("Error: "+data.error);
			return;
		} else {
			for (var i=0; i<xaxis.index.length; i++) {
				var m = xaxis.index[i];
				monthdata = data['data'][m];
				if (monthdata.error) {
					yaxis[m] = {'hits': monthdata.error};
				} else {
					yaxis[m] = {'hits': monthdata.hits, 'data': monthdata.pa};
				}
			}
			statusMsg("Finishing up");
			plotPoints();
		}
	});
}

// This plots the points and sets up the Y axis
function plotPoints() {
	var hits = new Array();
	for (var i=0; i<xaxis.index.length; i++) {
		if (!isNaN(parseInt(yaxis[xaxis.index[i]].hits))) {
			hits.push(yaxis[xaxis.index[i]].hits);
		}
	}
	hits.sort(sortNumber);
	var max = hits[0];
	var graphmax = 10;
	if (max >= 10 && max < 100) {
		graphmax =  Math.ceil(max/10)*10;
	} else if (max >= 100 && max < 1000) {
		graphmax =  Math.ceil(max/100)*100;
	} else if (max >= 1000 && max < 10000) {
		graphmax =  Math.ceil(max/1000)*1000;
	} else if (max >= 10000 && max < 100000) {
		graphmax =  Math.ceil(max/10000)*10000;
	} else if (max >= 100000) {
		graphmax =  Math.ceil(max/100000)*100000;
	}
	var graphscale = 50.0/graphmax;
	c.beginPath();
	var curY = 57
	c.moveTo(14, 57);
	c.textAlign = 'end';
	c.textBaseline = 'middle';
	c.font = '800 '+smalltextsize+'em Helvetica, sans-serif';
	for (var i=0; i<1; i+=0.1) {
		var num = Math.round(i*graphmax);
		if (num < 1000) {
			numdisp = num.toString();
		} else if (num >= 1000 && num < 1000000) {
			numdisp = (num/1000.0).toString()+'k';
		} else if ( num >= 1000000 ){
			numdisp = (num/1000000.0)+'M';
		}
		c.lineTo(16, curY);
		// Add grid line
		if (i != 0) {
			c.stroke();
			c.beginPath();
			c.moveTo(16, curY);
			c.strokeStyle = '#B5B5B5';
			c.lineTo(115, curY);
			c.stroke();
			// Reset for the label and next point
			c.beginPath();
		}
		c.strokeStyle = 'black';		
		c.addText(numdisp, 13, curY); //label
		curY-=5;
		c.moveTo(14, curY);	
	}
	c.stroke();
	c.fillStyle = '#006B39';
	c.strokeStyle = '#006B39';
	var lastPoint = false;
	var noresults = 0;
	for (var i=0; i<xaxis.index.length; i++) {
		var m = xaxis.index[i];
		if (yaxis[m].hits == 'noresult') {
			noresults++;
			lastPoint = false;
			continue;
		} else if (isNaN(parseInt(yaxis[m].hits))) {
			errormsg(yaxis[m].hits);
			lastPoint = false;
			continue;
		}
		if (!lastPoint) {
			c.beginPath();
			c.moveTo(xaxis[m]+15, yaxis[m].hits*graphscale);
			c.arc(xaxis[m]+15, 57-yaxis[m].hits*graphscale, 0.75, 0, (Math.PI/180)*360, false);
			c.fill();
			c.closePath();
			lastPoint = new Array(xaxis[m]+15, 57-yaxis[m].hits*graphscale);
			pointinfo.push( {'X':xaxis[m]+15, 'Y':57-yaxis[m].hits*graphscale, 'hits':yaxis[m].hits, 'data':yaxis[m].data, 'month':m } );
			continue;
		}
		var xval = xaxis[m]+15;
		var yval = 57-yaxis[m].hits*graphscale;
		setTimeout("drawPointAndLine("+xval+", "+yval+", "+lastPoint[0]+", "+lastPoint[1]+")", 50*i);
		pointinfo.push( {'X':xval, 'Y':yval, 'hits':yaxis[m].hits, 'data':yaxis[m].data, 'month':m } );
		lastPoint = new Array(xaxis[m]+15, 57-yaxis[m].hits*graphscale);
	}
	if (noresults == xaxis.index.length) {
		errormsg('No results found for "'+title.replace('_', ' ')+'"');
	} else {
		setTimeout('statusMsg("")', 50*i);
		setTimeout('addSaveLink()', 50*i);
		if (showstats && !rescale) {
			addStats();
		}
	}
	if (!rescale) {	
		function resizeGraph() {
			rescale = true;
			statusMsg("Resetting graph");
			$('#moreinfo').slideUp('fast', function() {
				$('#moreinfo').remove();
				currentlyopen = [];
			});
			width = document.documentElement.clientWidth - 300;
			labeltextsize = width/668.0;
			smalltextsize = width/1002.0;
			height = 0.5*width;
			$("#graph").attr('height', height);
			$("#graph").attr('width', width);
			var canvas = document.getElementById('graph');
			c = canvas.getContext('2d');
			initCanvas();
			addTitle();
			fancyBoxes(canvas);
			xaxis = setupXAxis(startpos, endpos);
			plotPoints();
			return false;
		}
		window.onresize = resizeGraph;
	}
}

function addSaveLink() {
	$('#save-image').remove();
	var imlink = $('<a />').attr('href', '#').attr('id', 'save-image');
	imlink.text('Save graph as image').css({'float':'right', 'margin-right':'3em'});
	if (!showstats) {
		$('#graph').after('<br />');
	}
	$('#graph').after(imlink);
	if (!showstats) {
		$('#graph').after('<br />');
	}
	$('#save-image').click(function() {
		var canvas = document.getElementById('graph');
		window.open(canvas.toDataURL('image/svg+xml'));
		return false;
	});
}

function drawPointAndLine(curX, curY, lastX, lastY) {
	c.beginPath();
	c.moveTo(lastX, lastY);
	c.lineTo(curX, curY);
	c.stroke();
	c.closePath();
	c.beginPath();
	c.arc(curX, curY, 0.75, 0, (Math.PI/180)*360, false);
	c.fill();
	c.closePath();
}

function addStats() {
	$('#stat-box').css({
		'margin': '0.2em',
		'border': '1px solid black',
		'width':'50%'
	});
	var s = start.replace(/(\D{3})(\d{2})/, "$1 20$2");
	var e = end.replace(/(\D{3})(\d{2})/, "$1 20$2");
	var beg = $("<span />").text('Statistics for "'+title.replace('_', ' ')+'" for '+s+' through '+e+':');
	beg.css({'font-weight':'bold', 'font-size':'125%'});
	var sum = 0;
	var hits = new Array();
	for (var i=0; i<xaxis.index.length; i++) {
		if (!isNaN(parseInt(yaxis[xaxis.index[i]].hits))) {
			hits.push(parseInt(yaxis[xaxis.index[i]].hits));
			sum+=parseInt(yaxis[xaxis.index[i]].hits);
		}
	}
	var len = parseFloat(hits.length);
	var mean = sum/len;
	var stdev = 0;
	for (var i=0; i<hits.length; i++) {
		stdev+= (hits[i]-mean)*(hits[i]-mean);
	}
	stdev /= len;
	stdev = Math.sqrt(stdev);
	hits.sort(sortNumber);
	if (len % 2 == 1) {
		var median = hits[(len+1)/2];
	} else {
		var median = (hits[(len+2)/2] + hits[len/2])/2;
	}
	
	var td1 = $('<td style="border:none" />').html('<b>Sum:</b>&nbsp;'+addCommas(Math.round(sum))).css('padding', '3px');
	var td2 = $('<td style="border:none" />').html('<b>Mean:</b>&nbsp;'+addCommas(Math.round(mean))).css('padding', '3px');
	var tr1 = $('<tr />').append(td1).append(td2);
	
	var td3 = $('<td style="border:none" />').html('<b>Median:</b>&nbsp;'+addCommas(Math.round(median))).css('padding', '3px');
	var td4 = $('<td style="border:none" />').html('<b>Standard deviation:</b>&nbsp;'+addCommas(Math.round(stdev))).css('padding', '3px');
	var tr2 = $('<tr />').append(td3).append(td4);
	var table = $('<table />').append(tr1).append(tr2);
	$('#stat-box').append(beg).append(table).css('padding', '3px');
}

function sortNumber(a, b) {
	return b-a;
}

function statusMsg(msg) {
	$('#status').text(msg).removeClass('result error').addClass('config-result');
}

function errormsg(msg) {
	$('#status').text(msg).removeClass('config-result').addClass('result error');
}

function addCommas(num) {
	num = num.toString();
	var reg = /(\d+)(\d{3})/;
	while (reg.test(num)) {
		num = num.replace(reg, '$1' + ',' + '$2');
	}
	return num;
}

daysinmonth = {
	'Jan':31,
	'Feb':28,
	'Mar':31,
	'Apr':30,
	'May':31,
	'Jun':30,
	'Jul':31,
	'Aug':31,
	'Sep':30,
	'Oct':31,
	'Nov':30,
	'Dec':31
}

assesstemplates = {
	'unassessed' : '<td class="assess unassessed"><a href="//en.wikipedia.org/wiki/Category:Unassessed_$1_articles" title="Category:Unassessed $2 articles">???</a></td>',
	'' : '<td class="assess unassessed"><a href="//en.wikipedia.org/wiki/Category:Unassessed_$1_articles" title="Category:Unassessed $2 articles">???</a></td>',
	'template' : '<td class="assess template"><a href="//en.wikipedia.org/wiki/Category:Template-Class_$1_articles" title="Category:Template-Class $2 articles">Template</a></td>',
	'category' : '<td class="assess category"><a href="//en.wikipedia.org/wiki/Category:Category-Class_$1_articles" title="Category:Category-Class $2 articles">Category</a></td>',
	'disambig' : '<td class="assess disambig"><a href="//en.wikipedia.org/wiki/Category:Disambig-Class_$1_articles" title="Category:Disambig-Class $2 articles">Disambig</a></td>',
	'file' : '<td class="assess file"><a href="//en.wikipedia.org/wiki/Category:File-Class_$1_articles" title="Category:File-Class $2 articles">File</a></td>',
	'image' : '<td class="assess file"><a href="//en.wikipedia.org/wiki/Category:Image-Class_$1_articles" title="Category:Image-Class $2 articles">Image</a></td>',
	'book' : '<td class="assess book"><a href="//en.wikipedia.org/wiki/Category:Book-Class_$1_articles" title="Category:Book-Class $2 articles">Book</a></td>',
	'list' : '<td class="assess list"><a href="//en.wikipedia.org/wiki/Category:List-Class_$1_articles" title="Category:List-Class $2 articles">List</a></td>',
	'non-article' : '<td class="assess na"><a href="//en.wikipedia.org/wiki/Category:NA-Class_$1_articles" title="Category:NA-Class $2 articles">NA</a></td>',
	'blank' : '<td class="assess na"><a href="//en.wikipedia.org/wiki/Category:NA-Class_$1_articles" title="Category:NA-Class $2 articles">NA</a></td>',
	'stub' : '<td class="assess stub"><a href="//en.wikipedia.org/wiki/Category:Stub-Class_$1_articles" title="Category:Stub-Class $2 articles">Stub</a></td>',
	'start' : '<td class="assess start"><a href="//en.wikipedia.org/wiki/Category:Start-Class_$1_articles" title="Category:Start-Class $2 articles">Start</a></td>',
	'c' : '<td class="assess c"><a href="//en.wikipedia.org/wiki/Category:C-Class_$1_articles" title="Category:C-Class $2 articles">C</a></td>',
	'b' : '<td class="assess b"><a href="//en.wikipedia.org/wiki/Category:B-Class_$1_articles" title="Category:B-Class $2 articles">B</a></td>',
	'ga' : '<td class="assess ga"><a href="//en.wikipedia.org/wiki/Category:GA-Class_$1_articles" title="Category:GA-Class $2 articles">GA</a></td>',
	'a' : '<td class="assess a"><a href="//en.wikipedia.org/wiki/Category:A-Class_$1_articles" title="Category:A-Class $2 articles">A</a></td>',
	'fa' : '<td class="assess fa"><a href="//en.wikipedia.org/wiki/Category:FA-Class_$1_articles" title="Category:FA-Class $2 articles">FA</a></td>',
	'fl' : '<td class="assess fl"><a href="//en.wikipedia.org/wiki/Category:FL-Class_$1_articles" title="Category:FL-Class $2 articles">FL</a></td>',
	'portal' : '<td class="assess portal"><a href="//en.wikipedia.org/wiki/Category:Portal-Class_$1_articles" title="Category:Portal-Class $2 articles">Portal</a></td>',
	'future' : '<td class="assess future"><a href="//en.wikipedia.org/wiki/Category:Future-Class_$1_articles" title="Category:Future-Class $2 articles">Future</a></td>',
	'merge' : '<td class="assess merge"><a href="//en.wikipedia.org/wiki/Category:Merge-Class_$1_articles" title="Category:Merge-Class $2 articles">Merge</a></td>',
	'needed' : '<td class="assess needed"><a href="//en.wikipedia.org/wiki/Category:Needed-Class_$1_articles" title="Category:Needed-Class $2 articles">Needed</a></td>'
};

importancetemplates = {
	'top' : '<td class="import top"><a href="//en.wikipedia.org/wiki/Category:Top-importance_$1_articles" title="Category:Top-importance $2 articles">Top</a></td>',
	'high' : '<td class="import high"><a href="//en.wikipedia.org/wiki/Category:High-importance_$1_articles" title="Category:High-importance $2 articles">High</a></td>',
	'mid' : '<td class="import mid"><a href="//en.wikipedia.org/wiki/Category:Mid-importance_$1_articles" title="Category:Mid-importance $2 articles">Mid</a></td>',
	'low' : '<td class="import low"><a href="//en.wikipedia.org/wiki/Category:Low-importance_$1_articles" title="Category:Low-importance $2 articles">Low</a></td>',
	'bottom' : '<td class="import bottom"><a href="//en.wikipedia.org/wiki/Category:Bottom-importance_$1_articles" title="Category:Bottom-importance $2 articles">Bottom</a></td>',
	'no' : '<td class="import no"><a href="//en.wikipedia.org/wiki/Category:No-importance_$1_articles" title="Category:No-importance $2 articles">No</a></td>',
	'na' : '<td class="import na"><a href="//en.wikipedia.org/wiki/Category:NA-importance_$1_articles" title="Category:NA-importance $2 articles">NA</a></td>',
	'unknown' : '<td class="import unknown"><a href="//en.wikipedia.org/wiki/Category:Unknown-importance_$1_articles" title="Category:Unknown-importance $2 articles">???</a></td>'
}
