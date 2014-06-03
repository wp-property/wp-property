!function(d) {
    d.widget("ui.slider", d.ui.mouse, {
        widgetEventPrefix: "slide",
        options: {
            animate: !1,
            distance: 0,
            max: 100,
            min: 0,
            orientation: "horizontal",
            range: !1,
            step: 1,
            value: 0,
            values: null
        },
        _create: function() {
            var b = this, a = this.options, c = this.element.find(".ui-slider-handle").addClass("ui-state-default ui-corner-all"), f = a.values && a.values.length || 1, e = [];
            this._mouseSliding = this._keySliding = !1, this._animateOff = !0, this._handleIndex = null, 
            this._detectOrientation(), this._mouseInit(), this.element.addClass("ui-slider ui-slider-" + this.orientation + " ui-widget ui-widget-content ui-corner-all" + (a.disabled ? " ui-slider-disabled ui-disabled" : "")), 
            this.range = d([]), a.range && (a.range === !0 && (a.values || (a.values = [ this._valueMin(), this._valueMin() ]), 
            a.values.length && 2 !== a.values.length && (a.values = [ a.values[0], a.values[0] ])), 
            this.range = d("<div></div>").appendTo(this.element).addClass("ui-slider-range ui-widget-header" + ("min" === a.range || "max" === a.range ? " ui-slider-range-" + a.range : "")));
            for (var j = c.length; f > j; j += 1) e.push("<a class='ui-slider-handle ui-state-default ui-corner-all' href='#'></a>");
            this.handles = c.add(d(e.join("")).appendTo(b.element)), this.handle = this.handles.eq(0), 
            this.handles.add(this.range).filter("a").click(function(g) {
                g.preventDefault();
            }).hover(function() {
                a.disabled || d(this).addClass("ui-state-hover");
            }, function() {
                d(this).removeClass("ui-state-hover");
            }).focus(function() {
                a.disabled ? d(this).blur() : (d(".ui-slider .ui-state-focus").removeClass("ui-state-focus"), 
                d(this).addClass("ui-state-focus"));
            }).blur(function() {
                d(this).removeClass("ui-state-focus");
            }), this.handles.each(function(g) {
                d(this).data("index.ui-slider-handle", g);
            }), this.handles.keydown(function(g) {
                var i, h, m, k = !0, l = d(this).data("index.ui-slider-handle");
                if (!b.options.disabled) {
                    switch (g.keyCode) {
                      case d.ui.keyCode.HOME:
                      case d.ui.keyCode.END:
                      case d.ui.keyCode.PAGE_UP:
                      case d.ui.keyCode.PAGE_DOWN:
                      case d.ui.keyCode.UP:
                      case d.ui.keyCode.RIGHT:
                      case d.ui.keyCode.DOWN:
                      case d.ui.keyCode.LEFT:
                        if (k = !1, !b._keySliding && (b._keySliding = !0, d(this).addClass("ui-state-active"), 
                        i = b._start(g, l), i === !1)) return;
                    }
                    switch (m = b.options.step, i = h = b.options.values && b.options.values.length ? b.values(l) : b.value(), 
                    g.keyCode) {
                      case d.ui.keyCode.HOME:
                        h = b._valueMin();
                        break;

                      case d.ui.keyCode.END:
                        h = b._valueMax();
                        break;

                      case d.ui.keyCode.PAGE_UP:
                        h = b._trimAlignValue(i + (b._valueMax() - b._valueMin()) / 5);
                        break;

                      case d.ui.keyCode.PAGE_DOWN:
                        h = b._trimAlignValue(i - (b._valueMax() - b._valueMin()) / 5);
                        break;

                      case d.ui.keyCode.UP:
                      case d.ui.keyCode.RIGHT:
                        if (i === b._valueMax()) return;
                        h = b._trimAlignValue(i + m);
                        break;

                      case d.ui.keyCode.DOWN:
                      case d.ui.keyCode.LEFT:
                        if (i === b._valueMin()) return;
                        h = b._trimAlignValue(i - m);
                    }
                    return b._slide(g, l, h), k;
                }
            }).keyup(function(g) {
                var k = d(this).data("index.ui-slider-handle");
                b._keySliding && (b._keySliding = !1, b._stop(g, k), b._change(g, k), d(this).removeClass("ui-state-active"));
            }), this._refreshValue(), this._animateOff = !1;
        },
        destroy: function() {
            return this.handles.remove(), this.range.remove(), this.element.removeClass("ui-slider ui-slider-horizontal ui-slider-vertical ui-slider-disabled ui-widget ui-widget-content ui-corner-all").removeData("slider").unbind(".slider"), 
            this._mouseDestroy(), this;
        },
        _mouseCapture: function(b) {
            var c, f, e, j, g, a = this.options;
            return a.disabled ? !1 : (this.elementSize = {
                width: this.element.outerWidth(),
                height: this.element.outerHeight()
            }, this.elementOffset = this.element.offset(), c = this._normValueFromMouse({
                x: b.pageX,
                y: b.pageY
            }), f = this._valueMax() - this._valueMin() + 1, j = this, this.handles.each(function(k) {
                var l = Math.abs(c - j.values(k));
                f > l && (f = l, e = d(this), g = k);
            }), a.range === !0 && this.values(1) === a.min && (g += 1, e = d(this.handles[g])), 
            this._start(b, g) === !1 ? !1 : (this._mouseSliding = !0, j._handleIndex = g, e.addClass("ui-state-active").focus(), 
            a = e.offset(), this._clickOffset = d(b.target).parents().andSelf().is(".ui-slider-handle") ? {
                left: b.pageX - a.left - e.width() / 2,
                top: b.pageY - a.top - e.height() / 2 - (parseInt(e.css("borderTopWidth"), 10) || 0) - (parseInt(e.css("borderBottomWidth"), 10) || 0) + (parseInt(e.css("marginTop"), 10) || 0)
            } : {
                left: 0,
                top: 0
            }, this.handles.hasClass("ui-state-hover") || this._slide(b, g, c), this._animateOff = !0));
        },
        _mouseStart: function() {
            return !0;
        },
        _mouseDrag: function(b) {
            var a = this._normValueFromMouse({
                x: b.pageX,
                y: b.pageY
            });
            return this._slide(b, this._handleIndex, a), !1;
        },
        _mouseStop: function(b) {
            return this.handles.removeClass("ui-state-active"), this._mouseSliding = !1, this._stop(b, this._handleIndex), 
            this._change(b, this._handleIndex), this._clickOffset = this._handleIndex = null, 
            this._animateOff = !1;
        },
        _detectOrientation: function() {
            this.orientation = "vertical" === this.options.orientation ? "vertical" : "horizontal";
        },
        _normValueFromMouse: function(b) {
            var a;
            return "horizontal" === this.orientation ? (a = this.elementSize.width, b = b.x - this.elementOffset.left - (this._clickOffset ? this._clickOffset.left : 0)) : (a = this.elementSize.height, 
            b = b.y - this.elementOffset.top - (this._clickOffset ? this._clickOffset.top : 0)), 
            a = b / a, a > 1 && (a = 1), 0 > a && (a = 0), "vertical" === this.orientation && (a = 1 - a), 
            b = this._valueMax() - this._valueMin(), this._trimAlignValue(this._valueMin() + a * b);
        },
        _start: function(b, a) {
            var c = {
                handle: this.handles[a],
                value: this.value()
            };
            return this.options.values && this.options.values.length && (c.value = this.values(a), 
            c.values = this.values()), this._trigger("start", b, c);
        },
        _slide: function(b, a, c) {
            var f;
            this.options.values && this.options.values.length ? (f = this.values(a ? 0 : 1), 
            2 === this.options.values.length && this.options.range === !0 && (0 === a && c > f || 1 === a && f > c) && (c = f), 
            c !== this.values(a) && (f = this.values(), f[a] = c, b = this._trigger("slide", b, {
                handle: this.handles[a],
                value: c,
                values: f
            }), this.values(a ? 0 : 1), b !== !1 && this.values(a, c, !0))) : c !== this.value() && (b = this._trigger("slide", b, {
                handle: this.handles[a],
                value: c
            }), b !== !1 && this.value(c));
        },
        _stop: function(b, a) {
            var c = {
                handle: this.handles[a],
                value: this.value()
            };
            this.options.values && this.options.values.length && (c.value = this.values(a), 
            c.values = this.values()), this._trigger("stop", b, c);
        },
        _change: function(b, a) {
            if (!this._keySliding && !this._mouseSliding) {
                var c = {
                    handle: this.handles[a],
                    value: this.value()
                };
                this.options.values && this.options.values.length && (c.value = this.values(a), 
                c.values = this.values()), this._trigger("change", b, c);
            }
        },
        value: function(b) {
            return arguments.length ? (this.options.value = this._trimAlignValue(b), this._refreshValue(), 
            this._change(null, 0), void 0) : this._value();
        },
        values: function(b, a) {
            var c, f, e;
            if (arguments.length > 1) this.options.values[b] = this._trimAlignValue(a), this._refreshValue(), 
            this._change(null, b); else {
                if (!arguments.length) return this._values();
                if (!d.isArray(arguments[0])) return this.options.values && this.options.values.length ? this._values(b) : this.value();
                for (c = this.options.values, f = arguments[0], e = 0; e < c.length; e += 1) c[e] = this._trimAlignValue(f[e]), 
                this._change(null, e);
                this._refreshValue();
            }
        },
        _setOption: function(b, a) {
            var c, f = 0;
            switch (d.isArray(this.options.values) && (f = this.options.values.length), d.Widget.prototype._setOption.apply(this, arguments), 
            b) {
              case "disabled":
                a ? (this.handles.filter(".ui-state-focus").blur(), this.handles.removeClass("ui-state-hover"), 
                this.handles.attr("disabled", "disabled"), this.element.addClass("ui-disabled")) : (this.handles.removeAttr("disabled"), 
                this.element.removeClass("ui-disabled"));
                break;

              case "orientation":
                this._detectOrientation(), this.element.removeClass("ui-slider-horizontal ui-slider-vertical").addClass("ui-slider-" + this.orientation), 
                this._refreshValue();
                break;

              case "value":
                this._animateOff = !0, this._refreshValue(), this._change(null, 0), this._animateOff = !1;
                break;

              case "values":
                for (this._animateOff = !0, this._refreshValue(), c = 0; f > c; c += 1) this._change(null, c);
                this._animateOff = !1;
            }
        },
        _value: function() {
            var b = this.options.value;
            return b = this._trimAlignValue(b);
        },
        _values: function(b) {
            var a, c;
            if (arguments.length) return a = this.options.values[b], a = this._trimAlignValue(a);
            for (a = this.options.values.slice(), c = 0; c < a.length; c += 1) a[c] = this._trimAlignValue(a[c]);
            return a;
        },
        _trimAlignValue: function(b) {
            if (b <= this._valueMin()) return this._valueMin();
            if (b >= this._valueMax()) return this._valueMax();
            var a = this.options.step > 0 ? this.options.step : 1, c = (b - this._valueMin()) % a;
            return alignValue = b - c, 2 * Math.abs(c) >= a && (alignValue += c > 0 ? a : -a), 
            parseFloat(alignValue.toFixed(5));
        },
        _valueMin: function() {
            return this.options.min;
        },
        _valueMax: function() {
            return this.options.max;
        },
        _refreshValue: function() {
            var e, g, k, l, i, b = this.options.range, a = this.options, c = this, f = this._animateOff ? !1 : a.animate, j = {};
            this.options.values && this.options.values.length ? this.handles.each(function(h) {
                e = (c.values(h) - c._valueMin()) / (c._valueMax() - c._valueMin()) * 100, j["horizontal" === c.orientation ? "left" : "bottom"] = e + "%", 
                d(this).stop(1, 1)[f ? "animate" : "css"](j, a.animate), c.options.range === !0 && ("horizontal" === c.orientation ? (0 === h && c.range.stop(1, 1)[f ? "animate" : "css"]({
                    left: e + "%"
                }, a.animate), 1 === h && c.range[f ? "animate" : "css"]({
                    width: e - g + "%"
                }, {
                    queue: !1,
                    duration: a.animate
                })) : (0 === h && c.range.stop(1, 1)[f ? "animate" : "css"]({
                    bottom: e + "%"
                }, a.animate), 1 === h && c.range[f ? "animate" : "css"]({
                    height: e - g + "%"
                }, {
                    queue: !1,
                    duration: a.animate
                }))), g = e;
            }) : (k = this.value(), l = this._valueMin(), i = this._valueMax(), e = i !== l ? (k - l) / (i - l) * 100 : 0, 
            j["horizontal" === c.orientation ? "left" : "bottom"] = e + "%", this.handle.stop(1, 1)[f ? "animate" : "css"](j, a.animate), 
            "min" === b && "horizontal" === this.orientation && this.range.stop(1, 1)[f ? "animate" : "css"]({
                width: e + "%"
            }, a.animate), "max" === b && "horizontal" === this.orientation && this.range[f ? "animate" : "css"]({
                width: 100 - e + "%"
            }, {
                queue: !1,
                duration: a.animate
            }), "min" === b && "vertical" === this.orientation && this.range.stop(1, 1)[f ? "animate" : "css"]({
                height: e + "%"
            }, a.animate), "max" === b && "vertical" === this.orientation && this.range[f ? "animate" : "css"]({
                height: 100 - e + "%"
            }, {
                queue: !1,
                duration: a.animate
            }));
        }
    }), d.extend(d.ui.slider, {
        version: "1.8.13"
    });
}(jQuery);