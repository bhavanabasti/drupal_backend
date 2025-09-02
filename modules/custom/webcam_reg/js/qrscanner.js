(function ($, Drupal) {
  Drupal.behaviors.qrscanner = {
    attach: function (context, settings) {
      $('#startScan', context).once('qrscanner').on('click', function () {
        const html5QrCode = new Html5Qrcode("preview");
        const config = { fps: 10, qrbox: 250 };
        html5QrCode.start(
          { facingMode: "environment" }, 
          config,
          (decodedText) => {
            $('#result').text("Scanned QR Code: " + decodedText);
            html5QrCode.stop();
          },
          (errorMessage) => {
            console.log("QR scan error:", errorMessage);
          }
        );
      });
    }
  };
})(jQuery, Drupal);
