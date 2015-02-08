> This is a sandbox project, which contains experimental code for developer use only. Please wait for release!

# Node.js integration with e107 v2

This plugin integrates Node.js with e107 v2. It provides an API that other plugins can use to add realtime capabilities to e107 (v2), specifically enabling pushing updates to open connected clients.

## Install Node.js

### Install nodejs from aptitude package manager:
```
sudo apt-get install python-software-properties
sudo add-apt-repository ppa:chris-lea/node.js
sudo apt-get update
```

As of Node.js v0.10.0, the nodejs package from Chris Lea's repo includes both npm and nodejs-dev. So just...
```
sudo apt-get install nodejs
```

### Install required Node.js modules with the Node Package Manager (NPM)

Install e107 Node.js integration plugin and go to the directory where node.js integration is installed.
```
cd path/to/e107/e107_plugins/nodejs
```

Make sure you are in the nodejs plugin directory - NPM needs the package.json file that comes with the nodejs plugin to install the right modules.
```
sudo npm install
sudo npm install socket.io
sudo npm install request
sudo npm install express
sudo npm install connect
```

NOTE: Some have found the need to target versions for the Express and Connect node modules:
```
sudo npm --node-version=0.4.12 install express
sudo npm --node-version=0.4.12 install connect
```

OPTIONAL: install node-gyp, so that the ws package is faster.
```
sudo npm install -g node-gyp
```

### Create a 'nodejs.config.js' file in your nodejs plugin directory.

Read the 'nodejs.config.js.example' file. Set debug to false when you are happy with your setup.

### Run the node server with the command: 
```
node server.js
```

As long as you have 'debug: true' in your configuration file, you'll see lots of helpful messages.

### Testing

A simple test to determine if the plugin is working is to monitor the terminal window or ssh terminal as you broadcast notification messages.

