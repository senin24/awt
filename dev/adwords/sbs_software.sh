#!/bin/sh

. ./.config.sh
{
	combine.pl advanced.txt website1.txt crossbrowser.txt testing1.txt software1.txt free.txt
	combine.pl advanced.txt website1.txt automation1.txt software1.txt free.txt
	combine.pl advanced.txt browser1.txt automation1.txt software1.txt free.txt
	combine.pl advanced.txt website1.txt uptime.txt monitoring1.txt software1.txt free.txt
} \
| filter \
| sort -u \
| csv
