(() => {
  'use strict';
  const mapElement = document.getElementById('checkout-jordan-map');
  if (!mapElement) return;
  const address = document.getElementById('shipping_address');
  const latInput = document.getElementById('delivery_lat');
  const lngInput = document.getElementById('delivery_lng');
  const labelInput = document.getElementById('meeting_point_label');
  const searchBtn = document.getElementById('checkout-find-address');
  const locationBtn = document.getElementById('checkout-my-location');
  const results = document.getElementById('checkout-address-results');
  const status = document.getElementById('checkout-map-status');
  const preview = document.getElementById('checkout-meeting-point-preview');
  const forms = mapElement.closest('form');
  const bounds = [[29.12, 34.86], [33.45, 39.35]];
  const withinBounds = (lat, lng) => Number.isFinite(lat) && Number.isFinite(lng) && lat >= bounds[0][0] && lat <= bounds[1][0] && lng >= bounds[0][1] && lng <= bounds[1][1];
  const setStatus = (message, error = false) => { status.textContent = message; status.dataset.error = String(error); };
  const clearPin = () => { latInput.value = ''; lngInput.value = ''; labelInput.value = ''; preview.textContent = 'Not selected'; if (marker) { map.removeLayer(marker); marker = null; } };
  if (!window.L) { setStatus('Map library could not load. Please check your internet connection and refresh.', true); return; }

  const map = L.map(mapElement, { scrollWheelZoom: false }).setView([31.3, 36.1], 7);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>'
  }).addTo(map);
  let marker = null;
  let lastRequest = 0;
  let requestQueue = Promise.resolve();
  let currentSelection = 0;
  const nominatim = (path, params) => {
    const task = requestQueue.then(async () => {
      const wait = Math.max(0, 1100 - (Date.now() - lastRequest));
      if (wait) await new Promise(resolve => setTimeout(resolve, wait));
      lastRequest = Date.now();
      const query = new URLSearchParams({ format: 'jsonv2', addressdetails: '1', ...params });
      const response = await fetch(`https://nominatim.openstreetmap.org/${path}?${query.toString()}`, {
        headers: { Accept: 'application/json' }, referrerPolicy: 'strict-origin-when-cross-origin'
      });
      if (!response.ok) throw new Error(`Map search service returned HTTP ${response.status}`);
      return response.json();
    });
    requestQueue = task.catch(() => {});
    return task;
  };
  const showMarker = (lat, lng) => {
    if (!marker) {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', () => { const p = marker.getLatLng(); void choosePin(p.lat, p.lng); });
    } else marker.setLatLng([lat, lng]);
    map.setView([lat, lng], Math.max(map.getZoom(), 15));
  };
  const choosePin = async (lat, lng, knownLabel = '') => {
    const selection = ++currentSelection;
    if (!withinBounds(lat, lng)) { clearPin(); setStatus('Please select a location inside Jordan.', true); return; }
    setStatus('Checking the selected location in Jordan…');
    try {
      const result = await nominatim('reverse', { lat: String(lat), lon: String(lng), zoom: '18' });
      if (selection !== currentSelection) return;
      if (result.error || result.address?.country_code?.toLowerCase() !== 'jo') {
        clearPin(); setStatus('This point is outside Jordan. Choose a point inside Jordan.', true); return;
      }
      const label = String(knownLabel || result.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`).slice(0, 255);
      latInput.value = lat.toFixed(7);
      lngInput.value = lng.toFixed(7);
      labelInput.value = label;
      preview.textContent = label;
      showMarker(lat, lng);
      setStatus('Meeting point pinned! Drag the marker to adjust the exact delivery location.');
    } catch (error) {
      if (selection !== currentSelection) return;
      clearPin(); setStatus('Could not confirm this point. Check your internet connection and try again.', true);
    }
  };
  map.on('click', (event) => { void choosePin(event.latlng.lat, event.latlng.lng); });

  const search = async () => {
    const q = address.value.trim();
    if (q.length < 3) { setStatus('Enter your shipping address first (at least 3 characters).', true); address.focus(); return; }
    searchBtn.disabled = true;
    results.hidden = true;
    results.replaceChildren();
    setStatus('Searching for your address in Jordan…');
    try {
      const places = await nominatim('search', { q, countrycodes: 'jo', limit: '5' });
      if (!Array.isArray(places) || places.length === 0) {
        setStatus('No matching address found. Try adding the city or neighborhood, or pin your location manually.', true);
        return;
      }
      places.forEach(place => {
        const lat = Number(place.lat), lng = Number(place.lon);
        if (!withinBounds(lat, lng)) return;
        const btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'checkout-map-result'; btn.textContent = place.display_name;
        btn.addEventListener('click', () => {
          results.hidden = true;
          void choosePin(lat, lng, place.display_name);
        });
        results.appendChild(btn);
      });
      if (results.children.length) { results.hidden = false; setStatus('Choose a result below, then drag the pin to the exact meeting point.'); }
      else setStatus('No matching location inside Jordan was found.', true);
    } catch (error) { setStatus('Address search unavailable. You can still click on the map to select a meeting point.', true); }
    finally { searchBtn.disabled = false; }
  };
  searchBtn.addEventListener('click', () => { void search(); });
  address.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); void search(); } });
  locationBtn.addEventListener('click', () => {
    if (!navigator.geolocation) { setStatus('Your browser does not support location access.', true); return; }
    setStatus('Waiting for location permission…');
    navigator.geolocation.getCurrentPosition(
      position => { void choosePin(position.coords.latitude, position.coords.longitude); },
      () => setStatus('Location access was denied or unavailable. Choose your meeting point on the map.', true),
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
  });
  if (forms) forms.addEventListener('submit', event => {
    if (!latInput.value || !lngInput.value) {
      event.preventDefault();
      setStatus('Pin your delivery meeting point on the map before continuing.', true);
      mapElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
  const savedLat = Number(latInput.value), savedLng = Number(lngInput.value);
  if (latInput.value && lngInput.value && withinBounds(savedLat, savedLng)) {
    showMarker(savedLat, savedLng);
    preview.textContent = labelInput.value || `${savedLat.toFixed(6)}, ${savedLng.toFixed(6)}`;
    setStatus('Your previously selected meeting point has been restored.');
  }
  setTimeout(() => map.invalidateSize(), 100);
})();
