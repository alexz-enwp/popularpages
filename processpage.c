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
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <zlib.h>
#include <curl/curl.h>
#include <glib.h>
#include <my_global.h>
#include <mysql.h>

// arg1 = datafile
// arg2 = tablename

int main ( int argc, char * argv[] ) {
	// Initialization
	MYSQL *db;
	db = mysql_init(NULL);
	mysql_options(db, MYSQL_READ_DEFAULT_FILE, "/data/project/popularpages/replica.my.cnf");
	if (!mysql_real_connect(db, "tools-db", NULL, NULL, "s51401__pop_temp", 0, NULL, 0)) {
		fprintf(stderr, "Error %u: %s\n", mysql_errno(db), mysql_error(db));
		exit(1);
	}
	char *qstart, *params, *tablename;
	char query[100000];
	tablename = argv[2];
	qstart = malloc(60*sizeof(char));
	sprintf(qstart, "INSERT INTO `%s` (ns, title, hits) VALUES ", tablename);
	curl_global_init( CURL_GLOBAL_NOTHING );
	CURL *curlob = curl_easy_init();
	char *title, *hits, *line, *content, *escaped, *ns, *prefix, *temp, *temp2, *temp3, **bits, **arr, **parts;
	int hit = 0, i = 0, j = 0, counter = 0;
	unsigned int tlen;
	void *nslist;
	FILE *namespacelist;
	nslist = g_hash_table_new( g_str_hash, g_str_equal );
	namespacelist = fopen( "/data/project/popularpages/namespaces.txt", "rb" );
	while(1) {
		line = malloc(512*sizeof(char));
		line = fgets( line, 512, namespacelist );
		if (line == NULL) {
				break;
		}
		line = strtok( line, "\n");
		if (line == NULL) {
				break;
		}
		arr = g_strsplit( line, "|", 2);
		g_hash_table_insert( nslist, arr[0], arr[1]);
		free(line);
	}
	fclose(namespacelist);
	char* excluded[] = {"-2", "-1", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "15", "101", "109", "446", "447", "710", "711", "828", "829"};
	int exclen = sizeof(excluded)/sizeof(excluded[0]);
	// Open file
	gzFile datafile;
	datafile = gzopen( argv[1], "r" );
	//FILE *outlog;
	//outlog = fopen( "/data/project/popularpages/logfile", "wb" );
	while(1) {
		content = malloc(2000*sizeof(char));
		content = gzgets( datafile, content, 2000 );
		if (content == Z_NULL) {
			break;
		}
		if ( !g_str_has_prefix(content, "en ") ) {
			free(content);
			if (hit == 1) {
				// Do the last queries
				if (counter > 0) {
					temp3 = malloc(100000*sizeof(char));
					sprintf(temp3, "%s%s ON DUPLICATE KEY UPDATE hits=hits+VALUES(hits)", qstart, query);
					if (mysql_real_query(db, temp3, strlen(temp3)) != 0) {
						fprintf(stderr, "mysql_real_query Error: %u: %s\n", mysql_errno(db), mysql_error(db));
						exit(1);
					}
					free(temp3);
				}
				break;
			}
			continue;
		}
		//outlog = fopen( "/data/project/popularpages/logfile", "wb" );
		//fprintf(outlog, "%s", content);
		//fclose( outlog );
		hit = 1;
		bits = g_strsplit(content, " ", 0);
		title = bits[1];
		hits = bits[2];
		// Title normalization - unescape url encoding
		title = curl_easy_unescape( curlob, title, 0, NULL );
		// Strip everything after a #
		if ( strchr(title, '#') ) {
			title = g_strsplit( title, "#", 2)[0];
		}
		tlen = strlen(title);
		// Don't waste time on obviously invalid titles
		if (tlen > 300) {
			curl_free(title);
			free(content);
			continue;
		}
		// Capitalize first letter
		title[0] = toupper(title[0]);
		// Removing leading _
		if ( g_str_has_prefix( title, "_" ) ) {
			title = g_strsplit( title, "_", 2)[1];
		}
		// Basic heuristics to exclude more obvious garbage data:
		// Invalid title characters
		if (strpbrk(title, "<>[]|{}\n")) {
			curl_free(title);
			free(content);
			continue;
		}
		// A single colon followed by nothing or multiple colons are invalid titles and break NS detection
		if ((tlen == 1 && title[0] == ':') || strncmp(title, "::", 2) == 0) {
			curl_free(title);
			free(content);
			continue;
		}
		// Strip leading colon
		if ( g_str_has_prefix( title, ":" ) ) {
			title = g_strsplit( title, ":", 2)[1];
		}
		// Multiple spaces in a row
		if (strstr(title, "__") != NULL) {
			curl_free(title);
			free(content);
			continue;
		}
		// Exclude titles containing a URL, except for the redirects like [[http://]]
		if ((strstr(title, "http://") != NULL || strstr(title, "Http://") != NULL || strstr(title, "HTTP://") != NULL) && tlen > 7) {
			curl_free(title);
			free(content);
			continue;
		}
		if ((strstr(title, "https://") != NULL || strstr(title, "Https://") != NULL || strstr(title, "HTTPS://") != NULL) && tlen > 8) {
			curl_free(title);
			free(content);
			continue;
		}
		// Weird pseudo-links to wiki(p|m)edia
		if ((strstr(title, "wikipedia.org") != NULL || strstr(title, "wikimedia.org") != NULL ||
		  strstr(title, "Wikipedia.org") != NULL || strstr(title, "wikimedia.org") != NULL) &&
		  (strchr(title, '/') || strstr(title, "upload.wikimedia.org") != NULL)) {
			curl_free(title);
			free(content);
			continue;
		}
		// Things containing "/wiki/"
		if (strstr(title, "/wiki/") != NULL) {
			curl_free(title);
			free(content);
			continue;
		}
		// Titles like Keywords_* - there seem to be a lot of these, a broken bot maybe?
		// There is actually one valid title in this group, but it's just a redirect with no incoming links, <1 hit/day
		if (g_str_has_prefix(title, "Keywords_")) {
			curl_free(title);
			free(content);
			continue;
		}
		// Things containing ".php" - There are several legit articles/redirects with this,
		// so we only exclude things with content after the ".php" or have slashes other than
		// at the beginning
		if (strstr(title, ".php") != NULL && 
		 (!g_str_has_suffix(title, ".php" ) || (strchr(title, '/') && title[0] != '/') )) {
			curl_free(title);
			free(content);
			continue;
		}
		// Replace spaces with underscores
		if ( strchr(title, ' ') ) {
			for (i=0; i<tlen; i++) {
				if( title[i] == ' ') {
					title[i] = '_';
				}
			}
		}
		// Check for namespaces we don't want
		ns = "0";
		temp = malloc((tlen+1)*sizeof(char));
		temp2 = malloc((tlen+1)*sizeof(char));
		strcpy(temp, title);
		strcpy(temp2, title);
		curl_free(title);
		title = temp2;
		if ( strchr( temp, ':' ) ) {
			prefix = strtok(temp, ":");
			ns = g_hash_table_lookup( nslist, prefix );
			if (ns == NULL) {
				ns = "0";
			} else {
				title = strtok(NULL, "\0");
				if (title == NULL) {
					goto end;
				}
				tlen = strlen(title);
				for (j=0; j<exclen; j++) {
					if( strncmp(excluded[j], ns, 3) == 0 ) {
						goto end;
					}
				}
			}
		}
		// Build the query
		escaped = malloc((strlen(title)*2+1)*sizeof(char));
		mysql_real_escape_string(db, escaped, title, strlen(title));
		params = malloc((strlen(escaped)+100)*sizeof(char));
		sprintf(params, "(%d, '%s', %d)", atoi(ns), escaped, atoi(hits));
		temp3 = malloc(100000*sizeof(char));
		counter++;
		if (counter == 500) {
			sprintf(temp3, "%s%s,%s ON DUPLICATE KEY UPDATE hits=hits+VALUES(hits)", qstart, query, params);
			// Execute the query
			if (mysql_real_query(db, temp3, strlen(temp3)) != 0) {
				fprintf(stderr, "mysql_real_query Error: %u: %s\n", mysql_errno(db), mysql_error(db));
				exit(1);
			}
			counter = 0;
			query[0] = '\0';
		} else if (counter == 1) {
			sprintf(temp3, "%s%s", query, params);
			sprintf(query, "%s", temp3);
		} else {
			sprintf(temp3, "%s,%s", query, params);
			sprintf(query, "%s", temp3);
		}
		free(escaped);
		free(params);
		free(temp3);
		// Cleanup
		end:
		g_strfreev(bits);
		free(temp);
		free(temp2);
		free(content);
	}
	curl_easy_cleanup( curlob );
	curl_global_cleanup();
	return 0;
}
