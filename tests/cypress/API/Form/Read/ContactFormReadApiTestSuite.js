/**
 * Conf to add to cypress.json:
 "wwp-contact": {
    "api": {
      "form": {
        "read": {
          "successUrl": "/wp-json/wwp-contact/v1/form/1",
          "errorUrl": "/wp-json/wwp-contact/v1/form/99999999999999"
        }
      }
    }
 },
 */
export class ContactFormReadApiTestSuite {

  constructor() {
    this.conf = Cypress.config('wwp-contact') || false;
  }

  getTestsDefinitions() {
    let definitions = [];
    if (this.conf && this.conf.api) {
      if (this.conf.api.form) {
        if (this.conf.api.form.read) {
          definitions.push({
            "title": "Form/Read/validationError : Form Not Found",
            "callable": "formReadWithIncorrectIdShouldReturn404"
          });
          definitions.push({
            "title": "Form/Read/processingSuccess : Form Found",
            "callable": "formReadWithCorrectRequestShouldReadForm"
          });
        }
      }
    }
    return definitions;
  }


  formReadWithIncorrectIdShouldReturn404(cy) {
    const host = Cypress.env('host') || Cypress.config('host'),
      conf = this.conf.api.form.read;

    cy.request({
      method: "GET",
      failOnStatusCode: false,
      url: host + conf.errorUrl
    }).then((response) => {
      this.check404Response(response);
    });
  }

  check404Response(response) {
    cy.log(response.body);
    expect(response.body).to.have.keys("code", "data", "error", "requestData", "message", "form");
    expect(response.body.code).to.eq(404);
    expect(response.body.message.key).to.eq("contact.form.read.validation.not_found");
    expect(response.body.form).to.be.null;
    expect(response.body.error).not.to.be.null;
    expect(response.body.error.message).to.eq("contact.form.read.validation.not_found");
  }

  formReadWithCorrectRequestShouldReadForm(cy) {
    const host = Cypress.env('host') || Cypress.config('host'),
      conf = this.conf.api.form.read;

    cy.request({
      method: "GET",
      url: host + conf.successUrl
    }).then((response) => {
      this.checkValidReadResponse(response);
    });
  }

  checkValidReadResponse(response) {
    //Check Request
    expect(response.body).to.have.keys('code', 'data', 'error', 'form', 'message', 'serializedForm');
    expect(response.body.code).to.eq(200);
    expect(response.body.error).to.be.null;
    expect(response.body.serializedForm).not.to.be.null;
    expect(response.body.message.key).to.eq("contact.form.read.processing.success");

    this.checkSerializedFormFormat(response.body.serializedForm)
  }

  checkSerializedFormFormat(serializedForm) {
    expect(serializedForm).to.have.keys('instance', 'item', 'viewOpts');
    expect(serializedForm.instance.fields).to.exist;
    expect(Object.keys(serializedForm.instance.fields)).to.have.length.of.at.least(1);

    for(let i in serializedForm.instance.fields){
      let thisField = serializedForm.instance.fields[i];
      expect(thisField).to.have.keys('displayRules', 'errors', 'name', 'rendered', 'tag', 'type', 'validationRules', 'value');
    }
  }

}
