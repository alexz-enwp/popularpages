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
import os
import datetime
import json
import MySQLdb
import ConfigParser

class Setup(object):

	def setup(self):
		os.chdir('/data/project/popularpages/')
		self.makeTable()
		self.makeJSList()
		
	def makeTable(self):
		date = datetime.datetime.utcnow()+datetime.timedelta(days=7)	
		table = date.strftime('pop_%b%y')
		db = MySQLdb.connect(host="enwiki.labsdb", db='p50380g50816__pop_stats', read_default_file="/data/project/popularpages/replica.my.cnf")
		db.autocommit(True)
		cursor = db.cursor()
		query1 = """CREATE TABLE `%s` (
			`ns` int(11) NOT NULL,
			`title` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
			`hits` int(10) NOT NULL DEFAULT '0',
			`project_assess` text,
			UNIQUE KEY `ns_title` (`ns`,`title`),
			FULLTEXT KEY `project_asssess` (`project_assess`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC""" % (table)
		cursor.execute(query1)
		db.close()
		db = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
		db.autocommit(True)
		cursor = db.cursor()
		query2 = """CREATE TABLE `%s` (
			`hour` DATETIME NOT NULL,
			`status` ENUM('not done', 'in progress', 'done', 'error') DEFAULT 'not done',
			UNIQUE KEY `hour` (`hour`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC""" % (table)
		cursor.execute(query2)
		query3 = "INSERT INTO "+table+" (hour, status) VALUES (%s, 'not done')"
		month = date.month
		year = date.year
		d = datetime.datetime(year=year, month=month, day=1, hour=1)
		while True:
			cursor.execute(query3, d)
			d = d+datetime.timedelta(hours=1)
			if d.month != month and d.hour > 0:
				break
		db.close()		
		config = ConfigParser.ConfigParser()
		config.read('pop.ini')
		config.set('Finished', date.strftime('%b%y'), 'Ready')
		with open('pop.ini', 'wb') as configfile:
			config.write(configfile)

	# Still needs to be updated, but can wait until frontend stuff is ready
	def makeJSList(self):
		db = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
		cursor = db.cursor()
		cursor.execute('SELECT category, name FROM project_config')
		projects = {}	
		while True:
			row = cursor.fetchone()
			if row:
				projects[row[0]] = row[1]
			else:
				break
		f = open('/data/project/popularpages/public_html/projectinfo.js', 'wb')
		f.write('var projectinfo = ')
		json.dump(projects, f)
		f.close()
		cursor.close()


