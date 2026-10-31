import * as THREE from './vendor/three.module.js';
import { GLTFLoader } from './vendor/GLTFLoader.js';

const host = document.getElementById('ai-products');
if (host) boot().catch(showError);
function showError(error) {
    console.error(error);
    document.getElementById('ai-model-status').textContent =
        'تعذّر عرض المجسّم. تأكد من ملفات public/models ودعم WebGL في المتصفح.';
}
async function boot() {
    const stage = document.getElementById('ai-stage');
    const status = document.getElementById('ai-model-status');
    const image = document.getElementById('generated-design');
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    stage.append(renderer.domElement);
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(35, 1, .01, 50);
    camera.position.set(0, .05, 3.9);
    camera.lookAt(0, 0, 0);
    scene.add(new THREE.HemisphereLight(0xffffff, 0x665879, 2.8));
    for (const [x,y,z,power] of [[3,4,5,3],[-3,1,2,2],[0,3,-3,2]]) {
        const light = new THREE.DirectionalLight(0xffffff, power);
        light.position.set(x,y,z); scene.add(light);
    }
    const loader = new GLTFLoader();
    const texture = await new THREE.TextureLoader().loadAsync(image.src);
    texture.colorSpace = THREE.SRGBColorSpace;
    const uniforms = {
        design: { value: texture },
        rect: { value: new THREE.Vector4(0, 0, .5, .6) },
        mug: { value: 0 },
    };
    let current = null, sequence = 0, mug = false, pending = false;
    const render = () => {
        if (pending) return;
        pending = true;
        requestAnimationFrame(() => { pending = false; renderer.render(scene,camera); });
    };
    const resize = () => {
        const {width,height} = stage.getBoundingClientRect();
        if (!width || !height) return;
        renderer.setSize(width,height,false);
        camera.aspect=width/height; camera.updateProjectionMatrix(); render();
    };
    new ResizeObserver(resize).observe(stage);
    function dispose(group) {
        const textures = new Set();
        group.traverse(mesh => {
            if (!mesh.isMesh) return;
            mesh.geometry.dispose();
            for (const material of [].concat(mesh.material)) {
                for (const value of Object.values(material)) if(value?.isTexture) textures.add(value);
                material.dispose();
            }
        });
        textures.forEach(t=> {if(t!==texture)t.dispose();});
    }
    function updatePrint() {
        const scale = Number(document.getElementById('ai-print-size').value)/100;
        const ratio = image.naturalWidth / image.naturalHeight || 1;
        const maxW=mug?.85:.55, maxH=mug?.85:.62;
        let w=maxW*scale, h=w/ratio;
        if(h>maxH*scale){h=maxH*scale;w=h*ratio;}
        const x=Number(document.getElementById('ai-print-x').value)/100*(maxW-w)/2;
        const y=(mug?0:.05)+Number(document.getElementById('ai-print-y').value)/100*(maxH-h)/2;
        uniforms.rect.value.set(x,y,w,h); uniforms.mug.value=mug?1:0; render();
    }
    function addPrint(material) {
        material.onBeforeCompile = shader => {
            shader.uniforms.aiDesign=uniforms.design;
            shader.uniforms.aiRect=uniforms.rect;
            shader.uniforms.aiMug=uniforms.mug;
            shader.vertexShader='varying vec3 aiPosition; varying vec3 aiNormal;\n'+shader.vertexShader;
            shader.vertexShader=shader.vertexShader.replace('#include <begin_vertex>',
                '#include <begin_vertex>\naiPosition=position; aiNormal=normal;');
            shader.fragmentShader='uniform sampler2D aiDesign; uniform vec4 aiRect; uniform float aiMug; varying vec3 aiPosition; varying vec3 aiNormal;\n'+shader.fragmentShader;
            shader.fragmentShader=shader.fragmentShader.replace('#include <map_fragment>', `
                #include <map_fragment>
                vec2 aiUV=(aiPosition.xy-aiRect.xy)/aiRect.zw+0.5;
                bool aiFront=aiPosition.z>0.02 && normalize(aiNormal).z>0.2;
                if(aiFront && aiUV.x>=0.0 && aiUV.x<=1.0 && aiUV.y>=0.0 && aiUV.y<=1.0){
                    vec4 ink=texture2D(aiDesign,aiUV);
                    diffuseColor.rgb=mix(diffuseColor.rgb,ink.rgb,ink.a);
                }
            `);
        };
        material.customProgramCacheKey=()=> 'loothub-front-print-v1';
        material.needsUpdate=true;
    }
    const credits = {
        'hoodie-black': 'Hoodie — Obridje · CC BY 4.0 · Sketchfab',
        'hoodie-white': 'Hoodie — Ahmad Riazi · CC BY 4.0 · Sketchfab',
        'tshirt-white': 'T Shirt — funlab117 · CC BY 4.0 · Sketchfab',
        'tshirt-black': 'Black T-Shirt — Dizzy Engine · CC BY 4.0 · Sketchfab',
        'mug-white': 'White Cup — rxdevebg · Sketchfab Standard License',
    };
    async function select(key) {
        const request=++sequence;
        status.textContent='جاري تحميل المنتج…';
        const gltf=await loader.loadAsync(new URL('../models/'+key+'.glb',import.meta.url).href);
        if(request!==sequence){dispose(gltf.scene);return;}
        const source=gltf.scene;
        source.updateMatrixWorld(true);
        const box=new THREE.Box3().setFromObject(source);
        const center=box.getCenter(new THREE.Vector3());
        const size=box.getSize(new THREE.Vector3());
        const scale=1.9/Math.max(size.x,size.y,size.z);
        const normalize=new THREE.Matrix4().makeScale(scale,scale,scale)
            .multiply(new THREE.Matrix4().makeTranslation(-center.x,-center.y,-center.z));
        const group=new THREE.Group();
        source.traverse(mesh=>{
            if(!mesh.isMesh)return;
            const geometry=mesh.geometry.clone();
            geometry.applyMatrix4(new THREE.Matrix4().multiplyMatrices(normalize,mesh.matrixWorld));
            const materials=[].concat(mesh.material).map(m=>{
                const material=m.clone();
                material.side=THREE.DoubleSide;
                if(material.metalness!==undefined)material.metalness=Math.min(material.metalness,.15);
                addPrint(material); return material;
            });
            group.add(new THREE.Mesh(geometry,Array.isArray(mesh.material)?materials:materials[0]));
        });
        // Geometry was copied; materials share the source textures until disposal.
        source.traverse(m=>{if(m.isMesh){m.geometry.dispose();[].concat(m.material).forEach(v=>v.dispose());}});
        if(current){scene.remove(current);dispose(current);}
        current=group;scene.add(group);mug=key==='mug-white';
        host.querySelectorAll('[data-model]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.model===key)));
        document.getElementById('ai-model-credit').textContent=credits[key]||'راجع ترخيص المجسّم الأصلي قبل النشر.';
        status.textContent='معاينة الطباعة — اسحب لتدوير المنتج.';
        updatePrint();resize();
    }
    host.querySelectorAll('[data-model]').forEach(button=>button.addEventListener('click',()=>select(button.dataset.model).catch(showError)));
    host.querySelectorAll('input[type="range"]').forEach(input=>input.addEventListener('input',updatePrint));
    document.getElementById('ai-front').addEventListener('click',()=>{if(current){current.rotation.set(0,0,0);render();}});
    let drag=null;
    stage.addEventListener('pointerdown',e=>{drag={x:e.clientX,y:e.clientY};stage.setPointerCapture(e.pointerId);});
    stage.addEventListener('pointermove',e=>{
        if(!drag||!current)return;
        current.rotation.y+=(e.clientX-drag.x)*.008;
        current.rotation.x=THREE.MathUtils.clamp(current.rotation.x+(e.clientY-drag.y)*.004,-.45,.45);
        drag={x:e.clientX,y:e.clientY};render();
    });
    stage.addEventListener('pointerup',()=>drag=null);
    stage.addEventListener('pointercancel',()=>drag=null);
    await select('hoodie-black');
}
