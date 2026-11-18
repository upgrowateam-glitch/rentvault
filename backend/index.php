<?php
// The root of rentdocs.upgrowa.com intentionally shows nothing.
// Customers can only open a specific vehicle URL, e.g. /vehicle1
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Not found</title>
<style>body{font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0;color:#555;background:#f5f6f8}</style>
</head><body><p>Page not found. Please scan the QR code on your vehicle.</p></body></html>
