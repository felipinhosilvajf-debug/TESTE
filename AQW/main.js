const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');
const fs = require('fs');
const net = require('net');
const WebSocket = require('ws');

const CONFIG_PATH = path.join(__dirname, 'config.json');
const CONFIG = JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8'));
const PROXY_HOST = '127.0.0.1';
const PROXY_PORT = Number(CONFIG.proxyPort || 8181);

function validConfig() {
  return Boolean(
    typeof CONFIG.gameSwfUrl === 'string' &&
    CONFIG.gameSwfUrl.startsWith('http') &&
    typeof CONFIG.socketHost === 'string' &&
    CONFIG.socketHost.trim() &&
    typeof CONFIG.tcpTargetHost === 'string' &&
    CONFIG.tcpTargetHost.trim() &&
    Array.isArray(CONFIG.socketPorts) &&
    CONFIG.socketPorts.length > 0 &&
    CONFIG.socketPorts.every(p => Number.isInteger(Number(p)) && Number(p) > 0 && Number(p) <= 65535)
  );
}

// Localhost-only bridge. It forwards only the host and ports configured by the server owner.
const wss = new WebSocket.Server({
  host: PROXY_HOST,
  port: PROXY_PORT,
  perMessageDeflate: false
});

wss.on('connection', (ws, req) => {
  const url = new URL(req.url, 'http://localhost');
  const requestedHost = url.searchParams.get('host');
  const requestedPort = Number(url.searchParams.get('port'));
  const allowedPorts = (CONFIG.socketPorts || []).map(Number);

  if (
    !CONFIG.socketHost ||
    requestedHost !== CONFIG.socketHost ||
    !allowedPorts.includes(requestedPort) ||
    !CONFIG.tcpTargetHost
  ) {
    ws.close(1008, 'Socket host/port is not configured for this client');
    return;
  }

  const tcp = net.createConnection({
    host: CONFIG.tcpTargetHost,
    port: requestedPort
  });

  tcp.on('data', data => {
    if (ws.readyState === WebSocket.OPEN) ws.send(data);
  });
  tcp.on('close', () => {
    if (ws.readyState === WebSocket.OPEN) ws.close();
  });
  tcp.on('error', () => {
    if (ws.readyState === WebSocket.OPEN) ws.close(1011, 'TCP connection failed');
  });

  ws.on('message', data => {
    if (!tcp.destroyed) tcp.write(data);
  });
  ws.on('close', () => tcp.destroy());
  ws.on('error', () => tcp.destroy());
});

ipcMain.handle('aqw:get-config', () => ({
  gameSwfUrl: CONFIG.gameSwfUrl,
  socketHost: CONFIG.socketHost,
  socketPorts: (CONFIG.socketPorts || []).map(Number),
  proxyUrl: `ws://${PROXY_HOST}:${PROXY_PORT}`,
  configured: validConfig()
}));

function createWindow() {
  const win = new BrowserWindow({
    width: 1280,
    height: 800,
    minWidth: 960,
    minHeight: 600,
    autoHideMenuBar: true,
    show: false,
    title: 'MeuAQWClient',
    backgroundColor: '#080b14',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
      sandbox: true,
      webgl: true,
      backgroundThrottling: false
    }
  });

  win.loadFile(path.join(__dirname, 'index.html'));
  win.once('ready-to-show', () => win.show());
}

app.whenReady().then(() => {
  createWindow();
  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('window-all-closed', () => {
  wss.close();
  if (process.platform !== 'darwin') app.quit();
});
