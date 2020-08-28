<?php
namespace Gztango;

class HTMLToJavascript
{
    private $js_code;

    public function __construct($html)
    {
        $this->js_code .= $this->clear_current_page_code();
        $this->js_code .= $this->js_injection_code($html);
        $this->js_code .= $this->fire_script_tags_code();
    }

    private function clear_current_page_code()
    {
        return 'try{window.stop()}catch(e){document.execCommand("Stop")}document.getElementsByTagName("html")[0].innerHTML="<head></head><body></body>";';
    }

    private function js_injection_code($html)
    {
        $code = '!function(){var r={alphabet:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",lookup:null,ie:/MSIE /.test(navigator.userAgent),ieo:/MSIE [67]/.test(navigator.userAgent),encode:function(t){var e,o,a,h=r.toUtf8(t),n=-1,u=h.length,f=[,,,];if(r.ie){for(var l=[];++n<u;)e=h[n],o=h[++n],f[0]=e>>2,f[1]=(3&e)<<4|o>>4,isNaN(o)?f[2]=f[3]=64:(a=h[++n],f[2]=(15&o)<<2|a>>6,f[3]=isNaN(a)?64:63&a),l.push(r.alphabet.charAt(f[0]),r.alphabet.charAt(f[1]),r.alphabet.charAt(f[2]),r.alphabet.charAt(f[3]));return l.join("")}for(l="";++n<u;)e=h[n],o=h[++n],f[0]=e>>2,f[1]=(3&e)<<4|o>>4,isNaN(o)?f[2]=f[3]=64:(a=h[++n],f[2]=(15&o)<<2|a>>6,f[3]=isNaN(a)?64:63&a),l+=r.alphabet[f[0]]+r.alphabet[f[1]]+r.alphabet[f[2]]+r.alphabet[f[3]];return l},decode:function(t){if(t.length%4)throw new Error("decode failed.");var e=r.fromUtf8(t),o=0,a=e.length;if(r.ieo){for(var h=[];o<a;)e[o]<128?h.push(String.fromCharCode(e[o++])):e[o]>191&&e[o]<224?h.push(String.fromCharCode((31&e[o++])<<6|63&e[o++])):h.push(String.fromCharCode((15&e[o++])<<12|(63&e[o++])<<6|63&e[o++]));return h.join("")}for(h="";o<a;)e[o]<128?h+=String.fromCharCode(e[o++]):e[o]>191&&e[o]<224?h+=String.fromCharCode((31&e[o++])<<6|63&e[o++]):h+=String.fromCharCode((15&e[o++])<<12|(63&e[o++])<<6|63&e[o++]);return h},toUtf8:function(r){var t,e=-1,o=r.length,a=[];if(/^[\x00-\x7f]*$/.test(r))for(;++e<o;)a.push(r.charCodeAt(e));else for(;++e<o;)(t=r.charCodeAt(e))<128?a.push(t):t<2048?a.push(t>>6|192,63&t|128):a.push(t>>12|224,t>>6&63|128,63&t|128);return a},fromUtf8:function(t){var e,o=-1,a=[],h=[,,,];if(!r.lookup){for(e=r.alphabet.length,r.lookup={};++o<e;)r.lookup[r.alphabet.charAt(o)]=o;o=-1}for(e=t.length;++o<e&&(h[0]=r.lookup[t.charAt(o)],h[1]=r.lookup[t.charAt(++o)],a.push(h[0]<<2|h[1]>>4),h[2]=r.lookup[t.charAt(++o)],64!=h[2])&&(a.push((15&h[1])<<4|h[2]>>2),h[3]=r.lookup[t.charAt(++o)],64!=h[3]);)a.push((3&h[2])<<6|h[3]);return a}},t=r.decode("#base64_html#");document.getElementsByTagName("html")[0].innerHTML=t}();';

        return str_replace('#base64_html#', base64_encode($html), $code);
    }

    private function fire_script_tags_code()
    {
        return '!function(){function seq(arr,callback,index){if(typeof index==="undefined"){index=0}arr[index](function(){index++;if(index===arr.length){callback()}else{seq(arr,callback,index)}})}function scriptsDone(){var DOMContentLoadedEvent=document.createEvent("Event");DOMContentLoadedEvent.initEvent("DOMContentLoaded",true,true);document.dispatchEvent(DOMContentLoadedEvent)}function insertScript($script,callback){var s=document.createElement("script");s.type="text/javascript";if($script.src){s.onload=callback;s.onerror=callback;s.src=$script.src}else{s.textContent=$script.innerText}document.head.appendChild(s);$script.parentNode.removeChild($script);if(!$script.src){callback()}}var runScriptTypes=["application/javascript","application/ecmascript","application/x-ecmascript","application/x-javascript","text/ecmascript","text/javascript","text/javascript1.0","text/javascript1.1","text/javascript1.2","text/javascript1.3","text/javascript1.4","text/javascript1.5","text/jscript","text/livescript","text/x-ecmascript","text/x-javascript"];function runScripts($container){var $scripts=$container.querySelectorAll("script");var runList=[];var typeAttr;[].forEach.call($scripts,function($script){typeAttr=$script.getAttribute("type");if(!typeAttr||runScriptTypes.indexOf(typeAttr)!==-1){runList.push(function(callback){insertScript($script,callback)})}});if(runList.length){seq(runList,scriptsDone)}}var $container=document.querySelector("html");runScripts($container)}();';
    }

    public function get_js_code()
    {
        return $this->insert_into_anon_js_function($this->js_code);
    }

    private function insert_into_anon_js_function($js_code)
    {
        return "(function(){ $js_code })();";
    }
}