<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-dismiss alerts
document.querySelectorAll('.alert-dismissible').forEach(a=>{
  setTimeout(()=>{try{bootstrap.Alert.getOrCreateInstance(a).close();}catch(e){}},4500);
});
// Logout modal
function showLogoutModal(id){document.getElementById(id).classList.add('show');}
function hideLogoutModal(id){document.getElementById(id).classList.remove('show');}
document.addEventListener('keydown',e=>{
  if(e.key==='Escape')document.querySelectorAll('.brgy-modal-overlay.show').forEach(m=>m.classList.remove('show'));
});
// Mobile sidebar
function openSidebar(){
  document.querySelector('.sidebar')?.classList.add('mobile-open');
  document.getElementById('sidebarOverlay')?.classList.add('show');
  document.getElementById('hamburgerBtn')?.style.setProperty('display','none');
}
function closeSidebar(){
  document.querySelector('.sidebar')?.classList.remove('mobile-open');
  document.getElementById('sidebarOverlay')?.classList.remove('show');
  // only re-show hamburger on mobile
  if(window.innerWidth<=768){
    const h=document.getElementById('hamburgerBtn');
    if(h) h.style.removeProperty('display');
  }
}
// Live search for tables
function initLiveSearch(inputId,tableId){
  const inp=document.getElementById(inputId),tbl=document.getElementById(tableId);
  if(!inp||!tbl)return;
  inp.addEventListener('input',function(){
    const q=this.value.toLowerCase();
    tbl.querySelectorAll('tbody tr').forEach(row=>{row.style.display=row.textContent.toLowerCase().includes(q)?'':'none';});
  });
}
// Copy to clipboard
function copyId(rid){
  navigator.clipboard.writeText(rid).then(()=>{
    const t=document.getElementById('copyToast');
    if(t) new bootstrap.Toast(t,{delay:2500}).show();
  });
}
// Real-time polling (resident pages only)
if(typeof RESIDENT_RID!=='undefined'&&RESIDENT_RID){
  let _cache={};
  function pollStatuses(){
    fetch('/BarangayProject/api/status_poll.php?rid='+encodeURIComponent(RESIDENT_RID))
      .then(r=>r.json()).then(data=>{
        if(!data||!data.records)return;
        data.records.forEach(rec=>{
          if(_cache[rec.record_id]&&_cache[rec.record_id]!==rec.status){
            showRtToast(rec.record_id,rec.status);
            // update badge in-place
            document.querySelectorAll('[data-record-id="'+rec.record_id+'"] .rt-status-badge').forEach(el=>{
              const m={Pending:'warning',Approved:'primary',Done:'success',Rejected:'danger'};
              el.className='badge bg-'+(m[rec.status]||'secondary')+' rt-status-badge';
              el.textContent=rec.status;
            });
          }
          _cache[rec.record_id]=rec.status;
        });
      }).catch(()=>{});
  }
  function showRtToast(rid,status){
    const t=document.getElementById('rtToast'),b=document.getElementById('rtToastBody');
    if(!t||!b)return;
    b.innerHTML='<strong>'+rid+'</strong> updated to <strong>'+status+'</strong>';
    new bootstrap.Toast(t,{delay:7000}).show();
  }
  document.querySelectorAll('[data-record-id]').forEach(el=>{
    const b=el.querySelector('.rt-status-badge');
    if(b) _cache[el.dataset.recordId]=b.textContent.trim();
  });
  setInterval(pollStatuses,30000);
}
</script>

<!-- Real-time toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9000;">
  <div id="rtToast" class="toast align-items-center text-bg-primary border-0">
    <div class="d-flex">
      <div class="toast-body" id="rtToastBody">Status updated!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<!-- Copy toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:8999;">
  <div id="copyToast" class="toast align-items-center text-bg-success border-0">
    <div class="d-flex">
      <div class="toast-body"><i class="bi bi-check-circle me-1"></i>Record ID copied!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://www.tuqlas.com/chatbot.js"
  data-key="tq_live_3dffb1f6fc1579a1bb36f5f71ee4c31529d20756"
  data-api="https://www.tuqlas.com" defer></script>
</body></html>
