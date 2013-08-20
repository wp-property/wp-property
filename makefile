REPORTER = list
JSON_FILE = static/all.json
HTML_FILE = static/coverage.html

test-all: clean document test-code

document:
	yuidoc

test-code:
	@NODE_ENV=test mocha \
  --timeout 200 \
  --ui exports \
  --reporter $(REPORTER) \
  test/*.js

clean:
	rm -fr static/assets/*
	rm -fr static/classes/*
	rm -fr static/files/*
	rm -fr static/modules/*
	rm -f static/api.js
	rm -f static/data.json
	rm -f static/index.html