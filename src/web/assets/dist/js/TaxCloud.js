(function($) {
    $('#taxcloud-sync-categories-btn').click(function(event) {
        $('#taxcloud-sync-categories-spinner').toggleClass('hidden');
        Craft.postActionRequest('taxcloud/categories/sync', {}, function(response) {
            console.log(response);
            if (response.success) {
                Craft.cp.displayNotice('Categories Updated. Reloading page.');
                location.reload();
            } else {
                Craft.cp.displayError('Categories update failed.');
            }
            $('#taxcloud-sync-categories-spinner').toggleClass('hidden');
        });
    });
})(jQuery);
