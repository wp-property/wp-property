# Usability Dynamics component build.
#
# Requires
# - upm: Node.js Usability Dynamics Package Manager tool. https://github.com/UsabilityDynamics/upm
# - yuidoc: Node.js tool for generating documentation. https://github.com/yui/yuidoc
# - jscoverage: Node.js tool for generating JavaScript usage reports. https://github.com/visionmedia/node-jscoverage
#
# @author potanin@UD
# @source https://gist.github.com/andypotanin/084f15057ba01a5cc385
# @version 0.0.1

test-all: clean document test-code

install:
	upm-install

push:
	yuidoc -q --configfile static/yuidoc.json
	upm-install
	upm-build
	upm-commit

update:
	upm-udpate
	upm-build
	yuidoc -q --configfile static/yuidoc.json

document:
	yuidoc -q --configfile static/yuidoc.json

lib-cov:
	jscoverage scripts static/codex/lib-cov

test-code:
	@NODE_ENV=test mocha \
  --timeout 200 \
  --ui exports \
  --reporter list \
  test/*.js

clean:
	rm -fr static/codex/lib-cov
	rm -fr components
	rm -fr ux/build