// DB-backed Thai address cascading dropdown.
// Data comes from thai_provinces, thai_districts, thai_subdistricts via Laravel API routes.
(function(){
  const cache = { provinces:null, districts:{}, subdistricts:{} };
  async function getJson(url){ const res = await fetch(url,{headers:{'Accept':'application/json'}}); if(!res.ok) throw new Error('โหลดข้อมูลที่อยู่ไม่สำเร็จ'); return await res.json(); }
  async function provinces(){ if(!cache.provinces) cache.provinces = await getJson('/api/address/provinces'); return cache.provinces; }
  async function districts(provinceId){ if(!provinceId) return []; if(!cache.districts[provinceId]) cache.districts[provinceId] = await getJson(`/api/address/provinces/${provinceId}/districts`); return cache.districts[provinceId]; }
  async function subdistricts(districtId){ if(!districtId) return []; if(!cache.subdistricts[districtId]) cache.subdistricts[districtId] = await getJson(`/api/address/districts/${districtId}/subdistricts`); return cache.subdistricts[districtId]; }
  function setOptions(select, items, placeholder, oldValue){
    select.innerHTML = `<option value="">${placeholder}</option>` + items.map(x=>`<option value="${escapeHtml(x.name_th)}" data-id="${x.id}" data-zip="${x.zip_code||''}">${escapeHtml(x.name_th)}</option>`).join('');
    if(oldValue){ select.value = oldValue; }
  }
  function escapeHtml(s){ return String(s??'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
  function selectedId(select){ return select.selectedOptions[0]?.dataset?.id || ''; }
  window.initThaiAddressCascader = async function(scope){
    const root = scope || document;
    const boxes = root.matches?.('[data-thai-address]') ? [root] : root.querySelectorAll('[data-thai-address]');
    boxes.forEach(async box=>{
      const province = box.querySelector('[data-province]');
      const district = box.querySelector('[data-district]');
      const subdistrict = box.querySelector('[data-subdistrict]');
      const postal = box.querySelector('[data-postal]');
      if(!province || !district || !subdistrict || !postal) return;
      const old = { province: province.dataset.value || province.value, district: district.dataset.value || district.value, subdistrict: subdistrict.dataset.value || subdistrict.value, postal: postal.dataset.value || postal.value };
      async function loadProvinces(){ setOptions(province, await provinces(), 'เลือกจังหวัด', old.province); await loadDistricts(); }
      async function loadDistricts(){ setOptions(district, await districts(selectedId(province)), 'เลือกอำเภอ/เขต', old.district); await loadSubdistricts(); }
      async function loadSubdistricts(){ setOptions(subdistrict, await subdistricts(selectedId(district)), 'เลือกตำบล/แขวง', old.subdistrict); loadPostal(); }
      function loadPostal(){ const zip = subdistrict.selectedOptions[0]?.dataset?.zip || old.postal || ''; postal.value = zip; }
      province.addEventListener('change', async()=>{ old.district=''; old.subdistrict=''; old.postal=''; await loadDistricts(); });
      district.addEventListener('change', async()=>{ old.subdistrict=''; old.postal=''; await loadSubdistricts(); });
      subdistrict.addEventListener('change', ()=>{ old.postal=''; loadPostal(); });
      try{ await loadProvinces(); }catch(e){ console.error(e); }
    });
  };
  document.addEventListener('DOMContentLoaded',()=>window.initThaiAddressCascader());
})();
