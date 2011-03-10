#/bin/sh

f=`dirname $0`
if [ "${f:0:1}" != "/" ]
	then f=$PWD/$f
fi
PATH=$PATH:/usr/sbin/:/opt/local/sbin/:/usr/local/sbin/:$f/
if [ ! $(which lighttpd) ]
	then echo "Couldn't find lighttpd!"
	exit
fi
echo " server.document-root = \"$f/\"
include \"$f/lighty_static.conf\"
fastcgi.server = ( \".php\" => ((\"bin-path\" => \"/usr/bin/php5 -c $f/localphp.ini \",
	\"socket\" => \"/tmp/farmphp.socket\",
	\"max-procs\" => 1)))" > $f/lighty.conf


LIGHTTPD_PID=$(pidof lighttpd) 
if [ $LIGHTTPD_PID > 0 ]; then 
	echo 'stopping lighttp...'
	kill -INT $LIGHTTPD_PID 
	sleep 1 
fi
lighttpd -f $f/lighty.conf
