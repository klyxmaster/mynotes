const editor=document.getElementById('editor');
if(editor?.hasAttribute('open')) {
    editor.close();
    editor.showModal();
}
document.getElementById('menu')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('visible'));
document.getElementById('newNote')?.addEventListener('click',()=>{document.querySelector('#noteForm').reset();document.querySelector('#noteForm [name=id]').value='0';document.getElementById('duplicateOverride').value='0';refreshLinkPreviews();editor.showModal();});
for(const id of ['closeEditor','cancelEditor'])document.getElementById(id)?.addEventListener('click',()=>{editor.close();if(new URLSearchParams(location.search).has('edit'))location.href='index.php';});
document.querySelectorAll('[data-confirm]').forEach(b=>b.addEventListener('click',e=>{if(!confirm(b.dataset.confirm))e.preventDefault();}));

const saveAnyway=document.getElementById('saveAnyway');
saveAnyway?.addEventListener('click',()=>document.getElementById('duplicateOverride').value='1');
if(saveAnyway && document.querySelector('.error') && document.querySelector('#noteForm [name=id]').value==='0'){
    saveAnyway.style.display='';
};

// Remember selected category filters for this browser.
document.querySelectorAll('.cat-filter').forEach(box=>box.addEventListener('change',()=>{
    const ids=[...document.querySelectorAll('.cat-filter:checked')].map(el=>el.value);
    document.cookie='mn_visible_cats='+ids.join(',')+'; path=/MyNotes/; max-age=31536000; SameSite=Lax; Secure';
    location.href='index.php';
}));

// Link previews appear inside the editor; note text is never changed.
const noteBody=document.querySelector('#noteForm textarea[name="body"]');
const linkPreviews=document.getElementById('linkPreviews');
function refreshLinkPreviews(){
    if(!noteBody||!linkPreviews)return;
    linkPreviews.replaceChildren();
    const found=[...noteBody.value.matchAll(/https?:\/\/[^\s<>"']+/gi)].map(m=>m[0].replace(/[),.;!?]+$/,''));
    const urls=[...new Set(found)].slice(0,5);
    if(!urls.length)return;
    const heading=document.createElement('div');heading.className='preview-heading';heading.textContent='Link previews';linkPreviews.append(heading);
    urls.forEach(raw=>{
        let url;try{url=new URL(raw);}catch(e){return;}
        if(!['https:','http:'].includes(url.protocol))return;
        const host=url.hostname.toLowerCase().replace(/^www\./,'');
        const card=document.createElement('a');card.className='link-preview';card.href=url.href;card.target='_blank';card.rel='noopener noreferrer';
        let videoId=null;
        if(host==='youtu.be')videoId=url.pathname.split('/')[1];
        else if(host==='youtube.com'||host==='m.youtube.com'||host==='youtube-nocookie.com'){
            if(url.pathname==='/watch')videoId=url.searchParams.get('v');
            else if(/^\/(shorts|embed)\//.test(url.pathname))videoId=url.pathname.split('/')[2];
        }
        if(videoId&&/^[a-zA-Z0-9_-]{11}$/.test(videoId)){
            const img=document.createElement('img');img.src='link_thumbnail.php?v='+encodeURIComponent(videoId);img.alt='YouTube video thumbnail';img.loading='lazy';img.addEventListener('error',()=>img.remove());card.append(img);
        }
        const details=document.createElement('span');details.className='link-preview-details';
        const site=document.createElement('strong');site.textContent=host;
        const address=document.createElement('small');address.textContent=url.href;
        details.append(site,address);card.append(details);linkPreviews.append(card);
    });
}
noteBody?.addEventListener('input',refreshLinkPreviews);
refreshLinkPreviews();

/* Restore scroll position after note actions */
const scrollKey = 'mynotes_scroll';

function rememberScroll() {
    sessionStorage.setItem(scrollKey, String(window.scrollY));
}

document.querySelectorAll('.note a, .actions form button, #noteForm button[type="submit"]').forEach(el => {
    el.addEventListener('click', rememberScroll);
});

document.querySelectorAll('.actions form, #noteForm').forEach(form => {
    form.addEventListener('submit', rememberScroll);
});

document.querySelectorAll('#closeEditor, #cancelEditor').forEach(el => {
    el.addEventListener('click', rememberScroll, true);
});

const previousScroll = sessionStorage.getItem(scrollKey);
if (previousScroll !== null) {
    sessionStorage.removeItem(scrollKey);
    window.addEventListener('load', () => {
        requestAnimationFrame(() => {
            window.scrollTo(0, Number(previousScroll));
        });
    });
}

document.getElementById('deleteNote')?.addEventListener('click', () => {
    if (!confirm('Move this note to Trash?')) return;

    const form = document.getElementById('noteForm');
    form.querySelector('[name="action"]').value = 'trash';
    form.requestSubmit();
});


/* Bulk note selection */
const bulkForm=document.getElementById('bulkForm');
const bulkChecks=[...document.querySelectorAll('.bulk-check input')];
const bulkLabels=[...document.querySelectorAll('.bulk-check')];
const selectNotes=document.getElementById('selectNotes');
function updateBulkCount(){
    const count=bulkChecks.filter(c=>c.checked).length;
    const counter=document.getElementById('selectedCount');
    if(counter)counter.textContent=count+' selected';
    bulkChecks.forEach(c=>c.closest('.note')?.classList.toggle('bulk-selected',c.checked));
}
function setBulkMode(enabled){
    if(!bulkForm)return;
    bulkForm.hidden=!enabled;
    bulkLabels.forEach(label=>label.hidden=!enabled);
    selectNotes?.classList.toggle('active',enabled);
    if(!enabled)bulkChecks.forEach(c=>c.checked=false);
    updateBulkCount();
}
selectNotes?.addEventListener('click',()=>setBulkMode(bulkForm.hidden));
document.getElementById('cancelSelection')?.addEventListener('click',()=>setBulkMode(false));
bulkChecks.forEach(c=>c.addEventListener('change',updateBulkCount));
document.getElementById('selectAllVisible')?.addEventListener('click',()=>{
    const allSelected=bulkChecks.length>0&&bulkChecks.every(c=>c.checked);
    bulkChecks.forEach(c=>c.checked=!allSelected);
    updateBulkCount();
});
document.getElementById('bulkMove')?.addEventListener('click',()=>{
    const count=bulkChecks.filter(c=>c.checked).length;
    if(!count){alert('Select at least one note.');return;}
    const category=document.getElementById('bulkCategory');
    if(category.value===''){alert('Choose a destination category.');return;}
    if(!confirm('Move '+count+' selected note(s) to this category?'))return;
    document.getElementById('bulkOperation').value='category';
    rememberScroll();bulkForm.requestSubmit();
});
document.getElementById('bulkTrash')?.addEventListener('click',()=>{
    const count=bulkChecks.filter(c=>c.checked).length;
    if(!count){alert('Select at least one note.');return;}
    if(!confirm('Move '+count+' selected note(s) to Trash?'))return;
    document.getElementById('bulkOperation').value='trash';
    rememberScroll();bulkForm.requestSubmit();
});
