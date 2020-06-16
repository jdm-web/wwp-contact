/**
 * Conf to add to cypress.json:
 "wwp-contact" : {
    "form": {
        "url" : "/fr/plugins/contact/",
        "formSelector" : ".contactForm"
    },
    "admin": {
        "list": {
            "url" : "/wp/wp-admin/admin.php?page=wwp-contact"
        }
    }
 }
 */
export class ContactTestSuite {

  constructor() {
    this.conf = Cypress.config('wwp-contact') || false;
  }

  getTestsDefinitions() {
    let definitions = [];
    if (this.conf) {
      definitions.push({"title": "Checks that contact is working", "callable": "testForm"});
      if (this.conf.admin && Cypress.config('wp-admin')) {
        definitions.push({"title": "Checks that admin is working", "callable": "testBackOffice"});
      }
    }
    return definitions;
  }

  testForm(cy) {
    let host = Cypress.env('host') || Cypress.config('host'),
      conf = this.conf.form,
      formUrl = conf.url;

    cy.server();
    cy.route('POST', '/contactFormSubmit').as('contactAjaxSubmit');

    cy.visit(host + formUrl);
    cy.checkNoFatal();

    //Fill Forms
    cy.get(conf.formSelector).then(($forms) => {
      $forms.each((i, formElt) => {
        let $form = Cypress.$(formElt);
        this.fillForm($form, cy);
      });
    });

    //Submit Forms
    cy.get(conf.formSelector).then(($forms) => {
      $forms.each((i, formElt) => {
        let $form = cy.wrap(formElt);
        $form.submit();
        cy.wait('@contactAjaxSubmit');
      });
    });

    //Control errors
    cy.get(conf.formSelector).then(($forms) => {
      $forms.each((i, formElt) => {
        let $formAlert = Cypress.$(formElt).parent().find('.alert'),
          formAlert = cy.wrap($formAlert)

        // we should have visible errors now
        formAlert
          .should('exist')
          .and('have.class', 'alert-success');
      });
    });
  }

  fillForm($form, cy) {
    let $formGroups = $form.find('.form-group'),
      formGroupsLength = $formGroups.length;

    expect(formGroupsLength).to.be.greaterThan(0);

    let data = {
      "text": "Test input",
      "textarea": "Test Textarea",
      "email": "test@cypress.bot"
    };

    $formGroups.each((i, elt) => {
      let $inpt = Cypress.$(elt).find('input.text,textarea'),
        inputType = $inpt.attr('type') ? $inpt.attr('type') : 'textarea';

      if ($inpt.length > 0) {
        cy.wrap($inpt).type(data[inputType], {force: true});
      }

      let $selects = Cypress.$(elt).find('select');
      if ($selects.length > 0) {
        $selects.each((i, select) => {
          let $options = Cypress.$(select).find('option'),
            random = ~~(Math.random() * $options.length);
          if (random === 0) {
            random = 1;
          }

          let val = $options.eq(random).text();

          cy.wrap(Cypress.$(select)).select(val, {force: true});
        });
      }

      let $chekboxes = Cypress.$(elt).find('input.checkbox');
      if ($chekboxes.length > 0) {
        $chekboxes.each((i, cb) => {
          cy.wrap(Cypress.$(cb)).check({force: true});
        });
      }
    });
  }

  testBackOffice(cy) {
    cy.wpLogin();
    let host = Cypress.env('host') || Cypress.config('host'),
      conf = this.conf.admin,
      listingUrl = conf.list.url;
    cy.visit(host + listingUrl);
    cy.checkNoFatalInAdmin();
    cy.get('.bottom .noewpaddrecordbtn').click();
    cy.get("#data").should('be.visible').children().should('have.length.above', 0);
    cy.checkNoFatalInAdmin();
    cy.get('.nav-tab:nth-child(2)').click();
    cy.checkNoFatalInAdmin();
    cy.get('.bottom .noewpaddrecordbtn').click();
    cy.checkNoFatalInAdmin();
  }

}
