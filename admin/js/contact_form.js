'use strict';

/**
 * Created by jeremydesvaux on 13/09/2016.
 */

(function ($, ns) {

  var contactFormComponent       = function (context, givenOptions) {

    var defaultOptions = {
      $wrap: (context instanceof jQuery) ? context : $(context)
    };
    this.options       = $.extend(defaultOptions, givenOptions);
    this.$wrap         = this.options.$wrap;
    if (this.$wrap.length) {
      this.init();
    }
  };

  contactFormComponent.prototype = {
    init: function () {
        this.registerRepeatables();
        this.initSortable();
    },
    registerRepeatables: function () {
      var t = this;
      var max_id = 0;
      var last_group = null;
      this.$wrap.find('.group-wrap').each(function () {
        var group_id = parseInt($(this).attr("id").replace("group_wrap_", ""));
        if(group_id > max_id){
          max_id = group_id;
          last_group = $(this);
        }
      });

      this.$wrap.find('.add-repeatable').on('click', function (e) {
        e.preventDefault();
        var fieldname = $(this).data("repeatable");
        var clone = t.$wrap.find('#group_wrap_'+fieldname).clone();
        clone.removeClass("hidden");

        var contentClone = clone[0].outerHTML;
        contentClone = contentClone.replace(new RegExp(fieldname, "g"), (max_id + 1));
        var $cloneMarkup = $(contentClone);


        $(this).before($cloneMarkup);
        t.initSortable();
      });


    },

    initSortable : function(){
      var _this = this;
      this.$wrap.find(".form-group-wrap").sortable({
        axis: 'y',
        tolerance: 'pointer',
        handle: '.dragHandle',
        connectWith: '.form-group-wrap',
        receive: function(event, ui){
          var id_group_dest = $(event.target).attr("id");
          var elem = ui.item.html();
          var new_data = _this.replaceId(elem, id_group_dest);


          ui.item.html(new_data);
        }
      });
    },

    replaceId: function(elem, replace){

      var new_data = elem.replace(/_g[0-9]{1,}_/g, '_'+replace+'_');
      var new_data = new_data.replace(/data_g[0-9]{1,}/g, 'data_'+replace);
      var new_data = new_data.replace(/data_Others/g, 'data_'+replace);

      return new_data;
    }
  };

  if (window.pew) {
    window.pew.addRegistryEntry({key: 'wdf-admin-contact-form', domSelector: '.contact-form-form', classDef: contactFormComponent});
  } else {
    ns.adminComponents                  = ns.adminComponents || {};
    ns.adminComponents.contactFormComponent = contactFormComponent;
  }

})(jQuery, window.wonderwp);
