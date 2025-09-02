(function ($, Drupal, once, drupalSettings) {
  Drupal.behaviors.qrscanner = {
    attach: function (context, settings) {
      $(once('qrscanner', '#scanBtn', context)).on('click', function (e) {
        e.preventDefault();

        const resultEl = document.getElementById("scanResult");
        const html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: 250 };

        function onScanSuccess(decodedText) {
          resultEl.innerText = "QR Detected: " + decodedText;

          // Stop the camera
          html5QrCode.stop().catch(console.error);

          // Get current user UID from drupalSettings
          const uid = drupalSettings.evWallet?.uid;
          if (!uid) {
            alert("User not logged in.");
            return;
          }

          const payload = {
            uid: uid,
            device_id: decodedText,
            status: "success",
          };

          // Save to Drupal
          fetch("/api/device_event_log", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
          })
          .then(r => r.json())
          .then(data => alert("Saved to Drupal successfully"))
          .catch(err => alert("Error saving to Drupal"));

          // Send to ESP device
          fetch("http://172.16.233.195/api/check_device", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
          })
          .then(r => r.json())
          .then(data => alert("Device " + data.status + " | Current: " + data.current + " A"))
          .catch(err => alert("Error contacting ESP device"));
        }

        // Get cameras and start scanner
        Html5Qrcode.getCameras().then(devices => {
          if (!devices || !devices.length) {
            alert("No camera found");
            return;
          }

          let backCamera =
            devices.find(d => d.label.toLowerCase().includes("back")) ||
            devices.find(d => d.label.toLowerCase().includes("environment")) ||
            devices[devices.length - 1];

          html5QrCode.start(
            { deviceId: { exact: backCamera.id } },
            config,
            onScanSuccess,
            errorMessage => console.log("QR scan error:", errorMessage)
          ).catch(err => alert("Camera access error: " + err));
        }).catch(err => alert("Camera list error: " + err));
      });
    }
  };
})(jQuery, Drupal, once, drupalSettings);
