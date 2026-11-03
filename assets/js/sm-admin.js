jQuery(function ($) {
    var navigation = $('.sm-workspace-nav');
    var sectionLinks = navigation.find('a[href^="#"]');
    function markSection(hash) {
        var current = sectionLinks.filter(function () { return $(this).attr('href') === hash; }).first();
        if (!current.length) { current = sectionLinks.first(); }
        sectionLinks.removeAttr('aria-current');
        current.attr('aria-current', 'location');
    }
    markSection(window.location.hash);
    $(window).on('hashchange', function () { markSection(window.location.hash); });
    $('#sm-generate-script').on('click', function () {
        var button = $(this);
        var label = button.text();
        button.text(sm_gs.generating).prop('disabled', true);
        $.post(sm_gs.ajax_url, { action: 'sm_generate_google_script', nonce: sm_gs.nonce })
            .done(function (response) {
                if (!response.success || !response.data.script) {
                    window.alert(sm_gs.error);
                    return;
                }
                $('#sm-generated-script').val(response.data.script);
                $('#sm-script-output').prop('hidden', false);
            })
            .fail(function () { window.alert(sm_gs.error); })
            .always(function () { button.text(label).prop('disabled', false); });
    });
    $('#sm-copy-script').on('click', async function () {
        var textarea = $('#sm-generated-script')[0];
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(textarea.value);
            } else {
                textarea.select();
                if (!document.execCommand('copy')) { return; }
            }
            $('#sm-copy-status').prop('hidden', false);
            window.setTimeout(function () { $('#sm-copy-status').prop('hidden', true); }, 2000);
        } catch (error) {
            textarea.focus();
            textarea.select();
        }
    });
    $('#sm-field-search').on('input', function () {
        var query = $(this).val().toLowerCase();
        var matches = 0;
        $('.sm-field-item').each(function () {
            var visible = $(this).text().toLowerCase().indexOf(query) !== -1;
            $(this).toggle(visible);
            if (visible) { matches++; }
        });
        $('#sm-field-empty').prop('hidden', matches > 0);
    });
    $("input[name='sm_gs_selected_fields[]'], input[name='sm_gs_order_statuses[]']").on('change', function () {
        $('.sm-selected-count').text($(".sm-field-item input[type='checkbox']:checked").length);
        $('.sm-trigger-count').text($("input[type='checkbox'][name='sm_gs_order_statuses[]']:checked").length);
    });
});
