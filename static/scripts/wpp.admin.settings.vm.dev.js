define([ "require", "module", "exports" ], function(require, module) {
    console.info("Loaded %s module.", module.id), function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        d.getElementById(id) || (js = d.createElement(s), js.id = id, js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=373515126019844", 
        fjs.parentNode.insertBefore(js, fjs));
    }(document, "script", "facebook-jssdk");
});