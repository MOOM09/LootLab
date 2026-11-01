// LootLab interactive GLB viewer, with preview fallback and visible error diagnostics.
const host = document.querySelector('[data-lootlab-3d-viewer]');
if (host) {
  startViewer(host).catch((error) => {
    console.error('LootLab 3D viewer:', error);
    const status = host.querySelector('[data-lootlab-3d-status]');
    if (status) {
      status.hidden = false;
      status.textContent = '3D could not load: ' + (error?.message || 'Unknown error') + '. Preview image remains available.';
      status.style.background = 'rgba(0,0,0,.8)';
      status.style.padding = '8px';
    }
    host.querySelector('canvas')?.remove();
  });
}

async function startViewer(host) {
  const status = host.querySelector('[data-lootlab-3d-status]');
  const preview = host.querySelector('[data-lootlab-3d-preview]');
  const modelUrl = host.dataset.modelSrc;
  if (!modelUrl) throw new Error('Missing GLB path in product record');
  const THREE = await import('./vendor/three.module.js');
  const { GLTFLoader } = await import('./vendor/GLTFLoader.js');
  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(devicePixelRatio || 1, 2));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.domElement.style.position = 'relative';
  renderer.domElement.style.zIndex = '1';
  renderer.domElement.style.touchAction = 'none';
  host.prepend(renderer.domElement);

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(35, 1, 0.01, 1000);
  const hemisphere = new THREE.HemisphereLight(0xffffff, 0x706080, 3.0);
  scene.add(hemisphere);
  [[4, 6, 7, 3], [-5, 2, 4, 2], [1, 5, -6, 2]].forEach(([x,y,z,intensity]) => {
    const light = new THREE.DirectionalLight(0xffffff, intensity);
    light.position.set(x,y,z);
    scene.add(light);
  });
  const gltf = await new GLTFLoader().loadAsync(modelUrl);
  const model = gltf.scene;
  model.updateMatrixWorld(true);
  const bounds = new THREE.Box3().setFromObject(model);
  if (bounds.isEmpty()) throw new Error('GLB has no visible geometry');
  const center = bounds.getCenter(new THREE.Vector3());
  const size = bounds.getSize(new THREE.Vector3());
  const longest = Math.max(size.x, size.y, size.z);
  if (!Number.isFinite(longest) || longest <= 0) throw new Error('Invalid GLB bounds');
  model.position.sub(center);
  const group = new THREE.Group();
  group.add(model);
  group.scale.setScalar(2.3 / longest);
  scene.add(group);

  let yaw = 0.25, pitch = 0, distance = 4.6;
  let drag = null;
  const draw = () => {
    group.rotation.set(pitch, yaw, 0);
    camera.position.set(0, 0, distance);
    camera.lookAt(0, 0, 0);
    renderer.render(scene, camera);
  };
  const resize = () => {
    const rect = host.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    renderer.setSize(rect.width, rect.height, false);
    camera.aspect = rect.width / rect.height;
    camera.updateProjectionMatrix();
    draw();
  };
  if (window.ResizeObserver) new ResizeObserver(resize).observe(host);
  else window.addEventListener('resize', resize);

  host.addEventListener('pointerdown', (event) => {
    if (event.target.closest('button')) return;
    drag = { x: event.clientX, y: event.clientY };
    host.setPointerCapture(event.pointerId);
  });
  host.addEventListener('pointermove', (event) => {
    if (!drag) return;
    yaw += (event.clientX - drag.x) * 0.009;
    pitch = THREE.MathUtils.clamp(pitch + (event.clientY - drag.y) * 0.007, -1.2, 1.2);
    drag = { x: event.clientX, y: event.clientY };
    draw();
  });
  host.addEventListener('pointerup', () => { drag = null; });
  host.addEventListener('pointercancel', () => { drag = null; });
  host.addEventListener('wheel', (event) => {
    event.preventDefault();
    distance = THREE.MathUtils.clamp(distance + event.deltaY * 0.003, 2.5, 9);
    draw();
  }, { passive: false });
  host.querySelector('[data-lootlab-3d-reset]')?.addEventListener('click', () => {
    yaw = 0.25; pitch = 0; distance = 4.6; draw();
  });
  if (preview) preview.hidden = true;
  if (status) status.hidden = true;
  resize();
}
