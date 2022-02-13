/**
 * Conf to add to cypress.json:
 "wwp-contact": {
    "api": {
      "form": {
        "post": {
          "successUrl": "/wp-json/wwp-contact/v1/form/1",
          "errorUrl": "/wp-json/wwp-contact/v1/form/99999999999999"
        }
      }
    }
 },
 */
export class ContactFormPostApiTestSuite {

  constructor() {
    this.conf = Cypress.config('wwp-contact') || false;
  }

  getTestsDefinitions() {
    let definitions = [];
    if (this.conf && this.conf.api) {
      if (this.conf.api.form) {
        if (this.conf.api.form.post) {
          definitions.push({
            "title": "Form/Post/validationError : Form Not Found",
            "callable": "formPostWithIncorrectIdShouldReturn404"
          });
          definitions.push({
            "title": "Form/Post/validationError : Incorrect body",
            "callable": "formPostWithIncorrectBodyShouldReturnValidationError"
          });
          definitions.push({
            "title": "Form/Post/fakeProcessingSuccess : Bot Detected",
            "callable": "formPostWithBotRequestShouldDetectBot"
          });
          definitions.push({
            "title": "Form/Post/processingSuccess : Form Posted",
            "callable": "formPostWithCorrectRequestShouldPostForm"
          });
        }
      }
    }
    return definitions;
  }


  formPostWithIncorrectIdShouldReturn404(cy) {
    const host = Cypress.env('host') || Cypress.config('host'),
      conf = this.conf.api.form.post;

    cy.request({
      method: "POST",
      failOnStatusCode: false,
      url: host + conf.errorUrl
    }).then((response) => {
      this.check404Response(response);
    });
  }

  check404Response(response) {
    cy.log(response.body);
    expect(response.body).to.have.keys('code', 'data', 'error', 'form', 'isBot', 'message', 'requestData', 'validatedFiles');
    expect(response.body.code).to.eq(404);
    expect(response.body.message.key).to.eq("contact.form.post.validation.not_found");
    expect(response.body.form).to.be.null;
    expect(response.body.error).not.to.be.null;
    expect(response.body.error.message).to.eq("contact.form.post.validation.not_found");
  }

  formPostWithIncorrectBodyShouldReturnValidationError(cy) {
    const host = Cypress.env('host') || Cypress.config('host'),
      conf = this.conf.api.form.post;

    cy.request({
      method: "POST",
      failOnStatusCode: false,
      url: host + conf.successUrl
    }).then((response) => {
      this.checkIncorrectResponse(response);
    });
  }

  checkIncorrectResponse(response) {
    expect(response.body.code).to.eq(400);
    expect(response.body.message.key).to.eq("contact.form.post.validation.error");
    expect(response.body.form).to.be.null;
    expect(response.body.error).not.to.be.null;
    expect(response.body.error.message).to.eq("contact.form.post.validation.error");
    expect(Object.keys(response.body.error.details)).to.have.length.of.at.least(1);
  }

  formPostWithBotRequestShouldDetectBot(cy) {
    const host = Cypress.env('host') || Cypress.config('host'),
      conf = this.conf.api.form.post;

    cy.request({
      method: "GET",
      url: host + conf.successUrl
    }).then((readResponse) => {
      let requestBody = this.buildRequestFromSerializedForm(readResponse.body.serializedForm, true);
      console.log(requestBody);
      cy.request({
        method: "POST",
        body: requestBody,
        url: host + conf.successUrl
      }).then((postResponse) => {
        this.checkBotResponse(postResponse);
      });
    });
  }

  checkBotResponse(response) {
    //Check Request
    expect(response.body).to.have.keys('code', 'contactEntity', 'data', 'error', 'message');
    expect(response.body.code).to.eq(200);
    expect(response.body.error).to.be.null;
    expect(response.body.serializedForm).not.to.be.null;
    expect(response.body.message.key).to.eq("contact.form.post.processing._success");
  }

  formPostWithCorrectRequestShouldPostForm(cy) {
    const host = Cypress.env('host') || Cypress.config('host'),
      conf = this.conf.api.form.post;

    cy.request({
      method: "GET",
      url: host + conf.successUrl
    }).then((readResponse) => {
      let requestBody = this.buildRequestFromSerializedForm(readResponse.body.serializedForm, false);
      console.log(requestBody);
      cy.request({
        method: "POST",
        body: requestBody,
        url: host + conf.successUrl
      }).then((postResponse) => {
        this.checkValidPostResponse(postResponse);
      });
    });
  }

  buildRequestFromSerializedForm(serializedForm, forceBot) {
    let data = {
      "text": "Test input",
      "textarea": "Test Textarea",
      "email": "test@wonderful.fr"
    };
    if (forceBot) {
      data.email = 'test@cypress.bot';
    }
    let requestBody = {}

    for (let i in serializedForm.instance.fields) {
      let thisField = serializedForm.instance.fields[i],
        thisFieldName = thisField.name

      //prevent bot check
      if (!forceBot && thisFieldName === 'raison_societe_name') {
        continue;
      }

      switch (thisField.tag) {
        case'input':

          switch (thisField.type) {
            case'checkbox':
              //todo : fill checkbox
              break;
            case'radio':
              //todo : fill radio
              break;
            default:
              requestBody[thisFieldName] = data[thisField.type];
              break;
          }

          break;
        case'textarea':
          requestBody[thisFieldName] = data[thisField.tag];
          break;
        case'select':
          //todo : fill select
          break;
      }
    }
    return requestBody;
  }

  checkValidPostResponse(response) {
    //Check Request
    expect(response.body).to.have.keys('code', 'contactEntity', 'data', 'error', 'message');
    expect(response.body.code).to.eq(200);
    expect(response.body.error).to.be.null;
    expect(response.body.contactEntity).not.to.be.null;
    expect(response.body.message.key).to.eq("contact.form.post.processing.success");
    //todo : check entity id if form should save entities
    //todo : check admin email if form should send amin email
    //todo : check customer email id if form should send customer emails
  }


}
