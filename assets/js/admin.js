(function ($) {
	var moduleSwitchRequest = function (data, btn) {
		$.post(optimizeformCore.ajaxUrl, data)
			.done(function (r) {
				if (r.success && r.nonce) {
					btn.data('nonce', r.nonce);
				}

				if (r.success) {
					if (data.action === 'optimizeform_core_activate_module') {
						$.post(optimizeformCore.ajaxUrl, { slug: data.slug, action: 'optimizeform_core_module_footer' })
							.done(function (r) {
								btn.parent('.footer').html(r.footer);
							})
							.complete(function () {
								btn.removeClass('loading');
								$('.optimizeform-core-module-switch').removeClass('loading');
							});
					} else {
						btn.parent('.footer').html(r.footer);
					}
				}

				if (!r.success && r.message) {
					$('.optimizeform-core-module-switch').removeClass('loading');
					btn.toggleClass('switch-on switch-off');
					alert(r.message);
				}
			})
			.complete(function () {
				// $('.optimizeform-core-module-switch').removeClass('loading');
			});
	};

	var moduleInstallRequest = function (data, btn) {
		$.post(optimizeformCore.ajaxUrl, data)
			.done(function (r) {
				if (r.success) {
					$.post(optimizeformCore.ajaxUrl, { slug: data.slug, action: 'optimizeform_core_module_footer' })
						.done(function (r) {
							btn.parent('.footer').html(r.footer);
						})
						.complete(function () {
							btn.removeClass('loading');
						});
				} else if (r.data.errorMessage) {
					btn.removeClass('loading');
					alert(r.data.errorMessage);
				}
			})
			.complete(function () {
				$('.install-btn').not(btn).removeClass('loading');
			})
	};

	$(document).ready(function () {
		$(document).on('click', '.install-btn', function (e) {
			e.preventDefault;
			var btn = $(this);

			if (btn.hasClass('loading')) {
				return false;
			}

			$('.install-btn').addClass('loading');

			moduleInstallRequest({
				'slug': btn.data('slug'),
				'action': 'install-plugin',
				'_wpnonce': btn.data('nonce')
			}, btn);
			return false;
		});

		$(document).on('click', '.optimizeform-core-module-switch', function (e) {
			e.preventDefault;
			var btn = $(this);

			if (btn.hasClass('loading')) {
				return false;
			}

			$('.optimizeform-core-module-switch').addClass('loading');

			if (btn.hasClass('switch-on')) {
				btn.toggleClass('switch-on switch-off');
				moduleSwitchRequest({
					'slug': btn.data('slug'),
					'action': 'optimizeform_core_deactivate_module',
					'nonce': btn.data('nonce')
				}, btn);
			} else {
				btn.toggleClass('switch-on switch-off');
				moduleSwitchRequest({
					'slug': btn.data('slug'),
					'action': 'optimizeform_core_activate_module',
					'nonce': btn.data('nonce')
				}, btn);
			}
			return false;
		});
	});
	$(document).ready(function () {
		var ctx = $('#myChart');
		const inputs = {
			min: -100,
			max: 100,
			count: 8,
			decimals: 2,
			continuity: 1
		  };
		  
		  const generateLabels = () => {
			'January'
		  };
		  
		  
		var myChart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: ['January','February','March','April'],
				datasets: [
					{
					label:['customer_1'],
					data: [12, 19, 3, 5, 2, 3],
					backgroundColor: [
						'rgba(255, 99, 132, 0.2)',
						'rgba(54, 162, 235, 0.2)',
						'rgba(255, 206, 86, 0.2)',
						'rgba(75, 192, 192, 0.2)',
						'rgba(153, 102, 255, 0.2)',
						'rgba(255, 159, 64, 0.2)'
					],
					borderColor: [
						'rgba(255, 99, 132, 1)',
						'rgba(54, 162, 235, 1)',
						'rgba(255, 206, 86, 1)',
						'rgba(75, 192, 192, 1)',
						'rgba(153, 102, 255, 1)',
						'rgba(255, 159, 64, 1)'
					],
					borderWidth: 1
				},
					{
					label:[ 'customer_2'],
					data: [15, 16, 2, 7, 9],
					backgroundColor: [
						'rgba(255, 99, 132, 0.2)',
						'rgba(54, 162, 235, 0.2)',
						'rgba(255, 206, 86, 0.2)',
						'rgba(75, 192, 192, 0.2)',
						'rgba(153, 102, 255, 0.2)',
						'rgba(255, 159, 64, 0.2)'
					],
					borderColor: [
						'rgba(255, 99, 132, 1)',
						'rgba(54, 162, 235, 1)',
						'rgba(255, 206, 86, 1)',
						'rgba(75, 192, 192, 1)',
						'rgba(153, 102, 255, 1)',
						'rgba(255, 159, 64, 1)'
					],
					borderWidth: 1
				}]
			},
			
			options: {
				scales: {
					y: {
						beginAtZero: true
					}
				},
				name: 'Fill: start',
			}
		});
	});
})(jQuery);
