jQuery( document ).ready(function() {
  var url = ShopInfo.url + '?action=shopinfo_shopname';
  jQuery( '#shopinfo_shopname' ).autocomplete({
    source: url,
    delay: 500,
    minLength: 3,
    select: function( event, ui ) { 
      jQuery( '#shopinfo_shopid' ).val( ui.item.shopid);
      jQuery( '#shopinfo_textid' ).text(ui.item.shopid);
    }
  });	

});
