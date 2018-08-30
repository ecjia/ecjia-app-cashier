// JavaScript Document
;(function (app, $) {
    app.cashdesk_scales = {
        init: function () {
            app.cashdesk_scales.toggle_wipezero();
            app.cashdesk_scales.toggle_reserve_quantile();
        },
        toggle_wipezero: function() {
			$('[data-trigger="toggle_wipezero"]').on('click', function(e) {
				e.preventDefault();
				var $this = $(this);
				var url = $this.attr('data-url');
				var id = $this.attr('data-id');
				var val = $this.hasClass('fa-times') ? 1 : 0;
				var type = $this.attr('data-type') ? $this.attr('data-type') : "POST";
				var pjaxurl = $this.attr('refresh-url');

				var option = {
					obj: $this,
					url: url,
					id: id,
					val: val,
					type: type
				};

				$.ajax({
					url: option.url,
					data: {
						id: option.id,
						val: option.val
					},
					type: option.type,
					dataType: "json",
					success: function(data) {
						data.content ? option.obj.removeClass('fa-times').addClass('fa-check') : option.obj.removeClass('fa-check').addClass('fa-times');
						ecjia.pjax(pjaxurl, function() {
							ecjia.merchant.showmessage(data);
						});
					}
				});
			})
		},
		
		toggle_reserve_quantile: function() {
			$('[data-trigger="toggle_reserve_quantile"]').on('click', function(e) {
				e.preventDefault();
				var $this = $(this);
				var url = $this.attr('data-url');
				var id = $this.attr('data-id');
				var val = $this.hasClass('fa-times') ? 1 : 0;
				var type = $this.attr('data-type') ? $this.attr('data-type') : "POST";
				var pjaxurl = $this.attr('refresh-url');

				var option = {
					obj: $this,
					url: url,
					id: id,
					val: val,
					type: type
				};

				$.ajax({
					url: option.url,
					data: {
						id: option.id,
						val: option.val
					},
					type: option.type,
					dataType: "json",
					success: function(data) {
						data.content ? option.obj.removeClass('fa-times').addClass('fa-check') : option.obj.removeClass('fa-check').addClass('fa-times');
						ecjia.pjax(pjaxurl, function() {
							ecjia.merchant.showmessage(data);
						});
					}
				});
			})
		}
        
        
    };
    
})(ecjia.merchant, jQuery);
 
// end