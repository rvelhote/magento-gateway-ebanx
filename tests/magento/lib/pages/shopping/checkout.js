import R from 'ramda';
import { CHECKOUT_SCHEMA } from '../../schemas/checkout';
import { validateSchema } from '../../../../utils';

const fillCity = Symbol('fillCity');
const fillInput = Symbol('fillInput');
const fillState = Symbol('fillState');
const fillPhone = Symbol('fillPhone');
const fillEmail = Symbol('fillEmail');
const placeOrder = Symbol('placeOrder');
const selectField = Symbol('selectField');
const fillBilling = Symbol('fillBilling');
const fillAddress = Symbol('fillAddress');
const clickElement = Symbol('clickElement');
const fillPostcode = Symbol('fillPostcode');
const fillLastName = Symbol('fillLastName');
const selectCountry = Symbol('selectCountry');
const fillFirstName = Symbol('fillFirstName');
const chooseShipping = Symbol('chooseShipping');
const fillInputWithJquery = Symbol('fillInputWithJquery');

export default class Checkout {
  constructor(cy) {
    this.cy = cy;
    this.inputs = {
      creditCardNumber: (country) => `#ebanx_cc_${country}_cc_number`,
    };
  }

  [fillInputWithJquery] (data, property, input) {
    R.ifElse(
      R.propSatisfies((x) => (x !== undefined), property), (data) => {
        this.cy
          .get(input, { timeout: 15000 })
          .should('be.visible')
          .then(($input) => {
            $input.val(data[property]).trigger('input');
          })
          .get(input)
          .should('have.value', data[property]);
      },
      R.always(null)
    )(data);
  }

  [fillInput] (data, property, input) {
    R.ifElse(
      R.propSatisfies((x) => (x !== undefined), property), (data) => {
        this.cy
          .get(input, { timeout: 15000 })
          .should('be.visible')
          .type(data[property])
          .get(input)
          .should('have.value', data[property]);
      },
      R.always(null)
    )(data);
  }

  [fillFirstName] (data) {
    this[fillInput](data, 'firstName', '#billing\\3a firstname');
  }

  [fillLastName] (data) {
    this[fillInput](data, 'lastName', '#billing\\3a lastname');
  }

  [fillAddress] (data) {
    this[fillInput](data, 'address', '#billing\\3a street1');
  }

  [fillCity] (data) {
    this[fillInput](data, 'city', '#billing\\3a city');
  }

  [fillState] (data) {
    this[fillInput](data, 'state', '#billing\\3a region');
  }

  [fillPostcode] (data) {
    this[fillInput](data, 'zipcode', '#billing\\3a postcode');
  }

  [fillPhone] (data) {
    this[fillInput](data, 'phone', '#billing\\3a telephone');
  }

  [fillEmail] (data) {
    this[fillInput](data, 'email', '#billing\\3a email');
  }

  [selectField] (data, property, propertyId, input) {
    R.ifElse(
      R.propSatisfies((x) => (x !== undefined), property), (data) => {
        this.cy
          .get(input, { timeout: 15000 })
          .should('be.visible')
          .select(data[property])
          .get(input)
          .should('have.value', data[propertyId]);
      },
      R.always(null)
    )(data);
  }

  [selectCountry] (data) {
    this[selectField](data, 'country', 'countryId', '#billing\\3a country_id');
  }

  [clickElement] (element) {
    this.cy
      .get(element, { timeout: 15000 })
      .should('be.visible')
      .click();
  }

  [placeOrder] () {
    this[clickElement]('#payment-buttons-container > button');
    this[clickElement]('#review-buttons-container > button');
  }

  [chooseShipping] (method) {
    this[clickElement](`#s_method_${method || 'flatrate'}_${method || 'flatrate'}`);
    this[clickElement]('#shipping-method-buttons-container > button');
  }

  [fillBilling] (data) {
    this[clickElement]('#onepage-guest-register-button');

    this[selectCountry](data);
    this[fillFirstName](data);
    this[fillLastName](data);
    this[fillEmail](data);
    this[fillAddress](data);
    this[fillPostcode](data);
    this[fillCity](data);
    this[fillState](data);
    this[fillPhone](data);

    this[clickElement]('#billing-buttons-container > button');
  }

  placeWithBoleto(data, next) {
    validateSchema(CHECKOUT_SCHEMA.br.boleto(), data, () => {
      this[fillBilling](data);
      this[chooseShipping](data.shippingMethod);
      this[clickElement]('#p_method_ebanx_boleto');
      this[fillInputWithJquery](data, 'document', '#ebanx-document-ebanx_boleto');
      this[placeOrder]();

      next();
    });
  }
}
