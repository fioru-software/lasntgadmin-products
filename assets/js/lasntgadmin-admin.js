jQuery(document).ready(function ($) {
   $('.set-all-zero').click(function(e){
      e.preventDefault();
      
      const parent_id = $(this).data('id');
      $('input[data-parent=' + parent_id +']').each(function() {
         $(this).val(0)
      })
      return false;
   })
   $('.set-all-unlimited').click(function(e){
      e.preventDefault();
      
      const parent_id = $(this).data('id');
      $('input[data-parent=' + parent_id +']').each(function() {
         $(this).val('')
      })
      return false;
   })

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
   const post_status_el = $('#post_status');
   if( post_status_el.length ){
      const pending = post_status_el.find("option[value='pending']");
      if( pending.length ){
         pending.remove()
      }
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

