function validateCouponSerial() 
{
	var sCouponNumber = jQuery('#coupon_number').val();
	var params = new Array();
	params['coupon_number'] = sCouponNumber;
	params['cartnos'] = _getParameterByName( 'cartnos' );
	var respons = ['discpolicy', 'disctype', 'promotion_title'];
	exec_xml('svcart', 'getSvcartCouponValidation', params, function(ret_obj) {
		var nTotalAmnt = jQuery("#total_amount").text();
		nTotalAmnt = parseInt( nTotalAmnt.replace(/,/g, '') );
		
		var sDisctype = ret_obj['disctype'];
		var sDiscPolicy = ret_obj['discpolicy'];

		if( sDisctype == 'amount' )
			var nTotalDiscount = sDiscPolicy;
		if( sDisctype == 'rate' )
			var nTotalDiscount = nTotalAmnt * sDiscPolicy;

		var nDeliveryFee = jQuery("#delivery_fee").text();
		
		if( nDeliveryFee.length == 0 )
			nDeliveryFee = 0;
		else
			nDeliveryFee = parseInt( nDeliveryFee.replace(',', '') );

		var nMileageAmnt = jQuery("#mileage_amount").text();

		if( nMileageAmnt.length == 0 )
			nMileageAmnt = 0;
		else
			nMileageAmnt = parseInt( nMileageAmnt.replace(',', '') );

		nPaymentAmnt = parseInt( nTotalAmnt )- parseInt( nTotalDiscount )+ parseInt( nDeliveryFee ) - parseInt( nMileageAmnt );
		jQuery("#promotion_title").html(ret_obj['promotion_title']);
		jQuery("#total_discount").html( formatCurrencyInt( nTotalDiscount ) );
		jQuery("#payment_amount").html( formatCurrencyInt( nPaymentAmnt ) );
	},respons);
}

function _getParameterByName( name )
{
	var regexS = "[\\?&]"+name+"=([^&#]*)", 
	regex = new RegExp( regexS ),
	results = regex.exec( window.location.search );
	if( results == null )
		return '';
	else
		return decodeURIComponent(results[1].replace(/\+/g, ' '));
}

function formatCurrencyInt( num )
{
	num = num.toString().replace( /$|,/g,'' );
	if( isNaN( num ) )
		num = '0';

	cents = Math.floor( ( num * 100 + 0.5 ) % 100 );
	num = Math.floor( ( num * 100 + 0.5 ) / 100 ).toString();

	if( cents < 10 )
		cents = '0' + cents;

	for( var i = 0; i < Math.floor( ( num.length - ( 1 + i ) ) / 3 ); i++ )
		num = num.substring( 0, num.length - ( 4 * i + 3 ) ) + ',' + num.substring( num.length - ( 4 * i + 3 ) );
	
	return ( num );
}

function storeInsertOrder() {
	var f = document.getElementById('fo_insert_order');
	return procFilter(f, insert_order);
}

