var map = function(array, fn) {
    var result = [];
    for (var i=0, l=array.length; i < l; i++) {
        result.push(fn(array[i]));
    }
    return result;
};

var pairs = function(hash, fn) {
    for (var key in hash) {
        if (hash.hasOwnProperty(key)) {
            fn(key, hash[key]);
        }
    }
};

var select = function(array, fn) {
    var result = [];
    for (var i=0, l=array.length; i < l; i++) {
        if (fn(array[i])) {
            result.push(array[i]);
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
        var html = "<" + nodeName + pairs(
            attributes,
            function(k, v) {
                return k + '="' + v + '"';
            }
        ).join(" ") + "/>";
        el = document.createElement(html);
    } catch (err) {
        el = document.createElement(nodeName);
        pairs(
            attributes,
            function(k, v) {
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
                if (data.is_locked) {
                    inputFields["user"].disabled = true;
                    inputFields["name"].disabled = true;
                    inputFields["email"].disabled = true;
                }
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

var tooltipDefer = makeDeferrer();

var showTooltip = function(element, message) {
    tooltipDefer(
        function() {
            var div = document.getElementById("tooltip");
            div.innerHTML = message;
            var pos = getElementPosition(element);
            div.style.display = "block";
            div.style.top = (pos.y + (element.offsetHeight || 0)) + "px";
            div.style.left = pos.x + "px";
        }, 10);
};

var hideTooltip = function() {
    tooltipDefer(
        function() {
            var div = document.getElementById("tooltip");
            div.style.display = "none";
        }, 100);
};

var installTooltip = function(element, message) {
    if (typeof(element) == "string") {
        element = document.getElementById(element);
    }
    if (element) {
        bind(element, 'onmouseover', function() { showTooltip(element, message); });
        bind(element, 'onmouseout', hideTooltip);
    }
    return element;
};

var wrapInLabel = function(name, input) {
    var label = document.createElement("label");
    var span = document.createElement("span");
    span.appendChild(document.createTextNode(name + ": "));
    label.appendChild(span);
    label.appendChild(input);
    return label;
};

var init = function() {
    var tooltext = {};
    tooltext['path'] = "<h3>Path in repository.</h3><p>Select the location of files, relative to the repository root. Typically <code>/lib</code> or <code>/src</code></p>";
    tooltext['destination'] = "<h3>Install path.</h3><p>Select the path where the files will be installed to.</p>";
    tooltext['ignore'] = "<h3>Ignore pattern.</h3><p>Perl compatible regular expression. Filenames that match this expression will not be included. Use to skip tests etc. from the repository.</p>";
    tooltext['channel'] = "<h3>Package URL</h3><p>Enter the URL to the package. This should be on the form <code>channel/Package_Name</code>. For example: <code>pearhub.org/Konstrukt</code></p>";
    tooltext['version'] = "<h3>Minimum required version.</h3><p>Should be on the form <code>X.X.X</code></p>";
    tooltext['user'] = "<h3>Username</h3><p>Should be a single, short name. The username is unique across projects.</p>";
    tooltext['name'] = "<h3>Name</h3><p>Enter the full name of the person. This field is optional.</p>";
    tooltext['email'] = "<h3>E-mail address.</h3><p>This field is optional.</p>";
    initContainer(
        document.getElementById("files-append"),
        document.getElementById("files-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "files-fieldset fieldset";
            makeRemoveButton(fieldset, "path");
            fieldset.appendChild(
                installTooltip(
                    wrapInLabel(
                        "path",
                        createElement("input", {type: "text", name: "files[" + id + "][path]"})), tooltext['path']));
            fieldset.appendChild(
                installTooltip(
                    wrapInLabel(
                        "destination",
                        createElement("input", {type: "text", name: "files[" + id + "][destination]", value: "/"})), tooltext['destination']));
            fieldset.appendChild(
                installTooltip(
                    wrapInLabel(
                        "ignore",
                        createElement("input", {type: "text", name: "files[" + id + "][ignore]"})), tooltext['ignore']));
            container.appendChild(fieldset);
            return fieldset;
        });
    initContainer(
        document.getElementById("dependencies-append"),
        document.getElementById("dependencies-container"),
        function(container) {
            var id = uniqueId();
            var fieldset = document.createElement("div");
            fieldset.className = "dependencies-fieldset fieldset";
            makeRemoveButton(fieldset, "dependency");
            fieldset.appendChild(
                installTooltip(
                    wrapInLabel(
                        "channel",
                        createElement("input", {type: "text", name: "dependencies[" + id + "][channel]"})), tooltext['channel']));
            fieldset.appendChild(
                installTooltip(
                    wrapInLabel(
                        "version",
                        createElement("input", {type: "text", name: "dependencies[" + id + "][version]"})), tooltext['version']));
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
                if (typeof(tooltext[name]) != "undefined") {
                    installTooltip(input, tooltext[name]);
                }
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
            inputType.appendChild(new Option("developer"));
            inputType.appendChild(new Option("contributor"));
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
    installTooltip("field-name", "<h3>Enter the projects name.</h3><p>This must be unique. If you're maintaining an unofficial fork of a project, you should prefix the name with your name/handle to prevent conflicts. Eg. <code>troelskn_Konstrukt</code>, rather than <code>Konstrukt</code></p><p>You can only use alphanumeric characters and underscores for names.</p>");
    installTooltip("field-summary", "<h3>Short summary.</h3><p>Enter a single line, summarising your project.</p>");
    installTooltip("field-description", "<h3>A longer description.</h3><p>Enter a paragraph or two, describing your project.</p>");
    installTooltip("field-repository", "<h3>Repository URL</h3><p>Enter the URL for the projects repository here. Currently only subversion and git repositories are supported.</p><p>If you use subversion, you should enter the URL to the main branch or trunk.</p><p>If your project is hosted at github, use the read-only URL.</p>");
    installTooltip("field-href", "<h3>Enter URL to the projects website.</h3><p>This field is optional</p>");
    installTooltip("field-php-version", "<h3>Minimum supported PHP version.</h3><p>If in doubt, leave this untouched</p>");
    installTooltip("field-license-title", "<h3>Enter the project license.</h3>");
    installTooltip("field-license-href", "<h3>Enter a URL to the license text.</h3><p>This field is optional</p>");
};