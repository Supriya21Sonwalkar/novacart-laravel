(() => {
 const input=document.querySelector('.search input[name=q]'), list=document.querySelector('[data-suggestions]');if(!input||!list)return;
 let timer,controller;input.addEventListener('input',()=>{clearTimeout(timer);if(controller)controller.abort();timer=setTimeout(async()=>{if(input.value.trim().length<2)return;controller=new AbortController();try{const r=await fetch('/search/suggestions?q='+encodeURIComponent(input.value),{signal:controller.signal});if(!r.ok)return;const rows=await r.json();list.replaceChildren();rows.forEach(p=>{const option=document.createElement('option');option.value=p.name;option.label='₹'+(p.price/100).toFixed(2);list.append(option);});}catch(e){}},250);});
})();
