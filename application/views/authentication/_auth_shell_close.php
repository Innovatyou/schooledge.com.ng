				<p class="sk-card-foot"><?php echo $global_config['footer_text']; ?></p>
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
