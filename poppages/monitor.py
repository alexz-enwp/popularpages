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
import MySQLdb
import datetime
import ConfigParser
import os
import pprint

# Monitor process

class Monitor(object):

	def check(self):
		status = {}
		d = datetime.datetime.utcnow()
		config = ConfigParser.ConfigParser()
		config.read(['pop.ini'])
		db1 = MySQLdb.connect(host="enwiki.labsdb", db='p50380g50816__pop_stats', read_default_file="/data/project/popularpages/replica.my.cnf")
		db1.autocommit(True)
		c1 = db1.cursor()
		db3 = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
		db3.autocommit(True)
		c3 = db3.cursor()
		# Check percent of titles with assessment filled
		table = 'pop_'+config.get('Main', 'assessmentdate')
		c1.execute("SELECT COUNT(*) FROM "+table)
		res = c1.fetchone()
		status['table_size'] = res[0]
		c1.execute("SELECT COUNT(*) FROM "+table+" WHERE project_assess IS NULL")
		res = c1.fetchone()
		status['table_unassessed'] = res[0]
		if status['table_size'] > 0 and d.day > 20 and float(status['table_unassessed'])/float(status['table_size']) > 0.75:
			print "WARNING: Assessment filler is not close to done. Percent done: ", str(round(float(status['table_unassessed'])/float(status['table_size'])))
		# Check FAIL.txt
		try:
			f = open('/data/project/popularpages/FAIL.txt', 'rb')
			print "WARNING: Fileprocessor error"
			print f.read()
		except:
			pass
		c3.execute("SELECT hour FROM "+table+" WHERE status='error'")
		res = c3.fetchall()
		status['errors'] = res
		if res:
			print "WARNING: Error on "+str(res)
		c3.execute("SELECT MAX(hour) FROM "+table+" WHERE status='done'")
		status['lasthour'] = c3.fetchone()
		if d.hour == 21:
			self.dailyreport(status)
	
	def dailyreport(self, status):
		print "Popular pages daily status report"
		pprint.pprint(status)
		#os.system('/usr/bin/qstat')
		f = open('/data/project/popularpages/pop.ini', 'rb')
		print f.read()
		
