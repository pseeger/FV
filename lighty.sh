#/bin/sh

f=`dirname $0`
if [ "${f:0:1}" != "/" ]
	then f=$PWD/$f
fi
if test -f /usr/sbin/lighttpd
	then LIGHTTPD=/usr/sbin/lighttpd
elif test -f $f/lighttpd
	then LIGHTTPD=$f/lighttpd
else
	echo "Couldn't find lighttpd!"
fi
echo "server.modules = (\"mod_fastcgi\", \"mod_rewrite\")
server.document-root = \"$f/\"
index-file.names = ( \"index.php\", \"main.php\")
server.port = 5000
url.rewrite-once = ( \"/plugins/([^/]+)/([^?]+.php)\??(.*)\" => \"http.php?plugin=\$1&url=\$2&\$3\")
fastcgi.server = ( \".php\" => ((\"bin-path\" => \"/usr/bin/php5-cgi -c $f/localphp.ini \",
	\"socket\" => \"/tmp/farmphp.socket\",
	\"max-procs\" => 1)))" > $f/lighty.conf
`$LIGHTTPD -f $f/lighty.conf`
