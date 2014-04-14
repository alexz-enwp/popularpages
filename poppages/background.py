#!/usr/bin/python
# -*- coding: latin1 -*-
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

import MySQLdb
import time
import ConfigParser
from datetime import datetime
#import tablemaker
import re
import multiprocessing
import sys
import json

class Classifier(object):

	# This object will handle adding importance/assessment ratings
	def update(self):
		# Initialize
		config = ConfigParser.ConfigParser()
		config.read(['pop.ini'])
		self.date = datetime.strptime(config.get('Main', 'assessmentdate'), "%b%y")
		date = self.date
		threads = int(config.get('Main', 'pa_threads'))
		db = MySQLdb.connect(host="enwiki.labsdb", db='p50380g50816__pop_stats', read_default_file="/data/project/popularpages/replica.my.cnf")
		db.autocommit(True)
		cursor = db.cursor()
		pool = multiprocessing.Pool(processes=threads)
		table = self.date.strftime('pop_%b%y')
		tt = (table,)
		count = 0
		query = "SELECT ns, title FROM %s WHERE project_assess IS NULL LIMIT 100" % (table)
		# Do groups of 100
		while True:
			count+=1
			rows = cursor.execute(query)
			if rows == 0L: 
				break
			res = cursor.fetchall()
			res = [row+tt for row in res]
			pool.map(assessmentFiller, res) # This would be difficult to do asynchronously
			if count == 10: # Since this has to open a file, don't do it every iteration
				count = 0
				config = ConfigParser.ConfigParser()
				config.read(['pop.ini'])
				tc = int(config.get('Main', 'pa_threads'))
				if tc != threads: # Add/remove some processes
					pool = multiprocessing.Pool(processes=threads)
					threads = tc		
		config = ConfigParser.ConfigParser()
		config.read('pop.ini')
		if config.get('Finished', self.date.strftime('%b%y')) == 'Updated':
			config.set('Finished', self.date.strftime('%b%y'), 'Assessed')
			year = self.date.year
			month = self.date.month + 1
			d = config.get('Main', 'hitcounterdate')
			config.set('Main', 'assessmentdate', d)
			with open('pop.ini', 'wb') as configfile:
				config.write(configfile)
			#t = tablemaker.TableMaker()
			#t.makeResults()
			sys.exit(1)
		else:
			time.sleep(120)
			sys.exit(1) # This way will SGE will restart it
	
def assessmentFiller(row):
	db1 = MySQLdb.connect(host="enwiki.labsdb", db='p50380g50816__pop_stats', read_default_file="/data/project/popularpages/replica.my.cnf")
	db1.autocommit(True)
	cursor = db1.cursor()
	db2 = MySQLdb.connect(host="enwiki.labsdb", db='enwiki_p', read_default_file="/data/project/popularpages/replica.my.cnf")
	db2.autocommit(True)
	c2 = db2.cursor()
	imppattern = re.compile('^([a-zA-Z]+)-importance_(.+)_articles$')
	assesspattern = re.compile('^([a-zA-Z]+)-Class_(.+)_articles$')
	ns = int(row[0])
	title = row[1]
	table = row[2]
	talkns = ns+1
	rows = c2.execute("SELECT cl_to FROM page LEFT JOIN categorylinks ON page_id=cl_from WHERE page_namespace=%s AND page_title=%s", (talkns, title))
	if not rows:
		cursor.execute("UPDATE "+table+" SET project_assess = %s WHERE title = %s AND ns = %s", ('', title, ns))
		return
	cats = c2.fetchall()
	if cats[0][0] is None:
		cursor.execute("UPDATE "+table+" SET project_assess = %s WHERE title = %s AND ns = %s", ('', title, ns))
		return
	assessment = {}
	importance = {}
	for c in cats:
		cat = c[0]
		assess = assesspattern.match(cat)
		if assess:
			assessment[assess.group(2)] = assess.group(1)
		imp = imppattern.match(cat)
		if imp:
			importance[imp.group(2)] = imp.group(1)
	projectinfo = {}
	aproj = set(assessment.keys())
	iproj = set(importance.keys())
	projects = aproj.union(iproj) # In case some projects have only assessment or importance
	for p in projects:
		a = ''
		i = ''
		if p in assessment:
			a = assessment[p]
		if p in importance:
			i = importance[p]
		p = p.replace("'", '').replace('"', '').replace(':', '') # Strip quote marks and colons, could cause problems
		projectinfo[p] = [a, i]
	project_assess = json.dumps(projectinfo)
	cursor.execute("UPDATE "+table+" SET project_assess = %s WHERE title = %s AND ns = %s", (project_assess, title, ns))
	
