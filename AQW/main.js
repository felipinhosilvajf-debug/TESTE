const { app, BrowserWindow } = require('electron');
const path = require('path');

function createWindow() {
    const win = new BrowserWindow({
        width: 960,
        height: 650,
        autoHideMenuBar: true,
        title: "Meu Servidor Privado AQW",
        webPreferences: {
            nodeIntegration: true, // Ativa para o Ruffle funcionar localmente
            contextIsolation: false
        }
    });

    // IMPORTANTE: Altere aqui para o endereço onde está o seu servidor pirata
    win.loadURL('http://163.245.202.163/'); 
}

app.whenReady().then(() => {
    createWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
});
