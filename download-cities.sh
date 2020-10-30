#!

BASE_DIR=`cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd`

curl -s -G http://dbpedia.org/sparql/ --data-urlencode query="`cat cities.sparql`" --data-urlencode output=json > $BASE_DIR/cities.json

