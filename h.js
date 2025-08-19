!function(h,o,r,i,z,n){h.horizn=h.horizn||function(){(h.horizn.q=h.horizn.q||[]).push(arguments)};
n=o.createElement("script");z=o.getElementsByTagName("script")[0];
n.async=1;n.src=r+"?t="+Date.now();z.parentNode.insertBefore(n,z)
}(window,document,"/data.js");

(function(){
var h=window.horizn,d=document,w=window,n=navigator,l=location,s=screen,
c=d.cookie,ls=localStorage,e=encodeURIComponent,de=decodeURIComponent,
se=sessionStorage,ce=d.createElement,u=Math.random,f=Math.floor,
hid='h_id',sid='h_sid',uid='h_uid',endpoints=['/data.css','/pixel.png','/i.php'],
currentEndpoint=0,sessionTimeout=30*60*1000,initialized=false,
startTime=Date.now(),pageStartTime=performance.now(),
events=[],batch=[],batchTimer=null,userId=null,sessionId=null,
trackingCode=d.currentScript&&d.currentScript.getAttribute('data-site')||'',
apiBase=l.protocol+'//'+l.host,sendBeacon=!!(n.sendBeacon),
isBot=/bot|crawler|spider|scraper|facebookexternalhit|twitterbot|linkedinbot|whatsapp/i.test(n.userAgent);

if(!trackingCode||isBot)return;

function hash(s){var h=0,i,c;if(s.length===0)return h;
for(i=0;i<s.length;i++){c=s.charCodeAt(i);h=((h<<5)-h)+c;h=h&h;}return h;}

function uuid(){return 'xxxx-xxxx-4xxx-yxxx'.replace(/[xy]/g,function(c){
var r=u()*16|0,v=c=='x'?r:(r&0x3|0x8);return v.toString(16);});}

function getCookie(name){
var v=c.match('(^|;)\\s*'+name+'\\s*=\\s*([^;]+)');return v?de(v.pop()):'';}

function setCookie(name,value,days){var expires='';
if(days){var d=new Date();d.setTime(d.getTime()+(days*24*60*60*1000));
expires='; expires='+d.toUTCString();}
d.cookie=name+'='+e(value)+expires+'; path=/; samesite=lax';}

function getFingerprint(){
return hash(n.userAgent+(s.width+'x'+s.height)+n.language+
(n.platform||'')+(n.cookieEnabled?'1':'0')+
(typeof w.localStorage!=='undefined'?'1':'0')).toString(36);}

function generateUserId(){
var fp=getFingerprint(),stored=getCookie(uid)||'';
if(!stored){stored=fp+'_'+uuid();setCookie(uid,stored,365);}
return stored;}

function generateSessionId(){
var stored=se.getItem(sid);if(stored){
var data=JSON.parse(stored);
if(Date.now()-data.t<sessionTimeout)return data.id;}
var newId='s_'+Date.now()+'_'+u().toString(36).substr(2,9);
se.setItem(sid,JSON.stringify({id:newId,t:Date.now()}));return newId;}

function getPageTitle(){return d.title||'';}
function getPageUrl(){return l.href;}
function getPagePath(){return l.pathname+l.search;}
function getReferrer(){return d.referrer||'';}

function detectAdBlock(){try{
var testAd=ce('div');testAd.innerHTML='&nbsp;';
testAd.className='adsbox';d.body.appendChild(testAd);
var isBlocked=testAd.offsetHeight===0;d.body.removeChild(testAd);
return isBlocked;}catch(e){return false;}}

function tryEndpoint(endpoint,data,callback,attempt){
attempt=attempt||0;if(attempt>=3){
if(callback)callback(false);return;}

var xhr=new XMLHttpRequest();
xhr.open('POST',apiBase+endpoint,true);
xhr.setRequestHeader('Content-Type','application/json');
xhr.timeout=5000;xhr.onreadystatechange=function(){
if(xhr.readyState===4){
if(xhr.status===200){if(callback)callback(true);}
else{tryEndpoint(endpoint,data,callback,attempt+1);}}};
xhr.onerror=function(){tryEndpoint(endpoint,data,callback,attempt+1);};
xhr.ontimeout=function(){tryEndpoint(endpoint,data,callback,attempt+1);};
try{xhr.send(JSON.stringify(data));}catch(e){
tryEndpoint(endpoint,data,callback,attempt+1);}}

function sendData(data,callback){
currentEndpoint=(currentEndpoint+1)%endpoints.length;
var endpoint=endpoints[currentEndpoint];

if(sendBeacon&&u()<0.5){
try{var success=n.sendBeacon(apiBase+endpoint,JSON.stringify(data));
if(success){if(callback)callback(true);return;}}catch(e){}}

if(typeof fetch!=='undefined'&&u()<0.7){
try{fetch(apiBase+endpoint,{method:'POST',
body:JSON.stringify(data),
headers:{'Content-Type':'application/json'},
keepalive:true}).then(function(r){
if(callback)callback(r.ok);}).catch(function(){
tryEndpoint(endpoint,data,callback);});return;}catch(e){}}

tryEndpoint(endpoint,data,callback);}

function sendEvent(eventData){
var data={type:'event',site_id:trackingCode,session_id:sessionId,
user_id:userId,timestamp:Date.now(),ua:n.userAgent.substr(0,200),
url:getPageUrl(),path:getPagePath(),referrer:getReferrer(),
title:getPageTitle().substr(0,200),event:eventData};
sendData(data);}

function sendPageview(additional){
var loadTime=additional&&additional.loadTime?additional.loadTime:
f(performance.now()-pageStartTime);
var data={type:'pageview',site_id:trackingCode,session_id:sessionId,
user_id:userId,timestamp:Date.now(),ua:n.userAgent.substr(0,200),
url:getPageUrl(),path:getPagePath(),referrer:getReferrer(),
title:getPageTitle().substr(0,200),load_time:loadTime};
if(additional){for(var k in additional){
if(k!=='loadTime')data[k]=additional[k];}}
sendData(data);}

function flushBatch(){if(batch.length===0)return;
var data={type:'batch',site_id:trackingCode,session_id:sessionId,
user_id:userId,batch:batch.slice()};
batch=[];sendData(data);clearTimeout(batchTimer);batchTimer=null;}

function addToBatch(item){batch.push(item);
if(batch.length>=10){flushBatch();}else{
clearTimeout(batchTimer);batchTimer=setTimeout(flushBatch,2000);}}

function trackClick(e){var t=e.target||e.srcElement,tag=t.tagName.toLowerCase(),
href=t.href||t.getAttribute('href')||'',text=(t.textContent||t.innerText||'').substr(0,100);
if(tag==='a'&&href){var isOutbound=href.indexOf('://')>-1&&href.indexOf(l.hostname)===-1;
sendEvent({name:'click',category:'navigation',action:isOutbound?'outbound':'internal',
label:href,value:isOutbound?1:0,data:{text:text,tag:tag}});}
else if(tag==='button'||t.type==='submit'||t.type==='button'){
sendEvent({name:'click',category:'interaction',action:tag,label:text,
data:{text:text,tag:tag,type:t.type}});}}

function trackScroll(){var scrolled=f((w.scrollY||d.documentElement.scrollTop)/
(d.documentElement.scrollHeight-w.innerHeight)*100);
if(scrolled>=25&&!h._s25){h._s25=true;
sendEvent({name:'scroll',category:'engagement',action:'25%',value:25});}
if(scrolled>=50&&!h._s50){h._s50=true;
sendEvent({name:'scroll',category:'engagement',action:'50%',value:50});}
if(scrolled>=75&&!h._s75){h._s75=true;
sendEvent({name:'scroll',category:'engagement',action:'75%',value:75});}
if(scrolled>=90&&!h._s90){h._s90=true;
sendEvent({name:'scroll',category:'engagement',action:'90%',value:90});}}

function trackVisibility(){
if(d.hidden){h._hidden=Date.now();}
else if(h._hidden){var hiddenTime=Date.now()-h._hidden;
if(hiddenTime>5000){sendEvent({name:'visibility',category:'engagement',
action:'return',value:f(hiddenTime/1000)});h._hidden=null;}}}

function init(){if(initialized)return;initialized=true;
userId=generateUserId();sessionId=generateSessionId();
sendPageview();

try{d.addEventListener('click',trackClick,true);
w.addEventListener('scroll',trackScroll,true);
d.addEventListener('visibilitychange',trackVisibility,true);
w.addEventListener('beforeunload',function(){flushBatch();},false);}catch(e){}}

function track(){var args=Array.prototype.slice.call(arguments),
cmd=args.shift();

if(cmd==='page'||cmd==='pageview'){
if(!initialized)init();else sendPageview(args[0]||{});}
else if(cmd==='event'){var eventData=args[0];if(eventData){
if(!initialized)init();sendEvent(eventData);}}
else if(cmd==='set'){var key=args[0],value=args[1];
if(key==='userId')userId=value;
else if(key==='sessionId')sessionId=value;}
else if(cmd==='identify'){userId=args[0]||userId;}
else if(cmd==='init'||cmd==='create'){trackingCode=args[0]||trackingCode;init();}}

h.track=track;h.page=function(d){track('page',d);};
h.event=function(d){track('event',d);};h.identify=function(id){track('identify',id);};

if(h.q){for(var i=0;i<h.q.length;i++){track.apply(null,h.q[i]);}h.q=[];}

if(d.readyState==='loading'){d.addEventListener('DOMContentLoaded',init);}
else{setTimeout(init,100);}

h.trackingCode=trackingCode;h.sessionId=function(){return sessionId;};
h.userId=function(){return userId;};})();