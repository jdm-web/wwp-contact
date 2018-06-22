/**
 * contact.js. Created by jeremydesvaux the 16 mai 2014
 */

export class ContactPluginComponent {
    constructor(context) {
        this.$context = (context instanceof jQuery) ? context : $(context);
        this.init();
    }
    init() {
        this.registerFormSubmit();
    }
    registerFormSubmit() {
        var t = this;
        t.$context.find('form.contactForm').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            //check form validity
            if ($form.valid && !$form.valid()) {
                return false;
            }
            if ($form.hasClass('loading')) {
                return false;
            }

            $form.addClass('loading');
            $form.find('[type="submit"]').prop("disabled", true);
            var formData = new FormData(this);
            t.submitForm($form, formData);
        });
    }
    getAjaxParams(){
        return {
            type: 'POST',
            cache: false,
            contentType: false,
            processData: false
        };
    }
    submitCallBack(res,form){
        var $form = (form instanceof jQuery) ? form : $(form);
        var t = this;
        if (res && res.code && res.code === 200) {
            t.onSubmitSuccess(res,$form);
            setTimeout(function(){
                $form.find('[type="submit"]').prop('disabled', false);
            },5000);
        } else {
            t.onSubmitError(res,$form);
            $form.find('[type="submit"]').prop('disabled', false);
        }
    }
    onSubmitSuccess(res,form){
        var $form = (form instanceof jQuery) ? form : $(form);
        $form[0].reset();

        this.notify('success', res.data.msg, $form.parent());
    }
    notify(type, msg, $dest){
        var notifComponent = new (window.pew.getRegistryEntry('wdf-notification')).classDef();

        if(notifComponent) {

            notifComponent.show(type, msg, $dest);
            $('html,body').animate({
                scrollTop: $dest.find('.alert').offset().top
            }, 750);

        }
    }
    onSubmitError(res,form){
        var $form = (form instanceof jQuery) ? form : $(form);

        var notifType = res && res.code && res.code === 202 ? 'info' : 'error',
            notifMsg  = res && res.data && res.data.msg ? res.data.msg : 'Error';

        this.notify(notifType, notifMsg, $form.parent());
    }
    submitForm(form, formData) {
        var t = this;
            $.ajax($.extend({
                url: form.attr('action'),
                data: formData,
            }, t.getAjaxParams()))
                .done(function(data, textStatus, jqXHR) {
                    t.submitCallBack(data, form);
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    t.submitCallBack({ code: 500 }, form);
                })
                .always(function() {
                    form[0].classList.remove('loading');
                });

    }
}
window.pew.addRegistryEntry({key: 'wdf-plugin-contact', classDef: ContactPluginComponent, domSelector: '.module-contact'});
