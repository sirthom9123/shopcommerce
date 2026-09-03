(function () {
	'use strict';

	var root = document.querySelector('[data-product-tabs]');
	if (!root) {
		return;
	}

	var tabs = root.querySelectorAll('.product-tabs__tab');
	var panels = root.querySelectorAll('.product-tabs__panel');

	function activateTab(tabKey) {
		tabs.forEach(function (tab) {
			var isActive = tab.getAttribute('data-tab') === tabKey;
			tab.classList.toggle('is-active', isActive);
			tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
		});

		panels.forEach(function (panel) {
			var isActive = panel.getAttribute('data-panel') === tabKey;
			panel.classList.toggle('is-active', isActive);
			if (isActive) {
				panel.removeAttribute('hidden');
			} else {
				panel.setAttribute('hidden', '');
			}
		});
	}

	tabs.forEach(function (tab) {
		tab.addEventListener('click', function () {
			activateTab(tab.getAttribute('data-tab'));
		});
	});
})();
