const { app, BrowserWindow } = require('electron');
const path = require('path');
const sqliteDbPath = path.join(__dirname, 'database.sqlite');

function createWindow() {
  const win = new BrowserWindow({
    width: 800,
    height: 600,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
    },
  });

  // Load your Laravel application
  win.loadURL('http://www.cannabestpos.com/login'); // Ensure your Laravel app is running on this URL
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