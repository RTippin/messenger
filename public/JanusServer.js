/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/Janus.js":
/*!*******************************!*\
  !*** ./resources/js/Janus.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var webrtc_adapter__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! webrtc-adapter */ "./node_modules/webrtc-adapter/src/js/adapter_core.js");
function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }


/*
	The MIT License (MIT)

	Copyright (c) 2016 Meetecho

	Permission is hereby granted, free of charge, to any person obtaining
	a copy of this software and associated documentation files (the "Software"),
	to deal in the Software without restriction, including without limitation
	the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included
	in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
	OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
	THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
	OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
	ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
 */
// List of sessions

Janus.sessions = {};

Janus.isExtensionEnabled = function () {
  if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
    // No need for the extension, getDisplayMedia is supported
    return true;
  }

  if (window.navigator.userAgent.match('Chrome')) {
    var chromever = parseInt(window.navigator.userAgent.match(/Chrome\/(.*) /)[1], 10);
    var maxver = 33;
    if (window.navigator.userAgent.match('Linux')) maxver = 35; // "known" crash in chrome 34 and 35 on linux

    if (chromever >= 26 && chromever <= maxver) {
      // Older versions of Chrome don't support this extension-based approach, so lie
      return true;
    }

    return Janus.extension.isInstalled();
  } else {
    // Firefox of others, no need for the extension (but this doesn't mean it will work)
    return true;
  }
};

var defaultExtension = {
  // Screensharing Chrome Extension ID
  extensionId: 'hapfgfdkleiggjjpfpenajgdnfckjpaj',
  isInstalled: function isInstalled() {
    return document.querySelector('#janus-extension-installed') !== null;
  },
  getScreen: function getScreen(callback) {
    var pending = window.setTimeout(function () {
      var error = new Error('NavigatorUserMediaError');
      error.name = 'The required Chrome extension is not installed: click <a href="#">here</a> to install it. (NOTE: this will need you to refresh the page)';
      return callback(error);
    }, 1000);
    this.cache[pending] = callback;
    window.postMessage({
      type: 'janusGetScreen',
      id: pending
    }, '*');
  },
  init: function init() {
    var cache = {};
    this.cache = cache; // Wait for events from the Chrome Extension

    window.addEventListener('message', function (event) {
      if (event.origin != window.location.origin) return;

      if (event.data.type == 'janusGotScreen' && cache[event.data.id]) {
        var callback = cache[event.data.id];
        delete cache[event.data.id];

        if (event.data.sourceId === '') {
          // user canceled
          var error = new Error('NavigatorUserMediaError');
          error.name = 'You cancelled the request for permission, giving up...';
          callback(error);
        } else {
          callback(null, event.data.sourceId);
        }
      } else if (event.data.type == 'janusGetScreenPending') {
        console.log('clearing ', event.data.id);
        window.clearTimeout(event.data.id);
      }
    });
  }
};

Janus.useDefaultDependencies = function (deps) {
  var f = deps && deps.fetch || fetch;
  var p = deps && deps.Promise || Promise;
  var socketCls = deps && deps.WebSocket || WebSocket;
  return {
    newWebSocket: function newWebSocket(server, proto) {
      return new socketCls(server, proto);
    },
    extension: deps && deps.extension || defaultExtension,
    isArray: function isArray(arr) {
      return Array.isArray(arr);
    },
    webRTCAdapter: deps && deps.adapter || webrtc_adapter__WEBPACK_IMPORTED_MODULE_0__.default,
    httpAPICall: function httpAPICall(url, options) {
      var fetchOptions = {
        method: options.verb,
        headers: {
          'Accept': 'application/json, text/plain, */*'
        },
        cache: 'no-cache'
      };

      if (options.verb === "POST") {
        fetchOptions.headers['Content-Type'] = 'application/json';
      }

      if (options.withCredentials !== undefined) {
        fetchOptions.credentials = options.withCredentials === true ? 'include' : options.withCredentials ? options.withCredentials : 'omit';
      }

      if (options.body) {
        fetchOptions.body = JSON.stringify(options.body);
      }

      var fetching = f(url, fetchOptions)["catch"](function (error) {
        return p.reject({
          message: 'Probably a network error, is the server down?',
          error: error
        });
      });
      /*
      * fetch() does not natively support timeouts.
      * Work around this by starting a timeout manually, and racing it agains the fetch() to see which thing resolves first.
      */

      if (options.timeout) {
        var timeout = new p(function (resolve, reject) {
          var timerId = setTimeout(function () {
            clearTimeout(timerId);
            return reject({
              message: 'Request timed out',
              timeout: options.timeout
            });
          }, options.timeout);
        });
        fetching = p.race([fetching, timeout]);
      }

      fetching.then(function (response) {
        if (response.ok) {
          if (_typeof(options.success) === _typeof(Janus.noop)) {
            return response.json().then(function (parsed) {
              options.success(parsed);
            })["catch"](function (error) {
              return p.reject({
                message: 'Failed to parse response body',
                error: error,
                response: response
              });
            });
          }
        } else {
          return p.reject({
            message: 'API call failed',
            response: response
          });
        }
      })["catch"](function (error) {
        if (_typeof(options.error) === _typeof(Janus.noop)) {
          options.error(error.message || '<< internal error >>', error);
        }
      });
      return fetching;
    }
  };
};

Janus.useOldDependencies = function (deps) {
  var jq = deps && deps.jQuery || jQuery;
  var socketCls = deps && deps.WebSocket || WebSocket;
  return {
    newWebSocket: function newWebSocket(server, proto) {
      return new socketCls(server, proto);
    },
    isArray: function isArray(arr) {
      return jq.isArray(arr);
    },
    extension: deps && deps.extension || defaultExtension,
    webRTCAdapter: deps && deps.adapter || webrtc_adapter__WEBPACK_IMPORTED_MODULE_0__.default,
    httpAPICall: function httpAPICall(url, options) {
      var payload = options.body !== undefined ? {
        contentType: 'application/json',
        data: JSON.stringify(options.body)
      } : {};
      var credentials = options.withCredentials !== undefined ? {
        xhrFields: {
          withCredentials: options.withCredentials
        }
      } : {};
      return jq.ajax(jq.extend(payload, credentials, {
        url: url,
        type: options.verb,
        cache: false,
        dataType: 'json',
        async: options.async,
        timeout: options.timeout,
        success: function success(result) {
          if (_typeof(options.success) === _typeof(Janus.noop)) {
            options.success(result);
          }
        },
        error: function error(xhr, status, err) {
          if (_typeof(options.error) === _typeof(Janus.noop)) {
            options.error(status, err);
          }
        }
      }));
    }
  };
};

Janus.noop = function () {};

Janus.dataChanDefaultLabel = "JanusDataChannel"; // Note: in the future we may want to change this, e.g., as was
// attempted in https://github.com/meetecho/janus-gateway/issues/1670

Janus.endOfCandidates = null; // Initialization

Janus.init = function (options) {
  options = options || {};
  options.callback = typeof options.callback == "function" ? options.callback : Janus.noop;

  if (Janus.initDone) {
    // Already initialized
    options.callback();
  } else {
    if (typeof console == "undefined" || typeof console.log == "undefined") {
      console = {
        log: function log() {}
      };
    } // Console logging (all debugging disabled by default)


    Janus.trace = Janus.noop;
    Janus.debug = Janus.noop;
    Janus.vdebug = Janus.noop;
    Janus.log = Janus.noop;
    Janus.warn = Janus.noop;
    Janus.error = Janus.noop;

    if (options.debug === true || options.debug === "all") {
      // Enable all debugging levels
      Janus.trace = console.trace.bind(console);
      Janus.debug = console.debug.bind(console);
      Janus.vdebug = console.debug.bind(console);
      Janus.log = console.log.bind(console);
      Janus.warn = console.warn.bind(console);
      Janus.error = console.error.bind(console);
    } else if (Array.isArray(options.debug)) {
      var _iterator = _createForOfIteratorHelper(options.debug),
          _step;

      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var d = _step.value;

          switch (d) {
            case "trace":
              Janus.trace = console.trace.bind(console);
              break;

            case "debug":
              Janus.debug = console.debug.bind(console);
              break;

            case "vdebug":
              Janus.vdebug = console.debug.bind(console);
              break;

            case "log":
              Janus.log = console.log.bind(console);
              break;

            case "warn":
              Janus.warn = console.warn.bind(console);
              break;

            case "error":
              Janus.error = console.error.bind(console);
              break;

            default:
              console.error("Unknown debugging option '" + d + "' (supported: 'trace', 'debug', 'vdebug', 'log', warn', 'error')");
              break;
          }
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }
    }

    Janus.log("Initializing library");
    var usedDependencies = options.dependencies || Janus.useDefaultDependencies();
    Janus.isArray = usedDependencies.isArray;
    Janus.webRTCAdapter = usedDependencies.webRTCAdapter;
    Janus.httpAPICall = usedDependencies.httpAPICall;
    Janus.newWebSocket = usedDependencies.newWebSocket;
    Janus.extension = usedDependencies.extension;
    Janus.extension.init(); // Helper method to enumerate devices

    Janus.listDevices = function (callback, config) {
      callback = typeof callback == "function" ? callback : Janus.noop;
      if (config == null) config = {
        audio: true,
        video: true
      };

      if (Janus.isGetUserMediaAvailable()) {
        navigator.mediaDevices.getUserMedia(config).then(function (stream) {
          navigator.mediaDevices.enumerateDevices().then(function (devices) {
            Janus.debug(devices);
            callback(devices); // Get rid of the now useless stream

            try {
              var tracks = stream.getTracks();

              var _iterator2 = _createForOfIteratorHelper(tracks),
                  _step2;

              try {
                for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
                  var mst = _step2.value;
                  if (mst) mst.stop();
                }
              } catch (err) {
                _iterator2.e(err);
              } finally {
                _iterator2.f();
              }
            } catch (e) {}
          });
        })["catch"](function (err) {
          Janus.error(err);
          callback([]);
        });
      } else {
        Janus.warn("navigator.mediaDevices unavailable");
        callback([]);
      }
    }; // Helper methods to attach/reattach a stream to a video element (previously part of adapter.js)


    Janus.attachMediaStream = function (element, stream) {
      try {
        element.srcObject = stream;
      } catch (e) {
        try {
          element.src = URL.createObjectURL(stream);
        } catch (e) {
          Janus.error("Error attaching stream to element");
        }
      }
    };

    Janus.reattachMediaStream = function (to, from) {
      try {
        to.srcObject = from.srcObject;
      } catch (e) {
        try {
          to.src = from.src;
        } catch (e) {
          Janus.error("Error reattaching stream to element");
        }
      }
    }; // Detect tab close: make sure we don't loose existing onbeforeunload handlers
    // (note: for iOS we need to subscribe to a different event, 'pagehide', see
    // https://gist.github.com/thehunmonkgroup/6bee8941a49b86be31a787fe8f4b8cfe)


    var iOS = ['iPad', 'iPhone', 'iPod'].indexOf(navigator.platform) >= 0;
    var eventName = iOS ? 'pagehide' : 'beforeunload';
    var oldOBF = window["on" + eventName];
    window.addEventListener(eventName, function (event) {
      Janus.log("Closing window");

      for (var s in Janus.sessions) {
        if (Janus.sessions[s] && Janus.sessions[s].destroyOnUnload) {
          Janus.log("Destroying session " + s);
          Janus.sessions[s].destroy({
            unload: true,
            notifyDestroyed: false
          });
        }
      }

      if (oldOBF && typeof oldOBF == "function") {
        oldOBF();
      }
    }); // If this is a Safari Technology Preview, check if VP8 is supported

    Janus.safariVp8 = false;

    if (Janus.webRTCAdapter.browserDetails.browser === 'safari' && Janus.webRTCAdapter.browserDetails.version >= 605) {
      // Let's see if RTCRtpSender.getCapabilities() is there
      if (RTCRtpSender && RTCRtpSender.getCapabilities && RTCRtpSender.getCapabilities("video") && RTCRtpSender.getCapabilities("video").codecs && RTCRtpSender.getCapabilities("video").codecs.length) {
        var _iterator3 = _createForOfIteratorHelper(RTCRtpSender.getCapabilities("video").codecs),
            _step3;

        try {
          for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
            var codec = _step3.value;

            if (codec && codec.mimeType && codec.mimeType.toLowerCase() === "video/vp8") {
              Janus.safariVp8 = true;
              break;
            }
          }
        } catch (err) {
          _iterator3.e(err);
        } finally {
          _iterator3.f();
        }

        if (Janus.safariVp8) {
          Janus.log("This version of Safari supports VP8");
        } else {
          Janus.warn("This version of Safari does NOT support VP8: if you're using a Technology Preview, " + "try enabling the 'WebRTC VP8 codec' setting in the 'Experimental Features' Develop menu");
        }
      } else {
        // We do it in a very ugly way, as there's no alternative...
        // We create a PeerConnection to see if VP8 is in an offer
        var testpc = new RTCPeerConnection({});
        testpc.createOffer({
          offerToReceiveVideo: true
        }).then(function (offer) {
          Janus.safariVp8 = offer.sdp.indexOf("VP8") !== -1;

          if (Janus.safariVp8) {
            Janus.log("This version of Safari supports VP8");
          } else {
            Janus.warn("This version of Safari does NOT support VP8: if you're using a Technology Preview, " + "try enabling the 'WebRTC VP8 codec' setting in the 'Experimental Features' Develop menu");
          }

          testpc.close();
          testpc = null;
        });
      }
    } // Check if this browser supports Unified Plan and transceivers
    // Based on https://codepen.io/anon/pen/ZqLwWV?editors=0010


    Janus.unifiedPlan = false;

    if (Janus.webRTCAdapter.browserDetails.browser === 'firefox' && Janus.webRTCAdapter.browserDetails.version >= 59) {
      // Firefox definitely does, starting from version 59
      Janus.unifiedPlan = true;
    } else if (Janus.webRTCAdapter.browserDetails.browser === 'chrome' && Janus.webRTCAdapter.browserDetails.version < 72) {
      // Chrome does, but it's only usable from version 72 on
      Janus.unifiedPlan = false;
    } else if (!window.RTCRtpTransceiver || !('currentDirection' in RTCRtpTransceiver.prototype)) {
      // Safari supports addTransceiver() but not Unified Plan when
      // currentDirection is not defined (see codepen above).
      Janus.unifiedPlan = false;
    } else {
      // Check if addTransceiver() throws an exception
      var tempPc = new RTCPeerConnection();

      try {
        tempPc.addTransceiver('audio');
        Janus.unifiedPlan = true;
      } catch (e) {}

      tempPc.close();
    }

    Janus.initDone = true;
    options.callback();
  }
}; // Helper method to check whether WebRTC is supported by this browser


Janus.isWebrtcSupported = function () {
  return !!window.RTCPeerConnection;
}; // Helper method to check whether devices can be accessed by this browser (e.g., not possible via plain HTTP)


Janus.isGetUserMediaAvailable = function () {
  return navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
}; // Helper method to create random identifiers (e.g., transaction)


Janus.randomString = function (len) {
  var charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  var randomString = '';

  for (var i = 0; i < len; i++) {
    var randomPoz = Math.floor(Math.random() * charSet.length);
    randomString += charSet.substring(randomPoz, randomPoz + 1);
  }

  return randomString;
};

function Janus(gatewayCallbacks) {
  gatewayCallbacks = gatewayCallbacks || {};
  gatewayCallbacks.success = typeof gatewayCallbacks.success == "function" ? gatewayCallbacks.success : Janus.noop;
  gatewayCallbacks.error = typeof gatewayCallbacks.error == "function" ? gatewayCallbacks.error : Janus.noop;
  gatewayCallbacks.destroyed = typeof gatewayCallbacks.destroyed == "function" ? gatewayCallbacks.destroyed : Janus.noop;

  if (!Janus.initDone) {
    gatewayCallbacks.error("Library not initialized");
    return {};
  }

  if (!Janus.isWebrtcSupported()) {
    gatewayCallbacks.error("WebRTC not supported by this browser");
    return {};
  }

  Janus.log("Library initialized: " + Janus.initDone);

  if (!gatewayCallbacks.server) {
    gatewayCallbacks.error("Invalid server url");
    return {};
  }

  var websockets = false;
  var ws = null;
  var wsHandlers = {};
  var wsKeepaliveTimeoutId = null;
  var servers = null;
  var serversIndex = 0;
  var server = gatewayCallbacks.server;

  if (Janus.isArray(server)) {
    Janus.log("Multiple servers provided (" + server.length + "), will use the first that works");
    server = null;
    servers = gatewayCallbacks.server;
    Janus.debug(servers);
  } else {
    if (server.indexOf("ws") === 0) {
      websockets = true;
      Janus.log("Using WebSockets to contact Janus: " + server);
    } else {
      websockets = false;
      Janus.log("Using REST API to contact Janus: " + server);
    }
  }

  var iceServers = gatewayCallbacks.iceServers || [{
    urls: "stun:stun.l.google.com:19302"
  }];
  var iceTransportPolicy = gatewayCallbacks.iceTransportPolicy;
  var bundlePolicy = gatewayCallbacks.bundlePolicy; // Whether IPv6 candidates should be gathered

  var ipv6Support = gatewayCallbacks.ipv6 === true; // Whether we should enable the withCredentials flag for XHR requests

  var withCredentials = false;
  if (gatewayCallbacks.withCredentials !== undefined && gatewayCallbacks.withCredentials !== null) withCredentials = gatewayCallbacks.withCredentials === true; // Optional max events

  var maxev = 10;
  if (gatewayCallbacks.max_poll_events !== undefined && gatewayCallbacks.max_poll_events !== null) maxev = gatewayCallbacks.max_poll_events;
  if (maxev < 1) maxev = 1; // Token to use (only if the token based authentication mechanism is enabled)

  var token = null;
  if (gatewayCallbacks.token !== undefined && gatewayCallbacks.token !== null) token = gatewayCallbacks.token; // API secret to use (only if the shared API secret is enabled)

  var apisecret = null;
  if (gatewayCallbacks.apisecret !== undefined && gatewayCallbacks.apisecret !== null) apisecret = gatewayCallbacks.apisecret; // Whether we should destroy this session when onbeforeunload is called

  this.destroyOnUnload = true;
  if (gatewayCallbacks.destroyOnUnload !== undefined && gatewayCallbacks.destroyOnUnload !== null) this.destroyOnUnload = gatewayCallbacks.destroyOnUnload === true; // Some timeout-related values

  var keepAlivePeriod = 25000;
  if (gatewayCallbacks.keepAlivePeriod !== undefined && gatewayCallbacks.keepAlivePeriod !== null) keepAlivePeriod = gatewayCallbacks.keepAlivePeriod;
  if (isNaN(keepAlivePeriod)) keepAlivePeriod = 25000;
  var longPollTimeout = 60000;
  if (gatewayCallbacks.longPollTimeout !== undefined && gatewayCallbacks.longPollTimeout !== null) longPollTimeout = gatewayCallbacks.longPollTimeout;
  if (isNaN(longPollTimeout)) longPollTimeout = 60000; // overrides for default maxBitrate values for simulcasting

  function getMaxBitrates(simulcastMaxBitrates) {
    var maxBitrates = {
      high: 900000,
      medium: 300000,
      low: 100000
    };

    if (simulcastMaxBitrates !== undefined && simulcastMaxBitrates !== null) {
      if (simulcastMaxBitrates.high) maxBitrates.high = simulcastMaxBitrates.high;
      if (simulcastMaxBitrates.medium) maxBitrates.medium = simulcastMaxBitrates.medium;
      if (simulcastMaxBitrates.low) maxBitrates.low = simulcastMaxBitrates.low;
    }

    return maxBitrates;
  }

  var connected = false;
  var sessionId = null;
  var pluginHandles = {};
  var that = this;
  var retries = 0;
  var transactions = {};
  createSession(gatewayCallbacks); // Public methods

  this.getServer = function () {
    return server;
  };

  this.isConnected = function () {
    return connected;
  };

  this.reconnect = function (callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    callbacks["reconnect"] = true;
    createSession(callbacks);
  };

  this.getSessionId = function () {
    return sessionId;
  };

  this.destroy = function (callbacks) {
    destroySession(callbacks);
  };

  this.attach = function (callbacks) {
    createHandle(callbacks);
  };

  function eventHandler() {
    if (sessionId == null) return;
    Janus.debug('Long poll...');

    if (!connected) {
      Janus.warn("Is the server down? (connected=false)");
      return;
    }

    var longpoll = server + "/" + sessionId + "?rid=" + new Date().getTime();
    if (maxev) longpoll = longpoll + "&maxev=" + maxev;
    if (token) longpoll = longpoll + "&token=" + encodeURIComponent(token);
    if (apisecret) longpoll = longpoll + "&apisecret=" + encodeURIComponent(apisecret);
    Janus.httpAPICall(longpoll, {
      verb: 'GET',
      withCredentials: withCredentials,
      success: handleEvent,
      timeout: longPollTimeout,
      error: function error(textStatus, errorThrown) {
        Janus.error(textStatus + ":", errorThrown);
        retries++;

        if (retries > 3) {
          // Did we just lose the server? :-(
          connected = false;
          gatewayCallbacks.error("Lost connection to the server (is it down?)");
          return;
        }

        eventHandler();
      }
    });
  } // Private event handler: this will trigger plugin callbacks, if set


  function handleEvent(json, skipTimeout) {
    retries = 0;
    if (!websockets && sessionId !== undefined && sessionId !== null && skipTimeout !== true) eventHandler();

    if (!websockets && Janus.isArray(json)) {
      // We got an array: it means we passed a maxev > 1, iterate on all objects
      for (var i = 0; i < json.length; i++) {
        handleEvent(json[i], true);
      }

      return;
    }

    if (json["janus"] === "keepalive") {
      // Nothing happened
      Janus.vdebug("Got a keepalive on session " + sessionId);
      return;
    } else if (json["janus"] === "ack") {
      // Just an ack, we can probably ignore
      Janus.debug("Got an ack on session " + sessionId);
      Janus.debug(json);
      var transaction = json["transaction"];

      if (transaction) {
        var reportSuccess = transactions[transaction];
        if (reportSuccess) reportSuccess(json);
        delete transactions[transaction];
      }

      return;
    } else if (json["janus"] === "success") {
      // Success!
      Janus.debug("Got a success on session " + sessionId);
      Janus.debug(json);
      var transaction = json["transaction"];

      if (transaction) {
        var reportSuccess = transactions[transaction];
        if (reportSuccess) reportSuccess(json);
        delete transactions[transaction];
      }

      return;
    } else if (json["janus"] === "trickle") {
      // We got a trickle candidate from Janus
      var sender = json["sender"];

      if (!sender) {
        Janus.warn("Missing sender...");
        return;
      }

      var pluginHandle = pluginHandles[sender];

      if (!pluginHandle) {
        Janus.debug("This handle is not attached to this session");
        return;
      }

      var candidate = json["candidate"];
      Janus.debug("Got a trickled candidate on session " + sessionId);
      Janus.debug(candidate);
      var config = pluginHandle.webrtcStuff;

      if (config.pc && config.remoteSdp) {
        // Add candidate right now
        Janus.debug("Adding remote candidate:", candidate);

        if (!candidate || candidate.completed === true) {
          // end-of-candidates
          config.pc.addIceCandidate(Janus.endOfCandidates);
        } else {
          // New candidate
          config.pc.addIceCandidate(candidate);
        }
      } else {
        // We didn't do setRemoteDescription (trickle got here before the offer?)
        Janus.debug("We didn't do setRemoteDescription (trickle got here before the offer?), caching candidate");
        if (!config.candidates) config.candidates = [];
        config.candidates.push(candidate);
        Janus.debug(config.candidates);
      }
    } else if (json["janus"] === "webrtcup") {
      // The PeerConnection with the server is up! Notify this
      Janus.debug("Got a webrtcup event on session " + sessionId);
      Janus.debug(json);
      var sender = json["sender"];

      if (!sender) {
        Janus.warn("Missing sender...");
        return;
      }

      var pluginHandle = pluginHandles[sender];

      if (!pluginHandle) {
        Janus.debug("This handle is not attached to this session");
        return;
      }

      pluginHandle.webrtcState(true);
      return;
    } else if (json["janus"] === "hangup") {
      // A plugin asked the core to hangup a PeerConnection on one of our handles
      Janus.debug("Got a hangup event on session " + sessionId);
      Janus.debug(json);
      var sender = json["sender"];

      if (!sender) {
        Janus.warn("Missing sender...");
        return;
      }

      var pluginHandle = pluginHandles[sender];

      if (!pluginHandle) {
        Janus.debug("This handle is not attached to this session");
        return;
      }

      pluginHandle.webrtcState(false, json["reason"]);
      pluginHandle.hangup();
    } else if (json["janus"] === "detached") {
      // A plugin asked the core to detach one of our handles
      Janus.debug("Got a detached event on session " + sessionId);
      Janus.debug(json);
      var sender = json["sender"];

      if (!sender) {
        Janus.warn("Missing sender...");
        return;
      }

      var pluginHandle = pluginHandles[sender];

      if (!pluginHandle) {
        // Don't warn here because destroyHandle causes this situation.
        return;
      }

      pluginHandle.detached = true;
      pluginHandle.ondetached();
      pluginHandle.detach();
    } else if (json["janus"] === "media") {
      // Media started/stopped flowing
      Janus.debug("Got a media event on session " + sessionId);
      Janus.debug(json);
      var sender = json["sender"];

      if (!sender) {
        Janus.warn("Missing sender...");
        return;
      }

      var pluginHandle = pluginHandles[sender];

      if (!pluginHandle) {
        Janus.debug("This handle is not attached to this session");
        return;
      }

      pluginHandle.mediaState(json["type"], json["receiving"]);
    } else if (json["janus"] === "slowlink") {
      Janus.debug("Got a slowlink event on session " + sessionId);
      Janus.debug(json); // Trouble uplink or downlink

      var sender = json["sender"];

      if (!sender) {
        Janus.warn("Missing sender...");
        return;
      }

      var pluginHandle = pluginHandles[sender];

      if (!pluginHandle) {
        Janus.debug("This handle is not attached to this session");
        return;
      }

      pluginHandle.slowLink(json["uplink"], json["lost"]);
    } else if (json["janus"] === "error") {
      // Oops, something wrong happened
      Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME

      Janus.debug(json);
      var transaction = json["transaction"];

      if (transaction) {
        var reportSuccess = transactions[transaction];

        if (reportSuccess) {
          reportSuccess(json);
        }

        delete transactions[transaction];
      }

      return;
    } else if (json["janus"] === "event") {
      Janus.debug("Got a plugin event on session " + sessionId);
      Janus.debug(json);
      var sender = json["sender"];

      if (!sender) {
        Janus.warn("Missing sender...");
        return;
      }

      var plugindata = json["plugindata"];

      if (!plugindata) {
        Janus.warn("Missing plugindata...");
        return;
      }

      Janus.debug("  -- Event is coming from " + sender + " (" + plugindata["plugin"] + ")");
      var data = plugindata["data"];
      Janus.debug(data);
      var pluginHandle = pluginHandles[sender];

      if (!pluginHandle) {
        Janus.warn("This handle is not attached to this session");
        return;
      }

      var jsep = json["jsep"];

      if (jsep) {
        Janus.debug("Handling SDP as well...");
        Janus.debug(jsep);
      }

      var callback = pluginHandle.onmessage;

      if (callback) {
        Janus.debug("Notifying application..."); // Send to callback specified when attaching plugin handle

        callback(data, jsep);
      } else {
        // Send to generic callback (?)
        Janus.debug("No provided notification callback");
      }
    } else if (json["janus"] === "timeout") {
      Janus.error("Timeout on session " + sessionId);
      Janus.debug(json);

      if (websockets) {
        ws.close(3504, "Gateway timeout");
      }

      return;
    } else {
      Janus.warn("Unknown message/event  '" + json["janus"] + "' on session " + sessionId);
      Janus.debug(json);
    }
  } // Private helper to send keep-alive messages on WebSockets


  function keepAlive() {
    if (!server || !websockets || !connected) return;
    wsKeepaliveTimeoutId = setTimeout(keepAlive, keepAlivePeriod);
    var request = {
      "janus": "keepalive",
      "session_id": sessionId,
      "transaction": Janus.randomString(12)
    };
    if (token) request["token"] = token;
    if (apisecret) request["apisecret"] = apisecret;
    ws.send(JSON.stringify(request));
  } // Private method to create a session


  function createSession(callbacks) {
    var transaction = Janus.randomString(12);
    var request = {
      "janus": "create",
      "transaction": transaction
    };

    if (callbacks["reconnect"]) {
      // We're reconnecting, claim the session
      connected = false;
      request["janus"] = "claim";
      request["session_id"] = sessionId; // If we were using websockets, ignore the old connection

      if (ws) {
        ws.onopen = null;
        ws.onerror = null;
        ws.onclose = null;

        if (wsKeepaliveTimeoutId) {
          clearTimeout(wsKeepaliveTimeoutId);
          wsKeepaliveTimeoutId = null;
        }
      }
    }

    if (token) request["token"] = token;
    if (apisecret) request["apisecret"] = apisecret;

    if (!server && Janus.isArray(servers)) {
      // We still need to find a working server from the list we were given
      server = servers[serversIndex];

      if (server.indexOf("ws") === 0) {
        websockets = true;
        Janus.log("Server #" + (serversIndex + 1) + ": trying WebSockets to contact Janus (" + server + ")");
      } else {
        websockets = false;
        Janus.log("Server #" + (serversIndex + 1) + ": trying REST API to contact Janus (" + server + ")");
      }
    }

    if (websockets) {
      ws = Janus.newWebSocket(server, 'janus-protocol');
      wsHandlers = {
        'error': function error() {
          Janus.error("Error connecting to the Janus WebSockets server... " + server);

          if (Janus.isArray(servers) && !callbacks["reconnect"]) {
            serversIndex++;

            if (serversIndex === servers.length) {
              // We tried all the servers the user gave us and they all failed
              callbacks.error("Error connecting to any of the provided Janus servers: Is the server down?");
              return;
            } // Let's try the next server


            server = null;
            setTimeout(function () {
              createSession(callbacks);
            }, 200);
            return;
          }

          callbacks.error("Error connecting to the Janus WebSockets server: Is the server down?");
        },
        'open': function open() {
          // We need to be notified about the success
          transactions[transaction] = function (json) {
            Janus.debug(json);

            if (json["janus"] !== "success") {
              Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME

              callbacks.error(json["error"].reason);
              return;
            }

            wsKeepaliveTimeoutId = setTimeout(keepAlive, keepAlivePeriod);
            connected = true;
            sessionId = json["session_id"] ? json["session_id"] : json.data["id"];

            if (callbacks["reconnect"]) {
              Janus.log("Claimed session: " + sessionId);
            } else {
              Janus.log("Created session: " + sessionId);
            }

            Janus.sessions[sessionId] = that;
            callbacks.success();
          };

          ws.send(JSON.stringify(request));
        },
        'message': function message(event) {
          handleEvent(JSON.parse(event.data));
        },
        'close': function close() {
          if (!server || !connected) {
            return;
          }

          connected = false; // FIXME What if this is called when the page is closed?

          gatewayCallbacks.error("Lost connection to the server (is it down?)");
        }
      };

      for (var eventName in wsHandlers) {
        ws.addEventListener(eventName, wsHandlers[eventName]);
      }

      return;
    }

    Janus.httpAPICall(server, {
      verb: 'POST',
      withCredentials: withCredentials,
      body: request,
      success: function success(json) {
        Janus.debug(json);

        if (json["janus"] !== "success") {
          Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME

          callbacks.error(json["error"].reason);
          return;
        }

        connected = true;
        sessionId = json["session_id"] ? json["session_id"] : json.data["id"];

        if (callbacks["reconnect"]) {
          Janus.log("Claimed session: " + sessionId);
        } else {
          Janus.log("Created session: " + sessionId);
        }

        Janus.sessions[sessionId] = that;
        eventHandler();
        callbacks.success();
      },
      error: function error(textStatus, errorThrown) {
        Janus.error(textStatus + ":", errorThrown); // FIXME

        if (Janus.isArray(servers) && !callbacks["reconnect"]) {
          serversIndex++;

          if (serversIndex === servers.length) {
            // We tried all the servers the user gave us and they all failed
            callbacks.error("Error connecting to any of the provided Janus servers: Is the server down?");
            return;
          } // Let's try the next server


          server = null;
          setTimeout(function () {
            createSession(callbacks);
          }, 200);
          return;
        }

        if (errorThrown === "") callbacks.error(textStatus + ": Is the server down?");else callbacks.error(textStatus + ": " + errorThrown);
      }
    });
  } // Private method to destroy a session


  function destroySession(callbacks) {
    callbacks = callbacks || {}; // FIXME This method triggers a success even when we fail

    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    var unload = callbacks.unload === true;
    var notifyDestroyed = true;
    if (callbacks.notifyDestroyed !== undefined && callbacks.notifyDestroyed !== null) notifyDestroyed = callbacks.notifyDestroyed === true;
    var cleanupHandles = callbacks.cleanupHandles === true;
    Janus.log("Destroying session " + sessionId + " (unload=" + unload + ")");

    if (!sessionId) {
      Janus.warn("No session to destroy");
      callbacks.success();
      if (notifyDestroyed) gatewayCallbacks.destroyed();
      return;
    }

    if (cleanupHandles) {
      for (var handleId in pluginHandles) {
        destroyHandle(handleId, {
          noRequest: true
        });
      }
    }

    if (!connected) {
      Janus.warn("Is the server down? (connected=false)");
      sessionId = null;
      callbacks.success();
      return;
    } // No need to destroy all handles first, Janus will do that itself


    var request = {
      "janus": "destroy",
      "transaction": Janus.randomString(12)
    };
    if (token) request["token"] = token;
    if (apisecret) request["apisecret"] = apisecret;

    if (unload) {
      // We're unloading the page: use sendBeacon for HTTP instead,
      // or just close the WebSocket connection if we're using that
      if (websockets) {
        ws.onclose = null;
        ws.close();
        ws = null;
      } else {
        navigator.sendBeacon(server + "/" + sessionId, JSON.stringify(request));
      }

      Janus.log("Destroyed session:");
      sessionId = null;
      connected = false;
      callbacks.success();
      if (notifyDestroyed) gatewayCallbacks.destroyed();
      return;
    }

    if (websockets) {
      request["session_id"] = sessionId;

      var unbindWebSocket = function unbindWebSocket() {
        for (var eventName in wsHandlers) {
          ws.removeEventListener(eventName, wsHandlers[eventName]);
        }

        ws.removeEventListener('message', onUnbindMessage);
        ws.removeEventListener('error', onUnbindError);

        if (wsKeepaliveTimeoutId) {
          clearTimeout(wsKeepaliveTimeoutId);
        }

        ws.close();
      };

      var onUnbindMessage = function onUnbindMessage(event) {
        var data = JSON.parse(event.data);

        if (data.session_id == request.session_id && data.transaction == request.transaction) {
          unbindWebSocket();
          callbacks.success();
          if (notifyDestroyed) gatewayCallbacks.destroyed();
        }
      };

      var onUnbindError = function onUnbindError(event) {
        unbindWebSocket();
        callbacks.error("Failed to destroy the server: Is the server down?");
        if (notifyDestroyed) gatewayCallbacks.destroyed();
      };

      ws.addEventListener('message', onUnbindMessage);
      ws.addEventListener('error', onUnbindError);
      ws.send(JSON.stringify(request));
      return;
    }

    Janus.httpAPICall(server + "/" + sessionId, {
      verb: 'POST',
      withCredentials: withCredentials,
      body: request,
      success: function success(json) {
        Janus.log("Destroyed session:");
        Janus.debug(json);
        sessionId = null;
        connected = false;

        if (json["janus"] !== "success") {
          Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME
        }

        callbacks.success();
        if (notifyDestroyed) gatewayCallbacks.destroyed();
      },
      error: function error(textStatus, errorThrown) {
        Janus.error(textStatus + ":", errorThrown); // FIXME
        // Reset everything anyway

        sessionId = null;
        connected = false;
        callbacks.success();
        if (notifyDestroyed) gatewayCallbacks.destroyed();
      }
    });
  } // Private method to create a plugin handle


  function createHandle(callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    callbacks.consentDialog = typeof callbacks.consentDialog == "function" ? callbacks.consentDialog : Janus.noop;
    callbacks.iceState = typeof callbacks.iceState == "function" ? callbacks.iceState : Janus.noop;
    callbacks.mediaState = typeof callbacks.mediaState == "function" ? callbacks.mediaState : Janus.noop;
    callbacks.webrtcState = typeof callbacks.webrtcState == "function" ? callbacks.webrtcState : Janus.noop;
    callbacks.slowLink = typeof callbacks.slowLink == "function" ? callbacks.slowLink : Janus.noop;
    callbacks.onmessage = typeof callbacks.onmessage == "function" ? callbacks.onmessage : Janus.noop;
    callbacks.onlocalstream = typeof callbacks.onlocalstream == "function" ? callbacks.onlocalstream : Janus.noop;
    callbacks.onremotestream = typeof callbacks.onremotestream == "function" ? callbacks.onremotestream : Janus.noop;
    callbacks.ondata = typeof callbacks.ondata == "function" ? callbacks.ondata : Janus.noop;
    callbacks.ondataopen = typeof callbacks.ondataopen == "function" ? callbacks.ondataopen : Janus.noop;
    callbacks.oncleanup = typeof callbacks.oncleanup == "function" ? callbacks.oncleanup : Janus.noop;
    callbacks.ondetached = typeof callbacks.ondetached == "function" ? callbacks.ondetached : Janus.noop;

    if (!connected) {
      Janus.warn("Is the server down? (connected=false)");
      callbacks.error("Is the server down? (connected=false)");
      return;
    }

    var plugin = callbacks.plugin;

    if (!plugin) {
      Janus.error("Invalid plugin");
      callbacks.error("Invalid plugin");
      return;
    }

    var opaqueId = callbacks.opaqueId;
    var handleToken = callbacks.token ? callbacks.token : token;
    var transaction = Janus.randomString(12);
    var request = {
      "janus": "attach",
      "plugin": plugin,
      "opaque_id": opaqueId,
      "transaction": transaction
    };
    if (handleToken) request["token"] = handleToken;
    if (apisecret) request["apisecret"] = apisecret;

    if (websockets) {
      transactions[transaction] = function (json) {
        Janus.debug(json);

        if (json["janus"] !== "success") {
          Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME

          callbacks.error("Ooops: " + json["error"].code + " " + json["error"].reason);
          return;
        }

        var handleId = json.data["id"];
        Janus.log("Created handle: " + handleId);
        var pluginHandle = {
          session: that,
          plugin: plugin,
          id: handleId,
          token: handleToken,
          detached: false,
          webrtcStuff: {
            started: false,
            myStream: null,
            streamExternal: false,
            remoteStream: null,
            mySdp: null,
            mediaConstraints: null,
            pc: null,
            dataChannel: {},
            dtmfSender: null,
            trickle: true,
            iceDone: false,
            volume: {
              value: null,
              timer: null
            },
            bitrate: {
              value: null,
              bsnow: null,
              bsbefore: null,
              tsnow: null,
              tsbefore: null,
              timer: null
            }
          },
          getId: function getId() {
            return handleId;
          },
          getPlugin: function getPlugin() {
            return plugin;
          },
          getVolume: function getVolume() {
            return _getVolume(handleId, true);
          },
          getRemoteVolume: function getRemoteVolume() {
            return _getVolume(handleId, true);
          },
          getLocalVolume: function getLocalVolume() {
            return _getVolume(handleId, false);
          },
          isAudioMuted: function isAudioMuted() {
            return isMuted(handleId, false);
          },
          muteAudio: function muteAudio() {
            return mute(handleId, false, true);
          },
          unmuteAudio: function unmuteAudio() {
            return mute(handleId, false, false);
          },
          isVideoMuted: function isVideoMuted() {
            return isMuted(handleId, true);
          },
          muteVideo: function muteVideo() {
            return mute(handleId, true, true);
          },
          unmuteVideo: function unmuteVideo() {
            return mute(handleId, true, false);
          },
          getBitrate: function getBitrate() {
            return _getBitrate(handleId);
          },
          send: function send(callbacks) {
            sendMessage(handleId, callbacks);
          },
          data: function data(callbacks) {
            sendData(handleId, callbacks);
          },
          dtmf: function dtmf(callbacks) {
            sendDtmf(handleId, callbacks);
          },
          consentDialog: callbacks.consentDialog,
          iceState: callbacks.iceState,
          mediaState: callbacks.mediaState,
          webrtcState: callbacks.webrtcState,
          slowLink: callbacks.slowLink,
          onmessage: callbacks.onmessage,
          createOffer: function createOffer(callbacks) {
            prepareWebrtc(handleId, true, callbacks);
          },
          createAnswer: function createAnswer(callbacks) {
            prepareWebrtc(handleId, false, callbacks);
          },
          handleRemoteJsep: function handleRemoteJsep(callbacks) {
            prepareWebrtcPeer(handleId, callbacks);
          },
          onlocalstream: callbacks.onlocalstream,
          onremotestream: callbacks.onremotestream,
          ondata: callbacks.ondata,
          ondataopen: callbacks.ondataopen,
          oncleanup: callbacks.oncleanup,
          ondetached: callbacks.ondetached,
          hangup: function hangup(sendRequest) {
            cleanupWebrtc(handleId, sendRequest === true);
          },
          detach: function detach(callbacks) {
            destroyHandle(handleId, callbacks);
          }
        };
        pluginHandles[handleId] = pluginHandle;
        callbacks.success(pluginHandle);
      };

      request["session_id"] = sessionId;
      ws.send(JSON.stringify(request));
      return;
    }

    Janus.httpAPICall(server + "/" + sessionId, {
      verb: 'POST',
      withCredentials: withCredentials,
      body: request,
      success: function success(json) {
        Janus.debug(json);

        if (json["janus"] !== "success") {
          Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME

          callbacks.error("Ooops: " + json["error"].code + " " + json["error"].reason);
          return;
        }

        var handleId = json.data["id"];
        Janus.log("Created handle: " + handleId);
        var pluginHandle = {
          session: that,
          plugin: plugin,
          id: handleId,
          token: handleToken,
          detached: false,
          webrtcStuff: {
            started: false,
            myStream: null,
            streamExternal: false,
            remoteStream: null,
            mySdp: null,
            mediaConstraints: null,
            pc: null,
            dataChannel: {},
            dtmfSender: null,
            trickle: true,
            iceDone: false,
            volume: {
              value: null,
              timer: null
            },
            bitrate: {
              value: null,
              bsnow: null,
              bsbefore: null,
              tsnow: null,
              tsbefore: null,
              timer: null
            }
          },
          getId: function getId() {
            return handleId;
          },
          getPlugin: function getPlugin() {
            return plugin;
          },
          getVolume: function getVolume() {
            return _getVolume(handleId, true);
          },
          getRemoteVolume: function getRemoteVolume() {
            return _getVolume(handleId, true);
          },
          getLocalVolume: function getLocalVolume() {
            return _getVolume(handleId, false);
          },
          isAudioMuted: function isAudioMuted() {
            return isMuted(handleId, false);
          },
          muteAudio: function muteAudio() {
            return mute(handleId, false, true);
          },
          unmuteAudio: function unmuteAudio() {
            return mute(handleId, false, false);
          },
          isVideoMuted: function isVideoMuted() {
            return isMuted(handleId, true);
          },
          muteVideo: function muteVideo() {
            return mute(handleId, true, true);
          },
          unmuteVideo: function unmuteVideo() {
            return mute(handleId, true, false);
          },
          getBitrate: function getBitrate() {
            return _getBitrate(handleId);
          },
          send: function send(callbacks) {
            sendMessage(handleId, callbacks);
          },
          data: function data(callbacks) {
            sendData(handleId, callbacks);
          },
          dtmf: function dtmf(callbacks) {
            sendDtmf(handleId, callbacks);
          },
          consentDialog: callbacks.consentDialog,
          iceState: callbacks.iceState,
          mediaState: callbacks.mediaState,
          webrtcState: callbacks.webrtcState,
          slowLink: callbacks.slowLink,
          onmessage: callbacks.onmessage,
          createOffer: function createOffer(callbacks) {
            prepareWebrtc(handleId, true, callbacks);
          },
          createAnswer: function createAnswer(callbacks) {
            prepareWebrtc(handleId, false, callbacks);
          },
          handleRemoteJsep: function handleRemoteJsep(callbacks) {
            prepareWebrtcPeer(handleId, callbacks);
          },
          onlocalstream: callbacks.onlocalstream,
          onremotestream: callbacks.onremotestream,
          ondata: callbacks.ondata,
          ondataopen: callbacks.ondataopen,
          oncleanup: callbacks.oncleanup,
          ondetached: callbacks.ondetached,
          hangup: function hangup(sendRequest) {
            cleanupWebrtc(handleId, sendRequest === true);
          },
          detach: function detach(callbacks) {
            destroyHandle(handleId, callbacks);
          }
        };
        pluginHandles[handleId] = pluginHandle;
        callbacks.success(pluginHandle);
      },
      error: function error(textStatus, errorThrown) {
        Janus.error(textStatus + ":", errorThrown); // FIXME

        if (errorThrown === "") callbacks.error(textStatus + ": Is the server down?");else callbacks.error(textStatus + ": " + errorThrown);
      }
    });
  } // Private method to send a message


  function sendMessage(handleId, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;

    if (!connected) {
      Janus.warn("Is the server down? (connected=false)");
      callbacks.error("Is the server down? (connected=false)");
      return;
    }

    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      callbacks.error("Invalid handle");
      return;
    }

    var message = callbacks.message;
    var jsep = callbacks.jsep;
    var transaction = Janus.randomString(12);
    var request = {
      "janus": "message",
      "body": message,
      "transaction": transaction
    };
    if (pluginHandle.token) request["token"] = pluginHandle.token;
    if (apisecret) request["apisecret"] = apisecret;
    if (jsep) request.jsep = jsep;
    Janus.debug("Sending message to plugin (handle=" + handleId + "):");
    Janus.debug(request);

    if (websockets) {
      request["session_id"] = sessionId;
      request["handle_id"] = handleId;

      transactions[transaction] = function (json) {
        Janus.debug("Message sent!");
        Janus.debug(json);

        if (json["janus"] === "success") {
          // We got a success, must have been a synchronous transaction
          var plugindata = json["plugindata"];

          if (!plugindata) {
            Janus.warn("Request succeeded, but missing plugindata...");
            callbacks.success();
            return;
          }

          Janus.log("Synchronous transaction successful (" + plugindata["plugin"] + ")");
          var data = plugindata["data"];
          Janus.debug(data);
          callbacks.success(data);
          return;
        } else if (json["janus"] !== "ack") {
          // Not a success and not an ack, must be an error
          if (json["error"]) {
            Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME

            callbacks.error(json["error"].code + " " + json["error"].reason);
          } else {
            Janus.error("Unknown error"); // FIXME

            callbacks.error("Unknown error");
          }

          return;
        } // If we got here, the plugin decided to handle the request asynchronously


        callbacks.success();
      };

      ws.send(JSON.stringify(request));
      return;
    }

    Janus.httpAPICall(server + "/" + sessionId + "/" + handleId, {
      verb: 'POST',
      withCredentials: withCredentials,
      body: request,
      success: function success(json) {
        Janus.debug("Message sent!");
        Janus.debug(json);

        if (json["janus"] === "success") {
          // We got a success, must have been a synchronous transaction
          var plugindata = json["plugindata"];

          if (!plugindata) {
            Janus.warn("Request succeeded, but missing plugindata...");
            callbacks.success();
            return;
          }

          Janus.log("Synchronous transaction successful (" + plugindata["plugin"] + ")");
          var data = plugindata["data"];
          Janus.debug(data);
          callbacks.success(data);
          return;
        } else if (json["janus"] !== "ack") {
          // Not a success and not an ack, must be an error
          if (json["error"]) {
            Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME

            callbacks.error(json["error"].code + " " + json["error"].reason);
          } else {
            Janus.error("Unknown error"); // FIXME

            callbacks.error("Unknown error");
          }

          return;
        } // If we got here, the plugin decided to handle the request asynchronously


        callbacks.success();
      },
      error: function error(textStatus, errorThrown) {
        Janus.error(textStatus + ":", errorThrown); // FIXME

        callbacks.error(textStatus + ": " + errorThrown);
      }
    });
  } // Private method to send a trickle candidate


  function sendTrickleCandidate(handleId, candidate) {
    if (!connected) {
      Janus.warn("Is the server down? (connected=false)");
      return;
    }

    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      return;
    }

    var request = {
      "janus": "trickle",
      "candidate": candidate,
      "transaction": Janus.randomString(12)
    };
    if (pluginHandle.token) request["token"] = pluginHandle.token;
    if (apisecret) request["apisecret"] = apisecret;
    Janus.vdebug("Sending trickle candidate (handle=" + handleId + "):");
    Janus.vdebug(request);

    if (websockets) {
      request["session_id"] = sessionId;
      request["handle_id"] = handleId;
      ws.send(JSON.stringify(request));
      return;
    }

    Janus.httpAPICall(server + "/" + sessionId + "/" + handleId, {
      verb: 'POST',
      withCredentials: withCredentials,
      body: request,
      success: function success(json) {
        Janus.vdebug("Candidate sent!");
        Janus.vdebug(json);

        if (json["janus"] !== "ack") {
          Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME

          return;
        }
      },
      error: function error(textStatus, errorThrown) {
        Janus.error(textStatus + ":", errorThrown); // FIXME
      }
    });
  } // Private method to create a data channel


  function createDataChannel(handleId, dclabel, incoming, pendingData) {
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      return;
    }

    var config = pluginHandle.webrtcStuff;

    var onDataChannelMessage = function onDataChannelMessage(event) {
      Janus.log('Received message on data channel:', event);
      var label = event.target.label;
      pluginHandle.ondata(event.data, label);
    };

    var onDataChannelStateChange = function onDataChannelStateChange(event) {
      Janus.log('Received state change on data channel:', event);
      var label = event.target.label;
      var dcState = config.dataChannel[label] ? config.dataChannel[label].readyState : "null";
      Janus.log('State change on <' + label + '> data channel: ' + dcState);

      if (dcState === 'open') {
        // Any pending messages to send?
        if (config.dataChannel[label].pending && config.dataChannel[label].pending.length > 0) {
          Janus.log("Sending pending messages on <" + label + ">:", config.dataChannel[label].pending.length);

          var _iterator4 = _createForOfIteratorHelper(config.dataChannel[label].pending),
              _step4;

          try {
            for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
              var data = _step4.value;
              Janus.log("Sending data on data channel <" + label + ">");
              Janus.debug(data);
              config.dataChannel[label].send(data);
            }
          } catch (err) {
            _iterator4.e(err);
          } finally {
            _iterator4.f();
          }

          config.dataChannel[label].pending = [];
        } // Notify the open data channel


        pluginHandle.ondataopen(label);
      }
    };

    var onDataChannelError = function onDataChannelError(error) {
      Janus.error('Got error on data channel:', error); // TODO
    };

    if (!incoming) {
      // FIXME Add options (ordered, maxRetransmits, etc.)
      config.dataChannel[dclabel] = config.pc.createDataChannel(dclabel, {
        ordered: true
      });
    } else {
      // The channel was created by Janus
      config.dataChannel[dclabel] = incoming;
    }

    config.dataChannel[dclabel].onmessage = onDataChannelMessage;
    config.dataChannel[dclabel].onopen = onDataChannelStateChange;
    config.dataChannel[dclabel].onclose = onDataChannelStateChange;
    config.dataChannel[dclabel].onerror = onDataChannelError;
    config.dataChannel[dclabel].pending = [];
    if (pendingData) config.dataChannel[dclabel].pending.push(pendingData);
  } // Private method to send a data channel message


  function sendData(handleId, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      callbacks.error("Invalid handle");
      return;
    }

    var config = pluginHandle.webrtcStuff;
    var data = callbacks.text || callbacks.data;

    if (!data) {
      Janus.warn("Invalid data");
      callbacks.error("Invalid data");
      return;
    }

    var label = callbacks.label ? callbacks.label : Janus.dataChanDefaultLabel;

    if (!config.dataChannel[label]) {
      // Create new data channel and wait for it to open
      createDataChannel(handleId, label, false, data);
      callbacks.success();
      return;
    }

    if (config.dataChannel[label].readyState !== "open") {
      config.dataChannel[label].pending.push(data);
      callbacks.success();
      return;
    }

    Janus.log("Sending data on data channel <" + label + ">");
    Janus.debug(data);
    config.dataChannel[label].send(data);
    callbacks.success();
  } // Private method to send a DTMF tone


  function sendDtmf(handleId, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      callbacks.error("Invalid handle");
      return;
    }

    var config = pluginHandle.webrtcStuff;

    if (!config.dtmfSender) {
      // Create the DTMF sender the proper way, if possible
      if (config.pc) {
        var senders = config.pc.getSenders();
        var audioSender = senders.find(function (sender) {
          return sender.track && sender.track.kind === 'audio';
        });

        if (!audioSender) {
          Janus.warn("Invalid DTMF configuration (no audio track)");
          callbacks.error("Invalid DTMF configuration (no audio track)");
          return;
        }

        config.dtmfSender = audioSender.dtmf;

        if (config.dtmfSender) {
          Janus.log("Created DTMF Sender");

          config.dtmfSender.ontonechange = function (tone) {
            Janus.debug("Sent DTMF tone: " + tone.tone);
          };
        }
      }

      if (!config.dtmfSender) {
        Janus.warn("Invalid DTMF configuration");
        callbacks.error("Invalid DTMF configuration");
        return;
      }
    }

    var dtmf = callbacks.dtmf;

    if (!dtmf) {
      Janus.warn("Invalid DTMF parameters");
      callbacks.error("Invalid DTMF parameters");
      return;
    }

    var tones = dtmf.tones;

    if (!tones) {
      Janus.warn("Invalid DTMF string");
      callbacks.error("Invalid DTMF string");
      return;
    }

    var duration = typeof dtmf.duration === 'number' ? dtmf.duration : 500; // We choose 500ms as the default duration for a tone

    var gap = typeof dtmf.gap === 'number' ? dtmf.gap : 50; // We choose 50ms as the default gap between tones

    Janus.debug("Sending DTMF string " + tones + " (duration " + duration + "ms, gap " + gap + "ms)");
    config.dtmfSender.insertDTMF(tones, duration, gap);
    callbacks.success();
  } // Private method to destroy a plugin handle


  function destroyHandle(handleId, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    var noRequest = callbacks.noRequest === true;
    Janus.log("Destroying handle " + handleId + " (only-locally=" + noRequest + ")");
    cleanupWebrtc(handleId);
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || pluginHandle.detached) {
      // Plugin was already detached by Janus, calling detach again will return a handle not found error, so just exit here
      delete pluginHandles[handleId];
      callbacks.success();
      return;
    }

    if (noRequest) {
      // We're only removing the handle locally
      delete pluginHandles[handleId];
      callbacks.success();
      return;
    }

    if (!connected) {
      Janus.warn("Is the server down? (connected=false)");
      callbacks.error("Is the server down? (connected=false)");
      return;
    }

    var request = {
      "janus": "detach",
      "transaction": Janus.randomString(12)
    };
    if (pluginHandle.token) request["token"] = pluginHandle.token;
    if (apisecret) request["apisecret"] = apisecret;

    if (websockets) {
      request["session_id"] = sessionId;
      request["handle_id"] = handleId;
      ws.send(JSON.stringify(request));
      delete pluginHandles[handleId];
      callbacks.success();
      return;
    }

    Janus.httpAPICall(server + "/" + sessionId + "/" + handleId, {
      verb: 'POST',
      withCredentials: withCredentials,
      body: request,
      success: function success(json) {
        Janus.log("Destroyed handle:");
        Janus.debug(json);

        if (json["janus"] !== "success") {
          Janus.error("Ooops: " + json["error"].code + " " + json["error"].reason); // FIXME
        }

        delete pluginHandles[handleId];
        callbacks.success();
      },
      error: function error(textStatus, errorThrown) {
        Janus.error(textStatus + ":", errorThrown); // FIXME
        // We cleanup anyway

        delete pluginHandles[handleId];
        callbacks.success();
      }
    });
  } // WebRTC stuff


  function streamsDone(handleId, jsep, media, callbacks, stream) {
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      callbacks.error("Invalid handle");
      return;
    }

    var config = pluginHandle.webrtcStuff;
    Janus.debug("streamsDone:", stream);

    if (stream) {
      Janus.debug("  -- Audio tracks:", stream.getAudioTracks());
      Janus.debug("  -- Video tracks:", stream.getVideoTracks());
    } // We're now capturing the new stream: check if we're updating or if it's a new thing


    var addTracks = false;

    if (!config.myStream || !media.update || config.streamExternal) {
      config.myStream = stream;
      addTracks = true;
    } else {
      // We only need to update the existing stream
      if ((!media.update && isAudioSendEnabled(media) || media.update && (media.addAudio || media.replaceAudio)) && stream.getAudioTracks() && stream.getAudioTracks().length) {
        config.myStream.addTrack(stream.getAudioTracks()[0]);

        if (Janus.unifiedPlan) {
          // Use Transceivers
          Janus.log((media.replaceAudio ? "Replacing" : "Adding") + " audio track:", stream.getAudioTracks()[0]);
          var audioTransceiver = null;
          var transceivers = config.pc.getTransceivers();

          if (transceivers && transceivers.length > 0) {
            var _iterator5 = _createForOfIteratorHelper(transceivers),
                _step5;

            try {
              for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
                var t = _step5.value;

                if (t.sender && t.sender.track && t.sender.track.kind === "audio" || t.receiver && t.receiver.track && t.receiver.track.kind === "audio") {
                  audioTransceiver = t;
                  break;
                }
              }
            } catch (err) {
              _iterator5.e(err);
            } finally {
              _iterator5.f();
            }
          }

          if (audioTransceiver && audioTransceiver.sender) {
            audioTransceiver.sender.replaceTrack(stream.getAudioTracks()[0]);
          } else {
            config.pc.addTrack(stream.getAudioTracks()[0], stream);
          }
        } else {
          Janus.log((media.replaceAudio ? "Replacing" : "Adding") + " audio track:", stream.getAudioTracks()[0]);
          config.pc.addTrack(stream.getAudioTracks()[0], stream);
        }
      }

      if ((!media.update && isVideoSendEnabled(media) || media.update && (media.addVideo || media.replaceVideo)) && stream.getVideoTracks() && stream.getVideoTracks().length) {
        config.myStream.addTrack(stream.getVideoTracks()[0]);

        if (Janus.unifiedPlan) {
          // Use Transceivers
          Janus.log((media.replaceVideo ? "Replacing" : "Adding") + " video track:", stream.getVideoTracks()[0]);
          var videoTransceiver = null;
          var transceivers = config.pc.getTransceivers();

          if (transceivers && transceivers.length > 0) {
            var _iterator6 = _createForOfIteratorHelper(transceivers),
                _step6;

            try {
              for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
                var t = _step6.value;

                if (t.sender && t.sender.track && t.sender.track.kind === "video" || t.receiver && t.receiver.track && t.receiver.track.kind === "video") {
                  videoTransceiver = t;
                  break;
                }
              }
            } catch (err) {
              _iterator6.e(err);
            } finally {
              _iterator6.f();
            }
          }

          if (videoTransceiver && videoTransceiver.sender) {
            videoTransceiver.sender.replaceTrack(stream.getVideoTracks()[0]);
          } else {
            config.pc.addTrack(stream.getVideoTracks()[0], stream);
          }
        } else {
          Janus.log((media.replaceVideo ? "Replacing" : "Adding") + " video track:", stream.getVideoTracks()[0]);
          config.pc.addTrack(stream.getVideoTracks()[0], stream);
        }
      }
    } // If we still need to create a PeerConnection, let's do that


    if (!config.pc) {
      var pc_config = {
        "iceServers": iceServers,
        "iceTransportPolicy": iceTransportPolicy,
        "bundlePolicy": bundlePolicy
      };

      if (Janus.webRTCAdapter.browserDetails.browser === "chrome") {
        // For Chrome versions before 72, we force a plan-b semantic, and unified-plan otherwise
        pc_config["sdpSemantics"] = Janus.webRTCAdapter.browserDetails.version < 72 ? "plan-b" : "unified-plan";
      }

      var pc_constraints = {
        "optional": [{
          "DtlsSrtpKeyAgreement": true
        }]
      };

      if (ipv6Support) {
        pc_constraints.optional.push({
          "googIPv6": true
        });
      } // Any custom constraint to add?


      if (callbacks.rtcConstraints && _typeof(callbacks.rtcConstraints) === 'object') {
        Janus.debug("Adding custom PeerConnection constraints:", callbacks.rtcConstraints);

        for (var i in callbacks.rtcConstraints) {
          pc_constraints.optional.push(callbacks.rtcConstraints[i]);
        }
      }

      if (Janus.webRTCAdapter.browserDetails.browser === "edge") {
        // This is Edge, enable BUNDLE explicitly
        pc_config.bundlePolicy = "max-bundle";
      }

      Janus.log("Creating PeerConnection");
      Janus.debug(pc_constraints);
      config.pc = new RTCPeerConnection(pc_config, pc_constraints);
      Janus.debug(config.pc);

      if (config.pc.getStats) {
        // FIXME
        config.volume = {};
        config.bitrate.value = "0 kbits/sec";
      }

      Janus.log("Preparing local SDP and gathering candidates (trickle=" + config.trickle + ")");

      config.pc.oniceconnectionstatechange = function (e) {
        if (config.pc) pluginHandle.iceState(config.pc.iceConnectionState);
      };

      config.pc.onicecandidate = function (event) {
        if (!event.candidate || Janus.webRTCAdapter.browserDetails.browser === 'edge' && event.candidate.candidate.indexOf('endOfCandidates') > 0) {
          Janus.log("End of candidates.");
          config.iceDone = true;

          if (config.trickle === true) {
            // Notify end of candidates
            sendTrickleCandidate(handleId, {
              "completed": true
            });
          } else {
            // No trickle, time to send the complete SDP (including all candidates)
            sendSDP(handleId, callbacks);
          }
        } else {
          // JSON.stringify doesn't work on some WebRTC objects anymore
          // See https://code.google.com/p/chromium/issues/detail?id=467366
          var candidate = {
            "candidate": event.candidate.candidate,
            "sdpMid": event.candidate.sdpMid,
            "sdpMLineIndex": event.candidate.sdpMLineIndex
          };

          if (config.trickle === true) {
            // Send candidate
            sendTrickleCandidate(handleId, candidate);
          }
        }
      };

      config.pc.ontrack = function (event) {
        Janus.log("Handling Remote Track");
        Janus.debug(event);
        if (!event.streams) return;
        config.remoteStream = event.streams[0];
        pluginHandle.onremotestream(config.remoteStream);
        if (event.track.onended) return;
        Janus.log("Adding onended callback to track:", event.track);

        event.track.onended = function (ev) {
          Janus.log("Remote track muted/removed:", ev);

          if (config.remoteStream) {
            config.remoteStream.removeTrack(ev.target);
            pluginHandle.onremotestream(config.remoteStream);
          }
        };

        event.track.onmute = event.track.onended;

        event.track.onunmute = function (ev) {
          Janus.log("Remote track flowing again:", ev);

          try {
            config.remoteStream.addTrack(ev.target);
            pluginHandle.onremotestream(config.remoteStream);
          } catch (e) {
            Janus.error(e);
          }

          ;
        };
      };
    }

    if (addTracks && stream) {
      Janus.log('Adding local stream');
      var simulcast2 = callbacks.simulcast2 === true;
      stream.getTracks().forEach(function (track) {
        Janus.log('Adding local track:', track);

        if (!simulcast2) {
          config.pc.addTrack(track, stream);
        } else {
          if (track.kind === "audio") {
            config.pc.addTrack(track, stream);
          } else {
            Janus.log('Enabling rid-based simulcasting:', track);
            var maxBitrates = getMaxBitrates(callbacks.simulcastMaxBitrates);
            config.pc.addTransceiver(track, {
              direction: "sendrecv",
              streams: [stream],
              sendEncodings: [{
                rid: "h",
                active: true,
                maxBitrate: maxBitrates.high
              }, {
                rid: "m",
                active: true,
                maxBitrate: maxBitrates.medium,
                scaleResolutionDownBy: 2
              }, {
                rid: "l",
                active: true,
                maxBitrate: maxBitrates.low,
                scaleResolutionDownBy: 4
              }]
            });
          }
        }
      });
    } // Any data channel to create?


    if (isDataEnabled(media) && !config.dataChannel[Janus.dataChanDefaultLabel]) {
      Janus.log("Creating data channel");
      createDataChannel(handleId, Janus.dataChanDefaultLabel, false);

      config.pc.ondatachannel = function (event) {
        Janus.log("Data channel created by Janus:", event);
        createDataChannel(handleId, event.channel.label, event.channel);
      };
    } // If there's a new local stream, let's notify the application


    if (config.myStream) {
      pluginHandle.onlocalstream(config.myStream);
    } // Create offer/answer now


    if (!jsep) {
      createOffer(handleId, media, callbacks);
    } else {
      config.pc.setRemoteDescription(jsep).then(function () {
        Janus.log("Remote description accepted!");
        config.remoteSdp = jsep.sdp; // Any trickle candidate we cached?

        if (config.candidates && config.candidates.length > 0) {
          for (var i = 0; i < config.candidates.length; i++) {
            var candidate = config.candidates[i];
            Janus.debug("Adding remote candidate:", candidate);

            if (!candidate || candidate.completed === true) {
              // end-of-candidates
              config.pc.addIceCandidate(Janus.endOfCandidates);
            } else {
              // New candidate
              config.pc.addIceCandidate(candidate);
            }
          }

          config.candidates = [];
        } // Create the answer now


        createAnswer(handleId, media, callbacks);
      }, callbacks.error);
    }
  }

  function prepareWebrtc(handleId, offer, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : webrtcError;
    var jsep = callbacks.jsep;

    if (offer && jsep) {
      Janus.error("Provided a JSEP to a createOffer");
      callbacks.error("Provided a JSEP to a createOffer");
      return;
    } else if (!offer && (!jsep || !jsep.type || !jsep.sdp)) {
      Janus.error("A valid JSEP is required for createAnswer");
      callbacks.error("A valid JSEP is required for createAnswer");
      return;
    }
    /* Check that callbacks.media is a (not null) Object */


    callbacks.media = _typeof(callbacks.media) === 'object' && callbacks.media ? callbacks.media : {
      audio: true,
      video: true
    };
    var media = callbacks.media;
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      callbacks.error("Invalid handle");
      return;
    }

    var config = pluginHandle.webrtcStuff;
    config.trickle = isTrickleEnabled(callbacks.trickle); // Are we updating a session?

    if (!config.pc) {
      // Nope, new PeerConnection
      media.update = false;
      media.keepAudio = false;
      media.keepVideo = false;
    } else {
      Janus.log("Updating existing media session");
      media.update = true; // Check if there's anything to add/remove/replace, or if we
      // can go directly to preparing the new SDP offer or answer

      if (callbacks.stream) {
        // External stream: is this the same as the one we were using before?
        if (callbacks.stream !== config.myStream) {
          Janus.log("Renegotiation involves a new external stream");
        }
      } else {
        // Check if there are changes on audio
        if (media.addAudio) {
          media.keepAudio = false;
          media.replaceAudio = false;
          media.removeAudio = false;
          media.audioSend = true;

          if (config.myStream && config.myStream.getAudioTracks() && config.myStream.getAudioTracks().length) {
            Janus.error("Can't add audio stream, there already is one");
            callbacks.error("Can't add audio stream, there already is one");
            return;
          }
        } else if (media.removeAudio) {
          media.keepAudio = false;
          media.replaceAudio = false;
          media.addAudio = false;
          media.audioSend = false;
        } else if (media.replaceAudio) {
          media.keepAudio = false;
          media.addAudio = false;
          media.removeAudio = false;
          media.audioSend = true;
        }

        if (!config.myStream) {
          // No media stream: if we were asked to replace, it's actually an "add"
          if (media.replaceAudio) {
            media.keepAudio = false;
            media.replaceAudio = false;
            media.addAudio = true;
            media.audioSend = true;
          }

          if (isAudioSendEnabled(media)) {
            media.keepAudio = false;
            media.addAudio = true;
          }
        } else {
          if (!config.myStream.getAudioTracks() || config.myStream.getAudioTracks().length === 0) {
            // No audio track: if we were asked to replace, it's actually an "add"
            if (media.replaceAudio) {
              media.keepAudio = false;
              media.replaceAudio = false;
              media.addAudio = true;
              media.audioSend = true;
            }

            if (isAudioSendEnabled(media)) {
              media.keepAudio = false;
              media.addAudio = true;
            }
          } else {
            // We have an audio track: should we keep it as it is?
            if (isAudioSendEnabled(media) && !media.removeAudio && !media.replaceAudio) {
              media.keepAudio = true;
            }
          }
        } // Check if there are changes on video


        if (media.addVideo) {
          media.keepVideo = false;
          media.replaceVideo = false;
          media.removeVideo = false;
          media.videoSend = true;

          if (config.myStream && config.myStream.getVideoTracks() && config.myStream.getVideoTracks().length) {
            Janus.error("Can't add video stream, there already is one");
            callbacks.error("Can't add video stream, there already is one");
            return;
          }
        } else if (media.removeVideo) {
          media.keepVideo = false;
          media.replaceVideo = false;
          media.addVideo = false;
          media.videoSend = false;
        } else if (media.replaceVideo) {
          media.keepVideo = false;
          media.addVideo = false;
          media.removeVideo = false;
          media.videoSend = true;
        }

        if (!config.myStream) {
          // No media stream: if we were asked to replace, it's actually an "add"
          if (media.replaceVideo) {
            media.keepVideo = false;
            media.replaceVideo = false;
            media.addVideo = true;
            media.videoSend = true;
          }

          if (isVideoSendEnabled(media)) {
            media.keepVideo = false;
            media.addVideo = true;
          }
        } else {
          if (!config.myStream.getVideoTracks() || config.myStream.getVideoTracks().length === 0) {
            // No video track: if we were asked to replace, it's actually an "add"
            if (media.replaceVideo) {
              media.keepVideo = false;
              media.replaceVideo = false;
              media.addVideo = true;
              media.videoSend = true;
            }

            if (isVideoSendEnabled(media)) {
              media.keepVideo = false;
              media.addVideo = true;
            }
          } else {
            // We have a video track: should we keep it as it is?
            if (isVideoSendEnabled(media) && !media.removeVideo && !media.replaceVideo) {
              media.keepVideo = true;
            }
          }
        } // Data channels can only be added


        if (media.addData) {
          media.data = true;
        }
      } // If we're updating and keeping all tracks, let's skip the getUserMedia part


      if (isAudioSendEnabled(media) && media.keepAudio && isVideoSendEnabled(media) && media.keepVideo) {
        pluginHandle.consentDialog(false);
        streamsDone(handleId, jsep, media, callbacks, config.myStream);
        return;
      }
    } // If we're updating, check if we need to remove/replace one of the tracks


    if (media.update && !config.streamExternal) {
      if (media.removeAudio || media.replaceAudio) {
        if (config.myStream && config.myStream.getAudioTracks() && config.myStream.getAudioTracks().length) {
          var at = config.myStream.getAudioTracks()[0];
          Janus.log("Removing audio track:", at);
          config.myStream.removeTrack(at);

          try {
            at.stop();
          } catch (e) {}
        }

        if (config.pc.getSenders() && config.pc.getSenders().length) {
          var ra = true;

          if (media.replaceAudio && Janus.unifiedPlan) {
            // We can use replaceTrack
            ra = false;
          }

          if (ra) {
            var _iterator7 = _createForOfIteratorHelper(config.pc.getSenders()),
                _step7;

            try {
              for (_iterator7.s(); !(_step7 = _iterator7.n()).done;) {
                var asnd = _step7.value;

                if (asnd && asnd.track && asnd.track.kind === "audio") {
                  Janus.log("Removing audio sender:", asnd);
                  config.pc.removeTrack(asnd);
                }
              }
            } catch (err) {
              _iterator7.e(err);
            } finally {
              _iterator7.f();
            }
          }
        }
      }

      if (media.removeVideo || media.replaceVideo) {
        if (config.myStream && config.myStream.getVideoTracks() && config.myStream.getVideoTracks().length) {
          var vt = config.myStream.getVideoTracks()[0];
          Janus.log("Removing video track:", vt);
          config.myStream.removeTrack(vt);

          try {
            vt.stop();
          } catch (e) {}
        }

        if (config.pc.getSenders() && config.pc.getSenders().length) {
          var rv = true;

          if (media.replaceVideo && Janus.unifiedPlan) {
            // We can use replaceTrack
            rv = false;
          }

          if (rv) {
            var _iterator8 = _createForOfIteratorHelper(config.pc.getSenders()),
                _step8;

            try {
              for (_iterator8.s(); !(_step8 = _iterator8.n()).done;) {
                var vsnd = _step8.value;

                if (vsnd && vsnd.track && vsnd.track.kind === "video") {
                  Janus.log("Removing video sender:", vsnd);
                  config.pc.removeTrack(vsnd);
                }
              }
            } catch (err) {
              _iterator8.e(err);
            } finally {
              _iterator8.f();
            }
          }
        }
      }
    } // Was a MediaStream object passed, or do we need to take care of that?


    if (callbacks.stream) {
      var stream = callbacks.stream;
      Janus.log("MediaStream provided by the application");
      Janus.debug(stream); // If this is an update, let's check if we need to release the previous stream

      if (media.update) {
        if (config.myStream && config.myStream !== callbacks.stream && !config.streamExternal) {
          // We're replacing a stream we captured ourselves with an external one
          try {
            // Try a MediaStreamTrack.stop() for each track
            var tracks = config.myStream.getTracks();

            var _iterator9 = _createForOfIteratorHelper(tracks),
                _step9;

            try {
              for (_iterator9.s(); !(_step9 = _iterator9.n()).done;) {
                var mst = _step9.value;
                Janus.log(mst);
                if (mst) mst.stop();
              }
            } catch (err) {
              _iterator9.e(err);
            } finally {
              _iterator9.f();
            }
          } catch (e) {// Do nothing if this fails
          }

          config.myStream = null;
        }
      } // Skip the getUserMedia part


      config.streamExternal = true;
      pluginHandle.consentDialog(false);
      streamsDone(handleId, jsep, media, callbacks, stream);
      return;
    }

    if (isAudioSendEnabled(media) || isVideoSendEnabled(media)) {
      if (!Janus.isGetUserMediaAvailable()) {
        callbacks.error("getUserMedia not available");
        return;
      }

      var constraints = {
        mandatory: {},
        optional: []
      };
      pluginHandle.consentDialog(true);
      var audioSupport = isAudioSendEnabled(media);
      if (audioSupport && media && _typeof(media.audio) === 'object') audioSupport = media.audio;
      var videoSupport = isVideoSendEnabled(media);

      if (videoSupport && media) {
        var simulcast = callbacks.simulcast === true;
        var simulcast2 = callbacks.simulcast2 === true;
        if ((simulcast || simulcast2) && !jsep && !media.video) media.video = "hires";

        if (media.video && media.video != 'screen' && media.video != 'window') {
          if (_typeof(media.video) === 'object') {
            videoSupport = media.video;
          } else {
            var width = 0;
            var height = 0,
                maxHeight = 0;

            if (media.video === 'lowres') {
              // Small resolution, 4:3
              height = 240;
              maxHeight = 240;
              width = 320;
            } else if (media.video === 'lowres-16:9') {
              // Small resolution, 16:9
              height = 180;
              maxHeight = 180;
              width = 320;
            } else if (media.video === 'hires' || media.video === 'hires-16:9' || media.video === 'hdres') {
              // High(HD) resolution is only 16:9
              height = 720;
              maxHeight = 720;
              width = 1280;
            } else if (media.video === 'fhdres') {
              // Full HD resolution is only 16:9
              height = 1080;
              maxHeight = 1080;
              width = 1920;
            } else if (media.video === '4kres') {
              // 4K resolution is only 16:9
              height = 2160;
              maxHeight = 2160;
              width = 3840;
            } else if (media.video === 'stdres') {
              // Normal resolution, 4:3
              height = 480;
              maxHeight = 480;
              width = 640;
            } else if (media.video === 'stdres-16:9') {
              // Normal resolution, 16:9
              height = 360;
              maxHeight = 360;
              width = 640;
            } else {
              Janus.log("Default video setting is stdres 4:3");
              height = 480;
              maxHeight = 480;
              width = 640;
            }

            Janus.log("Adding media constraint:", media.video);
            videoSupport = {
              'height': {
                'ideal': height
              },
              'width': {
                'ideal': width
              }
            };
            Janus.log("Adding video constraint:", videoSupport);
          }
        } else if (media.video === 'screen' || media.video === 'window') {
          // We're going to try and use the extension for Chrome 34+, the old approach
          // for older versions of Chrome, or the experimental support in Firefox 33+
          var callbackUserMedia = function callbackUserMedia(error, stream) {
            pluginHandle.consentDialog(false);

            if (error) {
              callbacks.error(error);
            } else {
              streamsDone(handleId, jsep, media, callbacks, stream);
            }
          };

          var getScreenMedia = function getScreenMedia(constraints, gsmCallback, useAudio) {
            Janus.log("Adding media constraint (screen capture)");
            Janus.debug(constraints);
            navigator.mediaDevices.getUserMedia(constraints).then(function (stream) {
              if (useAudio) {
                navigator.mediaDevices.getUserMedia({
                  audio: true,
                  video: false
                }).then(function (audioStream) {
                  stream.addTrack(audioStream.getAudioTracks()[0]);
                  gsmCallback(null, stream);
                });
              } else {
                gsmCallback(null, stream);
              }
            })["catch"](function (error) {
              pluginHandle.consentDialog(false);
              gsmCallback(error);
            });
          };

          if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
            // The new experimental getDisplayMedia API is available, let's use that
            // https://groups.google.com/forum/#!topic/discuss-webrtc/Uf0SrR4uxzk
            // https://webrtchacks.com/chrome-screensharing-getdisplaymedia/
            constraints.video = {};

            if (media.screenshareFrameRate) {
              constraints.video.frameRate = media.screenshareFrameRate;
            }

            if (media.screenshareHeight) {
              constraints.video.height = media.screenshareHeight;
            }

            if (media.screenshareWidth) {
              constraints.video.width = media.screenshareWidth;
            }

            constraints.audio = media.captureDesktopAudio;
            navigator.mediaDevices.getDisplayMedia(constraints).then(function (stream) {
              pluginHandle.consentDialog(false);

              if (isAudioSendEnabled(media) && !media.keepAudio) {
                navigator.mediaDevices.getUserMedia({
                  audio: true,
                  video: false
                }).then(function (audioStream) {
                  stream.addTrack(audioStream.getAudioTracks()[0]);
                  streamsDone(handleId, jsep, media, callbacks, stream);
                });
              } else {
                streamsDone(handleId, jsep, media, callbacks, stream);
              }
            }, function (error) {
              pluginHandle.consentDialog(false);
              callbacks.error(error);
            });
            return;
          }

          if (Janus.webRTCAdapter.browserDetails.browser === 'chrome') {
            var chromever = Janus.webRTCAdapter.browserDetails.version;
            var maxver = 33;
            if (window.navigator.userAgent.match('Linux')) maxver = 35; // "known" crash in chrome 34 and 35 on linux

            if (chromever >= 26 && chromever <= maxver) {
              // Chrome 26->33 requires some awkward chrome://flags manipulation
              constraints = {
                video: {
                  mandatory: {
                    googLeakyBucket: true,
                    maxWidth: window.screen.width,
                    maxHeight: window.screen.height,
                    minFrameRate: media.screenshareFrameRate,
                    maxFrameRate: media.screenshareFrameRate,
                    chromeMediaSource: 'screen'
                  }
                },
                audio: isAudioSendEnabled(media) && !media.keepAudio
              };
              getScreenMedia(constraints, callbackUserMedia);
            } else {
              // Chrome 34+ requires an extension
              Janus.extension.getScreen(function (error, sourceId) {
                if (error) {
                  pluginHandle.consentDialog(false);
                  return callbacks.error(error);
                }

                constraints = {
                  audio: false,
                  video: {
                    mandatory: {
                      chromeMediaSource: 'desktop',
                      maxWidth: window.screen.width,
                      maxHeight: window.screen.height,
                      minFrameRate: media.screenshareFrameRate,
                      maxFrameRate: media.screenshareFrameRate
                    },
                    optional: [{
                      googLeakyBucket: true
                    }, {
                      googTemporalLayeredScreencast: true
                    }]
                  }
                };
                constraints.video.mandatory.chromeMediaSourceId = sourceId;
                getScreenMedia(constraints, callbackUserMedia, isAudioSendEnabled(media) && !media.keepAudio);
              });
            }
          } else if (Janus.webRTCAdapter.browserDetails.browser === 'firefox') {
            if (Janus.webRTCAdapter.browserDetails.version >= 33) {
              // Firefox 33+ has experimental support for screen sharing
              constraints = {
                video: {
                  mozMediaSource: media.video,
                  mediaSource: media.video
                },
                audio: isAudioSendEnabled(media) && !media.keepAudio
              };
              getScreenMedia(constraints, function (err, stream) {
                callbackUserMedia(err, stream); // Workaround for https://bugzilla.mozilla.org/show_bug.cgi?id=1045810

                if (!err) {
                  var lastTime = stream.currentTime;
                  var polly = window.setInterval(function () {
                    if (!stream) window.clearInterval(polly);

                    if (stream.currentTime == lastTime) {
                      window.clearInterval(polly);

                      if (stream.onended) {
                        stream.onended();
                      }
                    }

                    lastTime = stream.currentTime;
                  }, 500);
                }
              });
            } else {
              var error = new Error('NavigatorUserMediaError');
              error.name = 'Your version of Firefox does not support screen sharing, please install Firefox 33 (or more recent versions)';
              pluginHandle.consentDialog(false);
              callbacks.error(error);
              return;
            }
          }

          return;
        }
      } // If we got here, we're not screensharing


      if (!media || media.video !== 'screen') {
        // Check whether all media sources are actually available or not
        navigator.mediaDevices.enumerateDevices().then(function (devices) {
          var audioExist = devices.some(function (device) {
            return device.kind === 'audioinput';
          }),
              videoExist = isScreenSendEnabled(media) || devices.some(function (device) {
            return device.kind === 'videoinput';
          }); // Check whether a missing device is really a problem

          var audioSend = isAudioSendEnabled(media);
          var videoSend = isVideoSendEnabled(media);
          var needAudioDevice = isAudioSendRequired(media);
          var needVideoDevice = isVideoSendRequired(media);

          if (audioSend || videoSend || needAudioDevice || needVideoDevice) {
            // We need to send either audio or video
            var haveAudioDevice = audioSend ? audioExist : false;
            var haveVideoDevice = videoSend ? videoExist : false;

            if (!haveAudioDevice && !haveVideoDevice) {
              // FIXME Should we really give up, or just assume recvonly for both?
              pluginHandle.consentDialog(false);
              callbacks.error('No capture device found');
              return false;
            } else if (!haveAudioDevice && needAudioDevice) {
              pluginHandle.consentDialog(false);
              callbacks.error('Audio capture is required, but no capture device found');
              return false;
            } else if (!haveVideoDevice && needVideoDevice) {
              pluginHandle.consentDialog(false);
              callbacks.error('Video capture is required, but no capture device found');
              return false;
            }
          }

          var gumConstraints = {
            audio: audioExist && !media.keepAudio ? audioSupport : false,
            video: videoExist && !media.keepVideo ? videoSupport : false
          };
          Janus.debug("getUserMedia constraints", gumConstraints);

          if (!gumConstraints.audio && !gumConstraints.video) {
            pluginHandle.consentDialog(false);
            streamsDone(handleId, jsep, media, callbacks, stream);
          } else {
            navigator.mediaDevices.getUserMedia(gumConstraints).then(function (stream) {
              pluginHandle.consentDialog(false);
              streamsDone(handleId, jsep, media, callbacks, stream);
            })["catch"](function (error) {
              pluginHandle.consentDialog(false);
              callbacks.error({
                code: error.code,
                name: error.name,
                message: error.message
              });
            });
          }
        })["catch"](function (error) {
          pluginHandle.consentDialog(false);
          callbacks.error('enumerateDevices error', error);
        });
      }
    } else {
      // No need to do a getUserMedia, create offer/answer right away
      streamsDone(handleId, jsep, media, callbacks);
    }
  }

  function prepareWebrtcPeer(handleId, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : webrtcError;
    var jsep = callbacks.jsep;
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      callbacks.error("Invalid handle");
      return;
    }

    var config = pluginHandle.webrtcStuff;

    if (jsep) {
      if (!config.pc) {
        Janus.warn("Wait, no PeerConnection?? if this is an answer, use createAnswer and not handleRemoteJsep");
        callbacks.error("No PeerConnection: if this is an answer, use createAnswer and not handleRemoteJsep");
        return;
      }

      config.pc.setRemoteDescription(jsep).then(function () {
        Janus.log("Remote description accepted!");
        config.remoteSdp = jsep.sdp; // Any trickle candidate we cached?

        if (config.candidates && config.candidates.length > 0) {
          for (var i = 0; i < config.candidates.length; i++) {
            var candidate = config.candidates[i];
            Janus.debug("Adding remote candidate:", candidate);

            if (!candidate || candidate.completed === true) {
              // end-of-candidates
              config.pc.addIceCandidate(Janus.endOfCandidates);
            } else {
              // New candidate
              config.pc.addIceCandidate(candidate);
            }
          }

          config.candidates = [];
        } // Done


        callbacks.success();
      }, callbacks.error);
    } else {
      callbacks.error("Invalid JSEP");
    }
  }

  function createOffer(handleId, media, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    callbacks.customizeSdp = typeof callbacks.customizeSdp == "function" ? callbacks.customizeSdp : Janus.noop;
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      callbacks.error("Invalid handle");
      return;
    }

    var config = pluginHandle.webrtcStuff;
    var simulcast = callbacks.simulcast === true;

    if (!simulcast) {
      Janus.log("Creating offer (iceDone=" + config.iceDone + ")");
    } else {
      Janus.log("Creating offer (iceDone=" + config.iceDone + ", simulcast=" + simulcast + ")");
    } // https://code.google.com/p/webrtc/issues/detail?id=3508


    var mediaConstraints = {};

    if (Janus.unifiedPlan) {
      // We can use Transceivers
      var audioTransceiver = null,
          videoTransceiver = null;
      var transceivers = config.pc.getTransceivers();

      if (transceivers && transceivers.length > 0) {
        var _iterator10 = _createForOfIteratorHelper(transceivers),
            _step10;

        try {
          for (_iterator10.s(); !(_step10 = _iterator10.n()).done;) {
            var t = _step10.value;

            if (t.sender && t.sender.track && t.sender.track.kind === "audio" || t.receiver && t.receiver.track && t.receiver.track.kind === "audio") {
              if (!audioTransceiver) {
                audioTransceiver = t;
              }

              continue;
            }

            if (t.sender && t.sender.track && t.sender.track.kind === "video" || t.receiver && t.receiver.track && t.receiver.track.kind === "video") {
              if (!videoTransceiver) {
                videoTransceiver = t;
              }

              continue;
            }
          }
        } catch (err) {
          _iterator10.e(err);
        } finally {
          _iterator10.f();
        }
      } // Handle audio (and related changes, if any)


      var audioSend = isAudioSendEnabled(media);
      var audioRecv = isAudioRecvEnabled(media);

      if (!audioSend && !audioRecv) {
        // Audio disabled: have we removed it?
        if (media.removeAudio && audioTransceiver) {
          if (audioTransceiver.setDirection) {
            audioTransceiver.setDirection("inactive");
          } else {
            audioTransceiver.direction = "inactive";
          }

          Janus.log("Setting audio transceiver to inactive:", audioTransceiver);
        }
      } else {
        // Take care of audio m-line
        if (audioSend && audioRecv) {
          if (audioTransceiver) {
            if (audioTransceiver.setDirection) {
              audioTransceiver.setDirection("sendrecv");
            } else {
              audioTransceiver.direction = "sendrecv";
            }

            Janus.log("Setting audio transceiver to sendrecv:", audioTransceiver);
          }
        } else if (audioSend && !audioRecv) {
          if (audioTransceiver) {
            if (audioTransceiver.setDirection) {
              audioTransceiver.setDirection("sendonly");
            } else {
              audioTransceiver.direction = "sendonly";
            }

            Janus.log("Setting audio transceiver to sendonly:", audioTransceiver);
          }
        } else if (!audioSend && audioRecv) {
          if (audioTransceiver) {
            if (audioTransceiver.setDirection) {
              audioTransceiver.setDirection("recvonly");
            } else {
              audioTransceiver.direction = "recvonly";
            }

            Janus.log("Setting audio transceiver to recvonly:", audioTransceiver);
          } else {
            // In theory, this is the only case where we might not have a transceiver yet
            audioTransceiver = config.pc.addTransceiver("audio", {
              direction: "recvonly"
            });
            Janus.log("Adding recvonly audio transceiver:", audioTransceiver);
          }
        }
      } // Handle video (and related changes, if any)


      var videoSend = isVideoSendEnabled(media);
      var videoRecv = isVideoRecvEnabled(media);

      if (!videoSend && !videoRecv) {
        // Video disabled: have we removed it?
        if (media.removeVideo && videoTransceiver) {
          if (videoTransceiver.setDirection) {
            videoTransceiver.setDirection("inactive");
          } else {
            videoTransceiver.direction = "inactive";
          }

          Janus.log("Setting video transceiver to inactive:", videoTransceiver);
        }
      } else {
        // Take care of video m-line
        if (videoSend && videoRecv) {
          if (videoTransceiver) {
            if (videoTransceiver.setDirection) {
              videoTransceiver.setDirection("sendrecv");
            } else {
              videoTransceiver.direction = "sendrecv";
            }

            Janus.log("Setting video transceiver to sendrecv:", videoTransceiver);
          }
        } else if (videoSend && !videoRecv) {
          if (videoTransceiver) {
            if (videoTransceiver.setDirection) {
              videoTransceiver.setDirection("sendonly");
            } else {
              videoTransceiver.direction = "sendonly";
            }

            Janus.log("Setting video transceiver to sendonly:", videoTransceiver);
          }
        } else if (!videoSend && videoRecv) {
          if (videoTransceiver) {
            if (videoTransceiver.setDirection) {
              videoTransceiver.setDirection("recvonly");
            } else {
              videoTransceiver.direction = "recvonly";
            }

            Janus.log("Setting video transceiver to recvonly:", videoTransceiver);
          } else {
            // In theory, this is the only case where we might not have a transceiver yet
            videoTransceiver = config.pc.addTransceiver("video", {
              direction: "recvonly"
            });
            Janus.log("Adding recvonly video transceiver:", videoTransceiver);
          }
        }
      }
    } else {
      mediaConstraints["offerToReceiveAudio"] = isAudioRecvEnabled(media);
      mediaConstraints["offerToReceiveVideo"] = isVideoRecvEnabled(media);
    }

    var iceRestart = callbacks.iceRestart === true;

    if (iceRestart) {
      mediaConstraints["iceRestart"] = true;
    }

    Janus.debug(mediaConstraints); // Check if this is Firefox and we've been asked to do simulcasting

    var sendVideo = isVideoSendEnabled(media);

    if (sendVideo && simulcast && Janus.webRTCAdapter.browserDetails.browser === "firefox") {
      // FIXME Based on https://gist.github.com/voluntas/088bc3cc62094730647b
      Janus.log("Enabling Simulcasting for Firefox (RID)");
      var sender = config.pc.getSenders().find(function (s) {
        return s.track.kind === "video";
      });

      if (sender) {
        var parameters = sender.getParameters();

        if (!parameters) {
          parameters = {};
        }

        var maxBitrates = getMaxBitrates(callbacks.simulcastMaxBitrates);
        parameters.encodings = [{
          rid: "h",
          active: true,
          maxBitrate: maxBitrates.high
        }, {
          rid: "m",
          active: true,
          maxBitrate: maxBitrates.medium,
          scaleResolutionDownBy: 2
        }, {
          rid: "l",
          active: true,
          maxBitrate: maxBitrates.low,
          scaleResolutionDownBy: 4
        }];
        sender.setParameters(parameters);
      }
    }

    config.pc.createOffer(mediaConstraints).then(function (offer) {
      Janus.debug(offer); // JSON.stringify doesn't work on some WebRTC objects anymore
      // See https://code.google.com/p/chromium/issues/detail?id=467366

      var jsep = {
        "type": offer.type,
        "sdp": offer.sdp
      };
      callbacks.customizeSdp(jsep);
      offer.sdp = jsep.sdp;
      Janus.log("Setting local description");

      if (sendVideo && simulcast) {
        // This SDP munging only works with Chrome (Safari STP may support it too)
        if (Janus.webRTCAdapter.browserDetails.browser === "chrome" || Janus.webRTCAdapter.browserDetails.browser === "safari") {
          Janus.log("Enabling Simulcasting for Chrome (SDP munging)");
          offer.sdp = mungeSdpForSimulcasting(offer.sdp);
        } else if (Janus.webRTCAdapter.browserDetails.browser !== "firefox") {
          Janus.warn("simulcast=true, but this is not Chrome nor Firefox, ignoring");
        }
      }

      config.mySdp = offer.sdp;
      config.pc.setLocalDescription(offer)["catch"](callbacks.error);
      config.mediaConstraints = mediaConstraints;

      if (!config.iceDone && !config.trickle) {
        // Don't do anything until we have all candidates
        Janus.log("Waiting for all candidates...");
        return;
      }

      Janus.log("Offer ready");
      Janus.debug(callbacks);
      callbacks.success(offer);
    }, callbacks.error);
  }

  function createAnswer(handleId, media, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    callbacks.customizeSdp = typeof callbacks.customizeSdp == "function" ? callbacks.customizeSdp : Janus.noop;
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      callbacks.error("Invalid handle");
      return;
    }

    var config = pluginHandle.webrtcStuff;
    var simulcast = callbacks.simulcast === true;

    if (!simulcast) {
      Janus.log("Creating answer (iceDone=" + config.iceDone + ")");
    } else {
      Janus.log("Creating answer (iceDone=" + config.iceDone + ", simulcast=" + simulcast + ")");
    }

    var mediaConstraints = null;

    if (Janus.unifiedPlan) {
      // We can use Transceivers
      mediaConstraints = {};
      var audioTransceiver = null,
          videoTransceiver = null;
      var transceivers = config.pc.getTransceivers();

      if (transceivers && transceivers.length > 0) {
        var _iterator11 = _createForOfIteratorHelper(transceivers),
            _step11;

        try {
          for (_iterator11.s(); !(_step11 = _iterator11.n()).done;) {
            var t = _step11.value;

            if (t.sender && t.sender.track && t.sender.track.kind === "audio" || t.receiver && t.receiver.track && t.receiver.track.kind === "audio") {
              if (!audioTransceiver) audioTransceiver = t;
              continue;
            }

            if (t.sender && t.sender.track && t.sender.track.kind === "video" || t.receiver && t.receiver.track && t.receiver.track.kind === "video") {
              if (!videoTransceiver) videoTransceiver = t;
              continue;
            }
          }
        } catch (err) {
          _iterator11.e(err);
        } finally {
          _iterator11.f();
        }
      } // Handle audio (and related changes, if any)


      var audioSend = isAudioSendEnabled(media);
      var audioRecv = isAudioRecvEnabled(media);

      if (!audioSend && !audioRecv) {
        // Audio disabled: have we removed it?
        if (media.removeAudio && audioTransceiver) {
          try {
            if (audioTransceiver.setDirection) {
              audioTransceiver.setDirection("inactive");
            } else {
              audioTransceiver.direction = "inactive";
            }

            Janus.log("Setting audio transceiver to inactive:", audioTransceiver);
          } catch (e) {
            Janus.error(e);
          }
        }
      } else {
        // Take care of audio m-line
        if (audioSend && audioRecv) {
          if (audioTransceiver) {
            try {
              if (audioTransceiver.setDirection) {
                audioTransceiver.setDirection("sendrecv");
              } else {
                audioTransceiver.direction = "sendrecv";
              }

              Janus.log("Setting audio transceiver to sendrecv:", audioTransceiver);
            } catch (e) {
              Janus.error(e);
            }
          }
        } else if (audioSend && !audioRecv) {
          try {
            if (audioTransceiver) {
              if (audioTransceiver.setDirection) {
                audioTransceiver.setDirection("sendonly");
              } else {
                audioTransceiver.direction = "sendonly";
              }

              Janus.log("Setting audio transceiver to sendonly:", audioTransceiver);
            }
          } catch (e) {
            Janus.error(e);
          }
        } else if (!audioSend && audioRecv) {
          if (audioTransceiver) {
            try {
              if (audioTransceiver.setDirection) {
                audioTransceiver.setDirection("recvonly");
              } else {
                audioTransceiver.direction = "recvonly";
              }

              Janus.log("Setting audio transceiver to recvonly:", audioTransceiver);
            } catch (e) {
              Janus.error(e);
            }
          } else {
            // In theory, this is the only case where we might not have a transceiver yet
            audioTransceiver = config.pc.addTransceiver("audio", {
              direction: "recvonly"
            });
            Janus.log("Adding recvonly audio transceiver:", audioTransceiver);
          }
        }
      } // Handle video (and related changes, if any)


      var videoSend = isVideoSendEnabled(media);
      var videoRecv = isVideoRecvEnabled(media);

      if (!videoSend && !videoRecv) {
        // Video disabled: have we removed it?
        if (media.removeVideo && videoTransceiver) {
          try {
            if (videoTransceiver.setDirection) {
              videoTransceiver.setDirection("inactive");
            } else {
              videoTransceiver.direction = "inactive";
            }

            Janus.log("Setting video transceiver to inactive:", videoTransceiver);
          } catch (e) {
            Janus.error(e);
          }
        }
      } else {
        // Take care of video m-line
        if (videoSend && videoRecv) {
          if (videoTransceiver) {
            try {
              if (videoTransceiver.setDirection) {
                videoTransceiver.setDirection("sendrecv");
              } else {
                videoTransceiver.direction = "sendrecv";
              }

              Janus.log("Setting video transceiver to sendrecv:", videoTransceiver);
            } catch (e) {
              Janus.error(e);
            }
          }
        } else if (videoSend && !videoRecv) {
          if (videoTransceiver) {
            try {
              if (videoTransceiver.setDirection) {
                videoTransceiver.setDirection("sendonly");
              } else {
                videoTransceiver.direction = "sendonly";
              }

              Janus.log("Setting video transceiver to sendonly:", videoTransceiver);
            } catch (e) {
              Janus.error(e);
            }
          }
        } else if (!videoSend && videoRecv) {
          if (videoTransceiver) {
            try {
              if (videoTransceiver.setDirection) {
                videoTransceiver.setDirection("recvonly");
              } else {
                videoTransceiver.direction = "recvonly";
              }

              Janus.log("Setting video transceiver to recvonly:", videoTransceiver);
            } catch (e) {
              Janus.error(e);
            }
          } else {
            // In theory, this is the only case where we might not have a transceiver yet
            videoTransceiver = config.pc.addTransceiver("video", {
              direction: "recvonly"
            });
            Janus.log("Adding recvonly video transceiver:", videoTransceiver);
          }
        }
      }
    } else {
      if (Janus.webRTCAdapter.browserDetails.browser === "firefox" || Janus.webRTCAdapter.browserDetails.browser === "edge") {
        mediaConstraints = {
          offerToReceiveAudio: isAudioRecvEnabled(media),
          offerToReceiveVideo: isVideoRecvEnabled(media)
        };
      } else {
        mediaConstraints = {
          mandatory: {
            OfferToReceiveAudio: isAudioRecvEnabled(media),
            OfferToReceiveVideo: isVideoRecvEnabled(media)
          }
        };
      }
    }

    Janus.debug(mediaConstraints); // Check if this is Firefox and we've been asked to do simulcasting

    var sendVideo = isVideoSendEnabled(media);

    if (sendVideo && simulcast && Janus.webRTCAdapter.browserDetails.browser === "firefox") {
      // FIXME Based on https://gist.github.com/voluntas/088bc3cc62094730647b
      Janus.log("Enabling Simulcasting for Firefox (RID)");
      var sender = config.pc.getSenders()[1];
      Janus.log(sender);
      var parameters = sender.getParameters();
      Janus.log(parameters);
      var maxBitrates = getMaxBitrates(callbacks.simulcastMaxBitrates);
      sender.setParameters({
        encodings: [{
          rid: "high",
          active: true,
          priority: "high",
          maxBitrate: maxBitrates.high
        }, {
          rid: "medium",
          active: true,
          priority: "medium",
          maxBitrate: maxBitrates.medium
        }, {
          rid: "low",
          active: true,
          priority: "low",
          maxBitrate: maxBitrates.low
        }]
      });
    }

    config.pc.createAnswer(mediaConstraints).then(function (answer) {
      Janus.debug(answer); // JSON.stringify doesn't work on some WebRTC objects anymore
      // See https://code.google.com/p/chromium/issues/detail?id=467366

      var jsep = {
        "type": answer.type,
        "sdp": answer.sdp
      };
      callbacks.customizeSdp(jsep);
      answer.sdp = jsep.sdp;
      Janus.log("Setting local description");

      if (sendVideo && simulcast) {
        // This SDP munging only works with Chrome
        if (Janus.webRTCAdapter.browserDetails.browser === "chrome") {
          // FIXME Apparently trying to simulcast when answering breaks video in Chrome...
          //~ Janus.log("Enabling Simulcasting for Chrome (SDP munging)");
          //~ answer.sdp = mungeSdpForSimulcasting(answer.sdp);
          Janus.warn("simulcast=true, but this is an answer, and video breaks in Chrome if we enable it");
        } else if (Janus.webRTCAdapter.browserDetails.browser !== "firefox") {
          Janus.warn("simulcast=true, but this is not Chrome nor Firefox, ignoring");
        }
      }

      config.mySdp = answer.sdp;
      config.pc.setLocalDescription(answer)["catch"](callbacks.error);
      config.mediaConstraints = mediaConstraints;

      if (!config.iceDone && !config.trickle) {
        // Don't do anything until we have all candidates
        Janus.log("Waiting for all candidates...");
        return;
      }

      callbacks.success(answer);
    }, callbacks.error);
  }

  function sendSDP(handleId, callbacks) {
    callbacks = callbacks || {};
    callbacks.success = typeof callbacks.success == "function" ? callbacks.success : Janus.noop;
    callbacks.error = typeof callbacks.error == "function" ? callbacks.error : Janus.noop;
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle, not sending anything");
      return;
    }

    var config = pluginHandle.webrtcStuff;
    Janus.log("Sending offer/answer SDP...");

    if (!config.mySdp) {
      Janus.warn("Local SDP instance is invalid, not sending anything...");
      return;
    }

    config.mySdp = {
      "type": config.pc.localDescription.type,
      "sdp": config.pc.localDescription.sdp
    };
    if (config.trickle === false) config.mySdp["trickle"] = false;
    Janus.debug(callbacks);
    config.sdpSent = true;
    callbacks.success(config.mySdp);
  }

  function _getVolume(handleId, remote) {
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      return 0;
    }

    var stream = remote ? "remote" : "local";
    var config = pluginHandle.webrtcStuff;
    if (!config.volume[stream]) config.volume[stream] = {
      value: 0
    }; // Start getting the volume, if audioLevel in getStats is supported (apparently
    // they're only available in Chrome/Safari right now: https://webrtc-stats.callstats.io/)

    if (config.pc.getStats && (Janus.webRTCAdapter.browserDetails.browser === "chrome" || Janus.webRTCAdapter.browserDetails.browser === "safari")) {
      if (remote && !config.remoteStream) {
        Janus.warn("Remote stream unavailable");
        return 0;
      } else if (!remote && !config.myStream) {
        Janus.warn("Local stream unavailable");
        return 0;
      }

      if (!config.volume[stream].timer) {
        Janus.log("Starting " + stream + " volume monitor");
        config.volume[stream].timer = setInterval(function () {
          config.pc.getStats().then(function (stats) {
            stats.forEach(function (res) {
              if (!res || res.kind !== "audio") return;
              if (remote && !res.remoteSource || !remote && res.type !== "media-source") return;
              config.volume[stream].value = res.audioLevel ? res.audioLevel : 0;
            });
          });
        }, 200);
        return 0; // We don't have a volume to return yet
      }

      return config.volume[stream].value;
    } else {
      // audioInputLevel and audioOutputLevel seem only available in Chrome? audioLevel
      // seems to be available on Chrome and Firefox, but they don't seem to work
      Janus.warn("Getting the " + stream + " volume unsupported by browser");
      return 0;
    }
  }

  function isMuted(handleId, video) {
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      return true;
    }

    var config = pluginHandle.webrtcStuff;

    if (!config.pc) {
      Janus.warn("Invalid PeerConnection");
      return true;
    }

    if (!config.myStream) {
      Janus.warn("Invalid local MediaStream");
      return true;
    }

    if (video) {
      // Check video track
      if (!config.myStream.getVideoTracks() || config.myStream.getVideoTracks().length === 0) {
        Janus.warn("No video track");
        return true;
      }

      return !config.myStream.getVideoTracks()[0].enabled;
    } else {
      // Check audio track
      if (!config.myStream.getAudioTracks() || config.myStream.getAudioTracks().length === 0) {
        Janus.warn("No audio track");
        return true;
      }

      return !config.myStream.getAudioTracks()[0].enabled;
    }
  }

  function mute(handleId, video, mute) {
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      return false;
    }

    var config = pluginHandle.webrtcStuff;

    if (!config.pc) {
      Janus.warn("Invalid PeerConnection");
      return false;
    }

    if (!config.myStream) {
      Janus.warn("Invalid local MediaStream");
      return false;
    }

    if (video) {
      // Mute/unmute video track
      if (!config.myStream.getVideoTracks() || config.myStream.getVideoTracks().length === 0) {
        Janus.warn("No video track");
        return false;
      }

      config.myStream.getVideoTracks()[0].enabled = !mute;
      return true;
    } else {
      // Mute/unmute audio track
      if (!config.myStream.getAudioTracks() || config.myStream.getAudioTracks().length === 0) {
        Janus.warn("No audio track");
        return false;
      }

      config.myStream.getAudioTracks()[0].enabled = !mute;
      return true;
    }
  }

  function _getBitrate(handleId) {
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle || !pluginHandle.webrtcStuff) {
      Janus.warn("Invalid handle");
      return "Invalid handle";
    }

    var config = pluginHandle.webrtcStuff;
    if (!config.pc) return "Invalid PeerConnection"; // Start getting the bitrate, if getStats is supported

    if (config.pc.getStats) {
      if (!config.bitrate.timer) {
        Janus.log("Starting bitrate timer (via getStats)");
        config.bitrate.timer = setInterval(function () {
          config.pc.getStats().then(function (stats) {
            stats.forEach(function (res) {
              if (!res) return;
              var inStats = false; // Check if these are statistics on incoming media

              if ((res.mediaType === "video" || res.id.toLowerCase().indexOf("video") > -1) && res.type === "inbound-rtp" && res.id.indexOf("rtcp") < 0) {
                // New stats
                inStats = true;
              } else if (res.type == 'ssrc' && res.bytesReceived && (res.googCodecName === "VP8" || res.googCodecName === "")) {
                // Older Chromer versions
                inStats = true;
              } // Parse stats now


              if (inStats) {
                config.bitrate.bsnow = res.bytesReceived;
                config.bitrate.tsnow = res.timestamp;

                if (config.bitrate.bsbefore === null || config.bitrate.tsbefore === null) {
                  // Skip this round
                  config.bitrate.bsbefore = config.bitrate.bsnow;
                  config.bitrate.tsbefore = config.bitrate.tsnow;
                } else {
                  // Calculate bitrate
                  var timePassed = config.bitrate.tsnow - config.bitrate.tsbefore;
                  if (Janus.webRTCAdapter.browserDetails.browser === "safari") timePassed = timePassed / 1000; // Apparently the timestamp is in microseconds, in Safari

                  var bitRate = Math.round((config.bitrate.bsnow - config.bitrate.bsbefore) * 8 / timePassed);
                  if (Janus.webRTCAdapter.browserDetails.browser === "safari") bitRate = parseInt(bitRate / 1000);
                  config.bitrate.value = bitRate + ' kbits/sec'; //~ Janus.log("Estimated bitrate is " + config.bitrate.value);

                  config.bitrate.bsbefore = config.bitrate.bsnow;
                  config.bitrate.tsbefore = config.bitrate.tsnow;
                }
              }
            });
          });
        }, 1000);
        return "0 kbits/sec"; // We don't have a bitrate value yet
      }

      return config.bitrate.value;
    } else {
      Janus.warn("Getting the video bitrate unsupported by browser");
      return "Feature unsupported by browser";
    }
  }

  function webrtcError(error) {
    Janus.error("WebRTC error:", error);
  }

  function cleanupWebrtc(handleId, hangupRequest) {
    Janus.log("Cleaning WebRTC stuff");
    var pluginHandle = pluginHandles[handleId];

    if (!pluginHandle) {
      // Nothing to clean
      return;
    }

    var config = pluginHandle.webrtcStuff;

    if (config) {
      if (hangupRequest === true) {
        // Send a hangup request (we don't really care about the response)
        var request = {
          "janus": "hangup",
          "transaction": Janus.randomString(12)
        };
        if (pluginHandle.token) request["token"] = pluginHandle.token;
        if (apisecret) request["apisecret"] = apisecret;
        Janus.debug("Sending hangup request (handle=" + handleId + "):");
        Janus.debug(request);

        if (websockets) {
          request["session_id"] = sessionId;
          request["handle_id"] = handleId;
          ws.send(JSON.stringify(request));
        } else {
          Janus.httpAPICall(server + "/" + sessionId + "/" + handleId, {
            verb: 'POST',
            withCredentials: withCredentials,
            body: request
          });
        }
      } // Cleanup stack


      config.remoteStream = null;

      if (config.volume) {
        if (config.volume["local"] && config.volume["local"].timer) clearInterval(config.volume["local"].timer);
        if (config.volume["remote"] && config.volume["remote"].timer) clearInterval(config.volume["remote"].timer);
      }

      config.volume = {};
      if (config.bitrate.timer) clearInterval(config.bitrate.timer);
      config.bitrate.timer = null;
      config.bitrate.bsnow = null;
      config.bitrate.bsbefore = null;
      config.bitrate.tsnow = null;
      config.bitrate.tsbefore = null;
      config.bitrate.value = null;

      try {
        // Try a MediaStreamTrack.stop() for each track
        if (!config.streamExternal && config.myStream) {
          Janus.log("Stopping local stream tracks");
          var tracks = config.myStream.getTracks();

          var _iterator12 = _createForOfIteratorHelper(tracks),
              _step12;

          try {
            for (_iterator12.s(); !(_step12 = _iterator12.n()).done;) {
              var mst = _step12.value;
              Janus.log(mst);
              if (mst) mst.stop();
            }
          } catch (err) {
            _iterator12.e(err);
          } finally {
            _iterator12.f();
          }
        }
      } catch (e) {// Do nothing if this fails
      }

      config.streamExternal = false;
      config.myStream = null; // Close PeerConnection

      try {
        config.pc.close();
      } catch (e) {// Do nothing
      }

      config.pc = null;
      config.candidates = null;
      config.mySdp = null;
      config.remoteSdp = null;
      config.iceDone = false;
      config.dataChannel = {};
      config.dtmfSender = null;
    }

    pluginHandle.oncleanup();
  } // Helper method to munge an SDP to enable simulcasting (Chrome only)


  function mungeSdpForSimulcasting(sdp) {
    // Let's munge the SDP to add the attributes for enabling simulcasting
    // (based on https://gist.github.com/ggarber/a19b4c33510028b9c657)
    var lines = sdp.split("\r\n");
    var video = false;
    var ssrc = [-1],
        ssrc_fid = [-1];
    var cname = null,
        msid = null,
        mslabel = null,
        label = null;
    var insertAt = -1;

    for (var i = 0; i < lines.length; i++) {
      var mline = lines[i].match(/m=(\w+) */);

      if (mline) {
        var medium = mline[1];

        if (medium === "video") {
          // New video m-line: make sure it's the first one
          if (ssrc[0] < 0) {
            video = true;
          } else {
            // We're done, let's add the new attributes here
            insertAt = i;
            break;
          }
        } else {
          // New non-video m-line: do we have what we were looking for?
          if (ssrc[0] > -1) {
            // We're done, let's add the new attributes here
            insertAt = i;
            break;
          }
        }

        continue;
      }

      if (!video) continue;
      var fid = lines[i].match(/a=ssrc-group:FID (\d+) (\d+)/);

      if (fid) {
        ssrc[0] = fid[1];
        ssrc_fid[0] = fid[2];
        lines.splice(i, 1);
        i--;
        continue;
      }

      if (ssrc[0]) {
        var match = lines[i].match('a=ssrc:' + ssrc[0] + ' cname:(.+)');

        if (match) {
          cname = match[1];
        }

        match = lines[i].match('a=ssrc:' + ssrc[0] + ' msid:(.+)');

        if (match) {
          msid = match[1];
        }

        match = lines[i].match('a=ssrc:' + ssrc[0] + ' mslabel:(.+)');

        if (match) {
          mslabel = match[1];
        }

        match = lines[i].match('a=ssrc:' + ssrc[0] + ' label:(.+)');

        if (match) {
          label = match[1];
        }

        if (lines[i].indexOf('a=ssrc:' + ssrc_fid[0]) === 0) {
          lines.splice(i, 1);
          i--;
          continue;
        }

        if (lines[i].indexOf('a=ssrc:' + ssrc[0]) === 0) {
          lines.splice(i, 1);
          i--;
          continue;
        }
      }

      if (lines[i].length == 0) {
        lines.splice(i, 1);
        i--;
        continue;
      }
    }

    if (ssrc[0] < 0) {
      // Couldn't find a FID attribute, let's just take the first video SSRC we find
      insertAt = -1;
      video = false;

      for (var i = 0; i < lines.length; i++) {
        var mline = lines[i].match(/m=(\w+) */);

        if (mline) {
          var medium = mline[1];

          if (medium === "video") {
            // New video m-line: make sure it's the first one
            if (ssrc[0] < 0) {
              video = true;
            } else {
              // We're done, let's add the new attributes here
              insertAt = i;
              break;
            }
          } else {
            // New non-video m-line: do we have what we were looking for?
            if (ssrc[0] > -1) {
              // We're done, let's add the new attributes here
              insertAt = i;
              break;
            }
          }

          continue;
        }

        if (!video) continue;

        if (ssrc[0] < 0) {
          var value = lines[i].match(/a=ssrc:(\d+)/);

          if (value) {
            ssrc[0] = value[1];
            lines.splice(i, 1);
            i--;
            continue;
          }
        } else {
          var match = lines[i].match('a=ssrc:' + ssrc[0] + ' cname:(.+)');

          if (match) {
            cname = match[1];
          }

          match = lines[i].match('a=ssrc:' + ssrc[0] + ' msid:(.+)');

          if (match) {
            msid = match[1];
          }

          match = lines[i].match('a=ssrc:' + ssrc[0] + ' mslabel:(.+)');

          if (match) {
            mslabel = match[1];
          }

          match = lines[i].match('a=ssrc:' + ssrc[0] + ' label:(.+)');

          if (match) {
            label = match[1];
          }

          if (lines[i].indexOf('a=ssrc:' + ssrc_fid[0]) === 0) {
            lines.splice(i, 1);
            i--;
            continue;
          }

          if (lines[i].indexOf('a=ssrc:' + ssrc[0]) === 0) {
            lines.splice(i, 1);
            i--;
            continue;
          }
        }

        if (lines[i].length === 0) {
          lines.splice(i, 1);
          i--;
          continue;
        }
      }
    }

    if (ssrc[0] < 0) {
      // Still nothing, let's just return the SDP we were asked to munge
      Janus.warn("Couldn't find the video SSRC, simulcasting NOT enabled");
      return sdp;
    }

    if (insertAt < 0) {
      // Append at the end
      insertAt = lines.length;
    } // Generate a couple of SSRCs (for retransmissions too)
    // Note: should we check if there are conflicts, here?


    ssrc[1] = Math.floor(Math.random() * 0xFFFFFFFF);
    ssrc[2] = Math.floor(Math.random() * 0xFFFFFFFF);
    ssrc_fid[1] = Math.floor(Math.random() * 0xFFFFFFFF);
    ssrc_fid[2] = Math.floor(Math.random() * 0xFFFFFFFF); // Add attributes to the SDP

    for (var i = 0; i < ssrc.length; i++) {
      if (cname) {
        lines.splice(insertAt, 0, 'a=ssrc:' + ssrc[i] + ' cname:' + cname);
        insertAt++;
      }

      if (msid) {
        lines.splice(insertAt, 0, 'a=ssrc:' + ssrc[i] + ' msid:' + msid);
        insertAt++;
      }

      if (mslabel) {
        lines.splice(insertAt, 0, 'a=ssrc:' + ssrc[i] + ' mslabel:' + mslabel);
        insertAt++;
      }

      if (label) {
        lines.splice(insertAt, 0, 'a=ssrc:' + ssrc[i] + ' label:' + label);
        insertAt++;
      } // Add the same info for the retransmission SSRC


      if (cname) {
        lines.splice(insertAt, 0, 'a=ssrc:' + ssrc_fid[i] + ' cname:' + cname);
        insertAt++;
      }

      if (msid) {
        lines.splice(insertAt, 0, 'a=ssrc:' + ssrc_fid[i] + ' msid:' + msid);
        insertAt++;
      }

      if (mslabel) {
        lines.splice(insertAt, 0, 'a=ssrc:' + ssrc_fid[i] + ' mslabel:' + mslabel);
        insertAt++;
      }

      if (label) {
        lines.splice(insertAt, 0, 'a=ssrc:' + ssrc_fid[i] + ' label:' + label);
        insertAt++;
      }
    }

    lines.splice(insertAt, 0, 'a=ssrc-group:FID ' + ssrc[2] + ' ' + ssrc_fid[2]);
    lines.splice(insertAt, 0, 'a=ssrc-group:FID ' + ssrc[1] + ' ' + ssrc_fid[1]);
    lines.splice(insertAt, 0, 'a=ssrc-group:FID ' + ssrc[0] + ' ' + ssrc_fid[0]);
    lines.splice(insertAt, 0, 'a=ssrc-group:SIM ' + ssrc[0] + ' ' + ssrc[1] + ' ' + ssrc[2]);
    sdp = lines.join("\r\n");
    if (!sdp.endsWith("\r\n")) sdp += "\r\n";
    return sdp;
  } // Helper methods to parse a media object


  function isAudioSendEnabled(media) {
    Janus.debug("isAudioSendEnabled:", media);
    if (!media) return true; // Default

    if (media.audio === false) return false; // Generic audio has precedence

    if (media.audioSend === undefined || media.audioSend === null) return true; // Default

    return media.audioSend === true;
  }

  function isAudioSendRequired(media) {
    Janus.debug("isAudioSendRequired:", media);
    if (!media) return false; // Default

    if (media.audio === false || media.audioSend === false) return false; // If we're not asking to capture audio, it's not required

    if (media.failIfNoAudio === undefined || media.failIfNoAudio === null) return false; // Default

    return media.failIfNoAudio === true;
  }

  function isAudioRecvEnabled(media) {
    Janus.debug("isAudioRecvEnabled:", media);
    if (!media) return true; // Default

    if (media.audio === false) return false; // Generic audio has precedence

    if (media.audioRecv === undefined || media.audioRecv === null) return true; // Default

    return media.audioRecv === true;
  }

  function isVideoSendEnabled(media) {
    Janus.debug("isVideoSendEnabled:", media);
    if (!media) return true; // Default

    if (media.video === false) return false; // Generic video has precedence

    if (media.videoSend === undefined || media.videoSend === null) return true; // Default

    return media.videoSend === true;
  }

  function isVideoSendRequired(media) {
    Janus.debug("isVideoSendRequired:", media);
    if (!media) return false; // Default

    if (media.video === false || media.videoSend === false) return false; // If we're not asking to capture video, it's not required

    if (media.failIfNoVideo === undefined || media.failIfNoVideo === null) return false; // Default

    return media.failIfNoVideo === true;
  }

  function isVideoRecvEnabled(media) {
    Janus.debug("isVideoRecvEnabled:", media);
    if (!media) return true; // Default

    if (media.video === false) return false; // Generic video has precedence

    if (media.videoRecv === undefined || media.videoRecv === null) return true; // Default

    return media.videoRecv === true;
  }

  function isScreenSendEnabled(media) {
    Janus.debug("isScreenSendEnabled:", media);
    if (!media) return false;
    if (_typeof(media.video) !== 'object' || _typeof(media.video.mandatory) !== 'object') return false;
    var constraints = media.video.mandatory;
    if (constraints.chromeMediaSource) return constraints.chromeMediaSource === 'desktop' || constraints.chromeMediaSource === 'screen';else if (constraints.mozMediaSource) return constraints.mozMediaSource === 'window' || constraints.mozMediaSource === 'screen';else if (constraints.mediaSource) return constraints.mediaSource === 'window' || constraints.mediaSource === 'screen';
    return false;
  }

  function isDataEnabled(media) {
    Janus.debug("isDataEnabled:", media);

    if (Janus.webRTCAdapter.browserDetails.browser === "edge") {
      Janus.warn("Edge doesn't support data channels yet");
      return false;
    }

    if (media === undefined || media === null) return false; // Default

    return media.data === true;
  }

  function isTrickleEnabled(trickle) {
    Janus.debug("isTrickleEnabled:", trickle);
    return trickle === false ? false : true;
  }
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Janus);

/***/ }),

/***/ "./node_modules/rtcpeerconnection-shim/rtcpeerconnection.js":
/*!******************************************************************!*\
  !*** ./node_modules/rtcpeerconnection-shim/rtcpeerconnection.js ***!
  \******************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/*
 *  Copyright (c) 2017 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


var SDPUtils = __webpack_require__(/*! sdp */ "./node_modules/sdp/sdp.js");

function fixStatsType(stat) {
  return {
    inboundrtp: 'inbound-rtp',
    outboundrtp: 'outbound-rtp',
    candidatepair: 'candidate-pair',
    localcandidate: 'local-candidate',
    remotecandidate: 'remote-candidate'
  }[stat.type] || stat.type;
}

function writeMediaSection(transceiver, caps, type, stream, dtlsRole) {
  var sdp = SDPUtils.writeRtpDescription(transceiver.kind, caps);

  // Map ICE parameters (ufrag, pwd) to SDP.
  sdp += SDPUtils.writeIceParameters(
      transceiver.iceGatherer.getLocalParameters());

  // Map DTLS parameters to SDP.
  sdp += SDPUtils.writeDtlsParameters(
      transceiver.dtlsTransport.getLocalParameters(),
      type === 'offer' ? 'actpass' : dtlsRole || 'active');

  sdp += 'a=mid:' + transceiver.mid + '\r\n';

  if (transceiver.rtpSender && transceiver.rtpReceiver) {
    sdp += 'a=sendrecv\r\n';
  } else if (transceiver.rtpSender) {
    sdp += 'a=sendonly\r\n';
  } else if (transceiver.rtpReceiver) {
    sdp += 'a=recvonly\r\n';
  } else {
    sdp += 'a=inactive\r\n';
  }

  if (transceiver.rtpSender) {
    var trackId = transceiver.rtpSender._initialTrackId ||
        transceiver.rtpSender.track.id;
    transceiver.rtpSender._initialTrackId = trackId;
    // spec.
    var msid = 'msid:' + (stream ? stream.id : '-') + ' ' +
        trackId + '\r\n';
    sdp += 'a=' + msid;
    // for Chrome. Legacy should no longer be required.
    sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].ssrc +
        ' ' + msid;

    // RTX
    if (transceiver.sendEncodingParameters[0].rtx) {
      sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].rtx.ssrc +
          ' ' + msid;
      sdp += 'a=ssrc-group:FID ' +
          transceiver.sendEncodingParameters[0].ssrc + ' ' +
          transceiver.sendEncodingParameters[0].rtx.ssrc +
          '\r\n';
    }
  }
  // FIXME: this should be written by writeRtpDescription.
  sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].ssrc +
      ' cname:' + SDPUtils.localCName + '\r\n';
  if (transceiver.rtpSender && transceiver.sendEncodingParameters[0].rtx) {
    sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].rtx.ssrc +
        ' cname:' + SDPUtils.localCName + '\r\n';
  }
  return sdp;
}

// Edge does not like
// 1) stun: filtered after 14393 unless ?transport=udp is present
// 2) turn: that does not have all of turn:host:port?transport=udp
// 3) turn: with ipv6 addresses
// 4) turn: occurring muliple times
function filterIceServers(iceServers, edgeVersion) {
  var hasTurn = false;
  iceServers = JSON.parse(JSON.stringify(iceServers));
  return iceServers.filter(function(server) {
    if (server && (server.urls || server.url)) {
      var urls = server.urls || server.url;
      if (server.url && !server.urls) {
        console.warn('RTCIceServer.url is deprecated! Use urls instead.');
      }
      var isString = typeof urls === 'string';
      if (isString) {
        urls = [urls];
      }
      urls = urls.filter(function(url) {
        var validTurn = url.indexOf('turn:') === 0 &&
            url.indexOf('transport=udp') !== -1 &&
            url.indexOf('turn:[') === -1 &&
            !hasTurn;

        if (validTurn) {
          hasTurn = true;
          return true;
        }
        return url.indexOf('stun:') === 0 && edgeVersion >= 14393 &&
            url.indexOf('?transport=udp') === -1;
      });

      delete server.url;
      server.urls = isString ? urls[0] : urls;
      return !!urls.length;
    }
  });
}

// Determines the intersection of local and remote capabilities.
function getCommonCapabilities(localCapabilities, remoteCapabilities) {
  var commonCapabilities = {
    codecs: [],
    headerExtensions: [],
    fecMechanisms: []
  };

  var findCodecByPayloadType = function(pt, codecs) {
    pt = parseInt(pt, 10);
    for (var i = 0; i < codecs.length; i++) {
      if (codecs[i].payloadType === pt ||
          codecs[i].preferredPayloadType === pt) {
        return codecs[i];
      }
    }
  };

  var rtxCapabilityMatches = function(lRtx, rRtx, lCodecs, rCodecs) {
    var lCodec = findCodecByPayloadType(lRtx.parameters.apt, lCodecs);
    var rCodec = findCodecByPayloadType(rRtx.parameters.apt, rCodecs);
    return lCodec && rCodec &&
        lCodec.name.toLowerCase() === rCodec.name.toLowerCase();
  };

  localCapabilities.codecs.forEach(function(lCodec) {
    for (var i = 0; i < remoteCapabilities.codecs.length; i++) {
      var rCodec = remoteCapabilities.codecs[i];
      if (lCodec.name.toLowerCase() === rCodec.name.toLowerCase() &&
          lCodec.clockRate === rCodec.clockRate) {
        if (lCodec.name.toLowerCase() === 'rtx' &&
            lCodec.parameters && rCodec.parameters.apt) {
          // for RTX we need to find the local rtx that has a apt
          // which points to the same local codec as the remote one.
          if (!rtxCapabilityMatches(lCodec, rCodec,
              localCapabilities.codecs, remoteCapabilities.codecs)) {
            continue;
          }
        }
        rCodec = JSON.parse(JSON.stringify(rCodec)); // deepcopy
        // number of channels is the highest common number of channels
        rCodec.numChannels = Math.min(lCodec.numChannels,
            rCodec.numChannels);
        // push rCodec so we reply with offerer payload type
        commonCapabilities.codecs.push(rCodec);

        // determine common feedback mechanisms
        rCodec.rtcpFeedback = rCodec.rtcpFeedback.filter(function(fb) {
          for (var j = 0; j < lCodec.rtcpFeedback.length; j++) {
            if (lCodec.rtcpFeedback[j].type === fb.type &&
                lCodec.rtcpFeedback[j].parameter === fb.parameter) {
              return true;
            }
          }
          return false;
        });
        // FIXME: also need to determine .parameters
        //  see https://github.com/openpeer/ortc/issues/569
        break;
      }
    }
  });

  localCapabilities.headerExtensions.forEach(function(lHeaderExtension) {
    for (var i = 0; i < remoteCapabilities.headerExtensions.length;
         i++) {
      var rHeaderExtension = remoteCapabilities.headerExtensions[i];
      if (lHeaderExtension.uri === rHeaderExtension.uri) {
        commonCapabilities.headerExtensions.push(rHeaderExtension);
        break;
      }
    }
  });

  // FIXME: fecMechanisms
  return commonCapabilities;
}

// is action=setLocalDescription with type allowed in signalingState
function isActionAllowedInSignalingState(action, type, signalingState) {
  return {
    offer: {
      setLocalDescription: ['stable', 'have-local-offer'],
      setRemoteDescription: ['stable', 'have-remote-offer']
    },
    answer: {
      setLocalDescription: ['have-remote-offer', 'have-local-pranswer'],
      setRemoteDescription: ['have-local-offer', 'have-remote-pranswer']
    }
  }[type][action].indexOf(signalingState) !== -1;
}

function maybeAddCandidate(iceTransport, candidate) {
  // Edge's internal representation adds some fields therefore
  // not all fieldѕ are taken into account.
  var alreadyAdded = iceTransport.getRemoteCandidates()
      .find(function(remoteCandidate) {
        return candidate.foundation === remoteCandidate.foundation &&
            candidate.ip === remoteCandidate.ip &&
            candidate.port === remoteCandidate.port &&
            candidate.priority === remoteCandidate.priority &&
            candidate.protocol === remoteCandidate.protocol &&
            candidate.type === remoteCandidate.type;
      });
  if (!alreadyAdded) {
    iceTransport.addRemoteCandidate(candidate);
  }
  return !alreadyAdded;
}


function makeError(name, description) {
  var e = new Error(description);
  e.name = name;
  // legacy error codes from https://heycam.github.io/webidl/#idl-DOMException-error-names
  e.code = {
    NotSupportedError: 9,
    InvalidStateError: 11,
    InvalidAccessError: 15,
    TypeError: undefined,
    OperationError: undefined
  }[name];
  return e;
}

module.exports = function(window, edgeVersion) {
  // https://w3c.github.io/mediacapture-main/#mediastream
  // Helper function to add the track to the stream and
  // dispatch the event ourselves.
  function addTrackToStreamAndFireEvent(track, stream) {
    stream.addTrack(track);
    stream.dispatchEvent(new window.MediaStreamTrackEvent('addtrack',
        {track: track}));
  }

  function removeTrackFromStreamAndFireEvent(track, stream) {
    stream.removeTrack(track);
    stream.dispatchEvent(new window.MediaStreamTrackEvent('removetrack',
        {track: track}));
  }

  function fireAddTrack(pc, track, receiver, streams) {
    var trackEvent = new Event('track');
    trackEvent.track = track;
    trackEvent.receiver = receiver;
    trackEvent.transceiver = {receiver: receiver};
    trackEvent.streams = streams;
    window.setTimeout(function() {
      pc._dispatchEvent('track', trackEvent);
    });
  }

  var RTCPeerConnection = function(config) {
    var pc = this;

    var _eventTarget = document.createDocumentFragment();
    ['addEventListener', 'removeEventListener', 'dispatchEvent']
        .forEach(function(method) {
          pc[method] = _eventTarget[method].bind(_eventTarget);
        });

    this.canTrickleIceCandidates = null;

    this.needNegotiation = false;

    this.localStreams = [];
    this.remoteStreams = [];

    this._localDescription = null;
    this._remoteDescription = null;

    this.signalingState = 'stable';
    this.iceConnectionState = 'new';
    this.connectionState = 'new';
    this.iceGatheringState = 'new';

    config = JSON.parse(JSON.stringify(config || {}));

    this.usingBundle = config.bundlePolicy === 'max-bundle';
    if (config.rtcpMuxPolicy === 'negotiate') {
      throw(makeError('NotSupportedError',
          'rtcpMuxPolicy \'negotiate\' is not supported'));
    } else if (!config.rtcpMuxPolicy) {
      config.rtcpMuxPolicy = 'require';
    }

    switch (config.iceTransportPolicy) {
      case 'all':
      case 'relay':
        break;
      default:
        config.iceTransportPolicy = 'all';
        break;
    }

    switch (config.bundlePolicy) {
      case 'balanced':
      case 'max-compat':
      case 'max-bundle':
        break;
      default:
        config.bundlePolicy = 'balanced';
        break;
    }

    config.iceServers = filterIceServers(config.iceServers || [], edgeVersion);

    this._iceGatherers = [];
    if (config.iceCandidatePoolSize) {
      for (var i = config.iceCandidatePoolSize; i > 0; i--) {
        this._iceGatherers.push(new window.RTCIceGatherer({
          iceServers: config.iceServers,
          gatherPolicy: config.iceTransportPolicy
        }));
      }
    } else {
      config.iceCandidatePoolSize = 0;
    }

    this._config = config;

    // per-track iceGathers, iceTransports, dtlsTransports, rtpSenders, ...
    // everything that is needed to describe a SDP m-line.
    this.transceivers = [];

    this._sdpSessionId = SDPUtils.generateSessionId();
    this._sdpSessionVersion = 0;

    this._dtlsRole = undefined; // role for a=setup to use in answers.

    this._isClosed = false;
  };

  Object.defineProperty(RTCPeerConnection.prototype, 'localDescription', {
    configurable: true,
    get: function() {
      return this._localDescription;
    }
  });
  Object.defineProperty(RTCPeerConnection.prototype, 'remoteDescription', {
    configurable: true,
    get: function() {
      return this._remoteDescription;
    }
  });

  // set up event handlers on prototype
  RTCPeerConnection.prototype.onicecandidate = null;
  RTCPeerConnection.prototype.onaddstream = null;
  RTCPeerConnection.prototype.ontrack = null;
  RTCPeerConnection.prototype.onremovestream = null;
  RTCPeerConnection.prototype.onsignalingstatechange = null;
  RTCPeerConnection.prototype.oniceconnectionstatechange = null;
  RTCPeerConnection.prototype.onconnectionstatechange = null;
  RTCPeerConnection.prototype.onicegatheringstatechange = null;
  RTCPeerConnection.prototype.onnegotiationneeded = null;
  RTCPeerConnection.prototype.ondatachannel = null;

  RTCPeerConnection.prototype._dispatchEvent = function(name, event) {
    if (this._isClosed) {
      return;
    }
    this.dispatchEvent(event);
    if (typeof this['on' + name] === 'function') {
      this['on' + name](event);
    }
  };

  RTCPeerConnection.prototype._emitGatheringStateChange = function() {
    var event = new Event('icegatheringstatechange');
    this._dispatchEvent('icegatheringstatechange', event);
  };

  RTCPeerConnection.prototype.getConfiguration = function() {
    return this._config;
  };

  RTCPeerConnection.prototype.getLocalStreams = function() {
    return this.localStreams;
  };

  RTCPeerConnection.prototype.getRemoteStreams = function() {
    return this.remoteStreams;
  };

  // internal helper to create a transceiver object.
  // (which is not yet the same as the WebRTC 1.0 transceiver)
  RTCPeerConnection.prototype._createTransceiver = function(kind, doNotAdd) {
    var hasBundleTransport = this.transceivers.length > 0;
    var transceiver = {
      track: null,
      iceGatherer: null,
      iceTransport: null,
      dtlsTransport: null,
      localCapabilities: null,
      remoteCapabilities: null,
      rtpSender: null,
      rtpReceiver: null,
      kind: kind,
      mid: null,
      sendEncodingParameters: null,
      recvEncodingParameters: null,
      stream: null,
      associatedRemoteMediaStreams: [],
      wantReceive: true
    };
    if (this.usingBundle && hasBundleTransport) {
      transceiver.iceTransport = this.transceivers[0].iceTransport;
      transceiver.dtlsTransport = this.transceivers[0].dtlsTransport;
    } else {
      var transports = this._createIceAndDtlsTransports();
      transceiver.iceTransport = transports.iceTransport;
      transceiver.dtlsTransport = transports.dtlsTransport;
    }
    if (!doNotAdd) {
      this.transceivers.push(transceiver);
    }
    return transceiver;
  };

  RTCPeerConnection.prototype.addTrack = function(track, stream) {
    if (this._isClosed) {
      throw makeError('InvalidStateError',
          'Attempted to call addTrack on a closed peerconnection.');
    }

    var alreadyExists = this.transceivers.find(function(s) {
      return s.track === track;
    });

    if (alreadyExists) {
      throw makeError('InvalidAccessError', 'Track already exists.');
    }

    var transceiver;
    for (var i = 0; i < this.transceivers.length; i++) {
      if (!this.transceivers[i].track &&
          this.transceivers[i].kind === track.kind) {
        transceiver = this.transceivers[i];
      }
    }
    if (!transceiver) {
      transceiver = this._createTransceiver(track.kind);
    }

    this._maybeFireNegotiationNeeded();

    if (this.localStreams.indexOf(stream) === -1) {
      this.localStreams.push(stream);
    }

    transceiver.track = track;
    transceiver.stream = stream;
    transceiver.rtpSender = new window.RTCRtpSender(track,
        transceiver.dtlsTransport);
    return transceiver.rtpSender;
  };

  RTCPeerConnection.prototype.addStream = function(stream) {
    var pc = this;
    if (edgeVersion >= 15025) {
      stream.getTracks().forEach(function(track) {
        pc.addTrack(track, stream);
      });
    } else {
      // Clone is necessary for local demos mostly, attaching directly
      // to two different senders does not work (build 10547).
      // Fixed in 15025 (or earlier)
      var clonedStream = stream.clone();
      stream.getTracks().forEach(function(track, idx) {
        var clonedTrack = clonedStream.getTracks()[idx];
        track.addEventListener('enabled', function(event) {
          clonedTrack.enabled = event.enabled;
        });
      });
      clonedStream.getTracks().forEach(function(track) {
        pc.addTrack(track, clonedStream);
      });
    }
  };

  RTCPeerConnection.prototype.removeTrack = function(sender) {
    if (this._isClosed) {
      throw makeError('InvalidStateError',
          'Attempted to call removeTrack on a closed peerconnection.');
    }

    if (!(sender instanceof window.RTCRtpSender)) {
      throw new TypeError('Argument 1 of RTCPeerConnection.removeTrack ' +
          'does not implement interface RTCRtpSender.');
    }

    var transceiver = this.transceivers.find(function(t) {
      return t.rtpSender === sender;
    });

    if (!transceiver) {
      throw makeError('InvalidAccessError',
          'Sender was not created by this connection.');
    }
    var stream = transceiver.stream;

    transceiver.rtpSender.stop();
    transceiver.rtpSender = null;
    transceiver.track = null;
    transceiver.stream = null;

    // remove the stream from the set of local streams
    var localStreams = this.transceivers.map(function(t) {
      return t.stream;
    });
    if (localStreams.indexOf(stream) === -1 &&
        this.localStreams.indexOf(stream) > -1) {
      this.localStreams.splice(this.localStreams.indexOf(stream), 1);
    }

    this._maybeFireNegotiationNeeded();
  };

  RTCPeerConnection.prototype.removeStream = function(stream) {
    var pc = this;
    stream.getTracks().forEach(function(track) {
      var sender = pc.getSenders().find(function(s) {
        return s.track === track;
      });
      if (sender) {
        pc.removeTrack(sender);
      }
    });
  };

  RTCPeerConnection.prototype.getSenders = function() {
    return this.transceivers.filter(function(transceiver) {
      return !!transceiver.rtpSender;
    })
    .map(function(transceiver) {
      return transceiver.rtpSender;
    });
  };

  RTCPeerConnection.prototype.getReceivers = function() {
    return this.transceivers.filter(function(transceiver) {
      return !!transceiver.rtpReceiver;
    })
    .map(function(transceiver) {
      return transceiver.rtpReceiver;
    });
  };


  RTCPeerConnection.prototype._createIceGatherer = function(sdpMLineIndex,
      usingBundle) {
    var pc = this;
    if (usingBundle && sdpMLineIndex > 0) {
      return this.transceivers[0].iceGatherer;
    } else if (this._iceGatherers.length) {
      return this._iceGatherers.shift();
    }
    var iceGatherer = new window.RTCIceGatherer({
      iceServers: this._config.iceServers,
      gatherPolicy: this._config.iceTransportPolicy
    });
    Object.defineProperty(iceGatherer, 'state',
        {value: 'new', writable: true}
    );

    this.transceivers[sdpMLineIndex].bufferedCandidateEvents = [];
    this.transceivers[sdpMLineIndex].bufferCandidates = function(event) {
      var end = !event.candidate || Object.keys(event.candidate).length === 0;
      // polyfill since RTCIceGatherer.state is not implemented in
      // Edge 10547 yet.
      iceGatherer.state = end ? 'completed' : 'gathering';
      if (pc.transceivers[sdpMLineIndex].bufferedCandidateEvents !== null) {
        pc.transceivers[sdpMLineIndex].bufferedCandidateEvents.push(event);
      }
    };
    iceGatherer.addEventListener('localcandidate',
      this.transceivers[sdpMLineIndex].bufferCandidates);
    return iceGatherer;
  };

  // start gathering from an RTCIceGatherer.
  RTCPeerConnection.prototype._gather = function(mid, sdpMLineIndex) {
    var pc = this;
    var iceGatherer = this.transceivers[sdpMLineIndex].iceGatherer;
    if (iceGatherer.onlocalcandidate) {
      return;
    }
    var bufferedCandidateEvents =
      this.transceivers[sdpMLineIndex].bufferedCandidateEvents;
    this.transceivers[sdpMLineIndex].bufferedCandidateEvents = null;
    iceGatherer.removeEventListener('localcandidate',
      this.transceivers[sdpMLineIndex].bufferCandidates);
    iceGatherer.onlocalcandidate = function(evt) {
      if (pc.usingBundle && sdpMLineIndex > 0) {
        // if we know that we use bundle we can drop candidates with
        // ѕdpMLineIndex > 0. If we don't do this then our state gets
        // confused since we dispose the extra ice gatherer.
        return;
      }
      var event = new Event('icecandidate');
      event.candidate = {sdpMid: mid, sdpMLineIndex: sdpMLineIndex};

      var cand = evt.candidate;
      // Edge emits an empty object for RTCIceCandidateComplete‥
      var end = !cand || Object.keys(cand).length === 0;
      if (end) {
        // polyfill since RTCIceGatherer.state is not implemented in
        // Edge 10547 yet.
        if (iceGatherer.state === 'new' || iceGatherer.state === 'gathering') {
          iceGatherer.state = 'completed';
        }
      } else {
        if (iceGatherer.state === 'new') {
          iceGatherer.state = 'gathering';
        }
        // RTCIceCandidate doesn't have a component, needs to be added
        cand.component = 1;
        // also the usernameFragment. TODO: update SDP to take both variants.
        cand.ufrag = iceGatherer.getLocalParameters().usernameFragment;

        var serializedCandidate = SDPUtils.writeCandidate(cand);
        event.candidate = Object.assign(event.candidate,
            SDPUtils.parseCandidate(serializedCandidate));

        event.candidate.candidate = serializedCandidate;
        event.candidate.toJSON = function() {
          return {
            candidate: event.candidate.candidate,
            sdpMid: event.candidate.sdpMid,
            sdpMLineIndex: event.candidate.sdpMLineIndex,
            usernameFragment: event.candidate.usernameFragment
          };
        };
      }

      // update local description.
      var sections = SDPUtils.getMediaSections(pc._localDescription.sdp);
      if (!end) {
        sections[event.candidate.sdpMLineIndex] +=
            'a=' + event.candidate.candidate + '\r\n';
      } else {
        sections[event.candidate.sdpMLineIndex] +=
            'a=end-of-candidates\r\n';
      }
      pc._localDescription.sdp =
          SDPUtils.getDescription(pc._localDescription.sdp) +
          sections.join('');
      var complete = pc.transceivers.every(function(transceiver) {
        return transceiver.iceGatherer &&
            transceiver.iceGatherer.state === 'completed';
      });

      if (pc.iceGatheringState !== 'gathering') {
        pc.iceGatheringState = 'gathering';
        pc._emitGatheringStateChange();
      }

      // Emit candidate. Also emit null candidate when all gatherers are
      // complete.
      if (!end) {
        pc._dispatchEvent('icecandidate', event);
      }
      if (complete) {
        pc._dispatchEvent('icecandidate', new Event('icecandidate'));
        pc.iceGatheringState = 'complete';
        pc._emitGatheringStateChange();
      }
    };

    // emit already gathered candidates.
    window.setTimeout(function() {
      bufferedCandidateEvents.forEach(function(e) {
        iceGatherer.onlocalcandidate(e);
      });
    }, 0);
  };

  // Create ICE transport and DTLS transport.
  RTCPeerConnection.prototype._createIceAndDtlsTransports = function() {
    var pc = this;
    var iceTransport = new window.RTCIceTransport(null);
    iceTransport.onicestatechange = function() {
      pc._updateIceConnectionState();
      pc._updateConnectionState();
    };

    var dtlsTransport = new window.RTCDtlsTransport(iceTransport);
    dtlsTransport.ondtlsstatechange = function() {
      pc._updateConnectionState();
    };
    dtlsTransport.onerror = function() {
      // onerror does not set state to failed by itself.
      Object.defineProperty(dtlsTransport, 'state',
          {value: 'failed', writable: true});
      pc._updateConnectionState();
    };

    return {
      iceTransport: iceTransport,
      dtlsTransport: dtlsTransport
    };
  };

  // Destroy ICE gatherer, ICE transport and DTLS transport.
  // Without triggering the callbacks.
  RTCPeerConnection.prototype._disposeIceAndDtlsTransports = function(
      sdpMLineIndex) {
    var iceGatherer = this.transceivers[sdpMLineIndex].iceGatherer;
    if (iceGatherer) {
      delete iceGatherer.onlocalcandidate;
      delete this.transceivers[sdpMLineIndex].iceGatherer;
    }
    var iceTransport = this.transceivers[sdpMLineIndex].iceTransport;
    if (iceTransport) {
      delete iceTransport.onicestatechange;
      delete this.transceivers[sdpMLineIndex].iceTransport;
    }
    var dtlsTransport = this.transceivers[sdpMLineIndex].dtlsTransport;
    if (dtlsTransport) {
      delete dtlsTransport.ondtlsstatechange;
      delete dtlsTransport.onerror;
      delete this.transceivers[sdpMLineIndex].dtlsTransport;
    }
  };

  // Start the RTP Sender and Receiver for a transceiver.
  RTCPeerConnection.prototype._transceive = function(transceiver,
      send, recv) {
    var params = getCommonCapabilities(transceiver.localCapabilities,
        transceiver.remoteCapabilities);
    if (send && transceiver.rtpSender) {
      params.encodings = transceiver.sendEncodingParameters;
      params.rtcp = {
        cname: SDPUtils.localCName,
        compound: transceiver.rtcpParameters.compound
      };
      if (transceiver.recvEncodingParameters.length) {
        params.rtcp.ssrc = transceiver.recvEncodingParameters[0].ssrc;
      }
      transceiver.rtpSender.send(params);
    }
    if (recv && transceiver.rtpReceiver && params.codecs.length > 0) {
      // remove RTX field in Edge 14942
      if (transceiver.kind === 'video'
          && transceiver.recvEncodingParameters
          && edgeVersion < 15019) {
        transceiver.recvEncodingParameters.forEach(function(p) {
          delete p.rtx;
        });
      }
      if (transceiver.recvEncodingParameters.length) {
        params.encodings = transceiver.recvEncodingParameters;
      } else {
        params.encodings = [{}];
      }
      params.rtcp = {
        compound: transceiver.rtcpParameters.compound
      };
      if (transceiver.rtcpParameters.cname) {
        params.rtcp.cname = transceiver.rtcpParameters.cname;
      }
      if (transceiver.sendEncodingParameters.length) {
        params.rtcp.ssrc = transceiver.sendEncodingParameters[0].ssrc;
      }
      transceiver.rtpReceiver.receive(params);
    }
  };

  RTCPeerConnection.prototype.setLocalDescription = function(description) {
    var pc = this;

    // Note: pranswer is not supported.
    if (['offer', 'answer'].indexOf(description.type) === -1) {
      return Promise.reject(makeError('TypeError',
          'Unsupported type "' + description.type + '"'));
    }

    if (!isActionAllowedInSignalingState('setLocalDescription',
        description.type, pc.signalingState) || pc._isClosed) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not set local ' + description.type +
          ' in state ' + pc.signalingState));
    }

    var sections;
    var sessionpart;
    if (description.type === 'offer') {
      // VERY limited support for SDP munging. Limited to:
      // * changing the order of codecs
      sections = SDPUtils.splitSections(description.sdp);
      sessionpart = sections.shift();
      sections.forEach(function(mediaSection, sdpMLineIndex) {
        var caps = SDPUtils.parseRtpParameters(mediaSection);
        pc.transceivers[sdpMLineIndex].localCapabilities = caps;
      });

      pc.transceivers.forEach(function(transceiver, sdpMLineIndex) {
        pc._gather(transceiver.mid, sdpMLineIndex);
      });
    } else if (description.type === 'answer') {
      sections = SDPUtils.splitSections(pc._remoteDescription.sdp);
      sessionpart = sections.shift();
      var isIceLite = SDPUtils.matchPrefix(sessionpart,
          'a=ice-lite').length > 0;
      sections.forEach(function(mediaSection, sdpMLineIndex) {
        var transceiver = pc.transceivers[sdpMLineIndex];
        var iceGatherer = transceiver.iceGatherer;
        var iceTransport = transceiver.iceTransport;
        var dtlsTransport = transceiver.dtlsTransport;
        var localCapabilities = transceiver.localCapabilities;
        var remoteCapabilities = transceiver.remoteCapabilities;

        // treat bundle-only as not-rejected.
        var rejected = SDPUtils.isRejected(mediaSection) &&
            SDPUtils.matchPrefix(mediaSection, 'a=bundle-only').length === 0;

        if (!rejected && !transceiver.rejected) {
          var remoteIceParameters = SDPUtils.getIceParameters(
              mediaSection, sessionpart);
          var remoteDtlsParameters = SDPUtils.getDtlsParameters(
              mediaSection, sessionpart);
          if (isIceLite) {
            remoteDtlsParameters.role = 'server';
          }

          if (!pc.usingBundle || sdpMLineIndex === 0) {
            pc._gather(transceiver.mid, sdpMLineIndex);
            if (iceTransport.state === 'new') {
              iceTransport.start(iceGatherer, remoteIceParameters,
                  isIceLite ? 'controlling' : 'controlled');
            }
            if (dtlsTransport.state === 'new') {
              dtlsTransport.start(remoteDtlsParameters);
            }
          }

          // Calculate intersection of capabilities.
          var params = getCommonCapabilities(localCapabilities,
              remoteCapabilities);

          // Start the RTCRtpSender. The RTCRtpReceiver for this
          // transceiver has already been started in setRemoteDescription.
          pc._transceive(transceiver,
              params.codecs.length > 0,
              false);
        }
      });
    }

    pc._localDescription = {
      type: description.type,
      sdp: description.sdp
    };
    if (description.type === 'offer') {
      pc._updateSignalingState('have-local-offer');
    } else {
      pc._updateSignalingState('stable');
    }

    return Promise.resolve();
  };

  RTCPeerConnection.prototype.setRemoteDescription = function(description) {
    var pc = this;

    // Note: pranswer is not supported.
    if (['offer', 'answer'].indexOf(description.type) === -1) {
      return Promise.reject(makeError('TypeError',
          'Unsupported type "' + description.type + '"'));
    }

    if (!isActionAllowedInSignalingState('setRemoteDescription',
        description.type, pc.signalingState) || pc._isClosed) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not set remote ' + description.type +
          ' in state ' + pc.signalingState));
    }

    var streams = {};
    pc.remoteStreams.forEach(function(stream) {
      streams[stream.id] = stream;
    });
    var receiverList = [];
    var sections = SDPUtils.splitSections(description.sdp);
    var sessionpart = sections.shift();
    var isIceLite = SDPUtils.matchPrefix(sessionpart,
        'a=ice-lite').length > 0;
    var usingBundle = SDPUtils.matchPrefix(sessionpart,
        'a=group:BUNDLE ').length > 0;
    pc.usingBundle = usingBundle;
    var iceOptions = SDPUtils.matchPrefix(sessionpart,
        'a=ice-options:')[0];
    if (iceOptions) {
      pc.canTrickleIceCandidates = iceOptions.substr(14).split(' ')
          .indexOf('trickle') >= 0;
    } else {
      pc.canTrickleIceCandidates = false;
    }

    sections.forEach(function(mediaSection, sdpMLineIndex) {
      var lines = SDPUtils.splitLines(mediaSection);
      var kind = SDPUtils.getKind(mediaSection);
      // treat bundle-only as not-rejected.
      var rejected = SDPUtils.isRejected(mediaSection) &&
          SDPUtils.matchPrefix(mediaSection, 'a=bundle-only').length === 0;
      var protocol = lines[0].substr(2).split(' ')[2];

      var direction = SDPUtils.getDirection(mediaSection, sessionpart);
      var remoteMsid = SDPUtils.parseMsid(mediaSection);

      var mid = SDPUtils.getMid(mediaSection) || SDPUtils.generateIdentifier();

      // Reject datachannels which are not implemented yet.
      if (rejected || (kind === 'application' && (protocol === 'DTLS/SCTP' ||
          protocol === 'UDP/DTLS/SCTP'))) {
        // TODO: this is dangerous in the case where a non-rejected m-line
        //     becomes rejected.
        pc.transceivers[sdpMLineIndex] = {
          mid: mid,
          kind: kind,
          protocol: protocol,
          rejected: true
        };
        return;
      }

      if (!rejected && pc.transceivers[sdpMLineIndex] &&
          pc.transceivers[sdpMLineIndex].rejected) {
        // recycle a rejected transceiver.
        pc.transceivers[sdpMLineIndex] = pc._createTransceiver(kind, true);
      }

      var transceiver;
      var iceGatherer;
      var iceTransport;
      var dtlsTransport;
      var rtpReceiver;
      var sendEncodingParameters;
      var recvEncodingParameters;
      var localCapabilities;

      var track;
      // FIXME: ensure the mediaSection has rtcp-mux set.
      var remoteCapabilities = SDPUtils.parseRtpParameters(mediaSection);
      var remoteIceParameters;
      var remoteDtlsParameters;
      if (!rejected) {
        remoteIceParameters = SDPUtils.getIceParameters(mediaSection,
            sessionpart);
        remoteDtlsParameters = SDPUtils.getDtlsParameters(mediaSection,
            sessionpart);
        remoteDtlsParameters.role = 'client';
      }
      recvEncodingParameters =
          SDPUtils.parseRtpEncodingParameters(mediaSection);

      var rtcpParameters = SDPUtils.parseRtcpParameters(mediaSection);

      var isComplete = SDPUtils.matchPrefix(mediaSection,
          'a=end-of-candidates', sessionpart).length > 0;
      var cands = SDPUtils.matchPrefix(mediaSection, 'a=candidate:')
          .map(function(cand) {
            return SDPUtils.parseCandidate(cand);
          })
          .filter(function(cand) {
            return cand.component === 1;
          });

      // Check if we can use BUNDLE and dispose transports.
      if ((description.type === 'offer' || description.type === 'answer') &&
          !rejected && usingBundle && sdpMLineIndex > 0 &&
          pc.transceivers[sdpMLineIndex]) {
        pc._disposeIceAndDtlsTransports(sdpMLineIndex);
        pc.transceivers[sdpMLineIndex].iceGatherer =
            pc.transceivers[0].iceGatherer;
        pc.transceivers[sdpMLineIndex].iceTransport =
            pc.transceivers[0].iceTransport;
        pc.transceivers[sdpMLineIndex].dtlsTransport =
            pc.transceivers[0].dtlsTransport;
        if (pc.transceivers[sdpMLineIndex].rtpSender) {
          pc.transceivers[sdpMLineIndex].rtpSender.setTransport(
              pc.transceivers[0].dtlsTransport);
        }
        if (pc.transceivers[sdpMLineIndex].rtpReceiver) {
          pc.transceivers[sdpMLineIndex].rtpReceiver.setTransport(
              pc.transceivers[0].dtlsTransport);
        }
      }
      if (description.type === 'offer' && !rejected) {
        transceiver = pc.transceivers[sdpMLineIndex] ||
            pc._createTransceiver(kind);
        transceiver.mid = mid;

        if (!transceiver.iceGatherer) {
          transceiver.iceGatherer = pc._createIceGatherer(sdpMLineIndex,
              usingBundle);
        }

        if (cands.length && transceiver.iceTransport.state === 'new') {
          if (isComplete && (!usingBundle || sdpMLineIndex === 0)) {
            transceiver.iceTransport.setRemoteCandidates(cands);
          } else {
            cands.forEach(function(candidate) {
              maybeAddCandidate(transceiver.iceTransport, candidate);
            });
          }
        }

        localCapabilities = window.RTCRtpReceiver.getCapabilities(kind);

        // filter RTX until additional stuff needed for RTX is implemented
        // in adapter.js
        if (edgeVersion < 15019) {
          localCapabilities.codecs = localCapabilities.codecs.filter(
              function(codec) {
                return codec.name !== 'rtx';
              });
        }

        sendEncodingParameters = transceiver.sendEncodingParameters || [{
          ssrc: (2 * sdpMLineIndex + 2) * 1001
        }];

        // TODO: rewrite to use http://w3c.github.io/webrtc-pc/#set-associated-remote-streams
        var isNewTrack = false;
        if (direction === 'sendrecv' || direction === 'sendonly') {
          isNewTrack = !transceiver.rtpReceiver;
          rtpReceiver = transceiver.rtpReceiver ||
              new window.RTCRtpReceiver(transceiver.dtlsTransport, kind);

          if (isNewTrack) {
            var stream;
            track = rtpReceiver.track;
            // FIXME: does not work with Plan B.
            if (remoteMsid && remoteMsid.stream === '-') {
              // no-op. a stream id of '-' means: no associated stream.
            } else if (remoteMsid) {
              if (!streams[remoteMsid.stream]) {
                streams[remoteMsid.stream] = new window.MediaStream();
                Object.defineProperty(streams[remoteMsid.stream], 'id', {
                  get: function() {
                    return remoteMsid.stream;
                  }
                });
              }
              Object.defineProperty(track, 'id', {
                get: function() {
                  return remoteMsid.track;
                }
              });
              stream = streams[remoteMsid.stream];
            } else {
              if (!streams.default) {
                streams.default = new window.MediaStream();
              }
              stream = streams.default;
            }
            if (stream) {
              addTrackToStreamAndFireEvent(track, stream);
              transceiver.associatedRemoteMediaStreams.push(stream);
            }
            receiverList.push([track, rtpReceiver, stream]);
          }
        } else if (transceiver.rtpReceiver && transceiver.rtpReceiver.track) {
          transceiver.associatedRemoteMediaStreams.forEach(function(s) {
            var nativeTrack = s.getTracks().find(function(t) {
              return t.id === transceiver.rtpReceiver.track.id;
            });
            if (nativeTrack) {
              removeTrackFromStreamAndFireEvent(nativeTrack, s);
            }
          });
          transceiver.associatedRemoteMediaStreams = [];
        }

        transceiver.localCapabilities = localCapabilities;
        transceiver.remoteCapabilities = remoteCapabilities;
        transceiver.rtpReceiver = rtpReceiver;
        transceiver.rtcpParameters = rtcpParameters;
        transceiver.sendEncodingParameters = sendEncodingParameters;
        transceiver.recvEncodingParameters = recvEncodingParameters;

        // Start the RTCRtpReceiver now. The RTPSender is started in
        // setLocalDescription.
        pc._transceive(pc.transceivers[sdpMLineIndex],
            false,
            isNewTrack);
      } else if (description.type === 'answer' && !rejected) {
        transceiver = pc.transceivers[sdpMLineIndex];
        iceGatherer = transceiver.iceGatherer;
        iceTransport = transceiver.iceTransport;
        dtlsTransport = transceiver.dtlsTransport;
        rtpReceiver = transceiver.rtpReceiver;
        sendEncodingParameters = transceiver.sendEncodingParameters;
        localCapabilities = transceiver.localCapabilities;

        pc.transceivers[sdpMLineIndex].recvEncodingParameters =
            recvEncodingParameters;
        pc.transceivers[sdpMLineIndex].remoteCapabilities =
            remoteCapabilities;
        pc.transceivers[sdpMLineIndex].rtcpParameters = rtcpParameters;

        if (cands.length && iceTransport.state === 'new') {
          if ((isIceLite || isComplete) &&
              (!usingBundle || sdpMLineIndex === 0)) {
            iceTransport.setRemoteCandidates(cands);
          } else {
            cands.forEach(function(candidate) {
              maybeAddCandidate(transceiver.iceTransport, candidate);
            });
          }
        }

        if (!usingBundle || sdpMLineIndex === 0) {
          if (iceTransport.state === 'new') {
            iceTransport.start(iceGatherer, remoteIceParameters,
                'controlling');
          }
          if (dtlsTransport.state === 'new') {
            dtlsTransport.start(remoteDtlsParameters);
          }
        }

        // If the offer contained RTX but the answer did not,
        // remove RTX from sendEncodingParameters.
        var commonCapabilities = getCommonCapabilities(
          transceiver.localCapabilities,
          transceiver.remoteCapabilities);

        var hasRtx = commonCapabilities.codecs.filter(function(c) {
          return c.name.toLowerCase() === 'rtx';
        }).length;
        if (!hasRtx && transceiver.sendEncodingParameters[0].rtx) {
          delete transceiver.sendEncodingParameters[0].rtx;
        }

        pc._transceive(transceiver,
            direction === 'sendrecv' || direction === 'recvonly',
            direction === 'sendrecv' || direction === 'sendonly');

        // TODO: rewrite to use http://w3c.github.io/webrtc-pc/#set-associated-remote-streams
        if (rtpReceiver &&
            (direction === 'sendrecv' || direction === 'sendonly')) {
          track = rtpReceiver.track;
          if (remoteMsid) {
            if (!streams[remoteMsid.stream]) {
              streams[remoteMsid.stream] = new window.MediaStream();
            }
            addTrackToStreamAndFireEvent(track, streams[remoteMsid.stream]);
            receiverList.push([track, rtpReceiver, streams[remoteMsid.stream]]);
          } else {
            if (!streams.default) {
              streams.default = new window.MediaStream();
            }
            addTrackToStreamAndFireEvent(track, streams.default);
            receiverList.push([track, rtpReceiver, streams.default]);
          }
        } else {
          // FIXME: actually the receiver should be created later.
          delete transceiver.rtpReceiver;
        }
      }
    });

    if (pc._dtlsRole === undefined) {
      pc._dtlsRole = description.type === 'offer' ? 'active' : 'passive';
    }

    pc._remoteDescription = {
      type: description.type,
      sdp: description.sdp
    };
    if (description.type === 'offer') {
      pc._updateSignalingState('have-remote-offer');
    } else {
      pc._updateSignalingState('stable');
    }
    Object.keys(streams).forEach(function(sid) {
      var stream = streams[sid];
      if (stream.getTracks().length) {
        if (pc.remoteStreams.indexOf(stream) === -1) {
          pc.remoteStreams.push(stream);
          var event = new Event('addstream');
          event.stream = stream;
          window.setTimeout(function() {
            pc._dispatchEvent('addstream', event);
          });
        }

        receiverList.forEach(function(item) {
          var track = item[0];
          var receiver = item[1];
          if (stream.id !== item[2].id) {
            return;
          }
          fireAddTrack(pc, track, receiver, [stream]);
        });
      }
    });
    receiverList.forEach(function(item) {
      if (item[2]) {
        return;
      }
      fireAddTrack(pc, item[0], item[1], []);
    });

    // check whether addIceCandidate({}) was called within four seconds after
    // setRemoteDescription.
    window.setTimeout(function() {
      if (!(pc && pc.transceivers)) {
        return;
      }
      pc.transceivers.forEach(function(transceiver) {
        if (transceiver.iceTransport &&
            transceiver.iceTransport.state === 'new' &&
            transceiver.iceTransport.getRemoteCandidates().length > 0) {
          console.warn('Timeout for addRemoteCandidate. Consider sending ' +
              'an end-of-candidates notification');
          transceiver.iceTransport.addRemoteCandidate({});
        }
      });
    }, 4000);

    return Promise.resolve();
  };

  RTCPeerConnection.prototype.close = function() {
    this.transceivers.forEach(function(transceiver) {
      /* not yet
      if (transceiver.iceGatherer) {
        transceiver.iceGatherer.close();
      }
      */
      if (transceiver.iceTransport) {
        transceiver.iceTransport.stop();
      }
      if (transceiver.dtlsTransport) {
        transceiver.dtlsTransport.stop();
      }
      if (transceiver.rtpSender) {
        transceiver.rtpSender.stop();
      }
      if (transceiver.rtpReceiver) {
        transceiver.rtpReceiver.stop();
      }
    });
    // FIXME: clean up tracks, local streams, remote streams, etc
    this._isClosed = true;
    this._updateSignalingState('closed');
  };

  // Update the signaling state.
  RTCPeerConnection.prototype._updateSignalingState = function(newState) {
    this.signalingState = newState;
    var event = new Event('signalingstatechange');
    this._dispatchEvent('signalingstatechange', event);
  };

  // Determine whether to fire the negotiationneeded event.
  RTCPeerConnection.prototype._maybeFireNegotiationNeeded = function() {
    var pc = this;
    if (this.signalingState !== 'stable' || this.needNegotiation === true) {
      return;
    }
    this.needNegotiation = true;
    window.setTimeout(function() {
      if (pc.needNegotiation) {
        pc.needNegotiation = false;
        var event = new Event('negotiationneeded');
        pc._dispatchEvent('negotiationneeded', event);
      }
    }, 0);
  };

  // Update the ice connection state.
  RTCPeerConnection.prototype._updateIceConnectionState = function() {
    var newState;
    var states = {
      'new': 0,
      closed: 0,
      checking: 0,
      connected: 0,
      completed: 0,
      disconnected: 0,
      failed: 0
    };
    this.transceivers.forEach(function(transceiver) {
      if (transceiver.iceTransport && !transceiver.rejected) {
        states[transceiver.iceTransport.state]++;
      }
    });

    newState = 'new';
    if (states.failed > 0) {
      newState = 'failed';
    } else if (states.checking > 0) {
      newState = 'checking';
    } else if (states.disconnected > 0) {
      newState = 'disconnected';
    } else if (states.new > 0) {
      newState = 'new';
    } else if (states.connected > 0) {
      newState = 'connected';
    } else if (states.completed > 0) {
      newState = 'completed';
    }

    if (newState !== this.iceConnectionState) {
      this.iceConnectionState = newState;
      var event = new Event('iceconnectionstatechange');
      this._dispatchEvent('iceconnectionstatechange', event);
    }
  };

  // Update the connection state.
  RTCPeerConnection.prototype._updateConnectionState = function() {
    var newState;
    var states = {
      'new': 0,
      closed: 0,
      connecting: 0,
      connected: 0,
      completed: 0,
      disconnected: 0,
      failed: 0
    };
    this.transceivers.forEach(function(transceiver) {
      if (transceiver.iceTransport && transceiver.dtlsTransport &&
          !transceiver.rejected) {
        states[transceiver.iceTransport.state]++;
        states[transceiver.dtlsTransport.state]++;
      }
    });
    // ICETransport.completed and connected are the same for this purpose.
    states.connected += states.completed;

    newState = 'new';
    if (states.failed > 0) {
      newState = 'failed';
    } else if (states.connecting > 0) {
      newState = 'connecting';
    } else if (states.disconnected > 0) {
      newState = 'disconnected';
    } else if (states.new > 0) {
      newState = 'new';
    } else if (states.connected > 0) {
      newState = 'connected';
    }

    if (newState !== this.connectionState) {
      this.connectionState = newState;
      var event = new Event('connectionstatechange');
      this._dispatchEvent('connectionstatechange', event);
    }
  };

  RTCPeerConnection.prototype.createOffer = function() {
    var pc = this;

    if (pc._isClosed) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not call createOffer after close'));
    }

    var numAudioTracks = pc.transceivers.filter(function(t) {
      return t.kind === 'audio';
    }).length;
    var numVideoTracks = pc.transceivers.filter(function(t) {
      return t.kind === 'video';
    }).length;

    // Determine number of audio and video tracks we need to send/recv.
    var offerOptions = arguments[0];
    if (offerOptions) {
      // Reject Chrome legacy constraints.
      if (offerOptions.mandatory || offerOptions.optional) {
        throw new TypeError(
            'Legacy mandatory/optional constraints not supported.');
      }
      if (offerOptions.offerToReceiveAudio !== undefined) {
        if (offerOptions.offerToReceiveAudio === true) {
          numAudioTracks = 1;
        } else if (offerOptions.offerToReceiveAudio === false) {
          numAudioTracks = 0;
        } else {
          numAudioTracks = offerOptions.offerToReceiveAudio;
        }
      }
      if (offerOptions.offerToReceiveVideo !== undefined) {
        if (offerOptions.offerToReceiveVideo === true) {
          numVideoTracks = 1;
        } else if (offerOptions.offerToReceiveVideo === false) {
          numVideoTracks = 0;
        } else {
          numVideoTracks = offerOptions.offerToReceiveVideo;
        }
      }
    }

    pc.transceivers.forEach(function(transceiver) {
      if (transceiver.kind === 'audio') {
        numAudioTracks--;
        if (numAudioTracks < 0) {
          transceiver.wantReceive = false;
        }
      } else if (transceiver.kind === 'video') {
        numVideoTracks--;
        if (numVideoTracks < 0) {
          transceiver.wantReceive = false;
        }
      }
    });

    // Create M-lines for recvonly streams.
    while (numAudioTracks > 0 || numVideoTracks > 0) {
      if (numAudioTracks > 0) {
        pc._createTransceiver('audio');
        numAudioTracks--;
      }
      if (numVideoTracks > 0) {
        pc._createTransceiver('video');
        numVideoTracks--;
      }
    }

    var sdp = SDPUtils.writeSessionBoilerplate(pc._sdpSessionId,
        pc._sdpSessionVersion++);
    pc.transceivers.forEach(function(transceiver, sdpMLineIndex) {
      // For each track, create an ice gatherer, ice transport,
      // dtls transport, potentially rtpsender and rtpreceiver.
      var track = transceiver.track;
      var kind = transceiver.kind;
      var mid = transceiver.mid || SDPUtils.generateIdentifier();
      transceiver.mid = mid;

      if (!transceiver.iceGatherer) {
        transceiver.iceGatherer = pc._createIceGatherer(sdpMLineIndex,
            pc.usingBundle);
      }

      var localCapabilities = window.RTCRtpSender.getCapabilities(kind);
      // filter RTX until additional stuff needed for RTX is implemented
      // in adapter.js
      if (edgeVersion < 15019) {
        localCapabilities.codecs = localCapabilities.codecs.filter(
            function(codec) {
              return codec.name !== 'rtx';
            });
      }
      localCapabilities.codecs.forEach(function(codec) {
        // work around https://bugs.chromium.org/p/webrtc/issues/detail?id=6552
        // by adding level-asymmetry-allowed=1
        if (codec.name === 'H264' &&
            codec.parameters['level-asymmetry-allowed'] === undefined) {
          codec.parameters['level-asymmetry-allowed'] = '1';
        }

        // for subsequent offers, we might have to re-use the payload
        // type of the last offer.
        if (transceiver.remoteCapabilities &&
            transceiver.remoteCapabilities.codecs) {
          transceiver.remoteCapabilities.codecs.forEach(function(remoteCodec) {
            if (codec.name.toLowerCase() === remoteCodec.name.toLowerCase() &&
                codec.clockRate === remoteCodec.clockRate) {
              codec.preferredPayloadType = remoteCodec.payloadType;
            }
          });
        }
      });
      localCapabilities.headerExtensions.forEach(function(hdrExt) {
        var remoteExtensions = transceiver.remoteCapabilities &&
            transceiver.remoteCapabilities.headerExtensions || [];
        remoteExtensions.forEach(function(rHdrExt) {
          if (hdrExt.uri === rHdrExt.uri) {
            hdrExt.id = rHdrExt.id;
          }
        });
      });

      // generate an ssrc now, to be used later in rtpSender.send
      var sendEncodingParameters = transceiver.sendEncodingParameters || [{
        ssrc: (2 * sdpMLineIndex + 1) * 1001
      }];
      if (track) {
        // add RTX
        if (edgeVersion >= 15019 && kind === 'video' &&
            !sendEncodingParameters[0].rtx) {
          sendEncodingParameters[0].rtx = {
            ssrc: sendEncodingParameters[0].ssrc + 1
          };
        }
      }

      if (transceiver.wantReceive) {
        transceiver.rtpReceiver = new window.RTCRtpReceiver(
            transceiver.dtlsTransport, kind);
      }

      transceiver.localCapabilities = localCapabilities;
      transceiver.sendEncodingParameters = sendEncodingParameters;
    });

    // always offer BUNDLE and dispose on return if not supported.
    if (pc._config.bundlePolicy !== 'max-compat') {
      sdp += 'a=group:BUNDLE ' + pc.transceivers.map(function(t) {
        return t.mid;
      }).join(' ') + '\r\n';
    }
    sdp += 'a=ice-options:trickle\r\n';

    pc.transceivers.forEach(function(transceiver, sdpMLineIndex) {
      sdp += writeMediaSection(transceiver, transceiver.localCapabilities,
          'offer', transceiver.stream, pc._dtlsRole);
      sdp += 'a=rtcp-rsize\r\n';

      if (transceiver.iceGatherer && pc.iceGatheringState !== 'new' &&
          (sdpMLineIndex === 0 || !pc.usingBundle)) {
        transceiver.iceGatherer.getLocalCandidates().forEach(function(cand) {
          cand.component = 1;
          sdp += 'a=' + SDPUtils.writeCandidate(cand) + '\r\n';
        });

        if (transceiver.iceGatherer.state === 'completed') {
          sdp += 'a=end-of-candidates\r\n';
        }
      }
    });

    var desc = new window.RTCSessionDescription({
      type: 'offer',
      sdp: sdp
    });
    return Promise.resolve(desc);
  };

  RTCPeerConnection.prototype.createAnswer = function() {
    var pc = this;

    if (pc._isClosed) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not call createAnswer after close'));
    }

    if (!(pc.signalingState === 'have-remote-offer' ||
        pc.signalingState === 'have-local-pranswer')) {
      return Promise.reject(makeError('InvalidStateError',
          'Can not call createAnswer in signalingState ' + pc.signalingState));
    }

    var sdp = SDPUtils.writeSessionBoilerplate(pc._sdpSessionId,
        pc._sdpSessionVersion++);
    if (pc.usingBundle) {
      sdp += 'a=group:BUNDLE ' + pc.transceivers.map(function(t) {
        return t.mid;
      }).join(' ') + '\r\n';
    }
    sdp += 'a=ice-options:trickle\r\n';

    var mediaSectionsInOffer = SDPUtils.getMediaSections(
        pc._remoteDescription.sdp).length;
    pc.transceivers.forEach(function(transceiver, sdpMLineIndex) {
      if (sdpMLineIndex + 1 > mediaSectionsInOffer) {
        return;
      }
      if (transceiver.rejected) {
        if (transceiver.kind === 'application') {
          if (transceiver.protocol === 'DTLS/SCTP') { // legacy fmt
            sdp += 'm=application 0 DTLS/SCTP 5000\r\n';
          } else {
            sdp += 'm=application 0 ' + transceiver.protocol +
                ' webrtc-datachannel\r\n';
          }
        } else if (transceiver.kind === 'audio') {
          sdp += 'm=audio 0 UDP/TLS/RTP/SAVPF 0\r\n' +
              'a=rtpmap:0 PCMU/8000\r\n';
        } else if (transceiver.kind === 'video') {
          sdp += 'm=video 0 UDP/TLS/RTP/SAVPF 120\r\n' +
              'a=rtpmap:120 VP8/90000\r\n';
        }
        sdp += 'c=IN IP4 0.0.0.0\r\n' +
            'a=inactive\r\n' +
            'a=mid:' + transceiver.mid + '\r\n';
        return;
      }

      // FIXME: look at direction.
      if (transceiver.stream) {
        var localTrack;
        if (transceiver.kind === 'audio') {
          localTrack = transceiver.stream.getAudioTracks()[0];
        } else if (transceiver.kind === 'video') {
          localTrack = transceiver.stream.getVideoTracks()[0];
        }
        if (localTrack) {
          // add RTX
          if (edgeVersion >= 15019 && transceiver.kind === 'video' &&
              !transceiver.sendEncodingParameters[0].rtx) {
            transceiver.sendEncodingParameters[0].rtx = {
              ssrc: transceiver.sendEncodingParameters[0].ssrc + 1
            };
          }
        }
      }

      // Calculate intersection of capabilities.
      var commonCapabilities = getCommonCapabilities(
          transceiver.localCapabilities,
          transceiver.remoteCapabilities);

      var hasRtx = commonCapabilities.codecs.filter(function(c) {
        return c.name.toLowerCase() === 'rtx';
      }).length;
      if (!hasRtx && transceiver.sendEncodingParameters[0].rtx) {
        delete transceiver.sendEncodingParameters[0].rtx;
      }

      sdp += writeMediaSection(transceiver, commonCapabilities,
          'answer', transceiver.stream, pc._dtlsRole);
      if (transceiver.rtcpParameters &&
          transceiver.rtcpParameters.reducedSize) {
        sdp += 'a=rtcp-rsize\r\n';
      }
    });

    var desc = new window.RTCSessionDescription({
      type: 'answer',
      sdp: sdp
    });
    return Promise.resolve(desc);
  };

  RTCPeerConnection.prototype.addIceCandidate = function(candidate) {
    var pc = this;
    var sections;
    if (candidate && !(candidate.sdpMLineIndex !== undefined ||
        candidate.sdpMid)) {
      return Promise.reject(new TypeError('sdpMLineIndex or sdpMid required'));
    }

    // TODO: needs to go into ops queue.
    return new Promise(function(resolve, reject) {
      if (!pc._remoteDescription) {
        return reject(makeError('InvalidStateError',
            'Can not add ICE candidate without a remote description'));
      } else if (!candidate || candidate.candidate === '') {
        for (var j = 0; j < pc.transceivers.length; j++) {
          if (pc.transceivers[j].rejected) {
            continue;
          }
          pc.transceivers[j].iceTransport.addRemoteCandidate({});
          sections = SDPUtils.getMediaSections(pc._remoteDescription.sdp);
          sections[j] += 'a=end-of-candidates\r\n';
          pc._remoteDescription.sdp =
              SDPUtils.getDescription(pc._remoteDescription.sdp) +
              sections.join('');
          if (pc.usingBundle) {
            break;
          }
        }
      } else {
        var sdpMLineIndex = candidate.sdpMLineIndex;
        if (candidate.sdpMid) {
          for (var i = 0; i < pc.transceivers.length; i++) {
            if (pc.transceivers[i].mid === candidate.sdpMid) {
              sdpMLineIndex = i;
              break;
            }
          }
        }
        var transceiver = pc.transceivers[sdpMLineIndex];
        if (transceiver) {
          if (transceiver.rejected) {
            return resolve();
          }
          var cand = Object.keys(candidate.candidate).length > 0 ?
              SDPUtils.parseCandidate(candidate.candidate) : {};
          // Ignore Chrome's invalid candidates since Edge does not like them.
          if (cand.protocol === 'tcp' && (cand.port === 0 || cand.port === 9)) {
            return resolve();
          }
          // Ignore RTCP candidates, we assume RTCP-MUX.
          if (cand.component && cand.component !== 1) {
            return resolve();
          }
          // when using bundle, avoid adding candidates to the wrong
          // ice transport. And avoid adding candidates added in the SDP.
          if (sdpMLineIndex === 0 || (sdpMLineIndex > 0 &&
              transceiver.iceTransport !== pc.transceivers[0].iceTransport)) {
            if (!maybeAddCandidate(transceiver.iceTransport, cand)) {
              return reject(makeError('OperationError',
                  'Can not add ICE candidate'));
            }
          }

          // update the remoteDescription.
          var candidateString = candidate.candidate.trim();
          if (candidateString.indexOf('a=') === 0) {
            candidateString = candidateString.substr(2);
          }
          sections = SDPUtils.getMediaSections(pc._remoteDescription.sdp);
          sections[sdpMLineIndex] += 'a=' +
              (cand.type ? candidateString : 'end-of-candidates')
              + '\r\n';
          pc._remoteDescription.sdp =
              SDPUtils.getDescription(pc._remoteDescription.sdp) +
              sections.join('');
        } else {
          return reject(makeError('OperationError',
              'Can not add ICE candidate'));
        }
      }
      resolve();
    });
  };

  RTCPeerConnection.prototype.getStats = function(selector) {
    if (selector && selector instanceof window.MediaStreamTrack) {
      var senderOrReceiver = null;
      this.transceivers.forEach(function(transceiver) {
        if (transceiver.rtpSender &&
            transceiver.rtpSender.track === selector) {
          senderOrReceiver = transceiver.rtpSender;
        } else if (transceiver.rtpReceiver &&
            transceiver.rtpReceiver.track === selector) {
          senderOrReceiver = transceiver.rtpReceiver;
        }
      });
      if (!senderOrReceiver) {
        throw makeError('InvalidAccessError', 'Invalid selector.');
      }
      return senderOrReceiver.getStats();
    }

    var promises = [];
    this.transceivers.forEach(function(transceiver) {
      ['rtpSender', 'rtpReceiver', 'iceGatherer', 'iceTransport',
          'dtlsTransport'].forEach(function(method) {
            if (transceiver[method]) {
              promises.push(transceiver[method].getStats());
            }
          });
    });
    return Promise.all(promises).then(function(allStats) {
      var results = new Map();
      allStats.forEach(function(stats) {
        stats.forEach(function(stat) {
          results.set(stat.id, stat);
        });
      });
      return results;
    });
  };

  // fix low-level stat names and return Map instead of object.
  var ortcObjects = ['RTCRtpSender', 'RTCRtpReceiver', 'RTCIceGatherer',
    'RTCIceTransport', 'RTCDtlsTransport'];
  ortcObjects.forEach(function(ortcObjectName) {
    var obj = window[ortcObjectName];
    if (obj && obj.prototype && obj.prototype.getStats) {
      var nativeGetstats = obj.prototype.getStats;
      obj.prototype.getStats = function() {
        return nativeGetstats.apply(this)
        .then(function(nativeStats) {
          var mapStats = new Map();
          Object.keys(nativeStats).forEach(function(id) {
            nativeStats[id].type = fixStatsType(nativeStats[id]);
            mapStats.set(id, nativeStats[id]);
          });
          return mapStats;
        });
      };
    }
  });

  // legacy callback shims. Should be moved to adapter.js some days.
  var methods = ['createOffer', 'createAnswer'];
  methods.forEach(function(method) {
    var nativeMethod = RTCPeerConnection.prototype[method];
    RTCPeerConnection.prototype[method] = function() {
      var args = arguments;
      if (typeof args[0] === 'function' ||
          typeof args[1] === 'function') { // legacy
        return nativeMethod.apply(this, [arguments[2]])
        .then(function(description) {
          if (typeof args[0] === 'function') {
            args[0].apply(null, [description]);
          }
        }, function(error) {
          if (typeof args[1] === 'function') {
            args[1].apply(null, [error]);
          }
        });
      }
      return nativeMethod.apply(this, arguments);
    };
  });

  methods = ['setLocalDescription', 'setRemoteDescription', 'addIceCandidate'];
  methods.forEach(function(method) {
    var nativeMethod = RTCPeerConnection.prototype[method];
    RTCPeerConnection.prototype[method] = function() {
      var args = arguments;
      if (typeof args[1] === 'function' ||
          typeof args[2] === 'function') { // legacy
        return nativeMethod.apply(this, arguments)
        .then(function() {
          if (typeof args[1] === 'function') {
            args[1].apply(null);
          }
        }, function(error) {
          if (typeof args[2] === 'function') {
            args[2].apply(null, [error]);
          }
        });
      }
      return nativeMethod.apply(this, arguments);
    };
  });

  // getStats is special. It doesn't have a spec legacy method yet we support
  // getStats(something, cb) without error callbacks.
  ['getStats'].forEach(function(method) {
    var nativeMethod = RTCPeerConnection.prototype[method];
    RTCPeerConnection.prototype[method] = function() {
      var args = arguments;
      if (typeof args[1] === 'function') {
        return nativeMethod.apply(this, arguments)
        .then(function() {
          if (typeof args[1] === 'function') {
            args[1].apply(null);
          }
        });
      }
      return nativeMethod.apply(this, arguments);
    };
  });

  return RTCPeerConnection;
};


/***/ }),

/***/ "./node_modules/sdp/sdp.js":
/*!*********************************!*\
  !*** ./node_modules/sdp/sdp.js ***!
  \*********************************/
/***/ ((module) => {

/* eslint-env node */


// SDP helpers.
var SDPUtils = {};

// Generate an alphanumeric identifier for cname or mids.
// TODO: use UUIDs instead? https://gist.github.com/jed/982883
SDPUtils.generateIdentifier = function() {
  return Math.random().toString(36).substr(2, 10);
};

// The RTCP CNAME used by all peerconnections from the same JS.
SDPUtils.localCName = SDPUtils.generateIdentifier();

// Splits SDP into lines, dealing with both CRLF and LF.
SDPUtils.splitLines = function(blob) {
  return blob.trim().split('\n').map(function(line) {
    return line.trim();
  });
};
// Splits SDP into sessionpart and mediasections. Ensures CRLF.
SDPUtils.splitSections = function(blob) {
  var parts = blob.split('\nm=');
  return parts.map(function(part, index) {
    return (index > 0 ? 'm=' + part : part).trim() + '\r\n';
  });
};

// returns the session description.
SDPUtils.getDescription = function(blob) {
  var sections = SDPUtils.splitSections(blob);
  return sections && sections[0];
};

// returns the individual media sections.
SDPUtils.getMediaSections = function(blob) {
  var sections = SDPUtils.splitSections(blob);
  sections.shift();
  return sections;
};

// Returns lines that start with a certain prefix.
SDPUtils.matchPrefix = function(blob, prefix) {
  return SDPUtils.splitLines(blob).filter(function(line) {
    return line.indexOf(prefix) === 0;
  });
};

// Parses an ICE candidate line. Sample input:
// candidate:702786350 2 udp 41819902 8.8.8.8 60769 typ relay raddr 8.8.8.8
// rport 55996"
SDPUtils.parseCandidate = function(line) {
  var parts;
  // Parse both variants.
  if (line.indexOf('a=candidate:') === 0) {
    parts = line.substring(12).split(' ');
  } else {
    parts = line.substring(10).split(' ');
  }

  var candidate = {
    foundation: parts[0],
    component: parseInt(parts[1], 10),
    protocol: parts[2].toLowerCase(),
    priority: parseInt(parts[3], 10),
    ip: parts[4],
    address: parts[4], // address is an alias for ip.
    port: parseInt(parts[5], 10),
    // skip parts[6] == 'typ'
    type: parts[7]
  };

  for (var i = 8; i < parts.length; i += 2) {
    switch (parts[i]) {
      case 'raddr':
        candidate.relatedAddress = parts[i + 1];
        break;
      case 'rport':
        candidate.relatedPort = parseInt(parts[i + 1], 10);
        break;
      case 'tcptype':
        candidate.tcpType = parts[i + 1];
        break;
      case 'ufrag':
        candidate.ufrag = parts[i + 1]; // for backward compability.
        candidate.usernameFragment = parts[i + 1];
        break;
      default: // extension handling, in particular ufrag
        candidate[parts[i]] = parts[i + 1];
        break;
    }
  }
  return candidate;
};

// Translates a candidate object into SDP candidate attribute.
SDPUtils.writeCandidate = function(candidate) {
  var sdp = [];
  sdp.push(candidate.foundation);
  sdp.push(candidate.component);
  sdp.push(candidate.protocol.toUpperCase());
  sdp.push(candidate.priority);
  sdp.push(candidate.address || candidate.ip);
  sdp.push(candidate.port);

  var type = candidate.type;
  sdp.push('typ');
  sdp.push(type);
  if (type !== 'host' && candidate.relatedAddress &&
      candidate.relatedPort) {
    sdp.push('raddr');
    sdp.push(candidate.relatedAddress);
    sdp.push('rport');
    sdp.push(candidate.relatedPort);
  }
  if (candidate.tcpType && candidate.protocol.toLowerCase() === 'tcp') {
    sdp.push('tcptype');
    sdp.push(candidate.tcpType);
  }
  if (candidate.usernameFragment || candidate.ufrag) {
    sdp.push('ufrag');
    sdp.push(candidate.usernameFragment || candidate.ufrag);
  }
  return 'candidate:' + sdp.join(' ');
};

// Parses an ice-options line, returns an array of option tags.
// a=ice-options:foo bar
SDPUtils.parseIceOptions = function(line) {
  return line.substr(14).split(' ');
};

// Parses an rtpmap line, returns RTCRtpCoddecParameters. Sample input:
// a=rtpmap:111 opus/48000/2
SDPUtils.parseRtpMap = function(line) {
  var parts = line.substr(9).split(' ');
  var parsed = {
    payloadType: parseInt(parts.shift(), 10) // was: id
  };

  parts = parts[0].split('/');

  parsed.name = parts[0];
  parsed.clockRate = parseInt(parts[1], 10); // was: clockrate
  parsed.channels = parts.length === 3 ? parseInt(parts[2], 10) : 1;
  // legacy alias, got renamed back to channels in ORTC.
  parsed.numChannels = parsed.channels;
  return parsed;
};

// Generate an a=rtpmap line from RTCRtpCodecCapability or
// RTCRtpCodecParameters.
SDPUtils.writeRtpMap = function(codec) {
  var pt = codec.payloadType;
  if (codec.preferredPayloadType !== undefined) {
    pt = codec.preferredPayloadType;
  }
  var channels = codec.channels || codec.numChannels || 1;
  return 'a=rtpmap:' + pt + ' ' + codec.name + '/' + codec.clockRate +
      (channels !== 1 ? '/' + channels : '') + '\r\n';
};

// Parses an a=extmap line (headerextension from RFC 5285). Sample input:
// a=extmap:2 urn:ietf:params:rtp-hdrext:toffset
// a=extmap:2/sendonly urn:ietf:params:rtp-hdrext:toffset
SDPUtils.parseExtmap = function(line) {
  var parts = line.substr(9).split(' ');
  return {
    id: parseInt(parts[0], 10),
    direction: parts[0].indexOf('/') > 0 ? parts[0].split('/')[1] : 'sendrecv',
    uri: parts[1]
  };
};

// Generates a=extmap line from RTCRtpHeaderExtensionParameters or
// RTCRtpHeaderExtension.
SDPUtils.writeExtmap = function(headerExtension) {
  return 'a=extmap:' + (headerExtension.id || headerExtension.preferredId) +
      (headerExtension.direction && headerExtension.direction !== 'sendrecv'
        ? '/' + headerExtension.direction
        : '') +
      ' ' + headerExtension.uri + '\r\n';
};

// Parses an ftmp line, returns dictionary. Sample input:
// a=fmtp:96 vbr=on;cng=on
// Also deals with vbr=on; cng=on
SDPUtils.parseFmtp = function(line) {
  var parsed = {};
  var kv;
  var parts = line.substr(line.indexOf(' ') + 1).split(';');
  for (var j = 0; j < parts.length; j++) {
    kv = parts[j].trim().split('=');
    parsed[kv[0].trim()] = kv[1];
  }
  return parsed;
};

// Generates an a=ftmp line from RTCRtpCodecCapability or RTCRtpCodecParameters.
SDPUtils.writeFmtp = function(codec) {
  var line = '';
  var pt = codec.payloadType;
  if (codec.preferredPayloadType !== undefined) {
    pt = codec.preferredPayloadType;
  }
  if (codec.parameters && Object.keys(codec.parameters).length) {
    var params = [];
    Object.keys(codec.parameters).forEach(function(param) {
      if (codec.parameters[param]) {
        params.push(param + '=' + codec.parameters[param]);
      } else {
        params.push(param);
      }
    });
    line += 'a=fmtp:' + pt + ' ' + params.join(';') + '\r\n';
  }
  return line;
};

// Parses an rtcp-fb line, returns RTCPRtcpFeedback object. Sample input:
// a=rtcp-fb:98 nack rpsi
SDPUtils.parseRtcpFb = function(line) {
  var parts = line.substr(line.indexOf(' ') + 1).split(' ');
  return {
    type: parts.shift(),
    parameter: parts.join(' ')
  };
};
// Generate a=rtcp-fb lines from RTCRtpCodecCapability or RTCRtpCodecParameters.
SDPUtils.writeRtcpFb = function(codec) {
  var lines = '';
  var pt = codec.payloadType;
  if (codec.preferredPayloadType !== undefined) {
    pt = codec.preferredPayloadType;
  }
  if (codec.rtcpFeedback && codec.rtcpFeedback.length) {
    // FIXME: special handling for trr-int?
    codec.rtcpFeedback.forEach(function(fb) {
      lines += 'a=rtcp-fb:' + pt + ' ' + fb.type +
      (fb.parameter && fb.parameter.length ? ' ' + fb.parameter : '') +
          '\r\n';
    });
  }
  return lines;
};

// Parses an RFC 5576 ssrc media attribute. Sample input:
// a=ssrc:3735928559 cname:something
SDPUtils.parseSsrcMedia = function(line) {
  var sp = line.indexOf(' ');
  var parts = {
    ssrc: parseInt(line.substr(7, sp - 7), 10)
  };
  var colon = line.indexOf(':', sp);
  if (colon > -1) {
    parts.attribute = line.substr(sp + 1, colon - sp - 1);
    parts.value = line.substr(colon + 1);
  } else {
    parts.attribute = line.substr(sp + 1);
  }
  return parts;
};

SDPUtils.parseSsrcGroup = function(line) {
  var parts = line.substr(13).split(' ');
  return {
    semantics: parts.shift(),
    ssrcs: parts.map(function(ssrc) {
      return parseInt(ssrc, 10);
    })
  };
};

// Extracts the MID (RFC 5888) from a media section.
// returns the MID or undefined if no mid line was found.
SDPUtils.getMid = function(mediaSection) {
  var mid = SDPUtils.matchPrefix(mediaSection, 'a=mid:')[0];
  if (mid) {
    return mid.substr(6);
  }
};

SDPUtils.parseFingerprint = function(line) {
  var parts = line.substr(14).split(' ');
  return {
    algorithm: parts[0].toLowerCase(), // algorithm is case-sensitive in Edge.
    value: parts[1]
  };
};

// Extracts DTLS parameters from SDP media section or sessionpart.
// FIXME: for consistency with other functions this should only
//   get the fingerprint line as input. See also getIceParameters.
SDPUtils.getDtlsParameters = function(mediaSection, sessionpart) {
  var lines = SDPUtils.matchPrefix(mediaSection + sessionpart,
    'a=fingerprint:');
  // Note: a=setup line is ignored since we use the 'auto' role.
  // Note2: 'algorithm' is not case sensitive except in Edge.
  return {
    role: 'auto',
    fingerprints: lines.map(SDPUtils.parseFingerprint)
  };
};

// Serializes DTLS parameters to SDP.
SDPUtils.writeDtlsParameters = function(params, setupType) {
  var sdp = 'a=setup:' + setupType + '\r\n';
  params.fingerprints.forEach(function(fp) {
    sdp += 'a=fingerprint:' + fp.algorithm + ' ' + fp.value + '\r\n';
  });
  return sdp;
};

// Parses a=crypto lines into
//   https://rawgit.com/aboba/edgertc/master/msortc-rs4.html#dictionary-rtcsrtpsdesparameters-members
SDPUtils.parseCryptoLine = function(line) {
  var parts = line.substr(9).split(' ');
  return {
    tag: parseInt(parts[0], 10),
    cryptoSuite: parts[1],
    keyParams: parts[2],
    sessionParams: parts.slice(3),
  };
};

SDPUtils.writeCryptoLine = function(parameters) {
  return 'a=crypto:' + parameters.tag + ' ' +
    parameters.cryptoSuite + ' ' +
    (typeof parameters.keyParams === 'object'
      ? SDPUtils.writeCryptoKeyParams(parameters.keyParams)
      : parameters.keyParams) +
    (parameters.sessionParams ? ' ' + parameters.sessionParams.join(' ') : '') +
    '\r\n';
};

// Parses the crypto key parameters into
//   https://rawgit.com/aboba/edgertc/master/msortc-rs4.html#rtcsrtpkeyparam*
SDPUtils.parseCryptoKeyParams = function(keyParams) {
  if (keyParams.indexOf('inline:') !== 0) {
    return null;
  }
  var parts = keyParams.substr(7).split('|');
  return {
    keyMethod: 'inline',
    keySalt: parts[0],
    lifeTime: parts[1],
    mkiValue: parts[2] ? parts[2].split(':')[0] : undefined,
    mkiLength: parts[2] ? parts[2].split(':')[1] : undefined,
  };
};

SDPUtils.writeCryptoKeyParams = function(keyParams) {
  return keyParams.keyMethod + ':'
    + keyParams.keySalt +
    (keyParams.lifeTime ? '|' + keyParams.lifeTime : '') +
    (keyParams.mkiValue && keyParams.mkiLength
      ? '|' + keyParams.mkiValue + ':' + keyParams.mkiLength
      : '');
};

// Extracts all SDES paramters.
SDPUtils.getCryptoParameters = function(mediaSection, sessionpart) {
  var lines = SDPUtils.matchPrefix(mediaSection + sessionpart,
    'a=crypto:');
  return lines.map(SDPUtils.parseCryptoLine);
};

// Parses ICE information from SDP media section or sessionpart.
// FIXME: for consistency with other functions this should only
//   get the ice-ufrag and ice-pwd lines as input.
SDPUtils.getIceParameters = function(mediaSection, sessionpart) {
  var ufrag = SDPUtils.matchPrefix(mediaSection + sessionpart,
    'a=ice-ufrag:')[0];
  var pwd = SDPUtils.matchPrefix(mediaSection + sessionpart,
    'a=ice-pwd:')[0];
  if (!(ufrag && pwd)) {
    return null;
  }
  return {
    usernameFragment: ufrag.substr(12),
    password: pwd.substr(10),
  };
};

// Serializes ICE parameters to SDP.
SDPUtils.writeIceParameters = function(params) {
  return 'a=ice-ufrag:' + params.usernameFragment + '\r\n' +
      'a=ice-pwd:' + params.password + '\r\n';
};

// Parses the SDP media section and returns RTCRtpParameters.
SDPUtils.parseRtpParameters = function(mediaSection) {
  var description = {
    codecs: [],
    headerExtensions: [],
    fecMechanisms: [],
    rtcp: []
  };
  var lines = SDPUtils.splitLines(mediaSection);
  var mline = lines[0].split(' ');
  for (var i = 3; i < mline.length; i++) { // find all codecs from mline[3..]
    var pt = mline[i];
    var rtpmapline = SDPUtils.matchPrefix(
      mediaSection, 'a=rtpmap:' + pt + ' ')[0];
    if (rtpmapline) {
      var codec = SDPUtils.parseRtpMap(rtpmapline);
      var fmtps = SDPUtils.matchPrefix(
        mediaSection, 'a=fmtp:' + pt + ' ');
      // Only the first a=fmtp:<pt> is considered.
      codec.parameters = fmtps.length ? SDPUtils.parseFmtp(fmtps[0]) : {};
      codec.rtcpFeedback = SDPUtils.matchPrefix(
        mediaSection, 'a=rtcp-fb:' + pt + ' ')
        .map(SDPUtils.parseRtcpFb);
      description.codecs.push(codec);
      // parse FEC mechanisms from rtpmap lines.
      switch (codec.name.toUpperCase()) {
        case 'RED':
        case 'ULPFEC':
          description.fecMechanisms.push(codec.name.toUpperCase());
          break;
        default: // only RED and ULPFEC are recognized as FEC mechanisms.
          break;
      }
    }
  }
  SDPUtils.matchPrefix(mediaSection, 'a=extmap:').forEach(function(line) {
    description.headerExtensions.push(SDPUtils.parseExtmap(line));
  });
  // FIXME: parse rtcp.
  return description;
};

// Generates parts of the SDP media section describing the capabilities /
// parameters.
SDPUtils.writeRtpDescription = function(kind, caps) {
  var sdp = '';

  // Build the mline.
  sdp += 'm=' + kind + ' ';
  sdp += caps.codecs.length > 0 ? '9' : '0'; // reject if no codecs.
  sdp += ' UDP/TLS/RTP/SAVPF ';
  sdp += caps.codecs.map(function(codec) {
    if (codec.preferredPayloadType !== undefined) {
      return codec.preferredPayloadType;
    }
    return codec.payloadType;
  }).join(' ') + '\r\n';

  sdp += 'c=IN IP4 0.0.0.0\r\n';
  sdp += 'a=rtcp:9 IN IP4 0.0.0.0\r\n';

  // Add a=rtpmap lines for each codec. Also fmtp and rtcp-fb.
  caps.codecs.forEach(function(codec) {
    sdp += SDPUtils.writeRtpMap(codec);
    sdp += SDPUtils.writeFmtp(codec);
    sdp += SDPUtils.writeRtcpFb(codec);
  });
  var maxptime = 0;
  caps.codecs.forEach(function(codec) {
    if (codec.maxptime > maxptime) {
      maxptime = codec.maxptime;
    }
  });
  if (maxptime > 0) {
    sdp += 'a=maxptime:' + maxptime + '\r\n';
  }
  sdp += 'a=rtcp-mux\r\n';

  if (caps.headerExtensions) {
    caps.headerExtensions.forEach(function(extension) {
      sdp += SDPUtils.writeExtmap(extension);
    });
  }
  // FIXME: write fecMechanisms.
  return sdp;
};

// Parses the SDP media section and returns an array of
// RTCRtpEncodingParameters.
SDPUtils.parseRtpEncodingParameters = function(mediaSection) {
  var encodingParameters = [];
  var description = SDPUtils.parseRtpParameters(mediaSection);
  var hasRed = description.fecMechanisms.indexOf('RED') !== -1;
  var hasUlpfec = description.fecMechanisms.indexOf('ULPFEC') !== -1;

  // filter a=ssrc:... cname:, ignore PlanB-msid
  var ssrcs = SDPUtils.matchPrefix(mediaSection, 'a=ssrc:')
    .map(function(line) {
      return SDPUtils.parseSsrcMedia(line);
    })
    .filter(function(parts) {
      return parts.attribute === 'cname';
    });
  var primarySsrc = ssrcs.length > 0 && ssrcs[0].ssrc;
  var secondarySsrc;

  var flows = SDPUtils.matchPrefix(mediaSection, 'a=ssrc-group:FID')
    .map(function(line) {
      var parts = line.substr(17).split(' ');
      return parts.map(function(part) {
        return parseInt(part, 10);
      });
    });
  if (flows.length > 0 && flows[0].length > 1 && flows[0][0] === primarySsrc) {
    secondarySsrc = flows[0][1];
  }

  description.codecs.forEach(function(codec) {
    if (codec.name.toUpperCase() === 'RTX' && codec.parameters.apt) {
      var encParam = {
        ssrc: primarySsrc,
        codecPayloadType: parseInt(codec.parameters.apt, 10)
      };
      if (primarySsrc && secondarySsrc) {
        encParam.rtx = {ssrc: secondarySsrc};
      }
      encodingParameters.push(encParam);
      if (hasRed) {
        encParam = JSON.parse(JSON.stringify(encParam));
        encParam.fec = {
          ssrc: primarySsrc,
          mechanism: hasUlpfec ? 'red+ulpfec' : 'red'
        };
        encodingParameters.push(encParam);
      }
    }
  });
  if (encodingParameters.length === 0 && primarySsrc) {
    encodingParameters.push({
      ssrc: primarySsrc
    });
  }

  // we support both b=AS and b=TIAS but interpret AS as TIAS.
  var bandwidth = SDPUtils.matchPrefix(mediaSection, 'b=');
  if (bandwidth.length) {
    if (bandwidth[0].indexOf('b=TIAS:') === 0) {
      bandwidth = parseInt(bandwidth[0].substr(7), 10);
    } else if (bandwidth[0].indexOf('b=AS:') === 0) {
      // use formula from JSEP to convert b=AS to TIAS value.
      bandwidth = parseInt(bandwidth[0].substr(5), 10) * 1000 * 0.95
          - (50 * 40 * 8);
    } else {
      bandwidth = undefined;
    }
    encodingParameters.forEach(function(params) {
      params.maxBitrate = bandwidth;
    });
  }
  return encodingParameters;
};

// parses http://draft.ortc.org/#rtcrtcpparameters*
SDPUtils.parseRtcpParameters = function(mediaSection) {
  var rtcpParameters = {};

  // Gets the first SSRC. Note tha with RTX there might be multiple
  // SSRCs.
  var remoteSsrc = SDPUtils.matchPrefix(mediaSection, 'a=ssrc:')
    .map(function(line) {
      return SDPUtils.parseSsrcMedia(line);
    })
    .filter(function(obj) {
      return obj.attribute === 'cname';
    })[0];
  if (remoteSsrc) {
    rtcpParameters.cname = remoteSsrc.value;
    rtcpParameters.ssrc = remoteSsrc.ssrc;
  }

  // Edge uses the compound attribute instead of reducedSize
  // compound is !reducedSize
  var rsize = SDPUtils.matchPrefix(mediaSection, 'a=rtcp-rsize');
  rtcpParameters.reducedSize = rsize.length > 0;
  rtcpParameters.compound = rsize.length === 0;

  // parses the rtcp-mux attrіbute.
  // Note that Edge does not support unmuxed RTCP.
  var mux = SDPUtils.matchPrefix(mediaSection, 'a=rtcp-mux');
  rtcpParameters.mux = mux.length > 0;

  return rtcpParameters;
};

// parses either a=msid: or a=ssrc:... msid lines and returns
// the id of the MediaStream and MediaStreamTrack.
SDPUtils.parseMsid = function(mediaSection) {
  var parts;
  var spec = SDPUtils.matchPrefix(mediaSection, 'a=msid:');
  if (spec.length === 1) {
    parts = spec[0].substr(7).split(' ');
    return {stream: parts[0], track: parts[1]};
  }
  var planB = SDPUtils.matchPrefix(mediaSection, 'a=ssrc:')
    .map(function(line) {
      return SDPUtils.parseSsrcMedia(line);
    })
    .filter(function(msidParts) {
      return msidParts.attribute === 'msid';
    });
  if (planB.length > 0) {
    parts = planB[0].value.split(' ');
    return {stream: parts[0], track: parts[1]};
  }
};

// SCTP
// parses draft-ietf-mmusic-sctp-sdp-26 first and falls back
// to draft-ietf-mmusic-sctp-sdp-05
SDPUtils.parseSctpDescription = function(mediaSection) {
  var mline = SDPUtils.parseMLine(mediaSection);
  var maxSizeLine = SDPUtils.matchPrefix(mediaSection, 'a=max-message-size:');
  var maxMessageSize;
  if (maxSizeLine.length > 0) {
    maxMessageSize = parseInt(maxSizeLine[0].substr(19), 10);
  }
  if (isNaN(maxMessageSize)) {
    maxMessageSize = 65536;
  }
  var sctpPort = SDPUtils.matchPrefix(mediaSection, 'a=sctp-port:');
  if (sctpPort.length > 0) {
    return {
      port: parseInt(sctpPort[0].substr(12), 10),
      protocol: mline.fmt,
      maxMessageSize: maxMessageSize
    };
  }
  var sctpMapLines = SDPUtils.matchPrefix(mediaSection, 'a=sctpmap:');
  if (sctpMapLines.length > 0) {
    var parts = SDPUtils.matchPrefix(mediaSection, 'a=sctpmap:')[0]
      .substr(10)
      .split(' ');
    return {
      port: parseInt(parts[0], 10),
      protocol: parts[1],
      maxMessageSize: maxMessageSize
    };
  }
};

// SCTP
// outputs the draft-ietf-mmusic-sctp-sdp-26 version that all browsers
// support by now receiving in this format, unless we originally parsed
// as the draft-ietf-mmusic-sctp-sdp-05 format (indicated by the m-line
// protocol of DTLS/SCTP -- without UDP/ or TCP/)
SDPUtils.writeSctpDescription = function(media, sctp) {
  var output = [];
  if (media.protocol !== 'DTLS/SCTP') {
    output = [
      'm=' + media.kind + ' 9 ' + media.protocol + ' ' + sctp.protocol + '\r\n',
      'c=IN IP4 0.0.0.0\r\n',
      'a=sctp-port:' + sctp.port + '\r\n'
    ];
  } else {
    output = [
      'm=' + media.kind + ' 9 ' + media.protocol + ' ' + sctp.port + '\r\n',
      'c=IN IP4 0.0.0.0\r\n',
      'a=sctpmap:' + sctp.port + ' ' + sctp.protocol + ' 65535\r\n'
    ];
  }
  if (sctp.maxMessageSize !== undefined) {
    output.push('a=max-message-size:' + sctp.maxMessageSize + '\r\n');
  }
  return output.join('');
};

// Generate a session ID for SDP.
// https://tools.ietf.org/html/draft-ietf-rtcweb-jsep-20#section-5.2.1
// recommends using a cryptographically random +ve 64-bit value
// but right now this should be acceptable and within the right range
SDPUtils.generateSessionId = function() {
  return Math.random().toString().substr(2, 21);
};

// Write boilder plate for start of SDP
// sessId argument is optional - if not supplied it will
// be generated randomly
// sessVersion is optional and defaults to 2
// sessUser is optional and defaults to 'thisisadapterortc'
SDPUtils.writeSessionBoilerplate = function(sessId, sessVer, sessUser) {
  var sessionId;
  var version = sessVer !== undefined ? sessVer : 2;
  if (sessId) {
    sessionId = sessId;
  } else {
    sessionId = SDPUtils.generateSessionId();
  }
  var user = sessUser || 'thisisadapterortc';
  // FIXME: sess-id should be an NTP timestamp.
  return 'v=0\r\n' +
      'o=' + user + ' ' + sessionId + ' ' + version +
        ' IN IP4 127.0.0.1\r\n' +
      's=-\r\n' +
      't=0 0\r\n';
};

SDPUtils.writeMediaSection = function(transceiver, caps, type, stream) {
  var sdp = SDPUtils.writeRtpDescription(transceiver.kind, caps);

  // Map ICE parameters (ufrag, pwd) to SDP.
  sdp += SDPUtils.writeIceParameters(
    transceiver.iceGatherer.getLocalParameters());

  // Map DTLS parameters to SDP.
  sdp += SDPUtils.writeDtlsParameters(
    transceiver.dtlsTransport.getLocalParameters(),
    type === 'offer' ? 'actpass' : 'active');

  sdp += 'a=mid:' + transceiver.mid + '\r\n';

  if (transceiver.direction) {
    sdp += 'a=' + transceiver.direction + '\r\n';
  } else if (transceiver.rtpSender && transceiver.rtpReceiver) {
    sdp += 'a=sendrecv\r\n';
  } else if (transceiver.rtpSender) {
    sdp += 'a=sendonly\r\n';
  } else if (transceiver.rtpReceiver) {
    sdp += 'a=recvonly\r\n';
  } else {
    sdp += 'a=inactive\r\n';
  }

  if (transceiver.rtpSender) {
    // spec.
    var msid = 'msid:' + stream.id + ' ' +
        transceiver.rtpSender.track.id + '\r\n';
    sdp += 'a=' + msid;

    // for Chrome.
    sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].ssrc +
        ' ' + msid;
    if (transceiver.sendEncodingParameters[0].rtx) {
      sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].rtx.ssrc +
          ' ' + msid;
      sdp += 'a=ssrc-group:FID ' +
          transceiver.sendEncodingParameters[0].ssrc + ' ' +
          transceiver.sendEncodingParameters[0].rtx.ssrc +
          '\r\n';
    }
  }
  // FIXME: this should be written by writeRtpDescription.
  sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].ssrc +
      ' cname:' + SDPUtils.localCName + '\r\n';
  if (transceiver.rtpSender && transceiver.sendEncodingParameters[0].rtx) {
    sdp += 'a=ssrc:' + transceiver.sendEncodingParameters[0].rtx.ssrc +
        ' cname:' + SDPUtils.localCName + '\r\n';
  }
  return sdp;
};

// Gets the direction from the mediaSection or the sessionpart.
SDPUtils.getDirection = function(mediaSection, sessionpart) {
  // Look for sendrecv, sendonly, recvonly, inactive, default to sendrecv.
  var lines = SDPUtils.splitLines(mediaSection);
  for (var i = 0; i < lines.length; i++) {
    switch (lines[i]) {
      case 'a=sendrecv':
      case 'a=sendonly':
      case 'a=recvonly':
      case 'a=inactive':
        return lines[i].substr(2);
      default:
        // FIXME: What should happen here?
    }
  }
  if (sessionpart) {
    return SDPUtils.getDirection(sessionpart);
  }
  return 'sendrecv';
};

SDPUtils.getKind = function(mediaSection) {
  var lines = SDPUtils.splitLines(mediaSection);
  var mline = lines[0].split(' ');
  return mline[0].substr(2);
};

SDPUtils.isRejected = function(mediaSection) {
  return mediaSection.split(' ', 2)[1] === '0';
};

SDPUtils.parseMLine = function(mediaSection) {
  var lines = SDPUtils.splitLines(mediaSection);
  var parts = lines[0].substr(2).split(' ');
  return {
    kind: parts[0],
    port: parseInt(parts[1], 10),
    protocol: parts[2],
    fmt: parts.slice(3).join(' ')
  };
};

SDPUtils.parseOLine = function(mediaSection) {
  var line = SDPUtils.matchPrefix(mediaSection, 'o=')[0];
  var parts = line.substr(2).split(' ');
  return {
    username: parts[0],
    sessionId: parts[1],
    sessionVersion: parseInt(parts[2], 10),
    netType: parts[3],
    addressType: parts[4],
    address: parts[5]
  };
};

// a very naive interpretation of a valid SDP.
SDPUtils.isValidSDP = function(blob) {
  if (typeof blob !== 'string' || blob.length === 0) {
    return false;
  }
  var lines = SDPUtils.splitLines(blob);
  for (var i = 0; i < lines.length; i++) {
    if (lines[i].length < 2 || lines[i].charAt(1) !== '=') {
      return false;
    }
    // TODO: check the modifier a bit more.
  }
  return true;
};

// Expose public methods.
if (true) {
  module.exports = SDPUtils;
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/adapter_core.js":
/*!************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/adapter_core.js ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _adapter_factory_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./adapter_factory.js */ "./node_modules/webrtc-adapter/src/js/adapter_factory.js");
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */





const adapter =
  (0,_adapter_factory_js__WEBPACK_IMPORTED_MODULE_0__.adapterFactory)({window: typeof window === 'undefined' ? undefined : window});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (adapter);


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/adapter_factory.js":
/*!***************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/adapter_factory.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "adapterFactory": () => (/* binding */ adapterFactory)
/* harmony export */ });
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils */ "./node_modules/webrtc-adapter/src/js/utils.js");
/* harmony import */ var _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./chrome/chrome_shim */ "./node_modules/webrtc-adapter/src/js/chrome/chrome_shim.js");
/* harmony import */ var _edge_edge_shim__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edge/edge_shim */ "./node_modules/webrtc-adapter/src/js/edge/edge_shim.js");
/* harmony import */ var _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./firefox/firefox_shim */ "./node_modules/webrtc-adapter/src/js/firefox/firefox_shim.js");
/* harmony import */ var _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./safari/safari_shim */ "./node_modules/webrtc-adapter/src/js/safari/safari_shim.js");
/* harmony import */ var _common_shim__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./common_shim */ "./node_modules/webrtc-adapter/src/js/common_shim.js");
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */


  // Browser shims.






// Shimming starts here.
function adapterFactory({window} = {}, options = {
  shimChrome: true,
  shimFirefox: true,
  shimEdge: true,
  shimSafari: true,
}) {
  // Utils.
  const logging = _utils__WEBPACK_IMPORTED_MODULE_0__.log;
  const browserDetails = _utils__WEBPACK_IMPORTED_MODULE_0__.detectBrowser(window);

  const adapter = {
    browserDetails,
    commonShim: _common_shim__WEBPACK_IMPORTED_MODULE_5__,
    extractVersion: _utils__WEBPACK_IMPORTED_MODULE_0__.extractVersion,
    disableLog: _utils__WEBPACK_IMPORTED_MODULE_0__.disableLog,
    disableWarnings: _utils__WEBPACK_IMPORTED_MODULE_0__.disableWarnings
  };

  // Shim browser if found.
  switch (browserDetails.browser) {
    case 'chrome':
      if (!_chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__ || !_chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimPeerConnection ||
          !options.shimChrome) {
        logging('Chrome shim is not included in this adapter release.');
        return adapter;
      }
      if (browserDetails.version === null) {
        logging('Chrome shim can not determine version, not shimming.');
        return adapter;
      }
      logging('adapter.js shimming chrome.');
      // Export to the adapter global object visible in the browser.
      adapter.browserShim = _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__;

      // Must be called before shimPeerConnection.
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimAddIceCandidateNullOrEmpty(window, browserDetails);

      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimGetUserMedia(window, browserDetails);
      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimMediaStream(window, browserDetails);
      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimPeerConnection(window, browserDetails);
      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimOnTrack(window, browserDetails);
      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimAddTrackRemoveTrack(window, browserDetails);
      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimGetSendersWithDtmf(window, browserDetails);
      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimGetStats(window, browserDetails);
      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.shimSenderReceiverGetStats(window, browserDetails);
      _chrome_chrome_shim__WEBPACK_IMPORTED_MODULE_1__.fixNegotiationNeeded(window, browserDetails);

      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimRTCIceCandidate(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimConnectionState(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimMaxMessageSize(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimSendThrowTypeError(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.removeExtmapAllowMixed(window, browserDetails);
      break;
    case 'firefox':
      if (!_firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__ || !_firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimPeerConnection ||
          !options.shimFirefox) {
        logging('Firefox shim is not included in this adapter release.');
        return adapter;
      }
      logging('adapter.js shimming firefox.');
      // Export to the adapter global object visible in the browser.
      adapter.browserShim = _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__;

      // Must be called before shimPeerConnection.
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimAddIceCandidateNullOrEmpty(window, browserDetails);

      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimGetUserMedia(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimPeerConnection(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimOnTrack(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimRemoveStream(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimSenderGetStats(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimReceiverGetStats(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimRTCDataChannel(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimAddTransceiver(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimGetParameters(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimCreateOffer(window, browserDetails);
      _firefox_firefox_shim__WEBPACK_IMPORTED_MODULE_3__.shimCreateAnswer(window, browserDetails);

      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimRTCIceCandidate(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimConnectionState(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimMaxMessageSize(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimSendThrowTypeError(window, browserDetails);
      break;
    case 'edge':
      if (!_edge_edge_shim__WEBPACK_IMPORTED_MODULE_2__ || !_edge_edge_shim__WEBPACK_IMPORTED_MODULE_2__.shimPeerConnection || !options.shimEdge) {
        logging('MS edge shim is not included in this adapter release.');
        return adapter;
      }
      logging('adapter.js shimming edge.');
      // Export to the adapter global object visible in the browser.
      adapter.browserShim = _edge_edge_shim__WEBPACK_IMPORTED_MODULE_2__;

      _edge_edge_shim__WEBPACK_IMPORTED_MODULE_2__.shimGetUserMedia(window, browserDetails);
      _edge_edge_shim__WEBPACK_IMPORTED_MODULE_2__.shimGetDisplayMedia(window, browserDetails);
      _edge_edge_shim__WEBPACK_IMPORTED_MODULE_2__.shimPeerConnection(window, browserDetails);
      _edge_edge_shim__WEBPACK_IMPORTED_MODULE_2__.shimReplaceTrack(window, browserDetails);

      // the edge shim implements the full RTCIceCandidate object.

      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimMaxMessageSize(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimSendThrowTypeError(window, browserDetails);
      break;
    case 'safari':
      if (!_safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__ || !options.shimSafari) {
        logging('Safari shim is not included in this adapter release.');
        return adapter;
      }
      logging('adapter.js shimming safari.');
      // Export to the adapter global object visible in the browser.
      adapter.browserShim = _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__;

      // Must be called before shimCallbackAPI.
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimAddIceCandidateNullOrEmpty(window, browserDetails);

      _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__.shimRTCIceServerUrls(window, browserDetails);
      _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__.shimCreateOfferLegacy(window, browserDetails);
      _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__.shimCallbacksAPI(window, browserDetails);
      _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__.shimLocalStreamsAPI(window, browserDetails);
      _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__.shimRemoteStreamsAPI(window, browserDetails);
      _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__.shimTrackEventTransceiver(window, browserDetails);
      _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__.shimGetUserMedia(window, browserDetails);
      _safari_safari_shim__WEBPACK_IMPORTED_MODULE_4__.shimAudioContext(window, browserDetails);

      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimRTCIceCandidate(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimMaxMessageSize(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.shimSendThrowTypeError(window, browserDetails);
      _common_shim__WEBPACK_IMPORTED_MODULE_5__.removeExtmapAllowMixed(window, browserDetails);
      break;
    default:
      logging('Unsupported browser!');
      break;
  }

  return adapter;
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/chrome/chrome_shim.js":
/*!******************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/chrome/chrome_shim.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetUserMedia": () => (/* reexport safe */ _getusermedia__WEBPACK_IMPORTED_MODULE_1__.shimGetUserMedia),
/* harmony export */   "shimGetDisplayMedia": () => (/* reexport safe */ _getdisplaymedia__WEBPACK_IMPORTED_MODULE_2__.shimGetDisplayMedia),
/* harmony export */   "shimMediaStream": () => (/* binding */ shimMediaStream),
/* harmony export */   "shimOnTrack": () => (/* binding */ shimOnTrack),
/* harmony export */   "shimGetSendersWithDtmf": () => (/* binding */ shimGetSendersWithDtmf),
/* harmony export */   "shimGetStats": () => (/* binding */ shimGetStats),
/* harmony export */   "shimSenderReceiverGetStats": () => (/* binding */ shimSenderReceiverGetStats),
/* harmony export */   "shimAddTrackRemoveTrackWithNative": () => (/* binding */ shimAddTrackRemoveTrackWithNative),
/* harmony export */   "shimAddTrackRemoveTrack": () => (/* binding */ shimAddTrackRemoveTrack),
/* harmony export */   "shimPeerConnection": () => (/* binding */ shimPeerConnection),
/* harmony export */   "fixNegotiationNeeded": () => (/* binding */ fixNegotiationNeeded)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils.js */ "./node_modules/webrtc-adapter/src/js/utils.js");
/* harmony import */ var _getusermedia__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getusermedia */ "./node_modules/webrtc-adapter/src/js/chrome/getusermedia.js");
/* harmony import */ var _getdisplaymedia__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getdisplaymedia */ "./node_modules/webrtc-adapter/src/js/chrome/getdisplaymedia.js");
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */






function shimMediaStream(window) {
  window.MediaStream = window.MediaStream || window.webkitMediaStream;
}

function shimOnTrack(window) {
  if (typeof window === 'object' && window.RTCPeerConnection && !('ontrack' in
      window.RTCPeerConnection.prototype)) {
    Object.defineProperty(window.RTCPeerConnection.prototype, 'ontrack', {
      get() {
        return this._ontrack;
      },
      set(f) {
        if (this._ontrack) {
          this.removeEventListener('track', this._ontrack);
        }
        this.addEventListener('track', this._ontrack = f);
      },
      enumerable: true,
      configurable: true
    });
    const origSetRemoteDescription =
        window.RTCPeerConnection.prototype.setRemoteDescription;
    window.RTCPeerConnection.prototype.setRemoteDescription =
      function setRemoteDescription() {
        if (!this._ontrackpoly) {
          this._ontrackpoly = (e) => {
            // onaddstream does not fire when a track is added to an existing
            // stream. But stream.onaddtrack is implemented so we use that.
            e.stream.addEventListener('addtrack', te => {
              let receiver;
              if (window.RTCPeerConnection.prototype.getReceivers) {
                receiver = this.getReceivers()
                  .find(r => r.track && r.track.id === te.track.id);
              } else {
                receiver = {track: te.track};
              }

              const event = new Event('track');
              event.track = te.track;
              event.receiver = receiver;
              event.transceiver = {receiver};
              event.streams = [e.stream];
              this.dispatchEvent(event);
            });
            e.stream.getTracks().forEach(track => {
              let receiver;
              if (window.RTCPeerConnection.prototype.getReceivers) {
                receiver = this.getReceivers()
                  .find(r => r.track && r.track.id === track.id);
              } else {
                receiver = {track};
              }
              const event = new Event('track');
              event.track = track;
              event.receiver = receiver;
              event.transceiver = {receiver};
              event.streams = [e.stream];
              this.dispatchEvent(event);
            });
          };
          this.addEventListener('addstream', this._ontrackpoly);
        }
        return origSetRemoteDescription.apply(this, arguments);
      };
  } else {
    // even if RTCRtpTransceiver is in window, it is only used and
    // emitted in unified-plan. Unfortunately this means we need
    // to unconditionally wrap the event.
    _utils_js__WEBPACK_IMPORTED_MODULE_0__.wrapPeerConnectionEvent(window, 'track', e => {
      if (!e.transceiver) {
        Object.defineProperty(e, 'transceiver',
          {value: {receiver: e.receiver}});
      }
      return e;
    });
  }
}

function shimGetSendersWithDtmf(window) {
  // Overrides addTrack/removeTrack, depends on shimAddTrackRemoveTrack.
  if (typeof window === 'object' && window.RTCPeerConnection &&
      !('getSenders' in window.RTCPeerConnection.prototype) &&
      'createDTMFSender' in window.RTCPeerConnection.prototype) {
    const shimSenderWithDtmf = function(pc, track) {
      return {
        track,
        get dtmf() {
          if (this._dtmf === undefined) {
            if (track.kind === 'audio') {
              this._dtmf = pc.createDTMFSender(track);
            } else {
              this._dtmf = null;
            }
          }
          return this._dtmf;
        },
        _pc: pc
      };
    };

    // augment addTrack when getSenders is not available.
    if (!window.RTCPeerConnection.prototype.getSenders) {
      window.RTCPeerConnection.prototype.getSenders = function getSenders() {
        this._senders = this._senders || [];
        return this._senders.slice(); // return a copy of the internal state.
      };
      const origAddTrack = window.RTCPeerConnection.prototype.addTrack;
      window.RTCPeerConnection.prototype.addTrack =
        function addTrack(track, stream) {
          let sender = origAddTrack.apply(this, arguments);
          if (!sender) {
            sender = shimSenderWithDtmf(this, track);
            this._senders.push(sender);
          }
          return sender;
        };

      const origRemoveTrack = window.RTCPeerConnection.prototype.removeTrack;
      window.RTCPeerConnection.prototype.removeTrack =
        function removeTrack(sender) {
          origRemoveTrack.apply(this, arguments);
          const idx = this._senders.indexOf(sender);
          if (idx !== -1) {
            this._senders.splice(idx, 1);
          }
        };
    }
    const origAddStream = window.RTCPeerConnection.prototype.addStream;
    window.RTCPeerConnection.prototype.addStream = function addStream(stream) {
      this._senders = this._senders || [];
      origAddStream.apply(this, [stream]);
      stream.getTracks().forEach(track => {
        this._senders.push(shimSenderWithDtmf(this, track));
      });
    };

    const origRemoveStream = window.RTCPeerConnection.prototype.removeStream;
    window.RTCPeerConnection.prototype.removeStream =
      function removeStream(stream) {
        this._senders = this._senders || [];
        origRemoveStream.apply(this, [stream]);

        stream.getTracks().forEach(track => {
          const sender = this._senders.find(s => s.track === track);
          if (sender) { // remove sender
            this._senders.splice(this._senders.indexOf(sender), 1);
          }
        });
      };
  } else if (typeof window === 'object' && window.RTCPeerConnection &&
             'getSenders' in window.RTCPeerConnection.prototype &&
             'createDTMFSender' in window.RTCPeerConnection.prototype &&
             window.RTCRtpSender &&
             !('dtmf' in window.RTCRtpSender.prototype)) {
    const origGetSenders = window.RTCPeerConnection.prototype.getSenders;
    window.RTCPeerConnection.prototype.getSenders = function getSenders() {
      const senders = origGetSenders.apply(this, []);
      senders.forEach(sender => sender._pc = this);
      return senders;
    };

    Object.defineProperty(window.RTCRtpSender.prototype, 'dtmf', {
      get() {
        if (this._dtmf === undefined) {
          if (this.track.kind === 'audio') {
            this._dtmf = this._pc.createDTMFSender(this.track);
          } else {
            this._dtmf = null;
          }
        }
        return this._dtmf;
      }
    });
  }
}

function shimGetStats(window) {
  if (!window.RTCPeerConnection) {
    return;
  }

  const origGetStats = window.RTCPeerConnection.prototype.getStats;
  window.RTCPeerConnection.prototype.getStats = function getStats() {
    const [selector, onSucc, onErr] = arguments;

    // If selector is a function then we are in the old style stats so just
    // pass back the original getStats format to avoid breaking old users.
    if (arguments.length > 0 && typeof selector === 'function') {
      return origGetStats.apply(this, arguments);
    }

    // When spec-style getStats is supported, return those when called with
    // either no arguments or the selector argument is null.
    if (origGetStats.length === 0 && (arguments.length === 0 ||
        typeof selector !== 'function')) {
      return origGetStats.apply(this, []);
    }

    const fixChromeStats_ = function(response) {
      const standardReport = {};
      const reports = response.result();
      reports.forEach(report => {
        const standardStats = {
          id: report.id,
          timestamp: report.timestamp,
          type: {
            localcandidate: 'local-candidate',
            remotecandidate: 'remote-candidate'
          }[report.type] || report.type
        };
        report.names().forEach(name => {
          standardStats[name] = report.stat(name);
        });
        standardReport[standardStats.id] = standardStats;
      });

      return standardReport;
    };

    // shim getStats with maplike support
    const makeMapStats = function(stats) {
      return new Map(Object.keys(stats).map(key => [key, stats[key]]));
    };

    if (arguments.length >= 2) {
      const successCallbackWrapper_ = function(response) {
        onSucc(makeMapStats(fixChromeStats_(response)));
      };

      return origGetStats.apply(this, [successCallbackWrapper_,
        selector]);
    }

    // promise-support
    return new Promise((resolve, reject) => {
      origGetStats.apply(this, [
        function(response) {
          resolve(makeMapStats(fixChromeStats_(response)));
        }, reject]);
    }).then(onSucc, onErr);
  };
}

function shimSenderReceiverGetStats(window) {
  if (!(typeof window === 'object' && window.RTCPeerConnection &&
      window.RTCRtpSender && window.RTCRtpReceiver)) {
    return;
  }

  // shim sender stats.
  if (!('getStats' in window.RTCRtpSender.prototype)) {
    const origGetSenders = window.RTCPeerConnection.prototype.getSenders;
    if (origGetSenders) {
      window.RTCPeerConnection.prototype.getSenders = function getSenders() {
        const senders = origGetSenders.apply(this, []);
        senders.forEach(sender => sender._pc = this);
        return senders;
      };
    }

    const origAddTrack = window.RTCPeerConnection.prototype.addTrack;
    if (origAddTrack) {
      window.RTCPeerConnection.prototype.addTrack = function addTrack() {
        const sender = origAddTrack.apply(this, arguments);
        sender._pc = this;
        return sender;
      };
    }
    window.RTCRtpSender.prototype.getStats = function getStats() {
      const sender = this;
      return this._pc.getStats().then(result =>
        /* Note: this will include stats of all senders that
         *   send a track with the same id as sender.track as
         *   it is not possible to identify the RTCRtpSender.
         */
        _utils_js__WEBPACK_IMPORTED_MODULE_0__.filterStats(result, sender.track, true));
    };
  }

  // shim receiver stats.
  if (!('getStats' in window.RTCRtpReceiver.prototype)) {
    const origGetReceivers = window.RTCPeerConnection.prototype.getReceivers;
    if (origGetReceivers) {
      window.RTCPeerConnection.prototype.getReceivers =
        function getReceivers() {
          const receivers = origGetReceivers.apply(this, []);
          receivers.forEach(receiver => receiver._pc = this);
          return receivers;
        };
    }
    _utils_js__WEBPACK_IMPORTED_MODULE_0__.wrapPeerConnectionEvent(window, 'track', e => {
      e.receiver._pc = e.srcElement;
      return e;
    });
    window.RTCRtpReceiver.prototype.getStats = function getStats() {
      const receiver = this;
      return this._pc.getStats().then(result =>
        _utils_js__WEBPACK_IMPORTED_MODULE_0__.filterStats(result, receiver.track, false));
    };
  }

  if (!('getStats' in window.RTCRtpSender.prototype &&
      'getStats' in window.RTCRtpReceiver.prototype)) {
    return;
  }

  // shim RTCPeerConnection.getStats(track).
  const origGetStats = window.RTCPeerConnection.prototype.getStats;
  window.RTCPeerConnection.prototype.getStats = function getStats() {
    if (arguments.length > 0 &&
        arguments[0] instanceof window.MediaStreamTrack) {
      const track = arguments[0];
      let sender;
      let receiver;
      let err;
      this.getSenders().forEach(s => {
        if (s.track === track) {
          if (sender) {
            err = true;
          } else {
            sender = s;
          }
        }
      });
      this.getReceivers().forEach(r => {
        if (r.track === track) {
          if (receiver) {
            err = true;
          } else {
            receiver = r;
          }
        }
        return r.track === track;
      });
      if (err || (sender && receiver)) {
        return Promise.reject(new DOMException(
          'There are more than one sender or receiver for the track.',
          'InvalidAccessError'));
      } else if (sender) {
        return sender.getStats();
      } else if (receiver) {
        return receiver.getStats();
      }
      return Promise.reject(new DOMException(
        'There is no sender or receiver for the track.',
        'InvalidAccessError'));
    }
    return origGetStats.apply(this, arguments);
  };
}

function shimAddTrackRemoveTrackWithNative(window) {
  // shim addTrack/removeTrack with native variants in order to make
  // the interactions with legacy getLocalStreams behave as in other browsers.
  // Keeps a mapping stream.id => [stream, rtpsenders...]
  window.RTCPeerConnection.prototype.getLocalStreams =
    function getLocalStreams() {
      this._shimmedLocalStreams = this._shimmedLocalStreams || {};
      return Object.keys(this._shimmedLocalStreams)
        .map(streamId => this._shimmedLocalStreams[streamId][0]);
    };

  const origAddTrack = window.RTCPeerConnection.prototype.addTrack;
  window.RTCPeerConnection.prototype.addTrack =
    function addTrack(track, stream) {
      if (!stream) {
        return origAddTrack.apply(this, arguments);
      }
      this._shimmedLocalStreams = this._shimmedLocalStreams || {};

      const sender = origAddTrack.apply(this, arguments);
      if (!this._shimmedLocalStreams[stream.id]) {
        this._shimmedLocalStreams[stream.id] = [stream, sender];
      } else if (this._shimmedLocalStreams[stream.id].indexOf(sender) === -1) {
        this._shimmedLocalStreams[stream.id].push(sender);
      }
      return sender;
    };

  const origAddStream = window.RTCPeerConnection.prototype.addStream;
  window.RTCPeerConnection.prototype.addStream = function addStream(stream) {
    this._shimmedLocalStreams = this._shimmedLocalStreams || {};

    stream.getTracks().forEach(track => {
      const alreadyExists = this.getSenders().find(s => s.track === track);
      if (alreadyExists) {
        throw new DOMException('Track already exists.',
            'InvalidAccessError');
      }
    });
    const existingSenders = this.getSenders();
    origAddStream.apply(this, arguments);
    const newSenders = this.getSenders()
      .filter(newSender => existingSenders.indexOf(newSender) === -1);
    this._shimmedLocalStreams[stream.id] = [stream].concat(newSenders);
  };

  const origRemoveStream = window.RTCPeerConnection.prototype.removeStream;
  window.RTCPeerConnection.prototype.removeStream =
    function removeStream(stream) {
      this._shimmedLocalStreams = this._shimmedLocalStreams || {};
      delete this._shimmedLocalStreams[stream.id];
      return origRemoveStream.apply(this, arguments);
    };

  const origRemoveTrack = window.RTCPeerConnection.prototype.removeTrack;
  window.RTCPeerConnection.prototype.removeTrack =
    function removeTrack(sender) {
      this._shimmedLocalStreams = this._shimmedLocalStreams || {};
      if (sender) {
        Object.keys(this._shimmedLocalStreams).forEach(streamId => {
          const idx = this._shimmedLocalStreams[streamId].indexOf(sender);
          if (idx !== -1) {
            this._shimmedLocalStreams[streamId].splice(idx, 1);
          }
          if (this._shimmedLocalStreams[streamId].length === 1) {
            delete this._shimmedLocalStreams[streamId];
          }
        });
      }
      return origRemoveTrack.apply(this, arguments);
    };
}

function shimAddTrackRemoveTrack(window, browserDetails) {
  if (!window.RTCPeerConnection) {
    return;
  }
  // shim addTrack and removeTrack.
  if (window.RTCPeerConnection.prototype.addTrack &&
      browserDetails.version >= 65) {
    return shimAddTrackRemoveTrackWithNative(window);
  }

  // also shim pc.getLocalStreams when addTrack is shimmed
  // to return the original streams.
  const origGetLocalStreams = window.RTCPeerConnection.prototype
      .getLocalStreams;
  window.RTCPeerConnection.prototype.getLocalStreams =
    function getLocalStreams() {
      const nativeStreams = origGetLocalStreams.apply(this);
      this._reverseStreams = this._reverseStreams || {};
      return nativeStreams.map(stream => this._reverseStreams[stream.id]);
    };

  const origAddStream = window.RTCPeerConnection.prototype.addStream;
  window.RTCPeerConnection.prototype.addStream = function addStream(stream) {
    this._streams = this._streams || {};
    this._reverseStreams = this._reverseStreams || {};

    stream.getTracks().forEach(track => {
      const alreadyExists = this.getSenders().find(s => s.track === track);
      if (alreadyExists) {
        throw new DOMException('Track already exists.',
            'InvalidAccessError');
      }
    });
    // Add identity mapping for consistency with addTrack.
    // Unless this is being used with a stream from addTrack.
    if (!this._reverseStreams[stream.id]) {
      const newStream = new window.MediaStream(stream.getTracks());
      this._streams[stream.id] = newStream;
      this._reverseStreams[newStream.id] = stream;
      stream = newStream;
    }
    origAddStream.apply(this, [stream]);
  };

  const origRemoveStream = window.RTCPeerConnection.prototype.removeStream;
  window.RTCPeerConnection.prototype.removeStream =
    function removeStream(stream) {
      this._streams = this._streams || {};
      this._reverseStreams = this._reverseStreams || {};

      origRemoveStream.apply(this, [(this._streams[stream.id] || stream)]);
      delete this._reverseStreams[(this._streams[stream.id] ?
          this._streams[stream.id].id : stream.id)];
      delete this._streams[stream.id];
    };

  window.RTCPeerConnection.prototype.addTrack =
    function addTrack(track, stream) {
      if (this.signalingState === 'closed') {
        throw new DOMException(
          'The RTCPeerConnection\'s signalingState is \'closed\'.',
          'InvalidStateError');
      }
      const streams = [].slice.call(arguments, 1);
      if (streams.length !== 1 ||
          !streams[0].getTracks().find(t => t === track)) {
        // this is not fully correct but all we can manage without
        // [[associated MediaStreams]] internal slot.
        throw new DOMException(
          'The adapter.js addTrack polyfill only supports a single ' +
          ' stream which is associated with the specified track.',
          'NotSupportedError');
      }

      const alreadyExists = this.getSenders().find(s => s.track === track);
      if (alreadyExists) {
        throw new DOMException('Track already exists.',
            'InvalidAccessError');
      }

      this._streams = this._streams || {};
      this._reverseStreams = this._reverseStreams || {};
      const oldStream = this._streams[stream.id];
      if (oldStream) {
        // this is using odd Chrome behaviour, use with caution:
        // https://bugs.chromium.org/p/webrtc/issues/detail?id=7815
        // Note: we rely on the high-level addTrack/dtmf shim to
        // create the sender with a dtmf sender.
        oldStream.addTrack(track);

        // Trigger ONN async.
        Promise.resolve().then(() => {
          this.dispatchEvent(new Event('negotiationneeded'));
        });
      } else {
        const newStream = new window.MediaStream([track]);
        this._streams[stream.id] = newStream;
        this._reverseStreams[newStream.id] = stream;
        this.addStream(newStream);
      }
      return this.getSenders().find(s => s.track === track);
    };

  // replace the internal stream id with the external one and
  // vice versa.
  function replaceInternalStreamId(pc, description) {
    let sdp = description.sdp;
    Object.keys(pc._reverseStreams || []).forEach(internalId => {
      const externalStream = pc._reverseStreams[internalId];
      const internalStream = pc._streams[externalStream.id];
      sdp = sdp.replace(new RegExp(internalStream.id, 'g'),
          externalStream.id);
    });
    return new RTCSessionDescription({
      type: description.type,
      sdp
    });
  }
  function replaceExternalStreamId(pc, description) {
    let sdp = description.sdp;
    Object.keys(pc._reverseStreams || []).forEach(internalId => {
      const externalStream = pc._reverseStreams[internalId];
      const internalStream = pc._streams[externalStream.id];
      sdp = sdp.replace(new RegExp(externalStream.id, 'g'),
          internalStream.id);
    });
    return new RTCSessionDescription({
      type: description.type,
      sdp
    });
  }
  ['createOffer', 'createAnswer'].forEach(function(method) {
    const nativeMethod = window.RTCPeerConnection.prototype[method];
    const methodObj = {[method]() {
      const args = arguments;
      const isLegacyCall = arguments.length &&
          typeof arguments[0] === 'function';
      if (isLegacyCall) {
        return nativeMethod.apply(this, [
          (description) => {
            const desc = replaceInternalStreamId(this, description);
            args[0].apply(null, [desc]);
          },
          (err) => {
            if (args[1]) {
              args[1].apply(null, err);
            }
          }, arguments[2]
        ]);
      }
      return nativeMethod.apply(this, arguments)
      .then(description => replaceInternalStreamId(this, description));
    }};
    window.RTCPeerConnection.prototype[method] = methodObj[method];
  });

  const origSetLocalDescription =
      window.RTCPeerConnection.prototype.setLocalDescription;
  window.RTCPeerConnection.prototype.setLocalDescription =
    function setLocalDescription() {
      if (!arguments.length || !arguments[0].type) {
        return origSetLocalDescription.apply(this, arguments);
      }
      arguments[0] = replaceExternalStreamId(this, arguments[0]);
      return origSetLocalDescription.apply(this, arguments);
    };

  // TODO: mangle getStats: https://w3c.github.io/webrtc-stats/#dom-rtcmediastreamstats-streamidentifier

  const origLocalDescription = Object.getOwnPropertyDescriptor(
      window.RTCPeerConnection.prototype, 'localDescription');
  Object.defineProperty(window.RTCPeerConnection.prototype,
      'localDescription', {
        get() {
          const description = origLocalDescription.get.apply(this);
          if (description.type === '') {
            return description;
          }
          return replaceInternalStreamId(this, description);
        }
      });

  window.RTCPeerConnection.prototype.removeTrack =
    function removeTrack(sender) {
      if (this.signalingState === 'closed') {
        throw new DOMException(
          'The RTCPeerConnection\'s signalingState is \'closed\'.',
          'InvalidStateError');
      }
      // We can not yet check for sender instanceof RTCRtpSender
      // since we shim RTPSender. So we check if sender._pc is set.
      if (!sender._pc) {
        throw new DOMException('Argument 1 of RTCPeerConnection.removeTrack ' +
            'does not implement interface RTCRtpSender.', 'TypeError');
      }
      const isLocal = sender._pc === this;
      if (!isLocal) {
        throw new DOMException('Sender was not created by this connection.',
            'InvalidAccessError');
      }

      // Search for the native stream the senders track belongs to.
      this._streams = this._streams || {};
      let stream;
      Object.keys(this._streams).forEach(streamid => {
        const hasTrack = this._streams[streamid].getTracks()
          .find(track => sender.track === track);
        if (hasTrack) {
          stream = this._streams[streamid];
        }
      });

      if (stream) {
        if (stream.getTracks().length === 1) {
          // if this is the last track of the stream, remove the stream. This
          // takes care of any shimmed _senders.
          this.removeStream(this._reverseStreams[stream.id]);
        } else {
          // relying on the same odd chrome behaviour as above.
          stream.removeTrack(sender.track);
        }
        this.dispatchEvent(new Event('negotiationneeded'));
      }
    };
}

function shimPeerConnection(window, browserDetails) {
  if (!window.RTCPeerConnection && window.webkitRTCPeerConnection) {
    // very basic support for old versions.
    window.RTCPeerConnection = window.webkitRTCPeerConnection;
  }
  if (!window.RTCPeerConnection) {
    return;
  }

  // shim implicit creation of RTCSessionDescription/RTCIceCandidate
  if (browserDetails.version < 53) {
    ['setLocalDescription', 'setRemoteDescription', 'addIceCandidate']
        .forEach(function(method) {
          const nativeMethod = window.RTCPeerConnection.prototype[method];
          const methodObj = {[method]() {
            arguments[0] = new ((method === 'addIceCandidate') ?
                window.RTCIceCandidate :
                window.RTCSessionDescription)(arguments[0]);
            return nativeMethod.apply(this, arguments);
          }};
          window.RTCPeerConnection.prototype[method] = methodObj[method];
        });
  }
}

// Attempt to fix ONN in plan-b mode.
function fixNegotiationNeeded(window, browserDetails) {
  _utils_js__WEBPACK_IMPORTED_MODULE_0__.wrapPeerConnectionEvent(window, 'negotiationneeded', e => {
    const pc = e.target;
    if (browserDetails.version < 72 || (pc.getConfiguration &&
        pc.getConfiguration().sdpSemantics === 'plan-b')) {
      if (pc.signalingState !== 'stable') {
        return;
      }
    }
    return e;
  });
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/chrome/getdisplaymedia.js":
/*!**********************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/chrome/getdisplaymedia.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetDisplayMedia": () => (/* binding */ shimGetDisplayMedia)
/* harmony export */ });
/*
 *  Copyright (c) 2018 The adapter.js project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */

function shimGetDisplayMedia(window, getSourceId) {
  if (window.navigator.mediaDevices &&
    'getDisplayMedia' in window.navigator.mediaDevices) {
    return;
  }
  if (!(window.navigator.mediaDevices)) {
    return;
  }
  // getSourceId is a function that returns a promise resolving with
  // the sourceId of the screen/window/tab to be shared.
  if (typeof getSourceId !== 'function') {
    console.error('shimGetDisplayMedia: getSourceId argument is not ' +
        'a function');
    return;
  }
  window.navigator.mediaDevices.getDisplayMedia =
    function getDisplayMedia(constraints) {
      return getSourceId(constraints)
        .then(sourceId => {
          const widthSpecified = constraints.video && constraints.video.width;
          const heightSpecified = constraints.video &&
            constraints.video.height;
          const frameRateSpecified = constraints.video &&
            constraints.video.frameRate;
          constraints.video = {
            mandatory: {
              chromeMediaSource: 'desktop',
              chromeMediaSourceId: sourceId,
              maxFrameRate: frameRateSpecified || 3
            }
          };
          if (widthSpecified) {
            constraints.video.mandatory.maxWidth = widthSpecified;
          }
          if (heightSpecified) {
            constraints.video.mandatory.maxHeight = heightSpecified;
          }
          return window.navigator.mediaDevices.getUserMedia(constraints);
        });
    };
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/chrome/getusermedia.js":
/*!*******************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/chrome/getusermedia.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetUserMedia": () => (/* binding */ shimGetUserMedia)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils.js */ "./node_modules/webrtc-adapter/src/js/utils.js");
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */


const logging = _utils_js__WEBPACK_IMPORTED_MODULE_0__.log;

function shimGetUserMedia(window, browserDetails) {
  const navigator = window && window.navigator;

  if (!navigator.mediaDevices) {
    return;
  }

  const constraintsToChrome_ = function(c) {
    if (typeof c !== 'object' || c.mandatory || c.optional) {
      return c;
    }
    const cc = {};
    Object.keys(c).forEach(key => {
      if (key === 'require' || key === 'advanced' || key === 'mediaSource') {
        return;
      }
      const r = (typeof c[key] === 'object') ? c[key] : {ideal: c[key]};
      if (r.exact !== undefined && typeof r.exact === 'number') {
        r.min = r.max = r.exact;
      }
      const oldname_ = function(prefix, name) {
        if (prefix) {
          return prefix + name.charAt(0).toUpperCase() + name.slice(1);
        }
        return (name === 'deviceId') ? 'sourceId' : name;
      };
      if (r.ideal !== undefined) {
        cc.optional = cc.optional || [];
        let oc = {};
        if (typeof r.ideal === 'number') {
          oc[oldname_('min', key)] = r.ideal;
          cc.optional.push(oc);
          oc = {};
          oc[oldname_('max', key)] = r.ideal;
          cc.optional.push(oc);
        } else {
          oc[oldname_('', key)] = r.ideal;
          cc.optional.push(oc);
        }
      }
      if (r.exact !== undefined && typeof r.exact !== 'number') {
        cc.mandatory = cc.mandatory || {};
        cc.mandatory[oldname_('', key)] = r.exact;
      } else {
        ['min', 'max'].forEach(mix => {
          if (r[mix] !== undefined) {
            cc.mandatory = cc.mandatory || {};
            cc.mandatory[oldname_(mix, key)] = r[mix];
          }
        });
      }
    });
    if (c.advanced) {
      cc.optional = (cc.optional || []).concat(c.advanced);
    }
    return cc;
  };

  const shimConstraints_ = function(constraints, func) {
    if (browserDetails.version >= 61) {
      return func(constraints);
    }
    constraints = JSON.parse(JSON.stringify(constraints));
    if (constraints && typeof constraints.audio === 'object') {
      const remap = function(obj, a, b) {
        if (a in obj && !(b in obj)) {
          obj[b] = obj[a];
          delete obj[a];
        }
      };
      constraints = JSON.parse(JSON.stringify(constraints));
      remap(constraints.audio, 'autoGainControl', 'googAutoGainControl');
      remap(constraints.audio, 'noiseSuppression', 'googNoiseSuppression');
      constraints.audio = constraintsToChrome_(constraints.audio);
    }
    if (constraints && typeof constraints.video === 'object') {
      // Shim facingMode for mobile & surface pro.
      let face = constraints.video.facingMode;
      face = face && ((typeof face === 'object') ? face : {ideal: face});
      const getSupportedFacingModeLies = browserDetails.version < 66;

      if ((face && (face.exact === 'user' || face.exact === 'environment' ||
                    face.ideal === 'user' || face.ideal === 'environment')) &&
          !(navigator.mediaDevices.getSupportedConstraints &&
            navigator.mediaDevices.getSupportedConstraints().facingMode &&
            !getSupportedFacingModeLies)) {
        delete constraints.video.facingMode;
        let matches;
        if (face.exact === 'environment' || face.ideal === 'environment') {
          matches = ['back', 'rear'];
        } else if (face.exact === 'user' || face.ideal === 'user') {
          matches = ['front'];
        }
        if (matches) {
          // Look for matches in label, or use last cam for back (typical).
          return navigator.mediaDevices.enumerateDevices()
          .then(devices => {
            devices = devices.filter(d => d.kind === 'videoinput');
            let dev = devices.find(d => matches.some(match =>
              d.label.toLowerCase().includes(match)));
            if (!dev && devices.length && matches.includes('back')) {
              dev = devices[devices.length - 1]; // more likely the back cam
            }
            if (dev) {
              constraints.video.deviceId = face.exact ? {exact: dev.deviceId} :
                                                        {ideal: dev.deviceId};
            }
            constraints.video = constraintsToChrome_(constraints.video);
            logging('chrome: ' + JSON.stringify(constraints));
            return func(constraints);
          });
        }
      }
      constraints.video = constraintsToChrome_(constraints.video);
    }
    logging('chrome: ' + JSON.stringify(constraints));
    return func(constraints);
  };

  const shimError_ = function(e) {
    if (browserDetails.version >= 64) {
      return e;
    }
    return {
      name: {
        PermissionDeniedError: 'NotAllowedError',
        PermissionDismissedError: 'NotAllowedError',
        InvalidStateError: 'NotAllowedError',
        DevicesNotFoundError: 'NotFoundError',
        ConstraintNotSatisfiedError: 'OverconstrainedError',
        TrackStartError: 'NotReadableError',
        MediaDeviceFailedDueToShutdown: 'NotAllowedError',
        MediaDeviceKillSwitchOn: 'NotAllowedError',
        TabCaptureError: 'AbortError',
        ScreenCaptureError: 'AbortError',
        DeviceCaptureError: 'AbortError'
      }[e.name] || e.name,
      message: e.message,
      constraint: e.constraint || e.constraintName,
      toString() {
        return this.name + (this.message && ': ') + this.message;
      }
    };
  };

  const getUserMedia_ = function(constraints, onSuccess, onError) {
    shimConstraints_(constraints, c => {
      navigator.webkitGetUserMedia(c, onSuccess, e => {
        if (onError) {
          onError(shimError_(e));
        }
      });
    });
  };
  navigator.getUserMedia = getUserMedia_.bind(navigator);

  // Even though Chrome 45 has navigator.mediaDevices and a getUserMedia
  // function which returns a Promise, it does not accept spec-style
  // constraints.
  if (navigator.mediaDevices.getUserMedia) {
    const origGetUserMedia = navigator.mediaDevices.getUserMedia.
        bind(navigator.mediaDevices);
    navigator.mediaDevices.getUserMedia = function(cs) {
      return shimConstraints_(cs, c => origGetUserMedia(c).then(stream => {
        if (c.audio && !stream.getAudioTracks().length ||
            c.video && !stream.getVideoTracks().length) {
          stream.getTracks().forEach(track => {
            track.stop();
          });
          throw new DOMException('', 'NotFoundError');
        }
        return stream;
      }, e => Promise.reject(shimError_(e))));
    };
  }
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/common_shim.js":
/*!***********************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/common_shim.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimRTCIceCandidate": () => (/* binding */ shimRTCIceCandidate),
/* harmony export */   "shimMaxMessageSize": () => (/* binding */ shimMaxMessageSize),
/* harmony export */   "shimSendThrowTypeError": () => (/* binding */ shimSendThrowTypeError),
/* harmony export */   "shimConnectionState": () => (/* binding */ shimConnectionState),
/* harmony export */   "removeExtmapAllowMixed": () => (/* binding */ removeExtmapAllowMixed),
/* harmony export */   "shimAddIceCandidateNullOrEmpty": () => (/* binding */ shimAddIceCandidateNullOrEmpty)
/* harmony export */ });
/* harmony import */ var sdp__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! sdp */ "./node_modules/sdp/sdp.js");
/* harmony import */ var sdp__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(sdp__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils */ "./node_modules/webrtc-adapter/src/js/utils.js");
/*
 *  Copyright (c) 2017 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */





function shimRTCIceCandidate(window) {
  // foundation is arbitrarily chosen as an indicator for full support for
  // https://w3c.github.io/webrtc-pc/#rtcicecandidate-interface
  if (!window.RTCIceCandidate || (window.RTCIceCandidate && 'foundation' in
      window.RTCIceCandidate.prototype)) {
    return;
  }

  const NativeRTCIceCandidate = window.RTCIceCandidate;
  window.RTCIceCandidate = function RTCIceCandidate(args) {
    // Remove the a= which shouldn't be part of the candidate string.
    if (typeof args === 'object' && args.candidate &&
        args.candidate.indexOf('a=') === 0) {
      args = JSON.parse(JSON.stringify(args));
      args.candidate = args.candidate.substr(2);
    }

    if (args.candidate && args.candidate.length) {
      // Augment the native candidate with the parsed fields.
      const nativeCandidate = new NativeRTCIceCandidate(args);
      const parsedCandidate = sdp__WEBPACK_IMPORTED_MODULE_0___default().parseCandidate(args.candidate);
      const augmentedCandidate = Object.assign(nativeCandidate,
          parsedCandidate);

      // Add a serializer that does not serialize the extra attributes.
      augmentedCandidate.toJSON = function toJSON() {
        return {
          candidate: augmentedCandidate.candidate,
          sdpMid: augmentedCandidate.sdpMid,
          sdpMLineIndex: augmentedCandidate.sdpMLineIndex,
          usernameFragment: augmentedCandidate.usernameFragment,
        };
      };
      return augmentedCandidate;
    }
    return new NativeRTCIceCandidate(args);
  };
  window.RTCIceCandidate.prototype = NativeRTCIceCandidate.prototype;

  // Hook up the augmented candidate in onicecandidate and
  // addEventListener('icecandidate', ...)
  _utils__WEBPACK_IMPORTED_MODULE_1__.wrapPeerConnectionEvent(window, 'icecandidate', e => {
    if (e.candidate) {
      Object.defineProperty(e, 'candidate', {
        value: new window.RTCIceCandidate(e.candidate),
        writable: 'false'
      });
    }
    return e;
  });
}

function shimMaxMessageSize(window, browserDetails) {
  if (!window.RTCPeerConnection) {
    return;
  }

  if (!('sctp' in window.RTCPeerConnection.prototype)) {
    Object.defineProperty(window.RTCPeerConnection.prototype, 'sctp', {
      get() {
        return typeof this._sctp === 'undefined' ? null : this._sctp;
      }
    });
  }

  const sctpInDescription = function(description) {
    if (!description || !description.sdp) {
      return false;
    }
    const sections = sdp__WEBPACK_IMPORTED_MODULE_0___default().splitSections(description.sdp);
    sections.shift();
    return sections.some(mediaSection => {
      const mLine = sdp__WEBPACK_IMPORTED_MODULE_0___default().parseMLine(mediaSection);
      return mLine && mLine.kind === 'application'
          && mLine.protocol.indexOf('SCTP') !== -1;
    });
  };

  const getRemoteFirefoxVersion = function(description) {
    // TODO: Is there a better solution for detecting Firefox?
    const match = description.sdp.match(/mozilla...THIS_IS_SDPARTA-(\d+)/);
    if (match === null || match.length < 2) {
      return -1;
    }
    const version = parseInt(match[1], 10);
    // Test for NaN (yes, this is ugly)
    return version !== version ? -1 : version;
  };

  const getCanSendMaxMessageSize = function(remoteIsFirefox) {
    // Every implementation we know can send at least 64 KiB.
    // Note: Although Chrome is technically able to send up to 256 KiB, the
    //       data does not reach the other peer reliably.
    //       See: https://bugs.chromium.org/p/webrtc/issues/detail?id=8419
    let canSendMaxMessageSize = 65536;
    if (browserDetails.browser === 'firefox') {
      if (browserDetails.version < 57) {
        if (remoteIsFirefox === -1) {
          // FF < 57 will send in 16 KiB chunks using the deprecated PPID
          // fragmentation.
          canSendMaxMessageSize = 16384;
        } else {
          // However, other FF (and RAWRTC) can reassemble PPID-fragmented
          // messages. Thus, supporting ~2 GiB when sending.
          canSendMaxMessageSize = 2147483637;
        }
      } else if (browserDetails.version < 60) {
        // Currently, all FF >= 57 will reset the remote maximum message size
        // to the default value when a data channel is created at a later
        // stage. :(
        // See: https://bugzilla.mozilla.org/show_bug.cgi?id=1426831
        canSendMaxMessageSize =
          browserDetails.version === 57 ? 65535 : 65536;
      } else {
        // FF >= 60 supports sending ~2 GiB
        canSendMaxMessageSize = 2147483637;
      }
    }
    return canSendMaxMessageSize;
  };

  const getMaxMessageSize = function(description, remoteIsFirefox) {
    // Note: 65536 bytes is the default value from the SDP spec. Also,
    //       every implementation we know supports receiving 65536 bytes.
    let maxMessageSize = 65536;

    // FF 57 has a slightly incorrect default remote max message size, so
    // we need to adjust it here to avoid a failure when sending.
    // See: https://bugzilla.mozilla.org/show_bug.cgi?id=1425697
    if (browserDetails.browser === 'firefox'
         && browserDetails.version === 57) {
      maxMessageSize = 65535;
    }

    const match = sdp__WEBPACK_IMPORTED_MODULE_0___default().matchPrefix(description.sdp,
      'a=max-message-size:');
    if (match.length > 0) {
      maxMessageSize = parseInt(match[0].substr(19), 10);
    } else if (browserDetails.browser === 'firefox' &&
                remoteIsFirefox !== -1) {
      // If the maximum message size is not present in the remote SDP and
      // both local and remote are Firefox, the remote peer can receive
      // ~2 GiB.
      maxMessageSize = 2147483637;
    }
    return maxMessageSize;
  };

  const origSetRemoteDescription =
      window.RTCPeerConnection.prototype.setRemoteDescription;
  window.RTCPeerConnection.prototype.setRemoteDescription =
    function setRemoteDescription() {
      this._sctp = null;
      // Chrome decided to not expose .sctp in plan-b mode.
      // As usual, adapter.js has to do an 'ugly worakaround'
      // to cover up the mess.
      if (browserDetails.browser === 'chrome' && browserDetails.version >= 76) {
        const {sdpSemantics} = this.getConfiguration();
        if (sdpSemantics === 'plan-b') {
          Object.defineProperty(this, 'sctp', {
            get() {
              return typeof this._sctp === 'undefined' ? null : this._sctp;
            },
            enumerable: true,
            configurable: true,
          });
        }
      }

      if (sctpInDescription(arguments[0])) {
        // Check if the remote is FF.
        const isFirefox = getRemoteFirefoxVersion(arguments[0]);

        // Get the maximum message size the local peer is capable of sending
        const canSendMMS = getCanSendMaxMessageSize(isFirefox);

        // Get the maximum message size of the remote peer.
        const remoteMMS = getMaxMessageSize(arguments[0], isFirefox);

        // Determine final maximum message size
        let maxMessageSize;
        if (canSendMMS === 0 && remoteMMS === 0) {
          maxMessageSize = Number.POSITIVE_INFINITY;
        } else if (canSendMMS === 0 || remoteMMS === 0) {
          maxMessageSize = Math.max(canSendMMS, remoteMMS);
        } else {
          maxMessageSize = Math.min(canSendMMS, remoteMMS);
        }

        // Create a dummy RTCSctpTransport object and the 'maxMessageSize'
        // attribute.
        const sctp = {};
        Object.defineProperty(sctp, 'maxMessageSize', {
          get() {
            return maxMessageSize;
          }
        });
        this._sctp = sctp;
      }

      return origSetRemoteDescription.apply(this, arguments);
    };
}

function shimSendThrowTypeError(window) {
  if (!(window.RTCPeerConnection &&
      'createDataChannel' in window.RTCPeerConnection.prototype)) {
    return;
  }

  // Note: Although Firefox >= 57 has a native implementation, the maximum
  //       message size can be reset for all data channels at a later stage.
  //       See: https://bugzilla.mozilla.org/show_bug.cgi?id=1426831

  function wrapDcSend(dc, pc) {
    const origDataChannelSend = dc.send;
    dc.send = function send() {
      const data = arguments[0];
      const length = data.length || data.size || data.byteLength;
      if (dc.readyState === 'open' &&
          pc.sctp && length > pc.sctp.maxMessageSize) {
        throw new TypeError('Message too large (can send a maximum of ' +
          pc.sctp.maxMessageSize + ' bytes)');
      }
      return origDataChannelSend.apply(dc, arguments);
    };
  }
  const origCreateDataChannel =
    window.RTCPeerConnection.prototype.createDataChannel;
  window.RTCPeerConnection.prototype.createDataChannel =
    function createDataChannel() {
      const dataChannel = origCreateDataChannel.apply(this, arguments);
      wrapDcSend(dataChannel, this);
      return dataChannel;
    };
  _utils__WEBPACK_IMPORTED_MODULE_1__.wrapPeerConnectionEvent(window, 'datachannel', e => {
    wrapDcSend(e.channel, e.target);
    return e;
  });
}


/* shims RTCConnectionState by pretending it is the same as iceConnectionState.
 * See https://bugs.chromium.org/p/webrtc/issues/detail?id=6145#c12
 * for why this is a valid hack in Chrome. In Firefox it is slightly incorrect
 * since DTLS failures would be hidden. See
 * https://bugzilla.mozilla.org/show_bug.cgi?id=1265827
 * for the Firefox tracking bug.
 */
function shimConnectionState(window) {
  if (!window.RTCPeerConnection ||
      'connectionState' in window.RTCPeerConnection.prototype) {
    return;
  }
  const proto = window.RTCPeerConnection.prototype;
  Object.defineProperty(proto, 'connectionState', {
    get() {
      return {
        completed: 'connected',
        checking: 'connecting'
      }[this.iceConnectionState] || this.iceConnectionState;
    },
    enumerable: true,
    configurable: true
  });
  Object.defineProperty(proto, 'onconnectionstatechange', {
    get() {
      return this._onconnectionstatechange || null;
    },
    set(cb) {
      if (this._onconnectionstatechange) {
        this.removeEventListener('connectionstatechange',
            this._onconnectionstatechange);
        delete this._onconnectionstatechange;
      }
      if (cb) {
        this.addEventListener('connectionstatechange',
            this._onconnectionstatechange = cb);
      }
    },
    enumerable: true,
    configurable: true
  });

  ['setLocalDescription', 'setRemoteDescription'].forEach((method) => {
    const origMethod = proto[method];
    proto[method] = function() {
      if (!this._connectionstatechangepoly) {
        this._connectionstatechangepoly = e => {
          const pc = e.target;
          if (pc._lastConnectionState !== pc.connectionState) {
            pc._lastConnectionState = pc.connectionState;
            const newEvent = new Event('connectionstatechange', e);
            pc.dispatchEvent(newEvent);
          }
          return e;
        };
        this.addEventListener('iceconnectionstatechange',
          this._connectionstatechangepoly);
      }
      return origMethod.apply(this, arguments);
    };
  });
}

function removeExtmapAllowMixed(window, browserDetails) {
  /* remove a=extmap-allow-mixed for webrtc.org < M71 */
  if (!window.RTCPeerConnection) {
    return;
  }
  if (browserDetails.browser === 'chrome' && browserDetails.version >= 71) {
    return;
  }
  if (browserDetails.browser === 'safari' && browserDetails.version >= 605) {
    return;
  }
  const nativeSRD = window.RTCPeerConnection.prototype.setRemoteDescription;
  window.RTCPeerConnection.prototype.setRemoteDescription =
  function setRemoteDescription(desc) {
    if (desc && desc.sdp && desc.sdp.indexOf('\na=extmap-allow-mixed') !== -1) {
      const sdp = desc.sdp.split('\n').filter((line) => {
        return line.trim() !== 'a=extmap-allow-mixed';
      }).join('\n');
      // Safari enforces read-only-ness of RTCSessionDescription fields.
      if (window.RTCSessionDescription &&
          desc instanceof window.RTCSessionDescription) {
        arguments[0] = new window.RTCSessionDescription({
          type: desc.type,
          sdp,
        });
      } else {
        desc.sdp = sdp;
      }
    }
    return nativeSRD.apply(this, arguments);
  };
}

function shimAddIceCandidateNullOrEmpty(window, browserDetails) {
  // Support for addIceCandidate(null or undefined)
  // as well as addIceCandidate({candidate: "", ...})
  // https://bugs.chromium.org/p/chromium/issues/detail?id=978582
  // Note: must be called before other polyfills which change the signature.
  if (!(window.RTCPeerConnection && window.RTCPeerConnection.prototype)) {
    return;
  }
  const nativeAddIceCandidate =
      window.RTCPeerConnection.prototype.addIceCandidate;
  if (!nativeAddIceCandidate || nativeAddIceCandidate.length === 0) {
    return;
  }
  window.RTCPeerConnection.prototype.addIceCandidate =
    function addIceCandidate() {
      if (!arguments[0]) {
        if (arguments[1]) {
          arguments[1].apply(null);
        }
        return Promise.resolve();
      }
      // Firefox 68+ emits and processes {candidate: "", ...}, ignore
      // in older versions.
      // Native support for ignoring exists for Chrome M77+.
      // Safari ignores as well, exact version unknown but works in the same
      // version that also ignores addIceCandidate(null).
      if (((browserDetails.browser === 'chrome' && browserDetails.version < 78)
           || (browserDetails.browser === 'firefox'
               && browserDetails.version < 68)
           || (browserDetails.browser === 'safari'))
          && arguments[0] && arguments[0].candidate === '') {
        return Promise.resolve();
      }
      return nativeAddIceCandidate.apply(this, arguments);
    };
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/edge/edge_shim.js":
/*!**************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/edge/edge_shim.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetUserMedia": () => (/* reexport safe */ _getusermedia__WEBPACK_IMPORTED_MODULE_3__.shimGetUserMedia),
/* harmony export */   "shimGetDisplayMedia": () => (/* reexport safe */ _getdisplaymedia__WEBPACK_IMPORTED_MODULE_4__.shimGetDisplayMedia),
/* harmony export */   "shimPeerConnection": () => (/* binding */ shimPeerConnection),
/* harmony export */   "shimReplaceTrack": () => (/* binding */ shimReplaceTrack)
/* harmony export */ });
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils */ "./node_modules/webrtc-adapter/src/js/utils.js");
/* harmony import */ var _filtericeservers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./filtericeservers */ "./node_modules/webrtc-adapter/src/js/edge/filtericeservers.js");
/* harmony import */ var rtcpeerconnection_shim__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! rtcpeerconnection-shim */ "./node_modules/rtcpeerconnection-shim/rtcpeerconnection.js");
/* harmony import */ var rtcpeerconnection_shim__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(rtcpeerconnection_shim__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _getusermedia__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./getusermedia */ "./node_modules/webrtc-adapter/src/js/edge/getusermedia.js");
/* harmony import */ var _getdisplaymedia__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./getdisplaymedia */ "./node_modules/webrtc-adapter/src/js/edge/getdisplaymedia.js");
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */









function shimPeerConnection(window, browserDetails) {
  if (window.RTCIceGatherer) {
    if (!window.RTCIceCandidate) {
      window.RTCIceCandidate = function RTCIceCandidate(args) {
        return args;
      };
    }
    if (!window.RTCSessionDescription) {
      window.RTCSessionDescription = function RTCSessionDescription(args) {
        return args;
      };
    }
    // this adds an additional event listener to MediaStrackTrack that signals
    // when a tracks enabled property was changed. Workaround for a bug in
    // addStream, see below. No longer required in 15025+
    if (browserDetails.version < 15025) {
      const origMSTEnabled = Object.getOwnPropertyDescriptor(
          window.MediaStreamTrack.prototype, 'enabled');
      Object.defineProperty(window.MediaStreamTrack.prototype, 'enabled', {
        set(value) {
          origMSTEnabled.set.call(this, value);
          const ev = new Event('enabled');
          ev.enabled = value;
          this.dispatchEvent(ev);
        }
      });
    }
  }

  // ORTC defines the DTMF sender a bit different.
  // https://github.com/w3c/ortc/issues/714
  if (window.RTCRtpSender && !('dtmf' in window.RTCRtpSender.prototype)) {
    Object.defineProperty(window.RTCRtpSender.prototype, 'dtmf', {
      get() {
        if (this._dtmf === undefined) {
          if (this.track.kind === 'audio') {
            this._dtmf = new window.RTCDtmfSender(this);
          } else if (this.track.kind === 'video') {
            this._dtmf = null;
          }
        }
        return this._dtmf;
      }
    });
  }
  // Edge currently only implements the RTCDtmfSender, not the
  // RTCDTMFSender alias. See http://draft.ortc.org/#rtcdtmfsender2*
  if (window.RTCDtmfSender && !window.RTCDTMFSender) {
    window.RTCDTMFSender = window.RTCDtmfSender;
  }

  const RTCPeerConnectionShim = rtcpeerconnection_shim__WEBPACK_IMPORTED_MODULE_2___default()(window,
      browserDetails.version);
  window.RTCPeerConnection = function RTCPeerConnection(config) {
    if (config && config.iceServers) {
      config.iceServers = (0,_filtericeservers__WEBPACK_IMPORTED_MODULE_1__.filterIceServers)(config.iceServers,
        browserDetails.version);
      _utils__WEBPACK_IMPORTED_MODULE_0__.log('ICE servers after filtering:', config.iceServers);
    }
    return new RTCPeerConnectionShim(config);
  };
  window.RTCPeerConnection.prototype = RTCPeerConnectionShim.prototype;
}

function shimReplaceTrack(window) {
  // ORTC has replaceTrack -- https://github.com/w3c/ortc/issues/614
  if (window.RTCRtpSender &&
      !('replaceTrack' in window.RTCRtpSender.prototype)) {
    window.RTCRtpSender.prototype.replaceTrack =
        window.RTCRtpSender.prototype.setTrack;
  }
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/edge/filtericeservers.js":
/*!*********************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/edge/filtericeservers.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "filterIceServers": () => (/* binding */ filterIceServers)
/* harmony export */ });
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils */ "./node_modules/webrtc-adapter/src/js/utils.js");
/*
 *  Copyright (c) 2018 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */



// Edge does not like
// 1) stun: filtered after 14393 unless ?transport=udp is present
// 2) turn: that does not have all of turn:host:port?transport=udp
// 3) turn: with ipv6 addresses
// 4) turn: occurring muliple times
function filterIceServers(iceServers, edgeVersion) {
  let hasTurn = false;
  iceServers = JSON.parse(JSON.stringify(iceServers));
  return iceServers.filter(server => {
    if (server && (server.urls || server.url)) {
      let urls = server.urls || server.url;
      if (server.url && !server.urls) {
        _utils__WEBPACK_IMPORTED_MODULE_0__.deprecated('RTCIceServer.url', 'RTCIceServer.urls');
      }
      const isString = typeof urls === 'string';
      if (isString) {
        urls = [urls];
      }
      urls = urls.filter(url => {
        // filter STUN unconditionally.
        if (url.indexOf('stun:') === 0) {
          return false;
        }

        const validTurn = url.startsWith('turn') &&
            !url.startsWith('turn:[') &&
            url.includes('transport=udp');
        if (validTurn && !hasTurn) {
          hasTurn = true;
          return true;
        }
        return validTurn && !hasTurn;
      });

      delete server.url;
      server.urls = isString ? urls[0] : urls;
      return !!urls.length;
    }
  });
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/edge/getdisplaymedia.js":
/*!********************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/edge/getdisplaymedia.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetDisplayMedia": () => (/* binding */ shimGetDisplayMedia)
/* harmony export */ });
/*
 *  Copyright (c) 2018 The adapter.js project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


function shimGetDisplayMedia(window) {
  if (!('getDisplayMedia' in window.navigator)) {
    return;
  }
  if (!(window.navigator.mediaDevices)) {
    return;
  }
  if (window.navigator.mediaDevices &&
    'getDisplayMedia' in window.navigator.mediaDevices) {
    return;
  }
  window.navigator.mediaDevices.getDisplayMedia =
    window.navigator.getDisplayMedia.bind(window.navigator);
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/edge/getusermedia.js":
/*!*****************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/edge/getusermedia.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetUserMedia": () => (/* binding */ shimGetUserMedia)
/* harmony export */ });
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


function shimGetUserMedia(window) {
  const navigator = window && window.navigator;

  const shimError_ = function(e) {
    return {
      name: {PermissionDeniedError: 'NotAllowedError'}[e.name] || e.name,
      message: e.message,
      constraint: e.constraint,
      toString() {
        return this.name;
      }
    };
  };

  // getUserMedia error shim.
  const origGetUserMedia = navigator.mediaDevices.getUserMedia.
      bind(navigator.mediaDevices);
  navigator.mediaDevices.getUserMedia = function(c) {
    return origGetUserMedia(c).catch(e => Promise.reject(shimError_(e)));
  };
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/firefox/firefox_shim.js":
/*!********************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/firefox/firefox_shim.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetUserMedia": () => (/* reexport safe */ _getusermedia__WEBPACK_IMPORTED_MODULE_1__.shimGetUserMedia),
/* harmony export */   "shimGetDisplayMedia": () => (/* reexport safe */ _getdisplaymedia__WEBPACK_IMPORTED_MODULE_2__.shimGetDisplayMedia),
/* harmony export */   "shimOnTrack": () => (/* binding */ shimOnTrack),
/* harmony export */   "shimPeerConnection": () => (/* binding */ shimPeerConnection),
/* harmony export */   "shimSenderGetStats": () => (/* binding */ shimSenderGetStats),
/* harmony export */   "shimReceiverGetStats": () => (/* binding */ shimReceiverGetStats),
/* harmony export */   "shimRemoveStream": () => (/* binding */ shimRemoveStream),
/* harmony export */   "shimRTCDataChannel": () => (/* binding */ shimRTCDataChannel),
/* harmony export */   "shimAddTransceiver": () => (/* binding */ shimAddTransceiver),
/* harmony export */   "shimGetParameters": () => (/* binding */ shimGetParameters),
/* harmony export */   "shimCreateOffer": () => (/* binding */ shimCreateOffer),
/* harmony export */   "shimCreateAnswer": () => (/* binding */ shimCreateAnswer)
/* harmony export */ });
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils */ "./node_modules/webrtc-adapter/src/js/utils.js");
/* harmony import */ var _getusermedia__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getusermedia */ "./node_modules/webrtc-adapter/src/js/firefox/getusermedia.js");
/* harmony import */ var _getdisplaymedia__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getdisplaymedia */ "./node_modules/webrtc-adapter/src/js/firefox/getdisplaymedia.js");
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */






function shimOnTrack(window) {
  if (typeof window === 'object' && window.RTCTrackEvent &&
      ('receiver' in window.RTCTrackEvent.prototype) &&
      !('transceiver' in window.RTCTrackEvent.prototype)) {
    Object.defineProperty(window.RTCTrackEvent.prototype, 'transceiver', {
      get() {
        return {receiver: this.receiver};
      }
    });
  }
}

function shimPeerConnection(window, browserDetails) {
  if (typeof window !== 'object' ||
      !(window.RTCPeerConnection || window.mozRTCPeerConnection)) {
    return; // probably media.peerconnection.enabled=false in about:config
  }
  if (!window.RTCPeerConnection && window.mozRTCPeerConnection) {
    // very basic support for old versions.
    window.RTCPeerConnection = window.mozRTCPeerConnection;
  }

  if (browserDetails.version < 53) {
    // shim away need for obsolete RTCIceCandidate/RTCSessionDescription.
    ['setLocalDescription', 'setRemoteDescription', 'addIceCandidate']
        .forEach(function(method) {
          const nativeMethod = window.RTCPeerConnection.prototype[method];
          const methodObj = {[method]() {
            arguments[0] = new ((method === 'addIceCandidate') ?
                window.RTCIceCandidate :
                window.RTCSessionDescription)(arguments[0]);
            return nativeMethod.apply(this, arguments);
          }};
          window.RTCPeerConnection.prototype[method] = methodObj[method];
        });
  }

  const modernStatsTypes = {
    inboundrtp: 'inbound-rtp',
    outboundrtp: 'outbound-rtp',
    candidatepair: 'candidate-pair',
    localcandidate: 'local-candidate',
    remotecandidate: 'remote-candidate'
  };

  const nativeGetStats = window.RTCPeerConnection.prototype.getStats;
  window.RTCPeerConnection.prototype.getStats = function getStats() {
    const [selector, onSucc, onErr] = arguments;
    return nativeGetStats.apply(this, [selector || null])
      .then(stats => {
        if (browserDetails.version < 53 && !onSucc) {
          // Shim only promise getStats with spec-hyphens in type names
          // Leave callback version alone; misc old uses of forEach before Map
          try {
            stats.forEach(stat => {
              stat.type = modernStatsTypes[stat.type] || stat.type;
            });
          } catch (e) {
            if (e.name !== 'TypeError') {
              throw e;
            }
            // Avoid TypeError: "type" is read-only, in old versions. 34-43ish
            stats.forEach((stat, i) => {
              stats.set(i, Object.assign({}, stat, {
                type: modernStatsTypes[stat.type] || stat.type
              }));
            });
          }
        }
        return stats;
      })
      .then(onSucc, onErr);
  };
}

function shimSenderGetStats(window) {
  if (!(typeof window === 'object' && window.RTCPeerConnection &&
      window.RTCRtpSender)) {
    return;
  }
  if (window.RTCRtpSender && 'getStats' in window.RTCRtpSender.prototype) {
    return;
  }
  const origGetSenders = window.RTCPeerConnection.prototype.getSenders;
  if (origGetSenders) {
    window.RTCPeerConnection.prototype.getSenders = function getSenders() {
      const senders = origGetSenders.apply(this, []);
      senders.forEach(sender => sender._pc = this);
      return senders;
    };
  }

  const origAddTrack = window.RTCPeerConnection.prototype.addTrack;
  if (origAddTrack) {
    window.RTCPeerConnection.prototype.addTrack = function addTrack() {
      const sender = origAddTrack.apply(this, arguments);
      sender._pc = this;
      return sender;
    };
  }
  window.RTCRtpSender.prototype.getStats = function getStats() {
    return this.track ? this._pc.getStats(this.track) :
        Promise.resolve(new Map());
  };
}

function shimReceiverGetStats(window) {
  if (!(typeof window === 'object' && window.RTCPeerConnection &&
      window.RTCRtpSender)) {
    return;
  }
  if (window.RTCRtpSender && 'getStats' in window.RTCRtpReceiver.prototype) {
    return;
  }
  const origGetReceivers = window.RTCPeerConnection.prototype.getReceivers;
  if (origGetReceivers) {
    window.RTCPeerConnection.prototype.getReceivers = function getReceivers() {
      const receivers = origGetReceivers.apply(this, []);
      receivers.forEach(receiver => receiver._pc = this);
      return receivers;
    };
  }
  _utils__WEBPACK_IMPORTED_MODULE_0__.wrapPeerConnectionEvent(window, 'track', e => {
    e.receiver._pc = e.srcElement;
    return e;
  });
  window.RTCRtpReceiver.prototype.getStats = function getStats() {
    return this._pc.getStats(this.track);
  };
}

function shimRemoveStream(window) {
  if (!window.RTCPeerConnection ||
      'removeStream' in window.RTCPeerConnection.prototype) {
    return;
  }
  window.RTCPeerConnection.prototype.removeStream =
    function removeStream(stream) {
      _utils__WEBPACK_IMPORTED_MODULE_0__.deprecated('removeStream', 'removeTrack');
      this.getSenders().forEach(sender => {
        if (sender.track && stream.getTracks().includes(sender.track)) {
          this.removeTrack(sender);
        }
      });
    };
}

function shimRTCDataChannel(window) {
  // rename DataChannel to RTCDataChannel (native fix in FF60):
  // https://bugzilla.mozilla.org/show_bug.cgi?id=1173851
  if (window.DataChannel && !window.RTCDataChannel) {
    window.RTCDataChannel = window.DataChannel;
  }
}

function shimAddTransceiver(window) {
  // https://github.com/webrtcHacks/adapter/issues/998#issuecomment-516921647
  // Firefox ignores the init sendEncodings options passed to addTransceiver
  // https://bugzilla.mozilla.org/show_bug.cgi?id=1396918
  if (!(typeof window === 'object' && window.RTCPeerConnection)) {
    return;
  }
  const origAddTransceiver = window.RTCPeerConnection.prototype.addTransceiver;
  if (origAddTransceiver) {
    window.RTCPeerConnection.prototype.addTransceiver =
      function addTransceiver() {
        this.setParametersPromises = [];
        const initParameters = arguments[1];
        const shouldPerformCheck = initParameters &&
                                  'sendEncodings' in initParameters;
        if (shouldPerformCheck) {
          // If sendEncodings params are provided, validate grammar
          initParameters.sendEncodings.forEach((encodingParam) => {
            if ('rid' in encodingParam) {
              const ridRegex = /^[a-z0-9]{0,16}$/i;
              if (!ridRegex.test(encodingParam.rid)) {
                throw new TypeError('Invalid RID value provided.');
              }
            }
            if ('scaleResolutionDownBy' in encodingParam) {
              if (!(parseFloat(encodingParam.scaleResolutionDownBy) >= 1.0)) {
                throw new RangeError('scale_resolution_down_by must be >= 1.0');
              }
            }
            if ('maxFramerate' in encodingParam) {
              if (!(parseFloat(encodingParam.maxFramerate) >= 0)) {
                throw new RangeError('max_framerate must be >= 0.0');
              }
            }
          });
        }
        const transceiver = origAddTransceiver.apply(this, arguments);
        if (shouldPerformCheck) {
          // Check if the init options were applied. If not we do this in an
          // asynchronous way and save the promise reference in a global object.
          // This is an ugly hack, but at the same time is way more robust than
          // checking the sender parameters before and after the createOffer
          // Also note that after the createoffer we are not 100% sure that
          // the params were asynchronously applied so we might miss the
          // opportunity to recreate offer.
          const {sender} = transceiver;
          const params = sender.getParameters();
          if (!('encodings' in params) ||
              // Avoid being fooled by patched getParameters() below.
              (params.encodings.length === 1 &&
               Object.keys(params.encodings[0]).length === 0)) {
            params.encodings = initParameters.sendEncodings;
            sender.sendEncodings = initParameters.sendEncodings;
            this.setParametersPromises.push(sender.setParameters(params)
              .then(() => {
                delete sender.sendEncodings;
              }).catch(() => {
                delete sender.sendEncodings;
              })
            );
          }
        }
        return transceiver;
      };
  }
}

function shimGetParameters(window) {
  if (!(typeof window === 'object' && window.RTCRtpSender)) {
    return;
  }
  const origGetParameters = window.RTCRtpSender.prototype.getParameters;
  if (origGetParameters) {
    window.RTCRtpSender.prototype.getParameters =
      function getParameters() {
        const params = origGetParameters.apply(this, arguments);
        if (!('encodings' in params)) {
          params.encodings = [].concat(this.sendEncodings || [{}]);
        }
        return params;
      };
  }
}

function shimCreateOffer(window) {
  // https://github.com/webrtcHacks/adapter/issues/998#issuecomment-516921647
  // Firefox ignores the init sendEncodings options passed to addTransceiver
  // https://bugzilla.mozilla.org/show_bug.cgi?id=1396918
  if (!(typeof window === 'object' && window.RTCPeerConnection)) {
    return;
  }
  const origCreateOffer = window.RTCPeerConnection.prototype.createOffer;
  window.RTCPeerConnection.prototype.createOffer = function createOffer() {
    if (this.setParametersPromises && this.setParametersPromises.length) {
      return Promise.all(this.setParametersPromises)
      .then(() => {
        return origCreateOffer.apply(this, arguments);
      })
      .finally(() => {
        this.setParametersPromises = [];
      });
    }
    return origCreateOffer.apply(this, arguments);
  };
}

function shimCreateAnswer(window) {
  // https://github.com/webrtcHacks/adapter/issues/998#issuecomment-516921647
  // Firefox ignores the init sendEncodings options passed to addTransceiver
  // https://bugzilla.mozilla.org/show_bug.cgi?id=1396918
  if (!(typeof window === 'object' && window.RTCPeerConnection)) {
    return;
  }
  const origCreateAnswer = window.RTCPeerConnection.prototype.createAnswer;
  window.RTCPeerConnection.prototype.createAnswer = function createAnswer() {
    if (this.setParametersPromises && this.setParametersPromises.length) {
      return Promise.all(this.setParametersPromises)
      .then(() => {
        return origCreateAnswer.apply(this, arguments);
      })
      .finally(() => {
        this.setParametersPromises = [];
      });
    }
    return origCreateAnswer.apply(this, arguments);
  };
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/firefox/getdisplaymedia.js":
/*!***********************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/firefox/getdisplaymedia.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetDisplayMedia": () => (/* binding */ shimGetDisplayMedia)
/* harmony export */ });
/*
 *  Copyright (c) 2018 The adapter.js project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */


function shimGetDisplayMedia(window, preferredMediaSource) {
  if (window.navigator.mediaDevices &&
    'getDisplayMedia' in window.navigator.mediaDevices) {
    return;
  }
  if (!(window.navigator.mediaDevices)) {
    return;
  }
  window.navigator.mediaDevices.getDisplayMedia =
    function getDisplayMedia(constraints) {
      if (!(constraints && constraints.video)) {
        const err = new DOMException('getDisplayMedia without video ' +
            'constraints is undefined');
        err.name = 'NotFoundError';
        // from https://heycam.github.io/webidl/#idl-DOMException-error-names
        err.code = 8;
        return Promise.reject(err);
      }
      if (constraints.video === true) {
        constraints.video = {mediaSource: preferredMediaSource};
      } else {
        constraints.video.mediaSource = preferredMediaSource;
      }
      return window.navigator.mediaDevices.getUserMedia(constraints);
    };
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/firefox/getusermedia.js":
/*!********************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/firefox/getusermedia.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimGetUserMedia": () => (/* binding */ shimGetUserMedia)
/* harmony export */ });
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils */ "./node_modules/webrtc-adapter/src/js/utils.js");
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
/* eslint-env node */




function shimGetUserMedia(window, browserDetails) {
  const navigator = window && window.navigator;
  const MediaStreamTrack = window && window.MediaStreamTrack;

  navigator.getUserMedia = function(constraints, onSuccess, onError) {
    // Replace Firefox 44+'s deprecation warning with unprefixed version.
    _utils__WEBPACK_IMPORTED_MODULE_0__.deprecated('navigator.getUserMedia',
        'navigator.mediaDevices.getUserMedia');
    navigator.mediaDevices.getUserMedia(constraints).then(onSuccess, onError);
  };

  if (!(browserDetails.version > 55 &&
      'autoGainControl' in navigator.mediaDevices.getSupportedConstraints())) {
    const remap = function(obj, a, b) {
      if (a in obj && !(b in obj)) {
        obj[b] = obj[a];
        delete obj[a];
      }
    };

    const nativeGetUserMedia = navigator.mediaDevices.getUserMedia.
        bind(navigator.mediaDevices);
    navigator.mediaDevices.getUserMedia = function(c) {
      if (typeof c === 'object' && typeof c.audio === 'object') {
        c = JSON.parse(JSON.stringify(c));
        remap(c.audio, 'autoGainControl', 'mozAutoGainControl');
        remap(c.audio, 'noiseSuppression', 'mozNoiseSuppression');
      }
      return nativeGetUserMedia(c);
    };

    if (MediaStreamTrack && MediaStreamTrack.prototype.getSettings) {
      const nativeGetSettings = MediaStreamTrack.prototype.getSettings;
      MediaStreamTrack.prototype.getSettings = function() {
        const obj = nativeGetSettings.apply(this, arguments);
        remap(obj, 'mozAutoGainControl', 'autoGainControl');
        remap(obj, 'mozNoiseSuppression', 'noiseSuppression');
        return obj;
      };
    }

    if (MediaStreamTrack && MediaStreamTrack.prototype.applyConstraints) {
      const nativeApplyConstraints =
        MediaStreamTrack.prototype.applyConstraints;
      MediaStreamTrack.prototype.applyConstraints = function(c) {
        if (this.kind === 'audio' && typeof c === 'object') {
          c = JSON.parse(JSON.stringify(c));
          remap(c, 'autoGainControl', 'mozAutoGainControl');
          remap(c, 'noiseSuppression', 'mozNoiseSuppression');
        }
        return nativeApplyConstraints.apply(this, [c]);
      };
    }
  }
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/safari/safari_shim.js":
/*!******************************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/safari/safari_shim.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "shimLocalStreamsAPI": () => (/* binding */ shimLocalStreamsAPI),
/* harmony export */   "shimRemoteStreamsAPI": () => (/* binding */ shimRemoteStreamsAPI),
/* harmony export */   "shimCallbacksAPI": () => (/* binding */ shimCallbacksAPI),
/* harmony export */   "shimGetUserMedia": () => (/* binding */ shimGetUserMedia),
/* harmony export */   "shimConstraints": () => (/* binding */ shimConstraints),
/* harmony export */   "shimRTCIceServerUrls": () => (/* binding */ shimRTCIceServerUrls),
/* harmony export */   "shimTrackEventTransceiver": () => (/* binding */ shimTrackEventTransceiver),
/* harmony export */   "shimCreateOfferLegacy": () => (/* binding */ shimCreateOfferLegacy),
/* harmony export */   "shimAudioContext": () => (/* binding */ shimAudioContext)
/* harmony export */ });
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils */ "./node_modules/webrtc-adapter/src/js/utils.js");
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */



function shimLocalStreamsAPI(window) {
  if (typeof window !== 'object' || !window.RTCPeerConnection) {
    return;
  }
  if (!('getLocalStreams' in window.RTCPeerConnection.prototype)) {
    window.RTCPeerConnection.prototype.getLocalStreams =
      function getLocalStreams() {
        if (!this._localStreams) {
          this._localStreams = [];
        }
        return this._localStreams;
      };
  }
  if (!('addStream' in window.RTCPeerConnection.prototype)) {
    const _addTrack = window.RTCPeerConnection.prototype.addTrack;
    window.RTCPeerConnection.prototype.addStream = function addStream(stream) {
      if (!this._localStreams) {
        this._localStreams = [];
      }
      if (!this._localStreams.includes(stream)) {
        this._localStreams.push(stream);
      }
      // Try to emulate Chrome's behaviour of adding in audio-video order.
      // Safari orders by track id.
      stream.getAudioTracks().forEach(track => _addTrack.call(this, track,
        stream));
      stream.getVideoTracks().forEach(track => _addTrack.call(this, track,
        stream));
    };

    window.RTCPeerConnection.prototype.addTrack =
      function addTrack(track, ...streams) {
        if (streams) {
          streams.forEach((stream) => {
            if (!this._localStreams) {
              this._localStreams = [stream];
            } else if (!this._localStreams.includes(stream)) {
              this._localStreams.push(stream);
            }
          });
        }
        return _addTrack.apply(this, arguments);
      };
  }
  if (!('removeStream' in window.RTCPeerConnection.prototype)) {
    window.RTCPeerConnection.prototype.removeStream =
      function removeStream(stream) {
        if (!this._localStreams) {
          this._localStreams = [];
        }
        const index = this._localStreams.indexOf(stream);
        if (index === -1) {
          return;
        }
        this._localStreams.splice(index, 1);
        const tracks = stream.getTracks();
        this.getSenders().forEach(sender => {
          if (tracks.includes(sender.track)) {
            this.removeTrack(sender);
          }
        });
      };
  }
}

function shimRemoteStreamsAPI(window) {
  if (typeof window !== 'object' || !window.RTCPeerConnection) {
    return;
  }
  if (!('getRemoteStreams' in window.RTCPeerConnection.prototype)) {
    window.RTCPeerConnection.prototype.getRemoteStreams =
      function getRemoteStreams() {
        return this._remoteStreams ? this._remoteStreams : [];
      };
  }
  if (!('onaddstream' in window.RTCPeerConnection.prototype)) {
    Object.defineProperty(window.RTCPeerConnection.prototype, 'onaddstream', {
      get() {
        return this._onaddstream;
      },
      set(f) {
        if (this._onaddstream) {
          this.removeEventListener('addstream', this._onaddstream);
          this.removeEventListener('track', this._onaddstreampoly);
        }
        this.addEventListener('addstream', this._onaddstream = f);
        this.addEventListener('track', this._onaddstreampoly = (e) => {
          e.streams.forEach(stream => {
            if (!this._remoteStreams) {
              this._remoteStreams = [];
            }
            if (this._remoteStreams.includes(stream)) {
              return;
            }
            this._remoteStreams.push(stream);
            const event = new Event('addstream');
            event.stream = stream;
            this.dispatchEvent(event);
          });
        });
      }
    });
    const origSetRemoteDescription =
      window.RTCPeerConnection.prototype.setRemoteDescription;
    window.RTCPeerConnection.prototype.setRemoteDescription =
      function setRemoteDescription() {
        const pc = this;
        if (!this._onaddstreampoly) {
          this.addEventListener('track', this._onaddstreampoly = function(e) {
            e.streams.forEach(stream => {
              if (!pc._remoteStreams) {
                pc._remoteStreams = [];
              }
              if (pc._remoteStreams.indexOf(stream) >= 0) {
                return;
              }
              pc._remoteStreams.push(stream);
              const event = new Event('addstream');
              event.stream = stream;
              pc.dispatchEvent(event);
            });
          });
        }
        return origSetRemoteDescription.apply(pc, arguments);
      };
  }
}

function shimCallbacksAPI(window) {
  if (typeof window !== 'object' || !window.RTCPeerConnection) {
    return;
  }
  const prototype = window.RTCPeerConnection.prototype;
  const origCreateOffer = prototype.createOffer;
  const origCreateAnswer = prototype.createAnswer;
  const setLocalDescription = prototype.setLocalDescription;
  const setRemoteDescription = prototype.setRemoteDescription;
  const addIceCandidate = prototype.addIceCandidate;

  prototype.createOffer =
    function createOffer(successCallback, failureCallback) {
      const options = (arguments.length >= 2) ? arguments[2] : arguments[0];
      const promise = origCreateOffer.apply(this, [options]);
      if (!failureCallback) {
        return promise;
      }
      promise.then(successCallback, failureCallback);
      return Promise.resolve();
    };

  prototype.createAnswer =
    function createAnswer(successCallback, failureCallback) {
      const options = (arguments.length >= 2) ? arguments[2] : arguments[0];
      const promise = origCreateAnswer.apply(this, [options]);
      if (!failureCallback) {
        return promise;
      }
      promise.then(successCallback, failureCallback);
      return Promise.resolve();
    };

  let withCallback = function(description, successCallback, failureCallback) {
    const promise = setLocalDescription.apply(this, [description]);
    if (!failureCallback) {
      return promise;
    }
    promise.then(successCallback, failureCallback);
    return Promise.resolve();
  };
  prototype.setLocalDescription = withCallback;

  withCallback = function(description, successCallback, failureCallback) {
    const promise = setRemoteDescription.apply(this, [description]);
    if (!failureCallback) {
      return promise;
    }
    promise.then(successCallback, failureCallback);
    return Promise.resolve();
  };
  prototype.setRemoteDescription = withCallback;

  withCallback = function(candidate, successCallback, failureCallback) {
    const promise = addIceCandidate.apply(this, [candidate]);
    if (!failureCallback) {
      return promise;
    }
    promise.then(successCallback, failureCallback);
    return Promise.resolve();
  };
  prototype.addIceCandidate = withCallback;
}

function shimGetUserMedia(window) {
  const navigator = window && window.navigator;

  if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    // shim not needed in Safari 12.1
    const mediaDevices = navigator.mediaDevices;
    const _getUserMedia = mediaDevices.getUserMedia.bind(mediaDevices);
    navigator.mediaDevices.getUserMedia = (constraints) => {
      return _getUserMedia(shimConstraints(constraints));
    };
  }

  if (!navigator.getUserMedia && navigator.mediaDevices &&
    navigator.mediaDevices.getUserMedia) {
    navigator.getUserMedia = function getUserMedia(constraints, cb, errcb) {
      navigator.mediaDevices.getUserMedia(constraints)
      .then(cb, errcb);
    }.bind(navigator);
  }
}

function shimConstraints(constraints) {
  if (constraints && constraints.video !== undefined) {
    return Object.assign({},
      constraints,
      {video: _utils__WEBPACK_IMPORTED_MODULE_0__.compactObject(constraints.video)}
    );
  }

  return constraints;
}

function shimRTCIceServerUrls(window) {
  if (!window.RTCPeerConnection) {
    return;
  }
  // migrate from non-spec RTCIceServer.url to RTCIceServer.urls
  const OrigPeerConnection = window.RTCPeerConnection;
  window.RTCPeerConnection =
    function RTCPeerConnection(pcConfig, pcConstraints) {
      if (pcConfig && pcConfig.iceServers) {
        const newIceServers = [];
        for (let i = 0; i < pcConfig.iceServers.length; i++) {
          let server = pcConfig.iceServers[i];
          if (!server.hasOwnProperty('urls') &&
              server.hasOwnProperty('url')) {
            _utils__WEBPACK_IMPORTED_MODULE_0__.deprecated('RTCIceServer.url', 'RTCIceServer.urls');
            server = JSON.parse(JSON.stringify(server));
            server.urls = server.url;
            delete server.url;
            newIceServers.push(server);
          } else {
            newIceServers.push(pcConfig.iceServers[i]);
          }
        }
        pcConfig.iceServers = newIceServers;
      }
      return new OrigPeerConnection(pcConfig, pcConstraints);
    };
  window.RTCPeerConnection.prototype = OrigPeerConnection.prototype;
  // wrap static methods. Currently just generateCertificate.
  if ('generateCertificate' in OrigPeerConnection) {
    Object.defineProperty(window.RTCPeerConnection, 'generateCertificate', {
      get() {
        return OrigPeerConnection.generateCertificate;
      }
    });
  }
}

function shimTrackEventTransceiver(window) {
  // Add event.transceiver member over deprecated event.receiver
  if (typeof window === 'object' && window.RTCTrackEvent &&
      'receiver' in window.RTCTrackEvent.prototype &&
      !('transceiver' in window.RTCTrackEvent.prototype)) {
    Object.defineProperty(window.RTCTrackEvent.prototype, 'transceiver', {
      get() {
        return {receiver: this.receiver};
      }
    });
  }
}

function shimCreateOfferLegacy(window) {
  const origCreateOffer = window.RTCPeerConnection.prototype.createOffer;
  window.RTCPeerConnection.prototype.createOffer =
    function createOffer(offerOptions) {
      if (offerOptions) {
        if (typeof offerOptions.offerToReceiveAudio !== 'undefined') {
          // support bit values
          offerOptions.offerToReceiveAudio =
            !!offerOptions.offerToReceiveAudio;
        }
        const audioTransceiver = this.getTransceivers().find(transceiver =>
          transceiver.receiver.track.kind === 'audio');
        if (offerOptions.offerToReceiveAudio === false && audioTransceiver) {
          if (audioTransceiver.direction === 'sendrecv') {
            if (audioTransceiver.setDirection) {
              audioTransceiver.setDirection('sendonly');
            } else {
              audioTransceiver.direction = 'sendonly';
            }
          } else if (audioTransceiver.direction === 'recvonly') {
            if (audioTransceiver.setDirection) {
              audioTransceiver.setDirection('inactive');
            } else {
              audioTransceiver.direction = 'inactive';
            }
          }
        } else if (offerOptions.offerToReceiveAudio === true &&
            !audioTransceiver) {
          this.addTransceiver('audio');
        }

        if (typeof offerOptions.offerToReceiveVideo !== 'undefined') {
          // support bit values
          offerOptions.offerToReceiveVideo =
            !!offerOptions.offerToReceiveVideo;
        }
        const videoTransceiver = this.getTransceivers().find(transceiver =>
          transceiver.receiver.track.kind === 'video');
        if (offerOptions.offerToReceiveVideo === false && videoTransceiver) {
          if (videoTransceiver.direction === 'sendrecv') {
            if (videoTransceiver.setDirection) {
              videoTransceiver.setDirection('sendonly');
            } else {
              videoTransceiver.direction = 'sendonly';
            }
          } else if (videoTransceiver.direction === 'recvonly') {
            if (videoTransceiver.setDirection) {
              videoTransceiver.setDirection('inactive');
            } else {
              videoTransceiver.direction = 'inactive';
            }
          }
        } else if (offerOptions.offerToReceiveVideo === true &&
            !videoTransceiver) {
          this.addTransceiver('video');
        }
      }
      return origCreateOffer.apply(this, arguments);
    };
}

function shimAudioContext(window) {
  if (typeof window !== 'object' || window.AudioContext) {
    return;
  }
  window.AudioContext = window.webkitAudioContext;
}


/***/ }),

/***/ "./node_modules/webrtc-adapter/src/js/utils.js":
/*!*****************************************************!*\
  !*** ./node_modules/webrtc-adapter/src/js/utils.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "extractVersion": () => (/* binding */ extractVersion),
/* harmony export */   "wrapPeerConnectionEvent": () => (/* binding */ wrapPeerConnectionEvent),
/* harmony export */   "disableLog": () => (/* binding */ disableLog),
/* harmony export */   "disableWarnings": () => (/* binding */ disableWarnings),
/* harmony export */   "log": () => (/* binding */ log),
/* harmony export */   "deprecated": () => (/* binding */ deprecated),
/* harmony export */   "detectBrowser": () => (/* binding */ detectBrowser),
/* harmony export */   "compactObject": () => (/* binding */ compactObject),
/* harmony export */   "walkStats": () => (/* binding */ walkStats),
/* harmony export */   "filterStats": () => (/* binding */ filterStats)
/* harmony export */ });
/*
 *  Copyright (c) 2016 The WebRTC project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a BSD-style license
 *  that can be found in the LICENSE file in the root of the source
 *  tree.
 */
 /* eslint-env node */


let logDisabled_ = true;
let deprecationWarnings_ = true;

/**
 * Extract browser version out of the provided user agent string.
 *
 * @param {!string} uastring userAgent string.
 * @param {!string} expr Regular expression used as match criteria.
 * @param {!number} pos position in the version string to be returned.
 * @return {!number} browser version.
 */
function extractVersion(uastring, expr, pos) {
  const match = uastring.match(expr);
  return match && match.length >= pos && parseInt(match[pos], 10);
}

// Wraps the peerconnection event eventNameToWrap in a function
// which returns the modified event object (or false to prevent
// the event).
function wrapPeerConnectionEvent(window, eventNameToWrap, wrapper) {
  if (!window.RTCPeerConnection) {
    return;
  }
  const proto = window.RTCPeerConnection.prototype;
  const nativeAddEventListener = proto.addEventListener;
  proto.addEventListener = function(nativeEventName, cb) {
    if (nativeEventName !== eventNameToWrap) {
      return nativeAddEventListener.apply(this, arguments);
    }
    const wrappedCallback = (e) => {
      const modifiedEvent = wrapper(e);
      if (modifiedEvent) {
        if (cb.handleEvent) {
          cb.handleEvent(modifiedEvent);
        } else {
          cb(modifiedEvent);
        }
      }
    };
    this._eventMap = this._eventMap || {};
    if (!this._eventMap[eventNameToWrap]) {
      this._eventMap[eventNameToWrap] = new Map();
    }
    this._eventMap[eventNameToWrap].set(cb, wrappedCallback);
    return nativeAddEventListener.apply(this, [nativeEventName,
      wrappedCallback]);
  };

  const nativeRemoveEventListener = proto.removeEventListener;
  proto.removeEventListener = function(nativeEventName, cb) {
    if (nativeEventName !== eventNameToWrap || !this._eventMap
        || !this._eventMap[eventNameToWrap]) {
      return nativeRemoveEventListener.apply(this, arguments);
    }
    if (!this._eventMap[eventNameToWrap].has(cb)) {
      return nativeRemoveEventListener.apply(this, arguments);
    }
    const unwrappedCb = this._eventMap[eventNameToWrap].get(cb);
    this._eventMap[eventNameToWrap].delete(cb);
    if (this._eventMap[eventNameToWrap].size === 0) {
      delete this._eventMap[eventNameToWrap];
    }
    if (Object.keys(this._eventMap).length === 0) {
      delete this._eventMap;
    }
    return nativeRemoveEventListener.apply(this, [nativeEventName,
      unwrappedCb]);
  };

  Object.defineProperty(proto, 'on' + eventNameToWrap, {
    get() {
      return this['_on' + eventNameToWrap];
    },
    set(cb) {
      if (this['_on' + eventNameToWrap]) {
        this.removeEventListener(eventNameToWrap,
            this['_on' + eventNameToWrap]);
        delete this['_on' + eventNameToWrap];
      }
      if (cb) {
        this.addEventListener(eventNameToWrap,
            this['_on' + eventNameToWrap] = cb);
      }
    },
    enumerable: true,
    configurable: true
  });
}

function disableLog(bool) {
  if (typeof bool !== 'boolean') {
    return new Error('Argument type: ' + typeof bool +
        '. Please use a boolean.');
  }
  logDisabled_ = bool;
  return (bool) ? 'adapter.js logging disabled' :
      'adapter.js logging enabled';
}

/**
 * Disable or enable deprecation warnings
 * @param {!boolean} bool set to true to disable warnings.
 */
function disableWarnings(bool) {
  if (typeof bool !== 'boolean') {
    return new Error('Argument type: ' + typeof bool +
        '. Please use a boolean.');
  }
  deprecationWarnings_ = !bool;
  return 'adapter.js deprecation warnings ' + (bool ? 'disabled' : 'enabled');
}

function log() {
  if (typeof window === 'object') {
    if (logDisabled_) {
      return;
    }
    if (typeof console !== 'undefined' && typeof console.log === 'function') {
      console.log.apply(console, arguments);
    }
  }
}

/**
 * Shows a deprecation warning suggesting the modern and spec-compatible API.
 */
function deprecated(oldMethod, newMethod) {
  if (!deprecationWarnings_) {
    return;
  }
  console.warn(oldMethod + ' is deprecated, please use ' + newMethod +
      ' instead.');
}

/**
 * Browser detector.
 *
 * @return {object} result containing browser and version
 *     properties.
 */
function detectBrowser(window) {
  // Returned result object.
  const result = {browser: null, version: null};

  // Fail early if it's not a browser
  if (typeof window === 'undefined' || !window.navigator) {
    result.browser = 'Not a browser.';
    return result;
  }

  const {navigator} = window;

  if (navigator.mozGetUserMedia) { // Firefox.
    result.browser = 'firefox';
    result.version = extractVersion(navigator.userAgent,
        /Firefox\/(\d+)\./, 1);
  } else if (navigator.webkitGetUserMedia ||
      (window.isSecureContext === false && window.webkitRTCPeerConnection &&
       !window.RTCIceGatherer)) {
    // Chrome, Chromium, Webview, Opera.
    // Version matches Chrome/WebRTC version.
    // Chrome 74 removed webkitGetUserMedia on http as well so we need the
    // more complicated fallback to webkitRTCPeerConnection.
    result.browser = 'chrome';
    result.version = extractVersion(navigator.userAgent,
        /Chrom(e|ium)\/(\d+)\./, 2);
  } else if (navigator.mediaDevices &&
      navigator.userAgent.match(/Edge\/(\d+).(\d+)$/)) { // Edge.
    result.browser = 'edge';
    result.version = extractVersion(navigator.userAgent,
        /Edge\/(\d+).(\d+)$/, 2);
  } else if (window.RTCPeerConnection &&
      navigator.userAgent.match(/AppleWebKit\/(\d+)\./)) { // Safari.
    result.browser = 'safari';
    result.version = extractVersion(navigator.userAgent,
        /AppleWebKit\/(\d+)\./, 1);
    result.supportsUnifiedPlan = window.RTCRtpTransceiver &&
        'currentDirection' in window.RTCRtpTransceiver.prototype;
  } else { // Default fallthrough: not supported.
    result.browser = 'Not a supported browser.';
    return result;
  }

  return result;
}

/**
 * Checks if something is an object.
 *
 * @param {*} val The something you want to check.
 * @return true if val is an object, false otherwise.
 */
function isObject(val) {
  return Object.prototype.toString.call(val) === '[object Object]';
}

/**
 * Remove all empty objects and undefined values
 * from a nested object -- an enhanced and vanilla version
 * of Lodash's `compact`.
 */
function compactObject(data) {
  if (!isObject(data)) {
    return data;
  }

  return Object.keys(data).reduce(function(accumulator, key) {
    const isObj = isObject(data[key]);
    const value = isObj ? compactObject(data[key]) : data[key];
    const isEmptyObject = isObj && !Object.keys(value).length;
    if (value === undefined || isEmptyObject) {
      return accumulator;
    }
    return Object.assign(accumulator, {[key]: value});
  }, {});
}

/* iterates the stats graph recursively. */
function walkStats(stats, base, resultSet) {
  if (!base || resultSet.has(base.id)) {
    return;
  }
  resultSet.set(base.id, base);
  Object.keys(base).forEach(name => {
    if (name.endsWith('Id')) {
      walkStats(stats, stats.get(base[name]), resultSet);
    } else if (name.endsWith('Ids')) {
      base[name].forEach(id => {
        walkStats(stats, stats.get(id), resultSet);
      });
    }
  });
}

/* filter getStats for a sender/receiver track. */
function filterStats(result, track, outbound) {
  const streamStatsType = outbound ? 'outbound-rtp' : 'inbound-rtp';
  const filteredResult = new Map();
  if (track === null) {
    return filteredResult;
  }
  const trackStats = [];
  result.forEach(value => {
    if (value.type === 'track' &&
        value.trackIdentifier === track.id) {
      trackStats.push(value);
    }
  });
  trackStats.forEach(trackStat => {
    result.forEach(stats => {
      if (stats.type === streamStatsType && stats.trackId === trackStat.id) {
        walkStats(result, stats, filteredResult);
      }
    });
  });
  return filteredResult;
}



/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!*******************************************!*\
  !*** ./resources/js/janus/JanusServer.js ***!
  \*******************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Janus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../Janus */ "./resources/js/Janus.js");


window.JanusServer = function () {
  var opt = {
    TUTORIAL: false,
    DEMO: false,
    initialized: false,
    lock: true,
    my_stream_ctnr: $("#my_video_ctrn"),
    other_stream_ctnr: $("#other_videos_ctrn"),
    empty_room: $("#empty_room"),
    settings: {
      _mode: 'default',
      mode: 'default',
      bitrate: 128000,
      audio: true,
      video: true,
      screen: false,
      muted: false,
      video_paused: false,
      active_speaker: false,
      simulcast_1: false,
      simulcast_2: false,
      video_constraints: {
        term: 'stdres',
        width: {
          ideal: 1280
        },
        height: {
          ideal: 720
        },
        facingMode: 'user'
      }
    },
    speech_events: {
      analyzer: null,
      speaker_locked: false,
      speaker_id: null,
      screen_share_id: null,
      speaker_timeout: null,
      activeLockId: function activeLockId() {
        return this.screen_share_id ? this.screen_share_id : this.speaker_id;
      }
    },
    server: {
      JANUS: null,
      SFU: null,
      call_socket: null,
      api_secret: null,
      room_joined: false,
      retries: 0,
      main: [],
      ice: []
    },
    storage: {
      display: null,
      my_janus_id: null,
      my_janus_private_id: null,
      broadcast_stream: null,
      feeds: [],
      participants: [],
      bitrateTimer: []
    }
  },
      Config = {
    init: function init(demo) {
      if (opt.initialized) return;
      opt.lock = false;
      if (demo) opt.DEMO = true;
      opt.server.api_secret = CallManager.state().janus_secret;
      opt.server.main = CallManager.state().janus_main;
      opt.server.ice = CallManager.state().janus_ice;
      opt.storage.display = {
        id: Messenger.common().id,
        name: Messenger.common().name,
        avatar: Messenger.common().avatar_md
      };

      if (!opt.DEMO) {
        switch (CallManager.state().thread_type) {
          case 1:
            opt.settings.bitrate = 1024000;
            break;

          case 2:
            opt.settings.bitrate = 600000;
            break;
        }
      }

      if (CallManager.state().call_type === 1) opt.settings.video_constraints.term = 'hires-16:9';
      if (opt.DEMO) return;
      opt.initialized = true;
      _Janus__WEBPACK_IMPORTED_MODULE_0__.default.init({
        debug: CallManager.state().janus_debug,
        callback: Config.setup
      });
      Sockets.setup();
    },
    reset: function reset() {
      opt.storage = {
        display: opt.storage.display,
        my_janus_id: null,
        my_janus_private_id: null,
        broadcast_stream: null,
        feeds: [],
        participants: [],
        bitrateTimer: []
      };
      opt.settings.screen = false;
      opt.settings.muted = false;
      opt.settings.video_paused = false;
      opt.my_stream_ctnr.html('');
      opt.other_stream_ctnr.html('');
    },
    demo: function demo() {
      if (opt.TUTORIAL) return;
      opt.initialized = true;
      PageListeners.listen().disposeTooltips();
      opt.other_stream_ctnr.show();
      opt.empty_room.show();
      _Janus__WEBPACK_IMPORTED_MODULE_0__.default.init({
        debug: CallManager.state().janus_debug,
        callback: Config.setup
      });
    },
    setup: function setup() {
      if (!_Janus__WEBPACK_IMPORTED_MODULE_0__.default.isWebrtcSupported()) {
        _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('No WebRTC Support');
        Messenger.alert().Alert({
          toast: true,
          theme: 'error',
          title: 'It seems your browser does not support WebRTC. Unable to continue loading streams'
        });
        return;
      }

      if (opt.server.JANUS) return;
      Config.reset();

      if (opt.settings._mode === 'unpublished') {
        opt.settings.mode = 'unpublished';
        opt.my_stream_ctnr.html(templates.observing()).show();
      } else {
        opt.settings.mode = 'default';
        opt.my_stream_ctnr.html(templates.loading_media()).show();
      }

      opt.other_stream_ctnr.show();
      opt.empty_room.show();
      opt.server.JANUS = new _Janus__WEBPACK_IMPORTED_MODULE_0__.default({
        server: opt.server.main,
        opaqueId: Messenger.common().id,
        apisecret: opt.server.api_secret,
        iceServers: opt.server.ice,
        success: Attach.myself,
        error: function error(_error) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.error(_error);
          Messenger.alert().Alert({
            toast: true,
            theme: 'error',
            title: 'Unable to connect to our streaming services, please reload and try again'
          });
        },
        destroyed: function destroyed() {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('destroyed');
          Config.destroy(true);
        }
      });
    },
    destroy: function destroy(destroyed, callback) {
      if (!destroyed && opt.server.JANUS) {
        opt.my_stream_ctnr.hide();
        Config.reset();
        if (opt.server.SFU) opt.server.SFU.hangup();
        setTimeout(opt.server.JANUS.destroy, 500);
      }

      opt.other_stream_ctnr.html('').hide();
      opt.empty_room.hide();
      opt.settings.mode = 'destroyed';
      methods.toolbarState();

      if (destroyed) {
        opt.server.JANUS = null;
        opt.server.SFU = null;
      }

      if (callback && typeof callback === 'function') callback();
    },
    demoEnded: function demoEnded() {
      opt.initialized = false;
      Config.destroy(false, function () {
        opt.my_stream_ctnr.html('<div class="h5 p-2 text-danger">This demo streaming room has been destroyed. If you would like to rejoin, please reload your page</div>').show();
      });
    }
  },
      Sockets = {
    setup: function setup() {
      if (!CallManager.channel().state || !CallManager.channel().socket) {
        setTimeout(Sockets.setup, 1000);
        return;
      }

      opt.server.call_socket = CallManager.channel().socket;
      opt.server.call_socket.listenForWhisper('screen_share_started', methods.maximizePublisher); //     .listenForWhisper('screen_share_ended', methods.minimizePublisher)
    },
    joining: function joining(user) {
      _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("User joined socket", user);
    },
    leaving: function leaving(user) {
      _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("User left socket", user);
    },
    disconnected: function disconnected() {// if(!opt.initialized || CallManager.state().processing) return;
      // Messenger.alert().Alert({
      //     toast : true,
      //     close_toast : true,
      //     theme : 'warning',
      //     title : 'You may be experiencing connection issues, your video streams may become interrupted'
      // });
    },
    reconnected: function reconnected() {// if(!opt.initialized) return;
      // Messenger.alert().Alert({
      //     toast : true,
      //     close_toast : true,
      //     theme : 'success',
      //     title : 'Reconnected'
      // });
    }
  },
      Attach = {
    myself: function myself() {
      opt.server.JANUS.attach({
        plugin: "janus.plugin.videoroom",
        success: function success(pluginHandle) {
          opt.server.SFU = pluginHandle;
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Plugin attached! (" + opt.server.SFU.getPlugin() + ", id=" + opt.server.SFU.getId() + ")");
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("  -- This is a publisher/manager");
          opt.server.SFU.send({
            message: {
              request: "join",
              room: CallManager.state().room_id,
              ptype: "publisher",
              display: JSON.stringify(opt.storage.display),
              pin: CallManager.state().room_pin
            }
          });
          methods.toolbarState();
        },
        error: function error(_error2) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.error("  -- Error attaching plugin...", _error2);
        },
        consentDialog: function consentDialog(on) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Consent dialog should be " + (on ? "on" : "off") + " now");
        },
        mediaState: function mediaState(medium, on) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Janus " + (on ? "started" : "stopped") + " receiving our " + medium);
        },
        webrtcState: function webrtcState(on) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Janus says our WebRTC PeerConnection is " + (on ? "up" : "down") + " now");
        },
        onmessage: function onmessage(msg, jsep) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(" ::: Got a message (publisher) :::");
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(msg);
          var event = msg["videoroom"];
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Event: " + event);

          if (event !== undefined && event !== null) {
            if (event === "joined") {
              // Publisher/manager created, negotiate WebRTC and attach to existing feeds, if any
              opt.storage.my_janus_id = msg["id"];
              opt.storage.my_janus_private_id = msg["private_id"];
              _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Successfully joined room " + msg["room"] + " with ID " + opt.storage.my_janus_id);
              methods.publishOwnFeed(); // Any new feed to attach to?

              if (msg["publishers"] !== undefined && msg["publishers"] !== null) {
                var list = msg["publishers"];
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Got a list of available publishers/feeds:");
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(list);

                for (var f in list) {
                  if (!list.hasOwnProperty(f)) continue;
                  var obj = {
                    id: list[f]["id"],
                    display: list[f]["display"],
                    audio: list[f]["audio_codec"],
                    video: list[f]["video_codec"]
                  };
                  _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("  >> [" + obj.id + "] " + obj.display + " (audio: " + obj.audio + ", video: " + obj.video + ")");
                  Attach.remote(obj.id, obj.display, obj.audio, obj.video);
                }
              } //get room participants on join


              methods.getParticipants();
            } else if (event === "talking") {
              _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('talking event :)');
            } else if (event === "stopped-talking") {
              _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('stopped talking event :(');
            } else if (event === "destroyed") {
              _Janus__WEBPACK_IMPORTED_MODULE_0__.default.warn("The room has been destroyed!");
              if (opt.DEMO) Config.demoEnded();
            } else if (event === "event") {
              // Any new feed to attach to?
              if (msg["publishers"] !== undefined && msg["publishers"] !== null) {
                var _list = msg["publishers"];
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Got a list of available publishers/feeds:");
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(_list);

                for (var _f in _list) {
                  if (!_list.hasOwnProperty(_f)) continue;
                  var _obj = {
                    id: _list[_f]["id"],
                    display: _list[_f]["display"],
                    audio: _list[_f]["audio_codec"],
                    video: _list[_f]["video_codec"]
                  };
                  _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("  >> [" + _obj.id + "] " + _obj.display + " (audio: " + _obj.audio + ", video: " + _obj.video + ")");
                  Attach.remote(_obj.id, _obj.display, _obj.audio, _obj.video);
                }
              } else if (msg["joining"] !== undefined && msg["joining"] !== null) {
                // A participant joined the room
                var joining = msg["joining"];
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Participant Joined: " + joining['id']);
                methods.addParticipant(joining);
                methods.drawParticipants();
              } else if (msg["leaving"] !== undefined && msg["leaving"] !== null) {
                // One of the publishers has gone away?
                var leaving_id = msg["leaving"],
                    remote_feed = null,
                    remote_feed_storage = methods.locateStorageItem('feed', leaving_id);
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Publisher left: " + leaving_id);

                if (remote_feed_storage.found) {
                  remote_feed = opt.storage.feeds[remote_feed_storage.index];
                  _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Feed " + remote_feed.feed_id + " (" + remote_feed.feed_name + ") has left the room, detaching");
                  $("#other_stream_ctnr_" + remote_feed.feed_id).remove();
                  remote_feed.detach();
                  opt.storage.feeds.splice(remote_feed_storage.index, 1);
                }

                methods.removeParticipant(leaving_id);
                if (opt.initialized) methods.drawParticipants();
              } else if (msg["unpublished"] !== undefined && msg["unpublished"] !== null) {
                // One of the publishers has unpublished?
                var unpublished_id = msg["unpublished"],
                    _remote_feed = null,
                    _remote_feed_storage = methods.locateStorageItem('feed', unpublished_id);

                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Publisher left: " + unpublished_id);

                if (unpublished_id === 'ok') {
                  // That's us
                  opt.server.SFU.hangup();
                  return;
                }

                if (_remote_feed_storage.found) {
                  _remote_feed = opt.storage.feeds[_remote_feed_storage.index];
                  _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Feed " + _remote_feed.feed_id + " (" + _remote_feed.feed_name + ") has left the room, detaching");
                  $("#other_stream_ctnr_" + _remote_feed.feed_id).remove();

                  _remote_feed.detach();

                  opt.storage.feeds.splice(_remote_feed_storage.index, 1);
                  if (opt.initialized) methods.drawParticipants();
                }
              } else if (msg["error"] !== undefined && msg["error"] !== null) {
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.error(msg["error"]);

                if (msg["error_code"] !== undefined && msg["error_code"] === 426) {
                  Messenger.alert().Alert({
                    toast: true,
                    theme: 'error',
                    title: 'Unable to join the streaming room. This session may have ended'
                  });
                } else if (!opt.DEMO) {
                  Messenger.alert().Alert({
                    toast: true,
                    theme: 'error',
                    title: msg["error"]
                  });
                }

                if (opt.DEMO) Config.demoEnded();
              }
            }
          }

          if (jsep !== undefined && jsep !== null) {
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Handling SDP as well...");
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(jsep);
            opt.server.SFU.handleRemoteJsep({
              jsep: jsep
            }); // Check if any of the media we wanted to publish has
            // been rejected (e.g., wrong or unsupported codec)

            var audio = msg["audio_codec"],
                video = msg["video_codec"];

            if (opt.storage.broadcast_stream && opt.storage.broadcast_stream.getAudioTracks() && opt.storage.broadcast_stream.getAudioTracks().length > 0 && !audio) {
              // Audio has been rejected
              toastr.warning("Our audio stream has been rejected, viewers won't hear us");
            }

            if (opt.storage.broadcast_stream && opt.storage.broadcast_stream.getVideoTracks() && opt.storage.broadcast_stream.getVideoTracks().length > 0 && !video) {
              // Video has been rejected
              toastr.warning("Our video stream has been rejected, viewers won't see us"); // Hide the webcam video
            }
          }
        },
        onlocalstream: function onlocalstream(stream) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(" ::: Got a local stream :::");
          opt.storage.broadcast_stream = stream;
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(stream);
          opt.my_stream_ctnr.html(templates.my_video_stream()).show();
          var video_elm = document.getElementById('my_stream_src');
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.attachMediaStream(video_elm, stream);

          if (opt.server.SFU.webrtcStuff.pc.iceConnectionState !== "completed" && opt.server.SFU.webrtcStuff.pc.iceConnectionState !== "connected") {
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug('publishing');
          }

          var videoTracks = stream.getVideoTracks();

          if (videoTracks === null || videoTracks === undefined || videoTracks.length === 0) {
            // No webcam
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug('no stream');
          } else {
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug('stream shown');
          }

          methods.toolbarState();
        },
        onremotestream: function onremotestream(stream) {// The publisher stream is sendonly, we don't expect anything here
        },
        oncleanup: function oncleanup() {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log(" ::: Got a cleanup notification: we are unpublished now :::");
          opt.storage.broadcast_stream = null;
          if (opt.settings.mode !== 'destroyed') opt.my_stream_ctnr.html(templates.observing());
        }
      });
    },
    remote: function remote(id, display, audio, video) {
      var remoteFeed = null;
      opt.server.JANUS.attach({
        plugin: "janus.plugin.videoroom",
        success: function success(pluginHandle) {
          remoteFeed = pluginHandle;
          remoteFeed.simulcastStarted = false;
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Plugin attached! (" + remoteFeed.getPlugin() + ", id=" + remoteFeed.getId() + ")");
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("  -- This is a subscriber");
          var subscribe = {
            request: "join",
            room: CallManager.state().room_id,
            ptype: "subscriber",
            feed: id,
            pin: CallManager.state().room_pin,
            private_id: opt.storage.my_janus_private_id
          };

          if (_Janus__WEBPACK_IMPORTED_MODULE_0__.default.webRTCAdapter.browserDetails.browser === "safari" && (video === "vp9" || video === "vp8" && !_Janus__WEBPACK_IMPORTED_MODULE_0__.default.safariVp8)) {
            if (video) video = video.toUpperCase();
            toastr.warning("Publisher is using " + video + ", but Safari doesn't support it: disabling video");
            subscribe["offer_video"] = false;
          }

          remoteFeed.videoCodec = video;
          remoteFeed.send({
            "message": subscribe
          });
        },
        error: function error(_error3) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.error("  -- Error attaching plugin...", _error3);
        },
        onmessage: function onmessage(msg, jsep) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(" ::: Got a message (subscriber) :::");
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(msg);
          var event = msg["videoroom"];
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Event: " + event);

          if (msg["error"] !== undefined && msg["error"] !== null) {
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.error(msg["error"]);
          } else if (event !== undefined && event !== null) {
            if (event === "attached") {
              var info = JSON.parse('' + msg["display"] + '');
              remoteFeed.feed_id = msg["id"];
              remoteFeed.feed_name = info.name;
              remoteFeed.feed_avatar = info.avatar;
              remoteFeed.feed_owner_id = info.id;
              opt.storage.feeds.push(remoteFeed);
              _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Successfully attached to feed " + remoteFeed.feed_id + " (" + remoteFeed.feed_name + ") in room " + msg["room"]);
            } else if (event === "event") {
              // Check if we got an event on a simulcast-related event from this publisher
              _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(event);
              var substream = msg["substream"],
                  temporal = msg["temporal"];

              if (substream !== null && substream !== undefined || temporal !== null && temporal !== undefined) {//manage simulcast buttons
              }
            } else {// What has just happened?
              }
          }

          if (jsep !== undefined && jsep !== null) {
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Handling SDP as well...");
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(jsep); // Answer and attach

            remoteFeed.createAnswer({
              jsep: jsep,
              media: {
                audioSend: false,
                videoSend: false
              },
              success: function success(jsep) {
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Got SDP!");
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(jsep);
                remoteFeed.send({
                  message: {
                    request: 'start',
                    room: CallManager.state().room_id
                  },
                  jsep: jsep
                });
              },
              error: function error(_error4) {
                _Janus__WEBPACK_IMPORTED_MODULE_0__.default.error("WebRTC error:", _error4);
              }
            });
          }
        },
        webrtcState: function webrtcState(on) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Janus says this WebRTC PeerConnection (feed #" + remoteFeed.feed_id + ") is " + (on ? "up" : "down") + " now");
        },
        onlocalstream: function onlocalstream(stream) {// The subscriber stream is recvonly, we don't expect anything here
        },
        onremotestream: function onremotestream(stream) {
          if (!opt.initialized || opt.settings.mode === 'destroyed') return;
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Remote feed #" + remoteFeed.feed_id);
          $('.observer-' + remoteFeed.feed_id).remove();

          var video_elm = document.getElementById('other_stream_src_' + remoteFeed.feed_id),
              refreshVideoElm = function refreshVideoElm() {
            video_elm = document.getElementById('other_stream_src_' + remoteFeed.feed_id);
          };

          if (!video_elm) {
            opt.other_stream_ctnr.prepend(templates.other_video_stream(remoteFeed));
            refreshVideoElm();
          }

          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.attachMediaStream(video_elm, stream);

          video_elm.onpause = function () {
            methods.manageStreamPlayIcon(remoteFeed.feed_id, true);
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('paused');
          };

          video_elm.onplay = function () {
            methods.manageStreamPlayIcon(remoteFeed.feed_id, false);
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('playing');
          };

          video_elm.onvolumechange = function () {
            methods.toggleStreamMute(remoteFeed.feed_id, true);
          };

          setTimeout(function () {
            methods.manageStreamPlayIcon(remoteFeed.feed_id, video_elm.paused);
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('Is Paused??', video_elm.paused);
          }, 1000);
          opt.empty_room.hide();
        },
        oncleanup: function oncleanup() {
          if (!opt.initialized || opt.settings.mode === 'destroyed') return;
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log(" ::: Got a cleanup notification (remote feed " + remoteFeed.feed_id + ") :::");
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log("Publisher left: " + remoteFeed.feed_id);
          var remote_feed_storage = methods.locateStorageItem('feed', remoteFeed.feed_id);
          $("#other_stream_ctnr_" + remoteFeed.feed_id).remove();

          if (remote_feed_storage.found) {
            opt.storage.feeds.splice(remote_feed_storage.index, 1);
          } // if(!opt.storage.feeds.length && opt.initialized) opt.empty_room.show();


          if (opt.initialized) methods.drawParticipants();
        }
      });
    }
  },
      methods = {
    getParticipants: function getParticipants() {
      opt.server.SFU.send({
        message: {
          request: "listparticipants",
          room: CallManager.state().room_id
        },
        success: function success(msg) {
          opt.storage.participants = [];
          var list = msg["participants"];
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log(" ::: Got a participant listing");
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log(list);

          for (var f in list) {
            if (!list.hasOwnProperty(f) || list[f]['id'] === opt.storage.my_janus_id) continue;
            methods.addParticipant(list[f]);
          }

          methods.drawParticipants();
        }
      });
    },
    addParticipant: function addParticipant(participant) {
      try {
        if (methods.locateStorageItem('participant', participant['id']).found) return;
        var info = JSON.parse('' + participant['display'] + '');
        opt.storage.participants.push({
          id: participant['id'],
          name: info.name,
          avatar: info.avatar,
          owner_id: info.id,
          publisher: participant['publisher']
        });
      } catch (e) {
        _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log(e);
      }
    },
    removeParticipant: function removeParticipant(id) {
      var participant = methods.locateStorageItem('participant', id);

      if (participant.found) {
        opt.storage.participants.splice(participant.index, 1);
      }

      $("#other_stream_ctnr_" + id).remove();
      methods.drawParticipants();
    },
    drawParticipants: function drawParticipants() {
      if (!opt.storage.participants.length) {
        opt.empty_room.show();
        return;
      }

      opt.empty_room.hide();
      opt.storage.participants.forEach(function (participant) {
        if ($('#other_stream_src_' + participant.id).length || $('#other_stream_ctnr_' + participant.id).length) return;
        opt.other_stream_ctnr.append(templates.observer(participant));
      });
    },
    publishOwnFeed: function publishOwnFeed(opts, callback, fail) {
      opts = opts || {};
      opt.server.SFU.createOffer({
        media: {
          audioRecv: false,
          videoRecv: false,
          removeAudio: opts.hasOwnProperty('removeAudio') ? opts.removeAudio : false,
          removeVideo: opts.hasOwnProperty('removeVideo') ? opts.removeVideo : false,
          replaceVideo: opts.hasOwnProperty('replaceVideo') ? opts.replaceVideo : false,
          addAudio: opts.hasOwnProperty('addAudio') ? opts.addAudio : false,
          addVideo: opts.hasOwnProperty('addVideo') ? opts.addVideo : false,
          audioSend: opts.hasOwnProperty('audioSend') ? opts.audioSend : opt.settings.audio,
          videoSend: opts.hasOwnProperty('videoSend') ? opts.videoSend : opt.settings.video,
          video: opts.hasOwnProperty('video') ? opts.video : opt.settings.video_constraints.term,
          screenshareFrameRate: opts.hasOwnProperty('screenshareFrameRate') ? opts.screenshareFrameRate : null
        },
        simulcast: opt.settings.simulcast_1,
        simulcast2: opt.settings.simulcast_2,
        success: function success(jsep) {
          if (opts.mode) opt.settings.mode = opts.mode;
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug("Got publisher SDP!");
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.debug(jsep);
          var message = {
            request: 'configure',
            audio: opts.hasOwnProperty('audioSend') ? opts.audioSend : opt.settings.audio,
            video: opts.hasOwnProperty('videoSend') ? opts.videoSend : opt.settings.video
          };
          if (opts.hasOwnProperty('bitrate')) message.bitrate = opts.bitrate; // if(opts.display) message.display = JSON.stringify(opt.storage.display);

          opt.server.SFU.send({
            message: message,
            jsep: jsep
          });
          if (callback && typeof callback === 'function') setTimeout(callback, 500);
        },
        error: function error(_error5) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.error("WebRTC error:", _error5);

          if (fail && typeof fail === 'function') {
            setTimeout(fail, 500);
            return;
          }

          if (opt.settings.video && opt.settings.audio) {
            opt.settings.video = false;
            opt.settings.mode = 'audio';
            opt.settings._mode = 'audio';
            methods.publishOwnFeed({
              video: false
            });
          } else {
            opt.settings.audio = false;
            opt.settings.mode = 'unpublished';
            opt.settings._mode = 'unpublished';
            _Janus__WEBPACK_IMPORTED_MODULE_0__.default.error("WebRTC error, all failed");
            opt.my_stream_ctnr.html(templates.observing()).show();
            methods.toolbarState();
            Messenger.alert().Alert({
              toast: true,
              theme: 'info',
              title: 'Unable to load your media devices. Proceeding as an observer'
            });
          }
        }
      });
    },
    toggleVideo: function toggleVideo() {
      if (!opt.initialized || opt.settings.screen || opt.TUTORIAL || !opt.settings.video || ['destroyed', 'unpublished'].includes(opt.settings.mode)) return;
      var muted = opt.server.SFU.isVideoMuted();
      _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log((muted ? "Unmuting" : "Muting") + " video stream...");

      if (!muted) {
        opt.settings.video_paused = true;
        opt.server.SFU.muteVideo();
        methods.publishOwnFeed({
          removeVideo: true,
          videoSend: false,
          video: false
        });
      } else {
        opt.settings.video_paused = false;
        opt.server.SFU.unmuteVideo();
        methods.publishOwnFeed({
          addVideo: true
        });
      }

      methods.toolbarState();
    },
    toggleMute: function toggleMute() {
      if (!opt.initialized || opt.TUTORIAL || !opt.settings.audio || ['destroyed', 'unpublished'].includes(opt.settings.mode)) return;
      var muted = opt.server.SFU.isAudioMuted();
      _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log((muted ? "Unmuting" : "Muting") + " local stream...");

      if (muted) {
        opt.server.SFU.unmuteAudio();
        opt.settings.muted = false;
      } else {
        opt.server.SFU.muteAudio();
        opt.settings.muted = true;
      }

      methods.toolbarState();
    },
    toggleShareScreen: function toggleShareScreen() {
      if (!opt.initialized || opt.TUTORIAL || ['destroyed', 'unpublished'].includes(opt.settings.mode)) return;

      if (!opt.settings.screen) {
        var config = {
          video: "screen",
          screenshareFrameRate: 30,
          bitrate: opt.DEMO ? 600000 : 1024000,
          mode: 'screen'
        };

        if (opt.settings.video_paused || !opt.settings.video) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('Video was paused, adding video');
          config.addVideo = true;
          config.videoSend = true;
        } else {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('Video existed, replacing instead');
          config.replaceVideo = true;
        }

        methods.publishOwnFeed(config, methods.screenShareReady, methods.screenShareRemove);
      } else {
        methods.screenShareRemove();
      }
    },
    screenShareReady: function screenShareReady() {
      opt.settings.screen = true;
      methods.toolbarState();

      opt.storage.broadcast_stream.getVideoTracks()[0].onended = function () {
        if (opt.settings.screen) {
          _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('Screen stopped by track ending');
          methods.toggleShareScreen();
        }
      };

      if (opt.server.call_socket) {
        opt.server.call_socket.whisper('screen_share_started', {
          name: Messenger.common().name,
          owner_id: Messenger.common().id,
          janus_id: opt.storage.my_janus_id
        });
      }
    },
    screenShareRemove: function screenShareRemove() {
      opt.settings.screen = false;
      opt.settings.mode = 'default';

      if (opt.settings.video_paused || !opt.settings.video) {
        _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('Remove screen media');
        if (!opt.settings.video) opt.settings.mode = 'audio';
        methods.publishOwnFeed({
          removeVideo: true,
          videoSend: false,
          video: false,
          bitrate: opt.settings.bitrate
        });
      } else {
        _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('Replace screen with webcam');
        methods.publishOwnFeed({
          replaceVideo: true,
          bitrate: opt.settings.bitrate
        });
      }

      if (opt.server.call_socket) {
        opt.server.call_socket.whisper('screen_share_ended', {
          name: Messenger.common().name,
          owner_id: Messenger.common().id,
          janus_id: opt.storage.my_janus_id
        });
      }

      methods.toolbarState();
    },
    unpublish: function unpublish() {
      if (!opt.initialized || opt.TUTORIAL || ['destroyed', 'unpublished'].includes(opt.settings.mode)) return;
      opt.settings._mode = opt.settings.mode;
      opt.settings.mode = 'unpublished';
      opt.settings.screen = false;
      opt.settings.video_paused = false;
      opt.settings.muted = false;
      methods.toolbarState();

      if (opt.server.SFU) {
        opt.server.SFU.send({
          message: {
            request: 'unpublish'
          },
          success: methods.toolbarState
        });
      }
    },
    publish: function publish() {
      if (!opt.initialized || opt.TUTORIAL || opt.settings._mode === 'unpublished' || opt.settings.mode !== 'unpublished') return;
      opt.settings.mode = opt.settings._mode;
      methods.toolbarState();

      if (!opt.settings.video) {
        _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('Add audio only');
        methods.publishOwnFeed({
          videoSend: false,
          video: false
        });
      } else {
        _Janus__WEBPACK_IMPORTED_MODULE_0__.default.log('Add audio and video');
        methods.publishOwnFeed();
      }
    },
    leaveRoom: function leaveRoom() {
      if (!opt.initialized || opt.TUTORIAL || opt.settings.mode === 'destroyed' || CallManager.state().call_type === 1) return;
      Config.destroy(false, function () {
        opt.my_stream_ctnr.html('<div class="h5 p-2 text-danger">You left the streaming room. Use the settings toggle above to re-join</div>').show();
      });
    },
    joinRoom: function joinRoom() {
      if (!opt.initialized || opt.TUTORIAL || opt.settings.mode !== 'destroyed' || CallManager.state().call_type === 1) return;
      Config.setup();
    },
    locateStorageItem: function locateStorageItem(type, id) {
      var collection,
          term,
          item = {
        found: false,
        index: 0
      };

      switch (type) {
        case 'feed':
          collection = opt.storage.feeds;
          term = 'feed_id';
          break;

        case 'participant':
          collection = opt.storage.participants;
          term = 'id';
          break;
      }

      for (var i = 0; i < collection.length; i++) {
        if (collection[i][term] === id) {
          item.found = true;
          item.index = i;
          break;
        }
      }

      return item;
    },
    hangUp: function hangUp(end) {
      Messenger.button().addLoader({
        id: end ? '#end_call_btn' : '#hang_up_btn'
      });
      Config.destroy();
      end ? CallManager.endCall() : CallManager.leave(false);
    },
    maximizePublisher: function maximizePublisher(publisher) {
      Messenger.alert().Modal({
        wait_for_others: true,
        size: 'sm',
        icon: 'chalkboard-teacher',
        unlock_buttons: false,
        backdrop_ctrl: false,
        centered: true,
        title: 'Maximize Stream?',
        theme: 'info',
        body: publisher.name + ' is sharing their screen. Would you like to maximize their stream?',
        cb_btn_txt: 'Maximize',
        cb_btn_icon: 'chalkboard-teacher',
        cb_btn_theme: 'success',
        callback: function callback() {
          try {
            methods.requestFullScreen(publisher.janus_id);
          } catch (e) {
            console.log(e);
          }
        },
        cb_close: true,
        timer: 15000
      });
      NotifyManager.sound('notify');
    },
    toolbarState: function toolbarState(force) {
      var rtc_opt = $(".rtc_nav_opt"),
          rtc_vid = $(".rtc_nav_video"),
          rtc_audio = $(".rtc_nav_audio"),
          rtc_screen = $(".rtc_nav_screen"),
          rtc_vid_on = $(".rtc_video_on"),
          rtc_vid_off = $(".rtc_video_off"),
          rtc_audio_on = $(".rtc_audio_on"),
          rtc_audio_off = $(".rtc_audio_off"),
          rtc_screen_on = $(".rtc_screen_on"),
          rtc_screen_off = $(".rtc_screen_off"),
          rtc_options_dropdown = $("#rtc_options_dropdown");
      rtc_opt.hide();
      rtc_options_dropdown.html('');
      if (!opt.initialized) return;

      switch (opt.settings.mode) {
        case 'default':
          if (opt.settings.video_paused) {
            rtc_vid.show();
            rtc_vid_off.show();
            rtc_audio.show();
            opt.settings.muted ? rtc_audio_off.show() : rtc_audio_on.show();
            rtc_screen.show();
            rtc_screen_off.show();
          } else if (opt.settings.video && opt.settings.muted) {
            rtc_vid.show();
            rtc_vid_on.show();
            rtc_audio.show();
            rtc_audio_off.show();
            rtc_screen.show();
            rtc_screen_off.show();
          } else if (opt.settings.video && opt.settings.audio) {
            rtc_vid.show();
            rtc_vid_on.show();
            rtc_audio.show();
            rtc_audio_on.show();
            rtc_screen.show();
            rtc_screen_off.show();
          }

          break;

        case 'video':
          if (opt.settings.video_paused) {
            rtc_vid.show();
            rtc_vid_off.show();
            rtc_screen.show();
            rtc_screen_off.show();
          } else {
            rtc_vid.show();
            rtc_vid_on.show();
            rtc_screen.show();
            rtc_screen_off.show();
          }

          break;

        case 'audio':
          if (opt.settings.muted) {
            rtc_audio.show();
            rtc_audio_off.show();
            rtc_screen.show();
            rtc_screen_off.show();
          } else {
            rtc_audio.show();
            rtc_audio_on.show();
            rtc_screen.show();
            rtc_screen_off.show();
          }

          break;

        case 'screen':
          rtc_screen.show();
          rtc_screen_on.show();

          if (opt.settings.audio) {
            rtc_audio.show();
            opt.settings.muted ? rtc_audio_off.show() : rtc_audio_on.show();
          }

          break;

        case 'unpublished':
          break;

        case 'destroyed':
          break;
      }

      rtc_options_dropdown.html(templates.wb_room_dropdown());
      PageListeners.listen().tooltips();
    },
    tutorialMode: function tutorialMode(power) {
      opt.TUTORIAL = power;
      methods.toolbarState(true);
    },
    manageStreamPlayIcon: function manageStreamPlayIcon(id, power) {
      var play_btn = $("#publisher_play_" + id);
      power ? play_btn.show() : play_btn.hide();
    },
    requestFullScreen: function requestFullScreen(id) {
      var stream = document.getElementById('other_stream_src_' + id),
          maxStream = function maxStream() {
        if (stream.requestFullscreen) {
          stream.requestFullscreen();
        } else if (stream.mozRequestFullScreen) {
          /* Firefox */
          stream.mozRequestFullScreen();
        } else if (stream.webkitRequestFullscreen) {
          /* Chrome, Safari & Opera */
          stream.webkitRequestFullscreen();
        } else if (stream.msRequestFullscreen) {
          /* IE/Edge */
          stream.msRequestFullscreen();
        }
      };

      if (stream) {
        maxStream();
      }
    },
    toggleStreamMute: function toggleStreamMute(id, check) {
      var stream = document.getElementById('other_stream_src_' + id),
          toggle = $('#publisher_sound_toggle_' + id),
          volume_on = '<i class="fas fa-volume-up"></i>',
          volume_off = '<i class="fas fa-volume-mute"></i>';

      if (stream) {
        if (check) {
          toggle.html(stream.muted ? volume_off : volume_on);
          return;
        }

        if (stream.muted) {
          stream.muted = false;
          toggle.html(volume_on);
        } else {
          stream.muted = true;
          toggle.html(volume_off);
        }
      }
    },
    playStream: function playStream(id) {
      var stream = document.getElementById('other_stream_src_' + id);
      if (stream) stream.play();
    }
  },
      templates = {
    wb_room_dropdown: function wb_room_dropdown() {
      var unpublish = '<a class="dropdown-item" onclick="JanusServer.unpublish(); return false;" href="#"><i class="fas fa-stop"></i> Unpublish Stream</a>',
          publish = '<a class="dropdown-item" onclick="JanusServer.publish(); return false;" href="#"><i class="fas fa-play"></i> Publish Stream</a>',
          leave = '<a class="dropdown-item" onclick="JanusServer.leave(); return false;" href="#"><i class="fas fa-sign-out-alt"></i> Leave Room</a>',
          join = '<a class="dropdown-item" onclick="JanusServer.join(); return false;" href="#"><i class="fas fa-sign-in-alt"></i> Join Room</a>';

      switch (opt.settings.mode) {
        case 'destroyed':
          return join;

        case 'unpublished':
          if (opt.settings._mode === 'unpublished') return leave;
          return publish + leave;

        default:
          if (CallManager.state().call_type === 1) return unpublish;
          return unpublish + leave;
      }
    },
    my_video_stream: function my_video_stream() {
      return '<div class="shadow-sm rounded w-100 mx-auto embed-responsive embed-responsive-16by9">' + '<video style="background: url(\'' + Messenger.common().avatar_md + '\') no-repeat 50% 50%; background-size: contain;" id="my_stream_src" muted autoplay playsinline class="embed-responsive-item"></video>' + '</div>';
    },
    observing: function observing() {
      return '<div class="shadow-sm rounded w-100 mx-auto embed-responsive embed-responsive-16by9">' + '<div id="my_stream_src" style="background: url(\'' + Messenger.common().avatar_md + '\') no-repeat 50% 50%; background-size: contain;"  class="embed-responsive-item" /></div>' + '</div>';
    },
    other_video_stream: function other_video_stream(user) {
      return '<div class="col-12 ' + (CallManager.state().thread_type === 2 ? 'col-md-6 col-lg-4' : '') + ' mt-2 mb-4 other_stream_ctnr" id="other_stream_ctnr_' + user.feed_id + '">' + '<div class="col-12 text-center h4"><span class="badge badge-pill badge-light shadow">' + user.feed_name + '</span></div>' + '<div class="group_stream w-100 mx-auto embed-responsive embed-responsive-16by9">' + '<video style="background: url(\'' + user.feed_avatar + '\') no-repeat 50% 50%; background-size: contain;" id="other_stream_src_' + user.feed_id + '" autoplay playsinline class="other-janus-stream embed-responsive-item"></video><span class="player_main_controls">' + templates.control_buttons(user.feed_id, true) + '</span>' + templates.play_button(user.feed_id, true) + '</div>' + '</div>';
    },
    observer: function observer(user) {
      return '<div class="col-12 ' + (CallManager.state().thread_type === 2 ? 'col-md-6 col-lg-4' : '') + ' mt-2 mb-4 other_stream_ctnr observer-' + user.id + '" id="other_stream_ctnr_' + user.id + '">' + '<div class="col-12 text-center h4"><span class="badge badge-pill badge-light shadow">' + user.name + ' - Watching</span></div>' + '<div class="group_stream w-100 mx-auto embed-responsive embed-responsive-16by9">' + '<div style="background: url(\'' + user.avatar + '\') no-repeat 50% 50%; background-size: contain;"  class="embed-responsive-item" /></div></div>' + '</div>';
    },
    control_buttons: function control_buttons(id, large) {
      return '<span class="player_control_expand"><button title="Full Screen" type="button" onclick="JanusServer.fullScreen(\'' + id + '\')" class="btn ' + (large ? 'btn-lg' : 'btn-sm') + ' text-white bg-dark"><i class="fas fa-expand"></i></button></span>' + '<span class="player_control_sound"><button title="Mute/Unmute" id="publisher_sound_toggle_' + id + '" type="button" onclick="JanusServer.toggleStreamMute(\'' + id + '\')" class="btn ' + (large ? 'btn-lg' : 'btn-sm') + ' text-white bg-dark"><i class="fas fa-volume-up"></i></button></span>';
    },
    play_button: function play_button(id, large) {
      return '<span id="publisher_play_' + id + '" class="player_control_play' + (large ? '_large' : '') + ' NS"><button title="Play" type="button" onclick="JanusServer.playStream(\'' + id + '\')" class="btn btn-circle btn-circle-' + (large ? 'xl' : 'lg') + ' text-white bg-success glowing_warning_btn"><i class="fas fa-play-circle fa-3x"></i></button></span>';
    },
    loading_media: function loading_media() {
      return '<div class="col-12 text-center mt-2">\n' + '   <span class="h4">\n' + '     <span class="badge badge-pill badge-light">Loading media <span class="spinner-border spinner-border-sm text-primary" role="status"></span></span>\n' + '     </span>\n' + ' </div>';
    }
  };
  return {
    config: function config() {
      return Config;
    },
    hangUp: methods.hangUp,
    toggleScreenShare: methods.toggleShareScreen,
    toggleMute: methods.toggleMute,
    toggleVideo: methods.toggleVideo,
    unpublish: methods.unpublish,
    publish: methods.publish,
    join: methods.joinRoom,
    leave: methods.leaveRoom,
    toolbar: methods.toolbarState,
    tutorial: methods.tutorialMode,
    fullScreen: methods.requestFullScreen,
    toggleStreamMute: methods.toggleStreamMute,
    playStream: methods.playStream,
    socket: function socket() {
      return {
        onDisconnect: Sockets.disconnected,
        onReconnect: Sockets.reconnected,
        peerJoin: Sockets.joining,
        peerLeave: Sockets.leaving
      };
    },
    lock: function lock(arg) {
      if (typeof arg === 'boolean') opt.lock = arg;
    }
  };
}();
})();

/******/ })()
;