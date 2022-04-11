jQuery(document).ready(function (){

	var params = new Array();
	var respons = ['item_count'];
	exec_xml('svcart', 'getSvcartFavoriteItems', params, function(ret_obj) {
		jQuery("#count_favorites_items").html(ret_obj['item_count']);
	},respons);

	exec_xml('svcart', 'getSvcartCartItems', params, function(ret_obj) {
		jQuery("#count_cart_items").html(ret_obj['item_count']);
	},respons);

});
