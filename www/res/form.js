map = function(it, fn) {
    var result = [];
    for (var key in it) {
        if (it.hasOwnProperty(key)) {
            result.push(fn(it[key]));
        }
    }
    return result;
};
select = function(it, fn) {
    var result = [];
    for (var key in it) {
        if (it.hasOwnProperty(key) && fn(it[key])) {
            result[key] = it[key];
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
initContainer = function(append, container, create) {
    bind(
        append, 'click',
        function() {
            create(container);
        });
    map(
        select(
            container.getElementsByTagName("div"),
            function(div) {
                return div.className.match(/\bfieldset\b/);
            }),
        function(fieldset) {
            var remove = select(
                fieldset.getElementsByTagName("span"),
                function(span) {
                    return span.className.match(/\bremove\b/);
                })[0];
            bindRemoveButton(fieldset, remove);
        });
};
bindRemoveButton = function(fieldset, button) {
    bind(
        button, 'click',
        function() {
            fieldset.parentNode.removeChild(fieldset);
        });
};
makeRemoveButton = function(fieldset, name) {
    var span = document.createElement("span");
    span.className = "remove";
    span.title = "Click to remove this " + name;
    span.appendChild(document.createTextNode("\u2297"));
    bindRemoveButton(fieldset, span);
    fieldset.appendChild(span);
};
init = function() {
    initContainer(
        document.getElementById("filespec-append"),
        document.getElementById("filespec-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "filespec-fieldset fieldset";
            makeRemoveButton(fieldset, "filespec");
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
        document.getElementById("ignore-append"),
        document.getElementById("ignore-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "ignore-fieldset fieldset";
            makeRemoveButton(fieldset, "ignore");
            var input = document.createElement("input");
            input.name = "ignore[" + id + "]";
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

            makeRemoveButton(fieldset, "maintainer");
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