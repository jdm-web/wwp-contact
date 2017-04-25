/**
 * contact.js. Created by jeremydesvaux the 16 mai 2014
 */
(function($,ns) {

    "use strict";

    /**
     * init module scripts, relative to its context (multiple context of the same module may exist in a page)
     * @param jQuery $context wraper div of the module
     */
    var contact = function($context) {
        this.$context = $context;
        this.init();
    };

    contact.prototype = {
        init: function(){
            this.registerFormSubmit();
        },
        registerFormSubmit: function(){
            var t = this;
            t.$context.find('form.contactForm').on('submit',function(e){
                e.preventDefault();
                var $form = $(this);
                var formData = new FormData(this);
                $form.addClass('loading').find('input[type="submit"]').attr('disabled', 'disabled');
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    async: false,
                    success: function (res) {
                        var notifComponent = ns.app.getComponent('notification');
                        if(res && res.code && res.code==200){
                            notifComponent.show('success',res.data.msg,t.$context);
                        } else {
                            var notifType = res && res.code && res.code==202 ? 'info' : 'error',
                                notifMsg = res && res.data && res.data.msg ? res.data.msg : 'Error';
                            notifComponent.show(notifType,notifMsg,t.$context);
                        }
                        $form.removeClass('loading').find('input[type="submit"]').removeAttr('disabled','disabled');
                    },
                    cache: false,
                    contentType: false,
                    processData: false
                });
            })
        }
    };

    ns.app.registerModule('contact',contact);

})(jQuery,window.wonderwp);
