;(function($) { 
	$.entwine('sws', function($){

		$('input.shipping-same-address').entwine({

			onmatch : function() {
				var self = this;
				var form = this.closest('form');

				this.on('change', function(e) {
					self._copyAddress(e);
				});
				
				$('#address-shipping input[type=text], #address-shipping select', form).on('keyup blur', function(e){
					self._copyAddress(e);
				});

				this._copyAddress();

				this._super();
			},

			onunmatch: function() {
				this._super();
			},
			
			_copyAddress: function(e) {
				var form = this.closest('form');

				if (this.is(':checked')) {
	        $('#address-shipping input[type=text], #address-shipping select', form).each(function(){
            $('#' + $(this).attr('id').replace(/Shipping/i, 'Billing'))
	            .val($('#' + $(this).attr('id')).val())
	            .parent().parent().hide();
	        });
    		}
    		//Only clear fields if specifically unticking checkbox
        else if ($(e.currentTarget).attr('id') == this.attr('id')) {
          $('#address-shipping input[type=text], #address-shipping select', form).each(function(){
            $('#' + $(this).attr('id').replace(/Shipping/i, 'Billing'))
              .val('')
              .parent().parent().show();
          });
        }
			}
		});

	});
})(jQuery);