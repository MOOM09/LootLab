(() => {
'use strict';
const boot=()=>{
const catalog=document.querySelector('[data-user-catalog]');if(!catalog)return;
const cards=Array.from(catalog.querySelectorAll('[data-user-product-card]'));
const search=document.querySelector('[data-user-catalog-search]');
const form=document.querySelector('[data-user-search-form]');
const catBtns=Array.from(document.querySelectorAll('[data-user-category]'));
const subBtns=Array.from(document.querySelectorAll('[data-user-subcategory]'));
const typeBtns=Array.from(document.querySelectorAll('[data-user-type]'));
const subList=document.querySelector('[data-user-subcategory-list]');
const sortSelect=document.querySelector('[data-user-sort]');
const pagination=document.querySelector('[data-user-pagination]');
const counter=document.querySelector('[data-user-result-count]');
const activeLabel=document.querySelector('[data-user-active-category]');
const empty=document.querySelector('[data-user-no-results]');
const minPrice=document.querySelector('[data-user-min-price]');
const maxPrice=document.querySelector('[data-user-max-price]');
const presets=Array.from(document.querySelectorAll('[data-user-price-preset]'));
const subGroup=document.querySelector('[data-user-subcategory-group]');
const original=new Map(cards.map((el,i)=>[el,i]));
let category='all',subcategory='all',type='all',page=1;
const press=(group,key,value)=>group.forEach(b=>b.setAttribute('aria-pressed',String(b.dataset[key]===value)));
const updateSubs=()=>{if(subGroup)subGroup.hidden=category==='all';if(subList)subList.hidden=category==='all';subBtns.forEach(b=>{b.hidden=b.dataset.subcategoryId!=='all'&&b.dataset.categoryId!==category;});};
const paginate=(total)=>{
if(!pagination)return;pagination.replaceChildren();if(total<2)return;
const add=(label,num,active,disabled)=>{const b=document.createElement('button');b.type='button';b.textContent=label;b.className='user-page-button'+(active?' user-is-current':'');b.disabled=disabled;if(active)b.setAttribute('aria-current','page');b.addEventListener('click',()=>{page=num;render();catalog.scrollIntoView({behavior:'smooth',block:'start'});});pagination.appendChild(b);};
add('←',Math.max(1,page-1),false,page===1);
let prev=0;[...new Set([1,total,page-1,page,page+1].filter(n=>n>=1&&n<=total))].sort((a,b)=>a-b).forEach(n=>{if(prev&&n-prev>1){const dots=document.createElement('span');dots.textContent='…';dots.className='user-page-ellipsis';pagination.appendChild(dots);}add(String(n),n,n===page,false);prev=n;});
add('→',Math.min(total,page+1),false,page===total);
};
function render(){
const q=(search?.value||'').trim().toLocaleLowerCase();
const min=minPrice?.value===''||!minPrice?null:Number(minPrice.value);
const max=maxPrice?.value===''||!maxPrice?null:Number(maxPrice.value);
const matching=cards.filter(card=>(min===null||Number(card.dataset.price)>=min)&&(max===null||Number(card.dataset.price)<=max)&&(category==='all'||card.dataset.categoryId===category)&&(subcategory==='all'||card.dataset.subcategoryId===subcategory)&&(type==='all'||card.dataset.typeId===type)&&(!q||[card.dataset.name,card.dataset.categoryName,card.dataset.subcategoryName].some(t=>(t||'').toLocaleLowerCase().includes(q))));
const mode=sortSelect?.value||'newest';
matching.sort((a,b)=>mode==='price-asc'?Number(a.dataset.price)-Number(b.dataset.price):mode==='price-desc'?Number(b.dataset.price)-Number(a.dataset.price):mode==='name'?(a.dataset.name||'').localeCompare(b.dataset.name||''):original.get(a)-original.get(b));
const total=Math.max(1,Math.ceil(matching.length/12));page=Math.min(page,total);
matching.forEach(card=>catalog.appendChild(card));cards.forEach(card=>{card.hidden=true;});matching.slice((page-1)*12,page*12).forEach(card=>{card.hidden=false;});
if(counter)counter.textContent=String(matching.length);if(empty)empty.hidden=matching.length>0;
if(activeLabel){const b=catBtns.find(b=>b.dataset.categoryId===category);activeLabel.textContent=b?.dataset.categoryName||'All products';}
paginate(total);
}
document.addEventListener('click',e=>{const btn=e.target.closest('[data-user-category],[data-user-subcategory],[data-user-type]');if(!btn)return;
if(btn.hasAttribute('data-user-category')){category=btn.dataset.categoryId||'all';subcategory='all';press(catBtns,'categoryId',category);press(subBtns,'subcategoryId',subcategory);updateSubs();}
else if(btn.hasAttribute('data-user-subcategory')){subcategory=btn.dataset.subcategoryId||'all';press(subBtns,'subcategoryId',subcategory);}
else{type=btn.dataset.typeId||'all';press(typeBtns,'typeId',type);}
page=1;render();});
const q=new URLSearchParams(location.search).get('q');if(q&&search)search.value=q;
form?.addEventListener('submit',e=>{e.preventDefault();page=1;render();});search?.addEventListener('input',()=>{page=1;render();});sortSelect?.addEventListener('change',()=>{page=1;render();});
const setPresets=()=>presets.forEach(p=>p.setAttribute('aria-pressed',String((minPrice?.value||'')===(p.dataset.min||'')&&(maxPrice?.value||'')===(p.dataset.max||''))));
presets.forEach(btn=>btn.addEventListener('click',()=>{if(minPrice)minPrice.value=btn.dataset.min||'';if(maxPrice)maxPrice.value=btn.dataset.max||'';setPresets();page=1;render();}));
[minPrice,maxPrice].forEach(input=>input?.addEventListener('input',()=>{setPresets();page=1;render();}));
document.querySelector('[data-user-clear-filters]')?.addEventListener('click',()=>{category='all';subcategory='all';type='all';if(search)search.value='';if(minPrice)minPrice.value='';if(maxPrice)maxPrice.value='';press(catBtns,'categoryId','all');press(subBtns,'subcategoryId','all');press(typeBtns,'typeId','all');setPresets();updateSubs();page=1;render();});
setPresets();updateSubs();render();
};if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();