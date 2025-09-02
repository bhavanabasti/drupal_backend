(function () {
  let video, canvas, ctx, statusEl, resultEl;
  let stream = null, detector = null, useJsQr = false, raf = null;
  let scanning = true; // 🚨 flag to prevent multiple scans

  const settings = (window.drupalSettings && window.drupalSettings.qr_scanner_simple) || { stopAfterFirst: false };

  function setStatus(msg){ 
    if(statusEl){ statusEl.textContent = msg; } 
    console.log("📢 STATUS:", msg);
  }

  async function setupDetector(){
    try {
      if ('BarcodeDetector' in window) {
        const formats = await window.BarcodeDetector.getSupportedFormats();
        if (formats && formats.includes('qr_code')) {
          detector = new window.BarcodeDetector({ formats: ['qr_code'] });
          useJsQr = false;
          return;
        }
      }
    } catch(e){ console.error("❌ Detector setup error:", e); }
    await loadJsQr();
    useJsQr = true;
  }

  function loadJsQr(){
    return new Promise((resolve, reject)=>{
      if (window.jsQR) return resolve();
      const s = document.createElement('script');
      s.src = 'https://unpkg.com/jsqr@1.4.0/dist/jsQR.js';
      s.onload = () => resolve();
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function pickConstraints(){
    return { video: { facingMode: { ideal: 'environment' } }, audio: false };
  }

  async function openCamera(){
    try{
      stream = await navigator.mediaDevices.getUserMedia(pickConstraints());
      video.srcObject = stream;
      await video.play();
      canvas.width = video.videoWidth || 640;
      canvas.height = video.videoHeight || 480;
      setStatus('Scanning…');
      loop();
    }catch(e){
      console.error("❌ Camera error:", e);
      setStatus('Camera error. Use HTTPS and allow camera permission.');
    }
  }

  function drawFrame(){
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  }

  function handleFound(text) {
    if (!text || !scanning) return; // 🚨 ignore duplicates
    scanning = false; // lock further scans
    console.log("🔍 QR Found:", text);

    resultEl.value = text;

    fetch(Drupal.url('qr-scanner-simple/submit'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: 'qr_value=' + encodeURIComponent(text)
    })
    .then(res => res.json())
   .then(data => {
  console.log("✅ Response from Drupal:", data);

  const msgEl = document.getElementById("scan-message");
  if (msgEl) {
    if (data.status === "success") {
      msgEl.textContent = "✅ Scan successful! Device ID: " + data.received;
      msgEl.style.color = "green";
    } else {
      msgEl.textContent = data.message || "⚠️ Scan failed or already in use.";
      msgEl.style.color = "red";
    }
  }

  setStatus("Scanned: " + data.received);
  stop(); // still stop after backend confirms
});

  }

  async function loop(){
    drawFrame();
    try{
      if (detector && !useJsQr){
        const codes = await detector.detect(canvas);
        if (codes && codes.length && codes[0].rawValue){
          handleFound(codes[0].rawValue);
        }
      } else if (window.jsQR){
        const img = ctx.getImageData(0,0,canvas.width,canvas.height);
        const code = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
        if (code && code.data){ handleFound(code.data); }
      }
    }catch(e){ console.error("❌ Loop error:", e); }
    raf = requestAnimationFrame(loop);
  }

  function stop() {
    if (raf) cancelAnimationFrame(raf);
    raf = null;

    if (stream) { 
      stream.getTracks().forEach(t => { 
        try { t.stop(); console.log("🛑 Track stopped:", t.kind); }
        catch(e) { console.error("❌ Error stopping track:", e); }
      });
      stream = null;
    }

    if (video) {
      video.pause();
      video.srcObject = null;
      video.removeAttribute("src");
      video.load(); // 🔑 fully release camera
      video.style.display = "none";
    }
    if (canvas) {
      canvas.style.display = "none";
    }

    // setStatus('Stopped.');
  }

  function init(){
    video = document.getElementById('qrs-video');
    canvas = document.getElementById('qrs-canvas');
    statusEl = document.getElementById('qrs-status');
    resultEl = document.getElementById('qrs-result');
    ctx = canvas.getContext('2d', { willReadFrequently: true });
    setupDetector().then(openCamera);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
