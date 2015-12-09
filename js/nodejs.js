var e107 = e107 || {'settings': {}, 'behaviors': {}};

(function ($) {

    e107.Nodejs = e107.Nodejs || {
        'contentChannelNotificationCallbacks': {},
        'presenceCallbacks': {},
        'callbacks': {},
        'socket': false,
        'connectionSetupHandlers': {}
    };

    e107.behaviors.nodejs = {
        attach: function (context, settings) {
            if (!e107.Nodejs.socket) {
                e107.Nodejs.connect();
            }
        }
    };

    e107.Nodejs.runCallbacks = function (message) {
        // It's possible that this message originated from an ajax request from the
        // client associated with this socket.
        if (message.clientSocketId == e107.Nodejs.socket.sessionid) {
            return;
        }
        if (message.callback) {
            if (typeof message.callback == 'string') {
                message.callback = [message.callback];
            }
            $.each(message.callback, function () {
                var callback = this;
                if (e107.Nodejs.callbacks[callback] && $.isFunction(e107.Nodejs.callbacks[callback].callback)) {
                    try {
                        e107.Nodejs.callbacks[callback].callback(message);
                    }
                    catch (exception) {
                    }
                }
            });
        }
        else if (message.presenceNotification != undefined) {
            $.each(e107.Nodejs.presenceCallbacks, function () {
                if ($.isFunction(this.callback)) {
                    try {
                        this.callback(message);
                    }
                    catch (exception) {
                    }
                }
            });
        }
        else if (message.contentChannelNotification != undefined) {
            $.each(e107.Nodejs.contentChannelNotificationCallbacks, function () {
                if ($.isFunction(this.callback)) {
                    try {
                        this.callback(message);
                    }
                    catch (exception) {
                    }
                }
            });
        }
        else {
            $.each(e107.Nodejs.callbacks, function () {
                if ($.isFunction(this.callback)) {
                    try {
                        this.callback(message);
                    }
                    catch (exception) {
                    }
                }
            });
        }
    };

    e107.Nodejs.runSetupHandlers = function (type) {
        $.each(e107.Nodejs.connectionSetupHandlers, function () {
            if ($.isFunction(this[type])) {
                try {
                    this[type]();
                }
                catch (exception) {
                }
            }
        });
    };

    e107.Nodejs.connect = function () {
        var scheme = e107.settings.nodejs.client.secure ? 'https' : 'http',
            url = scheme + '://' + e107.settings.nodejs.client.host + ':' + e107.settings.nodejs.client.port;

        e107.settings.nodejs.connectTimeout = e107.settings.nodejs.connectTimeout || 5000;

        if (typeof io === 'undefined') {
            return false;
        }

        e107.Nodejs.socket = io.connect(url, {'connect timeout': e107.settings.nodejs.connectTimeout});
        e107.Nodejs.socket.on('connect', function () {
            e107.Nodejs.sendAuthMessage();
            e107.Nodejs.runSetupHandlers('connect');
        });

        e107.Nodejs.socket.on('message', e107.Nodejs.runCallbacks);

        e107.Nodejs.socket.on('disconnect', function () {
            e107.Nodejs.runSetupHandlers('disconnect');
        });
        setTimeout("e107.Nodejs.checkConnection()", e107.settings.nodejs.connectTimeout + 250);
    };

    e107.Nodejs.checkConnection = function () {
        if (!e107.Nodejs.socket.connected) {
            e107.Nodejs.runSetupHandlers('connectionFailure');
        }
    };

    e107.Nodejs.sendAuthMessage = function () {
        var authMessage = {
            authToken: e107.settings.nodejs.authToken,
            contentTokens: e107.settings.nodejs.contentTokens
        };
        e107.Nodejs.socket.emit('authenticate', authMessage);
    };

})(jQuery);
