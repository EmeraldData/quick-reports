#!/bin/bash

REPO_NAME="report-creator"
BRICK_HEADS="brick02-head brick03-head brick04-head brick05-head brick06-head"
OSRF_WEB_ROOT="/openils/var/web"
GIT_LOCATION="/home/opensrf/$REPO_NAME"
PROD_BACKUP="/home/opensrf/report-creator-prod-backup"

# back up what's already installed
if [ ! -d "$PROD_BACKUP" ]; then
	echo "Production backup directory $PROD_BACKUP not found, creating it..."
	mkdir "$PROD_BACKUP"
fi
rsync -auv "$OSRF_WEB_ROOT"/"$REPO_NAME"/ "$PROD_BACKUP"/
date > "$PROD_BACKUP"/last_sync_date

# quick sed to change the prod URL in a config file
sed -i 's/next.gapines.org/gapines.org/' $GIT_LOCATION/config/production.config.php

# now copy the git files over to the web dir
rsync -auv --exclude="install*.sh" --exclude=".git" "$GIT_LOCATION"/ "$OSRF_WEB_ROOT"/"$REPO_NAME"/

# and now do the rest of the bricks
for i in $BRICK_HEADS; do
	rsync -auvz --exclude="install*.sh" --exclude=".git" "$OSRF_WEB_ROOT"/"$REPO_NAME"/ "$i":"$OSRF_WEB_ROOT"/"$REPO_NAME"/
done
