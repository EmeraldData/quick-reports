#!/bin/bash
#
# Copyright (C) 2016 Georgia Public Library Service
# Chris Sharp <csharp@georgialibraries.org>
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
#
# A script to automate installation of the report-creator feature.

if [ "$(whoami)" != "root" ]; then
    echo "Must be root to run this script." && exit 1
fi

DISTRO="$(lsb_release -i | awk '{print $3}')"
RELEASE="$(lsb_release -r | awk '{print $2}')"
OSRF_WEB_ROOT="/openils/var/web"
if [ "$DISTRO" == "Ubuntu" ]; then
	if [ "$RELEASE" == "16.04" ]; then
		APT_TOOL="apt"
		PREREQS="php7.0 php7.0-gd php7.0-pgsql php-memcache php-pear"
		APACHE_RESTART="systemctl restart apache2.service"
	else
		APT_TOOL="apt-get"
		PREREQS="php5 php5-gd php5-pgsql php5-memcache php-pear"
		APACHE_RESTART="service apache2 restart"
	fi
else
	echo "You do not appear to be running Ubuntu.  Please install manually."
	exit 1
fi

# install prerequisites
$APT_TOOL install -y $PREREQS

# restart apache to activate PHP
$APACHE_RESTART

# create the report-creator directory
mkdir $OSRF_WEB_ROOT/report-creator
chown -R opensrf:opensrf $OSRF_WEB_ROOT/report-creator

echo "DirectoryIndex index.php" > $OSRF_WEB_ROOT/report-creator/.htaccess

echo "This script will not create the required tables in the database."
echo "Please run sql/quick_reports_setup.sql ONLY if you haven't already."

echo "Copying report-creator files into $OSRF_WEB_ROOT/report-creator."
rsync -auv --exclude="install*.sh" --exclude=".git" ./ $OSRF_WEB_ROOT/report-creator/
chown -R opensrf:opensrf $OSRF_WEB_ROOT/report-creator

echo "Now browse to https://www.yourdomain.tld/report-creator to complete setup."
echo "Refer to docs/Installation_Instructions.txt for details."
