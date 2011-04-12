#/bin/sh

f=`dirname $0`
if [ "${f:0:1}" != "/" ]
	then f=$PWD/$f
fi
if [ -e /usr/bin/php-cgi ]
	then PHP="/usr/bin/php-cgi"
elif [ -e /opt/local/bin/php-cgi ]
	then PHP="/opt/local/bin/php-cgi"
else
	echo "Apparently PHP with CGI-support wasn't installed correctly."
	exit
fi
PATH=$PATH:/usr/sbin/:/opt/local/sbin/:/usr/local/sbin/:$f/
if [ ! $(which lighttpd) ]
	then echo "Couldn't find lighttpd!"
	exit
fi
echo " server.document-root = \"$f/\"
include \"$f/lighty_static.conf\"
server.errorlog = \"$f/lightylog.txt\"
fastcgi.server = ( \".php\" => ((\"bin-path\" => \"$PHP -c $f/localphp.ini \",
	\"socket\" => \"/tmp/farmphp.socket\",
	\"max-procs\" => 1)))" > $f/lighty.conf


echo 'trying to stop all running lighttpds...'
killall -INT -w -u `whoami` lighttpd 
echo 'Starting lighttpd'
lighttpd -f $f/lighty.conf
echo 'Started lighttpd, you can close this window now'
