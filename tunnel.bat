@echo off
title Cloudflare Tunnel - Parañaque CSN Alert System
echo ====================================================================
echo   Parañaque CSN Emergency Alert System - Cloudflare Tunnel
echo ====================================================================
echo.
echo [*] Forwarding local web app (http://127.0.0.1:8000) to Cloudflare...
echo [*] Your mobile app can use the generated https://...trycloudflare.com URL
echo [*] Press Ctrl+C anytime to stop the tunnel.
echo.

if exist "C:\Program Files (x86)\cloudflared\cloudflared.exe" (
    "C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:8000
) else (
    cloudflared tunnel --url http://127.0.0.1:8000
)
pause

