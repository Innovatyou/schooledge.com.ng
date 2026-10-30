				<p class="sk-card-foot"><?php echo $global_config['footer_text']; ?></p>
			</div>
			</div>
			<a class="sk-powered" href="https://schooledge.com.ng" target="_blank" rel="noopener">
				<img src="<?php echo base_url('assets/frontend/images/skoolos/mark.svg'); ?>" alt="" width="18" height="18"> Powered by <b>SkoolOS</b>
			</a>
		</section>
	</main>

	<script type="text/javascript">
		(function () {
			var root = document.documentElement;

			// light / dark toggle (same storage key as the marketing site)
			var toggle = document.querySelector('.sk-theme');
			var label = function () {
				var dark = root.getAttribute('data-theme') === 'dark';
				toggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
				toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
			};
			label();
			toggle.addEventListener('click', function () {
				var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
				root.setAttribute('data-theme', next);
				try { localStorage.setItem('skoolos-theme', next); } catch (e) {}
				label();
			});
			requestAnimationFrame(function () { root.classList.add('sk-ready'); });

			// 3D: the card leans toward the pointer and the scene drifts the other way
			var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			var fine = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
			if (!still && fine) {
				var card = document.querySelector('.sk-card');
				var glare = document.querySelector('.sk-card-glare');
				var body = document.body;
				var raf = 0, px = 0, py = 0;
				document.addEventListener('mousemove', function (e) {
					px = e.clientX / window.innerWidth - 0.5;
					py = e.clientY / window.innerHeight - 0.5;
					if (raf) return;
					raf = requestAnimationFrame(function () {
						raf = 0;
						body.style.setProperty('--px', px.toFixed(3));
						body.style.setProperty('--py', py.toFixed(3));
						var r = card.getBoundingClientRect();
						var cx = (window.innerWidth * (px + 0.5) - r.left) / r.width - 0.5;
						var cy = (window.innerHeight * (py + 0.5) - r.top) / r.height - 0.5;
						var near = Math.abs(cx) < 0.9 && Math.abs(cy) < 0.9;
						card.style.transform = near ? 'rotateY(' + (cx * 7).toFixed(2) + 'deg) rotateX(' + (-cy * 7).toFixed(2) + 'deg)' : '';
						glare.style.opacity = near ? '1' : '0';
						glare.style.background = 'radial-gradient(420px circle at ' + ((cx + 0.5) * 100).toFixed(1) + '% ' + ((cy + 0.5) * 100).toFixed(1) + '%, rgba(255,255,255,.16), transparent 45%)';
					});
				});
			}

			// show / hide password
			document.querySelectorAll('.sk-reveal').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var input = btn.parentNode.querySelector('input');
					var show = input.type === 'password';
					input.type = show ? 'text' : 'password';
					btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
					btn.innerHTML = show ? '<i class="far fa-eye-slash"></i>' : '<i class="far fa-eye"></i>';
				});
			});

			// busy state on submit so double-clicks don't post twice
			document.querySelectorAll('.sk-card form').forEach(function (form) {
				form.addEventListener('submit', function () {
					var btn = form.querySelector('.sk-submit');
					if (!btn) return;
					btn.classList.add('is-busy');
					setTimeout(function () { btn.disabled = true; }, 0);
				});
			});
		})();
	</script>

	<?php
	$alertclass = "";
	if ($this->session->flashdata('alert-message-success')) {
		$alertclass = "success";
	} else if ($this->session->flashdata('alert-message-error')) {
		$alertclass = "error";
	} else if ($this->session->flashdata('alert-message-info')) {
		$alertclass = "info";
	}
	if ($alertclass != ''):
		$alert_message = $this->session->flashdata('alert-message-' . $alertclass);
	?>
	<script type="text/javascript">
		swal({
			toast: true,
			position: 'top-end',
			type: '<?php echo $alertclass; ?>',
			title: '<?php echo $alert_message; ?>',
			confirmButtonClass: 'btn btn-default',
			buttonsStyling: false,
			timer: 8000
		})
	</script>
	<?php endif; ?>
</body>
</html>
