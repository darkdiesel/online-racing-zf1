/*
 Copyright 2012 Igor Vaynberg

 Version: @@ver@@ Timestamp: @@timestamp@@

 This software is licensed under the Apache License, Version 2.0 (the "Apache License") or the GNU
 General Public License version 2 (the "GPL License"). You may choose either license to govern your
 use of this software only upon the condition that you accept all of the terms of either the Apache
 License or the GPL License.

 You may obtain a copy of the Apache License and the GPL License at:

 http://www.apache.org/licenses/LICENSE-2.0
 http://www.gnu.org/licenses/gpl-2.0.html

 Unless required by applicable law or agreed to in writing, software distributed under the
 Apache License or the GPL Licesnse is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 CONDITIONS OF ANY KIND, either express or implied. See the Apache License and the GPL License for
 the specific language governing permissions and limitations under the Apache License and the GPL License.
 */

(function ($) {
    if(typeof $.fn.each2 == "undefined"){
        $.fn.extend({
            /*
             * 4-10 times faster .each replacement
             * use it carefully, as it overrides jQuery context of element on each iteration
             */
            each2 : function (c) {
                var j = $([0]), i = -1, l = this.length;
                while (
                    ++i < l
                        && (j.context = j[0] = this[i])
                        && c.call(j[0], i, j) !== false //"this"=DOM, i=index, j=jQuery object
                    );
                return this;
            }
        });
    }
})(jQuery);

(function ($, undefined) {
    "use strict";
    /*global document, window, jQuery, console */

    if (window.Select2 !== undefined) {
        return;
    }

    var KEY, AbstractSelect2, SingleSelect2, MultiSelect2, nextUid, sizer,
        lastMousePosition={x:0,y:0}, $document, scrollBarDimensions,

        KEY = {
            TAB: 9,
            ENTER: 13,
            ESC: 27,
            SPACE: 32,
            LEFT: 37,
            UP: 38,
            RIGHT: 39,
            DOWN: 40,
            SHIFT: 16,
            CTRL: 17,
            ALT: 18,
            PAGE_UP: 33,
            PAGE_DOWN: 34,
            HOME: 36,
            END: 35,
            BACKSPACE: 8,
            DELETE: 46,
            isArrow: function (k) {
                k = k.which ? k.which : k;
                switch (k) {
                    case KEY.LEFT:
                    case KEY.RIGHT:
                    case KEY.UP:
                    case KEY.DOWN:
                        return true;
                }
                return false;
            },
            isControl: function (e) {
                var k = e.which;
                switch (k) {
                    case KEY.SHIFT:
                    case KEY.CTRL:
                    case KEY.ALT:
                        return true;
                }

                if (e.metaKey) return true;

                return false;
            },
            isFunctionKey: function (k) {
                k = k.which ? k.which : k;
                return k >= 112 && k <= 123;
            }
        },
        MEASURE_SCROLLBAR_TEMPLATE = "<div class='select2-measure-scrollbar'></div>";

    $document = $(document);

    nextUid=(function() { var counter=1; return function() { return counter++; }; }());

    function indexOf(value, array) {
        var i = 0, l = array.length;
        for (; i < l; i = i + 1) {
            if (equal(value, array[i])) return i;
        }
        return -1;
    }

    function measureScrollbar () {
        var $template = $( MEASURE_SCROLLBAR_TEMPLATE );
        $template.appendTo('body');

        var dim = {
            width: $template.width() - $template[0].clientWidth,
            height: $template.height() - $template[0].clientHeight
        };
        $template.remove();

        return dim;
    }

    /**
     * Compares equality of a and b
     * @param a
     * @param b
     */
    function equal(a, b) {
        if (a === b) return true;
        if (a === undefined || b === undefined) return false;
        if (a === null || b === null) return false;
        if (a.constructor === String) return a+'' === b+''; // IE requires a+'' instead of just a
        if (b.constructor === String) return b+'' === a+''; // IE requires b+'' instead of just b
        return false;
    }

    /**
     * Splits the string into an array of values, trimming each value. An empty array is returned for nulls or empty
     * strings
     * @param string
     * @param separator
     */
    function splitVal(string, separator) {
        var val, i, l;
        if (string === null || string.length < 1) return [];
        val = string.split(separator);
        for (i = 0, l = val.length; i < l; i = i + 1) val[i] = $.trim(val[i]);
        return val;
    }

    function getSideBorderPadding(element) {
        return element.outerWidth(false) - element.width();
    }

    function installKeyUpChangeEvent(element) {
        var key="keyup-change-value";
        element.on("keydown", function () {
            if ($.data(element, key) === undefined) {
                $.data(element, key, element.val());
            }
        });
        element.on("keyup", function () {
            var val= $.data(element, key);
            if (val !== undefined && element.val() !== val) {
                $.removeData(element, key);
                element.trigger("keyup-change");
            }
        });
    }

    $document.on("mousemove", function (e) {
        lastMousePosition.x = e.pageX;
        lastMousePosition.y = e.pageY;
    });

    /**
     * filters mouse events so an event is fired only if the mouse moved.
     *
     * filters out mouse events that occur when mouse is stationary but
     * the elements under the pointer are scrolled.
     */
    function installFilteredMouseMove(element) {
        element.on("mousemove", function (e) {
            var lastpos = lastMousePosition;
            if (lastpos === undefined || lastpos.x !== e.pageX || lastpos.y !== e.pageY) {
                $(e.target).trigger("mousemove-filtered", e);
            }
        });
    }

    /**
     * Debounces a function. Returns a function that calls the original fn function only if no invocations have been made
     * within the last quietMillis milliseconds.
     *
     * @param quietMillis number of milliseconds to wait before invoking fn
     * @param fn function to be debounced
     * @param ctx object to be used as this reference within fn
     * @return debounced version of fn
     */
    function debounce(quietMillis, fn, ctx) {
        ctx = ctx || undefined;
        var timeout;
        return function () {
            var args = arguments;
            window.clearTimeout(timeout);
            timeout = window.setTimeout(function() {
                fn.apply(ctx, args);
            }, quietMillis);
        };
    }

    /**
     * A simple implementation of a thunk
     * @param formula function used to lazily initialize the thunk
     * @return {Function}
     */
    function thunk(formula) {
        var evaluated = false,
            value;
        return function() {
            if (evaluated === false) { value = formula(); evaluated = true; }
            return value;
        };
    };

    function installDebouncedScroll(threshold, element) {
        var notify = debounce(threshold, function (e) { element.trigger("scroll-debounced", e);});
        element.on("scroll", function (e) {
            if (indexOf(e.target, element.get()) >= 0) notify(e);
        });
    }

    function focus($el) {
        if ($el[0] === document.activeElement) return;

        /* set the focus in a 0 timeout - that way the focus is set after the processing
         of the current event has finished - which seems like the only reliable way
         to set focus */
        window.setTimeout(function() {
            var el=$el[0], pos=$el.val().length, range;

            $el.focus();

            /* make sure el received focus so we do not error out when trying to manipulate the caret.
             sometimes modals or others listeners may steal it after its set */
            if ($el.is(":visible") && el === document.activeElement) {

                /* after the focus is set move the caret to the end, necessary when we val()
                 just before setting focus */
                if(el.setSelectionRange)
                {
                    el.setSelectionRange(pos, pos);
                }
                else if (el.createTextRange) {
                    range = el.createTextRange();
                    range.collapse(false);
                    range.select();
                }
            }
        }, 0);
    }

    function getCursorInfo(el) {
        el = $(el)[0];
        var offset = 0;
        var length = 0;
        if ('selectionStart' in el) {
            offset = el.selectionStart;
            length = el.selectionEnd - offset;
        } else if ('selection' in document) {
            el.focus();
            var sel = document.selection.createRange();
            length = document.selection.createRange().text.length;
            sel.moveStart('character', -el.value.length);
            offset = sel.text.length - length;
        }
        return { offset: offset, length: length };
    }

    function killEvent(event) {
        event.preventDefault();
        event.stopPropagation();
    }
    function killEventImmediately(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }

    function measureTextWidth(e) {
        if (!sizer){
            var style = e[0].currentStyle || window.getComputedStyle(e[0], null);
            sizer = $(document.createElement("div")).css({
                position: "absolute",
                left: "-10000px",
                top: "-10000px",
                display: "none",
                fontSize: style.fontSize,
                fontFamily: style.fontFamily,
                fontStyle: style.fontStyle,
                fontWeight: style.fontWeight,
                letterSpacing: style.letterSpacing,
                textTransform: style.textTransform,
                whiteSpace: "nowrap"
            });
            sizer.attr("class","select2-sizer");
            $("body").append(sizer);
        }
        sizer.text(e.val());
        return sizer.width();
    }

    function syncCssClasses(dest, src, adapter) {
        var classes, replacements = [], adapted;

        classes = dest.attr("class");
        if (classes) {
            classes = '' + classes; // for IE which returns object
            $(classes.split(" ")).each2(function() {
                if (this.indexOf("select2-") === 0) {
                    replacements.push(this);
                }
            });
        }
        classes = src.attr("class");
        if (classes) {
            classes = '' + classes; // for IE which returns object
            $(classes.split(" ")).each2(function() {
                if (this.indexOf("select2-") !== 0) {
                    adapted = adapter(this);
                    if (adapted) {
                        replacements.push(this);
                    }
                }
            });
        }
        dest.attr("class", replacements.join(" "));
    }


    function markMatch(text, term, markup, escapeMarkup) {
        var match=text.toUpperCase().indexOf(term.toUpperCase()),
            tl=term.length;

        if (match<0) {
            markup.push(escapeMarkup(text));
            return;
        }

        markup.push(escapeMarkup(text.substring(0, match)));
        markup.push("<span class='select2-match'>");
        markup.push(escapeMarkup(text.substring(match, match + tl)));
        markup.push("</span>");
        markup.push(escapeMarkup(text.substring(match + tl, text.length)));
    }

    function defaultEscapeMarkup(markup) {
        var replace_map = {
            '\\': '&#92;',
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            "/": '&#47;'
        };

        return String(markup).replace(/[&<>"'\/\\]/g, function (match) {
            return replace_map[match];
        });
    }

    /**
     * Produces an ajax-based query function
     *
     * @param options object containing configuration paramters
     * @param options.params parameter map for the transport ajax call, can contain such options as cache, jsonpCallback, etc. see $.ajax
     * @param options.transport function that will be used to execute the ajax request. must be compatible with parameters supported by $.ajax
     * @param options.url url for the data
     * @param options.data a function(searchTerm, pageNumber, context) that should return an object containing query string parameters for the above url.
     * @param options.dataType request data type: ajax, jsonp, other datatatypes supported by jQuery's $.ajax function or the transport function if specified
     * @param options.quietMillis (optional) milliseconds to wait before making the ajaxRequest, helps debounce the ajax function if invoked too often
     * @param options.results a function(remoteData, pageNumber) that converts data returned form the remote request to the format expected by Select2.
     *      The expected format is an object containing the following keys:
     *      results array of objects that will be used as choices
     *      more (optional) boolean indicating whether there are more results available
     *      Example: {results:[{id:1, text:'Red'},{id:2, text:'Blue'}], more:true}
     */
    function ajax(options) {
        var timeout, // current scheduled but not yet executed request
            requestSequence = 0, // sequence used to drop out-of-order responses
            handler = null,
            quietMillis = options.quietMillis || 100,
            ajaxUrl = options.url,
            self = this;

        return function (query) {
            window.clearTimeout(timeout);
            timeout = window.setTimeout(function () {
                requestSequence += 1; // increment the sequence
                var requestNumber = requestSequence, // this request's sequence number
                    data = options.data, // ajax data function
                    url = ajaxUrl, // ajax url string or function
                    transport = options.transport || $.fn.select2.ajaxDefaults.transport,
                // deprecated - to be removed in 4.0  - use params instead
                    deprecated = {
                        type: options.type || 'GET', // set type of request (GET or POST)
                        cache: options.cache || false,
                        jsonpCallback: options.jsonpCallback||undefined,
                        dataType: options.dataType||"json"
                    },
                    params = $.extend({}, $.fn.select2.ajaxDefaults.params, deprecated);

                data = data ? data.call(self, query.term, query.page, query.context) : null;
                url = (typeof url === 'function') ? url.call(self, query.term, query.page, query.context) : url;

                if (handler) { handler.abort(); }

                if (options.params) {
                    if ($.isFunction(options.params)) {
                        $.extend(params, options.params.call(self));
                    } else {
                        $.extend(params, options.params);
                    }
                }

                $.extend(params, {
                    url: url,
                    dataType: options.dataType,
                    data: data,
                    success: function (data) {
                        if (requestNumber < requestSequence) {
                            return;
                        }
                        // TODO - replace query.page with query so users have access to term, page, etc.
                        var results = options.results(data, query.page);
                        query.callback(results);
                    }
                });
                handler = transport.call(self, params);
            }, quietMillis);
        };
    }

    /**
     * Produces a query function that works with a local array
     *
     * @param options object containing configuration parameters. The options parameter can either be an array or an
     * object.
     *
     * If the array form is used it is assumed that it contains objects with 'id' and 'text' keys.
     *
     * If the object form is used ti is assumed that it contains 'data' and 'text' keys. The 'data' key should contain
     * an array of objects that will be used as choices. These objects must contain at least an 'id' key. The 'text'
     * key can either be a String in which case it is expected that each element in the 'data' array has a key with the
     * value of 'text' which will be used to match choices. Alternatively, text can be a function(item) that can extract
     * the text.
     */
    function local(options) {
        var data = options, // data elements
            dataText,
            tmp,
            text = function (item) { return ""+item.text; }; // function used to retrieve the text portion of a data item that is matched against the search

        if ($.isArray(data)) {
            tmp = data;
            data = { results: tmp };
        }

        if ($.isFunction(data) === false) {
            tmp = data;
            data = function() { return tmp; };
        }

        var dataItem = data();
        if (dataItem.text) {
            text = dataItem.text;
            // if text is not a function we assume it to be a key name
            if (!$.isFunction(text)) {
                dataText = dataItem.text; // we need to store this in a separate variable because in the next step data gets reset and data.text is no longer available
                text = function (item) { return item[dataText]; };
            }
        }

        return function (query) {
            var t = query.term, filtered = { results: [] }, process;
            if (t === "") {
                query.callback(data());
                return;
            }

            process = function(datum, collection) {
                var group, attr;
                datum = datum[0];
                if (datum.children) {
                    group = {};
                    for (attr in datum) {
                        if (datum.hasOwnProperty(attr)) group[attr]=datum[attr];
                    }
                    group.children=[];
                    $(datum.children).each2(function(i, childDatum) { process(childDatum, group.children); });
                    if (group.children.length || query.matcher(t, text(group), datum)) {
                        collection.push(group);
                    }
                } else {
                    if (query.matcher(t, text(datum), datum)) {
                        collection.push(datum);
                    }
                }
            };

            $(data().results).each2(function(i, datum) { process(datum, filtered.results); });
            query.callback(filtered);
        };
    }

    // TODO javadoc
    function tags(data) {
        var isFunc = $.isFunction(data);
        return function (query) {
            var t = query.term, filtered = {results: []};
            $(isFunc ? data() : data).each(function () {
                var isObject = this.text !== undefined,
                    text = isObject ? this.text : this;
                if (t === "" || query.matcher(t, text)) {
                    filtered.results.push(isObject ? this : {id: this, text: this});
                }
            });
            query.callback(filtered);
        };
    }

    /**
     * Checks if the formatter function should be used.
     *
     * Throws an error if it is not a function. Returns true if it should be used,
     * false if no formatting should be performed.
     *
     * @param formatter
     */
    function checkFormatter(formatter, formatterName) {
        if ($.isFunction(formatter)) return true;
        if (!formatter) return false;
        throw new Error(formatterName +" must be a function or a falsy value");
    }

    function evaluate(val) {
        return $.isFunction(val) ? val() : val;
    }

    function countResults(results) {
        var count = 0;
        $.each(results, function(i, item) {
            if (item.children) {
                count += countResults(item.children);
            } else {
                count++;
            }
        });
        return count;
    }

    /**
     * Default tokenizer. This function uses breaks the input on substring match of any string from the
     * opts.tokenSeparators array and uses opts.createSearchChoice to create the choice object. Both of those
     * two options have to be defined in order for the tokenizer to work.
     *
     * @param input text user has typed so far or pasted into the search field
     * @param selection currently selected choices
     * @param selectCallback function(choice) callback tho add the choice to selection
     * @param opts select2's opts
     * @return undefined/null to leave the current input unchanged, or a string to change the input to the returned value
     */
    function defaultTokenizer(input, selection, selectCallback, opts) {
        var original = input, // store the original so we can compare and know if we need to tell the search to update its text
            dupe = false, // check for whether a token we extracted represents a duplicate selected choice
            token, // token
            index, // position at which the separator was found
            i, l, // looping variables
            separator; // the matched separator

        if (!opts.createSearchChoice || !opts.tokenSeparators || opts.tokenSeparators.length < 1) return undefined;

        while (true) {
            index = -1;

            for (i = 0, l = opts.tokenSeparators.length; i < l; i++) {
                separator = opts.tokenSeparators[i];
                index = input.indexOf(separator);
                if (index >= 0) break;
            }

            if (index < 0) break; // did not find any token separator in the input string, bail

            token = input.substring(0, index);
            input = input.substring(index + separator.length);

            if (token.length > 0) {
                token = opts.createSearchChoice(token, selection);
                if (token !== undefined && token !== null && opts.id(token) !== undefined && opts.id(token) !== null) {
                    dupe = false;
                    for (i = 0, l = selection.length; i < l; i++) {
                        if (equal(opts.id(token), opts.id(selection[i]))) {
                            dupe = true; break;
                        }
                    }

                    if (!dupe) selectCallback(token);
                }
            }
        }

        if (original!==input) return input;
    }

    /**
     * Creates a new class
     *
     * @param superClass
     * @param methods
     */
    function clazz(SuperClass, methods) {
        var constructor = function () {};
        constructor.prototype = new SuperClass;
        constructor.prototype.constructor = constructor;
        constructor.prototype.parent = SuperClass.prototype;
        constructor.prototype = $.extend(constructor.prototype, methods);
        return constructor;
    }

    AbstractSelect2 = clazz(Object, {

        // abstract
        bind: function (func) {
            var self = this;
            return function () {
                func.apply(self, arguments);
            };
        },

        // abstract
        init: function (opts) {
            var results, search, resultsSelector = ".select2-results", disabled, readonly;

            // prepare options
            this.opts = opts = this.prepareOpts(opts);

            this.id=opts.id;

            // destroy if called on an existing component
            if (opts.element.data("select2") !== undefined &&
                opts.element.data("select2") !== null) {
                this.destroy();
            }

            this.container = this.createContainer();

            this.containerId="s2id_"+(opts.element.attr("id") || "autogen"+nextUid());
            this.containerSelector="#"+this.containerId.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, '\\$1');
            this.container.attr("id", this.containerId);

            // cache the body so future lookups are cheap
            this.body = thunk(function() { return opts.element.closest("body"); });

            syncCssClasses(this.container, this.opts.element, this.opts.adaptContainerCssClass);

            this.container.css(evaluate(opts.containerCss));
            this.container.addClass(evaluate(opts.containerCssClass));

            this.elementTabIndex = this.opts.element.attr("tabindex");

            // swap container for the element
            this.opts.element
                .data("select2", this)
                .attr("tabindex", "-1")
                .before(this.container);
            this.container.data("select2", this);

            this.dropdown = this.container.find(".select2-drop");
            this.dropdown.addClass(evaluate(opts.dropdownCssClass));
            this.dropdown.data("select2", this);

            this.results = results = this.container.find(resultsSelector);
            this.search = search = this.container.find("input.select2-input");

            this.resultsPage = 0;
            this.context = null;

            // initialize the container
            this.initContainer();

            installFilteredMouseMove(this.results);
            this.dropdown.on("mousemove-filtered touchstart touchmove touchend", resultsSelector, this.bind(this.highlightUnderEvent));

            installDebouncedScroll(80, this.results);
            this.dropdown.on("scroll-debounced", resultsSelector, this.bind(this.loadMoreIfNeeded));

            // do not propagate change event from the search field out of the component
            $(this.container).on("change", ".select2-input", function(e) {e.stopPropagation();});
            $(this.dropdown).on("change", ".select2-input", function(e) {e.stopPropagation();});

            // if jquery.mousewheel plugin is installed we can prevent out-of-bounds scrolling of results via mousewheel
            if ($.fn.mousewheel) {
                results.mousewheel(function (e, delta, deltaX, deltaY) {
                    var top = results.scrollTop(), height;
                    if (deltaY > 0 && top - deltaY <= 0) {
                        results.scrollTop(0);
                        killEvent(e);
                    } else if (deltaY < 0 && results.get(0).scrollHeight - results.scrollTop() + deltaY <= results.height()) {
                        results.scrollTop(results.get(0).scrollHeight - results.height());
                        killEvent(e);
                    }
                });
            }

            installKeyUpChangeEvent(search);
            search.on("keyup-change input paste", this.bind(this.updateResults));
            search.on("focus", function () { search.addClass("select2-focused"); });
            search.on("blur", function () { search.removeClass("select2-focused");});

            this.dropdown.on("mouseup", resultsSelector, this.bind(function (e) {
                if ($(e.target).closest(".select2-result-selectable").length > 0) {
                    this.highlightUnderEvent(e);
                    this.selectHighlighted(e);
                }
            }));

            // trap all mouse events from leaving the dropdown. sometimes there may be a modal that is listening
            // for mouse events outside of itself so it can close itself. since the dropdown is now outside the select2's
            // dom it will trigger the popup close, which is not what we want
            this.dropdown.on("click mouseup mousedown", function (e) { e.stopPropagation(); });

            if ($.isFunction(this.opts.initSelection)) {
                // initialize selection based on the current value of the source element
                this.initSelection();

                // if the user has provided a function that can set selection based on the value of the source element
                // we monitor the change event on the element and trigger it, allowing for two way synchronization
                this.monitorSource();
            }

            if (opts.maximumInputLength !== null) {
                this.search.attr("maxlength", opts.maximumInputLength);
            }

            var disabled = opts.element.prop("disabled");
            if (disabled === undefined) disabled = false;
            this.enable(!disabled);

            var readonly = opts.element.prop("readonly");
            if (readonly === undefined) readonly = false;
            this.readonly(readonly);

            // Calculate size of scrollbar
            scrollBarDimensions = scrollBarDimensions || measureScrollbar();

            this.autofocus = opts.element.prop("autofocus")
            opts.element.prop("autofocus", false);
            if (this.autofocus) this.focus();
        },

        // abstract
        destroy: function () {
            var element=this.opts.element, select2 = element.data("select2");

            if (this.propertyObserver) { delete this.propertyObserver; this.propertyObserver = null; }

            if (select2 !== undefined) {
                select2.container.remove();
                select2.dropdown.remove();
                element
                    .removeClass("select2-offscreen")
                    .removeData("select2")
                    .off(".select2")
                    .prop("autofocus", this.autofocus || false);
                if (this.elementTabIndex) {
                    element.attr({tabindex: this.elementTabIndex});
                } else {
                    element.removeAttr("tabindex");
                }
                element.show();
            }
        },

        // abstract
        optionToData: function(element) {
            if (element.is("option")) {
                return {
                    id:element.prop("value"),
                    text:element.text(),
                    element: element.get(),
                    css: element.attr("class"),
                    disabled: element.prop("disabled"),
                    locked: equal(element.attr("locked"), "locked")
                };
            } else if (element.is("optgroup")) {
                return {
                    text:element.attr("label"),
                    children:[],
                    element: element.get(),
                    css: element.attr("class")
                };
            }
        },

        // abstract
        prepareOpts: function (opts) {
            var element, select, idKey, ajaxUrl, self = this;

            element = opts.element;

            if (element.get(0).tagName.toLowerCase() === "select") {
                this.select = select = opts.element;
            }

            if (select) {
                // these options are not allowed when attached to a select because they are picked up off the element itself
                $.each(["id", "multiple", "ajax", "query", "createSearchChoice", "initSelection", "data", "tags"], function () {
                    if (this in opts) {
                        throw new Error("Option '" + this + "' is not allowed for Select2 when attached to a <select> element.");
                    }
                });
            }

            opts = $.extend({}, {
                populateResults: function(container, results, query) {
                    var populate,  data, result, children, id=this.opts.id;

                    populate=function(results, container, depth) {

                        var i, l, result, selectable, disabled, compound, node, label, innerContainer, formatted;

                        results = opts.sortResults(results, container, query);

                        for (i = 0, l = results.length; i < l; i = i + 1) {

                            result=results[i];

                            disabled = (result.disabled === true);
                            selectable = (!disabled) && (id(result) !== undefined);

                            compound=result.children && result.children.length > 0;

                            node=$("<li></li>");
                            node.addClass("select2-results-dept-"+depth);
                            node.addClass("select2-result");
                            node.addClass(selectable ? "select2-result-selectable" : "select2-result-unselectable");
                            if (disabled) { node.addClass("select2-disabled"); }
                            if (compound) { node.addClass("select2-result-with-children"); }
                            node.addClass(self.opts.formatResultCssClass(result));

                            label=$(document.createElement("div"));
                            label.addClass("select2-result-label");

                            formatted=opts.formatResult(result, label, query, self.opts.escapeMarkup);
                            if (formatted!==undefined) {
                                label.html(formatted);
                            }

                            node.append(label);

                            if (compound) {

                                innerContainer=$("<ul></ul>");
                                innerContainer.addClass("select2-result-sub");
                                populate(result.children, innerContainer, depth+1);
                                node.append(innerContainer);
                            }

                            node.data("select2-data", result);
                            container.append(node);
                        }
                    };

                    populate(results, container, 0);
                }
            }, $.fn.select2.defaults, opts);

            if (typeof(opts.id) !== "function") {
                idKey = opts.id;
                opts.id = function (e) { return e[idKey]; };
            }

            if ($.isArray(opts.element.data("select2Tags"))) {
                if ("tags" in opts) {
                    throw "tags specified as both an attribute 'data-select2-tags' and in options of Select2 " + opts.element.attr("id");
                }
                opts.tags=opts.element.data("select2Tags");
            }

            if (select) {
                opts.query = this.bind(function (query) {
                    var data = { results: [], more: false },
                        term = query.term,
                        children, placeholderOption, process;

                    process=function(element, collection) {
                        var group;
                        if (element.is("option")) {
                            if (query.matcher(term, element.text(), element)) {
                                collection.push(self.optionToData(element));
                            }
                        } else if (element.is("optgroup")) {
                            group=self.optionToData(element);
                            element.children().each2(function(i, elm) { process(elm, group.children); });
                            if (group.children.length>0) {
                                collection.push(group);
                            }
                        }
                    };

                    children=element.children();

                    // ignore the placeholder option if there is one
                    if (this.getPlaceholder() !== undefined && children.length > 0) {
                        placeholderOption = this.getPlaceholderOption();
                        if (placeholderOption) {
                            children=children.not(placeholderOption);
                        }
                    }

                    children.each2(function(i, elm) { process(elm, data.results); });

                    query.callback(data);
                });
                // this is needed because inside val() we construct choices from options and there id is hardcoded
                opts.id=function(e) { return e.id; };
                opts.formatResultCssClass = function(data) { return data.css; };
            } else {
                if (!("query" in opts)) {

                    if ("ajax" in opts) {
                        ajaxUrl = opts.element.data("ajax-url");
                        if (ajaxUrl && ajaxUrl.length > 0) {
                            opts.ajax.url = ajaxUrl;
                        }
                        opts.query = ajax.call(opts.element, opts.ajax);
                    } else if ("data" in opts) {
                        opts.query = local(opts.data);
                    } else if ("tags" in opts) {
                        opts.query = tags(opts.tags);
                        if (opts.createSearchChoice === undefined) {
                            opts.createSearchChoice = function (term) { return {id: term, text: term}; };
                        }
                        if (opts.initSelection === undefined) {
                            opts.initSelection = function (element, callback) {
                                var data = [];
                                $(splitVal(element.val(), opts.separator)).each(function () {
                                    var id = this, text = this, tags=opts.tags;
                                    if ($.isFunction(tags)) tags=tags();
                                    $(tags).each(function() { if (equal(this.id, id)) { text = this.text; return false; } });
                                    data.push({id: id, text: text});
                                });

                                callback(data);
                            };
                        }
                    }
                }
            }
            if (typeof(opts.query) !== "function") {
                throw "query function not defined for Select2 " + opts.element.attr("id");
            }

            return opts;
        },

        /**
         * Monitor the original element for changes and update select2 accordingly
         */
        // abstract
        monitorSource: function () {
            var el = this.opts.element, sync;

            el.on("change.select2", this.bind(function (e) {
                if (this.opts.element.data("select2-change-triggered") !== true) {
                    this.initSelection();
                }
            }));

            sync = this.bind(function () {

                var enabled, readonly, self = this;

                // sync enabled state

                var disabled = el.prop("disabled");
                if (disabled === undefined) disabled = false;
                this.enable(!disabled);

                var readonly = el.prop("readonly");
                if (readonly === undefined) readonly = false;
                this.readonly(readonly);

                syncCssClasses(this.container, this.opts.element, this.opts.adaptContainerCssClass);
                this.container.addClass(evaluate(this.opts.containerCssClass));

                syncCssClasses(this.dropdown, this.opts.element, this.opts.adaptDropdownCssClass);
                this.dropdown.addClass(evaluate(this.opts.dropdownCssClass));

            });

            // mozilla and IE
            el.on("propertychange.select2 DOMAttrModified.select2", sync);


            // hold onto a reference of the callback to work around a chromium bug
            if (this.mutationCallback === undefined) {
                this.mutationCallback = function (mutations) {
                    mutations.forEach(sync);
                }
            }

            // safari and chrome
            if (typeof WebKitMutationObserver !== "undefined") {
                if (this.propertyObserver) { delete this.propertyObserver; this.propertyObserver = null; }
                this.propertyObserver = new WebKitMutationObserver(this.mutationCallback);
                this.propertyObserver.observe(el.get(0), { attributes:true, subtree:false });
            }
        },

        // abstract
        triggerSelect: function(data) {
            var evt = $.Event("select2-selecting", { val: this.id(data), object: data });
            this.opts.element.trigger(evt);
            return !evt.isDefaultPrevented();
        },

        /**
         * Triggers the change event on the source element
         */
        // abstract
        triggerChange: function (details) {

            details = details || {};
            details= $.extend({}, details, { type: "change", val: this.val() });
            // prevents recursive triggering
            this.opts.element.data("select2-change-triggered", true);
            this.opts.element.trigger(details);
            this.opts.element.data("select2-change-triggered", false);

            // some validation frameworks ignore the change event and listen instead to keyup, click for selects
            // so here we trigger the click event manually
            this.opts.element.click();

            // ValidationEngine ignorea the change event and listens instead to blur
            // so here we trigger the blur event manually if so desired
            if (this.opts.blurOnChange)
                this.opts.element.blur();
        },

        //abstract
        isInterfaceEnabled: function()
        {
            return this.enabledInterface === true;
        },

        // abstract
        enableInterface: function() {
            var enabled = this._enabled && !this._readonly,
                disabled = !enabled;

            if (enabled === this.enabledInterface) return false;

            this.container.toggleClass("select2-container-disabled", disabled);
            this.close();
            this.enabledInterface = enabled;

            return true;
        },

        // abstract
        enable: function(enabled) {
            if (enabled === undefined) enabled = true;
            if (this._enabled === enabled) return false;
            this._enabled = enabled;

            this.opts.element.prop("disabled", !enabled);
            this.enableInterface();
            return true;
        },

        // abstract
        readonly: function(enabled) {
            if (enabled === undefined) enabled = false;
            if (this._readonly === enabled) return false;
            this._readonly = enabled;

            this.opts.element.prop("readonly", enabled);
            this.enableInterface();
            return true;
        },

        // abstract
        opened: function () {
            return this.container.hasClass("select2-dropdown-open");
        },

        // abstract
        positionDropdown: function() {
            var $dropdown = this.dropdown,
                offset = this.container.offset(),
                height = this.container.outerHeight(false),
                width = this.container.outerWidth(false),
                dropHeight = $dropdown.outerHeight(false),
                viewPortRight = $(window).scrollLeft() + $(window).width(),
                viewportBottom = $(window).scrollTop() + $(window).height(),
                dropTop = offset.top + height,
                dropLeft = offset.left,
                enoughRoomBelow = dropTop + dropHeight <= viewportBottom,
                enoughRoomAbove = (offset.top - dropHeight) >= this.body().scrollTop(),
                dropWidth = $dropdown.outerWidth(false),
                enoughRoomOnRight = dropLeft + dropWidth <= viewPortRight,
                aboveNow = $dropdown.hasClass("select2-drop-above"),
                bodyOffset,
                above,
                css,
                resultsListNode;

            if (this.opts.dropdownAutoWidth) {
                resultsListNode = $('.select2-results', $dropdown)[0];
                $dropdown.addClass('select2-drop-auto-width');
                $dropdown.css('width', '');
                // Add scrollbar width to dropdown if vertical scrollbar is present
                dropWidth = $dropdown.outerWidth(false) + (resultsListNode.scrollHeight === resultsListNode.clientHeight ? 0 : scrollBarDimensions.width);
                dropWidth > width ? width = dropWidth : dropWidth = width;
                enoughRoomOnRight = dropLeft + dropWidth <= viewPortRight;
            }
            else {
                this.container.removeClass('select2-drop-auto-width');
            }

            //console.log("below/ droptop:", dropTop, "dropHeight", dropHeight, "sum", (dropTop+dropHeight)+" viewport bottom", viewportBottom, "enough?", enoughRoomBelow);
            //console.log("above/ offset.top", offset.top, "dropHeight", dropHeight, "top", (offset.top-dropHeight), "scrollTop", this.body().scrollTop(), "enough?", enoughRoomAbove);

            // fix positioning when body has an offset and is not position: static

            if (this.body().css('position') !== 'static') {
                bodyOffset = this.body().offset();
                dropTop -= bodyOffset.top;
                dropLeft -= bodyOffset.left;
            }

            // always prefer the current above/below alignment, unless there is not enough room

            if (aboveNow) {
                above = true;
                if (!enoughRoomAbove && enoughRoomBelow) above = false;
            } else {
                above = false;
                if (!enoughRoomBelow && enoughRoomAbove) above = true;
            }

            if (!enoughRoomOnRight) {
                dropLeft = offset.left + width - dropWidth;
            }

            if (above) {
                dropTop = offset.top - dropHeight;
                this.container.addClass("select2-drop-above");
                $dropdown.addClass("select2-drop-above");
            }
            else {
                this.container.removeClass("select2-drop-above");
                $dropdown.removeClass("select2-drop-above");
            }

            css = $.extend({
                top: dropTop,
                left: dropLeft,
                width: width
            }, evaluate(this.opts.dropdownCss));

            $dropdown.css(css);
        },

        // abstract
        shouldOpen: function() {
            var event;

            if (this.opened()) return false;

            if (this._enabled === false || this._readonly === true) return false;

            event = $.Event("select2-opening");
            this.opts.element.trigger(event);
            return !event.isDefaultPrevented();
        },

        // abstract
        clearDropdownAlignmentPreference: function() {
            // clear the classes used to figure out the preference of where the dropdown should be opened
            this.container.removeClass("select2-drop-above");
            this.dropdown.removeClass("select2-drop-above");
        },

        /**
         * Opens the dropdown
         *
         * @return {Boolean} whether or not dropdown was opened. This method will return false if, for example,
         * the dropdown is already open, or if the 'open' event listener on the element called preventDefault().
         */
        // abstract
        open: function () {

            if (!this.shouldOpen()) return false;

            this.opening();

            return true;
        },

        /**
         * Performs the opening of the dropdown
         */
        // abstract
        opening: function() {
            var cid = this.containerId,
                scroll = "scroll." + cid,
                resize = "resize."+cid,
                orient = "orientationchange."+cid,
                mask, maskCss;

            this.container.addClass("select2-dropdown-open").addClass("select2-container-active");

            this.clearDropdownAlignmentPreference();

            if(this.dropdown[0] !== this.body().children().last()[0]) {
                this.dropdown.detach().appendTo(this.body());
            }

            // create the dropdown mask if doesnt already exist
            mask = $("#select2-drop-mask");
            if (mask.length == 0) {
                mask = $(document.createElement("div"));
                mask.attr("id","select2-drop-mask").attr("class","select2-drop-mask");
                mask.hide();
                mask.appendTo(this.body());
                mask.on("mousedown touchstart click", function (e) {
                    var dropdown = $("#select2-drop"), self;
                    if (dropdown.length > 0) {
                        self=dropdown.data("select2");
                        if (self.opts.selectOnBlur) {
                            self.selectHighlighted({noFocus: true});
                        }
                        self.close();
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
            }

            // ensure the mask is always right before the dropdown
            if (this.dropdown.prev()[0] !== mask[0]) {
                this.dropdown.before(mask);
            }

            // move the global id to the correct dropdown
            $("#select2-drop").removeAttr("id");
            this.dropdown.attr("id", "select2-drop");

            // show the elements
            maskCss=_makeMaskCss();

            mask.css(maskCss).show();

            this.dropdown.show();
            this.positionDropdown();

            this.dropdown.addClass("select2-drop-active");

            // attach listeners to events that can change the position of the container and thus require
            // the position of the dropdown to be updated as well so it does not come unglued from the container
            var that = this;
            this.container.parents().add(window).each(function () {
                $(this).on(resize+" "+scroll+" "+orient, function (e) {
                    var maskCss=_makeMaskCss();
                    $("#select2-drop-mask").css(maskCss);
                    that.positionDropdown();
                });
            });

            function _makeMaskCss() {
                return {
                    width  : Math.max(document.documentElement.scrollWidth,  $(window).width()),
                    height : Math.max(document.documentElement.scrollHeight, $(window).height())
                }
            }
        },

        // abstract
        close: function () {
            if (!this.opened()) return;

            var cid = this.containerId,
                scroll = "scroll." + cid,
                resize = "resize."+cid,
                orient = "orientationchange."+cid;

            // unbind event listeners
            this.container.parents().add(window).each(function () { $(this).off(scroll).off(resize).off(orient); });

            this.clearDropdownAlignmentPreference();

            $("#select2-drop-mask").hide();
            this.dropdown.removeAttr("id"); // only the active dropdown has the select2-drop id
            this.dropdown.hide();
            this.container.removeClass("select2-dropdown-open");
            this.results.empty();


            this.clearSearch();
            this.search.removeClass("select2-active");
            this.opts.element.trigger($.Event("select2-close"));
        },

        /**
         * Opens control, sets input value, and updates results.
         */
        // abstract
        externalSearch: function (term) {
            this.open();
            this.search.val(term);
            this.updateResults(false);
        },

        // abstract
        clearSearch: function () {

        },

        //abstract
        getMaximumSelectionSize: function() {
            return evaluate(this.opts.maximumSelectionSize);
        },

        // abstract
        ensureHighlightVisible: function () {
            var results = this.results, children, index, child, hb, rb, y, more;

            index = this.highlight();

            if (index < 0) return;

            if (index == 0) {

                // if the first element is highlighted scroll all the way to the top,
                // that way any unselectable headers above it will also be scrolled
                // into view

                results.scrollTop(0);
                return;
            }

            children = this.findHighlightableChoices().find('.select2-result-label');

            child = $(children[index]);

            hb = child.offset().top + child.outerHeight(true);

            // if this is the last child lets also make sure select2-more-results is visible
            if (index === children.length - 1) {
                more = results.find("li.select2-more-results");
                if (more.length > 0) {
                    hb = more.offset().top + more.outerHeight(true);
                }
            }

            rb = results.offset().top + results.outerHeight(true);
            if (hb > rb) {
                results.scrollTop(results.scrollTop() + (hb - rb));
            }
            y = child.offset().top - results.offset().top;

            // make sure the top of the element is visible
            if (y < 0 && child.css('display') != 'none' ) {
                results.scrollTop(results.scrollTop() + y); // y is negative
            }
        },

        // abstract
        findHighlightableChoices: function() {
            return this.results.find(".select2-result-selectable:not(.select2-selected):not(.select2-disabled)");
        },

        // abstract
        moveHighlight: function (delta) {
            var choices = this.findHighlightableChoices(),
                index = this.highlight();

            while (index > -1 && index < choices.length) {
                index += delta;
                var choice = $(choices[index]);
                if (choice.hasClass("select2-result-selectable") && !choice.hasClass("select2-disabled") && !choice.hasClass("select2-selected")) {
                    this.highlight(index);
                    break;
                }
            }
        },

        // abstract
        highlight: function (index) {
            var choices = this.findHighlightableChoices(),
                choice,
                data;

            if (arguments.length === 0) {
                return indexOf(choices.filter(".select2-highlighted")[0], choices.get());
            }

            if (index >= choices.length) index = choices.length - 1;
            if (index < 0) index = 0;

            this.results.find(".select2-highlighted").removeClass("select2-highlighted");

            choice = $(choices[index]);
            choice.addClass("select2-highlighted");

            this.ensureHighlightVisible();

            data = choice.data("select2-data");
            if (data) {
                this.opts.element.trigger({ type: "select2-highlight", val: this.id(data), choice: data });
            }
        },

        // abstract
        countSelectableResults: function() {
            return this.findHighlightableChoices().length;
        },

        // abstract
        highlightUnderEvent: function (event) {
            var el = $(event.target).closest(".select2-result-selectable");
            if (el.length > 0 && !el.is(".select2-highlighted")) {
                var choices = this.findHighlightableChoices();
                this.highlight(choices.index(el));
            } else if (el.length == 0) {
                // if we are over an unselectable item remove al highlights
                this.results.find(".select2-highlighted").removeClass("select2-highlighted");
            }
        },

        // abstract
        loadMoreIfNeeded: function () {
            var results = this.results,
                more = results.find("li.select2-more-results"),
                below, // pixels the element is below the scroll fold, below==0 is when the element is starting to be visible
                offset = -1, // index of first element without data
                page = this.resultsPage + 1,
                self=this,
                term=this.search.val(),
                context=this.context;

            if (more.length === 0) return;
            below = more.offset().top - results.offset().top - results.height();

            if (below <= this.opts.loadMorePadding) {
                more.addClass("select2-active");
                this.opts.query({
                    element: this.opts.element,
                    term: term,
                    page: page,
                    context: context,
                    matcher: this.opts.matcher,
                    callback: this.bind(function (data) {

                        // ignore a response if the select2 has been closed before it was received
                        if (!self.opened()) return;


                        self.opts.populateResults.call(this, results, data.results, {term: term, page: page, context:context});
                        self.postprocessResults(data, false, false);

                        if (data.more===true) {
                            more.detach().appendTo(results).text(self.opts.formatLoadMore(page+1));
                            window.setTimeout(function() { self.loadMoreIfNeeded(); }, 10);
                        } else {
                            more.remove();
                        }
                        self.positionDropdown();
                        self.resultsPage = page;
                        self.context = data.context;
                    })});
            }
        },

        /**
         * Default tokenizer function which does nothing
         */
        tokenize: function() {

        },

        /**
         * @param initial whether or not this is the call to this method right after the dropdown has been opened
         */
        // abstract
        updateResults: function (initial) {
            var search = this.search,
                results = this.results,
                opts = this.opts,
                data,
                self = this,
                input,
                term = search.val(),
                lastTerm=$.data(this.container, "select2-last-term");

            // prevent duplicate queries against the same term
            if (initial !== true && lastTerm && equal(term, lastTerm)) return;

            $.data(this.container, "select2-last-term", term);

            // if the search is currently hidden we do not alter the results
            if (initial !== true && (this.showSearchInput === false || !this.opened())) {
                return;
            }

            function postRender() {
                search.removeClass("select2-active");
                self.positionDropdown();
            }

            function render(html) {
                results.html(html);
                postRender();
            }

            var maxSelSize = this.getMaximumSelectionSize();
            if (maxSelSize >=1) {
                data = this.data();
                if ($.isArray(data) && data.length >= maxSelSize && checkFormatter(opts.formatSelectionTooBig, "formatSelectionTooBig")) {
                    render("<li class='select2-selection-limit'>" + opts.formatSelectionTooBig(maxSelSize) + "</li>");
                    return;
                }
            }

            if (search.val().length < opts.minimumInputLength) {
                if (checkFormatter(opts.formatInputTooShort, "formatInputTooShort")) {
                    render("<li class='select2-no-results'>" + opts.formatInputTooShort(search.val(), opts.minimumInputLength) + "</li>");
                } else {
                    render("");
                }
                if (initial && this.showSearch) this.showSearch(true);
                return;
            }

            if (opts.maximumInputLength && search.val().length > opts.maximumInputLength) {
                if (checkFormatter(opts.formatInputTooLong, "formatInputTooLong")) {
                    render("<li class='select2-no-results'>" + opts.formatInputTooLong(search.val(), opts.maximumInputLength) + "</li>");
                } else {
                    render("");
                }
                return;
            }

            if (opts.formatSearching && this.findHighlightableChoices().length === 0) {
                render("<li class='select2-searching'>" + opts.formatSearching() + "</li>");
            }

            search.addClass("select2-active");

            // give the tokenizer a chance to pre-process the input
            input = this.tokenize();
            if (input != undefined && input != null) {
                search.val(input);
            }

            this.resultsPage = 1;

            opts.query({
                element: opts.element,
                term: search.val(),
                page: this.resultsPage,
                context: null,
                matcher: opts.matcher,
                callback: this.bind(function (data) {
                    var def; // default choice

                    // ignore a response if the select2 has been closed before it was received
                    if (!this.opened()) {
                        this.search.removeClass("select2-active");
                        return;
                    }

                    // save context, if any
                    this.context = (data.context===undefined) ? null : data.context;
                    // create a default choice and prepend it to the list
                    if (this.opts.createSearchChoice && search.val() !== "") {
                        def = this.opts.createSearchChoice.call(null, search.val(), data.results);
                        if (def !== undefined && def !== null && self.id(def) !== undefined && self.id(def) !== null) {
                            if ($(data.results).filter(
                                function () {
                                    return equal(self.id(this), self.id(def));
                                }).length === 0) {
                                data.results.unshift(def);
                            }
                        }
                    }

                    if (data.results.length === 0 && checkFormatter(opts.formatNoMatches, "formatNoMatches")) {
                        render("<li class='select2-no-results'>" + opts.formatNoMatches(search.val()) + "</li>");
                        return;
                    }

                    results.empty();
                    self.opts.populateResults.call(this, results, data.results, {term: search.val(), page: this.resultsPage, context:null});

                    if (data.more === true && checkFormatter(opts.formatLoadMore, "formatLoadMore")) {
                        results.append("<li class='select2-more-results'>" + self.opts.escapeMarkup(opts.formatLoadMore(this.resultsPage)) + "</li>");
                        window.setTimeout(function() { self.loadMoreIfNeeded(); }, 10);
                    }

                    this.postprocessResults(data, initial);

                    postRender();

                    this.opts.element.trigger({ type: "select2-loaded", items: data });
                })});
        },

        // abstract
        cancel: function () {
            this.close();
        },

        // abstract
        blur: function () {
            // if selectOnBlur == true, select the currently highlighted option
            if (this.opts.selectOnBlur)
                this.selectHighlighted({noFocus: true});

            this.close();
            this.container.removeClass("select2-container-active");
            // synonymous to .is(':focus'), which is available in jquery >= 1.6
            if (this.search[0] === document.activeElement) { this.search.blur(); }
            this.clearSearch();
            this.selection.find(".select2-search-choice-focus").removeClass("select2-search-choice-focus");
        },

        // abstract
        focusSearch: function () {
            focus(this.search);
        },

        // abstract
        selectHighlighted: function (options) {
            var index=this.highlight(),
                highlighted=this.results.find(".select2-highlighted"),
                data = highlighted.closest('.select2-result').data("select2-data");

            if (data) {
                this.highlight(index);
                this.onSelect(data, options);
            } else if (options.noFocus) {
                this.close();
            }
        },

        // abstract
        getPlaceholder: function () {
            var placeholderOption;
            return this.opts.element.attr("placeholder") ||
                this.opts.element.attr("data-placeholder") || // jquery 1.4 compat
                this.opts.element.data("placeholder") ||
                this.opts.placeholder ||
                ((placeholderOption = this.getPlaceholderOption()) !== undefined ? placeholderOption.text() : undefined);
        },

        // abstract
        getPlaceholderOption: function() {
            if (this.select) {
                var firstOption = this.select.children().first();
                if (this.opts.placeholderOption !== undefined ) {
                    //Determine the placeholder option based on the specified placeholderOption setting
                    return (this.opts.placeholderOption === "first" && firstOption) ||
                        (typeof this.opts.placeholderOption === "function" && this.opts.placeholderOption(this.select));
                } else if (firstOption.text() === "" && firstOption.val() === "") {
                    //No explicit placeholder option specified, use the first if it's blank
                    return firstOption;
                }
            }
        },

        /**
         * Get the desired width for the container element.  This is
         * derived first from option `width` passed to select2, then
         * the inline 'style' on the original element, and finally
         * falls back to the jQuery calculated element width.
         */
        // abstract
        initContainerWidth: function () {
            function resolveContainerWidth() {
                var style, attrs, matches, i, l;

                if (this.opts.width === "off") {
                    return null;
                } else if (this.opts.width === "element"){
                    return this.opts.element.outerWidth(false) === 0 ? 'auto' : this.opts.element.outerWidth(false) + 'px';
                } else if (this.opts.width === "copy" || this.opts.width === "resolve") {
                    // check if there is inline style on the element that contains width
                    style = this.opts.element.attr('style');
                    if (style !== undefined) {
                        attrs = style.split(';');
                        for (i = 0, l = attrs.length; i < l; i = i + 1) {
                            matches = attrs[i].replace(/\s/g, '')
                                .match(/width:(([-+]?([0-9]*\.)?[0-9]+)(px|em|ex|%|in|cm|mm|pt|pc))/i);
                            if (matches !== null && matches.length >= 1)
                                return matches[1];
                        }
                    }

                    if (this.opts.width === "resolve") {
                        // next check if css('width') can resolve a width that is percent based, this is sometimes possible
                        // when attached to input type=hidden or elements hidden via css
                        style = this.opts.element.css('width');
                        if (style.indexOf("%") > 0) return style;

                        // finally, fallback on the calculated width of the element
                        return (this.opts.element.outerWidth(false) === 0 ? 'auto' : this.opts.element.outerWidth(false) + 'px');
                    }

                    return null;
                } else if ($.isFunction(this.opts.width)) {
                    return this.opts.width();
                } else {
                    return this.opts.width;
                }
            };

            var width = resolveContainerWidth.call(this);
            if (width !== null) {
                this.container.css("width", width);
            }
        }
    });

    SingleSelect2 = clazz(AbstractSelect2, {

        // single

        createContainer: function () {
            var container = $(document.createElement("div")).attr({
                "class": "select2-container"
            }).html([
                    "<a href='javascript:void(0)' onclick='return false;' class='select2-choice' tabindex='-1'>",
                    "   <span>&nbsp;</span><abbr class='select2-search-choice-close'></abbr>",
                    "   <div><b></b></div>" ,
                    "</a>",
                    "<input class='select2-focusser select2-offscreen' type='text'/>",
                    "<div class='select2-drop select2-display-none'>" ,
                    "   <div class='select2-search'>" ,
                    "       <input type='text' autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' class='select2-input'/>" ,
                    "   </div>" ,
                    "   <ul class='select2-results'>" ,
                    "   </ul>" ,
                    "</div>"].join(""));
            return container;
        },

        // single
        enableInterface: function() {
            if (this.parent.enableInterface.apply(this, arguments)) {
                this.focusser.prop("disabled", !this.isInterfaceEnabled());
            }
        },

        // single
        opening: function () {
            var el, range, len;

            if (this.opts.minimumResultsForSearch >= 0) {
                this.showSearch(true);
            }

            this.parent.opening.apply(this, arguments);

            if (this.showSearchInput !== false) {
                // IE appends focusser.val() at the end of field :/ so we manually insert it at the beginning using a range
                // all other browsers handle this just fine

                this.search.val(this.focusser.val());
            }
            this.search.focus();
            // move the cursor to the end after focussing, otherwise it will be at the beginning and
            // new text will appear *before* focusser.val()
            el = this.search.get(0);
            if (el.createTextRange) {
                range = el.createTextRange();
                range.collapse(false);
                range.select();
            } else if (el.setSelectionRange) {
                len = this.search.val().length;
                el.setSelectionRange(len, len);
            }

            this.focusser.prop("disabled", true).val("");
            this.updateResults(true);
            this.opts.element.trigger($.Event("select2-open"));
        },

        // single
        close: function () {
            if (!this.opened()) return;
            this.parent.close.apply(this, arguments);
            this.focusser.removeAttr("disabled");
            this.focusser.focus();
        },

        // single
        focus: function () {
            if (this.opened()) {
                this.close();
            } else {
                this.focusser.removeAttr("disabled");
                this.focusser.focus();
            }
        },

        // single
        isFocused: function () {
            return this.container.hasClass("select2-container-active");
        },

        // single
        cancel: function () {
            this.parent.cancel.apply(this, arguments);
            this.focusser.removeAttr("disabled");
            this.focusser.focus();
        },

        // single
        initContainer: function () {

            var selection,
                container = this.container,
                dropdown = this.dropdown;

            if (this.opts.minimumResultsForSearch < 0) {
                this.showSearch(false);
            } else {
                this.showSearch(true);
            }

            this.selection = selection = container.find(".select2-choice");

            this.focusser = container.find(".select2-focusser");

            // rewrite labels from original element to focusser
            this.focusser.attr("id", "s2id_autogen"+nextUid());

            $("label[for='" + this.opts.element.attr("id") + "']")
                .attr('for', this.focusser.attr('id'));

            this.focusser.attr("tabindex", this.elementTabIndex);

            this.search.on("keydown", this.bind(function (e) {
                if (!this.isInterfaceEnabled()) return;

                if (e.which === KEY.PAGE_UP || e.which === KEY.PAGE_DOWN) {
                    // prevent the page from scrolling
                    killEvent(e);
                    return;
                }

                switch (e.which) {
                    case KEY.UP:
                    case KEY.DOWN:
                        this.moveHighlight((e.which === KEY.UP) ? -1 : 1);
                        killEvent(e);
                        return;
                    case KEY.ENTER:
                        this.selectHighlighted();
                        killEvent(e);
                        return;
                    case KEY.TAB:
                        this.selectHighlighted({noFocus: true});
                        return;
                    case KEY.ESC:
                        this.cancel(e);
                        killEvent(e);
                        return;
                }
            }));

            this.search.on("blur", this.bind(function(e) {
                // a workaround for chrome to keep the search field focussed when the scroll bar is used to scroll the dropdown.
                // without this the search field loses focus which is annoying
                if (document.activeElement === this.body().get(0)) {
                    window.setTimeout(this.bind(function() {
                        this.search.focus();
                    }), 0);
                }
            }));

            this.focusser.on("keydown", this.bind(function (e) {
                if (!this.isInterfaceEnabled()) return;

                if (e.which === KEY.TAB || KEY.isControl(e) || KEY.isFunctionKey(e) || e.which === KEY.ESC) {
                    return;
                }

                if (this.opts.openOnEnter === false && e.which === KEY.ENTER) {
                    killEvent(e);
                    return;
                }

                if (e.which == KEY.DOWN || e.which == KEY.UP
                    || (e.which == KEY.ENTER && this.opts.openOnEnter)) {

                    if (e.altKey || e.ctrlKey || e.shiftKey || e.metaKey) return;

                    this.open();
                    killEvent(e);
                    return;
                }

                if (e.which == KEY.DELETE || e.which == KEY.BACKSPACE) {
                    if (this.opts.allowClear) {
                        this.clear();
                    }
                    killEvent(e);
                    return;
                }
            }));


            installKeyUpChangeEvent(this.focusser);
            this.focusser.on("keyup-change input", this.bind(function(e) {
                if (this.opts.minimumResultsForSearch >= 0) {
                    e.stopPropagation();
                    if (this.opened()) return;
                    this.open();
                }
            }));

            selection.on("mousedown", "abbr", this.bind(function (e) {
                if (!this.isInterfaceEnabled()) return;
                this.clear();
                killEventImmediately(e);
                this.close();
                this.selection.focus();
            }));

            selection.on("mousedown", this.bind(function (e) {

                if (!this.container.hasClass("select2-container-active")) {
                    this.opts.element.trigger($.Event("select2-focus"));
                }

                if (this.opened()) {
                    this.close();
                } else if (this.isInterfaceEnabled()) {
                    this.open();
                }

                killEvent(e);
            }));

            dropdown.on("mousedown", this.bind(function() { this.search.focus(); }));

            selection.on("focus", this.bind(function(e) {
                killEvent(e);
            }));

            this.focusser.on("focus", this.bind(function(){
                    if (!this.container.hasClass("select2-container-active")) {
                        this.opts.element.trigger($.Event("select2-focus"));
                    }
                    this.container.addClass("select2-container-active");
                })).on("blur", this.bind(function() {
                    if (!this.opened()) {
                        this.container.removeClass("select2-container-active");
                        this.opts.element.trigger($.Event("select2-blur"));
                    }
                }));
            this.search.on("focus", this.bind(function(){
                if (!this.container.hasClass("select2-container-active")) {
                    this.opts.element.trigger($.Event("select2-focus"));
                }
                this.container.addClass("select2-container-active");
            }));

            this.initContainerWidth();
            this.opts.element.addClass("select2-offscreen");
            this.setPlaceholder();

        },

        // single
        clear: function(triggerChange) {
            var data=this.selection.data("select2-data");
            if (data) { // guard against queued quick consecutive clicks
                var placeholderOption = this.getPlaceholderOption();
                this.opts.element.val(placeholderOption ? placeholderOption.val() : "");
                this.selection.find("span").empty();
                this.selection.removeData("select2-data");
                this.setPlaceholder();

                if (triggerChange !== false){
                    this.opts.element.trigger({ type: "select2-removed", val: this.id(data), choice: data });
                    this.triggerChange({removed:data});
                }
            }
        },

        /**
         * Sets selection based on source element's value
         */
        // single
        initSelection: function () {
            var selected;
            if (this.isPlaceholderOptionSelected()) {
                this.updateSelection([]);
                this.close();
                this.setPlaceholder();
            } else {
                var self = this;
                this.opts.initSelection.call(null, this.opts.element, function(selected){
                    if (selected !== undefined && selected !== null) {
                        self.updateSelection(selected);
                        self.close();
                        self.setPlaceholder();
                    }
                });
            }
        },

        isPlaceholderOptionSelected: function() {
            var placeholderOption;
            return ((placeholderOption = this.getPlaceholderOption()) !== undefined && placeholderOption.is(':selected')) ||
                (this.opts.element.val() === "") ||
                (this.opts.element.val() === undefined) ||
                (this.opts.element.val() === null);
        },

        // single
        prepareOpts: function () {
            var opts = this.parent.prepareOpts.apply(this, arguments),
                self=this;

            if (opts.element.get(0).tagName.toLowerCase() === "select") {
                // install the selection initializer
                opts.initSelection = function (element, callback) {
                    var selected = element.find(":selected");
                    // a single select box always has a value, no need to null check 'selected'
                    callback(self.optionToData(selected));
                };
            } else if ("data" in opts) {
                // install default initSelection when applied to hidden input and data is local
                opts.initSelection = opts.initSelection || function (element, callback) {
                    var id = element.val();
                    //search in data by id, storing the actual matching item
                    var match = null;
                    opts.query({
                        matcher: function(term, text, el){
                            var is_match = equal(id, opts.id(el));
                            if (is_match) {
                                match = el;
                            }
                            return is_match;
                        },
                        callback: !$.isFunction(callback) ? $.noop : function() {
                            callback(match);
                        }
                    });
                };
            }

            return opts;
        },

        // single
        getPlaceholder: function() {
            // if a placeholder is specified on a single select without a valid placeholder option ignore it
            if (this.select) {
                if (this.getPlaceholderOption() === undefined) {
                    return undefined;
                }
            }

            return this.parent.getPlaceholder.apply(this, arguments);
        },

        // single
        setPlaceholder: function () {
            var placeholder = this.getPlaceholder();

            if (this.isPlaceholderOptionSelected() && placeholder !== undefined) {

                // check for a placeholder option if attached to a select
                if (this.select && this.getPlaceholderOption() === undefined) return;

                this.selection.find("span").html(this.opts.escapeMarkup(placeholder));

                this.selection.addClass("select2-default");

                this.container.removeClass("select2-allowclear");
            }
        },

        // single
        postprocessResults: function (data, initial, noHighlightUpdate) {
            var selected = 0, self = this, showSearchInput = true;

            // find the selected element in the result list

            this.findHighlightableChoices().each2(function (i, elm) {
                if (equal(self.id(elm.data("select2-data")), self.opts.element.val())) {
                    selected = i;
                    return false;
                }
            });

            // and highlight it
            if (noHighlightUpdate !== false) {
                if (initial === true && selected >= 0) {
                    this.highlight(selected);
                } else {
                    this.highlight(0);
                }
            }

            // hide the search box if this is the first we got the results and there are enough of them for search

            if (initial === true) {
                var min = this.opts.minimumResultsForSearch;
                if (min >= 0) {
                    this.showSearch(countResults(data.results) >= min);
                }
            }
        },

        // single
        showSearch: function(showSearchInput) {
            if (this.showSearchInput === showSearchInput) return;

            this.showSearchInput = showSearchInput;

            this.dropdown.find(".select2-search").toggleClass("select2-search-hidden", !showSearchInput);
            this.dropdown.find(".select2-search").toggleClass("select2-offscreen", !showSearchInput);
            //add "select2-with-searchbox" to the container if search box is shown
            $(this.dropdown, this.container).toggleClass("select2-with-searchbox", showSearchInput);
        },

        // single
        onSelect: function (data, options) {

            if (!this.triggerSelect(data)) { return; }

            var old = this.opts.element.val(),
                oldData = this.data();

            this.opts.element.val(this.id(data));
            this.updateSelection(data);

            this.opts.element.trigger({ type: "select2-selected", val: this.id(data), choice: data });

            this.close();

            if (!options || !options.noFocus)
                this.selection.focus();

            if (!equal(old, this.id(data))) { this.triggerChange({added:data,removed:oldData}); }
        },

        // single
        updateSelection: function (data) {

            var container=this.selection.find("span"), formatted, cssClass;

            this.selection.data("select2-data", data);

            container.empty();
            formatted=this.opts.formatSelection(data, container);
            if (formatted !== undefined) {
                container.append(this.opts.escapeMarkup(formatted));
            }
            cssClass=this.opts.formatSelectionCssClass(data, container);
            if (cssClass !== undefined) {
                container.addClass(cssClass);
            }

            this.selection.removeClass("select2-default");

            if (this.opts.allowClear && this.getPlaceholder() !== undefined) {
                this.container.addClass("select2-allowclear");
            }
        },

        // single
        val: function () {
            var val,
                triggerChange = false,
                data = null,
                self = this,
                oldData = this.data();

            if (arguments.length === 0) {
                return this.opts.element.val();
            }

            val = arguments[0];

            if (arguments.length > 1) {
                triggerChange = arguments[1];
            }

            if (this.select) {
                this.select
                    .val(val)
                    .find(":selected").each2(function (i, elm) {
                        data = self.optionToData(elm);
                        return false;
                    });
                this.updateSelection(data);
                this.setPlaceholder();
                if (triggerChange) {
                    this.triggerChange({added: data, removed:oldData});
                }
            } else {
                // val is an id. !val is true for [undefined,null,'',0] - 0 is legal
                if (!val && val !== 0) {
                    this.clear(triggerChange);
                    return;
                }
                if (this.opts.initSelection === undefined) {
                    throw new Error("cannot call val() if initSelection() is not defined");
                }
                this.opts.element.val(val);
                this.opts.initSelection(this.opts.element, function(data){
                    self.opts.element.val(!data ? "" : self.id(data));
                    self.updateSelection(data);
                    self.setPlaceholder();
                    if (triggerChange) {
                        self.triggerChange({added: data, removed:oldData});
                    }
                });
            }
        },

        // single
        clearSearch: function () {
            this.search.val("");
            this.focusser.val("");
        },

        // single
        data: function(value, triggerChange) {
            var data;

            if (arguments.length === 0) {
                data = this.selection.data("select2-data");
                if (data == undefined) data = null;
                return data;
            } else {
                if (!value || value === "") {
                    this.clear(triggerChange);
                } else {
                    data = this.data();
                    this.opts.element.val(!value ? "" : this.id(value));
                    this.updateSelection(value);
                    if (triggerChange) {
                        this.triggerChange({added: value, removed:data});
                    }
                }
            }
        }
    });

    MultiSelect2 = clazz(AbstractSelect2, {

        // multi
        createContainer: function () {
            var container = $(document.createElement("div")).attr({
                "class": "select2-container select2-container-multi"
            }).html([
                    "    <ul class='select2-choices'>",
                    //"<li class='select2-search-choice'><span>California</span><a href="javascript:void(0)" class="select2-search-choice-close"></a></li>" ,
                    "  <li class='select2-search-field'>" ,
                    "    <input type='text' autocomplete='off' autocorrect='off' autocapitilize='off' spellcheck='false' class='select2-input'>" ,
                    "  </li>" ,
                    "</ul>" ,
                    "<div class='select2-drop select2-drop-multi select2-display-none'>" ,
                    "   <ul class='select2-results'>" ,
                    "   </ul>" ,
                    "</div>"].join(""));
            return container;
        },

        // multi
        prepareOpts: function () {
            var opts = this.parent.prepareOpts.apply(this, arguments),
                self=this;

            // TODO validate placeholder is a string if specified

            if (opts.element.get(0).tagName.toLowerCase() === "select") {
                // install sthe selection initializer
                opts.initSelection = function (element, callback) {

                    var data = [];

                    element.find(":selected").each2(function (i, elm) {
                        data.push(self.optionToData(elm));
                    });
                    callback(data);
                };
            } else if ("data" in opts) {
                // install default initSelection when applied to hidden input and data is local
                opts.initSelection = opts.initSelection || function (element, callback) {
                    var ids = splitVal(element.val(), opts.separator);
                    //search in data by array of ids, storing matching items in a list
                    var matches = [];
                    opts.query({
                        matcher: function(term, text, el){
                            var is_match = $.grep(ids, function(id) {
                                return equal(id, opts.id(el));
                            }).length;
                            if (is_match) {
                                matches.push(el);
                            }
                            return is_match;
                        },
                        callback: !$.isFunction(callback) ? $.noop : function() {
                            // reorder matches based on the order they appear in the ids array because right now
                            // they are in the order in which they appear in data array
                            var ordered = [];
                            for (var i = 0; i < ids.length; i++) {
                                var id = ids[i];
                                for (var j = 0; j < matches.length; j++) {
                                    var match = matches[j];
                                    if (equal(id, opts.id(match))) {
                                        ordered.push(match);
                                        matches.splice(j, 1);
                                        break;
                                    }
                                }
                            }
                            callback(ordered);
                        }
                    });
                };
            }

            return opts;
        },

        selectChoice: function (choice) {

            var selected = this.container.find(".select2-search-choice-focus");
            if (selected.length && choice && choice[0] == selected[0]) {

            } else {
                if (selected.length) {
                    this.opts.element.trigger("choice-deselected", selected);
                }
                selected.removeClass("select2-search-choice-focus");
                if (choice && choice.length) {
                    this.close();
                    choice.addClass("select2-search-choice-focus");
                    this.opts.element.trigger("choice-selected", choice);
                }
            }
        },

        // multi
        initContainer: function () {

            var selector = ".select2-choices", selection;

            this.searchContainer = this.container.find(".select2-search-field");
            this.selection = selection = this.container.find(selector);

            var _this = this;
            this.selection.on("mousedown", ".select2-search-choice", function (e) {
                //killEvent(e);
                _this.search[0].focus();
                _this.selectChoice($(this));
            })
            //.sortable({
            //    items: " > li",
            //    tolerance: "pointer",
            //    revert: 100
            //});

            // rewrite labels from original element to focusser
            this.search.attr("id", "s2id_autogen"+nextUid());
            $("label[for='" + this.opts.element.attr("id") + "']")
                .attr('for', this.search.attr('id'));

            this.search.on("input paste", this.bind(function() {
                if (!this.isInterfaceEnabled()) return;
                if (!this.opened()) {
                    this.open();
                }
            }));

            this.search.attr("tabindex", this.elementTabIndex);

            this.keydowns = 0;
            this.search.on("keydown", this.bind(function (e) {
                if (!this.isInterfaceEnabled()) return;

                ++this.keydowns;
                var selected = selection.find(".select2-search-choice-focus");
                var prev = selected.prev(".select2-search-choice:not(.select2-locked)");
                var next = selected.next(".select2-search-choice:not(.select2-locked)");
                var pos = getCursorInfo(this.search);

                if (selected.length &&
                    (e.which == KEY.LEFT || e.which == KEY.RIGHT || e.which == KEY.BACKSPACE || e.which == KEY.DELETE || e.which == KEY.ENTER)) {
                    var selectedChoice = selected;
                    if (e.which == KEY.LEFT && prev.length) {
                        selectedChoice = prev;
                    }
                    else if (e.which == KEY.RIGHT) {
                        selectedChoice = next.length ? next : null;
                    }
                    else if (e.which === KEY.BACKSPACE) {
                        this.unselect(selected.first());
                        this.search.width(10);
                        selectedChoice = prev.length ? prev : next;
                    } else if (e.which == KEY.DELETE) {
                        this.unselect(selected.first());
                        this.search.width(10);
                        selectedChoice = next.length ? next : null;
                    } else if (e.which == KEY.ENTER) {
                        selectedChoice = null;
                    }

                    this.selectChoice(selectedChoice);
                    killEvent(e);
                    if (!selectedChoice || !selectedChoice.length) {
                        this.open();
                    }
                    return;
                } else if (((e.which === KEY.BACKSPACE && this.keydowns == 1)
                    || e.which == KEY.LEFT) && (pos.offset == 0 && !pos.length)) {

                    this.selectChoice(selection.find(".select2-search-choice:not(.select2-locked)").last());
                    killEvent(e);
                    return;
                } else {
                    this.selectChoice(null);
                }

                if (this.opened()) {
                    switch (e.which) {
                        case KEY.UP:
                        case KEY.DOWN:
                            this.moveHighlight((e.which === KEY.UP) ? -1 : 1);
                            killEvent(e);
                            return;
                        case KEY.ENTER:
                            this.selectHighlighted();
                            killEvent(e);
                            return;
                        case KEY.TAB:
                            this.selectHighlighted({noFocus:true});
                            this.close();
                            return;
                        case KEY.ESC:
                            this.cancel(e);
                            killEvent(e);
                            return;
                    }
                }

                if (e.which === KEY.TAB || KEY.isControl(e) || KEY.isFunctionKey(e)
                    || e.which === KEY.BACKSPACE || e.which === KEY.ESC) {
                    return;
                }

                if (e.which === KEY.ENTER) {
                    if (this.opts.openOnEnter === false) {
                        return;
                    } else if (e.altKey || e.ctrlKey || e.shiftKey || e.metaKey) {
                        return;
                    }
                }

                this.open();

                if (e.which === KEY.PAGE_UP || e.which === KEY.PAGE_DOWN) {
                    // prevent the page from scrolling
                    killEvent(e);
                }

                if (e.which === KEY.ENTER) {
                    // prevent form from being submitted
                    killEvent(e);
                }

            }));

            this.search.on("keyup", this.bind(function (e) {
                this.keydowns = 0;
                this.resizeSearch();
            })
            );

            this.search.on("blur", this.bind(function(e) {
                this.container.removeClass("select2-container-active");
                this.search.removeClass("select2-focused");
                this.selectChoice(null);
                if (!this.opened()) this.clearSearch();
                e.stopImmediatePropagation();
                this.opts.element.trigger($.Event("select2-blur"));
            }));

            this.container.on("click", selector, this.bind(function (e) {
                if (!this.isInterfaceEnabled()) return;
                if ($(e.target).closest(".select2-search-choice").length > 0) {
                    // clicked inside a select2 search choice, do not open
                    return;
                }
                this.selectChoice(null);
                this.clearPlaceholder();
                if (!this.container.hasClass("select2-container-active")) {
                    this.opts.element.trigger($.Event("select2-focus"));
                }
                this.open();
                this.focusSearch();
                e.preventDefault();
            }));

            this.container.on("focus", selector, this.bind(function () {
                if (!this.isInterfaceEnabled()) return;
                if (!this.container.hasClass("select2-container-active")) {
                    this.opts.element.trigger($.Event("select2-focus"));
                }
                this.container.addClass("select2-container-active");
                this.dropdown.addClass("select2-drop-active");
                this.clearPlaceholder();
            }));

            this.initContainerWidth();
            this.opts.element.addClass("select2-offscreen");

            // set the placeholder if necessary
            this.clearSearch();
        },

        // multi
        enableInterface: function() {
            if (this.parent.enableInterface.apply(this, arguments)) {
                this.search.prop("disabled", !this.isInterfaceEnabled());
            }
        },

        // multi
        initSelection: function () {
            var data;
            if (this.opts.element.val() === "" && this.opts.element.text() === "") {
                this.updateSelection([]);
                this.close();
                // set the placeholder if necessary
                this.clearSearch();
            }
            if (this.select || this.opts.element.val() !== "") {
                var self = this;
                this.opts.initSelection.call(null, this.opts.element, function(data){
                    if (data !== undefined && data !== null) {
                        self.updateSelection(data);
                        self.close();
                        // set the placeholder if necessary
                        self.clearSearch();
                    }
                });
            }
        },

        // multi
        clearSearch: function () {
            var placeholder = this.getPlaceholder(),
                maxWidth = this.getMaxSearchWidth();

            if (placeholder !== undefined  && this.getVal().length === 0 && this.search.hasClass("select2-focused") === false) {
                this.search.val(placeholder).addClass("select2-default");
                // stretch the search box to full width of the container so as much of the placeholder is visible as possible
                // we could call this.resizeSearch(), but we do not because that requires a sizer and we do not want to create one so early because of a firefox bug, see #944
                this.search.width(maxWidth > 0 ? maxWidth : this.container.css("width"));
            } else {
                this.search.val("").width(10);
            }
        },

        // multi
        clearPlaceholder: function () {
            if (this.search.hasClass("select2-default")) {
                this.search.val("").removeClass("select2-default");
            }
        },

        // multi
        opening: function () {
            this.clearPlaceholder(); // should be done before super so placeholder is not used to search
            this.resizeSearch();

            this.parent.opening.apply(this, arguments);

            this.focusSearch();

            this.updateResults(true);
            this.search.focus();
            this.opts.element.trigger($.Event("select2-open"));
        },

        // multi
        close: function () {
            if (!this.opened()) return;
            this.parent.close.apply(this, arguments);
        },

        // multi
        focus: function () {
            this.close();
            this.search.focus();
            //this.opts.element.triggerHandler("focus");
        },

        // multi
        isFocused: function () {
            return this.search.hasClass("select2-focused");
        },

        // multi
        updateSelection: function (data) {
            var ids = [], filtered = [], self = this;

            // filter out duplicates
            $(data).each(function () {
                if (indexOf(self.id(this), ids) < 0) {
                    ids.push(self.id(this));
                    filtered.push(this);
                }
            });
            data = filtered;

            this.selection.find(".select2-search-choice").remove();
            $(data).each(function () {
                self.addSelectedChoice(this);
            });
            self.postprocessResults();
        },

        // multi
        tokenize: function() {
            var input = this.search.val();
            input = this.opts.tokenizer(input, this.data(), this.bind(this.onSelect), this.opts);
            if (input != null && input != undefined) {
                this.search.val(input);
                if (input.length > 0) {
                    this.open();
                }
            }

        },

        // multi
        onSelect: function (data, options) {

            if (!this.triggerSelect(data)) { return; }

            this.addSelectedChoice(data);

            this.opts.element.trigger({ type: "selected", val: this.id(data), choice: data });

            if (this.select || !this.opts.closeOnSelect) this.postprocessResults();

            if (this.opts.closeOnSelect) {
                this.close();
                this.search.width(10);
            } else {
                if (this.countSelectableResults()>0) {
                    this.search.width(10);
                    this.resizeSearch();
                    if (this.getMaximumSelectionSize() > 0 && this.val().length >= this.getMaximumSelectionSize()) {
                        // if we reached max selection size repaint the results so choices
                        // are replaced with the max selection reached message
                        this.updateResults(true);
                    }
                    this.positionDropdown();
                } else {
                    // if nothing left to select close
                    this.close();
                    this.search.width(10);
                }
            }

            // since its not possible to select an element that has already been
            // added we do not need to check if this is a new element before firing change
            this.triggerChange({ added: data });

            if (!options || !options.noFocus)
                this.focusSearch();
        },

        // multi
        cancel: function () {
            this.close();
            this.focusSearch();
        },

        addSelectedChoice: function (data) {
            var enableChoice = !data.locked,
                enabledItem = $(
                    "<li class='select2-search-choice'>" +
                        "    <div></div>" +
                        "    <a href='#' onclick='return false;' class='select2-search-choice-close' tabindex='-1'></a>" +
                        "</li>"),
                disabledItem = $(
                    "<li class='select2-search-choice select2-locked'>" +
                        "<div></div>" +
                        "</li>");
            var choice = enableChoice ? enabledItem : disabledItem,
                id = this.id(data),
                val = this.getVal(),
                formatted,
                cssClass;

            formatted=this.opts.formatSelection(data, choice.find("div"));
            if (formatted != undefined) {
                choice.find("div").replaceWith("<div>"+this.opts.escapeMarkup(formatted)+"</div>");
            }
            cssClass=this.opts.formatSelectionCssClass(data, choice.find("div"));
            if (cssClass != undefined) {
                choice.addClass(cssClass);
            }

            if(enableChoice){
                choice.find(".select2-search-choice-close")
                    .on("mousedown", killEvent)
                    .on("click dblclick", this.bind(function (e) {
                        if (!this.isInterfaceEnabled()) return;

                        $(e.target).closest(".select2-search-choice").fadeOut('fast', this.bind(function(){
                            this.unselect($(e.target));
                            this.selection.find(".select2-search-choice-focus").removeClass("select2-search-choice-focus");
                            this.close();
                            this.focusSearch();
                        })).dequeue();
                        killEvent(e);
                    })).on("focus", this.bind(function () {
                        if (!this.isInterfaceEnabled()) return;
                        this.container.addClass("select2-container-active");
                        this.dropdown.addClass("select2-drop-active");
                    }));
            }

            choice.data("select2-data", data);
            choice.insertBefore(this.searchContainer);

            val.push(id);
            this.setVal(val);
        },

        // multi
        unselect: function (selected) {
            var val = this.getVal(),
                data,
                index;

            selected = selected.closest(".select2-search-choice");

            if (selected.length === 0) {
                throw "Invalid argument: " + selected + ". Must be .select2-search-choice";
            }

            data = selected.data("select2-data");

            if (!data) {
                // prevent a race condition when the 'x' is clicked really fast repeatedly the event can be queued
                // and invoked on an element already removed
                return;
            }

            index = indexOf(this.id(data), val);

            if (index >= 0) {
                val.splice(index, 1);
                this.setVal(val);
                if (this.select) this.postprocessResults();
            }
            selected.remove();

            this.opts.element.trigger({ type: "removed", val: this.id(data), choice: data });
            this.triggerChange({ removed: data });
        },

        // multi
        postprocessResults: function (data, initial, noHighlightUpdate) {
            var val = this.getVal(),
                choices = this.results.find(".select2-result"),
                compound = this.results.find(".select2-result-with-children"),
                self = this;

            choices.each2(function (i, choice) {
                var id = self.id(choice.data("select2-data"));
                if (indexOf(id, val) >= 0) {
                    choice.addClass("select2-selected");
                    // mark all children of the selected parent as selected
                    choice.find(".select2-result-selectable").addClass("select2-selected");
                }
            });

            compound.each2(function(i, choice) {
                // hide an optgroup if it doesnt have any selectable children
                if (!choice.is('.select2-result-selectable')
                    && choice.find(".select2-result-selectable:not(.select2-selected)").length === 0) {
                    choice.addClass("select2-selected");
                }
            });

            if (this.highlight() == -1 && noHighlightUpdate !== false){
                self.highlight(0);
            }

            //If all results are chosen render formatNoMAtches
            if(!this.opts.createSearchChoice && !choices.filter('.select2-result:not(.select2-selected)').length > 0){
                if(!data || data && !data.more && this.results.find(".select2-no-results").length === 0) {
                    if (checkFormatter(self.opts.formatNoMatches, "formatNoMatches")) {
                        this.results.append("<li class='select2-no-results'>" + self.opts.formatNoMatches(self.search.val()) + "</li>");
                    }
                }
            }

        },

        // multi
        getMaxSearchWidth: function() {
            return this.selection.width() - getSideBorderPadding(this.search);
        },

        // multi
        resizeSearch: function () {
            var minimumWidth, left, maxWidth, containerLeft, searchWidth,
                sideBorderPadding = getSideBorderPadding(this.search);

            minimumWidth = measureTextWidth(this.search) + 10;

            left = this.search.offset().left;

            maxWidth = this.selection.width();
            containerLeft = this.selection.offset().left;

            searchWidth = maxWidth - (left - containerLeft) - sideBorderPadding;

            if (searchWidth < minimumWidth) {
                searchWidth = maxWidth - sideBorderPadding;
            }

            if (searchWidth < 40) {
                searchWidth = maxWidth - sideBorderPadding;
            }

            if (searchWidth <= 0) {
                searchWidth = minimumWidth;
            }

            this.search.width(searchWidth);
        },

        // multi
        getVal: function () {
            var val;
            if (this.select) {
                val = this.select.val();
                return val === null ? [] : val;
            } else {
                val = this.opts.element.val();
                return splitVal(val, this.opts.separator);
            }
        },

        // multi
        setVal: function (val) {
            var unique;
            if (this.select) {
                this.select.val(val);
            } else {
                unique = [];
                // filter out duplicates
                $(val).each(function () {
                    if (indexOf(this, unique) < 0) unique.push(this);
                });
                this.opts.element.val(unique.length === 0 ? "" : unique.join(this.opts.separator));
            }
        },

        // multi
        buildChangeDetails: function (old, current) {
            var current = current.slice(0),
                old = old.slice(0);

            // remove intersection from each array
            for (var i = 0; i < current.length; i++) {
                for (var j = 0; j < old.length; j++) {
                    if (equal(this.opts.id(current[i]), this.opts.id(old[j]))) {
                        current.splice(i, 1);
                        i--;
                        old.splice(j, 1);
                        j--;
                    }
                }
            }

            return {added: current, removed: old};
        },


        // multi
        val: function (val, triggerChange) {
            var oldData, self=this, changeDetails;

            if (arguments.length === 0) {
                return this.getVal();
            }

            oldData=this.data();
            if (!oldData.length) oldData=[];

            // val is an id. !val is true for [undefined,null,'',0] - 0 is legal
            if (!val && val !== 0) {
                this.opts.element.val("");
                this.updateSelection([]);
                this.clearSearch();
                if (triggerChange) {
                    this.triggerChange({added: this.data(), removed: oldData});
                }
                return;
            }

            // val is a list of ids
            this.setVal(val);

            if (this.select) {
                this.opts.initSelection(this.select, this.bind(this.updateSelection));
                if (triggerChange) {
                    this.triggerChange(this.buildChangeDetails(oldData, this.data()));
                }
            } else {
                if (this.opts.initSelection === undefined) {
                    throw new Error("val() cannot be called if initSelection() is not defined");
                }

                this.opts.initSelection(this.opts.element, function(data){
                    var ids=$.map(data, self.id);
                    self.setVal(ids);
                    self.updateSelection(data);
                    self.clearSearch();
                    if (triggerChange) {
                        self.triggerChange(this.buildChangeDetails(oldData, this.data()));
                    }
                });
            }
            this.clearSearch();
        },

        // multi
        onSortStart: function() {
            if (this.select) {
                throw new Error("Sorting of elements is not supported when attached to <select>. Attach to <input type='hidden'/> instead.");
            }

            // collapse search field into 0 width so its container can be collapsed as well
            this.search.width(0);
            // hide the container
            this.searchContainer.hide();
        },

        // multi
        onSortEnd:function() {

            var val=[], self=this;

            // show search and move it to the end of the list
            this.searchContainer.show();
            // make sure the search container is the last item in the list
            this.searchContainer.appendTo(this.searchContainer.parent());
            // since we collapsed the width in dragStarted, we resize it here
            this.resizeSearch();

            // update selection

            this.selection.find(".select2-search-choice").each(function() {
                val.push(self.opts.id($(this).data("select2-data")));
            });
            this.setVal(val);
            this.triggerChange();
        },

        // multi
        data: function(values, triggerChange) {
            var self=this, ids, old;
            if (arguments.length === 0) {
                return this.selection
                    .find(".select2-search-choice")
                    .map(function() { return $(this).data("select2-data"); })
                    .get();
            } else {
                old = this.data();
                if (!values) { values = []; }
                ids = $.map(values, function(e) { return self.opts.id(e); });
                this.setVal(ids);
                this.updateSelection(values);
                this.clearSearch();
                if (triggerChange) {
                    this.triggerChange(this.buildChangeDetails(old, this.data()));
                }
            }
        }
    });

    $.fn.select2 = function () {

        var args = Array.prototype.slice.call(arguments, 0),
            opts,
            select2,
            method, value, multiple,
            allowedMethods = ["val", "destroy", "opened", "open", "close", "focus", "isFocused", "container", "dropdown", "onSortStart", "onSortEnd", "enable", "readonly", "positionDropdown", "data", "search"],
            valueMethods = ["val", "opened", "isFocused", "container", "data"],
            methodsMap = { search: "externalSearch" };

        this.each(function () {
            if (args.length === 0 || typeof(args[0]) === "object") {
                opts = args.length === 0 ? {} : $.extend({}, args[0]);
                opts.element = $(this);

                if (opts.element.get(0).tagName.toLowerCase() === "select") {
                    multiple = opts.element.prop("multiple");
                } else {
                    multiple = opts.multiple || false;
                    if ("tags" in opts) {opts.multiple = multiple = true;}
                }

                select2 = multiple ? new MultiSelect2() : new SingleSelect2();
                select2.init(opts);
            } else if (typeof(args[0]) === "string") {

                if (indexOf(args[0], allowedMethods) < 0) {
                    throw "Unknown method: " + args[0];
                }

                value = undefined;
                select2 = $(this).data("select2");
                if (select2 === undefined) return;

                method=args[0];

                if (method === "container") {
                    value = select2.container;
                } else if (method === "dropdown") {
                    value = select2.dropdown;
                } else {
                    if (methodsMap[method]) method = methodsMap[method];

                    value = select2[method].apply(select2, args.slice(1));
                }
                if (indexOf(args[0], valueMethods) >= 0) {
                    return false;
                }
            } else {
                throw "Invalid arguments to select2 plugin: " + args;
            }
        });
        return (value === undefined) ? this : value;
    };

    // plugin defaults, accessible to users
    $.fn.select2.defaults = {
        width: "copy",
        loadMorePadding: 0,
        closeOnSelect: true,
        openOnEnter: true,
        containerCss: {},
        dropdownCss: {},
        containerCssClass: "",
        dropdownCssClass: "",
        formatResult: function(result, container, query, escapeMarkup) {
            var markup=[];
            markMatch(result.text, query.term, markup, escapeMarkup);
            return markup.join("");
        },
        formatSelection: function (data, container) {
            return data ? data.text : undefined;
        },
        sortResults: function (results, container, query) {
            return results;
        },
        formatResultCssClass: function(data) {return undefined;},
        formatSelectionCssClass: function(data, container) {return undefined;},
        formatNoMatches: function () { return "No matches found"; },
        formatInputTooShort: function (input, min) { var n = min - input.length; return "Please enter " + n + " more character" + (n == 1? "" : "s"); },
        formatInputTooLong: function (input, max) { var n = input.length - max; return "Please delete " + n + " character" + (n == 1? "" : "s"); },
        formatSelectionTooBig: function (limit) { return "You can only select " + limit + " item" + (limit == 1 ? "" : "s"); },
        formatLoadMore: function (pageNumber) { return "Loading more results..."; },
        formatSearching: function () { return "Searching..."; },
        minimumResultsForSearch: 0,
        minimumInputLength: 0,
        maximumInputLength: null,
        maximumSelectionSize: 0,
        id: function (e) { return e.id; },
        matcher: function(term, text) {
            return (''+text).toUpperCase().indexOf((''+term).toUpperCase()) >= 0;
        },
        separator: ",",
        tokenSeparators: [],
        tokenizer: defaultTokenizer,
        escapeMarkup: defaultEscapeMarkup,
        blurOnChange: false,
        selectOnBlur: false,
        adaptContainerCssClass: function(c) { return c; },
        adaptDropdownCssClass: function(c) { return null; }
    };

    $.fn.select2.ajaxDefaults = {
        transport: $.ajax,
        params: {
            type: "GET",
            cache: false,
            dataType: "json"
        }
    };

    // exports
    window.Select2 = {
        query: {
            ajax: ajax,
            local: local,
            tags: tags
        }, util: {
            debounce: debounce,
            markMatch: markMatch,
            escapeMarkup: defaultEscapeMarkup
        }, "class": {
            "abstract": AbstractSelect2,
            "single": SingleSelect2,
            "multi": MultiSelect2
        }
    };

}(jQuery));
(function(){function e(e,t){try{for(var n in t)Object.defineProperty(e.prototype,n,{value:t[n],enumerable:!1})}catch(r){e.prototype=t}}function t(e){var t=-1,n=e.length,r=[];while(++t<n)r.push(e[t]);return r}function n(e){return Array.prototype.slice.call(e)}function r(){}function i(e){return e}function s(){return this}function o(){return!0}function u(e){return typeof e=="function"?e:function(){return e}}function a(e,t,n){return function(){var r=n.apply(t,arguments);return arguments.length?e:r}}function f(e){return e!=null&&!isNaN(e)}function l(e){return e.length}function c(e){return e==null}function h(e){return e.trim().replace(/\s+/g," ")}function p(e){var t=1;while(e*t%1)t*=10;return t}function d(){}function v(e){function t(){var t=n,r=-1,i=t.length,s;while(++r<i)(s=t[r].on)&&s.apply(this,arguments);return e}var n=[],i=new r;return t.on=function(t,r){var s=i.get(t),o;return arguments.length<2?s&&s.on:(s&&(s.on=null,n=n.slice(0,o=n.indexOf(s)).concat(n.slice(o+1)),i.remove(t)),r&&n.push(i.set(t,{on:r})),e)},t}function m(e,t){return t-(e?1+Math.floor(Math.log(e+Math.pow(10,1+Math.floor(Math.log(e)/Math.LN10)-t))/Math.LN10):1)}function g(e){return e+""}function y(e){var t=e.lastIndexOf("."),n=t>=0?e.substring(t):(t=e.length,""),r=[];while(t>0)r.push(e.substring(t-=3,t+3));return r.reverse().join(",")+n}function b(e,t){var n=Math.pow(10,Math.abs(8-t)*3);return{scale:t>8?function(e){return e/n}:function(e){return e*n},symbol:e}}function w(e){return function(t){return t<=0?0:t>=1?1:e(t)}}function E(e){return function(t){return 1-e(1-t)}}function S(e){return function(t){return.5*(t<.5?e(2*t):2-e(2-2*t))}}function x(e){return e}function T(e){return function(t){return Math.pow(t,e)}}function N(e){return 1-Math.cos(e*Math.PI/2)}function C(e){return Math.pow(2,10*(e-1))}function k(e){return 1-Math.sqrt(1-e*e)}function L(e,t){var n;return arguments.length<2&&(t=.45),arguments.length<1?(e=1,n=t/4):n=t/(2*Math.PI)*Math.asin(1/e),function(r){return 1+e*Math.pow(2,10*-r)*Math.sin((r-n)*2*Math.PI/t)}}function A(e){return e||(e=1.70158),function(t){return t*t*((e+1)*t-e)}}function O(e){return e<1/2.75?7.5625*e*e:e<2/2.75?7.5625*(e-=1.5/2.75)*e+.75:e<2.5/2.75?7.5625*(e-=2.25/2.75)*e+.9375:7.5625*(e-=2.625/2.75)*e+.984375}function M(){d3.event.stopPropagation(),d3.event.preventDefault()}function _(){var e=d3.event,t;while(t=e.sourceEvent)e=t;return e}function D(e){var t=new d,n=0,r=arguments.length;while(++n<r)t[arguments[n]]=v(t);return t.of=function(n,r){return function(i){try{var s=i.sourceEvent=d3.event;i.target=e,d3.event=i,t[i.type].apply(n,r)}finally{d3.event=s}}},t}function P(e){var t=[e.a,e.b],n=[e.c,e.d],r=B(t),i=H(t,n),s=B(j(n,t,-i))||0;t[0]*n[1]<n[0]*t[1]&&(t[0]*=-1,t[1]*=-1,r*=-1,i*=-1),this.rotate=(r?Math.atan2(t[1],t[0]):Math.atan2(-n[0],n[1]))*ls,this.translate=[e.e,e.f],this.scale=[r,s],this.skew=s?Math.atan2(i,s)*ls:0}function H(e,t){return e[0]*t[0]+e[1]*t[1]}function B(e){var t=Math.sqrt(H(e,e));return t&&(e[0]/=t,e[1]/=t),t}function j(e,t,n){return e[0]+=n*t[0],e[1]+=n*t[1],e}function F(e){return e=="transform"?d3.interpolateTransform:d3.interpolate}function I(e,t){return t=t-(e=+e)?1/(t-e):0,function(n){return(n-e)*t}}function q(e,t){return t=t-(e=+e)?1/(t-e):0,function(n){return Math.max(0,Math.min(1,(n-e)*t))}}function R(){}function U(e,t,n){return new z(e,t,n)}function z(e,t,n){this.r=e,this.g=t,this.b=n}function W(e){return e<16?"0"+Math.max(0,e).toString(16):Math.min(255,e).toString(16)}function X(e,t,n){var r=0,i=0,s=0,o,u,a;o=/([a-z]+)\((.*)\)/i.exec(e);if(o){u=o[2].split(",");switch(o[1]){case"hsl":return n(parseFloat(u[0]),parseFloat(u[1])/100,parseFloat(u[2])/100);case"rgb":return t(K(u[0]),K(u[1]),K(u[2]))}}return(a=ds.get(e))?t(a.r,a.g,a.b):(e!=null&&e.charAt(0)==="#"&&(e.length===4?(r=e.charAt(1),r+=r,i=e.charAt(2),i+=i,s=e.charAt(3),s+=s):e.length===7&&(r=e.substring(1,3),i=e.substring(3,5),s=e.substring(5,7)),r=parseInt(r,16),i=parseInt(i,16),s=parseInt(s,16)),t(r,i,s))}function V(e,t,n){var r=Math.min(e/=255,t/=255,n/=255),i=Math.max(e,t,n),s=i-r,o,u,a=(i+r)/2;return s?(u=a<.5?s/(i+r):s/(2-i-r),e==i?o=(t-n)/s+(t<n?6:0):t==i?o=(n-e)/s+2:o=(e-t)/s+4,o*=60):u=o=0,Q(o,u,a)}function $(e,t,n){e=J(e),t=J(t),n=J(n);var r=ut((.4124564*e+.3575761*t+.1804375*n)/ys),i=ut((.2126729*e+.7151522*t+.072175*n)/bs),s=ut((.0193339*e+.119192*t+.9503041*n)/ws);return nt(116*i-16,500*(r-i),200*(i-s))}function J(e){return(e/=255)<=.04045?e/12.92:Math.pow((e+.055)/1.055,2.4)}function K(e){var t=parseFloat(e);return e.charAt(e.length-1)==="%"?Math.round(t*2.55):t}function Q(e,t,n){return new G(e,t,n)}function G(e,t,n){this.h=e,this.s=t,this.l=n}function Y(e,t,n){function r(e){return e>360?e-=360:e<0&&(e+=360),e<60?s+(o-s)*e/60:e<180?o:e<240?s+(o-s)*(240-e)/60:s}function i(e){return Math.round(r(e)*255)}var s,o;return e%=360,e<0&&(e+=360),t=t<0?0:t>1?1:t,n=n<0?0:n>1?1:n,o=n<=.5?n*(1+t):n+t-n*t,s=2*n-o,U(i(e+120),i(e),i(e-120))}function Z(e,t,n){return new et(e,t,n)}function et(e,t,n){this.h=e,this.c=t,this.l=n}function tt(e,t,n){return nt(n,Math.cos(e*=Math.PI/180)*t,Math.sin(e)*t)}function nt(e,t,n){return new rt(e,t,n)}function rt(e,t,n){this.l=e,this.a=t,this.b=n}function it(e,t,n){var r=(e+16)/116,i=r+t/500,s=r-n/200;return i=ot(i)*ys,r=ot(r)*bs,s=ot(s)*ws,U(at(3.2404542*i-1.5371385*r-.4985314*s),at(-0.969266*i+1.8760108*r+.041556*s),at(.0556434*i-.2040259*r+1.0572252*s))}function st(e,t,n){return Z(Math.atan2(n,t)/Math.PI*180,Math.sqrt(t*t+n*n),e)}function ot(e){return e>.206893034?e*e*e:(e-4/29)/7.787037}function ut(e){return e>.008856?Math.pow(e,1/3):7.787037*e+4/29}function at(e){return Math.round(255*(e<=.00304?12.92*e:1.055*Math.pow(e,1/2.4)-.055))}function ft(e){return Qi(e,ks),e}function lt(e){return function(){return Ss(e,this)}}function ct(e){return function(){return xs(e,this)}}function ht(e,t){function n(){this.removeAttribute(e)}function r(){this.removeAttributeNS(e.space,e.local)}function i(){this.setAttribute(e,t)}function s(){this.setAttributeNS(e.space,e.local,t)}function o(){var n=t.apply(this,arguments);n==null?this.removeAttribute(e):this.setAttribute(e,n)}function u(){var n=t.apply(this,arguments);n==null?this.removeAttributeNS(e.space,e.local):this.setAttributeNS(e.space,e.local,n)}return e=d3.ns.qualify(e),t==null?e.local?r:n:typeof t=="function"?e.local?u:o:e.local?s:i}function pt(e){return new RegExp("(?:^|\\s+)"+d3.requote(e)+"(?:\\s+|$)","g")}function dt(e,t){function n(){var n=-1;while(++n<i)e[n](this,t)}function r(){var n=-1,r=t.apply(this,arguments);while(++n<i)e[n](this,r)}e=e.trim().split(/\s+/).map(vt);var i=e.length;return typeof t=="function"?r:n}function vt(e){var t=pt(e);return function(n,r){if(i=n.classList)return r?i.add(e):i.remove(e);var i=n.className,s=i.baseVal!=null,o=s?i.baseVal:i;r?(t.lastIndex=0,t.test(o)||(o=h(o+" "+e),s?i.baseVal=o:n.className=o)):o&&(o=h(o.replace(t," ")),s?i.baseVal=o:n.className=o)}}function mt(e,t,n){function r(){this.style.removeProperty(e)}function i(){this.style.setProperty(e,t,n)}function s(){var r=t.apply(this,arguments);r==null?this.style.removeProperty(e):this.style.setProperty(e,r,n)}return t==null?r:typeof t=="function"?s:i}function gt(e,t){function n(){delete this[e]}function r(){this[e]=t}function i(){var n=t.apply(this,arguments);n==null?delete this[e]:this[e]=n}return t==null?n:typeof t=="function"?i:r}function yt(e){return{__data__:e}}function bt(e){return function(){return Cs(this,e)}}function wt(e){return arguments.length||(e=d3.ascending),function(t,n){return e(t&&t.__data__,n&&n.__data__)}}function Et(e,t,n){function r(){var t=this[s];t&&(this.removeEventListener(e,t,t.$),delete this[s])}function i(){function i(e){var n=d3.event;d3.event=e,u[0]=o.__data__;try{t.apply(o,u)}finally{d3.event=n}}var o=this,u=arguments;r.call(this),this.addEventListener(e,this[s]=i,i.$=n),i._=t}var s="__on"+e,o=e.indexOf(".");return o>0&&(e=e.substring(0,o)),t?i:r}function St(e,t){for(var n=0,r=e.length;n<r;n++)for(var i=e[n],s=0,o=i.length,u;s<o;s++)(u=i[s])&&t(u,s,n);return e}function xt(e){return Qi(e,As),e}function Tt(e,t,n){Qi(e,Os);var i=new r,s=d3.dispatch("start","end"),o=Fs;return e.id=t,e.time=n,e.tween=function(t,n){return arguments.length<2?i.get(t):(n==null?i.remove(t):i.set(t,n),e)},e.ease=function(t){return arguments.length?(o=typeof t=="function"?t:d3.ease.apply(d3,arguments),e):o},e.each=function(t,n){return arguments.length<2?Nt.call(e,t):(s.on(t,n),e)},d3.timer(function(r){return St(e,function(e,u,a){function f(r){return v.active>t?c():(v.active=t,i.forEach(function(t,n){(n=n.call(e,m,u))&&h.push(n)}),s.start.call(e,m,u),l(r)||d3.timer(l,0,n),1)}function l(n){if(v.active!==t)return c();var r=(n-p)/d,i=o(r),a=h.length;while(a>0)h[--a].call(e,i);if(r>=1)return c(),_s=t,s.end.call(e,m,u),_s=0,1}function c(){return--v.count||delete e.__transition__,1}var h=[],p=e.delay,d=e.duration,v=(e=e.node).__transition__||(e.__transition__={active:0,count:0}),m=e.__data__;++v.count,p<=r?f(r):d3.timer(f,p,n)})},0,n),e}function Nt(e){var t=_s,n=Fs,r=Bs,i=js;return _s=this.id,Fs=this.ease(),St(this,function(t,n,r){Bs=t.delay,js=t.duration,e.call(t=t.node,t.__data__,n,r)}),_s=t,Fs=n,Bs=r,js=i,this}function Ct(e,t,n){return n!=""&&Is}function kt(e,t){return d3.tween(e,F(t))}function Lt(){var e,t=Date.now(),n=Us;while(n)e=t-n.then,e>=n.delay&&(n.flush=n.callback(e)),n=n.next;var r=At()-t;r>24?(isFinite(r)&&(clearTimeout(Ws),Ws=setTimeout(Lt,r)),zs=0):(zs=1,Xs(Lt))}function At(){var e=null,t=Us,n=Infinity;while(t)t.flush?(delete Rs[t.callback.id],t=e?e.next=t.next:Us=t.next):(n=Math.min(n,t.then+t.delay),t=(e=t).next);return n}function Ot(e,t){var n=e.ownerSVGElement||e;if(n.createSVGPoint){var r=n.createSVGPoint();if(Vs<0&&(window.scrollX||window.scrollY)){n=d3.select(document.body).append("svg").style("position","absolute").style("top",0).style("left",0);var i=n[0][0].getScreenCTM();Vs=!i.f&&!i.e,n.remove()}return Vs?(r.x=t.pageX,r.y=t.pageY):(r.x=t.clientX,r.y=t.clientY),r=r.matrixTransform(e.getScreenCTM().inverse()),[r.x,r.y]}var s=e.getBoundingClientRect();return[t.clientX-s.left-e.clientLeft,t.clientY-s.top-e.clientTop]}function Mt(){}function _t(e){var t=e[0],n=e[e.length-1];return t<n?[t,n]:[n,t]}function Dt(e){return e.rangeExtent?e.rangeExtent():_t(e.range())}function Pt(e,t){var n=0,r=e.length-1,i=e[n],s=e[r],o;s<i&&(o=n,n=r,r=o,o=i,i=s,s=o);if(t=t(s-i))e[n]=t.floor(i),e[r]=t.ceil(s);return e}function Ht(){return Math}function Bt(e,t,n,r){function i(){var i=Math.min(e.length,t.length)>2?zt:Ut,a=r?q:I;return o=i(e,t,a,n),u=i(t,e,a,d3.interpolate),s}function s(e){return o(e)}var o,u;return s.invert=function(e){return u(e)},s.domain=function(t){return arguments.length?(e=t.map(Number),i()):e},s.range=function(e){return arguments.length?(t=e,i()):t},s.rangeRound=function(e){return s.range(e).interpolate(d3.interpolateRound)},s.clamp=function(e){return arguments.length?(r=e,i()):r},s.interpolate=function(e){return arguments.length?(n=e,i()):n},s.ticks=function(t){return qt(e,t)},s.tickFormat=function(t){return Rt(e,t)},s.nice=function(){return Pt(e,Ft),i()},s.copy=function(){return Bt(e,t,n,r)},i()}function jt(e,t){return d3.rebind(e,t,"range","rangeRound","interpolate","clamp")}function Ft(e){return e=Math.pow(10,Math.round(Math.log(e)/Math.LN10)-1),e&&{floor:function(t){return Math.floor(t/e)*e},ceil:function(t){return Math.ceil(t/e)*e}}}function It(e,t){var n=_t(e),r=n[1]-n[0],i=Math.pow(10,Math.floor(Math.log(r/t)/Math.LN10)),s=t/r*i;return s<=.15?i*=10:s<=.35?i*=5:s<=.75&&(i*=2),n[0]=Math.ceil(n[0]/i)*i,n[1]=Math.floor(n[1]/i)*i+i*.5,n[2]=i,n}function qt(e,t){return d3.range.apply(d3,It(e,t))}function Rt(e,t){return d3.format(",."+Math.max(0,-Math.floor(Math.log(It(e,t)[2])/Math.LN10+.01))+"f")}function Ut(e,t,n,r){var i=n(e[0],e[1]),s=r(t[0],t[1]);return function(e){return s(i(e))}}function zt(e,t,n,r){var i=[],s=[],o=0,u=Math.min(e.length,t.length)-1;e[u]<e[0]&&(e=e.slice().reverse(),t=t.slice().reverse());while(++o<=u)i.push(n(e[o-1],e[o])),s.push(r(t[o-1],t[o]));return function(t){var n=d3.bisect(e,t,1,u)-1;return s[n](i[n](t))}}function Wt(e,t){function n(n){return e(t(n))}var r=t.pow;return n.invert=function(t){return r(e.invert(t))},n.domain=function(i){return arguments.length?(t=i[0]<0?Vt:Xt,r=t.pow,e.domain(i.map(t)),n):e.domain().map(r)},n.nice=function(){return e.domain(Pt(e.domain(),Ht)),n},n.ticks=function(){var n=_t(e.domain()),i=[];if(n.every(isFinite)){var s=Math.floor(n[0]),o=Math.ceil(n[1]),u=r(n[0]),a=r(n[1]);if(t===Vt){i.push(r(s));for(;s++<o;)for(var f=9;f>0;f--)i.push(r(s)*f)}else{for(;s<o;s++)for(var f=1;f<10;f++)i.push(r(s)*f);i.push(r(s))}for(s=0;i[s]<u;s++);for(o=i.length;i[o-1]>a;o--);i=i.slice(s,o)}return i},n.tickFormat=function(e,i){arguments.length<2&&(i=$s);if(arguments.length<1)return i;var s=Math.max(.1,e/n.ticks().length),o=t===Vt?(u=-1e-12,Math.floor):(u=1e-12,Math.ceil),u;return function(e){return e/r(o(t(e)+u))<=s?i(e):""}},n.copy=function(){return Wt(e.copy(),t)},jt(n,e)}function Xt(e){return Math.log(e<0?0:e)/Math.LN10}function Vt(e){return-Math.log(e>0?0:-e)/Math.LN10}function $t(e,t){function n(t){return e(r(t))}var r=Jt(t),i=Jt(1/t);return n.invert=function(t){return i(e.invert(t))},n.domain=function(t){return arguments.length?(e.domain(t.map(r)),n):e.domain().map(i)},n.ticks=function(e){return qt(n.domain(),e)},n.tickFormat=function(e){return Rt(n.domain(),e)},n.nice=function(){return n.domain(Pt(n.domain(),Ft))},n.exponent=function(e){if(!arguments.length)return t;var s=n.domain();return r=Jt(t=e),i=Jt(1/t),n.domain(s)},n.copy=function(){return $t(e.copy(),t)},jt(n,e)}function Jt(e){return function(t){return t<0?-Math.pow(-t,e):Math.pow(t,e)}}function Kt(e,t){function n(t){return o[((s.get(t)||s.set(t,e.push(t)))-1)%o.length]}function i(t,n){return d3.range(e.length).map(function(e){return t+n*e})}var s,o,u;return n.domain=function(i){if(!arguments.length)return e;e=[],s=new r;var o=-1,u=i.length,a;while(++o<u)s.has(a=i[o])||s.set(a,e.push(a));return n[t.t].apply(n,t.a)},n.range=function(e){return arguments.length?(o=e,u=0,t={t:"range",a:arguments},n):o},n.rangePoints=function(r,s){arguments.length<2&&(s=0);var a=r[0],f=r[1],l=(f-a)/(Math.max(1,e.length-1)+s);return o=i(e.length<2?(a+f)/2:a+l*s/2,l),u=0,t={t:"rangePoints",a:arguments},n},n.rangeBands=function(r,s,a){arguments.length<2&&(s=0),arguments.length<3&&(a=s);var f=r[1]<r[0],l=r[f-0],c=r[1-f],h=(c-l)/(e.length-s+2*a);return o=i(l+h*a,h),f&&o.reverse(),u=h*(1-s),t={t:"rangeBands",a:arguments},n},n.rangeRoundBands=function(r,s,a){arguments.length<2&&(s=0),arguments.length<3&&(a=s);var f=r[1]<r[0],l=r[f-0],c=r[1-f],h=Math.floor((c-l)/(e.length-s+2*a)),p=c-l-(e.length-s)*h;return o=i(l+Math.round(p/2),h),f&&o.reverse(),u=Math.round(h*(1-s)),t={t:"rangeRoundBands",a:arguments},n},n.rangeBand=function(){return u},n.rangeExtent=function(){return _t(t.a[0])},n.copy=function(){return Kt(e,t)},n.domain(e)}function Qt(e,t){function n(){var n=0,s=e.length,o=t.length;i=[];while(++n<o)i[n-1]=d3.quantile(e,n/o);return r}function r(e){return isNaN(e=+e)?NaN:t[d3.bisect(i,e)]}var i;return r.domain=function(t){return arguments.length?(e=t.filter(function(e){return!isNaN(e)}).sort(d3.ascending),n()):e},r.range=function(e){return arguments.length?(t=e,n()):t},r.quantiles=function(){return i},r.copy=function(){return Qt(e,t)},n()}function Gt(e,t,n){function r(t){return n[Math.max(0,Math.min(o,Math.floor(s*(t-e))))]}function i(){return s=n.length/(t-e),o=n.length-1,r}var s,o;return r.domain=function(n){return arguments.length?(e=+n[0],t=+n[n.length-1],i()):[e,t]},r.range=function(e){return arguments.length?(n=e,i()):n},r.copy=function(){return Gt(e,t,n)},i()}function Yt(e,t){function n(n){return t[d3.bisect(e,n)]}return n.domain=function(t){return arguments.length?(e=t,n):e},n.range=function(e){return arguments.length?(t=e,n):t},n.copy=function(){return Yt(e,t)},n}function Zt(e){function t(e){return+e}return t.invert=t,t.domain=t.range=function(n){return arguments.length?(e=n.map(t),t):e},t.ticks=function(t){return qt(e,t)},t.tickFormat=function(t){return Rt(e,t)},t.copy=function(){return Zt(e)},t}function en(e){return e.innerRadius}function tn(e){return e.outerRadius}function nn(e){return e.startAngle}function rn(e){return e.endAngle}function sn(e){function t(t){function o(){a.push("M",s(e(l),f))}var a=[],l=[],c=-1,h=t.length,p,d=u(n),v=u(r);while(++c<h)i.call(this,p=t[c],c)?l.push([+d.call(this,p,c),+v.call(this,p,c)]):l.length&&(o(),l=[]);return l.length&&o(),a.length?a.join(""):null}var n=on,r=un,i=o,s=an,a=s.key,f=.7;return t.x=function(e){return arguments.length?(n=e,t):n},t.y=function(e){return arguments.length?(r=e,t):r},t.defined=function(e){return arguments.length?(i=e,t):i},t.interpolate=function(e){return arguments.length?(typeof e=="function"?a=s=e:a=(s=eo.get(e)||an).key,t):a},t.tension=function(e){return arguments.length?(f=e,t):f},t}function on(e){return e[0]}function un(e){return e[1]}function an(e){return e.join("L")}function fn(e){return an(e)+"Z"}function ln(e){var t=0,n=e.length,r=e[0],i=[r[0],",",r[1]];while(++t<n)i.push("V",(r=e[t])[1],"H",r[0]);return i.join("")}function cn(e){var t=0,n=e.length,r=e[0],i=[r[0],",",r[1]];while(++t<n)i.push("H",(r=e[t])[0],"V",r[1]);return i.join("")}function hn(e,t){return e.length<4?an(e):e[1]+vn(e.slice(1,e.length-1),mn(e,t))}function pn(e,t){return e.length<3?an(e):e[0]+vn((e.push(e[0]),e),mn([e[e.length-2]].concat(e,[e[1]]),t))}function dn(e,t,n){return e.length<3?an(e):e[0]+vn(e,mn(e,t))}function vn(e,t){if(t.length<1||e.length!=t.length&&e.length!=t.length+2)return an(e);var n=e.length!=t.length,r="",i=e[0],s=e[1],o=t[0],u=o,a=1;n&&(r+="Q"+(s[0]-o[0]*2/3)+","+(s[1]-o[1]*2/3)+","+s[0]+","+s[1],i=e[1],a=2);if(t.length>1){u=t[1],s=e[a],a++,r+="C"+(i[0]+o[0])+","+(i[1]+o[1])+","+(s[0]-u[0])+","+(s[1]-u[1])+","+s[0]+","+s[1];for(var f=2;f<t.length;f++,a++)s=e[a],u=t[f],r+="S"+(s[0]-u[0])+","+(s[1]-u[1])+","+s[0]+","+s[1]}if(n){var l=e[a];r+="Q"+(s[0]+u[0]*2/3)+","+(s[1]+u[1]*2/3)+","+l[0]+","+l[1]}return r}function mn(e,t){var n=[],r=(1-t)/2,i,s=e[0],o=e[1],u=1,a=e.length;while(++u<a)i=s,s=o,o=e[u],n.push([r*(o[0]-i[0]),r*(o[1]-i[1])]);return n}function gn(e){if(e.length<3)return an(e);var t=1,n=e.length,r=e[0],i=r[0],s=r[1],o=[i,i,i,(r=e[1])[0]],u=[s,s,s,r[1]],a=[i,",",s];Sn(a,o,u);while(++t<n)r=e[t],o.shift(),o.push(r[0]),u.shift(),u.push(r[1]),Sn(a,o,u);t=-1;while(++t<2)o.shift(),o.push(r[0]),u.shift(),u.push(r[1]),Sn(a,o,u);return a.join("")}function yn(e){if(e.length<4)return an(e);var t=[],n=-1,r=e.length,i,s=[0],o=[0];while(++n<3)i=e[n],s.push(i[0]),o.push(i[1]);t.push(En(ro,s)+","+En(ro,o)),--n;while(++n<r)i=e[n],s.shift(),s.push(i[0]),o.shift(),o.push(i[1]),Sn(t,s,o);return t.join("")}function bn(e){var t,n=-1,r=e.length,i=r+4,s,o=[],u=[];while(++n<4)s=e[n%r],o.push(s[0]),u.push(s[1]);t=[En(ro,o),",",En(ro,u)],--n;while(++n<i)s=e[n%r],o.shift(),o.push(s[0]),u.shift(),u.push(s[1]),Sn(t,o,u);return t.join("")}function wn(e,t){var n=e.length-1;if(n){var r=e[0][0],i=e[0][1],s=e[n][0]-r,o=e[n][1]-i,u=-1,a,f;while(++u<=n)a=e[u],f=u/n,a[0]=t*a[0]+(1-t)*(r+f*s),a[1]=t*a[1]+(1-t)*(i+f*o)}return gn(e)}function En(e,t){return e[0]*t[0]+e[1]*t[1]+e[2]*t[2]+e[3]*t[3]}function Sn(e,t,n){e.push("C",En(to,t),",",En(to,n),",",En(no,t),",",En(no,n),",",En(ro,t),",",En(ro,n))}function xn(e,t){return(t[1]-e[1])/(t[0]-e[0])}function Tn(e){var t=0,n=e.length-1,r=[],i=e[0],s=e[1],o=r[0]=xn(i,s);while(++t<n)r[t]=(o+(o=xn(i=s,s=e[t+1])))/2;return r[t]=o,r}function Nn(e){var t=[],n,r,i,s,o=Tn(e),u=-1,a=e.length-1;while(++u<a)n=xn(e[u],e[u+1]),Math.abs(n)<1e-6?o[u]=o[u+1]=0:(r=o[u]/n,i=o[u+1]/n,s=r*r+i*i,s>9&&(s=n*3/Math.sqrt(s),o[u]=s*r,o[u+1]=s*i));u=-1;while(++u<=a)s=(e[Math.min(a,u+1)][0]-e[Math.max(0,u-1)][0])/(6*(1+o[u]*o[u])),t.push([s||0,o[u]*s||0]);return t}function Cn(e){return e.length<3?an(e):e[0]+vn(e,Nn(e))}function kn(e){var t,n=-1,r=e.length,i,s;while(++n<r)t=e[n],i=t[0],s=t[1]+Ys,t[0]=i*Math.cos(s),t[1]=i*Math.sin(s);return e}function Ln(e){function t(t){function o(){l.push("M",f(e(v),p),h,c(e(d.reverse()),p),"Z")}var l=[],d=[],v=[],m=-1,g=t.length,y,b=u(n),w=u(i),E=n===r?function(){return x}:u(r),S=i===s?function(){return T}:u(s),x,T;while(++m<g)a.call(this,y=t[m],m)?(d.push([x=+b.call(this,y,m),T=+w.call(this,y,m)]),v.push([+E.call(this,y,m),+S.call(this,y,m)])):d.length&&(o(),d=[],v=[]);return d.length&&o(),l.length?l.join(""):null}var n=on,r=on,i=0,s=un,a=o,f=an,l=f.key,c=f,h="L",p=.7;return t.x=function(e){return arguments.length?(n=r=e,t):r},t.x0=function(e){return arguments.length?(n=e,t):n},t.x1=function(e){return arguments.length?(r=e,t):r},t.y=function(e){return arguments.length?(i=s=e,t):s},t.y0=function(e){return arguments.length?(i=e,t):i},t.y1=function(e){return arguments.length?(s=e,t):s},t.defined=function(e){return arguments.length?(a=e,t):a},t.interpolate=function(e){return arguments.length?(typeof e=="function"?l=f=e:l=(f=eo.get(e)||an).key,c=f.reverse||f,h=f.closed?"M":"L",t):l},t.tension=function(e){return arguments.length?(p=e,t):p},t}function An(e){return e.source}function On(e){return e.target}function Mn(e){return e.radius}function _n(e){return e.startAngle}function Dn(e){return e.endAngle}function Pn(e){return[e.x,e.y]}function Hn(e){return function(){var t=e.apply(this,arguments),n=t[0],r=t[1]+Ys;return[n*Math.cos(r),n*Math.sin(r)]}}function Bn(){return 64}function jn(){return"circle"}function Fn(e){var t=Math.sqrt(e/Math.PI);return"M0,"+t+"A"+t+","+t+" 0 1,1 0,"+ -t+"A"+t+","+t+" 0 1,1 0,"+t+"Z"}function In(e,t){e.attr("transform",function(e){return"translate("+t(e)+",0)"})}function qn(e,t){e.attr("transform",function(e){return"translate(0,"+t(e)+")"})}function Rn(e,t,n){i=[];if(n&&t.length>1){var r=_t(e.domain()),i,s=-1,o=t.length,u=(t[1]-t[0])/++n,a,f;while(++s<o)for(a=n;--a>0;)(f=+t[s]-a*u)>=r[0]&&i.push(f);for(--s,a=0;++a<n&&(f=+t[s]+a*u)<r[1];)i.push(f)}return i}function Un(){fo||(fo=d3.select("body").append("div").style("visibility","hidden").style("top",0).style("height",0).style("width",0).style("overflow-y","scroll").append("div").style("height","2000px").node().parentNode);var e=d3.event,t;try{fo.scrollTop=1e3,fo.dispatchEvent(e),t=1e3-fo.scrollTop}catch(n){t=e.wheelDelta||-e.detail*5}return t}function zn(e){var t=e.source,n=e.target,r=Xn(t,n),i=[t];while(t!==r)t=t.parent,i.push(t);var s=i.length;while(n!==r)i.splice(s,0,n),n=n.parent;return i}function Wn(e){var t=[],n=e.parent;while(n!=null)t.push(e),e=n,n=n.parent;return t.push(e),t}function Xn(e,t){if(e===t)return e;var n=Wn(e),r=Wn(t),i=n.pop(),s=r.pop(),o=null;while(i===s)o=i,i=n.pop(),s=r.pop();return o}function Vn(e){e.fixed|=2}function $n(e){e.fixed&=1}function Jn(e){e.fixed|=4}function Kn(e){e.fixed&=3}function Qn(e,t,n){var r=0,i=0;e.charge=0;if(!e.leaf){var s=e.nodes,o=s.length,u=-1,a;while(++u<o){a=s[u];if(a==null)continue;Qn(a,t,n),e.charge+=a.charge,r+=a.charge*a.cx,i+=a.charge*a.cy}}if(e.point){e.leaf||(e.point.x+=Math.random()-.5,e.point.y+=Math.random()-.5);var f=t*n[e.point.index];e.charge+=e.pointCharge=f,r+=f*e.point.x,i+=f*e.point.y}e.cx=r/e.charge,e.cy=i/e.charge}function Gn(e){return 20}function Yn(e){return 1}function Zn(e){return e.x}function er(e){return e.y}function tr(e,t,n){e.y0=t,e.y=n}function nr(e){return d3.range(e.length)}function rr(e){var t=-1,n=e[0].length,r=[];while(++t<n)r[t]=0;return r}function ir(e){var t=1,n=0,r=e[0][1],i,s=e.length;for(;t<s;++t)(i=e[t][1])>r&&(n=t,r=i);return n}function sr(e){return e.reduce(or,0)}function or(e,t){return e+t[1]}function ur(e,t){return ar(e,Math.ceil(Math.log(t.length)/Math.LN2+1))}function ar(e,t){var n=-1,r=+e[0],i=(e[1]-r)/t,s=[];while(++n<=t)s[n]=i*n+r;return s}function fr(e){return[d3.min(e),d3.max(e)]}function lr(e,t){return d3.rebind(e,t,"sort","children","value"),e.links=dr,e.nodes=function(t){return vo=!0,(e.nodes=e)(t)},e}function cr(e){return e.children}function hr(e){return e.value}function pr(e,t){return t.value-e.value}function dr(e){return d3.merge(e.map(function(e){return(e.children||[]).map(function(t){return{source:e,target:t}})}))}function vr(e,t){return e.value-t.value}function mr(e,t){var n=e._pack_next;e._pack_next=t,t._pack_prev=e,t._pack_next=n,n._pack_prev=t}function gr(e,t){e._pack_next=t,t._pack_prev=e}function yr(e,t){var n=t.x-e.x,r=t.y-e.y,i=e.r+t.r;return i*i-n*n-r*r>.001}function br(e){function t(e){r=Math.min(e.x-e.r,r),i=Math.max(e.x+e.r,i),s=Math.min(e.y-e.r,s),o=Math.max(e.y+e.r,o)}if(!(n=e.children)||!(p=n.length))return;var n,r=Infinity,i=-Infinity,s=Infinity,o=-Infinity,u,a,f,l,c,h,p;n.forEach(wr),u=n[0],u.x=-u.r,u.y=0,t(u);if(p>1){a=n[1],a.x=a.r,a.y=0,t(a);if(p>2){f=n[2],xr(u,a,f),t(f),mr(u,f),u._pack_prev=f,mr(f,a),a=u._pack_next;for(l=3;l<p;l++){xr(u,a,f=n[l]);var d=0,v=1,m=1;for(c=a._pack_next;c!==a;c=c._pack_next,v++)if(yr(c,f)){d=1;break}if(d==1)for(h=u._pack_prev;h!==c._pack_prev;h=h._pack_prev,m++)if(yr(h,f))break;d?(v<m||v==m&&a.r<u.r?gr(u,a=c):gr(u=h,a),l--):(mr(u,f),a=f,t(f))}}}var g=(r+i)/2,y=(s+o)/2,b=0;for(l=0;l<p;l++)f=n[l],f.x-=g,f.y-=y,b=Math.max(b,f.r+Math.sqrt(f.x*f.x+f.y*f.y));e.r=b,n.forEach(Er)}function wr(e){e._pack_next=e._pack_prev=e}function Er(e){delete e._pack_next,delete e._pack_prev}function Sr(e,t,n,r){var i=e.children;e.x=t+=r*e.x,e.y=n+=r*e.y,e.r*=r;if(i){var s=-1,o=i.length;while(++s<o)Sr(i[s],t,n,r)}}function xr(e,t,n){var r=e.r+n.r,i=t.x-e.x,s=t.y-e.y;if(r&&(i||s)){var o=t.r+n.r,u=i*i+s*s;o*=o,r*=r;var a=.5+(r-o)/(2*u),f=Math.sqrt(Math.max(0,2*o*(r+u)-(r-=u)*r-o*o))/(2*u);n.x=e.x+a*i+f*s,n.y=e.y+a*s-f*i}else n.x=e.x+r,n.y=e.y}function Tr(e){return 1+d3.max(e,function(e){return e.y})}function Nr(e){return e.reduce(function(e,t){return e+t.x},0)/e.length}function Cr(e){var t=e.children;return t&&t.length?Cr(t[0]):e}function kr(e){var t=e.children,n;return t&&(n=t.length)?kr(t[n-1]):e}function Lr(e,t){return e.parent==t.parent?1:2}function Ar(e){var t=e.children;return t&&t.length?t[0]:e._tree.thread}function Or(e){var t=e.children,n;return t&&(n=t.length)?t[n-1]:e._tree.thread}function Mr(e,t){var n=e.children;if(n&&(i=n.length)){var r,i,s=-1;while(++s<i)t(r=Mr(n[s],t),e)>0&&(e=r)}return e}function _r(e,t){return e.x-t.x}function Dr(e,t){return t.x-e.x}function Pr(e,t){return e.depth-t.depth}function Hr(e,t){function n(e,r){var i=e.children;if(i&&(a=i.length)){var s,o=null,u=-1,a;while(++u<a)s=i[u],n(s,o),o=s}t(e,r)}n(e,null)}function Br(e){var t=0,n=0,r=e.children,i=r.length,s;while(--i>=0)s=r[i]._tree,s.prelim+=t,s.mod+=t,t+=s.shift+(n+=s.change)}function jr(e,t,n){e=e._tree,t=t._tree;var r=n/(t.number-e.number);e.change+=r,t.change-=r,t.shift+=n,t.prelim+=n,t.mod+=n}function Fr(e,t,n){return e._tree.ancestor.parent==t.parent?e._tree.ancestor:n}function Ir(e){return{x:e.x,y:e.y,dx:e.dx,dy:e.dy}}function qr(e,t){var n=e.x+t[3],r=e.y+t[0],i=e.dx-t[1]-t[3],s=e.dy-t[0]-t[2];return i<0&&(n+=i/2,i=0),s<0&&(r+=s/2,s=0),{x:n,y:r,dx:i,dy:s}}function Rr(e,t){function n(e,r){d3.text(e,t,function(e){r(e&&n.parse(e))})}function r(t){return t.map(i).join(e)}function i(e){return o.test(e)?'"'+e.replace(/\"/g,'""')+'"':e}var s=new RegExp("\r\n|["+e+"\r\n]","g"),o=new RegExp('["'+e+"\n]"),u=e.charCodeAt(0);return n.parse=function(e){var t;return n.parseRows(e,function(e,n){if(n){var r={},i=-1,s=t.length;while(++i<s)r[t[i]]=e[i];return r}return t=e,null})},n.parseRows=function(e,t){function n(){if(s.lastIndex>=e.length)return i;if(l)return l=!1,r;var t=s.lastIndex;if(e.charCodeAt(t)===34){var n=t;while(n++<e.length)if(e.charCodeAt(n)===34){if(e.charCodeAt(n+1)!==34)break;n++}s.lastIndex=n+2;var o=e.charCodeAt(n+1);return o===13?(l=!0,e.charCodeAt(n+2)===10&&s.lastIndex++):o===10&&(l=!0),e.substring(t+1,n).replace(/""/g,'"')}var a=s.exec(e);return a?(l=a[0].charCodeAt(0)!==u,e.substring(t,a.index)):(s.lastIndex=e.length,e.substring(t))}var r={},i={},o=[],a=0,f,l;s.lastIndex=0;while((f=n())!==i){var c=[];while(f!==r&&f!==i)c.push(f),f=n();if(t&&!(c=t(c,a++)))continue;o.push(c)}return o},n.format=function(e){return e.map(r).join("\n")},n}function Ur(e,t){return function(n){return n&&e.hasOwnProperty(n.type)?e[n.type](n):t}}function zr(e){return"m0,"+e+"a"+e+","+e+" 0 1,1 0,"+ -2*e+"a"+e+","+e+" 0 1,1 0,"+2*e+"z"}function Wr(e,t){go.hasOwnProperty(e.type)&&go[e.type](e,t)}function Xr(e,t){Wr(e.geometry,t)}function Vr(e,t){for(var n=e.features,r=0,i=n.length;r<i;r++)Wr(n[r].geometry,t)}function $r(e,t){for(var n=e.geometries,r=0,i=n.length;r<i;r++)Wr(n[r],t)}function Jr(e,t){for(var n=e.coordinates,r=0,i=n.length;r<i;r++)t.apply(null,n[r])}function Kr(e,t){for(var n=e.coordinates,r=0,i=n.length;r<i;r++)for(var s=n[r],o=0,u=s.length;o<u;o++)t.apply(null,s[o])}function Qr(e,t){for(var n=e.coordinates,r=0,i=n.length;r<i;r++)for(var s=n[r][0],o=0,u=s.length;o<u;o++)t.apply(null,s[o])}function Gr(e,t){t.apply(null,e.coordinates)}function Yr(e,t){for(var n=e.coordinates[0],r=0,i=n.length;r<i;r++)t.apply(null,n[r])}function Zr(e){return e.source}function ei(e){return e.target}function ti(){function e(e){var t=Math.sin(e*=p)*d,n=Math.sin(p-e)*d,r=n*s+t*c,u=n*o+t*h,a=n*i+t*l;return[Math.atan2(u,r)/mo,Math.atan2(a,Math.sqrt(r*r+u*u))/mo]}var t,n,r,i,s,o,u,a,f,l,c,h,p,d;return e.distance=function(){return p==null&&(d=1/Math.sin(p=Math.acos(Math.max(-1,Math.min(1,i*l+r*f*Math.cos(u-t)))))),p},e.source=function(u){var a=Math.cos(t=u[0]*mo),f=Math.sin(t);return r=Math.cos(n=u[1]*mo),i=Math.sin(n),s=r*a,o=r*f,p=null,e},e.target=function(t){var n=Math.cos(u=t[0]*mo),r=Math.sin(u);return f=Math.cos(a=t[1]*mo),l=Math.sin(a),c=f*n,h=f*r,p=null,e},e}function ni(e,t){var n=ti().source(e).target(t);return n.distance(),n}function ri(e){var t=0,n=0;for(;;){if(e(t,n))return[t,n];t===0?(t=n+1,n=0):(t-=1,n+=1)}}function ii(e,t,n,r){var i,s,o,u,a,f,l;return i=r[e],s=i[0],o=i[1],i=r[t],u=i[0],a=i[1],i=r[n],f=i[0],l=i[1],(l-o)*(u-s)-(a-o)*(f-s)>0}function si(e,t,n){return(n[0]-t[0])*(e[1]-t[1])<(n[1]-t[1])*(e[0]-t[0])}function oi(e,t,n,r){var i=e[0],s=t[0],o=n[0],u=r[0],a=e[1],f=t[1],l=n[1],c=r[1],h=i-o,p=s-i,d=u-o,v=a-l,m=f-a,g=c-l,y=(d*v-g*h)/(g*p-d*m);return[i+y*p,a+y*m]}function ui(e,t){var n={list:e.map(function(e,t){return{index:t,x:e[0],y:e[1]}}).sort(function(e,t){return e.y<t.y?-1:e.y>t.y?1:e.x<t.x?-1:e.x>t.x?1:0}),bottomSite:null},r={list:[],leftEnd:null,rightEnd:null,init:function(){r.leftEnd=r.createHalfEdge(null,"l"),r.rightEnd=r.createHalfEdge(null,"l"),r.leftEnd.r=r.rightEnd,r.rightEnd.l=r.leftEnd,r.list.unshift(r.leftEnd,r.rightEnd)},createHalfEdge:function(e,t){return{edge:e,side:t,vertex:null,l:null,r:null}},insert:function(e,t){t.l=e,t.r=e.r,e.r.l=t,e.r=t},leftBound:function(e){var t=r.leftEnd;do t=t.r;while(t!=r.rightEnd&&i.rightOf(t,e));return t=t.l,t},del:function(e){e.l.r=e.r,e.r.l=e.l,e.edge=null},right:function(e){return e.r},left:function(e){return e.l},leftRegion:function(e){return e.edge==null?n.bottomSite:e.edge.region[e.side]},rightRegion:function(e){return e.edge==null?n.bottomSite:e.edge.region[wo[e.side]]}},i={bisect:function(e,t){var n={region:{l:e,r:t},ep:{l:null,r:null}},r=t.x-e.x,i=t.y-e.y,s=r>0?r:-r,o=i>0?i:-i;return n.c=e.x*r+e.y*i+(r*r+i*i)*.5,s>o?(n.a=1,n.b=i/r,n.c/=r):(n.b=1,n.a=r/i,n.c/=i),n},intersect:function(e,t){var n=e.edge,r=t.edge;if(!n||!r||n.region.r==r.region.r)return null;var i=n.a*r.b-n.b*r.a;if(Math.abs(i)<1e-10)return null;var s=(n.c*r.b-r.c*n.b)/i,o=(r.c*n.a-n.c*r.a)/i,u=n.region.r,a=r.region.r,f,l;u.y<a.y||u.y==a.y&&u.x<a.x?(f=e,l=n):(f=t,l=r);var c=s>=l.region.r.x;return c&&f.side==="l"||!c&&f.side==="r"?null:{x:s,y:o}},rightOf:function(e,t){var n=e.edge,r=n.region.r,i=t.x>r.x;if(i&&e.side==="l")return 1;if(!i&&e.side==="r")return 0;if(n.a===1){var s=t.y-r.y,o=t.x-r.x,u=0,a=0;!i&&n.b<0||i&&n.b>=0?a=u=s>=n.b*o:(a=t.x+t.y*n.b>n.c,n.b<0&&(a=!a),a||(u=1));if(!u){var f=r.x-n.region.l.x;a=n.b*(o*o-s*s)<f*s*(1+2*o/f+n.b*n.b),n.b<0&&(a=!a)}}else{var l=n.c-n.a*t.x,c=t.y-l,h=t.x-r.x,p=l-r.y;a=c*c>h*h+p*p}return e.side==="l"?a:!a},endPoint:function(e,n,r){e.ep[n]=r;if(!e.ep[wo[n]])return;t(e)},distance:function(e,t){var n=e.x-t.x,r=e.y-t.y;return Math.sqrt(n*n+r*r)}},s={list:[],insert:function(e,t,n){e.vertex=t,e.ystar=t.y+n;for(var r=0,i=s.list,o=i.length;r<o;r++){var u=i[r];if(e.ystar>u.ystar||e.ystar==u.ystar&&t.x>u.vertex.x)continue;break}i.splice(r,0,e)},del:function(e){for(var t=0,n=s.list,r=n.length;t<r&&n[t]!=e;++t);n.splice(t,1)},empty:function(){return s.list.length===0},nextEvent:function(e){for(var t=0,n=s.list,r=n.length;t<r;++t)if(n[t]==e)return n[t+1];return null},min:function(){var e=s.list[0];return{x:e.vertex.x,y:e.ystar}},extractMin:function(){return s.list.shift()}};r.init(),n.bottomSite=n.list.shift();var o=n.list.shift(),u,a,f,l,c,h,p,d,v,m,g,y,b;for(;;){s.empty()||(u=s.min());if(o&&(s.empty()||o.y<u.y||o.y==u.y&&o.x<u.x))a=r.leftBound(o),f=r.right(a),p=r.rightRegion(a),y=i.bisect(p,o),h=r.createHalfEdge(y,"l"),r.insert(a,h),m=i.intersect(a,h),m&&(s.del(a),s.insert(a,m,i.
    distance(m,o))),a=h,h=r.createHalfEdge(y,"r"),r.insert(a,h),m=i.intersect(h,f),m&&s.insert(h,m,i.distance(m,o)),o=n.list.shift();else{if(!!s.empty())break;a=s.extractMin(),l=r.left(a),f=r.right(a),c=r.right(f),p=r.leftRegion(a),d=r.rightRegion(f),g=a.vertex,i.endPoint(a.edge,a.side,g),i.endPoint(f.edge,f.side,g),r.del(a),s.del(f),r.del(f),b="l",p.y>d.y&&(v=p,p=d,d=v,b="r"),y=i.bisect(p,d),h=r.createHalfEdge(y,b),r.insert(l,h),i.endPoint(y,wo[b],g),m=i.intersect(l,h),m&&(s.del(l),s.insert(l,m,i.distance(m,p))),m=i.intersect(h,c),m&&s.insert(h,m,i.distance(m,p))}}for(a=r.right(r.leftEnd);a!=r.rightEnd;a=r.right(a))t(a.edge)}function ai(){return{leaf:!0,nodes:[],point:null}}function fi(e,t,n,r,i,s){if(!e(t,n,r,i,s)){var o=(n+i)*.5,u=(r+s)*.5,a=t.nodes;a[0]&&fi(e,a[0],n,r,o,u),a[1]&&fi(e,a[1],o,r,i,u),a[2]&&fi(e,a[2],n,u,o,s),a[3]&&fi(e,a[3],o,u,i,s)}}function li(e){return{x:e[0],y:e[1]}}function ci(){this._=new Date(arguments.length>1?Date.UTC.apply(this,arguments):arguments[0])}function hi(e){return e.substring(0,3)}function pi(e,t,n,r){var i,s,o=0,u=t.length,a=n.length;while(o<u){if(r>=a)return-1;i=t.charCodeAt(o++);if(i==37){s=Uo[t.charAt(o++)];if(!s||(r=s(e,n,r))<0)return-1}else if(i!=n.charCodeAt(r++))return-1}return r}function di(e){return new RegExp("^(?:"+e.map(d3.requote).join("|")+")","i")}function vi(e){var t=new r,n=-1,i=e.length;while(++n<i)t.set(e[n].toLowerCase(),n);return t}function mi(e,t,n){Bo.lastIndex=0;var r=Bo.exec(t.substring(n));return r?n+=r[0].length:-1}function gi(e,t,n){Ho.lastIndex=0;var r=Ho.exec(t.substring(n));return r?n+=r[0].length:-1}function yi(e,t,n){Io.lastIndex=0;var r=Io.exec(t.substring(n));return r?(e.m=qo.get(r[0].toLowerCase()),n+=r[0].length):-1}function bi(e,t,n){jo.lastIndex=0;var r=jo.exec(t.substring(n));return r?(e.m=Fo.get(r[0].toLowerCase()),n+=r[0].length):-1}function wi(e,t,n){return pi(e,Ro.c.toString(),t,n)}function Ei(e,t,n){return pi(e,Ro.x.toString(),t,n)}function Si(e,t,n){return pi(e,Ro.X.toString(),t,n)}function xi(e,t,n){zo.lastIndex=0;var r=zo.exec(t.substring(n,n+4));return r?(e.y=+r[0],n+=r[0].length):-1}function Ti(e,t,n){zo.lastIndex=0;var r=zo.exec(t.substring(n,n+2));return r?(e.y=Ni(+r[0]),n+=r[0].length):-1}function Ni(e){return e+(e>68?1900:2e3)}function Ci(e,t,n){zo.lastIndex=0;var r=zo.exec(t.substring(n,n+2));return r?(e.m=r[0]-1,n+=r[0].length):-1}function ki(e,t,n){zo.lastIndex=0;var r=zo.exec(t.substring(n,n+2));return r?(e.d=+r[0],n+=r[0].length):-1}function Li(e,t,n){zo.lastIndex=0;var r=zo.exec(t.substring(n,n+2));return r?(e.H=+r[0],n+=r[0].length):-1}function Ai(e,t,n){zo.lastIndex=0;var r=zo.exec(t.substring(n,n+2));return r?(e.M=+r[0],n+=r[0].length):-1}function Oi(e,t,n){zo.lastIndex=0;var r=zo.exec(t.substring(n,n+2));return r?(e.S=+r[0],n+=r[0].length):-1}function Mi(e,t,n){zo.lastIndex=0;var r=zo.exec(t.substring(n,n+3));return r?(e.L=+r[0],n+=r[0].length):-1}function _i(e,t,n){var r=Wo.get(t.substring(n,n+=2).toLowerCase());return r==null?-1:(e.p=r,n)}function Di(e){var t=e.getTimezoneOffset(),n=t>0?"-":"+",r=~~(Math.abs(t)/60),i=Math.abs(t)%60;return n+Mo(r)+Mo(i)}function Pi(e){return e.toISOString()}function Hi(e,t,n){function r(t){var n=e(t),r=s(n,1);return t-n<r-t?n:r}function i(n){return t(n=e(new Eo(n-1)),1),n}function s(e,n){return t(e=new Eo(+e),n),e}function o(e,r,s){var o=i(e),u=[];if(s>1)while(o<r)n(o)%s||u.push(new Date(+o)),t(o,1);else while(o<r)u.push(new Date(+o)),t(o,1);return u}function u(e,t,n){try{Eo=ci;var r=new ci;return r._=e,o(r,t,n)}finally{Eo=Date}}e.floor=e,e.round=r,e.ceil=i,e.offset=s,e.range=o;var a=e.utc=Bi(e);return a.floor=a,a.round=Bi(r),a.ceil=Bi(i),a.offset=Bi(s),a.range=u,e}function Bi(e){return function(t,n){try{Eo=ci;var r=new ci;return r._=t,e(r,n)._}finally{Eo=Date}}}function ji(e,t,n){function r(t){return e(t)}return r.invert=function(t){return Ii(e.invert(t))},r.domain=function(t){return arguments.length?(e.domain(t),r):e.domain().map(Ii)},r.nice=function(e){return r.domain(Pt(r.domain(),function(){return e}))},r.ticks=function(n,i){var s=Fi(r.domain());if(typeof n!="function"){var o=s[1]-s[0],u=o/n,a=d3.bisect(Vo,u);if(a==Vo.length)return t.year(s,n);if(!a)return e.ticks(n).map(Ii);Math.log(u/Vo[a-1])<Math.log(Vo[a]/u)&&--a,n=t[a],i=n[1],n=n[0].range}return n(s[0],new Date(+s[1]+1),i)},r.tickFormat=function(){return n},r.copy=function(){return ji(e.copy(),t,n)},d3.rebind(r,e,"range","rangeRound","interpolate","clamp")}function Fi(e){var t=e[0],n=e[e.length-1];return t<n?[t,n]:[n,t]}function Ii(e){return new Date(e)}function qi(e){return function(t){var n=e.length-1,r=e[n];while(!r[1](t))r=e[--n];return r[0](t)}}function Ri(e){var t=new Date(e,0,1);return t.setFullYear(e),t}function Ui(e){var t=e.getFullYear(),n=Ri(t),r=Ri(t+1);return t+(e-n)/(r-n)}function zi(e){var t=new Date(Date.UTC(e,0,1));return t.setUTCFullYear(e),t}function Wi(e){var t=e.getUTCFullYear(),n=zi(t),r=zi(t+1);return t+(e-n)/(r-n)}Date.now||(Date.now=function(){return+(new Date)});try{document.createElement("div").style.setProperty("opacity",0,"")}catch(Xi){var Vi=CSSStyleDeclaration.prototype,$i=Vi.setProperty;Vi.setProperty=function(e,t,n){$i.call(this,e,t+"",n)}}d3={version:"2.10.3"};var Ji=n;try{Ji(document.documentElement.childNodes)[0].nodeType}catch(Ki){Ji=t}var Qi=[].__proto__?function(e,t){e.__proto__=t}:function(e,t){for(var n in t)e[n]=t[n]};d3.map=function(e){var t=new r;for(var n in e)t.set(n,e[n]);return t},e(r,{has:function(e){return Gi+e in this},get:function(e){return this[Gi+e]},set:function(e,t){return this[Gi+e]=t},remove:function(e){return e=Gi+e,e in this&&delete this[e]},keys:function(){var e=[];return this.forEach(function(t){e.push(t)}),e},values:function(){var e=[];return this.forEach(function(t,n){e.push(n)}),e},entries:function(){var e=[];return this.forEach(function(t,n){e.push({key:t,value:n})}),e},forEach:function(e){for(var t in this)t.charCodeAt(0)===Yi&&e.call(this,t.substring(1),this[t])}});var Gi="\0",Yi=Gi.charCodeAt(0);d3.functor=u,d3.rebind=function(e,t){var n=1,r=arguments.length,i;while(++n<r)e[i=arguments[n]]=a(e,t,t[i]);return e},d3.ascending=function(e,t){return e<t?-1:e>t?1:e>=t?0:NaN},d3.descending=function(e,t){return t<e?-1:t>e?1:t>=e?0:NaN},d3.mean=function(e,t){var n=e.length,r,i=0,s=-1,o=0;if(arguments.length===1)while(++s<n)f(r=e[s])&&(i+=(r-i)/++o);else while(++s<n)f(r=t.call(e,e[s],s))&&(i+=(r-i)/++o);return o?i:undefined},d3.median=function(e,t){return arguments.length>1&&(e=e.map(t)),e=e.filter(f),e.length?d3.quantile(e.sort(d3.ascending),.5):undefined},d3.min=function(e,t){var n=-1,r=e.length,i,s;if(arguments.length===1){while(++n<r&&((i=e[n])==null||i!=i))i=undefined;while(++n<r)(s=e[n])!=null&&i>s&&(i=s)}else{while(++n<r&&((i=t.call(e,e[n],n))==null||i!=i))i=undefined;while(++n<r)(s=t.call(e,e[n],n))!=null&&i>s&&(i=s)}return i},d3.max=function(e,t){var n=-1,r=e.length,i,s;if(arguments.length===1){while(++n<r&&((i=e[n])==null||i!=i))i=undefined;while(++n<r)(s=e[n])!=null&&s>i&&(i=s)}else{while(++n<r&&((i=t.call(e,e[n],n))==null||i!=i))i=undefined;while(++n<r)(s=t.call(e,e[n],n))!=null&&s>i&&(i=s)}return i},d3.extent=function(e,t){var n=-1,r=e.length,i,s,o;if(arguments.length===1){while(++n<r&&((i=o=e[n])==null||i!=i))i=o=undefined;while(++n<r)(s=e[n])!=null&&(i>s&&(i=s),o<s&&(o=s))}else{while(++n<r&&((i=o=t.call(e,e[n],n))==null||i!=i))i=undefined;while(++n<r)(s=t.call(e,e[n],n))!=null&&(i>s&&(i=s),o<s&&(o=s))}return[i,o]},d3.random={normal:function(e,t){var n=arguments.length;return n<2&&(t=1),n<1&&(e=0),function(){var n,r,i;do n=Math.random()*2-1,r=Math.random()*2-1,i=n*n+r*r;while(!i||i>1);return e+t*n*Math.sqrt(-2*Math.log(i)/i)}},logNormal:function(e,t){var n=arguments.length;n<2&&(t=1),n<1&&(e=0);var r=d3.random.normal();return function(){return Math.exp(e+t*r())}},irwinHall:function(e){return function(){for(var t=0,n=0;n<e;n++)t+=Math.random();return t/e}}},d3.sum=function(e,t){var n=0,r=e.length,i,s=-1;if(arguments.length===1)while(++s<r)isNaN(i=+e[s])||(n+=i);else while(++s<r)isNaN(i=+t.call(e,e[s],s))||(n+=i);return n},d3.quantile=function(e,t){var n=(e.length-1)*t+1,r=Math.floor(n),i=e[r-1],s=n-r;return s?i+s*(e[r]-i):i},d3.transpose=function(e){return d3.zip.apply(d3,e)},d3.zip=function(){if(!(i=arguments.length))return[];for(var e=-1,t=d3.min(arguments,l),n=new Array(t);++e<t;)for(var r=-1,i,s=n[e]=new Array(i);++r<i;)s[r]=arguments[r][e];return n},d3.bisector=function(e){return{left:function(t,n,r,i){arguments.length<3&&(r=0),arguments.length<4&&(i=t.length);while(r<i){var s=r+i>>>1;e.call(t,t[s],s)<n?r=s+1:i=s}return r},right:function(t,n,r,i){arguments.length<3&&(r=0),arguments.length<4&&(i=t.length);while(r<i){var s=r+i>>>1;n<e.call(t,t[s],s)?i=s:r=s+1}return r}}};var Zi=d3.bisector(function(e){return e});d3.bisectLeft=Zi.left,d3.bisect=d3.bisectRight=Zi.right,d3.first=function(e,t){var n=0,r=e.length,i=e[0],s;arguments.length===1&&(t=d3.ascending);while(++n<r)t.call(e,i,s=e[n])>0&&(i=s);return i},d3.last=function(e,t){var n=0,r=e.length,i=e[0],s;arguments.length===1&&(t=d3.ascending);while(++n<r)t.call(e,i,s=e[n])<=0&&(i=s);return i},d3.nest=function(){function e(t,s){if(s>=i.length)return u?u.call(n,t):o?t.sort(o):t;var a=-1,f=t.length,l=i[s++],c,h,p=new r,d,v={};while(++a<f)(d=p.get(c=l(h=t[a])))?d.push(h):p.set(c,[h]);return p.forEach(function(t,n){v[t]=e(n,s)}),v}function t(e,n){if(n>=i.length)return e;var r=[],o=s[n++],u;for(u in e)r.push({key:u,values:t(e[u],n)});return o&&r.sort(function(e,t){return o(e.key,t.key)}),r}var n={},i=[],s=[],o,u;return n.map=function(t){return e(t,0)},n.entries=function(n){return t(e(n,0),0)},n.key=function(e){return i.push(e),n},n.sortKeys=function(e){return s[i.length-1]=e,n},n.sortValues=function(e){return o=e,n},n.rollup=function(e){return u=e,n},n},d3.keys=function(e){var t=[];for(var n in e)t.push(n);return t},d3.values=function(e){var t=[];for(var n in e)t.push(e[n]);return t},d3.entries=function(e){var t=[];for(var n in e)t.push({key:n,value:e[n]});return t},d3.permute=function(e,t){var n=[],r=-1,i=t.length;while(++r<i)n[r]=e[t[r]];return n},d3.merge=function(e){return Array.prototype.concat.apply([],e)},d3.split=function(e,t){var n=[],r=[],i,s=-1,o=e.length;arguments.length<2&&(t=c);while(++s<o)t.call(r,i=e[s],s)?r=[]:(r.length||n.push(r),r.push(i));return n},d3.range=function(e,t,n){arguments.length<3&&(n=1,arguments.length<2&&(t=e,e=0));if((t-e)/n===Infinity)throw new Error("infinite range");var r=[],i=p(Math.abs(n)),s=-1,o;e*=i,t*=i,n*=i;if(n<0)while((o=e+n*++s)>t)r.push(o/i);else while((o=e+n*++s)<t)r.push(o/i);return r},d3.requote=function(e){return e.replace(es,"\\$&")};var es=/[\\\^\$\*\+\?\|\[\]\(\)\.\{\}]/g;d3.round=function(e,t){return t?Math.round(e*(t=Math.pow(10,t)))/t:Math.round(e)},d3.xhr=function(e,t,n){var r=new XMLHttpRequest;arguments.length<3?(n=t,t=null):t&&r.overrideMimeType&&r.overrideMimeType(t),r.open("GET",e,!0),t&&r.setRequestHeader("Accept",t),r.onreadystatechange=function(){if(r.readyState===4){var e=r.status;n(!e&&r.response||e>=200&&e<300||e===304?r:null)}},r.send(null)},d3.text=function(e,t,n){function r(e){n(e&&e.responseText)}arguments.length<3&&(n=t,t=null),d3.xhr(e,t,r)},d3.json=function(e,t){d3.text(e,"application/json",function(e){t(e?JSON.parse(e):null)})},d3.html=function(e,t){d3.text(e,"text/html",function(e){if(e!=null){var n=document.createRange();n.selectNode(document.body),e=n.createContextualFragment(e)}t(e)})},d3.xml=function(e,t,n){function r(e){n(e&&e.responseXML)}arguments.length<3&&(n=t,t=null),d3.xhr(e,t,r)};var ts={svg:"http://www.w3.org/2000/svg",xhtml:"http://www.w3.org/1999/xhtml",xlink:"http://www.w3.org/1999/xlink",xml:"http://www.w3.org/XML/1998/namespace",xmlns:"http://www.w3.org/2000/xmlns/"};d3.ns={prefix:ts,qualify:function(e){var t=e.indexOf(":"),n=e;return t>=0&&(n=e.substring(0,t),e=e.substring(t+1)),ts.hasOwnProperty(n)?{space:ts[n],local:e}:e}},d3.dispatch=function(){var e=new d,t=-1,n=arguments.length;while(++t<n)e[arguments[t]]=v(e);return e},d.prototype.on=function(e,t){var n=e.indexOf("."),r="";return n>0&&(r=e.substring(n+1),e=e.substring(0,n)),arguments.length<2?this[e].on(r):this[e].on(r,t)},d3.format=function(e){var t=ns.exec(e),n=t[1]||" ",r=t[3]||"",i=t[5],s=+t[6],o=t[7],u=t[8],a=t[9],f=1,l="",c=!1;u&&(u=+u.substring(1)),i&&(n="0",o&&(s-=Math.floor((s-1)/4)));switch(a){case"n":o=!0,a="g";break;case"%":f=100,l="%",a="f";break;case"p":f=100,l="%",a="r";break;case"d":c=!0,u=0;break;case"s":f=-1,a="r"}return a=="r"&&!u&&(a="g"),a=rs.get(a)||g,function(e){if(c&&e%1)return"";var t=e<0&&(e=-e)?"-":r;if(f<0){var h=d3.formatPrefix(e,u);e=h.scale(e),l=h.symbol}else e*=f;e=a(e,u);if(i){var p=e.length+t.length;p<s&&(e=(new Array(s-p+1)).join(n)+e),o&&(e=y(e)),e=t+e}else{o&&(e=y(e)),e=t+e;var p=e.length;p<s&&(e=(new Array(s-p+1)).join(n)+e)}return e+l}};var ns=/(?:([^{])?([<>=^]))?([+\- ])?(#)?(0)?([0-9]+)?(,)?(\.[0-9]+)?([a-zA-Z%])?/,rs=d3.map({g:function(e,t){return e.toPrecision(t)},e:function(e,t){return e.toExponential(t)},f:function(e,t){return e.toFixed(t)},r:function(e,t){return d3.round(e,t=m(e,t)).toFixed(Math.max(0,Math.min(20,t)))}}),is=["y","z","a","f","p","n","Î¼","m","","k","M","G","T","P","E","Z","Y"].map(b);d3.formatPrefix=function(e,t){var n=0;return e&&(e<0&&(e*=-1),t&&(e=d3.round(e,m(e,t))),n=1+Math.floor(1e-12+Math.log(e)/Math.LN10),n=Math.max(-24,Math.min(24,Math.floor((n<=0?n+1:n-1)/3)*3))),is[8+n/3]};var ss=T(2),os=T(3),us=function(){return x},as=d3.map({linear:us,poly:T,quad:function(){return ss},cubic:function(){return os},sin:function(){return N},exp:function(){return C},circle:function(){return k},elastic:L,back:A,bounce:function(){return O}}),fs=d3.map({"in":x,out:E,"in-out":S,"out-in":function(e){return S(E(e))}});d3.ease=function(e){var t=e.indexOf("-"),n=t>=0?e.substring(0,t):e,r=t>=0?e.substring(t+1):"in";return n=as.get(n)||us,r=fs.get(r)||x,w(r(n.apply(null,Array.prototype.slice.call(arguments,1))))},d3.event=null,d3.transform=function(e){var t=document.createElementNS(d3.ns.prefix.svg,"g");return(d3.transform=function(e){t.setAttribute("transform",e);var n=t.transform.baseVal.consolidate();return new P(n?n.matrix:cs)})(e)},P.prototype.toString=function(){return"translate("+this.translate+")rotate("+this.rotate+")skewX("+this.skew+")scale("+this.scale+")"};var ls=180/Math.PI,cs={a:1,b:0,c:0,d:1,e:0,f:0};d3.interpolate=function(e,t){var n=d3.interpolators.length,r;while(--n>=0&&!(r=d3.interpolators[n](e,t)));return r},d3.interpolateNumber=function(e,t){return t-=e,function(n){return e+t*n}},d3.interpolateRound=function(e,t){return t-=e,function(n){return Math.round(e+t*n)}},d3.interpolateString=function(e,t){var n,r,i,s=0,o=0,u=[],a=[],f,l;hs.lastIndex=0;for(r=0;n=hs.exec(t);++r)n.index&&u.push(t.substring(s,o=n.index)),a.push({i:u.length,x:n[0]}),u.push(null),s=hs.lastIndex;s<t.length&&u.push(t.substring(s));for(r=0,f=a.length;(n=hs.exec(e))&&r<f;++r){l=a[r];if(l.x==n[0]){if(l.i)if(u[l.i+1]==null){u[l.i-1]+=l.x,u.splice(l.i,1);for(i=r+1;i<f;++i)a[i].i--}else{u[l.i-1]+=l.x+u[l.i+1],u.splice(l.i,2);for(i=r+1;i<f;++i)a[i].i-=2}else if(u[l.i+1]==null)u[l.i]=l.x;else{u[l.i]=l.x+u[l.i+1],u.splice(l.i+1,1);for(i=r+1;i<f;++i)a[i].i--}a.splice(r,1),f--,r--}else l.x=d3.interpolateNumber(parseFloat(n[0]),parseFloat(l.x))}while(r<f)l=a.pop(),u[l.i+1]==null?u[l.i]=l.x:(u[l.i]=l.x+u[l.i+1],u.splice(l.i+1,1)),f--;return u.length===1?u[0]==null?a[0].x:function(){return t}:function(e){for(r=0;r<f;++r)u[(l=a[r]).i]=l.x(e);return u.join("")}},d3.interpolateTransform=function(e,t){var n=[],r=[],i,s=d3.transform(e),o=d3.transform(t),u=s.translate,a=o.translate,f=s.rotate,l=o.rotate,c=s.skew,h=o.skew,p=s.scale,d=o.scale;return u[0]!=a[0]||u[1]!=a[1]?(n.push("translate(",null,",",null,")"),r.push({i:1,x:d3.interpolateNumber(u[0],a[0])},{i:3,x:d3.interpolateNumber(u[1],a[1])})):a[0]||a[1]?n.push("translate("+a+")"):n.push(""),f!=l?(f-l>180?l+=360:l-f>180&&(f+=360),r.push({i:n.push(n.pop()+"rotate(",null,")")-2,x:d3.interpolateNumber(f,l)})):l&&n.push(n.pop()+"rotate("+l+")"),c!=h?r.push({i:n.push(n.pop()+"skewX(",null,")")-2,x:d3.interpolateNumber(c,h)}):h&&n.push(n.pop()+"skewX("+h+")"),p[0]!=d[0]||p[1]!=d[1]?(i=n.push(n.pop()+"scale(",null,",",null,")"),r.push({i:i-4,x:d3.interpolateNumber(p[0],d[0])},{i:i-2,x:d3.interpolateNumber(p[1],d[1])})):(d[0]!=1||d[1]!=1)&&n.push(n.pop()+"scale("+d+")"),i=r.length,function(e){var t=-1,s;while(++t<i)n[(s=r[t]).i]=s.x(e);return n.join("")}},d3.interpolateRgb=function(e,t){e=d3.rgb(e),t=d3.rgb(t);var n=e.r,r=e.g,i=e.b,s=t.r-n,o=t.g-r,u=t.b-i;return function(e){return"#"+W(Math.round(n+s*e))+W(Math.round(r+o*e))+W(Math.round(i+u*e))}},d3.interpolateHsl=function(e,t){e=d3.hsl(e),t=d3.hsl(t);var n=e.h,r=e.s,i=e.l,s=t.h-n,o=t.s-r,u=t.l-i;return s>180?s-=360:s<-180&&(s+=360),function(e){return Y(n+s*e,r+o*e,i+u*e)+""}},d3.interpolateLab=function(e,t){e=d3.lab(e),t=d3.lab(t);var n=e.l,r=e.a,i=e.b,s=t.l-n,o=t.a-r,u=t.b-i;return function(e){return it(n+s*e,r+o*e,i+u*e)+""}},d3.interpolateHcl=function(e,t){e=d3.hcl(e),t=d3.hcl(t);var n=e.h,r=e.c,i=e.l,s=t.h-n,o=t.c-r,u=t.l-i;return s>180?s-=360:s<-180&&(s+=360),function(e){return tt(n+s*e,r+o*e,i+u*e)+""}},d3.interpolateArray=function(e,t){var n=[],r=[],i=e.length,s=t.length,o=Math.min(e.length,t.length),u;for(u=0;u<o;++u)n.push(d3.interpolate(e[u],t[u]));for(;u<i;++u)r[u]=e[u];for(;u<s;++u)r[u]=t[u];return function(e){for(u=0;u<o;++u)r[u]=n[u](e);return r}},d3.interpolateObject=function(e,t){var n={},r={},i;for(i in e)i in t?n[i]=F(i)(e[i],t[i]):r[i]=e[i];for(i in t)i in e||(r[i]=t[i]);return function(e){for(i in n)r[i]=n[i](e);return r}};var hs=/[-+]?(?:\d+\.?\d*|\.?\d+)(?:[eE][-+]?\d+)?/g;d3.interpolators=[d3.interpolateObject,function(e,t){return t instanceof Array&&d3.interpolateArray(e,t)},function(e,t){return(typeof e=="string"||typeof t=="string")&&d3.interpolateString(e+"",t+"")},function(e,t){return(typeof t=="string"?ds.has(t)||/^(#|rgb\(|hsl\()/.test(t):t instanceof R)&&d3.interpolateRgb(e,t)},function(e,t){return!isNaN(e=+e)&&!isNaN(t=+t)&&d3.interpolateNumber(e,t)}],R.prototype.toString=function(){return this.rgb()+""},d3.rgb=function(e,t,n){return arguments.length===1?e instanceof z?U(e.r,e.g,e.b):X(""+e,U,Y):U(~~e,~~t,~~n)};var ps=z.prototype=new R;ps.brighter=function(e){e=Math.pow(.7,arguments.length?e:1);var t=this.r,n=this.g,r=this.b,i=30;return!t&&!n&&!r?U(i,i,i):(t&&t<i&&(t=i),n&&n<i&&(n=i),r&&r<i&&(r=i),U(Math.min(255,Math.floor(t/e)),Math.min(255,Math.floor(n/e)),Math.min(255,Math.floor(r/e))))},ps.darker=function(e){return e=Math.pow(.7,arguments.length?e:1),U(Math.floor(e*this.r),Math.floor(e*this.g),Math.floor(e*this.b))},ps.hsl=function(){return V(this.r,this.g,this.b)},ps.toString=function(){return"#"+W(this.r)+W(this.g)+W(this.b)};var ds=d3.map({aliceblue:"#f0f8ff",antiquewhite:"#faebd7",aqua:"#00ffff",aquamarine:"#7fffd4",azure:"#f0ffff",beige:"#f5f5dc",bisque:"#ffe4c4",black:"#000000",blanchedalmond:"#ffebcd",blue:"#0000ff",blueviolet:"#8a2be2",brown:"#a52a2a",burlywood:"#deb887",cadetblue:"#5f9ea0",chartreuse:"#7fff00",chocolate:"#d2691e",coral:"#ff7f50",cornflowerblue:"#6495ed",cornsilk:"#fff8dc",crimson:"#dc143c",cyan:"#00ffff",darkblue:"#00008b",darkcyan:"#008b8b",darkgoldenrod:"#b8860b",darkgray:"#a9a9a9",darkgreen:"#006400",darkgrey:"#a9a9a9",darkkhaki:"#bdb76b",darkmagenta:"#8b008b",darkolivegreen:"#556b2f",darkorange:"#ff8c00",darkorchid:"#9932cc",darkred:"#8b0000",darksalmon:"#e9967a",darkseagreen:"#8fbc8f",darkslateblue:"#483d8b",darkslategray:"#2f4f4f",darkslategrey:"#2f4f4f",darkturquoise:"#00ced1",darkviolet:"#9400d3",deeppink:"#ff1493",deepskyblue:"#00bfff",dimgray:"#696969",dimgrey:"#696969",dodgerblue:"#1e90ff",firebrick:"#b22222",floralwhite:"#fffaf0",forestgreen:"#228b22",fuchsia:"#ff00ff",gainsboro:"#dcdcdc",ghostwhite:"#f8f8ff",gold:"#ffd700",goldenrod:"#daa520",gray:"#808080",green:"#008000",greenyellow:"#adff2f",grey:"#808080",honeydew:"#f0fff0",hotpink:"#ff69b4",indianred:"#cd5c5c",indigo:"#4b0082",ivory:"#fffff0",khaki:"#f0e68c",lavender:"#e6e6fa",lavenderblush:"#fff0f5",lawngreen:"#7cfc00",lemonchiffon:"#fffacd",lightblue:"#add8e6",lightcoral:"#f08080",lightcyan:"#e0ffff",lightgoldenrodyellow:"#fafad2",lightgray:"#d3d3d3",lightgreen:"#90ee90",lightgrey:"#d3d3d3",lightpink:"#ffb6c1",lightsalmon:"#ffa07a",lightseagreen:"#20b2aa",lightskyblue:"#87cefa",lightslategray:"#778899",lightslategrey:"#778899",lightsteelblue:"#b0c4de",lightyellow:"#ffffe0",lime:"#00ff00",limegreen:"#32cd32",linen:"#faf0e6",magenta:"#ff00ff",maroon:"#800000",mediumaquamarine:"#66cdaa",mediumblue:"#0000cd",mediumorchid:"#ba55d3",mediumpurple:"#9370db",mediumseagreen:"#3cb371",mediumslateblue:"#7b68ee",mediumspringgreen:"#00fa9a",mediumturquoise:"#48d1cc",mediumvioletred:"#c71585",midnightblue:"#191970",mintcream:"#f5fffa",mistyrose:"#ffe4e1",moccasin:"#ffe4b5",navajowhite:"#ffdead",navy:"#000080",oldlace:"#fdf5e6",olive:"#808000",olivedrab:"#6b8e23",orange:"#ffa500",orangered:"#ff4500",orchid:"#da70d6",palegoldenrod:"#eee8aa",palegreen:"#98fb98",paleturquoise:"#afeeee",palevioletred:"#db7093",papayawhip:"#ffefd5",peachpuff:"#ffdab9",peru:"#cd853f",pink:"#ffc0cb",plum:"#dda0dd",powderblue:"#b0e0e6",purple:"#800080",red:"#ff0000",rosybrown:"#bc8f8f",royalblue:"#4169e1",saddlebrown:"#8b4513",salmon:"#fa8072",sandybrown:"#f4a460",seagreen:"#2e8b57",seashell:"#fff5ee",sienna:"#a0522d",silver:"#c0c0c0",skyblue:"#87ceeb",slateblue:"#6a5acd",slategray:"#708090",slategrey:"#708090",snow:"#fffafa",springgreen:"#00ff7f",steelblue:"#4682b4",tan:"#d2b48c",teal:"#008080",thistle:"#d8bfd8",tomato:"#ff6347",turquoise:"#40e0d0",violet:"#ee82ee",wheat:"#f5deb3",white:"#ffffff",whitesmoke:"#f5f5f5",yellow:"#ffff00",yellowgreen:"#9acd32"});ds.forEach(function(e,t){ds.set(e,X(t,U,Y))}),d3.hsl=function(e,t,n){return arguments.length===1?e instanceof G?Q(e.h,e.s,e.l):X(""+e,V,Q):Q(+e,+t,+n)};var vs=G.prototype=new R;vs.brighter=function(e){return e=Math.pow(.7,arguments.length?e:1),Q(this.h,this.s,this.l/e)},vs.darker=function(e){return e=Math.pow(.7,arguments.length?e:1),Q(this.h,this.s,e*this.l)},vs.rgb=function(){return Y(this.h,this.s,this.l)},d3.hcl=function(e,t,n){return arguments.length===1?e instanceof et?Z(e.h,e.c,e.l):e instanceof rt?st(e.l,e.a,e.b):st((e=$((e=d3.rgb(e)).r,e.g,e.b)).l,e.a,e.b):Z(+e,+t,+n)};var ms=et.prototype=new R;ms.brighter=function(e){return Z(this.h,this.c,Math.min(100,this.l+gs*(arguments.length?e:1)))},ms.darker=function(e){return Z(this.h,this.c,Math.max(0,this.l-gs*(arguments.length?e:1)))},ms.rgb=function(){return tt(this.h,this.c,this.l).rgb()},d3.lab=function(e,t,n){return arguments.length===1?e instanceof rt?nt(e.l,e.a,e.b):e instanceof et?tt(e.l,e.c,e.h):$((e=d3.rgb(e)).r,e.g,e.b):nt(+e,+t,+n)};var gs=18,ys=.95047,bs=1,ws=1.08883,Es=rt.prototype=new R;Es.brighter=function(e){return nt(Math.min(100,this.l+gs*(arguments.length?e:1)),this.a,this.b)},Es.darker=function(e){return nt(Math.max(0,this.l-gs*(arguments.length?e:1)),this.a,this.b)},Es.rgb=function(){return it(this.l,this.a,this.b)};var Ss=function(e,t){return t.querySelector(e)},xs=function(e,t){return t.querySelectorAll(e)},Ts=document.documentElement,Ns=Ts.matchesSelector||Ts.webkitMatchesSelector||Ts.mozMatchesSelector||Ts.msMatchesSelector||Ts.oMatchesSelector,Cs=function(e,t){return Ns.call(e,t)};typeof Sizzle=="function"&&(Ss=function(e,t){return Sizzle(e,t)[0]||null},xs=function(e,t){return Sizzle.uniqueSort(Sizzle(e,t))},Cs=Sizzle.matchesSelector);var ks=[];d3.selection=function(){return Ls},d3.selection.prototype=ks,ks.select=function(e){var t=[],n,r,i,s;typeof e!="function"&&(e=lt(e));for(var o=-1,u=this.length;++o<u;){t.push(n=[]),n.parentNode=(i=this[o]).parentNode;for(var a=-1,f=i.length;++a<f;)(s=i[a])?(n.push(r=e.call(s,s.__data__,a)),r&&"__data__"in s&&(r.__data__=s.__data__)):n.push(null)}return ft(t)},ks.selectAll=function(e){var t=[],n,r;typeof e!="function"&&(e=ct(e));for(var i=-1,s=this.length;++i<s;)for(var o=this[i],u=-1,a=o.length;++u<a;)if(r=o[u])t.push(n=Ji(e.call(r,r.__data__,u))),n.parentNode=r;return ft(t)},ks.attr=function(e,t){if(arguments.length<2){if(typeof e=="string"){var n=this.node();return e=d3.ns.qualify(e),e.local?n.getAttributeNS(e.space,e.local):n.getAttribute(e)}for(t in e)this.each(ht(t,e[t]));return this}return this.each(ht(e,t))},ks.classed=function(e,t){if(arguments.length<2){if(typeof e=="string"){var n=this.node(),r=(e=e.trim().split(/^|\s+/g)).length,i=-1;if(t=n.classList){while(++i<r)if(!t.contains(e[i]))return!1}else{t=n.className,t.baseVal!=null&&(t=t.baseVal);while(++i<r)if(!pt(e[i]).test(t))return!1}return!0}for(t in e)this.each(dt(t,e[t]));return this}return this.each(dt(e,t))},ks.style=function(e,t,n){var r=arguments.length;if(r<3){if(typeof e!="string"){r<2&&(t="");for(n in e)this.each(mt(n,e[n],t));return this}if(r<2)return window.getComputedStyle(this.node(),null).getPropertyValue(e);n=""}return this.each(mt(e,t,n))},ks.property=function(e,t){if(arguments.length<2){if(typeof e=="string")return this.node()[e];for(t in e)this.each(gt(t,e[t]));return this}return this.each(gt(e,t))},ks.text=function(e){return arguments.length<1?this.node().textContent:this.each(typeof e=="function"?function(){var t=e.apply(this,arguments);this.textContent=t==null?"":t}:e==null?function(){this.textContent=""}:function(){this.textContent=e})},ks.html=function(e){return arguments.length<1?this.node().innerHTML:this.each(typeof e=="function"?function(){var t=e.apply(this,arguments);this.innerHTML=t==null?"":t}:e==null?function(){this.innerHTML=""}:function(){this.innerHTML=e})},ks.append=function(e){function t(){return this.appendChild(document.createElementNS(this.namespaceURI,e))}function n(){return this.appendChild(document.createElementNS(e.space,e.local))}return e=d3.ns.qualify(e),this.select(e.local?n:t)},ks.insert=function(e,t){function n(){return this.insertBefore(document.createElementNS(this.namespaceURI,e),Ss(t,this))}function r(){return this.insertBefore(document.createElementNS(e.space,e.local),Ss(t,this))}return e=d3.ns.qualify(e),this.select(e.local?r:n)},ks.remove=function(){return this.each(function(){var e=this.parentNode;e&&e.removeChild(this)})},ks.data=function(e,t){function n(e,n){var i,s=e.length,o=n.length,u=Math.min(s,o),c=Math.max(s,o),h=[],p=[],d=[],v,m;if(t){var g=new r,y=[],b,w=n.length;for(i=-1;++i<s;)b=t.call(v=e[i],v.__data__,i),g.has(b)?d[w++]=v:g.set(b,v),y.push(b);for(i=-1;++i<o;)b=t.call(n,m=n[i],i),g.has(b)?(h[i]=v=g.get(b),v.__data__=m,p[i]=d[i]=null):(p[i]=yt(m),h[i]=d[i]=null),g.remove(b);for(i=-1;++i<s;)g.has(y[i])&&(d[i]=e[i])}else{for(i=-1;++i<u;)v=e[i],m=n[i],v?(v.__data__=m,h[i]=v,p[i]=d[i]=null):(p[i]=yt(m),h[i]=d[i]=null);for(;i<o;++i)p[i]=yt(n[i]),h[i]=d[i]=null;for(;i<c;++i)d[i]=e[i],p[i]=h[i]=null}p.update=h,p.parentNode=h.parentNode=d.parentNode=e.parentNode,a.push(p),f.push(h),l.push(d)}var i=-1,s=this.length,o,u;if(!arguments.length){e=new Array(s=(o=this[0]).length);while(++i<s)if(u=o[i])e[i]=u.__data__;return e}var a=xt([]),f=ft([]),l=ft([]);if(typeof e=="function")while(++i<s)n(o=this[i],e.call(o,o.parentNode.__data__,i));else while(++i<s)n(o=this[i],e);return f.enter=function(){return a},f.exit=function(){return l},f},ks.datum=ks.map=function(e){return arguments.length<1?this.property("__data__"):this.property("__data__",e)},ks.filter=function(e){var t=[],n,r,i;typeof e!="function"&&(e=bt(e));for(var s=0,o=this.length;s<o;s++){t.push(n=[]),n.parentNode=(r=this[s]).parentNode;for(var u=0,a=r.length;u<a;u++)(i=r[u])&&e.call(i,i.__data__,u)&&n.push(i)}return ft(t)},ks.order=function(){for(var e=-1,t=this.length;++e<t;)for(var n=this[e],r=n.length-1,i=n[r],s;--r>=0;)if(s=n[r])i&&i!==s.nextSibling&&i.parentNode.insertBefore(s,i),i=s;return this},ks.sort=function(e){e=wt.apply(this,arguments);for(var t=-1,n=this.length;++t<n;)this[t].sort(e);return this.order()},ks.on=function(e,t,n){var r=arguments.length;if(r<3){if(typeof e!="string"){r<2&&(t=!1);for(n in e)this.each(Et(n,e[n],t));return this}if(r<2)return(r=this.node()["__on"+e])&&r._;n=!1}return this.each(Et(e,t,n))},ks.each=function(e){return St(this,function(t,n,r){e.call(t,t.__data__,n,r)})},ks.call=function(e){return e.apply(this,(arguments[0]=this,arguments)),this},ks.empty=function(){return!this.node()},ks.node=function(e){for(var t=0,n=this.length;t<n;t++)for(var r=this[t],i=0,s=r.length;i<s;i++){var o=r[i];if(o)return o}return null},ks.transition=function(){var e=[],t,n;for(var r=-1,i=this.length;++r<i;){e.push(t=[]);for(var s=this[r],o=-1,u=s.length;++o<u;)t.push((n=s[o])?{node:n,delay:Bs,duration:js}:null)}return Tt(e,_s||++Ms,Date.now())};var Ls=ft([[document]]);Ls[0].parentNode=Ts,d3.select=function(e){return typeof e=="string"?Ls.select(e):ft([[e]])},d3.selectAll=function(e){return typeof e=="string"?Ls.selectAll(e):ft([Ji(e)])};var As=[];d3.selection.enter=xt,d3.selection.enter.prototype=As,As.append=ks.append,As.insert=ks.insert,As.empty=ks.empty,As.node=ks.node,As.select=function(e){var t=[],n,r,i,s,o;for(var u=-1,a=this.length;++u<a;){i=(s=this[u]).update,t.push(n=[]),n.parentNode=s.parentNode;for(var f=-1,l=s.length;++f<l;)(o=s[f])?(n.push(i[f]=r=e.call(s.parentNode,o.__data__,f)),r.__data__=o.__data__):n.push(null)}return ft(t)};var Os=[],Ms=0,_s=0,Ds=0,Ps=250,Hs=d3.ease("cubic-in-out"),Bs=Ds,js=Ps,Fs=Hs;Os.call=ks.call,d3.transition=function(e){return arguments.length?_s?e.transition():e:Ls.transition()},d3.transition.prototype=Os,Os.select=function(e){var t=[],n,r,i;typeof e!="function"&&(e=lt(e));for(var s=-1,o=this.length;++s<o;){t.push(n=[]);for(var u=this[s],a=-1,f=u.length;++a<f;)(i=u[a])&&(r=e.call(i.node,i.node.__data__,a))?("__data__"in i.node&&(r.__data__=i.node.__data__),n.push({node:r,delay:i.delay,duration:i.duration})):n.push(null)}return Tt(t,this.id,this.time).ease(this.ease())},Os.selectAll=function(e){var t=[],n,r,i;typeof e!="function"&&(e=ct(e));for(var s=-1,o=this.length;++s<o;)for(var u=this[s],a=-1,f=u.length;++a<f;)if(i=u[a]){r=e.call(i.node,i.node.__data__,a),t.push(n=[]);for(var l=-1,c=r.length;++l<c;)n.push({node:r[l],delay:i.delay,duration:i.duration})}return Tt(t,this.id,this.time).ease(this.ease())},Os.filter=function(e){var t=[],n,r,i;typeof e!="function"&&(e=bt(e));for(var s=0,o=this.length;s<o;s++){t.push(n=[]);for(var r=this[s],u=0,a=r.length;u<a;u++)(i=r[u])&&e.call(i.node,i.node.__data__,u)&&n.push(i)}return Tt(t,this.id,this.time).ease(this.ease())},Os.attr=function(e,t){if(arguments.length<2){for(t in e)this.attrTween(t,kt(e[t],t));return this}return this.attrTween(e,kt(t,e))},Os.attrTween=function(e,t){function n(e,n){var r=t.call(this,e,n,this.getAttribute(i));return r===Is?(this.removeAttribute(i),null):r&&function(e){this.setAttribute(i,r(e))}}function r(e,n){var r=t.call(this,e,n,this.getAttributeNS(i.space,i.local));return r===Is?(this.removeAttributeNS(i.space,i.local),null):r&&function(e){this.setAttributeNS(i.space,i.local,r(e))}}var i=d3.ns.qualify(e);return this.tween("attr."+e,i.local?r:n)},Os.style=function(e,t,n){var r=arguments.length;if(r<3){if(typeof e!="string"){r<2&&(t="");for(n in e)this.styleTween(n,kt(e[n],n),t);return this}n=""}return this.styleTween(e,kt(t,e),n)},Os.styleTween=function(e,t,n){return arguments.length<3&&(n=""),this.tween("style."+e,function(r,i){var s=t.call(this,r,i,window.getComputedStyle(this,null).getPropertyValue(e));return s===Is?(this.style.removeProperty(e),null):s&&function(t){this.style.setProperty(e,s(t),n)}})},Os.text=function(e){return this.tween("text",function(t,n){this.textContent=typeof e=="function"?e.call(this,t,n):e})},Os.remove=function(){return this.each("end.transition",function(){var e;!this.__transition__&&(e=this.parentNode)&&e.removeChild(this)})},Os.delay=function(e){return St(this,typeof e=="function"?function(t,n,r){t.delay=e.call(t=t.node,t.__data__,n,r)|0}:(e|=0,function(t){t.delay=e}))},Os.duration=function(e){return St(this,typeof e=="function"?function(t,n,r){t.duration=Math.max(1,e.call(t=t.node,t.__data__,n,r)|0)}:(e=Math.max(1,e|0),function(t){t.duration=e}))},Os.transition=function(){return this.select(s)},d3.tween=function(e,t){function n(n,r,i){var s=e.call(this,n,r);return s==null?i!=""&&Is:i!=s&&t(i,s+"")}function r(n,r,i){return i!=e&&t(i,e)}return typeof e=="function"?n:e==null?Ct:(e+="",r)};var Is={},qs=0,Rs={},Us=null,zs,Ws;d3.timer=function(e,t,n){if(arguments.length<3){if(arguments.length<2)t=0;else if(!isFinite(t))return;n=Date.now()}var r=Rs[e.id];r&&r.callback===e?(r.then=n,r.delay=t):Rs[e.id=++qs]=Us={callback:e,then:n,delay:t,next:Us},zs||(Ws=clearTimeout(Ws),zs=1,Xs(Lt))},d3.timer.flush=function(){var e,t=Date.now(),n=Us;while(n)e=t-n.then,n.delay||(n.flush=n.callback(e)),n=n.next;At()};var Xs=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.oRequestAnimationFrame||window.msRequestAnimationFrame||function(e){setTimeout
(e,17)};d3.mouse=function(e){return Ot(e,_())};var Vs=/WebKit/.test(navigator.userAgent)?-1:0;d3.touches=function(e,t){return arguments.length<2&&(t=_().touches),t?Ji(t).map(function(t){var n=Ot(e,t);return n.identifier=t.identifier,n}):[]},d3.scale={},d3.scale.linear=function(){return Bt([0,1],[0,1],d3.interpolate,!1)},d3.scale.log=function(){return Wt(d3.scale.linear(),Xt)};var $s=d3.format(".0e");Xt.pow=function(e){return Math.pow(10,e)},Vt.pow=function(e){return-Math.pow(10,-e)},d3.scale.pow=function(){return $t(d3.scale.linear(),1)},d3.scale.sqrt=function(){return d3.scale.pow().exponent(.5)},d3.scale.ordinal=function(){return Kt([],{t:"range",a:[[]]})},d3.scale.category10=function(){return d3.scale.ordinal().range(Js)},d3.scale.category20=function(){return d3.scale.ordinal().range(Ks)},d3.scale.category20b=function(){return d3.scale.ordinal().range(Qs)},d3.scale.category20c=function(){return d3.scale.ordinal().range(Gs)};var Js=["#1f77b4","#ff7f0e","#2ca02c","#d62728","#9467bd","#8c564b","#e377c2","#7f7f7f","#bcbd22","#17becf"],Ks=["#1f77b4","#aec7e8","#ff7f0e","#ffbb78","#2ca02c","#98df8a","#d62728","#ff9896","#9467bd","#c5b0d5","#8c564b","#c49c94","#e377c2","#f7b6d2","#7f7f7f","#c7c7c7","#bcbd22","#dbdb8d","#17becf","#9edae5"],Qs=["#393b79","#5254a3","#6b6ecf","#9c9ede","#637939","#8ca252","#b5cf6b","#cedb9c","#8c6d31","#bd9e39","#e7ba52","#e7cb94","#843c39","#ad494a","#d6616b","#e7969c","#7b4173","#a55194","#ce6dbd","#de9ed6"],Gs=["#3182bd","#6baed6","#9ecae1","#c6dbef","#e6550d","#fd8d3c","#fdae6b","#fdd0a2","#31a354","#74c476","#a1d99b","#c7e9c0","#756bb1","#9e9ac8","#bcbddc","#dadaeb","#636363","#969696","#bdbdbd","#d9d9d9"];d3.scale.quantile=function(){return Qt([],[])},d3.scale.quantize=function(){return Gt(0,1,[0,1])},d3.scale.threshold=function(){return Yt([.5],[0,1])},d3.scale.identity=function(){return Zt([0,1])},d3.svg={},d3.svg.arc=function(){function e(){var e=t.apply(this,arguments),s=n.apply(this,arguments),o=r.apply(this,arguments)+Ys,u=i.apply(this,arguments)+Ys,a=(u<o&&(a=o,o=u,u=a),u-o),f=a<Math.PI?"0":"1",l=Math.cos(o),c=Math.sin(o),h=Math.cos(u),p=Math.sin(u);return a>=Zs?e?"M0,"+s+"A"+s+","+s+" 0 1,1 0,"+ -s+"A"+s+","+s+" 0 1,1 0,"+s+"M0,"+e+"A"+e+","+e+" 0 1,0 0,"+ -e+"A"+e+","+e+" 0 1,0 0,"+e+"Z":"M0,"+s+"A"+s+","+s+" 0 1,1 0,"+ -s+"A"+s+","+s+" 0 1,1 0,"+s+"Z":e?"M"+s*l+","+s*c+"A"+s+","+s+" 0 "+f+",1 "+s*h+","+s*p+"L"+e*h+","+e*p+"A"+e+","+e+" 0 "+f+",0 "+e*l+","+e*c+"Z":"M"+s*l+","+s*c+"A"+s+","+s+" 0 "+f+",1 "+s*h+","+s*p+"L0,0"+"Z"}var t=en,n=tn,r=nn,i=rn;return e.innerRadius=function(n){return arguments.length?(t=u(n),e):t},e.outerRadius=function(t){return arguments.length?(n=u(t),e):n},e.startAngle=function(t){return arguments.length?(r=u(t),e):r},e.endAngle=function(t){return arguments.length?(i=u(t),e):i},e.centroid=function(){var e=(t.apply(this,arguments)+n.apply(this,arguments))/2,s=(r.apply(this,arguments)+i.apply(this,arguments))/2+Ys;return[Math.cos(s)*e,Math.sin(s)*e]},e};var Ys=-Math.PI/2,Zs=2*Math.PI-1e-6;d3.svg.line=function(){return sn(i)};var eo=d3.map({linear:an,"linear-closed":fn,"step-before":ln,"step-after":cn,basis:gn,"basis-open":yn,"basis-closed":bn,bundle:wn,cardinal:dn,"cardinal-open":hn,"cardinal-closed":pn,monotone:Cn});eo.forEach(function(e,t){t.key=e,t.closed=/-closed$/.test(e)});var to=[0,2/3,1/3,0],no=[0,1/3,2/3,0],ro=[0,1/6,2/3,1/6];d3.svg.line.radial=function(){var e=sn(kn);return e.radius=e.x,delete e.x,e.angle=e.y,delete e.y,e},ln.reverse=cn,cn.reverse=ln,d3.svg.area=function(){return Ln(i)},d3.svg.area.radial=function(){var e=Ln(kn);return e.radius=e.x,delete e.x,e.innerRadius=e.x0,delete e.x0,e.outerRadius=e.x1,delete e.x1,e.angle=e.y,delete e.y,e.startAngle=e.y0,delete e.y0,e.endAngle=e.y1,delete e.y1,e},d3.svg.chord=function(){function e(e,u){var a=t(this,s,e,u),f=t(this,o,e,u);return"M"+a.p0+r(a.r,a.p1,a.a1-a.a0)+(n(a,f)?i(a.r,a.p1,a.r,a.p0):i(a.r,a.p1,f.r,f.p0)+r(f.r,f.p1,f.a1-f.a0)+i(f.r,f.p1,a.r,a.p0))+"Z"}function t(e,t,n,r){var i=t.call(e,n,r),s=a.call(e,i,r),o=f.call(e,i,r)+Ys,u=l.call(e,i,r)+Ys;return{r:s,a0:o,a1:u,p0:[s*Math.cos(o),s*Math.sin(o)],p1:[s*Math.cos(u),s*Math.sin(u)]}}function n(e,t){return e.a0==t.a0&&e.a1==t.a1}function r(e,t,n){return"A"+e+","+e+" 0 "+ +(n>Math.PI)+",1 "+t}function i(e,t,n,r){return"Q 0,0 "+r}var s=An,o=On,a=Mn,f=nn,l=rn;return e.radius=function(t){return arguments.length?(a=u(t),e):a},e.source=function(t){return arguments.length?(s=u(t),e):s},e.target=function(t){return arguments.length?(o=u(t),e):o},e.startAngle=function(t){return arguments.length?(f=u(t),e):f},e.endAngle=function(t){return arguments.length?(l=u(t),e):l},e},d3.svg.diagonal=function(){function e(e,i){var s=t.call(this,e,i),o=n.call(this,e,i),u=(s.y+o.y)/2,a=[s,{x:s.x,y:u},{x:o.x,y:u},o];return a=a.map(r),"M"+a[0]+"C"+a[1]+" "+a[2]+" "+a[3]}var t=An,n=On,r=Pn;return e.source=function(n){return arguments.length?(t=u(n),e):t},e.target=function(t){return arguments.length?(n=u(t),e):n},e.projection=function(t){return arguments.length?(r=t,e):r},e},d3.svg.diagonal.radial=function(){var e=d3.svg.diagonal(),t=Pn,n=e.projection;return e.projection=function(e){return arguments.length?n(Hn(t=e)):t},e},d3.svg.mouse=d3.mouse,d3.svg.touches=d3.touches,d3.svg.symbol=function(){function e(e,r){return(io.get(t.call(this,e,r))||Fn)(n.call(this,e,r))}var t=jn,n=Bn;return e.type=function(n){return arguments.length?(t=u(n),e):t},e.size=function(t){return arguments.length?(n=u(t),e):n},e};var io=d3.map({circle:Fn,cross:function(e){var t=Math.sqrt(e/5)/2;return"M"+ -3*t+","+ -t+"H"+ -t+"V"+ -3*t+"H"+t+"V"+ -t+"H"+3*t+"V"+t+"H"+t+"V"+3*t+"H"+ -t+"V"+t+"H"+ -3*t+"Z"},diamond:function(e){var t=Math.sqrt(e/(2*oo)),n=t*oo;return"M0,"+ -t+"L"+n+",0"+" 0,"+t+" "+ -n+",0"+"Z"},square:function(e){var t=Math.sqrt(e)/2;return"M"+ -t+","+ -t+"L"+t+","+ -t+" "+t+","+t+" "+ -t+","+t+"Z"},"triangle-down":function(e){var t=Math.sqrt(e/so),n=t*so/2;return"M0,"+n+"L"+t+","+ -n+" "+ -t+","+ -n+"Z"},"triangle-up":function(e){var t=Math.sqrt(e/so),n=t*so/2;return"M0,"+ -n+"L"+t+","+n+" "+ -t+","+n+"Z"}});d3.svg.symbolTypes=io.keys();var so=Math.sqrt(3),oo=Math.tan(30*Math.PI/180);d3.svg.axis=function(){function e(e){e.each(function(){var e=d3.select(this),c=a==null?t.ticks?t.ticks.apply(t,u):t.domain():a,h=f==null?t.tickFormat?t.tickFormat.apply(t,u):String:f,p=Rn(t,c,l),d=e.selectAll(".minor").data(p,String),v=d.enter().insert("line","g").attr("class","tick minor").style("opacity",1e-6),m=d3.transition(d.exit()).style("opacity",1e-6).remove(),g=d3.transition(d).style("opacity",1),y=e.selectAll("g").data(c,String),b=y.enter().insert("g","path").style("opacity",1e-6),w=d3.transition(y.exit()).style("opacity",1e-6).remove(),E=d3.transition(y).style("opacity",1),S,x=Dt(t),T=e.selectAll(".domain").data([0]),N=T.enter().append("path").attr("class","domain"),C=d3.transition(T),k=t.copy(),L=this.__chart__||k;this.__chart__=k,b.append("line").attr("class","tick"),b.append("text");var A=b.select("line"),O=E.select("line"),M=y.select("text").text(h),_=b.select("text"),D=E.select("text");switch(n){case"bottom":S=In,v.attr("y2",i),g.attr("x2",0).attr("y2",i),A.attr("y2",r),_.attr("y",Math.max(r,0)+o),O.attr("x2",0).attr("y2",r),D.attr("x",0).attr("y",Math.max(r,0)+o),M.attr("dy",".71em").attr("text-anchor","middle"),C.attr("d","M"+x[0]+","+s+"V0H"+x[1]+"V"+s);break;case"top":S=In,v.attr("y2",-i),g.attr("x2",0).attr("y2",-i),A.attr("y2",-r),_.attr("y",-(Math.max(r,0)+o)),O.attr("x2",0).attr("y2",-r),D.attr("x",0).attr("y",-(Math.max(r,0)+o)),M.attr("dy","0em").attr("text-anchor","middle"),C.attr("d","M"+x[0]+","+ -s+"V0H"+x[1]+"V"+ -s);break;case"left":S=qn,v.attr("x2",-i),g.attr("x2",-i).attr("y2",0),A.attr("x2",-r),_.attr("x",-(Math.max(r,0)+o)),O.attr("x2",-r).attr("y2",0),D.attr("x",-(Math.max(r,0)+o)).attr("y",0),M.attr("dy",".32em").attr("text-anchor","end"),C.attr("d","M"+ -s+","+x[0]+"H0V"+x[1]+"H"+ -s);break;case"right":S=qn,v.attr("x2",i),g.attr("x2",i).attr("y2",0),A.attr("x2",r),_.attr("x",Math.max(r,0)+o),O.attr("x2",r).attr("y2",0),D.attr("x",Math.max(r,0)+o).attr("y",0),M.attr("dy",".32em").attr("text-anchor","start"),C.attr("d","M"+s+","+x[0]+"H0V"+x[1]+"H"+s)}if(t.ticks)b.call(S,L),E.call(S,k),w.call(S,k),v.call(S,L),g.call(S,k),m.call(S,k);else{var P=k.rangeBand()/2,H=function(e){return k(e)+P};b.call(S,H),E.call(S,H)}})}var t=d3.scale.linear(),n="bottom",r=6,i=6,s=6,o=3,u=[10],a=null,f,l=0;return e.scale=function(n){return arguments.length?(t=n,e):t},e.orient=function(t){return arguments.length?(n=t,e):n},e.ticks=function(){return arguments.length?(u=arguments,e):u},e.tickValues=function(t){return arguments.length?(a=t,e):a},e.tickFormat=function(t){return arguments.length?(f=t,e):f},e.tickSize=function(t,n,o){if(!arguments.length)return r;var u=arguments.length-1;return r=+t,i=u>1?+n:r,s=u>0?+arguments[u]:r,e},e.tickPadding=function(t){return arguments.length?(o=+t,e):o},e.tickSubdivide=function(t){return arguments.length?(l=+t,e):l},e},d3.svg.brush=function(){function e(s){s.each(function(){var s=d3.select(this),f=s.selectAll(".background").data([0]),l=s.selectAll(".extent").data([0]),c=s.selectAll(".resize").data(a,String),h;s.style("pointer-events","all").on("mousedown.brush",i).on("touchstart.brush",i),f.enter().append("rect").attr("class","background").style("visibility","hidden").style("cursor","crosshair"),l.enter().append("rect").attr("class","extent").style("cursor","move"),c.enter().append("g").attr("class",function(e){return"resize "+e}).style("cursor",function(e){return uo[e]}).append("rect").attr("x",function(e){return/[ew]$/.test(e)?-3:null}).attr("y",function(e){return/^[ns]/.test(e)?-3:null}).attr("width",6).attr("height",6).style("visibility","hidden"),c.style("display",e.empty()?"none":null),c.exit().remove(),o&&(h=Dt(o),f.attr("x",h[0]).attr("width",h[1]-h[0]),n(s)),u&&(h=Dt(u),f.attr("y",h[0]).attr("height",h[1]-h[0]),r(s)),t(s)})}function t(e){e.selectAll(".resize").attr("transform",function(e){return"translate("+f[+/e$/.test(e)][0]+","+f[+/^s/.test(e)][1]+")"})}function n(e){e.select(".extent").attr("x",f[0][0]),e.selectAll(".extent,.n>rect,.s>rect").attr("width",f[1][0]-f[0][0])}function r(e){e.select(".extent").attr("y",f[0][1]),e.selectAll(".extent,.e>rect,.w>rect").attr("height",f[1][1]-f[0][1])}function i(){function i(){var e=d3.event.changedTouches;return e?d3.touches(v,e)[0]:d3.mouse(v)}function a(){d3.event.keyCode==32&&(S||(x=null,T[0]-=f[1][0],T[1]-=f[1][1],S=2),M())}function c(){d3.event.keyCode==32&&S==2&&(T[0]+=f[1][0],T[1]+=f[1][1],S=0,M())}function h(){var e=i(),s=!1;N&&(e[0]+=N[0],e[1]+=N[1]),S||(d3.event.altKey?(x||(x=[(f[0][0]+f[1][0])/2,(f[0][1]+f[1][1])/2]),T[0]=f[+(e[0]<x[0])][0],T[1]=f[+(e[1]<x[1])][1]):x=null),w&&p(e,o,0)&&(n(y),s=!0),E&&p(e,u,1)&&(r(y),s=!0),s&&(t(y),g({type:"brush",mode:S?"move":"resize"}))}function p(e,t,n){var r=Dt(t),i=r[0],s=r[1],o=T[n],u=f[1][n]-f[0][n],a,c;S&&(i-=o,s-=u+o),a=Math.max(i,Math.min(s,e[n])),S?c=(a+=o)+u:(x&&(o=Math.max(i,Math.min(s,2*x[n]-a))),o<a?(c=a,a=o):c=o);if(f[0][n]!==a||f[1][n]!==c)return l=null,f[0][n]=a,f[1][n]=c,!0}function d(){h(),y.style("pointer-events","all").selectAll(".resize").style("display",e.empty()?"none":null),d3.select("body").style("cursor",null),C.on("mousemove.brush",null).on("mouseup.brush",null).on("touchmove.brush",null).on("touchend.brush",null).on("keydown.brush",null).on("keyup.brush",null),g({type:"brushend"}),M()}var v=this,m=d3.select(d3.event.target),g=s.of(v,arguments),y=d3.select(v),b=m.datum(),w=!/^(n|s)$/.test(b)&&o,E=!/^(e|w)$/.test(b)&&u,S=m.classed("extent"),x,T=i(),N,C=d3.select(window).on("mousemove.brush",h).on("mouseup.brush",d).on("touchmove.brush",h).on("touchend.brush",d).on("keydown.brush",a).on("keyup.brush",c);if(S)T[0]=f[0][0]-T[0],T[1]=f[0][1]-T[1];else if(b){var k=+/w$/.test(b),L=+/^n/.test(b);N=[f[1-k][0]-T[0],f[1-L][1]-T[1]],T[0]=f[k][0],T[1]=f[L][1]}else d3.event.altKey&&(x=T.slice());y.style("pointer-events","none").selectAll(".resize").style("display",null),d3.select("body").style("cursor",m.style("cursor")),g({type:"brushstart"}),h(),M()}var s=D(e,"brushstart","brush","brushend"),o=null,u=null,a=ao[0],f=[[0,0],[0,0]],l;return e.x=function(t){return arguments.length?(o=t,a=ao[!o<<1|!u],e):o},e.y=function(t){return arguments.length?(u=t,a=ao[!o<<1|!u],e):u},e.extent=function(t){var n,r,i,s,a;return arguments.length?(l=[[0,0],[0,0]],o&&(n=t[0],r=t[1],u&&(n=n[0],r=r[0]),l[0][0]=n,l[1][0]=r,o.invert&&(n=o(n),r=o(r)),r<n&&(a=n,n=r,r=a),f[0][0]=n|0,f[1][0]=r|0),u&&(i=t[0],s=t[1],o&&(i=i[1],s=s[1]),l[0][1]=i,l[1][1]=s,u.invert&&(i=u(i),s=u(s)),s<i&&(a=i,i=s,s=a),f[0][1]=i|0,f[1][1]=s|0),e):(t=l||f,o&&(n=t[0][0],r=t[1][0],l||(n=f[0][0],r=f[1][0],o.invert&&(n=o.invert(n),r=o.invert(r)),r<n&&(a=n,n=r,r=a))),u&&(i=t[0][1],s=t[1][1],l||(i=f[0][1],s=f[1][1],u.invert&&(i=u.invert(i),s=u.invert(s)),s<i&&(a=i,i=s,s=a))),o&&u?[[n,i],[r,s]]:o?[n,r]:u&&[i,s])},e.clear=function(){return l=null,f[0][0]=f[0][1]=f[1][0]=f[1][1]=0,e},e.empty=function(){return o&&f[0][0]===f[1][0]||u&&f[0][1]===f[1][1]},d3.rebind(e,s,"on")};var uo={n:"ns-resize",e:"ew-resize",s:"ns-resize",w:"ew-resize",nw:"nwse-resize",ne:"nesw-resize",se:"nwse-resize",sw:"nesw-resize"},ao=[["n","e","s","w","nw","ne","se","sw"],["e","w"],["n","s"],[]];d3.behavior={},d3.behavior.drag=function(){function e(){this.on("mousedown.drag",t).on("touchstart.drag",t)}function t(){function e(){var e=o.parentNode;return f?d3.touches(e).filter(function(e){return e.identifier===f})[0]:d3.mouse(e)}function t(){if(!o.parentNode)return i();var t=e(),n=t[0]-c[0],r=t[1]-c[1];h|=n|r,c=t,M(),u({type:"drag",x:t[0]+l[0],y:t[1]+l[1],dx:n,dy:r})}function i(){u({type:"dragend"}),h&&(M(),d3.event.target===a&&p.on("click.drag",s,!0)),p.on(f?"touchmove.drag-"+f:"mousemove.drag",null).on(f?"touchend.drag-"+f:"mouseup.drag",null)}function s(){M(),p.on("click.drag",null)}var o=this,u=n.of(o,arguments),a=d3.event.target,f=d3.event.touches&&d3.event.changedTouches[0].identifier,l,c=e(),h=0,p=d3.select(window).on(f?"touchmove.drag-"+f:"mousemove.drag",t).on(f?"touchend.drag-"+f:"mouseup.drag",i,!0);r?(l=r.apply(o,arguments),l=[l.x-c[0],l.y-c[1]]):l=[0,0],f||M(),u({type:"dragstart"})}var n=D(e,"drag","dragstart","dragend"),r=null;return e.origin=function(t){return arguments.length?(r=t,e):r},d3.rebind(e,n,"on")},d3.behavior.zoom=function(){function e(){this.on("mousedown.zoom",o).on("mousewheel.zoom",u).on("mousemove.zoom",a).on("DOMMouseScroll.zoom",u).on("dblclick.zoom",f).on("touchstart.zoom",l).on("touchmove.zoom",c).on("touchend.zoom",l)}function t(e){return[(e[0]-h[0])/d,(e[1]-h[1])/d]}function n(e){return[e[0]*d+h[0],e[1]*d+h[1]]}function r(e){d=Math.max(m[0],Math.min(m[1],e))}function i(e,t){t=n(t),h[0]+=e[0]-t[0],h[1]+=e[1]-t[1]}function s(e){b&&b.domain(y.range().map(function(e){return(e-h[0])/d}).map(y.invert)),E&&E.domain(w.range().map(function(e){return(e-h[1])/d}).map(w.invert)),d3.event.preventDefault(),e({type:"zoom",scale:d,translate:h})}function o(){function e(){f=1,i(d3.mouse(o),c),s(u)}function n(){f&&M(),l.on("mousemove.zoom",null).on("mouseup.zoom",null),f&&d3.event.target===a&&l.on("click.zoom",r,!0)}function r(){M(),l.on("click.zoom",null)}var o=this,u=g.of(o,arguments),a=d3.event.target,f=0,l=d3.select(window).on("mousemove.zoom",e).on("mouseup.zoom",n),c=t(d3.mouse(o));window.focus(),M()}function u(){p||(p=t(d3.mouse(this))),r(Math.pow(2,Un()*.002)*d),i(d3.mouse(this),p),s(g.of(this,arguments))}function a(){p=null}function f(){var e=d3.mouse(this),n=t(e);r(d3.event.shiftKey?d/2:d*2),i(e,n),s(g.of(this,arguments))}function l(){var e=d3.touches(this),n=Date.now();v=d,p={},e.forEach(function(e){p[e.identifier]=t(e)}),M();if(e.length===1){if(n-S<500){var o=e[0],u=t(e[0]);r(d*2),i(o,u),s(g.of(this,arguments))}S=n}}function c(){var e=d3.touches(this),t=e[0],n=p[t.identifier];if(o=e[1]){var o,u=p[o.identifier];t=[(t[0]+o[0])/2,(t[1]+o[1])/2],n=[(n[0]+u[0])/2,(n[1]+u[1])/2],r(d3.event.scale*v)}i(t,n),S=null,s(g.of(this,arguments))}var h=[0,0],p,d=1,v,m=lo,g=D(e,"zoom"),y,b,w,E,S;return e.translate=function(t){return arguments.length?(h=t.map(Number),e):h},e.scale=function(t){return arguments.length?(d=+t,e):d},e.scaleExtent=function(t){return arguments.length?(m=t==null?lo:t.map(Number),e):m},e.x=function(t){return arguments.length?(b=t,y=t.copy(),e):b},e.y=function(t){return arguments.length?(E=t,w=t.copy(),e):E},d3.rebind(e,g,"on")};var fo,lo=[0,Infinity];d3.layout={},d3.layout.bundle=function(){return function(e){var t=[],n=-1,r=e.length;while(++n<r)t.push(zn(e[n]));return t}},d3.layout.chord=function(){function e(){var e={},n=[],c=d3.range(o),h=[],p,d,v,m,g;r=[],i=[],p=0,m=-1;while(++m<o){d=0,g=-1;while(++g<o)d+=s[m][g];n.push(d),h.push(d3.range(o)),p+=d}a&&c.sort(function(e,t){return a(n[e],n[t])}),f&&h.forEach(function(e,t){e.sort(function(e,n){return f(s[t][e],s[t][n])})}),p=(2*Math.PI-u*o)/p,d=0,m=-1;while(++m<o){v=d,g=-1;while(++g<o){var y=c[m],b=h[y][g],w=s[y][b],E=d,S=d+=w*p;e[y+"-"+b]={index:y,subindex:b,startAngle:E,endAngle:S,value:w}}i[y]={index:y,startAngle:v,endAngle:d,value:(d-v)/p},d+=u}m=-1;while(++m<o){g=m-1;while(++g<o){var x=e[m+"-"+g],T=e[g+"-"+m];(x.value||T.value)&&r.push(x.value<T.value?{source:T,target:x}:{source:x,target:T})}}l&&t()}function t(){r.sort(function(e,t){return l((e.source.value+e.target.value)/2,(t.source.value+t.target.value)/2)})}var n={},r,i,s,o,u=0,a,f,l;return n.matrix=function(e){return arguments.length?(o=(s=e)&&s.length,r=i=null,n):s},n.padding=function(e){return arguments.length?(u=e,r=i=null,n):u},n.sortGroups=function(e){return arguments.length?(a=e,r=i=null,n):a},n.sortSubgroups=function(e){return arguments.length?(f=e,r=null,n):f},n.sortChords=function(e){return arguments.length?(l=e,r&&t(),n):l},n.chords=function(){return r||e(),r},n.groups=function(){return i||e(),i},n},d3.layout.force=function(){function e(e){return function(t,n,r,i,s){if(t.point!==e){var o=t.cx-e.x,u=t.cy-e.y,a=1/Math.sqrt(o*o+u*u);if((i-n)*a<d){var f=t.charge*a*a;return e.px-=o*f,e.py-=u*f,!0}if(t.point&&isFinite(a)){var f=t.pointCharge*a*a;e.px-=o*f,e.py-=u*f}}return!t.charge}}function t(e){e.px=d3.event.x,e.py=d3.event.y,n.resume()}var n={},r=d3.dispatch("start","tick","end"),s=[1,1],o,a,f=.9,l=Gn,c=Yn,h=-30,p=.1,d=.8,v,m=[],g=[],y,b,w;return n.tick=function(){if((a*=.99)<.005)return r.end({type:"end",alpha:a=0}),!0;var t=m.length,n=g.length,i,o,u,l,c,d,v,E,S;for(o=0;o<n;++o){u=g[o],l=u.source,c=u.target,E=c.x-l.x,S=c.y-l.y;if(d=E*E+S*S)d=a*b[o]*((d=Math.sqrt(d))-y[o])/d,E*=d,S*=d,c.x-=E*(v=l.weight/(c.weight+l.weight)),c.y-=S*v,l.x+=E*(v=1-v),l.y+=S*v}if(v=a*p){E=s[0]/2,S=s[1]/2,o=-1;if(v)while(++o<t)u=m[o],u.x+=(E-u.x)*v,u.y+=(S-u.y)*v}if(h){Qn(i=d3.geom.quadtree(m),a,w),o=-1;while(++o<t)(u=m[o]).fixed||i.visit(e(u))}o=-1;while(++o<t)u=m[o],u.fixed?(u.x=u.px,u.y=u.py):(u.x-=(u.px-(u.px=u.x))*f,u.y-=(u.py-(u.py=u.y))*f);r.tick({type:"tick",alpha:a})},n.nodes=function(e){return arguments.length?(m=e,n):m},n.links=function(e){return arguments.length?(g=e,n):g},n.size=function(e){return arguments.length?(s=e,n):s},n.linkDistance=function(e){return arguments.length?(l=u(e),n):l},n.distance=n.linkDistance,n.linkStrength=function(e){return arguments.length?(c=u(e),n):c},n.friction=function(e){return arguments.length?(f=e,n):f},n.charge=function(e){return arguments.length?(h=typeof e=="function"?e:+e,n):h},n.gravity=function(e){return arguments.length?(p=e,n):p},n.theta=function(e){return arguments.length?(d=e,n):d},n.alpha=function(e){return arguments.length?(a?e>0?a=e:a=0:e>0&&(r.start({type:"start",alpha:a=e}),d3.timer(n.tick)),n):a},n.start=function(){function e(e,n){var i=t(r),s=-1,o=i.length,u;while(++s<o)if(!isNaN(u=i[s][e]))return u;return Math.random()*n}function t(){if(!p){p=[];for(i=0;i<o;++i)p[i]=[];for(i=0;i<u;++i){var e=g[i];p[e.source.index].push(e.target),p[e.target.index].push(e.source)}}return p[r]}var r,i,o=m.length,u=g.length,a=s[0],f=s[1],p,d;for(r=0;r<o;++r)(d=m[r]).index=r,d.weight=0;y=[],b=[];for(r=0;r<u;++r)d=g[r],typeof d.source=="number"&&(d.source=m[d.source]),typeof d.target=="number"&&(d.target=m[d.target]),y[r]=l.call(this,d,r),b[r]=c.call(this,d,r),++d.source.weight,++d.target.weight;for(r=0;r<o;++r)d=m[r],isNaN(d.x)&&(d.x=e("x",a)),isNaN(d.y)&&(d.y=e("y",f)),isNaN(d.px)&&(d.px=d.x),isNaN(d.py)&&(d.py=d.y);w=[];if(typeof h=="function")for(r=0;r<o;++r)w[r]=+h.call(this,m[r],r);else for(r=0;r<o;++r)w[r]=h;return n.resume()},n.resume=function(){return n.alpha(.1)},n.stop=function(){return n.alpha(0)},n.drag=function(){o||(o=d3.behavior.drag().origin(i).on("dragstart",Vn).on("drag",t).on("dragend",$n)),this.on("mouseover.force",Jn).on("mouseout.force",Kn).call(o)},d3.rebind(n,r,"on")},d3.layout.partition=function(){function e(t,n,r,i){var s=t.children;t.x=n,t.y=t.depth*i,t.dx=r,t.dy=i;if(s&&(u=s.length)){var o=-1,u,a,f;r=t.value?r/t.value:0;while(++o<u)e(a=s[o],n,f=a.value*r,i),n+=f}}function t(e){var n=e.children,r=0;if(n&&(s=n.length)){var i=-1,s;while(++i<s)r=Math.max(r,t(n[i]))}return 1+r}function n(n,s){var o=r.call(this,n,s);return e(o[0],0,i[0],i[1]/t(o[0])),o}var r=d3.layout.hierarchy(),i=[1,1];return n.size=function(e){return arguments.length?(i=e,n):i},lr(n,r)},d3.layout.pie=function(){function e(s,o){var u=s.map(function(n,r){return+t.call(e,n,r)}),a=+(typeof r=="function"?r.apply(this,arguments):r),f=((typeof i=="function"?i.apply(this,arguments):i)-r)/d3.sum(u),l=d3.range(s.length);n!=null&&l.sort(n===co?function(e,t){return u[t]-u[e]}:function(e,t){return n(s[e],s[t])});var c=[];return l.forEach(function(e){var t;c[e]={data:s[e],value:t=u[e],startAngle:a,endAngle:a+=t*f}}),c}var t=Number,n=co,r=0,i=2*Math.PI;return e.value=function(n){return arguments.length?(t=n,e):t},e.sort=function(t){return arguments.length?(n=t,e):n},e.startAngle=function(t){return arguments.length?(r=t,e):r},e.endAngle=function(t){return arguments.length?(i=t,e):i},e};var co={};d3.layout.stack=function(){function e(i,a){var f=i.map(function(n,r){return t.call(e,n,r)}),l=f.map(function(t,n){return t.map(function(t,n){return[o.call(e,t,n),u.call(e,t,n)]})}),c=n.call(e,l,a);f=d3.permute(f,c),l=d3.permute(l,c);var h=r.call(e,l,a),p=f.length,d=f[0].length,v,m,g;for(m=0;m<d;++m){s.call(e,f[0][m],g=h[m],l[0][m][1]);for(v=1;v<p;++v)s.call(e,f[v][m],g+=l[v-1][m][1],l[v][m][1])}return i}var t=i,n=nr,r=rr,s=tr,o=Zn,u=er;return e.values=function(n){return arguments.length?(t=n,e):t},e.order=function(t){return arguments.length?(n=typeof t=="function"?t:ho.get(t)||nr,e):n},e.offset=function(t){return arguments.length?(r=typeof t=="function"?t:po.get(t)||rr,e):r},e.x=function(t){return arguments.length?(o=t,e):o},e.y=function(t){return arguments.length?(u=t,e):u},e.out=function(t){return arguments.length?(s=t,e):s},e};var ho=d3.map({"inside-out":function(e){var t=e.length,n,r,i=e.map(ir),s=e.map(sr),o=d3.range(t).sort(function(e,t){return i[e]-i[t]}),u=0,a=0,f=[],l=[];for(n=0;n<t;++n)r=o[n],u<a?(u+=s[r],f.push(r)):(a+=s[r],l.push(r));return l.reverse().concat(f)},reverse:function(e){return d3.range(e.length).reverse()},"default":nr}),po=d3.map({silhouette:function(e){var t=e.length,n=e[0].length,r=[],i=0,s,o,u,a=[];for(o=0;o<n;++o){for(s=0,u=0;s<t;s++)u+=e[s][o][1];u>i&&(i=u),r.push(u)}for(o=0;o<n;++o)a[o]=(i-r[o])/2;return a},wiggle:function(e){var t=e.length,n=e[0],r=n.length,i=0,s,o,u,a,f,l,c,h,p,d=[];d[0]=h=p=0;for(o=1;o<r;++o){for(s=0,a=0;s<t;++s)a+=e[s][o][1];for(s=0,f=0,c=n[o][0]-n[o-1][0];s<t;++s){for(u=0,l=(e[s][o][1]-e[s][o-1][1])/(2*c);u<s;++u)l+=(e[u][o][1]-e[u][o-1][1])/c;f+=l*e[s][o][1]}d[o]=h-=a?f/a*c:0,h<p&&(p=h)}for(o=0;o<r;++o)d[o]-=p;return d},expand:function(e){var t=e.length,n=e[0].length,r=1/t,i,s,o,u=[];for(s=0;s<n;++s){for(i=0,o=0;i<t;i++)o+=e[i][s][1];if(o)for(i=0;i<t;i++)e[i][s][1]/=o;else for(i=0;i<t;i++)e[i][s][1]=r}for(s=0;s<n;++s)u[s]=0;return u},zero:rr});d3.layout.histogram=function(){function e(e,s){var o=[],u=e.map(n,this),a=r.call(this,u,s),f=i.call(this,a,u,s),l,s=-1,c=u.length,h=f.length-1,p=t?1:1/c,d;while(++s<h)l=o[s]=[],l.dx=f[s+1]-(l.x=f[s]),l.y=0;if(h>0){s=-1;while(++s<c)d=u[s],d>=a[0]&&d<=a[1]&&(l=o[d3.bisect(f,d,1,h)-1],l.y+=p,l.push(e[s]))}return o}var t=!0,n=Number,r=fr,i=ur;return e.value=function(t){return arguments.length?(n=t,e):n},e.range=function(t){return arguments.length?(r=u(t),e):r},e.bins=function(t){return arguments.length?(i=typeof t=="number"?function(e){return ar(e,t)}:u(t),e):i},e.frequency=function(n){return arguments.length?(t=!!n,e):t},e},d3.layout.hierarchy=function(){function e(t,o,u){var a=i.call(n,t,o),f=vo?t:{data:t};f.depth=o,u.push(f);if(a&&(c=a.length)){var l=-1,c,h=f.children=[],p=0,d=o+1,v;while(++l<c)v=e(a[l],d,u),v.parent=f,h.push(v),p+=v.value;r&&h.sort(r),s&&(f.value=p)}else s&&(f.value=+s.call(n,t,o)||0);return f}function t(e,r){var i=e.children,o=0;if(i&&(a=i.length)){var u=-1,a,f=r+1;while(++u<a)o+=t(i[u],f)}else s&&(o=+s.call(n,vo?e:e.data,r)||0);return s&&(e.value=o),o}function n(t){var n=[];return e(t,0,n),n}var r=pr,i=cr,s=hr;return n.sort=function(e){return arguments.length?(r=e,n):r},n.children=function(e){return arguments.length?(i=e,n):i},n.value=function(e){return arguments.length?(s=e,n):s},n.revalue=function(e){return t(e,0),e},n};var vo=!1;d3.layout.pack=function(){function e(e,i){var s=t.call(this,e,i),o=s[0];o.x=0,o.y=0,Hr(o,function(e){e.r=Math.sqrt(e.value)}),Hr(o,br);var u=r[0],a=r[1],f=Math.max(2*o.r/u,2*o.r/a);if(n>0){var l=n*f/2;Hr(o,function(e){e.r+=l}),Hr(o,br),Hr(o,function(e){e.r-=l}),f=Math.max(2*o.r/u,2*o.r/a)}return Sr(o,u/2,a/2,1/f),s}var t=d3.layout.hierarchy().sort(vr),n=0,r=[1,1];return e.size=function(t){return arguments.length?(r=t,e):r},e.padding=function(t){return arguments.length?(n=+t,e):n},lr(e,t)},d3.layout.cluster=function(){function e(e,i){var s=t.call(this,e,i),o=s[0],u,a=0,f,l;Hr(o,function(e){var t=e.children;t&&t.length?(e.x=Nr(t),e.y=Tr(t)):(e.x=u?a+=n(e,u):0,e.y=0,u=e)});var c=Cr(o),h=kr(o),p=c.x-n(c,h)/2,d=h.x+n(h,c)/2;return Hr(o,function(e){e.x=(e.x-p)/(d-p)*r[0],e.y=(1-(o.y?e.y/o.y:1))*r[1]}),s}var t=d3.layout.hierarchy().sort(null).value(null),n=Lr,r=[1,1];return e.separation=function(t){return arguments.length?(n=t,e):n},e.size=function(t){return arguments.length?(r=t,e):r},lr(e,t)},d3.layout.tree=function(){function e(e,i){function s(e,t){var r=e.children,i=e._tree;if(r&&(o=r.length)){var o,a=r[0],f,l=a,c,h=-1;while(++h<o)c=r[h],s(c,f),l=u(c,f,l),f=c;Br(e);var p=.5*(a._tree.prelim+c._tree.prelim);t?(i.prelim=t._tree.prelim+n(e,t),i.mod=i.prelim-p):i.prelim=p}else t&&(i.prelim=t._tree.prelim+n(e,t))}function o(e,t){e.x=e._tree.prelim+t;var n=e.children;if(n&&(i=n.length)){var r=-1,i;t+=e._tree.mod;while(++r<i)o(n[r],t)}}function u(e,t,r){if(t){var i=e,s=e,o=t,u=e.parent.children[0],a=i._tree.mod,f=s._tree.mod,l=o._tree.mod,c=u._tree.mod,h;while(o=Or(o),i=Ar(i),o&&i)u=Ar(u),s=Or(s),s._tree.ancestor=e,h=o._tree.prelim+l-i._tree.prelim-a+n(o,i),h>0&&(jr(Fr(o,e,r),e,h),a+=h,f+=h),l+=o._tree.mod,a+=i._tree.mod,c+=u._tree.mod,f+=s._tree.mod;o&&!Or(s)&&(s._tree.thread=o,s._tree.mod+=l-f),i&&!Ar(u)&&(u._tree.thread=i,u._tree.mod+=a-c,r=e)}return r}var a=t.call(this,e,i),f=a[0];Hr(f,function(e,t){e._tree={ancestor:e,prelim:0,mod:0,change:0,shift:0,number:t?t._tree.number+1:0}}),s(f),o(f,-f._tree.prelim);var l=Mr(f,Dr),c=Mr(f,_r),h=Mr(f,Pr),p=l.x-n(l,c)/2,d=c.x+n(c,l)/2,v=h.depth||1;return Hr(f,function(e){e.x=(e.x-p)/(d-p)*r[0],e.y=e.depth/v*r[1],delete e._tree}),a}var t=d3.layout.hierarchy().sort(null).value(null),n=Lr,r=[1,1];return e.separation=function(t){return arguments.length?(n=t,e):n},e.size=function(t){return arguments.length?(r=t,e):r},lr(e,t)},d3.layout.treemap=function(){function e(e,t){var n=-1,r=e.length,i,s;while(++n<r)s=(i=e[n]).value*(t<0?0:t),i.area=isNaN(s)||s<=0?0:s}function t(n){var s=n.children;if(s&&s.length){var o=l(n),u=[],a=s.slice(),f,c=Infinity,h,p=Math.min(o.dx,o.dy),d;e(a,o.dx*o.dy/n.value),u.area=0;while((d=a.length)>0)u.push(f=a[d-1]),u.area+=f.area,(h=r(u,p))<=c?(a.pop(),c=h):(u.area-=u.pop().area,i(u,p,o,!1),p=Math.min(o.dx,o.dy),u.length=u.area=0,c=Infinity);u.length&&(i(u,p,o,!0),u.length=u.area=0),s.forEach(t)}}function n(t){var r=t.children;if(r&&r.length){var s=l(t),o=r.slice(),u,a=[];e(o,s.dx*s.dy/t.value),a.area=0;while(u=o.pop())a.push(u),a.area+=u.area,u.z!=null&&(i(a,u.z?s.dx:s.dy,s,!o.length),a.length=a.area=0);r.forEach(n)}}function r(e,t){var n=e.area,r,i=0,s=Infinity,o=-1,u=e.length;while(++o<u){if(!(r=e[o].area))continue;r<s&&(s=r),r>i&&(i=r)}return n*=n,t*=t,n?Math.max(t*i*p/n,n/(t*s*p)):Infinity}function i(e,t,n,r){var i=-1,s=e.length,o=n.x,a=n.y,f=t?u(e.area/t):0,l;if(t==n.dx){if(r||f>n.dy)f=n.dy;while(++i<s)l=e[i],l.x=o,l.y=a,l.dy=f,o+=l.dx=Math.min(n.x+n.dx-o,f?u(l.area/f):0);l.z=!0,l.dx+=n.x+n.dx-o,n.y+=f,n.dy-=f}else{if(r||f>n.dx)f=n.dx;while(++i<s)l=e[i],l.x=o,l.y=a,l.dx=f,a+=l.dy=Math.min(n.y+n.dy-a,f?u(l.area/f):0);l.z=!1,l.dy+=n.y+n.dy-a,n.x+=f,n.dx-=f}}function s(r){var i=h||o(r),s=i[0];return s.x=0,s.y=0,s.dx=a[0],s.dy=a[1],h&&o.revalue(s),e([s],s.dx*s.dy/s.value),(h?n:t)(s),c&&(h=i),i}var o=d3.layout.hierarchy(),u=Math.round,a=[1,1],f=null,l=Ir,c=!1,h,p=.5*(1+Math.sqrt(5));return s.size=function(e){return arguments.length?(a=e,s):a},s.padding=function(e){function t(t){var n=e.call(s,t,t.depth);return n==null?Ir(t):qr(t,typeof n=="number"?[n,n,n,n]:n)}function n(t){return qr(t,e)}if(!arguments.length)return f;var r;return l=(f=e)==null?Ir:(r=typeof e)==="function"?t:r==="number"?(e=[e,e,e,e],n):n,s},s.round=function(e){return arguments.length?(u=e?Math.round:Number,s):u!=Number},s.sticky=function(e){return arguments.length?(c=e,h=null,s):c},s.ratio=function(e){return arguments.length?(p=e,s):p},lr(s,o)},d3.csv=Rr(",","text/csv"),d3.tsv=Rr("	","text/tab-separated-values"),d3.geo={};var mo=Math.PI/180;d3.geo.azimuthal=function(){function e(e){var n=e[0]*mo-s,o=e[1]*mo,f=Math.cos(n),l=Math.sin(n),c=Math.cos(o),h=Math.sin(o),p=t!=="orthographic"?a*h+u*c*f:null,d,v=t==="stereographic"?1/(1+p):t==="gnomonic"?1/p:t==="equidistant"?(d=Math.acos(p),d?d/Math.sin(d):0):t==="equalarea"?Math.sqrt(2/(1+p)):1,m=v*c*l,g=v*(a*c*f-u*h);return[r*m+i[0],r*g+i[1]]}var t="orthographic",n,r=200,i=[480,250],s,o,u,a;return e.invert=function(e){var n=(e[0]-i[0])/r,o=(e[1]-i[1])/r,f=Math.sqrt(n*n+o*o),l=t==="stereographic"?2*Math.atan(f):t==="gnomonic"?Math.atan(f):t==="equidistant"?f:t==="equalarea"?2*Math.asin(.5*f):Math.asin(f),c=Math.sin(l),h=Math.cos(l);return[(s+Math.atan2(n*c,f*u*h+o*a*c))/mo,Math.asin(h*a-(f?o*c*u/f:0))/mo]},e.mode=function(n){return arguments.length?(t=n+"",e):t},e.origin=function(t){return arguments.length?(n=t,s=n[0]*mo,o=n[1]*mo,u=Math.cos(o),a=Math.sin(o),e):n},e.scale=function(t){return arguments.length?(r=+t,e):r},e.translate=function(t){return arguments.length?(i=[+t[0],+t[1]],e):i},e.origin([0,0])},d3.geo.albers=function(){function e(e){var t=u*(mo*e[0]-o),n=Math.sqrt(a-2*u*Math.sin(mo*e[1]))/u;return[i*n*Math.sin(t)+s[0],i*(n*Math.cos(t)-f)+s[1]]}function t(){var t=mo*r[0],i=mo*r[1],s=mo*n[1],l=Math.sin(t),c=Math.cos(t);return o=mo*n[0],u=.5*(l+Math.sin(i)),a=c*c+2*u*l,f=Math.sqrt(a-2*u*Math.sin(s))/u,e}var n=[-98,38],r=[29.5,45.5],i=1e3,s=[480,250],o,u,a,f;return e.invert=function(e){var t=(e[0]-s[0])/i,n=(e[1]-s[1])/i,r=f+n,l=Math.atan2(t,r),c=Math.sqrt(t*t+r*r);return[(o+l/u)/mo,Math.asin((a-c*c*u*u)/(2*u))/mo]},e.origin=function(e){return arguments.length?(n=[+e[0],+e[1]],t()):n},e.parallels=function(e){return arguments.length?(r=[+e[0],+e[1]],t()):r},e.scale=function(t){return arguments.length?(i=+t,e):i},e.translate=function(t){return arguments.length?(s=[+t[0],+t[1]],e):s},t()},d3.geo.albersUsa=function(){function e(e){var s=e[0],o=e[1];return(o>50?n:s<-140?r:o<21?i:t)(e)}var t=d3.geo.albers(),n=d3.geo.albers().origin([-160,60]).parallels([55,65]),r=d3.geo.albers().origin([-160,20]).parallels([8,18]),i=d3.geo.albers().origin([-60,10]).parallels([8,18]);return e.scale=function(s){return arguments.length?(t.scale(s),n.scale(s*.6),r.scale(s),i.scale(s*1.5),e.translate(t.translate())):t.scale()},e.translate=function(s){if(!arguments.length)return t.translate();var o=t.scale()/1e3,u=s[0],a=s[1];return t.translate(s),n.translate([u-400*o,a+170*o]),r.translate([u-190*o,a+200*o]),i.translate([u+580*o,a+430*o]),e},e.scale(t.scale())},d3.geo.bonne=function(){function e(e){var u=e[0]*mo-r,a=e[1]*mo-i;if(s){var f=o+s-a,l=u*Math.cos(a)/f;u=f*Math.sin(l),a=f*Math.cos(l)-o}else u*=Math.cos(a),a*=-1;return[t*u+n[0],t*a+n[1]]}var t=200,n=[480,250],r,i,s,o;return e.invert=function(e){var i=(e[0]-n[0])/t,u=(e[1]-n[1])/t;if(s){var a=o+u,f=Math.sqrt(i*i+a*a);u=o+s-f,i=r+f*Math.atan2(i,a)/Math.cos(u)}else u*=-1,i/=Math.cos(u);return[i/mo,u/mo]},e.parallel=function(t){return arguments.length?(o=1/Math.tan(s=t*mo),e):s/mo},e.origin=function(t){return arguments.length?(r=t[0]*mo,i=t[1]*mo,e):[r/mo,i/mo]},e.scale=function(
    n){return arguments.length?(t=+n,e):t},e.translate=function(t){return arguments.length?(n=[+t[0],+t[1]],e):n},e.origin([0,0]).parallel(45)},d3.geo.equirectangular=function(){function e(e){var r=e[0]/360,i=-e[1]/360;return[t*r+n[0],t*i+n[1]]}var t=500,n=[480,250];return e.invert=function(e){var r=(e[0]-n[0])/t,i=(e[1]-n[1])/t;return[360*r,-360*i]},e.scale=function(n){return arguments.length?(t=+n,e):t},e.translate=function(t){return arguments.length?(n=[+t[0],+t[1]],e):n},e},d3.geo.mercator=function(){function e(e){var r=e[0]/360,i=-(Math.log(Math.tan(Math.PI/4+e[1]*mo/2))/mo)/360;return[t*r+n[0],t*Math.max(-0.5,Math.min(.5,i))+n[1]]}var t=500,n=[480,250];return e.invert=function(e){var r=(e[0]-n[0])/t,i=(e[1]-n[1])/t;return[360*r,2*Math.atan(Math.exp(-360*i*mo))/mo-90]},e.scale=function(n){return arguments.length?(t=+n,e):t},e.translate=function(t){return arguments.length?(n=[+t[0],+t[1]],e):n},e},d3.geo.path=function(){function e(e,t){typeof s=="function"&&(o=zr(s.apply(this,arguments))),f(e);var n=a.length?a.join(""):null;return a=[],n}function t(e){return u(e).join(",")}function n(e){var t=i(e[0]),n=0,r=e.length;while(++n<r)t-=i(e[n]);return t}function r(e){var t=d3.geom.polygon(e[0].map(u)),n=t.area(),r=t.centroid(n<0?(n*=-1,1):-1),i=r[0],s=r[1],o=n,a=0,f=e.length;while(++a<f)t=d3.geom.polygon(e[a].map(u)),n=t.area(),r=t.centroid(n<0?(n*=-1,1):-1),i-=r[0],s-=r[1],o-=n;return[i,s,6*o]}function i(e){return Math.abs(d3.geom.polygon(e.map(u)).area())}var s=4.5,o=zr(s),u=d3.geo.albersUsa(),a=[],f=Ur({FeatureCollection:function(e){var t=e.features,n=-1,r=t.length;while(++n<r)a.push(f(t[n].geometry))},Feature:function(e){f(e.geometry)},Point:function(e){a.push("M",t(e.coordinates),o)},MultiPoint:function(e){var n=e.coordinates,r=-1,i=n.length;while(++r<i)a.push("M",t(n[r]),o)},LineString:function(e){var n=e.coordinates,r=-1,i=n.length;a.push("M");while(++r<i)a.push(t(n[r]),"L");a.pop()},MultiLineString:function(e){var n=e.coordinates,r=-1,i=n.length,s,o,u;while(++r<i){s=n[r],o=-1,u=s.length,a.push("M");while(++o<u)a.push(t(s[o]),"L");a.pop()}},Polygon:function(e){var n=e.coordinates,r=-1,i=n.length,s,o,u;while(++r<i){s=n[r],o=-1;if((u=s.length-1)>0){a.push("M");while(++o<u)a.push(t(s[o]),"L");a[a.length-1]="Z"}}},MultiPolygon:function(e){var n=e.coordinates,r=-1,i=n.length,s,o,u,f,l,c;while(++r<i){s=n[r],o=-1,u=s.length;while(++o<u){f=s[o],l=-1;if((c=f.length-1)>0){a.push("M");while(++l<c)a.push(t(f[l]),"L");a[a.length-1]="Z"}}}},GeometryCollection:function(e){var t=e.geometries,n=-1,r=t.length;while(++n<r)a.push(f(t[n]))}}),l=e.area=Ur({FeatureCollection:function(e){var t=0,n=e.features,r=-1,i=n.length;while(++r<i)t+=l(n[r]);return t},Feature:function(e){return l(e.geometry)},Polygon:function(e){return n(e.coordinates)},MultiPolygon:function(e){var t=0,r=e.coordinates,i=-1,s=r.length;while(++i<s)t+=n(r[i]);return t},GeometryCollection:function(e){var t=0,n=e.geometries,r=-1,i=n.length;while(++r<i)t+=l(n[r]);return t}},0),c=e.centroid=Ur({Feature:function(e){return c(e.geometry)},Polygon:function(e){var t=r(e.coordinates);return[t[0]/t[2],t[1]/t[2]]},MultiPolygon:function(e){var t=0,n=e.coordinates,i,s=0,o=0,u=0,a=-1,f=n.length;while(++a<f)i=r(n[a]),s+=i[0],o+=i[1],u+=i[2];return[s/u,o/u]}});return e.projection=function(t){return u=t,e},e.pointRadius=function(t){return typeof t=="function"?s=t:(s=+t,o=zr(s)),e},e},d3.geo.bounds=function(e){var t=Infinity,n=Infinity,r=-Infinity,i=-Infinity;return Wr(e,function(e,s){e<t&&(t=e),e>r&&(r=e),s<n&&(n=s),s>i&&(i=s)}),[[t,n],[r,i]]};var go={Feature:Xr,FeatureCollection:Vr,GeometryCollection:$r,LineString:Jr,MultiLineString:Kr,MultiPoint:Jr,MultiPolygon:Qr,Point:Gr,Polygon:Yr};d3.geo.circle=function(){function e(){}function t(e){return a.distance(e)<u}function n(e){var t=-1,n=e.length,i=[],s,o,f,l,c;while(++t<n)c=a.distance(f=e[t]),c<u?(o&&i.push(ni(o,f)((l-u)/(l-c))),i.push(f),s=o=null):(o=f,!s&&i.length&&(i.push(ni(i[i.length-1],o)((u-l)/(c-l))),s=o)),l=c;return s=e[0],o=i[0],o&&f[0]===s[0]&&f[1]===s[1]&&(f[0]!==o[0]||f[1]!==o[1])&&i.push(o),r(i)}function r(e){var t=0,n=e.length,r,i,s=n?[e[0]]:e,o,u=a.source();while(++t<n){o=a.source(e[t-1])(e[t]).coordinates;for(r=0,i=o.length;++r<i;)s.push(o[r])}return a.source(u),s}var s=[0,0],o=89.99,u=o*mo,a=d3.geo.greatArc().source(s).target(i);e.clip=function(e){return typeof s=="function"&&a.source(s.apply(this,arguments)),f(e)||null};var f=Ur({FeatureCollection:function(e){var t=e.features.map(f).filter(i);return t&&(e=Object.create(e),e.features=t,e)},Feature:function(e){var t=f(e.geometry);return t&&(e=Object.create(e),e.geometry=t,e)},Point:function(e){return t(e.coordinates)&&e},MultiPoint:function(e){var n=e.coordinates.filter(t);return n.length&&{type:e.type,coordinates:n}},LineString:function(e){var t=n(e.coordinates);return t.length&&(e=Object.create(e),e.coordinates=t,e)},MultiLineString:function(e){var t=e.coordinates.map(n).filter(function(e){return e.length});return t.length&&(e=Object.create(e),e.coordinates=t,e)},Polygon:function(e){var t=e.coordinates.map(n);return t[0].length&&(e=Object.create(e),e.coordinates=t,e)},MultiPolygon:function(e){var t=e.coordinates.map(function(e){return e.map(n)}).filter(function(e){return e[0].length});return t.length&&(e=Object.create(e),e.coordinates=t,e)},GeometryCollection:function(e){var t=e.geometries.map(f).filter(i);return t.length&&(e=Object.create(e),e.geometries=t,e)}});return e.origin=function(t){return arguments.length?(s=t,typeof s!="function"&&a.source(s),e):s},e.angle=function(t){return arguments.length?(u=(o=+t)*mo,e):o},d3.rebind(e,a,"precision")},d3.geo.greatArc=function(){function e(){var t=e.distance.apply(this,arguments),r=0,u=s/t,a=[n];while((r+=u)<1)a.push(o(r));return a.push(i),{type:"LineString",coordinates:a}}var t=Zr,n,r=ei,i,s=6*mo,o=ti();return e.distance=function(){return typeof t=="function"&&o.source(n=t.apply(this,arguments)),typeof r=="function"&&o.target(i=r.apply(this,arguments)),o.distance()},e.source=function(r){return arguments.length?(t=r,typeof t!="function"&&o.source(n=t),e):t},e.target=function(t){return arguments.length?(r=t,typeof r!="function"&&o.target(i=r),e):r},e.precision=function(t){return arguments.length?(s=t*mo,e):s/mo},e},d3.geo.greatCircle=d3.geo.circle,d3.geom={},d3.geom.contour=function(e,t){var n=t||ri(e),r=[],i=n[0],s=n[1],o=0,u=0,a=NaN,f=NaN,l=0;do l=0,e(i-1,s-1)&&(l+=1),e(i,s-1)&&(l+=2),e(i-1,s)&&(l+=4),e(i,s)&&(l+=8),l===6?(o=f===-1?-1:1,u=0):l===9?(o=0,u=a===1?-1:1):(o=yo[l],u=bo[l]),o!=a&&u!=f&&(r.push([i,s]),a=o,f=u),i+=o,s+=u;while(n[0]!=i||n[1]!=s);return r};var yo=[1,0,1,1,-1,0,-1,1,0,0,0,0,-1,0,-1,NaN],bo=[0,-1,0,0,0,-1,0,0,1,-1,1,1,0,-1,0,NaN];d3.geom.hull=function(e){if(e.length<3)return[];var t=e.length,n=t-1,r=[],i=[],s,o,u=0,a,f,l,c,h,p,d,v;for(s=1;s<t;++s)e[s][1]<e[u][1]?u=s:e[s][1]==e[u][1]&&(u=e[s][0]<e[u][0]?s:u);for(s=0;s<t;++s){if(s===u)continue;f=e[s][1]-e[u][1],a=e[s][0]-e[u][0],r.push({angle:Math.atan2(f,a),index:s})}r.sort(function(e,t){return e.angle-t.angle}),d=r[0].angle,p=r[0].index,h=0;for(s=1;s<n;++s)o=r[s].index,d==r[s].angle?(a=e[p][0]-e[u][0],f=e[p][1]-e[u][1],l=e[o][0]-e[u][0],c=e[o][1]-e[u][1],a*a+f*f>=l*l+c*c?r[s].index=-1:(r[h].index=-1,d=r[s].angle,h=s,p=o)):(d=r[s].angle,h=s,p=o);i.push(u);for(s=0,o=0;s<2;++o)r[o].index!==-1&&(i.push(r[o].index),s++);v=i.length;for(;o<n;++o){if(r[o].index===-1)continue;while(!ii(i[v-2],i[v-1],r[o].index,e))--v;i[v++]=r[o].index}var m=[];for(s=0;s<v;++s)m.push(e[i[s]]);return m},d3.geom.polygon=function(e){return e.area=function(){var t=0,n=e.length,r=e[n-1][0]*e[0][1],i=e[n-1][1]*e[0][0];while(++t<n)r+=e[t-1][0]*e[t][1],i+=e[t-1][1]*e[t][0];return(i-r)*.5},e.centroid=function(t){var n=-1,r=e.length,i=0,s=0,o,u=e[r-1],a;arguments.length||(t=-1/(6*e.area()));while(++n<r)o=u,u=e[n],a=o[0]*u[1]-u[0]*o[1],i+=(o[0]+u[0])*a,s+=(o[1]+u[1])*a;return[i*t,s*t]},e.clip=function(t){var n,r=-1,i=e.length,s,o,u=e[i-1],a,f,l;while(++r<i){n=t.slice(),t.length=0,a=e[r],f=n[(o=n.length)-1],s=-1;while(++s<o)l=n[s],si(l,u,a)?(si(f,u,a)||t.push(oi(f,l,u,a)),t.push(l)):si(f,u,a)&&t.push(oi(f,l,u,a)),f=l;u=a}return t},e},d3.geom.voronoi=function(e){var t=e.map(function(){return[]});return ui(e,function(e){var n,r,i,s,o,u;e.a===1&&e.b>=0?(n=e.ep.r,r=e.ep.l):(n=e.ep.l,r=e.ep.r),e.a===1?(o=n?n.y:-1e6,i=e.c-e.b*o,u=r?r.y:1e6,s=e.c-e.b*u):(i=n?n.x:-1e6,o=e.c-e.a*i,s=r?r.x:1e6,u=e.c-e.a*s);var a=[i,o],f=[s,u];t[e.region.l.index].push(a,f),t[e.region.r.index].push(a,f)}),t.map(function(t,n){var r=e[n][0],i=e[n][1];return t.forEach(function(e){e.angle=Math.atan2(e[0]-r,e[1]-i)}),t.sort(function(e,t){return e.angle-t.angle}).filter(function(e,n){return!n||e.angle-t[n-1].angle>1e-10})})};var wo={l:"r",r:"l"};d3.geom.delaunay=function(e){var t=e.map(function(){return[]}),n=[];return ui(e,function(n){t[n.region.l.index].push(e[n.region.r.index])}),t.forEach(function(t,r){var i=e[r],s=i[0],o=i[1];t.forEach(function(e){e.angle=Math.atan2(e[0]-s,e[1]-o)}),t.sort(function(e,t){return e.angle-t.angle});for(var u=0,a=t.length-1;u<a;u++)n.push([i,t[u],t[u+1]])}),n},d3.geom.quadtree=function(e,t,n,r,i){function s(e,t,n,r,i,s){if(isNaN(t.x)||isNaN(t.y))return;if(e.leaf){var u=e.point;u?Math.abs(u.x-t.x)+Math.abs(u.y-t.y)<.01?o(e,t,n,r,i,s):(e.point=null,o(e,u,n,r,i,s),o(e,t,n,r,i,s)):e.point=t}else o(e,t,n,r,i,s)}function o(e,t,n,r,i,o){var u=(n+i)*.5,a=(r+o)*.5,f=t.x>=u,l=t.y>=a,c=(l<<1)+f;e.leaf=!1,e=e.nodes[c]||(e.nodes[c]=ai()),f?n=u:i=u,l?r=a:o=a,s(e,t,n,r,i,o)}var u,a=-1,f=e.length;f&&isNaN(e[0].x)&&(e=e.map(li));if(arguments.length<5)if(arguments.length===3)i=r=n,n=t;else{t=n=Infinity,r=i=-Infinity;while(++a<f)u=e[a],u.x<t&&(t=u.x),u.y<n&&(n=u.y),u.x>r&&(r=u.x),u.y>i&&(i=u.y);var l=r-t,c=i-n;l>c?i=n+l:r=t+c}var h=ai();return h.add=function(e){s(h,e,t,n,r,i)},h.visit=function(e){fi(e,h,t,n,r,i)},e.forEach(h.add),h},d3.time={};var Eo=Date,So=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];ci.prototype={getDate:function(){return this._.getUTCDate()},getDay:function(){return this._.getUTCDay()},getFullYear:function(){return this._.getUTCFullYear()},getHours:function(){return this._.getUTCHours()},getMilliseconds:function(){return this._.getUTCMilliseconds()},getMinutes:function(){return this._.getUTCMinutes()},getMonth:function(){return this._.getUTCMonth()},getSeconds:function(){return this._.getUTCSeconds()},getTime:function(){return this._.getTime()},getTimezoneOffset:function(){return 0},valueOf:function(){return this._.valueOf()},setDate:function(){xo.setUTCDate.apply(this._,arguments)},setDay:function(){xo.setUTCDay.apply(this._,arguments)},setFullYear:function(){xo.setUTCFullYear.apply(this._,arguments)},setHours:function(){xo.setUTCHours.apply(this._,arguments)},setMilliseconds:function(){xo.setUTCMilliseconds.apply(this._,arguments)},setMinutes:function(){xo.setUTCMinutes.apply(this._,arguments)},setMonth:function(){xo.setUTCMonth.apply(this._,arguments)},setSeconds:function(){xo.setUTCSeconds.apply(this._,arguments)},setTime:function(){xo.setTime.apply(this._,arguments)}};var xo=Date.prototype,To="%a %b %e %H:%M:%S %Y",No="%m/%d/%y",Co="%H:%M:%S",ko=So,Lo=ko.map(hi),Ao=["January","February","March","April","May","June","July","August","September","October","November","December"],Oo=Ao.map(hi);d3.time.format=function(e){function t(t){var r=[],i=-1,s=0,o,u;while(++i<n)e.charCodeAt(i)==37&&(r.push(e.substring(s,i),(u=Ro[o=e.charAt(++i)])?u(t):o),s=i+1);return r.push(e.substring(s,i)),r.join("")}var n=e.length;return t.parse=function(t){var n={y:1900,m:0,d:1,H:0,M:0,S:0,L:0},r=pi(n,e,t,0);if(r!=t.length)return null;"p"in n&&(n.H=n.H%12+n.p*12);var i=new Eo;return i.setFullYear(n.y,n.m,n.d),i.setHours(n.H,n.M,n.S,n.L),i},t.toString=function(){return e},t};var Mo=d3.format("02d"),_o=d3.format("03d"),Do=d3.format("04d"),Po=d3.format("2d"),Ho=di(ko),Bo=di(Lo),jo=di(Ao),Fo=vi(Ao),Io=di(Oo),qo=vi(Oo),Ro={a:function(e){return Lo[e.getDay()]},A:function(e){return ko[e.getDay()]},b:function(e){return Oo[e.getMonth()]},B:function(e){return Ao[e.getMonth()]},c:d3.time.format(To),d:function(e){return Mo(e.getDate())},e:function(e){return Po(e.getDate())},H:function(e){return Mo(e.getHours())},I:function(e){return Mo(e.getHours()%12||12)},j:function(e){return _o(1+d3.time.dayOfYear(e))},L:function(e){return _o(e.getMilliseconds())},m:function(e){return Mo(e.getMonth()+1)},M:function(e){return Mo(e.getMinutes())},p:function(e){return e.getHours()>=12?"PM":"AM"},S:function(e){return Mo(e.getSeconds())},U:function(e){return Mo(d3.time.sundayOfYear(e))},w:function(e){return e.getDay()},W:function(e){return Mo(d3.time.mondayOfYear(e))},x:d3.time.format(No),X:d3.time.format(Co),y:function(e){return Mo(e.getFullYear()%100)},Y:function(e){return Do(e.getFullYear()%1e4)},Z:Di,"%":function(e){return"%"}},Uo={a:mi,A:gi,b:yi,B:bi,c:wi,d:ki,e:ki,H:Li,I:Li,L:Mi,m:Ci,M:Ai,p:_i,S:Oi,x:Ei,X:Si,y:Ti,Y:xi},zo=/^\s*\d+/,Wo=d3.map({am:0,pm:1});d3.time.format.utc=function(e){function t(e){try{Eo=ci;var t=new Eo;return t._=e,n(t)}finally{Eo=Date}}var n=d3.time.format(e);return t.parse=function(e){try{Eo=ci;var t=n.parse(e);return t&&t._}finally{Eo=Date}},t.toString=n.toString,t};var Xo=d3.time.format.utc("%Y-%m-%dT%H:%M:%S.%LZ");d3.time.format.iso=Date.prototype.toISOString?Pi:Xo,Pi.parse=function(e){var t=new Date(e);return isNaN(t)?null:t},Pi.toString=Xo.toString,d3.time.second=Hi(function(e){return new Eo(Math.floor(e/1e3)*1e3)},function(e,t){e.setTime(e.getTime()+Math.floor(t)*1e3)},function(e){return e.getSeconds()}),d3.time.seconds=d3.time.second.range,d3.time.seconds.utc=d3.time.second.utc.range,d3.time.minute=Hi(function(e){return new Eo(Math.floor(e/6e4)*6e4)},function(e,t){e.setTime(e.getTime()+Math.floor(t)*6e4)},function(e){return e.getMinutes()}),d3.time.minutes=d3.time.minute.range,d3.time.minutes.utc=d3.time.minute.utc.range,d3.time.hour=Hi(function(e){var t=e.getTimezoneOffset()/60;return new Eo((Math.floor(e/36e5-t)+t)*36e5)},function(e,t){e.setTime(e.getTime()+Math.floor(t)*36e5)},function(e){return e.getHours()}),d3.time.hours=d3.time.hour.range,d3.time.hours.utc=d3.time.hour.utc.range,d3.time.day=Hi(function(e){var t=new Eo(1970,0);return t.setFullYear(e.getFullYear(),e.getMonth(),e.getDate()),t},function(e,t){e.setDate(e.getDate()+t)},function(e){return e.getDate()-1}),d3.time.days=d3.time.day.range,d3.time.days.utc=d3.time.day.utc.range,d3.time.dayOfYear=function(e){var t=d3.time.year(e);return Math.floor((e-t-(e.getTimezoneOffset()-t.getTimezoneOffset())*6e4)/864e5)},So.forEach(function(e,t){e=e.toLowerCase(),t=7-t;var n=d3.time[e]=Hi(function(e){return(e=d3.time.day(e)).setDate(e.getDate()-(e.getDay()+t)%7),e},function(e,t){e.setDate(e.getDate()+Math.floor(t)*7)},function(e){var n=d3.time.year(e).getDay();return Math.floor((d3.time.dayOfYear(e)+(n+t)%7)/7)-(n!==t)});d3.time[e+"s"]=n.range,d3.time[e+"s"].utc=n.utc.range,d3.time[e+"OfYear"]=function(e){var n=d3.time.year(e).getDay();return Math.floor((d3.time.dayOfYear(e)+(n+t)%7)/7)}}),d3.time.week=d3.time.sunday,d3.time.weeks=d3.time.sunday.range,d3.time.weeks.utc=d3.time.sunday.utc.range,d3.time.weekOfYear=d3.time.sundayOfYear,d3.time.month=Hi(function(e){return e=d3.time.day(e),e.setDate(1),e},function(e,t){e.setMonth(e.getMonth()+t)},function(e){return e.getMonth()}),d3.time.months=d3.time.month.range,d3.time.months.utc=d3.time.month.utc.range,d3.time.year=Hi(function(e){return e=d3.time.day(e),e.setMonth(0,1),e},function(e,t){e.setFullYear(e.getFullYear()+t)},function(e){return e.getFullYear()}),d3.time.years=d3.time.year.range,d3.time.years.utc=d3.time.year.utc.range;var Vo=[1e3,5e3,15e3,3e4,6e4,3e5,9e5,18e5,36e5,108e5,216e5,432e5,864e5,1728e5,6048e5,2592e6,7776e6,31536e6],$o=[[d3.time.second,1],[d3.time.second,5],[d3.time.second,15],[d3.time.second,30],[d3.time.minute,1],[d3.time.minute,5],[d3.time.minute,15],[d3.time.minute,30],[d3.time.hour,1],[d3.time.hour,3],[d3.time.hour,6],[d3.time.hour,12],[d3.time.day,1],[d3.time.day,2],[d3.time.week,1],[d3.time.month,1],[d3.time.month,3],[d3.time.year,1]],Jo=[[d3.time.format("%Y"),function(e){return!0}],[d3.time.format("%B"),function(e){return e.getMonth()}],[d3.time.format("%b %d"),function(e){return e.getDate()!=1}],[d3.time.format("%a %d"),function(e){return e.getDay()&&e.getDate()!=1}],[d3.time.format("%I %p"),function(e){return e.getHours()}],[d3.time.format("%I:%M"),function(e){return e.getMinutes()}],[d3.time.format(":%S"),function(e){return e.getSeconds()}],[d3.time.format(".%L"),function(e){return e.getMilliseconds()}]],Ko=d3.scale.linear(),Qo=qi(Jo);$o.year=function(e,t){return Ko.domain(e.map(Ui)).ticks(t).map(Ri)},d3.time.scale=function(){return ji(d3.scale.linear(),$o,Qo)};var Go=$o.map(function(e){return[e[0].utc,e[1]]}),Yo=[[d3.time.format.utc("%Y"),function(e){return!0}],[d3.time.format.utc("%B"),function(e){return e.getUTCMonth()}],[d3.time.format.utc("%b %d"),function(e){return e.getUTCDate()!=1}],[d3.time.format.utc("%a %d"),function(e){return e.getUTCDay()&&e.getUTCDate()!=1}],[d3.time.format.utc("%I %p"),function(e){return e.getUTCHours()}],[d3.time.format.utc("%I:%M"),function(e){return e.getUTCMinutes()}],[d3.time.format.utc(":%S"),function(e){return e.getUTCSeconds()}],[d3.time.format.utc(".%L"),function(e){return e.getUTCMilliseconds()}]],Zo=qi(Yo);Go.year=function(e,t){return Ko.domain(e.map(Wi)).ticks(t).map(zi)},d3.time.scale.utc=function(){return ji(d3.scale.linear(),Go,Zo)}})();
/*!
 xCharts v0.3.0 Copyright (c) 2012, tenXer, Inc. All Rights Reserved.
 @license MIT license. http://github.com/tenXer/xcharts for details
 */

(function(){var xChart,_vis={},_scales={},_visutils={};(function(){var n=this,t=n._,r={},e=Array.prototype,u=Object.prototype,i=Function.prototype,a=e.push,o=e.slice,c=e.concat,l=u.toString,f=u.hasOwnProperty,s=e.forEach,p=e.map,v=e.reduce,h=e.reduceRight,g=e.filter,d=e.every,m=e.some,y=e.indexOf,b=e.lastIndexOf,x=Array.isArray,_=Object.keys,j=i.bind,w=function(n){return n instanceof w?n:this instanceof w?(this._wrapped=n,void 0):new w(n)};"undefined"!=typeof exports?("undefined"!=typeof module&&module.exports&&(exports=module.exports=w),exports._=w):n._=w,w.VERSION="1.4.3";var A=w.each=w.forEach=function(n,t,e){if(null!=n)if(s&&n.forEach===s)n.forEach(t,e);else if(n.length===+n.length){for(var u=0,i=n.length;i>u;u++)if(t.call(e,n[u],u,n)===r)return}else for(var a in n)if(w.has(n,a)&&t.call(e,n[a],a,n)===r)return};w.map=w.collect=function(n,t,r){var e=[];return null==n?e:p&&n.map===p?n.map(t,r):(A(n,function(n,u,i){e[e.length]=t.call(r,n,u,i)}),e)};var O="Reduce of empty array with no initial value";w.reduce=w.foldl=w.inject=function(n,t,r,e){var u=arguments.length>2;if(null==n&&(n=[]),v&&n.reduce===v)return e&&(t=w.bind(t,e)),u?n.reduce(t,r):n.reduce(t);if(A(n,function(n,i,a){u?r=t.call(e,r,n,i,a):(r=n,u=!0)}),!u)throw new TypeError(O);return r},w.reduceRight=w.foldr=function(n,t,r,e){var u=arguments.length>2;if(null==n&&(n=[]),h&&n.reduceRight===h)return e&&(t=w.bind(t,e)),u?n.reduceRight(t,r):n.reduceRight(t);var i=n.length;if(i!==+i){var a=w.keys(n);i=a.length}if(A(n,function(o,c,l){c=a?a[--i]:--i,u?r=t.call(e,r,n[c],c,l):(r=n[c],u=!0)}),!u)throw new TypeError(O);return r},w.find=w.detect=function(n,t,r){var e;return E(n,function(n,u,i){return t.call(r,n,u,i)?(e=n,!0):void 0}),e},w.filter=w.select=function(n,t,r){var e=[];return null==n?e:g&&n.filter===g?n.filter(t,r):(A(n,function(n,u,i){t.call(r,n,u,i)&&(e[e.length]=n)}),e)},w.reject=function(n,t,r){return w.filter(n,function(n,e,u){return!t.call(r,n,e,u)},r)},w.every=w.all=function(n,t,e){t||(t=w.identity);var u=!0;return null==n?u:d&&n.every===d?n.every(t,e):(A(n,function(n,i,a){return(u=u&&t.call(e,n,i,a))?void 0:r}),!!u)};var E=w.some=w.any=function(n,t,e){t||(t=w.identity);var u=!1;return null==n?u:m&&n.some===m?n.some(t,e):(A(n,function(n,i,a){return u||(u=t.call(e,n,i,a))?r:void 0}),!!u)};w.contains=w.include=function(n,t){return null==n?!1:y&&n.indexOf===y?-1!=n.indexOf(t):E(n,function(n){return n===t})},w.invoke=function(n,t){var r=o.call(arguments,2);return w.map(n,function(n){return(w.isFunction(t)?t:n[t]).apply(n,r)})},w.pluck=function(n,t){return w.map(n,function(n){return n[t]})},w.where=function(n,t){return w.isEmpty(t)?[]:w.filter(n,function(n){for(var r in t)if(t[r]!==n[r])return!1;return!0})},w.max=function(n,t,r){if(!t&&w.isArray(n)&&n[0]===+n[0]&&65535>n.length)return Math.max.apply(Math,n);if(!t&&w.isEmpty(n))return-1/0;var e={computed:-1/0,value:-1/0};return A(n,function(n,u,i){var a=t?t.call(r,n,u,i):n;a>=e.computed&&(e={value:n,computed:a})}),e.value},w.min=function(n,t,r){if(!t&&w.isArray(n)&&n[0]===+n[0]&&65535>n.length)return Math.min.apply(Math,n);if(!t&&w.isEmpty(n))return 1/0;var e={computed:1/0,value:1/0};return A(n,function(n,u,i){var a=t?t.call(r,n,u,i):n;e.computed>a&&(e={value:n,computed:a})}),e.value},w.shuffle=function(n){var t,r=0,e=[];return A(n,function(n){t=w.random(r++),e[r-1]=e[t],e[t]=n}),e};var F=function(n){return w.isFunction(n)?n:function(t){return t[n]}};w.sortBy=function(n,t,r){var e=F(t);return w.pluck(w.map(n,function(n,t,u){return{value:n,index:t,criteria:e.call(r,n,t,u)}}).sort(function(n,t){var r=n.criteria,e=t.criteria;if(r!==e){if(r>e||void 0===r)return 1;if(e>r||void 0===e)return-1}return n.index<t.index?-1:1}),"value")};var k=function(n,t,r,e){var u={},i=F(t||w.identity);return A(n,function(t,a){var o=i.call(r,t,a,n);e(u,o,t)}),u};w.groupBy=function(n,t,r){return k(n,t,r,function(n,t,r){(w.has(n,t)?n[t]:n[t]=[]).push(r)})},w.countBy=function(n,t,r){return k(n,t,r,function(n,t){w.has(n,t)||(n[t]=0),n[t]++})},w.sortedIndex=function(n,t,r,e){r=null==r?w.identity:F(r);for(var u=r.call(e,t),i=0,a=n.length;a>i;){var o=i+a>>>1;u>r.call(e,n[o])?i=o+1:a=o}return i},w.toArray=function(n){return n?w.isArray(n)?o.call(n):n.length===+n.length?w.map(n,w.identity):w.values(n):[]},w.size=function(n){return null==n?0:n.length===+n.length?n.length:w.keys(n).length},w.first=w.head=w.take=function(n,t,r){return null==n?void 0:null==t||r?n[0]:o.call(n,0,t)},w.initial=function(n,t,r){return o.call(n,0,n.length-(null==t||r?1:t))},w.last=function(n,t,r){return null==n?void 0:null==t||r?n[n.length-1]:o.call(n,Math.max(n.length-t,0))},w.rest=w.tail=w.drop=function(n,t,r){return o.call(n,null==t||r?1:t)},w.compact=function(n){return w.filter(n,w.identity)};var R=function(n,t,r){return A(n,function(n){w.isArray(n)?t?a.apply(r,n):R(n,t,r):r.push(n)}),r};w.flatten=function(n,t){return R(n,t,[])},w.without=function(n){return w.difference(n,o.call(arguments,1))},w.uniq=w.unique=function(n,t,r,e){w.isFunction(t)&&(e=r,r=t,t=!1);var u=r?w.map(n,r,e):n,i=[],a=[];return A(u,function(r,e){(t?e&&a[a.length-1]===r:w.contains(a,r))||(a.push(r),i.push(n[e]))}),i},w.union=function(){return w.uniq(c.apply(e,arguments))},w.intersection=function(n){var t=o.call(arguments,1);return w.filter(w.uniq(n),function(n){return w.every(t,function(t){return w.indexOf(t,n)>=0})})},w.difference=function(n){var t=c.apply(e,o.call(arguments,1));return w.filter(n,function(n){return!w.contains(t,n)})},w.zip=function(){for(var n=o.call(arguments),t=w.max(w.pluck(n,"length")),r=Array(t),e=0;t>e;e++)r[e]=w.pluck(n,""+e);return r},w.object=function(n,t){if(null==n)return{};for(var r={},e=0,u=n.length;u>e;e++)t?r[n[e]]=t[e]:r[n[e][0]]=n[e][1];return r},w.indexOf=function(n,t,r){if(null==n)return-1;var e=0,u=n.length;if(r){if("number"!=typeof r)return e=w.sortedIndex(n,t),n[e]===t?e:-1;e=0>r?Math.max(0,u+r):r}if(y&&n.indexOf===y)return n.indexOf(t,r);for(;u>e;e++)if(n[e]===t)return e;return-1},w.lastIndexOf=function(n,t,r){if(null==n)return-1;var e=null!=r;if(b&&n.lastIndexOf===b)return e?n.lastIndexOf(t,r):n.lastIndexOf(t);for(var u=e?r:n.length;u--;)if(n[u]===t)return u;return-1},w.range=function(n,t,r){1>=arguments.length&&(t=n||0,n=0),r=arguments[2]||1;for(var e=Math.max(Math.ceil((t-n)/r),0),u=0,i=Array(e);e>u;)i[u++]=n,n+=r;return i};var I=function(){};w.bind=function(n,t){var r,e;if(n.bind===j&&j)return j.apply(n,o.call(arguments,1));if(!w.isFunction(n))throw new TypeError;return r=o.call(arguments,2),e=function(){if(!(this instanceof e))return n.apply(t,r.concat(o.call(arguments)));I.prototype=n.prototype;var u=new I;I.prototype=null;var i=n.apply(u,r.concat(o.call(arguments)));return Object(i)===i?i:u}},w.bindAll=function(n){var t=o.call(arguments,1);return 0==t.length&&(t=w.functions(n)),A(t,function(t){n[t]=w.bind(n[t],n)}),n},w.memoize=function(n,t){var r={};return t||(t=w.identity),function(){var e=t.apply(this,arguments);return w.has(r,e)?r[e]:r[e]=n.apply(this,arguments)}},w.delay=function(n,t){var r=o.call(arguments,2);return setTimeout(function(){return n.apply(null,r)},t)},w.defer=function(n){return w.delay.apply(w,[n,1].concat(o.call(arguments,1)))},w.throttle=function(n,t){var r,e,u,i,a=0,o=function(){a=new Date,u=null,i=n.apply(r,e)};return function(){var c=new Date,l=t-(c-a);return r=this,e=arguments,0>=l?(clearTimeout(u),u=null,a=c,i=n.apply(r,e)):u||(u=setTimeout(o,l)),i}},w.debounce=function(n,t,r){var e,u;return function(){var i=this,a=arguments,o=function(){e=null,r||(u=n.apply(i,a))},c=r&&!e;return clearTimeout(e),e=setTimeout(o,t),c&&(u=n.apply(i,a)),u}},w.once=function(n){var t,r=!1;return function(){return r?t:(r=!0,t=n.apply(this,arguments),n=null,t)}},w.wrap=function(n,t){return function(){var r=[n];return a.apply(r,arguments),t.apply(this,r)}},w.compose=function(){var n=arguments;return function(){for(var t=arguments,r=n.length-1;r>=0;r--)t=[n[r].apply(this,t)];return t[0]}},w.after=function(n,t){return 0>=n?t():function(){return 1>--n?t.apply(this,arguments):void 0}},w.keys=_||function(n){if(n!==Object(n))throw new TypeError("Invalid object");var t=[];for(var r in n)w.has(n,r)&&(t[t.length]=r);return t},w.values=function(n){var t=[];for(var r in n)w.has(n,r)&&t.push(n[r]);return t},w.pairs=function(n){var t=[];for(var r in n)w.has(n,r)&&t.push([r,n[r]]);return t},w.invert=function(n){var t={};for(var r in n)w.has(n,r)&&(t[n[r]]=r);return t},w.functions=w.methods=function(n){var t=[];for(var r in n)w.isFunction(n[r])&&t.push(r);return t.sort()},w.extend=function(n){return A(o.call(arguments,1),function(t){if(t)for(var r in t)n[r]=t[r]}),n},w.pick=function(n){var t={},r=c.apply(e,o.call(arguments,1));return A(r,function(r){r in n&&(t[r]=n[r])}),t},w.omit=function(n){var t={},r=c.apply(e,o.call(arguments,1));for(var u in n)w.contains(r,u)||(t[u]=n[u]);return t},w.defaults=function(n){return A(o.call(arguments,1),function(t){if(t)for(var r in t)null==n[r]&&(n[r]=t[r])}),n},w.clone=function(n){return w.isObject(n)?w.isArray(n)?n.slice():w.extend({},n):n},w.tap=function(n,t){return t(n),n};var S=function(n,t,r,e){if(n===t)return 0!==n||1/n==1/t;if(null==n||null==t)return n===t;n instanceof w&&(n=n._wrapped),t instanceof w&&(t=t._wrapped);var u=l.call(n);if(u!=l.call(t))return!1;switch(u){case"[object String]":return n==t+"";case"[object Number]":return n!=+n?t!=+t:0==n?1/n==1/t:n==+t;case"[object Date]":case"[object Boolean]":return+n==+t;case"[object RegExp]":return n.source==t.source&&n.global==t.global&&n.multiline==t.multiline&&n.ignoreCase==t.ignoreCase}if("object"!=typeof n||"object"!=typeof t)return!1;for(var i=r.length;i--;)if(r[i]==n)return e[i]==t;r.push(n),e.push(t);var a=0,o=!0;if("[object Array]"==u){if(a=n.length,o=a==t.length)for(;a--&&(o=S(n[a],t[a],r,e)););}else{var c=n.constructor,f=t.constructor;if(c!==f&&!(w.isFunction(c)&&c instanceof c&&w.isFunction(f)&&f instanceof f))return!1;for(var s in n)if(w.has(n,s)&&(a++,!(o=w.has(t,s)&&S(n[s],t[s],r,e))))break;if(o){for(s in t)if(w.has(t,s)&&!a--)break;o=!a}}return r.pop(),e.pop(),o};w.isEqual=function(n,t){return S(n,t,[],[])},w.isEmpty=function(n){if(null==n)return!0;if(w.isArray(n)||w.isString(n))return 0===n.length;for(var t in n)if(w.has(n,t))return!1;return!0},w.isElement=function(n){return!(!n||1!==n.nodeType)},w.isArray=x||function(n){return"[object Array]"==l.call(n)},w.isObject=function(n){return n===Object(n)},A(["Arguments","Function","String","Number","Date","RegExp"],function(n){w["is"+n]=function(t){return l.call(t)=="[object "+n+"]"}}),w.isArguments(arguments)||(w.isArguments=function(n){return!(!n||!w.has(n,"callee"))}),w.isFunction=function(n){return"function"==typeof n},w.isFinite=function(n){return isFinite(n)&&!isNaN(parseFloat(n))},w.isNaN=function(n){return w.isNumber(n)&&n!=+n},w.isBoolean=function(n){return n===!0||n===!1||"[object Boolean]"==l.call(n)},w.isNull=function(n){return null===n},w.isUndefined=function(n){return void 0===n},w.has=function(n,t){return f.call(n,t)},w.noConflict=function(){return n._=t,this},w.identity=function(n){return n},w.times=function(n,t,r){for(var e=Array(n),u=0;n>u;u++)e[u]=t.call(r,u);return e},w.random=function(n,t){return null==t&&(t=n,n=0),n+(0|Math.random()*(t-n+1))};var T={escape:{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#x27;","/":"&#x2F;"}};T.unescape=w.invert(T.escape);var M={escape:RegExp("["+w.keys(T.escape).join("")+"]","g"),unescape:RegExp("("+w.keys(T.unescape).join("|")+")","g")};w.each(["escape","unescape"],function(n){w[n]=function(t){return null==t?"":(""+t).replace(M[n],function(t){return T[n][t]})}}),w.result=function(n,t){if(null==n)return null;var r=n[t];return w.isFunction(r)?r.call(n):r},w.mixin=function(n){A(w.functions(n),function(t){var r=w[t]=n[t];w.prototype[t]=function(){var n=[this._wrapped];return a.apply(n,arguments),z.call(this,r.apply(w,n))}})};var N=0;w.uniqueId=function(n){var t=""+ ++N;return n?n+t:t},w.templateSettings={evaluate:/<%([\s\S]+?)%>/g,interpolate:/<%=([\s\S]+?)%>/g,escape:/<%-([\s\S]+?)%>/g};var q=/(.)^/,B={"'":"'","\\":"\\","\r":"r","\n":"n","	":"t","\u2028":"u2028","\u2029":"u2029"},D=/\\|'|\r|\n|\t|\u2028|\u2029/g;w.template=function(n,t,r){r=w.defaults({},r,w.templateSettings);var e=RegExp([(r.escape||q).source,(r.interpolate||q).source,(r.evaluate||q).source].join("|")+"|$","g"),u=0,i="__p+='";n.replace(e,function(t,r,e,a,o){return i+=n.slice(u,o).replace(D,function(n){return"\\"+B[n]}),r&&(i+="'+\n((__t=("+r+"))==null?'':_.escape(__t))+\n'"),e&&(i+="'+\n((__t=("+e+"))==null?'':__t)+\n'"),a&&(i+="';\n"+a+"\n__p+='"),u=o+t.length,t}),i+="';\n",r.variable||(i="with(obj||{}){\n"+i+"}\n"),i="var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};\n"+i+"return __p;\n";try{var a=Function(r.variable||"obj","_",i)}catch(o){throw o.source=i,o}if(t)return a(t,w);var c=function(n){return a.call(this,n,w)};return c.source="function("+(r.variable||"obj")+"){\n"+i+"}",c},w.chain=function(n){return w(n).chain()};var z=function(n){return this._chain?w(n).chain():n};w.mixin(w),A(["pop","push","reverse","shift","sort","splice","unshift"],function(n){var t=e[n];w.prototype[n]=function(){var r=this._wrapped;return t.apply(r,arguments),"shift"!=n&&"splice"!=n||0!==r.length||delete r[0],z.call(this,r)}}),A(["concat","join","slice"],function(n){var t=e[n];w.prototype[n]=function(){return z.call(this,t.apply(this._wrapped,arguments))}}),w.extend(w.prototype,{chain:function(){return this._chain=!0,this},value:function(){return this._wrapped}})}).call(this);function getInsertionPoint(zIndex){return _.chain(_.range(zIndex,10)).reverse().map(function(z){return'g[data-index="'+z+'"]'}).value().join(", ")}function colorClass(el,i){var c=el.getAttribute("class");return(c!==null?c.replace(/color\d+/g,""):"")+" color"+i}_visutils={getInsertionPoint:getInsertionPoint,colorClass:colorClass};var local=this,defaultSpacing=.25;function _getDomain(data,axis){return _.chain(data).pluck("data").flatten().pluck(axis).uniq().filter(function(d){return d!==undefined&&d!==null}).value().sort(d3.ascending)}_scales.ordinal=function(data,axis,bounds,extents){var domain=_getDomain(data,axis);return d3.scale.ordinal().domain(domain).rangeRoundBands(bounds,defaultSpacing)};_scales.linear=function(data,axis,bounds,extents){return d3.scale.linear().domain(extents).nice().rangeRound(bounds)};_scales.exponential=function(data,axis,bounds,extents){return d3.scale.pow().exponent(.65).domain(extents).nice().rangeRound(bounds)};_scales.time=function(data,axis,bounds,extents){return d3.time.scale().domain(_.map(extents,function(d){return new Date(d)})).range(bounds)};function _extendDomain(domain,axis){var min=domain[0],max=domain[1],diff,e;if(min===max){e=Math.max(Math.round(min/10),4);min-=e;max+=e}diff=max-min;min=min?min-diff/10:min;min=domain[0]>0?Math.max(min,0):min;max=max?max+diff/10:max;max=domain[1]<0?Math.min(max,0):max;return[min,max]}function _getExtents(options,data,xType,yType){var extents,nData=_.chain(data).pluck("data").flatten().value();extents={x:d3.extent(nData,function(d){return d.x}),y:d3.extent(nData,function(d){return d.y})};_.each([xType,yType],function(type,i){var axis=i?"y":"x",extended;extents[axis]=d3.extent(nData,function(d){return d[axis]});if(type==="ordinal"){return}_.each([axis+"Min",axis+"Max"],function(minMax,i){if(type!=="time"){extended=_extendDomain(extents[axis])}if(options.hasOwnProperty(minMax)&&options[minMax]!==null){extents[axis][i]=options[minMax]}else if(type!=="time"){extents[axis][i]=extended[i]}})});return extents}_scales.xy=function(self,data,xType,yType){var o=self._options,extents=_getExtents(o,data,xType,yType),scales={},horiz=[o.axisPaddingLeft,self._width],vert=[self._height,o.axisPaddingTop],xScale,yScale;_.each([xType,yType],function(type,i){var axis=i===0?"x":"y",bounds=i===0?horiz:vert,fn=xChart.getScale(type);scales[axis]=fn(data,axis,bounds,extents[axis])});return scales};(function(){var zIndex=2,selector="g.bar",insertBefore=_visutils.getInsertionPoint(zIndex);function postUpdateScale(self,scaleData,mainData,compData){self.xScale2=d3.scale.ordinal().domain(d3.range(0,mainData.length)).rangeRoundBands([0,self.xScale.rangeBand()],.08)}function enter(self,storage,className,data,callbacks){var barGroups,bars,yZero=self.yZero;barGroups=self._g.selectAll(selector+className).data(data,function(d){return d.className});barGroups.enter().insert("g",insertBefore).attr("data-index",zIndex).style("opacity",0).attr("class",function(d,i){var cl=_.uniq((className+d.className).split(".")).join(" ");return cl+" bar "+_visutils.colorClass(this,i)}).attr("transform",function(d,i){return"translate("+self.xScale2(i)+",0)"});bars=barGroups.selectAll("rect").data(function(d){return d.data},function(d){return d.x});bars.enter().append("rect").attr("width",0).attr("rx",3).attr("ry",3).attr("x",function(d){return self.xScale(d.x)+self.xScale2.rangeBand()/2}).attr("height",function(d){return Math.abs(yZero-self.yScale(d.y))}).attr("y",function(d){return d.y<0?yZero:self.yScale(d.y)}).on("mouseover",callbacks.mouseover).on("mouseout",callbacks.mouseout).on("click",callbacks.click);storage.barGroups=barGroups;storage.bars=bars}function update(self,storage,timing){var yZero=self.yZero;storage.barGroups.attr("class",function(d,i){return _visutils.colorClass(this,i)}).transition().duration(timing).style("opacity",1).attr("transform",function(d,i){return"translate("+self.xScale2(i)+",0)"});storage.bars.transition().duration(timing).attr("width",self.xScale2.rangeBand()).attr("x",function(d){return self.xScale(d.x)}).attr("height",function(d){return Math.abs(yZero-self.yScale(d.y))}).attr("y",function(d){return d.y<0?yZero:self.yScale(d.y)})}function exit(self,storage,timing){storage.bars.exit().transition().duration(timing).attr("width",0).remove();storage.barGroups.exit().transition().duration(timing).style("opacity",0).remove()}function destroy(self,storage,timing){var band=self.xScale2?self.xScale2.rangeBand()/2:0;delete self.xScale2;storage.bars.transition().duration(timing).attr("width",0).attr("x",function(d){return self.xScale(d.x)+band})}_vis.bar={postUpdateScale:postUpdateScale,enter:enter,update:update,exit:exit,destroy:destroy}})();(function(){var zIndex=3,selector="g.line",insertBefore=_visutils.getInsertionPoint(zIndex);function enter(self,storage,className,data,callbacks){var inter=self._options.interpolation,x=function(d,i){if(!self.xScale2&&!self.xScale.rangeBand){return self.xScale(d.x)}return self.xScale(d.x)+self.xScale.rangeBand()/2},y=function(d){return self.yScale(d.y)},line=d3.svg.line().x(x).interpolate(inter),area=d3.svg.area().x(x).y1(self.yZero).interpolate(inter),container,fills,paths;function datum(d){return[d.data]}container=self._g.selectAll(selector+className).data(data,function(d){return d.className});container.enter().insert("g",insertBefore).attr("data-index",zIndex).attr("class",function(d,i){var cl=_.uniq((className+d.className).split(".")).join(" ");return cl+" line "+_visutils.colorClass(this,i)});fills=container.selectAll("path.fill").data(datum);fills.enter().append("path").attr("class","fill").style("opacity",0).attr("d",area.y0(y));paths=container.selectAll("path.line").data(datum);paths.enter().append("path").attr("class","line").style("opacity",0).attr("d",line.y(y));storage.lineContainers=container;storage.lineFills=fills;storage.linePaths=paths;storage.lineX=x;storage.lineY=y;storage.lineA=area;storage.line=line}function update(self,storage,timing){storage.lineContainers.attr("class",function(d,i){return _visutils.colorClass(this,i)});storage.lineFills.transition().duration(timing).style("opacity",1).attr("d",storage.lineA.y0(storage.lineY));storage.linePaths.transition().duration(timing).style("opacity",1).attr("d",storage.line.y(storage.lineY))}function exit(self,storage){storage.linePaths.exit().style("opacity",0).remove();storage.lineFills.exit().style("opacity",0).remove();storage.lineContainers.exit().remove()}function destroy(self,storage,timing){storage.linePaths.transition().duration(timing).style("opacity",0);storage.lineFills.transition().duration(timing).style("opacity",0)}_vis.line={enter:enter,update:update,exit:exit,destroy:destroy}})();(function(){var line=_vis.line;function enter(self,storage,className,data,callbacks){var circles;line.enter(self,storage,className,data,callbacks);circles=storage.lineContainers.selectAll("circle").data(function(d){return d.data},function(d){return d.x});circles.enter().append("circle").style("opacity",0).attr("cx",storage.lineX).attr("cy",storage.lineY).attr("r",5).on("mouseover",callbacks.mouseover).on("mouseout",callbacks.mouseout).on("click",callbacks.click);storage.lineCircles=circles}function update(self,storage,timing){line.update.apply(null,_.toArray(arguments));storage.lineCircles.transition().duration(timing).style("opacity",1).attr("cx",storage.lineX).attr("cy",storage.lineY)}function exit(self,storage){storage.lineCircles.exit().remove();line.exit.apply(null,_.toArray(arguments))}function destroy(self,storage,timing){line.destroy.apply(null,_.toArray(arguments));if(!storage.lineCircles){return}storage.lineCircles.transition().duration(timing).style("opacity",0)}_vis["line-dotted"]={enter:enter,update:update,exit:exit,destroy:destroy}})();(function(){var line=_vis["line-dotted"];function enter(self,storage,className,data,callbacks){line.enter(self,storage,className,data,callbacks)}function _accumulate_data(data){function reduce(memo,num){return memo+num.y}var nData=_.map(data,function(set){var i=set.data.length,d=_.clone(set.data);set=_.clone(set);while(i){i-=1;d[i]=_.clone(set.data[i]);d[i].y0=set.data[i].y;d[i].y=_.reduce(_.first(set.data,i),reduce,set.data[i].y)}return _.extend(set,{data:d})});return nData}function _resetData(self){if(!self.hasOwnProperty("cumulativeOMainData")){return}self._mainData=self.cumulativeOMainData;delete self.cumulativeOMainData;self._compData=self.cumulativeOCompData;delete self.cumulativeOCompData}function preUpdateScale(self,data){_resetData(self);self.cumulativeOMainData=self._mainData;self._mainData=_accumulate_data(self._mainData);self.cumulativeOCompData=self._compData;self._compData=_accumulate_data(self._compData)}function destroy(self,storage,timing){_resetData(self);line.destroy.apply(null,_.toArray(arguments))}_vis.cumulative={preUpdateScale:preUpdateScale,enter:enter,update:line.update,exit:line.exit,destroy:destroy}})();var emptyData=[[]],defaults={mouseover:function(data,i){},mouseout:function(data,i){},click:function(data,i){},axisPaddingTop:0,axisPaddingRight:0,axisPaddingBottom:5,axisPaddingLeft:20,paddingTop:0,paddingRight:0,paddingBottom:20,paddingLeft:60,tickHintX:10,tickFormatX:function(x){return x},tickHintY:10,tickFormatY:function(y){return y},xMin:null,xMax:null,yMin:null,yMax:null,dataFormatX:function(x){return x},dataFormatY:function(y){return y},unsupported:function(selector){d3.select(selector).text("SVG is not supported on your browser")},empty:function(self,selector,d){},notempty:function(self,selector){},timing:750,interpolation:"monotone",sortX:function(a,b){return!a.x&&!b.x?0:a.x<b.x?-1:1}};function svgEnabled(){var d=document;return!!d.createElementNS&&!!d.createElementNS("http://www.w3.org/2000/svg","svg").createSVGRect}function xChart(type,data,selector,options){var self=this,resizeLock;self._options=options=_.defaults(options||{},defaults);if(svgEnabled()===false){return options.unsupported(selector)}self._selector=selector;self._container=d3.select(selector);self._drawSvg();self._mainStorage={};self._compStorage={};data=_.clone(data);if(type&&!data.type){data.type=type}self.setData(data);d3.select(window).on("resize.for."+selector,function(){if(resizeLock){clearTimeout(resizeLock)}resizeLock=setTimeout(function(){resizeLock=null;self._resize()},500)})}xChart.setVis=function(type,vis){if(_vis.hasOwnProperty(type)){throw'Cannot override vis type "'+type+'".'}_vis[type]=vis};xChart.getVis=function(type){if(!_vis.hasOwnProperty(type)){throw'Vis type "'+type+'" does not exist.'}return _.clone(_vis[type])};xChart.setScale=function(name,fn){if(_scales.hasOwnProperty(name)){throw'Scale type "'+name+'" already exists.'}_scales[name]=fn};xChart.getScale=function(name){if(!_scales.hasOwnProperty(name)){throw'Scale type "'+name+'" does not exist.'}return _scales[name]};xChart.visutils=_visutils;_.defaults(xChart.prototype,{setType:function(type,skipDraw){var self=this;if(self._type&&type===self._type){return}if(!_vis.hasOwnProperty(type)){throw'Vis type "'+type+'" is not defined.'}if(self._type){self._destroy(self._vis,self._mainStorage)}self._type=type;self._vis=_vis[type];if(!skipDraw){self._draw()}},setData:function(data){var self=this,o=self._options,nData=_.clone(data);if(!data.hasOwnProperty("main")){throw'No "main" key found in given chart data.'}switch(data.type){case"bar":data.xScale="ordinal";break;case undefined:data.type=self._type;break}o.xMin=isNaN(parseInt(data.xMin,10))?o.xMin:data.xMin;o.xMax=isNaN(parseInt(data.xMax,10))?o.xMax:data.xMax;o.yMin=isNaN(parseInt(data.yMin,10))?o.yMin:data.yMin;o.yMax=isNaN(parseInt(data.yMax,10))?o.yMax:data.yMax;if(self._vis){self._destroy(self._vis,self._mainStorage)}self.setType(data.type,true);function _mapData(set){var d=_.map(_.clone(set.data),function(p){var np=_.clone(p);if(p.hasOwnProperty("x")){np.x=o.dataFormatX(p.x)}if(p.hasOwnProperty("y")){np.y=o.dataFormatY(p.y)}return np}).sort(o.sortX);return _.extend(_.clone(set),{data:d})}nData.main=_.map(nData.main,_mapData);self._mainData=nData.main;self._xScaleType=nData.xScale;self._yScaleType=nData.yScale;if(nData.hasOwnProperty("comp")){nData.comp=_.map(nData.comp,_mapData);self._compData=nData.comp}else{self._compData=[]}self._draw()},setScale:function(axis,type){var self=this;switch(axis){case"x":self._xScaleType=type;break;case"y":self._yScaleType=type;break;default:throw'Cannot change scale of unknown axis "'+axis+'".'}self._draw()},_drawSvg:function(){var self=this,c=self._container,options=self._options,width=parseInt(c.style("width").replace("px",""),10),height=parseInt(c.style("height").replace("px",""),10),svg,g,gScale;svg=c.selectAll("svg").data(emptyData);svg.enter().append("svg").attr("height",height).attr("width",width).attr("class","xchart");svg.transition().attr("width",width).attr("height",height);g=svg.selectAll("g").data(emptyData);g.enter().append("g").attr("transform","translate("+options.paddingLeft+","+options.paddingTop+")");gScale=g.selectAll("g.scale").data(emptyData);gScale.enter().append("g").attr("class","scale");self._svg=svg;self._g=g;self._gScale=gScale;self._height=height-options.paddingTop-options.paddingBottom-options.axisPaddingTop-options.axisPaddingBottom;self._width=width-options.paddingLeft-options.paddingRight-options.axisPaddingLeft-options.axisPaddingRight},_resize:function(event){var self=this;self._drawSvg();self._draw()},_drawAxes:function(){if(this._noData){return}var self=this,o=self._options,t=self._gScale.transition().duration(o.timing),xTicks=o.tickHintX,yTicks=o.tickHintY,bottom=self._height+o.axisPaddingTop+o.axisPaddingBottom,zeroLine=d3.svg.line().x(function(d){return d}),zLine,zLinePath,xAxis,xRules,yAxis,yRules,labels;xRules=d3.svg.axis().scale(self.xScale).ticks(xTicks).tickSize(-self._height).tickFormat(o.tickFormatX).orient("bottom");xAxis=self._gScale.selectAll("g.axisX").data(emptyData);xAxis.enter().append("g").attr("class","axis axisX").attr("transform","translate(0,"+bottom+")");xAxis.call(xRules);labels=self._gScale.selectAll(".axisX g")[0];if(labels.length>self._width/80){labels.sort(function(a,b){var r=/translate\(([^,)]+)/;a=a.getAttribute("transform").match(r);b=b.getAttribute("transform").match(r);return parseFloat(a[1],10)-parseFloat(b[1],10)});d3.selectAll(labels).filter(function(d,i){return i%(Math.ceil(labels.length/xTicks)+1)}).remove()}yRules=d3.svg.axis().scale(self.yScale).ticks(yTicks).tickSize(-self._width-o.axisPaddingRight-o.axisPaddingLeft).tickFormat(o.tickFormatY).orient("left");yAxis=self._gScale.selectAll("g.axisY").data(emptyData);yAxis.enter().append("g").attr("class","axis axisY").attr("transform","translate(0,0)");t.selectAll("g.axisY").call(yRules);zLine=self._gScale.selectAll("g.axisZero").data([[]]);zLine.enter().append("g").attr("class","axisZero");zLinePath=zLine.selectAll("line").data([[]]);zLinePath.enter().append("line").attr("x1",0).attr("x2",self._width+o.axisPaddingLeft+o.axisPaddingRight).attr("y1",self.yZero).attr("y2",self.yZero);zLinePath.transition().duration(o.timing).attr("y1",self.yZero).attr("y2",self.yZero)},_updateScale:function(){var self=this,_unionData=function(){return _.union(self._mainData,self._compData)},scaleData=_unionData(),vis=self._vis,scale,min;delete self.xScale;delete self.yScale;delete self.yZero;if(vis.hasOwnProperty("preUpdateScale")){vis.preUpdateScale(self,scaleData,self._mainData,self._compData)}scaleData=_unionData();scale=_scales.xy(self,scaleData,self._xScaleType,self._yScaleType);self.xScale=scale.x;self.yScale=scale.y;min=self.yScale.domain()[0];self.yZero=min>0?self.yScale(min):self.yScale(0);if(vis.hasOwnProperty("postUpdateScale")){vis.postUpdateScale(self,scaleData,self._mainData,self._compData)}},_enter:function(vis,storage,data,className){var self=this,callbacks={click:self._options.click,mouseover:self._options.mouseover,mouseout:self._options.mouseout};self._checkVisMethod(vis,"enter");vis.enter(self,storage,className,data,callbacks)},_update:function(vis,storage){var self=this;self._checkVisMethod(vis,"update");vis.update(self,storage,self._options.timing)},_exit:function(vis,storage){var self=this;self._checkVisMethod(vis,"exit");vis.exit(self,storage,self._options.timing)},_destroy:function(vis,storage){var self=this;self._checkVisMethod(vis,"destroy");try{vis.destroy(self,storage,self._options.timing)}catch(e){}},_draw:function(){var self=this,o=self._options,comp,compKeys;self._noData=_.flatten(_.pluck(self._mainData,"data").concat(_.pluck(self._compData,"data"))).length===0;self._updateScale();self._drawAxes();self._enter(self._vis,self._mainStorage,self._mainData,".main");self._exit(self._vis,self._mainStorage);self._update(self._vis,self._mainStorage);comp=_.chain(self._compData).groupBy(function(d){return d.type});compKeys=comp.keys();_.each(self._compStorage,function(d,key){if(-1===compKeys.indexOf(key).value()){var vis=_vis[key];self._enter(vis,d,[],".comp."+key.replace(/\W+/g,""));self._exit(vis,d)}});comp.each(function(d,key){var vis=_vis[key],storage;if(!self._compStorage.hasOwnProperty(key)){self._compStorage[key]={}}storage=self._compStorage[key];self._enter(vis,storage,d,".comp."+key.replace(/\W+/g,""));self._exit(vis,storage);self._update(vis,storage)});if(self._noData){o.empty(self,self._selector,self._mainData)}else{o.notempty(self,self._selector)}},_checkVisMethod:function(vis,method){var self=this;if(!vis[method]){throw'Required method "'+method+'" not found on vis type "'+self._type+'".'}}});if(typeof define==="function"&&define.amd&&typeof define.amd==="object"){define(function(){return xChart});return}window.xChart=xChart})();
!function($, wysi) {
    "use strict";

    var tpl = {
        "font-styles": function(locale, options) {
            var size = (options && options.size) ? ' btn-'+options.size : '';
            return "<li class='dropdown'>" +
              "<a class='btn btn-default dropdown-toggle" + size + "' data-toggle='dropdown' href='#'>" +
              "<i class='icon-font'></i>&nbsp;<span class='current-font'>" + locale.font_styles.normal + "</span>&nbsp;<b class='caret'></b>" +
              "</a>" +
              "<ul class='dropdown-menu'>" +
                "<li><a data-wysihtml5-command='formatBlock' data-wysihtml5-command-value='div' tabindex='-1'>" + locale.font_styles.normal + "</a></li>" +
                "<li><a data-wysihtml5-command='formatBlock' data-wysihtml5-command-value='h1' tabindex='-1'>" + locale.font_styles.h1 + "</a></li>" +
                "<li><a data-wysihtml5-command='formatBlock' data-wysihtml5-command-value='h2' tabindex='-1'>" + locale.font_styles.h2 + "</a></li>" +
                "<li><a data-wysihtml5-command='formatBlock' data-wysihtml5-command-value='h3' tabindex='-1'>" + locale.font_styles.h3 + "</a></li>" +
              "</ul>" +
            "</li>";
        },

        "emphasis": function(locale, options) {
            var size = (options && options.size) ? ' btn-'+options.size : '';
            return "<li>" +
              "<div class='btn-group'>" +
                "<a class='btn btn-default" + size + "' data-wysihtml5-command='bold' title='CTRL+B' tabindex='-1'>" + locale.emphasis.bold + "</a>" +
                "<a class='btn btn-default" + size + "' data-wysihtml5-command='italic' title='CTRL+I' tabindex='-1'>" + locale.emphasis.italic + "</a>" +
                "<a class='btn btn-default" + size + "' data-wysihtml5-command='underline' title='CTRL+U' tabindex='-1'>" + locale.emphasis.underline + "</a>" +
              "</div>" +
            "</li>";
        },

        "lists": function(locale, options) {
            var size = (options && options.size) ? ' btn-'+options.size : '';
            return "<li>" +
              "<div class='btn-group'>" +
                "<a class='btn btn-default" + size + "' data-wysihtml5-command='insertUnorderedList' title='" + locale.lists.unordered + "' tabindex='-1'><i class='icon-list'></i></a>" +
                "<a class='btn btn-default" + size + "' data-wysihtml5-command='insertOrderedList' title='" + locale.lists.ordered + "' tabindex='-1'><i class='icon-th-list'></i></a>" +
                "<a class='btn btn-default" + size + "' data-wysihtml5-command='Outdent' title='" + locale.lists.outdent + "' tabindex='-1'><i class='icon-indent-right'></i></a>" +
                "<a class='btn btn-default" + size + "' data-wysihtml5-command='Indent' title='" + locale.lists.indent + "' tabindex='-1'><i class='icon-indent-left'></i></a>" +
              "</div>" +
            "</li>";
        },

        "link": function(locale, options) {
            var size = (options && options.size) ? ' btn-'+options.size : '';
            return "<li>" +
              "<div class='bootstrap-wysihtml5-insert-link-modal modal hide fade'>" +
                "<div class='modal-header'>" +
                  "<a class='close' data-dismiss='modal'>&times;</a>" +
                  "<h3>" + locale.link.insert + "</h3>" +
                "</div>" +
                "<div class='modal-body'>" +
                  "<input value='http://' class='bootstrap-wysihtml5-insert-link-url input-xlarge'>" +
                "</div>" +
                "<div class='modal-footer'>" +
                  "<a href='#' class='btn btn-default' data-dismiss='modal'>" + locale.link.cancel + "</a>" +
                  "<a href='#' class='btn btn-blue' data-dismiss='modal'>" + locale.link.insert + "</a>" +
                "</div>" +
              "</div>" +
              "<a class='btn btn-default" + size + "' data-wysihtml5-command='createLink' title='" + locale.link.insert + "' tabindex='-1'><i class='icon-share'></i></a>" +
            "</li>";
        },

        "image": function(locale, options) {
            var size = (options && options.size) ? ' btn-'+options.size : '';
            return "<li>" +
              "<div class='bootstrap-wysihtml5-insert-image-modal modal hide fade'>" +
                "<div class='modal-header'>" +
                  "<a class='close' data-dismiss='modal'>&times;</a>" +
                  "<h3>" + locale.image.insert + "</h3>" +
                "</div>" +
                "<div class='modal-body'>" +
                  "<input value='http://' class='bootstrap-wysihtml5-insert-image-url input-xlarge'>" +
                "</div>" +
                "<div class='modal-footer'>" +
                  "<a href='#' class='btn btn-default' data-dismiss='modal'>" + locale.image.cancel + "</a>" +
                  "<a href='#' class='btn btn-blue' data-dismiss='modal'>" + locale.image.insert + "</a>" +
                "</div>" +
              "</div>" +
              "<a class='btn btn-default" + size + "' data-wysihtml5-command='insertImage' title='" + locale.image.insert + "' tabindex='-1'><i class='icon-picture'></i></a>" +
            "</li>";
        },

        "html": function(locale, options) {
            var size = (options && options.size) ? ' btn-'+options.size : '';
            return "<li>" +
              "<div class='btn-group'>" +
                "<a class='btn btn-default" + size + "' data-wysihtml5-action='change_view' title='" + locale.html.edit + "' tabindex='-1'><i class='icon-pencil'></i></a>" +
              "</div>" +
            "</li>";
        },

        "color": function(locale, options) {
            var size = (options && options.size) ? ' btn-'+options.size : '';
            return "<li class='dropdown'>" +
              "<a class='btn btn-default dropdown-toggle" + size + "' data-toggle='dropdown' href='#' tabindex='-1'>" +
                "<span class='current-color'>" + locale.colours.black + "</span>&nbsp;<b class='caret'></b>" +
              "</a>" +
              "<ul class='dropdown-menu'>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='black'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='black'>" + locale.colours.black + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='silver'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='silver'>" + locale.colours.silver + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='gray'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='gray'>" + locale.colours.gray + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='maroon'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='maroon'>" + locale.colours.maroon + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='red'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='red'>" + locale.colours.red + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='purple'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='purple'>" + locale.colours.purple + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='green'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='green'>" + locale.colours.green + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='olive'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='olive'>" + locale.colours.olive + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='navy'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='navy'>" + locale.colours.navy + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='blue'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='blue'>" + locale.colours.blue + "</a></li>" +
                "<li><div class='wysihtml5-colors' data-wysihtml5-command-value='orange'></div><a class='wysihtml5-colors-title' data-wysihtml5-command='foreColor' data-wysihtml5-command-value='orange'>" + locale.colours.orange + "</a></li>" +
              "</ul>" +
            "</li>";
        }
    };

    var templates = function(key, locale, options) {
        return tpl[key](locale, options);
    };


    var Wysihtml5 = function(el, options) {
        this.el = el;
        var toolbarOpts = options || defaultOptions;
        for(var t in toolbarOpts.customTemplates) {
          tpl[t] = toolbarOpts.customTemplates[t];
        }
        this.toolbar = this.createToolbar(el, toolbarOpts);
        this.editor =  this.createEditor(options);

        window.editor = this.editor;

        $('iframe.wysihtml5-sandbox').each(function(i, el){
            $(el.contentWindow).off('focus.wysihtml5').on({
                'focus.wysihtml5' : function(){
                    $('li.dropdown').removeClass('open');
                }
            });
        });
    };

    Wysihtml5.prototype = {

        constructor: Wysihtml5,

        createEditor: function(options) {
            options = options || {};
            
            // Add the toolbar to a clone of the options object so multiple instances
            // of the WYISYWG don't break because "toolbar" is already defined
            options = $.extend(true, {}, options);
            options.toolbar = this.toolbar[0];

            var editor = new wysi.Editor(this.el[0], options);

            if(options && options.events) {
                for(var eventName in options.events) {
                    editor.on(eventName, options.events[eventName]);
                }
            }
            return editor;
        },

        createToolbar: function(el, options) {
            var self = this;
            var toolbar = $("<ul/>", {
                'class' : "wysihtml5-toolbar",
                'style': "display:none"
            });
            var culture = options.locale || defaultOptions.locale || "en";
            for(var key in defaultOptions) {
                var value = false;

                if(options[key] !== undefined) {
                    if(options[key] === true) {
                        value = true;
                    }
                } else {
                    value = defaultOptions[key];
                }

                if(value === true) {
                    toolbar.append(templates(key, locale[culture], options));

                    if(key === "html") {
                        this.initHtml(toolbar);
                    }

                    if(key === "link") {
                        this.initInsertLink(toolbar);
                    }

                    if(key === "image") {
                        this.initInsertImage(toolbar);
                    }
                }
            }

            if(options.toolbar) {
                for(key in options.toolbar) {
                    toolbar.append(options.toolbar[key]);
                }
            }

            toolbar.find("a[data-wysihtml5-command='formatBlock']").click(function(e) {
                var target = e.target || e.srcElement;
                var el = $(target);
                self.toolbar.find('.current-font').text(el.html());
            });

            toolbar.find("a[data-wysihtml5-command='foreColor']").click(function(e) {
                var target = e.target || e.srcElement;
                var el = $(target);
                self.toolbar.find('.current-color').text(el.html());
            });

            this.el.before(toolbar);

            return toolbar;
        },

        initHtml: function(toolbar) {
            var changeViewSelector = "a[data-wysihtml5-action='change_view']";
            toolbar.find(changeViewSelector).click(function(e) {
                toolbar.find('a.btn').not(changeViewSelector).toggleClass('disabled');
            });
        },

        initInsertImage: function(toolbar) {
            var self = this;
            var insertImageModal = toolbar.find('.bootstrap-wysihtml5-insert-image-modal');
            var urlInput = insertImageModal.find('.bootstrap-wysihtml5-insert-image-url');
            var insertButton = insertImageModal.find('a.btn-blue');
            var initialValue = urlInput.val();
            var caretBookmark;

            var insertImage = function() {
                var url = urlInput.val();
                urlInput.val(initialValue);
                self.editor.currentView.element.focus();
                if (caretBookmark) {
                  self.editor.composer.selection.setBookmark(caretBookmark);
                  caretBookmark = null;
                }
                self.editor.composer.commands.exec("insertImage", url);
            };

            urlInput.keypress(function(e) {
                if(e.which == 13) {
                    insertImage();
                    insertImageModal.modal('hide');
                }
            });

            insertButton.click(insertImage);

            insertImageModal.on('shown', function() {
                urlInput.focus();
            });

            insertImageModal.on('hide', function() {
                self.editor.currentView.element.focus();
            });

            toolbar.find('a[data-wysihtml5-command=insertImage]').click(function() {
                var activeButton = $(this).hasClass("wysihtml5-command-active");

                if (!activeButton) {
                    self.editor.currentView.element.focus(false);
                    caretBookmark = self.editor.composer.selection.getBookmark();
                    insertImageModal.appendTo('body').modal('show');
                    insertImageModal.on('click.dismiss.modal', '[data-dismiss="modal"]', function(e) {
                        e.stopPropagation();
                    });
                    return false;
                }
                else {
                    return true;
                }
            });
        },

        initInsertLink: function(toolbar) {
            var self = this;
            var insertLinkModal = toolbar.find('.bootstrap-wysihtml5-insert-link-modal');
            var urlInput = insertLinkModal.find('.bootstrap-wysihtml5-insert-link-url');
            var insertButton = insertLinkModal.find('a.btn-blue');
            var initialValue = urlInput.val();
            var caretBookmark;

            var insertLink = function() {
                var url = urlInput.val();
                urlInput.val(initialValue);
                self.editor.currentView.element.focus();
                if (caretBookmark) {
                  self.editor.composer.selection.setBookmark(caretBookmark);
                  caretBookmark = null;
                }
                self.editor.composer.commands.exec("createLink", {
                    href: url,
                    target: "_blank",
                    rel: "nofollow"
                });
            };
            var pressedEnter = false;

            urlInput.keypress(function(e) {
                if(e.which == 13) {
                    insertLink();
                    insertLinkModal.modal('hide');
                }
            });

            insertButton.click(insertLink);

            insertLinkModal.on('shown', function() {
                urlInput.focus();
            });

            insertLinkModal.on('hide', function() {
                self.editor.currentView.element.focus();
            });

            toolbar.find('a[data-wysihtml5-command=createLink]').click(function() {
                var activeButton = $(this).hasClass("wysihtml5-command-active");

                if (!activeButton) {
                    self.editor.currentView.element.focus(false);
                    caretBookmark = self.editor.composer.selection.getBookmark();
                    insertLinkModal.appendTo('body').modal('show');
                    insertLinkModal.on('click.dismiss.modal', '[data-dismiss="modal"]', function(e) {
                        e.stopPropagation();
                    });
                    return false;
                }
                else {
                    return true;
                }
            });
        }
    };

    // these define our public api
    var methods = {
        resetDefaults: function() {
            $.fn.wysihtml5.defaultOptions = $.extend(true, {}, $.fn.wysihtml5.defaultOptionsCache);
        },
        bypassDefaults: function(options) {
            return this.each(function () {
                var $this = $(this);
                $this.data('wysihtml5', new Wysihtml5($this, options));
            });
        },
        shallowExtend: function (options) {
            var settings = $.extend({}, $.fn.wysihtml5.defaultOptions, options || {});
            var that = this;
            return methods.bypassDefaults.apply(that, [settings]);
        },
        deepExtend: function(options) {
            var settings = $.extend(true, {}, $.fn.wysihtml5.defaultOptions, options || {});
            var that = this;
            return methods.bypassDefaults.apply(that, [settings]);
        },
        init: function(options) {
            var that = this;
            return methods.shallowExtend.apply(that, [options]);
        }
    };

    $.fn.wysihtml5 = function ( method ) {
        if ( methods[method] ) {
            return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.wysihtml5' );
        }    
    };

    $.fn.wysihtml5.Constructor = Wysihtml5;

    var defaultOptions = $.fn.wysihtml5.defaultOptions = {
        "font-styles": true,
        "color": false,
        "emphasis": true,
        "lists": true,
        "html": false,
        "link": true,
        "image": true,
        events: {},
        parserRules: {
            classes: {
                // (path_to_project/lib/css/wysiwyg-color.css)
                "wysiwyg-color-silver" : 1,
                "wysiwyg-color-gray" : 1,
                "wysiwyg-color-white" : 1,
                "wysiwyg-color-maroon" : 1,
                "wysiwyg-color-red" : 1,
                "wysiwyg-color-purple" : 1,
                "wysiwyg-color-fuchsia" : 1,
                "wysiwyg-color-green" : 1,
                "wysiwyg-color-lime" : 1,
                "wysiwyg-color-olive" : 1,
                "wysiwyg-color-yellow" : 1,
                "wysiwyg-color-navy" : 1,
                "wysiwyg-color-blue" : 1,
                "wysiwyg-color-teal" : 1,
                "wysiwyg-color-aqua" : 1,
                "wysiwyg-color-orange" : 1
            },
            tags: {
                "b":  {},
                "i":  {},
                "br": {},
                "ol": {},
                "ul": {},
                "li": {},
                "h1": {},
                "h2": {},
                "h3": {},
                "blockquote": {},
                "u": 1,
                "img": {
                    "check_attributes": {
                        "width": "numbers",
                        "alt": "alt",
                        "src": "url",
                        "height": "numbers"
                    }
                },
                "a":  {
                    set_attributes: {
                        target: "_blank",
                        rel:    "nofollow"
                    },
                    check_attributes: {
                        href:   "url" // important to avoid XSS
                    }
                },
                "span": 1,
                "div": 1,
                // to allow save and edit files with code tag hacks
                "code": 1,
                "pre": 1
            }
        },
        stylesheets: ["./lib/css/wysiwyg-color.css"], // (path_to_project/lib/css/wysiwyg-color.css)
        locale: "en"
    };

    if (typeof $.fn.wysihtml5.defaultOptionsCache === 'undefined') {
        $.fn.wysihtml5.defaultOptionsCache = $.extend(true, {}, $.fn.wysihtml5.defaultOptions);
    }

    var locale = $.fn.wysihtml5.locale = {
        en: {
            font_styles: {
                normal: "Normal text",
                h1: "Heading 1",
                h2: "Heading 2",
                h3: "Heading 3"
            },
            emphasis: {
                bold: "Bold",
                italic: "Italic",
                underline: "Underline"
            },
            lists: {
                unordered: "Unordered list",
                ordered: "Ordered list",
                outdent: "Outdent",
                indent: "Indent"
            },
            link: {
                insert: "Insert link",
                cancel: "Cancel"
            },
            image: {
                insert: "Insert image",
                cancel: "Cancel"
            },
            html: {
                edit: "Edit HTML"
            },
            colours: {
                black: "Black",
                silver: "Silver",
                gray: "Grey",
                maroon: "Maroon",
                red: "Red",
                purple: "Purple",
                green: "Green",
                olive: "Olive",
                navy: "Navy",
                blue: "Blue",
                orange: "Orange"
            }
        }
    };

}(window.jQuery, window.wysihtml5);


(function(a){var b="0.3.4",c="hasOwnProperty",d=/[\.\/]/,e="*",f=function(){},g=function(a,b){return a-b},h,i,j={n:{}},k=function(a,b){var c=j,d=i,e=Array.prototype.slice.call(arguments,2),f=k.listeners(a),l=0,m=!1,n,o=[],p={},q=[],r=h,s=[];h=a,i=0;for(var t=0,u=f.length;t<u;t++)"zIndex"in f[t]&&(o.push(f[t].zIndex),f[t].zIndex<0&&(p[f[t].zIndex]=f[t]));o.sort(g);while(o[l]<0){n=p[o[l++]],q.push(n.apply(b,e));if(i){i=d;return q}}for(t=0;t<u;t++){n=f[t];if("zIndex"in n)if(n.zIndex==o[l]){q.push(n.apply(b,e));if(i)break;do{l++,n=p[o[l]],n&&q.push(n.apply(b,e));if(i)break}while(n)}else p[n.zIndex]=n;else{q.push(n.apply(b,e));if(i)break}}i=d,h=r;return q.length?q:null};k.listeners=function(a){var b=a.split(d),c=j,f,g,h,i,k,l,m,n,o=[c],p=[];for(i=0,k=b.length;i<k;i++){n=[];for(l=0,m=o.length;l<m;l++){c=o[l].n,g=[c[b[i]],c[e]],h=2;while(h--)f=g[h],f&&(n.push(f),p=p.concat(f.f||[]))}o=n}return p},k.on=function(a,b){var c=a.split(d),e=j;for(var g=0,h=c.length;g<h;g++)e=e.n,!e[c[g]]&&(e[c[g]]={n:{}}),e=e[c[g]];e.f=e.f||[];for(g=0,h=e.f.length;g<h;g++)if(e.f[g]==b)return f;e.f.push(b);return function(a){+a==+a&&(b.zIndex=+a)}},k.stop=function(){i=1},k.nt=function(a){if(a)return(new RegExp("(?:\\.|\\/|^)"+a+"(?:\\.|\\/|$)")).test(h);return h},k.off=k.unbind=function(a,b){var f=a.split(d),g,h,i,k,l,m,n,o=[j];for(k=0,l=f.length;k<l;k++)for(m=0;m<o.length;m+=i.length-2){i=[m,1],g=o[m].n;if(f[k]!=e)g[f[k]]&&i.push(g[f[k]]);else for(h in g)g[c](h)&&i.push(g[h]);o.splice.apply(o,i)}for(k=0,l=o.length;k<l;k++){g=o[k];while(g.n){if(b){if(g.f){for(m=0,n=g.f.length;m<n;m++)if(g.f[m]==b){g.f.splice(m,1);break}!g.f.length&&delete g.f}for(h in g.n)if(g.n[c](h)&&g.n[h].f){var p=g.n[h].f;for(m=0,n=p.length;m<n;m++)if(p[m]==b){p.splice(m,1);break}!p.length&&delete g.n[h].f}}else{delete g.f;for(h in g.n)g.n[c](h)&&g.n[h].f&&delete g.n[h].f}g=g.n}}},k.once=function(a,b){var c=function(){var d=b.apply(this,arguments);k.unbind(a,c);return d};return k.on(a,c)},k.version=b,k.toString=function(){return"You are running Eve "+b},typeof module!="undefined"&&module.exports?module.exports=k:typeof define!="undefined"?define("eve",[],function(){return k}):a.eve=k})(this),function(){function cF(a){for(var b=0;b<cy.length;b++)cy[b].el.paper==a&&cy.splice(b--,1)}function cE(b,d,e,f,h,i){e=Q(e);var j,k,l,m=[],o,p,q,t=b.ms,u={},v={},w={};if(f)for(y=0,z=cy.length;y<z;y++){var x=cy[y];if(x.el.id==d.id&&x.anim==b){x.percent!=e?(cy.splice(y,1),l=1):k=x,d.attr(x.totalOrigin);break}}else f=+v;for(var y=0,z=b.percents.length;y<z;y++){if(b.percents[y]==e||b.percents[y]>f*b.top){e=b.percents[y],p=b.percents[y-1]||0,t=t/b.top*(e-p),o=b.percents[y+1],j=b.anim[e];break}f&&d.attr(b.anim[b.percents[y]])}if(!!j){if(!k){for(var A in j)if(j[g](A))if(U[g](A)||d.paper.customAttributes[g](A)){u[A]=d.attr(A),u[A]==null&&(u[A]=T[A]),v[A]=j[A];switch(U[A]){case C:w[A]=(v[A]-u[A])/t;break;case"colour":u[A]=a.getRGB(u[A]);var B=a.getRGB(v[A]);w[A]={r:(B.r-u[A].r)/t,g:(B.g-u[A].g)/t,b:(B.b-u[A].b)/t};break;case"path":var D=bR(u[A],v[A]),E=D[1];u[A]=D[0],w[A]=[];for(y=0,z=u[A].length;y<z;y++){w[A][y]=[0];for(var F=1,G=u[A][y].length;F<G;F++)w[A][y][F]=(E[y][F]-u[A][y][F])/t}break;case"transform":var H=d._,I=ca(H[A],v[A]);if(I){u[A]=I.from,v[A]=I.to,w[A]=[],w[A].real=!0;for(y=0,z=u[A].length;y<z;y++){w[A][y]=[u[A][y][0]];for(F=1,G=u[A][y].length;F<G;F++)w[A][y][F]=(v[A][y][F]-u[A][y][F])/t}}else{var J=d.matrix||new cb,K={_:{transform:H.transform},getBBox:function(){return d.getBBox(1)}};u[A]=[J.a,J.b,J.c,J.d,J.e,J.f],b$(K,v[A]),v[A]=K._.transform,w[A]=[(K.matrix.a-J.a)/t,(K.matrix.b-J.b)/t,(K.matrix.c-J.c)/t,(K.matrix.d-J.d)/t,(K.matrix.e-J.e)/t,(K.matrix.f-J.f)/t]}break;case"csv":var L=r(j[A])[s](c),M=r(u[A])[s](c);if(A=="clip-rect"){u[A]=M,w[A]=[],y=M.length;while(y--)w[A][y]=(L[y]-u[A][y])/t}v[A]=L;break;default:L=[][n](j[A]),M=[][n](u[A]),w[A]=[],y=d.paper.customAttributes[A].length;while(y--)w[A][y]=((L[y]||0)-(M[y]||0))/t}}var O=j.easing,P=a.easing_formulas[O];if(!P){P=r(O).match(N);if(P&&P.length==5){var R=P;P=function(a){return cC(a,+R[1],+R[2],+R[3],+R[4],t)}}else P=bf}q=j.start||b.start||+(new Date),x={anim:b,percent:e,timestamp:q,start:q+(b.del||0),status:0,initstatus:f||0,stop:!1,ms:t,easing:P,from:u,diff:w,to:v,el:d,callback:j.callback,prev:p,next:o,repeat:i||b.times,origin:d.attr(),totalOrigin:h},cy.push(x);if(f&&!k&&!l){x.stop=!0,x.start=new Date-t*f;if(cy.length==1)return cA()}l&&(x.start=new Date-x.ms*f),cy.length==1&&cz(cA)}else k.initstatus=f,k.start=new Date-k.ms*f;eve("raphael.anim.start."+d.id,d,b)}}function cD(a,b){var c=[],d={};this.ms=b,this.times=1;if(a){for(var e in a)a[g](e)&&(d[Q(e)]=a[e],c.push(Q(e)));c.sort(bd)}this.anim=d,this.top=c[c.length-1],this.percents=c}function cC(a,b,c,d,e,f){function o(a,b){var c,d,e,f,j,k;for(e=a,k=0;k<8;k++){f=m(e)-a;if(z(f)<b)return e;j=(3*i*e+2*h)*e+g;if(z(j)<1e-6)break;e=e-f/j}c=0,d=1,e=a;if(e<c)return c;if(e>d)return d;while(c<d){f=m(e);if(z(f-a)<b)return e;a>f?c=e:d=e,e=(d-c)/2+c}return e}function n(a,b){var c=o(a,b);return((l*c+k)*c+j)*c}function m(a){return((i*a+h)*a+g)*a}var g=3*b,h=3*(d-b)-g,i=1-g-h,j=3*c,k=3*(e-c)-j,l=1-j-k;return n(a,1/(200*f))}function cq(){return this.x+q+this.y+q+this.width+"  "+this.height}function cp(){return this.x+q+this.y}function cb(a,b,c,d,e,f){a!=null?(this.a=+a,this.b=+b,this.c=+c,this.d=+d,this.e=+e,this.f=+f):(this.a=1,this.b=0,this.c=0,this.d=1,this.e=0,this.f=0)}function bH(b,c,d){b=a._path2curve(b),c=a._path2curve(c);var e,f,g,h,i,j,k,l,m,n,o=d?0:[];for(var p=0,q=b.length;p<q;p++){var r=b[p];if(r[0]=="M")e=i=r[1],f=j=r[2];else{r[0]=="C"?(m=[e,f].concat(r.slice(1)),e=m[6],f=m[7]):(m=[e,f,e,f,i,j,i,j],e=i,f=j);for(var s=0,t=c.length;s<t;s++){var u=c[s];if(u[0]=="M")g=k=u[1],h=l=u[2];else{u[0]=="C"?(n=[g,h].concat(u.slice(1)),g=n[6],h=n[7]):(n=[g,h,g,h,k,l,k,l],g=k,h=l);var v=bG(m,n,d);if(d)o+=v;else{for(var w=0,x=v.length;w<x;w++)v[w].segment1=p,v[w].segment2=s,v[w].bez1=m,v[w].bez2=n;o=o.concat(v)}}}}}return o}function bG(b,c,d){var e=a.bezierBBox(b),f=a.bezierBBox(c);if(!a.isBBoxIntersect(e,f))return d?0:[];var g=bB.apply(0,b),h=bB.apply(0,c),i=~~(g/5),j=~~(h/5),k=[],l=[],m={},n=d?0:[];for(var o=0;o<i+1;o++){var p=a.findDotsAtSegment.apply(a,b.concat(o/i));k.push({x:p.x,y:p.y,t:o/i})}for(o=0;o<j+1;o++)p=a.findDotsAtSegment.apply(a,c.concat(o/j)),l.push({x:p.x,y:p.y,t:o/j});for(o=0;o<i;o++)for(var q=0;q<j;q++){var r=k[o],s=k[o+1],t=l[q],u=l[q+1],v=z(s.x-r.x)<.001?"y":"x",w=z(u.x-t.x)<.001?"y":"x",x=bD(r.x,r.y,s.x,s.y,t.x,t.y,u.x,u.y);if(x){if(m[x.x.toFixed(4)]==x.y.toFixed(4))continue;m[x.x.toFixed(4)]=x.y.toFixed(4);var y=r.t+z((x[v]-r[v])/(s[v]-r[v]))*(s.t-r.t),A=t.t+z((x[w]-t[w])/(u[w]-t[w]))*(u.t-t.t);y>=0&&y<=1&&A>=0&&A<=1&&(d?n++:n.push({x:x.x,y:x.y,t1:y,t2:A}))}}return n}function bF(a,b){return bG(a,b,1)}function bE(a,b){return bG(a,b)}function bD(a,b,c,d,e,f,g,h){if(!(x(a,c)<y(e,g)||y(a,c)>x(e,g)||x(b,d)<y(f,h)||y(b,d)>x(f,h))){var i=(a*d-b*c)*(e-g)-(a-c)*(e*h-f*g),j=(a*d-b*c)*(f-h)-(b-d)*(e*h-f*g),k=(a-c)*(f-h)-(b-d)*(e-g);if(!k)return;var l=i/k,m=j/k,n=+l.toFixed(2),o=+m.toFixed(2);if(n<+y(a,c).toFixed(2)||n>+x(a,c).toFixed(2)||n<+y(e,g).toFixed(2)||n>+x(e,g).toFixed(2)||o<+y(b,d).toFixed(2)||o>+x(b,d).toFixed(2)||o<+y(f,h).toFixed(2)||o>+x(f,h).toFixed(2))return;return{x:l,y:m}}}function bC(a,b,c,d,e,f,g,h,i){if(!(i<0||bB(a,b,c,d,e,f,g,h)<i)){var j=1,k=j/2,l=j-k,m,n=.01;m=bB(a,b,c,d,e,f,g,h,l);while(z(m-i)>n)k/=2,l+=(m<i?1:-1)*k,m=bB(a,b,c,d,e,f,g,h,l);return l}}function bB(a,b,c,d,e,f,g,h,i){i==null&&(i=1),i=i>1?1:i<0?0:i;var j=i/2,k=12,l=[-0.1252,.1252,-0.3678,.3678,-0.5873,.5873,-0.7699,.7699,-0.9041,.9041,-0.9816,.9816],m=[.2491,.2491,.2335,.2335,.2032,.2032,.1601,.1601,.1069,.1069,.0472,.0472],n=0;for(var o=0;o<k;o++){var p=j*l[o]+j,q=bA(p,a,c,e,g),r=bA(p,b,d,f,h),s=q*q+r*r;n+=m[o]*w.sqrt(s)}return j*n}function bA(a,b,c,d,e){var f=-3*b+9*c-9*d+3*e,g=a*f+6*b-12*c+6*d;return a*g-3*b+3*c}function by(a,b){var c=[];for(var d=0,e=a.length;e-2*!b>d;d+=2){var f=[{x:+a[d-2],y:+a[d-1]},{x:+a[d],y:+a[d+1]},{x:+a[d+2],y:+a[d+3]},{x:+a[d+4],y:+a[d+5]}];b?d?e-4==d?f[3]={x:+a[0],y:+a[1]}:e-2==d&&(f[2]={x:+a[0],y:+a[1]},f[3]={x:+a[2],y:+a[3]}):f[0]={x:+a[e-2],y:+a[e-1]}:e-4==d?f[3]=f[2]:d||(f[0]={x:+a[d],y:+a[d+1]}),c.push(["C",(-f[0].x+6*f[1].x+f[2].x)/6,(-f[0].y+6*f[1].y+f[2].y)/6,(f[1].x+6*f[2].x-f[3].x)/6,(f[1].y+6*f[2].y-f[3].y)/6,f[2].x,f[2].y])}return c}function bx(){return this.hex}function bv(a,b,c){function d(){var e=Array.prototype.slice.call(arguments,0),f=e.join("?"),h=d.cache=d.cache||{},i=d.count=d.count||[];if(h[g](f)){bu(i,f);return c?c(h[f]):h[f]}i.length>=1e3&&delete h[i.shift()],i.push(f),h[f]=a[m](b,e);return c?c(h[f]):h[f]}return d}function bu(a,b){for(var c=0,d=a.length;c<d;c++)if(a[c]===b)return a.push(a.splice(c,1)[0])}function bm(a){if(Object(a)!==a)return a;var b=new a.constructor;for(var c in a)a[g](c)&&(b[c]=bm(a[c]));return b}function a(c){if(a.is(c,"function"))return b?c():eve.on("raphael.DOMload",c);if(a.is(c,E))return a._engine.create[m](a,c.splice(0,3+a.is(c[0],C))).add(c);var d=Array.prototype.slice.call(arguments,0);if(a.is(d[d.length-1],"function")){var e=d.pop();return b?e.call(a._engine.create[m](a,d)):eve.on("raphael.DOMload",function(){e.call(a._engine.create[m](a,d))})}return a._engine.create[m](a,arguments)}a.version="2.1.0",a.eve=eve;var b,c=/[, ]+/,d={circle:1,rect:1,path:1,ellipse:1,text:1,image:1},e=/\{(\d+)\}/g,f="prototype",g="hasOwnProperty",h={doc:document,win:window},i={was:Object.prototype[g].call(h.win,"Raphael"),is:h.win.Raphael},j=function(){this.ca=this.customAttributes={}},k,l="appendChild",m="apply",n="concat",o="createTouch"in h.doc,p="",q=" ",r=String,s="split",t="click dblclick mousedown mousemove mouseout mouseover mouseup touchstart touchmove touchend touchcancel"[s](q),u={mousedown:"touchstart",mousemove:"touchmove",mouseup:"touchend"},v=r.prototype.toLowerCase,w=Math,x=w.max,y=w.min,z=w.abs,A=w.pow,B=w.PI,C="number",D="string",E="array",F="toString",G="fill",H=Object.prototype.toString,I={},J="push",K=a._ISURL=/^url\(['"]?([^\)]+?)['"]?\)$/i,L=/^\s*((#[a-f\d]{6})|(#[a-f\d]{3})|rgba?\(\s*([\d\.]+%?\s*,\s*[\d\.]+%?\s*,\s*[\d\.]+%?(?:\s*,\s*[\d\.]+%?)?)\s*\)|hsba?\(\s*([\d\.]+(?:deg|\xb0|%)?\s*,\s*[\d\.]+%?\s*,\s*[\d\.]+(?:%?\s*,\s*[\d\.]+)?)%?\s*\)|hsla?\(\s*([\d\.]+(?:deg|\xb0|%)?\s*,\s*[\d\.]+%?\s*,\s*[\d\.]+(?:%?\s*,\s*[\d\.]+)?)%?\s*\))\s*$/i,M={NaN:1,Infinity:1,"-Infinity":1},N=/^(?:cubic-)?bezier\(([^,]+),([^,]+),([^,]+),([^\)]+)\)/,O=w.round,P="setAttribute",Q=parseFloat,R=parseInt,S=r.prototype.toUpperCase,T=a._availableAttrs={"arrow-end":"none","arrow-start":"none",blur:0,"clip-rect":"0 0 1e9 1e9",cursor:"default",cx:0,cy:0,fill:"#fff","fill-opacity":1,font:'10px "Arial"',"font-family":'"Arial"',"font-size":"10","font-style":"normal","font-weight":400,gradient:0,height:0,href:"http://raphaeljs.com/","letter-spacing":0,opacity:1,path:"M0,0",r:0,rx:0,ry:0,src:"",stroke:"#000","stroke-dasharray":"","stroke-linecap":"butt","stroke-linejoin":"butt","stroke-miterlimit":0,"stroke-opacity":1,"stroke-width":1,target:"_blank","text-anchor":"middle",title:"Raphael",transform:"",width:0,x:0,y:0},U=a._availableAnimAttrs={blur:C,"clip-rect":"csv",cx:C,cy:C,fill:"colour","fill-opacity":C,"font-size":C,height:C,opacity:C,path:"path",r:C,rx:C,ry:C,stroke:"colour","stroke-opacity":C,"stroke-width":C,transform:"transform",width:C,x:C,y:C},V=/[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]/g,W=/[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*,[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*/,X={hs:1,rg:1},Y=/,?([achlmqrstvxz]),?/gi,Z=/([achlmrqstvz])[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029,]*((-?\d*\.?\d*(?:e[\-+]?\d+)?[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*,?[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*)+)/ig,$=/([rstm])[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029,]*((-?\d*\.?\d*(?:e[\-+]?\d+)?[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*,?[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*)+)/ig,_=/(-?\d*\.?\d*(?:e[\-+]?\d+)?)[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*,?[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*/ig,ba=a._radial_gradient=/^r(?:\(([^,]+?)[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*,[\x09\x0a\x0b\x0c\x0d\x20\xa0\u1680\u180e\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u202f\u205f\u3000\u2028\u2029]*([^\)]+?)\))?/,bb={},bc=function(a,b){return a.key-b.key},bd=function(a,b){return Q(a)-Q(b)},be=function(){},bf=function(a){return a},bg=a._rectPath=function(a,b,c,d,e){if(e)return[["M",a+e,b],["l",c-e*2,0],["a",e,e,0,0,1,e,e],["l",0,d-e*2],["a",e,e,0,0,1,-e,e],["l",e*2-c,0],["a",e,e,0,0,1,-e,-e],["l",0,e*2-d],["a",e,e,0,0,1,e,-e],["z"]];return[["M",a,b],["l",c,0],["l",0,d],["l",-c,0],["z"]]},bh=function(a,b,c,d){d==null&&(d=c);return[["M",a,b],["m",0,-d],["a",c,d,0,1,1,0,2*d],["a",c,d,0,1,1,0,-2*d],["z"]]},bi=a._getPath={path:function(a){return a.attr("path")},circle:function(a){var b=a.attrs;return bh(b.cx,b.cy,b.r)},ellipse:function(a){var b=a.attrs;return bh(b.cx,b.cy,b.rx,b.ry)},rect:function(a){var b=a.attrs;return bg(b.x,b.y,b.width,b.height,b.r)},image:function(a){var b=a.attrs;return bg(b.x,b.y,b.width,b.height)},text:function(a){var b=a._getBBox();return bg(b.x,b.y,b.width,b.height)}},bj=a.mapPath=function(a,b){if(!b)return a;var c,d,e,f,g,h,i;a=bR(a);for(e=0,g=a.length;e<g;e++){i=a[e];for(f=1,h=i.length;f<h;f+=2)c=b.x(i[f],i[f+1]),d=b.y(i[f],i[f+1]),i[f]=c,i[f+1]=d}return a};a._g=h,a.type=h.win.SVGAngle||h.doc.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure","1.1")?"SVG":"VML";if(a.type=="VML"){var bk=h.doc.createElement("div"),bl;bk.innerHTML='<v:shape adj="1"/>',bl=bk.firstChild,bl.style.behavior="url(#default#VML)";if(!bl||typeof bl.adj!="object")return a.type=p;bk=null}a.svg=!(a.vml=a.type=="VML"),a._Paper=j,a.fn=k=j.prototype=a.prototype,a._id=0,a._oid=0,a.is=function(a,b){b=v.call(b);if(b=="finite")return!M[g](+a);if(b=="array")return a instanceof Array;return b=="null"&&a===null||b==typeof a&&a!==null||b=="object"&&a===Object(a)||b=="array"&&Array.isArray&&Array.isArray(a)||H.call(a).slice(8,-1).toLowerCase()==b},a.angle=function(b,c,d,e,f,g){if(f==null){var h=b-d,i=c-e;if(!h&&!i)return 0;return(180+w.atan2(-i,-h)*180/B+360)%360}return a.angle(b,c,f,g)-a.angle(d,e,f,g)},a.rad=function(a){return a%360*B/180},a.deg=function(a){return a*180/B%360},a.snapTo=function(b,c,d){d=a.is(d,"finite")?d:10;if(a.is(b,E)){var e=b.length;while(e--)if(z(b[e]-c)<=d)return b[e]}else{b=+b;var f=c%b;if(f<d)return c-f;if(f>b-d)return c-f+b}return c};var bn=a.createUUID=function(a,b){return function(){return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(a,b).toUpperCase()}}(/[xy]/g,function(a){var b=w.random()*16|0,c=a=="x"?b:b&3|8;return c.toString(16)});a.setWindow=function(b){eve("raphael.setWindow",a,h.win,b),h.win=b,h.doc=h.win.document,a._engine.initWin&&a._engine.initWin(h.win)};var bo=function(b){if(a.vml){var c=/^\s+|\s+$/g,d;try{var e=new ActiveXObject("htmlfile");e.write("<body>"),e.close(),d=e.body}catch(f){d=createPopup().document.body}var g=d.createTextRange();bo=bv(function(a){try{d.style.color=r(a).replace(c,p);var b=g.queryCommandValue("ForeColor");b=(b&255)<<16|b&65280|(b&16711680)>>>16;return"#"+("000000"+b.toString(16)).slice(-6)}catch(e){return"none"}})}else{var i=h.doc.createElement("i");i.title="Raphal Colour Picker",i.style.display="none",h.doc.body.appendChild(i),bo=bv(function(a){i.style.color=a;return h.doc.defaultView.getComputedStyle(i,p).getPropertyValue("color")})}return bo(b)},bp=function(){return"hsb("+[this.h,this.s,this.b]+")"},bq=function(){return"hsl("+[this.h,this.s,this.l]+")"},br=function(){return this.hex},bs=function(b,c,d){c==null&&a.is(b,"object")&&"r"in b&&"g"in b&&"b"in b&&(d=b.b,c=b.g,b=b.r);if(c==null&&a.is(b,D)){var e=a.getRGB(b);b=e.r,c=e.g,d=e.b}if(b>1||c>1||d>1)b/=255,c/=255,d/=255;return[b,c,d]},bt=function(b,c,d,e){b*=255,c*=255,d*=255;var f={r:b,g:c,b:d,hex:a.rgb(b,c,d),toString:br};a.is(e,"finite")&&(f.opacity=e);return f};a.color=function(b){var c;a.is(b,"object")&&"h"in b&&"s"in b&&"b"in b?(c=a.hsb2rgb(b),b.r=c.r,b.g=c.g,b.b=c.b,b.hex=c.hex):a.is(b,"object")&&"h"in b&&"s"in b&&"l"in b?(c=a.hsl2rgb(b),b.r=c.r,b.g=c.g,b.b=c.b,b.hex=c.hex):(a.is(b,"string")&&(b=a.getRGB(b)),a.is(b,"object")&&"r"in b&&"g"in b&&"b"in b?(c=a.rgb2hsl(b),b.h=c.h,b.s=c.s,b.l=c.l,c=a.rgb2hsb(b),b.v=c.b):(b={hex:"none"},b.r=b.g=b.b=b.h=b.s=b.v=b.l=-1)),b.toString=br;return b},a.hsb2rgb=function(a,b,c,d){this.is(a,"object")&&"h"in a&&"s"in a&&"b"in a&&(c=a.b,b=a.s,a=a.h,d=a.o),a*=360;var e,f,g,h,i;a=a%360/60,i=c*b,h=i*(1-z(a%2-1)),e=f=g=c-i,a=~~a,e+=[i,h,0,0,h,i][a],f+=[h,i,i,h,0,0][a],g+=[0,0,h,i,i,h][a];return bt(e,f,g,d)},a.hsl2rgb=function(a,b,c,d){this.is(a,"object")&&"h"in a&&"s"in a&&"l"in a&&(c=a.l,b=a.s,a=a.h);if(a>1||b>1||c>1)a/=360,b/=100,c/=100;a*=360;var e,f,g,h,i;a=a%360/60,i=2*b*(c<.5?c:1-c),h=i*(1-z(a%2-1)),e=f=g=c-i/2,a=~~a,e+=[i,h,0,0,h,i][a],f+=[h,i,i,h,0,0][a],g+=[0,0,h,i,i,h][a];return bt(e,f,g,d)},a.rgb2hsb=function(a,b,c){c=bs(a,b,c),a=c[0],b=c[1],c=c[2];var d,e,f,g;f=x(a,b,c),g=f-y(a,b,c),d=g==0?null:f==a?(b-c)/g:f==b?(c-a)/g+2:(a-b)/g+4,d=(d+360)%6*60/360,e=g==0?0:g/f;return{h:d,s:e,b:f,toString:bp}},a.rgb2hsl=function(a,b,c){c=bs(a,b,c),a=c[0],b=c[1],c=c[2];var d,e,f,g,h,i;g=x(a,b,c),h=y(a,b,c),i=g-h,d=i==0?null:g==a?(b-c)/i:g==b?(c-a)/i+2:(a-b)/i+4,d=(d+360)%6*60/360,f=(g+h)/2,e=i==0?0:f<.5?i/(2*f):i/(2-2*f);return{h:d,s:e,l:f,toString:bq}},a._path2string=function(){return this.join(",").replace(Y,"$1")};var bw=a._preload=function(a,b){var c=h.doc.createElement("img");c.style.cssText="position:absolute;left:-9999em;top:-9999em",c.onload=function(){b.call(this),this.onload=null,h.doc.body.removeChild(this)},c.onerror=function(){h.doc.body.removeChild(this)},h.doc.body.appendChild(c),c.src=a};a.getRGB=bv(function(b){if(!b||!!((b=r(b)).indexOf("-")+1))return{r:-1,g:-1,b:-1,hex:"none",error:1,toString:bx};if(b=="none")return{r:-1,g:-1,b:-1,hex:"none",toString:bx};!X[g](b.toLowerCase().substring(0,2))&&b.charAt()!="#"&&(b=bo(b));var c,d,e,f,h,i,j,k=b.match(L);if(k){k[2]&&(f=R(k[2].substring(5),16),e=R(k[2].substring(3,5),16),d=R(k[2].substring(1,3),16)),k[3]&&(f=R((i=k[3].charAt(3))+i,16),e=R((i=k[3].charAt(2))+i,16),d=R((i=k[3].charAt(1))+i,16)),k[4]&&(j=k[4][s](W),d=Q(j[0]),j[0].slice(-1)=="%"&&(d*=2.55),e=Q(j[1]),j[1].slice(-1)=="%"&&(e*=2.55),f=Q(j[2]),j[2].slice(-1)=="%"&&(f*=2.55),k[1].toLowerCase().slice(0,4)=="rgba"&&(h=Q(j[3])),j[3]&&j[3].slice(-1)=="%"&&(h/=100));if(k[5]){j=k[5][s](W),d=Q(j[0]),j[0].slice(-1)=="%"&&(d*=2.55),e=Q(j[1]),j[1].slice(-1)=="%"&&(e*=2.55),f=Q(j[2]),j[2].slice(-1)=="%"&&(f*=2.55),(j[0].slice(-3)=="deg"||j[0].slice(-1)=="")&&(d/=360),k[1].toLowerCase().slice(0,4)=="hsba"&&(h=Q(j[3])),j[3]&&j[3].slice(-1)=="%"&&(h/=100);return a.hsb2rgb(d,e,f,h)}if(k[6]){j=k[6][s](W),d=Q(j[0]),j[0].slice(-1)=="%"&&(d*=2.55),e=Q(j[1]),j[1].slice(-1)=="%"&&(e*=2.55),f=Q(j[2]),j[2].slice(-1)=="%"&&(f*=2.55),(j[0].slice(-3)=="deg"||j[0].slice(-1)=="")&&(d/=360),k[1].toLowerCase().slice(0,4)=="hsla"&&(h=Q(j[3])),j[3]&&j[3].slice(-1)=="%"&&(h/=100);return a.hsl2rgb(d,e,f,h)}k={r:d,g:e,b:f,toString:bx},k.hex="#"+(16777216|f|e<<8|d<<16).toString(16).slice(1),a.is(h,"finite")&&(k.opacity=h);return k}return{r:-1,g:-1,b:-1,hex:"none",error:1,toString:bx}},a),a.hsb=bv(function(b,c,d){return a.hsb2rgb(b,c,d).hex}),a.hsl=bv(function(b,c,d){return a.hsl2rgb(b,c,d).hex}),a.rgb=bv(function(a,b,c){return"#"+(16777216|c|b<<8|a<<16).toString(16).slice(1)}),a.getColor=function(a){var b=this.getColor.start=this.getColor.start||{h:0,s:1,b:a||.75},c=this.hsb2rgb(b.h,b.s,b.b);b.h+=.075,b.h>1&&(b.h=0,b.s-=.2,b.s<=0&&(this.getColor.start={h:0,s:1,b:b.b}));return c.hex},a.getColor.reset=function(){delete this.start},a.parsePathString=function(b){if(!b)return null;var c=bz(b);if(c.arr)return bJ(c.arr);var d={a:7,c:6,h:1,l:2,m:2,r:4,q:4,s:4,t:2,v:1,z:0},e=[];a.is(b,E)&&a.is(b[0],E)&&(e=bJ(b)),e.length||r(b).replace(Z,function(a,b,c){var f=[],g=b.toLowerCase();c.replace(_,function(a,b){b&&f.push(+b)}),g=="m"&&f.length>2&&(e.push([b][n](f.splice(0,2))),g="l",b=b=="m"?"l":"L");if(g=="r")e.push([b][n](f));else while(f.length>=d[g]){e.push([b][n](f.splice(0,d[g])));if(!d[g])break}}),e.toString=a._path2string,c.arr=bJ(e);return e},a.parseTransformString=bv(function(b){if(!b)return null;var c={r:3,s:4,t:2,m:6},d=[];a.is(b,E)&&a.is(b[0],E)&&(d=bJ(b)),d.length||r(b).replace($,function(a,b,c){var e=[],f=v.call(b);c.replace(_,function(a,b){b&&e.push(+b)}),d.push([b][n](e))}),d.toString=a._path2string;return d});var bz=function(a){var b=bz.ps=bz.ps||{};b[a]?b[a].sleep=100:b[a]={sleep:100},setTimeout(function(){for(var c in b)b[g](c)&&c!=a&&(b[c].sleep--,!b[c].sleep&&delete b[c])});return b[a]};a.findDotsAtSegment=function(a,b,c,d,e,f,g,h,i){var j=1-i,k=A(j,3),l=A(j,2),m=i*i,n=m*i,o=k*a+l*3*i*c+j*3*i*i*e+n*g,p=k*b+l*3*i*d+j*3*i*i*f+n*h,q=a+2*i*(c-a)+m*(e-2*c+a),r=b+2*i*(d-b)+m*(f-2*d+b),s=c+2*i*(e-c)+m*(g-2*e+c),t=d+2*i*(f-d)+m*(h-2*f+d),u=j*a+i*c,v=j*b+i*d,x=j*e+i*g,y=j*f+i*h,z=90-w.atan2(q-s,r-t)*180/B;(q>s||r<t)&&(z+=180);return{x:o,y:p,m:{x:q,y:r},n:{x:s,y:t},start:{x:u,y:v},end:{x:x,y:y},alpha:z}},a.bezierBBox=function(b,c,d,e,f,g,h,i){a.is(b,"array")||(b=[b,c,d,e,f,g,h,i]);var j=bQ.apply(null,b);return{x:j.min.x,y:j.min.y,x2:j.max.x,y2:j.max.y,width:j.max.x-j.min.x,height:j.max.y-j.min.y}},a.isPointInsideBBox=function(a,b,c){return b>=a.x&&b<=a.x2&&c>=a.y&&c<=a.y2},a.isBBoxIntersect=function(b,c){var d=a.isPointInsideBBox;return d(c,b.x,b.y)||d(c,b.x2,b.y)||d(c,b.x,b.y2)||d(c,b.x2,b.y2)||d(b,c.x,c.y)||d(b,c.x2,c.y)||d(b,c.x,c.y2)||d(b,c.x2,c.y2)||(b.x<c.x2&&b.x>c.x||c.x<b.x2&&c.x>b.x)&&(b.y<c.y2&&b.y>c.y||c.y<b.y2&&c.y>b.y)},a.pathIntersection=function(a,b){return bH(a,b)},a.pathIntersectionNumber=function(a,b){return bH(a,b,1)},a.isPointInsidePath=function(b,c,d){var e=a.pathBBox(b);return a.isPointInsideBBox(e,c,d)&&bH(b,[["M",c,d],["H",e.x2+10]],1)%2==1},a._removedFactory=function(a){return function(){eve("raphael.log",null,"Raphal: you are calling to method "+a+" of removed object",a)}};var bI=a.pathBBox=function(a){var b=bz(a);if(b.bbox)return b.bbox;if(!a)return{x:0,y:0,width:0,height:0,x2:0,y2:0};a=bR(a);var c=0,d=0,e=[],f=[],g;for(var h=0,i=a.length;h<i;h++){g=a[h];if(g[0]=="M")c=g[1],d=g[2],e.push(c),f.push(d);else{var j=bQ(c,d,g[1],g[2],g[3],g[4],g[5],g[6]);e=e[n](j.min.x,j.max.x),f=f[n](j.min.y,j.max.y),c=g[5],d=g[6]}}var k=y[m](0,e),l=y[m](0,f),o=x[m](0,e),p=x[m](0,f),q={x:k,y:l,x2:o,y2:p,width:o-k,height:p-l};b.bbox=bm(q);return q},bJ=function(b){var c=bm(b);c.toString=a._path2string;return c},bK=a._pathToRelative=function(b){var c=bz(b);if(c.rel)return bJ(c.rel);if(!a.is(b,E)||!a.is(b&&b[0],E))b=a.parsePathString(b);var d=[],e=0,f=0,g=0,h=0,i=0;b[0][0]=="M"&&(e=b[0][1],f=b[0][2],g=e,h=f,i++,d.push(["M",e,f]));for(var j=i,k=b.length;j<k;j++){var l=d[j]=[],m=b[j];if(m[0]!=v.call(m[0])){l[0]=v.call(m[0]);switch(l[0]){case"a":l[1]=m[1],l[2]=m[2],l[3]=m[3],l[4]=m[4],l[5]=m[5],l[6]=+(m[6]-e).toFixed(3),l[7]=+(m[7]-f).toFixed(3);break;case"v":l[1]=+(m[1]-f).toFixed(3);break;case"m":g=m[1],h=m[2];default:for(var n=1,o=m.length;n<o;n++)l[n]=+(m[n]-(n%2?e:f)).toFixed(3)}}else{l=d[j]=[],m[0]=="m"&&(g=m[1]+e,h=m[2]+f);for(var p=0,q=m.length;p<q;p++)d[j][p]=m[p]}var r=d[j].length;switch(d[j][0]){case"z":e=g,f=h;break;case"h":e+=+d[j][r-1];break;case"v":f+=+d[j][r-1];break;default:e+=+d[j][r-2],f+=+d[j][r-1]}}d.toString=a._path2string,c.rel=bJ(d);return d},bL=a._pathToAbsolute=function(b){var c=bz(b);if(c.abs)return bJ(c.abs);if(!a.is(b,E)||!a.is(b&&b[0],E))b=a.parsePathString(b);if(!b||!b.length)return[["M",0,0]];var d=[],e=0,f=0,g=0,h=0,i=0;b[0][0]=="M"&&(e=+b[0][1],f=+b[0][2],g=e,h=f,i++,d[0]=["M",e,f]);var j=b.length==3&&b[0][0]=="M"&&b[1][0].toUpperCase()=="R"&&b[2][0].toUpperCase()=="Z";for(var k,l,m=i,o=b.length;m<o;m++){d.push(k=[]),l=b[m];if(l[0]!=S.call(l[0])){k[0]=S.call(l[0]);switch(k[0]){case"A":k[1]=l[1],k[2]=l[2],k[3]=l[3],k[4]=l[4],k[5]=l[5],k[6]=+(l[6]+e),k[7]=+(l[7]+f);break;case"V":k[1]=+l[1]+f;break;case"H":k[1]=+l[1]+e;break;case"R":var p=[e,f][n](l.slice(1));for(var q=2,r=p.length;q<r;q++)p[q]=+p[q]+e,p[++q]=+p[q]+f;d.pop(),d=d[n](by(p,j));break;case"M":g=+l[1]+e,h=+l[2]+f;default:for(q=1,r=l.length;q<r;q++)k[q]=+l[q]+(q%2?e:f)}}else if(l[0]=="R")p=[e,f][n](l.slice(1)),d.pop(),d=d[n](by(p,j)),k=["R"][n](l.slice(-2));else for(var s=0,t=l.length;s<t;s++)k[s]=l[s];switch(k[0]){case"Z":e=g,f=h;break;case"H":e=k[1];break;case"V":f=k[1];break;case"M":g=k[k.length-2],h=k[k.length-1];default:e=k[k.length-2],f=k[k.length-1]}}d.toString=a._path2string,c.abs=bJ(d);return d},bM=function(a,b,c,d){return[a,b,c,d,c,d]},bN=function(a,b,c,d,e,f){var g=1/3,h=2/3;return[g*a+h*c,g*b+h*d,g*e+h*c,g*f+h*d,e,f]},bO=function(a,b,c,d,e,f,g,h,i,j){var k=B*120/180,l=B/180*(+e||0),m=[],o,p=bv(function(a,b,c){var d=a*w.cos(c)-b*w.sin(c),e=a*w.sin(c)+b*w.cos(c);return{x:d,y:e}});if(!j){o=p(a,b,-l),a=o.x,b=o.y,o=p(h,i,-l),h=o.x,i=o.y;var q=w.cos(B/180*e),r=w.sin(B/180*e),t=(a-h)/2,u=(b-i)/2,v=t*t/(c*c)+u*u/(d*d);v>1&&(v=w.sqrt(v),c=v*c,d=v*d);var x=c*c,y=d*d,A=(f==g?-1:1)*w.sqrt(z((x*y-x*u*u-y*t*t)/(x*u*u+y*t*t))),C=A*c*u/d+(a+h)/2,D=A*-d*t/c+(b+i)/2,E=w.asin(((b-D)/d).toFixed(9)),F=w.asin(((i-D)/d).toFixed(9));E=a<C?B-E:E,F=h<C?B-F:F,E<0&&(E=B*2+E),F<0&&(F=B*2+F),g&&E>F&&(E=E-B*2),!g&&F>E&&(F=F-B*2)}else E=j[0],F=j[1],C=j[2],D=j[3];var G=F-E;if(z(G)>k){var H=F,I=h,J=i;F=E+k*(g&&F>E?1:-1),h=C+c*w.cos(F),i=D+d*w.sin(F),m=bO(h,i,c,d,e,0,g,I,J,[F,H,C,D])}G=F-E;var K=w.cos(E),L=w.sin(E),M=w.cos(F),N=w.sin(F),O=w.tan(G/4),P=4/3*c*O,Q=4/3*d*O,R=[a,b],S=[a+P*L,b-Q*K],T=[h+P*N,i-Q*M],U=[h,i];S[0]=2*R[0]-S[0],S[1]=2*R[1]-S[1];if(j)return[S,T,U][n](m);m=[S,T,U][n](m).join()[s](",");var V=[];for(var W=0,X=m.length;W<X;W++)V[W]=W%2?p(m[W-1],m[W],l).y:p(m[W],m[W+1],l).x;return V},bP=function(a,b,c,d,e,f,g,h,i){var j=1-i;return{x:A(j,3)*a+A(j,2)*3*i*c+j*3*i*i*e+A(i,3)*g,y:A(j,3)*b+A(j,2)*3*i*d+j*3*i*i*f+A(i,3)*h}},bQ=bv(function(a,b,c,d,e,f,g,h){var i=e-2*c+a-(g-2*e+c),j=2*(c-a)-2*(e-c),k=a-c,l=(-j+w.sqrt(j*j-4*i*k))/2/i,n=(-j-w.sqrt(j*j-4*i*k))/2/i,o=[b,h],p=[a,g],q;z(l)>"1e12"&&(l=.5),z(n)>"1e12"&&(n=.5),l>0&&l<1&&(q=bP(a,b,c,d,e,f,g,h,l),p.push(q.x),o.push(q.y)),n>0&&n<1&&(q=bP(a,b,c,d,e,f,g,h,n),p.push(q.x),o.push(q.y)),i=f-2*d+b-(h-2*f+d),j=2*(d-b)-2*(f-d),k=b-d,l=(-j+w.sqrt(j*j-4*i*k))/2/i,n=(-j-w.sqrt(j*j-4*i*k))/2/i,z(l)>"1e12"&&(l=.5),z(n)>"1e12"&&(n=.5),l>0&&l<1&&(q=bP(a,b,c,d,e,f,g,h,l),p.push(q.x),o.push(q.y)),n>0&&n<1&&(q=bP(a,b,c,d,e,f,g,h,n),p.push(q.x),o.push(q.y));return{min:{x:y[m](0,p),y:y[m](0,o)},max:{x:x[m](0,p),y:x[m](0,o)}}}),bR=a._path2curve=bv(function(a,b){var c=!b&&bz(a);if(!b&&c.curve)return bJ(c.curve);var d=bL(a),e=b&&bL(b),f={x:0,y:0,bx:0,by:0,X:0,Y:0,qx:null,qy:null},g={x:0,y:0,bx:0,by:0,X:0,Y:0,qx:null,qy:null},h=function(a,b){var c,d;if(!a)return["C",b.x,b.y,b.x,b.y,b.x,b.y];!(a[0]in{T:1,Q:1})&&(b.qx=b.qy=null);switch(a[0]){case"M":b.X=a[1],b.Y=a[2];break;case"A":a=["C"][n](bO[m](0,[b.x,b.y][n](a.slice(1))));break;case"S":c=b.x+(b.x-(b.bx||b.x)),d=b.y+(b.y-(b.by||b.y)),a=["C",c,d][n](a.slice(1));break;case"T":b.qx=b.x+(b.x-(b.qx||b.x)),b.qy=b.y+(b.y-(b.qy||b.y)),a=["C"][n](bN(b.x,b.y,b.qx,b.qy,a[1],a[2]));break;case"Q":b.qx=a[1],b.qy=a[2],a=["C"][n](bN(b.x,b.y,a[1],a[2],a[3],a[4]));break;case"L":a=["C"][n](bM(b.x,b.y,a[1],a[2]));break;case"H":a=["C"][n](bM(b.x,b.y,a[1],b.y));break;case"V":a=["C"][n](bM(b.x,b.y,b.x,a[1]));break;case"Z":a=["C"][n](bM(b.x,b.y,b.X,b.Y))}return a},i=function(a,b){if(a[b].length>7){a[b].shift();var c=a[b];while(c.length)a.splice(b++,0,["C"][n](c.splice(0,6)));a.splice(b,1),l=x(d.length,e&&e.length||0)}},j=function(a,b,c,f,g){a&&b&&a[g][0]=="M"&&b[g][0]!="M"&&(b.splice(g,0,["M",f.x,f.y]),c.bx=0,c.by=0,c.x=a[g][1],c.y=a[g][2],l=x(d.length,e&&e.length||0))};for(var k=0,l=x(d.length,e&&e.length||0);k<l;k++){d[k]=h(d[k],f),i(d,k),e&&(e[k]=h(e[k],g)),e&&i(e,k),j(d,e,f,g,k),j(e,d,g,f,k);var o=d[k],p=e&&e[k],q=o.length,r=e&&p.length;f.x=o[q-2],f.y=o[q-1],f.bx=Q(o[q-4])||f.x,f.by=Q(o[q-3])||f.y,g.bx=e&&(Q(p[r-4])||g.x),g.by=e&&(Q(p[r-3])||g.y),g.x=e&&p[r-2],g.y=e&&p[r-1]}e||(c.curve=bJ(d));return e?[d,e]:d},null,bJ),bS=a._parseDots=bv(function(b){var c=[];for(var d=0,e=b.length;d<e;d++){var f={},g=b[d].match(/^([^:]*):?([\d\.]*)/);f.color=a.getRGB(g[1]);if(f.color.error)return null;f.color=f.color.hex,g[2]&&(f.offset=g[2]+"%"),c.push(f)}for(d=1,e=c.length-1;d<e;d++)if(!c[d].offset){var h=Q(c[d-1].offset||0),i=0;for(var j=d+1;j<e;j++)if(c[j].offset){i=c[j].offset;break}i||(i=100,j=e),i=Q(i);var k=(i-h)/(j-d+1);for(;d<j;d++)h+=k,c[d].offset=h+"%"}return c}),bT=a._tear=function(a,b){a==b.top&&(b.top=a.prev),a==b.bottom&&(b.bottom=a.next),a.next&&(a.next.prev=a.prev),a.prev&&(a.prev.next=a.next)},bU=a._tofront=function(a,b){b.top!==a&&(bT(a,b),a.next=null,a.prev=b.top,b.top.next=a,b.top=a)},bV=a._toback=function(a,b){b.bottom!==a&&(bT(a,b),a.next=b.bottom,a.prev=null,b.bottom.prev=a,b.bottom=a)},bW=a._insertafter=function(a,b,c){bT(a,c),b==c.top&&(c.top=a),b.next&&(b.next.prev=a),a.next=b.next,a.prev=b,b.next=a},bX=a._insertbefore=function(a,b,c){bT(a,c),b==c.bottom&&(c.bottom=a),b.prev&&(b.prev.next=a),a.prev=b.prev,b.prev=a,a.next=b},bY=a.toMatrix=function(a,b){var c=bI(a),d={_:{transform:p},getBBox:function(){return c}};b$(d,b);return d.matrix},bZ=a.transformPath=function(a,b){return bj(a,bY(a,b))},b$=a._extractTransform=function(b,c){if(c==null)return b._.transform;c=r(c).replace(/\.{3}|\u2026/g,b._.transform||p);var d=a.parseTransformString(c),e=0,f=0,g=0,h=1,i=1,j=b._,k=new cb;j.transform=d||[];if(d)for(var l=0,m=d.length;l<m;l++){var n=d[l],o=n.length,q=r(n[0]).toLowerCase(),s=n[0]!=q,t=s?k.invert():0,u,v,w,x,y;q=="t"&&o==3?s?(u=t.x(0,0),v=t.y(0,0),w=t.x(n[1],n[2]),x=t.y(n[1],n[2]),k.translate(w-u,x-v)):k.translate(n[1],n[2]):q=="r"?o==2?(y=y||b.getBBox(1),k.rotate(n[1],y.x+y.width/2,y.y+y.height/2),e+=n[1]):o==4&&(s?(w=t.x(n[2],n[3]),x=t.y(n[2],n[3]),k.rotate(n[1],w,x)):k.rotate(n[1],n[2],n[3]),e+=n[1]):q=="s"?o==2||o==3?(y=y||b.getBBox(1),k.scale(n[1],n[o-1],y.x+y.width/2,y.y+y.height/2),h*=n[1],i*=n[o-1]):o==5&&(s?(w=t.x(n[3],n[4]),x=t.y(n[3],n[4]),k.scale(n[1],n[2],w,x)):k.scale(n[1],n[2],n[3],n[4]),h*=n[1],i*=n[2]):q=="m"&&o==7&&k.add(n[1],n[2],n[3],n[4],n[5],n[6]),j.dirtyT=1,b.matrix=k}b.matrix=k,j.sx=h,j.sy=i,j.deg=e,j.dx=f=k.e,j.dy=g=k.f,h==1&&i==1&&!e&&j.bbox?(j.bbox.x+=+f,j.bbox.y+=+g):j.dirtyT=1},b_=function(a){var b=a[0];switch(b.toLowerCase()){case"t":return[b,0,0];case"m":return[b,1,0,0,1,0,0];case"r":return a.length==4?[b,0,a[2],a[3]]:[b,0];case"s":return a.length==5?[b,1,1,a[3],a[4]]:a.length==3?[b,1,1]:[b,1]}},ca=a._equaliseTransform=function(b,c){c=r(c).replace(/\.{3}|\u2026/g,b),b=a.parseTransformString(b)||[],c=a.parseTransformString(c)||[];var d=x(b.length,c.length),e=[],f=[],g=0,h,i,j,k;for(;g<d;g++){j=b[g]||b_(c[g]),k=c[g]||b_(j);if(j[0]!=k[0]||j[0].toLowerCase()=="r"&&(j[2]!=k[2]||j[3]!=k[3])||j[0].toLowerCase()=="s"&&(j[3]!=k[3]||j[4]!=k[4]))return;e[g]=[],f[g]=[];for(h=0,i=x(j.length,k.length);h<i;h++)h in j&&(e[g][h]=j[h]),h in k&&(f[g][h]=k[h])}return{from:e,to:f}};a._getContainer=function(b,c,d,e){var f;f=e==null&&!a.is(b,"object")?h.doc.getElementById(b):b;if(f!=null){if(f.tagName)return c==null?{container:f,width:f.style.pixelWidth||f.offsetWidth,height:f.style.pixelHeight||f.offsetHeight}:{container:f,width:c,height:d};return{container:1,x:b,y:c,width:d,height:e}}},a.pathToRelative=bK,a._engine={},a.path2curve=bR,a.matrix=function(a,b,c,d,e,f){return new cb(a,b,c,d,e,f)},function(b){function d(a){var b=w.sqrt(c(a));a[0]&&(a[0]/=b),a[1]&&(a[1]/=b)}function c(a){return a[0]*a[0]+a[1]*a[1]}b.add=function(a,b,c,d,e,f){var g=[[],[],[]],h=[[this.a,this.c,this.e],[this.b,this.d,this.f],[0,0,1]],i=[[a,c,e],[b,d,f],[0,0,1]],j,k,l,m;a&&a instanceof cb&&(i=[[a.a,a.c,a.e],[a.b,a.d,a.f],[0,0,1]]);for(j=0;j<3;j++)for(k=0;k<3;k++){m=0;for(l=0;l<3;l++)m+=h[j][l]*i[l][k];g[j][k]=m}this.a=g[0][0],this.b=g[1][0],this.c=g[0][1],this.d=g[1][1],this.e=g[0][2],this.f=g[1][2]},b.invert=function(){var a=this,b=a.a*a.d-a.b*a.c;return new cb(a.d/b,-a.b/b,-a.c/b,a.a/b,(a.c*a.f-a.d*a.e)/b,(a.b*a.e-a.a*a.f)/b)},b.clone=function(){return new cb(this.a,this.b,this.c,this.d,this.e,this.f)},b.translate=function(a,b){this.add(1,0,0,1,a,b)},b.scale=function(a,b,c,d){b==null&&(b=a),(c||d)&&this.add(1,0,0,1,c,d),this.add(a,0,0,b,0,0),(c||d)&&this.add(1,0,0,1,-c,-d)},b.rotate=function(b,c,d){b=a.rad(b),c=c||0,d=d||0;var e=+w.cos(b).toFixed(9),f=+w.sin(b).toFixed(9);this.add(e,f,-f,e,c,d),this.add(1,0,0,1,-c,-d)},b.x=function(a,b){return a*this.a+b*this.c+this.e},b.y=function(a,b){return a*this.b+b*this.d+this.f},b.get=function(a){return+this[r.fromCharCode(97+a)].toFixed(4)},b.toString=function(){return a.svg?"matrix("+[this.get(0),this.get(1),this.get(2),this.get(3),this.get(4),this.get(5)].join()+")":[this.get(0),this.get(2),this.get(1),this.get(3),0,0].join()},b.toFilter=function(){return"progid:DXImageTransform.Microsoft.Matrix(M11="+this.get(0)+", M12="+this.get(2)+", M21="+this.get(1)+", M22="+this.get(3)+", Dx="+this.get(4)+", Dy="+this.get(5)+", sizingmethod='auto expand')"},b.offset=function(){return[this.e.toFixed(4),this.f.toFixed(4)]},b.split=function(){var b={};b.dx=this.e,b.dy=this.f;var e=[[this.a,this.c],[this.b,this.d]];b.scalex=w.sqrt(c(e[0])),d(e[0]),b.shear=e[0][0]*e[1][0]+e[0][1]*e[1][1],e[1]=[e[1][0]-e[0][0]*b.shear,e[1][1]-e[0][1]*b.shear],b.scaley=w.sqrt(c(e[1])),d(e[1]),b.shear/=b.scaley;var f=-e[0][1],g=e[1][1];g<0?(b.rotate=a.deg(w.acos(g)),f<0&&(b.rotate=360-b.rotate)):b.rotate=a.deg(w.asin(f)),b.isSimple=!+b.shear.toFixed(9)&&(b.scalex.toFixed(9)==b.scaley.toFixed(9)||!b.rotate),b.isSuperSimple=!+b.shear.toFixed(9)&&b.scalex.toFixed(9)==b.scaley.toFixed(9)&&!b.rotate,b.noRotation=!+b.shear.toFixed(9)&&!b.rotate;return b},b.toTransformString=function(a){var b=a||this[s]();if(b.isSimple){b.scalex=+b.scalex.toFixed(4),b.scaley=+b.scaley.toFixed(4),b.rotate=+b.rotate.toFixed(4);return(b.dx||b.dy?"t"+[b.dx,b.dy]:p)+(b.scalex!=1||b.scaley!=1?"s"+[b.scalex,b.scaley,0,0]:p)+(b.rotate?"r"+[b.rotate,0,0]:p)}return"m"+[this.get(0),this.get(1),this.get(2),this.get(3),this.get(4),this.get(5)]}}(cb.prototype);var cc=navigator.userAgent.match(/Version\/(.*?)\s/)||navigator.userAgent.match(/Chrome\/(\d+)/);navigator.vendor=="Apple Computer, Inc."&&(cc&&cc[1]<4||navigator.platform.slice(0,2)=="iP")||navigator.vendor=="Google Inc."&&cc&&cc[1]<8?k.safari=function(){var a=this.rect(-99,-99,this.width+99,this.height+99).attr({stroke:"none"});setTimeout(function(){a.remove()})}:k.safari=be;var cd=function(){this.returnValue=!1},ce=function(){return this.originalEvent.preventDefault()},cf=function(){this.cancelBubble=!0},cg=function(){return this.originalEvent.stopPropagation()},ch=function(){if(h.doc.addEventListener)return function(a,b,c,d){var e=o&&u[b]?u[b]:b,f=function(e){var f=h.doc.documentElement.scrollTop||h.doc.body.scrollTop,i=h.doc.documentElement.scrollLeft||h.doc.body.scrollLeft,j=e.clientX+i,k=e.clientY+f;if(o&&u[g](b))for(var l=0,m=e.targetTouches&&e.targetTouches.length;l<m;l++)if(e.targetTouches[l].target==a){var n=e;e=e.targetTouches[l],e.originalEvent=n,e.preventDefault=ce,e.stopPropagation=cg;break}return c.call(d,e,j,k)};a.addEventListener(e,f,!1);return function(){a.removeEventListener(e,f,!1);return!0}};if(h.doc.attachEvent)return function(a,b,c,d){var e=function(a){a=a||h.win.event;var b=h.doc.documentElement.scrollTop||h.doc.body.scrollTop,e=h.doc.documentElement.scrollLeft||h.doc.body.scrollLeft,f=a.clientX+e,g=a.clientY+b;a.preventDefault=a.preventDefault||cd,a.stopPropagation=a.stopPropagation||cf;return c.call(d,a,f,g)};a.attachEvent("on"+b,e);var f=function(){a.detachEvent("on"+b,e);return!0};return f}}(),ci=[],cj=function(a){var b=a.clientX,c=a.clientY,d=h.doc.documentElement.scrollTop||h.doc.body.scrollTop,e=h.doc.documentElement.scrollLeft||h.doc.body.scrollLeft,f,g=ci.length;while(g--){f=ci[g];if(o){var i=a.touches.length,j;while(i--){j=a.touches[i];if(j.identifier==f.el._drag.id){b=j.clientX,c=j.clientY,(a.originalEvent?a.originalEvent:a).preventDefault();break}}}else a.preventDefault();var k=f.el.node,l,m=k.nextSibling,n=k.parentNode,p=k.style.display;h.win.opera&&n.removeChild(k),k.style.display="none",l=f.el.paper.getElementByPoint(b,c),k.style.display=p,h.win.opera&&(m?n.insertBefore(k,m):n.appendChild(k)),l&&eve("raphael.drag.over."+f.el.id,f.el,l),b+=e,c+=d,eve("raphael.drag.move."+f.el.id,f.move_scope||f.el,b-f.el._drag.x,c-f.el._drag.y,b,c,a)}},ck=function(b){a.unmousemove(cj).unmouseup(ck);var c=ci.length,d;while(c--)d=ci[c],d.el._drag={},eve("raphael.drag.end."+d.el.id,d.end_scope||d.start_scope||d.move_scope||d.el,b);ci=[]},cl=a.el={};for(var cm=t.length;cm--;)(function(b){a[b]=cl[b]=function(c,d){a.is(c,"function")&&(this.events=this.events||[],this.events.push({name:b,f:c,unbind:ch(this.shape||this.node||h.doc,b,c,d||this)}));return this},a["un"+b]=cl["un"+b]=function(a){var c=this.events||[],d=c.length;while(d--)if(c[d].name==b&&c[d].f==a){c[d].unbind(),c.splice(d,1),!c.length&&delete this.events;return this}return this}})(t[cm]);cl.data=function(b,c){var d=bb[this.id]=bb[this.id]||{};if(arguments.length==1){if(a.is(b,"object")){for(var e in b)b[g](e)&&this.data(e,b[e]);return this}eve("raphael.data.get."+this.id,this,d[b],b);return d[b]}d[b]=c,eve("raphael.data.set."+this.id,this,c,b);return this},cl.removeData=function(a){a==null?bb[this.id]={}:bb[this.id]&&delete bb[this.id][a];return this},cl.hover=function(a,b,c,d){return this.mouseover(a,c).mouseout(b,d||c)},cl.unhover=function(a,b){return this.unmouseover(a).unmouseout(b)};var cn=[];cl.drag=function(b,c,d,e,f,g){function i(i){(i.originalEvent||i).preventDefault();var j=h.doc.documentElement.scrollTop||h.doc.body.scrollTop,k=h.doc.documentElement.scrollLeft||h.doc.body.scrollLeft;this._drag.x=i.clientX+k,this._drag.y=i.clientY+j,this._drag.id=i.identifier,!ci.length&&a.mousemove(cj).mouseup(ck),ci.push({el:this,move_scope:e,start_scope:f,end_scope:g}),c&&eve.on("raphael.drag.start."+this.id,c),b&&eve.on("raphael.drag.move."+this.id,b),d&&eve.on("raphael.drag.end."+this.id,d),eve("raphael.drag.start."+this.id,f||e||this,i.clientX+k,i.clientY+j,i)}this._drag={},cn.push({el:this,start:i}),this.mousedown(i);return this},cl.onDragOver=function(a){a?eve.on("raphael.drag.over."+this.id,a):eve.unbind("raphael.drag.over."+this.id)},cl.undrag=function(){var b=cn.length;while(b--)cn[b].el==this&&(this.unmousedown(cn[b].start),cn.splice(b,1),eve.unbind("raphael.drag.*."+this.id));!cn.length&&a.unmousemove(cj).unmouseup(ck)},k.circle=function(b,c,d){var e=a._engine.circle(this,b||0,c||0,d||0);this.__set__&&this.__set__.push(e);return e},k.rect=function(b,c,d,e,f){var g=a._engine.rect(this,b||0,c||0,d||0,e||0,f||0);this.__set__&&this.__set__.push(g);return g},k.ellipse=function(b,c,d,e){var f=a._engine.ellipse(this,b||0,c||0,d||0,e||0);this.__set__&&this.__set__.push(f);return f},k.path=function(b){b&&!a.is(b,D)&&!a.is(b[0],E)&&(b+=p);var c=a._engine.path(a.format[m](a,arguments),this);this.__set__&&this.__set__.push(c);return c},k.image=function(b,c,d,e,f){var g=a._engine.image(this,b||"about:blank",c||0,d||0,e||0,f||0);this.__set__&&this.__set__.push(g);return g},k.text=function(b,c,d){var e=a._engine.text(this,b||0,c||0,r(d));this.__set__&&this.__set__.push(e);return e},k.set=function(b){!a.is(b,"array")&&(b=Array.prototype.splice.call(arguments,0,arguments.length));var c=new cG(b);this.__set__&&this.__set__.push(c);return c},k.setStart=function(a){this.__set__=a||this.set()},k.setFinish=function(a){var b=this.__set__;delete this.__set__;return b},k.setSize=function(b,c){return a._engine.setSize.call(this,b,c)},k.setViewBox=function(b,c,d,e,f){return a._engine.setViewBox.call(this,b,c,d,e,f)},k.top=k.bottom=null,k.raphael=a;var co=function(a){var b=a.getBoundingClientRect(),c=a.ownerDocument,d=c.body,e=c.documentElement,f=e.clientTop||d.clientTop||0,g=e.clientLeft||d.clientLeft||0,i=b.top+(h.win.pageYOffset||e.scrollTop||d.scrollTop)-f,j=b.left+(h.win.pageXOffset||e.scrollLeft||d.scrollLeft)-g;return{y:i,x:j}};k.getElementByPoint=function(a,b){var c=this,d=c.canvas,e=h.doc.elementFromPoint(a,b);if(h.win.opera&&e.tagName=="svg"){var f=co(d),g=d.createSVGRect();g.x=a-f.x,g.y=b-f.y,g.width=g.height=1;var i=d.getIntersectionList(g,null);i.length&&(e=i[i.length-1])}if(!e)return null;while(e.parentNode&&e!=d.parentNode&&!e.raphael)e=e.parentNode;e==c.canvas.parentNode&&(e=d),e=e&&e.raphael?c.getById(e.raphaelid):null;return e},k.getById=function(a){var b=this.bottom;while(b){if(b.id==a)return b;b=b.next}return null},k.forEach=function(a,b){var c=this.bottom;while(c){if(a.call(b,c)===!1)return this;c=c.next}return this},k.getElementsByPoint=function(a,b){var c=this.set();this.forEach(function(d){d.isPointInside(a,b)&&c.push(d)});return c},cl.isPointInside=function(b,c){var d=this.realPath=this.realPath||bi[this.type](this);return a.isPointInsidePath(d,b,c)},cl.getBBox=function(a){if(this.removed)return{};var b=this._;if(a){if(b.dirty||!b.bboxwt)this.realPath=bi[this.type](this),b.bboxwt=bI(this.realPath),b.bboxwt.toString=cq,b.dirty=0;return b.bboxwt}if(b.dirty||b.dirtyT||!b.bbox){if(b.dirty||!this.realPath)b.bboxwt=0,this.realPath=bi[this.type](this);b.bbox=bI(bj(this.realPath,this.matrix)),b.bbox.toString=cq,b.dirty=b.dirtyT=0}return b.bbox},cl.clone=function(){if(this.removed)return null;var a=this.paper[this.type]().attr(this.attr());this.__set__&&this.__set__.push(a);return a},cl.glow=function(a){if(this.type=="text")return null;a=a||{};var b={width:(a.width||10)+(+this.attr("stroke-width")||1),fill:a.fill||!1,opacity:a.opacity||.5,offsetx:a.offsetx||0,offsety:a.offsety||0,color:a.color||"#000"},c=b.width/2,d=this.paper,e=d.set(),f=this.realPath||bi[this.type](this);f=this.matrix?bj(f,this.matrix):f;for(var g=1;g<c+1;g++)e.push(d.path(f).attr({stroke:b.color,fill:b.fill?b.color:"none","stroke-linejoin":"round","stroke-linecap":"round","stroke-width":+(b.width/c*g).toFixed(3),opacity:+(b.opacity/c).toFixed(3)}));return e.insertBefore(this).translate(b.offsetx,b.offsety)};var cr={},cs=function(b,c,d,e,f,g,h,i,j){return j==null?bB(b,c,d,e,f,g,h,i):a.findDotsAtSegment(b,c,d,e,f,g,h,i,bC(b,c,d,e,f,g,h,i,j))},ct=function(b,c){return function(d,e,f){d=bR(d);var g,h,i,j,k="",l={},m,n=0;for(var o=0,p=d.length;o<p;o++){i=d[o];if(i[0]=="M")g=+i[1],h=+i[2];else{j=cs(g,h,i[1],i[2],i[3],i[4],i[5],i[6]);if(n+j>e){if(c&&!l.start){m=cs(g,h,i[1],i[2],i[3],i[4],i[5],i[6],e-n),k+=["C"+m.start.x,m.start.y,m.m.x,m.m.y,m.x,m.y];if(f)return k;l.start=k,k=["M"+m.x,m.y+"C"+m.n.x,m.n.y,m.end.x,m.end.y,i[5],i[6]].join(),n+=j,g=+i[5],h=+i[6];continue}if(!b&&!c){m=cs(g,h,i[1],i[2],i[3],i[4],i[5],i[6],e-n);return{x:m.x,y:m.y,alpha:m.alpha}}}n+=j,g=+i[5],h=+i[6]}k+=i.shift()+i}l.end=k,m=b?n:c?l:a.findDotsAtSegment(g,h,i[0],i[1],i[2],i[3],i[4],i[5],1),m.alpha&&(m={x:m.x,y:m.y,alpha:m.alpha});return m}},cu=ct(1),cv=ct(),cw=ct(0,1);a.getTotalLength=cu,a.getPointAtLength=cv,a.getSubpath=function(a,b,c){if(this.getTotalLength(a)-c<1e-6)return cw(a,b).end;var d=cw(a,c,1);return b?cw(d,b).end:d},cl.getTotalLength=function(){if(this.type=="path"){if(this.node.getTotalLength)return this.node.getTotalLength();return cu(this.attrs.path)}},cl.getPointAtLength=function(a){if(this.type=="path")return cv(this.attrs.path,a)},cl.getSubpath=function(b,c){if(this.type=="path")return a.getSubpath(this.attrs.path,b,c)};var cx=a.easing_formulas={linear:function(a){return a},"<":function(a){return A(a,1.7)},">":function(a){return A(a,.48)},"<>":function(a){var b=.48-a/1.04,c=w.sqrt(.1734+b*b),d=c-b,e=A(z(d),1/3)*(d<0?-1:1),f=-c-b,g=A(z(f),1/3)*(f<0?-1:1),h=e+g+.5;return(1-h)*3*h*h+h*h*h},backIn:function(a){var b=1.70158;return a*a*((b+1)*a-b)},backOut:function(a){a=a-1;var b=1.70158;return a*a*((b+1)*a+b)+1},elastic:function(a){if(a==!!a)return a;return A(2,-10*a)*w.sin((a-.075)*2*B/.3)+1},bounce:function(a){var b=7.5625,c=2.75,d;a<1/c?d=b*a*a:a<2/c?(a-=1.5/c,d=b*a*a+.75):a<2.5/c?(a-=2.25/c,d=b*a*a+.9375):(a-=2.625/c,d=b*a*a+.984375);return d}};cx.easeIn=cx["ease-in"]=cx["<"],cx.easeOut=cx["ease-out"]=cx[">"],cx.easeInOut=cx["ease-in-out"]=cx["<>"],cx["back-in"]=cx.backIn,cx["back-out"]=cx.backOut;var cy=[],cz=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.oRequestAnimationFrame||window.msRequestAnimationFrame||function(a){setTimeout(a,16)},cA=function(){var b=+(new Date),c=0;for(;c<cy.length;c++){var d=cy[c];if(d.el.removed||d.paused)continue;var e=b-d.start,f=d.ms,h=d.easing,i=d.from,j=d.diff,k=d.to,l=d.t,m=d.el,o={},p,r={},s;d.initstatus?(e=(d.initstatus*d.anim.top-d.prev)/(d.percent-d.prev)*f,d.status=d.initstatus,delete d.initstatus,d.stop&&cy.splice(c--,1)):d.status=(d.prev+(d.percent-d.prev)*(e/f))/d.anim.top;if(e<0)continue;if(e<f){var t=h(e/f);for(var u in i)if(i[g](u)){switch(U[u]){case C:p=+i[u]+t*f*j[u];break;case"colour":p="rgb("+[cB(O(i[u].r+t*f*j[u].r)),cB(O(i[u].g+t*f*j[u].g)),cB(O(i[u].b+t*f*j[u].b))].join(",")+")";break;case"path":p=[];for(var v=0,w=i[u].length;v<w;v++){p[v]=[i[u][v][0]];for(var x=1,y=i[u][v].length;x<y;x++)p[v][x]=+i[u][v][x]+t*f*j[u][v][x];p[v]=p[v].join(q)}p=p.join(q);break;case"transform":if(j[u].real){p=[];for(v=0,w=i[u].length;v<w;v++){p[v]=[i[u][v][0]];for(x=1,y=i[u][v].length;x<y;x++)p[v][x]=i[u][v][x]+t*f*j[u][v][x]}}else{var z=function(a){return+i[u][a]+t*f*j[u][a]};p=[["m",z(0),z(1),z(2),z(3),z(4),z(5)]]}break;case"csv":if(u=="clip-rect"){p=[],v=4;while(v--)p[v]=+i[u][v]+t*f*j[u][v]}break;default:var A=[][n](i[u]);p=[],v=m.paper.customAttributes[u].length;while(v--)p[v]=+A[v]+t*f*j[u][v]}o[u]=p}m.attr(o),function(a,b,c){setTimeout(function(){eve("raphael.anim.frame."+a,b,c)})}(m.id,m,d.anim)}else{(function(b,c,d){setTimeout(function(){eve("raphael.anim.frame."+c.id,c,d),eve("raphael.anim.finish."+c.id,c,d),a.is(b,"function")&&b.call(c)})})(d.callback,m,d.anim),m.attr(k),cy.splice(c--,1);if(d.repeat>1&&!d.next){for(s in k)k[g](s)&&(r[s]=d.totalOrigin[s]);d.el.attr(r),cE(d.anim,d.el,d.anim.percents[0],null,d.totalOrigin,d.repeat-1)}d.next&&!d.stop&&cE(d.anim,d.el,d.next,null,d.totalOrigin,d.repeat)}}a.svg&&m&&m.paper&&m.paper.safari(),cy.length&&cz(cA)},cB=function(a){return a>255?255:a<0?0:a};cl.animateWith=function(b,c,d,e,f,g){var h=this;if(h.removed){g&&g.call(h);return h}var i=d instanceof cD?d:a.animation(d,e,f,g),j,k;cE(i,h,i.percents[0],null,h.attr());for(var l=0,m=cy.length;l<m;l++)if(cy[l].anim==c&&cy[l].el==b){cy[m-1].start=cy[l].start;break}return h},cl.onAnimation=function(a){a?eve.on("raphael.anim.frame."+this.id,a):eve.unbind("raphael.anim.frame."+this.id);return this},cD.prototype.delay=function(a){var b=new cD(this.anim,this.ms);b.times=this.times,b.del=+a||0;return b},cD.prototype.repeat=function(a){var b=new cD(this.anim,this.ms);b.del=this.del,b.times=w.floor(x(a,0))||1;return b},a.animation=function(b,c,d,e){if(b instanceof cD)return b;if(a.is(d,"function")||!d)e=e||d||null,d=null;b=Object(b),c=+c||0;var f={},h,i;for(i in b)b[g](i)&&Q(i)!=i&&Q(i)+"%"!=i&&(h=!0,f[i]=b[i]);if(!h)return new cD(b,c);d&&(f.easing=d),e&&(f.callback=e);return new cD({100:f},c)},cl.animate=function(b,c,d,e){var f=this;if(f.removed){e&&e.call(f);return f}var g=b instanceof cD?b:a.animation(b,c,d,e);cE(g,f,g.percents[0],null,f.attr());return f},cl.setTime=function(a,b){a&&b!=null&&this.status(a,y(b,a.ms)/a.ms);return this},cl.status=function(a,b){var c=[],d=0,e,f;if(b!=null){cE(a,this,-1,y(b,1));return this}e=cy.length;for(;d<e;d++){f=cy[d];if(f.el.id==this.id&&(!a||f.anim==a)){if(a)return f.status;c.push({anim:f.anim,status:f.status})}}if(a)return 0;return c},cl.pause=function(a){for(var b=0;b<cy.length;b++)cy[b].el.id==this.id&&(!a||cy[b].anim==a)&&eve("raphael.anim.pause."+this.id,this,cy[b].anim)!==!1&&(cy[b].paused=!0);return this},cl.resume=function(a){for(var b=0;b<cy.length;b++)if(cy[b].el.id==this.id&&(!a||cy[b].anim==a)){var c=cy[b];eve("raphael.anim.resume."+this.id,this,c.anim)!==!1&&(delete c.paused,this.status(c.anim,c.status))}return this},cl.stop=function(a){for(var b=0;b<cy.length;b++)cy[b].el.id==this.id&&(!a||cy[b].anim==a)&&eve("raphael.anim.stop."+this.id,this,cy[b].anim)!==!1&&cy.splice(b--,1);return this},eve.on("raphael.remove",cF),eve.on("raphael.clear",cF),cl.toString=function(){return"Raphals object"};var cG=function(a){this.items=[],this.length=0,this.type="set";if(a)for(var b=0,c=a.length;b<c;b++)a[b]&&(a[b].constructor==cl.constructor||a[b].constructor==cG)&&(this[this.items.length]=this.items[this.items.length]=a[b],this.length++)},cH=cG.prototype;cH.push=function(){var a,b;for(var c=0,d=arguments.length;c<d;c++)a=arguments[c],a&&(a.constructor==cl.constructor||a.constructor==cG)&&(b=this.items.length,this[b]=this.items[b]=a,this.length++);return this},cH.pop=function(){this.length&&delete this[this.length--];return this.items.pop()},cH.forEach=function(a,b){for(var c=0,d=this.items.length;c<d;c++)if(a.call(b,this.items[c],c)===!1)return this;return this};for(var cI in cl)cl[g](cI)&&(cH[cI]=function(a){return function(){var b=arguments;return this.forEach(function(c){c[a][m](c,b)})}}(cI));cH.attr=function(b,c){if(b&&a.is(b,E)&&a.is(b[0],"object"))for(var d=0,e=b.length;d<e;d++)this.items[d].attr(b[d]);else for(var f=0,g=this.items.length;f<g;f++)this.items[f].attr(b,c);return this},cH.clear=function(){while(this.length)this.pop()},cH.splice=function(a,b,c){a=a<0?x(this.length+a,0):a,b=x(0,y(this.length-a,b));var d=[],e=[],f=[],g;for(g=2;g<arguments.length;g++)f.push(arguments[g]);for(g=0;g<b;g++)e.push(this[a+g]);for(;g<this.length-a;g++)d.push(this[a+g]);var h=f.length;for(g=0;g<h+d.length;g++)this.items[a+g]=this[a+g]=g<h?f[g]:d[g-h];g=this.items.length=this.length-=b-h;while(this[g])delete this[g++];return new cG(e)},cH.exclude=function(a){for(var b=0,c=this.length;b<c;b++)if(this[b]==a){this.splice(b,1);return!0}},cH.animate=function(b,c,d,e){(a.is(d,"function")||!d)&&(e=d||null);var f=this.items.length,g=f,h,i=this,j;if(!f)return this;e&&(j=function(){!--f&&e.call(i)}),d=a.is(d,D)?d:j;var k=a.animation(b,c,d,j);h=this.items[--g].animate(k);while(g--)this.items[g]&&!this.items[g].removed&&this.items[g].animateWith(h,k,k);return this},cH.insertAfter=function(a){var b=this.items.length;while(b--)this.items[b].insertAfter(a);return this},cH.getBBox=function(){var a=[],b=[],c=[],d=[];for(var e=this.items.length;e--;)if(!this.items[e].removed){var f=this.items[e].getBBox();a.push(f.x),b.push(f.y),c.push(f.x+f.width),d.push(f.y+f.height)}a=y[m](0,a),b=y[m](0,b),c=x[m](0,c),d=x[m](0,d);return{x:a,y:b,x2:c,y2:d,width:c-a,height:d-b}},cH.clone=function(a){a=new cG;for(var b=0,c=this.items.length;b<c;b++)a.push(this.items[b].clone());return a},cH.toString=function(){return"Raphals set"},a.registerFont=function(a){if(!a.face)return a;this.fonts=this.fonts||{};var b={w:a.w,face:{},glyphs:{}},c=a.face["font-family"];for(var d in a.face)a.face[g](d)&&(b.face[d]=a.face[d]);this.fonts[c]?this.fonts[c].push(b):this.fonts[c]=[b];if(!a.svg){b.face["units-per-em"]=R(a.face["units-per-em"],10);for(var e in a.glyphs)if(a.glyphs[g](e)){var f=a.glyphs[e];b.glyphs[e]={w:f.w,k:{},d:f.d&&"M"+f.d.replace(/[mlcxtrv]/g,function(a){return{l:"L",c:"C",x:"z",t:"m",r:"l",v:"c"}[a]||"M"})+"z"};if(f.k)for(var h in f.k)f[g](h)&&(b.glyphs[e].k[h]=f.k[h])}}return a},k.getFont=function(b,c,d,e){e=e||"normal",d=d||"normal",c=+c||{normal:400,bold:700,lighter:300,bolder:800}[c]||400;if(!!a.fonts){var f=a.fonts[b];if(!f){var h=new RegExp("(^|\\s)"+b.replace(/[^\w\d\s+!~.:_-]/g,p)+"(\\s|$)","i");for(var i in a.fonts)if(a.fonts[g](i)&&h.test(i)){f=a.fonts[i];break}}var j;if(f)for(var k=0,l=f.length;k<l;k++){j=f[k];if(j.face["font-weight"]==c&&(j.face["font-style"]==d||!j.face["font-style"])&&j.face["font-stretch"]==e)break}return j}},k.print=function(b,d,e,f,g,h,i){h=h||"middle",i=x(y(i||0,1),-1);var j=r(e)[s](p),k=0,l=0,m=p,n;a.is(f,e)&&(f=this.getFont(f));if(f){n=(g||16)/f.face["units-per-em"];var o=f.face.bbox[s](c),q=+o[0],t=o[3]-o[1],u=0,v=+o[1]+(h=="baseline"?t+ +f.face.descent:t/2);for(var w=0,z=j.length;w<z;w++){if(j[w]=="\n")k=0,B=0,l=0,u+=t;else{var A=l&&f.glyphs[j[w-1]]||{},B=f.glyphs[j[w]];k+=l?(A.w||f.w)+(A.k&&A.k[j[w]]||0)+f.w*i:0,l=1}B&&B.d&&(m+=a.transformPath(B.d,["t",k*n,u*n,"s",n,n,q,v,"t",(b-q)/n,(d-v)/n]))}}return this.path(m).attr({fill:"#000",stroke:"none"})},k.add=function(b){if(a.is(b,"array")){var c=this.set(),e=0,f=b.length,h;for(;e<f;e++)h=b[e]||{},d[g](h.type)&&c.push(this[h.type]().attr(h))}return c},a.format=function(b,c){var d=a.is(c,E)?[0][n](c):arguments;b&&a.is(b,D)&&d.length-1&&(b=b.replace(e,function(a,b){return d[++b]==null?p:d[b]}));return b||p},a.fullfill=function(){var a=/\{([^\}]+)\}/g,b=/(?:(?:^|\.)(.+?)(?=\[|\.|$|\()|\[('|")(.+?)\2\])(\(\))?/g,c=function(a,c,d){var e=d;c.replace(b,function(a,b,c,d,f){b=b||d,e&&(b in e&&(e=e[b]),typeof e=="function"&&f&&(e=e()))}),e=(e==null||e==d?a:e)+"";return e};return function(b,d){return String(b).replace(a,function(a,b){return c(a,b,d)})}}(),a.ninja=function(){i.was?h.win.Raphael=i.is:delete Raphael;return a},a.st=cH,function(b,c,d){function e(){/in/.test(b.readyState)?setTimeout(e,9):a.eve("raphael.DOMload")}b.readyState==null&&b.addEventListener&&(b.addEventListener(c,d=function(){b.removeEventListener(c,d,!1),b.readyState="complete"},!1),b.readyState="loading"),e()}(document,"DOMContentLoaded"),i.was?h.win.Raphael=a:Raphael=a,eve.on("raphael.DOMload",function(){b=!0})}(),window.Raphael.svg&&function(a){var b="hasOwnProperty",c=String,d=parseFloat,e=parseInt,f=Math,g=f.max,h=f.abs,i=f.pow,j=/[, ]+/,k=a.eve,l="",m=" ",n="http://www.w3.org/1999/xlink",o={block:"M5,0 0,2.5 5,5z",classic:"M5,0 0,2.5 5,5 3.5,3 3.5,2z",diamond:"M2.5,0 5,2.5 2.5,5 0,2.5z",open:"M6,1 1,3.5 6,6",oval:"M2.5,0A2.5,2.5,0,0,1,2.5,5 2.5,2.5,0,0,1,2.5,0z"},p={};a.toString=function(){return"Your browser supports SVG.\nYou are running Raphal "+this.version};var q=function(d,e){if(e){typeof d=="string"&&(d=q(d));for(var f in e)e[b](f)&&(f.substring(0,6)=="xlink:"?d.setAttributeNS(n,f.substring(6),c(e[f])):d.setAttribute(f,c(e[f])))}else d=a._g.doc.createElementNS("http://www.w3.org/2000/svg",d),d.style&&(d.style.webkitTapHighlightColor="rgba(0,0,0,0)");return d},r=function(b,e){var j="linear",k=b.id+e,m=.5,n=.5,o=b.node,p=b.paper,r=o.style,s=a._g.doc.getElementById(k);if(!s){e=c(e).replace(a._radial_gradient,function(a,b,c){j="radial";if(b&&c){m=d(b),n=d(c);var e=(n>.5)*2-1;i(m-.5,2)+i(n-.5,2)>.25&&(n=f.sqrt(.25-i(m-.5,2))*e+.5)&&n!=.5&&(n=n.toFixed(5)-1e-5*e)}return l}),e=e.split(/\s*\-\s*/);if(j=="linear"){var t=e.shift();t=-d(t);if(isNaN(t))return null;var u=[0,0,f.cos(a.rad(t)),f.sin(a.rad(t))],v=1/(g(h(u[2]),h(u[3]))||1);u[2]*=v,u[3]*=v,u[2]<0&&(u[0]=-u[2],u[2]=0),u[3]<0&&(u[1]=-u[3],u[3]=0)}var w=a._parseDots(e);if(!w)return null;k=k.replace(/[\(\)\s,\xb0#]/g,"_"),b.gradient&&k!=b.gradient.id&&(p.defs.removeChild(b.gradient),delete b.gradient);if(!b.gradient){s=q(j+"Gradient",{id:k}),b.gradient=s,q(s,j=="radial"?{fx:m,fy:n}:{x1:u[0],y1:u[1],x2:u[2],y2:u[3],gradientTransform:b.matrix.invert()}),p.defs.appendChild(s);for(var x=0,y=w.length;x<y;x++)s.appendChild(q("stop",{offset:w[x].offset?w[x].offset:x?"100%":"0%","stop-color":w[x].color||"#fff"}))}}q(o,{fill:"url(#"+k+")",opacity:1,"fill-opacity":1}),r.fill=l,r.opacity=1,r.fillOpacity=1;return 1},s=function(a){var b=a.getBBox(1);q(a.pattern,{patternTransform:a.matrix.invert()+" translate("+b.x+","+b.y+")"})},t=function(d,e,f){if(d.type=="path"){var g=c(e).toLowerCase().split("-"),h=d.paper,i=f?"end":"start",j=d.node,k=d.attrs,m=k["stroke-width"],n=g.length,r="classic",s,t,u,v,w,x=3,y=3,z=5;while(n--)switch(g[n]){case"block":case"classic":case"oval":case"diamond":case"open":case"none":r=g[n];break;case"wide":y=5;break;case"narrow":y=2;break;case"long":x=5;break;case"short":x=2}r=="open"?(x+=2,y+=2,z+=2,u=1,v=f?4:1,w={fill:"none",stroke:k.stroke}):(v=u=x/2,w={fill:k.stroke,stroke:"none"}),d._.arrows?f?(d._.arrows.endPath&&p[d._.arrows.endPath]--,d._.arrows.endMarker&&p[d._.arrows.endMarker]--):(d._.arrows.startPath&&p[d._.arrows.startPath]--,d._.arrows.startMarker&&p[d._.arrows.startMarker]--):d._.arrows={};if(r!="none"){var A="raphael-marker-"+r,B="raphael-marker-"+i+r+x+y;a._g.doc.getElementById(A)?p[A]++:(h.defs.appendChild(q(q("path"),{"stroke-linecap":"round",d:o[r],id:A})),p[A]=1);var C=a._g.doc.getElementById(B),D;C?(p[B]++,D=C.getElementsByTagName("use")[0]):(C=q(q("marker"),{id:B,markerHeight:y,markerWidth:x,orient:"auto",refX:v,refY:y/2}),D=q(q("use"),{"xlink:href":"#"+A,transform:(f?"rotate(180 "+x/2+" "+y/2+") ":l)+"scale("+x/z+","+y/z+")","stroke-width":(1/((x/z+y/z)/2)).toFixed(4)}),C.appendChild(D),h.defs.appendChild(C),p[B]=1),q(D,w);var F=u*(r!="diamond"&&r!="oval");f?(s=d._.arrows.startdx*m||0,t=a.getTotalLength(k.path)-F*m):(s=F*m,t=a.getTotalLength(k.path)-(d._.arrows.enddx*m||0)),w={},w["marker-"+i]="url(#"+B+")";if(t||s)w.d=Raphael.getSubpath(k.path,s,t);q(j,w),d._.arrows[i+"Path"]=A,d._.arrows[i+"Marker"]=B,d._.arrows[i+"dx"]=F,d._.arrows[i+"Type"]=r,d._.arrows[i+"String"]=e}else f?(s=d._.arrows.startdx*m||0,t=a.getTotalLength(k.path)-s):(s=0,t=a.getTotalLength(k.path)-(d._.arrows.enddx*m||0)),d._.arrows[i+"Path"]&&q(j,{d:Raphael.getSubpath(k.path,s,t)}),delete d._.arrows[i+"Path"],delete d._.arrows[i+"Marker"],delete d._.arrows[i+"dx"],delete d._.arrows[i+"Type"],delete d._.arrows[i+"String"];for(w in p)if(p[b](w)&&!p[w]){var G=a._g.doc.getElementById(w);G&&G.parentNode.removeChild(G)}}},u={"":[0],none:[0],"-":[3,1],".":[1,1],"-.":[3,1,1,1],"-..":[3,1,1,1,1,1],". ":[1,3],"- ":[4,3],"--":[8,3],"- .":[4,3,1,3],"--.":[8,3,1,3],"--..":[8,3,1,3,1,3]},v=function(a,b,d){b=u[c(b).toLowerCase()];if(b){var e=a.attrs["stroke-width"]||"1",f={round:e,square:e,butt:0}[a.attrs["stroke-linecap"]||d["stroke-linecap"]]||0,g=[],h=b.length;while(h--)g[h]=b[h]*e+(h%2?1:-1)*f;q(a.node,{"stroke-dasharray":g.join(",")})}},w=function(d,f){var i=d.node,k=d.attrs,m=i.style.visibility;i.style.visibility="hidden";for(var o in f)if(f[b](o)){if(!a._availableAttrs[b](o))continue;var p=f[o];k[o]=p;switch(o){case"blur":d.blur(p);break;case"href":case"title":case"target":var u=i.parentNode;if(u.tagName.toLowerCase()!="a"){var w=q("a");u.insertBefore(w,i),w.appendChild(i),u=w}o=="target"?u.setAttributeNS(n,"show",p=="blank"?"new":p):u.setAttributeNS(n,o,p);break;case"cursor":i.style.cursor=p;break;case"transform":d.transform(p);break;case"arrow-start":t(d,p);break;case"arrow-end":t(d,p,1);break;case"clip-rect":var x=c(p).split(j);if(x.length==4){d.clip&&d.clip.parentNode.parentNode.removeChild(d.clip.parentNode);var z=q("clipPath"),A=q("rect");z.id=a.createUUID(),q(A,{x:x[0],y:x[1],width:x[2],height:x[3]}),z.appendChild(A),d.paper.defs.appendChild(z),q(i,{"clip-path":"url(#"+z.id+")"}),d.clip=A}if(!p){var B=i.getAttribute("clip-path");if(B){var C=a._g.doc.getElementById(B.replace(/(^url\(#|\)$)/g,l));C&&C.parentNode.removeChild(C),q(i,{"clip-path":l}),delete d.clip}}break;case"path":d.type=="path"&&(q(i,{d:p?k.path=a._pathToAbsolute(p):"M0,0"}),d._.dirty=1,d._.arrows&&("startString"in d._.arrows&&t(d,d._.arrows.startString),"endString"in d._.arrows&&t(d,d._.arrows.endString,1)));break;case"width":i.setAttribute(o,p),d._.dirty=1;if(k.fx)o="x",p=k.x;else break;case"x":k.fx&&(p=-k.x-(k.width||0));case"rx":if(o=="rx"&&d.type=="rect")break;case"cx":i.setAttribute(o,p),d.pattern&&s(d),d._.dirty=1;break;case"height":i.setAttribute(o,p),d._.dirty=1;if(k.fy)o="y",p=k.y;else break;case"y":k.fy&&(p=-k.y-(k.height||0));case"ry":if(o=="ry"&&d.type=="rect")break;case"cy":i.setAttribute(o,p),d.pattern&&s(d),d._.dirty=1;break;case"r":d.type=="rect"?q(i,{rx:p,ry:p}):i.setAttribute(o,p),d._.dirty=1;break;case"src":d.type=="image"&&i.setAttributeNS(n,"href",p);break;case"stroke-width":if(d._.sx!=1||d._.sy!=1)p/=g(h(d._.sx),h(d._.sy))||1;d.paper._vbSize&&(p*=d.paper._vbSize),i.setAttribute(o,p),k["stroke-dasharray"]&&v(d,k["stroke-dasharray"],f),d._.arrows&&("startString"in d._.arrows&&t(d,d._.arrows.startString),"endString"in d._.arrows&&t(d,d._.arrows.endString,1));break;case"stroke-dasharray":v(d,p,f);break;case"fill":var D=c(p).match(a._ISURL);if(D){z=q("pattern");var F=q("image");z.id=a.createUUID(),q(z,{x:0,y:0,patternUnits:"userSpaceOnUse",height:1,width:1}),q(F,{x:0,y:0,"xlink:href":D[1]}),z.appendChild(F),function(b){a._preload(D[1],function(){var a=this.offsetWidth,c=this.offsetHeight;q(b,{width:a,height:c}),q(F,{width:a,height:c}),d.paper.safari()})}(z),d.paper.defs.appendChild(z),q(i,{fill:"url(#"+z.id+")"}),d.pattern=z,d.pattern&&s(d);break}var G=a.getRGB(p);if(!G.error)delete f.gradient,delete k.gradient,!a.is(k.opacity,"undefined")&&a.is(f.opacity,"undefined")&&q(i,{opacity:k.opacity}),!a.is(k["fill-opacity"],"undefined")&&a.is(f["fill-opacity"],"undefined")&&q(i,{"fill-opacity":k["fill-opacity"]});else if((d.type=="circle"||d.type=="ellipse"||c(p).charAt()!="r")&&r(d,p)){if("opacity"in k||"fill-opacity"in k){var H=a._g.doc.getElementById(i.getAttribute("fill").replace(/^url\(#|\)$/g,l));if(H){var I=H.getElementsByTagName("stop");q(I[I.length-1],{"stop-opacity":("opacity"in k?k.opacity:1)*("fill-opacity"in k?k["fill-opacity"]:1)})}}k.gradient=p,k.fill="none";break}G[b]("opacity")&&q(i,{"fill-opacity":G.opacity>1?G.opacity/100:G.opacity});case"stroke":G=a.getRGB(p),i.setAttribute(o,G.hex),o=="stroke"&&G[b]("opacity")&&q(i,{"stroke-opacity":G.opacity>1?G.opacity/100:G.opacity}),o=="stroke"&&d._.arrows&&("startString"in d._.arrows&&t(d,d._.arrows.startString),"endString"in d._.arrows&&t(d,d._.arrows.endString,1));break;case"gradient":(d.type=="circle"||d.type=="ellipse"||c(p).charAt()!="r")&&r(d,p);break;case"opacity":k.gradient&&!k[b]("stroke-opacity")&&q(i,{"stroke-opacity":p>1?p/100:p});case"fill-opacity":if(k.gradient){H=a._g.doc.getElementById(i.getAttribute("fill").replace(/^url\(#|\)$/g,l)),H&&(I=H.getElementsByTagName("stop"),q(I[I.length-1],{"stop-opacity":p}));break};default:o=="font-size"&&(p=e(p,10)+"px");var J=o.replace(/(\-.)/g,function(a){return a.substring(1).toUpperCase()});i.style[J]=p,d._.dirty=1,i.setAttribute(o,p)}}y(d,f),i.style.visibility=m},x=1.2,y=function(d,f){if(d.type=="text"&&!!(f[b]("text")||f[b]("font")||f[b]("font-size")||f[b]("x")||f[b]("y"))){var g=d.attrs,h=d.node,i=h.firstChild?e(a._g.doc.defaultView.getComputedStyle(h.firstChild,l).getPropertyValue("font-size"),10):10;if(f[b]("text")){g.text=f.text;while(h.firstChild)h.removeChild(h.firstChild);var j=c(f.text).split("\n"),k=[],m;for(var n=0,o=j.length;n<o;n++)m=q("tspan"),n&&q(m,{dy:i*x,x:g.x}),m.appendChild(a._g.doc.createTextNode(j[n])),h.appendChild(m),k[n]=m}else{k=h.getElementsByTagName("tspan");for(n=0,o=k.length;n<o;n++)n?q(k[n],{dy:i*x,x:g.x}):q(k[0],{dy:0})}q(h,{x:g.x,y:g.y}),d._.dirty=1;var p=d._getBBox(),r=g.y-(p.y+p.height/2);r&&a.is(r,"finite")&&q(k[0],{dy:r})}},z=function(b,c){var d=0,e=0;this[0]=this.node=b,b.raphael=!0,this.id=a._oid++,b.raphaelid=this.id,this.matrix=a.matrix(),this.realPath=null,this.paper=c,this.attrs=this.attrs||{},this._={transform:[],sx:1,sy:1,deg:0,dx:0,dy:0,dirty:1},!c.bottom&&(c.bottom=this),this.prev=c.top,c.top&&(c.top.next=this),c.top=this,this.next=null},A=a.el;z.prototype=A,A.constructor=z,a._engine.path=function(a,b){var c=q("path");b.canvas&&b.canvas.appendChild(c);var d=new z(c,b);d.type="path",w(d,{fill:"none",stroke:"#000",path:a});return d},A.rotate=function(a,b,e){if(this.removed)return this;a=c(a).split(j),a.length-1&&(b=d(a[1]),e=d(a[2])),a=d(a[0]),e==null&&(b=e);if(b==null||e==null){var f=this.getBBox(1);b=f.x+f.width/2,e=f.y+f.height/2}this.transform(this._.transform.concat([["r",a,b,e]]));return this},A.scale=function(a,b,e,f){if(this.removed)return this;a=c(a).split(j),a.length-1&&(b=d(a[1]),e=d(a[2]),f=d(a[3])),a=d(a[0]),b==null&&(b=a),f==null&&(e=f);if(e==null||f==null)var g=this.getBBox(1);e=e==null?g.x+g.width/2:e,f=f==null?g.y+g.height/2:f,this.transform(this._.transform.concat([["s",a,b,e,f]]));return this},A.translate=function(a,b){if(this.removed)return this;a=c(a).split(j),a.length-1&&(b=d(a[1])),a=d(a[0])||0,b=+b||0,this.transform(this._.transform.concat([["t",a,b]]));return this},A.transform=function(c){var d=this._;if(c==null)return d.transform;a._extractTransform(this,c),this.clip&&q(this.clip,{transform:this.matrix.invert()}),this.pattern&&s(this),this.node&&q(this.node,{transform:this.matrix});if(d.sx!=1||d.sy!=1){var e=this.attrs[b]("stroke-width")?this.attrs["stroke-width"]:1;this.attr({"stroke-width":e})}return this},A.hide=function(){!this.removed&&this.paper.safari(this.node.style.display="none");return this},A.show=function(){!this.removed&&this.paper.safari(this.node.style.display="");return this},A.remove=function(){if(!this.removed&&!!this.node.parentNode){var b=this.paper;b.__set__&&b.__set__.exclude(this),k.unbind("raphael.*.*."+this.id),this.gradient&&b.defs.removeChild(this.gradient),a._tear(this,b),this.node.parentNode.tagName.toLowerCase()=="a"?this.node.parentNode.parentNode.removeChild(this.node.parentNode):this.node.parentNode.removeChild(this.node);for(var c in this)this[c]=typeof this[c]=="function"?a._removedFactory(c):null;this.removed=!0}},A._getBBox=function(){if(this.node.style.display=="none"){this.show();var a=!0}var b={};try{b=this.node.getBBox()}catch(c){}finally{b=b||{}}a&&this.hide();return b},A.attr=function(c,d){if(this.removed)return this;if(c==null){var e={};for(var f in this.attrs)this.attrs[b](f)&&(e[f]=this.attrs[f]);e.gradient&&e.fill=="none"&&(e.fill=e.gradient)&&delete e.gradient,e.transform=this._.transform;return e}if(d==null&&a.is(c,"string")){if(c=="fill"&&this.attrs.fill=="none"&&this.attrs.gradient)return this.attrs.gradient;if(c=="transform")return this._.transform;var g=c.split(j),h={};for(var i=0,l=g.length;i<l;i++)c=g[i],c in this.attrs?h[c]=this.attrs[c]:a.is(this.paper.customAttributes[c],"function")?h[c]=this.paper.customAttributes[c].def:h[c]=a._availableAttrs[c];return l-1?h:h[g[0]]}if(d==null&&a.is(c,"array")){h={};for(i=0,l=c.length;i<l;i++)h[c[i]]=this.attr(c[i]);return h}if(d!=null){var m={};m[c]=d}else c!=null&&a.is(c,"object")&&(m=c);for(var n in m)k("raphael.attr."+n+"."+this.id,this,m[n]);for(n in this.paper.customAttributes)if(this.paper.customAttributes[b](n)&&m[b](n)&&a.is(this.paper.customAttributes[n],"function")){var o=this.paper.customAttributes[n].apply(this,[].concat(m[n]));this.attrs[n]=m[n];for(var p in o)o[b](p)&&(m[p]=o[p])}w(this,m);return this},A.toFront=function(){if(this.removed)return this;this.node.parentNode.tagName.toLowerCase()=="a"?this.node.parentNode.parentNode.appendChild(this.node.parentNode):this.node.parentNode.appendChild(this.node);var b=this.paper;b.top!=this&&a._tofront(this,b);return this},A.toBack=function(){if(this.removed)return this;var b=this.node.parentNode;b.tagName.toLowerCase()=="a"?b.parentNode.insertBefore(this.node.parentNode,this.node.parentNode.parentNode.firstChild):b.firstChild!=this.node&&b.insertBefore(this.node,this.node.parentNode.firstChild),a._toback(this,this.paper);var c=this.paper;return this},A.insertAfter=function(b){if(this.removed)return this;var c=b.node||b[b.length-1].node;c.nextSibling?c.parentNode.insertBefore(this.node,c.nextSibling):c.parentNode.appendChild(this.node),a._insertafter(this,b,this.paper);return this},A.insertBefore=function(b){if(this.removed)return this;var c=b.node||b[0].node;c.parentNode.insertBefore(this.node,c),a._insertbefore(this,b,this.paper);return this},A.blur=function(b){var c=this;if(+b!==0){var d=q("filter"),e=q("feGaussianBlur");c.attrs.blur=b,d.id=a.createUUID(),q(e,{stdDeviation:+b||1.5}),d.appendChild(e),c.paper.defs.appendChild(d),c._blur=d,q(c.node,{filter:"url(#"+d.id+")"})}else c._blur&&(c._blur.parentNode.removeChild(c._blur),delete c._blur,delete c.attrs.blur),c.node.removeAttribute("filter")},a._engine.circle=function(a,b,c,d){var e=q("circle");a.canvas&&a.canvas.appendChild(e);var f=new z(e,a);f.attrs={cx:b,cy:c,r:d,fill:"none",stroke:"#000"},f.type="circle",q(e,f.attrs);return f},a._engine.rect=function(a,b,c,d,e,f){var g=q("rect");a.canvas&&a.canvas.appendChild(g);var h=new z(g,a);h.attrs={x:b,y:c,width:d,height:e,r:f||0,rx:f||0,ry:f||0,fill:"none",stroke:"#000"},h.type="rect",q(g,h.attrs);return h},a._engine.ellipse=function(a,b,c,d,e){var f=q("ellipse");a.canvas&&a.canvas.appendChild(f);var g=new z(f,a);g.attrs={cx:b,cy:c,rx:d,ry:e,fill:"none",stroke:"#000"},g.type="ellipse",q(f,g.attrs);return g},a._engine.image=function(a,b,c,d,e,f){var g=q("image");q(g,{x:c,y:d,width:e,height:f,preserveAspectRatio:"none"}),g.setAttributeNS(n,"href",b),a.canvas&&a.canvas.appendChild(g);var h=new z(g,a);h.attrs={x:c,y:d,width:e,height:f,src:b},h.type="image";return h},a._engine.text=function(b,c,d,e){var f=q("text");b.canvas&&b.canvas.appendChild(f);var g=new z(f,b);g.attrs={x:c,y:d,"text-anchor":"middle",text:e,font:a._availableAttrs.font,stroke:"none",fill:"#000"},g.type="text",w(g,g.attrs);return g},a._engine.setSize=function(a,b){this.width=a||this.width,this.height=b||this.height,this.canvas.setAttribute("width",this.width),this.canvas.setAttribute("height",this.height),this._viewBox&&this.setViewBox.apply(this,this._viewBox);return this},a._engine.create=function(){var b=a._getContainer.apply(0,arguments),c=b&&b.container,d=b.x,e=b.y,f=b.width,g=b.height;if(!c)throw new Error("SVG container not found.");var h=q("svg"),i="overflow:hidden;",j;d=d||0,e=e||0,f=f||512,g=g||342,q(h,{height:g,version:1.1,width:f,xmlns:"http://www.w3.org/2000/svg"}),c==1?(h.style.cssText=i+"position:absolute;left:"+d+"px;top:"+e+"px",a._g.doc.body.appendChild(h),j=1):(h.style.cssText=i+"position:relative",c.firstChild?c.insertBefore(h,c.firstChild):c.appendChild(h)),c=new a._Paper,c.width=f,c.height=g,c.canvas=h,c.clear(),c._left=c._top=0,j&&(c.renderfix=function(){}),c.renderfix();return c},a._engine.setViewBox=function(a,b,c,d,e){k("raphael.setViewBox",this,this._viewBox,[a,b,c,d,e]);var f=g(c/this.width,d/this.height),h=this.top,i=e?"meet":"xMinYMin",j,l;a==null?(this._vbSize&&(f=1),delete this._vbSize,j="0 0 "+this.width+m+this.height):(this._vbSize=f,j=a+m+b+m+c+m+d),q(this.canvas,{viewBox:j,preserveAspectRatio:i});while(f&&h)l="stroke-width"in h.attrs?h.attrs["stroke-width"]:1,h.attr({"stroke-width":l}),h._.dirty=1,h._.dirtyT=1,h=h.prev;this._viewBox=[a,b,c,d,!!e];return this},a.prototype.renderfix=function(){var a=this.canvas,b=a.style,c;try{c=a.getScreenCTM()||a.createSVGMatrix()}catch(d){c=a.createSVGMatrix()}var e=-c.e%1,f=-c.f%1;if(e||f)e&&(this._left=(this._left+e)%1,b.left=this._left+"px"),f&&(this._top=(this._top+f)%1,b.top=this._top+"px")},a.prototype.clear=function(){a.eve("raphael.clear",this);var b=this.canvas;while(b.firstChild)b.removeChild(b.firstChild);this.bottom=this.top=null,(this.desc=q("desc")).appendChild(a._g.doc.createTextNode("Created with Raphal "+a.version)),b.appendChild(this.desc),b.appendChild(this.defs=q("defs"))},a.prototype.remove=function(){k("raphael.remove",this),this.canvas.parentNode&&this.canvas.parentNode.removeChild(this.canvas);for(var b in this)this[b]=typeof this[b]=="function"?a._removedFactory(b):null};var B=a.st;for(var C in A)A[b](C)&&!B[b](C)&&(B[C]=function(a){return function(){var b=arguments;return this.forEach(function(c){c[a].apply(c,b)})}}(C))}(window.Raphael),window.Raphael.vml&&function(a){var b="hasOwnProperty",c=String,d=parseFloat,e=Math,f=e.round,g=e.max,h=e.min,i=e.abs,j="fill",k=/[, ]+/,l=a.eve,m=" progid:DXImageTransform.Microsoft",n=" ",o="",p={M:"m",L:"l",C:"c",Z:"x",m:"t",l:"r",c:"v",z:"x"},q=/([clmz]),?([^clmz]*)/gi,r=/ progid:\S+Blur\([^\)]+\)/g,s=/-?[^,\s-]+/g,t="position:absolute;left:0;top:0;width:1px;height:1px",u=21600,v={path:1,rect:1,image:1},w={circle:1,ellipse:1},x=function(b){var d=/[ahqstv]/ig,e=a._pathToAbsolute;c(b).match(d)&&(e=a._path2curve),d=/[clmz]/g;if(e==a._pathToAbsolute&&!c(b).match(d)){var g=c(b).replace(q,function(a,b,c){var d=[],e=b.toLowerCase()=="m",g=p[b];c.replace(s,function(a){e&&d.length==2&&(g+=d+p[b=="m"?"l":"L"],d=[]),d.push(f(a*u))});return g+d});return g}var h=e(b),i,j;g=[];for(var k=0,l=h.length;k<l;k++){i=h[k],j=h[k][0].toLowerCase(),j=="z"&&(j="x");for(var m=1,r=i.length;m<r;m++)j+=f(i[m]*u)+(m!=r-1?",":o);g.push(j)}return g.join(n)},y=function(b,c,d){var e=a.matrix();e.rotate(-b,.5,.5);return{dx:e.x(c,d),dy:e.y(c,d)}},z=function(a,b,c,d,e,f){var g=a._,h=a.matrix,k=g.fillpos,l=a.node,m=l.style,o=1,p="",q,r=u/b,s=u/c;m.visibility="hidden";if(!!b&&!!c){l.coordsize=i(r)+n+i(s),m.rotation=f*(b*c<0?-1:1);if(f){var t=y(f,d,e);d=t.dx,e=t.dy}b<0&&(p+="x"),c<0&&(p+=" y")&&(o=-1),m.flip=p,l.coordorigin=d*-r+n+e*-s;if(k||g.fillsize){var v=l.getElementsByTagName(j);v=v&&v[0],l.removeChild(v),k&&(t=y(f,h.x(k[0],k[1]),h.y(k[0],k[1])),v.position=t.dx*o+n+t.dy*o),g.fillsize&&(v.size=g.fillsize[0]*i(b)+n+g.fillsize[1]*i(c)),l.appendChild(v)}m.visibility="visible"}};a.toString=function(){return"Your browser doesnt support SVG. Falling down to VML.\nYou are running Raphal "+this.version};var A=function(a,b,d){var e=c(b).toLowerCase().split("-"),f=d?"end":"start",g=e.length,h="classic",i="medium",j="medium";while(g--)switch(e[g]){case"block":case"classic":case"oval":case"diamond":case"open":case"none":h=e[g];break;case"wide":case"narrow":j=e[g];break;case"long":case"short":i=e[g]}var k=a.node.getElementsByTagName("stroke")[0];k[f+"arrow"]=h,k[f+"arrowlength"]=i,k[f+"arrowwidth"]=j},B=function(e,i){e.attrs=e.attrs||{};var l=e.node,m=e.attrs,p=l.style,q,r=v[e.type]&&(i.x!=m.x||i.y!=m.y||i.width!=m.width||i.height!=m.height||i.cx!=m.cx||i.cy!=m.cy||i.rx!=m.rx||i.ry!=m.ry||i.r!=m.r),s=w[e.type]&&(m.cx!=i.cx||m.cy!=i.cy||m.r!=i.r||m.rx!=i.rx||m.ry!=i.ry),t=e;for(var y in i)i[b](y)&&(m[y]=i[y]);r&&(m.path=a._getPath[e.type](e),e._.dirty=1),i.href&&(l.href=i.href),i.title&&(l.title=i.title),i.target&&(l.target=i.target),i.cursor&&(p.cursor=i.cursor),"blur"in i&&e.blur(i.blur);if(i.path&&e.type=="path"||r)l.path=x(~c(m.path).toLowerCase().indexOf("r")?a._pathToAbsolute(m.path):m.path),e.type=="image"&&(e._.fillpos=[m.x,m.y],e._.fillsize=[m.width,m.height],z(e,1,1,0,0,0));"transform"in i&&e.transform(i.transform);if(s){var B=+m.cx,D=+m.cy,E=+m.rx||+m.r||0,G=+m.ry||+m.r||0;l.path=a.format("ar{0},{1},{2},{3},{4},{1},{4},{1}x",f((B-E)*u),f((D-G)*u),f((B+E)*u),f((D+G)*u),f(B*u))}if("clip-rect"in i){var H=c(i["clip-rect"]).split(k);if(H.length==4){H[2]=+H[2]+ +H[0],H[3]=+H[3]+ +H[1];var I=l.clipRect||a._g.doc.createElement("div"),J=I.style;J.clip=a.format("rect({1}px {2}px {3}px {0}px)",H),l.clipRect||(J.position="absolute",J.top=0,J.left=0,J.width=e.paper.width+"px",J.height=e.paper.height+"px",l.parentNode.insertBefore(I,l),I.appendChild(l),l.clipRect=I)}i["clip-rect"]||l.clipRect&&(l.clipRect.style.clip="auto")}if(e.textpath){var K=e.textpath.style;i.font&&(K.font=i.font),i["font-family"]&&(K.fontFamily='"'+i["font-family"].split(",")[0].replace(/^['"]+|['"]+$/g,o)+'"'),i["font-size"]&&(K.fontSize=i["font-size"]),i["font-weight"]&&(K.fontWeight=i["font-weight"]),i["font-style"]&&(K.fontStyle=i["font-style"])}"arrow-start"in i&&A(t,i["arrow-start"]),"arrow-end"in i&&A(t,i["arrow-end"],1);if(i.opacity!=null||i["stroke-width"]!=null||i.fill!=null||i.src!=null||i.stroke!=null||i["stroke-width"]!=null||i["stroke-opacity"]!=null||i["fill-opacity"]!=null||i["stroke-dasharray"]!=null||i["stroke-miterlimit"]!=null||i["stroke-linejoin"]!=null||i["stroke-linecap"]!=null){var L=l.getElementsByTagName(j),M=!1;L=L&&L[0],!L&&(M=L=F(j)),e.type=="image"&&i.src&&(L.src=i.src),i.fill&&(L.on=!0);if(L.on==null||i.fill=="none"||i.fill===null)L.on=!1;if(L.on&&i.fill){var N=c(i.fill).match(a._ISURL);if(N){L.parentNode==l&&l.removeChild(L),L.rotate=!0,L.src=N[1],L.type="tile";var O=e.getBBox(1);L.position=O.x+n+O.y,e._.fillpos=[O.x,O.y],a._preload(N[1],function(){e._.fillsize=[this.offsetWidth,this.offsetHeight]})}else L.color=a.getRGB(i.fill).hex,L.src=o,L.type="solid",a.getRGB(i.fill).error&&(t.type in{circle:1,ellipse:1}||c(i.fill).charAt()!="r")&&C(t,i.fill,L)&&(m.fill="none",m.gradient=i.fill,L.rotate=!1)}if("fill-opacity"in i||"opacity"in i){var P=((+m["fill-opacity"]+1||2)-1)*((+m.opacity+1||2)-1)*((+a.getRGB(i.fill).o+1||2)-1);P=h(g(P,0),1),L.opacity=P,L.src&&(L.color="none")}l.appendChild(L);var Q=l.getElementsByTagName("stroke")&&l.getElementsByTagName("stroke")[0],T=!1;!Q&&(T=Q=F("stroke"));if(i.stroke&&i.stroke!="none"||i["stroke-width"]||i["stroke-opacity"]!=null||i["stroke-dasharray"]||i["stroke-miterlimit"]||i["stroke-linejoin"]||i["stroke-linecap"])Q.on=!0;(i.stroke=="none"||i.stroke===null||Q.on==null||i.stroke==0||i["stroke-width"]==0)&&(Q.on=!1);var U=a.getRGB(i.stroke);Q.on&&i.stroke&&(Q.color=U.hex),P=((+m["stroke-opacity"]+1||2)-1)*((+m.opacity+1||2)-1)*((+U.o+1||2)-1);var V=(d(i["stroke-width"])||1)*.75;P=h(g(P,0),1),i["stroke-width"]==null&&(V=m["stroke-width"]),i["stroke-width"]&&(Q.weight=V),V&&V<1&&(P*=V)&&(Q.weight=1),Q.opacity=P,i["stroke-linejoin"]&&(Q.joinstyle=i["stroke-linejoin"]||"miter"),Q.miterlimit=i["stroke-miterlimit"]||8,i["stroke-linecap"]&&(Q.endcap=i["stroke-linecap"]=="butt"?"flat":i["stroke-linecap"]=="square"?"square":"round");if(i["stroke-dasharray"]){var W={"-":"shortdash",".":"shortdot","-.":"shortdashdot","-..":"shortdashdotdot",". ":"dot","- ":"dash","--":"longdash","- .":"dashdot","--.":"longdashdot","--..":"longdashdotdot"};Q.dashstyle=W[b](i["stroke-dasharray"])?W[i["stroke-dasharray"]]:o}T&&l.appendChild(Q)}if(t.type=="text"){t.paper.canvas.style.display=o;var X=t.paper.span,Y=100,Z=m.font&&m.font.match(/\d+(?:\.\d*)?(?=px)/);p=X.style,m.font&&(p.font=m.font),m["font-family"]&&(p.fontFamily=m["font-family"]),m["font-weight"]&&(p.fontWeight=m["font-weight"]),m["font-style"]&&(p.fontStyle=m["font-style"]),Z=d(m["font-size"]||Z&&Z[0])||10,p.fontSize=Z*Y+"px",t.textpath.string&&(X.innerHTML=c(t.textpath.string).replace(/</g,"&#60;").replace(/&/g,"&#38;").replace(/\n/g,"<br>"));var $=X.getBoundingClientRect();t.W=m.w=($.right-$.left)/Y,t.H=m.h=($.bottom-$.top)/Y,t.X=m.x,t.Y=m.y+t.H/2,("x"in i||"y"in i)&&(t.path.v=a.format("m{0},{1}l{2},{1}",f(m.x*u),f(m.y*u),f(m.x*u)+1));var _=["x","y","text","font","font-family","font-weight","font-style","font-size"];for(var ba=0,bb=_.length;ba<bb;ba++)if(_[ba]in i){t._.dirty=1;break}switch(m["text-anchor"]){case"start":t.textpath.style["v-text-align"]="left",t.bbx=t.W/2;break;case"end":t.textpath.style["v-text-align"]="right",t.bbx=-t.W/2;break;default:t.textpath.style["v-text-align"]="center",t.bbx=0}t.textpath.style["v-text-kern"]=!0}},C=function(b,f,g){b.attrs=b.attrs||{};var h=b.attrs,i=Math.pow,j,k,l="linear",m=".5 .5";b.attrs.gradient=f,f=c(f).replace(a._radial_gradient,function(a,b,c){l="radial",b&&c&&(b=d(b),c=d(c),i(b-.5,2)+i(c-.5,2)>.25&&(c=e.sqrt(.25-i(b-.5,2))*((c>.5)*2-1)+.5),m=b+n+c);return o}),f=f.split(/\s*\-\s*/);if(l=="linear"){var p=f.shift();p=-d(p);if(isNaN(p))return null}var q=a._parseDots(f);if(!q)return null;b=b.shape||b.node;if(q.length){b.removeChild(g),g.on=!0,g.method="none",g.color=q[0].color,g.color2=q[q.length-1].color;var r=[];for(var s=0,t=q.length;s<t;s++)q[s].offset&&r.push(q[s].offset+n+q[s].color);g.colors=r.length?r.join():"0% "+g.color,l=="radial"?(g.type="gradientTitle",g.focus="100%",g.focussize="0 0",g.focusposition=m,g.angle=0):(g.type="gradient",g.angle=(270-p)%360),b.appendChild(g)}return 1},D=function(b,c){this[0]=this.node=b,b.raphael=!0,this.id=a._oid++,b.raphaelid=this.id,this.X=0,this.Y=0,this.attrs={},this.paper=c,this.matrix=a.matrix(),this._={transform:[],sx:1,sy:1,dx:0,dy:0,deg:0,dirty:1,dirtyT:1},!c.bottom&&(c.bottom=this),this.prev=c.top,c.top&&(c.top.next=this),c.top=this,this.next=null},E=a.el;D.prototype=E,E.constructor=D,E.transform=function(b){if(b==null)return this._.transform;var d=this.paper._viewBoxShift,e=d?"s"+[d.scale,d.scale]+"-1-1t"+[d.dx,d.dy]:o,f;d&&(f=b=c(b).replace(/\.{3}|\u2026/g,this._.transform||o)),a._extractTransform(this,e+b);var g=this.matrix.clone(),h=this.skew,i=this.node,j,k=~c(this.attrs.fill).indexOf("-"),l=!c(this.attrs.fill).indexOf("url(");g.translate(-0.5,-0.5);if(l||k||this.type=="image"){h.matrix="1 0 0 1",h.offset="0 0",j=g.split();if(k&&j.noRotation||!j.isSimple){i.style.filter=g.toFilter();var m=this.getBBox(),p=this.getBBox(1),q=m.x-p.x,r=m.y-p.y;i.coordorigin=q*-u+n+r*-u,z(this,1,1,q,r,0)}else i.style.filter=o,z(this,j.scalex,j.scaley,j.dx,j.dy,j.rotate)}else i.style.filter=o,h.matrix=c(g),h.offset=g.offset();f&&(this._.transform=f);return this},E.rotate=function(a,b,e){if(this.removed)return this;if(a!=null){a=c(a).split(k),a.length-1&&(b=d(a[1]),e=d(a[2])),a=d(a[0]),e==null&&(b=e);if(b==null||e==null){var f=this.getBBox(1);b=f.x+f.width/2,e=f.y+f.height/2}this._.dirtyT=1,this.transform(this._.transform.concat([["r",a,b,e]]));return this}},E.translate=function(a,b){if(this.removed)return this;a=c(a).split(k),a.length-1&&(b=d(a[1])),a=d(a[0])||0,b=+b||0,this._.bbox&&(this._.bbox.x+=a,this._.bbox.y+=b),this.transform(this._.transform.concat([["t",a,b]]));return this},E.scale=function(a,b,e,f){if(this.removed)return this;a=c(a).split(k),a.length-1&&(b=d(a[1]),e=d(a[2]),f=d(a[3]),isNaN(e)&&(e=null),isNaN(f)&&(f=null)),a=d(a[0]),b==null&&(b=a),f==null&&(e=f);if(e==null||f==null)var g=this.getBBox(1);e=e==null?g.x+g.width/2:e,f=f==null?g.y+g.height/2:f,this.transform(this._.transform.concat([["s",a,b,e,f]])),this._.dirtyT=1;return this},E.hide=function(){!this.removed&&(this.node.style.display="none");return this},E.show=function(){!this.removed&&(this.node.style.display=o);return this},E._getBBox=function(){if(this.removed)return{};return{x:this.X+(this.bbx||0)-this.W/2,y:this.Y-this.H,width:this.W,height:this.H}},E.remove=function(){if(!this.removed&&!!this.node.parentNode){this.paper.__set__&&this.paper.__set__.exclude(this),a.eve.unbind("raphael.*.*."+this.id),a._tear(this,this.paper),this.node.parentNode.removeChild(this.node),this.shape&&this.shape.parentNode.removeChild(this.shape);for(var b in this)this[b]=typeof this[b]=="function"?a._removedFactory(b):null;this.removed=!0}},E.attr=function(c,d){if(this.removed)return this;if(c==null){var e={};for(var f in this.attrs)this.attrs[b](f)&&(e[f]=this.attrs[f]);e.gradient&&e.fill=="none"&&(e.fill=e.gradient)&&delete e.gradient,e.transform=this._.transform;return e}if(d==null&&a.is(c,"string")){if(c==j&&this.attrs.fill=="none"&&this.attrs.gradient)return this.attrs.gradient;var g=c.split(k),h={};for(var i=0,m=g.length;i<m;i++)c=g[i],c in this.attrs?h[c]=this.attrs[c]:a.is(this.paper.customAttributes[c],"function")?h[c]=this.paper.customAttributes[c].def:h[c]=a._availableAttrs[c];return m-1?h:h[g[0]]}if(this.attrs&&d==null&&a.is(c,"array")){h={};for(i=0,m=c.length;i<m;i++)h[c[i]]=this.attr(c[i]);return h}var n;d!=null&&(n={},n[c]=d),d==null&&a.is(c,"object")&&(n=c);for(var o in n)l("raphael.attr."+o+"."+this.id,this,n[o]);if(n){for(o in this.paper.customAttributes)if(this.paper.customAttributes[b](o)&&n[b](o)&&a.is(this.paper.customAttributes[o],"function")){var p=this.paper.customAttributes[o].apply(this,[].concat(n[o]));this.attrs[o]=n[o];for(var q in p)p[b](q)&&(n[q]=p[q])}n.text&&this.type=="text"&&(this.textpath.string=n.text),B(this,n)}return this},E.toFront=function(){!this.removed&&this.node.parentNode.appendChild(this.node),this.paper&&this.paper.top!=this&&a._tofront(this,this.paper);return this},E.toBack=function(){if(this.removed)return this;this.node.parentNode.firstChild!=this.node&&(this.node.parentNode.insertBefore(this.node,this.node.parentNode.firstChild),a._toback(this,this.paper));return this},E.insertAfter=function(b){if(this.removed)return this;b.constructor==a.st.constructor&&(b=b[b.length-1]),b.node.nextSibling?b.node.parentNode.insertBefore(this.node,b.node.nextSibling):b.node.parentNode.appendChild(this.node),a._insertafter(this,b,this.paper);return this},E.insertBefore=function(b){if(this.removed)return this;b.constructor==a.st.constructor&&(b=b[0]),b.node.parentNode.insertBefore(this.node,b.node),a._insertbefore(this,b,this.paper);return this},E.blur=function(b){var c=this.node.runtimeStyle,d=c.filter;d=d.replace(r,o),+b!==0?(this.attrs.blur=b,c.filter=d+n+m+".Blur(pixelradius="+(+b||1.5)+")",c.margin=a.format("-{0}px 0 0 -{0}px",f(+b||1.5))):(c.filter=d,c.margin=0,delete this.attrs.blur)},a._engine.path=function(a,b){var c=F("shape");c.style.cssText=t,c.coordsize=u+n+u,c.coordorigin=b.coordorigin;var d=new D(c,b),e={fill:"none",stroke:"#000"};a&&(e.path=a),d.type="path",d.path=[],d.Path=o,B(d,e),b.canvas.appendChild(c);var f=F("skew");f.on=!0,c.appendChild(f),d.skew=f,d.transform(o);return d},a._engine.rect=function(b,c,d,e,f,g){var h=a._rectPath(c,d,e,f,g),i=b.path(h),j=i.attrs;i.X=j.x=c,i.Y=j.y=d,i.W=j.width=e,i.H=j.height=f,j.r=g,j.path=h,i.type="rect";return i},a._engine.ellipse=function(a,b,c,d,e){var f=a.path(),g=f.attrs;f.X=b-d,f.Y=c-e,f.W=d*2,f.H=e*2,f.type="ellipse",B(f,{cx:b,cy:c,rx:d,ry:e});return f},a._engine.circle=function(a,b,c,d){var e=a.path(),f=e.attrs;e.X=b-d,e.Y=c-d,e.W=e.H=d*2,e.type="circle",B(e,{cx:b,cy:c,r:d});return e},a._engine.image=function(b,c,d,e,f,g){var h=a._rectPath(d,e,f,g),i=b.path(h).attr({stroke:"none"}),k=i.attrs,l=i.node,m=l.getElementsByTagName(j)[0];k.src=c,i.X=k.x=d,i.Y=k.y=e,i.W=k.width=f,i.H=k.height=g,k.path=h,i.type="image",m.parentNode==l&&l.removeChild(m),m.rotate=!0,m.src=c,m.type="tile",i._.fillpos=[d,e],i._.fillsize=[f,g],l.appendChild(m),z(i,1,1,0,0,0);return i},a._engine.text=function(b,d,e,g){var h=F("shape"),i=F("path"),j=F("textpath");d=d||0,e=e||0,g=g||"",i.v=a.format("m{0},{1}l{2},{1}",f(d*u),f(e*u),f(d*u)+1),i.textpathok=!0,j.string=c(g),j.on=!0,h.style.cssText=t,h.coordsize=u+n+u,h.coordorigin="0 0";var k=new D(h,b),l={fill:"#000",stroke:"none",font:a._availableAttrs.font,text:g};k.shape=h,k.path=i,k.textpath=j,k.type="text",k.attrs.text=c(g),k.attrs.x=d,k.attrs.y=e,k.attrs.w=1,k.attrs.h=1,B(k,l),h.appendChild(j),h.appendChild(i),b.canvas.appendChild(h);var m=F("skew");m.on=!0,h.appendChild(m),k.skew=m,k.transform(o);return k},a._engine.setSize=function(b,c){var d=this.canvas.style;this.width=b,this.height=c,b==+b&&(b+="px"),c==+c&&(c+="px"),d.width=b,d.height=c,d.clip="rect(0 "+b+" "+c+" 0)",this._viewBox&&a._engine.setViewBox.apply(this,this._viewBox);return this},a._engine.setViewBox=function(b,c,d,e,f){a.eve("raphael.setViewBox",this,this._viewBox,[b,c,d,e,f]);var h=this.width,i=this.height,j=1/g(d/h,e/i),k,l;f&&(k=i/e,l=h/d,d*k<h&&(b-=(h-d*k)/2/k),e*l<i&&(c-=(i-e*l)/2/l)),this._viewBox=[b,c,d,e,!!f],this._viewBoxShift={dx:-b,dy:-c,scale:j},this.forEach(function(a){a.transform("...")});return this};var F;a._engine.initWin=function(a){var b=a.document;b.createStyleSheet().addRule(".rvml","behavior:url(#default#VML)");try{!b.namespaces.rvml&&b.namespaces.add("rvml","urn:schemas-microsoft-com:vml"),F=function(a){return b.createElement("<rvml:"+a+' class="rvml">')}}catch(c){F=function(a){return b.createElement("<"+a+' xmlns="urn:schemas-microsoft.com:vml" class="rvml">')}}},a._engine.initWin(a._g.win),a._engine.create=function(){var b=a._getContainer.apply(0,arguments),c=b.container,d=b.height,e,f=b.width,g=b.x,h=b.y;if(!c)throw new Error("VML container not found.");var i=new a._Paper,j=i.canvas=a._g.doc.createElement("div"),k=j.style;g=g||0,h=h||0,f=f||512,d=d||342,i.width=f,i.height=d,f==+f&&(f+="px"),d==+d&&(d+="px"),i.coordsize=u*1e3+n+u*1e3,i.coordorigin="0 0",i.span=a._g.doc.createElement("span"),i.span.style.cssText="position:absolute;left:-9999em;top:-9999em;padding:0;margin:0;line-height:1;",j.appendChild(i.span),k.cssText=a.format("top:0;left:0;width:{0};height:{1};display:inline-block;position:relative;clip:rect(0 {0} {1} 0);overflow:hidden",f,d),c==1?(a._g.doc.body.appendChild(j),k.left=g+"px",k.top=h+"px",k.position="absolute"):c.firstChild?c.insertBefore(j,c.firstChild):c.appendChild(j),i.renderfix=function(){};return i},a.prototype.clear=function(){a.eve("raphael.clear",this),this.canvas.innerHTML=o,this.span=a._g.doc.createElement("span"),this.span.style.cssText="position:absolute;left:-9999em;top:-9999em;padding:0;margin:0;line-height:1;display:inline;",this.canvas.appendChild(this.span),this.bottom=this.top=null},a.prototype.remove=function(){a.eve("raphael.remove",this),this.canvas.parentNode.removeChild(this.canvas);for(var b in this)this[b]=typeof this[b]=="function"?a._removedFactory(b):null;return!0};var G=a.st;for(var H in E)E[b](H)&&!G[b](H)&&(G[H]=function(a){return function(){var b=arguments;return this.forEach(function(c){c[a].apply(c,b)})}}(H))}(window.Raphael)
;
/**
 * JustGage - a handy JavaScript plugin for generating and animating nice & clean dashboard gauges.
 * Copyright (c) 2012 Bojan Djuricic - pindjur(at)gmail(dot)com | http://www.madcog.com
 * Licensed under MIT.
 * Date: 31/07/2012
 * @author Bojan Djuricic  (@Toorshia)
 * @version 1.0
 *
 * http://www.justgage.com
 */


JustGage = function(config) { 
  
  if (!config.id) {alert("Missing id parameter for gauge!"); return false;} 
  if (!document.getElementById(config.id)) {alert("No element with id: \""+config.id+"\" found!"); return false;} 
  
  // configurable parameters
  this.config = 
  {
    // id : string 
    // this is container element id
    id : config.id,
    
    // title : string
    // gauge title 
    title : (config.title) ? config.title : "Title",
    
    // titleFontColor : string
    // color of gauge title 
    titleFontColor : (config.titleFontColor) ? config.titleFontColor : "#999999",
    
    // value : int
    // value gauge is showing 
    value : (config.value) ? config.value : 0,
    
    // valueFontColor : string
    // color of label showing current value
    valueFontColor : (config.valueFontColor) ? config.valueFontColor : "#010101",
    
    // min : int
    // min value
    min : (config.min) ? config.min : 0,
    
    // max : int
    // max value
    max : (config.max) ? config.max : 100,
    
    // showMinMax : bool
    // hide or display min and max values
    showMinMax : (config.showMinMax != null) ? config.showMinMax : true,
    
    // gaugeWidthScale : float
    // width of the gauge element
    gaugeWidthScale : (config.gaugeWidthScale) ? config.gaugeWidthScale : 1.0,
    
    // gaugeColor : string
    // background color of gauge element 
    gaugeColor : (config.gaugeColor) ? config.gaugeColor : "#edebeb",
    
    // label : string
    // text to show below value
    label : (config.label) ? config.label : "",
    
    // showInnerShadow : bool
    // give gauge element small amount of inner shadow 
    showInnerShadow : (config.showInnerShadow != null) ? config.showInnerShadow : true,
    
    // shadowOpacity : int
    // 0 ~ 1
    shadowOpacity : (config.shadowOpacity) ? config.shadowOpacity : 0.2,
    
    // shadowSize: int
    // inner shadow size
    shadowSize : (config.shadowSize) ? config.shadowSize : 5,
    
    // shadowVerticalOffset : int
    // how much shadow is offset from top 
    shadowVerticalOffset : (config.shadowVerticalOffset) ? config.shadowVerticalOffset : 3,
    
    // levelColors : string[]
    // colors of indicator, from lower to upper, in RGB format 
    levelColors : (config.levelColors) ? config.levelColors : percentColors,
    
    // levelColorsGradient : bool
    // whether to use gradual color change for value, or sector-based
    levelColorsGradient : (config.levelColorsGradient != null) ? config.levelColorsGradient : true,
    
    // labelFontColor : string
    // color of label showing label under value
    labelFontColor : (config.labelFontColor) ? config.labelFontColor : "#b3b3b3",
    
    // startAnimationTime : int
    // length of initial animation 
    startAnimationTime : (config.startAnimationTime) ? config.startAnimationTime : 700,
    
    // startAnimationType : string
    // type of initial animation (linear, >, <,  <>, bounce) 
    startAnimationType : (config.startAnimationType) ? config.startAnimationType : ">",
    
    // refreshAnimationTime : int
    // length of refresh animation 
    refreshAnimationTime : (config.refreshAnimationTime) ? config.refreshAnimationTime : 700,
    
    // refreshAnimationType : string
    // type of refresh animation (linear, >, <,  <>, bounce) 
    refreshAnimationType : (config.refreshAnimationType) ? config.refreshAnimationType : ">"
  };
  
  // overflow values
  if (config.value > this.config.max) this.config.value = this.config.max; 
  if (config.value < this.config.min) this.config.value = this.config.min;
  this.originalValue = config.value;
  
  // canvas
  this.canvas = Raphael(this.config.id, "100%", "100%");
  
  // canvas dimensions
  //var canvasW = document.getElementById(this.config.id).clientWidth;
  //var canvasH = document.getElementById(this.config.id).clientHeight;
  var canvasW = getStyle(document.getElementById(this.config.id), "width").slice(0, -2) * 1;
  var canvasH = getStyle(document.getElementById(this.config.id), "height").slice(0, -2) * 1;
  
  // widget dimensions
  var widgetW, widgetH;
  if ((canvasW / canvasH) > 1.25) {
    widgetW = 1.25 * canvasH;
    widgetH = canvasH;
  } else {
    widgetW = canvasW;
    widgetH = canvasW / 1.25;
  }
  
  // delta 
  var dx = (canvasW - widgetW)/2;
  var dy = (canvasH - widgetH)/2;
  
  // title 
  var titleFontSize = ((widgetH / 8) > 10) ? (widgetH / 10) : 10;
  var titleX = dx + widgetW / 2;
  var titleY = dy + widgetH / 6.5;
  
  // value 
  var valueFontSize = ((widgetH / 6.4) > 16) ? (widgetH / 6.4) : 16;
  var valueX = dx + widgetW / 2;
  var valueY = dy + widgetH / 1.4;
  
  // label 
  var labelFontSize = ((widgetH / 16) > 10) ? (widgetH / 16) : 10;
  var labelX = dx + widgetW / 2;
  //var labelY = dy + widgetH / 1.126760563380282;
  var labelY = valueY + valueFontSize / 2 + 6;
  
  // min 
  var minFontSize = ((widgetH / 16) > 10) ? (widgetH / 16) : 10;
  var minX = dx + (widgetW / 10) + (widgetW / 6.666666666666667 * this.config.gaugeWidthScale) / 2 ;
  var minY = dy + widgetH / 1.126760563380282;
  
  // max 
  var maxFontSize = ((widgetH / 16) > 10) ? (widgetH / 16) : 10;
  var maxX = dx + widgetW - (widgetW / 10) - (widgetW / 6.666666666666667 * this.config.gaugeWidthScale) / 2 ;
  var maxY = dy + widgetH / 1.126760563380282;
  
  // parameters
  this.params  = {
    canvasW : canvasW,
    canvasH : canvasH,
    widgetW : widgetW,
    widgetH : widgetH,
    dx : dx,
    dy : dy,
    titleFontSize : titleFontSize,
    titleX : titleX,
    titleY : titleY,
    valueFontSize : valueFontSize,
    valueX : valueX,
    valueY : valueY,
    labelFontSize : labelFontSize,
    labelX : labelX,
    labelY : labelY,
    minFontSize : minFontSize,
    minX : minX,
    minY : minY,
    maxFontSize : maxFontSize,
    maxX : maxX,
    maxY : maxY
  };
  
  // pki - custom attribute for generating gauge paths
  this.canvas.customAttributes.pki = function (value, min, max, w, h, dx, dy, gws) {
    
       var alpha = (1 - (value - min) / (max - min)) * Math.PI ,
          Ro = w / 2 - w / 10,
          Ri = Ro - w / 6.666666666666667 * gws,
          
          Cx = w / 2 + dx,
          Cy = h / 1.25 + dy,
          
          Xo = w / 2 + dx + Ro * Math.cos(alpha),
          Yo = h - (h - Cy) + dy - Ro * Math.sin(alpha),
          Xi = w / 2 + dx + Ri * Math.cos(alpha),
          Yi = h - (h - Cy) + dy - Ri * Math.sin(alpha),
          path;
    
      path += "M" + (Cx - Ri) + "," + Cy + " ";
      path += "L" + (Cx - Ro) + "," + Cy + " ";
      path += "A" + Ro + "," + Ro + " 0 0,1 " + Xo + "," + Yo + " ";
      path += "L" + Xi + "," + Yi + " ";
      path += "A" + Ri + "," + Ri + " 0 0,0 " + (Cx - Ri) + "," + Cy + " ";
      path += "z ";
      return { path: path };
  }  
  
  // gauge
  this.gauge = this.canvas.path().attr({
    "stroke": "none",
    "fill": this.config.gaugeColor,   
    pki: [this.config.max, this.config.min, this.config.max, this.params.widgetW, this.params.widgetH,  this.params.dx, this.params.dy, this.config.gaugeWidthScale]
  });
  this.gauge.id = this.config.id+"-gauge";
  
  // level
  this.level = this.canvas.path().attr({
    "stroke": "none",
    "fill": getColorForPercentage((this.config.value - this.config.min) / (this.config.max - this.config.min), this.config.levelColors, this.config.levelColorsGradient),  
    pki: [this.config.min, this.config.min, this.config.max, this.params.widgetW, this.params.widgetH,  this.params.dx, this.params.dy, this.config.gaugeWidthScale]
  });
  this.level.id = this.config.id+"-level";
  
  // title
  this.txtTitle = this.canvas.text(this.params.titleX, this.params.titleY, this.config.title);
  this.txtTitle. attr({
    "font-size":this.params.titleFontSize,
    "font-weight":"bold",
    "font-family":"Arial",
    "fill":this.config.titleFontColor,
    "fill-opacity":"1"         
  });
  this.txtTitle.id = this.config.id+"-txttitle";
  
  // value
  this.txtValue = this.canvas.text(this.params.valueX, this.params.valueY, this.originalValue);
  this.txtValue. attr({
    "font-size":this.params.valueFontSize,
    "font-weight":"bold",
    "font-family":"Arial",
    "fill":this.config.valueFontColor,
    "fill-opacity":"0"          
  });
  this.txtValue.id = this.config.id+"-txtvalue";
  
  // label
  this.txtLabel = this.canvas.text(this.params.labelX, this.params.labelY, this.config.label);
  this.txtLabel. attr({
    "font-size":this.params.labelFontSize,
    "font-weight":"normal",
    "font-family":"Arial",
    "fill":this.config.labelFontColor,   
    "fill-opacity":"0"
  });
  this.txtLabel.id = this.config.id+"-txtlabel";
  
  // min
  this.txtMin = this.canvas.text(this.params.minX, this.params.minY, this.config.min);
  this.txtMin. attr({
    "font-size":this.params.minFontSize,
    "font-weight":"normal",
    "font-family":"Arial",
    "fill":this.config.labelFontColor,   
    "fill-opacity": (this.config.showMinMax == true)? "1" : "0"
  });
  this.txtMin.id = this.config.id+"-txtmin";
  
  // max
  this.txtMax = this.canvas.text(this.params.maxX, this.params.maxY, this.config.max);
  this.txtMax. attr({
    "font-size":this.params.maxFontSize,
    "font-weight":"normal",
    "font-family":"Arial",
    "fill":this.config.labelFontColor,   
    "fill-opacity": (this.config.showMinMax == true)? "1" : "0"
  });
  this.txtMax.id = this.config.id+"-txtmax";
  
  var defs = this.canvas.canvas.childNodes[1];
  var svg = "http://www.w3.org/2000/svg";
  
  
  
  if (ie < 9) {
    onCreateElementNsReady(function() {
      this.generateShadow();
    });  
  } else {
    this.generateShadow(svg, defs);
  }
  
  // animate 
  this.level.animate({pki: [this.config.value, this.config.min, this.config.max, this.params.widgetW, this.params.widgetH,  this.params.dx, this.params.dy, this.config.gaugeWidthScale]},  this.config.startAnimationTime, this.config.startAnimationType);
  
  this.txtValue.animate({"fill-opacity":"1"}, this.config.startAnimationTime, this.config.startAnimationType); 
  this.txtLabel.animate({"fill-opacity":"1"}, this.config.startAnimationTime, this.config.startAnimationType);  
};

// refresh gauge level
JustGage.prototype.refresh = function(val) {
  // overflow values
  originalVal = val;
  if (val > this.config.max) {val = this.config.max;}
  if (val < this.config.min) {val = this.config.min;}
    
  var color = getColorForPercentage((val - this.config.min) / (this.config.max - this.config.min), this.config.levelColors, this.config.levelColorsGradient);
  this.canvas.getById(this.config.id+"-txtvalue").attr({"text":originalVal});
  this.canvas.getById(this.config.id+"-level").animate({pki: [val, this.config.min, this.config.max, this.params.widgetW, this.params.widgetH,  this.params.dx, this.params.dy, this.config.gaugeWidthScale], "fill":color},  this.config.refreshAnimationTime, this.config.refreshAnimationType);
};

var percentColors = [
  "#a9d70b",
  "#f9c802",
  "#ff0000"
]

JustGage.prototype.generateShadow = function(svg, defs) {
    // FILTER
    var gaussFilter=document.createElementNS(svg,"filter");
    gaussFilter.setAttribute("id", this.config.id + "-inner-shadow");
    defs.appendChild(gaussFilter);
    
    // offset
    var feOffset = document.createElementNS(svg,"feOffset");
    feOffset.setAttribute("dx", 0);
    feOffset.setAttribute("dy", this.config.shadowVerticalOffset);
    gaussFilter.appendChild(feOffset);
    
    // blur
    var feGaussianBlur = document.createElementNS(svg,"feGaussianBlur");
    feGaussianBlur.setAttribute("result","offset-blur");
    feGaussianBlur.setAttribute("stdDeviation", this.config.shadowSize);
    gaussFilter.appendChild(feGaussianBlur);
    
    // composite 1
    var feComposite1 = document.createElementNS(svg,"feComposite");
    feComposite1.setAttribute("operator","out");
    feComposite1.setAttribute("in", "SourceGraphic");
    feComposite1.setAttribute("in2","offset-blur");
    feComposite1.setAttribute("result","inverse");
    gaussFilter.appendChild(feComposite1);
    
    // flood
    var feFlood = document.createElementNS(svg,"feFlood");
    feFlood.setAttribute("flood-color","black");
    feFlood.setAttribute("flood-opacity", this.config.shadowOpacity);
    feFlood.setAttribute("result","color");
    gaussFilter.appendChild(feFlood);
    
    // composite 2
    var feComposite2 = document.createElementNS(svg,"feComposite");
    feComposite2.setAttribute("operator","in");
    feComposite2.setAttribute("in", "color");
    feComposite2.setAttribute("in2","inverse");
    feComposite2.setAttribute("result","shadow");
    gaussFilter.appendChild(feComposite2);
    
    // composite 3
    var feComposite3 = document.createElementNS(svg,"feComposite");
    feComposite3.setAttribute("operator","over");
    feComposite3.setAttribute("in", "shadow");
    feComposite3.setAttribute("in2","SourceGraphic");
    gaussFilter.appendChild(feComposite3);

    // set shadow
    if (this.config.showInnerShadow == true) {
      this.canvas.canvas.childNodes[2].setAttribute("filter", "url(#" + this.config.id + "-inner-shadow)");
      this.canvas.canvas.childNodes[3].setAttribute("filter", "url(#" + this.config.id + "-inner-shadow)");
    }
}

var getColorForPercentage = function(pct, col, grad) {
    
    var no = col.length;
    if (no === 1) return col[0];
    var inc = (grad) ? (1 / (no - 1)) : (1 / no);
    var colors = new Array();
    for (var i = 0; i < col.length; i++) {
      var percentage = (grad) ? (inc * i) : (inc * (i + 1));
      var rval = parseInt((cutHex(col[i])).substring(0,2),16);
      var gval = parseInt((cutHex(col[i])).substring(2,4),16);
      var bval = parseInt((cutHex(col[i])).substring(4,6),16);
      colors[i] = { pct: percentage, color: { r: rval, g: gval, b: bval  } };
    }
        
    if(pct == 0) return 'rgb(' + [colors[0].color.r, colors[0].color.g, colors[0].color.b].join(',') + ')';
      for (var i = 0; i < colors.length; i++) {
          if (pct <= colors[i].pct) {
            if (grad == true) {
              var lower = colors[i - 1];
              var upper = colors[i];
              var range = upper.pct - lower.pct;
              var rangePct = (pct - lower.pct) / range;
              var pctLower = 1 - rangePct;
              var pctUpper = rangePct;
              var color = {
                  r: Math.floor(lower.color.r * pctLower + upper.color.r * pctUpper),
                  g: Math.floor(lower.color.g * pctLower + upper.color.g * pctUpper),
                  b: Math.floor(lower.color.b * pctLower + upper.color.b * pctUpper)
              };
              return 'rgb(' + [color.r, color.g, color.b].join(',') + ')';
            } else {
              return 'rgb(' + [colors[i].color.r, colors[i].color.g, colors[i].color.b].join(',') + ')'; 
            }
          }
      }
} 

function getRandomInt (min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}    

function cutHex(str) {return (str.charAt(0)=="#") ? str.substring(1,7):str}

function getStyle(oElm, strCssRule){
	var strValue = "";
	if(document.defaultView && document.defaultView.getComputedStyle){
		strValue = document.defaultView.getComputedStyle(oElm, "").getPropertyValue(strCssRule);
	}
	else if(oElm.currentStyle){
		strCssRule = strCssRule.replace(/\-(\w)/g, function (strMatch, p1){
			return p1.toUpperCase();
		});
		strValue = oElm.currentStyle[strCssRule];
	}
	return strValue;
}

 function onCreateElementNsReady(func) {
  if (document.createElementNS != undefined) {
    func();
  } else {
    setTimeout(function() { onCreateElementNsReady(func); }, 100);
  }
}

// ----------------------------------------------------------
// A short snippet for detecting versions of IE in JavaScript
// without resorting to user-agent sniffing
// ----------------------------------------------------------
// If you're not in IE (or IE version is less than 5) then:
// ie === undefined
// If you're in IE (>=5) then you can determine which version:
// ie === 7; // IE7
// Thus, to detect IE:
// if (ie) {}
// And to detect the version:
// ie === 6 // IE6
// ie > 7 // IE8, IE9 ...
// ie < 9 // Anything less than IE9
// ----------------------------------------------------------

// UPDATE: Now using Live NodeList idea from @jdalton

var ie = (function(){

    var undef,
        v = 3,
        div = document.createElement('div'),
        all = div.getElementsByTagName('i');

    while (
        div.innerHTML = '<!--[if gt IE ' + (++v) + ']><i></i><![endif]-->',
        all[0]
    );

    return v > 4 ? v : undef;

}());


/*
 * Copyright (C) 2013 Panopta, Andrew Moffat
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */


(function ($) {
	$.fn.wizard = function(args) {
		return new Wizard(this, args);
	};

	$.fn.wizard.logging = false;


	WizardCard = function(wizard, card, index, prev, next) {
		this.wizard = wizard;
		this.index = index;
		this.prev = prev;
		this.next = next;
		this.el = card;
		this.title = card.find("h3").first().text();
		this.name = card.data("cardname") || this.title;

		this.nav = this._createNavElement(this.title, index);

		this._disabled = false;
		this._loaded = false;
		this._events = {};
	}

	WizardCard.prototype = {

		select: function() {
			this.log("selecting");
			if (!this.isSelected()) {
				this.nav.addClass("active");
				this.el.show();

				if (!this._loaded) {
					this.trigger("loaded");
					this.reload();
				}

				this.trigger("selected");
			}


			/*
			 * this is ugly, but we're handling the changing of the wizard's
			 * buttons here, in the WizardCard select.  so when a card is
			 * selected, we're figuring out if we're the first card or the
			 * last card and changing the wizard's buttons via the guts of
			 * the wizard
			 *
			 * ideally this logic should be encapsulated by some wizard methods
			 * that we can call from here, instead of messing with the guts
			 */
			var w = this.wizard;
			if (this.index >= w._cards.length-1) {
				this.log("on last card, changing next button to submit");

				w.changeNextButton(w.args.buttons.submitText, "btn-green");
				w._readyToSubmit = true;
				w.trigger("readySubmit");
			}
			else {
				w._readyToSubmit = false;
				if (this.index == 0) {
					w.backButton.toggleClass("disabled", true);
				}
				else {
					w.backButton.toggleClass("disabled", false);
				}
				w.changeNextButton(w.args.buttons.nextText, "btn-blue");
			}

			return this;
		},

		// <li><a href="#" data-navindex="0">
		//     <i class="icon-chevron-right"></i>
		//     Name &amp; FQDN
		// </a></li>
		_createNavElement: function(name, i) {
			var li = $('<li class="wizard-nav-item"></li>');
			var a = $('<a class="wizard-nav-link"></a>');
			a.data("navindex", i);
			li.append(a);
			a.append('<i class="icon-chevron-right"></i>')
			a.append(name);
			return li;
		},

		markVisited: function() {
			this.log("marking as visited");
			this.nav.addClass("already-visited");
			this.trigger("markVisited");
			return this;
		},

		unmarkVisited: function() {
			this.log("unmarking as visited");
			this.nav.removeClass("already-visited");
			this.trigger("unmarkVisited");
			return this;
		},

		deselect: function() {
			this.nav.removeClass("active");
			this.el.hide();
			this.trigger("deselect");
			return this;
		},

		enable: function() {
			this.log("enabling");
			this.nav.addClass("active");
			this._disabled = false;
			this.trigger("enabled");
			return this;
		},

		disable: function(hideCard) {
			this.log("disabling");
			this._disabled = true;
			this.nav.removeClass("active already-visited");
			if (hideCard) {this.el.hide();}
			this.trigger("disabled");
			return this;
		},

		isDisabled: function() {
			return this._disabled;
		},

		alreadyVisited: function() {
			return this.nav.hasClass("already-visited");
		},

		isSelected: function() {
			return this.nav.hasClass("active");
		},

		reload: function() {
			this._loaded = true;
			this.trigger("reload");
			return this;
		},

		on: function() {
			return this.wizard.on.apply(this, arguments);
		},

		trigger: function() {
			this.callListener("on"+arguments[0]);
			return this.wizard.trigger.apply(this, arguments);
		},

		/*
		 * displays an alert box on the current card
		 */
		toggleAlert: function(msg, toggle) {
			this.log("toggling alert to: " + toggle);

			toggle = typeof(toggle) == "undefined" ? true : toggle;

			if (toggle) {this.trigger("showAlert");}
			else {this.trigger("hideAlert");}

			var div;
			var alert = this.el.children("h3").first().next("div.alert");

			if (alert.length == 0) {
				/*
				 * we're hiding anyways, so no need to create anything.
				 * we'll do that if we ever are actually showing the alert
				 */
				if (!toggle) {return this;}

				this.log("couldn't find existing alert div, creating one");
				div = $("<div />");
				div.addClass("alert");
				div.addClass("hide");
				div.insertAfter(this.el.find("h3").first());
			}
			else {
				this.log("found existing alert div");
				div = alert.first();
			}

			if (toggle) {
				if (msg != null) {
					this.log("setting alert msg to", msg);
					div.html(msg);
				}
				div.show();
			}
			else {
				div.hide();
			}
			return this;
		},

		/*
		 * this looks for event handlers embedded into the html of the
		 * wizard card itself, in the form of a data- attribute
		 */
		callListener: function(name) {
            // a bug(?) in jquery..can't access data-<name> if name is camelCase
		    name = name.toLowerCase();

			this.log("looking for listener " + name);
			var listener = window[this.el.data(name)];
			if (listener) {
				this.log("calling listener " + name);
				var wizard = this.wizard;

				try {
					var vret = listener(this);
				}
				catch (e) {
					this.log("exception calling listener " + name + ": ", e);
				}
			}
			else {
				this.log("didn't find listener " + name);
			}
		},

		problem: function(toggle) {
			this.nav.find("a").toggleClass("wizard-step-error", toggle);
		},

		validate: function() {
			var failures = false;
			var self = this;

			/*
			 * run all the validators embedded on the inputs themselves
			 */
			this.el.find("[data-validate]").each(function(i, el) {
				self.log("validating individiual inputs");
				el = $(el);

				var v = el.data("validate");
				if (!v) {return;}

				var ret = {
					status: true,
					title: "Error",
					msg: ""
				};

				var vret = window[v](el);
				$.extend(ret, vret);

				if (!ret.status) {
					failures = true;
					el.parent(".control-group").toggleClass("error", true);
					self.wizard.errorPopover(el, ret.msg);
				}
				else {
					el.parent(".control-group").toggleClass("error", false);
					try {
						el.popover("destroy");
					}
					/*
					 * older versions of bootstrap don't have a destroy call
					 * for popovers
					 */
					catch (e) {
						el.popover("hide");
					}
				}
			});
			this.log("after validating inputs, failures is", failures);

			/*
			 * run the validator embedded in the card
			 */
			var cardValidator = window[this.el.data("validate")];
			if (cardValidator) {
				this.log("running html-embedded card validator");
				var cardValidated = cardValidator(this);
				if (typeof(cardValidated) == "undefined" || cardValidated == null) {
					cardValidated = true;
				}
				if (!cardValidated) failures = true;
				this.log("after running html-embedded card validator, failures\
 is", failures);
			}

			/*
			 * run the validate listener
			 */
			this.log("running listener validator");
			var listenerValidated = this.trigger("validate");
			if (typeof(listenerValidated) == "undefined" || listenerValidated == null) {
				listenerValidated = true;
			}
			if (!listenerValidated) failures = true;
			this.log("after running listener validator, failures is", failures);

			var validated = !failures;
			if (validated) {
				this.log("validated, calling listeners");
				this.trigger("validated");
			}
			else {
				this.log("invalid");
				this.trigger("invalid");
			}
			return validated;
		},

		log: function() {
			if (!window.console || !$.fn.wizard.logging) {return;}
			var prepend = "card '"+this.name+"': ";
			var args = [prepend];
			args.push.apply(args, arguments);

			console.log.apply(console, args);
		},

		isActive: function() {
			return this.nav.hasClass("active");
		},
	};


	Wizard = function(markup, args) {
		var wizard_template = [
			'<div class="modal" role="dialog">',
                '<div class="modal-dialog">',
                '<div class="modal-content wizard-modal">',
				'<div class="wizard-modal-header modal-header">',
					'<button class="wizard-close close" type="button">x</button>',
					'<h3 class="wizard-title"></h3>',
					'<span class="wizard-subtitle"></span>',
				'</div>',
				'<div class="pull-left wizard-steps">',
					'<div class="wizard-nav-container">',
						'<ul class="nav nav-list" style="padding-bottom:30px;">',
						'</ul>',
					'</div>',

					'<div class="wizard-progress-container">',,
						'<div class="progress">',
							'<div class="progress-bar"></div>',
						'</div>',
					'</div>',

				'</div>',

				'<form>',
					'<div class="wizard-cards">',
						'<div class="wizard-card-container">',
						'</div>',
						'<div class="wizard-modal-footer">',
							'<div class="wizard-buttons-container">',
								'<button class="btn btn-default wizard-back" type="button">Back</button>',
								'<button class="btn btn-blue wizard-next" type="button">Next</button>',
							'</div>',
						'</div>',
					'</div>',
				'</form>',
                '</div>',
                '</div>',
			'</div>'
		];

		this.args = {
			submitUrl: "",
			width: 750,
			progressBarCurrent: false,
			increaseHeight: 0,
			buttons: {
				nextText: "Next",
				backText: "Back",
				submitText: "Submit",
				submittingText: "Submitting...",
			}
		};
		$.extend(this.args, args || {});

		this.markup = $(markup);
		this.submitCards = this.markup.find(".wizard-error,.wizard-failure,\
.wizard-success,.wizard-loading");
		this.el = $(wizard_template.join("\n"));
		this.el.find(".wizard-card-container")
			.append(this.markup.find(".wizard-card"))
			.append(this.submitCards);
		$("body").append(this.el);

		this.closeButton = this.el.find("button.wizard-close");
		this.footer = this.el.find(".wizard-modal-footer");
		this.backButton = this.footer.find(".wizard-back");
		this.nextButton = this.footer.find(".wizard-next");
		this.progress = this.el.find(".progress");
		this._cards = [];
		this.cards = {};
		this._readyToSubmit = false;
		this.percentComplete = 0;
		this._submitting = false;
		this._events = {};
		this._firstShow = true;


		// construction


		this._createCards();

		this.nextButton.click(this, this._handleNextClick);
		this.backButton.click(this, this._handleBackClick);

		this.backButton.text(this.args.buttons.backText);
		this.nextButton.text(this.args.buttons.nextText);



		/*
		 * adjust the height of the modal, and everything associated with
		 * adjusting the height
		 */
		var baseHeight = 360;
		var navHeight = baseHeight + this.args.increaseHeight;

		this.el.find(".wizard-nav-container").css("height", navHeight);
		this.el.find(".wizard-steps").css("height", (navHeight+65)+"px");
		this.el.find(".wizard-card").css("height", (navHeight-60)+"px");
		this.submitCards.css("height", (navHeight-60)+"px");

        var formHeight = this.el.find(".wizard-steps").height();
        this.el.find("form").css('min-height', formHeight);
        this.el.find(".wizard-card-container").css("min-height", formHeight - 65);
//		this.el.css("margin-top", -(this.el.height() / 2));


		/*
		 * adjust the width of the modal
		 */
//		this.el.css("width", this.args.width);
//		this.el.css("margin-left", -(this.args.width / 2));



		/*
		 * set up slimScroll for our nav, if slimScroll is installed
		 */
		if ($.fn.slimScroll && false) {
			var slimScrollArgs = {
				position: "left",
				height: "360px",
				size: "8px",
				distance: "5px",
				railVisible: true,
				disableFadeOut: true,
			};
			$.extend(slimScrollArgs, this.args.slimScroll || {});
			this.el.find(".wizard-nav-container").slimScroll(slimScrollArgs);
		}

		/*
		 * if the close X is clicked, reset the wizard
		 */
		var self = this;
		this.closeButton.click(function() {
			self.reset();
			self.close();
		})

		this.el.find(".wizard-steps").on(
			"click", "li.already-visited a.wizard-nav-link", this,
			function(event) {
				var index = parseInt($(event.target).data("navindex"));
				event.data.setCard(index);
			});

		var title = this.markup.children("h1").first();
		if (title.length) {this.setTitle(title.text());}

		this.on("submit", this._defaultSubmit);
	}

	Wizard.prototype = {

		errorPopover: function(el, msg) {
			this.log("launching popover on", el);
			var popover = el.popover({
				content: msg,
				trigger: "manual"
			}).popover("show").next(".popover");

			popover.addClass("error-popover");
			return popover;
		},

		eachCard: function(fn) {
			$.each(this._cards, fn);
			return this;
		},

		getActiveCard: function() {
			this.log("getting active card");
			var currentCard = null;

			$.each(this._cards, function(i, card) {
				if (card.isActive()) {
					currentCard = card;
					return false;
				}
			});
			if (currentCard) {this.log("found active card", currentCard);}
			else {this.log("couldn't find an active card");}
			return currentCard;
		},

		setTitle: function(title) {
			this.log("setting title to", title);
			this.el.find(".wizard-title").first().text(title);
			return this;
		},

		setSubtitle: function(title) {
			this.log("setting subtitle to", title);
			this.el.find(".wizard-subtitle").first().text(title);
			return this;
		},

		changeNextButton: function(text, cls) {
			this.log("changing next button, text: " + text, "class: " + cls);
			if (typeof(cls) != "undefined") {
				this.nextButton.removeClass("btn-green btn-blue");
			}

			if (cls) {
				this.nextButton.addClass(cls);
			}
			this.nextButton.text(text);
			return this;
		},

		hide: function() {
			this.log("hiding");
			this.el.modal("hide");
			return this;
		},

		close: function() {
			this.log("closing");
			this.el.modal("hide");
			return this;
		},


		show: function() {
			this.log("showing");
			if (this._firstShow) {
				this.setCard(0);
				this._firstShow = false;
			}
			this.el.modal();
			return this;
		},

		on: function(name, fn) {
			this.log("adding listener to event " + name);
			this._events[name] = fn;
			return this;
		},

		trigger: function() {
			var name = arguments[0];
			var args = Array.prototype.slice.call(arguments);
			args.shift();
			args.unshift(this);

			this.log("firing event " + name);
			var handler = this._events[name];
			var ret = null;

			if (typeof(handler) == "function") {
				this.log("found event handler, calling " + name);
				try {
					ret = handler.apply(this, args);
				}
				catch (e) {
					this.log("event handler " + name + " had an exception");
				}
			}
			else {
				this.log("couldn't find an event handler for " + name);
			}
			return ret;
		},


		reset: function() {
			this.log("resetting");
			this.updateProgressBar(0);
			this.hideSubmitCards();

			this.setCard(0);
			this.lockCards();

			this.enableNextButton();
			this.showButtons();

			this.trigger("reset");
			return this;
		},

		log: function() {
			if (!window.console || !$.fn.wizard.logging) {return;}
			var prepend = "wizard "+this.el.id+": ";
			var args = [prepend];
			args.push.apply(args, arguments);
			console.log.apply(console, args);
		},


		/*
		 * this handles switching to the next card or previous card, taking
		 * care to skip over disabled cards
		 */
		_abstractIncrementStep: function(direction, getNext) {
			var current = this.getActiveCard();
			var next;

			if (current) {
				/*
				 * loop until we find a card that isn't disabled
				 */
			    this.log("searching for valid next card");
				while (true) {
					next = getNext(current);
					if (next) {
						this.log("looking at card", next.index);
						if (next.isDisabled()) {
							this.log("card " + next.index + " is disabled/locked, continuing");
							current = next;
							continue;
						}
						else {
							return this.setCard(current.index+direction);
						}
					}
					else {
						this.log("next card is not defined, breaking");
						break;
					}
				}
			}
			else {
				this.log("current card is undefined");
			}
		},


		incrementCard: function() {
			this.log("incrementing card");
			var card = this._abstractIncrementStep(1, function(current){return current.next;});
			this.trigger("incrementCard");
			return card;
		},

		decrementCard: function() {
			this.log("decrementing card");
			var card = this._abstractIncrementStep(-1, function(current){return current.prev;});
			this.trigger("decrementCard");
			return card;
		},

		setCard: function(i) {
			this.log("setting card to " + i);
			this.hideSubmitCards();
			var currentCard = this.getActiveCard();

			if (this._submitting) {
				this.log("we're submitting the wizard already, can't change cards");
				return currentCard;
			}

			var newCard = this._cards[i];
			if (newCard) {
				if (newCard.isDisabled()) {
					this.log("new card is currently disabled, returning");
					return currentCard;
				}

				if (currentCard) {

					/*
					 * here, we're only validating if we're going forward,
					 * not if we're going backwards in a step
					 */
					if (i > currentCard.index) {
						var cardToValidate = currentCard;
						var ok = false;

						/*
						 * we need to loop over every card between our current
						 * card and the card that we clicked, and re-validate
						 * them.  if there's an error, we need to select the
						 * first card to have an error
						 */
						while (cardToValidate.index != newCard.index) {
							/*
							 * unless we're validating the card that we're
							 * leaving, we need to select the card, so that
							 * any validators that trigger errorPopovers can
							 * display correctly
							 */
							if (cardToValidate.index != currentCard.index) {
								cardToValidate.prev.deselect();
								cardToValidate.prev.markVisited();
								cardToValidate.select();
							}
							ok = cardToValidate.validate();
							if (!ok) {
								return cardToValidate;
							}
							cardToValidate = cardToValidate.next;
						}

						cardToValidate.prev.deselect();
						cardToValidate.prev.markVisited();
					}

					currentCard.deselect();
					currentCard.markVisited();
				}

				newCard.select();

				if (this.args.progressBarCurrent) {
					var last_percent = this.percentComplete;
					this.percentComplete = i * 100.0 / this._cards.length;
					this.updateProgressBar(this.percentComplete);					
				}
				else {
					var last_percent = this.percentComplete;
					this.percentComplete = i * 100.0 / this._cards.length;
					this.percentComplete = Math.max(last_percent, this.percentComplete);
					this.updateProgressBar(this.percentComplete);
				}

				return newCard;
			}
			else {
				this.log("couldn't find card " + i);
			}
		},

		updateProgressBar: function(percent) {
			this.log("updating progress to " + percent + "%");
			this.progress.find(".progress-bar").css({width: percent + "%"});
			this.percentComplete = percent;

			this.trigger("progressBar", percent);

			if (percent == 100) {
				this.log("progress is 100, animating progress bar");
				this.progress.addClass("active");
			}
			else if (percent == 0) {
				this.log("progress is 0, disabling animation");
				this.progress.removeClass("active");
			}
		},

		getNextCard: function() {
			var currentCard = this.getActiveCard();
			if (currentCard) return currentCard.next;
		},

		lockCards: function() {
			this.log("locking nav cards");
			this.eachCard(function(i,card){card.unmarkVisited();});
			return this;
		},

		disableCards: function() {
			this.log("disabling all nav cards");
			this.eachCard(function(i,card){card.disable();});
			return this;
		},

		enableCards: function() {
			this.log("enabling all nav cards");
			this.eachCard(function(i,card){card.enable();});
			return this;
		},

		hideCards: function() {
			this.log("hiding cards");
			this.eachCard(function(i,card){card.deselect();});
			this.hideSubmitCards();
			return this;
		},

		hideButtons: function() {
			this.log("hiding buttons");
			this.nextButton.hide();
			this.backButton.hide();
			return this;
		},

		showButtons: function() {
			this.log("showing buttons");
			this.nextButton.show();
			this.backButton.show();
			return this;
		},

		getCard: function(el) {
			var cardDOMEl = $(el).parents(".wizard-card").first()[0];
			if (cardDOMEl) {
				var foundCard = null;
				this.eachCard(function(i, card) {
					if (cardDOMEl == card.el[0]) {
						foundCard = card;
						return false;
					}
					return true;
				});
				return foundCard;
			}
			else {
				return null;
			}
		},

		_createCards: function() {
			var prev = null;
			var next = null;
			var current_i = 0;
			var currentCard = null;
			var wizard = this;
			var self = this;

			var cards = this.el.find(".wizard-cards .wizard-card");
			$.each(cards, function(i, card) {
				card = $(card);

				prev = currentCard;
				currentCard = new WizardCard(wizard, card, i, prev, next);
				self._cards.push(currentCard);
				if (currentCard.name) {
					self.cards[currentCard.name] = currentCard;
				}
				if (prev) {prev.next = currentCard;}

				self.el.find(".wizard-steps .nav-list").append(currentCard.nav);
			});
		},

		showSubmitCard: function(name) {
			this.log("showing "+name+" submit card");

			var card = this.el.find(".wizard-"+name);
			if (card.length) {
				this.hideCards();
				this.el.find(".wizard-"+name).show();
			}
			else {
				this.log("couldn't find submit card "+name);
			}
		},

		hideSubmitCard: function(name) {
			this.log("hiding "+name+" submit card");
			this.el.find(".wizard-"+name).hide();
		},

		hideSubmitCards: function() {
			var wizard = this;
			$.each(["success", "error", "failure", "loading"], function(i, name) {
				wizard.hideSubmitCard(name);
			});
		},

		enableNextButton: function() {
			this.log("enabling next button");
			this.nextButton.removeAttr("disabled");
			return this;
		},

		disableNextButton: function() {
			this.log("disabling next button");
			this.nextButton.attr("disabled", "disabled");
			return this;
		},

		serializeArray: function() {
			var form = this.el.children("form").first();
			return form.serializeArray();
		},

		serialize: function() {
			var form = this.el.children("form").first();
			return form.serialize();
		},


		/*
		 * the next 3 functions are to be called by the custom submit event
		 * handler.  the idea is that after you make an ajax call to submit
		 * your wizard data (or whatever it is you want to do at the end of
		 * the wizard), you call one of these 3 handlers to display a specific
		 * card for either success, failure, or error
		 */
		submitSuccess: function() {
			this.log("submit success");
			this._submitting = false;
    		this.showSubmitCard("success");
    		this.trigger("submitSuccess");
		},

		submitFailure: function() {
			this.log("submit failure");
			this._submitting = false;
    		this.showSubmitCard("failure");
    		this.trigger("submitFailure");
		},

		submitError: function() {
			this.log("submit error");
			this._submitting = false;
    		this.showSubmitCard("error");
    		this.trigger("submitError");
		},


		_submit: function() {
			this.log("submitting wizard");
			this._submitting = true;

			this.lockCards();
			this.backButton.hide();

			this.showSubmitCard("loading");
			this.updateProgressBar(100);

			this.changeNextButton(this.args.buttons.submittingText, false);
			this.disableNextButton();

			var ret = this.trigger("submit");
			this.trigger("loading");
		},

		_onNextClick: function() {
			this.log("handling 'next' button click");

			if (this._readyToSubmit) {
				this._submit();
			}
			else {
				var currentCard = this.incrementCard();
			}
		},

		_onBackClick: function() {
			this.log("handling 'back' button click");
			var currentCard = this.decrementCard();
		},


		_handleNextClick: function(event) {
			var wizard = event.data;
			wizard._onNextClick.call(wizard);
		},

		_handleBackClick: function(event) {
			var wizard = event.data;
			wizard._onBackClick.call(wizard);
		},


		/*
		 * this function is attached by default to the wizard's "submit" event.
		 * if you choose to implement your own custom submit logic, you should
		 * copy how this function works
		 */
		_defaultSubmit: function(wizard) {
	    	$.ajax({
	    		type: "POST",
	    		url: wizard.args.submitUrl,
	    		data: wizard.serialize(),
	    		dataType: "json",
	    		success: function(resp) {
		    		wizard.submitSuccess();
		    		wizard.hideButtons();
		    		wizard.updateProgressBar(0);
		    	},
		    	error: function() {
		    		wizard.submitFailure();
		    		wizard.hideButtons();
		    	},
	    	});
		}

	};

}(window.jQuery));
function setServerName(card) {
    var host = $("#new-server-fqdn").val();
    var name = $("#new-server-name").val();
    var displayName = host;

    if (name) {
        displayName = name + " ("+host+")";
    };

    card.wizard.setSubtitle(displayName);
    card.wizard.el.find(".create-server-name").text(displayName);
}

function validateIP(ipaddr) {
    //Remember, this function will validate only Class C IP.
    //change to other IP Classes as you need
    ipaddr = ipaddr.replace(/\s/g, "") //remove spaces for checking
    var re = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/; //regex. check for digits and in
    //all 4 quadrants of the IP
    if (re.test(ipaddr)) {
        //split into units with dots "."
        var parts = ipaddr.split(".");
        //if the first unit/quadrant of the IP is zero
        if (parseInt(parseFloat(parts[0])) == 0) {
            return false;
        }
        //if the fourth unit/quadrant of the IP is zero
        if (parseInt(parseFloat(parts[3])) == 0) {
            return false;
        }
        //if any part is greater than 255
        for (var i=0; i<parts.length; i++) {
            if (parseInt(parseFloat(parts[i])) > 255){
                return false;
            }
        }
        return true;
    }
    else {
        return false;
    }
}

function validateFQDN(val) {
    return /^[a-z0-9-_]+(\.[a-z0-9-_]+)*\.([a-z]{2,4})$/.test(val);
}

function fqdn_or_ip(el) {
    var val = el.val();
    ret = {
        status: true
    };
    if (!validateFQDN(val)) {
        if (!validateIP(val)) {
            ret.status = false;
            ret.msg = "Invalid IP address or FQDN";
        }
    }
    return ret;
}


$(function() {
    $.fn.wizard.logging = true;



    if ($("#wizard-demo").length > 0) {

        var wizard = $("#wizard-demo").wizard();

        wizard.el.find(".wizard-ns-select").change(function() {
            wizard.el.find(".wizard-ns-detail").show();
        });

        wizard.el.find(".create-server-service-list").change(function() {
            var noOption = $(this).find("option:selected").length == 0;
            wizard.getCard(this).toggleAlert(null, noOption);
        });

        wizard.cards["name"].on("validated", function(card) {
            var hostname = card.el.find("#new-server-fqdn").val();
        });

        wizard.on("submit", function(wizard) {
            var submit = {
                "hostname": $("#new-server-fqdn").val()
            };

            setTimeout(function() {
                wizard.trigger("success");
                wizard.hideButtons();
                wizard._submitting = false;
                wizard.showSubmitCard("success");
                wizard._updateProgressBar(0);
            }, 2000);
        });

        wizard.on("reset", function(wizard) {
            wizard.setSubtitle("");
            wizard.el.find("#new-server-fqdn").val("");
            wizard.el.find("#new-server-name").val("");
        });

        wizard.el.find(".wizard-success .im-done").click(function() {
            wizard.reset().close();
        });

        wizard.el.find(".wizard-success .create-another-server").click(function() {
            wizard.reset();
        });

        $(".wizard-group-list").click(function() {
            alert("Disabled for demo.");
        });

        $("#open-wizard").click(function() {
            wizard.show();
        });

        wizard.show();
    }


});

/*
Easy pie chart is a jquery plugin to display simple animated pie charts for only one value

Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.

Built on top of the jQuery library (http://jquery.com)

@source: http://github.com/rendro/easy-pie-chart/
@autor: Robert Fleischmann
@version: 1.0.1

Inspired by: http://dribbble.com/shots/631074-Simple-Pie-Charts-II?list=popular&offset=210
Thanks to Philip Thrasher for the jquery plugin boilerplate for coffee script
*/


(function() {

  (function($) {
    $.easyPieChart = function(el, options) {
      var addScaleLine, animateLine, drawLine, easeInOutQuad, renderBackground, renderScale, renderTrack,
        _this = this;
      this.el = el;
      this.$el = $(el);
      this.$el.data("easyPieChart", this);
      this.init = function() {
        var percent, scaleBy;
        _this.options = $.extend({}, $.easyPieChart.defaultOptions, options);
        percent = parseInt(_this.$el.data('percent'), 10);
        _this.percentage = 0;
        _this.canvas = $("<canvas width='" + _this.options.size + "' height='" + _this.options.size + "'></canvas>").get(0);
        _this.$el.append(_this.canvas);
        if (typeof G_vmlCanvasManager !== "undefined" && G_vmlCanvasManager !== null) {
          G_vmlCanvasManager.initElement(_this.canvas);
        }
        _this.ctx = _this.canvas.getContext('2d');
        if (window.devicePixelRatio > 1) {
          scaleBy = window.devicePixelRatio;
          $(_this.canvas).css({
            width: _this.options.size,
            height: _this.options.size
          });
          _this.canvas.width *= scaleBy;
          _this.canvas.height *= scaleBy;
          _this.ctx.scale(scaleBy, scaleBy);
        }
        _this.ctx.translate(_this.options.size / 2, _this.options.size / 2);
        _this.$el.addClass('easyPieChart');
        _this.$el.css({
          width: _this.options.size,
          height: _this.options.size,
          lineHeight: "" + _this.options.size + "px"
        });
        _this.update(percent);
        return _this;
      };
      this.update = function(percent) {
        if (_this.options.animate === false) {
          return drawLine(percent);
        } else {
          return animateLine(_this.percentage, percent);
        }
      };
      renderScale = function() {
        var i, _i, _results;
        _this.ctx.fillStyle = _this.options.scaleColor;
        _this.ctx.lineWidth = 1;
        _results = [];
        for (i = _i = 0; _i <= 24; i = ++_i) {
          _results.push(addScaleLine(i));
        }
        return _results;
      };
      addScaleLine = function(i) {
        var offset;
        offset = i % 6 === 0 ? 0 : _this.options.size * 0.017;
        _this.ctx.save();
        _this.ctx.rotate(i * Math.PI / 12);
        _this.ctx.fillRect(_this.options.size / 2 - offset, 0, -_this.options.size * 0.05 + offset, 1);
        return _this.ctx.restore();
      };
      renderTrack = function() {
        var offset;
        offset = _this.options.size / 2 - _this.options.lineWidth / 2;
        if (_this.options.scaleColor !== false) {
          offset -= _this.options.size * 0.08;
        }
        _this.ctx.beginPath();
        _this.ctx.arc(0, 0, offset, 0, Math.PI * 2, true);
        _this.ctx.closePath();
        _this.ctx.strokeStyle = _this.options.trackColor;
        _this.ctx.lineWidth = _this.options.lineWidth;
        return _this.ctx.stroke();
      };
      renderBackground = function() {
        if (_this.options.scaleColor !== false) {
          renderScale();
        }
        if (_this.options.trackColor !== false) {
          return renderTrack();
        }
      };
      drawLine = function(percent) {
        var offset;
        renderBackground();
        _this.ctx.strokeStyle = $.isFunction(_this.options.barColor) ? _this.options.barColor(percent) : _this.options.barColor;
        _this.ctx.lineCap = _this.options.lineCap;
        _this.ctx.lineWidth = _this.options.lineWidth;
        offset = _this.options.size / 2 - _this.options.lineWidth / 2;
        if (_this.options.scaleColor !== false) {
          offset -= _this.options.size * 0.08;
        }
        _this.ctx.save();
        _this.ctx.rotate(-Math.PI / 2);
        _this.ctx.beginPath();
        _this.ctx.arc(0, 0, offset, 0, Math.PI * 2 * percent / 100, false);
        _this.ctx.stroke();
        return _this.ctx.restore();
      };
      animateLine = function(from, to) {
        var currentStep, fps, steps;
        fps = 30;
        steps = fps * _this.options.animate / 1000;
        currentStep = 0;
        _this.options.onStart.call(_this);
        _this.percentage = to;
        if (_this.animation) {
          clearInterval(_this.animation);
          _this.animation = false;
        }
        return _this.animation = setInterval(function() {
          _this.ctx.clearRect(-_this.options.size / 2, -_this.options.size / 2, _this.options.size, _this.options.size);
          renderBackground.call(_this);
          drawLine.call(_this, [easeInOutQuad(currentStep, from, to - from, steps)]);
          currentStep++;
          if ((currentStep / steps) > 1) {
            clearInterval(_this.animation);
            _this.animation = false;
            return _this.options.onStop.call(_this);
          }
        }, 1000 / fps);
      };
      easeInOutQuad = function(t, b, c, d) {
        var easeIn, easing;
        easeIn = function(t) {
          return Math.pow(t, 2);
        };
        easing = function(t) {
          if (t < 1) {
            return easeIn(t);
          } else {
            return 2 - easeIn((t / 2) * -2 + 2);
          }
        };
        t /= d / 2;
        return c / 2 * easing(t) + b;
      };
      return this.init();
    };
    $.easyPieChart.defaultOptions = {
      barColor: '#ef1e25',
      trackColor: '#f2f2f2',
      scaleColor: '#dfe0e0',
      lineCap: 'round',
      size: 110,
      lineWidth: 3,
      animate: false,
      onStart: $.noop,
      onStop: $.noop
    };
    $.fn.easyPieChart = function(options) {
      return $.each(this, function(i, el) {
        var $el;
        $el = $(el);
        if (!$el.data('easyPieChart')) {
          return $el.data('easyPieChart', new $.easyPieChart(el, options));
        }
      });
    };
    return void 0;
  })(jQuery);

}).call(this);
/**
 * @name		jQuery touchTouch plugin
 * @author		Martin Angelov
 * @version 	1.0
 * @url			http://tutorialzine.com/2012/04/mobile-touch-gallery/
 * @license		MIT License
 */


(function(){

    /* Private variables */

    var overlay = $('<div id="galleryOverlay">'),
        slider = $('<div id="gallerySlider">'),
    //CORE-ADMIN-MOD
//        prevArrow = $('<a id="prevArrow"></a>'),
//        nextArrow = $('<a id="nextArrow"></a>'),
        prevArrow = $('<a id="prevArrow"><i class="icon-angle-left icon-4x"></i></a>'),
        nextArrow = $('<a id="nextArrow"><i class="icon-angle-right icon-4x"></i></a>'),
        overlayVisible = false;


    /* Creating the plugin */

    $.fn.touchTouch = function(){

        var placeholders = $([]),
            index = 0,
            items = this;

        // Appending the markup to the page
        overlay.hide().appendTo('body');
        slider.appendTo(overlay);

        // Creating a placeholder for each image
        items.each(function(){
            placeholders = placeholders.add($('<div class="placeholder">'));
        });

        // Hide the gallery if the background is touched / clicked
        slider.append(placeholders).on('click',function(e){
            if(!$(e.target).is('img')){
                hideOverlay();
            }
        });

        // Listen for touch events on the body and check if they
        // originated in #gallerySlider img - the images in the slider.
        $('body').on('touchstart', '#gallerySlider img', function(e){

            var touch = e.originalEvent,
                startX = touch.changedTouches[0].pageX;

            slider.on('touchmove',function(e){

                e.preventDefault();

                touch = e.originalEvent.touches[0] ||
                    e.originalEvent.changedTouches[0];

                if(touch.pageX - startX > 10){
                    slider.off('touchmove');
                    showPrevious();
                }
                else if (touch.pageX - startX < -10){
                    slider.off('touchmove');
                    showNext();
                }
            });

            // Return false to prevent image
            // highlighting on Android
            return false;

        }).on('touchend',function(){
                slider.off('touchmove');
            });

        // Listening for clicks on the thumbnails

        items.on('click', function(e){
            e.preventDefault();

            // Find the position of this image
            // in the collection

            index = items.index(this);
            showOverlay(index);
            showImage(index);

            // Preload the next image
            preload(index+1);

            // Preload the previous
            preload(index-1);

        });

        // If the browser does not have support
        // for touch, display the arrows
        if ( !("ontouchstart" in window) ){
            overlay.append(prevArrow).append(nextArrow);

            prevArrow.click(function(e){
                e.preventDefault();
                showPrevious();
            });

            nextArrow.click(function(e){
                e.preventDefault();
                showNext();
            });
        }

        // Listen for arrow keys
        $(window).bind('keydown', function(e){

            if (e.keyCode == 37){
                showPrevious();
            }
            else if (e.keyCode==39){
                showNext();
            }

        });


        /* Private functions */


        function showOverlay(index){

            // If the overlay is already shown, exit
            if (overlayVisible){
                return false;
            }

            // Show the overlay
            overlay.show();

            setTimeout(function(){
                // Trigger the opacity CSS transition
                overlay.addClass('visible');
            }, 100);

            // Move the slider to the correct image
            offsetSlider(index);

            // Raise the visible flag
            overlayVisible = true;
        }

        function hideOverlay(){
            // If the overlay is not shown, exit
            if(!overlayVisible){
                return false;
            }

            // Hide the overlay
            overlay.hide().removeClass('visible');
            overlayVisible = false;
        }

        function offsetSlider(index){
            // This will trigger a smooth css transition
            slider.css('left',(-index*100)+'%');
        }

        // Preload an image by its index in the items array
        function preload(index){
            setTimeout(function(){
                showImage(index);
            }, 1000);
        }

        // Show image in the slider
        function showImage(index){

            // If the index is outside the bonds of the array
            if(index < 0 || index >= items.length){
                return false;
            }

            // Call the load function with the href attribute of the item
            loadImage(items.eq(index).attr('href'), function(){
                placeholders.eq(index).html(this);
            });
        }

        // Load the image and execute a callback function.
        // Returns a jQuery object

        function loadImage(src, callback){
            var img = $('<img>').on('load', function(){
                callback.call(img);
            });

            img.attr('src',src);
        }

        function showNext(){

            // If this is not the last image
            if(index+1 < items.length){
                index++;
                offsetSlider(index);
                preload(index+1);
            }
            else{
                // Trigger the spring animation

                slider.addClass('rightSpring');
                setTimeout(function(){
                    slider.removeClass('rightSpring');
                },500);
            }
        }

        function showPrevious(){

            // If this is not the first image
            if(index>0){
                index--;
                offsetSlider(index);
                preload(index-1);
            }
            else{
                // Trigger the spring animation

                slider.addClass('leftSpring');
                setTimeout(function(){
                    slider.removeClass('leftSpring');
                },500);
            }
        }
    };

})(jQuery);
/*
 * File:        jquery.dataTables.min.js
 * Version:     1.9.4
 * Author:      Allan Jardine (www.sprymedia.co.uk)
 * Info:        www.datatables.net
 *
 * Copyright 2008-2012 Allan Jardine, all rights reserved.
 *
 * This source file is free software, under either the GPL v2 license or a
 * BSD style license, available at:
 *   http://datatables.net/license_gpl2
 *   http://datatables.net/license_bsd
 *
 * This source file is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the license files for details.
 */

(function(X,l,n){var L=function(h){var j=function(e){function o(a,b){var c=j.defaults.columns,d=a.aoColumns.length,c=h.extend({},j.models.oColumn,c,{sSortingClass:a.oClasses.sSortable,sSortingClassJUI:a.oClasses.sSortJUI,nTh:b?b:l.createElement("th"),sTitle:c.sTitle?c.sTitle:b?b.innerHTML:"",aDataSort:c.aDataSort?c.aDataSort:[d],mData:c.mData?c.oDefaults:d});a.aoColumns.push(c);if(a.aoPreSearchCols[d]===n||null===a.aoPreSearchCols[d])a.aoPreSearchCols[d]=h.extend({},j.models.oSearch);else if(c=a.aoPreSearchCols[d],
    c.bRegex===n&&(c.bRegex=!0),c.bSmart===n&&(c.bSmart=!0),c.bCaseInsensitive===n)c.bCaseInsensitive=!0;m(a,d,null)}function m(a,b,c){var d=a.aoColumns[b];c!==n&&null!==c&&(c.mDataProp&&!c.mData&&(c.mData=c.mDataProp),c.sType!==n&&(d.sType=c.sType,d._bAutoType=!1),h.extend(d,c),p(d,c,"sWidth","sWidthOrig"),c.iDataSort!==n&&(d.aDataSort=[c.iDataSort]),p(d,c,"aDataSort"));var i=d.mRender?Q(d.mRender):null,f=Q(d.mData);d.fnGetData=function(a,b){var c=f(a,b);return d.mRender&&b&&""!==b?i(c,b,a):c};d.fnSetData=
    L(d.mData);a.oFeatures.bSort||(d.bSortable=!1);!d.bSortable||-1==h.inArray("asc",d.asSorting)&&-1==h.inArray("desc",d.asSorting)?(d.sSortingClass=a.oClasses.sSortableNone,d.sSortingClassJUI=""):-1==h.inArray("asc",d.asSorting)&&-1==h.inArray("desc",d.asSorting)?(d.sSortingClass=a.oClasses.sSortable,d.sSortingClassJUI=a.oClasses.sSortJUI):-1!=h.inArray("asc",d.asSorting)&&-1==h.inArray("desc",d.asSorting)?(d.sSortingClass=a.oClasses.sSortableAsc,d.sSortingClassJUI=a.oClasses.sSortJUIAscAllowed):-1==
    h.inArray("asc",d.asSorting)&&-1!=h.inArray("desc",d.asSorting)&&(d.sSortingClass=a.oClasses.sSortableDesc,d.sSortingClassJUI=a.oClasses.sSortJUIDescAllowed)}function k(a){if(!1===a.oFeatures.bAutoWidth)return!1;da(a);for(var b=0,c=a.aoColumns.length;b<c;b++)a.aoColumns[b].nTh.style.width=a.aoColumns[b].sWidth}function G(a,b){var c=r(a,"bVisible");return"number"===typeof c[b]?c[b]:null}function R(a,b){var c=r(a,"bVisible"),c=h.inArray(b,c);return-1!==c?c:null}function t(a){return r(a,"bVisible").length}
    function r(a,b){var c=[];h.map(a.aoColumns,function(a,i){a[b]&&c.push(i)});return c}function B(a){for(var b=j.ext.aTypes,c=b.length,d=0;d<c;d++){var i=b[d](a);if(null!==i)return i}return"string"}function u(a,b){for(var c=b.split(","),d=[],i=0,f=a.aoColumns.length;i<f;i++)for(var g=0;g<f;g++)if(a.aoColumns[i].sName==c[g]){d.push(g);break}return d}function M(a){for(var b="",c=0,d=a.aoColumns.length;c<d;c++)b+=a.aoColumns[c].sName+",";return b.length==d?"":b.slice(0,-1)}function ta(a,b,c,d){var i,f,
        g,e,w;if(b)for(i=b.length-1;0<=i;i--){var j=b[i].aTargets;h.isArray(j)||D(a,1,"aTargets must be an array of targets, not a "+typeof j);f=0;for(g=j.length;f<g;f++)if("number"===typeof j[f]&&0<=j[f]){for(;a.aoColumns.length<=j[f];)o(a);d(j[f],b[i])}else if("number"===typeof j[f]&&0>j[f])d(a.aoColumns.length+j[f],b[i]);else if("string"===typeof j[f]){e=0;for(w=a.aoColumns.length;e<w;e++)("_all"==j[f]||h(a.aoColumns[e].nTh).hasClass(j[f]))&&d(e,b[i])}}if(c){i=0;for(a=c.length;i<a;i++)d(i,c[i])}}function H(a,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   b){var c;c=h.isArray(b)?b.slice():h.extend(!0,{},b);var d=a.aoData.length,i=h.extend(!0,{},j.models.oRow);i._aData=c;a.aoData.push(i);for(var f,i=0,g=a.aoColumns.length;i<g;i++)c=a.aoColumns[i],"function"===typeof c.fnRender&&c.bUseRendered&&null!==c.mData?F(a,d,i,S(a,d,i)):F(a,d,i,v(a,d,i)),c._bAutoType&&"string"!=c.sType&&(f=v(a,d,i,"type"),null!==f&&""!==f&&(f=B(f),null===c.sType?c.sType=f:c.sType!=f&&"html"!=c.sType&&(c.sType="string")));a.aiDisplayMaster.push(d);a.oFeatures.bDeferRender||ea(a,
        d);return d}function ua(a){var b,c,d,i,f,g,e;if(a.bDeferLoading||null===a.sAjaxSource)for(b=a.nTBody.firstChild;b;){if("TR"==b.nodeName.toUpperCase()){c=a.aoData.length;b._DT_RowIndex=c;a.aoData.push(h.extend(!0,{},j.models.oRow,{nTr:b}));a.aiDisplayMaster.push(c);f=b.firstChild;for(d=0;f;){g=f.nodeName.toUpperCase();if("TD"==g||"TH"==g)F(a,c,d,h.trim(f.innerHTML)),d++;f=f.nextSibling}}b=b.nextSibling}i=T(a);d=[];b=0;for(c=i.length;b<c;b++)for(f=i[b].firstChild;f;)g=f.nodeName.toUpperCase(),("TD"==
        g||"TH"==g)&&d.push(f),f=f.nextSibling;c=0;for(i=a.aoColumns.length;c<i;c++){e=a.aoColumns[c];null===e.sTitle&&(e.sTitle=e.nTh.innerHTML);var w=e._bAutoType,o="function"===typeof e.fnRender,k=null!==e.sClass,n=e.bVisible,m,p;if(w||o||k||!n){g=0;for(b=a.aoData.length;g<b;g++)f=a.aoData[g],m=d[g*i+c],w&&"string"!=e.sType&&(p=v(a,g,c,"type"),""!==p&&(p=B(p),null===e.sType?e.sType=p:e.sType!=p&&"html"!=e.sType&&(e.sType="string"))),e.mRender?m.innerHTML=v(a,g,c,"display"):e.mData!==c&&(m.innerHTML=v(a,
        g,c,"display")),o&&(p=S(a,g,c),m.innerHTML=p,e.bUseRendered&&F(a,g,c,p)),k&&(m.className+=" "+e.sClass),n?f._anHidden[c]=null:(f._anHidden[c]=m,m.parentNode.removeChild(m)),e.fnCreatedCell&&e.fnCreatedCell.call(a.oInstance,m,v(a,g,c,"display"),f._aData,g,c)}}if(0!==a.aoRowCreatedCallback.length){b=0;for(c=a.aoData.length;b<c;b++)f=a.aoData[b],A(a,"aoRowCreatedCallback",null,[f.nTr,f._aData,b])}}function I(a,b){return b._DT_RowIndex!==n?b._DT_RowIndex:null}function fa(a,b,c){for(var b=J(a,b),d=0,a=
        a.aoColumns.length;d<a;d++)if(b[d]===c)return d;return-1}function Y(a,b,c,d){for(var i=[],f=0,g=d.length;f<g;f++)i.push(v(a,b,d[f],c));return i}function v(a,b,c,d){var i=a.aoColumns[c];if((c=i.fnGetData(a.aoData[b]._aData,d))===n)return a.iDrawError!=a.iDraw&&null===i.sDefaultContent&&(D(a,0,"Requested unknown parameter "+("function"==typeof i.mData?"{mData function}":"'"+i.mData+"'")+" from the data source for row "+b),a.iDrawError=a.iDraw),i.sDefaultContent;if(null===c&&null!==i.sDefaultContent)c=
        i.sDefaultContent;else if("function"===typeof c)return c();return"display"==d&&null===c?"":c}function F(a,b,c,d){a.aoColumns[c].fnSetData(a.aoData[b]._aData,d)}function Q(a){if(null===a)return function(){return null};if("function"===typeof a)return function(b,d,i){return a(b,d,i)};if("string"===typeof a&&(-1!==a.indexOf(".")||-1!==a.indexOf("["))){var b=function(a,d,i){var f=i.split("."),g;if(""!==i){var e=0;for(g=f.length;e<g;e++){if(i=f[e].match(U)){f[e]=f[e].replace(U,"");""!==f[e]&&(a=a[f[e]]);
        g=[];f.splice(0,e+1);for(var f=f.join("."),e=0,h=a.length;e<h;e++)g.push(b(a[e],d,f));a=i[0].substring(1,i[0].length-1);a=""===a?g:g.join(a);break}if(null===a||a[f[e]]===n)return n;a=a[f[e]]}}return a};return function(c,d){return b(c,d,a)}}return function(b){return b[a]}}function L(a){if(null===a)return function(){};if("function"===typeof a)return function(b,d){a(b,"set",d)};if("string"===typeof a&&(-1!==a.indexOf(".")||-1!==a.indexOf("["))){var b=function(a,d,i){var i=i.split("."),f,g,e=0;for(g=
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               i.length-1;e<g;e++){if(f=i[e].match(U)){i[e]=i[e].replace(U,"");a[i[e]]=[];f=i.slice();f.splice(0,e+1);g=f.join(".");for(var h=0,j=d.length;h<j;h++)f={},b(f,d[h],g),a[i[e]].push(f);return}if(null===a[i[e]]||a[i[e]]===n)a[i[e]]={};a=a[i[e]]}a[i[i.length-1].replace(U,"")]=d};return function(c,d){return b(c,d,a)}}return function(b,d){b[a]=d}}function Z(a){for(var b=[],c=a.aoData.length,d=0;d<c;d++)b.push(a.aoData[d]._aData);return b}function ga(a){a.aoData.splice(0,a.aoData.length);a.aiDisplayMaster.splice(0,
        a.aiDisplayMaster.length);a.aiDisplay.splice(0,a.aiDisplay.length);y(a)}function ha(a,b){for(var c=-1,d=0,i=a.length;d<i;d++)a[d]==b?c=d:a[d]>b&&a[d]--; -1!=c&&a.splice(c,1)}function S(a,b,c){var d=a.aoColumns[c];return d.fnRender({iDataRow:b,iDataColumn:c,oSettings:a,aData:a.aoData[b]._aData,mDataProp:d.mData},v(a,b,c,"display"))}function ea(a,b){var c=a.aoData[b],d;if(null===c.nTr){c.nTr=l.createElement("tr");c.nTr._DT_RowIndex=b;c._aData.DT_RowId&&(c.nTr.id=c._aData.DT_RowId);c._aData.DT_RowClass&&
    (c.nTr.className=c._aData.DT_RowClass);for(var i=0,f=a.aoColumns.length;i<f;i++){var g=a.aoColumns[i];d=l.createElement(g.sCellType);d.innerHTML="function"===typeof g.fnRender&&(!g.bUseRendered||null===g.mData)?S(a,b,i):v(a,b,i,"display");null!==g.sClass&&(d.className=g.sClass);g.bVisible?(c.nTr.appendChild(d),c._anHidden[i]=null):c._anHidden[i]=d;g.fnCreatedCell&&g.fnCreatedCell.call(a.oInstance,d,v(a,b,i,"display"),c._aData,b,i)}A(a,"aoRowCreatedCallback",null,[c.nTr,c._aData,b])}}function va(a){var b,
        c,d;if(0!==h("th, td",a.nTHead).length){b=0;for(d=a.aoColumns.length;b<d;b++)if(c=a.aoColumns[b].nTh,c.setAttribute("role","columnheader"),a.aoColumns[b].bSortable&&(c.setAttribute("tabindex",a.iTabIndex),c.setAttribute("aria-controls",a.sTableId)),null!==a.aoColumns[b].sClass&&h(c).addClass(a.aoColumns[b].sClass),a.aoColumns[b].sTitle!=c.innerHTML)c.innerHTML=a.aoColumns[b].sTitle}else{var i=l.createElement("tr");b=0;for(d=a.aoColumns.length;b<d;b++)c=a.aoColumns[b].nTh,c.innerHTML=a.aoColumns[b].sTitle,
        c.setAttribute("tabindex","0"),null!==a.aoColumns[b].sClass&&h(c).addClass(a.aoColumns[b].sClass),i.appendChild(c);h(a.nTHead).html("")[0].appendChild(i);V(a.aoHeader,a.nTHead)}h(a.nTHead).children("tr").attr("role","row");if(a.bJUI){b=0;for(d=a.aoColumns.length;b<d;b++){c=a.aoColumns[b].nTh;i=l.createElement("div");i.className=a.oClasses.sSortJUIWrapper;h(c).contents().appendTo(i);var f=l.createElement("span");f.className=a.oClasses.sSortIcon;i.appendChild(f);c.appendChild(i)}}if(a.oFeatures.bSort)for(b=
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        0;b<a.aoColumns.length;b++)!1!==a.aoColumns[b].bSortable?ia(a,a.aoColumns[b].nTh,b):h(a.aoColumns[b].nTh).addClass(a.oClasses.sSortableNone);""!==a.oClasses.sFooterTH&&h(a.nTFoot).children("tr").children("th").addClass(a.oClasses.sFooterTH);if(null!==a.nTFoot){c=N(a,null,a.aoFooter);b=0;for(d=a.aoColumns.length;b<d;b++)c[b]&&(a.aoColumns[b].nTf=c[b],a.aoColumns[b].sClass&&h(c[b]).addClass(a.aoColumns[b].sClass))}}function W(a,b,c){var d,i,f,g=[],e=[],h=a.aoColumns.length,j;c===n&&(c=!1);d=0;for(i=
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                b.length;d<i;d++){g[d]=b[d].slice();g[d].nTr=b[d].nTr;for(f=h-1;0<=f;f--)!a.aoColumns[f].bVisible&&!c&&g[d].splice(f,1);e.push([])}d=0;for(i=g.length;d<i;d++){if(a=g[d].nTr)for(;f=a.firstChild;)a.removeChild(f);f=0;for(b=g[d].length;f<b;f++)if(j=h=1,e[d][f]===n){a.appendChild(g[d][f].cell);for(e[d][f]=1;g[d+h]!==n&&g[d][f].cell==g[d+h][f].cell;)e[d+h][f]=1,h++;for(;g[d][f+j]!==n&&g[d][f].cell==g[d][f+j].cell;){for(c=0;c<h;c++)e[d+c][f+j]=1;j++}g[d][f].cell.rowSpan=h;g[d][f].cell.colSpan=j}}}function x(a){var b=
        A(a,"aoPreDrawCallback","preDraw",[a]);if(-1!==h.inArray(!1,b))E(a,!1);else{var c,d,b=[],i=0,f=a.asStripeClasses.length;c=a.aoOpenRows.length;a.bDrawing=!0;a.iInitDisplayStart!==n&&-1!=a.iInitDisplayStart&&(a._iDisplayStart=a.oFeatures.bServerSide?a.iInitDisplayStart:a.iInitDisplayStart>=a.fnRecordsDisplay()?0:a.iInitDisplayStart,a.iInitDisplayStart=-1,y(a));if(a.bDeferLoading)a.bDeferLoading=!1,a.iDraw++;else if(a.oFeatures.bServerSide){if(!a.bDestroying&&!wa(a))return}else a.iDraw++;if(0!==a.aiDisplay.length){var g=
        a._iDisplayStart;d=a._iDisplayEnd;a.oFeatures.bServerSide&&(g=0,d=a.aoData.length);for(;g<d;g++){var e=a.aoData[a.aiDisplay[g]];null===e.nTr&&ea(a,a.aiDisplay[g]);var j=e.nTr;if(0!==f){var o=a.asStripeClasses[i%f];e._sRowStripe!=o&&(h(j).removeClass(e._sRowStripe).addClass(o),e._sRowStripe=o)}A(a,"aoRowCallback",null,[j,a.aoData[a.aiDisplay[g]]._aData,i,g]);b.push(j);i++;if(0!==c)for(e=0;e<c;e++)if(j==a.aoOpenRows[e].nParent){b.push(a.aoOpenRows[e].nTr);break}}}else b[0]=l.createElement("tr"),a.asStripeClasses[0]&&
        (b[0].className=a.asStripeClasses[0]),c=a.oLanguage,f=c.sZeroRecords,1==a.iDraw&&null!==a.sAjaxSource&&!a.oFeatures.bServerSide?f=c.sLoadingRecords:c.sEmptyTable&&0===a.fnRecordsTotal()&&(f=c.sEmptyTable),c=l.createElement("td"),c.setAttribute("valign","top"),c.colSpan=t(a),c.className=a.oClasses.sRowEmpty,c.innerHTML=ja(a,f),b[i].appendChild(c);A(a,"aoHeaderCallback","header",[h(a.nTHead).children("tr")[0],Z(a),a._iDisplayStart,a.fnDisplayEnd(),a.aiDisplay]);A(a,"aoFooterCallback","footer",[h(a.nTFoot).children("tr")[0],
        Z(a),a._iDisplayStart,a.fnDisplayEnd(),a.aiDisplay]);i=l.createDocumentFragment();c=l.createDocumentFragment();if(a.nTBody){f=a.nTBody.parentNode;c.appendChild(a.nTBody);if(!a.oScroll.bInfinite||!a._bInitComplete||a.bSorted||a.bFiltered)for(;c=a.nTBody.firstChild;)a.nTBody.removeChild(c);c=0;for(d=b.length;c<d;c++)i.appendChild(b[c]);a.nTBody.appendChild(i);null!==f&&f.appendChild(a.nTBody)}A(a,"aoDrawCallback","draw",[a]);a.bSorted=!1;a.bFiltered=!1;a.bDrawing=!1;a.oFeatures.bServerSide&&(E(a,!1),
        a._bInitComplete||$(a))}}function aa(a){a.oFeatures.bSort?O(a,a.oPreviousSearch):a.oFeatures.bFilter?K(a,a.oPreviousSearch):(y(a),x(a))}function xa(a){var b=h("<div></div>")[0];a.nTable.parentNode.insertBefore(b,a.nTable);a.nTableWrapper=h('<div id="'+a.sTableId+'_wrapper" class="'+a.oClasses.sWrapper+'" role="grid"></div>')[0];a.nTableReinsertBefore=a.nTable.nextSibling;for(var c=a.nTableWrapper,d=a.sDom.split(""),i,f,g,e,w,o,k,m=0;m<d.length;m++){f=0;g=d[m];if("<"==g){e=h("<div></div>")[0];w=d[m+
        1];if("'"==w||'"'==w){o="";for(k=2;d[m+k]!=w;)o+=d[m+k],k++;"H"==o?o=a.oClasses.sJUIHeader:"F"==o&&(o=a.oClasses.sJUIFooter);-1!=o.indexOf(".")?(w=o.split("."),e.id=w[0].substr(1,w[0].length-1),e.className=w[1]):"#"==o.charAt(0)?e.id=o.substr(1,o.length-1):e.className=o;m+=k}c.appendChild(e);c=e}else if(">"==g)c=c.parentNode;else if("l"==g&&a.oFeatures.bPaginate&&a.oFeatures.bLengthChange)i=ya(a),f=1;else if("f"==g&&a.oFeatures.bFilter)i=za(a),f=1;else if("r"==g&&a.oFeatures.bProcessing)i=Aa(a),f=
        1;else if("t"==g)i=Ba(a),f=1;else if("i"==g&&a.oFeatures.bInfo)i=Ca(a),f=1;else if("p"==g&&a.oFeatures.bPaginate)i=Da(a),f=1;else if(0!==j.ext.aoFeatures.length){e=j.ext.aoFeatures;k=0;for(w=e.length;k<w;k++)if(g==e[k].cFeature){(i=e[k].fnInit(a))&&(f=1);break}}1==f&&null!==i&&("object"!==typeof a.aanFeatures[g]&&(a.aanFeatures[g]=[]),a.aanFeatures[g].push(i),c.appendChild(i))}b.parentNode.replaceChild(a.nTableWrapper,b)}function V(a,b){var c=h(b).children("tr"),d,i,f,g,e,j,o,k,m,p;a.splice(0,a.length);
        f=0;for(j=c.length;f<j;f++)a.push([]);f=0;for(j=c.length;f<j;f++){d=c[f];for(i=d.firstChild;i;){if("TD"==i.nodeName.toUpperCase()||"TH"==i.nodeName.toUpperCase()){k=1*i.getAttribute("colspan");m=1*i.getAttribute("rowspan");k=!k||0===k||1===k?1:k;m=!m||0===m||1===m?1:m;g=0;for(e=a[f];e[g];)g++;o=g;p=1===k?!0:!1;for(e=0;e<k;e++)for(g=0;g<m;g++)a[f+g][o+e]={cell:i,unique:p},a[f+g].nTr=d}i=i.nextSibling}}}function N(a,b,c){var d=[];c||(c=a.aoHeader,b&&(c=[],V(c,b)));for(var b=0,i=c.length;b<i;b++)for(var f=
        0,g=c[b].length;f<g;f++)if(c[b][f].unique&&(!d[f]||!a.bSortCellsTop))d[f]=c[b][f].cell;return d}function wa(a){if(a.bAjaxDataGet){a.iDraw++;E(a,!0);var b=Ea(a);ka(a,b);a.fnServerData.call(a.oInstance,a.sAjaxSource,b,function(b){Fa(a,b)},a);return!1}return!0}function Ea(a){var b=a.aoColumns.length,c=[],d,i,f,g;c.push({name:"sEcho",value:a.iDraw});c.push({name:"iColumns",value:b});c.push({name:"sColumns",value:M(a)});c.push({name:"iDisplayStart",value:a._iDisplayStart});c.push({name:"iDisplayLength",
        value:!1!==a.oFeatures.bPaginate?a._iDisplayLength:-1});for(f=0;f<b;f++)d=a.aoColumns[f].mData,c.push({name:"mDataProp_"+f,value:"function"===typeof d?"function":d});if(!1!==a.oFeatures.bFilter){c.push({name:"sSearch",value:a.oPreviousSearch.sSearch});c.push({name:"bRegex",value:a.oPreviousSearch.bRegex});for(f=0;f<b;f++)c.push({name:"sSearch_"+f,value:a.aoPreSearchCols[f].sSearch}),c.push({name:"bRegex_"+f,value:a.aoPreSearchCols[f].bRegex}),c.push({name:"bSearchable_"+f,value:a.aoColumns[f].bSearchable})}if(!1!==
        a.oFeatures.bSort){var e=0;d=null!==a.aaSortingFixed?a.aaSortingFixed.concat(a.aaSorting):a.aaSorting.slice();for(f=0;f<d.length;f++){i=a.aoColumns[d[f][0]].aDataSort;for(g=0;g<i.length;g++)c.push({name:"iSortCol_"+e,value:i[g]}),c.push({name:"sSortDir_"+e,value:d[f][1]}),e++}c.push({name:"iSortingCols",value:e});for(f=0;f<b;f++)c.push({name:"bSortable_"+f,value:a.aoColumns[f].bSortable})}return c}function ka(a,b){A(a,"aoServerParams","serverParams",[b])}function Fa(a,b){if(b.sEcho!==n){if(1*b.sEcho<
        a.iDraw)return;a.iDraw=1*b.sEcho}(!a.oScroll.bInfinite||a.oScroll.bInfinite&&(a.bSorted||a.bFiltered))&&ga(a);a._iRecordsTotal=parseInt(b.iTotalRecords,10);a._iRecordsDisplay=parseInt(b.iTotalDisplayRecords,10);var c=M(a),c=b.sColumns!==n&&""!==c&&b.sColumns!=c,d;c&&(d=u(a,b.sColumns));for(var i=Q(a.sAjaxDataProp)(b),f=0,g=i.length;f<g;f++)if(c){for(var e=[],h=0,j=a.aoColumns.length;h<j;h++)e.push(i[f][d[h]]);H(a,e)}else H(a,i[f]);a.aiDisplay=a.aiDisplayMaster.slice();a.bAjaxDataGet=!1;x(a);a.bAjaxDataGet=
        !0;E(a,!1)}function za(a){var b=a.oPreviousSearch,c=a.oLanguage.sSearch,c=-1!==c.indexOf("_INPUT_")?c.replace("_INPUT_",'<input type="text" />'):""===c?'<input type="text" />':c+' <input type="text" />',d=l.createElement("div");d.className=a.oClasses.sFilter;d.innerHTML="<label>"+c+"</label>";a.aanFeatures.f||(d.id=a.sTableId+"_filter");c=h('input[type="text"]',d);d._DT_Input=c[0];c.val(b.sSearch.replace('"',"&quot;"));c.bind("keyup.DT",function(){for(var c=a.aanFeatures.f,d=this.value===""?"":this.value,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    g=0,e=c.length;g<e;g++)c[g]!=h(this).parents("div.dataTables_filter")[0]&&h(c[g]._DT_Input).val(d);d!=b.sSearch&&K(a,{sSearch:d,bRegex:b.bRegex,bSmart:b.bSmart,bCaseInsensitive:b.bCaseInsensitive})});c.attr("aria-controls",a.sTableId).bind("keypress.DT",function(a){if(a.keyCode==13)return false});return d}function K(a,b,c){var d=a.oPreviousSearch,i=a.aoPreSearchCols,f=function(a){d.sSearch=a.sSearch;d.bRegex=a.bRegex;d.bSmart=a.bSmart;d.bCaseInsensitive=a.bCaseInsensitive};if(a.oFeatures.bServerSide)f(b);
    else{Ga(a,b.sSearch,c,b.bRegex,b.bSmart,b.bCaseInsensitive);f(b);for(b=0;b<a.aoPreSearchCols.length;b++)Ha(a,i[b].sSearch,b,i[b].bRegex,i[b].bSmart,i[b].bCaseInsensitive);Ia(a)}a.bFiltered=!0;h(a.oInstance).trigger("filter",a);a._iDisplayStart=0;y(a);x(a);la(a,0)}function Ia(a){for(var b=j.ext.afnFiltering,c=r(a,"bSearchable"),d=0,i=b.length;d<i;d++)for(var f=0,g=0,e=a.aiDisplay.length;g<e;g++){var h=a.aiDisplay[g-f];b[d](a,Y(a,h,"filter",c),h)||(a.aiDisplay.splice(g-f,1),f++)}}function Ha(a,b,c,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   d,i,f){if(""!==b)for(var g=0,b=ma(b,d,i,f),d=a.aiDisplay.length-1;0<=d;d--)i=Ja(v(a,a.aiDisplay[d],c,"filter"),a.aoColumns[c].sType),b.test(i)||(a.aiDisplay.splice(d,1),g++)}function Ga(a,b,c,d,i,f){d=ma(b,d,i,f);i=a.oPreviousSearch;c||(c=0);0!==j.ext.afnFiltering.length&&(c=1);if(0>=b.length)a.aiDisplay.splice(0,a.aiDisplay.length),a.aiDisplay=a.aiDisplayMaster.slice();else if(a.aiDisplay.length==a.aiDisplayMaster.length||i.sSearch.length>b.length||1==c||0!==b.indexOf(i.sSearch)){a.aiDisplay.splice(0,
        a.aiDisplay.length);la(a,1);for(b=0;b<a.aiDisplayMaster.length;b++)d.test(a.asDataSearch[b])&&a.aiDisplay.push(a.aiDisplayMaster[b])}else for(b=c=0;b<a.asDataSearch.length;b++)d.test(a.asDataSearch[b])||(a.aiDisplay.splice(b-c,1),c++)}function la(a,b){if(!a.oFeatures.bServerSide){a.asDataSearch=[];for(var c=r(a,"bSearchable"),d=1===b?a.aiDisplayMaster:a.aiDisplay,i=0,f=d.length;i<f;i++)a.asDataSearch[i]=na(a,Y(a,d[i],"filter",c))}}function na(a,b){var c=b.join("  ");-1!==c.indexOf("&")&&(c=h("<div>").html(c).text());
        return c.replace(/[\n\r]/g," ")}function ma(a,b,c,d){if(c)return a=b?a.split(" "):oa(a).split(" "),a="^(?=.*?"+a.join(")(?=.*?")+").*$",RegExp(a,d?"i":"");a=b?a:oa(a);return RegExp(a,d?"i":"")}function Ja(a,b){return"function"===typeof j.ext.ofnSearch[b]?j.ext.ofnSearch[b](a):null===a?"":"html"==b?a.replace(/[\r\n]/g," ").replace(/<.*?>/g,""):"string"===typeof a?a.replace(/[\r\n]/g," "):a}function oa(a){return a.replace(RegExp("(\\/|\\.|\\*|\\+|\\?|\\||\\(|\\)|\\[|\\]|\\{|\\}|\\\\|\\$|\\^|\\-)","g"),
        "\\$1")}function Ca(a){var b=l.createElement("div");b.className=a.oClasses.sInfo;a.aanFeatures.i||(a.aoDrawCallback.push({fn:Ka,sName:"information"}),b.id=a.sTableId+"_info");a.nTable.setAttribute("aria-describedby",a.sTableId+"_info");return b}function Ka(a){if(a.oFeatures.bInfo&&0!==a.aanFeatures.i.length){var b=a.oLanguage,c=a._iDisplayStart+1,d=a.fnDisplayEnd(),i=a.fnRecordsTotal(),f=a.fnRecordsDisplay(),g;g=0===f?b.sInfoEmpty:b.sInfo;f!=i&&(g+=" "+b.sInfoFiltered);g+=b.sInfoPostFix;g=ja(a,g);
        null!==b.fnInfoCallback&&(g=b.fnInfoCallback.call(a.oInstance,a,c,d,i,f,g));a=a.aanFeatures.i;b=0;for(c=a.length;b<c;b++)h(a[b]).html(g)}}function ja(a,b){var c=a.fnFormatNumber(a._iDisplayStart+1),d=a.fnDisplayEnd(),d=a.fnFormatNumber(d),i=a.fnRecordsDisplay(),i=a.fnFormatNumber(i),f=a.fnRecordsTotal(),f=a.fnFormatNumber(f);a.oScroll.bInfinite&&(c=a.fnFormatNumber(1));return b.replace(/_START_/g,c).replace(/_END_/g,d).replace(/_TOTAL_/g,i).replace(/_MAX_/g,f)}function ba(a){var b,c,d=a.iInitDisplayStart;
        if(!1===a.bInitialised)setTimeout(function(){ba(a)},200);else{xa(a);va(a);W(a,a.aoHeader);a.nTFoot&&W(a,a.aoFooter);E(a,!0);a.oFeatures.bAutoWidth&&da(a);b=0;for(c=a.aoColumns.length;b<c;b++)null!==a.aoColumns[b].sWidth&&(a.aoColumns[b].nTh.style.width=q(a.aoColumns[b].sWidth));a.oFeatures.bSort?O(a):a.oFeatures.bFilter?K(a,a.oPreviousSearch):(a.aiDisplay=a.aiDisplayMaster.slice(),y(a),x(a));null!==a.sAjaxSource&&!a.oFeatures.bServerSide?(c=[],ka(a,c),a.fnServerData.call(a.oInstance,a.sAjaxSource,
            c,function(c){var f=a.sAjaxDataProp!==""?Q(a.sAjaxDataProp)(c):c;for(b=0;b<f.length;b++)H(a,f[b]);a.iInitDisplayStart=d;if(a.oFeatures.bSort)O(a);else{a.aiDisplay=a.aiDisplayMaster.slice();y(a);x(a)}E(a,false);$(a,c)},a)):a.oFeatures.bServerSide||(E(a,!1),$(a))}}function $(a,b){a._bInitComplete=!0;A(a,"aoInitComplete","init",[a,b])}function pa(a){var b=j.defaults.oLanguage;!a.sEmptyTable&&(a.sZeroRecords&&"No data available in table"===b.sEmptyTable)&&p(a,a,"sZeroRecords","sEmptyTable");!a.sLoadingRecords&&
        (a.sZeroRecords&&"Loading..."===b.sLoadingRecords)&&p(a,a,"sZeroRecords","sLoadingRecords")}function ya(a){if(a.oScroll.bInfinite)return null;var b='<select size="1" '+('name="'+a.sTableId+'_length"')+">",c,d,i=a.aLengthMenu;if(2==i.length&&"object"===typeof i[0]&&"object"===typeof i[1]){c=0;for(d=i[0].length;c<d;c++)b+='<option value="'+i[0][c]+'">'+i[1][c]+"</option>"}else{c=0;for(d=i.length;c<d;c++)b+='<option value="'+i[c]+'">'+i[c]+"</option>"}b+="</select>";i=l.createElement("div");a.aanFeatures.l||
    (i.id=a.sTableId+"_length");i.className=a.oClasses.sLength;i.innerHTML="<label>"+a.oLanguage.sLengthMenu.replace("_MENU_",b)+"</label>";h('select option[value="'+a._iDisplayLength+'"]',i).attr("selected",!0);h("select",i).bind("change.DT",function(){var b=h(this).val(),i=a.aanFeatures.l;c=0;for(d=i.length;c<d;c++)i[c]!=this.parentNode&&h("select",i[c]).val(b);a._iDisplayLength=parseInt(b,10);y(a);if(a.fnDisplayEnd()==a.fnRecordsDisplay()){a._iDisplayStart=a.fnDisplayEnd()-a._iDisplayLength;if(a._iDisplayStart<
        0)a._iDisplayStart=0}if(a._iDisplayLength==-1)a._iDisplayStart=0;x(a)});h("select",i).attr("aria-controls",a.sTableId);return i}function y(a){a._iDisplayEnd=!1===a.oFeatures.bPaginate?a.aiDisplay.length:a._iDisplayStart+a._iDisplayLength>a.aiDisplay.length||-1==a._iDisplayLength?a.aiDisplay.length:a._iDisplayStart+a._iDisplayLength}function Da(a){if(a.oScroll.bInfinite)return null;var b=l.createElement("div");b.className=a.oClasses.sPaging+a.sPaginationType;j.ext.oPagination[a.sPaginationType].fnInit(a,
        b,function(a){y(a);x(a)});a.aanFeatures.p||a.aoDrawCallback.push({fn:function(a){j.ext.oPagination[a.sPaginationType].fnUpdate(a,function(a){y(a);x(a)})},sName:"pagination"});return b}function qa(a,b){var c=a._iDisplayStart;if("number"===typeof b)a._iDisplayStart=b*a._iDisplayLength,a._iDisplayStart>a.fnRecordsDisplay()&&(a._iDisplayStart=0);else if("first"==b)a._iDisplayStart=0;else if("previous"==b)a._iDisplayStart=0<=a._iDisplayLength?a._iDisplayStart-a._iDisplayLength:0,0>a._iDisplayStart&&(a._iDisplayStart=
        0);else if("next"==b)0<=a._iDisplayLength?a._iDisplayStart+a._iDisplayLength<a.fnRecordsDisplay()&&(a._iDisplayStart+=a._iDisplayLength):a._iDisplayStart=0;else if("last"==b)if(0<=a._iDisplayLength){var d=parseInt((a.fnRecordsDisplay()-1)/a._iDisplayLength,10)+1;a._iDisplayStart=(d-1)*a._iDisplayLength}else a._iDisplayStart=0;else D(a,0,"Unknown paging action: "+b);h(a.oInstance).trigger("page",a);return c!=a._iDisplayStart}function Aa(a){var b=l.createElement("div");a.aanFeatures.r||(b.id=a.sTableId+
        "_processing");b.innerHTML=a.oLanguage.sProcessing;b.className=a.oClasses.sProcessing;a.nTable.parentNode.insertBefore(b,a.nTable);return b}function E(a,b){if(a.oFeatures.bProcessing)for(var c=a.aanFeatures.r,d=0,i=c.length;d<i;d++)c[d].style.visibility=b?"visible":"hidden";h(a.oInstance).trigger("processing",[a,b])}function Ba(a){if(""===a.oScroll.sX&&""===a.oScroll.sY)return a.nTable;var b=l.createElement("div"),c=l.createElement("div"),d=l.createElement("div"),i=l.createElement("div"),f=l.createElement("div"),
        g=l.createElement("div"),e=a.nTable.cloneNode(!1),j=a.nTable.cloneNode(!1),o=a.nTable.getElementsByTagName("thead")[0],k=0===a.nTable.getElementsByTagName("tfoot").length?null:a.nTable.getElementsByTagName("tfoot")[0],m=a.oClasses;c.appendChild(d);f.appendChild(g);i.appendChild(a.nTable);b.appendChild(c);b.appendChild(i);d.appendChild(e);e.appendChild(o);null!==k&&(b.appendChild(f),g.appendChild(j),j.appendChild(k));b.className=m.sScrollWrapper;c.className=m.sScrollHead;d.className=m.sScrollHeadInner;
        i.className=m.sScrollBody;f.className=m.sScrollFoot;g.className=m.sScrollFootInner;a.oScroll.bAutoCss&&(c.style.overflow="hidden",c.style.position="relative",f.style.overflow="hidden",i.style.overflow="auto");c.style.border="0";c.style.width="100%";f.style.border="0";d.style.width=""!==a.oScroll.sXInner?a.oScroll.sXInner:"100%";e.removeAttribute("id");e.style.marginLeft="0";a.nTable.style.marginLeft="0";null!==k&&(j.removeAttribute("id"),j.style.marginLeft="0");d=h(a.nTable).children("caption");0<
            d.length&&(d=d[0],"top"===d._captionSide?e.appendChild(d):"bottom"===d._captionSide&&k&&j.appendChild(d));""!==a.oScroll.sX&&(c.style.width=q(a.oScroll.sX),i.style.width=q(a.oScroll.sX),null!==k&&(f.style.width=q(a.oScroll.sX)),h(i).scroll(function(){c.scrollLeft=this.scrollLeft;if(k!==null)f.scrollLeft=this.scrollLeft}));""!==a.oScroll.sY&&(i.style.height=q(a.oScroll.sY));a.aoDrawCallback.push({fn:La,sName:"scrolling"});a.oScroll.bInfinite&&h(i).scroll(function(){if(!a.bDrawing&&h(this).scrollTop()!==
            0&&h(this).scrollTop()+h(this).height()>h(a.nTable).height()-a.oScroll.iLoadGap&&a.fnDisplayEnd()<a.fnRecordsDisplay()){qa(a,"next");y(a);x(a)}});a.nScrollHead=c;a.nScrollFoot=f;return b}function La(a){var b=a.nScrollHead.getElementsByTagName("div")[0],c=b.getElementsByTagName("table")[0],d=a.nTable.parentNode,i,f,g,e,j,o,k,m,p=[],n=[],l=null!==a.nTFoot?a.nScrollFoot.getElementsByTagName("div")[0]:null,R=null!==a.nTFoot?l.getElementsByTagName("table")[0]:null,r=a.oBrowser.bScrollOversize,s=function(a){k=
        a.style;k.paddingTop="0";k.paddingBottom="0";k.borderTopWidth="0";k.borderBottomWidth="0";k.height=0};h(a.nTable).children("thead, tfoot").remove();i=h(a.nTHead).clone()[0];a.nTable.insertBefore(i,a.nTable.childNodes[0]);g=a.nTHead.getElementsByTagName("tr");e=i.getElementsByTagName("tr");null!==a.nTFoot&&(j=h(a.nTFoot).clone()[0],a.nTable.insertBefore(j,a.nTable.childNodes[1]),o=a.nTFoot.getElementsByTagName("tr"),j=j.getElementsByTagName("tr"));""===a.oScroll.sX&&(d.style.width="100%",b.parentNode.style.width=
        "100%");var t=N(a,i);i=0;for(f=t.length;i<f;i++)m=G(a,i),t[i].style.width=a.aoColumns[m].sWidth;null!==a.nTFoot&&C(function(a){a.style.width=""},j);a.oScroll.bCollapse&&""!==a.oScroll.sY&&(d.style.height=d.offsetHeight+a.nTHead.offsetHeight+"px");i=h(a.nTable).outerWidth();if(""===a.oScroll.sX){if(a.nTable.style.width="100%",r&&(h("tbody",d).height()>d.offsetHeight||"scroll"==h(d).css("overflow-y")))a.nTable.style.width=q(h(a.nTable).outerWidth()-a.oScroll.iBarWidth)}else""!==a.oScroll.sXInner?a.nTable.style.width=
        q(a.oScroll.sXInner):i==h(d).width()&&h(d).height()<h(a.nTable).height()?(a.nTable.style.width=q(i-a.oScroll.iBarWidth),h(a.nTable).outerWidth()>i-a.oScroll.iBarWidth&&(a.nTable.style.width=q(i))):a.nTable.style.width=q(i);i=h(a.nTable).outerWidth();C(s,e);C(function(a){p.push(q(h(a).width()))},e);C(function(a,b){a.style.width=p[b]},g);h(e).height(0);null!==a.nTFoot&&(C(s,j),C(function(a){n.push(q(h(a).width()))},j),C(function(a,b){a.style.width=n[b]},o),h(j).height(0));C(function(a,b){a.innerHTML=
        "";a.style.width=p[b]},e);null!==a.nTFoot&&C(function(a,b){a.innerHTML="";a.style.width=n[b]},j);if(h(a.nTable).outerWidth()<i){g=d.scrollHeight>d.offsetHeight||"scroll"==h(d).css("overflow-y")?i+a.oScroll.iBarWidth:i;if(r&&(d.scrollHeight>d.offsetHeight||"scroll"==h(d).css("overflow-y")))a.nTable.style.width=q(g-a.oScroll.iBarWidth);d.style.width=q(g);a.nScrollHead.style.width=q(g);null!==a.nTFoot&&(a.nScrollFoot.style.width=q(g));""===a.oScroll.sX?D(a,1,"The table cannot fit into the current element which will cause column misalignment. The table has been drawn at its minimum possible width."):
        ""!==a.oScroll.sXInner&&D(a,1,"The table cannot fit into the current element which will cause column misalignment. Increase the sScrollXInner value or remove it to allow automatic calculation")}else d.style.width=q("100%"),a.nScrollHead.style.width=q("100%"),null!==a.nTFoot&&(a.nScrollFoot.style.width=q("100%"));""===a.oScroll.sY&&r&&(d.style.height=q(a.nTable.offsetHeight+a.oScroll.iBarWidth));""!==a.oScroll.sY&&a.oScroll.bCollapse&&(d.style.height=q(a.oScroll.sY),r=""!==a.oScroll.sX&&a.nTable.offsetWidth>
        d.offsetWidth?a.oScroll.iBarWidth:0,a.nTable.offsetHeight<d.offsetHeight&&(d.style.height=q(a.nTable.offsetHeight+r)));r=h(a.nTable).outerWidth();c.style.width=q(r);b.style.width=q(r);c=h(a.nTable).height()>d.clientHeight||"scroll"==h(d).css("overflow-y");b.style.paddingRight=c?a.oScroll.iBarWidth+"px":"0px";null!==a.nTFoot&&(R.style.width=q(r),l.style.width=q(r),l.style.paddingRight=c?a.oScroll.iBarWidth+"px":"0px");h(d).scroll();if(a.bSorted||a.bFiltered)d.scrollTop=0}function C(a,b,c){for(var d=
        0,i=0,f=b.length,g,e;i<f;){g=b[i].firstChild;for(e=c?c[i].firstChild:null;g;)1===g.nodeType&&(c?a(g,e,d):a(g,d),d++),g=g.nextSibling,e=c?e.nextSibling:null;i++}}function Ma(a,b){if(!a||null===a||""===a)return 0;b||(b=l.body);var c,d=l.createElement("div");d.style.width=q(a);b.appendChild(d);c=d.offsetWidth;b.removeChild(d);return c}function da(a){var b=0,c,d=0,i=a.aoColumns.length,f,e,j=h("th",a.nTHead),o=a.nTable.getAttribute("width");e=a.nTable.parentNode;for(f=0;f<i;f++)a.aoColumns[f].bVisible&&
    (d++,null!==a.aoColumns[f].sWidth&&(c=Ma(a.aoColumns[f].sWidthOrig,e),null!==c&&(a.aoColumns[f].sWidth=q(c)),b++));if(i==j.length&&0===b&&d==i&&""===a.oScroll.sX&&""===a.oScroll.sY)for(f=0;f<a.aoColumns.length;f++)c=h(j[f]).width(),null!==c&&(a.aoColumns[f].sWidth=q(c));else{b=a.nTable.cloneNode(!1);f=a.nTHead.cloneNode(!0);d=l.createElement("tbody");c=l.createElement("tr");b.removeAttribute("id");b.appendChild(f);null!==a.nTFoot&&(b.appendChild(a.nTFoot.cloneNode(!0)),C(function(a){a.style.width=
        ""},b.getElementsByTagName("tr")));b.appendChild(d);d.appendChild(c);d=h("thead th",b);0===d.length&&(d=h("tbody tr:eq(0)>td",b));j=N(a,f);for(f=d=0;f<i;f++){var k=a.aoColumns[f];k.bVisible&&null!==k.sWidthOrig&&""!==k.sWidthOrig?j[f-d].style.width=q(k.sWidthOrig):k.bVisible?j[f-d].style.width="":d++}for(f=0;f<i;f++)a.aoColumns[f].bVisible&&(d=Na(a,f),null!==d&&(d=d.cloneNode(!0),""!==a.aoColumns[f].sContentPadding&&(d.innerHTML+=a.aoColumns[f].sContentPadding),c.appendChild(d)));e.appendChild(b);
        ""!==a.oScroll.sX&&""!==a.oScroll.sXInner?b.style.width=q(a.oScroll.sXInner):""!==a.oScroll.sX?(b.style.width="",h(b).width()<e.offsetWidth&&(b.style.width=q(e.offsetWidth))):""!==a.oScroll.sY?b.style.width=q(e.offsetWidth):o&&(b.style.width=q(o));b.style.visibility="hidden";Oa(a,b);i=h("tbody tr:eq(0)",b).children();0===i.length&&(i=N(a,h("thead",b)[0]));if(""!==a.oScroll.sX){for(f=d=e=0;f<a.aoColumns.length;f++)a.aoColumns[f].bVisible&&(e=null===a.aoColumns[f].sWidthOrig?e+h(i[d]).outerWidth():
            e+(parseInt(a.aoColumns[f].sWidth.replace("px",""),10)+(h(i[d]).outerWidth()-h(i[d]).width())),d++);b.style.width=q(e);a.nTable.style.width=q(e)}for(f=d=0;f<a.aoColumns.length;f++)a.aoColumns[f].bVisible&&(e=h(i[d]).width(),null!==e&&0<e&&(a.aoColumns[f].sWidth=q(e)),d++);i=h(b).css("width");a.nTable.style.width=-1!==i.indexOf("%")?i:q(h(b).outerWidth());b.parentNode.removeChild(b)}o&&(a.nTable.style.width=q(o))}function Oa(a,b){""===a.oScroll.sX&&""!==a.oScroll.sY?(h(b).width(),b.style.width=q(h(b).outerWidth()-
        a.oScroll.iBarWidth)):""!==a.oScroll.sX&&(b.style.width=q(h(b).outerWidth()))}function Na(a,b){var c=Pa(a,b);if(0>c)return null;if(null===a.aoData[c].nTr){var d=l.createElement("td");d.innerHTML=v(a,c,b,"");return d}return J(a,c)[b]}function Pa(a,b){for(var c=-1,d=-1,i=0;i<a.aoData.length;i++){var e=v(a,i,b,"display")+"",e=e.replace(/<.*?>/g,"");e.length>c&&(c=e.length,d=i)}return d}function q(a){if(null===a)return"0px";if("number"==typeof a)return 0>a?"0px":a+"px";var b=a.charCodeAt(a.length-1);
        return 48>b||57<b?a:a+"px"}function Qa(){var a=l.createElement("p"),b=a.style;b.width="100%";b.height="200px";b.padding="0px";var c=l.createElement("div"),b=c.style;b.position="absolute";b.top="0px";b.left="0px";b.visibility="hidden";b.width="200px";b.height="150px";b.padding="0px";b.overflow="hidden";c.appendChild(a);l.body.appendChild(c);b=a.offsetWidth;c.style.overflow="scroll";a=a.offsetWidth;b==a&&(a=c.clientWidth);l.body.removeChild(c);return b-a}function O(a,b){var c,d,i,e,g,k,o=[],m=[],p=
        j.ext.oSort,l=a.aoData,q=a.aoColumns,G=a.oLanguage.oAria;if(!a.oFeatures.bServerSide&&(0!==a.aaSorting.length||null!==a.aaSortingFixed)){o=null!==a.aaSortingFixed?a.aaSortingFixed.concat(a.aaSorting):a.aaSorting.slice();for(c=0;c<o.length;c++)if(d=o[c][0],i=R(a,d),e=a.aoColumns[d].sSortDataType,j.ext.afnSortData[e])if(g=j.ext.afnSortData[e].call(a.oInstance,a,d,i),g.length===l.length){i=0;for(e=l.length;i<e;i++)F(a,i,d,g[i])}else D(a,0,"Returned data sort array (col "+d+") is the wrong length");c=
        0;for(d=a.aiDisplayMaster.length;c<d;c++)m[a.aiDisplayMaster[c]]=c;var r=o.length,s;c=0;for(d=l.length;c<d;c++)for(i=0;i<r;i++){s=q[o[i][0]].aDataSort;g=0;for(k=s.length;g<k;g++)e=q[s[g]].sType,e=p[(e?e:"string")+"-pre"],l[c]._aSortData[s[g]]=e?e(v(a,c,s[g],"sort")):v(a,c,s[g],"sort")}a.aiDisplayMaster.sort(function(a,b){var c,d,e,i,f;for(c=0;c<r;c++){f=q[o[c][0]].aDataSort;d=0;for(e=f.length;d<e;d++)if(i=q[f[d]].sType,i=p[(i?i:"string")+"-"+o[c][1]](l[a]._aSortData[f[d]],l[b]._aSortData[f[d]]),0!==
        i)return i}return p["numeric-asc"](m[a],m[b])})}(b===n||b)&&!a.oFeatures.bDeferRender&&P(a);c=0;for(d=a.aoColumns.length;c<d;c++)e=q[c].sTitle.replace(/<.*?>/g,""),i=q[c].nTh,i.removeAttribute("aria-sort"),i.removeAttribute("aria-label"),q[c].bSortable?0<o.length&&o[0][0]==c?(i.setAttribute("aria-sort","asc"==o[0][1]?"ascending":"descending"),i.setAttribute("aria-label",e+("asc"==(q[c].asSorting[o[0][2]+1]?q[c].asSorting[o[0][2]+1]:q[c].asSorting[0])?G.sSortAscending:G.sSortDescending))):i.setAttribute("aria-label",
        e+("asc"==q[c].asSorting[0]?G.sSortAscending:G.sSortDescending)):i.setAttribute("aria-label",e);a.bSorted=!0;h(a.oInstance).trigger("sort",a);a.oFeatures.bFilter?K(a,a.oPreviousSearch,1):(a.aiDisplay=a.aiDisplayMaster.slice(),a._iDisplayStart=0,y(a),x(a))}function ia(a,b,c,d){Ra(b,{},function(b){if(!1!==a.aoColumns[c].bSortable){var e=function(){var d,e;if(b.shiftKey){for(var f=!1,h=0;h<a.aaSorting.length;h++)if(a.aaSorting[h][0]==c){f=!0;d=a.aaSorting[h][0];e=a.aaSorting[h][2]+1;a.aoColumns[d].asSorting[e]?
        (a.aaSorting[h][1]=a.aoColumns[d].asSorting[e],a.aaSorting[h][2]=e):a.aaSorting.splice(h,1);break}!1===f&&a.aaSorting.push([c,a.aoColumns[c].asSorting[0],0])}else 1==a.aaSorting.length&&a.aaSorting[0][0]==c?(d=a.aaSorting[0][0],e=a.aaSorting[0][2]+1,a.aoColumns[d].asSorting[e]||(e=0),a.aaSorting[0][1]=a.aoColumns[d].asSorting[e],a.aaSorting[0][2]=e):(a.aaSorting.splice(0,a.aaSorting.length),a.aaSorting.push([c,a.aoColumns[c].asSorting[0],0]));O(a)};a.oFeatures.bProcessing?(E(a,!0),setTimeout(function(){e();
        a.oFeatures.bServerSide||E(a,!1)},0)):e();"function"==typeof d&&d(a)}})}function P(a){var b,c,d,e,f,g=a.aoColumns.length,j=a.oClasses;for(b=0;b<g;b++)a.aoColumns[b].bSortable&&h(a.aoColumns[b].nTh).removeClass(j.sSortAsc+" "+j.sSortDesc+" "+a.aoColumns[b].sSortingClass);c=null!==a.aaSortingFixed?a.aaSortingFixed.concat(a.aaSorting):a.aaSorting.slice();for(b=0;b<a.aoColumns.length;b++)if(a.aoColumns[b].bSortable){f=a.aoColumns[b].sSortingClass;e=-1;for(d=0;d<c.length;d++)if(c[d][0]==b){f="asc"==c[d][1]?
        j.sSortAsc:j.sSortDesc;e=d;break}h(a.aoColumns[b].nTh).addClass(f);a.bJUI&&(f=h("span."+j.sSortIcon,a.aoColumns[b].nTh),f.removeClass(j.sSortJUIAsc+" "+j.sSortJUIDesc+" "+j.sSortJUI+" "+j.sSortJUIAscAllowed+" "+j.sSortJUIDescAllowed),f.addClass(-1==e?a.aoColumns[b].sSortingClassJUI:"asc"==c[e][1]?j.sSortJUIAsc:j.sSortJUIDesc))}else h(a.aoColumns[b].nTh).addClass(a.aoColumns[b].sSortingClass);f=j.sSortColumn;if(a.oFeatures.bSort&&a.oFeatures.bSortClasses){a=J(a);e=[];for(b=0;b<g;b++)e.push("");b=0;
        for(d=1;b<c.length;b++)j=parseInt(c[b][0],10),e[j]=f+d,3>d&&d++;f=RegExp(f+"[123]");var o;b=0;for(c=a.length;b<c;b++)j=b%g,d=a[b].className,o=e[j],j=d.replace(f,o),j!=d?a[b].className=h.trim(j):0<o.length&&-1==d.indexOf(o)&&(a[b].className=d+" "+o)}}function ra(a){if(a.oFeatures.bStateSave&&!a.bDestroying){var b,c;b=a.oScroll.bInfinite;var d={iCreate:(new Date).getTime(),iStart:b?0:a._iDisplayStart,iEnd:b?a._iDisplayLength:a._iDisplayEnd,iLength:a._iDisplayLength,aaSorting:h.extend(!0,[],a.aaSorting),
        oSearch:h.extend(!0,{},a.oPreviousSearch),aoSearchCols:h.extend(!0,[],a.aoPreSearchCols),abVisCols:[]};b=0;for(c=a.aoColumns.length;b<c;b++)d.abVisCols.push(a.aoColumns[b].bVisible);A(a,"aoStateSaveParams","stateSaveParams",[a,d]);a.fnStateSave.call(a.oInstance,a,d)}}function Sa(a,b){if(a.oFeatures.bStateSave){var c=a.fnStateLoad.call(a.oInstance,a);if(c){var d=A(a,"aoStateLoadParams","stateLoadParams",[a,c]);if(-1===h.inArray(!1,d)){a.oLoadedState=h.extend(!0,{},c);a._iDisplayStart=c.iStart;a.iInitDisplayStart=
        c.iStart;a._iDisplayEnd=c.iEnd;a._iDisplayLength=c.iLength;a.aaSorting=c.aaSorting.slice();a.saved_aaSorting=c.aaSorting.slice();h.extend(a.oPreviousSearch,c.oSearch);h.extend(!0,a.aoPreSearchCols,c.aoSearchCols);b.saved_aoColumns=[];for(d=0;d<c.abVisCols.length;d++)b.saved_aoColumns[d]={},b.saved_aoColumns[d].bVisible=c.abVisCols[d];A(a,"aoStateLoaded","stateLoaded",[a,c])}}}}function s(a){for(var b=0;b<j.settings.length;b++)if(j.settings[b].nTable===a)return j.settings[b];return null}function T(a){for(var b=
        [],a=a.aoData,c=0,d=a.length;c<d;c++)null!==a[c].nTr&&b.push(a[c].nTr);return b}function J(a,b){var c=[],d,e,f,g,h,j;e=0;var o=a.aoData.length;b!==n&&(e=b,o=b+1);for(f=e;f<o;f++)if(j=a.aoData[f],null!==j.nTr){e=[];for(d=j.nTr.firstChild;d;)g=d.nodeName.toLowerCase(),("td"==g||"th"==g)&&e.push(d),d=d.nextSibling;g=d=0;for(h=a.aoColumns.length;g<h;g++)a.aoColumns[g].bVisible?c.push(e[g-d]):(c.push(j._anHidden[g]),d++)}return c}function D(a,b,c){a=null===a?"DataTables warning: "+c:"DataTables warning (table id = '"+
        a.sTableId+"'): "+c;if(0===b)if("alert"==j.ext.sErrMode)alert(a);else throw Error(a);else X.console&&console.log&&console.log(a)}function p(a,b,c,d){d===n&&(d=c);b[c]!==n&&(a[d]=b[c])}function Ta(a,b){var c,d;for(d in b)b.hasOwnProperty(d)&&(c=b[d],"object"===typeof e[d]&&null!==c&&!1===h.isArray(c)?h.extend(!0,a[d],c):a[d]=c);return a}function Ra(a,b,c){h(a).bind("click.DT",b,function(b){a.blur();c(b)}).bind("keypress.DT",b,function(a){13===a.which&&c(a)}).bind("selectstart.DT",function(){return!1})}
    function z(a,b,c,d){c&&a[b].push({fn:c,sName:d})}function A(a,b,c,d){for(var b=a[b],e=[],f=b.length-1;0<=f;f--)e.push(b[f].fn.apply(a.oInstance,d));null!==c&&h(a.oInstance).trigger(c,d);return e}function Ua(a){var b=h('<div style="position:absolute; top:0; left:0; height:1px; width:1px; overflow:hidden"><div style="position:absolute; top:1px; left:1px; width:100px; overflow:scroll;"><div id="DT_BrowserTest" style="width:100%; height:10px;"></div></div></div>')[0];l.body.appendChild(b);a.oBrowser.bScrollOversize=
        100===h("#DT_BrowserTest",b)[0].offsetWidth?!0:!1;l.body.removeChild(b)}function Va(a){return function(){var b=[s(this[j.ext.iApiIndex])].concat(Array.prototype.slice.call(arguments));return j.ext.oApi[a].apply(this,b)}}var U=/\[.*?\]$/,Wa=X.JSON?JSON.stringify:function(a){var b=typeof a;if("object"!==b||null===a)return"string"===b&&(a='"'+a+'"'),a+"";var c,d,e=[],f=h.isArray(a);for(c in a)d=a[c],b=typeof d,"string"===b?d='"'+d+'"':"object"===b&&null!==d&&(d=Wa(d)),e.push((f?"":'"'+c+'":')+d);return(f?
        "[":"{")+e+(f?"]":"}")};this.$=function(a,b){var c,d,e=[],f;d=s(this[j.ext.iApiIndex]);var g=d.aoData,o=d.aiDisplay,k=d.aiDisplayMaster;b||(b={});b=h.extend({},{filter:"none",order:"current",page:"all"},b);if("current"==b.page){c=d._iDisplayStart;for(d=d.fnDisplayEnd();c<d;c++)(f=g[o[c]].nTr)&&e.push(f)}else if("current"==b.order&&"none"==b.filter){c=0;for(d=k.length;c<d;c++)(f=g[k[c]].nTr)&&e.push(f)}else if("current"==b.order&&"applied"==b.filter){c=0;for(d=o.length;c<d;c++)(f=g[o[c]].nTr)&&e.push(f)}else if("original"==
        b.order&&"none"==b.filter){c=0;for(d=g.length;c<d;c++)(f=g[c].nTr)&&e.push(f)}else if("original"==b.order&&"applied"==b.filter){c=0;for(d=g.length;c<d;c++)f=g[c].nTr,-1!==h.inArray(c,o)&&f&&e.push(f)}else D(d,1,"Unknown selection options");e=h(e);c=e.filter(a);e=e.find(a);return h([].concat(h.makeArray(c),h.makeArray(e)))};this._=function(a,b){var c=[],d,e,f=this.$(a,b);d=0;for(e=f.length;d<e;d++)c.push(this.fnGetData(f[d]));return c};this.fnAddData=function(a,b){if(0===a.length)return[];var c=[],
        d,e=s(this[j.ext.iApiIndex]);if("object"===typeof a[0]&&null!==a[0])for(var f=0;f<a.length;f++){d=H(e,a[f]);if(-1==d)return c;c.push(d)}else{d=H(e,a);if(-1==d)return c;c.push(d)}e.aiDisplay=e.aiDisplayMaster.slice();(b===n||b)&&aa(e);return c};this.fnAdjustColumnSizing=function(a){var b=s(this[j.ext.iApiIndex]);k(b);a===n||a?this.fnDraw(!1):(""!==b.oScroll.sX||""!==b.oScroll.sY)&&this.oApi._fnScrollDraw(b)};this.fnClearTable=function(a){var b=s(this[j.ext.iApiIndex]);ga(b);(a===n||a)&&x(b)};this.fnClose=
        function(a){for(var b=s(this[j.ext.iApiIndex]),c=0;c<b.aoOpenRows.length;c++)if(b.aoOpenRows[c].nParent==a)return(a=b.aoOpenRows[c].nTr.parentNode)&&a.removeChild(b.aoOpenRows[c].nTr),b.aoOpenRows.splice(c,1),0;return 1};this.fnDeleteRow=function(a,b,c){var d=s(this[j.ext.iApiIndex]),e,f,a="object"===typeof a?I(d,a):a,g=d.aoData.splice(a,1);e=0;for(f=d.aoData.length;e<f;e++)null!==d.aoData[e].nTr&&(d.aoData[e].nTr._DT_RowIndex=e);e=h.inArray(a,d.aiDisplay);d.asDataSearch.splice(e,1);ha(d.aiDisplayMaster,
        a);ha(d.aiDisplay,a);"function"===typeof b&&b.call(this,d,g);d._iDisplayStart>=d.fnRecordsDisplay()&&(d._iDisplayStart-=d._iDisplayLength,0>d._iDisplayStart&&(d._iDisplayStart=0));if(c===n||c)y(d),x(d);return g};this.fnDestroy=function(a){var b=s(this[j.ext.iApiIndex]),c=b.nTableWrapper.parentNode,d=b.nTBody,i,f,a=a===n?!1:a;b.bDestroying=!0;A(b,"aoDestroyCallback","destroy",[b]);if(!a){i=0;for(f=b.aoColumns.length;i<f;i++)!1===b.aoColumns[i].bVisible&&this.fnSetColumnVis(i,!0)}h(b.nTableWrapper).find("*").andSelf().unbind(".DT");
        h("tbody>tr>td."+b.oClasses.sRowEmpty,b.nTable).parent().remove();b.nTable!=b.nTHead.parentNode&&(h(b.nTable).children("thead").remove(),b.nTable.appendChild(b.nTHead));b.nTFoot&&b.nTable!=b.nTFoot.parentNode&&(h(b.nTable).children("tfoot").remove(),b.nTable.appendChild(b.nTFoot));b.nTable.parentNode.removeChild(b.nTable);h(b.nTableWrapper).remove();b.aaSorting=[];b.aaSortingFixed=[];P(b);h(T(b)).removeClass(b.asStripeClasses.join(" "));h("th, td",b.nTHead).removeClass([b.oClasses.sSortable,b.oClasses.sSortableAsc,
            b.oClasses.sSortableDesc,b.oClasses.sSortableNone].join(" "));b.bJUI&&(h("th span."+b.oClasses.sSortIcon+", td span."+b.oClasses.sSortIcon,b.nTHead).remove(),h("th, td",b.nTHead).each(function(){var a=h("div."+b.oClasses.sSortJUIWrapper,this),c=a.contents();h(this).append(c);a.remove()}));!a&&b.nTableReinsertBefore?c.insertBefore(b.nTable,b.nTableReinsertBefore):a||c.appendChild(b.nTable);i=0;for(f=b.aoData.length;i<f;i++)null!==b.aoData[i].nTr&&d.appendChild(b.aoData[i].nTr);!0===b.oFeatures.bAutoWidth&&
        (b.nTable.style.width=q(b.sDestroyWidth));if(f=b.asDestroyStripes.length){a=h(d).children("tr");for(i=0;i<f;i++)a.filter(":nth-child("+f+"n + "+i+")").addClass(b.asDestroyStripes[i])}i=0;for(f=j.settings.length;i<f;i++)j.settings[i]==b&&j.settings.splice(i,1);e=b=null};this.fnDraw=function(a){var b=s(this[j.ext.iApiIndex]);!1===a?(y(b),x(b)):aa(b)};this.fnFilter=function(a,b,c,d,e,f){var g=s(this[j.ext.iApiIndex]);if(g.oFeatures.bFilter){if(c===n||null===c)c=!1;if(d===n||null===d)d=!0;if(e===n||null===
        e)e=!0;if(f===n||null===f)f=!0;if(b===n||null===b){if(K(g,{sSearch:a+"",bRegex:c,bSmart:d,bCaseInsensitive:f},1),e&&g.aanFeatures.f){b=g.aanFeatures.f;c=0;for(d=b.length;c<d;c++)try{b[c]._DT_Input!=l.activeElement&&h(b[c]._DT_Input).val(a)}catch(o){h(b[c]._DT_Input).val(a)}}}else h.extend(g.aoPreSearchCols[b],{sSearch:a+"",bRegex:c,bSmart:d,bCaseInsensitive:f}),K(g,g.oPreviousSearch,1)}};this.fnGetData=function(a,b){var c=s(this[j.ext.iApiIndex]);if(a!==n){var d=a;if("object"===typeof a){var e=a.nodeName.toLowerCase();
        "tr"===e?d=I(c,a):"td"===e&&(d=I(c,a.parentNode),b=fa(c,d,a))}return b!==n?v(c,d,b,""):c.aoData[d]!==n?c.aoData[d]._aData:null}return Z(c)};this.fnGetNodes=function(a){var b=s(this[j.ext.iApiIndex]);return a!==n?b.aoData[a]!==n?b.aoData[a].nTr:null:T(b)};this.fnGetPosition=function(a){var b=s(this[j.ext.iApiIndex]),c=a.nodeName.toUpperCase();return"TR"==c?I(b,a):"TD"==c||"TH"==c?(c=I(b,a.parentNode),a=fa(b,c,a),[c,R(b,a),a]):null};this.fnIsOpen=function(a){for(var b=s(this[j.ext.iApiIndex]),c=0;c<
        b.aoOpenRows.length;c++)if(b.aoOpenRows[c].nParent==a)return!0;return!1};this.fnOpen=function(a,b,c){var d=s(this[j.ext.iApiIndex]),e=T(d);if(-1!==h.inArray(a,e)){this.fnClose(a);var e=l.createElement("tr"),f=l.createElement("td");e.appendChild(f);f.className=c;f.colSpan=t(d);"string"===typeof b?f.innerHTML=b:h(f).html(b);b=h("tr",d.nTBody);-1!=h.inArray(a,b)&&h(e).insertAfter(a);d.aoOpenRows.push({nTr:e,nParent:a});return e}};this.fnPageChange=function(a,b){var c=s(this[j.ext.iApiIndex]);qa(c,a);
        y(c);(b===n||b)&&x(c)};this.fnSetColumnVis=function(a,b,c){var d=s(this[j.ext.iApiIndex]),e,f,g=d.aoColumns,h=d.aoData,o,m;if(g[a].bVisible!=b){if(b){for(e=f=0;e<a;e++)g[e].bVisible&&f++;m=f>=t(d);if(!m)for(e=a;e<g.length;e++)if(g[e].bVisible){o=e;break}e=0;for(f=h.length;e<f;e++)null!==h[e].nTr&&(m?h[e].nTr.appendChild(h[e]._anHidden[a]):h[e].nTr.insertBefore(h[e]._anHidden[a],J(d,e)[o]))}else{e=0;for(f=h.length;e<f;e++)null!==h[e].nTr&&(o=J(d,e)[a],h[e]._anHidden[a]=o,o.parentNode.removeChild(o))}g[a].bVisible=
        b;W(d,d.aoHeader);d.nTFoot&&W(d,d.aoFooter);e=0;for(f=d.aoOpenRows.length;e<f;e++)d.aoOpenRows[e].nTr.colSpan=t(d);if(c===n||c)k(d),x(d);ra(d)}};this.fnSettings=function(){return s(this[j.ext.iApiIndex])};this.fnSort=function(a){var b=s(this[j.ext.iApiIndex]);b.aaSorting=a;O(b)};this.fnSortListener=function(a,b,c){ia(s(this[j.ext.iApiIndex]),a,b,c)};this.fnUpdate=function(a,b,c,d,e){var f=s(this[j.ext.iApiIndex]),b="object"===typeof b?I(f,b):b;if(h.isArray(a)&&c===n){f.aoData[b]._aData=a.slice();
        for(c=0;c<f.aoColumns.length;c++)this.fnUpdate(v(f,b,c),b,c,!1,!1)}else if(h.isPlainObject(a)&&c===n){f.aoData[b]._aData=h.extend(!0,{},a);for(c=0;c<f.aoColumns.length;c++)this.fnUpdate(v(f,b,c),b,c,!1,!1)}else{F(f,b,c,a);var a=v(f,b,c,"display"),g=f.aoColumns[c];null!==g.fnRender&&(a=S(f,b,c),g.bUseRendered&&F(f,b,c,a));null!==f.aoData[b].nTr&&(J(f,b)[c].innerHTML=a)}c=h.inArray(b,f.aiDisplay);f.asDataSearch[c]=na(f,Y(f,b,"filter",r(f,"bSearchable")));(e===n||e)&&k(f);(d===n||d)&&aa(f);return 0};
    this.fnVersionCheck=j.ext.fnVersionCheck;this.oApi={_fnExternApiFunc:Va,_fnInitialise:ba,_fnInitComplete:$,_fnLanguageCompat:pa,_fnAddColumn:o,_fnColumnOptions:m,_fnAddData:H,_fnCreateTr:ea,_fnGatherData:ua,_fnBuildHead:va,_fnDrawHead:W,_fnDraw:x,_fnReDraw:aa,_fnAjaxUpdate:wa,_fnAjaxParameters:Ea,_fnAjaxUpdateDraw:Fa,_fnServerParams:ka,_fnAddOptionsHtml:xa,_fnFeatureHtmlTable:Ba,_fnScrollDraw:La,_fnAdjustColumnSizing:k,_fnFeatureHtmlFilter:za,_fnFilterComplete:K,_fnFilterCustom:Ia,_fnFilterColumn:Ha,
        _fnFilter:Ga,_fnBuildSearchArray:la,_fnBuildSearchRow:na,_fnFilterCreateSearch:ma,_fnDataToSearch:Ja,_fnSort:O,_fnSortAttachListener:ia,_fnSortingClasses:P,_fnFeatureHtmlPaginate:Da,_fnPageChange:qa,_fnFeatureHtmlInfo:Ca,_fnUpdateInfo:Ka,_fnFeatureHtmlLength:ya,_fnFeatureHtmlProcessing:Aa,_fnProcessingDisplay:E,_fnVisibleToColumnIndex:G,_fnColumnIndexToVisible:R,_fnNodeToDataIndex:I,_fnVisbleColumns:t,_fnCalculateEnd:y,_fnConvertToWidth:Ma,_fnCalculateColumnWidths:da,_fnScrollingWidthAdjust:Oa,_fnGetWidestNode:Na,
        _fnGetMaxLenString:Pa,_fnStringToCss:q,_fnDetectType:B,_fnSettingsFromNode:s,_fnGetDataMaster:Z,_fnGetTrNodes:T,_fnGetTdNodes:J,_fnEscapeRegex:oa,_fnDeleteIndex:ha,_fnReOrderIndex:u,_fnColumnOrdering:M,_fnLog:D,_fnClearTable:ga,_fnSaveState:ra,_fnLoadState:Sa,_fnCreateCookie:function(a,b,c,d,e){var f=new Date;f.setTime(f.getTime()+1E3*c);var c=X.location.pathname.split("/"),a=a+"_"+c.pop().replace(/[\/:]/g,"").toLowerCase(),g;null!==e?(g="function"===typeof h.parseJSON?h.parseJSON(b):eval("("+b+")"),
            b=e(a,g,f.toGMTString(),c.join("/")+"/")):b=a+"="+encodeURIComponent(b)+"; expires="+f.toGMTString()+"; path="+c.join("/")+"/";a=l.cookie.split(";");e=b.split(";")[0].length;f=[];if(4096<e+l.cookie.length+10){for(var j=0,o=a.length;j<o;j++)if(-1!=a[j].indexOf(d)){var k=a[j].split("=");try{(g=eval("("+decodeURIComponent(k[1])+")"))&&g.iCreate&&f.push({name:k[0],time:g.iCreate})}catch(m){}}for(f.sort(function(a,b){return b.time-a.time});4096<e+l.cookie.length+10;){if(0===f.length)return;d=f.pop();l.cookie=
            d.name+"=; expires=Thu, 01-Jan-1970 00:00:01 GMT; path="+c.join("/")+"/"}}l.cookie=b},_fnReadCookie:function(a){for(var b=X.location.pathname.split("/"),a=a+"_"+b[b.length-1].replace(/[\/:]/g,"").toLowerCase()+"=",b=l.cookie.split(";"),c=0;c<b.length;c++){for(var d=b[c];" "==d.charAt(0);)d=d.substring(1,d.length);if(0===d.indexOf(a))return decodeURIComponent(d.substring(a.length,d.length))}return null},_fnDetectHeader:V,_fnGetUniqueThs:N,_fnScrollBarWidth:Qa,_fnApplyToChildren:C,_fnMap:p,_fnGetRowData:Y,
        _fnGetCellData:v,_fnSetCellData:F,_fnGetObjectDataFn:Q,_fnSetObjectDataFn:L,_fnApplyColumnDefs:ta,_fnBindAction:Ra,_fnExtend:Ta,_fnCallbackReg:z,_fnCallbackFire:A,_fnJsonString:Wa,_fnRender:S,_fnNodeToColumnIndex:fa,_fnInfoMacros:ja,_fnBrowserDetect:Ua,_fnGetColumns:r};h.extend(j.ext.oApi,this.oApi);for(var sa in j.ext.oApi)sa&&(this[sa]=Va(sa));var ca=this;this.each(function(){var a=0,b,c,d;c=this.getAttribute("id");var i=!1,f=!1;if("table"!=this.nodeName.toLowerCase())D(null,0,"Attempted to initialise DataTables on a node which is not a table: "+
        this.nodeName);else{a=0;for(b=j.settings.length;a<b;a++){if(j.settings[a].nTable==this){if(e===n||e.bRetrieve)return j.settings[a].oInstance;if(e.bDestroy){j.settings[a].oInstance.fnDestroy();break}else{D(j.settings[a],0,"Cannot reinitialise DataTable.\n\nTo retrieve the DataTables object for this table, pass no arguments or see the docs for bRetrieve and bDestroy");return}}if(j.settings[a].sTableId==this.id){j.settings.splice(a,1);break}}if(null===c||""===c)this.id=c="DataTables_Table_"+j.ext._oExternConfig.iNextUnique++;
        var g=h.extend(!0,{},j.models.oSettings,{nTable:this,oApi:ca.oApi,oInit:e,sDestroyWidth:h(this).width(),sInstance:c,sTableId:c});j.settings.push(g);g.oInstance=1===ca.length?ca:h(this).dataTable();e||(e={});e.oLanguage&&pa(e.oLanguage);e=Ta(h.extend(!0,{},j.defaults),e);p(g.oFeatures,e,"bPaginate");p(g.oFeatures,e,"bLengthChange");p(g.oFeatures,e,"bFilter");p(g.oFeatures,e,"bSort");p(g.oFeatures,e,"bInfo");p(g.oFeatures,e,"bProcessing");p(g.oFeatures,e,"bAutoWidth");p(g.oFeatures,e,"bSortClasses");
        p(g.oFeatures,e,"bServerSide");p(g.oFeatures,e,"bDeferRender");p(g.oScroll,e,"sScrollX","sX");p(g.oScroll,e,"sScrollXInner","sXInner");p(g.oScroll,e,"sScrollY","sY");p(g.oScroll,e,"bScrollCollapse","bCollapse");p(g.oScroll,e,"bScrollInfinite","bInfinite");p(g.oScroll,e,"iScrollLoadGap","iLoadGap");p(g.oScroll,e,"bScrollAutoCss","bAutoCss");p(g,e,"asStripeClasses");p(g,e,"asStripClasses","asStripeClasses");p(g,e,"fnServerData");p(g,e,"fnFormatNumber");p(g,e,"sServerMethod");p(g,e,"aaSorting");p(g,
            e,"aaSortingFixed");p(g,e,"aLengthMenu");p(g,e,"sPaginationType");p(g,e,"sAjaxSource");p(g,e,"sAjaxDataProp");p(g,e,"iCookieDuration");p(g,e,"sCookiePrefix");p(g,e,"sDom");p(g,e,"bSortCellsTop");p(g,e,"iTabIndex");p(g,e,"oSearch","oPreviousSearch");p(g,e,"aoSearchCols","aoPreSearchCols");p(g,e,"iDisplayLength","_iDisplayLength");p(g,e,"bJQueryUI","bJUI");p(g,e,"fnCookieCallback");p(g,e,"fnStateLoad");p(g,e,"fnStateSave");p(g.oLanguage,e,"fnInfoCallback");z(g,"aoDrawCallback",e.fnDrawCallback,"user");
        z(g,"aoServerParams",e.fnServerParams,"user");z(g,"aoStateSaveParams",e.fnStateSaveParams,"user");z(g,"aoStateLoadParams",e.fnStateLoadParams,"user");z(g,"aoStateLoaded",e.fnStateLoaded,"user");z(g,"aoRowCallback",e.fnRowCallback,"user");z(g,"aoRowCreatedCallback",e.fnCreatedRow,"user");z(g,"aoHeaderCallback",e.fnHeaderCallback,"user");z(g,"aoFooterCallback",e.fnFooterCallback,"user");z(g,"aoInitComplete",e.fnInitComplete,"user");z(g,"aoPreDrawCallback",e.fnPreDrawCallback,"user");g.oFeatures.bServerSide&&
            g.oFeatures.bSort&&g.oFeatures.bSortClasses?z(g,"aoDrawCallback",P,"server_side_sort_classes"):g.oFeatures.bDeferRender&&z(g,"aoDrawCallback",P,"defer_sort_classes");e.bJQueryUI?(h.extend(g.oClasses,j.ext.oJUIClasses),e.sDom===j.defaults.sDom&&"lfrtip"===j.defaults.sDom&&(g.sDom='<"H"lfr>t<"F"ip>')):h.extend(g.oClasses,j.ext.oStdClasses);h(this).addClass(g.oClasses.sTable);if(""!==g.oScroll.sX||""!==g.oScroll.sY)g.oScroll.iBarWidth=Qa();g.iInitDisplayStart===n&&(g.iInitDisplayStart=e.iDisplayStart,
            g._iDisplayStart=e.iDisplayStart);e.bStateSave&&(g.oFeatures.bStateSave=!0,Sa(g,e),z(g,"aoDrawCallback",ra,"state_save"));null!==e.iDeferLoading&&(g.bDeferLoading=!0,a=h.isArray(e.iDeferLoading),g._iRecordsDisplay=a?e.iDeferLoading[0]:e.iDeferLoading,g._iRecordsTotal=a?e.iDeferLoading[1]:e.iDeferLoading);null!==e.aaData&&(f=!0);""!==e.oLanguage.sUrl?(g.oLanguage.sUrl=e.oLanguage.sUrl,h.getJSON(g.oLanguage.sUrl,null,function(a){pa(a);h.extend(true,g.oLanguage,e.oLanguage,a);ba(g)}),i=!0):h.extend(!0,
            g.oLanguage,e.oLanguage);null===e.asStripeClasses&&(g.asStripeClasses=[g.oClasses.sStripeOdd,g.oClasses.sStripeEven]);b=g.asStripeClasses.length;g.asDestroyStripes=[];if(b){c=!1;d=h(this).children("tbody").children("tr:lt("+b+")");for(a=0;a<b;a++)d.hasClass(g.asStripeClasses[a])&&(c=!0,g.asDestroyStripes.push(g.asStripeClasses[a]));c&&d.removeClass(g.asStripeClasses.join(" "))}c=[];a=this.getElementsByTagName("thead");0!==a.length&&(V(g.aoHeader,a[0]),c=N(g));if(null===e.aoColumns){d=[];a=0;for(b=
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    c.length;a<b;a++)d.push(null)}else d=e.aoColumns;a=0;for(b=d.length;a<b;a++)e.saved_aoColumns!==n&&e.saved_aoColumns.length==b&&(null===d[a]&&(d[a]={}),d[a].bVisible=e.saved_aoColumns[a].bVisible),o(g,c?c[a]:null);ta(g,e.aoColumnDefs,d,function(a,b){m(g,a,b)});a=0;for(b=g.aaSorting.length;a<b;a++){g.aaSorting[a][0]>=g.aoColumns.length&&(g.aaSorting[a][0]=0);var k=g.aoColumns[g.aaSorting[a][0]];g.aaSorting[a][2]===n&&(g.aaSorting[a][2]=0);e.aaSorting===n&&g.saved_aaSorting===n&&(g.aaSorting[a][1]=
            k.asSorting[0]);c=0;for(d=k.asSorting.length;c<d;c++)if(g.aaSorting[a][1]==k.asSorting[c]){g.aaSorting[a][2]=c;break}}P(g);Ua(g);a=h(this).children("caption").each(function(){this._captionSide=h(this).css("caption-side")});b=h(this).children("thead");0===b.length&&(b=[l.createElement("thead")],this.appendChild(b[0]));g.nTHead=b[0];b=h(this).children("tbody");0===b.length&&(b=[l.createElement("tbody")],this.appendChild(b[0]));g.nTBody=b[0];g.nTBody.setAttribute("role","alert");g.nTBody.setAttribute("aria-live",
            "polite");g.nTBody.setAttribute("aria-relevant","all");b=h(this).children("tfoot");if(0===b.length&&0<a.length&&(""!==g.oScroll.sX||""!==g.oScroll.sY))b=[l.createElement("tfoot")],this.appendChild(b[0]);0<b.length&&(g.nTFoot=b[0],V(g.aoFooter,g.nTFoot));if(f)for(a=0;a<e.aaData.length;a++)H(g,e.aaData[a]);else ua(g);g.aiDisplay=g.aiDisplayMaster.slice();g.bInitialised=!0;!1===i&&ba(g)}});ca=null;return this};j.fnVersionCheck=function(e){for(var h=function(e,h){for(;e.length<h;)e+="0";return e},m=j.ext.sVersion.split("."),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            e=e.split("."),k="",n="",l=0,t=e.length;l<t;l++)k+=h(m[l],3),n+=h(e[l],3);return parseInt(k,10)>=parseInt(n,10)};j.fnIsDataTable=function(e){for(var h=j.settings,m=0;m<h.length;m++)if(h[m].nTable===e||h[m].nScrollHead===e||h[m].nScrollFoot===e)return!0;return!1};j.fnTables=function(e){var o=[];jQuery.each(j.settings,function(j,k){(!e||!0===e&&h(k.nTable).is(":visible"))&&o.push(k.nTable)});return o};j.version="1.9.4";j.settings=[];j.models={};j.models.ext={afnFiltering:[],afnSortData:[],aoFeatures:[],
    aTypes:[],fnVersionCheck:j.fnVersionCheck,iApiIndex:0,ofnSearch:{},oApi:{},oStdClasses:{},oJUIClasses:{},oPagination:{},oSort:{},sVersion:j.version,sErrMode:"alert",_oExternConfig:{iNextUnique:0}};j.models.oSearch={bCaseInsensitive:!0,sSearch:"",bRegex:!1,bSmart:!0};j.models.oRow={nTr:null,_aData:[],_aSortData:[],_anHidden:[],_sRowStripe:""};j.models.oColumn={aDataSort:null,asSorting:null,bSearchable:null,bSortable:null,bUseRendered:null,bVisible:null,_bAutoType:!0,fnCreatedCell:null,fnGetData:null,
    fnRender:null,fnSetData:null,mData:null,mRender:null,nTh:null,nTf:null,sClass:null,sContentPadding:null,sDefaultContent:null,sName:null,sSortDataType:"std",sSortingClass:null,sSortingClassJUI:null,sTitle:null,sType:null,sWidth:null,sWidthOrig:null};j.defaults={aaData:null,aaSorting:[[0,"asc"]],aaSortingFixed:null,aLengthMenu:[10,25,50,100],aoColumns:null,aoColumnDefs:null,aoSearchCols:[],asStripeClasses:null,bAutoWidth:!0,bDeferRender:!1,bDestroy:!1,bFilter:!0,bInfo:!0,bJQueryUI:!1,bLengthChange:!0,
    bPaginate:!0,bProcessing:!1,bRetrieve:!1,bScrollAutoCss:!0,bScrollCollapse:!1,bScrollInfinite:!1,bServerSide:!1,bSort:!0,bSortCellsTop:!1,bSortClasses:!0,bStateSave:!1,fnCookieCallback:null,fnCreatedRow:null,fnDrawCallback:null,fnFooterCallback:null,fnFormatNumber:function(e){if(1E3>e)return e;for(var h=e+"",e=h.split(""),j="",h=h.length,k=0;k<h;k++)0===k%3&&0!==k&&(j=this.oLanguage.sInfoThousands+j),j=e[h-k-1]+j;return j},fnHeaderCallback:null,fnInfoCallback:null,fnInitComplete:null,fnPreDrawCallback:null,
    fnRowCallback:null,fnServerData:function(e,j,m,k){k.jqXHR=h.ajax({url:e,data:j,success:function(e){e.sError&&k.oApi._fnLog(k,0,e.sError);h(k.oInstance).trigger("xhr",[k,e]);m(e)},dataType:"json",cache:!1,type:k.sServerMethod,error:function(e,h){"parsererror"==h&&k.oApi._fnLog(k,0,"DataTables warning: JSON data from server could not be parsed. This is caused by a JSON formatting error.")}})},fnServerParams:null,fnStateLoad:function(e){var e=this.oApi._fnReadCookie(e.sCookiePrefix+e.sInstance),j;try{j=
        "function"===typeof h.parseJSON?h.parseJSON(e):eval("("+e+")")}catch(m){j=null}return j},fnStateLoadParams:null,fnStateLoaded:null,fnStateSave:function(e,h){this.oApi._fnCreateCookie(e.sCookiePrefix+e.sInstance,this.oApi._fnJsonString(h),e.iCookieDuration,e.sCookiePrefix,e.fnCookieCallback)},fnStateSaveParams:null,iCookieDuration:7200,iDeferLoading:null,iDisplayLength:10,iDisplayStart:0,iScrollLoadGap:100,iTabIndex:0,oLanguage:{oAria:{sSortAscending:": activate to sort column ascending",sSortDescending:": activate to sort column descending"},
        oPaginate:{sFirst:"First",sLast:"Last",sNext:"Next",sPrevious:"Previous"},sEmptyTable:"No data available in table",sInfo:"Showing _START_ to _END_ of _TOTAL_ entries",sInfoEmpty:"Showing 0 to 0 of 0 entries",sInfoFiltered:"(filtered from _MAX_ total entries)",sInfoPostFix:"",sInfoThousands:",",sLengthMenu:"Show _MENU_ entries",sLoadingRecords:"Loading...",sProcessing:"Processing...",sSearch:"Search:",sUrl:"",sZeroRecords:"No matching records found"},oSearch:h.extend({},j.models.oSearch),sAjaxDataProp:"aaData",
    sAjaxSource:null,sCookiePrefix:"SpryMedia_DataTables_",sDom:"lfrtip",sPaginationType:"two_button",sScrollX:"",sScrollXInner:"",sScrollY:"",sServerMethod:"GET"};j.defaults.columns={aDataSort:null,asSorting:["asc","desc"],bSearchable:!0,bSortable:!0,bUseRendered:!0,bVisible:!0,fnCreatedCell:null,fnRender:null,iDataSort:-1,mData:null,mRender:null,sCellType:"td",sClass:"",sContentPadding:"",sDefaultContent:null,sName:"",sSortDataType:"std",sTitle:null,sType:null,sWidth:null};j.models.oSettings={oFeatures:{bAutoWidth:null,
    bDeferRender:null,bFilter:null,bInfo:null,bLengthChange:null,bPaginate:null,bProcessing:null,bServerSide:null,bSort:null,bSortClasses:null,bStateSave:null},oScroll:{bAutoCss:null,bCollapse:null,bInfinite:null,iBarWidth:0,iLoadGap:null,sX:null,sXInner:null,sY:null},oLanguage:{fnInfoCallback:null},oBrowser:{bScrollOversize:!1},aanFeatures:[],aoData:[],aiDisplay:[],aiDisplayMaster:[],aoColumns:[],aoHeader:[],aoFooter:[],asDataSearch:[],oPreviousSearch:{},aoPreSearchCols:[],aaSorting:null,aaSortingFixed:null,
    asStripeClasses:null,asDestroyStripes:[],sDestroyWidth:0,aoRowCallback:[],aoHeaderCallback:[],aoFooterCallback:[],aoDrawCallback:[],aoRowCreatedCallback:[],aoPreDrawCallback:[],aoInitComplete:[],aoStateSaveParams:[],aoStateLoadParams:[],aoStateLoaded:[],sTableId:"",nTable:null,nTHead:null,nTFoot:null,nTBody:null,nTableWrapper:null,bDeferLoading:!1,bInitialised:!1,aoOpenRows:[],sDom:null,sPaginationType:"two_button",iCookieDuration:0,sCookiePrefix:"",fnCookieCallback:null,aoStateSave:[],aoStateLoad:[],
    oLoadedState:null,sAjaxSource:null,sAjaxDataProp:null,bAjaxDataGet:!0,jqXHR:null,fnServerData:null,aoServerParams:[],sServerMethod:null,fnFormatNumber:null,aLengthMenu:null,iDraw:0,bDrawing:!1,iDrawError:-1,_iDisplayLength:10,_iDisplayStart:0,_iDisplayEnd:10,_iRecordsTotal:0,_iRecordsDisplay:0,bJUI:null,oClasses:{},bFiltered:!1,bSorted:!1,bSortCellsTop:null,oInit:null,aoDestroyCallback:[],fnRecordsTotal:function(){return this.oFeatures.bServerSide?parseInt(this._iRecordsTotal,10):this.aiDisplayMaster.length},
    fnRecordsDisplay:function(){return this.oFeatures.bServerSide?parseInt(this._iRecordsDisplay,10):this.aiDisplay.length},fnDisplayEnd:function(){return this.oFeatures.bServerSide?!1===this.oFeatures.bPaginate||-1==this._iDisplayLength?this._iDisplayStart+this.aiDisplay.length:Math.min(this._iDisplayStart+this._iDisplayLength,this._iRecordsDisplay):this._iDisplayEnd},oInstance:null,sInstance:null,iTabIndex:0,nScrollHead:null,nScrollFoot:null};j.ext=h.extend(!0,{},j.models.ext);h.extend(j.ext.oStdClasses,
    {sTable:"dataTable",sPagePrevEnabled:"paginate_enabled_previous",sPagePrevDisabled:"paginate_disabled_previous",sPageNextEnabled:"paginate_enabled_next",sPageNextDisabled:"paginate_disabled_next",sPageJUINext:"",sPageJUIPrev:"",sPageButton:"paginate_button",sPageButtonActive:"paginate_active",sPageButtonStaticDisabled:"paginate_button paginate_button_disabled",sPageFirst:"first",sPagePrevious:"previous",sPageNext:"next",sPageLast:"last",sStripeOdd:"odd",sStripeEven:"even",sRowEmpty:"dataTables_empty",
        sWrapper:"dataTables_wrapper",sFilter:"dataTables_filter",sInfo:"dataTables_info",sPaging:"dataTables_paginate paging_",sLength:"dataTables_length",sProcessing:"dataTables_processing",sSortAsc:"sorting_asc",sSortDesc:"sorting_desc",sSortable:"sorting",sSortableAsc:"sorting_asc_disabled",sSortableDesc:"sorting_desc_disabled",sSortableNone:"sorting_disabled",sSortColumn:"sorting_",sSortJUIAsc:"",sSortJUIDesc:"",sSortJUI:"",sSortJUIAscAllowed:"",sSortJUIDescAllowed:"",sSortJUIWrapper:"",sSortIcon:"",
        sScrollWrapper:"dataTables_scroll",sScrollHead:"dataTables_scrollHead",sScrollHeadInner:"dataTables_scrollHeadInner",sScrollBody:"dataTables_scrollBody",sScrollFoot:"dataTables_scrollFoot",sScrollFootInner:"dataTables_scrollFootInner",sFooterTH:"",sJUIHeader:"",sJUIFooter:""});h.extend(j.ext.oJUIClasses,j.ext.oStdClasses,{sPagePrevEnabled:"fg-button ui-button ui-state-default ui-corner-left",sPagePrevDisabled:"fg-button ui-button ui-state-default ui-corner-left ui-state-disabled",sPageNextEnabled:"fg-button ui-button ui-state-default ui-corner-right",
    sPageNextDisabled:"fg-button ui-button ui-state-default ui-corner-right ui-state-disabled",sPageJUINext:"ui-icon ui-icon-circle-arrow-e",sPageJUIPrev:"ui-icon ui-icon-circle-arrow-w",sPageButton:"fg-button ui-button ui-state-default",sPageButtonActive:"fg-button ui-button ui-state-default ui-state-disabled",sPageButtonStaticDisabled:"fg-button ui-button ui-state-default ui-state-disabled",sPageFirst:"first ui-corner-tl ui-corner-bl",sPageLast:"last ui-corner-tr ui-corner-br",sPaging:"dataTables_paginate fg-buttonset ui-buttonset fg-buttonset-multi ui-buttonset-multi paging_",
    sSortAsc:"ui-state-default",sSortDesc:"ui-state-default",sSortable:"ui-state-default",sSortableAsc:"ui-state-default",sSortableDesc:"ui-state-default",sSortableNone:"ui-state-default",sSortJUIAsc:"css_right ui-icon ui-icon-triangle-1-n",sSortJUIDesc:"css_right ui-icon ui-icon-triangle-1-s",sSortJUI:"css_right ui-icon ui-icon-carat-2-n-s",sSortJUIAscAllowed:"css_right ui-icon ui-icon-carat-1-n",sSortJUIDescAllowed:"css_right ui-icon ui-icon-carat-1-s",sSortJUIWrapper:"DataTables_sort_wrapper",sSortIcon:"DataTables_sort_icon",
    sScrollHead:"dataTables_scrollHead ui-state-default",sScrollFoot:"dataTables_scrollFoot ui-state-default",sFooterTH:"ui-state-default",sJUIHeader:"fg-toolbar ui-toolbar ui-widget-header ui-corner-tl ui-corner-tr ui-helper-clearfix",sJUIFooter:"fg-toolbar ui-toolbar ui-widget-header ui-corner-bl ui-corner-br ui-helper-clearfix"});h.extend(j.ext.oPagination,{two_button:{fnInit:function(e,j,m){var k=e.oLanguage.oPaginate,n=function(h){e.oApi._fnPageChange(e,h.data.action)&&m(e)},k=!e.bJUI?'<a class="'+
    e.oClasses.sPagePrevDisabled+'" tabindex="'+e.iTabIndex+'" role="button">'+k.sPrevious+'</a><a class="'+e.oClasses.sPageNextDisabled+'" tabindex="'+e.iTabIndex+'" role="button">'+k.sNext+"</a>":'<a class="'+e.oClasses.sPagePrevDisabled+'" tabindex="'+e.iTabIndex+'" role="button"><span class="'+e.oClasses.sPageJUIPrev+'"></span></a><a class="'+e.oClasses.sPageNextDisabled+'" tabindex="'+e.iTabIndex+'" role="button"><span class="'+e.oClasses.sPageJUINext+'"></span></a>';h(j).append(k);var l=h("a",j),
    k=l[0],l=l[1];e.oApi._fnBindAction(k,{action:"previous"},n);e.oApi._fnBindAction(l,{action:"next"},n);e.aanFeatures.p||(j.id=e.sTableId+"_paginate",k.id=e.sTableId+"_previous",l.id=e.sTableId+"_next",k.setAttribute("aria-controls",e.sTableId),l.setAttribute("aria-controls",e.sTableId))},fnUpdate:function(e){if(e.aanFeatures.p)for(var h=e.oClasses,j=e.aanFeatures.p,k,l=0,n=j.length;l<n;l++)if(k=j[l].firstChild)k.className=0===e._iDisplayStart?h.sPagePrevDisabled:h.sPagePrevEnabled,k=k.nextSibling,
    k.className=e.fnDisplayEnd()==e.fnRecordsDisplay()?h.sPageNextDisabled:h.sPageNextEnabled}},iFullNumbersShowPages:5,full_numbers:{fnInit:function(e,j,m){var k=e.oLanguage.oPaginate,l=e.oClasses,n=function(h){e.oApi._fnPageChange(e,h.data.action)&&m(e)};h(j).append('<a  tabindex="'+e.iTabIndex+'" class="'+l.sPageButton+" "+l.sPageFirst+'">'+k.sFirst+'</a><a  tabindex="'+e.iTabIndex+'" class="'+l.sPageButton+" "+l.sPagePrevious+'">'+k.sPrevious+'</a><span></span><a tabindex="'+e.iTabIndex+'" class="'+
    l.sPageButton+" "+l.sPageNext+'">'+k.sNext+'</a><a tabindex="'+e.iTabIndex+'" class="'+l.sPageButton+" "+l.sPageLast+'">'+k.sLast+"</a>");var t=h("a",j),k=t[0],l=t[1],r=t[2],t=t[3];e.oApi._fnBindAction(k,{action:"first"},n);e.oApi._fnBindAction(l,{action:"previous"},n);e.oApi._fnBindAction(r,{action:"next"},n);e.oApi._fnBindAction(t,{action:"last"},n);e.aanFeatures.p||(j.id=e.sTableId+"_paginate",k.id=e.sTableId+"_first",l.id=e.sTableId+"_previous",r.id=e.sTableId+"_next",t.id=e.sTableId+"_last")},
    fnUpdate:function(e,o){if(e.aanFeatures.p){var m=j.ext.oPagination.iFullNumbersShowPages,k=Math.floor(m/2),l=Math.ceil(e.fnRecordsDisplay()/e._iDisplayLength),n=Math.ceil(e._iDisplayStart/e._iDisplayLength)+1,t="",r,B=e.oClasses,u,M=e.aanFeatures.p,L=function(h){e.oApi._fnBindAction(this,{page:h+r-1},function(h){e.oApi._fnPageChange(e,h.data.page);o(e);h.preventDefault()})};-1===e._iDisplayLength?n=k=r=1:l<m?(r=1,k=l):n<=k?(r=1,k=m):n>=l-k?(r=l-m+1,k=l):(r=n-Math.ceil(m/2)+1,k=r+m-1);for(m=r;m<=k;m++)t+=
        n!==m?'<a tabindex="'+e.iTabIndex+'" class="'+B.sPageButton+'">'+e.fnFormatNumber(m)+"</a>":'<a tabindex="'+e.iTabIndex+'" class="'+B.sPageButtonActive+'">'+e.fnFormatNumber(m)+"</a>";m=0;for(k=M.length;m<k;m++)u=M[m],u.hasChildNodes()&&(h("span:eq(0)",u).html(t).children("a").each(L),u=u.getElementsByTagName("a"),u=[u[0],u[1],u[u.length-2],u[u.length-1]],h(u).removeClass(B.sPageButton+" "+B.sPageButtonActive+" "+B.sPageButtonStaticDisabled),h([u[0],u[1]]).addClass(1==n?B.sPageButtonStaticDisabled:
        B.sPageButton),h([u[2],u[3]]).addClass(0===l||n===l||-1===e._iDisplayLength?B.sPageButtonStaticDisabled:B.sPageButton))}}}});h.extend(j.ext.oSort,{"string-pre":function(e){"string"!=typeof e&&(e=null!==e&&e.toString?e.toString():"");return e.toLowerCase()},"string-asc":function(e,h){return e<h?-1:e>h?1:0},"string-desc":function(e,h){return e<h?1:e>h?-1:0},"html-pre":function(e){return e.replace(/<.*?>/g,"").toLowerCase()},"html-asc":function(e,h){return e<h?-1:e>h?1:0},"html-desc":function(e,h){return e<
    h?1:e>h?-1:0},"date-pre":function(e){e=Date.parse(e);if(isNaN(e)||""===e)e=Date.parse("01/01/1970 00:00:00");return e},"date-asc":function(e,h){return e-h},"date-desc":function(e,h){return h-e},"numeric-pre":function(e){return"-"==e||""===e?0:1*e},"numeric-asc":function(e,h){return e-h},"numeric-desc":function(e,h){return h-e}});h.extend(j.ext.aTypes,[function(e){if("number"===typeof e)return"numeric";if("string"!==typeof e)return null;var h,j=!1;h=e.charAt(0);if(-1=="0123456789-".indexOf(h))return null;
    for(var k=1;k<e.length;k++){h=e.charAt(k);if(-1=="0123456789.".indexOf(h))return null;if("."==h){if(j)return null;j=!0}}return"numeric"},function(e){var h=Date.parse(e);return null!==h&&!isNaN(h)||"string"===typeof e&&0===e.length?"date":null},function(e){return"string"===typeof e&&-1!=e.indexOf("<")&&-1!=e.indexOf(">")?"html":null}]);h.fn.DataTable=j;h.fn.dataTable=j;h.fn.dataTableSettings=j.settings;h.fn.dataTableExt=j.ext};"function"===typeof define&&define.amd?define(["jquery"],L):jQuery&&!jQuery.fn.dataTable&&
    L(jQuery)})(window,document);
$(document).ready(function() {
  var switched = false;
  var updateTables = function() {
    if (($(window).width() < 767) && !switched ){
      switched = true;
      $("table.responsive").each(function(i, element) {
        splitTable($(element));
      });
      return true;
    }
    else if (switched && ($(window).width() > 767)) {
      switched = false;
      $("table.responsive").each(function(i, element) {
        unsplitTable($(element));
      });
    }
  };
   
  $(window).load(updateTables);
  $(window).bind("resize", updateTables);
   
	
	function splitTable(original)
	{
		original.wrap("<div class='table-wrapper' />");
		
		var copy = original.clone();
		copy.find("td:not(:first-child), th:not(:first-child)").css("display", "none");
		copy.removeClass("responsive");
		
		original.closest(".table-wrapper").append(copy);
		copy.wrap("<div class='pinned' />");
		original.wrap("<div class='scrollable' />");

    setCellHeights(original, copy);
	}
	
	function unsplitTable(original) {
    original.closest(".table-wrapper").find(".pinned").remove();
    original.unwrap();
    original.unwrap();
	}

  function setCellHeights(original, copy) {
    var tr = original.find('tr'),
        tr_copy = copy.find('tr'),
        heights = [];

    tr.each(function (index) {
      var self = $(this),
          tx = self.find('th, td');

      tx.each(function () {
        var height = $(this).outerHeight(true);
        heights[index] = heights[index] || 0;
        if (height > heights[index]) heights[index] = height;
      });

    });

    tr_copy.each(function (index) {
      $(this).height(heights[index]);
    });
  }

});
(function($){
    $.fn.validationEngineLanguage = function(){
    };
    $.validationEngineLanguage = {
        newLang: function(){
            $.validationEngineLanguage.allRules = {
                "required": { // Add your regex rules here, you can take telephone as an example
                    "regex": "none",
                    "alertText": "* This field is required",
                    "alertTextCheckboxMultiple": "* Please select an option",
                    "alertTextCheckboxe": "* This checkbox is required",
                    "alertTextDateRange": "* Both date range fields are required"
                },
                "requiredInFunction": {
                    "func": function(field, rules, i, options){
                        return (field.val() == "test") ? true : false;
                    },
                    "alertText": "* Field must equal test"
                },
                "dateRange": {
                    "regex": "none",
                    "alertText": "* Invalid ",
                    "alertText2": "Date Range"
                },
                "dateTimeRange": {
                    "regex": "none",
                    "alertText": "* Invalid ",
                    "alertText2": "Date Time Range"
                },
                "minSize": {
                    "regex": "none",
                    "alertText": "* Minimum ",
                    "alertText2": " characters required"
                },
                "maxSize": {
                    "regex": "none",
                    "alertText": "* Maximum ",
                    "alertText2": " characters allowed"
                },
                "groupRequired": {
                    "regex": "none",
                    "alertText": "* You must fill one of the following fields"
                },
                "min": {
                    "regex": "none",
                    "alertText": "* Minimum value is "
                },
                "max": {
                    "regex": "none",
                    "alertText": "* Maximum value is "
                },
                "past": {
                    "regex": "none",
                    "alertText": "* Date prior to "
                },
                "future": {
                    "regex": "none",
                    "alertText": "* Date past "
                },
                "maxCheckbox": {
                    "regex": "none",
                    "alertText": "* Maximum ",
                    "alertText2": " options allowed"
                },
                "minCheckbox": {
                    "regex": "none",
                    "alertText": "* Please select ",
                    "alertText2": " options"
                },
                "equals": {
                    "regex": "none",
                    "alertText": "* Fields do not match"
                },
                "creditCard": {
                    "regex": "none",
                    "alertText": "* Invalid credit card number"
                },
                "phone": {
                    // credit: jquery.h5validate.js / orefalo
                    "regex": /^([\+][0-9]{1,3}[\ \.\-])?([\(]{1}[0-9]{2,6}[\)])?([0-9\ \.\-\/]{3,20})((x|ext|extension)[\ ]?[0-9]{1,4})?$/,
                    "alertText": "* Invalid phone number"
                },
                "email": {
                    // HTML5 compatible email regex ( http://www.whatwg.org/specs/web-apps/current-work/multipage/states-of-the-type-attribute.html#    e-mail-state-%28type=email%29 )
                    "regex": /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
                    "alertText": "* Invalid email address"
                },
                "integer": {
                    "regex": /^[\-\+]?\d+$/,
                    "alertText": "* Not a valid integer"
                },
                "number": {
                    // Number, including positive, negative, and floating decimal. credit: orefalo
                    "regex": /^[\-\+]?((([0-9]{1,3})([,][0-9]{3})*)|([0-9]+))?([\.]([0-9]+))?$/,
                    "alertText": "* Invalid floating decimal number"
                },
                "date": {
                    //	Check if date is valid by leap year
                    "func": function (field) {
                        var pattern = new RegExp(/^(\d{4})[\/\-\.](0?[1-9]|1[012])[\/\-\.](0?[1-9]|[12][0-9]|3[01])$/);
                        var match = pattern.exec(field.val());
                        if (match == null)
                            return false;

                        var year = match[1];
                        var month = match[2]*1;
                        var day = match[3]*1;
                        var date = new Date(year, month - 1, day); // because months starts from 0.

                        return (date.getFullYear() == year && date.getMonth() == (month - 1) && date.getDate() == day);
                    },
                    "alertText": "* Invalid date, must be in YYYY-MM-DD format"
                },
                "ipv4": {
                    "regex": /^((([01]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))[.]){3}(([0-1]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))$/,
                    "alertText": "* Invalid IP address"
                },
                "url": {
                    "regex": /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i,
                    "alertText": "* Invalid URL"
                },
                "onlyNumberSp": {
                    "regex": /^[0-9\ ]+$/,
                    "alertText": "* Numbers only"
                },
                "onlyLetterSp": {
                    "regex": /^[a-zA-Z\ \']+$/,
                    "alertText": "* Letters only"
                },
                "onlyLetterNumber": {
                    "regex": /^[0-9a-zA-Z]+$/,
                    "alertText": "* No special characters allowed"
                },
                // --- CUSTOM RULES -- Those are specific to the demos, they can be removed or changed to your likings
                "ajaxUserCall": {
                    "url": "ajaxValidateFieldUser",
                    // you may want to pass extra data on the ajax call
                    "extraData": "name=eric",
                    "alertText": "* This user is already taken",
                    "alertTextLoad": "* Validating, please wait"
                },
                "ajaxUserCallPhp": {
                    "url": "phpajax/ajaxValidateFieldUser.php",
                    // you may want to pass extra data on the ajax call
                    "extraData": "name=eric",
                    // if you provide an "alertTextOk", it will show as a green prompt when the field validates
                    "alertTextOk": "* This username is available",
                    "alertText": "* This user is already taken",
                    "alertTextLoad": "* Validating, please wait"
                },
                "ajaxNameCall": {
                    // remote json service location
                    "url": "ajaxValidateFieldName",
                    // error
                    "alertText": "* This name is already taken",
                    // if you provide an "alertTextOk", it will show as a green prompt when the field validates
                    "alertTextOk": "* This name is available",
                    // speaks by itself
                    "alertTextLoad": "* Validating, please wait"
                },
                "ajaxNameCallPhp": {
                    // remote json service location
                    "url": "phpajax/ajaxValidateFieldName.php",
                    // error
                    "alertText": "* This name is already taken",
                    // speaks by itself
                    "alertTextLoad": "* Validating, please wait"
                },
                "validate2fields": {
                    "alertText": "* Please input HELLO"
                },
                //tls warning:homegrown not fielded
                "dateFormat":{
                    "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(?:(?:0?[1-9]|1[0-2])(\/|-)(?:0?[1-9]|1\d|2[0-8]))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(0?2(\/|-)29)(\/|-)(?:(?:0[48]00|[13579][26]00|[2468][048]00)|(?:\d\d)?(?:0[48]|[2468][048]|[13579][26]))$/,
                    "alertText": "* Invalid Date"
                },
                //tls warning:homegrown not fielded
                "dateTimeFormat": {
                    "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1}$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^((1[012]|0?[1-9]){1}\/(0?[1-9]|[12][0-9]|3[01]){1}\/\d{2,4}\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1})$/,
                    "alertText": "* Invalid Date or Date Format",
                    "alertText2": "Expected Format: ",
                    "alertText3": "mm/dd/yyyy hh:mm:ss AM|PM or ",
                    "alertText4": "yyyy-mm-dd hh:mm:ss AM|PM"
                }
            };

        }
    };

    $.validationEngineLanguage.newLang();

})(jQuery);
/*
 * Inline Form Validation Engine 2.6.2, jQuery plugin
 *
 * Copyright(c) 2010, Cedric Dugas
 * http://www.position-absolute.com
 *
 * 2.0 Rewrite by Olivier Refalo
 * http://www.crionics.com
 *
 * Form validation engine allowing custom regex rules to be added.
 * Licensed under the MIT License
 */

(function($) {

    "use strict";

    var methods = {

        /**
         * Kind of the constructor, called before any action
         * @param {Map} user options
         */
        init: function(options) {
            var form = this;
            if (!form.data('jqv') || form.data('jqv') == null ) {
                options = methods._saveOptions(form, options);
                // bind all formError elements to close on click
                $(document).on("click", ".formError", function() {
                    $(this).fadeOut(150, function() {
                        // remove prompt once invisible
                        $(this).parent('.formErrorOuter').remove();
                        $(this).remove();
                    });
                });
            }
            return this;
        },
        /**
         * Attachs jQuery.validationEngine to form.submit and field.blur events
         * Takes an optional params: a list of options
         * ie. jQuery("#formID1").validationEngine('attach', {promptPosition : "centerRight"});
         */
        attach: function(userOptions) {

            var form = this;
            var options;

            if(userOptions)
                options = methods._saveOptions(form, userOptions);
            else
                options = form.data('jqv');

            options.validateAttribute = (form.find("[data-validation-engine*=validate]").length) ? "data-validation-engine" : "class";
            if (options.binded) {

                // delegate fields
                form.on(options.validationEventTrigger, "["+options.validateAttribute+"*=validate]:not([type=checkbox]):not([type=radio]):not(.datepicker)", methods._onFieldEvent);
                form.on("click", "["+options.validateAttribute+"*=validate][type=checkbox],["+options.validateAttribute+"*=validate][type=radio]", methods._onFieldEvent);
                form.on(options.validationEventTrigger,"["+options.validateAttribute+"*=validate][class*=datepicker]", {"delay": 300}, methods._onFieldEvent);
            }
            if (options.autoPositionUpdate) {
                $(window).bind("resize", {
                    "noAnimation": true,
                    "formElem": form
                }, methods.updatePromptsPosition);
            }
            form.on("click","a[data-validation-engine-skip], a[class*='validate-skip'], button[data-validation-engine-skip], button[class*='validate-skip'], input[data-validation-engine-skip], input[class*='validate-skip']", methods._submitButtonClick);
            form.removeData('jqv_submitButton');

            // bind form.submit
            form.on("submit", methods._onSubmitEvent);
            return this;
        },
        /**
         * Unregisters any bindings that may point to jQuery.validaitonEngine
         */
        detach: function() {

            var form = this;
            var options = form.data('jqv');

            // unbind fields
            form.find("["+options.validateAttribute+"*=validate]").not("[type=checkbox]").off(options.validationEventTrigger, methods._onFieldEvent);
            form.find("["+options.validateAttribute+"*=validate][type=checkbox],[class*=validate][type=radio]").off("click", methods._onFieldEvent);

            // unbind form.submit
            form.off("submit", methods.onAjaxFormComplete);

            // unbind form.submit
            form.die("submit", methods.onAjaxFormComplete);
            form.removeData('jqv');

            form.off("click", "a[data-validation-engine-skip], a[class*='validate-skip'], button[data-validation-engine-skip], button[class*='validate-skip'], input[data-validation-engine-skip], input[class*='validate-skip']", methods._submitButtonClick);
            form.removeData('jqv_submitButton');

            if (options.autoPositionUpdate)
                $(window).unbind("resize", methods.updatePromptsPosition);

            return this;
        },
        /**
         * Validates either a form or a list of fields, shows prompts accordingly.
         * Note: There is no ajax form validation with this method, only field ajax validation are evaluated
         *
         * @return true if the form validates, false if it fails
         */
        validate: function() {
            var element = $(this);
            var valid = null;

            if((element.is("form") || element.hasClass("validationEngineContainer")) && !element.hasClass('validating')) {
                element.addClass('validating');
                var options = element.data('jqv');
                var valid = methods._validateFields(this);

                // If the form doesn't validate, clear the 'validating' class before the user has a chance to submit again
                setTimeout(function(){
                    element.removeClass('validating');
                }, 100);
                if (valid && options.onSuccess) {
                    options.onSuccess();
                } else if (!valid && options.onFailure) {
                    options.onFailure();
                }
            } else if (element.is('form') || element.hasClass('validationEngineContainer')) {
                element.removeClass('validating');
            } else {
                // field validation
                var form = element.closest('form, .validationEngineContainer'),
                    options = (form.data('jqv')) ? form.data('jqv') : $.validationEngine.defaults,
                    valid = methods._validateField(element, options);

                if (valid && options.onFieldSuccess)
                    options.onFieldSuccess();
                else if (options.onFieldFailure && options.InvalidFields.length > 0) {
                    options.onFieldFailure();
                }
            }
            if(options.onValidationComplete) {
                // !! ensures that an undefined return is interpreted as return false but allows a onValidationComplete() to possibly return true and have form continue processing
                return !!options.onValidationComplete(form, valid);
            }
            return valid;
        },
        /**
         *  Redraw prompts position, useful when you change the DOM state when validating
         */
        updatePromptsPosition: function(event) {

            if (event && this == window) {
                var form = event.data.formElem;
                var noAnimation = event.data.noAnimation;
            }
            else
                var form = $(this.closest('form, .validationEngineContainer'));

            var options = form.data('jqv');
            // No option, take default one
            form.find('['+options.validateAttribute+'*=validate]').not(":disabled").each(function(){
                var field = $(this);
                if (options.prettySelect && field.is(":hidden"))
                    field = form.find("#" + options.usePrefix + field.attr('id') + options.useSuffix);
                var prompt = methods._getPrompt(field);
                var promptText = $(prompt).find(".formErrorContent").html();

                if(prompt)
                    methods._updatePrompt(field, $(prompt), promptText, undefined, false, options, noAnimation);
            });
            return this;
        },
        /**
         * Displays a prompt on a element.
         * Note that the element needs an id!
         *
         * @param {String} promptText html text to display type
         * @param {String} type the type of bubble: 'pass' (green), 'load' (black) anything else (red)
         * @param {String} possible values topLeft, topRight, bottomLeft, centerRight, bottomRight
         */
        showPrompt: function(promptText, type, promptPosition, showArrow) {
            var form = this.closest('form, .validationEngineContainer');
            var options = form.data('jqv');
            // No option, take default one
            if(!options)
                options = methods._saveOptions(this, options);
            if(promptPosition)
                options.promptPosition=promptPosition;
            options.showArrow = showArrow==true;

            methods._showPrompt(this, promptText, type, false, options);
            return this;
        },
        /**
         * Closes form error prompts, CAN be invidual
         */
        hide: function() {
            var form = $(this).closest('form, .validationEngineContainer');
            var options = form.data('jqv');
            var fadeDuration = (options && options.fadeDuration) ? options.fadeDuration : 0.3;
            var closingtag;

            if($(this).is("form") || $(this).hasClass("validationEngineContainer")) {
                closingtag = "parentForm"+methods._getClassName($(this).attr("id"));
            } else {
                closingtag = methods._getClassName($(this).attr("id")) +"formError";
            }
            $('.'+closingtag).fadeTo(fadeDuration, 0.3, function() {
                $(this).parent('.formErrorOuter').remove();
                $(this).remove();
            });
            return this;
        },
        /**
         * Closes all error prompts on the page
         */
        hideAll: function() {

            var form = this;
            var options = form.data('jqv');
            var duration = options ? options.fadeDuration:300;
            $('.formError').fadeTo(duration, 300, function() {
                $(this).parent('.formErrorOuter').remove();
                $(this).remove();
            });
            return this;
        },
        /**
         * Typically called when user exists a field using tab or a mouse click, triggers a field
         * validation
         */
        _onFieldEvent: function(event) {
            var field = $(this);
            var form = field.closest('form, .validationEngineContainer');
            var options = form.data('jqv');
            options.eventTrigger = "field";
            // validate the current field
            window.setTimeout(function() {
                methods._validateField(field, options);
                if (options.InvalidFields.length == 0 && options.onFieldSuccess) {
                    options.onFieldSuccess();
                } else if (options.InvalidFields.length > 0 && options.onFieldFailure) {
                    options.onFieldFailure();
                }
            }, (event.data) ? event.data.delay : 0);

        },
        /**
         * Called when the form is submited, shows prompts accordingly
         *
         * @param {jqObject}
            *            form
         * @return false if form submission needs to be cancelled
         */
        _onSubmitEvent: function() {
            var form = $(this);
            var options = form.data('jqv');

            //check if it is trigger from skipped button
            if (form.data("jqv_submitButton")){
                var submitButton = $("#" + form.data("jqv_submitButton"));
                if (submitButton){
                    if (submitButton.length > 0){
                        if (submitButton.hasClass("validate-skip") || submitButton.attr("data-validation-engine-skip") == "true")
                            return true;
                    }
                }
            }

            options.eventTrigger = "submit";

            // validate each field
            // (- skip field ajax validation, not necessary IF we will perform an ajax form validation)
            var r=methods._validateFields(form);

            if (r && options.ajaxFormValidation) {
                methods._validateFormWithAjax(form, options);
                // cancel form auto-submission - process with async call onAjaxFormComplete
                return false;
            }

            if(options.onValidationComplete) {
                // !! ensures that an undefined return is interpreted as return false but allows a onValidationComplete() to possibly return true and have form continue processing
                return !!options.onValidationComplete(form, r);
            }
            return r;
        },
        /**
         * Return true if the ajax field validations passed so far
         * @param {Object} options
         * @return true, is all ajax validation passed so far (remember ajax is async)
         */
        _checkAjaxStatus: function(options) {
            var status = true;
            $.each(options.ajaxValidCache, function(key, value) {
                if (!value) {
                    status = false;
                    // break the each
                    return false;
                }
            });
            return status;
        },

        /**
         * Return true if the ajax field is validated
         * @param {String} fieldid
         * @param {Object} options
         * @return true, if validation passed, false if false or doesn't exist
         */
        _checkAjaxFieldStatus: function(fieldid, options) {
            return options.ajaxValidCache[fieldid] == true;
        },
        /**
         * Validates form fields, shows prompts accordingly
         *
         * @param {jqObject}
            *            form
         * @param {skipAjaxFieldValidation}
            *            boolean - when set to true, ajax field validation is skipped, typically used when the submit button is clicked
         *
         * @return true if form is valid, false if not, undefined if ajax form validation is done
         */
        _validateFields: function(form) {
            var options = form.data('jqv');

            // this variable is set to true if an error is found
            var errorFound = false;

            // Trigger hook, start validation
            form.trigger("jqv.form.validating");
            // first, evaluate status of non ajax fields
            var first_err=null;
            form.find('['+options.validateAttribute+'*=validate]').not(":disabled").each( function() {
                var field = $(this);
                var names = [];
                if ($.inArray(field.attr('name'), names) < 0) {
                    errorFound |= methods._validateField(field, options);
                    if (errorFound && first_err==null)
                        if (field.is(":hidden") && options.prettySelect)
                            first_err = field = form.find("#" + options.usePrefix + methods._jqSelector(field.attr('id')) + options.useSuffix);
                        else
                            first_err=field;
                    if (options.doNotShowAllErrosOnSubmit)
                        return false;
                    names.push(field.attr('name'));

                    //if option set, stop checking validation rules after one error is found
                    if(options.showOneMessage == true && errorFound){
                        return false;
                    }
                }
            });

            // second, check to see if all ajax calls completed ok
            // errorFound |= !methods._checkAjaxStatus(options);

            // third, check status and scroll the container accordingly
            form.trigger("jqv.form.result", [errorFound]);

            if (errorFound) {
                if (options.scroll) {
                    var destination=first_err.offset().top;
                    var fixleft = first_err.offset().left;

                    //prompt positioning adjustment support. Usage: positionType:Xshift,Yshift (for ex.: bottomLeft:+20 or bottomLeft:-20,+10)
                    var positionType=options.promptPosition;
                    if (typeof(positionType)=='string' && positionType.indexOf(":")!=-1)
                        positionType=positionType.substring(0,positionType.indexOf(":"));

                    if (positionType!="bottomRight" && positionType!="bottomLeft") {
                        var prompt_err= methods._getPrompt(first_err);
                        if (prompt_err) {
                            destination=prompt_err.offset().top;
                        }
                    }

                    // Offset the amount the page scrolls by an amount in px to accomodate fixed elements at top of page
                    if (options.scrollOffset) {
                        destination -= options.scrollOffset;
                    }

                    // get the position of the first error, there should be at least one, no need to check this
                    //var destination = form.find(".formError:not('.greenPopup'):first").offset().top;
                    if (options.isOverflown) {
                        var overflowDIV = $(options.overflownDIV);
                        if(!overflowDIV.length) return false;
                        var scrollContainerScroll = overflowDIV.scrollTop();
                        var scrollContainerPos = -parseInt(overflowDIV.offset().top);

                        destination += scrollContainerScroll + scrollContainerPos - 5;
                        var scrollContainer = $(options.overflownDIV + ":not(:animated)");

                        scrollContainer.animate({ scrollTop: destination }, 1100, function(){
                            if(options.focusFirstField) first_err.focus();
                        });

                    } else {
                        $("html, body").animate({
                            scrollTop: destination
                        }, 1100, function(){
                            if(options.focusFirstField) first_err.focus();
                        });
                        $("html, body").animate({scrollLeft: fixleft},1100)
                    }

                } else if(options.focusFirstField)
                    first_err.focus();
                return false;
            }
            return true;
        },
        /**
         * This method is called to perform an ajax form validation.
         * During this process all the (field, value) pairs are sent to the server which returns a list of invalid fields or true
         *
         * @param {jqObject} form
         * @param {Map} options
         */
        _validateFormWithAjax: function(form, options) {

            var data = form.serialize();
            var type = (options.ajaxFormValidationMethod) ? options.ajaxFormValidationMethod : "GET";
            var url = (options.ajaxFormValidationURL) ? options.ajaxFormValidationURL : form.attr("action");
            var dataType = (options.dataType) ? options.dataType : "json";
            $.ajax({
                type: type,
                url: url,
                cache: false,
                dataType: dataType,
                data: data,
                form: form,
                methods: methods,
                options: options,
                beforeSend: function() {
                    return options.onBeforeAjaxFormValidation(form, options);
                },
                error: function(data, transport) {
                    methods._ajaxError(data, transport);
                },
                success: function(json) {
                    if ((dataType == "json") && (json !== true)) {
                        // getting to this case doesn't necessary means that the form is invalid
                        // the server may return green or closing prompt actions
                        // this flag helps figuring it out
                        var errorInForm=false;
                        for (var i = 0; i < json.length; i++) {
                            var value = json[i];

                            var errorFieldId = value[0];
                            var errorField = $($("#" + errorFieldId)[0]);

                            // make sure we found the element
                            if (errorField.length == 1) {

                                // promptText or selector
                                var msg = value[2];
                                // if the field is valid
                                if (value[1] == true) {

                                    if (msg == ""  || !msg){
                                        // if for some reason, status==true and error="", just close the prompt
                                        methods._closePrompt(errorField);
                                    } else {
                                        // the field is valid, but we are displaying a green prompt
                                        if (options.allrules[msg]) {
                                            var txt = options.allrules[msg].alertTextOk;
                                            if (txt)
                                                msg = txt;
                                        }
                                        if (options.showPrompts) methods._showPrompt(errorField, msg, "pass", false, options, true);
                                    }
                                } else {
                                    // the field is invalid, show the red error prompt
                                    errorInForm|=true;
                                    if (options.allrules[msg]) {
                                        var txt = options.allrules[msg].alertText;
                                        if (txt)
                                            msg = txt;
                                    }
                                    if(options.showPrompts) methods._showPrompt(errorField, msg, "", false, options, true);
                                }
                            }
                        }
                        options.onAjaxFormComplete(!errorInForm, form, json, options);
                    } else
                        options.onAjaxFormComplete(true, form, json, options);

                }
            });

        },
        /**
         * Validates field, shows prompts accordingly
         *
         * @param {jqObject}
            *            field
         * @param {Array[String]}
            *            field's validation rules
         * @param {Map}
            *            user options
         * @return false if field is valid (It is inversed for *fields*, it return false on validate and true on errors.)
         */
        _validateField: function(field, options, skipAjaxValidation) {
            if (!field.attr("id")) {
                field.attr("id", "form-validation-field-" + $.validationEngine.fieldIdCounter);
                ++$.validationEngine.fieldIdCounter;
            }

            if (!options.validateNonVisibleFields && (field.is(":hidden") && !options.prettySelect || field.parent().is(":hidden")))
                return false;

            var rulesParsing = field.attr(options.validateAttribute);
            var getRules = /validate\[(.*)\]/.exec(rulesParsing);

            if (!getRules)
                return false;
            var str = getRules[1];
            var rules = str.split(/\[|,|\]/);

            // true if we ran the ajax validation, tells the logic to stop messing with prompts
            var isAjaxValidator = false;
            var fieldName = field.attr("name");
            var promptText = "";
            var promptType = "";
            var required = false;
            var limitErrors = false;
            options.isError = false;
            options.showArrow = false;

            // If the programmer wants to limit the amount of error messages per field,
            if (options.maxErrorsPerField > 0) {
                limitErrors = true;
            }

            var form = $(field.closest("form, .validationEngineContainer"));
            // Fix for adding spaces in the rules
            for (var i = 0; i < rules.length; i++) {
                rules[i] = rules[i].replace(" ", "");
                // Remove any parsing errors
                if (rules[i] === '') {
                    delete rules[i];
                }
            }

            for (var i = 0, field_errors = 0; i < rules.length; i++) {

                // If we are limiting errors, and have hit the max, break
                if (limitErrors && field_errors >= options.maxErrorsPerField) {
                    // If we haven't hit a required yet, check to see if there is one in the validation rules for this
                    // field and that it's index is greater or equal to our current index
                    if (!required) {
                        var have_required = $.inArray('required', rules);
                        required = (have_required != -1 &&  have_required >= i);
                    }
                    break;
                }


                var errorMsg = undefined;
                switch (rules[i]) {

                    case "required":
                        required = true;
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._required);
                        break;
                    case "custom":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._custom);
                        break;
                    case "groupRequired":
                        // Check is its the first of group, if not, reload validation with first field
                        // AND continue normal validation on present field
                        var classGroup = "["+options.validateAttribute+"*=" +rules[i + 1] +"]";
                        var firstOfGroup = form.find(classGroup).eq(0);
                        if(firstOfGroup[0] != field[0]){

                            methods._validateField(firstOfGroup, options, skipAjaxValidation);
                            options.showArrow = true;

                        }
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._groupRequired);
                        if(errorMsg)  required = true;
                        options.showArrow = false;
                        break;
                    case "ajax":
                        // AJAX defaults to returning it's loading message
                        errorMsg = methods._ajax(field, rules, i, options);
                        if (errorMsg) {
                            promptType = "load";
                        }
                        break;
                    case "minSize":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._minSize);
                        break;
                    case "maxSize":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._maxSize);
                        break;
                    case "min":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._min);
                        break;
                    case "max":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._max);
                        break;
                    case "past":
                        errorMsg = methods._getErrorMessage(form, field,rules[i], rules, i, options, methods._past);
                        break;
                    case "future":
                        errorMsg = methods._getErrorMessage(form, field,rules[i], rules, i, options, methods._future);
                        break;
                    case "dateRange":
                        var classGroup = "["+options.validateAttribute+"*=" + rules[i + 1] + "]";
                        options.firstOfGroup = form.find(classGroup).eq(0);
                        options.secondOfGroup = form.find(classGroup).eq(1);

                        //if one entry out of the pair has value then proceed to run through validation
                        if (options.firstOfGroup[0].value || options.secondOfGroup[0].value) {
                            errorMsg = methods._getErrorMessage(form, field,rules[i], rules, i, options, methods._dateRange);
                        }
                        if (errorMsg) required = true;
                        options.showArrow = false;
                        break;

                    case "dateTimeRange":
                        var classGroup = "["+options.validateAttribute+"*=" + rules[i + 1] + "]";
                        options.firstOfGroup = form.find(classGroup).eq(0);
                        options.secondOfGroup = form.find(classGroup).eq(1);

                        //if one entry out of the pair has value then proceed to run through validation
                        if (options.firstOfGroup[0].value || options.secondOfGroup[0].value) {
                            errorMsg = methods._getErrorMessage(form, field,rules[i], rules, i, options, methods._dateTimeRange);
                        }
                        if (errorMsg) required = true;
                        options.showArrow = false;
                        break;
                    case "maxCheckbox":
                        field = $(form.find("input[name='" + fieldName + "']"));
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._maxCheckbox);
                        break;
                    case "minCheckbox":
                        field = $(form.find("input[name='" + fieldName + "']"));
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._minCheckbox);
                        break;
                    case "equals":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._equals);
                        break;
                    case "funcCall":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._funcCall);
                        break;
                    case "creditCard":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._creditCard);
                        break;
                    case "condRequired":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._condRequired);
                        if (errorMsg !== undefined) {
                            required = true;
                        }
                        break;

                    default:
                }

                var end_validation = false;

                // If we were passed back an message object, check what the status was to determine what to do
                if (typeof errorMsg == "object") {
                    switch (errorMsg.status) {
                        case "_break":
                            end_validation = true;
                            break;
                        // If we have an error message, set errorMsg to the error message
                        case "_error":
                            errorMsg = errorMsg.message;
                            break;
                        // If we want to throw an error, but not show a prompt, return early with true
                        case "_error_no_prompt":
                            return true;
                            break;
                        // Anything else we continue on
                        default:
                            break;
                    }
                }

                // If it has been specified that validation should end now, break
                if (end_validation) {
                    break;
                }

                // If we have a string, that means that we have an error, so add it to the error message.
                if (typeof errorMsg == 'string') {
                    promptText += errorMsg + "<br/>";
                    options.isError = true;
                    field_errors++;
                }
            }
            // If the rules required is not added, an empty field is not validated
            if(!required && !(field.val()) && field.val().length < 1) options.isError = false;

            // Hack for radio/checkbox group button, the validation go into the
            // first radio/checkbox of the group
            var fieldType = field.prop("type");
            var positionType=field.data("promptPosition") || options.promptPosition;

            if ((fieldType == "radio" || fieldType == "checkbox") && form.find("input[name='" + fieldName + "']").size() > 1) {
                if(positionType === 'inline') {
                    field = $(form.find("input[name='" + fieldName + "'][type!=hidden]:last"));
                } else {
                    field = $(form.find("input[name='" + fieldName + "'][type!=hidden]:first"));
                }
                options.showArrow = false;
            }

            if(field.is(":hidden") && options.prettySelect) {
                field = form.find("#" + options.usePrefix + methods._jqSelector(field.attr('id')) + options.useSuffix);
            }

            if (options.isError && options.showPrompts){
                methods._showPrompt(field, promptText, promptType, false, options);
            }else{
                if (!isAjaxValidator) methods._closePrompt(field);
            }

            if (!isAjaxValidator) {
                field.trigger("jqv.field.result", [field, options.isError, promptText]);
            }

            /* Record error */
            var errindex = $.inArray(field[0], options.InvalidFields);
            if (errindex == -1) {
                if (options.isError)
                    options.InvalidFields.push(field[0]);
            } else if (!options.isError) {
                options.InvalidFields.splice(errindex, 1);
            }

            methods._handleStatusCssClasses(field, options);

            /* run callback function for each field */
            if (options.isError && options.onFieldFailure)
                options.onFieldFailure(field);

            if (!options.isError && options.onFieldSuccess)
                options.onFieldSuccess(field);

            return options.isError;
        },
        /**
         * Handling css classes of fields indicating result of validation
         *
         * @param {jqObject}
            *            field
         * @param {Array[String]}
            *            field's validation rules
         * @private
         */
        _handleStatusCssClasses: function(field, options) {
            /* remove all classes */
            if(options.addSuccessCssClassToField)
                field.removeClass(options.addSuccessCssClassToField);

            if(options.addFailureCssClassToField)
                field.removeClass(options.addFailureCssClassToField);

            /* Add classes */
            if (options.addSuccessCssClassToField && !options.isError)
                field.addClass(options.addSuccessCssClassToField);

            if (options.addFailureCssClassToField && options.isError)
                field.addClass(options.addFailureCssClassToField);
        },

        /********************
         * _getErrorMessage
         *
         * @param form
         * @param field
         * @param rule
         * @param rules
         * @param i
         * @param options
         * @param originalValidationMethod
         * @return {*}
         * @private
         */
        _getErrorMessage:function (form, field, rule, rules, i, options, originalValidationMethod) {
            // If we are using the custon validation type, build the index for the rule.
            // Otherwise if we are doing a function call, make the call and return the object
            // that is passed back.
            var rule_index = jQuery.inArray(rule, rules);
            if (rule === "custom" || rule === "funcCall") {
                var custom_validation_type = rules[rule_index + 1];
                rule = rule + "[" + custom_validation_type + "]";
                // Delete the rule from the rules array so that it doesn't try to call the
                // same rule over again
                delete(rules[rule_index]);
            }
            // Change the rule to the composite rule, if it was different from the original
            var alteredRule = rule;


            var element_classes = (field.attr("data-validation-engine")) ? field.attr("data-validation-engine") : field.attr("class");
            var element_classes_array = element_classes.split(" ");

            // Call the original validation method. If we are dealing with dates or checkboxes, also pass the form
            var errorMsg;
            if (rule == "future" || rule == "past"  || rule == "maxCheckbox" || rule == "minCheckbox") {
                errorMsg = originalValidationMethod(form, field, rules, i, options);
            } else {
                errorMsg = originalValidationMethod(field, rules, i, options);
            }

            // If the original validation method returned an error and we have a custom error message,
            // return the custom message instead. Otherwise return the original error message.
            if (errorMsg != undefined) {
                var custom_message = methods._getCustomErrorMessage($(field), element_classes_array, alteredRule, options);
                if (custom_message) errorMsg = custom_message;
            }
            return errorMsg;

        },
        _getCustomErrorMessage:function (field, classes, rule, options) {
            var custom_message = false;
            var validityProp = methods._validityProp[rule];
            // If there is a validityProp for this rule, check to see if the field has an attribute for it
            if (validityProp != undefined) {
                custom_message = field.attr("data-errormessage-"+validityProp);
                // If there was an error message for it, return the message
                if (custom_message != undefined)
                    return custom_message;
            }
            custom_message = field.attr("data-errormessage");
            // If there is an inline custom error message, return it
            if (custom_message != undefined)
                return custom_message;
            var id = '#' + field.attr("id");
            // If we have custom messages for the element's id, get the message for the rule from the id.
            // Otherwise, if we have custom messages for the element's classes, use the first class message we find instead.
            if (typeof options.custom_error_messages[id] != "undefined" &&
                typeof options.custom_error_messages[id][rule] != "undefined" ) {
                custom_message = options.custom_error_messages[id][rule]['message'];
            } else if (classes.length > 0) {
                for (var i = 0; i < classes.length && classes.length > 0; i++) {
                    var element_class = "." + classes[i];
                    if (typeof options.custom_error_messages[element_class] != "undefined" &&
                        typeof options.custom_error_messages[element_class][rule] != "undefined") {
                        custom_message = options.custom_error_messages[element_class][rule]['message'];
                        break;
                    }
                }
            }
            if (!custom_message &&
                typeof options.custom_error_messages[rule] != "undefined" &&
                typeof options.custom_error_messages[rule]['message'] != "undefined"){
                custom_message = options.custom_error_messages[rule]['message'];
            }
            return custom_message;
        },
        _validityProp: {
            "required": "value-missing",
            "custom": "custom-error",
            "groupRequired": "value-missing",
            "ajax": "custom-error",
            "minSize": "range-underflow",
            "maxSize": "range-overflow",
            "min": "range-underflow",
            "max": "range-overflow",
            "past": "type-mismatch",
            "future": "type-mismatch",
            "dateRange": "type-mismatch",
            "dateTimeRange": "type-mismatch",
            "maxCheckbox": "range-overflow",
            "minCheckbox": "range-underflow",
            "equals": "pattern-mismatch",
            "funcCall": "custom-error",
            "creditCard": "pattern-mismatch",
            "condRequired": "value-missing"
        },
        /**
         * Required validation
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @param {bool} condRequired flag when method is used for internal purpose in condRequired check
         * @return an error string if validation failed
         */
        _required: function(field, rules, i, options, condRequired) {
            switch (field.prop("type")) {
                case "text":
                case "password":
                case "textarea":
                case "file":
                case "select-one":
                case "select-multiple":
                default:
                    var field_val      = $.trim( field.val()                               );
                    var dv_placeholder = $.trim( field.attr("data-validation-placeholder") );
                    var placeholder    = $.trim( field.attr("placeholder")                 );
                    if (
                        ( !field_val                                    )
                            || ( dv_placeholder && field_val == dv_placeholder )
                            || ( placeholder    && field_val == placeholder    )
                        ) {
                        return options.allrules[rules[i]].alertText;
                    }
                    break;
                case "radio":
                case "checkbox":
                    // new validation style to only check dependent field
                    if (condRequired) {
                        if (!field.attr('checked')) {
                            return options.allrules[rules[i]].alertTextCheckboxMultiple;
                        }
                        break;
                    }
                    // old validation style
                    var form = field.closest("form, .validationEngineContainer");
                    var name = field.attr("name");
                    if (form.find("input[name='" + name + "']:checked").size() == 0) {
                        if (form.find("input[name='" + name + "']:visible").size() == 1)
                            return options.allrules[rules[i]].alertTextCheckboxe;
                        else
                            return options.allrules[rules[i]].alertTextCheckboxMultiple;
                    }
                    break;
            }
        },
        /**
         * Validate that 1 from the group field is required
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _groupRequired: function(field, rules, i, options) {
            var classGroup = "["+options.validateAttribute+"*=" +rules[i + 1] +"]";
            var isValid = false;
            // Check all fields from the group
            field.closest("form, .validationEngineContainer").find(classGroup).each(function(){
                if(!methods._required($(this), rules, i, options)){
                    isValid = true;
                    return false;
                }
            });

            if(!isValid) {
                return options.allrules[rules[i]].alertText;
            }
        },
        /**
         * Validate rules
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _custom: function(field, rules, i, options) {
            var customRule = rules[i + 1];
            var rule = options.allrules[customRule];
            var fn;
            if(!rule) {
                alert("jqv:custom rule not found - "+customRule);
                return;
            }

            if(rule["regex"]) {
                var ex=rule.regex;
                if(!ex) {
                    alert("jqv:custom regex not found - "+customRule);
                    return;
                }
                var pattern = new RegExp(ex);

                if (!pattern.test(field.val())) return options.allrules[customRule].alertText;

            } else if(rule["func"]) {
                fn = rule["func"];

                if (typeof(fn) !== "function") {
                    alert("jqv:custom parameter 'function' is no function - "+customRule);
                    return;
                }

                if (!fn(field, rules, i, options))
                    return options.allrules[customRule].alertText;
            } else {
                alert("jqv:custom type not allowed "+customRule);
                return;
            }
        },
        /**
         * Validate custom function outside of the engine scope
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _funcCall: function(field, rules, i, options) {
            var functionName = rules[i + 1];
            var fn;
            if(functionName.indexOf('.') >-1)
            {
                var namespaces = functionName.split('.');
                var scope = window;
                while(namespaces.length)
                {
                    scope = scope[namespaces.shift()];
                }
                fn = scope;
            }
            else
                fn = window[functionName] || options.customFunctions[functionName];
            if (typeof(fn) == 'function')
                return fn(field, rules, i, options);

        },
        /**
         * Field match
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _equals: function(field, rules, i, options) {
            var equalsField = rules[i + 1];

            if (field.val() != $("#" + equalsField).val())
                return options.allrules.equals.alertText;
        },
        /**
         * Check the maximum size (in characters)
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _maxSize: function(field, rules, i, options) {
            var max = rules[i + 1];
            var len = field.val().length;

            if (len > max) {
                var rule = options.allrules.maxSize;
                return rule.alertText + max + rule.alertText2;
            }
        },
        /**
         * Check the minimum size (in characters)
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _minSize: function(field, rules, i, options) {
            var min = rules[i + 1];
            var len = field.val().length;

            if (len < min) {
                var rule = options.allrules.minSize;
                return rule.alertText + min + rule.alertText2;
            }
        },
        /**
         * Check number minimum value
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _min: function(field, rules, i, options) {
            var min = parseFloat(rules[i + 1]);
            var len = parseFloat(field.val());

            if (len < min) {
                var rule = options.allrules.min;
                if (rule.alertText2) return rule.alertText + min + rule.alertText2;
                return rule.alertText + min;
            }
        },
        /**
         * Check number maximum value
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _max: function(field, rules, i, options) {
            var max = parseFloat(rules[i + 1]);
            var len = parseFloat(field.val());

            if (len >max ) {
                var rule = options.allrules.max;
                if (rule.alertText2) return rule.alertText + max + rule.alertText2;
                //orefalo: to review, also do the translations
                return rule.alertText + max;
            }
        },
        /**
         * Checks date is in the past
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _past: function(form, field, rules, i, options) {

            var p=rules[i + 1];
            var fieldAlt = $(form.find("input[name='" + p.replace(/^#+/, '') + "']"));
            var pdate;

            if (p.toLowerCase() == "now") {
                pdate = new Date();
            } else if (undefined != fieldAlt.val()) {
                if (fieldAlt.is(":disabled"))
                    return;
                pdate = methods._parseDate(fieldAlt.val());
            } else {
                pdate = methods._parseDate(p);
            }
            var vdate = methods._parseDate(field.val());

            if (vdate > pdate ) {
                var rule = options.allrules.past;
                if (rule.alertText2) return rule.alertText + methods._dateToString(pdate) + rule.alertText2;
                return rule.alertText + methods._dateToString(pdate);
            }
        },
        /**
         * Checks date is in the future
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _future: function(form, field, rules, i, options) {

            var p=rules[i + 1];
            var fieldAlt = $(form.find("input[name='" + p.replace(/^#+/, '') + "']"));
            var pdate;

            if (p.toLowerCase() == "now") {
                pdate = new Date();
            } else if (undefined != fieldAlt.val()) {
                if (fieldAlt.is(":disabled"))
                    return;
                pdate = methods._parseDate(fieldAlt.val());
            } else {
                pdate = methods._parseDate(p);
            }
            var vdate = methods._parseDate(field.val());

            if (vdate < pdate ) {
                var rule = options.allrules.future;
                if (rule.alertText2)
                    return rule.alertText + methods._dateToString(pdate) + rule.alertText2;
                return rule.alertText + methods._dateToString(pdate);
            }
        },
        /**
         * Checks if valid date
         *
         * @param {string} date string
         * @return a bool based on determination of valid date
         */
        _isDate: function (value) {
            var dateRegEx = new RegExp(/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(?:(?:0?[1-9]|1[0-2])(\/|-)(?:0?[1-9]|1\d|2[0-8]))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(0?2(\/|-)29)(\/|-)(?:(?:0[48]00|[13579][26]00|[2468][048]00)|(?:\d\d)?(?:0[48]|[2468][048]|[13579][26]))$/);
            return dateRegEx.test(value);
        },
        /**
         * Checks if valid date time
         *
         * @param {string} date string
         * @return a bool based on determination of valid date time
         */
        _isDateTime: function (value){
            var dateTimeRegEx = new RegExp(/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1}$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^((1[012]|0?[1-9]){1}\/(0?[1-9]|[12][0-9]|3[01]){1}\/\d{2,4}\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1})$/);
            return dateTimeRegEx.test(value);
        },
        //Checks if the start date is before the end date
        //returns true if end is later than start
        _dateCompare: function (start, end) {
            return (new Date(start.toString()) < new Date(end.toString()));
        },
        /**
         * Checks date range
         *
         * @param {jqObject} first field name
         * @param {jqObject} second field name
         * @return an error string if validation failed
         */
        _dateRange: function (field, rules, i, options) {
            //are not both populated
            if ((!options.firstOfGroup[0].value && options.secondOfGroup[0].value) || (options.firstOfGroup[0].value && !options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }

            //are not both dates
            if (!methods._isDate(options.firstOfGroup[0].value) || !methods._isDate(options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }

            //are both dates but range is off
            if (!methods._dateCompare(options.firstOfGroup[0].value, options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }
        },
        /**
         * Checks date time range
         *
         * @param {jqObject} first field name
         * @param {jqObject} second field name
         * @return an error string if validation failed
         */
        _dateTimeRange: function (field, rules, i, options) {
            //are not both populated
            if ((!options.firstOfGroup[0].value && options.secondOfGroup[0].value) || (options.firstOfGroup[0].value && !options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }
            //are not both dates
            if (!methods._isDateTime(options.firstOfGroup[0].value) || !methods._isDateTime(options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }
            //are both dates but range is off
            if (!methods._dateCompare(options.firstOfGroup[0].value, options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }
        },
        /**
         * Max number of checkbox selected
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _maxCheckbox: function(form, field, rules, i, options) {

            var nbCheck = rules[i + 1];
            var groupname = field.attr("name");
            var groupSize = form.find("input[name='" + groupname + "']:checked").size();
            if (groupSize > nbCheck) {
                options.showArrow = false;
                if (options.allrules.maxCheckbox.alertText2)
                    return options.allrules.maxCheckbox.alertText + " " + nbCheck + " " + options.allrules.maxCheckbox.alertText2;
                return options.allrules.maxCheckbox.alertText;
            }
        },
        /**
         * Min number of checkbox selected
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _minCheckbox: function(form, field, rules, i, options) {

            var nbCheck = rules[i + 1];
            var groupname = field.attr("name");
            var groupSize = form.find("input[name='" + groupname + "']:checked").size();
            if (groupSize < nbCheck) {
                options.showArrow = false;
                return options.allrules.minCheckbox.alertText + " " + nbCheck + " " + options.allrules.minCheckbox.alertText2;
            }
        },
        /**
         * Checks that it is a valid credit card number according to the
         * Luhn checksum algorithm.
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return an error string if validation failed
         */
        _creditCard: function(field, rules, i, options) {
            //spaces and dashes may be valid characters, but must be stripped to calculate the checksum.
            var valid = false, cardNumber = field.val().replace(/ +/g, '').replace(/-+/g, '');

            var numDigits = cardNumber.length;
            if (numDigits >= 14 && numDigits <= 16 && parseInt(cardNumber) > 0) {

                var sum = 0, i = numDigits - 1, pos = 1, digit, luhn = new String();
                do {
                    digit = parseInt(cardNumber.charAt(i));
                    luhn += (pos++ % 2 == 0) ? digit * 2 : digit;
                } while (--i >= 0)

                for (i = 0; i < luhn.length; i++) {
                    sum += parseInt(luhn.charAt(i));
                }
                valid = sum % 10 == 0;
            }
            if (!valid) return options.allrules.creditCard.alertText;
        },
        /**
         * Ajax field validation
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            *            user options
         * @return nothing! the ajax validator handles the prompts itself
         */
        _ajax: function(field, rules, i, options) {

            var errorSelector = rules[i + 1];
            var rule = options.allrules[errorSelector];
            var extraData = rule.extraData;
            var extraDataDynamic = rule.extraDataDynamic;
            var data = {
                "fieldId" : field.attr("id"),
                "fieldValue" : field.val()
            };

            if (typeof extraData === "object") {
                $.extend(data, extraData);
            } else if (typeof extraData === "string") {
                var tempData = extraData.split("&");
                for(var i = 0; i < tempData.length; i++) {
                    var values = tempData[i].split("=");
                    if (values[0] && values[0]) {
                        data[values[0]] = values[1];
                    }
                }
            }

            if (extraDataDynamic) {
                var tmpData = [];
                var domIds = String(extraDataDynamic).split(",");
                for (var i = 0; i < domIds.length; i++) {
                    var id = domIds[i];
                    if ($(id).length) {
                        var inputValue = field.closest("form, .validationEngineContainer").find(id).val();
                        var keyValue = id.replace('#', '') + '=' + escape(inputValue);
                        data[id.replace('#', '')] = inputValue;
                    }
                }
            }

            // If a field change event triggered this we want to clear the cache for this ID
            if (options.eventTrigger == "field") {
                delete(options.ajaxValidCache[field.attr("id")]);
            }

            // If there is an error or if the the field is already validated, do not re-execute AJAX
            if (!options.isError && !methods._checkAjaxFieldStatus(field.attr("id"), options)) {
                $.ajax({
                    type: options.ajaxFormValidationMethod,
                    url: rule.url,
                    cache: false,
                    dataType: "json",
                    data: data,
                    field: field,
                    rule: rule,
                    methods: methods,
                    options: options,
                    beforeSend: function() {},
                    error: function(data, transport) {
                        methods._ajaxError(data, transport);
                    },
                    success: function(json) {

                        // asynchronously called on success, data is the json answer from the server
                        var errorFieldId = json[0];
                        //var errorField = $($("#" + errorFieldId)[0]);
                        var errorField = $("#"+ errorFieldId).eq(0);

                        // make sure we found the element
                        if (errorField.length == 1) {
                            var status = json[1];
                            // read the optional msg from the server
                            var msg = json[2];
                            if (!status) {
                                // Houston we got a problem - display an red prompt
                                options.ajaxValidCache[errorFieldId] = false;
                                options.isError = true;

                                // resolve the msg prompt
                                if(msg) {
                                    if (options.allrules[msg]) {
                                        var txt = options.allrules[msg].alertText;
                                        if (txt) {
                                            msg = txt;
                                        }
                                    }
                                }
                                else
                                    msg = rule.alertText;

                                if (options.showPrompts) methods._showPrompt(errorField, msg, "", true, options);
                            } else {
                                options.ajaxValidCache[errorFieldId] = true;

                                // resolves the msg prompt
                                if(msg) {
                                    if (options.allrules[msg]) {
                                        var txt = options.allrules[msg].alertTextOk;
                                        if (txt) {
                                            msg = txt;
                                        }
                                    }
                                }
                                else
                                    msg = rule.alertTextOk;

                                if (options.showPrompts) {
                                    // see if we should display a green prompt
                                    if (msg)
                                        methods._showPrompt(errorField, msg, "pass", true, options);
                                    else
                                        methods._closePrompt(errorField);
                                }

                                // If a submit form triggered this, we want to re-submit the form
                                if (options.eventTrigger == "submit")
                                    field.closest("form").submit();
                            }
                        }
                        errorField.trigger("jqv.field.result", [errorField, options.isError, msg]);
                    }
                });

                return rule.alertTextLoad;
            }
        },
        /**
         * Common method to handle ajax errors
         *
         * @param {Object} data
         * @param {Object} transport
         */
        _ajaxError: function(data, transport) {
            if(data.status == 0 && transport == null)
                alert("The page is not served from a server! ajax call failed");
            else if(typeof console != "undefined")
                console.log("Ajax error: " + data.status + " " + transport);
        },
        /**
         * date -> string
         *
         * @param {Object} date
         */
        _dateToString: function(date) {
            return date.getFullYear()+"-"+(date.getMonth()+1)+"-"+date.getDate();
        },
        /**
         * Parses an ISO date
         * @param {String} d
         */
        _parseDate: function(d) {

            var dateParts = d.split("-");
            if(dateParts==d)
                dateParts = d.split("/");
            if(dateParts==d) {
                dateParts = d.split(".");
                return new Date(dateParts[2], (dateParts[1] - 1), dateParts[0]);
            }
            return new Date(dateParts[0], (dateParts[1] - 1) ,dateParts[2]);
        },
        /**
         * Builds or updates a prompt with the given information
         *
         * @param {jqObject} field
         * @param {String} promptText html text to display type
         * @param {String} type the type of bubble: 'pass' (green), 'load' (black) anything else (red)
         * @param {boolean} ajaxed - use to mark fields than being validated with ajax
         * @param {Map} options user options
         */
        _showPrompt: function(field, promptText, type, ajaxed, options, ajaxform) {
            var prompt = methods._getPrompt(field);
            // The ajax submit errors are not see has an error in the form,
            // When the form errors are returned, the engine see 2 bubbles, but those are ebing closed by the engine at the same time
            // Because no error was found befor submitting
            if(ajaxform) prompt = false;
            // Check that there is indded text
            if($.trim(promptText)){
                if (prompt)
                    methods._updatePrompt(field, prompt, promptText, type, ajaxed, options);
                else
                    methods._buildPrompt(field, promptText, type, ajaxed, options);
            }
        },
        /**
         * Builds and shades a prompt for the given field.
         *
         * @param {jqObject} field
         * @param {String} promptText html text to display type
         * @param {String} type the type of bubble: 'pass' (green), 'load' (black) anything else (red)
         * @param {boolean} ajaxed - use to mark fields than being validated with ajax
         * @param {Map} options user options
         */
        _buildPrompt: function(field, promptText, type, ajaxed, options) {

            // create the prompt
            var prompt = $('<div>');
            prompt.addClass(methods._getClassName(field.attr("id")) + "formError");
            // add a class name to identify the parent form of the prompt
            prompt.addClass("parentForm"+methods._getClassName(field.closest('form, .validationEngineContainer').attr("id")));
            prompt.addClass("formError");

            switch (type) {
                case "pass":
                    prompt.addClass("greenPopup");
                    break;
                case "load":
                    prompt.addClass("blackPopup");
                    break;
                default:
                /* it has error  */
                //alert("unknown popup type:"+type);
            }
            if (ajaxed)
                prompt.addClass("ajaxed");

            // create the prompt content
            var promptContent = $('<div>').addClass("formErrorContent").html(promptText).appendTo(prompt);

            // determine position type
            var positionType=field.data("promptPosition") || options.promptPosition;

            // create the css arrow pointing at the field
            // note that there is no triangle on max-checkbox and radio
            if (options.showArrow) {
                var arrow = $('<div>').addClass("formErrorArrow");

                //prompt positioning adjustment support. Usage: positionType:Xshift,Yshift (for ex.: bottomLeft:+20 or bottomLeft:-20,+10)
                if (typeof(positionType)=='string')
                {
                    var pos=positionType.indexOf(":");
                    if(pos!=-1)
                        positionType=positionType.substring(0,pos);
                }

                switch (positionType) {
                    case "bottomLeft":
                    case "bottomRight":
                        prompt.find(".formErrorContent").before(arrow);
                        arrow.addClass("formErrorArrowBottom").html('<div class="line1"><!-- --></div><div class="line2"><!-- --></div><div class="line3"><!-- --></div><div class="line4"><!-- --></div><div class="line5"><!-- --></div><div class="line6"><!-- --></div><div class="line7"><!-- --></div><div class="line8"><!-- --></div><div class="line9"><!-- --></div><div class="line10"><!-- --></div>');
                        break;
                    case "topLeft":
                    case "topRight":
                        arrow.html('<div class="line10"><!-- --></div><div class="line9"><!-- --></div><div class="line8"><!-- --></div><div class="line7"><!-- --></div><div class="line6"><!-- --></div><div class="line5"><!-- --></div><div class="line4"><!-- --></div><div class="line3"><!-- --></div><div class="line2"><!-- --></div><div class="line1"><!-- --></div>');
                        prompt.append(arrow);
                        break;
                }
            }
            // Add custom prompt class
            if (options.addPromptClass)
                prompt.addClass(options.addPromptClass);

            // Add custom prompt class defined in element
            var requiredOverride = field.attr('data-required-class');
            if(requiredOverride !== undefined) {
                prompt.addClass(requiredOverride);
            } else {
                if(options.prettySelect) {
                    if($('#' + field.attr('id')).next().is('select')) {
                        var prettyOverrideClass = $('#' + field.attr('id').substr(options.usePrefix.length).substring(options.useSuffix.length)).attr('data-required-class');
                        if(prettyOverrideClass !== undefined) {
                            prompt.addClass(prettyOverrideClass);
                        }
                    }
                }
            }

            prompt.css({
                "opacity": 0
            });
            if(positionType === 'inline') {
                prompt.addClass("inline");
                if(typeof field.attr('data-prompt-target') !== 'undefined' && $('#'+field.attr('data-prompt-target')).length > 0) {
                    prompt.appendTo($('#'+field.attr('data-prompt-target')));
                } else {
                    field.after(prompt);
                }
            } else {
                field.before(prompt);
            }

            var pos = methods._calculatePosition(field, prompt, options);
            prompt.css({
                'position': positionType === 'inline' ? 'relative' : 'absolute',
                "top": pos.callerTopPosition,
                "left": pos.callerleftPosition,
                "marginTop": pos.marginTopSize,
                "opacity": 0
            }).data("callerField", field);


            if (options.autoHidePrompt) {
                setTimeout(function(){
                    prompt.animate({
                        "opacity": 0
                    },function(){
                        prompt.closest('.formErrorOuter').remove();
                        prompt.remove();
                    });
                }, options.autoHideDelay);
            }
            return prompt.animate({
                "opacity": 0.87
            });
        },
        /**
         * Updates the prompt text field - the field for which the prompt
         * @param {jqObject} field
         * @param {String} promptText html text to display type
         * @param {String} type the type of bubble: 'pass' (green), 'load' (black) anything else (red)
         * @param {boolean} ajaxed - use to mark fields than being validated with ajax
         * @param {Map} options user options
         */
        _updatePrompt: function(field, prompt, promptText, type, ajaxed, options, noAnimation) {

            if (prompt) {
                if (typeof type !== "undefined") {
                    if (type == "pass")
                        prompt.addClass("greenPopup");
                    else
                        prompt.removeClass("greenPopup");

                    if (type == "load")
                        prompt.addClass("blackPopup");
                    else
                        prompt.removeClass("blackPopup");
                }
                if (ajaxed)
                    prompt.addClass("ajaxed");
                else
                    prompt.removeClass("ajaxed");

                prompt.find(".formErrorContent").html(promptText);

                var pos = methods._calculatePosition(field, prompt, options);
                var css = {"top": pos.callerTopPosition,
                    "left": pos.callerleftPosition,
                    "marginTop": pos.marginTopSize};

                if (noAnimation)
                    prompt.css(css);
                else
                    prompt.animate(css);
            }
        },
        /**
         * Closes the prompt associated with the given field
         *
         * @param {jqObject}
            *            field
         */
        _closePrompt: function(field) {
            var prompt = methods._getPrompt(field);
            if (prompt)
                prompt.fadeTo("fast", 0, function() {
                    prompt.parent('.formErrorOuter').remove();
                    prompt.remove();
                });
        },
        closePrompt: function(field) {
            return methods._closePrompt(field);
        },
        /**
         * Returns the error prompt matching the field if any
         *
         * @param {jqObject}
            *            field
         * @return undefined or the error prompt (jqObject)
         */
        _getPrompt: function(field) {
            var formId = $(field).closest('form, .validationEngineContainer').attr('id');
            var className = methods._getClassName(field.attr("id")) + "formError";
            var match = $("." + methods._escapeExpression(className) + '.parentForm' + formId)[0];
            if (match)
                return $(match);
        },
        /**
         * Returns the escapade classname
         *
         * @param {selector}
            *            className
         */
        _escapeExpression: function (selector) {
            return selector.replace(/([#;&,\.\+\*\~':"\!\^$\[\]\(\)=>\|])/g, "\\$1");
        },
        /**
         * returns true if we are in a RTLed document
         *
         * @param {jqObject} field
         */
        isRTL: function(field)
        {
            var $document = $(document);
            var $body = $('body');
            var rtl =
                (field && field.hasClass('rtl')) ||
                    (field && (field.attr('dir') || '').toLowerCase()==='rtl') ||
                    $document.hasClass('rtl') ||
                    ($document.attr('dir') || '').toLowerCase()==='rtl' ||
                    $body.hasClass('rtl') ||
                    ($body.attr('dir') || '').toLowerCase()==='rtl';
            return Boolean(rtl);
        },
        /**
         * Calculates prompt position
         *
         * @param {jqObject}
            *            field
         * @param {jqObject}
            *            the prompt
         * @param {Map}
            *            options
         * @return positions
         */
        _calculatePosition: function (field, promptElmt, options) {

            var promptTopPosition, promptleftPosition, marginTopSize;
            var fieldWidth 	= field.width();
            var fieldLeft 	= field.position().left;
            var fieldTop 	=  field.position().top;
            var fieldHeight 	=  field.height();
            var promptHeight = promptElmt.height();


            // is the form contained in an overflown container?
            promptTopPosition = promptleftPosition = 0;
            // compensation for the arrow
            marginTopSize = -promptHeight;


            //prompt positioning adjustment support
            //now you can adjust prompt position
            //usage: positionType:Xshift,Yshift
            //for example:
            //   bottomLeft:+20 means bottomLeft position shifted by 20 pixels right horizontally
            //   topRight:20, -15 means topRight position shifted by 20 pixels to right and 15 pixels to top
            //You can use +pixels, - pixels. If no sign is provided than + is default.
            var positionType=field.data("promptPosition") || options.promptPosition;
            var shift1="";
            var shift2="";
            var shiftX=0;
            var shiftY=0;
            if (typeof(positionType)=='string') {
                //do we have any position adjustments ?
                if (positionType.indexOf(":")!=-1) {
                    shift1=positionType.substring(positionType.indexOf(":")+1);
                    positionType=positionType.substring(0,positionType.indexOf(":"));

                    //if any advanced positioning will be needed (percents or something else) - parser should be added here
                    //for now we use simple parseInt()

                    //do we have second parameter?
                    if (shift1.indexOf(",") !=-1) {
                        shift2=shift1.substring(shift1.indexOf(",") +1);
                        shift1=shift1.substring(0,shift1.indexOf(","));
                        shiftY=parseInt(shift2);
                        if (isNaN(shiftY)) shiftY=0;
                    };

                    shiftX=parseInt(shift1);
                    if (isNaN(shift1)) shift1=0;

                };
            };


            switch (positionType) {
                default:
                case "topRight":
                    promptleftPosition +=  fieldLeft + fieldWidth - 30;
                    promptTopPosition +=  fieldTop;
                    break;

                case "topLeft":
                    promptTopPosition +=  fieldTop;
                    promptleftPosition += fieldLeft;
                    break;

                case "centerRight":
                    promptTopPosition = fieldTop+4;
                    marginTopSize = 0;
                    promptleftPosition= fieldLeft + field.outerWidth(true)+5;
                    break;
                case "centerLeft":
                    promptleftPosition = fieldLeft - (promptElmt.width() + 2);
                    promptTopPosition = fieldTop+4;
                    marginTopSize = 0;

                    break;

                case "bottomLeft":
                    promptTopPosition = fieldTop + field.height() + 5;
                    marginTopSize = 0;
                    promptleftPosition = fieldLeft;
                    break;
                case "bottomRight":
                    promptleftPosition = fieldLeft + fieldWidth - 30;
                    promptTopPosition =  fieldTop +  field.height() + 5;
                    marginTopSize = 0;
                    break;
                case "inline":
                    promptleftPosition = 0;
                    promptTopPosition = 0;
                    marginTopSize = 0;
            };



            //apply adjusments if any
            promptleftPosition += shiftX;
            promptTopPosition  += shiftY;

            return {
                "callerTopPosition": promptTopPosition + "px",
                "callerleftPosition": promptleftPosition + "px",
                "marginTopSize": marginTopSize + "px"
            };
        },
        /**
         * Saves the user options and variables in the form.data
         *
         * @param {jqObject}
            *            form - the form where the user option should be saved
         * @param {Map}
            *            options - the user options
         * @return the user options (extended from the defaults)
         */
        _saveOptions: function(form, options) {

            // is there a language localisation ?
            if ($.validationEngineLanguage)
                var allRules = $.validationEngineLanguage.allRules;
            else
                $.error("jQuery.validationEngine rules are not loaded, plz add localization files to the page");
            // --- Internals DO NOT TOUCH or OVERLOAD ---
            // validation rules and i18
            $.validationEngine.defaults.allrules = allRules;

            var userOptions = $.extend(true,{},$.validationEngine.defaults,options);

            form.data('jqv', userOptions);
            return userOptions;
        },

        /**
         * Removes forbidden characters from class name
         * @param {String} className
         */
        _getClassName: function(className) {
            if(className)
                return className.replace(/:/g, "_").replace(/\./g, "_");
        },
        /**
         * Escape special character for jQuery selector
         * http://totaldev.com/content/escaping-characters-get-valid-jquery-id
         * @param {String} selector
         */
        _jqSelector: function(str){
            return str.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, '\\$1');
        },
        /**
         * Conditionally required field
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
            * user options
         * @return an error string if validation failed
         */
        _condRequired: function(field, rules, i, options) {
            var idx, dependingField;

            for(idx = (i + 1); idx < rules.length; idx++) {
                dependingField = jQuery("#" + rules[idx]).first();

                /* Use _required for determining wether dependingField has a value.
                 * There is logic there for handling all field types, and default value; so we won't replicate that here
                 * Indicate this special use by setting the last parameter to true so we only validate the dependingField on chackboxes and radio buttons (#462)
                 */
                if (dependingField.length && methods._required(dependingField, ["required"], 0, options, true) == undefined) {
                    /* We now know any of the depending fields has a value,
                     * so we can validate this field as per normal required code
                     */
                    return methods._required(field, ["required"], 0, options);
                }
            }
        },

        _submitButtonClick: function(event) {
            var button = $(this);
            var form = button.closest('form, .validationEngineContainer');
            form.data("jqv_submitButton", button.attr("id"));
        }
    };

    /**
     * Plugin entry point.
     * You may pass an action as a parameter or a list of options.
     * if none, the init and attach methods are being called.
     * Remember: if you pass options, the attached method is NOT called automatically
     *
     * @param {String}
        *            method (optional) action
     */
    $.fn.validationEngine = function(method) {

        var form = $(this);
        if(!form[0]) return form;  // stop here if the form does not exist

        if (typeof(method) == 'string' && method.charAt(0) != '_' && methods[method]) {

            // make sure init is called once
            if(method != "showPrompt" && method != "hide" && method != "hideAll")
                methods.init.apply(form);

            return methods[method].apply(form, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method == 'object' || !method) {

            // default constructor with or without arguments
            methods.init.apply(form, arguments);
            return methods.attach.apply(form);
        } else {
            $.error('Method ' + method + ' does not exist in jQuery.validationEngine');
        }
    };



    // LEAK GLOBAL OPTIONS
    $.validationEngine= {fieldIdCounter: 0,defaults:{

        // Name of the event triggering field validation
        validationEventTrigger: "blur",
        // Automatically scroll viewport to the first error
        scroll: true,
        // Focus on the first input
        focusFirstField:true,
        // Show prompts, set to false to disable prompts
        showPrompts: true,
        // Should we attempt to validate non-visible input fields contained in the form? (Useful in cases of tabbed containers, e.g. jQuery-UI tabs)
        validateNonVisibleFields: false,
        // Opening box position, possible locations are: topLeft,
        // topRight, bottomLeft, centerRight, bottomRight, inline
        // inline gets inserted after the validated field or into an element specified in data-prompt-target
        promptPosition: "topRight",
        bindMethod:"bind",
        // internal, automatically set to true when it parse a _ajax rule
        inlineAjax: false,
        // if set to true, the form data is sent asynchronously via ajax to the form.action url (get)
        ajaxFormValidation: false,
        // The url to send the submit ajax validation (default to action)
        ajaxFormValidationURL: false,
        // HTTP method used for ajax validation
        ajaxFormValidationMethod: 'get',
        // Ajax form validation callback method: boolean onComplete(form, status, errors, options)
        // retuns false if the form.submit event needs to be canceled.
        onAjaxFormComplete: $.noop,
        // called right before the ajax call, may return false to cancel
        onBeforeAjaxFormValidation: $.noop,
        // Stops form from submitting and execute function assiciated with it
        onValidationComplete: false,

        // Used when you have a form fields too close and the errors messages are on top of other disturbing viewing messages
        doNotShowAllErrosOnSubmit: false,
        // Object where you store custom messages to override the default error messages
        custom_error_messages:{},
        // true if you want to vind the input fields
        binded: true,
        // set to true, when the prompt arrow needs to be displayed
        showArrow: false,
        // did one of the validation fail ? kept global to stop further ajax validations
        isError: false,
        // Limit how many displayed errors a field can have
        maxErrorsPerField: false,

        // Caches field validation status, typically only bad status are created.
        // the array is used during ajax form validation to detect issues early and prevent an expensive submit
        ajaxValidCache: {},
        // Auto update prompt position after window resize
        autoPositionUpdate: false,

        InvalidFields: [],
        onFieldSuccess: false,
        onFieldFailure: false,
        onSuccess: false,
        onFailure: false,
        validateAttribute: "class",
        addSuccessCssClassToField: "",
        addFailureCssClassToField: "",

        // Auto-hide prompt
        autoHidePrompt: false,
        // Delay before auto-hide
        autoHideDelay: 10000,
        // Fade out duration while hiding the validations
        fadeDuration: 0.3,
        // Use Prettify select library
        prettySelect: false,
        // Add css class on prompt
        addPromptClass : "",
        // Custom ID uses prefix
        usePrefix: "",
        // Custom ID uses suffix
        useSuffix: "",
        // Only show one message per error prompt
        showOneMessage: false
    }};
    $(function(){$.validationEngine.defaults.promptPosition = methods.isRTL()?'topLeft':"topRight"});
})(jQuery);
(function() {
  var __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };

  this.CalendarEvent = (function() {

    function CalendarEvent(container, add) {
      var input;
      this.container = container;
      this.makeFullCalendarEventObject = __bind(this.makeFullCalendarEventObject, this);

      this.handleDoubleClick = __bind(this.handleDoubleClick, this);

      this.finalizeEvent = __bind(this.finalizeEvent, this);

      this.handleInputBlur = __bind(this.handleInputBlur, this);

      this.handleInputKeyup = __bind(this.handleInputKeyup, this);

      this.container.bind("dblclick", this.handleDoubleClick);
      if (add != null) {
        input = this.container.find("input");
        input.focus();
        input.bind("keyup", this.handleInputKeyup);
        input.bind("blur", this.handleInputBlur);
      } else {
        this.makeFullCalendarEventObject();
      }
    }

    CalendarEvent.prototype.handleInputKeyup = function(e) {
      var input;
      input = $(e.target);
      if (e.keyCode === 13) {
        if (input.val().length === 0) {
          return this.container.remove();
        } else {
          return this.finalizeEvent(input.val());
        }
      }
    };

    CalendarEvent.prototype.handleInputBlur = function(e) {
      var input;
      input = $(e.target);
      if (input.val().length === 0) {
        return this.container.remove();
      } else {
        return this.finalizeEvent(input.val());
      }
    };

    CalendarEvent.prototype.finalizeEvent = function(val) {
      this.container.find("a").html(val);
      return this.makeFullCalendarEventObject();
    };

    CalendarEvent.prototype.handleDoubleClick = function(e) {
      var input, link, oldval,
        _this = this;
      input = $("<input type='text'>");
      link = $(e.target);
      oldval = link.text();
      input.val(oldval);
      link.html(input);
      input.focus();
      input.bind("keyup", function(e) {
        if (e.keyCode === 13) {
          if (input.val().length > 0) {
            link.html(input.val());
            return _this.makeFullCalendarEventObject();
          } else {
            return link.html(oldval);
          }
        }
      });
      return input.bind("blur", function(e) {
        if (input.val().length > 0) {
          link.html(input.val());
          return _this.makeFullCalendarEventObject();
        } else {
          return link.html(oldval);
        }
      });
    };

    CalendarEvent.prototype.makeFullCalendarEventObject = function() {
      var eventObject, link;
      link = $(this.container);
      eventObject = {
        title: $.trim(link.text())
      };
      link.data('eventObject', eventObject);
      return link.draggable({
        zIndex: 999,
        revert: true,
        revertDuration: 0
      });
    };

    return CalendarEvent;

  })();

  this.CalendarEvents = (function() {

    function CalendarEvents(container) {
      this.container = container;
      this.handleAddLink = __bind(this.handleAddLink, this);

      this.addLink = this.container.find("#add-event");
      this.container.find("a.external-event").each(function() {
        return new CalendarEvent($(this));
      });
      this.template = "<li><a class='external-event'><input type='text'></a></li>";
      this.addLink.bind("click", this.handleAddLink);
    }

    CalendarEvents.prototype.handleAddLink = function() {
      var view;
      view = $(this.template);
      view.insertBefore(this.addLink.parent());
      return new CalendarEvent(view, true);
    };

    return CalendarEvents;

  })();

}).call(this);
// moment.js
// version : 2.0.0
// author : Tim Wood
// license : MIT
// momentjs.com
(function(e){function O(e,t){return function(n){return j(e.call(this,n),t)}}function M(e){return function(t){return this.lang().ordinal(e.call(this,t))}}function _(){}function D(e){H(this,e)}function P(e){var t=this._data={},n=e.years||e.year||e.y||0,r=e.months||e.month||e.M||0,i=e.weeks||e.week||e.w||0,s=e.days||e.day||e.d||0,o=e.hours||e.hour||e.h||0,u=e.minutes||e.minute||e.m||0,a=e.seconds||e.second||e.s||0,f=e.milliseconds||e.millisecond||e.ms||0;this._milliseconds=f+a*1e3+u*6e4+o*36e5,this._days=s+i*7,this._months=r+n*12,t.milliseconds=f%1e3,a+=B(f/1e3),t.seconds=a%60,u+=B(a/60),t.minutes=u%60,o+=B(u/60),t.hours=o%24,s+=B(o/24),s+=i*7,t.days=s%30,r+=B(s/30),t.months=r%12,n+=B(r/12),t.years=n}function H(e,t){for(var n in t)t.hasOwnProperty(n)&&(e[n]=t[n]);return e}function B(e){return e<0?Math.ceil(e):Math.floor(e)}function j(e,t){var n=e+"";while(n.length<t)n="0"+n;return n}function F(e,t,n){var r=t._milliseconds,i=t._days,s=t._months,o;r&&e._d.setTime(+e+r*n),i&&e.date(e.date()+i*n),s&&(o=e.date(),e.date(1).month(e.month()+s*n).date(Math.min(o,e.daysInMonth())))}function I(e){return Object.prototype.toString.call(e)==="[object Array]"}function q(e,t){var n=Math.min(e.length,t.length),r=Math.abs(e.length-t.length),i=0,s;for(s=0;s<n;s++)~~e[s]!==~~t[s]&&i++;return i+r}function R(e,t){return t.abbr=e,s[e]||(s[e]=new _),s[e].set(t),s[e]}function U(e){return e?(!s[e]&&o&&require("./lang/"+e),s[e]):t.fn._lang}function z(e){return e.match(/\[.*\]/)?e.replace(/^\[|\]$/g,""):e.replace(/\\/g,"")}function W(e){var t=e.match(a),n,r;for(n=0,r=t.length;n<r;n++)A[t[n]]?t[n]=A[t[n]]:t[n]=z(t[n]);return function(i){var s="";for(n=0;n<r;n++)s+=typeof t[n].call=="function"?t[n].call(i,e):t[n];return s}}function X(e,t){function r(t){return e.lang().longDateFormat(t)||t}var n=5;while(n--&&f.test(t))t=t.replace(f,r);return C[t]||(C[t]=W(t)),C[t](e)}function V(e){switch(e){case"DDDD":return p;case"YYYY":return d;case"YYYYY":return v;case"S":case"SS":case"SSS":case"DDD":return h;case"MMM":case"MMMM":case"dd":case"ddd":case"dddd":case"a":case"A":return m;case"X":return b;case"Z":case"ZZ":return g;case"T":return y;case"MM":case"DD":case"YY":case"HH":case"hh":case"mm":case"ss":case"M":case"D":case"d":case"H":case"h":case"m":case"s":return c;default:return new RegExp(e.replace("\\",""))}}function $(e,t,n){var r,i,s=n._a;switch(e){case"M":case"MM":s[1]=t==null?0:~~t-1;break;case"MMM":case"MMMM":r=U(n._l).monthsParse(t),r!=null?s[1]=r:n._isValid=!1;break;case"D":case"DD":case"DDD":case"DDDD":t!=null&&(s[2]=~~t);break;case"YY":s[0]=~~t+(~~t>68?1900:2e3);break;case"YYYY":case"YYYYY":s[0]=~~t;break;case"a":case"A":n._isPm=(t+"").toLowerCase()==="pm";break;case"H":case"HH":case"h":case"hh":s[3]=~~t;break;case"m":case"mm":s[4]=~~t;break;case"s":case"ss":s[5]=~~t;break;case"S":case"SS":case"SSS":s[6]=~~(("0."+t)*1e3);break;case"X":n._d=new Date(parseFloat(t)*1e3);break;case"Z":case"ZZ":n._useUTC=!0,r=(t+"").match(x),r&&r[1]&&(n._tzh=~~r[1]),r&&r[2]&&(n._tzm=~~r[2]),r&&r[0]==="+"&&(n._tzh=-n._tzh,n._tzm=-n._tzm)}t==null&&(n._isValid=!1)}function J(e){var t,n,r=[];if(e._d)return;for(t=0;t<7;t++)e._a[t]=r[t]=e._a[t]==null?t===2?1:0:e._a[t];r[3]+=e._tzh||0,r[4]+=e._tzm||0,n=new Date(0),e._useUTC?(n.setUTCFullYear(r[0],r[1],r[2]),n.setUTCHours(r[3],r[4],r[5],r[6])):(n.setFullYear(r[0],r[1],r[2]),n.setHours(r[3],r[4],r[5],r[6])),e._d=n}function K(e){var t=e._f.match(a),n=e._i,r,i;e._a=[];for(r=0;r<t.length;r++)i=(V(t[r]).exec(n)||[])[0],i&&(n=n.slice(n.indexOf(i)+i.length)),A[t[r]]&&$(t[r],i,e);e._isPm&&e._a[3]<12&&(e._a[3]+=12),e._isPm===!1&&e._a[3]===12&&(e._a[3]=0),J(e)}function Q(e){var t,n,r,i=99,s,o,u;while(e._f.length){t=H({},e),t._f=e._f.pop(),K(t),n=new D(t);if(n.isValid()){r=n;break}u=q(t._a,n.toArray()),u<i&&(i=u,r=n)}H(e,r)}function G(e){var t,n=e._i;if(w.exec(n)){e._f="YYYY-MM-DDT";for(t=0;t<4;t++)if(S[t][1].exec(n)){e._f+=S[t][0];break}g.exec(n)&&(e._f+=" Z"),K(e)}else e._d=new Date(n)}function Y(t){var n=t._i,r=u.exec(n);n===e?t._d=new Date:r?t._d=new Date(+r[1]):typeof n=="string"?G(t):I(n)?(t._a=n.slice(0),J(t)):t._d=n instanceof Date?new Date(+n):new Date(n)}function Z(e,t,n,r,i){return i.relativeTime(t||1,!!n,e,r)}function et(e,t,n){var i=r(Math.abs(e)/1e3),s=r(i/60),o=r(s/60),u=r(o/24),a=r(u/365),f=i<45&&["s",i]||s===1&&["m"]||s<45&&["mm",s]||o===1&&["h"]||o<22&&["hh",o]||u===1&&["d"]||u<=25&&["dd",u]||u<=45&&["M"]||u<345&&["MM",r(u/30)]||a===1&&["y"]||["yy",a];return f[2]=t,f[3]=e>0,f[4]=n,Z.apply({},f)}function tt(e,n,r){var i=r-n,s=r-e.day();return s>i&&(s-=7),s<i-7&&(s+=7),Math.ceil(t(e).add("d",s).dayOfYear()/7)}function nt(e){var n=e._i,r=e._f;return n===null||n===""?null:(typeof n=="string"&&(e._i=n=U().preparse(n)),t.isMoment(n)?(e=H({},n),e._d=new Date(+n._d)):r?I(r)?Q(e):K(e):Y(e),new D(e))}function rt(e,n){t.fn[e]=t.fn[e+"s"]=function(e){var t=this._isUTC?"UTC":"";return e!=null?(this._d["set"+t+n](e),this):this._d["get"+t+n]()}}function it(e){t.duration.fn[e]=function(){return this._data[e]}}function st(e,n){t.duration.fn["as"+e]=function(){return+this/n}}var t,n="2.0.0",r=Math.round,i,s={},o=typeof module!="undefined"&&module.exports,u=/^\/?Date\((\-?\d+)/i,a=/(\[[^\[]*\])|(\\)?(Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|YYYYY|YYYY|YY|a|A|hh?|HH?|mm?|ss?|SS?S?|X|zz?|ZZ?|.)/g,f=/(\[[^\[]*\])|(\\)?(LT|LL?L?L?|l{1,4})/g,l=/([0-9a-zA-Z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)/gi,c=/\d\d?/,h=/\d{1,3}/,p=/\d{3}/,d=/\d{1,4}/,v=/[+\-]?\d{1,6}/,m=/[0-9]*[a-z\u00A0-\u05FF\u0700-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+|[\u0600-\u06FF]+\s*?[\u0600-\u06FF]+/i,g=/Z|[\+\-]\d\d:?\d\d/i,y=/T/i,b=/[\+\-]?\d+(\.\d{1,3})?/,w=/^\s*\d{4}-\d\d-\d\d((T| )(\d\d(:\d\d(:\d\d(\.\d\d?\d?)?)?)?)?([\+\-]\d\d:?\d\d)?)?/,E="YYYY-MM-DDTHH:mm:ssZ",S=[["HH:mm:ss.S",/(T| )\d\d:\d\d:\d\d\.\d{1,3}/],["HH:mm:ss",/(T| )\d\d:\d\d:\d\d/],["HH:mm",/(T| )\d\d:\d\d/],["HH",/(T| )\d\d/]],x=/([\+\-]|\d\d)/gi,T="Month|Date|Hours|Minutes|Seconds|Milliseconds".split("|"),N={Milliseconds:1,Seconds:1e3,Minutes:6e4,Hours:36e5,Days:864e5,Months:2592e6,Years:31536e6},C={},k="DDD w W M D d".split(" "),L="M D H h m s w W".split(" "),A={M:function(){return this.month()+1},MMM:function(e){return this.lang().monthsShort(this,e)},MMMM:function(e){return this.lang().months(this,e)},D:function(){return this.date()},DDD:function(){return this.dayOfYear()},d:function(){return this.day()},dd:function(e){return this.lang().weekdaysMin(this,e)},ddd:function(e){return this.lang().weekdaysShort(this,e)},dddd:function(e){return this.lang().weekdays(this,e)},w:function(){return this.week()},W:function(){return this.isoWeek()},YY:function(){return j(this.year()%100,2)},YYYY:function(){return j(this.year(),4)},YYYYY:function(){return j(this.year(),5)},a:function(){return this.lang().meridiem(this.hours(),this.minutes(),!0)},A:function(){return this.lang().meridiem(this.hours(),this.minutes(),!1)},H:function(){return this.hours()},h:function(){return this.hours()%12||12},m:function(){return this.minutes()},s:function(){return this.seconds()},S:function(){return~~(this.milliseconds()/100)},SS:function(){return j(~~(this.milliseconds()/10),2)},SSS:function(){return j(this.milliseconds(),3)},Z:function(){var e=-this.zone(),t="+";return e<0&&(e=-e,t="-"),t+j(~~(e/60),2)+":"+j(~~e%60,2)},ZZ:function(){var e=-this.zone(),t="+";return e<0&&(e=-e,t="-"),t+j(~~(10*e/6),4)},X:function(){return this.unix()}};while(k.length)i=k.pop(),A[i+"o"]=M(A[i]);while(L.length)i=L.pop(),A[i+i]=O(A[i],2);A.DDDD=O(A.DDD,3),_.prototype={set:function(e){var t,n;for(n in e)t=e[n],typeof t=="function"?this[n]=t:this["_"+n]=t},_months:"January_February_March_April_May_June_July_August_September_October_November_December".split("_"),months:function(e){return this._months[e.month()]},_monthsShort:"Jan_Feb_Mar_Apr_May_Jun_Jul_Aug_Sep_Oct_Nov_Dec".split("_"),monthsShort:function(e){return this._monthsShort[e.month()]},monthsParse:function(e){var n,r,i,s;this._monthsParse||(this._monthsParse=[]);for(n=0;n<12;n++){this._monthsParse[n]||(r=t([2e3,n]),i="^"+this.months(r,"")+"|^"+this.monthsShort(r,""),this._monthsParse[n]=new RegExp(i.replace(".",""),"i"));if(this._monthsParse[n].test(e))return n}},_weekdays:"Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),weekdays:function(e){return this._weekdays[e.day()]},_weekdaysShort:"Sun_Mon_Tue_Wed_Thu_Fri_Sat".split("_"),weekdaysShort:function(e){return this._weekdaysShort[e.day()]},_weekdaysMin:"Su_Mo_Tu_We_Th_Fr_Sa".split("_"),weekdaysMin:function(e){return this._weekdaysMin[e.day()]},_longDateFormat:{LT:"h:mm A",L:"MM/DD/YYYY",LL:"MMMM D YYYY",LLL:"MMMM D YYYY LT",LLLL:"dddd, MMMM D YYYY LT"},longDateFormat:function(e){var t=this._longDateFormat[e];return!t&&this._longDateFormat[e.toUpperCase()]&&(t=this._longDateFormat[e.toUpperCase()].replace(/MMMM|MM|DD|dddd/g,function(e){return e.slice(1)}),this._longDateFormat[e]=t),t},meridiem:function(e,t,n){return e>11?n?"pm":"PM":n?"am":"AM"},_calendar:{sameDay:"[Today at] LT",nextDay:"[Tomorrow at] LT",nextWeek:"dddd [at] LT",lastDay:"[Yesterday at] LT",lastWeek:"[last] dddd [at] LT",sameElse:"L"},calendar:function(e,t){var n=this._calendar[e];return typeof n=="function"?n.apply(t):n},_relativeTime:{future:"in %s",past:"%s ago",s:"a few seconds",m:"a minute",mm:"%d minutes",h:"an hour",hh:"%d hours",d:"a day",dd:"%d days",M:"a month",MM:"%d months",y:"a year",yy:"%d years"},relativeTime:function(e,t,n,r){var i=this._relativeTime[n];return typeof i=="function"?i(e,t,n,r):i.replace(/%d/i,e)},pastFuture:function(e,t){var n=this._relativeTime[e>0?"future":"past"];return typeof n=="function"?n(t):n.replace(/%s/i,t)},ordinal:function(e){return this._ordinal.replace("%d",e)},_ordinal:"%d",preparse:function(e){return e},postformat:function(e){return e},week:function(e){return tt(e,this._week.dow,this._week.doy)},_week:{dow:0,doy:6}},t=function(e,t,n){return nt({_i:e,_f:t,_l:n,_isUTC:!1})},t.utc=function(e,t,n){return nt({_useUTC:!0,_isUTC:!0,_l:n,_i:e,_f:t})},t.unix=function(e){return t(e*1e3)},t.duration=function(e,n){var r=t.isDuration(e),i=typeof e=="number",s=r?e._data:i?{}:e,o;return i&&(n?s[n]=e:s.milliseconds=e),o=new P(s),r&&e.hasOwnProperty("_lang")&&(o._lang=e._lang),o},t.version=n,t.defaultFormat=E,t.lang=function(e,n){var r;if(!e)return t.fn._lang._abbr;n?R(e,n):s[e]||U(e),t.duration.fn._lang=t.fn._lang=U(e)},t.langData=function(e){return e&&e._lang&&e._lang._abbr&&(e=e._lang._abbr),U(e)},t.isMoment=function(e){return e instanceof D},t.isDuration=function(e){return e instanceof P},t.fn=D.prototype={clone:function(){return t(this)},valueOf:function(){return+this._d},unix:function(){return Math.floor(+this._d/1e3)},toString:function(){return this.format("ddd MMM DD YYYY HH:mm:ss [GMT]ZZ")},toDate:function(){return this._d},toJSON:function(){return t.utc(this).format("YYYY-MM-DD[T]HH:mm:ss.SSS[Z]")},toArray:function(){var e=this;return[e.year(),e.month(),e.date(),e.hours(),e.minutes(),e.seconds(),e.milliseconds()]},isValid:function(){return this._isValid==null&&(this._a?this._isValid=!q(this._a,(this._isUTC?t.utc(this._a):t(this._a)).toArray()):this._isValid=!isNaN(this._d.getTime())),!!this._isValid},utc:function(){return this._isUTC=!0,this},local:function(){return this._isUTC=!1,this},format:function(e){var n=X(this,e||t.defaultFormat);return this.lang().postformat(n)},add:function(e,n){var r;return typeof e=="string"?r=t.duration(+n,e):r=t.duration(e,n),F(this,r,1),this},subtract:function(e,n){var r;return typeof e=="string"?r=t.duration(+n,e):r=t.duration(e,n),F(this,r,-1),this},diff:function(e,n,r){var i=this._isUTC?t(e).utc():t(e).local(),s=(this.zone()-i.zone())*6e4,o,u;return n&&(n=n.replace(/s$/,"")),n==="year"||n==="month"?(o=(this.daysInMonth()+i.daysInMonth())*432e5,u=(this.year()-i.year())*12+(this.month()-i.month()),u+=(this-t(this).startOf("month")-(i-t(i).startOf("month")))/o,n==="year"&&(u/=12)):(o=this-i-s,u=n==="second"?o/1e3:n==="minute"?o/6e4:n==="hour"?o/36e5:n==="day"?o/864e5:n==="week"?o/6048e5:o),r?u:B(u)},from:function(e,n){return t.duration(this.diff(e)).lang(this.lang()._abbr).humanize(!n)},fromNow:function(e){return this.from(t(),e)},calendar:function(){var e=this.diff(t().startOf("day"),"days",!0),n=e<-6?"sameElse":e<-1?"lastWeek":e<0?"lastDay":e<1?"sameDay":e<2?"nextDay":e<7?"nextWeek":"sameElse";return this.format(this.lang().calendar(n,this))},isLeapYear:function(){var e=this.year();return e%4===0&&e%100!==0||e%400===0},isDST:function(){return this.zone()<t([this.year()]).zone()||this.zone()<t([this.year(),5]).zone()},day:function(e){var t=this._isUTC?this._d.getUTCDay():this._d.getDay();return e==null?t:this.add({d:e-t})},startOf:function(e){e=e.replace(/s$/,"");switch(e){case"year":this.month(0);case"month":this.date(1);case"week":case"day":this.hours(0);case"hour":this.minutes(0);case"minute":this.seconds(0);case"second":this.milliseconds(0)}return e==="week"&&this.day(0),this},endOf:function(e){return this.startOf(e).add(e.replace(/s?$/,"s"),1).subtract("ms",1)},isAfter:function(e,n){return n=typeof n!="undefined"?n:"millisecond",+this.clone().startOf(n)>+t(e).startOf(n)},isBefore:function(e,n){return n=typeof n!="undefined"?n:"millisecond",+this.clone().startOf(n)<+t(e).startOf(n)},isSame:function(e,n){return n=typeof n!="undefined"?n:"millisecond",+this.clone().startOf(n)===+t(e).startOf(n)},zone:function(){return this._isUTC?0:this._d.getTimezoneOffset()},daysInMonth:function(){return t.utc([this.year(),this.month()+1,0]).date()},dayOfYear:function(e){var n=r((t(this).startOf("day")-t(this).startOf("year"))/864e5)+1;return e==null?n:this.add("d",e-n)},isoWeek:function(e){var t=tt(this,1,4);return e==null?t:this.add("d",(e-t)*7)},week:function(e){var t=this.lang().week(this);return e==null?t:this.add("d",(e-t)*7)},lang:function(t){return t===e?this._lang:(this._lang=U(t),this)}};for(i=0;i<T.length;i++)rt(T[i].toLowerCase().replace(/s$/,""),T[i]);rt("year","FullYear"),t.fn.days=t.fn.day,t.fn.weeks=t.fn.week,t.fn.isoWeeks=t.fn.isoWeek,t.duration.fn=P.prototype={weeks:function(){return B(this.days()/7)},valueOf:function(){return this._milliseconds+this._days*864e5+this._months*2592e6},humanize:function(e){var t=+this,n=et(t,!e,this.lang());return e&&(n=this.lang().pastFuture(t,n)),this.lang().postformat(n)},lang:t.fn.lang};for(i in N)N.hasOwnProperty(i)&&(st(i,N[i]),it(i.toLowerCase()));st("Weeks",6048e5),t.lang("en",{ordinal:function(e){var t=e%10,n=~~(e%100/10)===1?"th":t===1?"st":t===2?"nd":t===3?"rd":"th";return e+n}}),o&&(module.exports=t),typeof ender=="undefined"&&(this.moment=t),typeof define=="function"&&define.amd&&define("moment",[],function(){return t})}).call(this);
/*! X-editable - v1.5.0 
* In-place editing with Twitter Bootstrap, jQuery UI or pure jQuery
* http://github.com/vitalets/x-editable
* Copyright (c) 2013 Vitaliy Potapov; Licensed MIT */
/**
Form with single input element, two buttons and two states: normal/loading.
Applied as jQuery method to DIV tag (not to form tag!). This is because form can be in loading state when spinner shown.
Editableform is linked with one of input types, e.g. 'text', 'select' etc.

@class editableform
@uses text
@uses textarea
**/

(function ($) {
    "use strict";
    
    var EditableForm = function (div, options) {
        this.options = $.extend({}, $.fn.editableform.defaults, options);
        this.$div = $(div); //div, containing form. Not form tag. Not editable-element.
        if(!this.options.scope) {
            this.options.scope = this;
        }
        //nothing shown after init
    };

    EditableForm.prototype = {
        constructor: EditableForm,
        initInput: function() {  //called once
            //take input from options (as it is created in editable-element)
            this.input = this.options.input;
            
            //set initial value
            //todo: may be add check: typeof str === 'string' ? 
            this.value = this.input.str2value(this.options.value); 
            
            //prerender: get input.$input
            this.input.prerender();
        },
        initTemplate: function() {
            this.$form = $($.fn.editableform.template); 
        },
        initButtons: function() {
            var $btn = this.$form.find('.editable-buttons');
            $btn.append($.fn.editableform.buttons);
            if(this.options.showbuttons === 'bottom') {
                $btn.addClass('editable-buttons-bottom');
            }
        },
        /**
        Renders editableform

        @method render
        **/        
        render: function() {
            //init loader
            this.$loading = $($.fn.editableform.loading);        
            this.$div.empty().append(this.$loading);
            
            //init form template and buttons
            this.initTemplate();
            if(this.options.showbuttons) {
                this.initButtons();
            } else {
                this.$form.find('.editable-buttons').remove();
            }

            //show loading state
            this.showLoading();            
            
            //flag showing is form now saving value to server. 
            //It is needed to wait when closing form.
            this.isSaving = false;
            
            /**        
            Fired when rendering starts
            @event rendering 
            @param {Object} event event object
            **/            
            this.$div.triggerHandler('rendering');
            
            //init input
            this.initInput();
            
            //append input to form
            this.$form.find('div.editable-input').append(this.input.$tpl);            
            
            //append form to container
            this.$div.append(this.$form);
            
            //render input
            $.when(this.input.render())
            .then($.proxy(function () {
                //setup input to submit automatically when no buttons shown
                if(!this.options.showbuttons) {
                    this.input.autosubmit(); 
                }
                 
                //attach 'cancel' handler
                this.$form.find('.editable-cancel').click($.proxy(this.cancel, this));
                
                if(this.input.error) {
                    this.error(this.input.error);
                    this.$form.find('.editable-submit').attr('disabled', true);
                    this.input.$input.attr('disabled', true);
                    //prevent form from submitting
                    this.$form.submit(function(e){ e.preventDefault(); });
                } else {
                    this.error(false);
                    this.input.$input.removeAttr('disabled');
                    this.$form.find('.editable-submit').removeAttr('disabled');
                    var value = (this.value === null || this.value === undefined || this.value === '') ? this.options.defaultValue : this.value;
                    this.input.value2input(value);
                    //attach submit handler
                    this.$form.submit($.proxy(this.submit, this));
                }

                /**        
                Fired when form is rendered
                @event rendered
                @param {Object} event event object
                **/            
                this.$div.triggerHandler('rendered');                

                this.showForm();
                
                //call postrender method to perform actions required visibility of form
                if(this.input.postrender) {
                    this.input.postrender();
                }                
            }, this));
        },
        cancel: function() {   
            /**        
            Fired when form was cancelled by user
            @event cancel 
            @param {Object} event event object
            **/              
            this.$div.triggerHandler('cancel');
        },
        showLoading: function() {
            var w, h;
            if(this.$form) {
                //set loading size equal to form
                w = this.$form.outerWidth();
                h = this.$form.outerHeight(); 
                if(w) {
                    this.$loading.width(w);
                }
                if(h) {
                    this.$loading.height(h);
                }
                this.$form.hide();
            } else {
                //stretch loading to fill container width
                w = this.$loading.parent().width();
                if(w) {
                    this.$loading.width(w);
                }
            }
            this.$loading.show(); 
        },

        showForm: function(activate) {
            this.$loading.hide();
            this.$form.show();
            if(activate !== false) {
                this.input.activate(); 
            }
            /**        
            Fired when form is shown
            @event show 
            @param {Object} event event object
            **/                    
            this.$div.triggerHandler('show');
        },

        error: function(msg) {
            var $group = this.$form.find('.control-group'),
                $block = this.$form.find('.editable-error-block'),
                lines;

            if(msg === false) {
                $group.removeClass($.fn.editableform.errorGroupClass);
                $block.removeClass($.fn.editableform.errorBlockClass).empty().hide(); 
            } else {
                //convert newline to <br> for more pretty error display
                if(msg) {
                    lines = msg.split("\n");
                    for (var i = 0; i < lines.length; i++) {
                        lines[i] = $('<div>').text(lines[i]).html();
                    }
                    msg = lines.join('<br>');
                }
                $group.addClass($.fn.editableform.errorGroupClass);
                $block.addClass($.fn.editableform.errorBlockClass).html(msg).show();
            }
        },

        submit: function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            var error,
                newValue = this.input.input2value(); //get new value from input

            //validation
            if (error = this.validate(newValue)) {
                this.error(error);
                this.showForm();
                return;
            } 
            
            //if value not changed --> trigger 'nochange' event and return
            /*jslint eqeq: true*/
            if (!this.options.savenochange && this.input.value2str(newValue) == this.input.value2str(this.value)) {
            /*jslint eqeq: false*/                
                /**        
                Fired when value not changed but form is submitted. Requires savenochange = false.
                @event nochange 
                @param {Object} event event object
                **/                    
                this.$div.triggerHandler('nochange');            
                return;
            } 

            //convert value for submitting to server
            var submitValue = this.input.value2submit(newValue);
            
            this.isSaving = true;
            
            //sending data to server
            $.when(this.save(submitValue))
            .done($.proxy(function(response) {
                this.isSaving = false;

                //run success callback
                var res = typeof this.options.success === 'function' ? this.options.success.call(this.options.scope, response, newValue) : null;

                //if success callback returns false --> keep form open and do not activate input
                if(res === false) {
                    this.error(false);
                    this.showForm(false);
                    return;
                }

                //if success callback returns string -->  keep form open, show error and activate input               
                if(typeof res === 'string') {
                    this.error(res);
                    this.showForm();
                    return;
                }

                //if success callback returns object like {newValue: <something>} --> use that value instead of submitted
                //it is usefull if you want to chnage value in url-function
                if(res && typeof res === 'object' && res.hasOwnProperty('newValue')) {
                    newValue = res.newValue;
                }

                //clear error message
                this.error(false);   
                this.value = newValue;
                /**        
                Fired when form is submitted
                @event save 
                @param {Object} event event object
                @param {Object} params additional params
                @param {mixed} params.newValue raw new value
                @param {mixed} params.submitValue submitted value as string
                @param {Object} params.response ajax response

                @example
                $('#form-div').on('save'), function(e, params){
                    if(params.newValue === 'username') {...}
                });
                **/
                this.$div.triggerHandler('save', {newValue: newValue, submitValue: submitValue, response: response});
            }, this))
            .fail($.proxy(function(xhr) {
                this.isSaving = false;

                var msg;
                if(typeof this.options.error === 'function') {
                    msg = this.options.error.call(this.options.scope, xhr, newValue);
                } else {
                    msg = typeof xhr === 'string' ? xhr : xhr.responseText || xhr.statusText || 'Unknown error!';
                }

                this.error(msg);
                this.showForm();
            }, this));
        },

        save: function(submitValue) {
            //try parse composite pk defined as json string in data-pk 
            this.options.pk = $.fn.editableutils.tryParseJson(this.options.pk, true); 
            
            var pk = (typeof this.options.pk === 'function') ? this.options.pk.call(this.options.scope) : this.options.pk,
            /*
              send on server in following cases:
              1. url is function
              2. url is string AND (pk defined OR send option = always) 
            */
            send = !!(typeof this.options.url === 'function' || (this.options.url && ((this.options.send === 'always') || (this.options.send === 'auto' && pk !== null && pk !== undefined)))),
            params;

            if (send) { //send to server
                this.showLoading();

                //standard params
                params = {
                    name: this.options.name || '',
                    value: submitValue,
                    pk: pk 
                };

                //additional params
                if(typeof this.options.params === 'function') {
                    params = this.options.params.call(this.options.scope, params);  
                } else {
                    //try parse json in single quotes (from data-params attribute)
                    this.options.params = $.fn.editableutils.tryParseJson(this.options.params, true);   
                    $.extend(params, this.options.params);
                }

                if(typeof this.options.url === 'function') { //user's function
                    return this.options.url.call(this.options.scope, params);
                } else {  
                    //send ajax to server and return deferred object
                    return $.ajax($.extend({
                        url     : this.options.url,
                        data    : params,
                        type    : 'POST'
                    }, this.options.ajaxOptions));
                }
            }
        }, 

        validate: function (value) {
            if (value === undefined) {
                value = this.value;
            }
            if (typeof this.options.validate === 'function') {
                return this.options.validate.call(this.options.scope, value);
            }
        },

        option: function(key, value) {
            if(key in this.options) {
                this.options[key] = value;
            }
            
            if(key === 'value') {
                this.setValue(value);
            }
            
            //do not pass option to input as it is passed in editable-element
        },

        setValue: function(value, convertStr) {
            if(convertStr) {
                this.value = this.input.str2value(value);
            } else {
                this.value = value;
            }
            
            //if form is visible, update input
            if(this.$form && this.$form.is(':visible')) {
                this.input.value2input(this.value);
            }            
        }               
    };

    /*
    Initialize editableform. Applied to jQuery object.

    @method $().editableform(options)
    @params {Object} options
    @example
    var $form = $('&lt;div&gt;').editableform({
        type: 'text',
        name: 'username',
        url: '/post',
        value: 'vitaliy'
    });

    //to display form you should call 'render' method
    $form.editableform('render');     
    */
    $.fn.editableform = function (option) {
        var args = arguments;
        return this.each(function () {
            var $this = $(this), 
            data = $this.data('editableform'), 
            options = typeof option === 'object' && option; 
            if (!data) {
                $this.data('editableform', (data = new EditableForm(this, options)));
            }

            if (typeof option === 'string') { //call method 
                data[option].apply(data, Array.prototype.slice.call(args, 1));
            } 
        });
    };

    //keep link to constructor to allow inheritance
    $.fn.editableform.Constructor = EditableForm;    

    //defaults
    $.fn.editableform.defaults = {
        /* see also defaults for input */

        /**
        Type of input. Can be <code>text|textarea|select|date|checklist</code>

        @property type 
        @type string
        @default 'text'
        **/
        type: 'text',
        /**
        Url for submit, e.g. <code>'/post'</code>  
        If function - it will be called instead of ajax. Function should return deferred object to run fail/done callbacks.

        @property url 
        @type string|function
        @default null
        @example
        url: function(params) {
            var d = new $.Deferred;
            if(params.value === 'abc') {
                return d.reject('error message'); //returning error via deferred object
            } else {
                //async saving data in js model
                someModel.asyncSaveMethod({
                   ..., 
                   success: function(){
                      d.resolve();
                   }
                }); 
                return d.promise();
            }
        } 
        **/        
        url:null,
        /**
        Additional params for submit. If defined as <code>object</code> - it is **appended** to original ajax data (pk, name and value).  
        If defined as <code>function</code> - returned object **overwrites** original ajax data.
        @example
        params: function(params) {
            //originally params contain pk, name and value
            params.a = 1;
            return params;
        }

        @property params 
        @type object|function
        @default null
        **/          
        params:null,
        /**
        Name of field. Will be submitted on server. Can be taken from <code>id</code> attribute

        @property name 
        @type string
        @default null
        **/         
        name: null,
        /**
        Primary key of editable object (e.g. record id in database). For composite keys use object, e.g. <code>{id: 1, lang: 'en'}</code>.
        Can be calculated dynamically via function.

        @property pk 
        @type string|object|function
        @default null
        **/         
        pk: null,
        /**
        Initial value. If not defined - will be taken from element's content.
        For __select__ type should be defined (as it is ID of shown text).

        @property value 
        @type string|object
        @default null
        **/        
        value: null,
        /**
        Value that will be displayed in input if original field value is empty (`null|undefined|''`).

        @property defaultValue 
        @type string|object
        @default null
        @since 1.4.6
        **/        
        defaultValue: null,
        /**
        Strategy for sending data on server. Can be `auto|always|never`.
        When 'auto' data will be sent on server **only if pk and url defined**, otherwise new value will be stored locally.

        @property send 
        @type string
        @default 'auto'
        **/          
        send: 'auto', 
        /**
        Function for client-side validation. If returns string - means validation not passed and string showed as error.

        @property validate 
        @type function
        @default null
        @example
        validate: function(value) {
            if($.trim(value) == '') {
                return 'This field is required';
            }
        }
        **/         
        validate: null,
        /**
        Success callback. Called when value successfully sent on server and **response status = 200**.  
        Usefull to work with json response. For example, if your backend response can be <code>{success: true}</code>
        or <code>{success: false, msg: "server error"}</code> you can check it inside this callback.  
        If it returns **string** - means error occured and string is shown as error message.  
        If it returns **object like** <code>{newValue: &lt;something&gt;}</code> - it overwrites value, submitted by user.  
        Otherwise newValue simply rendered into element.
        
        @property success 
        @type function
        @default null
        @example
        success: function(response, newValue) {
            if(!response.success) return response.msg;
        }
        **/          
        success: null,
        /**
        Error callback. Called when request failed (response status != 200).  
        Usefull when you want to parse error response and display a custom message.
        Must return **string** - the message to be displayed in the error block.
                
        @property error 
        @type function
        @default null
        @since 1.4.4
        @example
        error: function(response, newValue) {
            if(response.status === 500) {
                return 'Service unavailable. Please try later.';
            } else {
                return response.responseText;
            }
        }
        **/          
        error: null,
        /**
        Additional options for submit ajax request.
        List of values: http://api.jquery.com/jQuery.ajax
        
        @property ajaxOptions 
        @type object
        @default null
        @since 1.1.1        
        @example 
        ajaxOptions: {
            type: 'put',
            dataType: 'json'
        }        
        **/        
        ajaxOptions: null,
        /**
        Where to show buttons: left(true)|bottom|false  
        Form without buttons is auto-submitted.

        @property showbuttons 
        @type boolean|string
        @default true
        @since 1.1.1
        **/         
        showbuttons: true,
        /**
        Scope for callback methods (success, validate).  
        If <code>null</code> means editableform instance itself. 

        @property scope 
        @type DOMElement|object
        @default null
        @since 1.2.0
        @private
        **/            
        scope: null,
        /**
        Whether to save or cancel value when it was not changed but form was submitted

        @property savenochange 
        @type boolean
        @default false
        @since 1.2.0
        **/
        savenochange: false
    };   

    /*
    Note: following params could redefined in engine: bootstrap or jqueryui:
    Classes 'control-group' and 'editable-error-block' must always present!
    */      
    $.fn.editableform.template = '<form class="form-inline editableform">'+
    '<div class="control-group">' + 
    '<div><div class="editable-input"></div><div class="editable-buttons"></div></div>'+
    '<div class="editable-error-block"></div>' + 
    '</div>' + 
    '</form>';

    //loading div
    $.fn.editableform.loading = '<div class="editableform-loading"></div>';

    //buttons
    $.fn.editableform.buttons = '<button type="submit" class="editable-submit">ok</button>'+
    '<button type="button" class="editable-cancel">cancel</button>';      

    //error class attached to control-group
    $.fn.editableform.errorGroupClass = null;  

    //error class attached to editable-error-block
    $.fn.editableform.errorBlockClass = 'editable-error';
    
    //engine
    $.fn.editableform.engine = 'jquery';
}(window.jQuery));

/**
* EditableForm utilites
*/
(function ($) {
    "use strict";
    
    //utils
    $.fn.editableutils = {
        /**
        * classic JS inheritance function
        */  
        inherit: function (Child, Parent) {
            var F = function() { };
            F.prototype = Parent.prototype;
            Child.prototype = new F();
            Child.prototype.constructor = Child;
            Child.superclass = Parent.prototype;
        },

        /**
        * set caret position in input
        * see http://stackoverflow.com/questions/499126/jquery-set-cursor-position-in-text-area
        */        
        setCursorPosition: function(elem, pos) {
            if (elem.setSelectionRange) {
                elem.setSelectionRange(pos, pos);
            } else if (elem.createTextRange) {
                var range = elem.createTextRange();
                range.collapse(true);
                range.moveEnd('character', pos);
                range.moveStart('character', pos);
                range.select();
            }
        },

        /**
        * function to parse JSON in *single* quotes. (jquery automatically parse only double quotes)
        * That allows such code as: <a data-source="{'a': 'b', 'c': 'd'}">
        * safe = true --> means no exception will be thrown
        * for details see http://stackoverflow.com/questions/7410348/how-to-set-json-format-to-html5-data-attributes-in-the-jquery
        */
        tryParseJson: function(s, safe) {
            if (typeof s === 'string' && s.length && s.match(/^[\{\[].*[\}\]]$/)) {
                if (safe) {
                    try {
                        /*jslint evil: true*/
                        s = (new Function('return ' + s))();
                        /*jslint evil: false*/
                    } catch (e) {} finally {
                        return s;
                    }
                } else {
                    /*jslint evil: true*/
                    s = (new Function('return ' + s))();
                    /*jslint evil: false*/
                }
            }
            return s;
        },

        /**
        * slice object by specified keys
        */
        sliceObj: function(obj, keys, caseSensitive /* default: false */) {
            var key, keyLower, newObj = {};

            if (!$.isArray(keys) || !keys.length) {
                return newObj;
            }

            for (var i = 0; i < keys.length; i++) {
                key = keys[i];
                if (obj.hasOwnProperty(key)) {
                    newObj[key] = obj[key];
                }

                if(caseSensitive === true) {
                    continue;
                }

                //when getting data-* attributes via $.data() it's converted to lowercase.
                //details: http://stackoverflow.com/questions/7602565/using-data-attributes-with-jquery
                //workaround is code below.
                keyLower = key.toLowerCase();
                if (obj.hasOwnProperty(keyLower)) {
                    newObj[key] = obj[keyLower];
                }
            }

            return newObj;
        },

        /*
        exclude complex objects from $.data() before pass to config
        */
        getConfigData: function($element) {
            var data = {};
            $.each($element.data(), function(k, v) {
                if(typeof v !== 'object' || (v && typeof v === 'object' && (v.constructor === Object || v.constructor === Array))) {
                    data[k] = v;
                }
            });
            return data;
        },

        /*
         returns keys of object
        */
        objectKeys: function(o) {
            if (Object.keys) {
                return Object.keys(o);  
            } else {
                if (o !== Object(o)) {
                    throw new TypeError('Object.keys called on a non-object');
                }
                var k=[], p;
                for (p in o) {
                    if (Object.prototype.hasOwnProperty.call(o,p)) {
                        k.push(p);
                    }
                }
                return k;
            }

        },
        
       /**
        method to escape html.
       **/
       escape: function(str) {
           return $('<div>').text(str).html();
       },
       
       /*
        returns array items from sourceData having value property equal or inArray of 'value'
       */
       itemsByValue: function(value, sourceData, valueProp) {
           if(!sourceData || value === null) {
               return [];
           }
           
           if (typeof(valueProp) !== "function") {
               var idKey = valueProp || 'value';
               valueProp = function (e) { return e[idKey]; };
           }
                      
           var isValArray = $.isArray(value),
           result = [], 
           that = this;

           $.each(sourceData, function(i, o) {
               if(o.children) {
                   result = result.concat(that.itemsByValue(value, o.children, valueProp));
               } else {
                   /*jslint eqeq: true*/
                   if(isValArray) {
                       if($.grep(value, function(v){  return v == (o && typeof o === 'object' ? valueProp(o) : o); }).length) {
                           result.push(o); 
                       }
                   } else {
                       var itemValue = (o && (typeof o === 'object')) ? valueProp(o) : o;
                       if(value == itemValue) {
                           result.push(o); 
                       }
                   }
                   /*jslint eqeq: false*/
               }
           });
           
           return result;
       },
       
       /*
       Returns input by options: type, mode. 
       */
       createInput: function(options) {
           var TypeConstructor, typeOptions, input,
           type = options.type;

           //`date` is some kind of virtual type that is transformed to one of exact types
           //depending on mode and core lib
           if(type === 'date') {
               //inline
               if(options.mode === 'inline') {
                   if($.fn.editabletypes.datefield) {
                       type = 'datefield';
                   } else if($.fn.editabletypes.dateuifield) {
                       type = 'dateuifield';
                   }
               //popup
               } else {
                   if($.fn.editabletypes.date) {
                       type = 'date';
                   } else if($.fn.editabletypes.dateui) {
                       type = 'dateui';
                   }
               }
               
               //if type still `date` and not exist in types, replace with `combodate` that is base input
               if(type === 'date' && !$.fn.editabletypes.date) {
                   type = 'combodate';
               } 
           }
           
           //`datetime` should be datetimefield in 'inline' mode
           if(type === 'datetime' && options.mode === 'inline') {
             type = 'datetimefield';  
           }           

           //change wysihtml5 to textarea for jquery UI and plain versions
           if(type === 'wysihtml5' && !$.fn.editabletypes[type]) {
               type = 'textarea';
           }

           //create input of specified type. Input will be used for converting value, not in form
           if(typeof $.fn.editabletypes[type] === 'function') {
               TypeConstructor = $.fn.editabletypes[type];
               typeOptions = this.sliceObj(options, this.objectKeys(TypeConstructor.defaults));
               input = new TypeConstructor(typeOptions);
               return input;
           } else {
               $.error('Unknown type: '+ type);
               return false; 
           }  
       },
       
       //see http://stackoverflow.com/questions/7264899/detect-css-transitions-using-javascript-and-without-modernizr
       supportsTransitions: function () {
           var b = document.body || document.documentElement,
               s = b.style,
               p = 'transition',
               v = ['Moz', 'Webkit', 'Khtml', 'O', 'ms'];
               
           if(typeof s[p] === 'string') {
               return true; 
           }

           // Tests for vendor specific prop
           p = p.charAt(0).toUpperCase() + p.substr(1);
           for(var i=0; i<v.length; i++) {
               if(typeof s[v[i] + p] === 'string') { 
                   return true; 
               }
           }
           return false;
       }            
       
    };      
}(window.jQuery));

/**
Attaches stand-alone container with editable-form to HTML element. Element is used only for positioning, value is not stored anywhere.<br>
This method applied internally in <code>$().editable()</code>. You should subscribe on it's events (save / cancel) to get profit of it.<br>
Final realization can be different: bootstrap-popover, jqueryui-tooltip, poshytip, inline-div. It depends on which js file you include.<br>
Applied as jQuery method.

@class editableContainer
@uses editableform
**/
(function ($) {
    "use strict";

    var Popup = function (element, options) {
        this.init(element, options);
    };
    
    var Inline = function (element, options) {
        this.init(element, options);
    };    

    //methods
    Popup.prototype = {
        containerName: null, //method to call container on element
        containerDataName: null, //object name in element's .data()
        innerCss: null, //tbd in child class
        containerClass: 'editable-container editable-popup', //css class applied to container element
        defaults: {}, //container itself defaults
        
        init: function(element, options) {
            this.$element = $(element);
            //since 1.4.1 container do not use data-* directly as they already merged into options.
            this.options = $.extend({}, $.fn.editableContainer.defaults, options);         
            this.splitOptions();
            
            //set scope of form callbacks to element
            this.formOptions.scope = this.$element[0]; 
            
            this.initContainer();
            
            //flag to hide container, when saving value will finish
            this.delayedHide = false;

            //bind 'destroyed' listener to destroy container when element is removed from dom
            this.$element.on('destroyed', $.proxy(function(){
                this.destroy();
            }, this)); 
            
            //attach document handler to close containers on click / escape
            if(!$(document).data('editable-handlers-attached')) {
                //close all on escape
                $(document).on('keyup.editable', function (e) {
                    if (e.which === 27) {
                        $('.editable-open').editableContainer('hide');
                        //todo: return focus on element 
                    }
                });

                //close containers when click outside 
                //(mousedown could be better than click, it closes everything also on drag drop)
                $(document).on('click.editable', function(e) {
                    var $target = $(e.target), i,
                        exclude_classes = ['.editable-container', 
                                           '.ui-datepicker-header', 
                                           '.datepicker', //in inline mode datepicker is rendered into body
                                           '.modal-backdrop', 
                                           '.bootstrap-wysihtml5-insert-image-modal', 
                                           '.bootstrap-wysihtml5-insert-link-modal'
                                           ];
                    
                    //check if element is detached. It occurs when clicking in bootstrap datepicker
                    if (!$.contains(document.documentElement, e.target)) {
                      return;
                    }

                    //for some reason FF 20 generates extra event (click) in select2 widget with e.target = document
                    //we need to filter it via construction below. See https://github.com/vitalets/x-editable/issues/199
                    //Possibly related to http://stackoverflow.com/questions/10119793/why-does-firefox-react-differently-from-webkit-and-ie-to-click-event-on-selec
                    if($target.is(document)) {
                       return; 
                    }
                    
                    //if click inside one of exclude classes --> no nothing
                    for(i=0; i<exclude_classes.length; i++) {
                         if($target.is(exclude_classes[i]) || $target.parents(exclude_classes[i]).length) {
                             return;
                         }
                    }
                      
                    //close all open containers (except one - target)
                    Popup.prototype.closeOthers(e.target);
                });
                
                $(document).data('editable-handlers-attached', true);
            }                        
        },

        //split options on containerOptions and formOptions
        splitOptions: function() {
            this.containerOptions = {};
            this.formOptions = {};
            
            if(!$.fn[this.containerName]) {
                throw new Error(this.containerName + ' not found. Have you included corresponding js file?');   
            }
            
            //keys defined in container defaults go to container, others go to form
            for(var k in this.options) {
              if(k in this.defaults) {
                 this.containerOptions[k] = this.options[k];
              } else {
                 this.formOptions[k] = this.options[k];
              } 
            }
        },
        
        /*
        Returns jquery object of container
        @method tip()
        */         
        tip: function() {
            return this.container() ? this.container().$tip : null;
        },

        /* returns container object */
        container: function() {
            var container;
            //first, try get it by `containerDataName`
            if(this.containerDataName) {
                if(container = this.$element.data(this.containerDataName)) {
                    return container;
                }
            }
            //second, try `containerName`
            container = this.$element.data(this.containerName);
            return container;
        },

        /* call native method of underlying container, e.g. this.$element.popover('method') */ 
        call: function() {
            this.$element[this.containerName].apply(this.$element, arguments); 
        },        
        
        initContainer: function(){
            this.call(this.containerOptions);
        },

        renderForm: function() {
            this.$form
            .editableform(this.formOptions)
            .on({
                save: $.proxy(this.save, this), //click on submit button (value changed)
                nochange: $.proxy(function(){ this.hide('nochange'); }, this), //click on submit button (value NOT changed)                
                cancel: $.proxy(function(){ this.hide('cancel'); }, this), //click on calcel button
                show: $.proxy(function() {
                    if(this.delayedHide) {
                        this.hide(this.delayedHide.reason);
                        this.delayedHide = false;
                    } else {
                        this.setPosition();
                    }
                }, this), //re-position container every time form is shown (occurs each time after loading state)
                rendering: $.proxy(this.setPosition, this), //this allows to place container correctly when loading shown
                resize: $.proxy(this.setPosition, this), //this allows to re-position container when form size is changed 
                rendered: $.proxy(function(){
                    /**        
                    Fired when container is shown and form is rendered (for select will wait for loading dropdown options).  
                    **Note:** Bootstrap popover has own `shown` event that now cannot be separated from x-editable's one.
                    The workaround is to check `arguments.length` that is always `2` for x-editable.                     
                    
                    @event shown 
                    @param {Object} event event object
                    @example
                    $('#username').on('shown', function(e, editable) {
                        editable.input.$input.val('overwriting value of input..');
                    });                     
                    **/                      
                    /*
                     TODO: added second param mainly to distinguish from bootstrap's shown event. It's a hotfix that will be solved in future versions via namespaced events.  
                    */
                    this.$element.triggerHandler('shown', $(this.options.scope).data('editable')); 
                }, this) 
            })
            .editableform('render');
        },        

        /**
        Shows container with form
        @method show()
        @param {boolean} closeAll Whether to close all other editable containers when showing this one. Default true.
        **/
        /* Note: poshytip owerwrites this method totally! */          
        show: function (closeAll) {
            this.$element.addClass('editable-open');
            if(closeAll !== false) {
                //close all open containers (except this)
                this.closeOthers(this.$element[0]);  
            }
            
            //show container itself
            this.innerShow();
            this.tip().addClass(this.containerClass);

            /*
            Currently, form is re-rendered on every show. 
            The main reason is that we dont know, what will container do with content when closed:
            remove(), detach() or just hide() - it depends on container.
            
            Detaching form itself before hide and re-insert before show is good solution, 
            but visually it looks ugly --> container changes size before hide.  
            */             
            
            //if form already exist - delete previous data 
            if(this.$form) {
                //todo: destroy prev data!
                //this.$form.destroy();
            }

            this.$form = $('<div>');
            
            //insert form into container body
            if(this.tip().is(this.innerCss)) {
                //for inline container
                this.tip().append(this.$form); 
            } else {
                this.tip().find(this.innerCss).append(this.$form);
            } 
            
            //render form
            this.renderForm();
        },

        /**
        Hides container with form
        @method hide()
        @param {string} reason Reason caused hiding. Can be <code>save|cancel|onblur|nochange|undefined (=manual)</code>
        **/         
        hide: function(reason) {  
            if(!this.tip() || !this.tip().is(':visible') || !this.$element.hasClass('editable-open')) {
                return;
            }
            
            //if form is saving value, schedule hide
            if(this.$form.data('editableform').isSaving) {
                this.delayedHide = {reason: reason};
                return;    
            } else {
                this.delayedHide = false;
            }

            this.$element.removeClass('editable-open');   
            this.innerHide();

            /**
            Fired when container was hidden. It occurs on both save or cancel.  
            **Note:** Bootstrap popover has own `hidden` event that now cannot be separated from x-editable's one.
            The workaround is to check `arguments.length` that is always `2` for x-editable. 

            @event hidden 
            @param {object} event event object
            @param {string} reason Reason caused hiding. Can be <code>save|cancel|onblur|nochange|manual</code>
            @example
            $('#username').on('hidden', function(e, reason) {
                if(reason === 'save' || reason === 'cancel') {
                    //auto-open next editable
                    $(this).closest('tr').next().find('.editable').editable('show');
                } 
            });
            **/
            this.$element.triggerHandler('hidden', reason || 'manual');   
        },

        /* internal show method. To be overwritten in child classes */
        innerShow: function () {
             
        },        

        /* internal hide method. To be overwritten in child classes */
        innerHide: function () {

        },
        
        /**
        Toggles container visibility (show / hide)
        @method toggle()
        @param {boolean} closeAll Whether to close all other editable containers when showing this one. Default true.
        **/          
        toggle: function(closeAll) {
            if(this.container() && this.tip() && this.tip().is(':visible')) {
                this.hide();
            } else {
                this.show(closeAll);
            } 
        },

        /*
        Updates the position of container when content changed.
        @method setPosition()
        */       
        setPosition: function() {
            //tbd in child class
        },

        save: function(e, params) {
            /**        
            Fired when new value was submitted. You can use <code>$(this).data('editableContainer')</code> inside handler to access to editableContainer instance
            
            @event save 
            @param {Object} event event object
            @param {Object} params additional params
            @param {mixed} params.newValue submitted value
            @param {Object} params.response ajax response
            @example
            $('#username').on('save', function(e, params) {
                //assuming server response: '{success: true}'
                var pk = $(this).data('editableContainer').options.pk;
                if(params.response && params.response.success) {
                    alert('value: ' + params.newValue + ' with pk: ' + pk + ' saved!');
                } else {
                    alert('error!'); 
                } 
            });
            **/             
            this.$element.triggerHandler('save', params);
            
            //hide must be after trigger, as saving value may require methods of plugin, applied to input
            this.hide('save');
        },

        /**
        Sets new option
        
        @method option(key, value)
        @param {string} key 
        @param {mixed} value 
        **/         
        option: function(key, value) {
            this.options[key] = value;
            if(key in this.containerOptions) {
                this.containerOptions[key] = value;
                this.setContainerOption(key, value); 
            } else {
                this.formOptions[key] = value;
                if(this.$form) {
                    this.$form.editableform('option', key, value);  
                }
            }
        },
        
        setContainerOption: function(key, value) {
            this.call('option', key, value);
        },

        /**
        Destroys the container instance
        @method destroy()
        **/        
        destroy: function() {
            this.hide();
            this.innerDestroy();
            this.$element.off('destroyed');
            this.$element.removeData('editableContainer');
        },
        
        /* to be overwritten in child classes */
        innerDestroy: function() {
            
        }, 
        
        /*
        Closes other containers except one related to passed element. 
        Other containers can be cancelled or submitted (depends on onblur option)
        */
        closeOthers: function(element) {
            $('.editable-open').each(function(i, el){
                //do nothing with passed element and it's children
                if(el === element || $(el).find(element).length) {
                    return;
                }

                //otherwise cancel or submit all open containers 
                var $el = $(el),
                ec = $el.data('editableContainer');

                if(!ec) {
                    return;  
                }
                
                if(ec.options.onblur === 'cancel') {
                    $el.data('editableContainer').hide('onblur');
                } else if(ec.options.onblur === 'submit') {
                    $el.data('editableContainer').tip().find('form').submit();
                }
            });

        },
        
        /**
        Activates input of visible container (e.g. set focus)
        @method activate()
        **/         
        activate: function() {
            if(this.tip && this.tip().is(':visible') && this.$form) {
               this.$form.data('editableform').input.activate(); 
            }
        } 

    };

    /**
    jQuery method to initialize editableContainer.
    
    @method $().editableContainer(options)
    @params {Object} options
    @example
    $('#edit').editableContainer({
        type: 'text',
        url: '/post',
        pk: 1,
        value: 'hello'
    });
    **/  
    $.fn.editableContainer = function (option) {
        var args = arguments;
        return this.each(function () {
            var $this = $(this),
            dataKey = 'editableContainer', 
            data = $this.data(dataKey),
            options = typeof option === 'object' && option,
            Constructor = (options.mode === 'inline') ? Inline : Popup;             

            if (!data) {
                $this.data(dataKey, (data = new Constructor(this, options)));
            }

            if (typeof option === 'string') { //call method 
                data[option].apply(data, Array.prototype.slice.call(args, 1));
            }            
        });
    };     

    //store constructors
    $.fn.editableContainer.Popup = Popup;
    $.fn.editableContainer.Inline = Inline;

    //defaults
    $.fn.editableContainer.defaults = {
        /**
        Initial value of form input

        @property value 
        @type mixed
        @default null
        @private
        **/        
        value: null,
        /**
        Placement of container relative to element. Can be <code>top|right|bottom|left</code>. Not used for inline container.

        @property placement 
        @type string
        @default 'top'
        **/        
        placement: 'top',
        /**
        Whether to hide container on save/cancel.

        @property autohide 
        @type boolean
        @default true
        @private 
        **/        
        autohide: true,
        /**
        Action when user clicks outside the container. Can be <code>cancel|submit|ignore</code>.  
        Setting <code>ignore</code> allows to have several containers open. 

        @property onblur 
        @type string
        @default 'cancel'
        @since 1.1.1
        **/        
        onblur: 'cancel',
        
        /**
        Animation speed (inline mode only)
        @property anim 
        @type string
        @default false
        **/        
        anim: false,
        
        /**
        Mode of editable, can be `popup` or `inline` 
        
        @property mode 
        @type string         
        @default 'popup'
        @since 1.4.0        
        **/        
        mode: 'popup'        
    };

    /* 
    * workaround to have 'destroyed' event to destroy popover when element is destroyed
    * see http://stackoverflow.com/questions/2200494/jquery-trigger-event-when-an-element-is-removed-from-the-dom
    */
    jQuery.event.special.destroyed = {
        remove: function(o) {
            if (o.handler) {
                o.handler();
            }
        }
    };    

}(window.jQuery));

/**
* Editable Inline 
* ---------------------
*/
(function ($) {
    "use strict";
    
    //copy prototype from EditableContainer
    //extend methods
    $.extend($.fn.editableContainer.Inline.prototype, $.fn.editableContainer.Popup.prototype, {
        containerName: 'editableform',
        innerCss: '.editable-inline',
        containerClass: 'editable-container editable-inline', //css class applied to container element
                 
        initContainer: function(){
            //container is <span> element
            this.$tip = $('<span></span>');
            
            //convert anim to miliseconds (int)
            if(!this.options.anim) {
                this.options.anim = 0;
            }         
        },
        
        splitOptions: function() {
            //all options are passed to form
            this.containerOptions = {};
            this.formOptions = this.options;
        },
        
        tip: function() {
           return this.$tip; 
        },
        
        innerShow: function () {
            this.$element.hide();
            this.tip().insertAfter(this.$element).show();
        }, 
        
        innerHide: function () {
            this.$tip.hide(this.options.anim, $.proxy(function() {
                this.$element.show();
                this.innerDestroy();
            }, this)); 
        },
        
        innerDestroy: function() {
            if(this.tip()) {
                this.tip().empty().remove();
            }
        } 
    });

}(window.jQuery));
/**
Makes editable any HTML element on the page. Applied as jQuery method.

@class editable
@uses editableContainer
**/
(function ($) {
    "use strict";

    var Editable = function (element, options) {
        this.$element = $(element);
        //data-* has more priority over js options: because dynamically created elements may change data-* 
        this.options = $.extend({}, $.fn.editable.defaults, options, $.fn.editableutils.getConfigData(this.$element));  
        if(this.options.selector) {
            this.initLive();
        } else {
            this.init();
        }
        
        //check for transition support
        if(this.options.highlight && !$.fn.editableutils.supportsTransitions()) {
            this.options.highlight = false;
        }
    };

    Editable.prototype = {
        constructor: Editable, 
        init: function () {
            var isValueByText = false, 
                doAutotext, finalize;

            //name
            this.options.name = this.options.name || this.$element.attr('id');
             
            //create input of specified type. Input needed already here to convert value for initial display (e.g. show text by id for select)
            //also we set scope option to have access to element inside input specific callbacks (e. g. source as function)
            this.options.scope = this.$element[0]; 
            this.input = $.fn.editableutils.createInput(this.options);
            if(!this.input) {
                return; 
            }            

            //set value from settings or by element's text
            if (this.options.value === undefined || this.options.value === null) {
                this.value = this.input.html2value($.trim(this.$element.html()));
                isValueByText = true;
            } else {
                /*
                  value can be string when received from 'data-value' attribute
                  for complext objects value can be set as json string in data-value attribute, 
                  e.g. data-value="{city: 'Moscow', street: 'Lenina'}"
                */
                this.options.value = $.fn.editableutils.tryParseJson(this.options.value, true); 
                if(typeof this.options.value === 'string') {
                    this.value = this.input.str2value(this.options.value);
                } else {
                    this.value = this.options.value;
                }
            }
            
            //add 'editable' class to every editable element
            this.$element.addClass('editable');
            
            //specifically for "textarea" add class .editable-pre-wrapped to keep linebreaks
            if(this.input.type === 'textarea') {
                this.$element.addClass('editable-pre-wrapped');
            }
            
            //attach handler activating editable. In disabled mode it just prevent default action (useful for links)
            if(this.options.toggle !== 'manual') {
                this.$element.addClass('editable-click');
                this.$element.on(this.options.toggle + '.editable', $.proxy(function(e){
                    //prevent following link if editable enabled
                    if(!this.options.disabled) {
                        e.preventDefault();
                    }
                    
                    //stop propagation not required because in document click handler it checks event target
                    //e.stopPropagation();
                    
                    if(this.options.toggle === 'mouseenter') {
                        //for hover only show container
                        this.show();
                    } else {
                        //when toggle='click' we should not close all other containers as they will be closed automatically in document click listener
                        var closeAll = (this.options.toggle !== 'click');
                        this.toggle(closeAll);
                    }
                }, this));
            } else {
                this.$element.attr('tabindex', -1); //do not stop focus on element when toggled manually
            }
            
            //if display is function it's far more convinient to have autotext = always to render correctly on init
            //see https://github.com/vitalets/x-editable-yii/issues/34
            if(typeof this.options.display === 'function') {
                this.options.autotext = 'always';
            }
            
            //check conditions for autotext:
            switch(this.options.autotext) {
              case 'always':
               doAutotext = true;
              break;
              case 'auto':
                //if element text is empty and value is defined and value not generated by text --> run autotext
                doAutotext = !$.trim(this.$element.text()).length && this.value !== null && this.value !== undefined && !isValueByText;
              break;
              default:
               doAutotext = false;
            }

            //depending on autotext run render() or just finilize init
            $.when(doAutotext ? this.render() : true).then($.proxy(function() {
                if(this.options.disabled) {
                    this.disable();
                } else {
                    this.enable(); 
                }
               /**        
               Fired when element was initialized by `$().editable()` method. 
               Please note that you should setup `init` handler **before** applying `editable`. 
                              
               @event init 
               @param {Object} event event object
               @param {Object} editable editable instance (as here it cannot accessed via data('editable'))
               @since 1.2.0
               @example
               $('#username').on('init', function(e, editable) {
                   alert('initialized ' + editable.options.name);
               });
               $('#username').editable();
               **/                  
                this.$element.triggerHandler('init', this);
            }, this));
        },

        /*
         Initializes parent element for live editables 
        */
        initLive: function() {
           //store selector 
           var selector = this.options.selector;
           //modify options for child elements
           this.options.selector = false; 
           this.options.autotext = 'never';
           //listen toggle events
           this.$element.on(this.options.toggle + '.editable', selector, $.proxy(function(e){
               var $target = $(e.target);
               if(!$target.data('editable')) {
                   //if delegated element initially empty, we need to clear it's text (that was manually set to `empty` by user)
                   //see https://github.com/vitalets/x-editable/issues/137 
                   if($target.hasClass(this.options.emptyclass)) {
                      $target.empty();
                   }
                   $target.editable(this.options).trigger(e);
               }
           }, this)); 
        },
        
        /*
        Renders value into element's text.
        Can call custom display method from options.
        Can return deferred object.
        @method render()
        @param {mixed} response server response (if exist) to pass into display function
        */          
        render: function(response) {
            //do not display anything
            if(this.options.display === false) {
                return;
            }
            
            //if input has `value2htmlFinal` method, we pass callback in third param to be called when source is loaded
            if(this.input.value2htmlFinal) {
                return this.input.value2html(this.value, this.$element[0], this.options.display, response); 
            //if display method defined --> use it    
            } else if(typeof this.options.display === 'function') {
                return this.options.display.call(this.$element[0], this.value, response);
            //else use input's original value2html() method    
            } else {
                return this.input.value2html(this.value, this.$element[0]); 
            }
        },
        
        /**
        Enables editable
        @method enable()
        **/          
        enable: function() {
            this.options.disabled = false;
            this.$element.removeClass('editable-disabled');
            this.handleEmpty(this.isEmpty);
            if(this.options.toggle !== 'manual') {
                if(this.$element.attr('tabindex') === '-1') {    
                    this.$element.removeAttr('tabindex');                                
                }
            }
        },
        
        /**
        Disables editable
        @method disable()
        **/         
        disable: function() {
            this.options.disabled = true; 
            this.hide();           
            this.$element.addClass('editable-disabled');
            this.handleEmpty(this.isEmpty);
            //do not stop focus on this element
            this.$element.attr('tabindex', -1);                
        },
        
        /**
        Toggles enabled / disabled state of editable element
        @method toggleDisabled()
        **/         
        toggleDisabled: function() {
            if(this.options.disabled) {
                this.enable();
            } else { 
                this.disable(); 
            }
        },  
        
        /**
        Sets new option
        
        @method option(key, value)
        @param {string|object} key option name or object with several options
        @param {mixed} value option new value
        @example
        $('.editable').editable('option', 'pk', 2);
        **/          
        option: function(key, value) {
            //set option(s) by object
            if(key && typeof key === 'object') {
               $.each(key, $.proxy(function(k, v){
                  this.option($.trim(k), v); 
               }, this)); 
               return;
            }

            //set option by string             
            this.options[key] = value;                          
            
            //disabled
            if(key === 'disabled') {
               return value ? this.disable() : this.enable();
            } 
            
            //value
            if(key === 'value') {
                this.setValue(value);
            }
            
            //transfer new option to container! 
            if(this.container) {
                this.container.option(key, value);  
            }
             
            //pass option to input directly (as it points to the same in form)
            if(this.input.option) {
                this.input.option(key, value);
            }
            
        },              
        
        /*
        * set emptytext if element is empty
        */
        handleEmpty: function (isEmpty) {
            //do not handle empty if we do not display anything
            if(this.options.display === false) {
                return;
            }

            /* 
            isEmpty may be set directly as param of method.
            It is required when we enable/disable field and can't rely on content 
            as node content is text: "Empty" that is not empty %)
            */
            if(isEmpty !== undefined) { 
                this.isEmpty = isEmpty;
            } else {
                //detect empty
                //for some inputs we need more smart check
                //e.g. wysihtml5 may have <br>, <p></p>, <img>
                if(typeof(this.input.isEmpty) === 'function') {
                    this.isEmpty = this.input.isEmpty(this.$element);                    
                } else {
                    this.isEmpty = $.trim(this.$element.html()) === '';
                }
            }           
            
            //emptytext shown only for enabled
            if(!this.options.disabled) {
                if (this.isEmpty) {
                    this.$element.html(this.options.emptytext);
                    if(this.options.emptyclass) {
                        this.$element.addClass(this.options.emptyclass);
                    }
                } else if(this.options.emptyclass) {
                    this.$element.removeClass(this.options.emptyclass);
                }
            } else {
                //below required if element disable property was changed
                if(this.isEmpty) {
                    this.$element.empty();
                    if(this.options.emptyclass) {
                        this.$element.removeClass(this.options.emptyclass);
                    }
                }
            }
        },        
        
        /**
        Shows container with form
        @method show()
        @param {boolean} closeAll Whether to close all other editable containers when showing this one. Default true.
        **/  
        show: function (closeAll) {
            if(this.options.disabled) {
                return;
            }
            
            //init editableContainer: popover, tooltip, inline, etc..
            if(!this.container) {
                var containerOptions = $.extend({}, this.options, {
                    value: this.value,
                    input: this.input //pass input to form (as it is already created)
                });
                this.$element.editableContainer(containerOptions);
                //listen `save` event 
                this.$element.on("save.internal", $.proxy(this.save, this));
                this.container = this.$element.data('editableContainer'); 
            } else if(this.container.tip().is(':visible')) {
                return;
            }      
            
            //show container
            this.container.show(closeAll);
        },
        
        /**
        Hides container with form
        @method hide()
        **/       
        hide: function () {   
            if(this.container) {  
                this.container.hide();
            }
        },
        
        /**
        Toggles container visibility (show / hide)
        @method toggle()
        @param {boolean} closeAll Whether to close all other editable containers when showing this one. Default true.
        **/  
        toggle: function(closeAll) {
            if(this.container && this.container.tip().is(':visible')) {
                this.hide();
            } else {
                this.show(closeAll);
            }
        },
        
        /*
        * called when form was submitted
        */          
        save: function(e, params) {
            //mark element with unsaved class if needed
            if(this.options.unsavedclass) {
                /*
                 Add unsaved css to element if:
                  - url is not user's function 
                  - value was not sent to server
                  - params.response === undefined, that means data was not sent
                  - value changed 
                */
                var sent = false;
                sent = sent || typeof this.options.url === 'function';
                sent = sent || this.options.display === false; 
                sent = sent || params.response !== undefined; 
                sent = sent || (this.options.savenochange && this.input.value2str(this.value) !== this.input.value2str(params.newValue)); 
                
                if(sent) {
                    this.$element.removeClass(this.options.unsavedclass); 
                } else {
                    this.$element.addClass(this.options.unsavedclass);                    
                }
            }
            
            //highlight when saving
            if(this.options.highlight) {
                var $e = this.$element,
                    bgColor = $e.css('background-color');
                    
                $e.css('background-color', this.options.highlight);
                setTimeout(function(){
                    if(bgColor === 'transparent') {
                        bgColor = ''; 
                    }
                    $e.css('background-color', bgColor);
                    $e.addClass('editable-bg-transition');
                    setTimeout(function(){
                       $e.removeClass('editable-bg-transition');  
                    }, 1700);
                }, 10);
            }
            
            //set new value
            this.setValue(params.newValue, false, params.response);
            
            /**        
            Fired when new value was submitted. You can use <code>$(this).data('editable')</code> to access to editable instance
            
            @event save 
            @param {Object} event event object
            @param {Object} params additional params
            @param {mixed} params.newValue submitted value
            @param {Object} params.response ajax response
            @example
            $('#username').on('save', function(e, params) {
                alert('Saved value: ' + params.newValue);
            });
            **/
            //event itself is triggered by editableContainer. Description here is only for documentation              
        },

        validate: function () {
            if (typeof this.options.validate === 'function') {
                return this.options.validate.call(this, this.value);
            }
        },
        
        /**
        Sets new value of editable
        @method setValue(value, convertStr)
        @param {mixed} value new value 
        @param {boolean} convertStr whether to convert value from string to internal format
        **/         
        setValue: function(value, convertStr, response) {
            if(convertStr) {
                this.value = this.input.str2value(value);
            } else {
                this.value = value;
            }
            if(this.container) {
                this.container.option('value', this.value);
            }
            $.when(this.render(response))
            .then($.proxy(function() {
                this.handleEmpty();
            }, this));
        },
        
        /**
        Activates input of visible container (e.g. set focus)
        @method activate()
        **/         
        activate: function() {
            if(this.container) {
               this.container.activate(); 
            }
        },
        
        /**
        Removes editable feature from element
        @method destroy()
        **/        
        destroy: function() {
            this.disable();
            
            if(this.container) {
               this.container.destroy(); 
            }
            
            this.input.destroy();

            if(this.options.toggle !== 'manual') {
                this.$element.removeClass('editable-click');
                this.$element.off(this.options.toggle + '.editable');
            } 
            
            this.$element.off("save.internal");
            
            this.$element.removeClass('editable editable-open editable-disabled');
            this.$element.removeData('editable');
        }        
    };

    /* EDITABLE PLUGIN DEFINITION
    * ======================= */

    /**
    jQuery method to initialize editable element.
    
    @method $().editable(options)
    @params {Object} options
    @example
    $('#username').editable({
        type: 'text',
        url: '/post',
        pk: 1
    });
    **/
    $.fn.editable = function (option) {
        //special API methods returning non-jquery object
        var result = {}, args = arguments, datakey = 'editable';
        switch (option) {
            /**
            Runs client-side validation for all matched editables
            
            @method validate()
            @returns {Object} validation errors map
            @example
            $('#username, #fullname').editable('validate');
            // possible result:
            {
              username: "username is required",
              fullname: "fullname should be minimum 3 letters length"
            }
            **/
            case 'validate':
                this.each(function () {
                    var $this = $(this), data = $this.data(datakey), error;
                    if (data && (error = data.validate())) {
                        result[data.options.name] = error;
                    }
                });
            return result;

            /**
            Returns current values of editable elements.   
            Note that it returns an **object** with name-value pairs, not a value itself. It allows to get data from several elements.    
            If value of some editable is `null` or `undefined` it is excluded from result object.
            When param `isSingle` is set to **true** - it is supposed you have single element and will return value of editable instead of object.   
             
            @method getValue()
            @param {bool} isSingle whether to return just value of single element
            @returns {Object} object of element names and values
            @example
            $('#username, #fullname').editable('getValue');
            //result:
            {
            username: "superuser",
            fullname: "John"
            }
            //isSingle = true
            $('#username').editable('getValue', true);
            //result "superuser" 
            **/
            case 'getValue':
                if(arguments.length === 2 && arguments[1] === true) { //isSingle = true
                    result = this.eq(0).data(datakey).value;
                } else {
                    this.each(function () {
                        var $this = $(this), data = $this.data(datakey);
                        if (data && data.value !== undefined && data.value !== null) {
                            result[data.options.name] = data.input.value2submit(data.value);
                        }
                    });
                }
            return result;

            /**
            This method collects values from several editable elements and submit them all to server.   
            Internally it runs client-side validation for all fields and submits only in case of success.  
            See <a href="#newrecord">creating new records</a> for details.
            
            @method submit(options)
            @param {object} options 
            @param {object} options.url url to submit data 
            @param {object} options.data additional data to submit
            @param {object} options.ajaxOptions additional ajax options
            @param {function} options.error(obj) error handler 
            @param {function} options.success(obj,config) success handler
            @returns {Object} jQuery object
            **/
            case 'submit':  //collects value, validate and submit to server for creating new record
                var config = arguments[1] || {},
                $elems = this,
                errors = this.editable('validate'),
                values;

                if($.isEmptyObject(errors)) {
                    values = this.editable('getValue'); 
                    if(config.data) {
                        $.extend(values, config.data);
                    }                    
                    
                    $.ajax($.extend({
                        url: config.url, 
                        data: values, 
                        type: 'POST'                        
                    }, config.ajaxOptions))
                    .success(function(response) {
                        //successful response 200 OK
                        if(typeof config.success === 'function') {
                            config.success.call($elems, response, config);
                        } 
                    })
                    .error(function(){  //ajax error
                        if(typeof config.error === 'function') {
                            config.error.apply($elems, arguments);
                        }
                    });
                } else { //client-side validation error
                    if(typeof config.error === 'function') {
                        config.error.call($elems, errors);
                    }
                }
            return this;
        }

        //return jquery object
        return this.each(function () {
            var $this = $(this), 
                data = $this.data(datakey), 
                options = typeof option === 'object' && option;

            //for delegated targets do not store `editable` object for element
            //it's allows several different selectors.
            //see: https://github.com/vitalets/x-editable/issues/312    
            if(options && options.selector) {
                data = new Editable(this, options);
                return; 
            }    
            
            if (!data) {
                $this.data(datakey, (data = new Editable(this, options)));
            }

            if (typeof option === 'string') { //call method 
                data[option].apply(data, Array.prototype.slice.call(args, 1));
            } 
        });
    };    
            

    $.fn.editable.defaults = {
        /**
        Type of input. Can be <code>text|textarea|select|date|checklist</code> and more

        @property type 
        @type string
        @default 'text'
        **/
        type: 'text',        
        /**
        Sets disabled state of editable

        @property disabled 
        @type boolean
        @default false
        **/         
        disabled: false,
        /**
        How to toggle editable. Can be <code>click|dblclick|mouseenter|manual</code>.   
        When set to <code>manual</code> you should manually call <code>show/hide</code> methods of editable.    
        **Note**: if you call <code>show</code> or <code>toggle</code> inside **click** handler of some DOM element, 
        you need to apply <code>e.stopPropagation()</code> because containers are being closed on any click on document.
        
        @example
        $('#edit-button').click(function(e) {
            e.stopPropagation();
            $('#username').editable('toggle');
        });

        @property toggle 
        @type string
        @default 'click'
        **/          
        toggle: 'click',
        /**
        Text shown when element is empty.

        @property emptytext 
        @type string
        @default 'Empty'
        **/         
        emptytext: 'Empty',
        /**
        Allows to automatically set element's text based on it's value. Can be <code>auto|always|never</code>. Useful for select and date.
        For example, if dropdown list is <code>{1: 'a', 2: 'b'}</code> and element's value set to <code>1</code>, it's html will be automatically set to <code>'a'</code>.  
        <code>auto</code> - text will be automatically set only if element is empty.  
        <code>always|never</code> - always(never) try to set element's text.

        @property autotext 
        @type string
        @default 'auto'
        **/          
        autotext: 'auto', 
        /**
        Initial value of input. If not set, taken from element's text.  
        Note, that if element's text is empty - text is automatically generated from value and can be customized (see `autotext` option).  
        For example, to display currency sign:
        @example
        <a id="price" data-type="text" data-value="100"></a>
        <script>
        $('#price').editable({
            ...
            display: function(value) {
              $(this).text(value + '$');
            } 
        }) 
        </script>
                
        @property value 
        @type mixed
        @default element's text
        **/
        value: null,
        /**
        Callback to perform custom displaying of value in element's text.  
        If `null`, default input's display used.  
        If `false`, no displaying methods will be called, element's text will never change.  
        Runs under element's scope.  
        _**Parameters:**_  
        
        * `value` current value to be displayed
        * `response` server response (if display called after ajax submit), since 1.4.0
         
        For _inputs with source_ (select, checklist) parameters are different:  
          
        * `value` current value to be displayed
        * `sourceData` array of items for current input (e.g. dropdown items) 
        * `response` server response (if display called after ajax submit), since 1.4.0
                  
        To get currently selected items use `$.fn.editableutils.itemsByValue(value, sourceData)`.
        
        @property display 
        @type function|boolean
        @default null
        @since 1.2.0
        @example
        display: function(value, sourceData) {
           //display checklist as comma-separated values
           var html = [],
               checked = $.fn.editableutils.itemsByValue(value, sourceData);
               
           if(checked.length) {
               $.each(checked, function(i, v) { html.push($.fn.editableutils.escape(v.text)); });
               $(this).html(html.join(', '));
           } else {
               $(this).empty(); 
           }
        }
        **/          
        display: null,
        /**
        Css class applied when editable text is empty.

        @property emptyclass 
        @type string
        @since 1.4.1        
        @default editable-empty
        **/        
        emptyclass: 'editable-empty',
        /**
        Css class applied when value was stored but not sent to server (`pk` is empty or `send = 'never'`).  
        You may set it to `null` if you work with editables locally and submit them together.  

        @property unsavedclass 
        @type string
        @since 1.4.1        
        @default editable-unsaved
        **/        
        unsavedclass: 'editable-unsaved',
        /**
        If selector is provided, editable will be delegated to the specified targets.  
        Usefull for dynamically generated DOM elements.  
        **Please note**, that delegated targets can't be initialized with `emptytext` and `autotext` options, 
        as they actually become editable only after first click.  
        You should manually set class `editable-click` to these elements.  
        Also, if element originally empty you should add class `editable-empty`, set `data-value=""` and write emptytext into element:

        @property selector 
        @type string
        @since 1.4.1        
        @default null
        @example
        <div id="user">
          <!-- empty -->
          <a href="#" data-name="username" data-type="text" class="editable-click editable-empty" data-value="" title="Username">Empty</a>
          <!-- non-empty -->
          <a href="#" data-name="group" data-type="select" data-source="/groups" data-value="1" class="editable-click" title="Group">Operator</a>
        </div>     
        
        <script>
        $('#user').editable({
            selector: 'a',
            url: '/post',
            pk: 1
        });
        </script>
        **/         
        selector: null,
        /**
        Color used to highlight element after update. Implemented via CSS3 transition, works in modern browsers.
        
        @property highlight 
        @type string|boolean
        @since 1.4.5        
        @default #FFFF80 
        **/
        highlight: '#FFFF80'
    };
    
}(window.jQuery));

/**
AbstractInput - base class for all editable inputs.
It defines interface to be implemented by any input type.
To create your own input you can inherit from this class.

@class abstractinput
**/
(function ($) {
    "use strict";

    //types
    $.fn.editabletypes = {};

    var AbstractInput = function () { };

    AbstractInput.prototype = {
       /**
        Initializes input

        @method init() 
        **/
       init: function(type, options, defaults) {
           this.type = type;
           this.options = $.extend({}, defaults, options);
       },

       /*
       this method called before render to init $tpl that is inserted in DOM
       */
       prerender: function() {
           this.$tpl = $(this.options.tpl); //whole tpl as jquery object    
           this.$input = this.$tpl;         //control itself, can be changed in render method
           this.$clear = null;              //clear button
           this.error = null;               //error message, if input cannot be rendered           
       },
       
       /**
        Renders input from tpl. Can return jQuery deferred object.
        Can be overwritten in child objects

        @method render()
       **/
       render: function() {

       }, 

       /**
        Sets element's html by value. 

        @method value2html(value, element)
        @param {mixed} value
        @param {DOMElement} element
       **/
       value2html: function(value, element) {
           $(element)[this.options.escape ? 'text' : 'html']($.trim(value));
       },

       /**
        Converts element's html to value

        @method html2value(html)
        @param {string} html
        @returns {mixed}
       **/
       html2value: function(html) {
           return $('<div>').html(html).text();
       },

       /**
        Converts value to string (for internal compare). For submitting to server used value2submit().

        @method value2str(value) 
        @param {mixed} value
        @returns {string}
       **/
       value2str: function(value) {
           return value;
       }, 

       /**
        Converts string received from server into value. Usually from `data-value` attribute.

        @method str2value(str)
        @param {string} str
        @returns {mixed}
       **/
       str2value: function(str) {
           return str;
       }, 
       
       /**
        Converts value for submitting to server. Result can be string or object.

        @method value2submit(value) 
        @param {mixed} value
        @returns {mixed}
       **/
       value2submit: function(value) {
           return value;
       },

       /**
        Sets value of input.

        @method value2input(value) 
        @param {mixed} value
       **/
       value2input: function(value) {
           this.$input.val(value);
       },

       /**
        Returns value of input. Value can be object (e.g. datepicker)

        @method input2value() 
       **/
       input2value: function() { 
           return this.$input.val();
       }, 

       /**
        Activates input. For text it sets focus.

        @method activate() 
       **/
       activate: function() {
           if(this.$input.is(':visible')) {
               this.$input.focus();
           }
       },

       /**
        Creates input.

        @method clear() 
       **/        
       clear: function() {
           this.$input.val(null);
       },

       /**
        method to escape html.
       **/
       escape: function(str) {
           return $('<div>').text(str).html();
       },
       
       /**
        attach handler to automatically submit form when value changed (useful when buttons not shown)
       **/
       autosubmit: function() {
        
       },
       
       /**
       Additional actions when destroying element 
       **/
       destroy: function() {
       },

       // -------- helper functions --------
       setClass: function() {          
           if(this.options.inputclass) {
               this.$input.addClass(this.options.inputclass); 
           } 
       },

       setAttr: function(attr) {
           if (this.options[attr] !== undefined && this.options[attr] !== null) {
               this.$input.attr(attr, this.options[attr]);
           } 
       },
       
       option: function(key, value) {
            this.options[key] = value;
       }
       
    };
        
    AbstractInput.defaults = {  
        /**
        HTML template of input. Normally you should not change it.

        @property tpl 
        @type string
        @default ''
        **/   
        tpl: '',
        /**
        CSS class automatically applied to input
        
        @property inputclass 
        @type string
        @default null
        **/         
        inputclass: null,
        
        /**
        If `true` - html will be escaped in content of element via $.text() method.  
        If `false` - html will not be escaped, $.html() used.  
        When you use own `display` function, this option obviosly has no effect.
        
        @property escape 
        @type boolean
        @since 1.5.0
        @default true
        **/         
        escape: true,
                
        //scope for external methods (e.g. source defined as function)
        //for internal use only
        scope: null,
        
        //need to re-declare showbuttons here to get it's value from common config (passed only options existing in defaults)
        showbuttons: true 
    };
    
    $.extend($.fn.editabletypes, {abstractinput: AbstractInput});
        
}(window.jQuery));

/**
List - abstract class for inputs that have source option loaded from js array or via ajax

@class list
@extends abstractinput
**/
(function ($) {
    "use strict";
    
    var List = function (options) {
       
    };

    $.fn.editableutils.inherit(List, $.fn.editabletypes.abstractinput);

    $.extend(List.prototype, {
        render: function () {
            var deferred = $.Deferred();

            this.error = null;
            this.onSourceReady(function () {
                this.renderList();
                deferred.resolve();
            }, function () {
                this.error = this.options.sourceError;
                deferred.resolve();
            });

            return deferred.promise();
        },

        html2value: function (html) {
            return null; //can't set value by text
        },
        
        value2html: function (value, element, display, response) {
            var deferred = $.Deferred(),
                success = function () {
                    if(typeof display === 'function') {
                        //custom display method
                        display.call(element, value, this.sourceData, response); 
                    } else {
                        this.value2htmlFinal(value, element);
                    }
                    deferred.resolve();
               };
            
            //for null value just call success without loading source
            if(value === null) {
               success.call(this);   
            } else {
               this.onSourceReady(success, function () { deferred.resolve(); });
            }

            return deferred.promise();
        },  

        // ------------- additional functions ------------

        onSourceReady: function (success, error) {
            //run source if it function
            var source;
            if ($.isFunction(this.options.source)) {
                source = this.options.source.call(this.options.scope);
                this.sourceData = null;
                //note: if function returns the same source as URL - sourceData will be taken from cahce and no extra request performed
            } else {
                source = this.options.source;
            }            
            
            //if allready loaded just call success
            if(this.options.sourceCache && $.isArray(this.sourceData)) {
                success.call(this);
                return; 
            }

            //try parse json in single quotes (for double quotes jquery does automatically)
            try {
                source = $.fn.editableutils.tryParseJson(source, false);
            } catch (e) {
                error.call(this);
                return;
            }

            //loading from url
            if (typeof source === 'string') {
                //try to get sourceData from cache
                if(this.options.sourceCache) {
                    var cacheID = source,
                    cache;

                    if (!$(document).data(cacheID)) {
                        $(document).data(cacheID, {});
                    }
                    cache = $(document).data(cacheID);

                    //check for cached data
                    if (cache.loading === false && cache.sourceData) { //take source from cache
                        this.sourceData = cache.sourceData;
                        this.doPrepend();
                        success.call(this);
                        return;
                    } else if (cache.loading === true) { //cache is loading, put callback in stack to be called later
                        cache.callbacks.push($.proxy(function () {
                            this.sourceData = cache.sourceData;
                            this.doPrepend();
                            success.call(this);
                        }, this));

                        //also collecting error callbacks
                        cache.err_callbacks.push($.proxy(error, this));
                        return;
                    } else { //no cache yet, activate it
                        cache.loading = true;
                        cache.callbacks = [];
                        cache.err_callbacks = [];
                    }
                }
                
                //ajaxOptions for source. Can be overwritten bt options.sourceOptions
                var ajaxOptions = $.extend({
                    url: source,
                    type: 'get',
                    cache: false,
                    dataType: 'json',
                    success: $.proxy(function (data) {
                        if(cache) {
                            cache.loading = false;
                        }
                        this.sourceData = this.makeArray(data);
                        if($.isArray(this.sourceData)) {
                            if(cache) {
                                //store result in cache
                                cache.sourceData = this.sourceData;
                                //run success callbacks for other fields waiting for this source
                                $.each(cache.callbacks, function () { this.call(); }); 
                            }
                            this.doPrepend();
                            success.call(this);
                        } else {
                            error.call(this);
                            if(cache) {
                                //run error callbacks for other fields waiting for this source
                                $.each(cache.err_callbacks, function () { this.call(); }); 
                            }
                        }
                    }, this),
                    error: $.proxy(function () {
                        error.call(this);
                        if(cache) {
                             cache.loading = false;
                             //run error callbacks for other fields
                             $.each(cache.err_callbacks, function () { this.call(); }); 
                        }
                    }, this)
                }, this.options.sourceOptions);
                
                //loading sourceData from server
                $.ajax(ajaxOptions);
                
            } else { //options as json/array
                this.sourceData = this.makeArray(source);
                    
                if($.isArray(this.sourceData)) {
                    this.doPrepend();
                    success.call(this);   
                } else {
                    error.call(this);
                }
            }
        },

        doPrepend: function () {
            if(this.options.prepend === null || this.options.prepend === undefined) {
                return;  
            }
            
            if(!$.isArray(this.prependData)) {
                //run prepend if it is function (once)
                if ($.isFunction(this.options.prepend)) {
                    this.options.prepend = this.options.prepend.call(this.options.scope);
                }
              
                //try parse json in single quotes
                this.options.prepend = $.fn.editableutils.tryParseJson(this.options.prepend, true);
                
                //convert prepend from string to object
                if (typeof this.options.prepend === 'string') {
                    this.options.prepend = {'': this.options.prepend};
                }
                
                this.prependData = this.makeArray(this.options.prepend);
            }

            if($.isArray(this.prependData) && $.isArray(this.sourceData)) {
                this.sourceData = this.prependData.concat(this.sourceData);
            }
        },

        /*
         renders input list
        */
        renderList: function() {
            // this method should be overwritten in child class
        },
       
         /*
         set element's html by value
        */
        value2htmlFinal: function(value, element) {
            // this method should be overwritten in child class
        },        

        /**
        * convert data to array suitable for sourceData, e.g. [{value: 1, text: 'abc'}, {...}]
        */
        makeArray: function(data) {
            var count, obj, result = [], item, iterateItem;
            if(!data || typeof data === 'string') {
                return null; 
            }

            if($.isArray(data)) { //array
                /* 
                   function to iterate inside item of array if item is object.
                   Caclulates count of keys in item and store in obj. 
                */
                iterateItem = function (k, v) {
                    obj = {value: k, text: v};
                    if(count++ >= 2) {
                        return false;// exit from `each` if item has more than one key.
                    }
                };
            
                for(var i = 0; i < data.length; i++) {
                    item = data[i]; 
                    if(typeof item === 'object') {
                        count = 0; //count of keys inside item
                        $.each(item, iterateItem);
                        //case: [{val1: 'text1'}, {val2: 'text2} ...]
                        if(count === 1) { 
                            result.push(obj); 
                            //case: [{value: 1, text: 'text1'}, {value: 2, text: 'text2'}, ...]
                        } else if(count > 1) {
                            //removed check of existance: item.hasOwnProperty('value') && item.hasOwnProperty('text')
                            if(item.children) {
                                item.children = this.makeArray(item.children);   
                            }
                            result.push(item);
                        }
                    } else {
                        //case: ['text1', 'text2' ...]
                        result.push({value: item, text: item}); 
                    }
                }
            } else {  //case: {val1: 'text1', val2: 'text2, ...}
                $.each(data, function (k, v) {
                    result.push({value: k, text: v});
                });  
            }
            return result;
        },
        
        option: function(key, value) {
            this.options[key] = value;
            if(key === 'source') {
                this.sourceData = null;
            }
            if(key === 'prepend') {
                this.prependData = null;
            }            
        }        

    });      

    List.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
        Source data for list.  
        If **array** - it should be in format: `[{value: 1, text: "text1"}, {value: 2, text: "text2"}, ...]`  
        For compability, object format is also supported: `{"1": "text1", "2": "text2" ...}` but it does not guarantee elements order.
        
        If **string** - considered ajax url to load items. In that case results will be cached for fields with the same source and name. See also `sourceCache` option.
          
        If **function**, it should return data in format above (since 1.4.0).
        
        Since 1.4.1 key `children` supported to render OPTGROUP (for **select** input only).  
        `[{text: "group1", children: [{value: 1, text: "text1"}, {value: 2, text: "text2"}]}, ...]` 

		
        @property source 
        @type string | array | object | function
        @default null
        **/         
        source: null, 
        /**
        Data automatically prepended to the beginning of dropdown list.
        
        @property prepend 
        @type string | array | object | function
        @default false
        **/         
        prepend: false,
        /**
        Error message when list cannot be loaded (e.g. ajax error)
        
        @property sourceError 
        @type string
        @default Error when loading list
        **/          
        sourceError: 'Error when loading list',
        /**
        if <code>true</code> and source is **string url** - results will be cached for fields with the same source.    
        Usefull for editable column in grid to prevent extra requests.
        
        @property sourceCache 
        @type boolean
        @default true
        @since 1.2.0
        **/        
        sourceCache: true,
        /**
        Additional ajax options to be used in $.ajax() when loading list from server.
        Useful to send extra parameters (`data` key) or change request method (`type` key).
        
        @property sourceOptions 
        @type object|function
        @default null
        @since 1.5.0
        **/        
        sourceOptions: null
    });

    $.fn.editabletypes.list = List;      

}(window.jQuery));

/**
Text input

@class text
@extends abstractinput
@final
@example
<a href="#" id="username" data-type="text" data-pk="1">awesome</a>
<script>
$(function(){
    $('#username').editable({
        url: '/post',
        title: 'Enter username'
    });
});
</script>
**/
(function ($) {
    "use strict";
    
    var Text = function (options) {
        this.init('text', options, Text.defaults);
    };

    $.fn.editableutils.inherit(Text, $.fn.editabletypes.abstractinput);

    $.extend(Text.prototype, {
        render: function() {
           this.renderClear();
           this.setClass();
           this.setAttr('placeholder');
        },
        
        activate: function() {
            if(this.$input.is(':visible')) {
                this.$input.focus();
                $.fn.editableutils.setCursorPosition(this.$input.get(0), this.$input.val().length);
                if(this.toggleClear) {
                    this.toggleClear();
                }
            }
        },
        
        //render clear button
        renderClear:  function() {
           if (this.options.clear) {
               this.$clear = $('<span class="editable-clear-x"></span>');
               this.$input.after(this.$clear)
                          .css('padding-right', 24)
                          .keyup($.proxy(function(e) {
                              //arrows, enter, tab, etc
                              if(~$.inArray(e.keyCode, [40,38,9,13,27])) {
                                return;
                              }                            

                              clearTimeout(this.t);
                              var that = this;
                              this.t = setTimeout(function() {
                                that.toggleClear(e);
                              }, 100);
                              
                          }, this))
                          .parent().css('position', 'relative');
                          
               this.$clear.click($.proxy(this.clear, this));                       
           }            
        },
        
        postrender: function() {
            /*
            //now `clear` is positioned via css
            if(this.$clear) {
                //can position clear button only here, when form is shown and height can be calculated
//                var h = this.$input.outerHeight(true) || 20,
                var h = this.$clear.parent().height(),
                    delta = (h - this.$clear.height()) / 2;
                    
                //this.$clear.css({bottom: delta, right: delta});
            }
            */ 
        },
        
        //show / hide clear button
        toggleClear: function(e) {
            if(!this.$clear) {
                return;
            }
            
            var len = this.$input.val().length,
                visible = this.$clear.is(':visible');
                 
            if(len && !visible) {
                this.$clear.show();
            } 
            
            if(!len && visible) {
                this.$clear.hide();
            } 
        },
        
        clear: function() {
           this.$clear.hide();
           this.$input.val('').focus();
        }          
    });

    Text.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
        @property tpl 
        @default <input type="text">
        **/         
        tpl: '<input type="text">',
        /**
        Placeholder attribute of input. Shown when input is empty.

        @property placeholder 
        @type string
        @default null
        **/             
        placeholder: null,
        
        /**
        Whether to show `clear` button 
        
        @property clear 
        @type boolean
        @default true        
        **/
        clear: true
    });

    $.fn.editabletypes.text = Text;

}(window.jQuery));

/**
Textarea input

@class textarea
@extends abstractinput
@final
@example
<a href="#" id="comments" data-type="textarea" data-pk="1">awesome comment!</a>
<script>
$(function(){
    $('#comments').editable({
        url: '/post',
        title: 'Enter comments',
        rows: 10
    });
});
</script>
**/
(function ($) {
    "use strict";
    
    var Textarea = function (options) {
        this.init('textarea', options, Textarea.defaults);
    };

    $.fn.editableutils.inherit(Textarea, $.fn.editabletypes.abstractinput);

    $.extend(Textarea.prototype, {
        render: function () {
            this.setClass();
            this.setAttr('placeholder');
            this.setAttr('rows');                        
            
            //ctrl + enter
            this.$input.keydown(function (e) {
                if (e.ctrlKey && e.which === 13) {
                    $(this).closest('form').submit();
                }
            });
        },
        
       //using `white-space: pre-wrap` solves \n  <--> BR conversion very elegant!
       /* 
       value2html: function(value, element) {
            var html = '', lines;
            if(value) {
                lines = value.split("\n");
                for (var i = 0; i < lines.length; i++) {
                    lines[i] = $('<div>').text(lines[i]).html();
                }
                html = lines.join('<br>');
            }
            $(element).html(html);
        },
       
        html2value: function(html) {
            if(!html) {
                return '';
            }

            var regex = new RegExp(String.fromCharCode(10), 'g');
            var lines = html.split(/<br\s*\/?>/i);
            for (var i = 0; i < lines.length; i++) {
                var text = $('<div>').html(lines[i]).text();

                // Remove newline characters (\n) to avoid them being converted by value2html() method
                // thus adding extra <br> tags
                text = text.replace(regex, '');

                lines[i] = text;
            }
            return lines.join("\n");
        },
         */
        activate: function() {
            $.fn.editabletypes.text.prototype.activate.call(this);
        }
    });

    Textarea.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
        @property tpl
        @default <textarea></textarea>
        **/
        tpl:'<textarea></textarea>',
        /**
        @property inputclass
        @default input-large
        **/
        inputclass: 'input-large',
        /**
        Placeholder attribute of input. Shown when input is empty.

        @property placeholder
        @type string
        @default null
        **/
        placeholder: null,
        /**
        Number of rows in textarea

        @property rows
        @type integer
        @default 7
        **/        
        rows: 7        
    });

    $.fn.editabletypes.textarea = Textarea;

}(window.jQuery));

/**
Select (dropdown)

@class select
@extends list
@final
@example
<a href="#" id="status" data-type="select" data-pk="1" data-url="/post" data-title="Select status"></a>
<script>
$(function(){
    $('#status').editable({
        value: 2,    
        source: [
              {value: 1, text: 'Active'},
              {value: 2, text: 'Blocked'},
              {value: 3, text: 'Deleted'}
           ]
    });
});
</script>
**/
(function ($) {
    "use strict";
    
    var Select = function (options) {
        this.init('select', options, Select.defaults);
    };

    $.fn.editableutils.inherit(Select, $.fn.editabletypes.list);

    $.extend(Select.prototype, {
        renderList: function() {
            this.$input.empty();

            var fillItems = function($el, data) {
                var attr;
                if($.isArray(data)) {
                    for(var i=0; i<data.length; i++) {
                        attr = {};
                        if(data[i].children) {
                            attr.label = data[i].text;
                            $el.append(fillItems($('<optgroup>', attr), data[i].children)); 
                        } else {
                            attr.value = data[i].value;
                            if(data[i].disabled) {
                                attr.disabled = true;
                            }
                            $el.append($('<option>', attr).text(data[i].text)); 
                        }
                    }
                }
                return $el;
            };        

            fillItems(this.$input, this.sourceData);
            
            this.setClass();
            
            //enter submit
            this.$input.on('keydown.editable', function (e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
            });            
        },
       
        value2htmlFinal: function(value, element) {
            var text = '', 
                items = $.fn.editableutils.itemsByValue(value, this.sourceData);
                
            if(items.length) {
                text = items[0].text;
            }
            
            //$(element).text(text);
            $.fn.editabletypes.abstractinput.prototype.value2html.call(this, text, element);
        },
        
        autosubmit: function() {
            this.$input.off('keydown.editable').on('change.editable', function(){
                $(this).closest('form').submit();
            });
        }
    });      

    Select.defaults = $.extend({}, $.fn.editabletypes.list.defaults, {
        /**
        @property tpl 
        @default <select></select>
        **/         
        tpl:'<select></select>'
    });

    $.fn.editabletypes.select = Select;      

}(window.jQuery));

/**
List of checkboxes. 
Internally value stored as javascript array of values.

@class checklist
@extends list
@final
@example
<a href="#" id="options" data-type="checklist" data-pk="1" data-url="/post" data-title="Select options"></a>
<script>
$(function(){
    $('#options').editable({
        value: [2, 3],    
        source: [
              {value: 1, text: 'option1'},
              {value: 2, text: 'option2'},
              {value: 3, text: 'option3'}
           ]
    });
});
</script>
**/
(function ($) {
    "use strict";
    
    var Checklist = function (options) {
        this.init('checklist', options, Checklist.defaults);
    };

    $.fn.editableutils.inherit(Checklist, $.fn.editabletypes.list);

    $.extend(Checklist.prototype, {
        renderList: function() {
            var $label, $div;
            
            this.$tpl.empty();
            
            if(!$.isArray(this.sourceData)) {
                return;
            }

            for(var i=0; i<this.sourceData.length; i++) {
                $label = $('<label>').append($('<input>', {
                                           type: 'checkbox',
                                           value: this.sourceData[i].value 
                                     }))
                                     .append($('<span>').text(' '+this.sourceData[i].text));
                
                $('<div>').append($label).appendTo(this.$tpl);
            }
            
            this.$input = this.$tpl.find('input[type="checkbox"]');
            this.setClass();
        },
       
       value2str: function(value) {
           return $.isArray(value) ? value.sort().join($.trim(this.options.separator)) : '';
       },  
       
       //parse separated string
        str2value: function(str) {
           var reg, value = null;
           if(typeof str === 'string' && str.length) {
               reg = new RegExp('\\s*'+$.trim(this.options.separator)+'\\s*');
               value = str.split(reg);
           } else if($.isArray(str)) {
               value = str; 
           } else {
               value = [str];
           }
           return value;
        },       
       
       //set checked on required checkboxes
       value2input: function(value) {
            this.$input.prop('checked', false);
            if($.isArray(value) && value.length) {
               this.$input.each(function(i, el) {
                   var $el = $(el);
                   // cannot use $.inArray as it performs strict comparison
                   $.each(value, function(j, val){
                       /*jslint eqeq: true*/
                       if($el.val() == val) {
                       /*jslint eqeq: false*/                           
                           $el.prop('checked', true);
                       }
                   });
               }); 
            }  
        },  
        
       input2value: function() { 
           var checked = [];
           this.$input.filter(':checked').each(function(i, el) {
               checked.push($(el).val());
           });
           return checked;
       },            
          
       //collect text of checked boxes
        value2htmlFinal: function(value, element) {
           var html = [],
               checked = $.fn.editableutils.itemsByValue(value, this.sourceData),
               escape = this.options.escape;
               
           if(checked.length) {
               $.each(checked, function(i, v) {
                   var text = escape ? $.fn.editableutils.escape(v.text) : v.text; 
                   html.push(text); 
               });
               $(element).html(html.join('<br>'));
           } else {
               $(element).empty(); 
           }
        },
        
       activate: function() {
           this.$input.first().focus();
       },
       
       autosubmit: function() {
           this.$input.on('keydown', function(e){
               if (e.which === 13) {
                   $(this).closest('form').submit();
               }
           });
       }
    });      

    Checklist.defaults = $.extend({}, $.fn.editabletypes.list.defaults, {
        /**
        @property tpl 
        @default <div></div>
        **/         
        tpl:'<div class="editable-checklist"></div>',
        
        /**
        @property inputclass 
        @type string
        @default null
        **/         
        inputclass: null,        
        
        /**
        Separator of values when reading from `data-value` attribute

        @property separator 
        @type string
        @default ','
        **/         
        separator: ','
    });

    $.fn.editabletypes.checklist = Checklist;      

}(window.jQuery));

/**
HTML5 input types.
Following types are supported:

* password
* email
* url
* tel
* number
* range
* time

Learn more about html5 inputs:  
http://www.w3.org/wiki/HTML5_form_additions  
To check browser compatibility please see:  
https://developer.mozilla.org/en-US/docs/HTML/Element/Input
            
@class html5types 
@extends text
@final
@since 1.3.0
@example
<a href="#" id="email" data-type="email" data-pk="1">admin@example.com</a>
<script>
$(function(){
    $('#email').editable({
        url: '/post',
        title: 'Enter email'
    });
});
</script>
**/

/**
@property tpl 
@default depends on type
**/ 

/*
Password
*/
(function ($) {
    "use strict";
    
    var Password = function (options) {
        this.init('password', options, Password.defaults);
    };
    $.fn.editableutils.inherit(Password, $.fn.editabletypes.text);
    $.extend(Password.prototype, {
       //do not display password, show '[hidden]' instead
       value2html: function(value, element) {
           if(value) {
               $(element).text('[hidden]');
           } else {
               $(element).empty(); 
           }
       },
       //as password not displayed, should not set value by html
       html2value: function(html) {
           return null;
       }       
    });    
    Password.defaults = $.extend({}, $.fn.editabletypes.text.defaults, {
        tpl: '<input type="password">'
    });
    $.fn.editabletypes.password = Password;
}(window.jQuery));


/*
Email
*/
(function ($) {
    "use strict";
    
    var Email = function (options) {
        this.init('email', options, Email.defaults);
    };
    $.fn.editableutils.inherit(Email, $.fn.editabletypes.text);
    Email.defaults = $.extend({}, $.fn.editabletypes.text.defaults, {
        tpl: '<input type="email">'
    });
    $.fn.editabletypes.email = Email;
}(window.jQuery));


/*
Url
*/
(function ($) {
    "use strict";
    
    var Url = function (options) {
        this.init('url', options, Url.defaults);
    };
    $.fn.editableutils.inherit(Url, $.fn.editabletypes.text);
    Url.defaults = $.extend({}, $.fn.editabletypes.text.defaults, {
        tpl: '<input type="url">'
    });
    $.fn.editabletypes.url = Url;
}(window.jQuery));


/*
Tel
*/
(function ($) {
    "use strict";
    
    var Tel = function (options) {
        this.init('tel', options, Tel.defaults);
    };
    $.fn.editableutils.inherit(Tel, $.fn.editabletypes.text);
    Tel.defaults = $.extend({}, $.fn.editabletypes.text.defaults, {
        tpl: '<input type="tel">'
    });
    $.fn.editabletypes.tel = Tel;
}(window.jQuery));


/*
Number
*/
(function ($) {
    "use strict";
    
    var NumberInput = function (options) {
        this.init('number', options, NumberInput.defaults);
    };
    $.fn.editableutils.inherit(NumberInput, $.fn.editabletypes.text);
    $.extend(NumberInput.prototype, {
         render: function () {
            NumberInput.superclass.render.call(this);
            this.setAttr('min');
            this.setAttr('max');
            this.setAttr('step');
        },
        postrender: function() {
            if(this.$clear) {
                //increase right ffset  for up/down arrows
                this.$clear.css({right: 24});
                /*
                //can position clear button only here, when form is shown and height can be calculated
                var h = this.$input.outerHeight(true) || 20,
                    delta = (h - this.$clear.height()) / 2;
                
                //add 12px to offset right for up/down arrows    
                this.$clear.css({top: delta, right: delta + 16});
                */
            } 
        }        
    });     
    NumberInput.defaults = $.extend({}, $.fn.editabletypes.text.defaults, {
        tpl: '<input type="number">',
        inputclass: 'input-mini',
        min: null,
        max: null,
        step: null
    });
    $.fn.editabletypes.number = NumberInput;
}(window.jQuery));


/*
Range (inherit from number)
*/
(function ($) {
    "use strict";
    
    var Range = function (options) {
        this.init('range', options, Range.defaults);
    };
    $.fn.editableutils.inherit(Range, $.fn.editabletypes.number);
    $.extend(Range.prototype, {
        render: function () {
            this.$input = this.$tpl.filter('input');
            
            this.setClass();
            this.setAttr('min');
            this.setAttr('max');
            this.setAttr('step');           
            
            this.$input.on('input', function(){
                $(this).siblings('output').text($(this).val()); 
            });  
        },
        activate: function() {
            this.$input.focus();
        }         
    });
    Range.defaults = $.extend({}, $.fn.editabletypes.number.defaults, {
        tpl: '<input type="range"><output style="width: 30px; display: inline-block"></output>',
        inputclass: 'input-medium'
    });
    $.fn.editabletypes.range = Range;
}(window.jQuery));

/*
Time
*/
(function ($) {
    "use strict";

    var Time = function (options) {
        this.init('time', options, Time.defaults);
    };
    //inherit from abstract, as inheritance from text gives selection error.
    $.fn.editableutils.inherit(Time, $.fn.editabletypes.abstractinput);
    $.extend(Time.prototype, {
        render: function() {
           this.setClass();
        }        
    });
    Time.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        tpl: '<input type="time">'
    });
    $.fn.editabletypes.time = Time;
}(window.jQuery));

/**
Select2 input. Based on amazing work of Igor Vaynberg https://github.com/ivaynberg/select2.  
Please see [original select2 docs](http://ivaynberg.github.com/select2) for detailed description and options.  
 
You should manually download and include select2 distributive:  

    <link href="select2/select2.css" rel="stylesheet" type="text/css"></link>  
    <script src="select2/select2.js"></script>  
    
To make it **bootstrap-styled** you can use css from [here](https://github.com/t0m/select2-bootstrap-css): 

    <link href="select2-bootstrap.css" rel="stylesheet" type="text/css"></link>    
    
**Note:** currently `autotext` feature does not work for select2 with `ajax` remote source.    
You need initially put both `data-value` and element's text youself:    

    <a href="#" data-type="select2" data-value="1">Text1</a>
    
    
@class select2
@extends abstractinput
@since 1.4.1
@final
@example
<a href="#" id="country" data-type="select2" data-pk="1" data-value="ru" data-url="/post" data-title="Select country"></a>
<script>
$(function(){
    //local source
    $('#country').editable({
        source: [
              {id: 'gb', text: 'Great Britain'},
              {id: 'us', text: 'United States'},
              {id: 'ru', text: 'Russia'}
           ],
        select2: {
           multiple: true
        }
    });
    //remote source (simple)
    $('#country').editable({
        source: '/getCountries'  
    });
    //remote source (advanced)
    $('#country').editable({
        select2: {
            placeholder: 'Select Country',
            allowClear: true,
            minimumInputLength: 3,
            id: function (item) {
                return item.CountryId;
            },
            ajax: {
                url: '/getCountries',
                dataType: 'json',
                data: function (term, page) {
                    return { query: term };
                },
                results: function (data, page) {
                    return { results: data };
                }
            },
            formatResult: function (item) {
                return item.CountryName;
            },
            formatSelection: function (item) {
                return item.CountryName;
            },
            initSelection: function (element, callback) {
                return $.get('/getCountryById', { query: element.val() }, function (data) {
                    callback(data);
                });
            } 
        }  
    });
});
</script>
**/
(function ($) {
    "use strict";
    
    var Constructor = function (options) {
        this.init('select2', options, Constructor.defaults);

        options.select2 = options.select2 || {};

        this.sourceData = null;
        
        //placeholder
        if(options.placeholder) {
            options.select2.placeholder = options.placeholder;
        }
       
        //if not `tags` mode, use source
        if(!options.select2.tags && options.source) {
            var source = options.source;
            //if source is function, call it (once!)
            if ($.isFunction(options.source)) {
                source = options.source.call(options.scope);
            }               

            if (typeof source === 'string') {
                options.select2.ajax = options.select2.ajax || {};
                //some default ajax params
                if(!options.select2.ajax.data) {
                    options.select2.ajax.data = function(term) {return { query:term };};
                }
                if(!options.select2.ajax.results) {
                    options.select2.ajax.results = function(data) { return {results:data };};
                }
                options.select2.ajax.url = source;
            } else {
                //check format and convert x-editable format to select2 format (if needed)
                this.sourceData = this.convertSource(source);
                options.select2.data = this.sourceData;
            }
        } 
           
        //overriding objects in config (as by default jQuery extend() is not recursive)
        this.options.select2 = $.extend({}, Constructor.defaults.select2, options.select2);
        
        //detect whether it is multi-valued
        this.isMultiple = this.options.select2.tags || this.options.select2.multiple;
        this.isRemote = ('ajax' in this.options.select2);
        
        //store function returning ID of item
        //should be here as used inautotext for local source
        this.idFunc = this.options.select2.id;
        if (typeof(this.idFunc) !== "function") {
            var idKey = this.idFunc || 'id';
            this.idFunc = function (e) { return e[idKey]; };
        }
        
        //store function that renders text in select2
        this.formatSelection = this.options.select2.formatSelection; 
        if (typeof(this.formatSelection) !== "function") {
            this.formatSelection = function (e) { return e.text; };
        }       
    };

    $.fn.editableutils.inherit(Constructor, $.fn.editabletypes.abstractinput);

    $.extend(Constructor.prototype, {
        render: function() {
            this.setClass();

            //can not apply select2 here as it calls initSelection 
            //over input that does not have correct value yet.
            //apply select2 only in value2input
            //this.$input.select2(this.options.select2);

            //when data is loaded via ajax, we need to know when it's done to populate listData
            if(this.isRemote) {
                //listen to loaded event to populate data
                this.$input.on('select2-loaded', $.proxy(function(e) {
                    this.sourceData = e.items.results;
                }, this));
            }

            //trigger resize of editableform to re-position container in multi-valued mode           
            if(this.isMultiple) {
               this.$input.on('change', function() {
                   $(this).closest('form').parent().triggerHandler('resize');
               }); 
            }
       },
       
       value2html: function(value, element) {
           var text = '', data,
               that = this;
           
           if(this.options.select2.tags) { //in tags mode just assign value
              data = value; 
              //data = $.fn.editableutils.itemsByValue(value, this.options.select2.tags, this.idFunc); 
           } else if(this.sourceData) {
              data = $.fn.editableutils.itemsByValue(value, this.sourceData, this.idFunc); 
           } else {
              //can not get list of possible values 
              //(e.g. autotext for select2 with ajax source) 
           }
           
           //data may be array (when multiple values allowed)          
           if($.isArray(data)) {
               //collect selected data and show with separator
               text = [];
               $.each(data, function(k, v){
                   text.push(v && typeof v === 'object' ? that.formatSelection(v) : v); 
               });                   
           } else if(data) {
               text = that.formatSelection(data);  
           }

           text = $.isArray(text) ? text.join(this.options.viewseparator) : text;

           //$(element).text(text);
           Constructor.superclass.value2html.call(this, text, element); 
       },       
        
       html2value: function(html) {
           return this.options.select2.tags ? this.str2value(html, this.options.viewseparator) : null;
       }, 
       
       value2input: function(value) {
           //for local source use data directly from source (to allow autotext)
           /*
           if(!this.isRemote && !this.isMultiple) {
               var items = $.fn.editableutils.itemsByValue(value, this.sourceData, this.idFunc);
               if(items.length) {
                   this.$input.select2('data', items[0]);
                   return;
               }
           } 
           */
           
           //for remote source just set value, text is updated by initSelection
           if(!this.$input.data('select2')) {
               this.$input.val(value);
               this.$input.select2(this.options.select2);
           } else {
               //second argument needed to separate initial change from user's click (for autosubmit)   
               this.$input.val(value).trigger('change', true); 
           }
           
           //if remote source AND no user's initSelection provided --> try to use element's text
           if(this.isRemote && !this.isMultiple && !this.options.select2.initSelection) {
               var customId = this.options.select2.id,
                   customText = this.options.select2.formatSelection;
               if(!customId && !customText) {      
                   var data = {id: value, text: $(this.options.scope).text()};
                   this.$input.select2('data', data); 
               }
           }
       },
       
       input2value: function() { 
           return this.$input.select2('val');
       },

       str2value: function(str, separator) {
            if(typeof str !== 'string' || !this.isMultiple) {
                return str;
            }
            
            separator = separator || this.options.select2.separator || $.fn.select2.defaults.separator;
            
            var val, i, l;
                
            if (str === null || str.length < 1) {
                return null;
            }
            val = str.split(separator);
            for (i = 0, l = val.length; i < l; i = i + 1) {
                val[i] = $.trim(val[i]);
            }
            
            return val;
       },
       
        autosubmit: function() {
            this.$input.on('change', function(e, isInitial){
                if(!isInitial) {
                  $(this).closest('form').submit();
                }
            });
        },
        
        /*
        Converts source from x-editable format: {value: 1, text: "1"} to
        select2 format: {id: 1, text: "1"}
        */
        convertSource: function(source) {
            if($.isArray(source) && source.length && source[0].value !== undefined) {
                for(var i = 0; i<source.length; i++) {
                    if(source[i].value !== undefined) {
                        source[i].id = source[i].value;
                        delete source[i].value;
                    }
                }
            }
            return source;            
        },
        
        destroy: function() {
            if(this.$input.data('select2')) {
                this.$input.select2('destroy');
            }
        }               
        
    });      

    Constructor.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
        @property tpl 
        @default <input type="hidden">
        **/         
        tpl:'<input type="hidden">',
        /**
        Configuration of select2. [Full list of options](http://ivaynberg.github.com/select2).
        
        @property select2 
        @type object
        @default null
        **/
        select2: null,
        /**
        Placeholder attribute of select

        @property placeholder 
        @type string
        @default null
        **/             
        placeholder: null,
        /**
        Source data for select. It will be assigned to select2 `data` property and kept here just for convenience.
        Please note, that format is different from simple `select` input: use 'id' instead of 'value'.
        E.g. `[{id: 1, text: "text1"}, {id: 2, text: "text2"}, ...]`.  
        
        @property source 
        @type array|string|function
        @default null        
        **/
        source: null,
        /**
        Separator used to display tags. 
        
        @property viewseparator 
        @type string
        @default ', '        
        **/
        viewseparator: ', '
    });

    $.fn.editabletypes.select2 = Constructor;      
    
}(window.jQuery));

/**
Combodate input - dropdown date and time picker.    
Based on [combodate](http://vitalets.github.com/combodate) plugin (included). To use it you should manually include [momentjs](http://momentjs.com).

    <script src="js/moment.min.js"></script>
   
Allows to input:

* only date
* only time 
* both date and time  

Please note, that format is taken from momentjs and **not compatible** with bootstrap-datepicker / jquery UI datepicker.  
Internally value stored as `momentjs` object. 

@class combodate
@extends abstractinput
@final
@since 1.4.0
@example
<a href="#" id="dob" data-type="combodate" data-pk="1" data-url="/post" data-value="1984-05-15" data-title="Select date"></a>
<script>
$(function(){
    $('#dob').editable({
        format: 'YYYY-MM-DD',    
        viewformat: 'DD.MM.YYYY',    
        template: 'D / MMMM / YYYY',    
        combodate: {
                minYear: 2000,
                maxYear: 2015,
                minuteStep: 1
           }
        }
    });
});
</script>
**/

/*global moment*/

(function ($) {
    "use strict";
    
    var Constructor = function (options) {
        this.init('combodate', options, Constructor.defaults);
        
        //by default viewformat equals to format
        if(!this.options.viewformat) {
            this.options.viewformat = this.options.format;
        }        
        
        //try parse combodate config defined as json string in data-combodate
        options.combodate = $.fn.editableutils.tryParseJson(options.combodate, true);

        //overriding combodate config (as by default jQuery extend() is not recursive)
        this.options.combodate = $.extend({}, Constructor.defaults.combodate, options.combodate, {
            format: this.options.format,
            template: this.options.template
        });
    };

    $.fn.editableutils.inherit(Constructor, $.fn.editabletypes.abstractinput);    
    
    $.extend(Constructor.prototype, {
        render: function () {
            this.$input.combodate(this.options.combodate);
                    
            if($.fn.editableform.engine === 'bs3') {
                this.$input.siblings().find('select').addClass('form-control');
            }
            
            if(this.options.inputclass) {
                this.$input.siblings().find('select').addClass(this.options.inputclass);
            }            
            //"clear" link
            /*
            if(this.options.clear) {
                this.$clear = $('<a href="#"></a>').html(this.options.clear).click($.proxy(function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    this.clear();
                }, this));
                
                this.$tpl.parent().append($('<div class="editable-clear">').append(this.$clear));  
            } 
            */               
        },
        
        value2html: function(value, element) {
            var text = value ? value.format(this.options.viewformat) : '';
            //$(element).text(text);
            Constructor.superclass.value2html.call(this, text, element);  
        },

        html2value: function(html) {
            return html ? moment(html, this.options.viewformat) : null;
        },   
        
        value2str: function(value) {
            return value ? value.format(this.options.format) : '';
       }, 
       
       str2value: function(str) {
           return str ? moment(str, this.options.format) : null;
       }, 
       
       value2submit: function(value) {
           return this.value2str(value);
       },                    

       value2input: function(value) {
           this.$input.combodate('setValue', value);
       },
        
       input2value: function() { 
           return this.$input.combodate('getValue', null);
       },       
       
       activate: function() {
           this.$input.siblings('.combodate').find('select').eq(0).focus();
       },
       
       /*
       clear:  function() {
          this.$input.data('datepicker').date = null;
          this.$input.find('.active').removeClass('active');
       },
       */
       
       autosubmit: function() {
           
       }

    });
    
    Constructor.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
        @property tpl 
        @default <input type="text">
        **/         
        tpl:'<input type="text">',
        /**
        @property inputclass 
        @default null
        **/         
        inputclass: null,
        /**
        Format used for sending value to server. Also applied when converting date from <code>data-value</code> attribute.<br>
        See list of tokens in [momentjs docs](http://momentjs.com/docs/#/parsing/string-format)  
        
        @property format 
        @type string
        @default YYYY-MM-DD
        **/         
        format:'YYYY-MM-DD',
        /**
        Format used for displaying date. Also applied when converting date from element's text on init.   
        If not specified equals to `format`.
        
        @property viewformat 
        @type string
        @default null
        **/          
        viewformat: null,        
        /**
        Template used for displaying dropdowns.
        
        @property template 
        @type string
        @default D / MMM / YYYY
        **/          
        template: 'D / MMM / YYYY',  
        /**
        Configuration of combodate.
        Full list of options: http://vitalets.github.com/combodate/#docs
        
        @property combodate 
        @type object
        @default null
        **/
        combodate: null
        
        /*
        (not implemented yet)
        Text shown as clear date button. 
        If <code>false</code> clear button will not be rendered.
        
        @property clear 
        @type boolean|string
        @default 'x clear'         
        */
        //clear: '&times; clear'
    });   

    $.fn.editabletypes.combodate = Constructor;

}(window.jQuery));

/*
Editableform based on Twitter Bootstrap 3
*/
(function ($) {
    "use strict";
    
    //store parent methods
    var pInitInput = $.fn.editableform.Constructor.prototype.initInput;
    
    $.extend($.fn.editableform.Constructor.prototype, {
        initTemplate: function() {
            this.$form = $($.fn.editableform.template); 
            this.$form.find('.control-group').addClass('form-group');
            this.$form.find('.editable-error-block').addClass('help-block');
        },
        initInput: function() {  
            pInitInput.apply(this);

            //for bs3 set default class `input-sm` to standard inputs
            var emptyInputClass = this.input.options.inputclass === null || this.input.options.inputclass === false;
            var defaultClass = 'input-sm';
            
            //bs3 add `form-control` class to standard inputs
            var stdtypes = 'text,select,textarea,password,email,url,tel,number,range,time,typeaheadjs'.split(','); 
            if(~$.inArray(this.input.type, stdtypes)) {
                this.input.$input.addClass('form-control');
                if(emptyInputClass) {
                    this.input.options.inputclass = defaultClass;
                    this.input.$input.addClass(defaultClass);
                }
            }             
        
            //apply bs3 size class also to buttons (to fit size of control)
            var $btn = this.$form.find('.editable-buttons');
            var classes = emptyInputClass ? [defaultClass] : this.input.options.inputclass.split(' ');
            for(var i=0; i<classes.length; i++) {
                // `btn-sm` is default now
                /*
                if(classes[i].toLowerCase() === 'input-sm') { 
                    $btn.find('button').addClass('btn-sm');  
                }
                */
                if(classes[i].toLowerCase() === 'input-lg') {
                    $btn.find('button').removeClass('btn-sm').addClass('btn-lg'); 
                }
            }
        }
    });    
    
    //buttons
    $.fn.editableform.buttons = 
      '<button type="submit" class="btn btn-primary btn-sm editable-submit">'+
        '<i class="icon-ok"></i>'+
      '</button>'+
      '<button type="button" class="btn btn-default btn-sm editable-cancel">'+
        '<i class="icon-remove"></i>'+
      '</button>';         
    
    //error classes
    $.fn.editableform.errorGroupClass = 'has-error';
    $.fn.editableform.errorBlockClass = null;  
    //engine
    $.fn.editableform.engine = 'bs3';  
}(window.jQuery));
/**
* Editable Popover3 (for Bootstrap 3) 
* ---------------------
* requires bootstrap-popover.js
*/
(function ($) {
    "use strict";

    //extend methods
    $.extend($.fn.editableContainer.Popup.prototype, {
        containerName: 'popover',
        containerDataName: 'bs.popover',
        innerCss: '.popover-content',
        defaults: $.fn.popover.Constructor.DEFAULTS,

        initContainer: function(){
            $.extend(this.containerOptions, {
                trigger: 'manual',
                selector: false,
                content: ' ',
                template: this.defaults.template
            });
            
            //as template property is used in inputs, hide it from popover
            var t;
            if(this.$element.data('template')) {
               t = this.$element.data('template');
               this.$element.removeData('template');  
            } 
            
            this.call(this.containerOptions);
            
            if(t) {
               //restore data('template')
               this.$element.data('template', t); 
            }
        }, 
        
        /* show */
        innerShow: function () {
            this.call('show');                
        },  
        
        /* hide */
        innerHide: function () {
            this.call('hide');       
        }, 
        
        /* destroy */
        innerDestroy: function() {
            this.call('destroy');
        },                               
        
        setContainerOption: function(key, value) {
            this.container().options[key] = value; 
        },               

        /**
        * move popover to new position. This function mainly copied from bootstrap-popover.
        */
        /*jshint laxcomma: true, eqeqeq: false*/
        setPosition: function () { 

            (function() {
            /*    
                var $tip = this.tip()
                , inside
                , pos
                , actualWidth
                , actualHeight
                , placement
                , tp
                , tpt
                , tpb
                , tpl
                , tpr;

                placement = typeof this.options.placement === 'function' ?
                this.options.placement.call(this, $tip[0], this.$element[0]) :
                this.options.placement;

                inside = /in/.test(placement);
               
                $tip
              //  .detach()
              //vitalets: remove any placement class because otherwise they dont influence on re-positioning of visible popover
                .removeClass('top right bottom left')
                .css({ top: 0, left: 0, display: 'block' });
              //  .insertAfter(this.$element);
               
                pos = this.getPosition(inside);

                actualWidth = $tip[0].offsetWidth;
                actualHeight = $tip[0].offsetHeight;

                placement = inside ? placement.split(' ')[1] : placement;

                tpb = {top: pos.top + pos.height, left: pos.left + pos.width / 2 - actualWidth / 2};
                tpt = {top: pos.top - actualHeight, left: pos.left + pos.width / 2 - actualWidth / 2};
                tpl = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left - actualWidth};
                tpr = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left + pos.width};

                switch (placement) {
                    case 'bottom':
                        if ((tpb.top + actualHeight) > ($(window).scrollTop() + $(window).height())) {
                            if (tpt.top > $(window).scrollTop()) {
                                placement = 'top';
                            } else if ((tpr.left + actualWidth) < ($(window).scrollLeft() + $(window).width())) {
                                placement = 'right';
                            } else if (tpl.left > $(window).scrollLeft()) {
                                placement = 'left';
                            } else {
                                placement = 'right';
                            }
                        }
                        break;
                    case 'top':
                        if (tpt.top < $(window).scrollTop()) {
                            if ((tpb.top + actualHeight) < ($(window).scrollTop() + $(window).height())) {
                                placement = 'bottom';
                            } else if ((tpr.left + actualWidth) < ($(window).scrollLeft() + $(window).width())) {
                                placement = 'right';
                            } else if (tpl.left > $(window).scrollLeft()) {
                                placement = 'left';
                            } else {
                                placement = 'right';
                            }
                        }
                        break;
                    case 'left':
                        if (tpl.left < $(window).scrollLeft()) {
                            if ((tpr.left + actualWidth) < ($(window).scrollLeft() + $(window).width())) {
                                placement = 'right';
                            } else if (tpt.top > $(window).scrollTop()) {
                                placement = 'top';
                            } else if (tpt.top > $(window).scrollTop()) {
                                placement = 'bottom';
                            } else {
                                placement = 'right';
                            }
                        }
                        break;
                    case 'right':
                        if ((tpr.left + actualWidth) > ($(window).scrollLeft() + $(window).width())) {
                            if (tpl.left > $(window).scrollLeft()) {
                                placement = 'left';
                            } else if (tpt.top > $(window).scrollTop()) {
                                placement = 'top';
                            } else if (tpt.top > $(window).scrollTop()) {
                                placement = 'bottom';
                            }
                        }
                        break;
                }

                switch (placement) {
                    case 'bottom':
                        tp = tpb;
                        break;
                    case 'top':
                        tp = tpt;
                        break;
                    case 'left':
                        tp = tpl;
                        break;
                    case 'right':
                        tp = tpr;
                        break;
                }

                $tip
                .offset(tp)
                .addClass(placement)
                .addClass('in');
           */
                     
           
            var $tip = this.tip();
            
            var placement = typeof this.options.placement == 'function' ?
                this.options.placement.call(this, $tip[0], this.$element[0]) :
                this.options.placement;            
            
            
            var pos = this.getPosition();
            var actualWidth = $tip[0].offsetWidth;
            var actualHeight = $tip[0].offsetHeight;
            var calculatedOffset = this.getCalculatedOffset(placement, pos, actualWidth, actualHeight);

            this.applyPlacement(calculatedOffset, placement);            
           
           
                
            }).call(this.container());
          /*jshint laxcomma: false, eqeqeq: true*/  
        }            
    });

}(window.jQuery));

/**
Bootstrap-datepicker.  
Description and examples: https://github.com/eternicode/bootstrap-datepicker.  
For **i18n** you should include js file from here: https://github.com/eternicode/bootstrap-datepicker/tree/master/js/locales
and set `language` option.  
Since 1.4.0 date has different appearance in **popup** and **inline** modes. 

@class date
@extends abstractinput
@final
@example
<a href="#" id="dob" data-type="date" data-pk="1" data-url="/post" data-title="Select date">15/05/1984</a>
<script>
$(function(){
    $('#dob').editable({
        format: 'yyyy-mm-dd',    
        viewformat: 'dd/mm/yyyy',    
        datepicker: {
                weekStart: 1
           }
        }
    });
});
</script>
**/
(function ($) {
    "use strict";
    
    //store bootstrap-datepicker as bdateicker to exclude conflict with jQuery UI one
    $.fn.bdatepicker = $.fn.datepicker.noConflict();
    if(!$.fn.datepicker) { //if there were no other datepickers, keep also original name
        $.fn.datepicker = $.fn.bdatepicker;    
    }    
    
    var Date = function (options) {
        this.init('date', options, Date.defaults);
        this.initPicker(options, Date.defaults);
    };

    $.fn.editableutils.inherit(Date, $.fn.editabletypes.abstractinput);    
    
    $.extend(Date.prototype, {
        initPicker: function(options, defaults) {
            //'format' is set directly from settings or data-* attributes

            //by default viewformat equals to format
            if(!this.options.viewformat) {
                this.options.viewformat = this.options.format;
            }
            
            //try parse datepicker config defined as json string in data-datepicker
            options.datepicker = $.fn.editableutils.tryParseJson(options.datepicker, true);
            
            //overriding datepicker config (as by default jQuery extend() is not recursive)
            //since 1.4 datepicker internally uses viewformat instead of format. Format is for submit only
            this.options.datepicker = $.extend({}, defaults.datepicker, options.datepicker, {
                format: this.options.viewformat
            });
            
            //language
            this.options.datepicker.language = this.options.datepicker.language || 'en'; 

            //store DPglobal
            this.dpg = $.fn.bdatepicker.DPGlobal; 

            //store parsed formats
            this.parsedFormat = this.dpg.parseFormat(this.options.format);
            this.parsedViewFormat = this.dpg.parseFormat(this.options.viewformat);            
        },
        
        render: function () {
            this.$input.bdatepicker(this.options.datepicker);
            
            //"clear" link
            if(this.options.clear) {
                this.$clear = $('<a href="#"></a>').html(this.options.clear).click($.proxy(function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    this.clear();
                }, this));
                
                this.$tpl.parent().append($('<div class="editable-clear">').append(this.$clear));  
            }                
        },
        
        value2html: function(value, element) {
           var text = value ? this.dpg.formatDate(value, this.parsedViewFormat, this.options.datepicker.language) : '';
           Date.superclass.value2html.call(this, text, element); 
        },

        html2value: function(html) {
            return this.parseDate(html, this.parsedViewFormat);
        },   

        value2str: function(value) {
            return value ? this.dpg.formatDate(value, this.parsedFormat, this.options.datepicker.language) : '';
        }, 

        str2value: function(str) {
            return this.parseDate(str, this.parsedFormat);
        }, 

        value2submit: function(value) {
            return this.value2str(value);
        },                    

        value2input: function(value) {
            this.$input.bdatepicker('update', value);
        },

        input2value: function() { 
            return this.$input.data('datepicker').date;
        },       

        activate: function() {
        },

        clear:  function() {
            this.$input.data('datepicker').date = null;
            this.$input.find('.active').removeClass('active');
            if(!this.options.showbuttons) {
                this.$input.closest('form').submit(); 
            }
        },

        autosubmit: function() {
            this.$input.on('mouseup', '.day', function(e){
                if($(e.currentTarget).is('.old') || $(e.currentTarget).is('.new')) {
                    return;
                }
                var $form = $(this).closest('form');
                setTimeout(function() {
                    $form.submit();
                }, 200);
            });
           //changedate is not suitable as it triggered when showing datepicker. see #149
           /*
           this.$input.on('changeDate', function(e){
               var $form = $(this).closest('form');
               setTimeout(function() {
                   $form.submit();
               }, 200);
           });
           */
       },
       
       /*
        For incorrect date bootstrap-datepicker returns current date that is not suitable
        for datefield.
        This function returns null for incorrect date.  
       */
       parseDate: function(str, format) {
           var date = null, formattedBack;
           if(str) {
               date = this.dpg.parseDate(str, format, this.options.datepicker.language);
               if(typeof str === 'string') {
                   formattedBack = this.dpg.formatDate(date, format, this.options.datepicker.language);
                   if(str !== formattedBack) {
                       date = null;
                   }
               }
           }
           return date;
       }

    });

    Date.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
        @property tpl 
        @default <div></div>
        **/         
        tpl:'<div class="editable-date well"></div>',
        /**
        @property inputclass 
        @default null
        **/
        inputclass: null,
        /**
        Format used for sending value to server. Also applied when converting date from <code>data-value</code> attribute.<br>
        Possible tokens are: <code>d, dd, m, mm, yy, yyyy</code>  

        @property format 
        @type string
        @default yyyy-mm-dd
        **/
        format:'yyyy-mm-dd',
        /**
        Format used for displaying date. Also applied when converting date from element's text on init.   
        If not specified equals to <code>format</code>

        @property viewformat 
        @type string
        @default null
        **/
        viewformat: null,
        /**
        Configuration of datepicker.
        Full list of options: http://vitalets.github.com/bootstrap-datepicker

        @property datepicker 
        @type object
        @default {
            weekStart: 0,
            startView: 0,
            minViewMode: 0,
            autoclose: false
        }
        **/
        datepicker:{
            weekStart: 0,
            startView: 0,
            minViewMode: 0,
            autoclose: false
        },
        /**
        Text shown as clear date button. 
        If <code>false</code> clear button will not be rendered.

        @property clear 
        @type boolean|string
        @default 'x clear'
        **/
        clear: '&times; clear'
    });

    $.fn.editabletypes.date = Date;

}(window.jQuery));

/**
Bootstrap datefield input - modification for inline mode.
Shows normal <input type="text"> and binds popup datepicker.  
Automatically shown in inline mode.

@class datefield
@extends date

@since 1.4.0
**/
(function ($) {
    "use strict";
    
    var DateField = function (options) {
        this.init('datefield', options, DateField.defaults);
        this.initPicker(options, DateField.defaults);
    };

    $.fn.editableutils.inherit(DateField, $.fn.editabletypes.date);    
    
    $.extend(DateField.prototype, {
        render: function () {
            this.$input = this.$tpl.find('input');
            this.setClass();
            this.setAttr('placeholder');
    
            //bootstrap-datepicker is set `bdateicker` to exclude conflict with jQuery UI one. (in date.js)        
            this.$tpl.bdatepicker(this.options.datepicker);
            
            //need to disable original event handlers
            this.$input.off('focus keydown');
            
            //update value of datepicker
            this.$input.keyup($.proxy(function(){
               this.$tpl.removeData('date');
               this.$tpl.bdatepicker('update');
            }, this));
            
        },   
        
       value2input: function(value) {
           this.$input.val(value ? this.dpg.formatDate(value, this.parsedViewFormat, this.options.datepicker.language) : '');
           this.$tpl.bdatepicker('update');
       },
        
       input2value: function() { 
           return this.html2value(this.$input.val());
       },              
        
       activate: function() {
           $.fn.editabletypes.text.prototype.activate.call(this);
       },
       
       autosubmit: function() {
         //reset autosubmit to empty  
       }
    });
    
    DateField.defaults = $.extend({}, $.fn.editabletypes.date.defaults, {
        /**
        @property tpl 
        **/         
        tpl:'<div class="input-append date"><input type="text"/><span class="add-on"><i class="icon-th"></i></span></div>',
        /**
        @property inputclass 
        @default 'input-small'
        **/         
        inputclass: 'input-small',
        
        /* datepicker config */
        datepicker: {
            weekStart: 0,
            startView: 0,
            minViewMode: 0,
            autoclose: true
        }
    });
    
    $.fn.editabletypes.datefield = DateField;

}(window.jQuery));
/**
Bootstrap-datetimepicker.  
Based on [smalot bootstrap-datetimepicker plugin](https://github.com/smalot/bootstrap-datetimepicker). 
Before usage you should manually include dependent js and css:

    <link href="css/datetimepicker.css" rel="stylesheet" type="text/css"></link> 
    <script src="js/bootstrap-datetimepicker.js"></script>

For **i18n** you should include js file from here: https://github.com/smalot/bootstrap-datetimepicker/tree/master/js/locales
and set `language` option.  

@class datetime
@extends abstractinput
@final
@since 1.4.4
@example
<a href="#" id="last_seen" data-type="datetime" data-pk="1" data-url="/post" title="Select date & time">15/03/2013 12:45</a>
<script>
$(function(){
    $('#last_seen').editable({
        format: 'yyyy-mm-dd hh:ii',    
        viewformat: 'dd/mm/yyyy hh:ii',    
        datetimepicker: {
                weekStart: 1
           }
        }
    });
});
</script>
**/
(function ($) {
    "use strict";

    var DateTime = function (options) {
        this.init('datetime', options, DateTime.defaults);
        this.initPicker(options, DateTime.defaults);
    };

    $.fn.editableutils.inherit(DateTime, $.fn.editabletypes.abstractinput);

    $.extend(DateTime.prototype, {
        initPicker: function(options, defaults) {
            //'format' is set directly from settings or data-* attributes

            //by default viewformat equals to format
            if(!this.options.viewformat) {
                this.options.viewformat = this.options.format;
            }
            
            //try parse datetimepicker config defined as json string in data-datetimepicker
            options.datetimepicker = $.fn.editableutils.tryParseJson(options.datetimepicker, true);

            //overriding datetimepicker config (as by default jQuery extend() is not recursive)
            //since 1.4 datetimepicker internally uses viewformat instead of format. Format is for submit only
            this.options.datetimepicker = $.extend({}, defaults.datetimepicker, options.datetimepicker, {
                format: this.options.viewformat
            });

            //language
            this.options.datetimepicker.language = this.options.datetimepicker.language || 'en'; 

            //store DPglobal
            this.dpg = $.fn.datetimepicker.DPGlobal; 

            //store parsed formats
            this.parsedFormat = this.dpg.parseFormat(this.options.format, this.options.formatType);
            this.parsedViewFormat = this.dpg.parseFormat(this.options.viewformat, this.options.formatType);
        },

        render: function () {
            this.$input.datetimepicker(this.options.datetimepicker);

            //adjust container position when viewMode changes
            //see https://github.com/smalot/bootstrap-datetimepicker/pull/80
            this.$input.on('changeMode', function(e) {
                var f = $(this).closest('form').parent();
                //timeout here, otherwise container changes position before form has new size
                setTimeout(function(){
                    f.triggerHandler('resize');
                }, 0);
            });

            //"clear" link
            if(this.options.clear) {
                this.$clear = $('<a href="#"></a>').html(this.options.clear).click($.proxy(function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    this.clear();
                }, this));

                this.$tpl.parent().append($('<div class="editable-clear">').append(this.$clear));  
            }
        },

        value2html: function(value, element) {
            //formatDate works with UTCDate!
            var text = value ? this.dpg.formatDate(this.toUTC(value), this.parsedViewFormat, this.options.datetimepicker.language, this.options.formatType) : '';
            if(element) {
                DateTime.superclass.value2html.call(this, text, element);
            } else {
                return text;
            }
        },

        html2value: function(html) {
            //parseDate return utc date!
            var value = this.parseDate(html, this.parsedViewFormat); 
            return value ? this.fromUTC(value) : null;
        },

        value2str: function(value) {
            //formatDate works with UTCDate!
            return value ? this.dpg.formatDate(this.toUTC(value), this.parsedFormat, this.options.datetimepicker.language, this.options.formatType) : '';
       },

       str2value: function(str) {
           //parseDate return utc date!
           var value = this.parseDate(str, this.parsedFormat);
           return value ? this.fromUTC(value) : null;
       },

       value2submit: function(value) {
           return this.value2str(value);
       },

       value2input: function(value) {
           if(value) {
             this.$input.data('datetimepicker').setDate(value);
           }
       },

       input2value: function() { 
           //date may be cleared, in that case getDate() triggers error
           var dt = this.$input.data('datetimepicker');
           return dt.date ? dt.getDate() : null;
       },

       activate: function() {
       },

       clear: function() {
          this.$input.data('datetimepicker').date = null;
          this.$input.find('.active').removeClass('active');
          if(!this.options.showbuttons) {
             this.$input.closest('form').submit(); 
          }          
       },

       autosubmit: function() {
           this.$input.on('mouseup', '.minute', function(e){
               var $form = $(this).closest('form');
               setTimeout(function() {
                   $form.submit();
               }, 200);
           });
       },

       //convert date from local to utc
       toUTC: function(value) {
         return value ? new Date(value.valueOf() - value.getTimezoneOffset() * 60000) : value;  
       },

       //convert date from utc to local
       fromUTC: function(value) {
         return value ? new Date(value.valueOf() + value.getTimezoneOffset() * 60000) : value;  
       },

       /*
        For incorrect date bootstrap-datetimepicker returns current date that is not suitable
        for datetimefield.
        This function returns null for incorrect date.  
       */
       parseDate: function(str, format) {
           var date = null, formattedBack;
           if(str) {
               date = this.dpg.parseDate(str, format, this.options.datetimepicker.language, this.options.formatType);
               if(typeof str === 'string') {
                   formattedBack = this.dpg.formatDate(date, format, this.options.datetimepicker.language, this.options.formatType);
                   if(str !== formattedBack) {
                       date = null;
                   } 
               }
           }
           return date;
       }

    });

    DateTime.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
        @property tpl 
        @default <div></div>
        **/         
        tpl:'<div class="editable-date well"></div>',
        /**
        @property inputclass 
        @default null
        **/
        inputclass: null,
        /**
        Format used for sending value to server. Also applied when converting date from <code>data-value</code> attribute.<br>
        Possible tokens are: <code>d, dd, m, mm, yy, yyyy, h, i</code>  
        
        @property format 
        @type string
        @default yyyy-mm-dd hh:ii
        **/         
        format:'yyyy-mm-dd hh:ii',
        formatType:'standard',
        /**
        Format used for displaying date. Also applied when converting date from element's text on init.   
        If not specified equals to <code>format</code>
        
        @property viewformat 
        @type string
        @default null
        **/
        viewformat: null,
        /**
        Configuration of datetimepicker.
        Full list of options: https://github.com/smalot/bootstrap-datetimepicker

        @property datetimepicker 
        @type object
        @default { }
        **/
        datetimepicker:{
            todayHighlight: false,
            autoclose: false
        },
        /**
        Text shown as clear date button. 
        If <code>false</code> clear button will not be rendered.

        @property clear 
        @type boolean|string
        @default 'x clear'
        **/
        clear: '&times; clear'
    });

    $.fn.editabletypes.datetime = DateTime;

}(window.jQuery));
/**
Bootstrap datetimefield input - datetime input for inline mode.
Shows normal <input type="text"> and binds popup datetimepicker.  
Automatically shown in inline mode.

@class datetimefield
@extends datetime

**/
(function ($) {
    "use strict";
    
    var DateTimeField = function (options) {
        this.init('datetimefield', options, DateTimeField.defaults);
        this.initPicker(options, DateTimeField.defaults);
    };

    $.fn.editableutils.inherit(DateTimeField, $.fn.editabletypes.datetime);
    
    $.extend(DateTimeField.prototype, {
        render: function () {
            this.$input = this.$tpl.find('input');
            this.setClass();
            this.setAttr('placeholder');
            
            this.$tpl.datetimepicker(this.options.datetimepicker);
            
            //need to disable original event handlers
            this.$input.off('focus keydown');
            
            //update value of datepicker
            this.$input.keyup($.proxy(function(){
               this.$tpl.removeData('date');
               this.$tpl.datetimepicker('update');
            }, this));
            
        },   
      
       value2input: function(value) {
           this.$input.val(this.value2html(value));
           this.$tpl.datetimepicker('update');
       },
        
       input2value: function() { 
           return this.html2value(this.$input.val());
       },              
        
       activate: function() {
           $.fn.editabletypes.text.prototype.activate.call(this);
       },
       
       autosubmit: function() {
         //reset autosubmit to empty  
       }
    });
    
    DateTimeField.defaults = $.extend({}, $.fn.editabletypes.datetime.defaults, {
        /**
        @property tpl 
        **/         
        tpl:'<div class="input-append date"><input type="text"/><span class="add-on"><i class="icon-th"></i></span></div>',
        /**
        @property inputclass 
        @default 'input-medium'
        **/         
        inputclass: 'input-medium',
        
        /* datetimepicker config */
        datetimepicker:{
            todayHighlight: false,
            autoclose: true
        }
    });
    
    $.fn.editabletypes.datetimefield = DateTimeField;

}(window.jQuery));
/**
 Address editable input.
 Internally value stored as {city: "Moscow", street: "Lenina", building: "15"}

 @class address
 @extends abstractinput
 @final
 @example
 <a href="#" id="address" data-type="address" data-pk="1">awesome</a>
 <script>
 $(function(){
    $('#address').editable({
        url: '/post',
        title: 'Enter city, street and building #',
        value: {
            city: "Moscow", 
            street: "Lenina", 
            building: "15"
        }
    });
});
 </script>
 **/

(function ($) {
    "use strict";

    var Address = function (options) {
        this.init('address', options, Address.defaults);
    };

    //inherit from Abstract input
    $.fn.editableutils.inherit(Address, $.fn.editabletypes.abstractinput);

    $.extend(Address.prototype, {
        /**
         Renders input from tpl

         @method render()
         **/
        render: function() {
            this.$input = this.$tpl.find('input');
        },

        /**
         Default method to show value in element. Can be overwritten by display option.

         @method value2html(value, element)
         **/
        value2html: function(value, element) {
            if(!value) {
                $(element).empty();
                return;
            }
            var html = $('<div>').text(value.city).html() + ', ' + $('<div>').text(value.street).html() + ' st., bld. ' + $('<div>').text(value.building).html();
            $(element).html(html);
        },

        /**
         Gets value from element's html

         @method html2value(html)
         **/
        html2value: function(html) {
            /*
             you may write parsing method to get value by element's html
             e.g. "Moscow, st. Lenina, bld. 15" => {city: "Moscow", street: "Lenina", building: "15"}
             but for complex structures it's not recommended.
             Better set value directly via javascript, e.g.
             editable({
             value: {
             city: "Moscow",
             street: "Lenina",
             building: "15"
             }
             });
             */
            return null;
        },

        /**
         Converts value to string.
         It is used in internal comparing (not for sending to server).

         @method value2str(value)
         **/
        value2str: function(value) {
            var str = '';
            if(value) {
                for(var k in value) {
                    str = str + k + ':' + value[k] + ';';
                }
            }
            return str;
        },

        /*
         Converts string to value. Used for reading value from 'data-value' attribute.

         @method str2value(str)
         */
        str2value: function(str) {
            /*
             this is mainly for parsing value defined in data-value attribute.
             If you will always set value by javascript, no need to overwrite it
             */
            return str;
        },

        /**
         Sets value of input.

         @method value2input(value)
         @param {mixed} value
         **/
        value2input: function(value) {
            if(!value) {
                return;
            }
            this.$input.filter('[name="city"]').val(value.city);
            this.$input.filter('[name="street"]').val(value.street);
            this.$input.filter('[name="building"]').val(value.building);
        },

        /**
         Returns value of input.

         @method input2value()
         **/
        input2value: function() {
            return {
                city: this.$input.filter('[name="city"]').val(),
                street: this.$input.filter('[name="street"]').val(),
                building: this.$input.filter('[name="building"]').val()
            };
        },

        /**
         Activates input: sets focus on the first field.

         @method activate()
         **/
        activate: function() {
            this.$input.filter('[name="city"]').focus();
        },

        /**
         Attaches handler to submit form in case of 'showbuttons=false' mode

         @method autosubmit()
         **/
        autosubmit: function() {
            this.$input.keydown(function (e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
            });
        }
    });

    Address.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        tpl: '<div class="editable-address"><label><span>City: </span><input type="text" name="city" class="input-small"></label></div>'+
            '<div class="editable-address"><label><span>Street: </span><input type="text" name="street" class="input-small"></label></div>'+
            '<div class="editable-address"><label><span>Building: </span><input type="text" name="building" class="input-mini"></label></div>',

        inputclass: ''
    });

    $.fn.editabletypes.address = Address;

}(window.jQuery));



$(function(){

    //defaults
    $.fn.editable.defaults.url = '/';

    //enable / disable
    $('#enable').click(function() {
        $('#user .editable').editable('toggleDisabled');
    });

    //editables 
    $('#username').editable({
        type: 'text',
        pk: 1,
        name: 'username',
        title: 'Enter username'
    });

    $('#firstname').editable({
        validate: function(value) {
            if($.trim(value) == '') return 'This field is required';
        }
    });

    $('#sex').editable({
        prepend: "not selected",
        source: [
            {value: 1, text: 'Male'},
            {value: 2, text: 'Female'}
        ],
        display: function(value, sourceData) {
            var colors = {"": "gray", 1: "green", 2: "blue"},
                elem = $.grep(sourceData, function(o){return o.value == value;});

            if(elem.length) {
                $(this).text(elem[0].text).css("color", colors[value]);
            } else {
                $(this).empty();
            }
        }
    });

    $('#status').editable();

    $('#group').editable({
        showbuttons: false
    });

    $('#vacation').editable({
        datepicker: {
            todayBtn: 'linked'
        }
    });

    $('#dob').editable();

    $('#event').editable({
        placement: 'right',
        combodate: {
            firstItem: 'name'
        }
    });

    $('#meeting_start').editable({
        format: 'yyyy-mm-dd hh:ii',
        viewformat: 'dd/mm/yyyy hh:ii',
        validate: function(v) {
            if(v && v.getDate() == 10) return 'Day cant be 10!';
        },
        datetimepicker: {
            todayBtn: 'linked',
            weekStart: 1
        }
    });

    $('#comments').editable({
        showbuttons: 'bottom'
    });

    $('#note').editable();
    $('#pencil').click(function(e) {
        e.stopPropagation();
        e.preventDefault();
        $('#note').editable('toggle');
    });

    $('#state').editable({
        source: ["Alabama","Alaska","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","Florida","Georgia","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Dakota","North Carolina","Ohio","Oklahoma","Oregon","Pennsylvania","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virginia","Washington","West Virginia","Wisconsin","Wyoming"]
    });

    $('#fruits').editable({
        pk: 1,
        limit: 3,
        source: [
            {value: 1, text: 'banana'},
            {value: 2, text: 'peach'},
            {value: 3, text: 'apple'},
            {value: 4, text: 'watermelon'},
            {value: 5, text: 'orange'}
        ]
    });

    $('#tags').editable({
        inputclass: 'input-large',
        select2: {
            tags: ['html', 'javascript', 'css', 'ajax'],
            tokenSeparators: [",", " "]
        }
    });

    var countries = [];
    $.each({"BD": "Bangladesh", "BE": "Belgium", "BF": "Burkina Faso", "BG": "Bulgaria", "BA": "Bosnia and Herzegovina", "BB": "Barbados", "WF": "Wallis and Futuna", "BL": "Saint Bartelemey", "BM": "Bermuda", "BN": "Brunei Darussalam", "BO": "Bolivia", "BH": "Bahrain", "BI": "Burundi", "BJ": "Benin", "BT": "Bhutan", "JM": "Jamaica", "BV": "Bouvet Island", "BW": "Botswana", "WS": "Samoa", "BR": "Brazil", "BS": "Bahamas", "JE": "Jersey", "BY": "Belarus", "O1": "Other Country", "LV": "Latvia", "RW": "Rwanda", "RS": "Serbia", "TL": "Timor-Leste", "RE": "Reunion", "LU": "Luxembourg", "TJ": "Tajikistan", "RO": "Romania", "PG": "Papua New Guinea", "GW": "Guinea-Bissau", "GU": "Guam", "GT": "Guatemala", "GS": "South Georgia and the South Sandwich Islands", "GR": "Greece", "GQ": "Equatorial Guinea", "GP": "Guadeloupe", "JP": "Japan", "GY": "Guyana", "GG": "Guernsey", "GF": "French Guiana", "GE": "Georgia", "GD": "Grenada", "GB": "United Kingdom", "GA": "Gabon", "SV": "El Salvador", "GN": "Guinea", "GM": "Gambia", "GL": "Greenland", "GI": "Gibraltar", "GH": "Ghana", "OM": "Oman", "TN": "Tunisia", "JO": "Jordan", "HR": "Croatia", "HT": "Haiti", "HU": "Hungary", "HK": "Hong Kong", "HN": "Honduras", "HM": "Heard Island and McDonald Islands", "VE": "Venezuela", "PR": "Puerto Rico", "PS": "Palestinian Territory", "PW": "Palau", "PT": "Portugal", "SJ": "Svalbard and Jan Mayen", "PY": "Paraguay", "IQ": "Iraq", "PA": "Panama", "PF": "French Polynesia", "BZ": "Belize", "PE": "Peru", "PK": "Pakistan", "PH": "Philippines", "PN": "Pitcairn", "TM": "Turkmenistan", "PL": "Poland", "PM": "Saint Pierre and Miquelon", "ZM": "Zambia", "EH": "Western Sahara", "RU": "Russian Federation", "EE": "Estonia", "EG": "Egypt", "TK": "Tokelau", "ZA": "South Africa", "EC": "Ecuador", "IT": "Italy", "VN": "Vietnam", "SB": "Solomon Islands", "EU": "Europe", "ET": "Ethiopia", "SO": "Somalia", "ZW": "Zimbabwe", "SA": "Saudi Arabia", "ES": "Spain", "ER": "Eritrea", "ME": "Montenegro", "MD": "Moldova, Republic of", "MG": "Madagascar", "MF": "Saint Martin", "MA": "Morocco", "MC": "Monaco", "UZ": "Uzbekistan", "MM": "Myanmar", "ML": "Mali", "MO": "Macao", "MN": "Mongolia", "MH": "Marshall Islands", "MK": "Macedonia", "MU": "Mauritius", "MT": "Malta", "MW": "Malawi", "MV": "Maldives", "MQ": "Martinique", "MP": "Northern Mariana Islands", "MS": "Montserrat", "MR": "Mauritania", "IM": "Isle of Man", "UG": "Uganda", "TZ": "Tanzania, United Republic of", "MY": "Malaysia", "MX": "Mexico", "IL": "Israel", "FR": "France", "IO": "British Indian Ocean Territory", "FX": "France, Metropolitan", "SH": "Saint Helena", "FI": "Finland", "FJ": "Fiji", "FK": "Falkland Islands (Malvinas)", "FM": "Micronesia, Federated States of", "FO": "Faroe Islands", "NI": "Nicaragua", "NL": "Netherlands", "NO": "Norway", "NA": "Namibia", "VU": "Vanuatu", "NC": "New Caledonia", "NE": "Niger", "NF": "Norfolk Island", "NG": "Nigeria", "NZ": "New Zealand", "NP": "Nepal", "NR": "Nauru", "NU": "Niue", "CK": "Cook Islands", "CI": "Cote d'Ivoire", "CH": "Switzerland", "CO": "Colombia", "CN": "China", "CM": "Cameroon", "CL": "Chile", "CC": "Cocos (Keeling) Islands", "CA": "Canada", "CG": "Congo", "CF": "Central African Republic", "CD": "Congo, The Democratic Republic of the", "CZ": "Czech Republic", "CY": "Cyprus", "CX": "Christmas Island", "CR": "Costa Rica", "CV": "Cape Verde", "CU": "Cuba", "SZ": "Swaziland", "SY": "Syrian Arab Republic", "KG": "Kyrgyzstan", "KE": "Kenya", "SR": "Suriname", "KI": "Kiribati", "KH": "Cambodia", "KN": "Saint Kitts and Nevis", "KM": "Comoros", "ST": "Sao Tome and Principe", "SK": "Slovakia", "KR": "Korea, Republic of", "SI": "Slovenia", "KP": "Korea, Democratic People's Republic of", "KW": "Kuwait", "SN": "Senegal", "SM": "San Marino", "SL": "Sierra Leone", "SC": "Seychelles", "KZ": "Kazakhstan", "KY": "Cayman Islands", "SG": "Singapore", "SE": "Sweden", "SD": "Sudan", "DO": "Dominican Republic", "DM": "Dominica", "DJ": "Djibouti", "DK": "Denmark", "VG": "Virgin Islands, British", "DE": "Germany", "YE": "Yemen", "DZ": "Algeria", "US": "United States", "UY": "Uruguay", "YT": "Mayotte", "UM": "United States Minor Outlying Islands", "LB": "Lebanon", "LC": "Saint Lucia", "LA": "Lao People's Democratic Republic", "TV": "Tuvalu", "TW": "Taiwan", "TT": "Trinidad and Tobago", "TR": "Turkey", "LK": "Sri Lanka", "LI": "Liechtenstein", "A1": "Anonymous Proxy", "TO": "Tonga", "LT": "Lithuania", "A2": "Satellite Provider", "LR": "Liberia", "LS": "Lesotho", "TH": "Thailand", "TF": "French Southern Territories", "TG": "Togo", "TD": "Chad", "TC": "Turks and Caicos Islands", "LY": "Libyan Arab Jamahiriya", "VA": "Holy See (Vatican City State)", "VC": "Saint Vincent and the Grenadines", "AE": "United Arab Emirates", "AD": "Andorra", "AG": "Antigua and Barbuda", "AF": "Afghanistan", "AI": "Anguilla", "VI": "Virgin Islands, U.S.", "IS": "Iceland", "IR": "Iran, Islamic Republic of", "AM": "Armenia", "AL": "Albania", "AO": "Angola", "AN": "Netherlands Antilles", "AQ": "Antarctica", "AP": "Asia/Pacific Region", "AS": "American Samoa", "AR": "Argentina", "AU": "Australia", "AT": "Austria", "AW": "Aruba", "IN": "India", "AX": "Aland Islands", "AZ": "Azerbaijan", "IE": "Ireland", "ID": "Indonesia", "UA": "Ukraine", "QA": "Qatar", "MZ": "Mozambique"}, function(k, v) {
        countries.push({id: k, text: v});
    });
    $('#country').editable({
        source: countries,
        select2: {
            width: 200
        }
    });



    $('#address').editable({
        url: '/post',
        value: {
            city: "Moscow",
            street: "Lenina",
            building: "12"
        },
        validate: function(value) {
            if(value.city == '') return 'city is required!';
        },
        display: function(value) {
            if(!value) {
                $(this).empty();
                return;
            }
            var html = '<b>' + $('<div>').text(value.city).html() + '</b>, ' + $('<div>').text(value.street).html() + ' st., bld. ' + $('<div>').text(value.building).html();
            $(this).html(html);
        }
    });

    $('#user .editable').on('hidden', function(e, reason){
        if(reason === 'save' || reason === 'nochange') {
            var $next = $(this).closest('tr').next().find('.editable');
            if($('#autoopen').is(':checked')) {
                setTimeout(function() {
                    $next.editable('show');
                }, 300);
            } else {
                $next.focus();
            }
        }
    });

});

(function() {

  this.Growl = (function() {

    function Growl() {}

    Growl.info = function(options) {
      options['class_name'] = "info";
      options.title = "<i class='icon-info-sign'></i> " + options.title;
      return $.gritter.add(options);
    };

    Growl.warn = function(options) {
      options['class_name'] = "warn";
      options.title = "<i class='icon-warning-sign'></i> " + options.title;
      return $.gritter.add(options);
    };

    Growl.error = function(options) {
      options['class_name'] = "error";
      options.title = "<i class='icon-exclamation-sign'></i> " + options.title;
      return $.gritter.add(options);
    };

    Growl.success = function(options) {
      options['class_name'] = "success";
      options.title = "<i class='icon-ok-sign'></i> " + options.title;
      return $.gritter.add(options);
    };

    return Growl;

  })();

  $(function() {
    $.extend($.gritter.options, {
      position: 'top-right'
    });
    $(".growl-info").click(function(e) {
      e.preventDefault();
      return Growl.info({
        title: 'This is a notice!',
        text: 'This will fade out after a certain amount of time.'
      });
    });
    $(".growl-warn").click(function(e) {
      e.preventDefault();
      return Growl.warn({
        title: 'This is a warning!',
        text: 'This will fade out after a certain amount of time.'
      });
    });
    $(".growl-error").click(function(e) {
      e.preventDefault();
      return Growl.error({
        title: 'This is an error!',
        text: 'This will fade out after a certain amount of time.'
      });
    });
    return $(".growl-success").click(function(e) {
      e.preventDefault();
      return Growl.success({
        title: 'This is a success message!',
        text: 'This will fade out after a certain amount of time.'
      });
    });
  });

}).call(this);
(function() {

  $(function() {
    var d, date, m, y;
    new CalendarEvents($('#external-events'));
    date = new Date();
    d = date.getDate();
    m = date.getMonth();
    y = date.getFullYear();
    return $("#calendar").fullCalendar({
      header: {
        left: "prev,next",
        center: "title",
        right: "month,agendaWeek,agendaDay"
      },
      editable: true,
      droppable: true,
      drop: function(date, allDay) {
        var copiedEventObject, originalEventObject;
        originalEventObject = $(this).data('eventObject');
        copiedEventObject = $.extend({}, originalEventObject);
        copiedEventObject.start = date;
        copiedEventObject.allDay = allDay;
        $("#calendar").fullCalendar('renderEvent', copiedEventObject, true);
        if ($("#drop-remove").is(":checked")) {
          return $(this).remove();
        }
      },
      events: [
        {
          title: "All Day Event",
          start: new Date(y, m, 1)
        }, {
          title: "Long Event",
          start: new Date(y, m, d - 5),
          end: new Date(y, m, d - 2)
        }, {
          id: 999,
          title: "Repeating Event",
          start: new Date(y, m, d - 3, 16, 0),
          allDay: false
        }, {
          id: 999,
          title: "Repeating Event",
          start: new Date(y, m, d + 4, 16, 0),
          allDay: false
        }, {
          title: "Meeting",
          start: new Date(y, m, d, 10, 30),
          allDay: false
        }, {
          title: "Lunch",
          start: new Date(y, m, d, 12, 0),
          end: new Date(y, m, d, 14, 0),
          allDay: false
        }, {
          title: "Birthday Party",
          start: new Date(y, m, d + 1, 19, 0),
          end: new Date(y, m, d + 1, 22, 30),
          allDay: false
        }, {
          title: "Click for Google",
          start: new Date(y, m, 28),
          end: new Date(y, m, 29),
          url: "http://google.com/"
        }
      ]
    });
  });

}).call(this);
$(function(){
    var tt = document.createElement('div'),
        leftOffset = -($('html').css('padding-left').replace('px', '') + $('body').css('margin-left').replace('px', '')),
        topOffset = -32;
    tt.className = 'ex-tooltip';
    document.body.appendChild(tt);

    var data = {
        "xScale": "time",
        "yScale": "linear",
        "main": [
            {
                "className": ".pizza",
                "data": [
                    { "x": "2012-11-05", "y": 6 },
                    { "x": "2012-11-06", "y": 6 },
                    { "x": "2012-11-07", "y": 8 },
                    { "x": "2012-11-08", "y": 3 },
                    { "x": "2012-11-09", "y": 4 },
                    { "x": "2012-11-10", "y": 9 },
                    { "x": "2012-11-11", "y": 6 }
                ]
            }
        ]
    };
    var opts = {
        paddingLeft:15,
        paddingRight: 0,
        axisPaddingTop: 5,
        axisPaddingLeft: 5,
        dataFormatX: function (x) { return d3.time.format('%Y-%m-%d').parse(x); },
        tickFormatX: function (x) { return d3.time.format('%a')(x); },
        mouseover: function (d, i) {
            var pos = $(this).offset();
            $(tt).text(d3.time.format('%A')(d.x) + ': ' + d.y)
                .css({top: topOffset + pos.top, left: pos.left + leftOffset})
                .show();
        },
        "mouseout": function (x) { $(tt).hide(); }
    };

    if ($("#left-sidebar-chart").length > 0) {
        new xChart('line-dotted', data, '#left-sidebar-chart', opts);
    }

    if ($(".xcharts-line-dotted").length > 0) {
        new xChart('line-dotted', data, '.xcharts-line-dotted', opts);
    }


});
$(function () {
    var data = [
        {
            "xScale":"ordinal",
            "comp":[],
            "main":[
                {
                    "className":".main.l1",
                    "data":[
                        { "y":15, "x":"2012-11-19T00:00:00" },
                        { "y":11, "x":"2012-11-20T00:00:00" },
                        { "y":8, "x":"2012-11-21T00:00:00" },
                        { "y":10, "x":"2012-11-22T00:00:00" },
                        { "y":1, "x":"2012-11-23T00:00:00" },
                        { "y":6, "x":"2012-11-24T00:00:00" },
                        { "y":8, "x":"2012-11-25T00:00:00" }
                    ]
                },{
                    "className":".main.l2",
                    "data":[
                        {"y":29,"x":"2012-11-19T00:00:00"},
                        {"y":33,"x":"2012-11-20T00:00:00"},
                        {"y":13,"x":"2012-11-21T00:00:00"},
                        {"y":16,"x":"2012-11-22T00:00:00"},
                        {"y":7,"x":"2012-11-23T00:00:00"},
                        {"y":18,"x":"2012-11-24T00:00:00"},
                        {"y":8,"x":"2012-11-25T00:00:00"}
                    ]
                }
            ],
            "type":"line-dotted",
            "yScale":"linear"
        },{
            "xScale":"ordinal",
            "comp":[],
            "main":[
                {
                    "className":".main.l1",
                    "data":[
                        {"y":12,"x":"2012-11-19T00:00:00"},
                        {"y":18,"x":"2012-11-20T00:00:00"},
                        {"y":8,"x":"2012-11-21T00:00:00"},
                        {"y":7,"x":"2012-11-22T00:00:00"},
                        {"y":6,"x":"2012-11-23T00:00:00"},
                        {"y":12,"x":"2012-11-24T00:00:00"},
                        {"y":8,"x":"2012-11-25T00:00:00"}
                    ]
                },{
                    "className":".main.l2",
                    "data":[
                        {"y":29,"x":"2012-11-19T00:00:00"},
                        {"y":33,"x":"2012-11-20T00:00:00"},
                        {"y":13,"x":"2012-11-21T00:00:00"},
                        {"y":16,"x":"2012-11-22T00:00:00"},
                        {"y":7,"x":"2012-11-23T00:00:00"},
                        {"y":18,"x":"2012-11-24T00:00:00"},
                        {"y":8,"x":"2012-11-25T00:00:00"}
                    ]
                }
            ],
            "type":"cumulative",
            "yScale":"linear"
        },{
            "xScale":"ordinal",
            "comp":[],
            "main":[
                {
                    "className":".main.l1",
                    "data":[
                        {"y":12,"x":"2012-11-19T00:00:00"},
                        {"y":18,"x":"2012-11-20T00:00:00"},
                        {"y":8,"x":"2012-11-21T00:00:00"},
                        {"y":7,"x":"2012-11-22T00:00:00"},
                        {"y":6,"x":"2012-11-23T00:00:00"},
                        {"y":12,"x":"2012-11-24T00:00:00"},
                        {"y":8,"x":"2012-11-25T00:00:00"}
                    ]
                },{
                    "className":".main.l2",
                    "data":[
                        {"y":29,"x":"2012-11-19T00:00:00"},
                        {"y":33,"x":"2012-11-20T00:00:00"},
                        {"y":13,"x":"2012-11-21T00:00:00"},
                        {"y":16,"x":"2012-11-22T00:00:00"},
                        {"y":7,"x":"2012-11-23T00:00:00"},
                        {"y":18,"x":"2012-11-24T00:00:00"},
                        {"y":8,"x":"2012-11-25T00:00:00"}]
                }
            ],
            "type":"bar",
            "yScale":"linear"
        }
    ];

    var order = [0, 1, 0, 2],
        i = 0,
        xFormat = d3.time.format('%A'),
        rotateTimer,
        chart,
        t = 3500;

    if ($("#xchart-sine").length > 0) {
        chart = new xChart('bar', data[order[i]], '#xchart-sine', {
            axisPaddingTop: 5,
            paddingLeft: 30,
            dataFormatX: function (x) { return new Date(x); },
            tickFormatX: function (x) { return d3.time.format('%a')(x); }
        });

        rotateTimer = setTimeout(rotateChart, t);
    }

    function updateChart(i) {
        chart.setData(data[i]);
    }

    function rotateChart() {
        i += 1;
        i = (i >= order.length) ? 0 : i;
        updateChart(order[i]);
        rotateTimer = setTimeout(rotateChart, t);
    }

});
$(function () {

    var getData = function() {
        var data = {
            "xScale": "ordinal",
            "yScale": "linear"
        };

        data.main = []
        var foods = $(["Beef", "Ham", "Bacon"]);
        $([1,2,3]).each(function(){

            var graphData = []

            foods.each(function(){
                graphData.push({
                    "x": this,
                    "y": getRandomInt(3,12)
                });
            });

            data.main.push({
                "className": ".food",
                "data": graphData
            })
        });

        return data;
    }


    if($(".xcharts-bar").length > 0) {
        var myChart = new xChart('bar', getData(), '.xcharts-bar', {axisPaddingTop: 5, paddingLeft: 15});
        $("#randomize-bar-chart").click(function(){
            myChart.setData(getData())
        });
    }

});
(function() {

  $(function() {
    var barChartDataset, colors, drawMouseSpeedDemo, initBarChart, initLineChart, initPieChart, lineChartDataset, tooltipLabels;
    colors = $.map(Theme.colors, function(item) {
      return item;
    });
    tooltipLabels = {
      names: {
        0: 'Automotive',
        1: 'Locomotive',
        2: 'Unmotivated',
        3: 'Three',
        4: 'Four',
        5: 'Five'
      }
    };
    initPieChart = function() {
      var width;
      width = $(".spark-pie").width();
      if (width > 200) {
        width = 200;
      }
      return $(".spark-pie").sparkline([55, 100, 220, 180], {
        type: 'pie',
        height: width,
        sliceColors: colors,
        tooltipFormat: '<span style="color: {{color}}">&#9679;</span> {{offset:names}} ({{percent.1}}%)',
        tooltipValueLookups: tooltipLabels
      });
    };
    initBarChart = function(data, object) {
      var barCount, barSpacing, barWidth, currentWidth, widthLeftForBars;
      currentWidth = object.width();
      barCount = data.length;
      barSpacing = 5;
      widthLeftForBars = currentWidth - (barCount - 1) * barSpacing;
      barWidth = widthLeftForBars / barCount;
      return object.sparkline(data, {
        type: "bar",
        barColor: Theme.colors.blue,
        height: 200,
        barWidth: barWidth,
        barSpacing: barSpacing,
        tooltipSuffix: "$ income received"
      });
    };
    barChartDataset = [4, 1, 5, 7, 9, 9, 8, 7, 6, 6, 4, 7, 8, 4, 3, 2, 2, 5, 6, 7];
    lineChartDataset = [6, 3, 8, 5, 8, 4, 3, 2, 7, 6, 6, 4, 7, 2, 5, 6, 7, 2, 4, 11];
    initLineChart = function() {
      return $(".spark-composite").sparkline(lineChartDataset, {
        composite: true,
        fillColor: false,
        lineColor: Theme.colors.red,
        width: "100%",
        height: '200',
        spotColor: Theme.colors.lightBlue,
        lineWidth: 3,
        maxSpotColor: Theme.colors.red,
        highlightSpotColor: Theme.colors.orange,
        highlightLineColor: Theme.colors.orange,
        chartRangeMin: 0,
        spotRadius: 4,
        drawNormalOnTop: false,
        tooltipSuffix: " orders sent"
      });
    };
    initBarChart(barChartDataset, $(".spark-composite"));
    initLineChart();
    initPieChart();
    $(window).resize(function() {
      initBarChart(barChartDataset, $(".spark-composite"));
      initLineChart();
      return initPieChart();
    });
    drawMouseSpeedDemo = function() {
      var lastmousetime, lastmousex, lastmousey, mdraw, mousetravel, mpoints, mpoints_max, mrefreshinterval;
      mrefreshinterval = 100;
      lastmousex = -1;
      lastmousey = -1;
      lastmousetime = void 0;
      mousetravel = 0;
      mpoints = [];
      mpoints_max = 30;
      $("html").mousemove(function(e) {
        var mousex, mousey;
        mousex = e.pageX;
        mousey = e.pageY;
        if (lastmousex > -1) {
          mousetravel += Math.max(Math.abs(mousex - lastmousex), Math.abs(mousey - lastmousey));
        }
        lastmousex = mousex;
        return lastmousey = mousey;
      });
      mdraw = function() {
        var md, pps, timenow;
        md = new Date();
        timenow = md.getTime();
        if (lastmousetime && lastmousetime !== timenow) {
          pps = Math.round(mousetravel / (timenow - lastmousetime) * 1000);
          mpoints.push(pps);
          if (mpoints.length > mpoints_max) {
            mpoints.splice(0, 1);
          }
          mousetravel = 0;
          $(".spark-mouse").sparkline(mpoints, {
            type: 'line',
            width: "100%",
            height: '200',
            chartRangeMin: 0,
            chartRangeMax: 3000,
            lineColor: Theme.colors.blue,
            fillColor: Theme.colors.lightBlue,
            spotColor: Theme.colors.lightBlue,
            lineWidth: 3,
            maxSpotColor: Theme.colors.red,
            highlightSpotColor: Theme.colors.orange,
            highlightLineColor: Theme.colors.orange,
            spotRadius: 4,
            drawNormalOnTop: false,
            tooltipSuffix: " pixels per second"
          });
        }
        lastmousetime = timenow;
        return setTimeout(mdraw, mrefreshinterval);
      };
      return setTimeout(mdraw, mrefreshinterval);
    };
    if ($(".spark-mouse").length > 0) {
      return drawMouseSpeedDemo();
    }
  });

}).call(this);
(function() {

  this.Theme = (function() {

    function Theme() {}

    Theme.colors = {
      darkGreen: "#779148",
      red: "#C75D5D",
      green: "#96c877",
      blue: "#6e97aa",
      orange: "#ff9f01",
      gray: "#6B787F",
      lightBlue: "#D4E5DE"
    };

    return Theme;

  })();

  $(function() {
    var gauges;
    $('.icheck').iCheck({
      checkboxClass: 'icheckbox_flat-aero',
      radioClass: 'iradio_flat-aero'
    });
    $.uniform.defaults.fileButtonHtml = '+';
    $.uniform.defaults.selectAutoWidth = false;
    $(".sparkline").each(function() {
      var barSpacing, barWidth, color, height;
      color = $(this).attr("data-color") || "red";
      height = "18px";
      if ($(this).hasClass("big")) {
        barWidth = "5px";
        barSpacing = "2px";
        height = "30px";
      }
      return $(this).sparkline("html", {
        type: "bar",
        barColor: Theme.colors[color],
        height: height,
        barWidth: barWidth,
        barSpacing: barSpacing,
        zeroAxis: false
      });
    });
    $('.tip, [rel=tooltip]').tooltip({
      gravity: 'n',
      fade: true,
      html: true
    });
    $("[data-percent]").each(function() {
      return $(this).css({
        width: "" + ($(this).attr("data-percent")) + "%"
      });
    });
    $('.datepicker').datepicker({
      todayBtn: true
    });
    $('.tags').tagsInput({
      width: '100%'
    });
    $("form.validatable").validationEngine({
      promptPosition: "topLeft"
    });
    $(".chzn-select").select2();
    $('.textarea-html5').wysihtml5({
      "font-styles": true,
      "emphasis": true,
      "lists": true,
      "html": false,
      "link": true,
      "image": true,
      "color": false,
      stylesheets: false
    });
    $.extend($.fn.dataTableExt.oStdClasses, {
      "sWrapper": "dataTables_wrapper form-inline"
    });
    $(".dTable").dataTable({
      bJQueryUI: false,
      bAutoWidth: false,
      sPaginationType: "full_numbers",
      sDom: "<\"table-header\"fl>t<\"table-footer\"ip>"
    });
    $(".dTable-small").dataTable({
      iDisplayLength: 5,
      bJQueryUI: false,
      bAutoWidth: false,
      sPaginationType: "full_numbers",
      sDom: "<\"table-header\"fl>t<\"table-footer\"ip>"
    });
    $("select.uniform, input:file, .dataTables_length select").uniform();
    $(".core-animate-bars .box-toolbar a").click(function(e) {
      e.preventDefault();
      return $(this).closest(".core-animate-bars").find(".progress .tip").each(function() {
        var percent, randomNumber;
        randomNumber = Math.floor(Math.random() * 80) + 20;
        percent = "" + randomNumber + "%";
        return $(this).attr("title", percent).attr("data-percent", randomNumber).attr("data-original-title", percent).css({
          width: percent
        });
      });
    });
    $(".normal-slider").slider();
    $(".ranged-slider-ui.normal").slider({
      range: true,
      min: 0,
      max: 300,
      values: [40, 250]
    });
    $(".ranged-slider-ui.only-min").slider({
      range: "min",
      min: 0,
      max: 300,
      value: 40
    });
    $(".ranged-slider-ui.only-max").slider({
      range: "max",
      min: 0,
      max: 300,
      value: 240
    });
    $(".ranged-slider-ui.step").slider({
      range: "min",
      min: 20,
      max: 120,
      value: 40,
      step: 20,
      slide: function(event, ui) {
        return $(".upload-max-size").html("" + ui.value + " MB");
      }
    });
    $(".ranged-slider-ui.vertical-bars span").each(function() {
      var val;
      val = parseInt($(this).text(), 10);
      return $(this).empty().slider({
        value: val,
        range: "min",
        animate: true,
        orientation: "vertical"
      });
    });
    $(".iButton-icons").iButton({
      labelOn: "<i class='icon-ok'></i>",
      labelOff: "<i class='icon-remove'></i>",
      handleWidth: 30
    });
    $(".iButton-enabled").iButton({
      labelOn: "ENABLED",
      labelOff: "DISABLED",
      handleWidth: 30
    });
    $(".iButton").iButton();
    $(".iButton-icons-tab").each(function() {
      if ($(this).is(":visible")) {
        return $(this).iButton({
          labelOn: "<i class='icon-ok'></i>",
          labelOff: "<i class='icon-remove'></i>",
          handleWidth: 30
        });
      }
    });
    $('[data-toggle="tab"]').on('shown', function(e) {
      var id;
      id = $(e.target).attr("href");
      return $(id).find(".iButton-icons-tab").iButton({
        labelOn: "<i class='icon-ok'></i>",
        labelOff: "<i class='icon-remove'></i>",
        handleWidth: 30
      });
    });
    $('#thumbs a').touchTouch();
    $(".toggle-primary-sidebar").click(function(e) {
      return $.sparkline_display_visible();
    });
    $("#switch-sidebar").click(function() {
      return $('body').toggleClass('dropdown-sidebar');
    });
    $(".closable-chat-box textarea").click(function() {
      $(this).closest(".closable-chat-box").addClass("open");
      return $(this).wysihtml5({
        "font-styles": true,
        "emphasis": true,
        "lists": true,
        "html": false,
        "link": true,
        "image": true,
        "color": false,
        stylesheets: false
      });
    });
    gauges = [];
    $(".justgage").each(function() {
      var gaugeWidthScale, refreshAnimationType, showMinMax;
      showMinMax = $(this).attr("data-labels") || true;
      gaugeWidthScale = $(this).attr("data-gauge-width-scale") || 1;
      refreshAnimationType = $(this).attr("data-animation-type") || "linear";
      return gauges.push(new JustGage({
        id: $(this).attr("id"),
        min: 0,
        max: 100,
        title: $(this).attr("data-title"),
        value: getRandomInt(1, 80),
        label: "",
        levelColorsGradient: false,
        showMinMax: showMinMax,
        gaugeWidthScale: gaugeWidthScale,
        startAnimationTime: 1000,
        startAnimationType: ">",
        refreshAnimationTime: 1000,
        refreshAnimationType: refreshAnimationType,
        levelColors: [Theme.colors.green, Theme.colors.orange, Theme.colors.red]
      }));
    });
    setInterval(function() {
      return $(gauges).each(function() {
        return this.refresh(getRandomInt(0, 80));
      });
    }, 2500);
    $(".easy-pie-chart").each(function() {
      var el;
      el = $(this);
      return $(this).easyPieChart({
        lineWidth: 10,
        size: 150,
        lineCap: "square",
        barColor: Theme.colors[el.data("color")] || Theme.colors.red,
        scaleColor: Theme.colors.gray,
        animate: 1000
      });
    });
    $(".easy-pie-chart-small").each(function() {
      var el;
      el = $(this);
      return $(this).easyPieChart({
        lineWidth: 4,
        size: 40,
        lineCap: "square",
        barColor: Theme.colors[el.attr("data-color")] || Theme.colors.red,
        animate: 1000
      });
    });
    $(".easy-pie-chart-percent").easyPieChart({
      animate: 1000,
      trackColor: "#444",
      scaleColor: "#444",
      lineCap: 'square',
      lineWidth: 15,
      size: 150,
      barColor: function(percent) {
        return "rgb(" + Math.round(200 * percent / 100) + ", " + Math.round(200 * (1 - percent / 100)) + ", 0)";
      }
    });
    return setInterval(function() {
      return $(".easy-pie-chart, .easy-pie-chart-percent").each(function() {
        var val;
        val = getRandomInt(0, 80);
        $(this).data("easyPieChart").update(val);
        return $(this).find("span").text("" + val + "%");
      });
    }, 2500);
  });

}).call(this);