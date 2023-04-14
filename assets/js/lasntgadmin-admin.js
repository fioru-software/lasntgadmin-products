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


   //
   if ('product' !== lasntgadmin_products_admin_localize.post_type) {
      return;
   }
   const sel1 = $('[name="groups-read[]"]').selectize();
   sel1[0].selectize.removeOption(1);
   sel1[0].selectize.refreshOptions(false);
   const lastngtadmin_status = $('#lasntgadmin_status');
   if (lastngtadmin_status.length) {
      if (!lastngtadmin_status.val()) {
         lastngtadmin_status.val($('select[name="post_status"]').val())
      }
      $('.save-post-status').on('click', function () {
         lastngtadmin_status.val($('select[name="post_status"]').val())
      })


      if (lasntgadmin_products_admin_localize.lasntg_status && lasntgadmin_products_admin_localize.post_type) {
         const current_status = lasntgadmin_products_admin_localize.lasntg_status;
         const post_status = $('select[name="post_status"]');

         const statuses = lasntgadmin_products_admin_localize.statuses;
         Object.keys(statuses).forEach(status => {
            post_status.append(`<option value="${status}">${statuses[status]}</option>`)
         });
         post_status.val(current_status);
         $('#post-status-display').html(statuses[current_status])
      }

   }

   
});

