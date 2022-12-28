jQuery(document).ready(function ($) {
   const wrapper = $('.product-data-wrapper');

   if (wrapper.length) {
      $('#_virtual').prop('checked', true);
      wrapper.hide();
   }
   // turn product categories checklist to radio buttons.
   $('#product_catchecklist li input[type="checkbox"]').each(function () {
      $(this).prop('type', 'radio')
      const next_class = $(this).parents('label').next().attr('class');
      if (next_class === 'children') {
         $(this).remove()
      }
   });

   const lastngtadmin_status = $('#lasntgadmin_status')
   if(lastngtadmin_status.length){
      $('.save-post-status').on('click', function(){
         lastngtadmin_status.val($('select[name="post_status"]').val())
      })
   }
});