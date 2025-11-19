(function ($) {
    'use strict';

    var Multiselect = function (selector) {
        if (!$(selector)) {
            console.error("ERROR: Element %s does not exist.", selector);
            return;
        }

        this.selector = selector;
        this.selections = [];

        (function (that) {
            that.events();
        })(this);
    };

    Multiselect.prototype = {
        open: function (that) {
            var target = $(that).parent().attr("data-target");

            // If we are not keeping track of this one's entries, then
            // start doing so.
            if (!this.selections) {
                this.selections = [];
            }

            $(this.selector + ".lpsettings-multiselect").toggleClass("active");
        },

        close: function () {
            $(this.selector + ".lpsettings-multiselect").removeClass("active");
        },

        events: function () {
            var that = this;
            that.initValue();

            $(document).on("click", that.selector + ".lpsettings-multiselect > .lpsettings-title", function (e) {
                if (e.target.className.indexOf("close-icon") < 0) {
                    that.open();
                }
            });

            $(document).on("click", that.selector + ".lpsettings-multiselect option", function (e) {
                var selection = $(this).attr("value");

                var io = that.selections.indexOf(selection);
                if (io < 0) that.selections.push(selection);
                else that.selections.splice(io, 1);

                that.selectionStatus();
                that.setSelectionsString();
            });

            $(document).on("click", that.selector + ".lpsettings-multiselect > .lpsettings-title > .lpsettings-close-icon", function (e) {
                that.clearSelections();
            });

            $(document).click(function (e) {
                if (e.target.className.indexOf("lpsettings-title") < 0) {
                    if (e.target.className.indexOf("text") < 0) {
                        if (e.target.className.indexOf("-icon") < 0) {
                            if (e.target.className.indexOf("selected") < 0 ||
                                e.target.localName != "option") {
                                that.close();
                            }
                        }
                    }
                }
            });
        },

        selectionStatus: function () {
            var obj = $(this.selector + ".lpsettings-multiselect");

            if (this.selections.length) obj.addClass("selection");
            else obj.removeClass("selection");
        },

        clearSelections: function () {
            this.selections = [];
            this.selectionStatus();
            this.setSelectionsString();
        },

        getSelections: function () {
            return this.selections;
        },

        setSelectionsString: function () {
            this.onSelectUpdate(this.getSelectionsString());
            var selects = this.getSelectionsString().split(", ");

            $(this.selector + ".lpsettings-multiselect > .lpsettings-title").attr("title", selects);

            if (selects.length > 6) {
                $(this.selector + ".lpsettings-multiselect > .lpsettings-title > .lpsettings-text")
                    .text("Everyday");
            } else {
                $(this.selector + ".lpsettings-multiselect > .lpsettings-title > .lpsettings-text")
                    .text(selects);
            }
            this.updateStatus(selects);
        },

        getSelectionsString: function () {
            if (this.selections.length > 0)
                return this.selections.join(", ");
            else return "-";
        },

        setSelections: function (arr) {
            if (!arr || !arr[0]) {
                return;
            }

            this.selections = arr;
            this.selectionStatus();
            this.setSelectionsString();
        },

        getValueHolder: function () {
            return $("#lpsettings_courier_call_days");
        },

        onSelectUpdate: function (selects) {
            var valueHolder = this.getValueHolder();
            if (valueHolder != null) {
                valueHolder.val(selects);
            }
        },

        updateStatus: function (selects) {
            var opts = $(this.selector + ".lpsettings-multiselect option");
            for (var i = 0; i < opts.length; i++) {
                $(opts[i]).removeClass("selected");
            }

            for (var j = 0; j < selects.length; j++) {
                var select = selects[j];

                for (var i = 0; i < opts.length; i++) {
                    if ($(opts[i]).attr("value") == select) {
                        $(opts[i]).addClass("selected");
                        break;
                    }
                }
            }
        },

        initValue: function () {
            var valueHolder = this.getValueHolder();
            if (valueHolder != null && valueHolder.val() != null) {
                var selects = valueHolder.val().split(", ");
                this.setSelections(selects);
                this.updateStatus(selects);
            }
        }
    };

    $(document).ready(function () {
        new Multiselect("#lpsettings_call_courier_days_select");
    });

})(jQuery);