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
import datetime
from wikitools import wiki, page
import MySQLdb
import MySQLdb.cursors
import projectlister
import locale
import ConfigParser
import settings
import calendar
	
class TableMaker(object):
		
	def __init__(self, date=None):
		if not date:
			self.date = datetime.date.today()
			self.date = date-datetime.timedelta(days=20)
			self.date = date.replace(day=1)
		else:
			self.date = date
		
	def makeResults(self, manual=False):
		# Login
		site = wiki.Wiki()
		site.login(settings.bot, settings.botpass)
		site.setMaxlag(15)
		lister = projectlister.ProjectList()
		projects = lister.projects
		month = self.date.month
		year = self.date.year
		numdays = calendar.monthrange(year, month)[1]
		dbtable = self.date.strftime('pop_%b%y')
		db = MySQLdb.connect(host="enwiki.labsdb", db='p50380g50816__pop_stats', read_default_file="/data/project/popularpages/replica.my.cnf")
		db.autocommit(True)
		cursor = db.cursor(MySQLdb.cursors.SSCursor)
		skip = False
		for proj in projects:
			if proj.removed:
				continue
			# if proj.listpage != 'WikiProject Athletics/Popular pages':
				# if not skip:
					# continue
			# skip = True
			#if proj.listpage != 'WikiProject Military history/Australia, New Zealand and South Pacific military history task force/Popular pages':
			#	continue
			target = page.Page(site, proj.listpage, namespace=4)
			section = 0
			if target.exists:
				section = 1
			limit = proj.limit
			header = "This is a list of the top pages ordered by number of views in the scope of "+proj.name+".\n\n"
			header += "The data comes from data extracted from Wikipedia's [[Squid (software)|squid]] server logs. "
			header += "Note that due to the use of a different program than http://stats.grok.se/ the numbers here may differ from that site. For more information, "
			header += "leave a message on [[User talk:Mr.Z-man|this talk page]].\n\n"
			header += "You can view more results on the [[toollabs:popularpages/|Tool Labs tool]].\n\n"
			header += "'''Note:''' This data aggregates the views for all redirects to each page.\n\n"
			header += "==List==\n<!-- Changes made to this section will be overwritten on the next update. Do not change the name of this section. -->"
			header += "\nPeriod: "+str(year)+"-"+str(month).zfill(2)+"-01 &mdash; "+str(year)+"-"+str(month).zfill(2)+"-"+str(numdays)+" (UTC)\n\n"
			if not section:
				top = header + '{| class="wikitable sortable" style="text-align: right;"\n'
			else:
				top = "==List==\n<!-- Changes made to this section will be overwritten on the next update. Do not change the name of this section. -->"
				top += "\nPeriod: "+str(year)+"-"+str(month).zfill(2)+"-01 &mdash; "+str(year)+"-"+str(month).zfill(2)+"-"+str(numdays)+" (UTC)\n\n"
				top += '{| class="wikitable sortable" style="text-align: right;"\n'
			query = "SELECT ns, title, hits, project_assess FROM `"+dbtable+"` WHERE project_assess LIKE '%\""+proj.category+"\":%' ORDER BY hits DESC LIMIT "+str(limit)
			rows = cursor.execute(query)
			useImportance = True
			table = ''
			rank = 0
			for record in cursor:
				hits = locale.format("%.*f", (0,record[2]), True)
				avg = locale.format("%.*f", (0, record[2]/numdays ), True)					
				project_assess = eval(record[3])
				assess = project_assess[proj.category][0]
				if rank == 0 and project_assess[proj.category][1] is '':
					useImportance = False
				template = "{{class|"+assess+"}}"
				p = page.Page(site, title=record[1], check=False, followRedir=False, namespace=int(record[0]))
				rank+=1
				table+= "|-\n"
				table+= "| " + locale.format("%.*f", (0,rank), True) + "\n"
				table+= "| style='text-align: left;' | [[:" + p.title + "]]\n"
				table+= "| " + hits + "\n"
				table+= "| " + avg + "\n"
				table+= template + "\n"
				if useImportance:
					imp = project_assess[proj.category][1]
					tem = "{{importance|"+imp+"}}"
					table+= tem + "\n"
			if rank == 0:
				print proj.name, "is broken"
				continue
			table += "|}\n[[Category:Lists of popular pages by WikiProject]]"
			top+= '! Rank\n! Page\n! Views\n! Views (per day average)\n! Assessment\n'
			if useImportance:
				top+= '! Importance\n'
			table = top + table
			# target = page.Page(site, 'User:Mr.Z-man/Sandbox')
			# section=1
			res = target.edit(newtext=table.encode('utf-8'), summary="Popularity stats for "+proj.name, section=str(section))
			# print proj.category
			# print proj.listpage
			# if proj.listpage == 'WikiProject Military history/Australian military history task force/Popular pages':
				# break
			if 'new' in res['edit']:
				self.__notifyProject(proj.name, proj.listpage, site)
			cursor.nextset()
		if not manual:
			config = ConfigParser.ConfigParser()
			config.read('pop.ini')
			config.set('Finished', self.date.strftime('%b%y'), 'Finished')
			with open('pop.ini', 'wb') as configfile:
				config.write(configfile)
				
	def __notifyProject(self, proj, listpage, site):
		p = page.Page(site, proj, namespace=4)
		talk = p.toggleTalk()
		text = '\n{{subst:User:Mr.Z-man/np|%s|%s}}' % (proj, listpage)
		summary = 'Pageview stats'
		talk.edit(text=text, summary=summary, section='new')
		
		
