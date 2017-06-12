/**
 * contact.js. Created by jeremydesvaux the 16 mai 2014
 */
(function ($, ns) {

    "use strict";

    /**
     * init module scripts, relative to its context (multiple context of the same module may exist in a page)
     * @param $context wraper div of the module
     */
    var contact = function ($context) {
        this.$context = $context;
        this.init();
    };

    contact.prototype = {
        init: function () {
            this.registerFormSubmit();
        },
        registerFormSubmit: function () {
            var t = this;
            t.$context.find('form.contactForm').on('submit', function (e) {
                e.preventDefault();
                var $form    = $(this);

                //check form validity
                if($form.valid && !$form.valid()){
                    return false;
                }

                var formData = new FormData(this);
                $form.addClass('loading');
                $form.find('input[type="submit"]').attr('disabled', 'disabled');

                $.ajax($.extend({
                    url: $form.attr('action'),
                    data: formData,
                },t.getAjaxParams()))
                .done(function(data, textStatus, jqXHR) {
                    t.submitCallBack(data, $form);
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    t.submitCallBack({ code: 500 }, $form);
                })
                .always(function() {
                    $form.removeClass('loading');
                    $form.find('input[type="submit"]').removeAttr('disabled', 'disabled');
                });
            })
        },
        getAjaxParams : function(){
            return {
                type: 'POST',
                cache: false,
                contentType: false,
                processData: false
            };
        },
        submitCallBack : function(res,$form){
            var t = this;
            if (res && res.code && res.code == 200) {
                t.onSubmitSuccess(res,$form);
            } else {
                t.onSubmitError(res,$form);
            }
        },
        onSubmitSuccess: function(res,$form){
            var notifComponent = ns.app.getComponent('notification');
            notifComponent.show('success', res.data.msg, $form.parent());
            $('html,body').animate({
                scrollTop: $form.parent().find('.alert').offset().top
            }, 750);
        },
        onSubmitError: function(res,$form){
            var notifComponent = ns.app.getComponent('notification');
            var notifType = res && res.code && res.code == 202 ? 'info' : 'error',
                notifMsg  = res && res.data && res.data.msg ? res.data.msg : 'Error';
            notifComponent.show(notifType, notifMsg, $form.parent());
            $('html,body').animate({
                scrollTop: $form.parent().find('.alert').offset().top
            }, 750);
        }
    };

    ns.app.registerModule('contact', contact);

})(jQuery, window.wonderwp);
