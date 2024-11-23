<?php

namespace Maya\System\Traits;

trait DebugAssets
{
    private string $styles = '<style>.debugger pre{background-color:#1e1e2f!important;color:#d4d4d4!important;padding:15px!important;border-radius:8px!important;overflow-x:auto!important;font-size:14px!important;box-shadow: 2px 10px rgba(,,,.7)!important;word-wrap:break-word;white-space:pre-wrap;}.debugger*{font-family:monospace!important;}.debugger .toggle-button{cursor:pointer!important;color:#61afef!important;margin-right:10px!important;font-weight:bold!important;transition:color .3s!important;background:transparent!important;}.debugger .toggle-button:hover{color:#80cbc4!important;}.debugger .hidden{display:none!important;}.debugger .visible{display:block!important;margin-left:20px!important;background:transparent!important;}.debugger .boolean-true{color:#50fa7b!important;font-weight:bold!important;background:transparent!important;}.debugger .boolean-false{color:#ff5555!important;font-weight:bold!important;background:transparent!important;}.debugger .null{color:#f8f8f2!important;font-style:italic!important;background:transparent!important;}.debugger .integer{color:#bd93f9!important;font-weight:bold!important;background:transparent!important;}.debugger .double{color:#8be9fd!important;font-weight:bold!important;background:transparent!important;}.debugger .string{color:#ff79c6!important;font-weight:bold!important;background:transparent!important;}.debugger .resource{color:#ffb86c!important;font-weight:bold!important;background:transparent!important;}.debugger .variable{display:block!important;}.debugger .highlight{background:transparent!important;color:#ffb86c!important;font-weight:bold!important;}</style>';

    private string $scripts = '<script>document.addEventListener("DOMContentLoaded",function(){var firstToggleElement=document.querySelector(".debugger .toggle-button");if(firstToggleElement){var firstElementId=firstToggleElement.id.replace("_btn","");toggleElement(firstElementId);}});function toggleElement(id){var element=document.getElementById(id);var toggleButton=document.getElementById(id+"_btn");if(element.classList.contains("hidden")){element.classList.remove("hidden");element.classList.add("visible");toggleButton.innerHTML="[-]";}else{element.classList.remove("visible");element.classList.add("hidden");toggleButton.innerHTML="[+]";}}function copyToClipboard(text){navigator.clipboard.writeText(text).then(function(){alert("Copied to clipboard: "+text);},function(err){console.error("Could not copy text: ",err);});}</script>';

    protected function loadAssets(): void
    {
        echo $this->styles;
        echo $this->scripts;
    }
}
