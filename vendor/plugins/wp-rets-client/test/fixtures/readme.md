```
wp user create test@rets.ci test@rets.ci --role=editor --user_pass=bvnyofvrrmvjilee
```
Test property updates.
```
curl --data @wp-content/plugins/wp-rets-client/test/fixtures/updateProperty.xml http://localhost/xmlrpc.php
```

A `wpp.updateProperty` request that does not specify the `ID` will take much longer on a large database since WordPress will need to find the post first.
```
curl --data @wp-content/plugins/wp-rets-client/test/fixtures/updateProperty-no-id.xml http://localhost/xmlrpc.php
```
