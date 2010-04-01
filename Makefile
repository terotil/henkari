# Copyleft 2002, Tero Tilus <tero@tilus.net>
#
# $Id: Makefile,v 1.13 2003-06-12 20:38:22 mediaseitti Exp $
#
# This file is part of Henkari, a website framework.
#
# Henkari is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.  
# 
# Henkari is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.  
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
# USA 

# Comment this out if you don't want to use LDP-stylesheet
LDP_DSL=-d /home/mediaseitti/usr/docbookx/ldp.dsl

# Version number
HENKARI_VERSION=`cat VERSION`

# Name of the release package
RELFILE=henkari_$(HENKARI_VERSION).tar.gz

all :

.PHONY : clean
clean : clean_doc
	-rm *~ lib/*~ root/*~ root/testdir/*~

.PHONY : clean_doc
clean_doc :
	-rm -f doc/html/* doc/*~ doc/*.ps doc/*.html doc/*.txt

.PHONY : doc
doc : clean_doc
	# Html split in multiple files
	docbook2html $(LDP_DSL) -o doc/html/ doc/manual.xml
	#-tidy -config doc/tidyconfig -modify -quiet doc/html/*html
	# Html in sigle file
	docbook2html $(LDP_DSL) -u -o doc/ doc/manual.xml
	#-tidy -config doc/tidyconfig -modify -quiet doc/manual.html
	# Raw text 
	-links -dump doc/manual.html > doc/manual.txt
	# ps
	#-docbook2ps -o doc/ doc/manual.xml

.PHONY : release
release : clean doc
	-rm -f ../$(RELFILE)
	tar --create --gzip --file=../$(RELFILE) --directory=../ --exclude=*CVS* --verbose --dereference henkari/VERSION henkari/COPYING henkari/TODO henkari/example.htaccess henkari/index.php henkari/config.php henkari/doc henkari/lib henkari/phplib/ henkari/root/.henkari henkari/root/.index henkari/root/_template.html henkari/root/testdir/
