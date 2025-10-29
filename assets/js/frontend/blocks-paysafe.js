/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "@woocommerce/blocks-registry":
/*!******************************************!*\
  !*** external ["wc","wcBlocksRegistry"] ***!
  \******************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcBlocksRegistry"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/html-entities":
/*!**************************************!*\
  !*** external ["wp","htmlEntities"] ***!
  \**************************************/
/***/ ((module) => {

module.exports = window["wp"]["htmlEntities"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!******************************************!*\
  !*** ./resources/js/frontend/paysafe.js ***!
  \******************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/blocks-registry */ "@woocommerce/blocks-registry");
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);






const paymentMethodId = "paysafe";
const defaultLabel = 'Paysafe Checkout';
const settings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__.getSetting)('paysafe_data', {});
const log_paysafe_error = function (message, context) {
  if (settings.log_errors === true && settings.log_error_endpoint) {
    fetch(settings.log_error_endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: new Headers({
        'Content-Type': 'application/json'
      }),
      body: JSON.stringify({
        message: message,
        context: context
      })
    }).catch(error => {});
  }
};
const is_hosted_integration = settings.integration_type === 'paysafe_js';
let is_cvv_verification = !!settings.cvv_verification;
const label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings.title) || defaultLabel;
const icons = [{
  id: 'paysafe-icon',
  src: settings.icon,
  alt: label
}];
const Label = () => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'paysafe-gateway-title'
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    className: 'paysafe-gateway-image',
    src: settings.icon,
    alt: label
  }));
};
let paymentAmount = 0;
let currentCartData = {};
let next_page = null;
const show_checkout_error = function (error_message, dont_clear_messages) {
  let woo_errors = document.getElementsByClassName('woocommerce-error');
  woo_errors = woo_errors.length > 0 ? woo_errors[0] : null;
  if (!woo_errors) {
    let notice_wrappers = document.getElementsByClassName('woocommerce-notices-wrapper');
    let notice_wrapper = notice_wrappers.length > 0 ? notice_wrappers[0] : null;
    if (notice_wrapper) {
      woo_errors = document.createElement('ul');
      woo_errors.className = 'woocommerce-error';
      notice_wrapper.appendChild(woo_errors);
    }
  }
  if (woo_errors) {
    let error_li = document.getElementById('paysafe-fatal-error-message');
    if (!error_li || !!dont_clear_messages) {
      error_li = document.createElement('li');
      error_li.setAttribute('id', 'paysafe-fatal-error-message');
      error_li.innerHTML = error_message;
      woo_errors.appendChild(error_li);
      window.scrollTo({
        top: 0,
        left: 0,
        behavior: "smooth"
      });
    } else {
      error_li.innerHTML = error_message;
    }
  }
};
let hostedPaymentInstance;
const init_paysafe_hosted_integration = function () {
  // if the page doesn't have the form, don't load it
  if (!jQuery('#paysafe-hosted-payment-form').length) {
    return;
  }

  // reset the CVV field initialization for saved cards
  cvv_form_initialized = false;
  const paysafeOptions = {
    // You must provide currencyCode to the Paysafe JS SDK to enable the Payment API integration
    currencyCode: settings.currency_code,
    // select the Paysafe test / sandbox environment
    environment: settings.test_mode ? 'TEST' : 'LIVE',
    transactionSource: 'WooCommerceJs',
    // set the CSS selectors to identify the payment field divs above
    // set the placeholder text to display in these fields
    fields: {
      cardNumber: {
        selector: '#cardNumber',
        placeholder: 'Card number',
        separator: ' '
      },
      expiryDate: {
        selector: '#expiryDate',
        placeholder: 'MM/YY'
      },
      cvv: {
        selector: '#cvv',
        placeholder: 'CVV',
        optional: false
      }
    },
    style: {
      input: {
        "font-family": "sans-serif",
        "font-weight": "normal",
        "font-size": "16px"
      }
    },
    accounts: {
      default: settings.account_id
    }
  };

  // initialize the hosted iframes using the SDK setup function
  paysafe.fields.setup(settings.authorization, paysafeOptions).then(instance => {
    hostedPaymentInstance = instance;
    return instance.show();
  }).then(paymentMethods => {
    if (paymentMethods.card && !paymentMethods.card.error) {
      // When the customer clicks Pay Now,
      // call the SDK tokenize function to create
      // a single-use payment token corresponding to the card details entered
    }
  }).catch(error => {
    // this means that the initialization of the form failed,
    // disable this payment method
    const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
    log_paysafe_error(error_message, []);
    show_checkout_error(error_message);
  });
};
let cvv_verification_instance = null;
let cvv_form_initialized = false;
const init_paysafe_cvv_verification = function () {
  // if the page doesn't have the form, don't load it
  if (!jQuery('#cvv-verification-container').length) {
    return;
  }

  // mark that the CVV initialization has been done
  cvv_form_initialized = true;
  cvv_verification_instance = null;
  const paysafe_js_options = {
    // You must provide currencyCode to the Paysafe JS SDK to enable the Payment API integration
    currencyCode: settings.currency_code,
    // select the Paysafe test / sandbox environment
    environment: settings.test_mode ? 'TEST' : 'LIVE',
    transactionSource: 'WooCommerceJs',
    fields: {
      cardNumber: {
        selector: '#cardNumber',
        placeholder: 'Card number',
        separator: ' ',
        optional: true
      },
      expiryDate: {
        selector: '#expiryDate',
        placeholder: 'MM/YY',
        optional: true
      },
      cvv: {
        selector: '#cvv',
        placeholder: 'CVV',
        optional: !is_cvv_verification
      }
    },
    accounts: {
      default: settings.account_id
    }
  };

  // if the CVV verification is not enabled, don't load it
  if (typeof paysafe === 'undefined' || typeof paysafe.fields === 'undefined') {
    return;
  }

  // initialize the hosted iframes using the SDK setup function
  paysafe.fields.setup(settings.authorization, paysafe_js_options).then(instance => {
    cvv_verification_instance = instance;
    return instance.show();
  }).then(paymentMethods => {
    if (paymentMethods.card && !paymentMethods.card.error) {
      // When the customer clicks Pay Now,
      // call the SDK tokenize function to create
      // a single-use payment token corresponding to the card details entered
    }
  }).catch(error => {
    // this means that the initialization of the form failed,
    // disable this payment method
    const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
    log_paysafe_error(error_message, []);
    if (parseInt(error.code) === 9073 && is_cvv_verification) {
      // account not configured properly
      is_cvv_verification = false;
      init_paysafe_cvv_verification();
    }
  });
};
const Content = props => {
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onCheckoutSuccess,
    onPaymentSetup
  } = eventRegistration;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(() => {
    if (is_cvv_verification) {
      // reset the CVV field initialization for saved cards
      cvv_form_initialized = false;
    }
    if (is_hosted_integration) {
      init_paysafe_hosted_integration();
    }
    const unsubscribe = onPaymentSetup(async response => {
      if (is_hosted_integration) {
        if (hostedPaymentInstance) {
          // check if the hosted integration is filled in and the data is valid
          let isTokenizationAvailable = hostedPaymentInstance.areAllFieldsValid();
          if (isTokenizationAvailable) {
            let holderNameInput = document.getElementById('holderNameInput');
            let isHolderNameValid = holderNameInput && holderNameInput.value && holderNameInput.value.length && holderNameInput.value.length >= 2 && holderNameInput.value.length <= 160;
            if (isHolderNameValid) {
              return {
                type: emitResponse.responseTypes.SUCCESS
              };
            } else {
              log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Card holder name must have between 2 and 160 characters!", "paysafe-checkout"), {
                'tokenization_stage': 2
              });
              return {
                type: emitResponse.responseTypes.ERROR,
                message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Card holder name must have between 2 and 160 characters!", "paysafe-checkout")
              };
            }
          } else {
            log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Card holder name must have between 2 and 160 characters!", "paysafe-checkout"), {
              'tokenization_stage': 2
            });
            return {
              type: emitResponse.responseTypes.ERROR,
              message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please enter your card details and try again", "paysafe-checkout")
            };
          }
        } else {
          log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed.", "paysafe-checkout"), {
            'tokenization_stage': 3
          });
          return {
            type: emitResponse.responseTypes.ERROR,
            message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please reload the page and try again", "paysafe-checkout")
          };
        }
      }
    });
    return () => {
      unsubscribe();
    };
  }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(() => {
    const unsubscribe = onCheckoutSuccess(response => {
      const orderId = response.orderId;
      const customerSingleUseToken = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.single_use_token ? response.processingResponse.paymentDetails.single_use_token : '';
      const merch_ref_num = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.merch_ref_num ? response.processingResponse.paymentDetails.merch_ref_num : '';
      let master_error = null;
      paymentAmount = parseInt(currentCartData.cartTotals.total_price);
      if (orderId && paymentAmount) {
        let currency_code = currentCartData.cartTotals.currency_code;
        let customer_first_name = currentCartData.billingAddress.first_name;
        let customer_last_name = currentCartData.billingAddress.last_name;
        let customer_email = currentCartData.billingAddress.email;
        let billing_street = currentCartData.billingAddress.address_1;
        let billing_street2 = currentCartData.billingAddress.address_2;
        let billing_city = currentCartData.billingAddress.city;
        let billing_zip = currentCartData.billingAddress.postcode;
        let billing_state = currentCartData.billingAddress.state;
        let billing_country = currentCartData.billingAddress.country;
        let billing_phone = currentCartData.billingAddress.phone;
        if (is_hosted_integration) {
          let tokenizationOptions = {
            transactionSource: 'WooCommerceJs',
            amount: paymentAmount,
            transactionType: 'PAYMENT',
            currency: currency_code,
            merchantRefNum: merch_ref_num,
            environment: settings.test_mode ? 'TEST' : 'LIVE',
            threeDs: {
              merchantUrl: settings.checkout_url,
              deviceChannel: "BROWSER",
              messageCategory: "PAYMENT",
              authenticationPurpose: "PAYMENT_TRANSACTION",
              transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
              profile: {
                email: settings.user_email
              }
            },
            paymentType: 'CARD',
            accountId: settings.account_id
          };
          if (settings.merchant_descriptor && settings.merchant_phone) {
            tokenizationOptions.merchantDescriptor = {
              dynamicDescriptor: settings.merchant_descriptor,
              phone: settings.merchant_phone
            };
          }
          tokenizationOptions.customerDetails = {
            holderName: customer_first_name + ' ' + customer_last_name,
            billingDetails: {
              nickName: "Home",
              zip: billing_zip,
              country: billing_country
            },
            profile: {
              firstName: customer_first_name,
              lastName: customer_last_name,
              email: customer_email,
              locale: settings.locale,
              phone: billing_phone
            }
          };
          let holderNameInput = document.getElementById('holderNameInput');
          let holderName = holderNameInput && holderNameInput.value && holderNameInput.value.length && holderNameInput.value.length >= 2 && holderNameInput.value.length <= 160 ? holderNameInput.value : null;
          if (holderName) {
            tokenizationOptions.customerDetails.holderName = holderName;
          }
          if (billing_street) {
            tokenizationOptions.customerDetails.billingDetails.street = billing_street;
          }
          if (billing_street2) {
            tokenizationOptions.customerDetails.billingDetails.street2 = billing_street2;
          }
          if (billing_city) {
            tokenizationOptions.customerDetails.billingDetails.city = billing_city;
          }
          if (billing_state) {
            tokenizationOptions.customerDetails.billingDetails.state = billing_state;
          }
          if (billing_phone) {
            tokenizationOptions.customerDetails.billingDetails.phone = billing_phone;
          }
          hostedPaymentInstance.tokenize(tokenizationOptions).then(result => {
            if (!result || !result.token) {
              throw 'Result failure';
            }
            const paymentData = {
              orderId: orderId,
              paymentMethod: 'CARD',
              transactionType: 'PAYMENT',
              paymentHandleToken: result.token,
              amount: paymentAmount,
              customerOperation: '',
              merchantRefNum: merch_ref_num
            };
            let save_card_checkbox_checked = false;
            if (is_hosted_integration) {
              let save_card_checkbox = document.getElementById('paysafe_hosted_save_card');
              if (save_card_checkbox && save_card_checkbox.checked) {
                save_card_checkbox_checked = true;
              }
            }
            if (save_card_checkbox_checked) {
              paymentData.save_card = true;
            }
            fetch(settings.register_url, {
              method: 'POST',
              credentials: 'same-origin',
              headers: new Headers({
                'Content-Type': 'application/json'
              }),
              body: JSON.stringify(paymentData)
            }).then(result => {
              return result.json();
            }).then(json => {
              window.location = json.redirect_url;
            }).catch(error => {
              // the BE call failed
              const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
              log_paysafe_error(error_message, []);
              show_checkout_error(error_message);
              show_checkout_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout"), true);
            });
          }).catch(error => {
            // this means that the tokenization of the card form failed,
            // disable this payment method, show the error and refresh the page in 15 seconds
            const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
            log_paysafe_error(error_message, []);
            show_checkout_error(error_message);
            show_checkout_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('The payment process failed. Please reload the page and try again', 'paysafe-checkout'), true);
          });
        } else {
          const checkout_options = {
            transactionSource: 'WooCommerceCheckout',
            amount: paymentAmount,
            transactionType: 'PAYMENT',
            currency: currency_code,
            merchantRefNum: merch_ref_num,
            environment: settings.test_mode ? 'TEST' : 'LIVE',
            displayPaymentMethods: ['card'],
            paymentMethodDetails: {
              card: {
                accountId: settings.account_id
              }
            },
            threeDs: {
              merchantUrl: settings.checkout_url,
              deviceChannel: "BROWSER",
              messageCategory: "PAYMENT",
              authenticationPurpose: "PAYMENT_TRANSACTION",
              transactionIntent: "GOODS_OR_SERVICE_PURCHASE"
            },
            locale: settings.locale
          };
          if (settings.merchant_descriptor && settings.merchant_phone) {
            checkout_options.merchantDescriptor = {
              dynamicDescriptor: settings.merchant_descriptor,
              phone: settings.merchant_phone
            };
          }
          if (customerSingleUseToken) {
            checkout_options.singleUseCustomerToken = customerSingleUseToken;
          } else {
            checkout_options.customer = {
              firstName: customer_first_name,
              lastName: customer_last_name,
              email: customer_email
            };
            checkout_options.billingAddress = {
              nickName: "Home",
              zip: billing_zip,
              country: billing_country
            };
            if (billing_street) {
              checkout_options.billingAddress.street = billing_street;
            }
            if (billing_street2) {
              checkout_options.billingAddress.street2 = billing_street2;
            }
            if (billing_city) {
              checkout_options.billingAddress.city = billing_city;
            }
            if (billing_state) {
              checkout_options.billingAddress.state = billing_state;
            }
          }
          paysafe.checkout.setup(settings.authorization, checkout_options,
          // resultCallback
          function (instance, error, result) {
            if (result && result.paymentHandleToken) {
              // Successfully Tokenized transaction, use result.paymentHandleToken to process a payment
              // add AJAX code to send token to your merchant server

              const paymentData = {
                orderId: orderId,
                paymentMethod: result.paymentMethod,
                transactionType: result.transactionType,
                paymentHandleToken: result.paymentHandleToken,
                amount: result.amount,
                customerOperation: result.customerOperation,
                merchantRefNum: checkout_options.merchantRefNum
              };
              fetch(settings.register_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: new Headers({
                  'Content-Type': 'application/json'
                }),
                body: JSON.stringify(paymentData)
              }).then(result => {
                return result.json();
              }).then(json => {
                if (json.status === 'success') {
                  instance.showSuccessScreen((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Your goods are now purchased. Expect them to be delivered in next 5 business days.", "paysafe-checkout"));
                } else {
                  log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Payment failed. Popup was closed without a correct end message!", "paysafe-checkout"), {
                    'pace': 1
                  });
                  instance.showFailureScreen((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
                }
                next_page = json.redirect_url;
              }).catch(error => {
                // the BE call failed
                log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Payment failed. Popup was closed without a correct end message!", "paysafe-checkout"), {
                  'pace': 2
                });
                instance.showFailureScreen((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
              });
            } else {
              let errorMessage = '';
              if (error) {
                if (error.code) {
                  errorMessage += error.code + ' ';
                }
                if (error.message) {
                  errorMessage += error.message + ' ';
                }
                if (error.detailedMessage) {
                  errorMessage += error.detailedMessage;
                }
              }

              // Tokenization failed and Payment Handled moved to failed Status
              if (instance) {
                log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Payment failed. Popup was closed without a correct end message!", "paysafe-checkout") + ' ' + errorMessage, {
                  'pace': 3
                });
                instance.showFailureScreen((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment was declined. Please, try again with the same or another payment method." + ' ' + errorMessage, "paysafe-checkout"));
              } else {
                master_error = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('The payment was declined. Please, try again with the same or another payment method.', "paysafe-checkout");
                if (error && error.code && error.detailedMessage) {
                  master_error += "<br />" + ' ' + error.code + ' ' + error.detailedMessage;
                }
                log_paysafe_error(master_error, {
                  'pace': 4
                });
                show_checkout_error(master_error);
              }
            }
          },
          // closeCallback
          function (stage, expired) {
            if (stage) {
              // Depending upon the stage take different actions
              switch (stage) {
                case "PAYMENT_HANDLE_NOT_CREATED":
                  // don't show any errors,
                  // as the customer choose to close the popup window
                  log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Payment failed. Popup was closed without a correct end message!', 'paysafe-checkout'), {
                    'stage': stage,
                    'expired': expired
                  });
                  window.location.reload();
                  break;
                case "PAYMENT_HANDLE_CREATED":
                case "PAYMENT_HANDLE_REDIRECT":
                case "PAYMENT_HANDLE_PAYABLE":
                  // in all these cases the user closed the popup after the payment
                  if (null !== next_page) {
                    window.location = next_page;
                    return;
                  }

                  // reload the cart page, something must have happened
                  window.location.reload();
                  break;
                default:
              }
            } else {
              // Add action in case Checkout is expired
              // the popup expired,
              // lets reload the page so that the customer
              // can try another payment option
              log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Payment failed. Popup was closed without a correct end message!', 'paysafe-checkout'), {
                'stage': stage,
                'expired': expired
              });
              window.location.reload();
            }
          },
          // riskCallback
          function (instance, amount, paymentMethod) {
            if (amount === paymentAmount) {
              instance.accept();
            } else {
              log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Amount is not the value expected", "paysafe-checkout"));
              instance.decline((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Amount is not the value expected", "paysafe-checkout"));
            }
          });
        }
      } else {
        log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Payment failed. Popup was closed without a correct end message!", "paysafe-checkout"), {
          'pace': 5
        });
        return {
          type: emitResponse.responseTypes.ERROR,
          message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout")
        };
      }
      return response;
    });
    return () => {
      unsubscribe();
    };
  }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, '', [], onCheckoutSuccess]);
  let descriptionForDisplay = [];
  if (settings.description) {
    descriptionForDisplay.push((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings.description || '')));
  }
  if (settings.description && settings.test_mode) {
    descriptionForDisplay.push((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("hr", null));
  }
  if (settings.test_mode) {
    descriptionForDisplay.push((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      style: {
        textAlign: "justify",
        marginTop: "5px"
      }
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Paysafe Checkout is in TEST MODE. Use the test Visa card 4000000000001091 with any expiry date, CVC, email, or OTP token. Important notice: Please use only TEST CARDS for testing. You can find other test cards ', 'paysafe-checkout')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "https://developer.paysafe.com/en/api-docs/cards/test-and-go-live/test-cards/",
      target: "_blank"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('here. ', 'paysafe-checkout'), " ")));
  }
  if (settings.test_mode && is_hosted_integration) {
    descriptionForDisplay.push((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null));
  }
  if (is_hosted_integration) {
    descriptionForDisplay.push(wp.element.RawHTML({
      children: settings.integration_hosted_form
    }));
  } else {
    if (settings.is_subscription_payment) {
      descriptionForDisplay.push((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
        style: {
          fontSize: "0.8em",
          marginBottom: "0"
        }
      }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("b", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Note:', 'paysafe-checkout')), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('This order includes a subscription.', 'paysafe-checkout'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('By proceeding with payment, your card details will be securely saved for future recurring payments.', 'paysafe-checkout'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card data is stored and processed securely by Paysafe in compliance with PCI DSS standards and according to the merchant’s', 'paysafe-checkout'), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Terms and Conditions', 'paysafe-checkout'), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('and', 'paysafe-checkout'), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Privacy Policy.', 'paysafe-checkout')));
    }
  }
  return descriptionForDisplay;
};
const SavedTokenComponent = props => {
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onCheckoutSuccess,
    onPaymentSetup
  } = eventRegistration;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(() => {
    if (!cvv_form_initialized) {
      cvv_form_initialized = true;
      init_paysafe_cvv_verification();
    }
    const unsubscribe = onPaymentSetup(() => {
      if (is_cvv_verification) {
        if (cvv_verification_instance) {
          // check if the CVV verification is filled in and the data is valid
          let is_tokenization_available = cvv_verification_instance.areAllFieldsValid();
          if (!is_tokenization_available) {
            log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. No CVV entered", "paysafe-checkout"), {
              'tokenization_stage': 2
            });
            return {
              type: emitResponse.responseTypes.ERROR,
              message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please enter your saved card CVV and try again", "paysafe-checkout")
            };
          }
        } else {
          log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed.", "paysafe-checkout"), {
            'tokenization_stage': 3
          });
          return {
            type: emitResponse.responseTypes.ERROR,
            message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please reload the page and try again", "paysafe-checkout")
          };
        }
      }
    });
    return () => {
      unsubscribe();
    };
  }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(() => {
    const unsubscribe = onCheckoutSuccess(response => {
      const orderId = response.orderId;
      paymentAmount = parseInt(currentCartData.cartTotals.total_price);
      if (orderId && paymentAmount) {
        const single_use_token = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.single_use_token ? response.processingResponse.paymentDetails.single_use_token : '';
        const paysafe_token = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.paysafe_token ? response.processingResponse.paymentDetails.paysafe_token : '';
        const merch_ref_num = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.merch_ref_num ? response.processingResponse.paymentDetails.merch_ref_num : '';
        const redirect_url = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.redirect ? response.processingResponse.paymentDetails.redirect : null;

        // in case of BE token payments, we are done at this point
        // redirect to success page
        if (redirect_url) {
          window.location = redirect_url;
          return {
            type: emitResponse.responseTypes.SUCCESS
          };
        }
        let currency_code = currentCartData.cartTotals.currency_code;
        let customer_first_name = currentCartData.billingAddress.first_name;
        let customer_last_name = currentCartData.billingAddress.last_name;
        let customer_email = currentCartData.billingAddress.email;
        let billing_street = currentCartData.billingAddress.address_1;
        let billing_street2 = currentCartData.billingAddress.address_2;
        let billing_city = currentCartData.billingAddress.city;
        let billing_zip = currentCartData.billingAddress.postcode;
        let billing_state = currentCartData.billingAddress.state;
        let billing_country = currentCartData.billingAddress.country;
        let billing_phone = currentCartData.billingAddress.phone;
        let tokenization_options = {
          transactionSource: 'WooCommerceJs',
          amount: paymentAmount,
          transactionType: 'PAYMENT',
          currency: currency_code,
          merchantRefNum: merch_ref_num,
          environment: settings.test_mode ? 'TEST' : 'LIVE',
          threeDs: {
            merchantUrl: settings.checkout_url,
            deviceChannel: "BROWSER",
            messageCategory: "PAYMENT",
            authenticationPurpose: "PAYMENT_TRANSACTION",
            transactionIntent: "GOODS_OR_SERVICE_PURCHASE",
            profile: {
              email: settings.user_email
            }
          },
          paymentType: 'CARD',
          accountId: settings.account_id,
          singleUseCustomerToken: single_use_token,
          paymentTokenFrom: paysafe_token
        };
        if (settings.merchant_descriptor && settings.merchant_phone) {
          tokenization_options.merchantDescriptor = {
            dynamicDescriptor: settings.merchant_descriptor,
            phone: settings.merchant_phone
          };
        }
        tokenization_options.customerDetails = {
          holderName: customer_first_name + ' ' + customer_last_name,
          billingDetails: {
            nickName: "Home",
            zip: billing_zip,
            country: billing_country
          },
          profile: {
            firstName: customer_first_name,
            lastName: customer_last_name,
            email: customer_email,
            locale: settings.locale,
            phone: billing_phone
          }
        };
        if (billing_street) {
          tokenization_options.customerDetails.billingDetails.street = billing_street;
        }
        if (billing_street2) {
          tokenization_options.customerDetails.billingDetails.street2 = billing_street2;
        }
        if (billing_city) {
          tokenization_options.customerDetails.billingDetails.city = billing_city;
        }
        if (billing_state) {
          tokenization_options.customerDetails.billingDetails.state = billing_state;
        }
        if (billing_phone) {
          tokenization_options.customerDetails.billingDetails.phone = billing_phone;
        }
        cvv_verification_instance.tokenize(tokenization_options).then(result => {
          if (!result || !result.token) {
            throw 'Result failure';
          }
          const paymentData = {
            orderId: orderId,
            paymentMethod: 'CARD',
            transactionType: 'PAYMENT',
            paymentHandleToken: result.token,
            amount: paymentAmount,
            customerOperation: '',
            merchantRefNum: merch_ref_num
          };
          fetch(settings.register_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: new Headers({
              'Content-Type': 'application/json'
            }),
            body: JSON.stringify(paymentData)
          }).then(result => {
            return result.json();
          }).then(json => {
            if (json.error_message) {
              // the BE call failed
              log_paysafe_error(json.error_message, {
                'tokenization_stage': 1
              });
              show_checkout_error(json.error_message);
              show_checkout_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('The payment process failed. Please reload the page and try again', 'paysafe-checkout'), true);
            } else {
              window.location = json.redirect_url;
            }
          }).catch(error => {
            // the BE call failed
            log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed.", "paysafe-checkout"), {
              'tokenization_stage': 1
            });
            show_checkout_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
          });
        }).catch(error => {
          // this means that the tokenization of the card form failed,
          // disable this payment method, show the error and refresh the page in 15 seconds
          const error_message = 'ERROR ' + error.code + ': ' + error.detailedMessage;
          log_paysafe_error(error_message, []);
          show_checkout_error(error_message);
          show_checkout_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('The payment process failed. Please reload the page and try again', 'paysafe-checkout'), true);
        });
      }
      return response;
    });
    return () => {
      unsubscribe();
    };
  }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, '', [], onCheckoutSuccess]);
  if (is_cvv_verification && typeof paysafe !== 'undefined' && typeof paysafe.fields !== 'undefined') {
    return [(0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      id: "cvv-verification-container"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("b", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('CVV', 'paysafe-checkout')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      id: "holderName",
      className: "paysafe-input-field optional-field",
      style: {
        display: "none"
      }
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      id: "cardNumber",
      className: "paysafe-input-field optional-field",
      style: {
        display: "none"
      }
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      id: "expiryDate",
      className: "paysafe-input-field optional-field",
      style: {
        display: "none"
      }
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "paysafe-cc-form-exp-cvv-row"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "paysafe-cc-form-exp-cvv-box1"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "paysafe-cc-form-exp-cvv-box2"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      id: "cvv",
      className: "paysafe-input-field"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "paysafe-cc-form-exp-cvv-box3"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
      className: "paysafe-cc-form-cvv-image",
      src: settings.paysafe_base_url + "assets/img/cvv.svg",
      alt: "CVV"
    }))))];
  }
  return [(0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "cvv-verification-container"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "holderName",
    className: "paysafe-input-field optional-field"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "cardNumber",
    className: "paysafe-input-field optional-field"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "expiryDate",
    className: "paysafe-input-field optional-field"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "cvv",
    className: "paysafe-input-field optional-field"
  }))];
};
const PaysafePayments = {
  name: paymentMethodId,
  paymentMethodId: paymentMethodId,
  label: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Label, null),
  content: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Content, null),
  edit: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Content, null),
  savedTokenComponent: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(SavedTokenComponent, null),
  canMakePayment: cartData => {
    currentCartData = cartData;
    return true;
  },
  ariaLabel: label,
  supports: {
    features: settings.supports
  }
};
(0,_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__.registerPaymentMethod)(PaysafePayments);
})();

/******/ })()
;
//# sourceMappingURL=blocks-paysafe.js.map