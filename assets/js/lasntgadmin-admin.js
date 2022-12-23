jQuery(document).ready(function($){
   const wrapper = $('.product-data-wrapper');

   if(wrapper.length){
        $('#_virtual').prop('checked', true);
        wrapper.hide();
   }
});