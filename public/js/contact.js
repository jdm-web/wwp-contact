/**
 * contact.js. Created by jeremydesvaux the 16 mai 2014
 */

(function ($, ns) {

    "use strict";

    /**
     * init module scripts, relative to its context (multiple context of the same module may exist in a page)
     * @param context wraper div of the module
     */
    var contact = function (context) {
        this.$context = (context instanceof jQuery) ? context : $(context);
        this.init();
    };

    contact.prototype = {
        init: function () {
            var t = this,
                $forms = this.$context.find('form.contactForm');

            $forms.each(function (i, form) {
                t.registerFormSubmit($(form));
            });
            if ($forms.length > 1) {
                this.registerFormSwitcher($forms);
            }
        },
        registerFormSubmit: function ($form) {
            var t = this;
            $form.on('submit', function (e) {
                e.preventDefault();
                var $_form = $(this);

                //check form validity
                if ($_form.valid && !$_form.valid()) {
                    return false;
                }
                if ($_form.hasClass('loading')) {
                    return false;
                }

                var formData = new FormData(this);
                $_form.addClass('loading');
                $_form.find('[type="submit"]').prop("disabled", true);

                $.ajax($.extend({
                    url: $_form.attr('action'),
                    data: formData,
                }, t.getAjaxParams()))
                    .done(function (data, textStatus, jqXHR) {
                        t.submitCallBack(data, $_form);
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        t.submitCallBack({code: 500}, $_form);
                    })
                    .always(function () {
                        $_form.removeClass('loading');
                    });
            })
        },
        getAjaxParams: function () {
            return {
                type: 'POST',
                cache: false,
                contentType: false,
                processData: false
            };
        },
        submitCallBack: function (res, $form) {
            var t = this;
            if (res && res.code && res.code === 200) {
                t.onSubmitSuccess(res, $form);
                setTimeout(function () {
                    $form.find('[type="submit"]').prop('disabled', false);
                }, 5000);
            } else {
                t.onSubmitError(res, $form);
                $form.find('[type="submit"]').prop('disabled', false);
            }
        },
        onSubmitSuccess: function (res, $form) {
            var notifComponent = ns.app.getComponent('notification');
            notifComponent.show('success', res.data.msg, $form.parent());
            var topPos = $form.parent().find('.alert').offset().top;
            if (window.smoothScrollMargin) {
                topPos -= window.smoothScrollMargin;
            }
            $('html,body').animate({
                scrollTop: topPos
            }, 750);
            $form[0].reset();
        },
        onSubmitError: function (res, $form) {

            var notifComponent = ns.app.getComponent('notification');
            var notifType      = res && res.code && res.code === 202 ? 'info' : 'error',
                notifMsg       = res && res.data && res.data.msg ? res.data.msg : 'Error';
            notifComponent.show(notifType, notifMsg, $form.parent());

            var topPos = $form.parent().find('.alert').offset().top;
            if (window.smoothScrollMargin) {
                topPos -= window.smoothScrollMargin;
            }

            $('html,body').animate({
                scrollTop: topPos
            }, 750);
        },
        registerFormSwitcher: function() {
            var $context           = this.$context;
            var pickerLabel        = 'Votre demande concerne';
            var pickerDefaultLabel = 'Choisissez un sujet';

            if (window.wonderwp.i18n) {

                if (window.wonderwp.i18n.contactPickerLabel) {
                    pickerLabel = window.wonderwp.i18n.contactPickerLabel;
                }
                if (window.wonderwp.i18n.themeContactPickerDefaultLabel) {
                    pickerDefaultLabel = window.wonderwp.i18n.themeContactPickerDefaultLabel;
                }
            }

            var picker = '<select><option value="">' + pickerDefaultLabel + '</option>';

            $context.find('.contactForm').each(function (index) {
                var $contactDomFrag = $(this).parent();
                $contactDomFrag.hide();
                var title = $(this).data('title');
                picker += '<option value="' + index + '">' + title + '</option>';
            });
            picker += '</select>';
            var $picker = $(picker);

            var $pickerWrap = $('<div class="picker-wrap select-wrap"><label>' + pickerLabel + '</label><div class="select-style"></div></div>');
            $pickerWrap.find('.select-style').append($picker);
            $context.prepend($pickerWrap);

            var select = $context.find('.picker-wrap.select-wrap .select-style select');
            if($ && $.fn && $.fn.selectric) {
                select.selectric();
            }

            select.on('change', function () {
                var formIndex = $(this).val();
                $context.find('.contactForm').parent().hide();
                var $toShow = $context.find('.contactForm:eq(' + formIndex + ')');

                $toShow.parent().show();
                var $toShowSelect = $toShow.find('select');
                if($ && $.fn && $.fn.selectric) {
                    $($toShowSelect[0]).selectric();
                }
            });
        }
    };
    if (ns && ns.app) {
        ns.app.registerModule('contact', contact);
    }

})(jQuery, window.wonderwp);