function completeGetAddressInfo(ret_obj) {
	var data = ret_obj['data'];
	var addrinfo = data.address;
	clear_form_elements(document.getElementById('section2'));

	for (var i in fieldset)
	{
		var obj = fieldset[i];
		if (!addrinfo[obj.column_name]) continue;
		switch (obj.column_type)
		{
			case 'kr_zip':
				jQuery('input[name="'+obj.column_name+'[]"]').each(function(index) {
					jQuery(this).val(addrinfo[obj.column_name].item[index]) 
				});
				var full_address = "";
				for(var i = 0; i < addrinfo[obj.column_name].item.length; i++) {
					full_address = full_address + addrinfo[obj.column_name].item[i];
				}
				jQuery('input[name="'+obj.column_name+'[]"]').next().find('.current_address').val(full_address);

				break;
			case 'tel':
				jQuery('input[name="'+obj.column_name+'[]"]').each(function(index) { jQuery(this).val(addrinfo[obj.column_name].item[index]) });
				break;
			case 'checkbox':
				for(var i = 0; i < addrinfo[obj.column_name].item.length; i++)
				{
					jQuery('input[name="'+obj.column_name+'[]"][value="'+addrinfo[obj.column_name].item[i]+'"]').each(function(index) { jQuery(this).attr('checked','checked'); });
				}
				break;
			case 'radio':
				jQuery('input[name="'+obj.column_name+'[]"][value="'+addrinfo[obj.column_name].item[0]+'"]').each(function(index) { jQuery(this).attr('checked','checked'); });
				break;
			case 'select':
				jQuery('select[name="'+obj.column_name+'"] option[value="'+addrinfo[obj.column_name]+'"]').each(function(index) { jQuery(this).attr('selected','selected'); });
				break;
			case 'date':
				var dateval = addrinfo[obj.column_name].substring(0,4) + '-' + addrinfo[obj.column_name].substring(4,6) + '-' + addrinfo[obj.column_name].substring(6,8);
				jQuery('input[name="'+obj.column_name+'"]').val(addrinfo[obj.column_name]).next('.inputDate').val(dateval);

				break;
			case 'textarea':
				jQuery('textarea[name='+obj.column_name+']').val(addrinfo[obj.column_name]);
				break;
			default:
				jQuery('input[name='+obj.column_name+']').val(addrinfo[obj.column_name]);
				break;
		}
	}
}

function apply_address_info(address_srl) {
	exec_xml('svcart'
	,'getSvcartAddressInfo'
	, {address_srl : address_srl}
	, completeGetAddressInfo
	, ['error','message','data']);
}

function set_delivery_address(recipient, cellphone, telnum, address, address2, postcode) {
	jQuery('input[name=recipient_name]').val(recipient);
	jQuery('input[name=recipient_cellphone]').val(cellphone);
	jQuery('input[name=recipient_telnum]').val(telnum);
	var addr = document.getElementById('address_list_address');
	if (addr.nodeName == 'INPUT') {
		addr.value = address;
	} else {
		jQuery('select[name=address1]').html('<option value="'+address+'" selected="selected">'+address+'</option>');
	}
	jQuery('input[name=address2]').val(address2);
	jQuery('input[name=postcode]').val(postcode);
	if (address) {
		jQuery("#zone_address_search_address").hide();
		jQuery("#zone_address_list_address").show();
	} else {
		jQuery("#zone_address_search_address").show();
		jQuery("#zone_address_list_address").hide();
	}
}

function set_address_as_purchaser() {
	set_delivery_address(purchaser_name, purchaser_cellphone, purchaser_telnum, purchaser_address, purchaser_address2, '');
}

function do_order() {
	var cartnos = makeList();
	location.href = current_url.setQuery('act','dispSvorderOrderItems').setQuery('cartnos',cartnos);
}

function calculate_totalprice(deliv) {
	var amount = total_price;
	if (deliv=='N') amount -= delivery_fee;
	return amount;
}


function calculate_payamount(mileage, deliv)
{
	if( mileage == 'N' )
		var payment_amount = total_price;
	else
		var payment_amount = total_price - mileage;
	if (deliv=='N') 
		payment_amount -= delivery_fee;
	return payment_amount;
}

