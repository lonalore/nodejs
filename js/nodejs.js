(function ($) {

  e107Nodejs.Nodejs = e107Nodejs.Nodejs || {
    'contentChannelNotificationCallbacks': {},
    'presenceCallbacks': {},
    'callbacks': {},
    'socket': false,
    'connectionSetupHandlers': {}
  };

  e107Nodejs.Nodejs.runCallbacks = function (message) {
    // It's possible that this message originated from an ajax request from the
    // client associated with this socket.
    if (message.clientSocketId == e107Nodejs.Nodejs.socket.sessionid) {
      return;
    }
    if (message.callback) {
      if (typeof message.callback == 'string') {
        message.callback = [message.callback];
      }
      $.each(message.callback, function () {
        var callback = this;
        if (e107Nodejs.Nodejs.callbacks[callback] && $.isFunction(e107Nodejs.Nodejs.callbacks[callback].callback)) {
          try {
            e107Nodejs.Nodejs.callbacks[callback].callback(message);
          }
          catch (exception) {
          }
        }
      });
    }
    else if (message.presenceNotification != undefined) {
      $.each(e107Nodejs.Nodejs.presenceCallbacks, function () {
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
      $.each(e107Nodejs.Nodejs.contentChannelNotificationCallbacks, function () {
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
      $.each(e107Nodejs.Nodejs.callbacks, function () {
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

  e107Nodejs.Nodejs.runSetupHandlers = function (type) {
    $.each(e107Nodejs.Nodejs.connectionSetupHandlers, function () {
      if ($.isFunction(this[type])) {
        try {
          this[type]();
        }
        catch (exception) {
        }
      }
    });
  };

  e107Nodejs.Nodejs.connect = function () {
    var scheme = e107Nodejs.settings.nodejs.client.secure ? 'https' : 'http',
      url = scheme + '://' + e107Nodejs.settings.nodejs.client.host + ':' + e107Nodejs.settings.nodejs.client.port;

    e107Nodejs.settings.nodejs.connectTimeout = e107Nodejs.settings.nodejs.connectTimeout || 5000;

    if (typeof io === 'undefined') {
      return false;
    }

    e107Nodejs.Nodejs.socket = io.connect(url, {'connect timeout': e107Nodejs.settings.nodejs.connectTimeout});
    e107Nodejs.Nodejs.socket.on('connect', function () {
      e107Nodejs.Nodejs.sendAuthMessage();
      e107Nodejs.Nodejs.runSetupHandlers('connect');
      if (e107Nodejs.ajax != undefined) {
        // Monkey-patch e107Nodejs.ajax.prototype.beforeSerialize to auto-magically
        // send sessionId for AJAX requests so we can exclude the current browser
        // window from resulting notifications. We do this so that modules can hook
        // in to other modules ajax requests without having to patch them.
        e107Nodejs.Nodejs.originalBeforeSerialize = e107Nodejs.ajax.prototype.beforeSerialize;
        e107Nodejs.ajax.prototype.beforeSerialize = function (element_settings, options) {
          options.data['nodejs_client_socket_id'] = e107Nodejs.Nodejs.socket.sessionid;
          return e107Nodejs.Nodejs.originalBeforeSerialize(element_settings, options);
        };
      }
    });

    e107Nodejs.Nodejs.socket.on('message', e107Nodejs.Nodejs.runCallbacks);

    e107Nodejs.Nodejs.socket.on('disconnect', function () {
      e107Nodejs.Nodejs.runSetupHandlers('disconnect');
      if (e107Nodejs.ajax != undefined) {
        e107Nodejs.ajax.prototype.beforeSerialize = e107Nodejs.Nodejs.originalBeforeSerialize;
      }
    });
    setTimeout("e107Nodejs.Nodejs.checkConnection()", e107Nodejs.settings.nodejs.connectTimeout + 250);
  };

  e107Nodejs.Nodejs.checkConnection = function () {
    if (!e107Nodejs.Nodejs.socket.connected) {
      e107Nodejs.Nodejs.runSetupHandlers('connectionFailure');
    }
  };

  e107Nodejs.Nodejs.sendAuthMessage = function () {
    var authMessage = {
      authToken: e107Nodejs.settings.nodejs.authToken,
      contentTokens: e107Nodejs.settings.nodejs.contentTokens
    };
    e107Nodejs.Nodejs.socket.emit('authenticate', authMessage);
  };

})(jQuery);
