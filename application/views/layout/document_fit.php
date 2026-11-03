<script>
// Shrink-to-fit for printed cards and certificates (.certificate has a fixed
// mm size). When a long subject list, address or name makes the content taller
// or wider than the card, it is scaled down just enough to fit instead of being
// cut off. Runs before the print dialog: the print window's body onload is
// wrapped, and it re-runs as photos and logos finish loading.
(function () {
	function fit() {
		var cards = document.querySelectorAll('.certificate');
		for (var i = 0; i < cards.length; i++) {
			var card = cards[i], box = card.firstElementChild;
			if (!box || !box.classList.contains('doc-fit')) {
				box = document.createElement('div');
				box.className = 'doc-fit';
				while (card.firstChild) box.appendChild(card.firstChild);
				card.appendChild(box);
			}
			// full size by default, so a template whose root uses height:100% or
			// pins a footer to the bottom lays out exactly as before
			box.style.transform = '';
			box.style.width = '100%';
			box.style.height = '100%';
			var cs = getComputedStyle(card);
			var availH = card.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
			var availW = card.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
			var scale = Math.min(1, availH / Math.max(box.scrollHeight, 1), availW / Math.max(box.scrollWidth, 1));
			if (scale < 0.999) {
				scale = Math.max(scale, 0.5);
				box.style.transformOrigin = 'top left';
				box.style.transform = 'scale(' + scale + ')';
				box.style.width = (100 / scale) + '%';
				box.style.height = (100 / scale) + '%';
			}
		}
	}
	fit();
	var imgs = document.querySelectorAll('.certificate img');
	for (var i = 0; i < imgs.length; i++) {
		if (!imgs[i].complete) imgs[i].addEventListener('load', fit);
	}
	var previous = window.onload;
	window.onload = function (e) {
		fit();
		if (typeof previous === 'function') previous.call(window, e);
	};
})();
</script>
