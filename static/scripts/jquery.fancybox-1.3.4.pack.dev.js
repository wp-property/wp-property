!function(b) {
    var m, t, u, f, D, j, E, n, z, A, K, r, i, q = 0, e = {}, o = [], p = 0, d = {}, l = [], G = null, v = new Image(), J = /\.(jpg|gif|png|bmp|jpeg)(.*)?$/i, W = /[^\.]\.(swf)\s*$/i, L = 1, y = 0, s = "", h = !1, B = b.extend(b("<div/>")[0], {
        prop: 0
    }), M = b.browser.msie && b.browser.version < 7 && !window.XMLHttpRequest, N = function() {
        t.hide(), v.onerror = v.onload = null, G && G.abort(), m.empty();
    }, O = function() {
        !1 === e.onError(o, q, e) ? (t.hide(), h = !1) : (e.titleShow = !1, e.width = "auto", 
        e.height = "auto", m.html('<p id="fancybox-error">The requested content cannot be loaded.<br />Please try again later.</p>'), 
        F());
    }, I = function() {
        var c, g, k, C, P, w, a = o[q];
        if (N(), e = b.extend({}, b.fn.fancybox.defaults, "undefined" == typeof b(a).data("fancybox") ? e : b(a).data("fancybox")), 
        w = e.onStart(o, q, e), w === !1) h = !1; else if ("object" == typeof w && (e = b.extend(e, w)), 
        k = e.title || (a.nodeName ? b(a).attr("title") : a.title) || "", a.nodeName && !e.orig && (e.orig = b(a).children("img:first").length ? b(a).children("img:first") : b(a)), 
        "" === k && e.orig && e.titleFromAlt && (k = e.orig.attr("alt")), c = e.href || (a.nodeName ? b(a).attr("href") : a.href) || null, 
        (/^(?:javascript)/i.test(c) || "#" == c) && (c = null), e.type ? (g = e.type, c || (c = e.content)) : e.content ? g = "html" : c && (g = c.match(J) ? "image" : c.match(W) ? "swf" : b(a).hasClass("iframe") ? "iframe" : 0 === c.indexOf("#") ? "inline" : "ajax"), 
        g) switch ("inline" == g && (a = c.substr(c.indexOf("#")), g = b(a).length > 0 ? "inline" : "ajax"), 
        e.type = g, e.href = c, e.title = k, e.autoDimensions && ("html" == e.type || "inline" == e.type || "ajax" == e.type ? (e.width = "auto", 
        e.height = "auto") : e.autoDimensions = !1), e.modal && (e.overlayShow = !0, e.hideOnOverlayClick = !1, 
        e.hideOnContentClick = !1, e.enableEscapeButton = !1, e.showCloseButton = !1), e.padding = parseInt(e.padding, 10), 
        e.margin = parseInt(e.margin, 10), m.css("padding", e.padding + e.margin), b(".fancybox-inline-tmp").unbind("fancybox-cancel").bind("fancybox-change", function() {
            b(this).replaceWith(j.children());
        }), g) {
          case "html":
            m.html(e.content), F();
            break;

          case "inline":
            if (b(a).parent().is("#fancybox-content") === !0) {
                h = !1;
                break;
            }
            b('<div class="fancybox-inline-tmp" />').hide().insertBefore(b(a)).bind("fancybox-cleanup", function() {
                b(this).replaceWith(j.children());
            }).bind("fancybox-cancel", function() {
                b(this).replaceWith(m.children());
            }), b(a).appendTo(m), F();
            break;

          case "image":
            h = !1, b.fancybox.showActivity(), v = new Image(), v.onerror = function() {
                O();
            }, v.onload = function() {
                h = !0, v.onerror = v.onload = null, e.width = v.width, e.height = v.height, b("<img />").attr({
                    id: "fancybox-img",
                    src: v.src,
                    alt: e.title
                }).appendTo(m), Q();
            }, v.src = c;
            break;

          case "swf":
            e.scrolling = "no", C = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="' + e.width + '" height="' + e.height + '"><param name="movie" value="' + c + '"></param>', 
            P = "", b.each(e.swf, function(x, H) {
                C += '<param name="' + x + '" value="' + H + '"></param>', P += " " + x + '="' + H + '"';
            }), C += '<embed src="' + c + '" type="application/x-shockwave-flash" width="' + e.width + '" height="' + e.height + '"' + P + "></embed></object>", 
            m.html(C), F();
            break;

          case "ajax":
            h = !1, b.fancybox.showActivity(), e.ajax.win = e.ajax.success, G = b.ajax(b.extend({}, e.ajax, {
                url: c,
                data: e.ajax.data || {},
                error: function(x) {
                    x.status > 0 && O();
                },
                success: function(x, H, R) {
                    if (200 == ("object" == typeof R ? R : G).status) {
                        if ("function" == typeof e.ajax.win) {
                            if (w = e.ajax.win(c, x, H, R), w === !1) return void t.hide();
                            ("string" == typeof w || "object" == typeof w) && (x = w);
                        }
                        m.html(x), F();
                    }
                }
            }));
            break;

          case "iframe":
            Q();
        } else O();
    }, F = function() {
        var a = e.width, c = e.height;
        a = a.toString().indexOf("%") > -1 ? parseInt((b(window).width() - 2 * e.margin) * parseFloat(a) / 100, 10) + "px" : "auto" == a ? "auto" : a + "px", 
        c = c.toString().indexOf("%") > -1 ? parseInt((b(window).height() - 2 * e.margin) * parseFloat(c) / 100, 10) + "px" : "auto" == c ? "auto" : c + "px", 
        m.wrapInner('<div style="width:' + a + ";height:" + c + ";overflow: " + ("auto" == e.scrolling ? "auto" : "yes" == e.scrolling ? "scroll" : "hidden") + ';position:relative;"></div>'), 
        e.width = m.width(), e.height = m.height(), Q();
    }, Q = function() {
        var a, c;
        if (t.hide(), f.is(":visible") && !1 === d.onCleanup(l, p, d)) b.event.trigger("fancybox-cancel"), 
        h = !1; else {
            if (h = !0, b(j.add(u)).unbind(), b(window).unbind("resize.fb scroll.fb"), b(document).unbind("keydown.fb"), 
            f.is(":visible") && "outside" !== d.titlePosition && f.css("height", f.height()), 
            l = o, p = q, d = e, d.overlayShow ? (u.css({
                "background-color": d.overlayColor,
                opacity: d.overlayOpacity,
                cursor: d.hideOnOverlayClick ? "pointer" : "auto",
                height: b(document).height()
            }), u.is(":visible") || (M && b("select:not(#fancybox-tmp select)").filter(function() {
                return "hidden" !== this.style.visibility;
            }).css({
                visibility: "hidden"
            }).one("fancybox-cleanup", function() {
                this.style.visibility = "inherit";
            }), u.show())) : u.hide(), i = X(), s = d.title || "", y = 0, n.empty().removeAttr("style").removeClass(), 
            d.titleShow !== !1 && (a = b.isFunction(d.titleFormat) ? d.titleFormat(s, l, p, d) : s && s.length ? "float" == d.titlePosition ? '<table id="fancybox-title-float-wrap" cellpadding="0" cellspacing="0"><tr><td id="fancybox-title-float-left"></td><td id="fancybox-title-float-main">' + s + '</td><td id="fancybox-title-float-right"></td></tr></table>' : '<div id="fancybox-title-' + d.titlePosition + '">' + s + "</div>" : !1, 
            s = a, s && "" !== s)) switch (n.addClass("fancybox-title-" + d.titlePosition).html(s).appendTo("body").show(), 
            d.titlePosition) {
              case "inside":
                n.css({
                    width: i.width - 2 * d.padding,
                    marginLeft: d.padding,
                    marginRight: d.padding
                }), y = n.outerHeight(!0), n.appendTo(D), i.height += y;
                break;

              case "over":
                n.css({
                    marginLeft: d.padding,
                    width: i.width - 2 * d.padding,
                    bottom: d.padding
                }).appendTo(D);
                break;

              case "float":
                n.css("left", -1 * parseInt((n.width() - i.width - 40) / 2, 10)).appendTo(f);
                break;

              default:
                n.css({
                    width: i.width - 2 * d.padding,
                    paddingLeft: d.padding,
                    paddingRight: d.padding
                }).appendTo(f);
            }
            n.hide(), f.is(":visible") ? (b(E.add(z).add(A)).hide(), a = f.position(), r = {
                top: a.top,
                left: a.left,
                width: f.width(),
                height: f.height()
            }, c = r.width == i.width && r.height == i.height, j.fadeTo(d.changeFade, .3, function() {
                var g = function() {
                    j.html(m.contents()).fadeTo(d.changeFade, 1, S);
                };
                b.event.trigger("fancybox-change"), j.empty().removeAttr("filter").css({
                    "border-width": d.padding,
                    width: i.width - 2 * d.padding,
                    height: e.autoDimensions ? "auto" : i.height - y - 2 * d.padding
                }), c ? g() : (B.prop = 0, b(B).animate({
                    prop: 1
                }, {
                    duration: d.changeSpeed,
                    easing: d.easingChange,
                    step: T,
                    complete: g
                }));
            })) : (f.removeAttr("style"), j.css("border-width", d.padding), "elastic" == d.transitionIn ? (r = V(), 
            j.html(m.contents()), f.show(), d.opacity && (i.opacity = 0), B.prop = 0, b(B).animate({
                prop: 1
            }, {
                duration: d.speedIn,
                easing: d.easingIn,
                step: T,
                complete: S
            })) : ("inside" == d.titlePosition && y > 0 && n.show(), j.css({
                width: i.width - 2 * d.padding,
                height: e.autoDimensions ? "auto" : i.height - y - 2 * d.padding
            }).html(m.contents()), f.css(i).fadeIn("none" == d.transitionIn ? 0 : d.speedIn, S)));
        }
    }, Y = function() {
        (d.enableEscapeButton || d.enableKeyboardNav) && b(document).bind("keydown.fb", function(a) {
            27 == a.keyCode && d.enableEscapeButton ? (a.preventDefault(), b.fancybox.close()) : 37 != a.keyCode && 39 != a.keyCode || !d.enableKeyboardNav || "INPUT" === a.target.tagName || "TEXTAREA" === a.target.tagName || "SELECT" === a.target.tagName || (a.preventDefault(), 
            b.fancybox[37 == a.keyCode ? "prev" : "next"]());
        }), d.showNavArrows ? ((d.cyclic && l.length > 1 || 0 !== p) && z.show(), (d.cyclic && l.length > 1 || p != l.length - 1) && A.show()) : (z.hide(), 
        A.hide());
    }, S = function() {
        b.support.opacity || (j.get(0).style.removeAttribute("filter"), f.get(0).style.removeAttribute("filter")), 
        e.autoDimensions && j.css("height", "auto"), f.css("height", "auto"), s && s.length && n.show(), 
        d.showCloseButton && E.show(), Y(), d.hideOnContentClick && j.bind("click", b.fancybox.close), 
        d.hideOnOverlayClick && u.bind("click", b.fancybox.close), b(window).bind("resize.fb", b.fancybox.resize), 
        d.centerOnScroll && b(window).bind("scroll.fb", b.fancybox.center), "iframe" == d.type && b('<iframe id="fancybox-frame" name="fancybox-frame' + new Date().getTime() + '" frameborder="0" hspace="0" ' + (b.browser.msie ? 'allowtransparency="true""' : "") + ' scrolling="' + e.scrolling + '" src="' + d.href + '"></iframe>').appendTo(j), 
        f.show(), h = !1, b.fancybox.center(), d.onComplete(l, p, d);
        var a, c;
        l.length - 1 > p && (a = l[p + 1].href, "undefined" != typeof a && a.match(J) && (c = new Image(), 
        c.src = a)), p > 0 && (a = l[p - 1].href, "undefined" != typeof a && a.match(J) && (c = new Image(), 
        c.src = a));
    }, T = function(a) {
        var c = {
            width: parseInt(r.width + (i.width - r.width) * a, 10),
            height: parseInt(r.height + (i.height - r.height) * a, 10),
            top: parseInt(r.top + (i.top - r.top) * a, 10),
            left: parseInt(r.left + (i.left - r.left) * a, 10)
        };
        "undefined" != typeof i.opacity && (c.opacity = .5 > a ? .5 : a), f.css(c), j.css({
            width: c.width - 2 * d.padding,
            height: c.height - y * a - 2 * d.padding
        });
    }, U = function() {
        return [ b(window).width() - 2 * d.margin, b(window).height() - 2 * d.margin, b(document).scrollLeft() + d.margin, b(document).scrollTop() + d.margin ];
    }, X = function() {
        var a = U(), c = {}, g = d.autoScale, k = 2 * d.padding;
        return c.width = d.width.toString().indexOf("%") > -1 ? parseInt(a[0] * parseFloat(d.width) / 100, 10) : d.width + k, 
        c.height = d.height.toString().indexOf("%") > -1 ? parseInt(a[1] * parseFloat(d.height) / 100, 10) : d.height + k, 
        g && (c.width > a[0] || c.height > a[1]) && ("image" == e.type || "swf" == e.type ? (g = d.width / d.height, 
        c.width > a[0] && (c.width = a[0], c.height = parseInt((c.width - k) / g + k, 10)), 
        c.height > a[1] && (c.height = a[1], c.width = parseInt((c.height - k) * g + k, 10))) : (c.width = Math.min(c.width, a[0]), 
        c.height = Math.min(c.height, a[1]))), c.top = parseInt(Math.max(a[3] - 20, a[3] + .5 * (a[1] - c.height - 40)), 10), 
        c.left = parseInt(Math.max(a[2] - 20, a[2] + .5 * (a[0] - c.width - 40)), 10), c;
    }, V = function() {
        var a = e.orig ? b(e.orig) : !1, c = {};
        return a && a.length ? (c = a.offset(), c.top += parseInt(a.css("paddingTop"), 10) || 0, 
        c.left += parseInt(a.css("paddingLeft"), 10) || 0, c.top += parseInt(a.css("border-top-width"), 10) || 0, 
        c.left += parseInt(a.css("border-left-width"), 10) || 0, c.width = a.width(), c.height = a.height(), 
        c = {
            width: c.width + 2 * d.padding,
            height: c.height + 2 * d.padding,
            top: c.top - d.padding - 20,
            left: c.left - d.padding - 20
        }) : (a = U(), c = {
            width: 2 * d.padding,
            height: 2 * d.padding,
            top: parseInt(a[3] + .5 * a[1], 10),
            left: parseInt(a[2] + .5 * a[0], 10)
        }), c;
    }, Z = function() {
        t.is(":visible") ? (b("div", t).css("top", -40 * L + "px"), L = (L + 1) % 12) : clearInterval(K);
    };
    b.fn.fancybox = function(a) {
        return b(this).length ? (b(this).data("fancybox", b.extend({}, a, b.metadata ? b(this).metadata() : {})).unbind("click.fb").bind("click.fb", function(c) {
            c.preventDefault(), h || (h = !0, b(this).blur(), o = [], q = 0, c = b(this).attr("rel") || "", 
            c && "" != c && "nofollow" !== c ? (o = b("a[rel=" + c + "], area[rel=" + c + "]"), 
            q = o.index(this)) : o.push(this), I());
        }), this) : this;
    }, b.fancybox = function(a, c) {
        var g;
        if (!h) {
            if (h = !0, g = "undefined" != typeof c ? c : {}, o = [], q = parseInt(g.index, 10) || 0, 
            b.isArray(a)) {
                for (var k = 0, C = a.length; C > k; k++) "object" == typeof a[k] ? b(a[k]).data("fancybox", b.extend({}, g, a[k])) : a[k] = b({}).data("fancybox", b.extend({
                    content: a[k]
                }, g));
                o = jQuery.merge(o, a);
            } else "object" == typeof a ? b(a).data("fancybox", b.extend({}, g, a)) : a = b({}).data("fancybox", b.extend({
                content: a
            }, g)), o.push(a);
            (q > o.length || 0 > q) && (q = 0), I();
        }
    }, b.fancybox.showActivity = function() {
        clearInterval(K), t.show(), K = setInterval(Z, 66);
    }, b.fancybox.hideActivity = function() {
        t.hide();
    }, b.fancybox.next = function() {
        return b.fancybox.pos(p + 1);
    }, b.fancybox.prev = function() {
        return b.fancybox.pos(p - 1);
    }, b.fancybox.pos = function(a) {
        h || (a = parseInt(a), o = l, a > -1 && a < l.length ? (q = a, I()) : d.cyclic && l.length > 1 && (q = a >= l.length ? 0 : l.length - 1, 
        I()));
    }, b.fancybox.cancel = function() {
        h || (h = !0, b.event.trigger("fancybox-cancel"), N(), e.onCancel(o, q, e), h = !1);
    }, b.fancybox.close = function() {
        function a() {
            u.fadeOut("fast"), n.empty().hide(), f.hide(), b.event.trigger("fancybox-cleanup"), 
            j.empty(), d.onClosed(l, p, d), l = e = [], p = q = 0, d = e = {}, h = !1;
        }
        if (!h && !f.is(":hidden")) if (h = !0, d && !1 === d.onCleanup(l, p, d)) h = !1; else if (N(), 
        b(E.add(z).add(A)).hide(), b(j.add(u)).unbind(), b(window).unbind("resize.fb scroll.fb"), 
        b(document).unbind("keydown.fb"), j.find("iframe").attr("src", M && /^https/i.test(window.location.href || "") ? "javascript:void(false)" : "about:blank"), 
        "inside" !== d.titlePosition && n.empty(), f.stop(), "elastic" == d.transitionOut) {
            r = V();
            var c = f.position();
            i = {
                top: c.top,
                left: c.left,
                width: f.width(),
                height: f.height()
            }, d.opacity && (i.opacity = 1), n.empty().hide(), B.prop = 1, b(B).animate({
                prop: 0
            }, {
                duration: d.speedOut,
                easing: d.easingOut,
                step: T,
                complete: a
            });
        } else f.fadeOut("none" == d.transitionOut ? 0 : d.speedOut, a);
    }, b.fancybox.resize = function() {
        u.is(":visible") && u.css("height", b(document).height()), b.fancybox.center(!0);
    }, b.fancybox.center = function(a) {
        var c, g;
        h || (g = a === !0 ? 1 : 0, c = U(), !g && (f.width() > c[0] || f.height() > c[1]) || f.stop().animate({
            top: parseInt(Math.max(c[3] - 20, c[3] + .5 * (c[1] - j.height() - 40) - d.padding)),
            left: parseInt(Math.max(c[2] - 20, c[2] + .5 * (c[0] - j.width() - 40) - d.padding))
        }, "number" == typeof a ? a : 200));
    }, b.fancybox.init = function() {
        b("#fancybox-wrap").length || (b("body").append(m = b('<div id="fancybox-tmp"></div>'), t = b('<div id="fancybox-loading"><div></div></div>'), u = b('<div id="fancybox-overlay"></div>'), f = b('<div id="fancybox-wrap"></div>')), 
        D = b('<div id="fancybox-outer"></div>').append('<div class="fancybox-bg" id="fancybox-bg-n"></div><div class="fancybox-bg" id="fancybox-bg-ne"></div><div class="fancybox-bg" id="fancybox-bg-e"></div><div class="fancybox-bg" id="fancybox-bg-se"></div><div class="fancybox-bg" id="fancybox-bg-s"></div><div class="fancybox-bg" id="fancybox-bg-sw"></div><div class="fancybox-bg" id="fancybox-bg-w"></div><div class="fancybox-bg" id="fancybox-bg-nw"></div>').appendTo(f), 
        D.append(j = b('<div id="fancybox-content"></div>'), E = b('<a id="fancybox-close"></a>'), n = b('<div id="fancybox-title"></div>'), z = b('<a href="javascript:;" id="fancybox-left"><span class="fancy-ico" id="fancybox-left-ico"></span></a>'), A = b('<a href="javascript:;" id="fancybox-right"><span class="fancy-ico" id="fancybox-right-ico"></span></a>')), 
        E.click(b.fancybox.close), t.click(b.fancybox.cancel), z.click(function(a) {
            a.preventDefault(), b.fancybox.prev();
        }), A.click(function(a) {
            a.preventDefault(), b.fancybox.next();
        }), b.fn.mousewheel && f.bind("mousewheel.fb", function(a, c) {
            h ? a.preventDefault() : (0 == b(a.target).get(0).clientHeight || b(a.target).get(0).scrollHeight === b(a.target).get(0).clientHeight) && (a.preventDefault(), 
            b.fancybox[c > 0 ? "prev" : "next"]());
        }), b.support.opacity || f.addClass("fancybox-ie"), M && (t.addClass("fancybox-ie6"), 
        f.addClass("fancybox-ie6"), b('<iframe id="fancybox-hide-sel-frame" src="' + (/^https/i.test(window.location.href || "") ? "javascript:void(false)" : "about:blank") + '" scrolling="no" border="0" frameborder="0" tabindex="-1"></iframe>').prependTo(D)));
    }, b.fn.fancybox.defaults = {
        padding: 10,
        margin: 40,
        opacity: !1,
        modal: !1,
        cyclic: !1,
        scrolling: "auto",
        width: 560,
        height: 340,
        autoScale: !0,
        autoDimensions: !0,
        centerOnScroll: !1,
        ajax: {},
        swf: {
            wmode: "transparent"
        },
        hideOnOverlayClick: !0,
        hideOnContentClick: !1,
        overlayShow: !0,
        overlayOpacity: .7,
        overlayColor: "#777",
        titleShow: !0,
        titlePosition: "float",
        titleFormat: null,
        titleFromAlt: !1,
        transitionIn: "fade",
        transitionOut: "fade",
        speedIn: 300,
        speedOut: 300,
        changeSpeed: 300,
        changeFade: "fast",
        easingIn: "swing",
        easingOut: "swing",
        showCloseButton: !0,
        showNavArrows: !0,
        enableEscapeButton: !0,
        enableKeyboardNav: !0,
        onStart: function() {},
        onCancel: function() {},
        onComplete: function() {},
        onCleanup: function() {},
        onClosed: function() {},
        onError: function() {}
    }, b(document).ready(function() {
        b.fancybox.init();
    });
}(jQuery);