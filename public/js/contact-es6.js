/**
 * contact.js. Created by jeremydesvaux the 16 mai 2014
 */

export class ContactPluginComponent {
  constructor(context) {
    this.$context = (context instanceof jQuery) ? context : $(context);
    this.init();
  }

  init() {
    let $forms = this.$context.find('form.contactForm');
    $forms.each((i, form) => {
      this.registerFormSubmit($(form));
    });
    if ($forms.length > 1) {
      this.registerFormSwitcher($forms);
    }
  }

  registerFormSubmit($form) {
    let t = this;
    $form.on('submit', (e) => {
      e.preventDefault();
      let _form = e.currentTarget,
        $_form = $(_form);

      t.clearNotifications($_form.parent());
      t.clearErrors($_form);

      //check form validity
      if ($_form.valid && !$_form.valid()) {
        return false;
      }
      if ($_form.hasClass('loading')) {
        return false;
      }

      $_form.addClass('loading');
      $_form.find('[type="submit"]').prop("disabled", true);

      let formData = new FormData(_form);
      t.submitForm($_form, formData);
    });
  }

  getAjaxParams() {
    return {
      type: 'POST',
      cache: false,
      contentType: false,
      processData: false
    };
  }

  submitCallBack(res, form) {
    let $form = (form instanceof jQuery) ? form : $(form);
    let t = this;
    if (res && res.code && res.code === 200) {
      t.onSubmitSuccess(res, $form);
      setTimeout(function () {
        $form.find('[type="submit"]').prop('disabled', false);
      }, 5000);
    } else {
      t.onSubmitError(res, $form);
      $form.find('[type="submit"]').prop('disabled', false);
    }
  }

  onSubmitSuccess(res, form) {
    let $form = (form instanceof jQuery) ? form : $(form);
    this.resetForm($form);

    $form.trigger({
      type: 'contact.submit.success',
      form: $form,
      res: res
    });

    this.notify('success', res.data.msg, $form.parent());
  }

  resetForm($form){
    $form[0].reset();
  }

  notify(type, msg, $dest) {
    const EventManager = window.EventManager || $(document);
    EventManager.trigger('notification', {
      type: type,
      msg: msg,
      dest: $dest,
      focus: true
    });
  }

  clearNotifications($dest) {
    let $alerts = $dest.find('.alert');
    if ($alerts.length) {
      $alerts.fadeOut(400, function () {
        $(this).remove();
      });
    }
  }

  onSubmitError(res, form) {
    let $form = (form instanceof jQuery) ? form : $(form);

    $form.trigger({
      type: 'contact.submit.error',
      form: $form,
      res: res
    });

    let notifType = res && res.code && res.code === 202 ? 'info' : 'error',
      notifMsg = res && res.data && res.data.msg ? res.data.msg : 'Error';

    this.notify(notifType, notifMsg, $form.parent());

    let errors = res && res.data && res.data.errors ? res.data.errors : {};
    this.displayErrors($form, errors);
  }

  displayErrors($form, errors) {
    for (let i in errors) {
      let $input = $form.find('[name=' + i + ']');

      if ($input.length) {
        $input.addClass('error');
        $input.parent().find('label').addClass('error');
        let errorMsg = '<label class="error error-label">' + errors[i][0] + '</label>';
        $input.after(errorMsg);
      }
    }
  }

  clearErrors($form) {
    $form.find('.error-label').remove();
    $form.find('.error').removeClass('error');
  }

  submitForm($form, formData) {

    let t = this;
    $.ajax($.extend({
      url: $form.attr('action'),
      data: formData,
    }, t.getAjaxParams()))
      .done(function (data, textStatus, jqXHR) {
        t.submitCallBack(data, $form);
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        t.submitCallBack({code: 500}, $form);
      })
      .always(function () {
        $form.removeClass('loading');
      });

  }

  registerFormSwitcher() {
    let $context = this.$context;
    let pickerLabel = 'Votre demande concerne';
    let pickerDefaultLabel = 'Choisissez un sujet';

    if (window.wonderwp.i18n) {

      if (window.wonderwp.i18n.contactPickerLabel) {
        pickerLabel = window.wonderwp.i18n.contactPickerLabel;
      }
      if (window.wonderwp.i18n.themeContactPickerDefaultLabel) {
        pickerDefaultLabel = window.wonderwp.i18n.themeContactPickerDefaultLabel;
      }
    }

    let picker = '<select><option value="">' + pickerDefaultLabel + '</option>';

    $context.find('.contactForm').each(function (index) {
      let $contactDomFrag = $(this).parent();
      $contactDomFrag.hide();
      let title = $(this).data('title');
      picker += '<option value="' + index + '">' + title + '</option>';
    });
    picker += '</select>';
    let $picker = $(picker);

    let $pickerWrap = $('<div class="picker-wrap select-wrap"><label>' + pickerLabel + '</label><div class="select-style"></div></div>');
    $pickerWrap.find('.select-style').append($picker);
    $context.prepend($pickerWrap);

    let select = $context.find('.picker-wrap.select-wrap .select-style select');
    //select.selectric();

    select.on('change', function () {
      let formIndex = $(this).val();
      $context.find('.contactForm').parent().hide();
      let $toShow = $context.find('.contactForm:eq(' + formIndex + ')');

      $toShow.parent().show();
      let $toShowSelect = $toShow.find('select');
      if ($ && $.fn && $.fn.selectric) {
        $($toShowSelect[0]).selectric();
      }
    });
  }
}

window.pew.addRegistryEntry({key: 'wdf-plugin-contact', classDef: ContactPluginComponent, domSelector: '.module-contact'});
