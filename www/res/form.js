var map = function(it, fn) {
    var result = [];
    for (var key in it) {
        if (it.hasOwnProperty(key)) {
            result.push(fn(it[key], key));
        }
    }
    return result;
};

var select = function(it, fn) {
    var result = [];
    for (var key in it) {
        if (it.hasOwnProperty(key) && fn(it[key])) {
            result[key] = it[key];
        }
    }
    return result;
};

var getElementPosition = function(obj) {
    var curleft = 0;
    var curtop = 0;
    if (obj.offsetParent) {
        curleft = obj.offsetLeft;
        curtop = obj.offsetTop;
        while ((obj = obj.offsetParent)) {
            curleft += obj.offsetLeft;
            curtop += obj.offsetTop;
        }
    }
    return {x: curleft, y: curtop};
};

var createElement = function(nodeName, attributes) {
    var el;
    try {
        var html = "<" + nodeName + map(
            attributes,
            function(v, k) {
                return k + '="' + v + '"';
            }
        ).join(" ") + "/>";
        el = document.createElement(html);
    } catch (err) {
        el = document.createElement(nodeName);
        map(
            attributes,
            function(v, k) {
                el.setAttribute(k, v);
            });
    }
    return el;
};

var makeDeferrer = function(defaultMsec) {
    var timeout = null;
    return function(fn, msec) {
        if (timeout) {
            clearTimeout(timeout);
            timeout = null;
        }
        timeout = setTimeout(
            function() {
                timeout = null;
                fn();
            }, msec || defaultMsec || 200);
    };
};

var createXhr = (function() {
                 var xhr = null;
                 if (window.XMLHttpRequest) {
                     return function() {
                         if (xhr && xhr.readyState < 4) {
                             xhr.abort();
                             xhr = null;
                         }
                         return new XMLHttpRequest();
                     };
                 } else {
                     return function() {
                         if (xhr && xhr.readyState < 4) {
                             xhr.abort();
                             xhr = null;
                         }
                         return new ActiveXObject("Microsoft.XMLHTTP");
                     };
                 }
             })();

var uniqueId = (function() {
                var id = (new Date()).getTime();
                return function() {
                    return id++;
                };
            })();

/** Registers an event handler. Returns a detach function. */
var bind = function(element, signal, fnc) {
    if (element.addEventListener) {
        var wrapper = function(e) {
            var evt = {
                stop : function() {
                    if (e.cancelable) {
                        e.preventDefault();
                    }
                    e.stopPropagation();
                },
                key: function() {
                    return e.keyCode;
                },
                target: element
            };
            fnc(evt);
        };
        element.addEventListener(signal.replace(/^(on)/, ""), wrapper, false);
        return function() {
            element.removeEventListener(signal.replace(/^(on)/, ""), wrapper, false);
        };
    } else if (element.attachEvent) {
        var wrapper = function() {
            var e = window.event;
            var evt = {
                stop : function() {
                    e.cancelBubble = true;
                    e.returnValue = false;
                },
                key: function() {
                    return e.which;
                },
                target: element
            };
            fnc(evt);
        };
        element.attachEvent(signal, wrapper);
        return function() {
            element.detachEvent(signal, wrapper);
        };
    } else {
        throw new Error("Can't register event handler");
    }
};

var initContainer = function(append, container, create) {
    bind(
        append, 'click',
        function(event) {
            create(container);
            event.stop();
        });
    map(
        select(
            container.getElementsByTagName("div"),
            function(div) {
                return div.className.match(/\bfieldset\b/);
            }),
        function(fieldset) {
            var remove = select(
                fieldset.getElementsByTagName("a"),
                function(span) {
                    return span.className.match(/\bremove\b/);
                })[0];
            bindRemoveButton(fieldset, remove);
        });
};

var bindRemoveButton = function(fieldset, button) {
    bind(
        button, 'click',
        function(event) {
            fieldset.parentNode.removeChild(fieldset);
            event.stop();
        });
};

var makeRemoveButton = function(fieldset, name) {
    var span = document.createElement("a");
    span.href = "#";
    span.className = "remove";
    span.title = "Click to remove this " + name;
    span.appendChild(document.createTextNode("\u2297"));
    bindRemoveButton(fieldset, span);
    fieldset.appendChild(span);
};

var defer = makeDeferrer();