(function($) {
	jQuery(function($) {
		$('#popAddressBook').click(function() {
			var url = current_url.setQuery('act','dispSvcartAddressList');
			if(getCookie('mobile') == 'true') popup_modal(url, '배송주소록 관리', "100%", 400);
			else popup_modal(url, '배송주소록 관리', 600, 400);

			$("#modal-dialog").attr("tabindex", -1).focus();
		});
		$('#popRecentAddress').click(function() {
			var url = current_url.setQuery('act','dispSvcartRecentAddress');
			if(getCookie('mobile') == 'true') popup_modal(url, '최근배송지에서 선택', "100%", 400);
			else popup_modal(url, '최근배송지에서 선택', 600, 400);

			$("#modal-dialog").attr("tabindex", -1).focus();
		});
		$('input[name=select_address]').click(function() { 
			switch ($(this).val()) {
				case 'default':
					set_delivery_address(default_recipient, default_cellphone, default_telnum, default_address, default_address2, default_postcode);
					break;
				case 'purchaser':
					if (purchaser_chk == 'N')
					{
						purchaser_name = $('#purchaser_name').val();
						purchaser_address = $("#address_list_paddress option:selected").text();
						purchaser_address2 = $('#krzip_address2_paddress').val();
						purchaser_cellphone = $('#cellphone').val();
						purchaser_telnum = $('#telnum').val();
					}
					
					set_delivery_address(purchaser_name, purchaser_cellphone, purchaser_telnum, purchaser_address, purchaser_address2, default_postcode);
					break;
				case 'new':
					set_delivery_address('', '', '', '', '', '', '');
					jQuery('input[name=recipient_name]').focus();
					break;
			}
		});

		$('input[name=input_mileage]').keyup(function() {
			var use_mileage = $(this).val();
			var reg_mileage = new RegExp("^[0-9\.]+$");
			//var reg_trim = new RegExp("^[0][0-9\.]+$");

			if (!use_mileage.match(reg_mileage))
			{
				use_mileage = 0;
				$(this).val('');
			}
			if (use_mileage < 0)
			{
				use_mileage = 0;
				$(this).val('');
			}
			raw_mileage = getRawPrice(use_mileage);

			if (total_price < raw_mileage && total_price <= my_mileage)
			{
				raw_mileage = total_price; 
				$(this).val(getPrice(raw_mileage));
			}
			if (raw_mileage > my_mileage)
			{
				raw_mileage = my_mileage;
				$(this).val(getPrice(my_mileage));
			}

			$('input[name=use_mileage]').val(raw_mileage);

			var delivfee_inadvance = $('input[name=delivfee_inadvance]:checked').val();
			var payment_amount = calculate_payamount(raw_mileage, delivfee_inadvance);

			$('#mileage_amount').text(getPrice(raw_mileage));
			$('#payment_amount').text(getPrice(payment_amount));
			if(payment_amount == 0)
			{	
				var answer = confirm('마일리지로 결제 하시겠습니까?');
				if(answer)
				{
					$('#fo_insert_order').submit();
				}
				else alert('마일리지 결제를 취소 하셨습니다.');
			}
		});

		$('input[name=delivfee_inadvance]').click(function() {
			var use_mileage = $('input[name=use_mileage]').val();

			if( use_mileage === undefined )
				use_mileage = 'N';

			if (use_mileage > my_mileage) use_mileage = my_mileage;
			var delivfee_inadvance = $(this).val();
			if (delivfee_inadvance=='Y')
			{
				$('#delivery_fee').text(number_format(delivery_fee) + ' 원');
			}
			else 
			{
				$('#delivery_fee').text('0 원');
			}
			var orderamount = calculate_totalprice(delivfee_inadvance);
			var payamount = calculate_payamount(use_mileage,delivfee_inadvance);
			//$('#order_amount').text(number_format(orderamount));
			//$('#order_amount2').text(number_format(orderamount));
			$('#payment_amount').text(number_format(payamount) + ' 원');
		});
	});
}) (jQuery);


