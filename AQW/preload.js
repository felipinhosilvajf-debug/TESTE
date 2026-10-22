const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('aqwClient', {
  getConfig: () => ipcRenderer.invoke('aqw:get-config')
});
