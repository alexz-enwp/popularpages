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


class ProjectList(object):

	__slots__ = ('projects')
	
	def __init__(self):
		db = MySQLdb.connect(host="tools-db", read_default_file="/data/project/popularpages/replica.my.cnf", db='s51401__pop_data')
		db.autocommit(True)
		cursor = db.cursor()
		cursor.execute('SELECT category, name, listpage, lim, removed FROM project_config')
		res = cursor.fetchall()
		self.projects = []
		for item in res:
			self.projects.append(Project(item))
		db.close()
			
class Project(object):

	__slots__ = ('category', 'name', 'listpage', 'limit', 'removed')

	def __init__(self, row):
		self.category = row[0] # This will be unused
		self.name = row[1]
		self.listpage = row[2]
		self.limit = row[3]
		self.removed = row[4]
		
