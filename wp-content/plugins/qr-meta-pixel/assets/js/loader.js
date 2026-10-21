(function () {
	function start() {
		if (window.__qrMetaStarted) {
			return;
		}
		if (!window.qrConsent || !qrConsent.granted("marketing")) {
			return;
		}
		var cfg = window.qrMetaPixelBoot || {};
		if (!cfg.pixelId) {
			return;
		}
		window.__qrMetaStarted = true;
		!function (f, b, e, v, n, t, s) {
			if (f.fbq) return;
			n = f.fbq = function () {
				n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
			};
			if (!f._fbq) f._fbq = n;
			n.push = n;
			n.loaded = !0;
			n.version = "2.0";
			n.queue = [];
			t = b.createElement(e);
			t.async = !0;
			t.src = v;
			s = b.getElementsByTagName(e)[0];
			s.parentNode.insertBefore(t, s);
		}(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
		fbq("init", cfg.pixelId);
		fbq("track", "PageView");
		(cfg.events || []).forEach(function (event) {
			fbq("track", event.name, event.params || {});
		});
	}

	function bind() {
		start();
		if (window.qrConsent) {
			qrConsent.onChange(start);
		}
	}

	if (window.qrConsent) {
		bind();
	} else {
		document.addEventListener("qr-consent-ready", bind);
	}
})();