jQuery(document).ready(function ()
{
	jQuery('#purchaser_email').blur(function()
	{
		if( !isValidEmail( jQuery('#purchaser_email').val() ) )
			alert( '유효한 이메일이 아닙니다.' );
	});

	jQuery('#purchaser_cellphone_1').blur(function()
	{
		if( jQuery('#purchaser_cellphone_1').val().length > 1 )
			if( !jQuery.isNumeric( jQuery('#purchaser_cellphone_1').val() ) )
			{
				alert('유효한 전화번호가 아닙니다.');
				jQuery('#purchaser_cellphone_1').focus()
			}
	});

	jQuery('#purchaser_cellphone_2').blur(function()
	{
		if( jQuery('#purchaser_cellphone_2').val().length > 1 )
			if( !jQuery.isNumeric( jQuery('#purchaser_cellphone_2').val() ) )
			{
				alert('유효한 전화번호가 아닙니다.');
				jQuery('#purchaser_cellphone_2').focus()
			}
	});

	jQuery('#purchaser_cellphone_3').blur(function()
	{
		if( jQuery('#purchaser_cellphone_3').val().length > 1 )
			if( !jQuery.isNumeric( jQuery('#purchaser_cellphone_3').val() ) )
			{
				alert('유효한 전화번호가 아닙니다.');
				jQuery('#purchaser_cellphone_3').focus()
			}
	});

	jQuery('#recipient_cellphone_1').blur(function()
	{
		if( jQuery('#recipient_cellphone_1').val().length > 1 )
			if( !jQuery.isNumeric( jQuery('#recipient_cellphone_1').val() ) )
			{
				alert('유효한 전화번호가 아닙니다.');
				jQuery('#recipient_cellphone_1').focus()
			}
	});

	jQuery('#recipient_cellphone_2').blur(function()
	{
		if( jQuery('#recipient_cellphone_2').val().length > 1 )
			if( !jQuery.isNumeric( jQuery('#recipient_cellphone_2').val() ) )
			{
				alert('유효한 전화번호가 아닙니다.');
				jQuery('#recipient_cellphone_2').focus()
			}
	});

	jQuery('#recipient_cellphone_3').blur(function()
	{
		if( jQuery('#recipient_cellphone_3').val().length > 1 )
			if( !jQuery.isNumeric( jQuery('#recipient_cellphone_3').val() ) )
			{
				alert('유효한 전화번호가 아닙니다.');
				jQuery('#recipient_cellphone_3').focus()
			}
	});

	jQuery('#copyInfo').click(function()
	{
		if( jQuery('#copyInfo').is(':checked') )
		{
			if( jQuery('#purchaser_name').val().length > 0 && jQuery('#purchaser_cellphone_1').val().length > 0 && 
					jQuery('#purchaser_cellphone_2').val().length > 0 && jQuery('#purchaser_cellphone_3').val().length > 0 )
			{
				jQuery('#recipient_name').val( jQuery('#purchaser_name').val() );
				jQuery("#recipient_name").attr('readonly', 'readonly');
				jQuery('#recipient_cellphone_1').val( jQuery('#purchaser_cellphone_1').val() );
				jQuery('#recipient_cellphone_2').val( jQuery('#purchaser_cellphone_2').val() );
				jQuery('#recipient_cellphone_3').val( jQuery('#purchaser_cellphone_3').val() );
				jQuery("#recipient_cellphone_1").attr('readonly', 'readonly');
				jQuery("#recipient_cellphone_2").attr('readonly', 'readonly');
				jQuery("#recipient_cellphone_3").attr('readonly', 'readonly');
			}
			else
			{
				alert( '먼저 주문자 정보를 완성해 주세요.' );
				jQuery('#copyInfo').removeAttr('checked');
			}
		}
		else
		{
			jQuery('#recipient_name').val('');
			jQuery("#recipient_name").removeAttr('readonly');
			jQuery('#recipient_cellphone_1').val('');
			jQuery('#recipient_cellphone_2').val('');
			jQuery('#recipient_cellphone_3').val('');
			jQuery("#recipient_cellphone_1").removeAttr('readonly');
			jQuery("#recipient_cellphone_2").removeAttr('readonly');
			jQuery("#recipient_cellphone_3").removeAttr('readonly');
		}
	});	
});

function isValidEmail(emailText) {
    var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    return pattern.test(emailText);
};