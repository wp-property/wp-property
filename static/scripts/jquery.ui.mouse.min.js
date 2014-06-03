!function(b) {
    var d = !1;
    b(document).mousedown(function() {
        d = !1;
    }), b.widget("ui.mouse", {
        options: {
            cancel: ":input,option",
            distance: 1,
            delay: 0
        },
        _mouseInit: function() {
            var a = this;
            this.element.bind("mousedown." + this.widgetName, function(c) {
                return a._mouseDown(c);
            }).bind("click." + this.widgetName, function(c) {
                return !0 === b.data(c.target, a.widgetName + ".preventClickEvent") ? (b.removeData(c.target, a.widgetName + ".preventClickEvent"), 
                c.stopImmediatePropagation(), !1) : void 0;
            }), this.started = !1;
        },
        _mouseDestroy: function() {
            this.element.unbind("." + this.widgetName);
        },
        _mouseDown: function(a) {
            if (!d) {
                this._mouseStarted && this._mouseUp(a), this._mouseDownEvent = a;
                var c = this, f = 1 == a.which, g = "string" == typeof this.options.cancel ? b(a.target).parents().add(a.target).filter(this.options.cancel).length : !1;
                return f && !g && this._mouseCapture(a) ? (this.mouseDelayMet = !this.options.delay, 
                this.mouseDelayMet || (this._mouseDelayTimer = setTimeout(function() {
                    c.mouseDelayMet = !0;
                }, this.options.delay)), this._mouseDistanceMet(a) && this._mouseDelayMet(a) && (this._mouseStarted = this._mouseStart(a) !== !1, 
                !this._mouseStarted) ? (a.preventDefault(), !0) : (!0 === b.data(a.target, this.widgetName + ".preventClickEvent") && b.removeData(a.target, this.widgetName + ".preventClickEvent"), 
                this._mouseMoveDelegate = function(e) {
                    return c._mouseMove(e);
                }, this._mouseUpDelegate = function(e) {
                    return c._mouseUp(e);
                }, b(document).bind("mousemove." + this.widgetName, this._mouseMoveDelegate).bind("mouseup." + this.widgetName, this._mouseUpDelegate), 
                a.preventDefault(), d = !0)) : !0;
            }
        },
        _mouseMove: function(a) {
            return !b.browser.msie || document.documentMode >= 9 || a.button ? this._mouseStarted ? (this._mouseDrag(a), 
            a.preventDefault()) : (this._mouseDistanceMet(a) && this._mouseDelayMet(a) && ((this._mouseStarted = this._mouseStart(this._mouseDownEvent, a) !== !1) ? this._mouseDrag(a) : this._mouseUp(a)), 
            !this._mouseStarted) : this._mouseUp(a);
        },
        _mouseUp: function(a) {
            return b(document).unbind("mousemove." + this.widgetName, this._mouseMoveDelegate).unbind("mouseup." + this.widgetName, this._mouseUpDelegate), 
            this._mouseStarted && (this._mouseStarted = !1, a.target == this._mouseDownEvent.target && b.data(a.target, this.widgetName + ".preventClickEvent", !0), 
            this._mouseStop(a)), !1;
        },
        _mouseDistanceMet: function(a) {
            return Math.max(Math.abs(this._mouseDownEvent.pageX - a.pageX), Math.abs(this._mouseDownEvent.pageY - a.pageY)) >= this.options.distance;
        },
        _mouseDelayMet: function() {
            return this.mouseDelayMet;
        },
        _mouseStart: function() {},
        _mouseDrag: function() {},
        _mouseStop: function() {},
        _mouseCapture: function() {
            return !0;
        }
    });
}(jQuery);