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

    constructor(){
        this.conf = Cypress.config('wwp-contact');
    }

    getTestsDefinitions() {
        return [
            {"title": "Checks that form is working", "callable": "testForm"},
            {"title": "Checks that admin is working", "callable": "testBackOffice"}
        ];
    }

    testForm(cy) {
        let host    = Cypress.env('host') || Cypress.config('host'),
            conf    = this.conf.form,
            formUrl = conf.url;

        cy.server();
        cy.route('POST', '/contactFormSubmit').as('contactAjaxSubmit');

        cy.visit(host + formUrl);
        cy.get("#colophon").should('be.visible');
        cy.get(conf.formSelector).then(($form) => {

            let $formGroups      = $form.find('.form-group'),
                formGroupsLength = $formGroups.length;

            expect(formGroupsLength).to.be.greaterThan(0);

            console.log(formGroupsLength);

            let data = {
                "text" : "Test input",
                "textarea" : "Test Textarea",
                "email" : "test@cypress.bot"
            };

            $formGroups.each((i, elt) => {
                let $inpt     = Cypress.$(elt).find('input.text,textarea'),
                    inputType = $inpt.attr('type') ? $inpt.attr('type') : 'textarea';

                if ($inpt.length > 0) {
                    cy.wrap($inpt).type(data[inputType], {force: true});
                    /*setTimeout(() => {
                        if (i === (formGroupsLength - 1)) {
                            $form.submit().as('submit');

                        }
                    }, i * 500);*/
                }

                let $selects = Cypress.$(elt).find('select');
                if ($selects.length > 0) {
                    $selects.each((i, select) => {
                        let $options = Cypress.$(select).find('option'),
                            random   = ~~(Math.random() * $options.length);
                        if (random === 0) {
                            random = 1;
                        }

                        let val = $options.eq(random).text();

                        cy.wrap(Cypress.$(select)).select(val,{force: true});
                    });
                }

                let $chekboxes = Cypress.$(elt).find('input.checkbox');
                if ($chekboxes.length > 0) {
                    $chekboxes.each((i, cb) => {
                        cy.wrap(Cypress.$(cb)).check({force: true});
                    });
                }
            });

        });

        cy.wait(1);
        cy.get(conf.formSelector).submit();
        cy.wait('@contactAjaxSubmit');

        // we should have visible errors now
        cy.get('.alert')
            .should('be.visible')
            .and('have.class', 'alert-success')
    }

    testBackOffice(cy) {
        cy.wpLogin();
        let host       = Cypress.env('host') || Cypress.config('host'),
            conf       = this.conf.admin,
            listingUrl = conf.list.url;
        cy.visit(host + listingUrl);
        cy.get("#wpfooter").should('be.visible');
        cy.get('.bottom .noewpaddrecordbtn').click();
        cy.get("#data").should('be.visible').children().should('have.length.above',0);
        cy.get("#wpfooter").should('be.visible');
        cy.get('.nav-tab:nth-child(2)').click();
        cy.get("#wpfooter").should('be.visible');
        cy.get('.bottom .noewpaddrecordbtn').click();
        cy.get("#wpfooter").should('be.visible');
    }

}