var autoComplete = function(event) {
    var div = document.getElementById("maintainers-autocomplete");
    var list = div.getElementsByTagName("li");
    var current = null;
    var currentIndex = -1;
    map(
        list,
        function(li) {
            if (!current) {
                currentIndex++;
            }
            if (li.className == "selected") {
                current = li;
            }
        });
    if (event.key() == 38) { // up
        if (current && currentIndex > 0) {
            current.className = "";
            list[currentIndex - 1].className = "selected";
        }
    } else if (event.key() == 40) { // down
        if (current) {
            if (currentIndex < (list.length - 1)) {
                current.className = "";
                list[currentIndex + 1].className = "selected";
            }
        } else if (list.length > 0 && list[0]) {
            list[0].className = "selected";
        }
    } else if (event.key() == 13) { // enter
        event.stop();
        if (current && (div.style.display != "none")) {
            current.selectItem();
            event.target.blur();
        }
    } else if (event.key() == 27) { // escape
        event.stop();
        div.style.display = "none";
    } else {
        div.style.display = "none";
        defer(
            function() {
                var xhr = createXhr();
                xhr.open("GET", URL_AUTOCOMPLETE_MAINTAINERS + "&q=" + escape(event.target.value), true);
                xhr.setRequestHeader("Accept", "application/json");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4) {
                        if (xhr.status == 200) {
                            var json;
                            eval("json = " + xhr.responseText + ";");
                            var inputFields = {};
                            map(
                                event.target.parentNode.parentNode.getElementsByTagName("input"),
                                function(input) {
                                    inputFields[input.name.match(/\[([^\]]+)\]$/)[1]] = input;
                                });
                            updateAutocomplete(event.target, inputFields, json);
                        } else {
                            if (console) {
                                console.log("There was a problem retrieving the data:\n" + xhr.statusText);
                            }
                        }
                        xhr = null;
                    }
                };
                xhr.send(null);
            }, 200);
    }
};

var updateAutocomplete = function(input, inputFields, json) {
    var div = document.getElementById("maintainers-autocomplete");
    div.innerHTML = "";
    if (json.length == 0) {
        div.style.display = "none";
        return;
    }
    var ul = div.appendChild(document.createElement("ul"));
    map(
        json,
        function(data) {
            var li = document.createElement("li");
            var bold = document.createElement("b");
            bold.appendChild(document.createTextNode(data.user));
            li.appendChild(bold);
            if (data.name) {
                li.appendChild(document.createTextNode(" " + data.name));
            }
            if (data.email) {
                li.appendChild(document.createTextNode(" <" + data.email + ">"));
            }
            var handler = function() {
                inputFields["user"].value = data.user;
                inputFields["name"].value = data.name;
                inputFields["email"].value = data.email;
            };
            bind(li, "click", handler);
            li.selectItem = handler;
            ul.appendChild(li);
        });
    var pos = getElementPosition(input);
    div.style.display = "block";
    div.style.top = (pos.y + (input.offsetHeight || 0)) + "px";
    div.style.left = pos.x + "px";
};

var hideAutoComplete = function() {
    defer(
        function() {
            var div = document.getElementById("maintainers-autocomplete");
            div.style.display = "none";
        }, 100);
};

var init = function() {
    initContainer(
        document.getElementById("filespec-append"),
        document.getElementById("filespec-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "filespec-fieldset fieldset";
            makeRemoveButton(fieldset, "filespec");
            var inputPath = createElement("input", {type: "text", name: "filespec[" + id + "][path]"});
            fieldset.appendChild(inputPath);
            var inputType = createElement("select", {name: "filespec[" + id + "][type]"});
            inputType.appendChild(new Option("src"));
            inputType.appendChild(new Option("doc"));
            inputType.appendChild(new Option("bin"));
            fieldset.appendChild(inputType);
            container.appendChild(fieldset);
            return fieldset;
        });
    initContainer(
        document.getElementById("ignore-append"),
        document.getElementById("ignore-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "ignore-fieldset fieldset";
            makeRemoveButton(fieldset, "ignore");
            var input = createElement("input", {type: "text", name: "ignore[" + id + "]"});
            fieldset.appendChild(input);
            container.appendChild(fieldset);
            return fieldset;
        });
    initContainer(
        document.getElementById("maintainers-append"),
        document.getElementById("maintainers-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "maintainers-fieldset fieldset";

            var makeElement = function(name, input) {
                var label = document.createElement("label");
                var span = document.createElement("span");
                span.appendChild(document.createTextNode(name + ": "));
                label.appendChild(span);
                input.name = "maintainers[" + id + "][" + name + "]";
                label.appendChild(input);
                fieldset.appendChild(label);
                return input;
            };

            makeRemoveButton(fieldset, "maintainer");
            map(
                ["user", "name", "email"],
                function(name) {
                    var input = createElement("input", {type: "text", name: "maintainers[" + id + "][" + name + "]"});
                    makeElement(name, input);
                    if (name == "user") {
                        bind(input, 'keydown', autoComplete);
                        bind(input, 'blur', hideAutoComplete);
                    }
                });

            var inputType = createElement("select", {name: "maintainers[" + id + "][type]"});
            inputType.appendChild(new Option("lead"));
            inputType.appendChild(new Option("helper"));
            makeElement("type", inputType);

            container.appendChild(fieldset);
            return fieldset;
        });
    // install auto-complete for static maintainers-fieldsets
    map(
        select(
            document.getElementById("maintainers-container").getElementsByTagName("div"),
            function(div) {
                return div.className.match(/\bfieldset\b/);
            }),
        function(fieldset) {
            map(
                fieldset.getElementsByTagName("input"),
                function(input) {
                    if (input.name.match(/\[user\]$/)) {
                        bind(input, 'keydown', autoComplete);
                        bind(input, 'blur', hideAutoComplete);
                    }
                });

        });

};