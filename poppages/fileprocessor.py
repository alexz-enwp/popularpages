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
import urllib
import httplib
import subprocess
import ConfigParser
import MySQLdb
import time
import sys
	
class FileProcessor(object):

	def __init__(self, filename=None, tablename=None):
		os.chdir('/data/project/popularpages/')
		if filename:
			# TODO: Add fancy processing in case the flie isn't nicely named
			self.todo = datetime.datetime.strptime(filename, 'pagecounts-%Y%m%d-%H0000.gz')
		else:
			self.todo = datetime.datetime.utcnow()
		self.todo = self.todo.replace(minute = 0, second=0, microsecond=0)
		# Note, the timestamp of the datafile is the hour it was published, not collected
		# This means that the first page of the month is actually data from the previous month
		# This is accounted for in various places, including processCurrentFile() and processPage()

	def processCurrentFile(self, cmddate):
		# cmddate is the date/time when the command was issued. In most cases, this should be
		# equivelent to self.todo, but this will prevent potential issues if jobs aren't executed
		# immediately after submission, which can happen if the servers are overloaded
		# Get current status
		config = ConfigParser.ConfigParser()
		config.read('pop.ini')		
		curmonth = config.get('Main', 'filesdate')
		table = 'pop_'+curmonth
		cmddate = datetime.datetime.strptime(cmddate, '%Y-%m-%d-%H')
		cmddate = cmddate.replace(minute = 0, second=0, microsecond=0)
		if cmddate != self.todo:
			return
		hours = self.getHours(table)
		# Update the config now - this way if we get backed up, it can run 
		# concurrently without trying to run the same file twice
		db = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
		db.autocommit(True)
		cursor = db.cursor()
		query1 = "UPDATE "+table+" SET status='in progress' WHERE hour=%s"
		for h in hours:
			cursor.execute(query1, h)	
		db.close()
		next = self.todo + datetime.timedelta(hours=1)
		if next.day == 1 and next.hour == 1:
			config.set('Main', 'filesdate', next.strftime('%b%y'))
			config.set('Finished', next.strftime('%b%y'), 'In progress')			
		with open('pop.ini', 'wb') as configfile:
			config.write(configfile)
		ndquery = "UPDATE "+table+" SET status='not done' WHERE hour=%s"
		dquery = "UPDATE "+table+" SET status='done' WHERE hour=%s"
		equery = "UPDATE "+table+" SET status='error' WHERE hour=%s"
		tablename = str(int(time.time()*10000000))
		self.createTable(tablename)
		date = self.todo
		if self.todo.day == 1 and self.todo.hour == 0:
			date = date-datetime.timedelta(hours=2)
		arg = '--agg='+tablename+'-'+date.strftime("%b%y")
		proc2 = subprocess.Popen(['/data/project/popularpages/bot/bin/python', '/data/project/popularpages/lib/popularity5.py', arg], stderr=subprocess.PIPE)
		for h in hours:
			f = self.__getFile(h)
			if f is 0:
				db = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
				db.autocommit(True)
				cursor = db.cursor()
				cursor.execute(ndquery, h)
				db.close()
				continue
			try:
				self.processPage(f, tablename)
				db = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
				db.autocommit(True)
				cursor = db.cursor()
				cursor.execute(dquery, h)
				db.close()
			except:
				db = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
				db.autocommit(True)
				cursor = db.cursor()
				cursor.execute(equery, h)
				print sys.exc_info()
				db.close()
		(out, err) = proc2.communicate(input=None)
		if proc2.returncode is not 0:
			f = open('/data/project/popularpages/FAIL.txt', 'ab')
			f.write(str(self.todo)+'\n'+err)
			f.close()
			raise Exception
		else:
			db = MySQLdb.connect(host="tools-db", db='s51401__pop_temp', read_default_file="/data/project/popularpages/replica.my.cnf")
			db.autocommit(True)
			cursor = db.cursor()
			cursor.execute("SELECT COUNT(*) FROM `%s`" % (tablename))
			res = cursor.fetchone()
			if int(res[0]) != 0:
				f = open('/data/project/popularpages/FAIL.txt', 'ab')
				f.write('%s is not empty!\n' % (tablename))
				f.close()
			else:
				cursor.execute("DROP TABLE `%s`" % (tablename))
			db.close()
		# But we wait until we're actually done to set this so the background process doesn't stop prematurely
		if next.day == 1 and next.hour == 1:
			db = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
			db.autocommit(True)
			cursor = db.cursor()
			donecheck = "SELECT COUNT(*) FROM `"+table+"` WHERE status != 'done'"
			cursor.execute(donecheck)
			res = cursor.fetchone()
			if int(res[0]) == 0:	
				config = ConfigParser.ConfigParser()
				config.read('pop.ini')
				config.set('Finished', curmonth, 'Updated')
				with open('pop.ini', 'wb') as configfile:
					config.write(configfile)
			else:
				f = open('/data/project/popularpages/FAIL.txt', 'ab')
				f.write(str(int(res[0]))+' files not done\n')
				f.close()
		return next
		
	def processPage(self, filename, tablename):
		proc = subprocess.Popen(['/data/project/popularpages/processpage', filename, tablename], stderr=subprocess.PIPE)
		(out, err) = proc.communicate(input=None)
		os.remove(filename)
		if proc.returncode is not 0:
			f = open('/data/project/popularpages/FAIL.txt', 'ab')
			f.write(str(self.todo)+'\n'+err)
			f.close()
			raise Exception
			
	def getHours(self, table):
		db = MySQLdb.connect(host="tools-db", db='s51401__pop_data', read_default_file="/data/project/popularpages/replica.my.cnf")
		db.autocommit(True)
		cursor = db.cursor()
		query = "SELECT hour FROM "+table+" WHERE hour <= %s AND status='not done'"
		rows = cursor.execute(query, self.todo)
		hours = []
		for x in range(0, rows):
			hours.append(cursor.fetchone()[0])
		db.close()
		return hours
	
	def createTable(self, tablename):
		db = MySQLdb.connect(host="tools-db", db='s51401__pop_temp', read_default_file="/data/project/popularpages/replica.my.cnf")
		db.autocommit(True)
		cursor = db.cursor()
		query = """CREATE TABLE `%s` (
  `ns` int(11) NOT NULL,
  `title` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `hits` int(11) NOT NULL,
  UNIQUE KEY `ns` (`ns`,`title`)
) ENGINE=Aria DEFAULT CHARSET=latin1 PAGE_CHECKSUM=0 ROW_FORMAT=PAGE TRANSACTIONAL=0""" % tablename
		cursor.execute(query)
		db.close()
	
	def __getFile(self, date=None):
		if not date:
			date = self.todo
		page = date.strftime('pagecounts-%Y%m%d-%H0000.gz')
		url = "http://dumps.wikimedia.org"
		main = date.strftime('/other/pagecounts-raw/%Y/%Y-%m/')
		if self.__checkExist(main+page):
			url += main + page
			filename = page
			urllib.urlretrieve(url, filename)
			return filename
		start = date.strftime('pagecounts-%Y%m%d-%H')
		end = '.gz'
		for x in range(1,100):
			mid = str(x).zfill(4)
			if self.__checkExist(main+start+mid+end):
				url += main+start+mid+end
				filename = start+mid+end
				urllib.urlretrieve(url, filename)
				return filename
		else:
			return 0
	
	def __checkExist(self, testurl):
		conn = httplib.HTTPConnection('dumps.wikimedia.org')
		conn.request('HEAD', testurl)
		r = conn.getresponse()
		if r.status == 404 or r.status == 500:
			conn.close()
			return False
		else:
			cl = int(r.getheader('content-length'))
			if cl < 26214400: #25 MB
				return False
			conn.close()
			return True

