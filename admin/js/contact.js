'use strict';

/**
 * Created by jeremydesvaux on 13/09/2016.
 */

(function ($, ns) {
    var contactComponent       = function (context, givenOptions) {

        //let test = require("/node_modules/jquery-ui-sortable-npm/jquery-ui-sortable.min");

        var defaultOptions = {
            $wrap: (context instanceof jQuery) ? context.find('.contact-form') : $(context)
        };
        this.options       = $.extend(defaultOptions, givenOptions);
        this.$wrap         = this.options.$wrap;
        if (this.$wrap.length) {
            this.init();
        }

    };
    contactComponent.prototype = {
        init: function () {
            if (this.$wrap.length) {
                this.bindUiActions();
            }
        },
        bindUiActions: function () {
            var t = this;

            /**
             * Add subject Line
             */
            this.$wrap.on('click', '#add-choice', function (e) {
                e.preventDefault();

                var thisStepId = 0;
                t.$wrap.find('.choice').each(function (i) {
                    var choiceId = parseInt($(this).attr('id').split('_')[1]);
                    if (choiceId > thisStepId) {
                        thisStepId = choiceId;
                    }
                });
                thisStepId++;

                var newStepId    = '_new',
                    $clone       = t.$wrap.find('#choice_' + newStepId).clone(),
                    cloneMarkup  = $clone[0].outerHTML.replace(/_new/gi, thisStepId),
                    $cloneMarkup = $(cloneMarkup);

                $cloneMarkup.removeClass('new-choice hidden').addClass('choice');
                $cloneMarkup.insertBefore($(this).parent());

                return false;
            });

            /**
             * Remove subject line
             */
            this.$wrap.on('click', '.remove-choice', function (e) {
                e.preventDefault();
                $(this).closest('.choice').remove();
                return false;
            });

            /**
             * Enable sorting
             */
            this.$wrap.find("#data, #options-choices").sortable({
                axis: 'y',
                containment: 'parent',
                handle: '.dragHandle',
                tolerance: 'pointer'
            });

        }

    };

    /**
     * Export
     */
    $('.fonctionnalites_page_wwp-contact .export-btn').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.addClass('loading').addClass('disabled');
        var trgt = $(this).attr('href');
        $.get(trgt, {}, function (html) {
            $btn.removeClass('loading').removeClass('disabled');
            var $res   = $(html).find('.export-result');
            var $notif = $('<div class="notice is-dismissible">' +
                '<p><strong>' + $res.html() + '</strong></p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>');
            if ($res.length && $res.hasClass('success')) {
                $notif.addClass('notice-success');
            } else {
                $notif.addClass('notice-error');
            }
            $btn.parent().append($notif);

            $notif.find('a,button').on('click', function () {
                $notif.fadeOut();
            })
        });
    });

    document.addEventListener("DOMContentLoaded", function(event) {

        if (ns && ns.app) {
            ns.adminComponents                  = ns.adminComponents || {};
            ns.adminComponents.contactComponent = contactComponent;
        } else {
            window.pew.addRegistryEntry('wdf-admin-contact', {selector: '.contact-form', classDef: contactComponent});
        }

    });

})(jQuery, window.wonderwp);
