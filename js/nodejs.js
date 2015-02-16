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
    var scheme = e107Nodejs.settings.client.secure ? 'https' : 'http',
      url = scheme + '://' + e107Nodejs.settings.client.host + ':' + e107Nodejs.settings.client.port;

    e107Nodejs.settings.connectTimeout = e107Nodejs.settings.connectTimeout || 5000;

    if (typeof io === 'undefined') {
      return false;
    }

    e107Nodejs.Nodejs.socket = io.connect(url, {'connect timeout': e107Nodejs.settings.connectTimeout});
    e107Nodejs.Nodejs.socket.on('connect', function () {
      e107Nodejs.Nodejs.sendAuthMessage();
      e107Nodejs.Nodejs.runSetupHandlers('connect');
    });

    e107Nodejs.Nodejs.socket.on('message', e107Nodejs.Nodejs.runCallbacks);

    e107Nodejs.Nodejs.socket.on('disconnect', function () {
      e107Nodejs.Nodejs.runSetupHandlers('disconnect');
    });
    setTimeout("e107Nodejs.Nodejs.checkConnection()", e107Nodejs.settings.connectTimeout + 250);
  };

  e107Nodejs.Nodejs.checkConnection = function () {
    if (!e107Nodejs.Nodejs.socket.connected) {
      e107Nodejs.Nodejs.runSetupHandlers('connectionFailure');
    }
  };

  e107Nodejs.Nodejs.sendAuthMessage = function () {
    var authMessage = {
      authToken: e107Nodejs.settings.authToken,
      contentTokens: e107Nodejs.settings.contentTokens
    };
    e107Nodejs.Nodejs.socket.emit('authenticate', authMessage);
  };

  $(document).ready(function() {
    if (!e107Nodejs.Nodejs.socket) {
      setTimeout(function () {
        e107Nodejs.Nodejs.connect();
      }, 1000);
    }
  });

})(jQuery);
