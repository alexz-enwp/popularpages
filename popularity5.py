#!/usr/bin/python
# -*- coding: utf-8 -*-
#	Copyright 2014 Alex Zaddach. (mrzmanwiki@gmail.com)
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.

import sys
import datetime
import time
import ConfigParser
	
# Run options:
# * --setup - runs setup
# * --bg - start background process
# * --agg=<tablename>-<date> - process a temporary table
# * --make-tables - makes the result tables and saves them to the wiki
# * --manual=page - manually run the given datapage
# * --monitor - check status
# * --date=datestring - process the current hour's file
	
if __name__ == '__main__':
	# Run setup - create new table, update the list for the website
	if len(sys.argv) > 1 and sys.argv[1] == '--setup':
		from poppages import popsetup
		s = popsetup.Setup()
		s.setup()
	# Manually generate tables
	elif len(sys.argv) > 1 and sys.argv[1] == '--make-tables':
		from poppages import tablemaker
		month = int(raw_input('Month: '))
		year = int(raw_input('Year: '))
		d = datetime.date(month=month, year=year, day=1)
		t = tablemaker.TableMaker(d)
		t.makeResults(manual=True)
	# Start background task
	elif len(sys.argv) > 1 and sys.argv[1] == '--bg':
		from poppages import background
		bg = background.Classifier()
		bg.update()
	# Aggregator process
	elif len(sys.argv) > 1 and sys.argv[1].startswith('--agg'):
		details = sys.argv[1].split('=')[1]
		tablename, date = details.split('-')
		from poppages import aggregator
		agg = aggregator.Aggregator()
		agg.update(tablename, date)
	# Manually process a page
	elif len(sys.argv) > 1 and sys.argv[1].startswith('--manual'):
		from poppages import fileprocessor
		filename = sys.argv[1].split('=')[1]
		p = fileprocessor.FileProcessor(filename)
		tablename = str(int(time.time()*10000000))
		p.createTable(tablename)
		p.processPage(filename, 'fast')
	# Check status
	elif len(sys.argv) > 1 and sys.argv[1] == '--monitor':
		from poppages import monitor
		m = monitor.Monitor()
		m.check()
	elif len(sys.argv) > 1 and sys.argv[1].startswith('--date'):
		date = sys.argv[1].split('=')[1]
		from poppages import fileprocessor
		p = fileprocessor.FileProcessor()
		p.processCurrentFile(date)
	else:
		sys.exit("Unrecognized option "+str(sys.argv))
