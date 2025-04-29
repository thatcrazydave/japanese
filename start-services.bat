@echo off
echo Starting all services...

:: Start the proxy server (in a new window)
start "Proxy Server" cmd /k "cd proxy-server && npm start"

:: Start the backend (in a new window)
start "Japanese AI Backend" cmd /k "cd japanese-ai-backend && python main.py"

:: Start the frontend (in this window)
cd japanese-project
echo Starting frontend application...
npm start

pause 