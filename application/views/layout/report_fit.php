<script>
// Fit-to-page for printed report cards. Each .mark-container is measured at
// the printed page width (A4, data-layout portrait or landscape); when it is
// up to about a quarter taller than the page it is zoomed down for print so
// the whole term stays on one sheet. Anything longer is left at full size and
// carries on to the next page (it is never clipped).
(function () {
	var MM = 96 / 25.4;
	function fit() {
		var cards = document.querySelectorAll('.mark-container');
		for (var i = 0; i < cards.length; i++) {
			var card = cards[i], landscape = card.getAttribute('data-layout') === 'landscape';
			var keep = card.style.cssText;
			card.style.width = (landscape ? 297 : 210) + 'mm';
			card.style.maxWidth = 'none';
			card.style.zoom = '1';
			var pageH = (landscape ? 210 : 297) * MM * 0.985;
			var scale = pageH / Math.max(card.scrollHeight, 1);
			card.style.cssText = keep;
			if (scale < 1 && scale >= 0.75) {
				card.setAttribute('data-fit', '');
				card.style.setProperty('--fit', scale.toFixed(3));
			} else {
				card.removeAttribute('data-fit');
			}
		}
	}
	fit();
	var imgs = document.querySelectorAll('.mark-container img');
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
