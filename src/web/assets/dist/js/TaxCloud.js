(function($) {
    $('.taxcloud-sync-categories-btn').first().click(function(event) {
        Craft.postActionRequest('taxcloud/categories/sync', {}, function(response) {
            console.log(response);
            if (response.success) {
                Craft.cp.displayNotice('Categories Updated. Reloading page.');
                location.reload();
            } else {
                Craft.cp.displayError('Categories update failed.');
            }
        });
    });
})(jQuery);
