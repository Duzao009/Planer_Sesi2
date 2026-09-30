(function () {
  'use strict';
  const icons = {
    trophy:'<path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4v1a4 4 0 0 0 4 4M17 6h3v1a4 4 0 0 1-4 4"/>',
    home:'<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/>',
    user:'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    users:'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    clipboard:'<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 10h6M9 14h6M9 18h4"/>',
    chart:'<path d="M3 3v18h18M7 16v-4M12 16V7M17 16V4"/>',
    calendar:'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    pin:'<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/>',
    login:'<path d="M10 17l5-5-5-5M15 12H3"/><path d="M15 3h5a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-5"/>',
    logout:'<path d="M14 8l4 4-4 4M18 12H7"/><path d="M10 4H4a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h6"/>',
    edit:'<path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20l-5 1 1-5Z"/>',
    check:'<path d="m5 12 4 4L19 6"/>',
    clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    warning:'<path d="M10.3 3.7 2.2 18a2 2 0 0 0 1.8 3h16a2 2 0 0 0 1.8-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    info:'<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
    close:'<path d="m6 6 12 12M18 6 6 18"/>',
    sync:'<path d="M20 7h-5V2M4 17h5v5"/><path d="M5.5 9a7 7 0 0 1 11.7-3L20 7M4 17l2.8 1A7 7 0 0 0 18.5 15"/>',
    menu:'<path d="M4 7h16M4 12h16M4 17h16"/>',
    eye:'<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    ball:'<circle cx="12" cy="12" r="9"/><path d="m8 4 3 4-2 4-5 1M16 4l-3 4 2 4 5 1M9 12l3 3 3-3M12 15v6"/>',
    file:'<path d="M6 2h8l4 4v16H6Z"/><path d="M14 2v5h5M9 13h6M9 17h6"/>',
    search:'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    plus:'<path d="M12 5v14M5 12h14"/>',
    minus:'<path d="M5 12h14"/>',
    trash:'<path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6"/>',
    arrow:'<path d="M5 12h14M13 6l6 6-6 6"/>',
    school:'<path d="m3 10 9-5 9 5-9 5Z"/><path d="M7 12v5c3 2 7 2 10 0v-5M21 10v6"/>'
  };
  const aliases = {'🏆':'trophy','🏠':'home','🎓':'school','👨‍🏫':'user','👥':'users','📋':'clipboard','📊':'chart','📈':'chart','📅':'calendar','🏁':'calendar','📍':'pin','🔑':'login','🚪':'logout','✍️':'edit','✏️':'edit','✅':'check','🟢':'check','⏳':'clock','⚠️':'warning','ℹ️':'info','❌':'close','🔄':'sync','☰':'menu','👁️':'eye','🌐':'home','📄':'file','💾':'check','🔍':'search','📭':'clipboard','😢':'info','🎉':'check','🏅':'trophy','⚽':'ball','🤾':'ball','🏀':'ball','🏐':'ball','🎾':'ball','🏖️':'ball','👋':'user','▶️':'arrow','🔴':'close','🟡':'clock','⚫':'close'};
  function svg(name){const p=icons[name]||icons.ball;return '<svg class="icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'+p+'</svg>';}
  window.PlanerIcon=svg;
  function replace(root){
    const walker=document.createTreeWalker(root||document.body,NodeFilter.SHOW_TEXT);
    const nodes=[]; while(walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(n=>{if(!n.parentElement||['SCRIPT','STYLE','TEXTAREA','OPTION'].includes(n.parentElement.tagName))return;let text=n.nodeValue;const hits=Object.keys(aliases).filter(e=>text.includes(e));if(!hits.length)return;let parts=[{t:text}];hits.forEach(e=>{parts=parts.flatMap(x=>x.t!==undefined?x.t.split(e).flatMap((s,i,a)=>i<a.length-1?[{t:s},{i:aliases[e]}]:[{t:s}]):[x]);});const f=document.createDocumentFragment();parts.forEach(x=>{if(x.i){const s=document.createElement('span');s.className='inline-icon';s.innerHTML=svg(x.i);f.appendChild(s);}else if(x.t)f.appendChild(document.createTextNode(x.t));});n.replaceWith(f);});
    document.querySelectorAll('.menu-mobile').forEach(b=>{b.innerHTML=svg('menu');b.setAttribute('aria-label','Abrir menu');});
  }
  document.addEventListener('DOMContentLoaded',()=>replace(document.body));
})();
