!function($) {
    $.address = function() {
        var UNDEFINED, _frame, _trigger = function(name) {
            var ev = $.extend($.Event(name), function() {
                for (var parameters = {}, parameterNames = $.address.parameterNames(), i = 0, l = parameterNames.length; l > i; i++) parameters[parameterNames[i]] = $.address.parameter(parameterNames[i]);
                return {
                    value: $.address.value(),
                    path: $.address.path(),
                    pathNames: $.address.pathNames(),
                    parameterNames: parameterNames,
                    parameters: parameters,
                    queryString: $.address.queryString()
                };
            }.call($.address));
            return $($.address).trigger(ev), ev;
        }, _array = function(obj) {
            return Array.prototype.slice.call(obj);
        }, _bind = function() {
            return $().bind.apply($($.address), Array.prototype.slice.call(arguments)), $.address;
        }, _unbind = function() {
            return $().unbind.apply($($.address), Array.prototype.slice.call(arguments)), $.address;
        }, _supportsState = function() {
            return _h.pushState && _opts.state !== UNDEFINED;
        }, _hrefState = function() {
            return ("/" + _l.pathname.replace(new RegExp(_opts.state), "") + _l.search + (_hrefHash() ? "#" + _hrefHash() : "")).replace(_re, "/");
        }, _hrefHash = function() {
            var index = _l.href.indexOf("#");
            return -1 != index ? _crawl(_l.href.substr(index + 1), FALSE) : "";
        }, _href = function() {
            return _supportsState() ? _hrefState() : _hrefHash();
        }, _window = function() {
            try {
                return top.document !== UNDEFINED && top.document.title !== UNDEFINED ? top : window;
            } catch (e) {
                return window;
            }
        }, _js = function() {
            return "javascript";
        }, _strict = function(value) {
            return value = value.toString(), (_opts.strict && "/" != value.substr(0, 1) ? "/" : "") + value;
        }, _crawl = function(value, direction) {
            return _opts.crawlable && direction ? ("" !== value ? "!" : "") + value : value.replace(/^\!/, "");
        }, _cssint = function(el, value) {
            return parseInt(el.css(value), 10);
        }, _listen = function() {
            if (!_silent) {
                var hash = _href(), diff = decodeURI(_value) != decodeURI(hash);
                diff && (_msie && 7 > _version ? _l.reload() : (_msie && !_hashchange && _opts.history && _st(_html, 50), 
                _old = _value, _value = hash, _update(FALSE)));
            }
        }, _update = function(internal) {
            var changeEv = _trigger(CHANGE), xChangeEv = _trigger(internal ? INTERNAL_CHANGE : EXTERNAL_CHANGE);
            _st(_track, 10), (changeEv.isDefaultPrevented() || xChangeEv.isDefaultPrevented()) && _preventDefault();
        }, _preventDefault = function() {
            _value = _old, _supportsState() ? _h.popState({}, "", _opts.state.replace(/\/$/, "") + ("" === _value ? "/" : _value)) : (_silent = TRUE, 
            _webkit ? _opts.history ? _l.hash = "#" + _crawl(_value, TRUE) : _l.replace("#" + _crawl(_value, TRUE)) : _value != _href() && (_opts.history ? _l.hash = "#" + _crawl(_value, TRUE) : _l.replace("#" + _crawl(_value, TRUE))), 
            _msie && !_hashchange && _opts.history && _st(_html, 50), _webkit ? _st(function() {
                _silent = FALSE;
            }, 1) : _silent = FALSE);
        }, _track = function() {
            if ("null" !== _opts.tracker && _opts.tracker !== NULL) {
                var fn = $.isFunction(_opts.tracker) ? _opts.tracker : _t[_opts.tracker], value = (_l.pathname + _l.search + ($.address && !_supportsState() ? $.address.value() : "")).replace(/\/\//, "/").replace(/^\/$/, "");
                $.isFunction(fn) ? fn(value) : $.isFunction(_t.urchinTracker) ? _t.urchinTracker(value) : _t.pageTracker !== UNDEFINED && $.isFunction(_t.pageTracker._trackPageview) ? _t.pageTracker._trackPageview(value) : _t._gaq !== UNDEFINED && $.isFunction(_t._gaq.push) && _t._gaq.push([ "_trackPageview", decodeURI(value) ]);
            }
        }, _html = function() {
            var src = _js() + ":" + FALSE + ";document.open();document.writeln('<html><head><title>" + _d.title.replace(/\'/g, "\\'") + "</title><script>var " + ID + ' = "' + encodeURIComponent(_href()).replace(/\'/g, "\\'") + (_d.domain != _l.hostname ? '";document.domain="' + _d.domain : "") + "\";</script></head></html>');document.close();";
            7 > _version ? _frame.src = src : _frame.contentWindow.location.replace(src);
        }, _options = function() {
            if (_url && -1 != _qi) {
                var i, param, params = _url.substr(_qi + 1).split("&");
                for (i = 0; i < params.length; i++) param = params[i].split("="), /^(autoUpdate|crawlable|history|strict|wrap)$/.test(param[0]) && (_opts[param[0]] = isNaN(param[1]) ? /^(true|yes)$/i.test(param[1]) : 0 !== parseInt(param[1], 10)), 
                /^(state|tracker)$/.test(param[0]) && (_opts[param[0]] = param[1]);
                _url = NULL;
            }
            _old = _value, _value = _href();
        }, _load = function() {
            if (!_loaded) {
                _loaded = TRUE, _options();
                var complete = function() {
                    _enable.call(this), _unescape.call(this);
                }, body = $("body").ajaxComplete(complete);
                if (complete(), _opts.wrap) {
                    {
                        $("body > *").wrapAll('<div style="padding:' + (_cssint(body, "marginTop") + _cssint(body, "paddingTop")) + "px " + (_cssint(body, "marginRight") + _cssint(body, "paddingRight")) + "px " + (_cssint(body, "marginBottom") + _cssint(body, "paddingBottom")) + "px " + (_cssint(body, "marginLeft") + _cssint(body, "paddingLeft")) + 'px;" />').parent().wrap('<div id="' + ID + '" style="height:100%;overflow:auto;position:relative;' + (_webkit && !window.statusbar.visible ? "resize:both;" : "") + '" />');
                    }
                    $("html, body").css({
                        height: "100%",
                        margin: 0,
                        padding: 0,
                        overflow: "hidden"
                    }), _webkit && $('<style type="text/css" />').appendTo("head").text("#" + ID + "::-webkit-resizer { background-color: #fff; }");
                }
                if (_msie && !_hashchange) {
                    var frameset = _d.getElementsByTagName("frameset")[0];
                    _frame = _d.createElement((frameset ? "" : "i") + "frame"), _frame.src = _js() + ":" + FALSE, 
                    frameset ? (frameset.insertAdjacentElement("beforeEnd", _frame), frameset[frameset.cols ? "cols" : "rows"] += ",0", 
                    _frame.noResize = TRUE, _frame.frameBorder = _frame.frameSpacing = 0) : (_frame.style.display = "none", 
                    _frame.style.width = _frame.style.height = 0, _frame.tabIndex = -1, _d.body.insertAdjacentElement("afterBegin", _frame)), 
                    _st(function() {
                        $(_frame).bind("load", function() {
                            var win = _frame.contentWindow;
                            _old = _value, _value = win[ID] !== UNDEFINED ? win[ID] : "", _value != _href() && (_update(FALSE), 
                            _l.hash = _crawl(_value, TRUE));
                        }), _frame.contentWindow[ID] === UNDEFINED && _html();
                    }, 50);
                }
                _st(function() {
                    _trigger("init"), _update(FALSE);
                }, 1), _supportsState() || (_msie && _version > 7 || !_msie && _hashchange ? _t.addEventListener ? _t.addEventListener(HASH_CHANGE, _listen, FALSE) : _t.attachEvent && _t.attachEvent("on" + HASH_CHANGE, _listen) : _si(_listen, 50)), 
                "state" in window.history && $(window).trigger("popstate");
            }
        }, _enable = function() {
            var el, elements = $("a"), length = elements.size(), delay = 1, index = -1, sel = '[rel*="address:"]', fn = function() {
                ++index != length && (el = $(elements.get(index)), el.is(sel) && el.address(sel), 
                _st(fn, delay));
            };
            _st(fn, delay);
        }, _popstate = function() {
            decodeURI(_value) != decodeURI(_href()) && (_old = _value, _value = _href(), _update(FALSE));
        }, _unload = function() {
            _t.removeEventListener ? _t.removeEventListener(HASH_CHANGE, _listen, FALSE) : _t.detachEvent && _t.detachEvent("on" + HASH_CHANGE, _listen);
        }, _unescape = function() {
            if (_opts.crawlable) {
                var base = _l.pathname.replace(/\/$/, ""), fragment = "_escaped_fragment_";
                -1 != $("body").html().indexOf(fragment) && $('a[href]:not([href^=http]), a[href*="' + document.domain + '"]').each(function() {
                    var href = $(this).attr("href").replace(/^http:/, "").replace(new RegExp(base + "/?$"), "");
                    ("" === href || -1 != href.indexOf(fragment)) && $(this).attr("href", "#" + encodeURI(decodeURIComponent(href.replace(new RegExp("/(.*)\\?" + fragment + "=(.*)$"), "!$2"))));
                });
            }
        }, NULL = null, ID = "jQueryAddress", STRING = "string", HASH_CHANGE = "hashchange", INIT = "init", CHANGE = "change", INTERNAL_CHANGE = "internalChange", EXTERNAL_CHANGE = "externalChange", TRUE = !0, FALSE = !1, _opts = {
            autoUpdate: TRUE,
            crawlable: FALSE,
            history: TRUE,
            strict: TRUE,
            wrap: FALSE
        }, _browser = $.browser, _version = parseFloat(_browser.version), _msie = !$.support.opacity, _webkit = _browser.webkit || _browser.safari, _t = _window(), _d = _t.document, _h = _t.history, _l = _t.location, _si = setInterval, _st = setTimeout, _re = /\/{2,9}/g, _agent = navigator.userAgent, _hashchange = "on" + HASH_CHANGE in _t, _url = $("script:last").attr("src"), _qi = _url ? _url.indexOf("?") : -1, _title = _d.title, _silent = FALSE, _loaded = FALSE, _juststart = TRUE, _updating = FALSE, _value = _href();
        if (_old = _value, _msie) {
            _version = parseFloat(_agent.substr(_agent.indexOf("MSIE") + 4)), _d.documentMode && _d.documentMode != _version && (_version = 8 != _d.documentMode ? 7 : 8);
            var pc = _d.onpropertychange;
            _d.onpropertychange = function() {
                pc && pc.call(_d), _d.title != _title && -1 != _d.title.indexOf("#" + _href()) && (_d.title = _title);
            };
        }
        if (_h.navigationMode && (_h.navigationMode = "compatible"), "complete" == document.readyState) var interval = setInterval(function() {
            $.address && (_load(), clearInterval(interval));
        }, 50); else _options(), $(_load);
        return $(window).bind("popstate", _popstate).bind("unload", _unload), {
            bind: function() {
                return _bind.apply(this, _array(arguments));
            },
            unbind: function() {
                return _unbind.apply(this, _array(arguments));
            },
            init: function() {
                return _bind.apply(this, [ INIT ].concat(_array(arguments)));
            },
            change: function() {
                return _bind.apply(this, [ CHANGE ].concat(_array(arguments)));
            },
            internalChange: function() {
                return _bind.apply(this, [ INTERNAL_CHANGE ].concat(_array(arguments)));
            },
            externalChange: function() {
                return _bind.apply(this, [ EXTERNAL_CHANGE ].concat(_array(arguments)));
            },
            baseURL: function() {
                var url = _l.href;
                return -1 != url.indexOf("#") && (url = url.substr(0, url.indexOf("#"))), /\/$/.test(url) && (url = url.substr(0, url.length - 1)), 
                url;
            },
            autoUpdate: function(value) {
                return value !== UNDEFINED ? (_opts.autoUpdate = value, this) : _opts.autoUpdate;
            },
            crawlable: function(value) {
                return value !== UNDEFINED ? (_opts.crawlable = value, this) : _opts.crawlable;
            },
            history: function(value) {
                return value !== UNDEFINED ? (_opts.history = value, this) : _opts.history;
            },
            state: function(value) {
                if (value !== UNDEFINED) {
                    _opts.state = value;
                    var hrefState = _hrefState();
                    return _opts.state !== UNDEFINED && (_h.pushState ? "/#/" == hrefState.substr(0, 3) && _l.replace(_opts.state.replace(/^\/$/, "") + hrefState.substr(2)) : "/" != hrefState && hrefState.replace(/^\/#/, "") != _hrefHash() && _st(function() {
                        _l.replace(_opts.state.replace(/^\/$/, "") + "/#" + hrefState);
                    }, 1)), this;
                }
                return _opts.state;
            },
            strict: function(value) {
                return value !== UNDEFINED ? (_opts.strict = value, this) : _opts.strict;
            },
            tracker: function(value) {
                return value !== UNDEFINED ? (_opts.tracker = value, this) : _opts.tracker;
            },
            wrap: function(value) {
                return value !== UNDEFINED ? (_opts.wrap = value, this) : _opts.wrap;
            },
            update: function() {
                return _updating = TRUE, this.value(_value), _updating = FALSE, this;
            },
            title: function(value) {
                return value !== UNDEFINED ? (_st(function() {
                    _title = _d.title = value, _juststart && _frame && _frame.contentWindow && _frame.contentWindow.document && (_frame.contentWindow.document.title = value, 
                    _juststart = FALSE);
                }, 50), this) : _d.title;
            },
            value: function(value) {
                if (value !== UNDEFINED) {
                    if (value = _strict(value), "/" == value && (value = ""), _value == value && !_updating) return;
                    return _old = _value, _value = value, (_opts.autoUpdate || _updating) && (_update(TRUE), 
                    _supportsState() ? _h[_opts.history ? "pushState" : "replaceState"]({}, "", _opts.state.replace(/\/$/, "") + ("" === _value ? "/" : _value)) : (_silent = TRUE, 
                    _webkit ? _opts.history ? _l.hash = "#" + _crawl(_value, TRUE) : _l.replace("#" + _crawl(_value, TRUE)) : _value != _href() && (_opts.history ? _l.hash = "#" + _crawl(_value, TRUE) : _l.replace("#" + _crawl(_value, TRUE))), 
                    _msie && !_hashchange && _opts.history && _st(_html, 50), _webkit ? _st(function() {
                        _silent = FALSE;
                    }, 1) : _silent = FALSE)), this;
                }
                return _strict(_value);
            },
            path: function(value) {
                if (value !== UNDEFINED) {
                    var qs = this.queryString(), hash = this.hash();
                    return this.value(value + (qs ? "?" + qs : "") + (hash ? "#" + hash : "")), this;
                }
                return _strict(_value).split("#")[0].split("?")[0];
            },
            pathNames: function() {
                var path = this.path(), names = path.replace(_re, "/").split("/");
                return ("/" == path.substr(0, 1) || 0 === path.length) && names.splice(0, 1), "/" == path.substr(path.length - 1, 1) && names.splice(names.length - 1, 1), 
                names;
            },
            queryString: function(value) {
                if (value !== UNDEFINED) {
                    var hash = this.hash();
                    return this.value(this.path() + (value ? "?" + value : "") + (hash ? "#" + hash : "")), 
                    this;
                }
                var arr = _value.split("?");
                return arr.slice(1, arr.length).join("?").split("#")[0];
            },
            parameter: function(name, value, append) {
                var i, params;
                if (value !== UNDEFINED) {
                    var names = this.parameterNames();
                    for (params = [], value = value === UNDEFINED || value === NULL ? "" : value.toString(), 
                    i = 0; i < names.length; i++) {
                        var n = names[i], v = this.parameter(n);
                        typeof v == STRING && (v = [ v ]), n == name && (v = value === NULL || "" === value ? [] : append ? v.concat([ value ]) : [ value ]);
                        for (var j = 0; j < v.length; j++) params.push(n + "=" + v[j]);
                    }
                    return -1 == $.inArray(name, names) && value !== NULL && "" !== value && params.push(name + "=" + value), 
                    this.queryString(params.join("&")), this;
                }
                if (value = this.queryString()) {
                    var r = [];
                    for (params = value.split("&"), i = 0; i < params.length; i++) {
                        var p = params[i].split("=");
                        p[0] == name && r.push(p.slice(1).join("="));
                    }
                    if (0 !== r.length) return 1 != r.length ? r : r[0];
                }
            },
            parameterNames: function() {
                var qs = this.queryString(), names = [];
                if (qs && -1 != qs.indexOf("=")) for (var params = qs.split("&"), i = 0; i < params.length; i++) {
                    var name = params[i].split("=")[0];
                    -1 == $.inArray(name, names) && names.push(name);
                }
                return names;
            },
            hash: function(value) {
                if (value !== UNDEFINED) return this.value(_value.split("#")[0] + (value ? "#" + value : "")), 
                this;
                var arr = _value.split("#");
                return arr.slice(1, arr.length).join("#");
            }
        };
    }(), $.fn.address = function(fn) {
        var sel;
        if ("string" == typeof fn && (sel = fn, fn = void 0), !$(this).attr("address")) {
            var f = function(e) {
                if (e.shiftKey || e.ctrlKey || e.metaKey || 2 == e.which) return !0;
                if ($(this).is("a")) {
                    e.preventDefault();
                    var value = fn ? fn.call(this) : /address:/.test($(this).attr("rel")) ? $(this).attr("rel").split("address:")[1].split(" ")[0] : void 0 === $.address.state() || /^\/?$/.test($.address.state()) ? $(this).attr("href").replace(/^(#\!?|\.)/, "") : $(this).attr("href").replace(new RegExp("^(.*" + $.address.state() + "|\\.)"), "");
                    $.address.value(value);
                }
            };
            $(sel ? sel : this).live("click", f).live("submit", function(e) {
                if ($(this).is("form")) {
                    e.preventDefault();
                    var action = $(this).attr("action"), value = fn ? fn.call(this) : (-1 != action.indexOf("?") ? action.replace(/&$/, "") : action + "?") + $(this).serialize();
                    $.address.value(value);
                }
            }).attr("address", !0);
        }
        return this;
    };
}(jQuery);