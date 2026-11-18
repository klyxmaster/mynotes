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


/* Select individual notes for bulk category assignment or trash. */
document.getElementById('bulkSlot')?.append(document.getElementById('bulkBar'));
const bulkSelected = new Set();
const bulkBar = document.getElementById('bulkBar');
const bulkIds = document.getElementById('bulkIds');
const bulkAction = document.getElementById('bulkAction');
const bulkCategory = document.getElementById('bulkCategory');
function refreshBulkSelection() {
    document.querySelectorAll('.note[data-note-id]').forEach(note => {
        const active = bulkSelected.has(note.dataset.noteId);
        note.classList.toggle('is-selected', active);
        const btn = note.querySelector('.select-note');
        if (btn) { btn.textContent = active ? '✓' : '+'; btn.setAttribute('aria-pressed', String(active)); btn.title = active ? 'Deselect note' : 'Select note'; }
    });
    if (bulkBar) bulkBar.hidden = bulkSelected.size === 0;
	const normalCategories = document.getElementById('normalCategories');
	if (normalCategories) normalCategories.hidden = bulkSelected.size > 0;
    const count = document.getElementById('bulkCount');
    if (count) count.textContent = bulkSelected.size + (bulkSelected.size === 1 ? ' selected' : ' selected');
    if (bulkIds) bulkIds.value = [...bulkSelected].join(',');
}
document.querySelectorAll('.note .select-note').forEach(btn => btn.addEventListener('click', () => {
    const id = btn.closest('.note').dataset.noteId;
    if (bulkSelected.has(id)) bulkSelected.delete(id); else bulkSelected.add(id);
    refreshBulkSelection();
}));
document.getElementById('bulkClear')?.addEventListener('click', () => { bulkSelected.clear(); refreshBulkSelection(); });
document.getElementById('bulkMove')?.addEventListener('click', () => {
    const newCategory = document.getElementById('bulkNewCategory')?.value.trim() || '';
		if (!bulkSelected.size || (!newCategory && (!bulkCategory || bulkCategory.value === ''))) {
			alert('Choose an existing category or enter a new category name.');
			return;
		}
    bulkAction.value = 'category';
    rememberScroll();
    bulkBar.requestSubmit();
});
document.getElementById('bulkTrash')?.addEventListener('click', () => {
    if (!bulkSelected.size) return;
    if (!confirm('Move ' + bulkSelected.size + ' selected notes to Trash?')) return;
    bulkAction.value = 'trash';
    rememberScroll();
    bulkBar.requestSubmit();
});
/* Drag the bulk toolbar using its selection count. */
document.getElementById('bulkArchive')?.addEventListener('click', () => {
    if (!bulkSelected.size) return;
    if (!confirm('Archive ' + bulkSelected.size + ' selected notes?')) return;
    bulkAction.value = 'archive';
    rememberScroll();
    bulkBar.requestSubmit();
});
document.getElementById('emptyTrashForm')?.addEventListener('submit', function(e) {
    if (!confirm('Permanently delete ALL notes in Trash? This cannot be undone.')) {
        e.preventDefault();
    }
});