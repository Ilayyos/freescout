(function ($) {
    'use strict';

    function escapeHtml(text) {
        return $('<div/>').text(text).html();
    }

    function formatTag(tag) {
        if (!tag.id) {
            return escapeHtml(tag.text || '');
        }

        var color = tag.color;
        if (!color && tag.element) {
            color = $(tag.element).data('color');
        }
        color = color || '#58666e';

        var label = tag.text || tag.name || '';
        return '<span class="tag-pill" style="background-color: ' + color + '">' + escapeHtml(label) + '</span>';
    }

    function applyTagColors($select, tags) {
        if (!tags || !tags.length) {
            return;
        }
        var colorMap = {};
        $.each(tags, function (_, tag) {
            if (tag && tag.name) {
                colorMap[tag.name.toLowerCase()] = tag.color || null;
            }
        });
        $select.find('option').each(function () {
            var value = ($(this).val() || '').toLowerCase();
            if (colorMap.hasOwnProperty(value)) {
                var color = colorMap[value];
                if (color) {
                    $(this).attr('data-color', color).data('color', color);
                } else {
                    $(this).removeAttr('data-color').removeData('color');
                }
            }
        });
        $select.trigger('change.select2');
    }

    function syncConversationTags($select) {
        var syncUrl = $select.data('sync-url');
        if (!syncUrl) {
            return;
        }

        var tags = $select.val() || [];
        var $container = $select.closest('.conv-tags');
        $container.addClass('is-loading');

        $.ajax({
            url: syncUrl,
            method: 'PUT',
            data: { tags: tags }
        }).done(function (response) {
            if (response && response.tags) {
                applyTagColors($select, response.tags);
            }
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to save tags.';
            window.alert(message);
        }).always(function () {
            $container.removeClass('is-loading');
        });
    }

    function initialiseConversationTags(context) {
        var $root = context ? $(context) : $(document);
        $root.find('.conv-tags__select').each(function () {
            var $select = $(this);
            if ($select.data('tags-initialized')) {
                return;
            }
            $select.data('tags-initialized', true);

            var allowCreate = !!$select.data('allow-create');
            var suggestUrl = $select.data('suggest-url');
            var conversationId = $select.data('conversation-id');

            $select.select2({
                width: 'resolve',
                placeholder: $select.data('placeholder') || '',
                tags: allowCreate,
                tokenSeparators: [','],
                ajax: suggestUrl ? {
                    url: suggestUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            conversation_id: conversationId
                        };
                    },
                    processResults: function (data) {
                        var results = [];
                        if (data && data.items) {
                            results = $.map(data.items, function (item) {
                                return {
                                    id: item.name,
                                    text: item.name,
                                    color: item.color || null
                                };
                            });
                        }
                        return { results: results };
                    }
                } : null,
                createTag: allowCreate ? function (params) {
                    var term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return {
                        id: term,
                        text: term,
                        newTag: true
                    };
                } : undefined,
                templateSelection: formatTag,
                templateResult: formatTag,
                escapeMarkup: function (markup) { return markup; }
            }).on('change', function () {
                var timer = $select.data('sync-timer');
                if (timer) {
                    clearTimeout(timer);
                }
                $select.data('sync-timer', setTimeout(function () {
                    syncConversationTags($select);
                }, 300));
            });
        });
    }

    $(document).ready(function () {
        initialiseConversationTags();
    });

    window.TagsModule = window.TagsModule || {};
    window.TagsModule.initialiseConversationTags = initialiseConversationTags;
})(window.jQuery);
