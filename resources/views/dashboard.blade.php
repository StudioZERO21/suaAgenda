<!DOCTYPE html><html lang='pt_BR'><head><meta charset='UTF-8'><title>{{ config('app.name') }} - Dashboard</title>
<link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap' rel='stylesheet'>
<style>*{font-family:Inter,sans-serif}body{background:#F1F3F4;margin:0}.kpi{background:#fff;border-radius:4px;padding:16px 20px}</style></head><body>
<header style='position:fixed;top:0;left:0;right:0;height:56px;background:#fff;border-bottom:1px solid #E8EAED;display:flex;align-items:center;justify-content:space-between;padding:0 24px;z-index:100'>
<span style='font-size:14px;font-weight:700;color:#154360'>{{ config('app.name') }}</span>
<form method='POST' action='{{ route('logout') }}'>@csrf<button type='submit' style='font-size:13px;padding:5px 12px;border:1px solid #E8EAED;border-radius:4px;background:#fff;color:#6C757D;cursor:pointer'>Sair</button></form></header>
<aside style='position:fixed;top:56px;left:0;bottom:0;width:200px;background:#154360;padding:16px 12px'>
<a href='{{ route('dashboard') }}' style='display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13px;color:#fff;border-radius:4px;text-decoration:none;background:rgba(255,255,255,.12)'>Dashboard</a></aside>
<main style='margin-left:200px;padding:80px 24px 24px'><h1 style='font-size:18px;font-weight:700;color:#343A40;margin:0 0 24px'>Dashboard</h1>
<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px'>
<div class='kpi' style='border-left:4px solid #1B4F72'><p style='font-size:11px;font-weight:600;text-transform:uppercase;color:#ADB5BD;margin:0 0 6px'>Usuario</p>
<p style='font-size:17px;font-weight:700;color:#343A40;margin:0 0 4px'>{{ auth()->user()->name }}</p>
<p style='font-size:12px;color:#6C757D;margin:0'>{{ auth()->user()->getRoleNames()->first() ?? 'sem role' }}</p>
</div>
<div class='kpi' style='border-left:4px solid #1A6B3C'><p style='font-size:11px;font-weight:600;text-transform:uppercase;color:#ADB5BD;margin:0 0 6px'>Status</p>
<p style='font-size:17px;font-weight:700;color:#1A6B3C;margin:0'>Online</p></div></div></main></body></html>