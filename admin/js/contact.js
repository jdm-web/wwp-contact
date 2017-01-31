/**
 * Created by jeremydesvaux on 13/09/2016.
 */

(function ($, ns) {
    var contactComponent       = function ($context, givenOptions) {
        var defaultOptions = {
            $wrap: $context.find('.contact-form')
        };
        this.options       = $.extend(defaultOptions, givenOptions);
        this.$wrap         = this.options.$wrap;
        if (this.$wrap.length) {
            this.init();
        }

    };
    contactComponent.prototype = {
        init:          function () {
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
                var newStepId    = '_new',
                    thisStepId   = (t.$wrap.find('.choice').length) + 1,
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
                axis:        'y',
                containment: 'parent',
                handle:      '.dragHandle',
                tolerance:   'pointer'
            });
        }

    };

    ns.adminComponents                  = ns.adminComponents || {};
    ns.adminComponents.contactComponent = contactComponent;

})(jQuery, window.wonderwp);
