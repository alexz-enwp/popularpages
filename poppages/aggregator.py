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
import multiprocessing
import sys
import traceback
from Queue import Empty

class Aggregator(object): 
	
	def update(self, tablename, date):
		# Initialize
		time.sleep(30)
		self.date = date
		config = ConfigParser.ConfigParser()
		config.read(['pop.ini'])
		self.excludednamespaces = config.get('Main', 'excluded').split(',')
		self.excludednamespaces = [int(n) for n in self.excludednamespaces]
		threads = int(config.get('Main', 'hc_threads'))
		db = MySQLdb.connect(host="tools-db", db='s51401__pop_temp', read_default_file="/data/project/popularpages/replica.my.cnf")
		db.autocommit(True)
		cursor = db.cursor()
		# Set up the processes
		q = multiprocessing.Queue()
		processes = []
		events = []
		for x in range(1,threads+1):
			f = '/data/project/popularpages/results'+str(x)+'.out'
			event = multiprocessing.Event()
			events.append(event)
			p = multiprocessing.Process(target=self.hitCount, args=(q, f, event))
			processes.append(p)
			p.start()
		tries = 0
		try:
			while True:
				if q.qsize() > threads*100: # For safety/performance, don't store too much in memory
					time.sleep(0.5)
				rows = cursor.execute("SELECT HIGH_PRIORITY ns, title, hits FROM `"+tablename+"` LIMIT 500") # Batch 1
				if rows == 0:
					tries += 1
					if tries > 10:
						time.sleep(30) # Queue.empty seems to be unreliable, this should be more than enough time to finish processing
						for x in range(1,threads+1): # Need to kill all the subprocesses or else it just hangs
							events[x-1].set()
						time.sleep(90)
						break
					else: #Restart the loop
						time.sleep(10)
						continue
				res = cursor.fetchall()
				items = []
				query = "DELETE FROM `"+tablename+"` WHERE ("
				for row in res:
					if items:
						query += ' OR '
					ns = int(row[0])
					title = row[1]
					hits = row[2]
					qstring = "(ns="+str(ns)+" AND title='"+db.escape_string(title)+"')"
					items.append((ns, title, hits))					
					query +=qstring
				query += ')'
				for item in items:
					q.put(item, False)
				cursor.execute(query)
				for x in range(1,threads+1): # Check that all processes are alive
					if not processes[x-1].is_alive():
						f = '/data/project/popularpages/results'+str(x)+'.out'
						event = multiprocessing.Event()
						events[x-1] = event
						p = multiprocessing.Process(target=self.hitCount, args=(q, f, event))
						processes[x-1] = p
						p.start()
		except SystemExit:
			raise
		except: # Error handling, try to put everything in the queue back in the temp table if we fail
			print sys.exc_info()[1]
			traceback.print_tb(sys.exc_info()[2])
			db = MySQLdb.connect(host="tools-db", db='s51401__pop_temp', read_default_file="/data/project/popularpages/replica.my.cnf")
			cursor = db.cursor()
			while True:
				try:
					res = q.get(False)
				except Empty:
					break
				ns = res[0]
				title = res[1]
				hits = res[2]
				cursor.execute("INSERT INTO pop_temp (ns, title, hits) VALUES (%s, %s, %s, %s)", (ns, title, hits))
			print "All cleaned up"
			sys.exit(1)

	def hitCount(self, q, outfile, event):
		db2 = MySQLdb.connect(host="enwiki.labsdb", db='enwiki_p', read_default_file="/data/project/popularpages/replica.my.cnf")
		db2.autocommit(True)
		c2 = db2.cursor()
		table = 'pop_'+self.date
		insquery = ''
		count = 0
		do_insert = False
		killed = False
		finish_query = True
		while True:
			try:
				querybits = ''
				data = {}
				if event.is_set():
					killed = True
					do_insert = True
				if not do_insert:
					for x in range(500): # Batch 2
						try:
							res = q.get(True, 2)
							ns = res[0]
							title = res[1]
							hits = res[2]
							if x != 0:
								querybits+= ' OR '
							querybits+= "(page_namespace="+str(ns)+" AND page_title='"+db2.escape_string(title)+"')"					
							data[(ns, title)] = hits
						except Empty:
							if querybits or count: # If the queue is empty, do the insert query with what we have
								do_insert = True
							break
					if querybits == '':
						continue
					query = "SELECT page_namespace, page_title, rd_namespace, rd_title FROM page LEFT JOIN redirect ON rd_from=page_id WHERE "+querybits
					c2.execute(query)
					while True:
						res = c2.fetchone()
						if not res:
							break
						ns = int(res[0])
						title = res[1]
						hits = data[(ns, title)]
						if res[3]:
							# Redirect resolving
							ns = int(res[2])
							title = res[3]
							if ns in self.excludednamespaces:
								continue
						if insquery:
							insquery += ','
						insquery += '(%s, "%s", %s)' % (ns, db2.escape_string(title), hits)
						count += 1
						if count >= 1000:
							do_insert = True
				if do_insert: # Batch 3
					if insquery: # If there's something to do
						try:
							db2.close()
						except:
							pass
						db = MySQLdb.connect(host="enwiki.labsdb", db='p50380g50816__pop_stats', read_default_file="/data/project/popularpages/replica.my.cnf")
						db.autocommit(True)
						c = db.cursor()
						if finish_query:
							insquery = "INSERT INTO "+table+" (ns, title, hits) VALUES "+insquery+' ON DUPLICATE KEY UPDATE hits=hits+VALUES(hits)'
							finish_query = False
						c.execute(insquery)
						try:
							db.close()
						except:
							pass
					if killed:
						break
					# Reset everything
					insquery = ''
					db2 = MySQLdb.connect(host="enwiki.labsdb", db='enwiki_p', read_default_file="/data/project/popularpages/replica.my.cnf")
					db2.autocommit(True)
					c2 = db2.cursor()
					count = 0	
					do_insert = False
					finish_query = True
			except:
				f = open(outfile, 'ab')
				f.write(insquery+'\n')
				f.write(str(sys.exc_info()[1])+'\n')
				traceback.print_tb(sys.exc_info()[2], file=f)
				f.close()
				do_insert = True
				if killed:
					break
