<?php

namespace Maya\System\Traits;

trait ErrorAssets
{
    private string $styles = "
    body{font-family:monospace;background:linear-gradient(135deg,#1a1d27,#0d0f14);color:#f1f1f1;margin:0;display:flex;align-items:center;justify-content:center;height:100vh;overflow-y:auto}.error-container{background:rgba(33,37,41,.9);padding:30px;border-radius:12px;max-width:800px;box-shadow:0 10px 20px rgba(0,0,0,.4);animation:.5s ease-in-out fadeIn}@keyframes fadeIn{from{opacity:0}to{opacity:1}}p{font-size:16px;margin:8px 0;line-height:1.6}button{background-color:#ff4757;color:#fff;border:none;padding:12px 18px;font-size:14px;font-weight:500;border-radius:5px;cursor:pointer;margin-top:15px;transition:background-color .3s}button:hover{background-color:#ff6b81}pre{background-color:#222;padding:20px;border-radius:6px;margin-top:20px;overflow:auto;white-space:pre-wrap;box-shadow:inset 0 4px 8px rgba(0,0,0,.2)}.code-snippet{background-color:#0f1014;color:#f1f1f1;border-left:4px solid #ff4757;padding-left:10px}.highlight{background-color:#ff4757;color:#fff;border-radius:0 2px 2px 0}.line-number{color:#888;display:inline-block;width:30px;user-select:none}body::-webkit-scrollbar{width:8px}body::-webkit-scrollbar-thumb{background-color:rgba(40,40,40,.9);border-radius:10px}.error-title h2,.error-title h3{padding:6px 12px;border-radius:999px;margin-bottom:10px}body::-webkit-scrollbar-thumb:hover{background-color:#3c3c3c}body::-webkit-scrollbar-track{background-color:rgba(20,20,20,.9)}.error-title h2{background-color:#ef444433;color:#ef4444}.error-message,.error-title h3{background-color:#1c1e29;color:#fff}.error-message{padding:20px 12px;font-size:18px;border-radius:6px 6px 0 0;margin-top:10px}.error-suggestion{margin-bottom:10px;padding:12px;color:#fff;font-size:14px;border-radius:0 0 6px 6px;background-color:#282c3a}";

    private string $scripts = "";
}
