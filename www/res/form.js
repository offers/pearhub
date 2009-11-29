map = function(it, fn) {
    var result = [];
    for (var key in it) {
        if (it.hasOwnProperty(key)) {
            result.push(fn(it[key]));
        }
    }
    return result;
};
getElementPosition = function(obj) {
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
makeDeferrer = function(defaultMsec) {
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
uniqueId = (function() {
                var __uniqueId = (new Date()).getTime();
                return function() {
                    return __uniqueId++;
                };
            })();
isBlank = function(fieldset) {
    var isEmpty = true;
    map(
        fieldset.getElementsByTagName("input"),
        function(input) {
            isEmpty = isEmpty && !! input.value.match(/^\s*$/);
        });
    return isEmpty;
};
findFieldsets = function(container) {
    var fieldsets = [];
    map(
        container.childNodes,
        function(node) {
            if (node.className && node.className.match(/fieldset/)) {
                fieldsets.push(node);
            }
        });
    return fieldsets;
};
bind = function(target, event, fn) {
    var handler = "on" + event;
    var eventAccess = {
        "target": target
    };
    if (typeof target[handler] == "function") {
        var old = target[handler];
        target[handler] = function() {
            old(eventAccess);
            fn(eventAccess);
        };
    } else {
        target[handler] = function() {
            fn(eventAccess);
        };
    }
};
bindFieldset = function(fieldset, handler) {
    map(
        fieldset.getElementsByTagName("input"),
        function(input) {
            bind(input, 'change', handler);
        });
};
update = function(container, create) {
// loop over each fieldset:
//   if empty + not last => remove
// if not last empty => add one
    var fieldsets = findFieldsets(container);
    var last = fieldsets.length - 1;
    for (var ii=0,ll=fieldsets.length; ii < ll; ii++) {
        var fieldset = fieldsets[ii];
        var isLast = ii == last;
        if (!isLast && isBlank(fieldset)) {
            fieldset.parentElement.removeChild(fieldset);
        }
    }
    if (fieldsets.length == 0 || !isBlank(fieldsets[last])) {
        create();
    }
};
initContainer = function(container, create) {
    var handler = function() {
        update(
            container,
            function() {
                bindFieldset(create(container), handler);
            });
    };
    map(
        findFieldsets(container),
        function(fieldset) {
            bindFieldset(fieldset, handler);
        });
    handler();
};
init = function() {
    initContainer(
        document.getElementById("filespec-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "filespec-fieldset";
            var inputPath = document.createElement("input");
            inputPath.name = "filespec[" + id + "][path]";
            fieldset.appendChild(inputPath);
            var inputType = document.createElement("select");
            inputType.name = "filespec[" + id + "][type]";
            inputType.appendChild(new Option("src"));
            inputType.appendChild(new Option("doc"));
            inputType.appendChild(new Option("bin"));
            fieldset.appendChild(inputType);
            container.appendChild(fieldset);
            return fieldset;
        });
    initContainer(
        document.getElementById("ignore-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "ignore-fieldset";
            var input = document.createElement("input");
            input.name = "ignore[" + id + "]";
            fieldset.appendChild(input);
            container.appendChild(fieldset);
            return fieldset;
        });
    initContainer(
        document.getElementById("maintainers-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "maintainers-fieldset fieldset";
            var createXhr;
            if (window.XMLHttpRequest) {
                createXhr = function() {
                    return new XMLHttpRequest();
                };
            } else {
                createXhr = function() {
                    return new ActiveXObject("Microsoft.XMLHTTP");
                };
            }
            var xhr = null;
            var defer = makeDeferrer();
            var autoComplete = function(event) {
                defer(
                    function() {
                        if (xhr) {
                            xhr.abort();
                            xhr = null;
                        }
                        xhr = createXhr();
                        xhr.open("GET", URL_AUTOCOMPLETE_MAINTAINERS + "&q=" + escape(event.target.value), true);
                        xhr.setRequestHeader("Accept", "application/json");
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState == 4) {
                                if (xhr.status == 200) {
                                    var json;
                                    eval("json = " + xhr.responseText + ";");
                                    updateAutocomplete(event.target, json);
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
            };
            var updateAutocomplete = function(input, json) {
                var div = document.getElementById("maintainers-autocomplete");
                div.innerHTML = "";
                var ul = div.appendChild(document.createElement("ul"));
                map(
                    json,
                    function(data) {
                        var li = document.createElement("li");
                        li.appendChild(document.createTextNode(data.user + " / " + data.name + " / " + data.email));
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

            var bindAutocomplete = function(element) {
                bind(element, 'keydown', autoComplete);
                bind(element, 'blur', hideAutoComplete);
            };

            var span = document.createElement("span");
            span.className = "remove";
            span.title = "Click to remove this maintainer";
            span.appendChild(document.createTextNode("\u2297"));
            bind(
                span, 'click',
                function() {
                    container.removeChild(fieldset);
                });
            fieldset.appendChild(span);

            bindAutocomplete(makeElement("user", document.createElement("input")));
            bindAutocomplete(makeElement("name", document.createElement("input")));
            bindAutocomplete(makeElement("email", document.createElement("input")));

            var inputType = document.createElement("select");
            inputType.name = "maintainers[" + id + "][type]";
            inputType.appendChild(new Option("lead"));
            inputType.appendChild(new Option("helper"));
            makeElement("type", inputType);

            container.appendChild(fieldset);
            return fieldset;
        });

};