var wpp = ( typeof wpp === 'object' ) ? wpp : {}; wpp.strings = ( typeof wpp_l10n === 'object' ? wpp_l10n : {} ) ;

jQuery.browser = {};
(function () {
  jQuery.browser.msie = false;
  jQuery.browser.version = 0;
  if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
    jQuery.browser.msie = true;
    jQuery.browser.version = RegExp.$1;
  }
})();